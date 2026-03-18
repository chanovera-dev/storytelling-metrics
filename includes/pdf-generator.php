<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle PDF Export requests.
 * Note: For high-fidelity PDF, we'd typically use Dompdf.
 * For this implementation, we will generate a clean, printable HTML view that triggers the browser's PDF save.
 */

add_action( 'wp_ajax_crisis_export_pdf', 'crisis_handle_pdf_export' );
add_action( 'wp_ajax_nopriv_crisis_export_pdf', 'crisis_handle_pdf_export' ); // Optional for frontend

function crisis_handle_pdf_export() {
    $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
    
    if ( $id ) {
        $record = Crisis_DB::get_data_by_id( $id );
        if ( ! $record ) wp_die( 'Registro no encontrado.' );
        crisis_render_record_pdf( $record );
    } else {
        crisis_render_global_pdf();
    }
    exit;
}

function crisis_render_record_pdf( $row ) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Reporte Crisis Manager - <?php echo esc_html( $row->company_name ); ?></title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.4; color: #333; padding: 20px; font-size: 13px; }
            .header { border-bottom: 2px solid #0073aa; margin-bottom: 15px; padding-bottom: 5px; position: relative; }
            .header h1 { margin: 0; color: #0073aa; font-size: 20px; }
            .header p { margin: 5px 0 0 0; font-size: 11px; color: #777; }
            .photo { position: absolute; top: 0; right: 0; max-width: 100px; border: 1px solid #ccc; padding: 3px; }
            .section { margin-bottom: 15px; clear: both; }
            .section h2 { background: #f4f4f4; padding: 3px 10px; font-size: 15px; border-left: 4px solid #0073aa; margin: 10px 0 8px 0; }
            .grid { display: flex; flex-wrap: wrap; }
            .item { width: 50%; margin-bottom: 5px; }
            .item strong { display: inline-block; width: 140px; font-size: 12px; color: #555; }
            @page { margin: 1cm; }
            @media print {
                .no-print { display: none; }
                body { padding: 0; margin: 0; }
            }
        </style>
    </head>
    <body onload="window.print()">
        <button class="no-print" onclick="window.print()">Imprimir / Guardar como PDF</button>
        
        <div class="header">
            <?php if ( $row->photo_url ) : ?>
                <img src="<?php echo esc_url( $row->photo_url ); ?>" class="photo">
            <?php endif; ?>
            <h1>Reporte Crisis Manager: <?php echo esc_html( $row->company_name ); ?></h1>
            <p>Generado el: <?php echo date('d/m/Y H:i'); ?></p>
        </div>

        <div class="section">
            <h2>Datos Corporativos</h2>
            <div class="grid">
                <div class="item"><strong>Compañía:</strong> <?php echo esc_html( $row->company_name ); ?></div>
                <div class="item"><strong>Industria:</strong> <?php echo esc_html( $row->industry ); ?></div>
            </div>
        </div>

        <div class="section">
            <h2>Contacto y Posición</h2>
            <div class="grid">
                <div class="item"><strong>Nombre Completo:</strong> <?php echo esc_html( $row->full_name ); ?></div>
                <div class="item"><strong>Cargo:</strong> <?php echo esc_html( $row->position_cargo ); ?></div>
                <div class="item" style="grid-column: 1 / -1; margin-top: 10px;"><strong>Otros:</strong><br><?php echo nl2br( esc_html( $row->contact_otros ) ); ?></div>
                <div class="item" style="grid-column: 1 / -1; margin-top: 10px;"><strong>Opinión personal:</strong><br><?php echo nl2br( esc_html( $row->personal_opinion ) ); ?></div>
            </div>
        </div>

        <div class="section">
            <h2>Métricas</h2>
            <p style="margin-top: -10px; margin-bottom: 15px; font-weight: bold; color: #0073aa;">Promedio General: <?php echo crisis_get_user_average($row); ?> / 5.00</p>
            <div class="grid">
                <div class="item"><strong>Lenguaje no verbal:</strong> <?php echo esc_html( $row->m_lenguaje_no_verbal ); ?></div>
                <div class="item"><strong>Dirige la entrevista:</strong> <?php echo esc_html( $row->m_dirige_entrevista ); ?></div>
                <div class="item"><strong>Mensajes memorables:</strong> <?php echo esc_html( $row->m_mensajes ); ?></div>
                <div class="item"><strong>Preguntas incisivas:</strong> <?php echo esc_html( $row->m_preguntas_incisivas ); ?></div>
                <div class="item"><strong>Frases citables:</strong> <?php echo esc_html( $row->m_frases_citables ); ?></div>
                <div class="item"><strong>Usa datos / cifras:</strong> <?php echo esc_html( $row->m_usa_datos ); ?></div>
                <div class="item"><strong>Valores e historias:</strong> <?php echo esc_html( $row->m_habla_valores ); ?></div>

                <?php $is_active_status = (!isset($row->is_active) || $row->is_active) ? 'Activo' : 'Inactivo'; ?>
                <div class="item"><strong>Estado Registrado:</strong> <?php echo $is_active_status; ?></div>
            </div>
        </div>
    </body>
    </html>
    <?php
}

function crisis_render_global_pdf() {
    $all_data = Crisis_DB::get_all_data();
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Reporte Integral Crisis Manager</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.4; color: #333; padding: 20px; font-size: 11px; }
            .main-title { color: #0073aa; border-bottom: 3px solid #0073aa; padding-bottom: 10px; margin-bottom: 30px; text-align: center; font-size: 24px; }
            .record-block { border: 1px solid #eee; margin-bottom: 25px; padding: 15px; page-break-inside: avoid; background: #fff; }
            .record-header { display: flex; align-items: center; border-bottom: 1px solid #0073aa; margin-bottom: 10px; padding-bottom: 5px; }
            .record-header h2 { margin: 0; color: #0073aa; font-size: 16px; flex-grow: 1; }
            .status-badge { padding: 2px 8px; border-radius: 4px; font-size: 10px; color: #fff; font-weight: bold; }
            .status-active { background: #2ecc71; }
            .status-inactive { background: #e74c3c; }
            .data-grid { display: flex; flex-wrap: wrap; }
            .data-col { width: 33.33%; margin-bottom: 8px; }
            .data-col.full { width: 100%; }
            .data-col strong { display: block; font-size: 9px; color: #777; text-transform: uppercase; }
            .record-photo { float: right; max-width: 60px; border-radius: 4px; margin-left: 10px; }
            .section-label { font-weight: bold; color: #555; border-bottom: 1px solid #f0f0f0; margin: 10px 0 5px 0; font-size: 10px; background: #f9f9f9; padding: 2px 5px; }
            @page { margin: 1.5cm; }
            @media print { .no-print { display: none; } }
        </style>
    </head>
    <body onload="window.print()">
        <button class="no-print" onclick="window.print()" style="padding: 10px 20px; background: #0073aa; color: #fff; border: none; border-radius: 4px; cursor: pointer; margin-bottom: 20px;">Imprimir Todo / Guardar PDF</button>
        
        <div class="main-title">Reporte Integral de Registros Crisis Manager</div>
        <p style="text-align: right;">Total de registros: <strong><?php echo count($all_data); ?></strong> | Fecha: <?php echo date('d/m/Y'); ?></p>

        <?php foreach ($all_data as $row) : ?>
            <div class="record-block">
                <div class="record-header">
                    <h2><?php echo esc_html($row->company_name); ?></h2>
                    <?php $is_act = (!isset($row->is_active) || $row->is_active); ?>
                    <span class="status-badge <?php echo $is_act ? 'status-active' : 'status-inactive'; ?>">
                        <?php echo $is_act ? 'ACTIVO' : 'INACTIVO'; ?>
                    </span>
                </div>

                <?php if ( $row->photo_url ) : ?>
                    <img src="<?php echo esc_url( $row->photo_url ); ?>" class="record-photo">
                <?php endif; ?>

                <div class="section-label">Información Corporativa y Contacto</div>
                <div class="data-grid">
                    <div class="data-col"><strong>Compañía</strong><?php echo esc_html($row->company_name); ?></div>
                    <div class="data-col"><strong>Industria</strong><?php echo esc_html($row->industry); ?></div>
                    <div class="data-col"><strong>Contacto</strong><?php echo esc_html($row->full_name); ?></div>
                    <div class="data-col"><strong>Cargo</strong> <?php echo esc_html($row->position_cargo); ?></div>
                    <div class="data-col full" style="margin-top: 10px;"><strong>Otros</strong> <?php echo nl2br(esc_html($row->contact_otros)); ?></div>
                    <div class="data-col full" style="margin-top: 10px;"><strong>Opinión personal</strong> <?php echo nl2br(esc_html($row->personal_opinion)); ?></div>
                </div>

                <div class="section-label">Métricas - Promedio: <?php echo crisis_get_user_average($row); ?> / 5.00</div>
                <div class="data-grid">
                    <div class="data-col"><strong>Lenguaje N.V.</strong><?php echo esc_html($row->m_lenguaje_no_verbal); ?></div>
                    <div class="data-col"><strong>Dirige Entrev.</strong><?php echo esc_html($row->m_dirige_entrevista); ?></div>
                    <div class="data-col"><strong>Mensajes M.</strong><?php echo esc_html($row->m_mensajes); ?></div>
                    <div class="data-col"><strong>Preg. Incisivas</strong><?php echo esc_html($row->m_preguntas_incisivas); ?></div>
                    <div class="data-col"><strong>Frases citables</strong><?php echo esc_html($row->m_frases_citables); ?></div>
                    <div class="data-col"><strong>Usa Datos</strong><?php echo esc_html($row->m_usa_datos); ?></div>
                    <div class="data-col"><strong>Valores y P.V.</strong><?php echo esc_html($row->m_habla_valores); ?></div>

                </div>

            </div>
        <?php endforeach; ?>
    </body>
    </html>
    <?php
}
