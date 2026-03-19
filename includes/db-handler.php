<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle database operations for Storytelling Manager.
 */
class Storytelling_DB {
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'storytelling_management_data';
    }

    public static function insert_data($data) {
        global $wpdb;
        return $wpdb->insert(self::get_table_name(), $data);
    }

    public static function get_all_data() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM " . self::get_table_name() . " ORDER BY created_at DESC");
    }

    public static function get_data_by_id($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . self::get_table_name() . " WHERE id = %d", $id));
    }

    public static function delete_data($id) {
        global $wpdb;
        return $wpdb->delete(self::get_table_name(), array('id' => $id));
    }

    public static function update_data($id, $data) {
        global $wpdb;
        return $wpdb->update(self::get_table_name(), $data, array('id' => $id));
    }
}
