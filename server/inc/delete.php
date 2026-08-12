<?php

require_once dirname(dirname(__DIR__)) . '/includes/sanitise.php';

/**
 * Tables that may be targeted by the generic delete endpoints, mapped to the
 * primary-key column each one is keyed by. Identifiers cannot be bound as
 * parameters, so they are matched against this list instead.
 */
function deletable_key_columns()
{
    return array(
        'request'     => 'request_id',
        'customer'    => 'customer_id',
        'employee'    => 'emp_id',
        'price_table' => 'price_id',
        'branch'      => 'branch_id',
        'area'        => 'area_id',
        'gallery'     => 'gallery_id',
        'contact'     => 'contact_id',
    );
}

/**
 * @return array|null array(table, idField) or null if not permitted
 */
function resolve_delete_target($table, $idField)
{
    $keys = deletable_key_columns();

    $table = clean_identifier($table, array_keys($keys));
    if ($table === null) {
        return null;
    }

    $idField = clean_identifier($idField, array($keys[$table]));
    if ($idField === null) {
        return null;
    }

    return array($table, $idField);
}

function deleteDataTables($data){
    include 'connection.php';

    $target = resolve_delete_target($data['table'], $data['id_fild']);
    $id = clean_id($data['id']);

    if ($target === null || $id === null) {
        echo 0;
        return false;
    }
    list($table, $idField) = $target;

    return db_exec("UPDATE `$table` SET is_deleted = '1' where `$idField` = ?", array($id));
}

function permanantDeleteDataTable($data){
    include 'connection.php';

    $target = resolve_delete_target($data['table'], $data['id_fild']);
    $id = clean_id($data['id']);

    if ($target === null || $id === null) {
        echo 0;
        return false;
    }
    list($table, $idField) = $target;

    return db_exec("DELETE FROM `$table` WHERE `$idField` = ?", array($id));
}


function deleteAllCartItems($customer_id){

	include 'connection.php';

    return db_exec("DELETE FROM cart where customer_id = ?", array(clean_id($customer_id)));
}


?>
