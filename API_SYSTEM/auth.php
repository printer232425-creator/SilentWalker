<?php
// API_SYSTEM/auth.php — shared auth utilities
declare(strict_types=1);

/* ============================================================
   RESPONSE / REQUEST
   ============================================================ */
function send_json_headers(): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store, max-age=0');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header('Content-Security-Policy: default-src \'none\'; frame-ancestors \'none\'');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function json_ok(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode(array_merge(['success' => true], $payload), JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(int $code, string $message, array $extra = []): void
{
    http_response_code($code);
    echo json_encode(array_merge(['success' => false, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function is_post_request(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

/* ============================================================
   SESSION
   ============================================================ */
function start_secure_session(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* ============================================================
   CSRF
   ============================================================ */
function current_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $clientToken): bool
{
    if (!is_string($clientToken) || $clientToken === '') {
        return false;
    }
    return hash_equals(current_csrf_token(), $clientToken);
}

function rotate_csrf_token(): string
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

/* ============================================================
   IP / NETWORK
   ============================================================ */
function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function ip_network(string $ip): string
{
    if (str_contains($ip, ':')) {
        $parts = explode(':', $ip);
        return implode(':', array_slice($parts, 0, 3)) . '::/48';
    }
    $parts = explode('.', $ip);
    if (count($parts) === 4) {
        return $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.0/24';
    }
    return $ip;
}

/* ============================================================
   RATE LIMIT — session layer
   Returns true if within limit, false if exceeded.
   Does NOT record an attempt — call record_session_attempt() after.
   ============================================================ */
function session_rate_limit(string $bucket, int $max, int $windowSeconds): bool
{
    $now = time();
    $_SESSION[$bucket] = array_values(array_filter(
        $_SESSION[$bucket] ?? [],
        fn($t) => is_int($t) && ($now - $t) < $windowSeconds
    ));
    return count($_SESSION[$bucket]) < $max;
}

function record_session_attempt(string $bucket): void
{
    $_SESSION[$bucket][] = time();
}

function clear_session_attempts(string $bucket): void
{
    unset($_SESSION[$bucket]);
}

/* ============================================================
   RATE LIMIT — DB layer (survives cookie clear)
   ============================================================ */
function db_rate_limit(PDO $pdo, string $bucket, string $key, int $max, int $windowSeconds): bool
{
    try {
        $pdo->prepare(
            "DELETE FROM rate_limits WHERE bucket = ? AND attempted_at < (NOW() - INTERVAL ? SECOND)"
        )->execute([$bucket, $windowSeconds]);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM rate_limits WHERE bucket = ? AND key_value = ?");
        $stmt->execute([$bucket, $key]);
        if ((int) $stmt->fetchColumn() >= $max) {
            return false;
        }

        $pdo->prepare("INSERT INTO rate_limits (bucket, key_value, attempted_at) VALUES (?, ?, NOW())")
            ->execute([$bucket, $key]);
        return true;
    } catch (PDOException $e) {
        error_log("db_rate_limit error: " . $e->getMessage());
        return true; // fail-open
    }
}

/* ============================================================
   AUDIT LOG
   ============================================================ */
function audit(string $event, array $ctx = []): void
{
    $line = sprintf(
        "[%s] %s %s\n",
        date('c'),
        $event,
        json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
    @file_put_contents(
        __DIR__ . '/../../logs/auth-audit.log',
        $line,
        FILE_APPEND | LOCK_EX
    );
}

/* ============================================================
   INPUT SANITIZER (reused by all registration endpoints)
   ============================================================ */
function clean_str(string $key, int $maxLen): string
{
    $v = $_POST[$key] ?? '';
    if (!is_string($v)) return '';
    $v = preg_replace('/[\x00-\x1F\x7F]/u', '', $v);
    $v = preg_replace('/\x{00A0}/u', ' ', $v);
    $v = trim($v);
    if (mb_strlen($v, 'UTF-8') > $maxLen) {
        $v = mb_substr($v, 0, $maxLen, 'UTF-8');
    }
    return $v;
}