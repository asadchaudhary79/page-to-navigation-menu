<?php
/*
Plugin Name: Page to Navigation Menu 
Description: Adds an option to the page editing meta box to directly add the page to a WordPress navigation menu.
Version: 1.1
Author: Muhammad Asad Mushtaq
Author URI: https://github.com/asadchaudhary79
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once plugin_dir_path(__FILE__) . 'inc/ajax-handlers.php';

// Include necessary scripts and styles only on page edit screen
function nma_enqueue_scripts($hook)
{
    if (!in_array($hook, array('post.php', 'post-new.php'))) {
        return;
    }

    $version = '1.1';

    $screen = get_current_screen();
    if ($screen->post_type !== 'page') {
        return;
    }

    wp_enqueue_style(
        'nma-style',
        plugin_dir_url(__FILE__) . 'css/admin-style.css',
        array(),
        $version
    );


    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script(
        'nma-script',
        plugin_dir_url(__FILE__) . 'js/nma-script.js',
        array('jquery'),
        $version,
        true
    );

    wp_localize_script('nma-script', 'nma_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('nma_nonce'),
        'strings' => array(
            'error' => esc_html__('An error occurred. Please try again.', 'nma'),
            'success' => esc_html__('Operation completed successfully.', 'nma')
        )
    ));
}
add_action('admin_enqueue_scripts', 'nma_enqueue_scripts');

// Add meta box to page editor
function nma_add_meta_box()
{
    if (!current_user_can('edit_theme_options')) {
        return;
    }
    add_meta_box(
        'nma_meta_box',
        __('Add to Navigation Menu', 'nma'),
        'nma_meta_box_callback',
        'page',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'nma_add_meta_box');

function nma_meta_box_callback($post)
{
    if (!current_user_can('edit_theme_options')) {
        return;
    }

    $menus = wp_get_nav_menus();
    if (empty($menus)) {
        echo '<p>' . esc_html__('No menus found. Please create a menu first.', 'nma') . '</p>';
        return;
    }

    wp_nonce_field('nma_meta_box', 'nma_meta_box_nonce');
    include plugin_dir_path(__FILE__) . 'views/meta-box-view.php';
}
