<?php

/**
 * Admin form configuration page with taxonomy integration
 */

$form_steps = json_decode(get_option('facility_locator_form_steps', '[]'), true);

// Get available taxonomies
$taxonomy_manager = new Facility_Locator_Taxonomy_Manager();
$available_taxonomies = $taxonomy_manager->get_all_taxonomies();
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
                                    </table>

                                    <div class="step-columns">
                                        <h4>Columns</h4>

                                        <div class="columns-container" data-sortable="columns">
                                            <?php if (isset($step['columns']) && is_array($step['columns'])) : ?>
                                                <?php foreach ($step['columns'] as $column_index => $column) : ?>
                                                    <div class="step-column" data-column="<?php echo esc_attr($column_index); ?>">
                                                        <div class="column-header-bar">
                                                            <span class="column-drag-handle">⋮⋮</span>
                                                            <h5>Column <?php echo esc_html($column_index + 1); ?></h5>
                                                        </div>

                                                        <table class="form-table">
                                                            <tr>
                                                                <th scope="row"><label>Column Header:</label></th>
                                                                <td>
                                                                    <input type="text" value="<?php echo esc_attr($column['header']); ?>" class="column-header regular-text">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row"><label>Input Type:</label></th>
                                                                <td>
                                                                    <select class="column-type">
                                                                        <option value="radio" <?php selected(isset($column['type']) ? $column['type'] : 'radio', 'radio'); ?>>Radio Button</option>
                                                                        <option value="checkbox" <?php selected(isset($column['type']) ? $column['type'] : 'radio', 'checkbox'); ?>>Checkbox</option>
                                                                        <option value="dropdown" <?php selected(isset($column['type']) ? $column['type'] : 'radio', 'dropdown'); ?>>Dropdown</option>
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row"><label>Populate from Taxonomy:</label></th>
                                                                <td>
                                                                    <select class="column-taxonomy">
                                                                        <option value="">Manual Options</option>
                                                                        <?php foreach ($available_taxonomies as $type => $taxonomy) : ?>
                                                                            <option value="<?php echo esc_attr($type); ?>"
                                                                                <?php selected(isset($column['taxonomy']) ? $column['taxonomy'] : '', $type); ?>>
                                                                                <?php echo esc_html($taxonomy->get_display_name()); ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                        </table>

                                                        <div class="column-options">
                                                            <p><strong>Options:</strong></p>
                                                            <div class="options-container" data-sortable="options">
                                                                <?php if (isset($column['options']) && is_array($column['options'])) : ?>
                                                                    <?php foreach ($column['options'] as $option) : ?>
                                                                        <?php if (is_array($option)) : ?>
                                                                            <div class="column-option">
                                                                                <span class="option-drag-handle">⋮</span>
                                                                                <input type="text" value="<?php echo esc_attr($option['value']); ?>" class="option-value small-text" placeholder="Value">
                                                                                <input type="text" value="<?php echo esc_attr($option['label']); ?>" class="option-label regular-text" placeholder="Label">
                                                                                <button type="button" class="button remove-option">Remove</button>
                                                                            </div>
                                                                        <?php else : ?>
                                                                            <!-- Backward compatibility for old object format -->
                                                                            <div class="column-option">
                                                                                <span class="option-drag-handle">⋮</span>
                                                                                <input type="text" value="<?php echo esc_attr($option); ?>" class="option-value small-text" placeholder="Value">
                                                                                <input type="text" value="<?php echo esc_attr($column['options'][$option]); ?>" class="option-label regular-text" placeholder="Label">
                                                                                <button type="button" class="button remove-option">Remove</button>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    <?php endforeach; ?>
                                                                <?php endif; ?>
                                                            </div>
                                                            <button type="button" class="button add-option">Add Option</button>
                                                        </div>

                                                        <p>
                                                            <button type="button" class="button button-small remove-column">Remove Column</button>
                                                        </p>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>

                                        <button type="button" class="button add-column">Add Column</button>
                                    </div>

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
        // Available taxonomy data
        const availableTaxonomies = <?php echo json_encode(array_map(function ($taxonomy) {
                                        return array(
                                            'name' => $taxonomy->get_display_name(),
                                            'items' => $taxonomy->get_all()
                                        );
                                    }, $available_taxonomies)); ?>;

        // Update JSON when form changes
        const updateFormStepsJson = () => {
            const steps = [];

            $('.form-step').each(function(stepIndex) {
                const step = {
                    title: $(this).find('.step-title').val(),
                    columns: []
                };

                $(this).find('.step-column').each(function(columnIndex) {
                    const column = {
                        header: $(this).find('.column-header').val(),
                        type: $(this).find('.column-type').val(),
                        taxonomy: $(this).find('.column-taxonomy').val() || null,
                        options: [] // Changed to array to preserve order
                    };

                    // Collect options in the order they appear in the DOM
                    $(this).find('.column-option').each(function() {
                        const optionValue = $(this).find('.option-value').val();
                        const optionLabel = $(this).find('.option-label').val();

                        if (optionValue && optionLabel) {
                            column.options.push({
                                value: optionValue,
                                label: optionLabel
                            });
                        }
                    });

                    step.columns.push(column);
                });

                steps.push(step);
            });

            $('#facility_locator_form_steps').val(JSON.stringify(steps));
        };

        // Populate options from taxonomy
        const populateFromTaxonomy = ($container, taxonomyType, isDropdown = false) => {
            if (!taxonomyType || !availableTaxonomies[taxonomyType]) {
                return;
            }

            const selector = isDropdown ? '.dropdown-options-container' : '.options-container';
            const $optionsContainer = $container.find(selector);

            // Clear existing options
            $optionsContainer.find(isDropdown ? '.dropdown-option' : '.column-option').remove();

            // Add taxonomy items as options IN THEIR ORIGINAL ORDER (no sorting)
            availableTaxonomies[taxonomyType].items.forEach(item => {
                const dragHandle = isDropdown ? 'option-drag-handle' : 'option-drag-handle';
                const optionClass = isDropdown ? 'dropdown-option' : 'column-option';
                const removeClass = isDropdown ? 'remove-dropdown-option' : 'remove-option';

                const optionHtml = `
                    <div class="${optionClass}">
                        <span class="${dragHandle}">⋮</span>
                        <input type="text" value="${item.id}" class="option-value small-text" placeholder="Value">
                        <input type="text" value="${item.name}" class="option-label regular-text" placeholder="Label">
                        <button type="button" class="button ${removeClass}">Remove</button>
                    </div>
                `;

                $optionsContainer.append(optionHtml);
            });

            // Reinitialize sortable for new items
            initializeSortable();
            updateFormStepsJson();
        };

        // Initialize sortable functionality
        const initializeSortable = () => {
            // Make columns sortable
            $('.columns-container').each(function() {
                if ($(this).hasClass('ui-sortable')) {
                    $(this).sortable('destroy');
                }

                $(this).sortable({
                    handle: '.column-drag-handle',
                    placeholder: 'sortable-placeholder-column',
                    update: function() {
                        updateColumnNumbers();
                        updateFormStepsJson();
                    }
                });
            });

            // Make options within columns sortable
            $('.options-container, .dropdown-options-container').each(function() {
                if ($(this).hasClass('ui-sortable')) {
                    $(this).sortable('destroy');
                }

                $(this).sortable({
                    handle: '.option-drag-handle',
                    placeholder: 'sortable-placeholder-option',
                    update: function() {
                        updateFormStepsJson();
                    }
                });
            });
        };

        // Update column numbers after reordering
        const updateColumnNumbers = () => {
            $('.step-column').each(function(index) {
                $(this).attr('data-column', index);
                $(this).find('h5').text(`Column ${index + 1}`);
            });
        };

        // Initialize sortable on page load
        initializeSortable();

        // Initial update
        updateFormStepsJson();

        // Add step
        $('#add-step').on('click', function() {
            const stepIndex = $('.form-step').length;

            const stepHtml = `
            <div class="form-step" data-step="${stepIndex}">
                <h3>Step ${stepIndex + 1}: New Step</h3>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="step-${stepIndex}-title">Step Title</label></th>
                        <td>
                            <input type="text" id="step-${stepIndex}-title" value="New Step" class="step-title regular-text">
                        </td>
                    </tr>
                </table>
                
                <div class="step-columns">
                    <h4>Columns</h4>
                    <div class="columns-container" data-sortable="columns">
                        <!-- Columns will be added here -->
                    </div>
                    <button type="button" class="button add-column">Add Column</button>
                </div>
                
                <p>
                    <button type="button" class="button button-small remove-step">Remove Step</button>
                </p>
            </div>
        `;

            $('#form-steps-container').append(stepHtml);
            initializeSortable();
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
        $(document).on('click', '.add-column', function(e) {
            e.preventDefault();
            console.log('Add column clicked'); // Debug log

            const $step = $(this).closest('.form-step');
            const $columnsContainer = $step.find('.columns-container');
            const columnIndex = $columnsContainer.find('.step-column').length;

            console.log('Step found:', $step.length); // Debug log
            console.log('Columns container found:', $columnsContainer.length); // Debug log
            console.log('Current column count:', columnIndex); // Debug log

            const taxonomyOptions = Object.keys(availableTaxonomies).map(type =>
                `<option value="${type}">${availableTaxonomies[type].name}</option>`
            ).join('');

            const columnHtml = `
            <div class="step-column" data-column="${columnIndex}">
                <div class="column-header-bar">
                    <span class="column-drag-handle">⋮⋮</span>
                    <h5>Column ${columnIndex + 1}</h5>
                </div>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label>Column Header:</label></th>
                        <td>
                            <input type="text" value="New Column" class="column-header regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label>Input Type:</label></th>
                        <td>
                            <select class="column-type">
                                <option value="radio">Radio Button</option>
                                <option value="checkbox">Checkbox</option>
                                <option value="dropdown">Dropdown</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label>Populate from Taxonomy:</label></th>
                        <td>
                            <select class="column-taxonomy">
                                <option value="">Manual Options</option>
                                ${taxonomyOptions}
                            </select>
                        </td>
                    </tr>
                </table>
                
                <div class="column-options">
                    <p><strong>Options:</strong></p>
                    <div class="options-container" data-sortable="options">
                        <div class="column-option">
                            <span class="option-drag-handle">⋮</span>
                            <input type="text" value="" class="option-value small-text" placeholder="Value">
                            <input type="text" value="" class="option-label regular-text" placeholder="Label">
                            <button type="button" class="button remove-option">Remove</button>
                        </div>
                    </div>
                    <button type="button" class="button add-option">Add Option</button>
                </div>
                
                <p>
                    <button type="button" class="button button-small remove-column">Remove Column</button>
                </p>
            </div>
        `;

            $columnsContainer.append(columnHtml);
            console.log('Column added'); // Debug log
            initializeSortable();
            updateFormStepsJson();
        });

        // Handle taxonomy selection change
        $(document).on('change', '.column-taxonomy', function() {
            const $column = $(this).closest('.step-column');
            const taxonomyType = $(this).val();

            if (taxonomyType) {
                populateFromTaxonomy($column, taxonomyType, false);
            }
        });

        // Handle dropdown taxonomy selection change
        $(document).on('change', '.dropdown-taxonomy', function() {
            const $step = $(this).closest('.form-step');
            const taxonomyType = $(this).val();

            if (taxonomyType) {
                populateFromTaxonomy($step, taxonomyType, true);
            }
        });

        // Remove column
        $(document).on('click', '.remove-column', function() {
            $(this).closest('.step-column').remove();
            updateColumnNumbers();
            updateFormStepsJson();
        });

        // Add option
        $(document).on('click', '.add-option', function() {
            const $optionsContainer = $(this).siblings('.options-container');

            const optionHtml = `
            <div class="column-option">
                <span class="option-drag-handle">⋮</span>
                <input type="text" value="" class="option-value small-text" placeholder="Value">
                <input type="text" value="" class="option-label regular-text" placeholder="Label">
                <button type="button" class="button remove-option">Remove</button>
            </div>
        `;

            $optionsContainer.append(optionHtml);
            initializeSortable();
            updateFormStepsJson();
        });

        // Add dropdown option
        $(document).on('click', '.add-dropdown-option', function() {
            const $optionsContainer = $(this).siblings('.dropdown-options-container');

            const optionHtml = `
            <div class="dropdown-option">
                <span class="option-drag-handle">⋮</span>
                <input type="text" value="" class="option-value small-text" placeholder="Value">
                <input type="text" value="" class="option-label regular-text" placeholder="Label">
                <button type="button" class="button remove-dropdown-option">Remove</button>
            </div>
        `;

            $optionsContainer.append(optionHtml);
            initializeSortable();
            updateFormStepsJson();
        });

        // Remove option
        $(document).on('click', '.remove-option', function() {
            $(this).closest('.column-option').remove();
            updateFormStepsJson();
        });

        // Remove dropdown option
        $(document).on('click', '.remove-dropdown-option', function() {
            $(this).closest('.dropdown-option').remove();
            updateFormStepsJson();
        });

        // Update step title in heading
        $(document).on('input', '.step-title', function() {
            const $step = $(this).closest('.form-step');
            const stepIndex = $step.data('step');
            const title = $(this).val();

            $step.find('h3').text(`Step ${stepIndex + 1}: ${title}`);
            updateFormStepsJson();
        });

        // Change step type
        $(document).on('change', '.step-type', function() {
            const $step = $(this).closest('.form-step');
            const stepType = $(this).val();

            // Remove existing content
            $step.find('.step-columns, .step-dropdown').remove();

            const taxonomyOptions = Object.keys(availableTaxonomies).map(type =>
                `<option value="${type}">${availableTaxonomies[type].name}</option>`
            ).join('');

            // Add appropriate content based on step type
            if (stepType === 'radio_columns' || stepType === 'checkbox_columns' || stepType === 'dropdown') {
                const columnsHtml = `
                    <div class="step-columns">
                        <h4>Columns</h4>
                        <div class="columns-container" data-sortable="columns">
                            <!-- Columns will be added here -->
                        </div>
                        <button type="button" class="button add-column">Add Column</button>
                    </div>
                `;

                $(this).closest('table').after(columnsHtml);
            }

            // Reinitialize sortable and update JSON
            initializeSortable();
            updateFormStepsJson();
        });

        // Update JSON on any input change
        $(document).on('change input', 'input, select', function() {
            updateFormStepsJson();
        });

        // Form submission
        $('form').on('submit', function() {
            updateFormStepsJson();
        });
    });
</script>