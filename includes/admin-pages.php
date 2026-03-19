<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'storytelling_admin_menu' );

function storytelling_admin_menu() {
    add_menu_page(
        'Storytelling Metrics',
        'Storytelling Metrics',
        'manage_options',
        'storytelling-manager',
        'storytelling_dashboard_page',
        'dashicons-chart-pie',
        30
    );

    add_submenu_page(
        'storytelling-manager',
        'Dashboard de Estadísticas',
        'Estadísticas',
        'manage_options',
        'storytelling-manager',
        'storytelling_dashboard_page'
    );

    add_submenu_page(
        'storytelling-manager',
        'Listado de Registros',
        'Registros',
        'manage_options',
        'storytelling-registros',
        'storytelling_registros_page'
    );

    add_submenu_page(
        'storytelling-manager',
        'Ajustes',
        'Ajustes',
        'manage_options',
        'storytelling-settings',
        'storytelling_settings_page'
    );
}

function storytelling_settings_page() {
    if ( isset( $_POST['storytelling_save_settings'] ) ) {
        if ( ! current_user_can( 'manage_options' ) ) wp_die();
        // check_admin_referer('xyz') maybe...

        // Find which ones to exclude globally
        $all_metrics = isset($_POST['known_metrics']) ? $_POST['known_metrics'] : array();
        $enabled_metrics = isset($_POST['global_include_metric']) ? $_POST['global_include_metric'] : array();
        
        $global_excluded = array();
        foreach ( $all_metrics as $mk ) {
            $mk_clean = sanitize_text_field( $mk );
            if ( !in_array($mk_clean, $enabled_metrics) ) {
                $global_excluded[] = $mk_clean;
            }
        }

        update_option( 'storytelling_global_excluded_metrics', $global_excluded );
        echo '<div class="notice notice-success is-dismissible"><p>Configuración general guardada.</p></div>';
    }

    if ( isset( $_POST['storytelling_sync_cpt_bulk'] ) && current_user_can('manage_options') ) {
        $count = 0;
        $all_data = Storytelling_DB::get_all_data();
        if ( !empty($all_data) ) {
            foreach ($all_data as $row) {
                if (storytelling_sync_participant_cpt($row)) {
                    $count++;
                }
            }
        }
        echo '<div class="notice notice-success is-dismissible"><p>Sincronización completada. Se procesaron ' . $count . ' participantes.</p></div>';
    }

    $global_excluded = get_option( 'storytelling_global_excluded_metrics', array() );

    // Gather all fixed and dynamic metric names
    $fixed_metrics = array(
        'm_lenguaje_no_verbal'  => 'Lenguaje no verbal',
        'm_dirige_entrevista'   => 'Dirige la entrevista',
        'm_mensajes'            => 'Transmite mensajes memorables',
        'm_preguntas_incisivas' => 'Maneja preguntas incisivas',
        'm_frases_citables'     => 'Ofrece frases citables (soundbites)',
        'm_usa_datos'           => 'Usa datos, cifras, ejemplos',
        'm_habla_valores'       => 'Habla de valores / historias'
    );
    
    // Scan all rows for unique dynamic metric names
    $all_data = Storytelling_DB::get_all_data();
    $dynamic_metric_names = array();
    foreach ( $all_data as $row ) {
        if ( !empty($row->dynamic_metrics) ) {
            $dm_arr = json_decode($row->dynamic_metrics, true);
            if ( is_array($dm_arr) ) {
                foreach ( $dm_arr as $dm ) {
                    if ( !empty($dm['name']) && !in_array($dm['name'], $dynamic_metric_names) ) {
                        $dynamic_metric_names[] = sanitize_text_field($dm['name']);
                    }
                }
            }
        }
    }

    ?>
    <div class="wrap">
        <h1>Configuración de Métricas</h1>
        <form method="post">
            <p>Selecciona qué métricas estarán habilitadas para mostrarse en el dashboard y en los promedios a nivel general. Desmarcar una métrica anulará obligatoriamente los checkboxes individuales de cada registro.</p>
            <table class="form-table">
                <tr>
                    <th colspan="2"><h3>Métricas Fijas</h3></th>
                </tr>
                <?php foreach ( $fixed_metrics as $key => $label ) : ?>
                    <tr>
                        <th scope="row"><?php echo esc_html($label); ?></th>
                        <td>
                            <input type="hidden" name="known_metrics[]" value="<?php echo esc_attr($key); ?>">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <label class="apple-switch">
                                    <input type="checkbox" name="global_include_metric[]" value="<?php echo esc_attr($key); ?>" <?php checked(!in_array($key, $global_excluded), true); ?>>
                                    <span class="slider"></span>
                                </label>
                                <span>Habilitada (promedios y gráficas)</span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if ( !empty($dynamic_metric_names) ) : ?>
                    <tr>
                        <th colspan="2"><h3>Métricas Dinámicas Detectadas</h3></th>
                    </tr>
                    <?php foreach ( $dynamic_metric_names as $dyn_name ) : ?>
                        <tr>
                            <th scope="row"><?php echo esc_html($dyn_name); ?></th>
                            <td>
                                <input type="hidden" name="known_metrics[]" value="<?php echo esc_attr($dyn_name); ?>">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <label class="apple-switch">
                                        <input type="checkbox" name="global_include_metric[]" value="<?php echo esc_attr($dyn_name); ?>" <?php checked(!in_array($dyn_name, $global_excluded), true); ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <span>Habilitada</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

            </table>
            <p class="submit">
                <input type="submit" name="storytelling_save_settings" class="button button-primary" value="Guardar Ajustes">
            </p>
        </form>

        <hr style="margin: 40px 0;">
        <h2>Sincronización con "Participants"</h2>
        <p>Si el Post Type "Participants" está activo en el sitio, puedes sincronizar todos los registros existentes hacia él (para usarlos con ACF o mostrar sus campos públicamente).</p>
        <form method="post">
            <p class="submit">
                <input type="submit" name="storytelling_sync_cpt_bulk" class="button button-secondary" value="Forzar Sincronización Completa de Registros">
            </p>
        </form>
    </div>
    <?php
}


function storytelling_dashboard_page() {
    $all_data = Storytelling_DB::get_all_data();
    
    // Prepare data for charts with company tracking
    $stats = array(
        'industry' => array(),
        'radar_metrics' => array(),
        'radar_participants' => array(),
        'monthly_growth' => array()
    );
    // Step 1: Discover all global metric names across all users
    $all_radar_labels = array(
        'Lenguaje no verbal',
        'Dirige la entrevista',
        'Mensajes memorables',
        'Preguntas incisivas',
        'Frases citables',
        'Usa datos, cifras',
        'Valores e historias'
    );

    // Scan for dynamic metrics across all active rows
    foreach ($all_data as $row) {
        $is_active = isset($row->is_active) ? (int)$row->is_active : 1;
        if (!$is_active) continue;

        if (!empty($row->dynamic_metrics)) {
            $dynamic = json_decode($row->dynamic_metrics, true);
            if (is_array($dynamic)) {
                foreach($dynamic as $dm) {
                    if (!empty($dm['name']) && !in_array($dm['name'], $all_radar_labels)) {
                        $all_radar_labels[] = $dm['name'];
                    }
                }
            }
        }
    }

    // Initialize global radar metric totals
    foreach ($all_radar_labels as $label) {
        $stats['radar_metrics'][$label] = array('total' => 0, 'count' => 0);
    }

    $stats['radar_participants'] = array();
    $global_excluded = get_option('storytelling_global_excluded_metrics', array());

    foreach ( $all_data as $row ) {
        // Default to active if the column doesn't exist or is NULL
        $is_active = isset($row->is_active) ? (int)$row->is_active : 1;
        if ( ! $is_active ) {
            continue;
        }

        $fields = array(
            'industry' => $row->industry
        );

        foreach ($fields as $key => $val) {
            $val = !empty($val) ? $val : 'no-data';
            if ($val === 'no-data') {
                continue;
            }
            if (!isset($stats[$key][$val])) {
                $stats[$key][$val] = array('count' => 0, 'companies' => array());
            }
            $stats[$key][$val]['count']++;
            $stats[$key][$val]['companies'][] = $row->company_name;
        }

        // Build associative map for this user
        $user_metrics_map = array(
            'Lenguaje no verbal' => $row->m_lenguaje_no_verbal ?? 'no-data',
            'Dirige la entrevista' => $row->m_dirige_entrevista ?? 'no-data',
            'Mensajes memorables' => $row->m_mensajes ?? 'no-data',
            'Preguntas incisivas' => $row->m_preguntas_incisivas ?? 'no-data',
            'Frases citables' => $row->m_frases_citables ?? 'no-data',
            'Usa datos, cifras' => $row->m_usa_datos ?? 'no-data',
            'Valores e historias' => $row->m_habla_valores ?? 'no-data'
        );

        if (!empty($row->dynamic_metrics)) {
            $dynamic = json_decode($row->dynamic_metrics, true);
            if (is_array($dynamic)) {
                foreach($dynamic as $dm) {
                    if (!empty($dm['name'])) {
                        $user_metrics_map[$dm['name']] = $dm['value'] ?? 'no-data';
                    }
                }
            }
        }

        $excluded_opts = array();
        if (!empty($row->excluded_metrics)) {
            $decoded = json_decode($row->excluded_metrics, true);
            if (is_array($decoded)) {
                $excluded_opts = $decoded;
            }
        }
        if (is_array($global_excluded)) {
            $excluded_opts = array_merge($excluded_opts, $global_excluded);
        }
        
        $label_to_db_key = array(
            'Lenguaje no verbal' => 'm_lenguaje_no_verbal',
            'Dirige la entrevista' => 'm_dirige_entrevista',
            'Mensajes memorables' => 'm_mensajes',
            'Preguntas incisivas' => 'm_preguntas_incisivas',
            'Frases citables' => 'm_frases_citables',
            'Usa datos, cifras' => 'm_usa_datos',
            'Valores e historias' => 'm_habla_valores'
        );

        // Array representing scores for all known global labels
        $participant_scores = array();

        foreach ($all_radar_labels as $label) {
            $db_val_key = isset($label_to_db_key[$label]) ? $label_to_db_key[$label] : $label;
            if (in_array($db_val_key, $excluded_opts)) {
                $participant_scores[] = null;
                continue;
            }

            $val = isset($user_metrics_map[$label]) ? $user_metrics_map[$label] : 'no-data';
            $score = 0;
            if ($val === 'bueno' || $val === '2.5') {
                $score = 2.5;
            } elseif ($val === 'experto' || $val === '5') {
                $score = 5;
            }

            if ($val !== 'no-data' && $val !== '') {
                $stats['radar_metrics'][$label]['total'] += $score;
                $stats['radar_metrics'][$label]['count']++;
                $participant_scores[] = $score;
            } else {
                $participant_scores[] = null;
            }
        }

        $stats['radar_participants'][] = array(
            'name' => $row->full_name,
            'data' => $participant_scores
        );



        // Monthly Progression
        $month = date('Y-m', strtotime($row->created_at));
        if (!isset($stats['monthly_growth'][$month])) {
            $stats['monthly_growth'][$month] = 0;
        }
        $stats['monthly_growth'][$month]++;
    }

    // Sort monthly growth by date
    ksort($stats['monthly_growth']);

    // Calculate final radar averages
    foreach ($stats['radar_metrics'] as $label => $data) {
        $avg = $data['count'] > 0 ? floatval(number_format($data['total'] / $data['count'], 2)) : null;
        $stats['radar_metrics'][$label] = $avg;
    }

    ?>
    <div class="wrap">
        <h1>Dashboard de Estadísticas</h1>
        
        <div class="storytelling-charts-grid">
            <?php if ( !empty($stats['radar_metrics']) ) : ?>
                <div class="chart-container full-width">
                    <div>
                        <h3>Promedio Global vs Participantes</h3>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                        <div style="flex: 2; min-width: 400px; display: flex; flex-direction: column; align-items: center; padding: 1rem; padding-top: 0;">
                            <div id="chart-radar-global" style="width: 100%;"></div>
                            <div style="margin-top: 10px; margin-bottom: 10px; display: flex; align-items: center; gap: 10px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                                <span style="font-size: 14px; color: #444; font-weight: 500;">Mostrar Promedio General</span>
                                <label class="apple-switch">
                                    <input type="checkbox" id="toggle-promedio">
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <style>
                                .apple-switch { position: relative; display: inline-block; width: 44px; height: 24px; }
                                .apple-switch input { opacity: 0; width: 0; height: 0; }
                                .apple-switch .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 24px; }
                                .apple-switch .slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 2px; bottom: 2px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
                                .apple-switch input:checked + .slider { background-color: #34c759; }
                                .apple-switch input:checked + .slider:before { transform: translateX(20px); }
                            </style>
                        </div>
                        <div id="list-radar-global" class="chart-list-container" style="flex: 1; min-width: 300px; padding: 1rem;">
                            <h4 style="margin-top: 0; padding-bottom: 10px; color: #555;">Participantes Incluidos</h4>
                            <ul style="margin: 0; padding: 0; list-style: none;">
                                <?php 
                                $count_active = 0;
                                foreach ($all_data as $row) {
                                    $is_active = isset($row->is_active) ? (int)$row->is_active : 1;
                                    if ($is_active) {
                                        $count_active++;
                                        echo '<li style="margin-bottom: 12px; font-size: 13px; line-height: 1.4; display: flex; align-items: flex-start; gap: 8px;">';
                                        echo '<input type="checkbox" class="toggle-participant" value="' . esc_attr($row->full_name) . '" checked style="margin-top: 2px;">';
                                        echo '<div>';
                                        echo '<strong>' . esc_html($row->full_name) . '</strong><br>';
                                        echo '<span style="color: #666; font-size: 11px;">' . esc_html($row->company_name) . '</span> | ';
                                        echo '<span style="color: #0073aa; font-weight: bold; font-size: 11px;">Promedio: ' . storytelling_get_user_average($row) . '</span>';
                                        echo '</div>';
                                        echo '</li>';
                                    }
                                }
                                if ($count_active === 0) {
                                    echo '<li style="color: #999;">No hay participantes activos aún.</li>';
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    // Pass data to external JS
    wp_localize_script( 'storytelling-admin-js', 'storytellingManagerStats', $stats );
}

function storytelling_registros_page() {
    global $wpdb;
    $table_name = Storytelling_DB::get_table_name();

    // Handle Delete
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) ) {
        check_admin_referer( 'storytelling_delete_record' );
        Storytelling_DB::delete_data( intval( $_GET['id'] ) );
        echo '<div class="updated"><p>Registro eliminado.</p></div>';
    }

    // Handle Add New/Edit Submit
    if ( isset( $_POST['storytelling_save_record'] ) ) {
        $data = array(
            'company_name'        => sanitize_text_field( $_POST['company_name'] ),
            'industry'            => sanitize_text_field( $_POST['industry'] ),
            'full_name'           => sanitize_text_field( $_POST['full_name'] ),
            'position_cargo'      => sanitize_text_field( $_POST['position_cargo'] ),
            'contact_otros'       => wp_kses_post( wp_unslash( $_POST['contact_otros'] ?? '' ) ),
            'ranking_personal'    => sanitize_text_field( $_POST['ranking_personal'] ?? '' ),
            'ranking_institutional'=> sanitize_text_field( $_POST['ranking_institutional'] ?? '' ),
            'personal_opinion'    => wp_kses_post( wp_unslash( $_POST['personal_opinion'] ?? '' ) ),
            'm_lenguaje_no_verbal'=> sanitize_text_field( $_POST['m_lenguaje_no_verbal'] ),
            'm_dirige_entrevista' => sanitize_text_field( $_POST['m_dirige_entrevista'] ),
            'm_mensajes'          => sanitize_text_field( $_POST['m_mensajes'] ),
            'm_preguntas_incisivas'=> sanitize_text_field( $_POST['m_preguntas_incisivas'] ),
            'm_frases_citables'   => sanitize_text_field( $_POST['m_frases_citables'] ),
            'm_usa_datos'         => sanitize_text_field( $_POST['m_usa_datos'] ),
            'm_habla_valores'     => sanitize_text_field( $_POST['m_habla_valores'] ),
            'is_active'           => isset( $_POST['is_active'] ) ? 1 : 0,
        );

        $dynamic_metrics_array = array();
        $all_metric_names = array(
            'm_lenguaje_no_verbal', 'm_dirige_entrevista', 'm_mensajes', 
            'm_preguntas_incisivas', 'm_frases_citables', 'm_usa_datos', 'm_habla_valores'
        );
        $included_array = isset($_POST['include_metric']) && is_array($_POST['include_metric']) ? $_POST['include_metric'] : array();

        if (isset($_POST['dynamic_metric_name']) && is_array($_POST['dynamic_metric_name'])) {
            foreach ($_POST['dynamic_metric_name'] as $index => $name) {
                $n = sanitize_text_field($name);
                $isset_val = isset($_POST['dynamic_metric_value'][$index]) ? $_POST['dynamic_metric_value'][$index] : 'no-data';
                $v = sanitize_text_field($isset_val);
                if (!empty($n)) {
                    $dynamic_metrics_array[] = array('name' => $n, 'value' => $v);
                    $all_metric_names[] = $n;
                    if (isset($_POST['include_dynamic_metric'][$index])) {
                        $included_array[] = $n;
                    }
                }
            }
        }
        $data['dynamic_metrics'] = wp_json_encode($dynamic_metrics_array);

        $excluded_metrics = array();
        foreach ($all_metric_names as $mn) {
            if (!in_array($mn, $included_array)) {
                $excluded_metrics[] = $mn;
            }
        }
        $data['excluded_metrics'] = wp_json_encode($excluded_metrics);

        // Handle Photo Upload
        if ( ! empty( $_FILES['photo']['name'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            $uploaded_file = wp_handle_upload( $_FILES['photo'], array( 'test_form' => false ) );
            if ( isset( $uploaded_file['url'] ) ) {
                $data['photo_url'] = $uploaded_file['url'];
            }
        }

        if ( isset( $_GET['id'] ) && $_GET['action'] === 'edit' ) {
            Storytelling_DB::update_data( intval( $_GET['id'] ), $data );
            
            // Auto sync
            $saved_row = (object) $data;
            storytelling_sync_participant_cpt($saved_row);
            
            echo '<div class="updated"><p>Registro actualizado y sincronizado.</p></div>';
        } else {
            Storytelling_DB::insert_data( $data );
            
            // Auto sync
            $saved_row = (object) $data;
            storytelling_sync_participant_cpt($saved_row);
            
            echo '<div class="updated"><p>Registro creado con éxito y sincronizado.</p></div>';
        }
    }

    // Handle New/Edit/View View
    $action = isset( $_GET['action'] ) ? $_GET['action'] : '';
    if ( $action === 'edit' || $action === 'new' || $action === 'view' ) {
        $row = ( $action === 'new' ) ? (object) array() : Storytelling_DB::get_data_by_id( intval( $_GET['id'] ) );
        
        if ( ! $row && $action !== 'new' ) {
            echo '<div class="error"><p>Registro no encontrado.</p></div>';
            return;
        }

        if ( $action === 'view' ) {
            ?>
            <div class="wrap">
                <h1>Detalles del Registro: <?php echo esc_html( $row->company_name ); ?></h1>
                <div class="card" style="max-width: 800px; padding: 20px;">
                    <?php if ( $row->photo_url ) : ?>
                        <img src="<?php echo esc_url( $row->photo_url ); ?>" style="float: right; max-width: 200px; border-radius: 8px; margin-left: 20px;">
                    <?php endif; ?>
                    
                    <h3>Información General</h3>
                    <p><strong>Compañía:</strong> <?php echo esc_html( $row->company_name ); ?></p>
                    <p><strong>Industria:</strong> <?php echo esc_html( $row->industry ); ?></p>

                    <hr>
                    <h3>Contacto</h3>
                    <p><strong>Nombre:</strong> <?php echo esc_html( $row->full_name ); ?></p>
                    <p><strong>Cargo:</strong> <?php echo esc_html( $row->position_cargo ); ?></p>
                    <p><strong>Ranking de reputación personal:</strong> <?php echo esc_html( $row->ranking_personal ?? '' ); ?></p>
                    <p><strong>Ranking de reputación institucional:</strong> <?php echo esc_html( $row->ranking_institutional ?? '' ); ?></p>
                    <div><strong>Presencia y dominio escénico:</strong> <div style="margin-top: 5px;"><?php echo wpautop( wp_kses_post( $row->contact_otros ) ); ?></div></div>
                    <div style="margin-top: 15px;"><strong>Desempeño retórico y contenidos:</strong> <div style="margin-top: 5px;"><?php echo wpautop( wp_kses_post( $row->personal_opinion ) ); ?></div></div>

                    <hr>
                    <h3>Métricas</h3>
                    <p style="font-size: 1.2em; color: #0073aa;"><strong>Promedio:</strong> <?php echo storytelling_get_user_average($row); ?> / 5.00</p>
                    <p><strong>Proyecta buen lenguaje no verbal:</strong> <?php echo esc_html( $row->m_lenguaje_no_verbal ); ?></p>
                    <p><strong>Dirige la entrevista:</strong> <?php echo esc_html( $row->m_dirige_entrevista ); ?></p>
                    <p><strong>Transmite mensajes memorables:</strong> <?php echo esc_html( $row->m_mensajes ); ?></p>
                    <p><strong>Maneja preguntas incisivas:</strong> <?php echo esc_html( $row->m_preguntas_incisivas ); ?></p>
                    <p><strong>Frases citables:</strong> <?php echo esc_html( $row->m_frases_citables ); ?></p>
                    <p><strong>Usa datos / cifras:</strong> <?php echo esc_html( $row->m_usa_datos ); ?></p>
                    <p><strong>Valores e historias:</strong> <?php echo esc_html( $row->m_habla_valores ); ?></p>
                    <?php 
                    if (!empty($row->dynamic_metrics)) {
                        $d_metrics = json_decode($row->dynamic_metrics, true);
                        if (is_array($d_metrics)) {
                            foreach($d_metrics as $dm) {
                                if (!empty($dm['name'])) {
                                    echo '<p><strong>' . esc_html($dm['name']) . ':</strong> ' . esc_html($dm['value']) . '</p>';
                                }
                            }
                        }
                    }
                    ?>
                    <?php $display_active = (!isset($row->is_active) || $row->is_active) ? true : false; ?>
                    <p><strong>Estado:</strong> <?php echo $display_active ? '<span style="color: green;">Activo</span>' : '<span style="color: red;">Inactivo</span>'; ?></p>



                    <p class="submit">
                        <a href="?page=storytelling-registros&action=edit&id=<?php echo $row->id; ?>" class="button button-primary">Editar</a>
                        <a href="?page=storytelling-registros" class="button">Volver al listado</a>
                    </p>
                </div>
            </div>
            <?php
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo ( $action === 'edit' ) ? 'Editar Registro' : 'Agregar Nuevo Registro'; ?></h1>
            <form method="post" enctype="multipart/form-data">
                <table class="form-table">
                    <tr>
                        <th colspan="2"><h3>Configuración y Estado</h3></th>
                    </tr>
                    <tr>
                        <th><label>Registro Activo</label></th>
                        <td>
                            <input type="checkbox" name="is_active" value="1" <?php checked( $row->is_active ?? 1, 1 ); ?>> 
                            <small>(Si está desactivado, no se incluirá en las estadísticas del dashboard)</small>
                        </td>
                    </tr>
                    <tr>
                        <th colspan="2"><h3>Información de la Compañía</h3></th>
                    </tr>
                    <tr>
                        <th><label>Compañía*</label></th>
                        <td><input type="text" name="company_name" value="<?php echo esc_attr( $row->company_name ?? '' ); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label>Industria</label></th>
                        <td><input type="text" name="industry" value="<?php echo esc_attr( $row->industry ?? '' ); ?>" class="regular-text"></td>
                    </tr>
                    
                    <tr>
                        <th colspan="2"><h3>Participante</h3></th>
                    </tr>
                    <tr>
                        <th><label>Nombre Completo*</label></th>
                        <td><input type="text" name="full_name" value="<?php echo esc_attr( $row->full_name ?? '' ); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label>Fotografía</label></th>
                        <td>
                            <?php if ( ! empty( $row->photo_url ) ) : ?>
                                <img src="<?php echo esc_url( $row->photo_url ); ?>" style="max-width: 100px; display: block; margin-bottom: 10px; border-radius: 4px;">
                            <?php endif; ?>
                            <input type="file" name="photo" accept="image/*">
                        </td>
                    </tr>
                    <tr>
                        <th><label>Cargo</label></th>
                        <td><input type="text" name="position_cargo" value="<?php echo esc_attr( $row->position_cargo ?? '' ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label>Ranking de reputación personal</label></th>
                        <td><input type="text" name="ranking_personal" value="<?php echo esc_attr( $row->ranking_personal ?? '' ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label>Ranking de reputación institucional</label></th>
                        <td><input type="text" name="ranking_institutional" value="<?php echo esc_attr( $row->ranking_institutional ?? '' ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label>Presencia y dominio escénico</label></th>
                        <td><?php wp_editor( wp_unslash( $row->contact_otros ?? '' ), 'contact_otros', array( 'textarea_rows' => 5, 'media_buttons' => false ) ); ?></td>
                    </tr>
                    <tr>
                        <th><label>Desempeño retórico y contenidos</label></th>
                        <td><?php wp_editor( wp_unslash( $row->personal_opinion ?? '' ), 'personal_opinion', array( 'textarea_rows' => 5, 'media_buttons' => false ) ); ?></td>
                    </tr>


                    <?php 
                    $excluded_opts = array();
                    if (!empty($row->excluded_metrics)) {
                        $decoded = json_decode($row->excluded_metrics, true);
                        if (is_array($decoded)) {
                            $excluded_opts = $decoded;
                        }
                    }
                    $global_excluded = get_option('storytelling_global_excluded_metrics', array());
                    if (is_array($global_excluded)) {
                        $excluded_opts = array_merge($excluded_opts, $global_excluded);
                    }
                    ?>
                    <tr>
                        <th colspan="2"><h3>Métricas</h3></th>
                    </tr>
                    <tr>
                        <th><label>Lenguaje no verbal</label></th>
                        <td>
                            <select name="m_lenguaje_no_verbal">
                                <option value="no-data" <?php selected( $row->m_lenguaje_no_verbal ?? 'no-data', 'no-data' ); ?>>No hay datos</option>
                                <option value="insuficiente" <?php selected( $row->m_lenguaje_no_verbal ?? '', 'insuficiente' ); ?>>Manejo insuficiente</option>
                                <option value="bueno" <?php selected( $row->m_lenguaje_no_verbal ?? '', 'bueno' ); ?>>Buen vocero/a</option>
                                <option value="experto" <?php selected( $row->m_lenguaje_no_verbal ?? '', 'experto' ); ?>>Experto/a</option>
                            </select>
                            <label style="margin-left: 10px;">
                                <input type="checkbox" name="include_metric[]" value="m_lenguaje_no_verbal" <?php checked(!in_array('m_lenguaje_no_verbal', $excluded_opts), true); ?> <?php echo in_array('m_lenguaje_no_verbal', $global_excluded) ? 'disabled' : ''; ?>>
                                Incluir en promedio y gráfica <?php echo in_array('m_lenguaje_no_verbal', $global_excluded) ? '<span style="color:#d63638;font-size:11px;">(Deshabilitada global)</span>' : ''; ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Dirige la entrevista</label></th>
                        <td>
                            <select name="m_dirige_entrevista">
                                <option value="no-data" <?php selected( $row->m_dirige_entrevista ?? 'no-data', 'no-data' ); ?>>No hay datos</option>
                                <option value="insuficiente" <?php selected( $row->m_dirige_entrevista ?? '', 'insuficiente' ); ?>>Manejo insuficiente</option>
                                <option value="bueno" <?php selected( $row->m_dirige_entrevista ?? '', 'bueno' ); ?>>Buen vocero/a</option>
                                <option value="experto" <?php selected( $row->m_dirige_entrevista ?? '', 'experto' ); ?>>Experto/a</option>
                            </select>
                            <label style="margin-left: 10px;">
                                <input type="checkbox" name="include_metric[]" value="m_dirige_entrevista" <?php checked(!in_array('m_dirige_entrevista', $excluded_opts), true); ?> <?php echo in_array('m_dirige_entrevista', $global_excluded) ? 'disabled' : ''; ?>>
                                Incluir en promedio y gráfica <?php echo in_array('m_dirige_entrevista', $global_excluded) ? '<span style="color:#d63638;font-size:11px;">(Deshabilitada global)</span>' : ''; ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Transmite mensajes memorables</label></th>
                        <td>
                            <select name="m_mensajes">
                                <option value="no-data" <?php selected( $row->m_mensajes ?? 'no-data', 'no-data' ); ?>>No hay datos</option>
                                <option value="insuficiente" <?php selected( $row->m_mensajes ?? '', 'insuficiente' ); ?>>Manejo insuficiente</option>
                                <option value="bueno" <?php selected( $row->m_mensajes ?? '', 'bueno' ); ?>>Buen vocero/a</option>
                                <option value="experto" <?php selected( $row->m_mensajes ?? '', 'experto' ); ?>>Experto/a</option>
                            </select>
                            <label style="margin-left: 10px;">
                                <input type="checkbox" name="include_metric[]" value="m_mensajes" <?php checked(!in_array('m_mensajes', $excluded_opts), true); ?> <?php echo in_array('m_mensajes', $global_excluded) ? 'disabled' : ''; ?>>
                                Incluir en promedio y gráfica <?php echo in_array('m_mensajes', $global_excluded) ? '<span style="color:#d63638;font-size:11px;">(Deshabilitada global)</span>' : ''; ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Maneja preguntas incisivas</label></th>
                        <td>
                            <select name="m_preguntas_incisivas">
                                <option value="no-data" <?php selected( $row->m_preguntas_incisivas ?? 'no-data', 'no-data' ); ?>>No hay datos</option>
                                <option value="insuficiente" <?php selected( $row->m_preguntas_incisivas ?? '', 'insuficiente' ); ?>>Manejo insuficiente</option>
                                <option value="bueno" <?php selected( $row->m_preguntas_incisivas ?? '', 'bueno' ); ?>>Buen vocero/a</option>
                                <option value="experto" <?php selected( $row->m_preguntas_incisivas ?? '', 'experto' ); ?>>Experto/a</option>
                            </select>
                            <label style="margin-left: 10px;">
                                <input type="checkbox" name="include_metric[]" value="m_preguntas_incisivas" <?php checked(!in_array('m_preguntas_incisivas', $excluded_opts), true); ?> <?php echo in_array('m_preguntas_incisivas', $global_excluded) ? 'disabled' : ''; ?>>
                                Incluir en promedio y gráfica <?php echo in_array('m_preguntas_incisivas', $global_excluded) ? '<span style="color:#d63638;font-size:11px;">(Deshabilitada global)</span>' : ''; ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Ofrece frases citables, soundbites</label></th>
                        <td>
                            <select name="m_frases_citables">
                                <option value="no-data" <?php selected( $row->m_frases_citables ?? 'no-data', 'no-data' ); ?>>No hay datos</option>
                                <option value="insuficiente" <?php selected( $row->m_frases_citables ?? '', 'insuficiente' ); ?>>Manejo insuficiente</option>
                                <option value="bueno" <?php selected( $row->m_frases_citables ?? '', 'bueno' ); ?>>Buen vocero/a</option>
                                <option value="experto" <?php selected( $row->m_frases_citables ?? '', 'experto' ); ?>>Experto/a</option>
                            </select>
                            <label style="margin-left: 10px;">
                                <input type="checkbox" name="include_metric[]" value="m_frases_citables" <?php checked(!in_array('m_frases_citables', $excluded_opts), true); ?> <?php echo in_array('m_frases_citables', $global_excluded) ? 'disabled' : ''; ?>>
                                Incluir en promedio y gráfica <?php echo in_array('m_frases_citables', $global_excluded) ? '<span style="color:#d63638;font-size:11px;">(Deshabilitada global)</span>' : ''; ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Usa datos, cifras, ejemplos</label></th>
                        <td>
                            <select name="m_usa_datos">
                                <option value="no-data" <?php selected( $row->m_usa_datos ?? 'no-data', 'no-data' ); ?>>No hay datos</option>
                                <option value="insuficiente" <?php selected( $row->m_usa_datos ?? '', 'insuficiente' ); ?>>Manejo insuficiente</option>
                                <option value="bueno" <?php selected( $row->m_usa_datos ?? '', 'bueno' ); ?>>Buen vocero/a</option>
                                <option value="experto" <?php selected( $row->m_usa_datos ?? '', 'experto' ); ?>>Experto/a</option>
                            </select>
                            <label style="margin-left: 10px;">
                                <input type="checkbox" name="include_metric[]" value="m_usa_datos" <?php checked(!in_array('m_usa_datos', $excluded_opts), true); ?> <?php echo in_array('m_usa_datos', $global_excluded) ? 'disabled' : ''; ?>>
                                Incluir en promedio y gráfica <?php echo in_array('m_usa_datos', $global_excluded) ? '<span style="color:#d63638;font-size:11px;">(Deshabilitada global)</span>' : ''; ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Habla de valores, puntos de vista personal, historias</label></th>
                        <td>
                            <select name="m_habla_valores">
                                <option value="no-data" <?php selected( $row->m_habla_valores ?? 'no-data', 'no-data' ); ?>>No hay datos</option>
                                <option value="insuficiente" <?php selected( $row->m_habla_valores ?? '', 'insuficiente' ); ?>>Manejo insuficiente</option>
                                <option value="bueno" <?php selected( $row->m_habla_valores ?? '', 'bueno' ); ?>>Buen vocero/a</option>
                                <option value="experto" <?php selected( $row->m_habla_valores ?? '', 'experto' ); ?>>Experto/a</option>
                            </select>
                            <label style="margin-left: 10px;">
                                <input type="checkbox" name="include_metric[]" value="m_habla_valores" <?php checked(!in_array('m_habla_valores', $excluded_opts), true); ?> <?php echo in_array('m_habla_valores', $global_excluded) ? 'disabled' : ''; ?>>
                                Incluir en promedio y gráfica <?php echo in_array('m_habla_valores', $global_excluded) ? '<span style="color:#d63638;font-size:11px;">(Deshabilitada global)</span>' : ''; ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2" style="padding: 0;">
                            <div id="dynamic-metrics-container" style="margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #0073aa;">
                                <h4 style="margin-top: 0;">Métricas Adicionales (Personalizadas)</h4>
                                <div id="dynamic-metrics-list">
                                    <?php
                                    if (!empty($row->dynamic_metrics)) {
                                        $d_metrics = json_decode($row->dynamic_metrics, true);
                                        if (is_array($d_metrics)) {
                                            $dyn_index = 0;
                                            foreach($d_metrics as $dm) {
                                                ?>
                                                <div class="dynamic-metric-row" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                                                    <input type="text" name="dynamic_metric_name[]" value="<?php echo esc_attr($dm['name']); ?>" placeholder="Nombre de la métrica" class="regular-text">
                                                    <select name="dynamic_metric_value[]">
                                                        <option value="no-data" <?php selected($dm['value'], 'no-data'); ?>>No hay datos</option>
                                                        <option value="insuficiente" <?php selected($dm['value'], 'insuficiente'); ?>>Manejo insuficiente</option>
                                                        <option value="bueno" <?php selected($dm['value'], 'bueno'); ?>>Buen vocero/a</option>
                                                        <option value="experto" <?php selected($dm['value'], 'experto'); ?>>Experto/a</option>
                                                    </select>
                                                    <label style="margin-left: 10px;">
                                                        <input type="checkbox" name="include_dynamic_metric[<?php echo $dyn_index; ?>]" value="1" <?php checked(!in_array($dm['name'], $excluded_opts), true); ?> <?php echo in_array($dm['name'], $global_excluded) ? 'disabled' : ''; ?>> Mostrar <?php echo in_array($dm['name'], $global_excluded) ? '<span style="color:#d63638;font-size:11px;">(Deshabilitada)</span>' : ''; ?>
                                                    </label>
                                                    <button type="button" class="button remove-dynamic-metric">X</button>
                                                </div>
                                                <?php
                                                $dyn_index++;
                                            }
                                        }
                                    }
                                    ?>
                                </div>
                                <button type="button" id="add-dynamic-metric" class="button">Añadir Métrica Extra</button>
                            </div>
                            
                            <script>
                                let dynamicMetricIndex = <?php echo (isset($d_metrics) && is_array($d_metrics)) ? count($d_metrics) : 0; ?>;
                                document.getElementById('add-dynamic-metric').addEventListener('click', function() {
                                    const container = document.getElementById('dynamic-metrics-list');
                                    const row = document.createElement('div');
                                    row.className = 'dynamic-metric-row';
                                    row.style = 'display: flex; gap: 10px; margin-bottom: 10px; align-items: center;';
                                    row.innerHTML = `
                                        <input type="text" name="dynamic_metric_name[]" placeholder="Nombre de la métrica" class="regular-text">
                                        <select name="dynamic_metric_value[]">
                                            <option value="no-data">No hay datos</option>
                                            <option value="insuficiente">Manejo insuficiente</option>
                                            <option value="bueno">Buen vocero/a</option>
                                            <option value="experto">Experto/a</option>
                                        </select>
                                        <label style="margin-left: 10px;">
                                            <input type="checkbox" name="include_dynamic_metric[${dynamicMetricIndex}]" value="1" checked> Mostrar
                                        </label>
                                        <button type="button" class="button remove-dynamic-metric">X</button>
                                    `;
                                    container.appendChild(row);
                                    dynamicMetricIndex++;
                                });
                                
                                document.getElementById('dynamic-metrics-list').addEventListener('click', function(e) {
                                    if (e.target.classList.contains('remove-dynamic-metric')) {
                                        e.target.closest('.dynamic-metric-row').remove();
                                    }
                                });
                            </script>
                        </td>
                    </tr>



                </table>
                <p class="submit">
                    <input type="submit" name="storytelling_save_record" class="button button-primary" value="<?php echo ( $action === 'edit' ) ? 'Guardar Cambios' : 'Crear Registro'; ?>">
                    <a href="?page=storytelling-registros" class="button">Volver</a>
                </p>
            </form>
        </div>
        <?php
        return;
    }

    // Handling Industry Filter
    $industry_filter = isset( $_GET['industry_filter'] ) ? sanitize_text_field( $_GET['industry_filter'] ) : '';
    
    $all_data_all = Storytelling_DB::get_all_data();
    $industries = array_unique( array_column( $all_data_all, 'industry' ) );
    
    // Filter the data if requested
    if ( !empty($industry_filter) ) {
        $all_data = array_filter( $all_data_all, function( $row ) use ( $industry_filter ) {
            return $row->industry === $industry_filter;
        });
    } else {
        $all_data = $all_data_all;
    }

    usort($all_data, function($a, $b) {
        $rank_a = isset($a->ranking_personal) ? trim($a->ranking_personal) : '';
        $rank_b = isset($b->ranking_personal) ? trim($b->ranking_personal) : '';
        
        if ($rank_a === '' && $rank_b === '') return 0;
        if ($rank_a === '') return 1;
        if ($rank_b === '') return -1;
        
        if (is_numeric($rank_a) && is_numeric($rank_b)) {
            if ((float)$rank_a == (float)$rank_b) return 0;
            return ((float)$rank_a < (float)$rank_b) ? -1 : 1;
        }
        
        return strnatcasecmp($rank_a, $rank_b);
    });
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Registros de Storytelling Manager</h1>
        <a href="?page=storytelling-registros&action=new" class="page-title-action">Agregar Nuevo</a>
        <a href="<?php echo admin_url( 'admin-ajax.php?action=storytelling_export_pdf' ); ?>" class="page-title-action" target="_blank">Exportar Todo a PDF</a>
        
        <div class="tablenav top">
            <form method="get">
                <input type="hidden" name="page" value="storytelling-registros">
                <div class="alignleft actions">
                    <select name="industry_filter">
                        <option value="">Todas las Industrias</option>
                        <?php foreach ( $industries as $industry ) : ?>
                            <?php if ( !empty($industry) ) : ?>
                                <option value="<?php echo esc_attr($industry); ?>" <?php selected($industry_filter, $industry); ?>><?php echo esc_html($industry); ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <input type="submit" class="button" value="Filtrar">
                    <?php if ( !empty($industry_filter) ) : ?>
                        <a href="?page=storytelling-registros" class="button-link" style="margin-left: 10px;">Limpiar Filtro</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <hr class="wp-header-end">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th width="50">ID</th>
                    <th width="60">Foto</th>
                    <th>Compañía</th>
                    <th>Contacto</th>
                    <th width="80">Estado</th>
                    <th>Industria</th>

                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $all_data ) : ?>
                    <?php foreach ( $all_data as $row ) : ?>
                        <tr>
                            <td><?php echo $row->id; ?></td>
                            <td>
                                <?php if ( ! empty( $row->photo_url ) ) : ?>
                                    <img src="<?php echo esc_url( $row->photo_url ); ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%; border: 1px solid #ddd;">
                                <?php else : ?>
                                    <div style="width: 40px; height: 40px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #999;"><span class="dashicons dashicons-admin-users"></span></div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo esc_html( $row->company_name ); ?></strong></td>
                            <td><?php echo esc_html( $row->full_name ); ?> (<?php echo esc_html( $row->position_cargo ); ?>)</td>
                            <td>
                                <label class="storytelling-switch">
                                    <input type="checkbox" class="storytelling-toggle-active" data-id="<?php echo $row->id; ?>" <?php checked( !isset($row->is_active) || $row->is_active, 1 ); ?>>
                                    <span class="slider round"></span>
                                </label>
                            </td>
                            <td><?php echo esc_html( $row->industry ); ?></td>

                            <td>
                                <a href="<?php echo admin_url( 'admin.php?page=storytelling-registros&action=view&id=' . $row->id ); ?>">Ver</a> | 
                                <a href="<?php echo admin_url( 'admin.php?page=storytelling-registros&action=edit&id=' . $row->id ); ?>">Editar</a> | 
                                <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=storytelling-registros&action=delete&id=' . $row->id ), 'storytelling_delete_record' ); ?>" onclick="return confirm('¿Estás seguro?')">Eliminar</a> |
                                <a href="<?php echo admin_url( 'admin-ajax.php?action=storytelling_export_pdf&id=' . $row->id ); ?>" target="_blank">PDF</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="7">No hay registros encontrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
