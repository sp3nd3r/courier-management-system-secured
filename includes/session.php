<?php
/**
 * Hardened session bootstrap, shared by the customer and staff entry points so
 * both get identical cookie flags and the same idle timeout.
 */

require_once __DIR__ . '/logger.php';

/** Idle time after which a session is discarded (30 minutes). */
if (!defined('SESSION_IDLE_TIMEOUT')) {
    define('SESSION_IDLE_TIMEOUT', 30 * 60);
}

/**
 * Secure cookies are only sent back over HTTPS. Forced on when the request is
 * already HTTPS; over plain HTTP on a local XAMPP install it must stay off or
 * the session cookie would never be returned and login could not work.
 */
if (!defined('SESSION_COOKIE_SECURE')) {
    define('SESSION_COOKIE_SECURE', (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    ));
}

if (!function_exists('secure_session_start')) {

    /**
     * Start a session with HttpOnly / Secure / SameSite=Strict cookie flags and
     * enforce the idle timeout.
     */
    function secure_session_start()
    {
        if (session_id() !== '') {
            enforce_idle_timeout();
            return;
        }

        // Internal detail stays in the log, never in the response body.
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');

        // HttpOnly keeps the cookie away from JavaScript, SameSite=Strict stops
        // it riding along on cross-site requests.
        session_set_cookie_params(array(
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => SESSION_COOKIE_SECURE,
            'httponly' => true,
            'samesite' => 'Strict',
        ));

        session_start();

        enforce_idle_timeout();
    }

    /**
     * Drop the session once it has been idle past the limit.
     */
    function enforce_idle_timeout()
    {
        if (isset($_SESSION['last_activity'])
            && (time() - $_SESSION['last_activity']) > SESSION_IDLE_TIMEOUT) {

            $who = isset($_SESSION['customer'])
                ? (string) $_SESSION['customer']
                : (isset($_SESSION['admin']) ? $_SESSION['admin'] : '-');

            security_log('SESSION_TIMEOUT', $who, 'idle for more than ' . SESSION_IDLE_TIMEOUT . 's');

            $_SESSION = array();
            session_unset();
            session_destroy();
            session_start();
            return;
        }

        $_SESSION['last_activity'] = time();
    }
}
