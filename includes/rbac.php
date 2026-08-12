<?php
/**
 * Role and ownership checks.
 *
 * Every guard terminates the request itself. The original code called
 * header('Location: ...') without exit, which sent a redirect header but let
 * PHP carry on rendering the protected page into the response body.
 */

require_once __DIR__ . '/logger.php';

if (!function_exists('current_customer_id')) {

    function rbac_boot_session()
    {
        if (session_id() === '') {
            session_start();
        }
    }

    /**
     * @return int|null the logged-in customer id, or null
     */
    function current_customer_id()
    {
        rbac_boot_session();
        if (!isset($_SESSION['customer'])) {
            return null;
        }
        $id = filter_var($_SESSION['customer'], FILTER_VALIDATE_INT, array(
            'options' => array('min_range' => 1),
        ));
        return ($id === false) ? null : $id;
    }

    function is_admin()
    {
        rbac_boot_session();
        return isset($_SESSION['admin']) && $_SESSION['admin'] !== '';
    }

    /**
     * Require a logged-in customer. Redirects to the login page and STOPS.
     */
    function require_customer($loginUrl = 'Admin/login.php')
    {
        $id = current_customer_id();
        if ($id === null) {
            header('Location: ' . $loginUrl);
            exit;
        }
        return $id;
    }

    /**
     * Require a logged-in member of staff. Redirects to the login page and STOPS.
     */
    function require_admin($loginUrl = 'login.php')
    {
        if (!is_admin()) {
            header('Location: ' . $loginUrl);
            exit;
        }
        return $_SESSION['admin'];
    }

    /**
     * Ownership check for customer-scoped records.
     *
     * Admins may act on any record. A customer may only act on their own, and
     * an attempt to reach someone else's is logged and refused with 403.
     *
     * @param int|null $requestedId id taken from the request, null when absent
     * @return int the customer id the caller is actually allowed to use
     */
    function assert_owns($requestedId)
    {
        if (is_admin()) {
            return $requestedId;
        }

        $sessionId = current_customer_id();
        if ($sessionId === null) {
            security_log('IDOR_ATTEMPT', '-', 'unauthenticated access, requested=' . var_export($requestedId, true));
            http_response_code(403);
            exit('403 Forbidden');
        }

        // No id supplied: fall back to the session. Nothing to compare.
        if ($requestedId === null) {
            return $sessionId;
        }

        if ((int) $requestedId !== (int) $sessionId) {
            security_log(
                'IDOR_ATTEMPT',
                (string) $sessionId,
                'requested customer_id=' . $requestedId . ' but session owns ' . $sessionId
            );
            http_response_code(403);
            exit('403 Forbidden');
        }

        return $sessionId;
    }
}
