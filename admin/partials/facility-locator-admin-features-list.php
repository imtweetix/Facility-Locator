<?php
/**
 * Admin view for displaying all program features
 */
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Program Features</h1>
    <a href="<?php echo admin_url('admin.php?page=facility-locator-program-features&action=add'); ?>" class="page-title-action">Add New</a>
    <hr class="wp-header-end">

    <div class="tablenav top">
        <div class="alignleft actions">
            <input type="text" id="feature-search" class="regular-text" placeholder="Search program features...">
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
            <?php if (empty($features)) : ?>
                <tr>
                    <td colspan="4">No program features found. <a href="<?php echo admin_url('admin.php?page=facility-locator-program-features&action=add'); ?>">Add your first program feature</a>.</td>
                </tr>
            <?php else : ?>
                <?php foreach ($features as $feature) : ?>
                    <tr data-id="<?php echo esc_attr($feature->id); ?>">
                        <td>
                            <strong>
                                <a href="<?php echo admin_url('admin.php?page=facility-locator-program-features&action=edit&id=' . $feature->id); ?>">
                                    <?php echo esc_html($feature->name); ?>
                                </a>
                            </strong>
                            <?php if (!empty($feature->description)) : ?>
                                <br><span class="description"><?php echo esc_html($feature->description); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><code><?php echo esc_html($feature->slug); ?></code></td>
                        <td>
                            <?php 
                            $usage_count = $this->program_features->get_usage_count($feature->id);
                            echo $usage_count;
                            ?>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=facility-locator-program-features&action=edit&id=' . $feature->id); ?>" class="button button-small">Edit</a>
                            <button type="button" class="button button-small delete-feature" data-id="<?php echo esc_attr($feature->id); ?>" data-name="<?php echo esc_attr($feature->name); ?>">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="delete-feature-modal" class="facility-locator-modal" style="display:none;">
    <div class="facility-locator-modal-content">
        <h3>Delete Program Feature</h3>
        <p>Are you sure you want to delete "<span id="delete-feature-name"></span>"? This action cannot be undone.</p>
        <div class="facility-locator-modal-footer">
            <button type="button" class="button" id="cancel-delete-feature">Cancel</button>
            <button type="button" class="button button-primary" id="confirm-delete-feature">Delete</button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Search functionality
    $('#feature-search').on('keyup', function() {
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
    
    // Delete feature
    $('.delete-feature').on('click', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const $modal = $('#delete-feature-modal');
        
        // Store feature ID and name in the modal
        $modal.data('id', id);
        $('#delete-feature-name').text(name);
        
        // Show modal
        $modal.show();
    });
    
    // Cancel delete
    $('#cancel-delete-feature').on('click', function() {
        $('#delete-feature-modal').hide();
    });
    
    // Confirm delete
    $('#confirm-delete-feature').on('click', function() {
        const $modal = $('#delete-feature-modal');
        const id = $modal.data('id');
        
        // Disable button and show loading state
        const $confirmBtn = $(this);
        $confirmBtn.prop('disabled', true).text('Deleting...');
        
        // Send AJAX request
        $.ajax({
            url: facilityLocator.ajaxUrl,
            type: 'POST',
            data: {
                action: 'delete_program_feature',
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
                            $('.wp-list-table tbody').html('<tr><td colspan="4">No program features found. <a href="admin.php?page=facility-locator-program-features&action=add">Add your first program feature</a>.</td></tr>');
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
        const $modal = $('#delete-feature-modal');
        
        if ($(e.target).is($modal)) {
            $modal.hide();
        }
    });
});
</script>
