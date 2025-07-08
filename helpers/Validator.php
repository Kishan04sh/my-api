<?php

class Validator {
    public static function validate($data, $rules) {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $rulesArr = explode('|', $ruleString);
            $value = $data[$field] ?? null;

            foreach ($rulesArr as $rule) {
                if ($rule === 'required' && (!isset($data[$field]) || trim($value) === '')) {
                    $errors[$field][] = 'This field is required.';
                }

                if ($rule === 'email' && isset($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = 'Invalid email format.';
                }

                if (str_starts_with($rule, 'min:') && isset($value)) {
                    $min = (int) explode(':', $rule)[1];
                    if (strlen($value) < $min) {
                        $errors[$field][] = "Minimum length is $min.";
                    }
                }

                if (str_starts_with($rule, 'in:') && isset($value)) {
                    $allowed = explode(',', explode(':', $rule)[1]);
                    if (!in_array($value, $allowed)) {
                        $errors[$field][] = "Value must be one of: " . implode(', ', $allowed);
                    }
                }
            }
        }

        return $errors;
    }
}
