<?php

/**
 * Form for adding/editing a program feature
 */

$is_edit = $feature !== null;
$page_title = $is_edit ? 'Edit Program Feature' : 'Add New Program Feature';

// Default values
$feature_data = array(
    'id' => $is_edit ? $feature->id : 0,
    'name' => $is_edit ? $feature->name : '',
    'description' => $is_edit ? $feature->description : '',
);
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html($page_title); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=facility-locator-program-features'); ?>" class="page-title-action">Back to Program Features</a>
    <hr class="wp-header-end">

    <form id="feature-form" method="post">
        <input type="hidden" name="id" value="<?php echo esc_attr($feature_data['id']); ?>">

        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <div class="postbox">
                        <h2 class="hndle">Program Feature Details</h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><label for="feature-name">Name</label></th>
                                    <td>
                                        <input type="text" id="feature-name" name="name" class="regular-text" value="<?php echo esc_attr($feature_data['name']); ?>" required>
                                        <p class="description">The name of the program feature (e.g., "Individual Therapy", "Group Sessions", "24/7 Support")</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="feature-description">Description</label></th>
                                    <td>
                                        <textarea id="feature-description" name="description" class="large-text" rows="4"><?php echo esc_textarea($feature_data['description']); ?></textarea>
                                        <p class="description">Optional description of this program feature</p>
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
                                $usage_count = $this->program_features->get_usage_count($feature->id);
                                ?>
                                <p><strong><?php echo $usage_count; ?></strong> facilities use this program feature.</p>
                                <?php if ($usage_count > 0) : ?>
                                    <p class="description">Deleting this program feature will remove it from all facilities that use it.</p>
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
        $('#feature-form').on('submit', function(e) {
            e.preventDefault();

            // Validate form
            const name = $('#feature-name').val().trim();
            if (!name) {
                alert('Please enter a name for the program feature.');
                return;
            }

            // Disable submit button and show spinner
            const $submitBtn = $('#publish');
            $submitBtn.prop('disabled', true);
            $submitBtn.prev('.spinner').addClass('is-active');

            // Collect form data
            const formData = new FormData(this);
            formData.append('action', 'save_program_feature');
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
                        alert('Program feature saved successfully!');

                        // Redirect to features list
                        window.location.href = 'admin.php?page=facility-locator-program-features&saved=true';
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
                    alert('Error saving program feature. Please try again.');

                    // Enable submit button and hide spinner
                    $submitBtn.prop('disabled', false);
                    $submitBtn.prev('.spinner').removeClass('is-active');
                }
            });
        });
    });
</script>