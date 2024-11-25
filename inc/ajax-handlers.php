<?php
// Handle AJAX request to add page to menu
function nma_add_page_to_menu()
{
    // Verify nonce and capabilities
    if (!check_ajax_referer('nma_nonce', 'nonce', false) || !current_user_can('edit_theme_options')) {
        wp_send_json_error(array('message' => __('Permission denied.', 'nma')), 403);
    }

    // Validate and sanitize inputs
    $page_id = filter_input(INPUT_POST, 'page_id', FILTER_VALIDATE_INT);
    $menu_id = filter_input(INPUT_POST, 'menu_id', FILTER_VALIDATE_INT);
    $parent_item_id = filter_input(INPUT_POST, 'parent_item_id', FILTER_VALIDATE_INT);

    if (!$page_id || !$menu_id) {
        wp_send_json_error(array('message' => __('Invalid parameters.', 'nma')), 400);
    }

    // Verify the menu exists
    $menu = wp_get_nav_menu_object($menu_id);
    if (!$menu) {
        wp_send_json_error(array('message' => __('Menu not found.', 'nma')), 404);
    }

    // Add the menu item
    $menu_item_data = array(
        'menu-item-object-id' => $page_id,
        'menu-item-object' => 'page',
        'menu-item-type' => 'post_type',
        'menu-item-status' => 'publish',
        'menu-item-parent-id' => $parent_item_id ?: 0
    );

    $menu_item_id = wp_update_nav_menu_item($menu_id, 0, $menu_item_data);

    if (is_wp_error($menu_item_id)) {
        wp_send_json_error(array('message' => $menu_item_id->get_error_message()), 500);
    }

    wp_send_json_success(array(
        'message' => __('Page added to menu successfully.', 'nma'),
        'menu_item_id' => $menu_item_id
    ));
}
add_action('wp_ajax_nma_add_page_to_menu', 'nma_add_page_to_menu');

// Handle AJAX request to delete menu item
function nma_delete_menu_item()
{
    if (!check_ajax_referer('nma_nonce', 'nonce', false) || !current_user_can('edit_theme_options')) {
        wp_send_json_error(array('message' => __('Permission denied.', 'nma')), 403);
    }

    $menu_item_id = filter_input(INPUT_POST, 'menu_item_id', FILTER_VALIDATE_INT);

    if (!$menu_item_id) {
        wp_send_json_error(array('message' => __('Invalid menu item ID.', 'nma')), 400);
    }

    $result = wp_delete_post($menu_item_id, true);

    if (!$result) {
        wp_send_json_error(array('message' => __('Failed to delete menu item.', 'nma')), 500);
    }

    wp_send_json_success(array('message' => __('Menu item deleted successfully.', 'nma')));
}
add_action('wp_ajax_nma_delete_menu_item', 'nma_delete_menu_item');

// Fetch menu structure
function nma_fetch_menu_structure()
{
    if (!check_ajax_referer('nma_nonce', 'nonce', false) || !current_user_can('edit_theme_options')) {
        wp_send_json_error(array('message' => __('Permission denied.', 'nma')), 403);
    }

    $menu_id = filter_input(INPUT_POST, 'menu_id', FILTER_VALIDATE_INT);

    if (!$menu_id) {
        wp_send_json_error(array('message' => __('Invalid menu ID.', 'nma')), 400);
    }

    $menu_items = wp_get_nav_menu_items($menu_id);

    if (is_wp_error($menu_items)) {
        wp_send_json_error(array('message' => $menu_items->get_error_message()), 500);
    }

    $response = array(
        'menu_structure' => $menu_items ? nma_build_menu_structure($menu_items) : '',
        'menu_items_options' => $menu_items ? nma_build_menu_items_options($menu_items) : ''
    );

    wp_send_json_success($response);
}
add_action('wp_ajax_nma_fetch_menu_structure', 'nma_fetch_menu_structure');

function nma_build_menu_structure($menu_items)
{
    $menu_items_by_parent = array();
    foreach ($menu_items as $menu_item) {
        $menu_items_by_parent[$menu_item->menu_item_parent][] = $menu_item;
    }
    return nma_build_menu_tree($menu_items_by_parent, 0);
}

function nma_build_menu_items_options($menu_items)
{
    $output = '<option value="0">' . esc_html__('No Parent', 'nma') . '</option>';
    foreach ($menu_items as $menu_item) {
        $output .= sprintf(
            '<option value="%d">%s</option>',
            esc_attr($menu_item->ID),
            esc_html($menu_item->title)
        );
    }
    return $output;
}

function nma_build_menu_tree($menu_items_by_parent, $parent_id)
{
    if (!isset($menu_items_by_parent[$parent_id])) {
        return '';
    }

    $output = '<ul>';
    foreach ($menu_items_by_parent[$parent_id] as $menu_item) {
        $output .= sprintf(
            '<li id="menu-item-%1$d" class="nma-menu-item" data-id="%1$d" data-parent="%2$d">%3$s
                <span class="nma-delete-menu-item" data-id="%1$d">×</span>%4$s
            </li>',
            esc_attr($menu_item->ID),
            esc_attr($menu_item->menu_item_parent),
            esc_html($menu_item->title),
            nma_build_menu_tree($menu_items_by_parent, $menu_item->ID)
        );
    }
    $output .= '</ul>';
    return $output;
}

// Save menu order
function nma_save_menu_order()
{
    if (!check_ajax_referer('nma_nonce', 'nonce', false) || !current_user_can('edit_theme_options')) {
        wp_send_json_error(array('message' => __('Permission denied.', 'nma')), 403);
    }

    // Safely get the order data
    $raw_order = filter_input(INPUT_POST, 'order');
    if (!$raw_order) {
        wp_send_json_error(array('message' => __('No order data received.', 'nma')), 400);
    }

    // Handle both string and array inputs
    if (is_string($raw_order)) {
        $order = json_decode(stripslashes($raw_order), true);
    } else {
        $order = json_decode(json_encode($raw_order), true); // Normalize array input
    }

    if (!is_array($order)) {
        wp_send_json_error(array(
            'message' => __('Invalid order data format.', 'nma'),
            'debug' => json_last_error_msg()
        ), 400);
    }

    $success = true;
    $errors = array();

    foreach ($order as $index => $item) {
        // Validate item structure
        if (!isset($item['id'])) {
            continue;
        }

        $item_id = absint($item['id']);
        $parent_id = isset($item['parent']) ? absint($item['parent']) : 0;

        if (!$item_id) {
            continue;
        }

        // Prepare update arguments
        $update_args = array(
            'ID' => $item_id,
            'menu_order' => $index,
            'post_parent' => $parent_id
        );

        // Update the menu item
        $result = wp_update_post($update_args, true);

        if (is_wp_error($result)) {
            $success = false;
            $errors[] = sprintf(
                __('Failed to update menu item %d: %s', 'nma'),
                $item_id,
                $result->get_error_message()
            );
        }
    }

    if (!$success) {
        wp_send_json_error(array(
            'message' => __('Some menu items failed to update.', 'nma'),
            'errors' => $errors
        ), 500);
    }

    wp_send_json_success(array(
        'message' => __('Menu order saved successfully.', 'nma')
    ));
}
add_action('wp_ajax_nma_save_menu_order', 'nma_save_menu_order');
