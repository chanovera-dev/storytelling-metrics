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

function storytelling_render_record_pdf( $row ) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Reporte Storytelling Manager - <?php echo esc_html( $row->company_name ); ?></title>
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
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    </head>
    <body>
        <button class="no-print" onclick="window.print()">Imprimir / Guardar como PDF</button>
        
        <div class="header">
            <?php if ( $row->photo_url ) : ?>
                <img src="<?php echo esc_url( $row->photo_url ); ?>" class="photo">
            <?php endif; ?>
            <h1>Reporte Storytelling Manager: <?php echo esc_html( $row->company_name ); ?></h1>
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
                <div class="item"><strong>Ranking Personal:</strong> <?php echo esc_html( $row->ranking_personal ?? '' ); ?></div>
                <div class="item"><strong>Ranking Institucional:</strong> <?php echo esc_html( $row->ranking_institutional ?? '' ); ?></div>
                <div class="item" style="grid-column: 1 / -1; margin-top: 10px;"><strong>Otros:</strong><br><?php echo nl2br( esc_html( $row->contact_otros ) ); ?></div>
                <div class="item" style="grid-column: 1 / -1; margin-top: 10px;"><strong>Observaciones:</strong><br><?php echo nl2br( esc_html( $row->personal_opinion ) ); ?></div>
            </div>
        </div>

        <div class="section">
            <h2>Métricas</h2>
            <p style="margin-top: -10px; margin-bottom: 15px; font-weight: bold; color: #0073aa;">Promedio General: <?php echo storytelling_get_user_average($row); ?> / 5.00</p>
            <div class="grid">
                <div class="item"><strong>Lenguaje no verbal:</strong> <?php echo esc_html( $row->m_lenguaje_no_verbal ); ?></div>
                <div class="item"><strong>Dirige la entrevista:</strong> <?php echo esc_html( $row->m_dirige_entrevista ); ?></div>
                <div class="item"><strong>Mensajes memorables:</strong> <?php echo esc_html( $row->m_mensajes ); ?></div>
                <div class="item"><strong>Preguntas incisivas:</strong> <?php echo esc_html( $row->m_preguntas_incisivas ); ?></div>
                <div class="item"><strong>Frases citables:</strong> <?php echo esc_html( $row->m_frases_citables ); ?></div>
                <div class="item"><strong>Usa datos / cifras:</strong> <?php echo esc_html( $row->m_usa_datos ); ?></div>
                <div class="item"><strong>Valores e historias:</strong> <?php echo esc_html( $row->m_habla_valores ); ?></div>
                <?php 
                if (!empty($row->dynamic_metrics)) {
                    $d_metrics = json_decode($row->dynamic_metrics, true);
                    if (is_array($d_metrics)) {
                        foreach($d_metrics as $dm) {
                            if (!empty($dm['name'])) {
                                echo '<div class="item"><strong>' . esc_html($dm['name']) . ':</strong> ' . esc_html($dm['value']) . '</div>';
                            }
                        }
                    }
                }
                ?>

                <?php $is_active_status = (!isset($row->is_active) || $row->is_active) ? 'Activo' : 'Inactivo'; ?>
                <div class="item"><strong>Estado Registrado:</strong> <?php echo $is_active_status; ?></div>
            </div>
        </div>

        <?php
        $metric_mapping = array(
            'Lenguaje no verbal' => $row->m_lenguaje_no_verbal ?? 'no-data',
            'Dirige la entrevista' => $row->m_dirige_entrevista ?? 'no-data',
            'Mensajes memorables' => $row->m_mensajes ?? 'no-data',
            'Preguntas incisivas' => $row->m_preguntas_incisivas ?? 'no-data',
            'Frases citables' => $row->m_frases_citables ?? 'no-data',
            'Usa datos, cifras' => $row->m_usa_datos ?? 'no-data',
            'Valores e historias' => $row->m_habla_valores ?? 'no-data'
        );

        if (!empty($row->dynamic_metrics)) {
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
            'Lenguaje no verbal' => 'm_lenguaje_no_verbal',
            'Dirige la entrevista' => 'm_dirige_entrevista',
            'Mensajes memorables' => 'm_mensajes',
            'Preguntas incisivas' => 'm_preguntas_incisivas',
            'Frases citables' => 'm_frases_citables',
            'Usa datos, cifras' => 'm_usa_datos',
            'Valores e historias' => 'm_habla_valores'
        );

        $categories = array();
        $data_points = array();
        foreach ($metric_mapping as $label => $val) {
            $db_val_key = isset($label_to_db_key[$label]) ? $label_to_db_key[$label] : $label;
            if (in_array($db_val_key, $excluded_opts)) {
                continue;
            }

            $categories[] = $label;
            $score = 0;
            if ($val === 'bueno' || $val === '2.5') {
                $score = 2.5;
            } elseif ($val === 'experto' || $val === '5') {
                $score = 5;
            }
            if ($val !== 'no-data' && $val !== '') {
                $data_points[] = $score;
            } else {
                array_pop($categories);
            }
        }

        $chart_categories = wp_json_encode($categories);
        $chart_data = wp_json_encode($data_points);
        ?>

        <div class="section">
            <h2>Gráfico de Rendimiento</h2>
            <div id="participant-chart" style="width: 100%; max-width: 600px; margin: 0 auto;"></div>
        </div>

        <script>
            var options = {
                series: [{
                    name: 'Rendimiento',
                    data: <?php echo $chart_data; ?>
                }],
                chart: {
                    height: 400,
                    type: 'radar',
                    toolbar: { show: false },
                    animations: { enabled: false }
                },
                labels: <?php echo $chart_categories; ?>,
                stroke: { width: 2, colors: ['#008ffb'] },
                fill: { opacity: 0.2, colors: ['#008ffb'] },
                markers: { size: 4 },
                yaxis: {
                    min: 0,
                    max: 5,
                    tickAmount: 5
                }
            };

            var chart = new ApexCharts(document.querySelector("#participant-chart"), options);
            chart.render().then(function() {
                // Ensure the chart has finished painting in the DOM before printing
                setTimeout(function(){
                    window.print();
                }, 500);
            });
        </script>
    </body>
    </html>
    <?php
}

function storytelling_render_global_pdf() {
    $all_data = Storytelling_DB::get_all_data();
    $global_excluded = get_option('storytelling_global_excluded_metrics', array());

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
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    </head>
    <body>
        <button class="no-print" onclick="window.print()" style="padding: 10px 20px; background: #0073aa; color: #fff; border: none; border-radius: 4px; cursor: pointer; margin-bottom: 20px;">Imprimir Todo / Guardar PDF</button>
        
        <div class="main-title">Reporte Integral de Registros Storytelling Manager</div>
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
                    <div class="data-col"><strong>Ranking Personal</strong> <?php echo esc_html($row->ranking_personal ?? ''); ?></div>
                    <div class="data-col"><strong>Ranking Institucional</strong> <?php echo esc_html($row->ranking_institutional ?? ''); ?></div>
                    <div class="data-col full" style="margin-top: 10px;"><strong>Otros</strong> <?php echo nl2br(esc_html($row->contact_otros)); ?></div>
                    <div class="data-col full" style="margin-top: 10px;"><strong>Observaciones</strong> <?php echo nl2br(esc_html($row->personal_opinion)); ?></div>
                </div>

                <div class="section-label">Métricas - Promedio: <?php echo storytelling_get_user_average($row); ?> / 5.00</div>
                <div class="data-grid">
                    <div class="data-col"><strong>Lenguaje N.V.</strong><?php echo esc_html($row->m_lenguaje_no_verbal); ?></div>
                    <div class="data-col"><strong>Dirige Entrev.</strong><?php echo esc_html($row->m_dirige_entrevista); ?></div>
                    <div class="data-col"><strong>Mensajes M.</strong><?php echo esc_html($row->m_mensajes); ?></div>
                    <div class="data-col"><strong>Preg. Incisivas</strong><?php echo esc_html($row->m_preguntas_incisivas); ?></div>
                    <div class="data-col"><strong>Frases citables</strong><?php echo esc_html($row->m_frases_citables); ?></div>
                    <div class="data-col"><strong>Usa Datos</strong><?php echo esc_html($row->m_usa_datos); ?></div>
                    <div class="data-col"><strong>Valores y P.V.</strong><?php echo esc_html($row->m_habla_valores); ?></div>
                    <?php 
                    if (!empty($row->dynamic_metrics)) {
                        $d_metrics = json_decode($row->dynamic_metrics, true);
                        if (is_array($d_metrics)) {
                            foreach($d_metrics as $dm) {
                                if (!empty($dm['name'])) {
                                    echo '<div class="data-col"><strong>' . esc_html($dm['name']) . '</strong>' . esc_html($dm['value']) . '</div>';
                                }
                            }
                        }
                    }
                    ?>
                </div>

                <!-- Gráfico Individual -->
                <div class="section-label" style="text-align: center; margin-top: 15px;">Gráfico de Rendimiento</div>
                <div id="chart-participant-<?php echo $row->id; ?>" style="width: 100%; max-width: 400px; margin: 0 auto;"></div>

                <?php
                // Preparar datos de gráfico para este registro
                $metric_mapping = array(
                    'Lenguaje no verbal' => $row->m_lenguaje_no_verbal ?? 'no-data',
                    'Dirige la entrevista' => $row->m_dirige_entrevista ?? 'no-data',
                    'Mensajes memorables' => $row->m_mensajes ?? 'no-data',
                    'Preguntas incisivas' => $row->m_preguntas_incisivas ?? 'no-data',
                    'Frases citables' => $row->m_frases_citables ?? 'no-data',
                    'Usa datos, cifras' => $row->m_usa_datos ?? 'no-data',
                    'Valores e historias' => $row->m_habla_valores ?? 'no-data'
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

                $categories = array();
                $data_points = array();
                foreach ($metric_mapping as $label => $val) {
                    $db_val_key = isset($label_to_db_key[$label]) ? $label_to_db_key[$label] : $label;
                    if (in_array($db_val_key, $excluded_opts)) {
                        continue;
                    }
                    $categories[] = $label;
                    $score = 0;
                    if ($val === 'bueno' || $val === '2.5') {
                        $score = 2.5;
                    } elseif ($val === 'experto' || $val === '5') {
                        $score = 5;
                    }
                    if ($val !== 'no-data' && $val !== '') {
                        $data_points[] = $score;
                    } else {
                        array_pop($categories);
                    }
                }

                if (!isset($all_charts_data)) $all_charts_data = array();
                $all_charts_data[] = array(
                    'id' => $row->id,
                    'categories' => $categories,
                    'data' => $data_points
                );
                ?>

            </div>
        <?php endforeach; ?>

        <script>
            var chartsData = <?php echo isset($all_charts_data) ? wp_json_encode($all_charts_data) : '[]'; ?>;
            var promises = [];
            
            chartsData.forEach(function(item) {
                var el = document.querySelector("#chart-participant-" + item.id);
                if (el) {
                    var options = {
                        series: [{ name: 'Rendimiento', data: item.data }],
                        chart: { height: 320, type: 'radar', toolbar: { show: false }, animations: { enabled: false } },
                        labels: item.categories,
                        stroke: { width: 2, colors: ['#008ffb'] },
                        fill: { opacity: 0.2, colors: ['#008ffb'] },
                        markers: { size: 3 },
                        yaxis: { min: 0, max: 5, tickAmount: 5 }
                    };
                    var chart = new ApexCharts(el, options);
                    promises.push(chart.render());
                }
            });

            Promise.all(promises).then(function() {
                setTimeout(function(){
                    window.print();
                }, 1000);
            });
        </script>
    </body>
    </html>
    <?php
}
