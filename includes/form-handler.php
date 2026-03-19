<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'storytelling_registration_form', 'storytelling_render_form' );

/**
 * Handle form submission.
 */
function storytelling_handle_submission() {
    if ( ! isset( $_POST['storytelling_submit'] ) ) {
        return;
    }

    // Verify nonce for security (to be added)
    
    global $wpdb;
    $table_name = Storytelling_DB::get_table_name();

    $data = array(
        'company_name'           => sanitize_text_field( $_POST['company_name'] ),
        'full_name'              => sanitize_text_field( $_POST['full_name'] ),
        'position_cargo'         => sanitize_text_field( $_POST['position_cargo'] ),
        'contact_otros'          => sanitize_textarea_field( $_POST['contact_otros'] ),
        'personal_opinion'       => sanitize_textarea_field( $_POST['personal_opinion'] ),
        'industry'               => sanitize_text_field( $_POST['industry'] ),
        'm_lenguaje_no_verbal'   => sanitize_text_field( $_POST['m_lenguaje_no_verbal'] ),
        'm_dirige_entrevista'    => sanitize_text_field( $_POST['m_dirige_entrevista'] ),
        'm_mensajes'             => sanitize_text_field( $_POST['m_mensajes'] ),
        'm_preguntas_incisivas'  => sanitize_text_field( $_POST['m_preguntas_incisivas'] ),
        'm_frases_citables'      => sanitize_text_field( $_POST['m_frases_citables'] ),
        'm_usa_datos'            => sanitize_text_field( $_POST['m_usa_datos'] ),
        'm_habla_valores'        => sanitize_text_field( $_POST['m_habla_valores'] ),
    );

    $all_metric_names = array(
        'm_lenguaje_no_verbal', 'm_dirige_entrevista', 'm_mensajes', 
        'm_preguntas_incisivas', 'm_frases_citables', 'm_usa_datos', 'm_habla_valores'
    );
    $included_array = isset($_POST['include_metric']) && is_array($_POST['include_metric']) ? $_POST['include_metric'] : array();
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

    $inserted = Storytelling_DB::insert_data( $data );

    if ( $inserted ) {
        add_filter( 'the_content', function( $content ) {
            return '<div class="notice notice-success"><p>¡Registro completado con éxito!</p></div>' . $content;
        }, 1 );
    } else {
        add_filter( 'the_content', function( $content ) {
            return '<div class="notice notice-error"><p>Hubo un error al guardar los datos.</p></div>' . $content;
        }, 1 );
    }
}
add_action( 'template_redirect', 'storytelling_handle_submission' );

function storytelling_render_form() {
    ob_start();
    ?>
    <div class="storytelling-form-wrapper">
        <form id="storytelling-registration-form" method="post" enctype="multipart/form-data">
            <!-- Company Info -->
            <fieldset>
                <legend>Información de la Compañía</legend>
                <div class="form-group">
                    <label for="company_name">Compañía*</label>
                    <input type="text" name="company_name" id="company_name" required>
                </div>
                <div class="form-group">
                    <label for="industry">Industria</label>
                    <input type="text" name="industry" id="industry">
                </div>
            </fieldset>

            <!-- Personal Info -->
            <fieldset>
                <legend>Información Personal</legend>
                <div class="form-group">
                    <label for="full_name">Nombre Completo*</label>
                    <input type="text" name="full_name" id="full_name" required>
                </div>
                <div class="form-group">
                    <label for="photo">Fotografía</label>
                    <input type="file" name="photo" id="photo" accept="image/*">
                </div>
                <div class="form-group">
                    <label for="position_cargo">Cargo</label>
                    <input type="text" name="position_cargo" id="position_cargo">
                </div>
                <div class="form-group">
                    <label for="contact_otros">Otros</label>
                    <textarea name="contact_otros" id="contact_otros" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="personal_opinion">Opinión personal</label>
                    <textarea name="personal_opinion" id="personal_opinion" rows="3"></textarea>
                </div>

            </fieldset>

            <!-- Métricas -->
            <fieldset>
                <legend>Métricas</legend>
                <div class="form-group">
                    <label for="m_lenguaje_no_verbal">Proyecta buen lenguaje no verbal (alta energía, confianza, naturalidad)</label>
                    <select name="m_lenguaje_no_verbal" id="m_lenguaje_no_verbal">
                        <option value="no-data" selected>No hay datos</option>
                        <option value="insuficiente">Manejo insuficiente</option>
                        <option value="bueno">Buen vocero/a</option>
                        <option value="experto">Experto/a</option>
                    </select>
                    <div><label style="font-weight:normal; font-size: 0.9em;"><input type="checkbox" name="include_metric[]" value="m_lenguaje_no_verbal" checked> Incluir en promedio y gráfica</label></div>
                </div>
                <div class="form-group">
                    <label for="m_dirige_entrevista">Dirige la entrevista (guía la conversación hacia sus temas o se limita a responder preguntas)</label>
                    <select name="m_dirige_entrevista" id="m_dirige_entrevista">
                        <option value="no-data" selected>No hay datos</option>
                        <option value="insuficiente">Manejo insuficiente</option>
                        <option value="bueno">Buen vocero/a</option>
                        <option value="experto">Experto/a</option>
                    </select>
                    <div><label style="font-weight:normal; font-size: 0.9em;"><input type="checkbox" name="include_metric[]" value="m_dirige_entrevista" checked> Incluir en promedio y gráfica</label></div>
                </div>
                <div class="form-group">
                    <label for="m_mensajes">Transmite mensajes memorables, únicos, relevantes</label>
                    <select name="m_mensajes" id="m_mensajes">
                        <option value="no-data" selected>No hay datos</option>
                        <option value="insuficiente">Manejo insuficiente</option>
                        <option value="bueno">Buen vocero/a</option>
                        <option value="experto">Experto/a</option>
                    </select>
                    <div><label style="font-weight:normal; font-size: 0.9em;"><input type="checkbox" name="include_metric[]" value="m_mensajes" checked> Incluir en promedio y gráfica</label></div>
                </div>
                <div class="form-group">
                    <label for="m_preguntas_incisivas">Maneja bien las preguntas incisivas</label>
                    <select name="m_preguntas_incisivas" id="m_preguntas_incisivas">
                        <option value="no-data" selected>No hay datos</option>
                        <option value="insuficiente">Manejo insuficiente</option>
                        <option value="bueno">Buen vocero/a</option>
                        <option value="experto">Experto/a</option>
                    </select>
                    <div><label style="font-weight:normal; font-size: 0.9em;"><input type="checkbox" name="include_metric[]" value="m_preguntas_incisivas" checked> Incluir en promedio y gráfica</label></div>
                </div>
                <div class="form-group">
                    <label for="m_frases_citables">Ofrece frases citables, soundbites</label>
                    <select name="m_frases_citables" id="m_frases_citables">
                        <option value="no-data" selected>No hay datos</option>
                        <option value="insuficiente">Manejo insuficiente</option>
                        <option value="bueno">Buen vocero/a</option>
                        <option value="experto">Experto/a</option>
                    </select>
                    <div><label style="font-weight:normal; font-size: 0.9em;"><input type="checkbox" name="include_metric[]" value="m_frases_citables" checked> Incluir en promedio y gráfica</label></div>
                </div>
                <div class="form-group">
                    <label for="m_usa_datos">Usa datos, cifras, ejemplos, visualización</label>
                    <select name="m_usa_datos" id="m_usa_datos">
                        <option value="no-data" selected>No hay datos</option>
                        <option value="insuficiente">Manejo insuficiente</option>
                        <option value="bueno">Buen vocero/a</option>
                        <option value="experto">Experto/a</option>
                    </select>
                    <div><label style="font-weight:normal; font-size: 0.9em;"><input type="checkbox" name="include_metric[]" value="m_usa_datos" checked> Incluir en promedio y gráfica</label></div>
                </div>
                <div class="form-group">
                    <label for="m_habla_valores">Habla de valores, puntos de vista personal, historias</label>
                    <select name="m_habla_valores" id="m_habla_valores">
                        <option value="no-data" selected>No hay datos</option>
                        <option value="insuficiente">Manejo insuficiente</option>
                        <option value="bueno">Buen vocero/a</option>
                        <option value="experto">Experto/a</option>
                    </select>
                    <div><label style="font-weight:normal; font-size: 0.9em;"><input type="checkbox" name="include_metric[]" value="m_habla_valores" checked> Incluir en promedio y gráfica</label></div>
                </div>
            </fieldset>

            <button type="submit" name="storytelling_submit">Enviar Registro</button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
