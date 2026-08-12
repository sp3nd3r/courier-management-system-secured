<?php

require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/rbac.php';

secure_session_start();

    // Redirects and STOPS. Previously the Location header was sent but
    // rendering continued, so the protected page was still returned.
    require_admin('login.php');
?>
