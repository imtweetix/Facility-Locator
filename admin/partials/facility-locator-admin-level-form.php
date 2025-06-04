<?php

/**
 * Form for adding/editing a level of care
 */

$is_edit = $level !== null;
$page_title = $is_edit ? 'Edit Level of Care' : 'Add New Level of Care';

// Default values
$level_data = array(
    'id' => $is_edit ? $level->id : 0,
    'name' => $is_edit ? $level->name : '',
    'description' => $is_edit ? $level->description : '',
);
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html($page_title); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=facility-locator-levels-of-care'); ?>" class="page-title-action">Back to Levels of Care</a>
    <hr class="wp-header-end">

    <form id="level-form" method="post">
        <input type="hidden" name="id" value="<?php echo esc_attr($level_data['id']); ?>">

        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <div class="postbox">
                        <h2 class="hndle">Level of Care Details</h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><label for="level-name">Name</label></th>
                                    <td>
                                        <input type="text" id="level-name" name="name" class="regular-text" value="<?php echo esc_attr($level_data['name']); ?>" required>
                                        <p class="description">The name of the level of care (e.g., "Residential Treatment", "Outpatient Care")</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="level-description">Description</label></th>
                                    <td>
                                        <textarea id="level-description" name="description" class="large-text" rows="4"><?php echo esc_textarea($level_data['description']); ?></textarea>
                                        <p class="description">Optional description of this level of care</p>
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
                                $usage_count = $this->levels_of_care->get_usage_count($level->id);
                                ?>
                                <p><strong><?php echo $usage_count; ?></strong> facilities use this level of care.</p>
                                <?php if ($usage_count > 0) : ?>
                                    <p class="description">Deleting this level of care will remove it from all facilities that use it.</p>
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
        $('#level-form').on('submit', function(e) {
            e.preventDefault();

            // Validate form
            const name = $('#level-name').val().trim();
            if (!name) {
                alert('Please enter a name for the level of care.');
                return;
            }

            // Disable submit button and show spinner
            const $submitBtn = $('#publish');
            $submitBtn.prop('disabled', true);
            $submitBtn.prev('.spinner').addClass('is-active');

            // Collect form data
            const formData = new FormData(this);
            formData.append('action', 'save_level_of_care');
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
                        alert('Level of care saved successfully!');

                        // Redirect to levels list
                        window.location.href = 'admin.php?page=facility-locator-levels-of-care&saved=true';
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
                    alert('Error saving level of care. Please try again.');

                    // Enable submit button and hide spinner
                    $submitBtn.prop('disabled', false);
                    $submitBtn.prev('.spinner').removeClass('is-active');
                }
            });
        });
    });
</script>