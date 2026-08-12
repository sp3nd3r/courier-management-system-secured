<?php
// Test fixture for .semgrep/royal-express.yml, run by `semgrep --test`.
//
// Purpose: the fixed application produces ZERO findings for these three rules.
// On its own that is indistinguishable from rules that are broken or never
// loaded. This fixture pins the vulnerable patterns taken from the pre-fix
// codebase, so CI can prove the rules still fire before reporting the real scan
// as clean. `ruleid:` lines MUST match; `ok:` lines MUST NOT.

// ---------------------------------------------------------------- V-01 SQLi

// This query is both V-01 and V-02: it interpolates values AND compares a
// plaintext password, so both rules are expected to fire on it.
// ruleid: royal-v01-sqli-interpolated-value, royal-v02-plaintext-password-in-sql
$viewcat = "SELECT * FROM customer WHERE is_deleted = 0 AND password = '$password' AND customer_id = '$customer_id' ";

// ruleid: royal-v01-sqli-interpolated-value
$q1 = "SELECT * FROM request join customer on customer.customer_id = request.customer_id WHERE request.customer_id = '$customer_id' ";

// ruleid: royal-v01-sqli-interpolated-value, royal-v02-plaintext-password-in-sql
$sql = "INSERT INTO customer(name, email, password, is_deleted) VALUES('$name', '$email', '$password', 0 )";

// ruleid: royal-v01-sqli-interpolated-value
$upd = "UPDATE customer SET name = '$name' WHERE customer_id = '$customer_id'";

// The fixed code parameterises values with ? placeholders.
// ok: royal-v01-sqli-interpolated-value
$safe = "SELECT * FROM customer WHERE customer_id = ?";

// ok: royal-v01-sqli-interpolated-value
$safe2 = "INSERT INTO branch(branch_name, is_deleted) VALUES(?, 0)";

// Identifier interpolation is NOT flagged: a table/column name cannot be bound
// with ?, so the fixed code allow-lists it through clean_identifier() instead.
// ok: royal-v01-sqli-interpolated-value
$safe3 = "UPDATE `$table` SET `$field` = ? where `$idField` = ?";

// ------------------------------------------------- V-02 plaintext password

// ruleid: royal-v01-sqli-interpolated-value, royal-v02-plaintext-password-in-sql
$login = "SELECT * FROM employee WHERE email = '$email' AND password ='$password'";

// ruleid: royal-v01-sqli-interpolated-value, royal-v02-plaintext-password-in-sql
$reg = "INSERT INTO employee(name, email, password ,is_deleted) VALUES('$name', '$email', '$password', 0)";

// The fixed code stores a bcrypt hash and compares with password_verify().
// ok: royal-v02-plaintext-password-in-sql
$hashed = password_hash($plaintext, PASSWORD_BCRYPT, array('cost' => PASSWORD_HASH_COST));

// ok: royal-v02-plaintext-password-in-sql
$check = password_verify($password, $row['password']);

// ------------------------------------------------------------- V-03 IDOR

// ruleid: royal-v03-idor-unverified-owner
$data = getBille($_REQUEST['customer_id']);

// ruleid: royal-v03-idor-unverified-owner
$data2 = getBille($_GET['customer_id']);

// The fixed code derives the id from the session and checks ownership first.
// ok: royal-v03-idor-unverified-owner
$data3 = getBille($bill_customer_id);
