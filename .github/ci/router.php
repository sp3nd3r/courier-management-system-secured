<?php
/**
 * Router for PHP's built-in server, used ONLY by the DAST workflow.
 *
 * `php -S` ignores .htaccess, so the one rule that must be reproduced by hand is
 * baseline/'s deny: that directory holds deliberately vulnerable pre-remediation
 * code, and letting a scanner execute it would produce findings against code that
 * is never reachable in a real deployment.
 *
 * Nothing else is blocked on purpose. The point of a dynamic scan is to see the
 * application as an attacker does, so hardening the stand-in server would hide
 * genuine exposures rather than fix them.
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (preg_match('#^/baseline(/|$)#', $path)) {
    http_response_code(403);
    header('Content-Type: text/plain');
    exit("Forbidden: pre-remediation baseline is not executable.\n");
}

// Fall through to the built-in server's default handling.
return false;
