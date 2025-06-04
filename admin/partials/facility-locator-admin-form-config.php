<?php

/**
 * Admin form configuration page
 */

$form_steps = json_decode(get_option('facility_locator_form_steps', '[]'), true);
?>

<div class="wrap">
    <h1>Form Configuration</h1>
    <p>Configure the multi-step form that users will see before the facility locator map.</p>

    <form method="post" action="options.php">
        <?php settings_fields('facility_locator_form_config'); ?>

        <div class="metabox-holder">
            <div class="postbox">
                <h2 class="hndle">Form Steps</h2>
                <div class="inside">
                    <div id="form-steps-container">
                        <?php if (empty($form_steps)) : ?>
                            <p style="padding: 20px; background: #f9f9f9; border: 1px dashed #ccc; text-align: center; color: #666;">
                                <strong>No form steps configured yet.</strong><br>
                                Click "Add Step" below to create your first form step.
                            </p>
                        <?php else : ?>
                            <?php foreach ($form_steps as $step_index => $step) : ?>
                                <div class="form-step" data-step="<?php echo esc_attr($step_index); ?>">
                                    <h3>Step <?php echo esc_html($step_index + 1); ?>: <?php echo esc_html($step['title']); ?></h3>

                                    <table class="form-table">
                                        <tr>
                                            <th scope="row"><label for="step-<?php echo esc_attr($step_index); ?>-title">Step Title</label></th>
                                            <td>
                                                <input type="text" id="step-<?php echo esc_attr($step_index); ?>-title" value="<?php echo esc_attr($step['title']); ?>" class="step-title regular-text">
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label for="step-<?php echo esc_attr($step_index); ?>-type">Step Type</label></th>
                                            <td>
                                                <select id="step-<?php echo esc_attr($step_index); ?>-type" class="step-type">
                                                    <option value="radio_columns" <?php selected($step['type'], 'radio_columns'); ?>>Radio Button Columns</option>
                                                    <option value="checkbox_columns" <?php selected($step['type'], 'checkbox_columns'); ?>>Checkbox Columns</option>
                                                    <option value="dropdown" <?php selected($step['type'], 'dropdown'); ?>>Insurance Dropdown</option>
                                                </select>
                                            </td>
                                        </tr>
                                    </table>

                                    <?php if ($step['type'] === 'radio_columns' || $step['type'] === 'checkbox_columns') : ?>
                                        <div class="step-columns">
                                            <h4>Columns</h4>

                                            <?php if (isset($step['columns']) && is_array($step['columns'])) : ?>
                                                <?php foreach ($step['columns'] as $column_index => $column) : ?>
                                                    <div class="step-column" data-column="<?php echo esc_attr($column_index); ?>">
                                                        <h5>Column <?php echo esc_html($column_index + 1); ?></h5>

                                                        <p>
                                                            <label>Column Header:</label>
                                                            <input type="text" value="<?php echo esc_attr($column['header']); ?>" class="column-header regular-text">
                                                        </p>

                                                        <div class="column-options">
                                                            <p><strong>Options:</strong></p>
                                                            <?php if (isset($column['options']) && is_array($column['options'])) : ?>
                                                                <?php foreach ($column['options'] as $option_value => $option_label) : ?>
                                                                    <div class="column-option">
                                                                        <input type="text" value="<?php echo esc_attr($option_value); ?>" class="option-value small-text" placeholder="Value">
                                                                        <input type="text" value="<?php echo esc_attr($option_label); ?>" class="option-label regular-text" placeholder="Label">
                                                                        <button type="button" class="button remove-option">Remove</button>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                            <button type="button" class="button add-option">Add Option</button>
                                                        </div>

                                                        <p>
                                                            <button type="button" class="button button-small remove-column">Remove Column</button>
                                                        </p>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>

                                            <button type="button" class="button add-column">Add Column</button>
                                        </div>
                                    <?php elseif ($step['type'] === 'dropdown') : ?>
                                        <div class="step-dropdown-notice">
                                            <p><em>This step will use a dropdown list that you can configure in the step options below.</em></p>
                                        </div>
                                    <?php endif; ?>

                                    <p>
                                        <button type="button" class="button button-small remove-step">Remove Step</button>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <p>
                        <button type="button" id="add-step" class="button">Add Step</button>
                    </p>

                    <textarea id="facility_locator_form_steps" name="facility_locator_form_steps" style="display:none;"></textarea>
                </div>
            </div>
        </div>

        <?php submit_button(); ?>
    </form>
</div>

<script>
    jQuery(document).ready(function($) {
        // Update JSON when form changes
        function updateFormStepsJson() {
            var steps = [];

            $('.form-step').each(function(stepIndex) {
                var step = {
                    title: $(this).find('.step-title').val(),
                    type: $(this).find('.step-type').val()
                };

                if (step.type === 'radio_columns' || step.type === 'checkbox_columns') {
                    step.columns = [];

                    $(this).find('.step-column').each(function(columnIndex) {
                        var column = {
                            header: $(this).find('.column-header').val(),
                            options: {}
                        };

                        $(this).find('.column-option').each(function() {
                            var optionValue = $(this).find('.option-value').val();
                            var optionLabel = $(this).find('.option-label').val();

                            if (optionValue && optionLabel) {
                                column.options[optionValue] = optionLabel;
                            }
                        });

                        step.columns.push(column);
                    });
                } else if (step.type === 'dropdown') {
                    step.options = [];
                }

                steps.push(step);
            });

            $('#facility_locator_form_steps').val(JSON.stringify(steps));
        }

        // Initial update
        updateFormStepsJson();

        // Add step
        $('#add-step').on('click', function() {
            var stepIndex = $('.form-step').length;

            var stepHtml = `
            <div class="form-step" data-step="${stepIndex}">
                <h3>Step ${stepIndex + 1}: New Step</h3>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="step-${stepIndex}-title">Step Title</label></th>
                        <td>
                            <input type="text" id="step-${stepIndex}-title" value="New Step" class="step-title regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="step-${stepIndex}-type">Step Type</label></th>
                        <td>
                            <select id="step-${stepIndex}-type" class="step-type">
                                <option value="radio_columns">Radio Button Columns</option>
                                <option value="checkbox_columns">Checkbox Columns</option>
                                <option value="dropdown">Insurance Dropdown</option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <div class="step-columns">
                    <h4>Columns</h4>
                    <button type="button" class="button add-column">Add Column</button>
                </div>
                
                <p>
                    <button type="button" class="button button-small remove-step">Remove Step</button>
                </p>
            </div>
        `;

            $('#form-steps-container').append(stepHtml);
            updateFormStepsJson();
        });

        // Remove step
        $(document).on('click', '.remove-step', function() {
            if (confirm('Are you sure you want to remove this step?')) {
                $(this).closest('.form-step').remove();
                updateFormStepsJson();
            }
        });

        // Add column
        $(document).on('click', '.add-column', function() {
            var $step = $(this).closest('.form-step');
            var columnIndex = $step.find('.step-column').length;

            var columnHtml = `
            <div class="step-column" data-column="${columnIndex}">
                <h5>Column ${columnIndex + 1}</h5>
                
                <p>
                    <label>Column Header:</label>
                    <input type="text" value="New Column" class="column-header regular-text">
                </p>
                
                <div class="column-options">
                    <p><strong>Options:</strong></p>
                    <div class="column-option">
                        <input type="text" value="" class="option-value small-text" placeholder="Value">
                        <input type="text" value="" class="option-label regular-text" placeholder="Label">
                        <button type="button" class="button remove-option">Remove</button>
                    </div>
                    <button type="button" class="button add-option">Add Option</button>
                </div>
                
                <p>
                    <button type="button" class="button button-small remove-column">Remove Column</button>
                </p>
            </div>
        `;

            $(this).before(columnHtml);
            updateFormStepsJson();
        });

        // Remove column
        $(document).on('click', '.remove-column', function() {
            $(this).closest('.step-column').remove();
            updateFormStepsJson();
        });

        // Add option
        $(document).on('click', '.add-option', function() {
            var optionHtml = `
            <div class="column-option">
                <input type="text" value="" class="option-value small-text" placeholder="Value">
                <input type="text" value="" class="option-label regular-text" placeholder="Label">
                <button type="button" class="button remove-option">Remove</button>
            </div>
        `;

            $(this).before(optionHtml);
            updateFormStepsJson();
        });

        // Remove option
        $(document).on('click', '.remove-option', function() {
            $(this).closest('.column-option').remove();
            updateFormStepsJson();
        });

        // Add insurance provider
        $('#add-provider').on('click', function() {
            var providerHtml = `
            <div class="insurance-provider">
                <input type="text" value="" class="provider-name regular-text" placeholder="Insurance Provider Name">
                <button type="button" class="button remove-provider">Remove</button>
            </div>
        `;

            $('#insurance-providers-container').append(providerHtml);
            updateInsuranceProvidersJson();
        });

        // Remove insurance provider
        $(document).on('click', '.remove-provider', function() {
            $(this).closest('.insurance-provider').remove();
            updateInsuranceProvidersJson();
        });

        // Update step title in heading
        $(document).on('input', '.step-title', function() {
            var $step = $(this).closest('.form-step');
            var stepIndex = $step.data('step');
            var title = $(this).val();

            $step.find('h3').text('Step ' + (stepIndex + 1) + ': ' + title);
            updateFormStepsJson();
        });

        // Change step type
        $(document).on('change', '.step-type', function() {
            var $step = $(this).closest('.form-step');
            var stepType = $(this).val();

            // Remove existing content
            $step.find('.step-columns, .step-dropdown-notice').remove();

            // Add appropriate content
            if (stepType === 'radio_columns' || stepType === 'checkbox_columns') {
                var columnsHtml = `
                <div class="step-columns">
                    <h4>Columns</h4>
                    <button type="button" class="button add-column">Add Column</button>
                </div>
            `;

                $(this).closest('table').after(columnsHtml);
            } else if (stepType === 'dropdown') {
                var dropdownHtml = `
                <div class="step-dropdown-notice">
                    <p><em>This step will use the insurance providers configured below.</em></p>
                </div>
            `;

                $(this).closest('table').after(dropdownHtml);
            }

            updateFormStepsJson();
        });

        // Update JSON on any input change
        $(document).on('change input', 'input, select', function() {
            updateFormStepsJson();
            updateInsuranceProvidersJson();
        });

        // Form submission
        $('form').on('submit', function() {
            updateFormStepsJson();
            updateInsuranceProvidersJson();
        });
    });
</script>