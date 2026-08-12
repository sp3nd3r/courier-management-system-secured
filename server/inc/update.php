<?php

require_once dirname(dirname(__DIR__)) . '/includes/sanitise.php';
require_once __DIR__ . '/add.php'; // hash_password()

/**
 * Columns that may be written through the generic update endpoint, keyed by
 * table. A table or column name cannot be sent as a bound parameter, so an
 * allowlist is the only safe way to accept one from the request.
 */
function updatable_columns()
{
    return array(
        'request'     => array('tracking_status'),
        'customer'    => array('name', 'phone', 'address', 'nic', 'email', 'gender', 'password'),
        'employee'    => array('name', 'email', 'phone', 'nic', 'branch_id', 'address', 'gender', 'password'),
        'price_table' => array('start_area', 'end_area', 'price'),
        'branch'      => array('branch_name'),
        'area'        => array('area_name'),
        'gallery'     => array('gallery_image'),
    );
}

/** Primary-key column allowed for each table. */
function updatable_key_columns()
{
    return array(
        'request'     => 'request_id',
        'customer'    => 'customer_id',
        'employee'    => 'emp_id',
        'price_table' => 'price_id',
        'branch'      => 'branch_id',
        'area'        => 'area_id',
        'gallery'     => 'gallery_id',
    );
}

/** Columns of the single-row settings table that may be written. */
function updatable_settings_columns()
{
    return array(
        'header_image', 'header_title', 'header_desc', 'about_title', 'about_desc',
        'company_phone', 'company_email', 'company_address', 'sub_image',
        'about_image', 'link_facebook', 'link_twiiter', 'link_instragram',
        'background_image',
    );
}

/**
 * Validate a table / column / key-column triple against the allowlists.
 *
 * @return array|null array(table, field, idField) or null if not permitted
 */
function resolve_update_target($table, $field, $idField)
{
    $columns = updatable_columns();
    $keys = updatable_key_columns();

    $table = clean_identifier($table, array_keys($columns));
    if ($table === null) {
        return null;
    }

    $field = clean_identifier($field, $columns[$table]);
    $idField = clean_identifier($idField, array($keys[$table]));

    if ($field === null || $idField === null) {
        return null;
    }

    return array($table, $field, $idField);
}

function updateDataTable($data)
{
    include 'connection.php';

    $target = resolve_update_target($data['table'], $data['field'], $data['id_fild']);
    if ($target === null) {
        echo 0;
        return false;
    }
    list($table, $field, $idField) = $target;

    $id = clean_id($data['id']);
    if ($id === null) {
        echo 0;
        return false;
    }

    // A new password is stored as a bcrypt hash, never as the supplied text.
    $value = ($field === 'password')
        ? hash_password($data['value'])
        : clean_string($data['value'], 1000);

    return db_exec(
        "UPDATE `$table` SET `$field` = ? where `$idField` = ?",
        array($value, $id)
    );
}


function updateSubCatData($data)
{
    include 'connection.php';

    $target = resolve_update_target($data['table'], $data['field'], $data['id_fild']);
    if ($target === null) {
        echo 0;
        return false;
    }
    list($table, $field, $idField) = $target;

    $id = clean_id($data['id']);
    if ($id === null) {
        echo 0;
        return false;
    }

    $getdatas = getAllSubCategory($id);
    $count = db_num_rows($getdatas);

    if ($count > 0) {
        echo $count;
    }
    else {
        $value = ($field === 'password')
            ? hash_password($data['value'])
            : clean_string($data['value'], 1000);

        return db_exec(
            "UPDATE `$table` SET `$field` = ? where `$idField` = ?",
            array($value, $id)
        );
    }
}

function editImages($data, $img)
{
    include 'connection.php';

    $target = resolve_update_target($data['table'], $data['field'], $data['id_fild']);
    if ($target === null) {
        echo 0;
        return false;
    }
    list($table, $field, $idField) = $target;

    $id = clean_id($data['id']);
    if ($id === null) {
        echo 0;
        return false;
    }

    return db_exec(
        "UPDATE `$table` SET `$field` = ? where `$idField` = ?",
        array(clean_string($img), $id)
    );
}

//qty reduce code

function productQtyReduce($pid, $qty)
{
    include 'connection.php';

    $pid = clean_id($pid);

    $res = db_select("SELECT * FROM products WHERE pid = ?", array($pid));
    $row = db_fetch($res);

    $value = $row['product_qty'] - $qty;

    return db_exec(
        "UPDATE products SET product_qty = ?, date_updated = now() where pid = ?",
        array($value, $pid)
    );
}

function increaseQtyProduct($data)
{
    include 'connection.php';

    $serve_id = clean_id($data['serve_id']);

    $res = db_select("SELECT * FROM server_products WHERE serve_id = ?", array($serve_id));
    $row = db_fetch($res);

    $pid = $row['pid'];

    $res2 = db_select("SELECT * FROM products WHERE pid = ?", array($pid));
    $row2 = db_fetch($res2);

    $value = $row['serve_qty'] + $row2['product_qty'];

    return db_exec(
        "UPDATE products SET product_qty = ?, date_updated = now() where pid = ?",
        array($value, $pid)
    );
}

function changePageSettings($data)
{
    include 'connection.php';

    $field = clean_identifier($data['field'], updatable_settings_columns());
    if ($field === null) {
        echo 0;
        return false;
    }
    $value = clean_string($data['value'], 1000);

    return db_exec("UPDATE settings SET `$field` = ?", array($value));
}

function editSettingImage($data, $img)
{
    include 'connection.php';

    $field = clean_identifier($data['field'], updatable_settings_columns());
    if ($field === null) {
        echo 0;
        return false;
    }

    return db_exec("UPDATE settings SET `$field` = ?", array(clean_string($img)));
}

function editQtyinCart($data)
{
    include 'connection.php';

    $cart_id = clean_id($data['cart_id']);
    $field = clean_identifier($data['field'], array('qty'));
    if ($field === null || $cart_id === null) {
        echo 0;
        return false;
    }

    return db_exec(
        "UPDATE cart SET `$field` = ?, date_updated = now() where cart_id = ?",
        array(clean_string($data['value'], 32), $cart_id)
    );
}

?>
