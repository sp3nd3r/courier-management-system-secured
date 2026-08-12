<?php

require_once dirname(dirname(__DIR__)) . '/includes/sanitise.php';
require_once dirname(dirname(__DIR__)) . '/includes/logger.php';

/**
 * Tables that may appear where an identifier is required. Identifiers cannot
 * be bound as parameters, so anything reaching that position is matched
 * against this list instead.
 */
if (!defined('DB_ALLOWED_TABLES')) {
    define('DB_ALLOWED_TABLES', serialize(array(
        'area', 'branch', 'contact', 'customer', 'employee',
        'gallery', 'price_table', 'request', 'settings',
    )));
}

function db_allowed_tables()
{
    return unserialize(DB_ALLOWED_TABLES);
}

function getAllBranch()
{
    include 'connection.php';

    return db_select("SELECT * FROM branch WHERE is_deleted = 0");
}
function getAllArea()
{
    include 'connection.php';

    return db_select("SELECT * FROM area WHERE is_deleted = 0");
}
function getAllAreabyID($area_id)
{
    include 'connection.php';

    return db_select(
        "SELECT * FROM area WHERE is_deleted = 0 AND area_id = ?",
        array(clean_id($area_id))
    );
}
function getAllPrice()
{
    include 'connection.php';

    return db_select("SELECT * FROM price_table WHERE is_deleted = 0");
}

function checkPrice($start_area, $end_area)
{
    include 'connection.php';

    $res = db_select(
        "SELECT * FROM price_table WHERE is_deleted = 0 AND start_area = ? AND end_area = ?",
        array(clean_string($start_area), clean_string($end_area))
    );
    return db_num_rows($res);
}

function getBille($customer_id)
{
    include 'connection.php';

    return db_select(
        "SELECT * FROM request join customer on customer.customer_id = request.customer_id WHERE request.customer_id = ?",
        array(clean_id($customer_id))
    );
}

//product

function getAllemployee()
{
    include 'connection.php';

    return db_select("SELECT * FROM employee WHERE is_deleted = 0 AND email != 'admin'");
}

function getemployeeByID($emp_id)
{
    include 'connection.php';

    return db_select(
        "SELECT * FROM employee WHERE is_deleted = 0 AND emp_id = ?",
        array(clean_id($emp_id))
    );
}

function getemployeeByEmail($email)
{
    include 'connection.php';

    return db_select(
        "SELECT * FROM employee WHERE is_deleted = 0 AND email = ?",
        array(clean_email($email))
    );
}

function getBranchByID($branch_id)
{
    include 'connection.php';

    return db_select(
        "SELECT * FROM branch WHERE is_deleted = 0 AND branch_id = ?",
        array(clean_id($branch_id))
    );
}

function getAllTrackingByCUS($customer_id)
{
    include 'connection.php';

    return db_select(
        "SELECT * FROM request WHERE is_deleted = 0 AND customer_id = ? ORDER BY date_updated DESC",
        array(clean_id($customer_id))
    );
}

function getAllTracking()
{
    include 'connection.php';

    return db_select("SELECT * FROM request join customer on customer.customer_id = request.customer_id WHERE request.is_deleted = 0 ORDER BY date_updated DESC");
}

function checkemployeetByEmail($email)
{
    include 'connection.php';

    $email = clean_email($email);

    $result = db_select("SELECT * FROM employee WHERE email = ? AND is_deleted = 0", array($email));
    $cus_res = db_select("SELECT * FROM customer WHERE email = ? AND is_deleted = 0", array($email));

    if (db_num_rows($result) > 0) {
        return db_num_rows($result);
    } else if (db_num_rows($cus_res) > 0) {
        return db_num_rows($cus_res);
    } else {
        return 0;
    }
}

function getAllgalleryImages()
{
    include 'connection.php';

    return db_select("SELECT * FROM gallery");
}

//customer


function checkuserPassword($data)
{
    include 'connection.php';
    $customer_id = clean_id($data['customer_id']);
    $password = isset($data['password']) ? (string) $data['password'] : '';

    $res = db_select(
        "SELECT password FROM customer WHERE is_deleted = 0 AND customer_id = ?",
        array($customer_id)
    );
    $row = db_fetch($res);

    echo ($row && password_verify($password, $row['password'])) ? 1 : 0;
}

function checkArea($data)
{
    include 'connection.php';

    $start_area = clean_string($data['send_location']);
    $end_area = clean_string($data['end_location']);

    $res = db_select(
        "SELECT price FROM price_table WHERE is_deleted = 0 AND start_area = ? AND end_area = ?",
        array($start_area, $end_area)
    );
    $row = db_fetch($res);
    echo $row ? $row['price'] : '';
}

function checkAreaByName($area_name)
{
    include 'connection.php';

    $res = db_select(
        "SELECT * FROM area WHERE area_name = ? AND is_deleted = 0",
        array(clean_string($area_name))
    );
    return db_num_rows($res);
}

function checkUserEmail($data)
{
    include 'connection.php';

    $customer_id = clean_id($data['customer_id']);
    $email = clean_email($data['email']);

    $res = db_select(
        "SELECT * FROM customer WHERE is_deleted = 0 AND email = ? AND customer_id = ?",
        array($email, $customer_id)
    );
    echo db_num_rows($res);
}

function getAllcustomerById($customer_id)
{
    include 'connection.php';

    return db_select(
        "SELECT * FROM customer WHERE is_deleted = '0' AND customer_id = ?",
        array(clean_id($customer_id))
    );
}

function getAllcustomers()
{
    include 'connection.php';

    return db_select("SELECT * FROM customer WHERE is_deleted = 0 AND email != 'admin'");
}

/**
 * Authenticate a member of staff or a customer.
 *
 * The account is located by email with a bound parameter, then the supplied
 * password is checked against the stored bcrypt hash. Both outcomes leave via
 * the same path so a caller cannot tell "no such account" from "wrong
 * password", and the session id is regenerated once authentication succeeds.
 */
function getLoginAdmin($data)
{
    include 'connection.php';

    $email = clean_email(isset($data['email']) ? $data['email'] : '');
    $password = isset($data['password']) ? (string) $data['password'] : '';

    // Compared against when no account matches, so that a missing account and
    // a wrong password take the same amount of work.
    $dummyHash = '$2y$12$usesomesillystringforsalt0000000000000000000000000000000';

    $value = '';
    $account = null;
    $role = '';

    if ($email !== null && $email !== '') {
        $res = db_select(
            "SELECT emp_id, email, password FROM employee WHERE email = ? AND is_deleted = '0'",
            array($email)
        );
        $account = db_fetch($res);
        $role = 'admin';

        if (!$account) {
            $res = db_select(
                "SELECT customer_id, email, password FROM customer WHERE email = ? AND is_deleted = '0'",
                array($email)
            );
            $account = db_fetch($res);
            $role = 'customer';
        }
    }

    $hash = $account ? $account['password'] : $dummyHash;

    if (password_verify($password, $hash) && $account) {
        if (session_id() === '') {
            session_start();
        }
        // New session id, so a pre-set id cannot be reused after login.
        session_regenerate_id(true);

        if ($role === 'admin') {
            $value = 'admin';
            $_SESSION['admin'] = $account['email'];
            security_log('LOGIN_OK', $account['email'], 'role=admin');
        } else {
            $value = 'customer';
            $_SESSION['customer'] = $account['customer_id'];
            security_log('LOGIN_OK', (string) $account['customer_id'], 'role=customer');
        }
        $_SESSION['last_activity'] = time();
    } else {
        security_log('LOGIN_FAIL', ($email === null ? '-' : $email), 'invalid credentials');
    }

    echo $value;
}

function checkemployee($email)
{
    include 'connection.php';

    return db_select(
        "SELECT * FROM employee WHERE email = ? AND is_deleted = '0'",
        array(clean_email($email))
    );
}

function checkCustomerByEmail($email)
{
    include 'connection.php';

    return db_select(
        "SELECT * FROM customer WHERE email = ? AND is_deleted = '0'",
        array(clean_email($email))
    );
}


function checkCustomerByID($customer_id)
{
    include 'connection.php';

    return db_select(
        "SELECT * FROM customer WHERE customer_id = ? AND is_deleted = '0'",
        array(clean_id($customer_id))
    );
}

function getAllCustomer()
{
    include 'connection.php';

    $table = db_select("SELECT * FROM customer WHERE is_deleted = '0' AND email != 'admin'");

    return db_fetch_all($table);
}


//contact

function getAllMessages()
{
    include 'connection.php';

    return db_select("SELECT * FROM contact");
}

//count

function dataCount($table)
{
    include 'connection.php';

    $table = clean_identifier($table, db_allowed_tables());
    if ($table === null) {
        echo 0;
        return;
    }

    $res = db_select("SELECT * FROM `$table` WHERE is_deleted = 0");
    echo db_num_rows($res);
}

/**
 * Count rows of $table where $column equals $value.
 *
 * The table and column are allowlisted because identifiers cannot be bound;
 * the value itself is passed as a real parameter.
 */
function dataCountWhere($table, $column, $value)
{
    include 'connection.php';

    $table = clean_identifier($table, db_allowed_tables());
    $column = clean_identifier($column, array('tracking_status', 'is_deleted', 'branch_id'));

    if ($table === null || $column === null) {
        echo 0;
        return;
    }

    $res = db_select(
        "SELECT * FROM `$table` WHERE `$column` = ? AND is_deleted = 0",
        array($value)
    );
    echo db_num_rows($res);
}

function dataforCount($table)
{
    include 'connection.php';

    $table = clean_identifier($table, db_allowed_tables());
    if ($table === null) {
        return new DbResult(array());
    }

    return db_select("SELECT sum(total) as sum FROM `$table` WHERE is_deleted = 0");
}

function dataforCountToday($table)
{
    include 'connection.php';

    $table = clean_identifier($table, db_allowed_tables());
    if ($table === null) {
        return new DbResult(array());
    }

    return db_select("SELECT sum(total) as sum FROM `$table` WHERE month(now()) = month(date_updated) AND is_deleted = 0");
}


//settings

function getAllSettings()
{
    include 'connection.php';

    return db_select("SELECT * FROM settings");
}

function checkPasswordByName($data)
{
    include 'connection.php';

    $email = clean_email($data['email']);
    $password = isset($data['password']) ? (string) $data['password'] : '';

    $res = db_select("SELECT password FROM employee WHERE email = ?", array($email));
    $row = db_fetch($res);

    echo ($row && password_verify($password, $row['password'])) ? 1 : 0;
}

function getAllCart($customer_id)
{
    include 'connection.php';

    return db_select(
        "SELECT * FROM cart join products on products.pid = cart.pid join customer on customer.customer_id = cart.customer_id WHERE cart.customer_id = ?",
        array(clean_id($customer_id))
    );
}


function getAllOrdersByCustomer($customer_id)
{
    include 'connection.php';

    return db_select(
        "SELECT * FROM product_orders WHERE customer_id = ? AND is_deleted = '0' ORDER BY date_updated DESC",
        array(clean_id($customer_id))
    );
}

function getAllOrderItemsBYOrder($order_id)
{
    include 'connection.php';

    return db_select(
        "SELECT * FROM order_items join products on order_items.pid = products.pid WHERE order_items.order_id = ?",
        array(clean_id($order_id))
    );
}

function getAllOrders()
{
    include 'connection.php';

    return db_select("SELECT * FROM product_orders join customer on customer.customer_id = product_orders.customer_id  WHERE product_orders.is_deleted = '0' ORDER BY date_updated DESC");
}

function getAllOrdersPending()
{
    include 'connection.php';

    return db_select("SELECT * FROM product_orders join customer on customer.customer_id = product_orders.customer_id  WHERE product_orders.is_deleted = '0' AND product_orders.order_status = '1' ORDER BY date_updated DESC");
}

function getAllOrderItems($order_id)
{
    include 'connection.php';

    return db_select(
        "SELECT * FROM order_items join products on order_items.pid = products.pid WHERE order_items.order_id = ?",
        array(clean_id($order_id))
    );
}
