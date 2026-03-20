<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle PDF Export requests.
 * Note: For high-fidelity PDF, we'd typically use Dompdf.
 * For this implementation, we will generate a clean, printable HTML view that triggers the browser's PDF save.
 */

add_action( 'wp_ajax_storytelling_export_pdf', 'storytelling_handle_pdf_export' );
add_action( 'wp_ajax_nopriv_storytelling_export_pdf', 'storytelling_handle_pdf_export' ); // Optional for frontend

function storytelling_handle_pdf_export() {
    $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
    
    if ( $id ) {
        $record = Storytelling_DB::get_data_by_id( $id );
        if ( ! $record ) wp_die( 'Registro no encontrado.' );
        storytelling_render_record_pdf( $record );
    } else {
        storytelling_render_global_pdf();
    }
    exit;
}

function storytelling_get_participant_css() {
    ob_start();
    ?>
    <style>
        :root {
            --wp--preset--font-size--small: 0.875rem;
            --wp--preset--font-size--medium: 1rem;
            --wp--preset--font-size--large: 1.38rem;
            --wp--preset--font-size--x-large: 1.75rem;
            --wp--preset--color--contrast: #000000;
            --wp--preset--color--focus: #cc0000;
            --wp--preset--color--tertiary: #f5f5f5;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Manrope', Arial, sans-serif; line-height: 1.7; color: var(--wp--preset--color--contrast); margin: 0; padding: 0; }
        .no-print-btn { padding: 10px 20px; background: #0073aa; color: #fff; border: none; border-radius: 4px; cursor: pointer; margin: 20px; font-size: 14px; }
        .site-main { max-width: 1200px; margin: 0 auto; padding: 20px; }
        h1, h2, h3, h4 { margin: 0; line-height: 1.2; font-weight: normal; }
        p { margin-top: 0; }
        .block { width: 100%; }
        
        header.block .content { display: flex; padding: 3rem 0; flex-wrap: nowrap; align-items: center; gap: 2rem; }
        header.block .content .container { flex: 1 1 0%; min-width: 0; width: 50%; }
        header.block .content .container .title-page { font-size: var(--wp--preset--font-size--x-large); text-transform: uppercase; padding-bottom: 1rem; margin-bottom: 1rem; position: relative; font-weight: 700; }
        header.block .content .container .title-page::after { content: ''; position: absolute; bottom: 0; left: 0; width: 50%; height: 2px; background-color: var(--wp--preset--color--contrast); opacity: .25; }
        header.block .content .container .subtitle-page, header.block .content .container .document-title, header.block .content .container .document-subtitle { font-size: var(--wp--preset--font-size--large); text-transform: uppercase; margin-bottom: 0.5rem; }
        header.block .content .container .position, header.block .content .container .company { font-size: var(--wp--preset--font-size--medium); margin-bottom: 0.5rem; }
        header.block .content .container .document-title { margin-top: 2rem; color: var(--wp--preset--color--focus); font-weight: 900; position: relative; display: inline-flex; }
        header.block .content .container .document-title::after { content: '®'; position: absolute; top: 0; right: -1rem; font-weight: 400; font-size: var(--wp--preset--font-size--large); color: var(--wp--preset--color--contrast); }
        header.block .content .container .document-subtitle { font-size: var(--wp--preset--font-size--medium); }
        
        header.block .content .avatar-container { display: flex; justify-content: flex-end; }
        header.block .content .avatar-container .avatar { aspect-ratio: 1/1; object-fit: cover; width: 250px; height: 250px; max-width: 100%; }
        
        .block.metadata-wrapper .content { display: flex; flex-wrap: nowrap; gap: 2rem; padding-bottom: 3rem; }
        .block.metadata-wrapper .content .container { flex: 1 1 0%; min-width: 0; width: 50%; }
        .block.metadata-wrapper .content .container .title-section { font-size: var(--wp--preset--font-size--large); margin-bottom: 2rem; font-weight: bold; }
        .block.metadata-wrapper .content .metadata-container { background-color: var(--wp--preset--color--tertiary); padding: 2rem; }
        
        .data-participant { display: flex; flex-direction: column; gap: 1rem; }
        .data-participant-item { display: flex; flex-direction: column; }
        .data-participant-item-label { font-size: var(--wp--preset--font-size--medium); font-weight: 700; margin-bottom: 0.2rem; margin-top: 0; }
        .data-participant-item-value { font-size: var(--wp--preset--font-size--medium); }
        .data-participant-item-value p { margin: 0 0 0.5rem 0; }
        .data-participant-item-value ul { margin: 0.5rem 0 0 0; padding-left: 1.5rem; }
        
        .block.participant-footer .footer-content { display: flex; justify-content: flex-end; padding-bottom: 3rem; }
        .participant-article { page-break-after: always; padding-bottom: 20px; }
        .participant-article:last-child { page-break-after: auto; }
        
        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; background: transparent; }
            .site-main { width: 100%; max-width: none; border: none; padding: 0; margin: 0; }
            @page { margin: 1cm; size: A4 portrait; }
            
            /* Ajustes compactos para forzar una sola página por registro */
            header.block .content { padding: 1rem 0 !important; gap: 1rem !important; }
            header.block .content .container .title-page { font-size: 1.2rem !important; padding-bottom: 0.5rem !important; margin-bottom: 0.5rem !important; border-width: 1px !important; }
            header.block .content .container .subtitle-page { font-size: 1.1rem !important; margin-bottom: 0.2rem !important; }
            header.block .content .container .position, 
            header.block .content .container .company { font-size: 0.9rem !important; margin-bottom: 0.2rem !important; }
            header.block .content .container .document-title { font-size: 1.2rem !important; margin-top: 1rem !important; }
            header.block .content .container .document-title::after { font-size: 1rem !important; }
            header.block .content .container .document-subtitle { font-size: 1rem !important; }
            header.block .content .avatar-container .avatar { width: 130px !important; height: 130px !important; }

            .block.metadata-wrapper .content { padding-bottom: 0.5rem !important; gap: 1rem !important; }
            .block.metadata-wrapper .content .metadata-container { padding: 1rem !important; }
            .block.metadata-wrapper .content .container .title-section { font-size: 1.2rem !important; margin-bottom: 0.7rem !important; }
            
            .data-participant { gap: 0.4rem !important; }
            .data-participant-item-label { font-size: 0.9rem !important; margin-bottom: 0 !important; }
            .data-participant-item-value, .data-participant-item-value p { font-size: 0.85rem !important; line-height: 1.3 !important; }
            .data-participant-item-value ul { margin: 0.2rem 0 0 0 !important; }
            
            .chart-inner-container { min-height: 280px !important; display: block; }
            .chart-inner-container > div { min-height: 280px !important; height: 280px !important; }
            
            .block.participant-footer .footer-content { padding-bottom: 0 !important; }
            .block.participant-footer img { max-width: 120px !important; height: auto !important; }
            
            .participant-article { padding-bottom: 0 !important; page-break-inside: avoid !important; }
        }
    </style>
    <?php
    return ob_get_clean();
}

function storytelling_render_participant_html( $row, $suffix = '' ) {
    ob_start();
    ?>
    <article class="participant participant-article">
        <header class="block">
            <div class="content">
                <div class="container">
                    <h1 class="title-page">Benchmark de competencias en comunicación</h1>
                    <h2 class="subtitle-page"><?php echo esc_html( $row->full_name ); ?></h2>
                    
                    <?php if ( !empty($row->position_cargo) ) : ?>
                        <h3 class="position"><?php echo esc_html( $row->position_cargo ); ?></h3>
                    <?php endif; ?>
                    
                    <?php if ( !empty($row->company_name) ) : ?>
                        <h3 class="company"><?php echo esc_html( $row->company_name ); ?></h3>
                    <?php endif; ?>
                    
                    <h2 class="document-title">MAPCO</h2>
                    <h2 class="document-subtitle">Mapa de competencias comunicativas</h2>
                </div>
                <div class="container avatar-container">
                    <?php if ( !empty($row->photo_url) ) : ?>
                        <img src="<?php echo esc_url( $row->photo_url ); ?>" class="avatar" alt="Avatar">
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <section class="block metadata-wrapper">
            <div class="content metadata">
                <div class="container metadata-container">
                    <h2 class="title-section">Datos del participante</h2>
                    <div class="data-participant">
                        <div class="data-participant-item">
                            <h3 class="data-participant-item-label">Nombre:</h3>
                            <span class="data-participant-item-value"><?php echo esc_html( $row->full_name ); ?></span>
                        </div>
                        <?php if ( !empty($row->position_cargo) ) : ?>
                        <div class="data-participant-item">
                            <h3 class="data-participant-item-label">Posición:</h3>
                            <span class="data-participant-item-value"><?php echo esc_html( $row->position_cargo ); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ( !empty($row->company_name) ) : ?>
                        <div class="data-participant-item">
                            <h3 class="data-participant-item-label">Empresa:</h3>
                            <span class="data-participant-item-value"><?php echo esc_html( $row->company_name ); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ( !empty($row->ranking_personal) ) : ?>
                        <div class="data-participant-item">
                            <h3 class="data-participant-item-label">Ranking de reputación personal:</h3>
                            <span class="data-participant-item-value"><?php echo esc_html( $row->ranking_personal ); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ( !empty($row->ranking_institutional) ) : ?>
                        <div class="data-participant-item">
                            <h3 class="data-participant-item-label">Ranking de reputación institucional:</h3>
                            <span class="data-participant-item-value"><?php echo esc_html( $row->ranking_institutional ); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ( !empty($row->contact_otros) ) : ?>
                        <div class="data-participant-item">
                            <h3 class="data-participant-item-label">Presencia y dominio escénico:</h3>
                            <span class="data-participant-item-value"><?php echo wpautop(wp_kses_post( $row->contact_otros )); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ( !empty($row->personal_opinion) ) : ?>
                        <div class="data-participant-item">
                            <h3 class="data-participant-item-label">Desempeño retórico y contenidos:</h3>
                            <span class="data-participant-item-value"><?php echo wpautop(wp_kses_post( $row->personal_opinion )); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="container chart-container">
                    <?php
                    $metric_mapping = array(
                        'Lenguaje no verbal'   => $row->m_lenguaje_no_verbal ?? 'no-data',
                        'Dirige la entrevista' => $row->m_dirige_entrevista ?? 'no-data',
                        'Mensajes memorables'  => $row->m_mensajes ?? 'no-data',
                        'Preguntas incisivas'  => $row->m_preguntas_incisivas ?? 'no-data',
                        'Frases citables'      => $row->m_frases_citables ?? 'no-data',
                        'Usa datos, cifras'    => $row->m_usa_datos ?? 'no-data',
                        'Valores e historias'  => $row->m_habla_valores ?? 'no-data'
                    );

                    if (!empty($row->dynamic_metrics)) {
                        $d_metrics = json_decode($row->dynamic_metrics, true);
                        if (is_array($d_metrics)) {
                            foreach($d_metrics as $dm) {
                                if (!empty($dm['name'])) {
                                    $metric_mapping[$dm['name']] = $dm['value'] ?? 'no-data';
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
                    $global_excluded = get_option('storytelling_global_excluded_metrics', array());
                    if (is_array($global_excluded)) {
                        $excluded_opts = array_merge($excluded_opts, $global_excluded);
                    }

                    $label_to_db_key = array(
                        'Lenguaje no verbal'   => 'm_lenguaje_no_verbal',
                        'Dirige la entrevista' => 'm_dirige_entrevista',
                        'Mensajes memorables'  => 'm_mensajes',
                        'Preguntas incisivas'  => 'm_preguntas_incisivas',
                        'Frases citables'      => 'm_frases_citables',
                        'Usa datos, cifras'    => 'm_usa_datos',
                        'Valores e historias'  => 'm_habla_valores'
                    );

                    $categories = array();
                    $data_points = array();
                    $has_data = false;

                    foreach ($metric_mapping as $label => $val) {
                        $db_val_key = isset($label_to_db_key[$label]) ? $label_to_db_key[$label] : $label;
                        if (in_array($db_val_key, $excluded_opts)) {
                            continue;
                        }

                        $categories[] = $label;
                        $score = 0;
                        if ($val === 'Manejo insuficiente' || $val === '1.0' || $val === '1' || strpos(strtolower($val), 'insuficiente') !== false) {
                            $score = 1;
                            $has_data = true;
                        } elseif ($val === 'Buen vocero/a' || $val === '2.5' || strpos(strtolower($val), 'buen') !== false) {
                            $score = 2.5;
                            $has_data = true;
                        } elseif ($val === 'Experto/a' || $val === '5.0' || $val === '5' || strpos(strtolower($val), 'experto') !== false) {
                            $score = 5;
                            $has_data = true;
                        }
                        
                        if ($val !== 'No hay datos' && $val !== 'no-data' && $val !== '') {
                            $data_points[] = $score;
                        } else {
                            array_pop($categories);
                        }
                    }

                    $chart_categories = wp_json_encode($categories);
                    $chart_data = wp_json_encode($data_points);
                    $chart_id = "participant-radar-" . esc_attr($row->id) . $suffix;
                    ?>

                    <?php if ( $has_data ) : ?>
                        <div class="chart-inner-container">
                            <div id="<?php echo $chart_id; ?>" style="width:100%; height:100%; min-height:400px;"></div>
                        </div>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var options = {
                                    series: [{
                                        name: 'Puntaje',
                                        data: <?php echo $chart_data; ?>
                                    }],
                                    chart: {
                                        height: '100%',
                                        width: '100%',
                                        type: 'radar',
                                        toolbar: { show: false },
                                        animations: { enabled: false }
                                    },
                                    labels: <?php echo $chart_categories; ?>,
                                    yaxis: {
                                        min: 0,
                                        max: 5,
                                        tickAmount: 5,
                                        labels: {
                                            formatter: function(val, i) {
                                                if(i % 2 === 0) { return val; } else { return ''; }
                                            }
                                        }
                                    },
                                    markers: { size: 4, colors: ['#fff'], strokeColor: '#0073aa', strokeWidth: 2 },
                                    fill: { opacity: 0.2, colors: ['#0073aa'] },
                                    stroke: { show: true, width: 2, colors: ['#0073aa'], dashArray: 0 }
                                };
                                var chart = new ApexCharts(document.querySelector("#<?php echo $chart_id; ?>"), options);
                                if (typeof window.radarChartsPromises === 'undefined') {
                                    window.radarChartsPromises = [];
                                }
                                window.radarChartsPromises.push(chart.render());
                            });
                        </script>
                    <?php else: ?>
                        <p style="color: #666; font-style: italic;">No hay suficientes datos de métricas para generar la gráfica.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="block participant-footer">
            <div class="content footer-content">
                <?php $logo_url = get_template_directory_uri() . '/assets/img/CE-red-logo.png'; ?>
                <img src="<?php echo esc_url($logo_url); ?>" alt="Logo de Carolina Eslava" width="150" height="61" loading="lazy">
            </div>
        </section>
    </article>
    <?php
    return ob_get_clean();
}

function storytelling_render_record_pdf( $row ) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Benchmark de competencias en comunicación - <?php echo esc_html( $row->full_name ); ?></title>
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <?php echo storytelling_get_participant_css(); ?>
    </head>
    <body>
        <div class="no-print" style="text-align: right; margin-bottom: 20px;">
            <button class="no-print-btn" onclick="window.print()">Imprimir / Guardar como PDF</button>
        </div>
        
        <main id="main" class="site-main" role="main">
            <?php echo storytelling_render_participant_html( $row ); ?>
        </main>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof window.radarChartsPromises !== 'undefined') {
                    Promise.all(window.radarChartsPromises).then(function() {
                        setTimeout(function(){
                            window.print();
                        }, 500);
                    });
                } else {
                    setTimeout(function(){
                        window.print();
                    }, 500);
                }
            });
        </script>
    </body>
    </html>
    <?php
}

function storytelling_render_global_pdf() {
    $all_data = Storytelling_DB::get_all_data();

    // Sort by ranking_personal ascending
    usort($all_data, function($a, $b) {
        $rank_a = isset($a->ranking_personal) ? trim($a->ranking_personal) : '';
        $rank_b = isset($b->ranking_personal) ? trim($b->ranking_personal) : '';
        
        // Put empty at the bottom
        if ($rank_a === '' && $rank_b === '') return 0;
        if ($rank_a === '') return 1;
        if ($rank_b === '') return -1;
        
        // Numeric sort
        if (is_numeric($rank_a) && is_numeric($rank_b)) {
            if ((float)$rank_a == (float)$rank_b) return 0;
            return ((float)$rank_a < (float)$rank_b) ? -1 : 1;
        }
        
        // Alpha-numeric sort if text
        return strnatcasecmp($rank_a, $rank_b);
    });
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Reporte Integral Storytelling Manager</title>
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <?php echo storytelling_get_participant_css(); ?>
    </head>
    <body>
        <div class="no-print" style="text-align: right; margin-bottom: 20px;">
            <button class="no-print-btn" onclick="window.print()">Imprimir Todo / Guardar PDF</button>
        </div>
        
        <main id="main" class="site-main" role="main">
            <?php 
            foreach ($all_data as $index => $row) {
                echo storytelling_render_participant_html( $row, '-g' . $index );
            }
            ?>
        </main>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof window.radarChartsPromises !== 'undefined') {
                    Promise.all(window.radarChartsPromises).then(function() {
                        setTimeout(function(){
                            window.print();
                        }, 1000);
                    });
                } else {
                    setTimeout(function(){
                        window.print();
                    }, 1000);
                }
            });
        </script>
    </body>
    </html>
    <?php
}
