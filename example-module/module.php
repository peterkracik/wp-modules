<?php

$moduleSettings = [
    'name'            => 'MODULE_NAME',
    'title'           => __( 'MODULE_TITLE', 'custom' ),
    'description'     => __( 'MODULE_TITLE', 'custom' ),
    'render_callback' => 'MODULE_NAME_ESC_module_render_callback',
    'category'        => 'pk',
    'icon'            => 'admin-comments',
    'keywords'        => [],
    'supports'        => [
        'align'             => false,
    ]
];



/**
 * This is the callback that displays the block.
 * function name must be the render callback property
 *
 * @param   array  $block      The block settings and attributes.
 * @param   string $content    The block content (emtpy string).
 * @param   bool   $is_preview True during AJAX preview.
 */
function MODULE_NAME_ESC_module_render_callback( $block, $content = '', $is_preview = false ) {
    $context = Timber::context();
    // Store block values.
    $context['block'] = $block;

    // Store field values.
    $context['data'] = get_fields();

    // Store $is_preview value.
    $context['is_preview'] = $is_preview;

    // Render the block.
    Timber::render( 'template/module.twig', $context );
}