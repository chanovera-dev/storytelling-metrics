<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'crisis_admin_menu' );

function crisis_admin_menu() {
    add_menu_page(
        'Crisis Manager',
        'Crisis Manager',
        'manage_options',
        'crisis-manager',
        'crisis_dashboard_page',
        'dashicons-chart-pie',
        30
    );

    add_submenu_page(
        'crisis-manager',
        'Dashboard de Estadísticas',
        'Estadísticas',
        'manage_options',
        'crisis-manager',
        'crisis_dashboard_page'
    );

    add_submenu_page(
        'crisis-manager',
        'Listado de Registros',
        'Registros',
        'manage_options',
        'crisis-registros',
        'crisis_registros_page'
    );
}


function crisis_dashboard_page() {
    $all_data = Crisis_DB::get_all_data();
    
    // Prepare data for charts with company tracking
    $stats = array(
        'industry' => array(),
        'radar_metrics' => array(
            'Lenguaje no verbal' => array('total' => 0, 'count' => 0),
            'Dirige la entrevista' => array('total' => 0, 'count' => 0),
            'Mensajes memorables' => array('total' => 0, 'count' => 0),
            'Preguntas incisivas' => array('total' => 0, 'count' => 0),
            'Frases citables' => array('total' => 0, 'count' => 0),
            'Usa datos, cifras' => array('total' => 0, 'count' => 0),
            'Valores e historias' => array('total' => 0, 'count' => 0)
        ),
        'radar_participants' => array(),
        'monthly_growth' => array()
    );

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

        $metric_mapping = array(
            'Lenguaje no verbal' => $row->m_lenguaje_no_verbal ?? 'no-data',
            'Dirige la entrevista'             => $row->m_dirige_entrevista ?? 'no-data',
            'Mensajes memorables'    => $row->m_mensajes ?? 'no-data',
            'Preguntas incisivas'       => $row->m_preguntas_incisivas ?? 'no-data',
            'Frases citables'=> $row->m_frases_citables ?? 'no-data',
            'Usa datos, cifras'      => $row->m_usa_datos ?? 'no-data',
            'Valores e historias'      => $row->m_habla_valores ?? 'no-data'
        );

        $participant_scores = array();
        foreach ($metric_mapping as $label => $val) {
            $score = 0;
            if ($val === 'bueno') {
                $score = 2.5;
            } elseif ($val === 'experto') {
                $score = 5;
            }
            $stats['radar_metrics'][$label]['total'] += $score;
            $stats['radar_metrics'][$label]['count']++;
            $participant_scores[] = $score;
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
        $avg = $data['count'] > 0 ? ($data['total'] / $data['count']) : 0;
        $stats['radar_metrics'][$label] = floatval(number_format($avg, 2));
    }

    ?>
    <div class="wrap">
        <h1>Dashboard de Estadísticas</h1>
        
        <div class="crisis-charts-grid">
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
                                        echo '<span style="color: #0073aa; font-weight: bold; font-size: 11px;">Promedio: ' . crisis_get_user_average($row) . '</span>';
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
    wp_localize_script( 'crisis-admin-js', 'crisisManagerStats', $stats );
}

function crisis_registros_page() {
    global $wpdb;
    $table_name = Crisis_DB::get_table_name();

    // Handle Delete
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) ) {
        check_admin_referer( 'crisis_delete_record' );
        Crisis_DB::delete_data( intval( $_GET['id'] ) );
        echo '<div class="updated"><p>Registro eliminado.</p></div>';
    }

    // Handle Add New/Edit Submit
    if ( isset( $_POST['crisis_save_record'] ) ) {
        $data = array(
            'company_name'        => sanitize_text_field( $_POST['company_name'] ),
            'industry'            => sanitize_text_field( $_POST['industry'] ),
            'full_name'           => sanitize_text_field( $_POST['full_name'] ),
            'position_cargo'      => sanitize_text_field( $_POST['position_cargo'] ),
            'contact_otros'       => sanitize_textarea_field( $_POST['contact_otros'] ),
            'personal_opinion'    => sanitize_textarea_field( $_POST['personal_opinion'] ),
            'm_lenguaje_no_verbal'=> sanitize_text_field( $_POST['m_lenguaje_no_verbal'] ),
            'm_dirige_entrevista' => sanitize_text_field( $_POST['m_dirige_entrevista'] ),
            'm_mensajes'          => sanitize_text_field( $_POST['m_mensajes'] ),
            'm_preguntas_incisivas'=> sanitize_text_field( $_POST['m_preguntas_incisivas'] ),
            'm_frases_citables'   => sanitize_text_field( $_POST['m_frases_citables'] ),
            'm_usa_datos'         => sanitize_text_field( $_POST['m_usa_datos'] ),
            'm_habla_valores'     => sanitize_text_field( $_POST['m_habla_valores'] ),
            'is_active'           => isset( $_POST['is_active'] ) ? 1 : 0,
        );

        // Handle Photo Upload
        if ( ! empty( $_FILES['photo']['name'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            $uploaded_file = wp_handle_upload( $_FILES['photo'], array( 'test_form' => false ) );
            if ( isset( $uploaded_file['url'] ) ) {
                $data['photo_url'] = $uploaded_file['url'];
            }
        }

        if ( isset( $_GET['id'] ) && $_GET['action'] === 'edit' ) {
            Crisis_DB::update_data( intval( $_GET['id'] ), $data );
            echo '<div class="updated"><p>Registro actualizado.</p></div>';
        } else {
            Crisis_DB::insert_data( $data );
            echo '<div class="updated"><p>Registro creado con éxito.</p></div>';
        }
    }

    // Handle New/Edit/View View
    $action = isset( $_GET['action'] ) ? $_GET['action'] : '';
    if ( $action === 'edit' || $action === 'new' || $action === 'view' ) {
        $row = ( $action === 'new' ) ? (object) array() : Crisis_DB::get_data_by_id( intval( $_GET['id'] ) );
        
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
                    <p><strong>Otros:</strong> <br><?php echo nl2br( esc_html( $row->contact_otros ) ); ?></p>
                    <p><strong>Opinión personal:</strong> <br><?php echo nl2br( esc_html( $row->personal_opinion ) ); ?></p>


                    <hr>
                    <h3>Métricas</h3>
                    <p style="font-size: 1.2em; color: #0073aa;"><strong>Promedio:</strong> <?php echo crisis_get_user_average($row); ?> / 5.00</p>
                    <p><strong>Proyecta buen lenguaje no verbal:</strong> <?php echo esc_html( $row->m_lenguaje_no_verbal ); ?></p>
                    <p><strong>Dirige la entrevista:</strong> <?php echo esc_html( $row->m_dirige_entrevista ); ?></p>
                    <p><strong>Transmite mensajes memorables:</strong> <?php echo esc_html( $row->m_mensajes ); ?></p>
                    <p><strong>Maneja preguntas incisivas:</strong> <?php echo esc_html( $row->m_preguntas_incisivas ); ?></p>
                    <p><strong>Frases citables:</strong> <?php echo esc_html( $row->m_frases_citables ); ?></p>
                    <p><strong>Usa datos / cifras:</strong> <?php echo esc_html( $row->m_usa_datos ); ?></p>
                    <p><strong>Valores e historias:</strong> <?php echo esc_html( $row->m_habla_valores ); ?></p>
                    <?php $display_active = (!isset($row->is_active) || $row->is_active) ? true : false; ?>
                    <p><strong>Estado:</strong> <?php echo $display_active ? '<span style="color: green;">Activo</span>' : '<span style="color: red;">Inactivo</span>'; ?></p>



                    <p class="submit">
                        <a href="?page=crisis-registros&action=edit&id=<?php echo $row->id; ?>" class="button button-primary">Editar</a>
                        <a href="?page=crisis-registros" class="button">Volver al listado</a>
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
                        <th colspan="2"><h3>Contacto Principal</h3></th>
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
                        <th><label>Otros</label></th>
                        <td><textarea name="contact_otros" class="large-text" rows="3"><?php echo esc_textarea( $row->contact_otros ?? '' ); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label>Opinión personal</label></th>
                        <td><textarea name="personal_opinion" class="large-text" rows="3"><?php echo esc_textarea( $row->personal_opinion ?? '' ); ?></textarea></td>
                    </tr>


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
                        </td>
                    </tr>



                </table>
                <p class="submit">
                    <input type="submit" name="crisis_save_record" class="button button-primary" value="<?php echo ( $action === 'edit' ) ? 'Guardar Cambios' : 'Crear Registro'; ?>">
                    <a href="?page=crisis-registros" class="button">Volver</a>
                </p>
            </form>
        </div>
        <?php
        return;
    }

    // Handling Industry Filter
    $industry_filter = isset( $_GET['industry_filter'] ) ? sanitize_text_field( $_GET['industry_filter'] ) : '';
    
    $all_data_all = Crisis_DB::get_all_data();
    $industries = array_unique( array_column( $all_data_all, 'industry' ) );
    
    // Filter the data if requested
    if ( !empty($industry_filter) ) {
        $all_data = array_filter( $all_data_all, function( $row ) use ( $industry_filter ) {
            return $row->industry === $industry_filter;
        });
    } else {
        $all_data = $all_data_all;
    }
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Registros de Crisis Manager</h1>
        <a href="?page=crisis-registros&action=new" class="page-title-action">Agregar Nuevo</a>
        <a href="<?php echo admin_url( 'admin-ajax.php?action=crisis_export_pdf' ); ?>" class="page-title-action" target="_blank">Exportar Todo a PDF</a>
        
        <div class="tablenav top">
            <form method="get">
                <input type="hidden" name="page" value="crisis-registros">
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
                        <a href="?page=crisis-registros" class="button-link" style="margin-left: 10px;">Limpiar Filtro</a>
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
                                <label class="crisis-switch">
                                    <input type="checkbox" class="crisis-toggle-active" data-id="<?php echo $row->id; ?>" <?php checked( !isset($row->is_active) || $row->is_active, 1 ); ?>>
                                    <span class="slider round"></span>
                                </label>
                            </td>
                            <td><?php echo esc_html( $row->industry ); ?></td>

                            <td>
                                <a href="<?php echo admin_url( 'admin.php?page=crisis-registros&action=view&id=' . $row->id ); ?>">Ver</a> | 
                                <a href="<?php echo admin_url( 'admin.php?page=crisis-registros&action=edit&id=' . $row->id ); ?>">Editar</a> | 
                                <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=crisis-registros&action=delete&id=' . $row->id ), 'crisis_delete_record' ); ?>" onclick="return confirm('¿Estás seguro?')">Eliminar</a> |
                                <a href="<?php echo admin_url( 'admin-ajax.php?action=crisis_export_pdf&id=' . $row->id ); ?>" target="_blank">PDF</a>
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
