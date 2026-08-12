<?php
/**
 * Append-only security event log.
 *
 * The log file is deliberately written OUTSIDE the web root so that it can
 * never be fetched over HTTP. See includes/private_path.php for how that
 * location is resolved.
 */

require_once __DIR__ . '/private_path.php';

if (!defined('SECURITY_LOG_DIR')) {
    define('SECURITY_LOG_DIR', RE_PRIVATE_DIR . '/logs');
}

if (!function_exists('security_log')) {

    /**
     * Resolve (and lazily create) the log file path.
     */
    function security_log_path()
    {
        if (!is_dir(SECURITY_LOG_DIR)) {
            @mkdir(SECURITY_LOG_DIR, 0730, true);
        }

        $path = SECURITY_LOG_DIR . '/security.log';

        // The log is appended to both by the web server (running as its own
        // user) and by command-line tools run as the developer. Whichever
        // creates it first must leave it writable by the other, or entries
        // from the second one would be dropped silently.
        if (!file_exists($path)) {
            @touch($path);
            @chmod($path, 0666);
        }

        return $path;
    }

    /**
     * Best-effort client IP. Only used for forensics, never for authorisation.
     */
    function security_log_ip()
    {
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'cli';
    }

    /**
     * Append one event. Never throws and never echoes: a logging failure must
     * not break the request or leak paths to the browser.
     *
     * @param string $event   LOGIN_OK | LOGIN_FAIL | IDOR_ATTEMPT | ...
     * @param string $userId  account id or email, '-' when unauthenticated
     * @param string $detail  free-text context
     */
    function security_log($event, $userId = '-', $detail = '')
    {
        $line = sprintf(
            "%s\t%s\t%s\t%s\t%s\n",
            date('Y-m-d H:i:s'),
            security_log_ip(),
            $event,
            ($userId === '' ? '-' : $userId),
            str_replace(array("\r", "\n", "\t"), ' ', $detail)
        );

        @file_put_contents(security_log_path(), $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Log an internal failure in full, then hand the caller a generic message
     * that is safe to show a user.
     */
    function log_internal_error($context, Throwable $e)
    {
        security_log('APP_ERROR', '-', $context . ': ' . $e->getMessage());
        return 'A system error occurred. Please try again later.';
    }
}
