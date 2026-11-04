<?php
// API_SYSTEM/login.php — verify credentials and redirect by user_type
declare(strict_types=1);

require_once __DIR__ . '/../DB_Conn/config.php';
require_once __DIR__ . '/auth.php';

/* ============================================================
   1) BOOTSTRAP — headers, session, method guard
   ============================================================ */
send_json_headers();
start_secure_session();

if (!is_post_request()) {
    json_error(405, 'Method not allowed.');
}

$correlationId = bin2hex(random_bytes(8));

/* ============================================================
   2) CSRF
   ============================================================ */
if (!verify_csrf_token($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
    audit('login_csrf_fail', ['cid' => $correlationId]);
    json_error(403, 'Invalid security token. Refresh and try again.');
}

/* ============================================================
   3) RATE LIMIT — per-account+IP, per-IP, per-network
   ============================================================ */
$ip    = client_ip();
$net   = ip_network($ip);
$rlWin = 600;

$accountNumber = trim((string) ($_POST['account_number'] ?? ''));
$pinPlain      = trim((string) ($_POST['pin'] ?? ''));

$accountBucket = 'login_acct_' . md5(strtolower($accountNumber) . '|' . $ip);

if (
    session_rate_limit($accountBucket, 5, $rlWin) === false
    || db_rate_limit($pdo, 'login_ip',  $ip,  15, $rlWin) === false
    || db_rate_limit($pdo, 'login_net', $net, 40, $rlWin) === false
) {
    audit('login_rate_limit', ['cid' => $correlationId, 'ip' => $ip]);
    json_error(429, 'Masyadong maraming pagsubok. Subukan muli mamaya.');
}
record_session_attempt($accountBucket);

/* ============================================================
   4) INPUT VALIDATION
   ============================================================ */
if ($accountNumber === '' || $pinPlain === '') {
    json_error(400, 'Ilagay ang account number at PIN.');
}

if (!is_valid_account_format($accountNumber) || !is_valid_pin_format($pinPlain)) {
    auth_fail($correlationId);
}

/* ============================================================
   5) USER LOOKUP across all 4 tables
   ============================================================ */
$found = find_user_by_account($pdo, $accountNumber, $correlationId);

if ($found === null) {
    burn_bcrypt_time($pinPlain);
    auth_fail($correlationId);
}

[$user, $table, $type] = $found;

/* ============================================================
   6) PIN VERIFY (constant-time whether hash exists or not)
   ============================================================ */
if (!verify_pin($pinPlain, (string) ($user['pin'] ?? ''))) {
    auth_fail($correlationId);
}

/* Opportunistic rehash — non-fatal */
maybe_rehash_pin($pdo, $table, $accountNumber, $pinPlain, $correlationId);

/* ============================================================
   7) USER TYPE RESOLUTION
   ============================================================ */
$userType = resolve_user_type($user, $type);

if ($userType === null) {
    auth_fail($correlationId, 'Hindi wastong uri ng account.');
}

/* ============================================================
   8) REDIRECT DECISION
   ============================================================ */
$redirect = resolve_redirect($userType, $user);

/* ============================================================
   9) SESSION COMMIT — regenerate + write user data + rotate CSRF
   ============================================================ */
commit_login_session($user, $userType, $accountBucket);

/* ============================================================
   10) RESPONSE
   ============================================================ */
audit('login_success', [
    'cid'     => $correlationId,
    'ip'      => $ip,
    'type'    => $userType,
    'account' => $user['account_number'],
]);

json_ok([
    'message'  => 'Matagumpay ang pag-login!',
    'redirect' => $redirect,
    'user'     => [
        'account_number' => $user['account_number'],
        'full_name'      => $_SESSION['full_name'],
        'user_type'      => $userType,
    ],
]);

/* ============================================================
   HELPERS — single responsibility, easy to unit-test
   ============================================================ */

/* ---------- Format checks ---------- */
function is_valid_account_format(string $account): bool
{
    if (strlen($account) > 64) return false;
    
    // CHANGED: Updated regex to accept both formats.
    // Format 1 (Student/Teacher): 26ENHS-123 
    // Format 2 (Old/Staff/Admin): 26ENHS_T123
    return (bool) preg_match('/^26[A-Z]{1,8}(?:-\d{1,3}|_[A-Z]\d{1,10})$/', $account);
}

function is_valid_pin_format(string $pin): bool
{
    return (bool) preg_match('/^\d{6}$/', $pin);
}

/* ---------- User lookup ---------- */
/**
 * Returns [row, tableName, typeKey] or null.
 */
function find_user_by_account(PDO $pdo, string $account, string $cid): ?array
{
    $tables = [
        'student' => 'students',
        'teacher' => 'teachers',
        'staff'   => 'faculties_staff',
        'admin'   => 'admins',
    ];

    foreach ($tables as $type => $table) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE account_number = ? LIMIT 1");
            $stmt->execute([$account]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return [$row, $table, $type];
            }
        } catch (PDOException $e) {
            error_log("login lookup failed on {$table} [{$cid}]: " . $e->getMessage());
        }
    }
    return null;
}

/* ---------- PIN verification ---------- */
function verify_pin(string $pin, string $storedHash): bool
{
    if ($storedHash === '') {
        burn_bcrypt_time($pin);
        return false;
    }
    return password_verify($pin, $storedHash);
}

/**
 * Burn the same time as a real bcrypt check so timing doesn't leak
 * whether the account exists / has a hash.
 */
function burn_bcrypt_time(string $pin): void
{
    password_verify($pin, '$2y$10$usesomesillystringforeverysinglelogincheck');
}

function maybe_rehash_pin(PDO $pdo, string $table, string $account, string $pin, string $cid): void
{
    try {
        $stmt = $pdo->prepare("SELECT pin FROM `{$table}` WHERE account_number = ? LIMIT 1");
        $stmt->execute([$account]);
        $hash = (string) ($stmt->fetchColumn() ?: '');
        if ($hash !== '' && password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $newHash = password_hash($pin, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE `{$table}` SET pin = ? WHERE account_number = ?")
                ->execute([$newHash, $account]);
        }
    } catch (PDOException $e) {
        error_log("login rehash failed [{$cid}]: " . $e->getMessage());
    }
}

/* ---------- User type + redirect ---------- */
function resolve_user_type(array $user, string $fallbackType): ?string
{
    $type = strtolower(trim((string) ($user['user_type'] ?? '')));
    if ($type === '') {
        $type = $fallbackType;
    }
    return in_array($type, ['student', 'teacher', 'staff', 'admin'], true) ? $type : null;
}

function resolve_redirect(string $userType, array $user): string
{
    if ($userType === 'student') {
        $status = strtolower(trim((string) ($user['enrollment_status'] ?? 'not enrolled')));
        return $status === 'enrolled'
            ? 'academic-information.php'
            : 'student-enrollment.php';
    }
    if ($userType === 'admin')   return 'admin/dashboard.php';
    if ($userType === 'teacher') return 'teacher/advisory-class.php';
    return 'staff/home.php';
}

/* ---------- Session ---------- */
function commit_login_session(array $user, string $userType, string $accountBucket): void
{
    session_regenerate_id(true);

    // Rotate CSRF on login
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $_SESSION['user_id']        = (int) ($user['id'] ?? 0);
    $_SESSION['account_number'] = (string) $user['account_number'];
    $_SESSION['user_type']      = $userType;
    $_SESSION['full_name']      = trim(
        ($user['first_name'] ?? '') . ' ' .
        ($user['middle_name'] ?? '') . ' ' .
        ($user['surname'] ?? '')
    );
    $_SESSION['logged_in_at']  = time();
    $_SESSION['last_activity'] = time();

    unset($_SESSION[$accountBucket]);
}

/* ---------- Failure helper ---------- */
function auth_fail(string $correlationId, string $message = 'Mali ang account number o PIN.'): void
{
    audit('login_fail', ['cid' => $correlationId]);
    json_error(401, $message);
}