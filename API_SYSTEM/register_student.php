<?php
// API_SYSTEM/register_student.php — student registration
// Account number format: 26<INITIALS>-<1 to 500> (e.g. 26ENHS-45)
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
    audit('csrf_fail', ['cid' => $correlationId]);
    json_error(403, 'Invalid security token. Refresh and try again.');
}

/* ============================================================
   3) RATE LIMIT — session + per-IP + per-network
   ============================================================ */
$ip = client_ip();
$net = ip_network($ip);
$rlWin = 600;

if (
    session_rate_limit('reg_student', 5, $rlWin) === false
    || db_rate_limit($pdo, 'reg_student_ip', $ip, 10, $rlWin) === false
    || db_rate_limit($pdo, 'reg_student_net', $net, 20, $rlWin) === false
) {
    audit('rate_limit', ['cid' => $correlationId, 'ip' => $ip]);
    json_error(429, 'Masyadong maraming pagsubok. Subukan muli mamaya.');
}
record_session_attempt('reg_student');

/* ============================================================
   4) INPUT
   ============================================================ */
$input = [
    'first_name' => clean_str('first_name', 60),
    'middle_name' => clean_str('middle_name', 60),
    'surname' => clean_str('surname', 60),
    'pin' => clean_str('pin', 6),
    'school_address' => clean_str('school_address', 200),
    'sex' => clean_str('sex', 10),
    'birth_date' => clean_str('birth_date', 10),
    'birth_place' => clean_str('birth_place', 200),
    'address' => clean_str('address', 500),
    'contact_number' => clean_str('contact_number', 20),
    'guardian' => clean_str('guardian', 120),
    'guardian_contact_number' => clean_str('guardian_contact_number', 20),
    'grade_level' => clean_str('grade_level', 2),
];

$studentType = strtoupper(clean_str('student_type', 1));
if ($studentType === '') {
    $studentType = strtoupper(clean_str('student_status', 1)); // legacy fallback
}
$input['student_type'] = $studentType;

/* ============================================================
   5) VALIDATION
   ============================================================ */
$errors = validate_student_input($input);
if ($errors) {
    json_error(400, 'Please fix the highlighted fields.', ['errors' => $errors]);
}

$computedAge = compute_age($input['birth_date']);

/* ============================================================
   6) SCHOOL LOOKUP + PREFIX
   ============================================================ */
$schoolName = lookup_school($pdo, $input['school_address'], $correlationId);
if ($schoolName === null) {
    json_error(400, 'Unknown school selected.', [
        'errors' => ['school_address' => 'Unknown school.'],
    ]);
}

$initials = school_initials($schoolName);
if ($initials === '') {
    json_error(400, 'Invalid school name format.', [
        'errors' => ['school_address' => 'Invalid school name format.'],
    ]);
}

// CHANGED: Prefix now uses a hyphen instead of _E
$schoolPrefix = '26' . $initials . '-';

/* ============================================================
   7) INSERT — retry loop for concurrent registrations
   ============================================================ */
$result = insert_student_with_retry($pdo, [
    'prefix' => $schoolPrefix,
    'pin_hash' => password_hash($input['pin'], PASSWORD_DEFAULT),
    'pin_text' => (int) $input['pin'],
    'first_name' => $input['first_name'],
    'middle_name' => $input['middle_name'],
    'surname' => $input['surname'],
    'age' => $computedAge,
    'sex' => $input['sex'],
    'birth_date' => $input['birth_date'],
    'birth_place' => $input['birth_place'],
    'address' => $input['address'],
    'contact' => $input['contact_number'],
    'guardian' => $input['guardian'],
    'guardian_ct' => $input['guardian_contact_number'],
    'school' => $schoolName,
    'grade' => (int) $input['grade_level'],
    'student_type' => $studentType,
], $correlationId);

if (!$result['ok']) {
    json_error($result['code'], $result['message'], ['cid' => $correlationId]);
}

/* ============================================================
   8) SUCCESS
   ============================================================ */
insert_system_update($pdo, $result['account_number'], $correlationId);

set_registration_cookie($result['account_number']);

audit('student_registered', [
    'cid' => $correlationId,
    'account_number' => $result['account_number'],
    'school' => $schoolName,
    'grade_level' => (int) $input['grade_level'],
]);

json_ok([
    'message' => 'Matagumpay ang pagrehistro!',
    'account_number' => $result['account_number'],
    'user_type' => 'student',
]);

/* ============================================================
   DOMAIN HELPERS — used only by this endpoint
   ============================================================ */

function validate_student_input(array $d): array
{
    $errors = [];
    $namePattern = "/^[\p{L}][\p{L} .'\-]{1,59}$/u";
    $phonePattern = '/^(?:\+639\d{9}|09\d{9}|9\d{9})$/';

    /* Names */
    foreach (['first_name' => 'First name', 'middle_name' => 'Middle name', 'surname' => 'Surname'] as $key => $label) {
        if ($d[$key] === '') {
            $errors[$key] = "{$label} is required.";
        } elseif (!preg_match($namePattern, $d[$key])) {
            $errors[$key] = "{$label} must be letters only (2-60 chars).";
        }
    }

    /* PIN */
    if (!preg_match('/^\d{6}$/', $d['pin'])) {
        $errors['pin'] = 'PIN must be exactly 6 digits.';
    }

    /* School */
    if ($d['school_address'] === '') {
        $errors['school_address'] = 'Please choose a school.';
    }

    /* Sex */
    if (!in_array($d['sex'], ['Male', 'Female'], true)) {
        $errors['sex'] = 'Please choose a sex.';
    }

    /* Birth date */
    if ($d['birth_date'] === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d['birth_date'])) {
        $errors['birth_date'] = 'Please provide a valid birth date.';
    } else {
        [$y, $mo, $day] = array_map('intval', explode('-', $d['birth_date']));
        if (!checkdate($mo, $day, $y)) {
            $errors['birth_date'] = 'Birth date is not valid.';
        } else {
            $bd = new DateTime($d['birth_date']);
            $age = $bd->diff(new DateTime('today'))->y;
            if ($age < 1 || $age > 120) {
                $errors['birth_date'] = 'Birth date implies an invalid age.';
            }
        }
    }

    /* Free text */
    if ($d['birth_place'] === '')
        $errors['birth_place'] = 'Birth place is required.';
    if ($d['address'] === '')
        $errors['address'] = 'Address is required.';

    /* Contact numbers */
    foreach (['contact_number' => 'Contact number', 'guardian_contact_number' => 'Guardian contact number'] as $key => $label) {
        if ($d[$key] === '') {
            $errors[$key] = "{$label} is required.";
        } elseif (!preg_match($phonePattern, preg_replace('/[\s\-]/', '', $d[$key]))) {
            $errors[$key] = 'Enter a valid PH mobile number (e.g., 09171234567).';
        }
    }

    /* Guardian name */
    if ($d['guardian'] === '') {
        $errors['guardian'] = 'Guardian name is required.';
    }

    /* Grade level */
    if ($d['grade_level'] === '' || !preg_match('/^(?:[1-9]|1[0-2])$/', $d['grade_level'])) {
        $errors['grade_level'] = 'Please choose a grade level (1–12).';
    }

    /* Student type */
    if (!in_array($d['student_type'], ['O', 'N', 'T'], true)) {
        $errors['student_type'] = 'Please choose a student type.';
    }

    return $errors;
}

function compute_age(string $birthDate): int
{
    $bd = new DateTime($birthDate);
    return $bd->diff(new DateTime('today'))->y;
}

function lookup_school(PDO $pdo, string $addr, string $cid): ?string
{
    try {
        $stmt = $pdo->prepare("SELECT school_name FROM schools WHERE school_name = ? LIMIT 1");
        $stmt->execute([$addr]);
        $name = trim((string) ($stmt->fetchColumn() ?: ''));
        return $name !== '' ? $name : null;
    } catch (PDOException $e) {
        error_log("register_student school lookup failed [{$cid}]: " . $e->getMessage());
        json_error(500, 'Database error. Try again later.', ['cid' => $cid]);
    }
}

function school_initials(string $name): string
{
    $name = preg_replace('/\x{00A0}/u', ' ', $name);
    $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY);
    $out = '';
    foreach ($words as $word) {
        $first = mb_strtoupper(mb_substr($word, 0, 1, 'UTF-8'), 'UTF-8');
        if (preg_match('/^[A-Z]$/', $first)) {
            $out .= $first;
        }
        if (strlen($out) >= 8)
            break;
    }
    return $out;
}

/**
 * Insert a welcome row into system_updates for a freshly registered student.
 * Failure here must NOT fail the registration — the student is already
 * committed at this point, so we log and continue.
 */
function insert_system_update(PDO $pdo, string $accountNumber, string $cid): void
{
    $title = 'Maligayang Pagdating!';
    $desc = 'Matagumpay ang iyong pagpaparehistro. Kumpletuhin ang iyong profile para makapagsimula.';

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO system_updates (account_number, title, `desc`, notif_shown)
             VALUES (:account_number, :title, :desc, 0)"
        );
        $stmt->execute([
            ':account_number' => $accountNumber,
            ':title' => $title,
            ':desc' => $desc,
        ]);
    } catch (PDOException $e) {
        // Non-fatal: registration already succeeded. Log and move on.
        error_log("register_student system_updates insert failed [{$cid}]: " . $e->getMessage());
        audit('system_update_failed', ['cid' => $cid, 'account_number' => $accountNumber]);
    }
}

/**
 * Retry loop for concurrent registrations.
 * On 23000 (duplicate key), mint a fresh suffix and retry with jittered backoff.
 * On any other DB error, abort immediately.
 *
 * @return array{ok: bool, account_number?: string, code?: int, message?: string}
 */
function insert_student_with_retry(PDO $pdo, array $d, string $cid): array
{
    $sql = "INSERT INTO students (
                account_number, pin, pin_text,
                first_name, middle_name, surname,
                age, sex, birth_date, birth_place, address, contact_number,
                guardian, guardian_contact_number,
                registered_date, profile_account, user_type,
                school_name, grade_level, student_type, enrollment_status
            ) VALUES (
                :account_number, :pin, :pin_text,
                :first_name, :middle_name, :surname,
                :age, :sex, :birth_date, :birth_place, :address, :contact_number,
                :guardian, :guardian_contact_number,
                :registered_date, :profile_account, 'student',
                :school_name, :grade_level, :student_type, 'Not Enrolled'
            )";

    // CHANGED: Increased maxAttempts significantly to handle the small pool of 500 numbers
    $maxAttempts = 600;
    $baseDelayUs = 50_000;
    $accountNumber = null;
    $lastError = null;

    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        // CHANGED: Random number is now between 1 and 500
        $candidate = $d['prefix'] . (string) random_int(1, 500);

        $params = [
            ':account_number' => $candidate,
            ':pin' => $d['pin_hash'],
            ':pin_text' => $d['pin_text'],
            ':first_name' => $d['first_name'],
            ':middle_name' => $d['middle_name'],
            ':surname' => $d['surname'],
            ':age' => $d['age'],
            ':sex' => $d['sex'],
            ':birth_date' => $d['birth_date'],
            ':birth_place' => $d['birth_place'],
            ':address' => $d['address'],
            ':contact_number' => $d['contact'],
            ':guardian' => $d['guardian'],
            ':guardian_contact_number' => $d['guardian_ct'],
            ':registered_date' => date('F j, Y g:iA'),
            ':profile_account' => 'profile-image.jpg',
            ':school_name' => $d['school'],
            ':grade_level' => $d['grade'],
            ':student_type' => $d['student_type'],
        ];

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $pdo->commit();

            $accountNumber = $candidate;
            break;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e->getCode() === '23000') {
                $lastError = $e;
                audit('register_retry', [
                    'cid' => $cid,
                    'attempt' => $attempt,
                    'candidate' => $candidate,
                ]);

                $jitter = random_int(0, 100_000);
                $sleepUs = ($baseDelayUs * $attempt) + $jitter;
                usleep($sleepUs);
                continue;
            }

            error_log("register_student insert failed [{$cid}] attempt {$attempt}: " . $e->getMessage());
            return [
                'ok' => false,
                'code' => 500,
                'message' => 'Registration failed. Please try again.',
            ];
        }
    }

    if ($accountNumber === null) {
        $errMsg = ($lastError instanceof PDOException) ? $lastError->getMessage() : 'unknown';
        error_log("register_student: max attempts reached [{$cid}]: {$errMsg}");
        audit('register_exhausted', ['cid' => $cid]);
        return [
            'ok' => false,
            'code' => 503,
            'message' => 'Masyadong abala ang system. Subukan muli pagkalipas ng ilang segundo.',
        ];
    }

    return ['ok' => true, 'account_number' => $accountNumber];
}

function set_registration_cookie(string $accountNumber): void
{
    setcookie('temp_account_number', $accountNumber, [
        'expires' => time() + 60,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}