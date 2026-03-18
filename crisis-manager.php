<?php
/**
 * Plugin Name: Nivel de storytelling para líderes
 * Description: Gestión del nivel de storytelling para líderes, formulario de registro, dashboard con estadísticas y exportación a PDF.
 * Version: 1.0.0
 * Author: ChanoDEV
 * Text Domain: storytelling-levels
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define constants
define( 'CRISIS_PATH', plugin_dir_path( __FILE__ ) );
define( 'CRISIS_URL', plugin_dir_url( __FILE__ ) );

function crisis_get_user_average($row) {
    if (!$row) return '0.00';

    $metrics = array(
        isset($row->m_lenguaje_no_verbal) ? $row->m_lenguaje_no_verbal : 'no-data',
        isset($row->m_dirige_entrevista) ? $row->m_dirige_entrevista : 'no-data',
        isset($row->m_mensajes) ? $row->m_mensajes : 'no-data',
        isset($row->m_preguntas_incisivas) ? $row->m_preguntas_incisivas : 'no-data',
        isset($row->m_frases_citables) ? $row->m_frases_citables : 'no-data',
        isset($row->m_usa_datos) ? $row->m_usa_datos : 'no-data',
        isset($row->m_habla_valores) ? $row->m_habla_valores : 'no-data'
    );
    
    $total = 0;
    foreach ($metrics as $m) {
        if ($m === 'bueno') {
            $total += 2.5;
        } elseif ($m === 'experto') {
            $total += 5;
        }
    }
    
    $avg = $total / count($metrics);
    return number_format($avg, 2);
}

// Include required files
require_once CRISIS_PATH . 'includes/db-handler.php';
require_once CRISIS_PATH . 'includes/form-handler.php';
require_once CRISIS_PATH . 'includes/admin-pages.php';
require_once CRISIS_PATH . 'includes/pdf-generator.php';

// Ensure database column exists
function crisis_update_db_check() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'crisis_management_data';
    $column = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'is_active'");
    if (empty($column)) {
        $wpdb->query("ALTER TABLE $table_name ADD is_active tinyint(1) DEFAULT 1 AFTER drill_frequency");
        // Also update existing rows to be active
        $wpdb->query("UPDATE $table_name SET is_active = 1 WHERE is_active IS NULL");
    }
    
    $column2 = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'm_lenguaje_no_verbal'");
    if (empty($column2)) {
        $wpdb->query("ALTER TABLE $table_name ADD m_lenguaje_no_verbal varchar(100) DEFAULT 'no-data' AFTER industry");
    }

    $column3 = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'm_dirige_entrevista'");
    if (empty($column3)) {
        $wpdb->query("ALTER TABLE $table_name ADD m_dirige_entrevista varchar(100) DEFAULT 'no-data' AFTER m_lenguaje_no_verbal");
    }

    $column4 = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'm_mensajes'");
    if (empty($column4)) {
        $wpdb->query("ALTER TABLE $table_name ADD m_mensajes varchar(100) DEFAULT 'no-data' AFTER m_dirige_entrevista");
    }

    $column5 = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'm_preguntas_incisivas'");
    if (empty($column5)) {
        $wpdb->query("ALTER TABLE $table_name ADD m_preguntas_incisivas varchar(100) DEFAULT 'no-data' AFTER m_mensajes");
    }

    $column6 = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'm_frases_citables'");
    if (empty($column6)) {
        $wpdb->query("ALTER TABLE $table_name ADD m_frases_citables varchar(100) DEFAULT 'no-data' AFTER m_preguntas_incisivas");
    }

    $column7 = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'm_usa_datos'");
    if (empty($column7)) {
        $wpdb->query("ALTER TABLE $table_name ADD m_usa_datos varchar(100) DEFAULT 'no-data' AFTER m_frases_citables");
    }

    $column8 = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'm_habla_valores'");
    if (empty($column8)) {
        $wpdb->query("ALTER TABLE $table_name ADD m_habla_valores varchar(100) DEFAULT 'no-data' AFTER m_usa_datos");
    }

    $column9 = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'contact_otros'");
    if (empty($column9)) {
        $wpdb->query("ALTER TABLE $table_name ADD contact_otros text AFTER position_cargo");
    }

    $column10 = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'personal_opinion'");
    if (empty($column10)) {
        $wpdb->query("ALTER TABLE $table_name ADD personal_opinion text AFTER contact_otros");
    }
}
add_action('admin_init', 'crisis_update_db_check');

/**
 * AJAX Toggle Active Status
 */
add_action( 'wp_ajax_crisis_toggle_active', 'crisis_toggle_active_callback' );
function crisis_toggle_active_callback() {
    check_ajax_referer( 'crisis_admin_nonce', 'security' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die();

    $id = intval( $_POST['id'] );
    $is_active = intval( $_POST['is_active'] );

    global $wpdb;
    $table_name = $wpdb->prefix . 'crisis_management_data';
    $updated = $wpdb->update( $table_name, array( 'is_active' => $is_active ), array( 'id' => $id ) );
    
    if ( $updated !== false ) {
        wp_send_json_success();
    } else {
        wp_send_json_error();
    }
}

/**
 * Enqueue scripts and styles.
 */
function crisis_enqueue_assets() {
    wp_enqueue_style( 'crisis-style', CRISIS_URL . 'assets/css/style.css' );
}
add_action( 'wp_enqueue_scripts', 'crisis_enqueue_assets' );

function crisis_admin_assets( $hook ) {
    // Only load on plugin pages
    if ( strpos( $hook, 'crisis' ) === false ) {
        return;
    }
    wp_enqueue_script( 'apexcharts', 'https://cdn.jsdelivr.net/npm/apexcharts', array(), '3.45.1', true );
    wp_enqueue_script( 'crisis-admin-js', CRISIS_URL . 'assets/js/admin-dashboard.js', array( 'apexcharts' ), '1.0.0', true );
    wp_enqueue_style( 'crisis-admin-style', CRISIS_URL . 'assets/css/style.css' );
    
    // Pass nonce to admin JS
    wp_localize_script( 'crisis-admin-js', 'crisisManagerAdmin', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'crisis_admin_nonce' )
    ) );
}
add_action( 'admin_enqueue_scripts', 'crisis_admin_assets' );

// Activation Hook
register_activation_hook( __FILE__, 'crisis_activate' );

function crisis_activate() {
	// Create Database Table
	crisis_create_db_table();

	// Create Registration Page
	crisis_create_registration_page();
}

/**
 * Create the custom database table on activation.
 */
function crisis_create_db_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'crisis_management_data';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		company_name varchar(255) NOT NULL,
		photo_url varchar(255),
		full_name varchar(255) NOT NULL,
		position_cargo varchar(255),
		contact_otros text,
		personal_opinion text,
		industry varchar(100),
		m_lenguaje_no_verbal varchar(100) DEFAULT 'no-data',
		m_dirige_entrevista varchar(100) DEFAULT 'no-data',
		m_mensajes varchar(100) DEFAULT 'no-data',
		m_preguntas_incisivas varchar(100) DEFAULT 'no-data',
		m_frases_citables varchar(100) DEFAULT 'no-data',
		m_usa_datos varchar(100) DEFAULT 'no-data',
		m_habla_valores varchar(100) DEFAULT 'no-data',

		is_active tinyint(1) DEFAULT 1,
		created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}

/**
 * Create the registration page if it doesn't exist.
 */
function crisis_create_registration_page() {
	$page_title = 'Registro Crisis Manager';
	$page_content = '[crisis_registration_form]';
	
    // It's better to check by path or meta in a real scenario, but title is ok for now
	$page_check = get_page_by_title( $page_title );

	if ( ! isset( $page_check->ID ) ) {
		$new_page = array(
			'post_type'    => 'page',
			'post_title'   => $page_title,
			'post_content' => $page_content,
			'post_status'  => 'publish',
			'post_author'  => 1,
		);
		wp_insert_post( $new_page );
	}
}
