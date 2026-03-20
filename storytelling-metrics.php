<?php
/**
 * Plugin Name: Nivel de storytelling para líderes
 * Description: Gestión del nivel de storytelling para líderes, formulario de registro, dashboard con estadísticas y exportación a PDF.
 * Version: 1.2.3
 * Author: ChanoDEV
 * Text Domain: storytelling-levels
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define constants
define( 'STORYTELLING_PATH', plugin_dir_path( __FILE__ ) );
define( 'STORYTELLING_URL', plugin_dir_url( __FILE__ ) );

function storytelling_get_attachment_id_by_url( $url ) {
    $attachment_id = attachment_url_to_postid( $url );
    if ( $attachment_id ) {
        return $attachment_id;
    }
    
    // If not found in library but file exists in uploads, Sideload it!
    $upload_dir = wp_upload_dir();
    $file_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $url );
    
    if ( file_exists( $file_path ) ) {
        $filetype = wp_check_filetype( basename( $file_path ), null );
        $attachment = array(
            'guid'           => $upload_dir['url'] . '/' . basename( $file_path ), 
            'post_mime_type' => $filetype['type'],
            'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $file_path ) ),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );
        $attachment_id = wp_insert_attachment( $attachment, $file_path, 0 );
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        $attach_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
        wp_update_attachment_metadata( $attachment_id, $attach_data );
        return $attachment_id;
    }
    return false;
}

function storytelling_sync_participant_cpt($row) {
    if (!$row || empty($row->full_name)) return false;
    
    // Determine post type correctly (participant vs participants)
    $pt = '';
    if (post_type_exists('participant')) {
        $pt = 'participant';
    } elseif (post_type_exists('participants')) {
        $pt = 'participants';
    } else {
        return false;
    }

    // Try to find if post already exists by exact title
    $existing_post = get_page_by_title($row->full_name, OBJECT, $pt);
    $post_id = 0;

    if ($existing_post) {
        $post_id = $existing_post->ID;
    } else {
        // Create new post
        $post_data = array(
            'post_title'   => sanitize_text_field($row->full_name),
            'post_status'  => 'publish',
            'post_type'    => $pt,
            'post_author'  => get_current_user_id() ? get_current_user_id() : 1,
        );
        $post_id = wp_insert_post($post_data);
    }

    if ($post_id && !is_wp_error($post_id)) {
        // Sync metadata dictionary (Our DB Column => Their ACF Field)
        $field_mapping = array(
            'company_name'          => 'company_name',
            'industry'              => 'industry',
            'position_cargo'        => 'position',
            'photo_url'             => 'avatar',
            'contact_otros'         => 'others',
            'personal_opinion'      => 'observations',
            'ranking_personal'      => 'personal_ranking',
            'ranking_institutional' => 'institutional_ranking',
            'm_lenguaje_no_verbal'  => 'no_verbal_language',
            'm_dirige_entrevista'   => 'manage_interview',
            'm_mensajes'            => 'memorable_messages',
            'm_preguntas_incisivas' => 'incisive_questions',
            'm_frases_citables'     => 'soundbites_messages',
            'm_usa_datos'           => 'show_data',
            'm_habla_valores'       => 'show_storytelling',
            'dynamic_metrics'       => 'dynamic_metrics',
            'excluded_metrics'      => 'excluded_metrics'
        );

        foreach ($field_mapping as $db_field => $acf_field) {
            if (isset($row->$db_field)) {
                $val = $row->$db_field;
                
                if ($db_field === 'photo_url' && !empty($val)) {
                    $attachment_id = storytelling_get_attachment_id_by_url($val);
                    if ($attachment_id) {
                        $val = $attachment_id;
                        // Always set as WP Featured Image too just in case
                        set_post_thumbnail($post_id, $attachment_id);
                    }
                }

                // Map plugin metric string identifiers to ACF human labels
                $metric_fields = array('m_lenguaje_no_verbal', 'm_dirige_entrevista', 'm_mensajes', 'm_preguntas_incisivas', 'm_frases_citables', 'm_usa_datos', 'm_habla_valores');
                if (in_array($db_field, $metric_fields)) {
                    $metric_map = array(
                        'no-data'      => 'No hay datos',
                        'insuficiente' => 'Manejo insuficiente',
                        'bueno'        => 'Buen vocero/a',
                        'experto'      => 'Experto/a'
                    );
                    if (isset($metric_map[$val])) {
                        $val = $metric_map[$val];
                    }
                }
                
                // Update basic WP meta
                update_post_meta($post_id, $acf_field, $val);
                // Attempt to update via generic update_field if ACF or similar is enabled
                if (function_exists('update_field')) {
                    update_field($acf_field, $val, $post_id);
                }
            }
        }
        return $post_id;
    }
    return false;
}

function storytelling_get_user_average($row) {
    if (!$row) return '0.00';

    $metrics = array(
        'm_lenguaje_no_verbal' => isset($row->m_lenguaje_no_verbal) ? $row->m_lenguaje_no_verbal : 'no-data',
        'm_dirige_entrevista' => isset($row->m_dirige_entrevista) ? $row->m_dirige_entrevista : 'no-data',
        'm_mensajes' => isset($row->m_mensajes) ? $row->m_mensajes : 'no-data',
        'm_preguntas_incisivas' => isset($row->m_preguntas_incisivas) ? $row->m_preguntas_incisivas : 'no-data',
        'm_frases_citables' => isset($row->m_frases_citables) ? $row->m_frases_citables : 'no-data',
        'm_usa_datos' => isset($row->m_usa_datos) ? $row->m_usa_datos : 'no-data',
        'm_habla_valores' => isset($row->m_habla_valores) ? $row->m_habla_valores : 'no-data'
    );

    if (!empty($row->dynamic_metrics)) {
        $dynamic = json_decode($row->dynamic_metrics, true);
        if (is_array($dynamic)) {
            foreach ($dynamic as $dm) {
                if (isset($dm['value'])) {
                    // We also need the name to check if excluded
                    $metrics[$dm['name']] = $dm['value'];
                }
            }
        }
    }
    
    $excluded = array();
    if (!empty($row->excluded_metrics)) {
        $decoded = json_decode($row->excluded_metrics, true);
        if (is_array($decoded)) {
            $excluded = $decoded;
        }
    }
    
    $global_excluded = get_option('storytelling_global_excluded_metrics', array());
    if (is_array($global_excluded)) {
        $excluded = array_merge($excluded, $global_excluded);
    }
    
    $total = 0;
    $count = 0;
    foreach ($metrics as $metric_name => $m) {
        if (in_array($metric_name, $excluded)) {
            continue; // Skip this metric for average
        }

        if ($m === 'bueno' || $m === '2.5') {
            $total += 2.5;
            $count++;
        } elseif ($m === 'experto' || $m === '5') {
            $total += 5;
            $count++;
        } elseif ($m !== 'no-data' && $m !== '') {
            $count++;
        }
    }
    
    $avg = $count > 0 ? ($total / $count) : 0;
    return number_format($avg, 2);
}

// Include required files
require_once STORYTELLING_PATH . 'includes/db-handler.php';
require_once STORYTELLING_PATH . 'includes/form-handler.php';
require_once STORYTELLING_PATH . 'includes/admin-pages.php';
require_once STORYTELLING_PATH . 'includes/pdf-generator.php';
require_once STORYTELLING_PATH . 'includes/cpt-registration.php';

// Ensure database column exists
function storytelling_update_db_check() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'storytelling_management_data';
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

    $col_rank_p = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'ranking_personal'");
    if (empty($col_rank_p)) {
        $wpdb->query("ALTER TABLE $table_name ADD ranking_personal varchar(100) AFTER contact_otros");
    }

    $col_rank_old = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'ranking_institucional'");
    if (!empty($col_rank_old)) {
        $wpdb->query("ALTER TABLE $table_name CHANGE COLUMN ranking_institucional ranking_institutional varchar(100)");
    } else {
        $col_rank_i = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'ranking_institutional'");
        if (empty($col_rank_i)) {
            $wpdb->query("ALTER TABLE $table_name ADD ranking_institutional varchar(100) AFTER ranking_personal");
        }
    }

    $column10 = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'personal_opinion'");
    if (empty($column10)) {
        $wpdb->query("ALTER TABLE $table_name ADD personal_opinion text AFTER contact_otros");
    }

    $column_dyn = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'dynamic_metrics'");
    if (empty($column_dyn)) {
        $wpdb->query("ALTER TABLE $table_name ADD dynamic_metrics text AFTER m_habla_valores");
    }

    $col_ex_metrics = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'excluded_metrics'");
    if (empty($col_ex_metrics)) {
        $wpdb->query("ALTER TABLE $table_name ADD excluded_metrics text AFTER dynamic_metrics");
    }
}
add_action('admin_init', 'storytelling_update_db_check');

/**
 * AJAX Toggle Active Status
 */
add_action( 'wp_ajax_storytelling_toggle_active', 'storytelling_toggle_active_callback' );
function storytelling_toggle_active_callback() {
    check_ajax_referer( 'storytelling_admin_nonce', 'security' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die();

    $id = intval( $_POST['id'] );
    $is_active = intval( $_POST['is_active'] );

    global $wpdb;
    $table_name = $wpdb->prefix . 'storytelling_management_data';
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
function storytelling_enqueue_assets() {
    wp_enqueue_style( 'storytelling-style', STORYTELLING_URL . 'assets/css/style.css', array(), filemtime( STORYTELLING_PATH . 'assets/css/style.css' ) );
}
add_action( 'wp_enqueue_scripts', 'storytelling_enqueue_assets' );

function storytelling_admin_assets( $hook ) {
    // Only load on plugin pages
    if ( strpos( $hook, 'storytelling' ) === false ) {
        return;
    }
    wp_enqueue_script( 'apexcharts', 'https://cdn.jsdelivr.net/npm/apexcharts', array(), '3.45.1', true );
    wp_enqueue_script( 'storytelling-admin-js', STORYTELLING_URL . 'assets/js/admin-dashboard.js', array( 'apexcharts' ), filemtime( STORYTELLING_PATH . 'assets/js/admin-dashboard.js' ), true );
    wp_enqueue_style( 'storytelling-admin-style', STORYTELLING_URL . 'assets/css/style.css', array(), filemtime( STORYTELLING_PATH . 'assets/css/style.css' ) );
    
    // Pass nonce to admin JS
    wp_localize_script( 'storytelling-admin-js', 'storytellingManagerAdmin', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'storytelling_admin_nonce' )
    ) );
}
add_action( 'admin_enqueue_scripts', 'storytelling_admin_assets' );

// Activation Hook
register_activation_hook( __FILE__, 'storytelling_activate' );

function storytelling_activate() {
	// Create Database Table
	storytelling_create_db_table();

	// Create Registration Page
	storytelling_create_registration_page();
}

/**
 * Create the custom database table on activation.
 */
function storytelling_create_db_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'storytelling_management_data';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		company_name varchar(255) NOT NULL,
		photo_url varchar(255),
		full_name varchar(255) NOT NULL,
		position_cargo varchar(255),
		contact_otros text,
		ranking_personal varchar(100),
		ranking_institutional varchar(100),
		personal_opinion text,
		industry varchar(100),
		m_lenguaje_no_verbal varchar(100) DEFAULT 'no-data',
		m_dirige_entrevista varchar(100) DEFAULT 'no-data',
		m_mensajes varchar(100) DEFAULT 'no-data',
		m_preguntas_incisivas varchar(100) DEFAULT 'no-data',
		m_frases_citables varchar(100) DEFAULT 'no-data',
		m_usa_datos varchar(100) DEFAULT 'no-data',
		m_habla_valores varchar(100) DEFAULT 'no-data',
		dynamic_metrics text,
		excluded_metrics text,

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
function storytelling_create_registration_page() {
	$page_title = 'Registro Storytelling Manager';
	$page_content = '[storytelling_registration_form]';
	
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
