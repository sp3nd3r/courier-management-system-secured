<?php
/**
 * Resolves the directory holding files that must never be reachable over HTTP:
 * the database credentials and the security log.
 *
 * The application folder itself sits inside the web root, so nothing private
 * may live beneath it. Candidates are tried in order and the first that already
 * exists wins, which keeps the CLI and the web server pointing at the same
 * place.
 *
 *   1. the RE_PRIVATE_DIR environment variable
 *   2. a sibling of the document root  (…/xamppfiles/royal_express_private)
 *   3. a sibling of the application    (…/Downloads/royal_express_private)
 */

if (!defined('RE_PRIVATE_DIR')) {

    $candidates = array();

    if (getenv('RE_PRIVATE_DIR')) {
        $candidates[] = rtrim(getenv('RE_PRIVATE_DIR'), '/');
    }

    if (!empty($_SERVER['DOCUMENT_ROOT']) && realpath($_SERVER['DOCUMENT_ROOT'])) {
        $candidates[] = dirname(realpath($_SERVER['DOCUMENT_ROOT'])) . '/royal_express_private';
    }

    // Fixed sibling of the document root on this installation, so a CLI run
    // resolves to the same directory the web server uses.
    $candidates[] = '/Applications/XAMPP/xamppfiles/royal_express_private';

    // Last resort: sibling of the application folder.
    $candidates[] = dirname(dirname(__DIR__)) . '/royal_express_private';

    $chosen = null;
    foreach ($candidates as $candidate) {
        if (is_dir($candidate)) {
            $chosen = $candidate;
            break;
        }
    }

    define('RE_PRIVATE_DIR', $chosen !== null ? $chosen : $candidates[count($candidates) - 1]);

    unset($candidates, $candidate, $chosen);
}
