<?php

require_once dirname(dirname(__DIR__)) . '/includes/sanitise.php';

/** Work factor for every password hashed by the application. */
if (!defined('PASSWORD_HASH_COST')) {
    define('PASSWORD_HASH_COST', 12);
}

/**
 * Hash a plaintext password for storage. Never store the input itself.
 */
function hash_password($plaintext)
{
    return password_hash($plaintext, PASSWORD_BCRYPT, array('cost' => PASSWORD_HASH_COST));
}

function insertImagetoGallery($img)
{
	include 'connection.php';

	return db_exec("INSERT INTO gallery(gallery_image) VALUES(?)", array(clean_string($img)));
}

function addBranch($data)
{
	include 'connection.php';

	$branch_name = clean_string($data['branch_name']);
	return db_exec("INSERT INTO branch(branch_name, is_deleted) VALUES(?, 0)", array($branch_name));
}

function addArea($data)
{
	include 'connection.php';

	$area_name = clean_string($data['area_name']);


	$count = checkAreaByName($area_name);

	if ($count == 0) {

		return db_exec("INSERT INTO area(area_name, is_deleted) VALUES(?, 0)", array($area_name));
	} else {
		echo json_encode($count);
	}
}

function addPrice($data)
{
	include 'connection.php';

	$start_area = clean_string($data['start_area']);
	$end_area = clean_string($data['end_area']);
	$price = clean_string($data['price'], 32);

	$count = checkPrice($start_area, $end_area);

	if ($count == 0) {

		return db_exec(
			"INSERT INTO price_table(start_area, end_area, price ,is_deleted, date_updated) VALUES(?, ?, ?, 0 , now())",
			array($start_area, $end_area, $price)
		);
	} else {
		echo json_encode($count);
	}
}

function addRequest($data)
{
	include 'connection.php';

	$customer_id = clean_id($data['customer_id']);
	$sender_phone = clean_string($data['sender_phone'], 32);
	$weight = clean_string($data['weight'], 32);
	$send_location = clean_string($data['send_location']);
	$end_location = clean_string($data['end_location']);
	$total_fee = clean_string($data['total_fee'], 32);
	$res_phone = clean_string($data['res_phone'], 32);
	$red_address = clean_string($data['red_address']);
	$res_name = clean_string($data['res_name']);

	return db_exec(
		"INSERT INTO request(customer_id, sender_phone, weight, send_location, end_location, total_fee, res_phone, red_address, is_deleted, date_updated, tracking_status, res_name)
	VALUES(?, ?, ?, ?, ?, ?, ?, ?, 0 , now(), 1 , ?)",
		array($customer_id, $sender_phone, $weight, $send_location, $end_location, $total_fee, $res_phone, $red_address, $res_name)
	);
}

function addEmployee($data)
{
	include 'connection.php';

	$name = clean_string($data['name']);
	$email = clean_email($data['email']);
	$phone = clean_string($data['phone'], 32);
	$nic = clean_string($data['nic'], 32);
	$address = clean_string($data['address']);
	$gender = clean_string($data['gender'], 16);
	$password = hash_password($data['password']);
	$branch_id = clean_id($data['branch_id']);

	if ($email === null) {
		echo json_encode(0);
		return;
	}

	$count = checkemployeetByEmail($email);

	if ($count == 0) {

		return db_exec(
			"INSERT INTO employee(name, email, phone, nic, address, gender, password ,is_deleted, branch_id) VALUES(?, ?, ?, ?, ?, ?, ?, 0 , ?)",
			array($name, $email, $phone, $nic, $address, $gender, $password, $branch_id)
		);
	} else {
		echo json_encode($count);
	}
}


//contact
function addMessage($data)
{
	include 'connection.php';

	$name = clean_string($data['name']);
	$email = clean_email($data['email']);
	$subject = clean_string($data['subject']);
	$message = clean_string($data['message'], 2000);


	return db_exec(
		"INSERT INTO contact(name, email, subject, message, date_updated) VALUES(?, ?, ?, ?, now())",
		array($name, $email, $subject, $message)
	);
}


function createCustomer($data)
{
	include 'connection.php';

	$name = clean_string($data['name']);
	$email = clean_email($data['email']);
	$phone = clean_string($data['phone'], 32);
	$nic = clean_string($data['nic'], 32);
	$address = clean_string($data['address']);
	$gender = clean_string($data['gender'], 16);
	$password = hash_password($data['password']);

	if ($email === null) {
		echo json_encode(0);
		return;
	}

	return db_exec(
		"INSERT INTO customer(name, email, phone, nic, address, gender, password, is_deleted) VALUES(?, ?, ?, ?, ?, ?, ?, 0 )",
		array($name, $email, $phone, $nic, $address, $gender, $password)
	);
}
