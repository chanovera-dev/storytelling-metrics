<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the 'participants' Custom Post Type automatically
 */
function storytelling_register_participants_cpt() {
    $labels = array(
        'name'                  => 'Participantes',
        'singular_name'         => 'Participante',
        'menu_name'             => 'Participantes',
        'name_admin_bar'        => 'Participante',
        'all_items'             => 'Todos los Participantes',
        'add_new'               => 'Añadir Nuevo',
        'add_new_item'          => 'Añadir Nuevo Participante',
        'edit_item'             => 'Editar Participante',
        'new_item'              => 'Nuevo Participante',
        'view_item'             => 'Ver Participante',
        'search_items'          => 'Buscar Participantes',
        'not_found'             => 'No se encontraron Participantes.',
        'not_found_in_trash'    => 'No se encontraron Participantes en la papelera.',
    );

    $args = array(
        'label'                 => 'Participante',
        'labels'                => $labels,
        'supports'              => array( 'title', 'thumbnail' ),
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 25,
        'menu_icon'             => 'dashicons-groups',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
        'show_in_rest'          => true, // Enables Gutenberg Editor
    );
    
    // Solo registrarlo si no existe ya para evitar conflictos
    if ( ! post_type_exists( 'participants' ) && ! post_type_exists( 'participant' ) ) {
        register_post_type( 'participants', $args );
    }
}
add_action( 'init', 'storytelling_register_participants_cpt', 0 );

/**
 * Register ACF (Secure Custom Fields) automatically if the plugin is active
 */
function storytelling_register_acf_participants_fields() {
    if ( function_exists('acf_add_local_field_group') ) {
        
        $metric_choices = array(
            'No hay datos'         => 'No hay datos',
            'Manejo insuficiente'  => 'Manejo insuficiente',
            'Buen vocero/a'        => 'Buen vocero/a',
            'Experto/a'            => 'Experto/a',
        );

        acf_add_local_field_group(array(
            'key' => 'group_storytelling_participants',
            'title' => 'Participantes en análisis de vocería (Auto-Generado)',
            'fields' => array(
                array(
                    'key' => 'field_company_name',
                    'label' => 'Compañía',
                    'name' => 'company_name',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_industry',
                    'label' => 'Industria',
                    'name' => 'industry',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_position',
                    'label' => 'Cargo',
                    'name' => 'position',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_avatar',
                    'label' => 'Fotografía',
                    'name' => 'avatar',
                    'type' => 'image',
                    'return_format' => 'id',
                    'preview_size' => 'medium',
                ),
                array(
                    'key' => 'field_others',
                    'label' => 'Presencia y dominio escénico',
                    'name' => 'others',
                    'type' => 'wysiwyg',
                ),
                array(
                    'key' => 'field_observations',
                    'label' => 'Desempeño retórico y contenidos',
                    'name' => 'observations',
                    'type' => 'wysiwyg',
                ),
                array(
                    'key' => 'field_personal_ranking',
                    'label' => 'Ranking de reputación personal',
                    'name' => 'personal_ranking',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_institutional_ranking',
                    'label' => 'Ranking de reputación Institucional',
                    'name' => 'institutional_ranking',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_no_verbal_language',
                    'label' => 'Lenguaje no verbal',
                    'name' => 'no_verbal_language',
                    'type' => 'select',
                    'choices' => $metric_choices,
                    'allow_null' => 1,
                ),
                array(
                    'key' => 'field_manage_interview',
                    'label' => 'Dirige la entrevista',
                    'name' => 'manage_interview',
                    'type' => 'select',
                    'choices' => $metric_choices,
                    'allow_null' => 1,
                ),
                array(
                    'key' => 'field_memorable_messages',
                    'label' => 'Transmite mensajes memorables',
                    'name' => 'memorable_messages',
                    'type' => 'select',
                    'choices' => $metric_choices,
                    'allow_null' => 1,
                ),
                array(
                    'key' => 'field_soundbites_messages',
                    'label' => 'Ofrece citas citables, soundbites',
                    'name' => 'soundbites_messages',
                    'type' => 'select',
                    'choices' => $metric_choices,
                    'allow_null' => 1,
                ),
                array(
                    'key' => 'field_show_data',
                    'label' => 'Usa datos, cifras, ejemplos',
                    'name' => 'show_data',
                    'type' => 'select',
                    'choices' => $metric_choices,
                    'allow_null' => 1,
                ),
                array(
                    'key' => 'field_show_storytelling',
                    'label' => 'Habla de valores, puntos de vista personal, historias',
                    'name' => 'show_storytelling',
                    'type' => 'select',
                    'choices' => $metric_choices,
                    'allow_null' => 1,
                ),
                array(
                    'key' => 'field_incisive_questions',
                    'label' => 'Maneja preguntas incisivas',
                    'name' => 'incisive_questions',
                    'type' => 'select',
                    'choices' => $metric_choices,
                    'allow_null' => 1,
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'participants',
                    ),
                ),
            ),
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'active' => true,
        ));
    }
}
add_action('acf/init', 'storytelling_register_acf_participants_fields');
