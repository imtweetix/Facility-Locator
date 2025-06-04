<?php
/**
 * Generic admin view for displaying taxonomy items
 * 
 * @var object $taxonomy The taxonomy object
 * @var array $items List of taxonomy items
 */
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html($taxonomy->get_display_name()); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=' . $_GET['page'] . '&action=add'); ?>" class="page-title-action">Add New</a>
    <hr class="wp-header-end">

    <div class="tablenav top">
        <div class="alignleft actions">
            <input type="text" id="taxonomy-search" class="regular-text" placeholder="Search <?php echo esc_attr(strtolower($taxonomy->get_display_name())); ?>...">
        </div>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 40%;">Name</th>
                <th style="width: 30%;">Slug</th>
                <th style="width: 15%;">Facilities</th>
                <th style="width: 15%;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)) : ?>
                <tr>
                    <td colspan="4">No <?php echo esc_html(strtolower($taxonomy->get_display_name())); ?> found. <a href="<?php echo admin_url('admin.php?page=' . $_GET['page'] . '&action=add'); ?>">Add your first <?php echo esc_html(strtolower($taxonomy->get_display_name())); ?></a>.</td>
                </tr>
            <?php else : ?>
                <?php foreach ($items as $item) : ?>
                    <tr data-id="<?php echo esc_attr($item->id); ?>">
                        <td>
                            <strong>
                                <a href="<?php echo admin_url('admin.php?page=' . $_GET['page'] . '&action=edit&id=' . $item->id); ?>">
                                    <?php echo esc_html($item->name); ?>
                                </a>
                            </strong>
                            <?php if (!empty($item->description)) : ?>
                                <br><span class="description"><?php echo esc_html($item->description); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><code><?php echo esc_html($item->slug); ?></code></td>
                        <td>
                            <?php 
                            $usage_count = $taxonomy->get_usage_count($item->id);
                            echo $usage_count;
                            ?>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=' . $_GET['page'] . '&action=edit&id=' . $item->id); ?>" class="button button-small">Edit</a>
                            <button type="button" class="button button-small delete-taxonomy-item" 
                                    data-id="<?php echo esc_attr($item->id); ?>" 
                                    data-name="<?php echo esc_attr($item->name); ?>"
                                    data-taxonomy-type="<?php echo esc_attr($taxonomy->get_taxonomy_type()); ?>">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="delete-taxonomy-modal" class="facility-locator-modal" style="display:none;">
    <div class="facility-locator-modal-content">
        <h3>Delete <?php echo esc_html($taxonomy->get_display_name()); ?></h3>
        <p>Are you sure you want to delete "<span id="delete-taxonomy-name"></span>"? This action cannot be undone.</p>
        <div class="facility-locator-modal-footer">
            <button type="button" class="button" id="cancel-delete-taxonomy">Cancel</button>
            <button type="button" class="button button-primary" id="confirm-delete-taxonomy">Delete</button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Search functionality
    $('#taxonomy-search').on('keyup', function() {
        const searchText = $(this).val().toLowerCase();
        
        $('.wp-list-table tbody tr').each(function() {
            const name = $(this).find('td:first').text().toLowerCase();
            const slug = $(this).find('td:nth-child(2)').text().toLowerCase();
            
            if (name.includes(searchText) || slug.includes(searchText)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Delete taxonomy item
    $('.delete-taxonomy-item').on('click', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const taxonomyType = $(this).data('taxonomy-type');
        const $modal = $('#delete-taxonomy-modal');
        
        // Store data in the modal
        $modal.data('id', id);
        $modal.data('taxonomy-type', taxonomyType);
        $('#delete-taxonomy-name').text(name);
        
        // Show modal
        $modal.show();
    });
    
    // Cancel delete
    $('#cancel-delete-taxonomy').on('click', function() {
        $('#delete-taxonomy-modal').hide();
    });
    
    // Confirm delete
    $('#confirm-delete-taxonomy').on('click', function() {
        const $modal = $('#delete-taxonomy-modal');
        const id = $modal.data('id');
        const taxonomyType = $modal.data('taxonomy-type');
        
        // Disable button and show loading state
        const $confirmBtn = $(this);
        $confirmBtn.prop('disabled', true).text('Deleting...');
        
        // Send AJAX request
        $.ajax({
            url: facilityLocator.ajaxUrl,
            type: 'POST',
            data: {
                action: 'delete_taxonomy_' + taxonomyType,
                nonce: facilityLocator.nonce,
                id: id,
                taxonomy_type: taxonomyType
            },
            success: function(response) {
                if (response.success) {
                    // Remove table row
                    $('tr[data-id="' + id + '"]').fadeOut(300, function() {
                        $(this).remove();
                        
                        // Check if table is empty
                        if ($('.wp-list-table tbody tr').length === 0) {
                            $('.wp-list-table tbody').html('<tr><td colspan="4">No <?php echo esc_js(strtolower($taxonomy->get_display_name())); ?> found. <a href="<?php echo admin_url('admin.php?page=' . $_GET['page'] . '&action=add'); ?>">Add your first <?php echo esc_js(strtolower($taxonomy->get_display_name())); ?></a>.</td></tr>');
                        }
                    });
                    
                    // Hide modal
                    $modal.hide();
                } else {
                    // Show error message
                    alert('Error: ' + response.data);
                    
                    // Reset button
                    $confirmBtn.prop('disabled', false).text('Delete');
                }
            },
            error: function(xhr, status, error) {
                // Show error message
                alert('AJAX error: ' + error);
                
                // Reset button
                $confirmBtn.prop('disabled', false).text('Delete');
            }
        });
    });
    
    // Close modal when clicking outside
    $(window).on('click', function(e) {
        const $modal = $('#delete-taxonomy-modal');
        
        if ($(e.target).is($modal)) {
            $modal.hide();
        }
    });
});
</script>