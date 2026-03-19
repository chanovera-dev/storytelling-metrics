<?php

require_once dirname(__DIR__, 3) . '/wp-load.php';

// Find field groups
if (function_exists('acf_get_field_groups')) {
    $groups = acf_get_field_groups();
    foreach ($groups as $group) {
        if (strpos(strtolower($group['title']), 'vocer') !== false || strpos(strtolower($group['title']), 'voceria') !== false || strpos(strtolower($group['title']), 'participant') !== false) {
            $fields = acf_get_fields($group['key']);
            echo "Group Name: " . $group['title'] . "\n";
            if ($fields) {
                foreach ($fields as $field) {
                    echo "Field: " . $field['label'] . " (" . $field['name'] . ") - " . $field['type'] . "\n";
                }
            } else {
                echo "No fields found.\n";
            }
        }
    }
} else {
    echo "ACF not active.\n";
}
