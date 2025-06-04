<?php
/**
 * Admin main view for displaying all facilities
 */
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Facilities</h1>
    <a href="<?php echo admin_url('admin.php?page=facility-locator-add-new'); ?>" class="page-title-action">Add New</a>
    <hr class="wp-header-end">
    
    <div class="notice notice-info">
        <p>Use the shortcode <code>[facility_locator]</code> on any page or post to display the facility locator.</p>
    </div>

    <div class="tablenav top">
        <div class="alignleft actions">
            <input type="text" id="facility-search" class="regular-text" placeholder="Search facilities...">
        </div>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>Address</th>
                <th>Categories</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($facilities)) : ?>
                <tr>
                    <td colspan="4">No facilities found. <a href="<?php echo admin_url('admin.php?page=facility-locator-add-new'); ?>">Add your first facility</a>.</td>
                </tr>
            <?php else : ?>
                <?php foreach ($facilities as $facility) : ?>
                    <tr data-id="<?php echo esc_attr($facility->id); ?>">
                        <td>
                            <strong><?php echo esc_html($facility->name); ?></strong>
                        </td>
                        <td><?php echo esc_html($facility->address); ?></td>
                        <td>
                            <?php 
                            if (!empty($facility->categories)) {
                                $categories = array_map('esc_html', $facility->categories);
                                echo implode(', ', $categories);
                            } else {
                                echo '—';
                            }
                            ?>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=facility-locator-add-new&id=' . $facility->id); ?>" class="button button-small">Edit</a>
                            <button type="button" class="button button-small delete-facility" data-id="<?php echo esc_attr($facility->id); ?>">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="delete-facility-modal" class="facility-locator-modal" style="display:none;">
    <div class="facility-locator-modal-content">
        <h3>Delete Facility</h3>
        <p>Are you sure you want to delete this facility? This action cannot be undone.</p>
        <div class="facility-locator-modal-footer">
            <button type="button" class="button" id="cancel-delete">Cancel</button>
            <button type="button" class="button button-primary" id="confirm-delete">Delete</button>
        </div>
    </div>
</div>
