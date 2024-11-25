jQuery(document).ready(function ($) {
    // Fetch initial menu structure
    var initial_menu_id = $('#nma_menu_select').val();
    fetchMenuStructure(initial_menu_id);

    // Fetch menu structure on menu selection change
    $('#nma_menu_select').on('change', function () {
        var menu_id = $(this).val();
        fetchMenuStructure(menu_id);
    });

    $('#nma_add_to_menu').on('click', function () {
        var page_id = $('#nma_post_id').val();
        var menu_id = $('#nma_menu_select').val();
        var parent_item_id = $('#nma_parent_item_select').val();
        var nonce = nma_ajax.nonce;

        $.post(nma_ajax.ajax_url, {
            action: 'nma_add_page_to_menu',
            page_id: page_id,
            menu_id: menu_id,
            parent_item_id: parent_item_id,
            nonce: nonce
        }, function (response) {
            if (response.success) {
                alert('Page added to menu.');
                // Fetch and display updated menu structure
                fetchMenuStructure(menu_id);
            } else {
                alert('Error: ' + response.data);
            }
        });
    });

    $(document).on('click', '.nma-delete-menu-item', function () {
        var menu_item_id = $(this).data('id');
        var nonce = nma_ajax.nonce;

        $.post(nma_ajax.ajax_url, {
            action: 'nma_delete_menu_item',
            menu_item_id: menu_item_id,
            nonce: nonce
        }, function (response) {
            if (response.success) {
                alert('Menu item deleted.');
                // Fetch and display updated menu structure
                var menu_id = $('#nma_menu_select').val();
                fetchMenuStructure(menu_id);
            } else {
                alert('Error: ' + response.data);
            }
        });
    });

    function fetchMenuStructure(menu_id) {
        $.post(nma_ajax.ajax_url, {
            action: 'nma_fetch_menu_structure',
            menu_id: menu_id,
            nonce: nma_ajax.nonce
        }, function (response) {
            if (response.success) {
                $('#nma_menu_structure').html(response.data.menu_structure);
                $('#nma_parent_item_select').html(response.data.menu_items_options);
                initSortable();
            } else {
                $('#nma_menu_structure').html('Error loading menu structure.');
            }
        });
    }

    function saveMenuOrder() {
        var order = [];
        $('#nma_menu_structure ul li').each(function (index, element) {
            var item_id = $(this).data('id');
            var parent_id = $(this).parent().closest('li').data('id') || 0;
            order.push({ id: item_id, parent: parent_id });
        });

        $.post(nma_ajax.ajax_url, {
            action: 'nma_save_menu_order',
            order: order,
            nonce: nma_ajax.nonce
        }, function (response) {
            if (!response.success) {
                alert('Error saving menu order: ' + response.data);
            }
        });
    }

    function initSortable() {
        $('#nma_menu_structure ul').sortable({
            connectWith: '#nma_menu_structure ul',
            update: function (event, ui) {
                saveMenuOrder();
            }
        }).disableSelection();
    }
});