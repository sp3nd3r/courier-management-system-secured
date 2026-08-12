<?php

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/rbac.php';
require_once __DIR__ . '/includes/sanitise.php';

// Session cookie carries HttpOnly / Secure / SameSite=Strict, and a session
// idle for more than 30 minutes is discarded here.
secure_session_start();

    // Redirects and STOPS. The original sent the Location header but then
    // carried on rendering the protected page into the response body.
    $customer_id = require_customer('Admin/login.php');

    $getall = getAllcustomerById($customer_id);
    $cus = db_fetch($getall);
?>
