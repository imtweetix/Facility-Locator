<?php
/**
 * Admin view for displaying all levels of care
 */
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Levels of Care</h1>
    <a href="<?php echo admin_url('admin.php?page=facility-locator-levels-of-care&action=add'); ?>" class="page-title-action">Add New</a>
    <hr class="wp-header-end">

    <div class="tablenav top">
        <div class="alignleft actions">
            <input type="text" id="level-search" class="regular-text" placeholder="Search levels of care...">
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
            <?php if (empty($levels)) : ?>
                <tr>
                    <td colspan="4">No levels of care found. <a href="<?php echo admin_url('admin.php?page=facility-locator-levels-of-care&action=add'); ?>">Add your first level of care</a>.</td>
                </tr>
            <?php else : ?>
                <?php foreach ($levels as $level) : ?>
                    <tr data-id="<?php echo esc_attr($level->id); ?>">
                        <td>
                            <strong>
                                <a href="<?php echo admin_url('admin.php?page=facility-locator-levels-of-care&action=edit&id=' . $level->id); ?>">
                                    <?php echo esc_html($level->name); ?>
                                </a>
                            </strong>
                            <?php if (!empty($level->description)) : ?>
                                <br><span class="description"><?php echo esc_html($level->description); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><code><?php echo esc_html($level->slug); ?></code></td>
                        <td>
                            <?php 
                            $usage_count = $this->levels_of_care->get_usage_count($level->id);
                            echo $usage_count;
                            ?>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=facility-locator-levels-of-care&action=edit&id=' . $level->id); ?>" class="button button-small">Edit</a>
                            <button type="button" class="button button-small delete-level" data-id="<?php echo esc_attr($level->id); ?>" data-name="<?php echo esc_attr($level->name); ?>">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="delete-level-modal" class="facility-locator-modal" style="display:none;">
    <div class="facility-locator-modal-content">
        <h3>Delete Level of Care</h3>
        <p>Are you sure you want to delete "<span id="delete-level-name"></span>"? This action cannot be undone.</p>
        <div class="facility-locator-modal-footer">
            <button type="button" class="button" id="cancel-delete-level">Cancel</button>
            <button type="button" class="button button-primary" id="confirm-delete-level">Delete</button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Search functionality
    $('#level-search').on('keyup', function() {
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
    
    // Delete level
    $('.delete-level').on('click', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const $modal = $('#delete-level-modal');
        
        // Store level ID and name in the modal
        $modal.data('id', id);
        $('#delete-level-name').text(name);
        
        // Show modal
        $modal.show();
    });
    
    // Cancel delete
    $('#cancel-delete-level').on('click', function() {
        $('#delete-level-modal').hide();
    });
    
    // Confirm delete
    $('#confirm-delete-level').on('click', function() {
        const $modal = $('#delete-level-modal');
        const id = $modal.data('id');
        
        // Disable button and show loading state
        const $confirmBtn = $(this);
        $confirmBtn.prop('disabled', true).text('Deleting...');
        
        // Send AJAX request
        $.ajax({
            url: facilityLocator.ajaxUrl,
            type: 'POST',
            data: {
                action: 'delete_level_of_care',
                nonce: facilityLocator.nonce,
                id: id
            },
            success: function(response) {
                if (response.success) {
                    // Remove table row
                    $('tr[data-id="' + id + '"]').fadeOut(300, function() {
                        $(this).remove();
                        
                        // Check if table is empty
                        if ($('.wp-list-table tbody tr').length === 0) {
                            $('.wp-list-table tbody').html('<tr><td colspan="4">No levels of care found. <a href="admin.php?page=facility-locator-levels-of-care&action=add">Add your first level of care</a>.</td></tr>');
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
        const $modal = $('#delete-level-modal');
        
        if ($(e.target).is($modal)) {
            $modal.hide();
        }
    });
});
</script>
