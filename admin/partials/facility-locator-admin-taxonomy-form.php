<?php

/**
 * Generic form for adding/editing taxonomy items
 * 
 * @var object $taxonomy The taxonomy object
 * @var object|null $item The item being edited (null for new items)
 */

$is_edit = $item !== null;
$page_title = $is_edit ? 'Edit ' . $taxonomy->get_display_name() : 'Add New ' . $taxonomy->get_display_name();
$taxonomy_type = $taxonomy->get_taxonomy_type();

// Default values
$item_data = array(
    'id' => $is_edit ? $item->id : 0,
    'name' => $is_edit ? $item->name : '',
    'description' => $is_edit ? $item->description : '',
);
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html($page_title); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=' . str_replace('&action=add', '', str_replace('&action=edit&id=' . $item_data['id'], '', $_SERVER['REQUEST_URI']))); ?>" class="page-title-action">Back to <?php echo esc_html($taxonomy->get_display_name()); ?></a>
    <hr class="wp-header-end">

    <form id="taxonomy-form" method="post">
        <input type="hidden" name="id" value="<?php echo esc_attr($item_data['id']); ?>">
        <input type="hidden" name="taxonomy_type" value="<?php echo esc_attr($taxonomy_type); ?>">

        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <div class="postbox">
                        <h2 class="hndle"><?php echo esc_html($taxonomy->get_display_name()); ?> Details</h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><label for="taxonomy-name">Name</label></th>
                                    <td>
                                        <input type="text" id="taxonomy-name" name="name" class="regular-text" value="<?php echo esc_attr($item_data['name']); ?>" required>
                                        <p class="description">The name of the <?php echo esc_html(strtolower($taxonomy->get_display_name())); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="taxonomy-description">Description</label></th>
                                    <td>
                                        <textarea id="taxonomy-description" name="description" class="large-text" rows="4"><?php echo esc_textarea($item_data['description']); ?></textarea>
                                        <p class="description">Optional description of this <?php echo esc_html(strtolower($taxonomy->get_display_name())); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div id="postbox-container-1" class="postbox-container">
                    <div class="postbox">
                        <h2 class="hndle">Save</h2>
                        <div class="inside">
                            <div id="major-publishing-actions">
                                <div id="publishing-action">
                                    <span class="spinner"></span>
                                    <input type="submit" id="publish" class="button button-primary button-large" value="<?php echo $is_edit ? 'Update' : 'Add'; ?>">
                                </div>
                                <div class="clear"></div>
                            </div>
                        </div>
                    </div>

                    <?php if ($is_edit) : ?>
                        <div class="postbox">
                            <h2 class="hndle">Usage</h2>
                            <div class="inside">
                                <?php
                                $usage_count = $taxonomy->get_usage_count($item->id);
                                ?>
                                <p><strong><?php echo $usage_count; ?></strong> facilities use this <?php echo esc_html(strtolower($taxonomy->get_display_name())); ?>.</p>
                                <?php if ($usage_count > 0) : ?>
                                    <p class="description">Deleting this <?php echo esc_html(strtolower($taxonomy->get_display_name())); ?> will remove it from all facilities that use it.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    jQuery(document).ready(function($) {
        $('#taxonomy-form').on('submit', function(e) {
            e.preventDefault();

            // Validate form
            const name = $('#taxonomy-name').val().trim();
            if (!name) {
                alert('Please enter a name for the <?php echo esc_js(strtolower($taxonomy->get_display_name())); ?>.');
                return;
            }

            // Disable submit button and show spinner
            const $submitBtn = $('#publish');
            $submitBtn.prop('disabled', true);
            $submitBtn.prev('.spinner').addClass('is-active');

            // Collect form data
            const formData = new FormData(this);
            formData.append('action', 'save_taxonomy_<?php echo esc_js($taxonomy_type); ?>');
            formData.append('nonce', facilityLocator.nonce);

            // Send AJAX request
            $.ajax({
                url: facilityLocator.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        alert('<?php echo esc_js($taxonomy->get_display_name()); ?> saved successfully!');

                        // Redirect to list
                        window.location.href = '<?php echo admin_url('admin.php?page=' . str_replace('&action=add', '', str_replace('&action=edit&id=' . $item_data['id'], '', $_SERVER['REQUEST_URI']))); ?>&saved=true';
                    } else {
                        // Show error message
                        alert('Error: ' + (response.data || 'Unknown error occurred'));

                        // Enable submit button and hide spinner
                        $submitBtn.prop('disabled', false);
                        $submitBtn.prev('.spinner').removeClass('is-active');
                    }
                },
                error: function(xhr, status, error) {
                    // Show error message
                    alert('Error saving <?php echo esc_js(strtolower($taxonomy->get_display_name())); ?>. Please try again.');

                    // Enable submit button and hide spinner
                    $submitBtn.prop('disabled', false);
                    $submitBtn.prev('.spinner').removeClass('is-active');
                }
            });
        });
    });
</script>