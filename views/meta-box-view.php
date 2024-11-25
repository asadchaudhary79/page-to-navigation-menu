<?php
// Check for direct access prevention
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!current_user_can('edit_theme_options')) {
    echo '<p>' . esc_html__('You do not have sufficient permissions to access this page.', 'nma') . '</p>';
    return;
}

$menus = wp_get_nav_menus();
if (empty($menus)) {
    echo '<p>' . esc_html__('No menus found. Please create a menu first.', 'nma') . '</p>';
    return;
}

?>
<div class="nma-container">
    <label for="nma_menu_select"><?php esc_html_e('Select Menu:', 'nma'); ?></label>
    <select id="nma_menu_select" name="nma_menu_select" class="widefat">
        <?php foreach ($menus as $menu) : ?>
            <option value="<?php echo esc_attr($menu->term_id); ?>">
                <?php echo esc_html($menu->name); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="nma_parent_item_select"><?php esc_html_e('Select Parent Menu Item:', 'nma'); ?></label>
    <select id="nma_parent_item_select" name="nma_parent_item_select" class="widefat">
        <option value="0"><?php esc_html_e('No Parent', 'nma'); ?></option>
        <!-- JavaScript will populate this based on the selected menu -->
    </select>

    <button type="button" id="nma_add_to_menu" class="button button-primary">
        <?php esc_html_e('Add to Menu', 'nma'); ?>
    </button>

    <div id="nma_menu_structure" class="nma-menu-structure"></div>
    <input type="hidden" id="nma_post_id" value="<?php echo esc_attr($post->ID); ?>">
</div>