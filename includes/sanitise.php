<?php
/**
 * Shared input-validation and output-encoding helpers.
 *
 * Every value taken from $_GET / $_POST / $_REQUEST is validated against the
 * type it is expected to be BEFORE it reaches a query, and every value echoed
 * back into a page is encoded on the way out.
 */

if (!function_exists('clean_id')) {

    /**
     * Database identifiers are always positive integers.
     *
     * @return int|null null when the value is missing or not a positive int
     */
    function clean_id($value)
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, array(
            'options' => array('min_range' => 1),
        ));
        return ($id === false) ? null : $id;
    }

    /**
     * @return string|null null when the value is not a syntactically valid email
     */
    function clean_email($value)
    {
        if (!is_string($value)) {
            return null;
        }
        $email = trim($value);
        if (strlen($email) > 254) {
            return null;
        }
        $valid = filter_var($email, FILTER_VALIDATE_EMAIL);
        return ($valid === false) ? null : $valid;
    }

    /**
     * Trim free text and cap its length so an oversized field cannot be used
     * to push a payload through or exhaust storage.
     */
    function clean_string($value, $maxLength = 255)
    {
        if (!is_string($value)) {
            return '';
        }
        $clean = trim($value);
        if (function_exists('mb_substr')) {
            return mb_substr($clean, 0, $maxLength, 'UTF-8');
        }
        return substr($clean, 0, $maxLength);
    }

    /**
     * Output encoding. Wrap EVERY dynamic value echoed into a page.
     */
    function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Identifiers (table / column names) can never be bound as parameters, so
     * anything user-supplied that lands in that position must be matched
     * against a fixed allowlist instead.
     *
     * @return string|null null when the name is not allowed
     */
    function clean_identifier($value, array $allowed)
    {
        if (!is_string($value)) {
            return null;
        }
        return in_array($value, $allowed, true) ? $value : null;
    }
}
