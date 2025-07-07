'use strict';

(function ($) {

    $(document).ready(function () {
        const collection = $('#o-resource_asset');
        const template = collection.find('[data-template]').data('template');

        /**
         * Append button Remove to each sub-collection fieldset.
         */
        function addRemoveButton(fieldset) {
            fieldset.append(`
                <div class="fieldset-buttons">
                    <button type="button" class="config-fieldset-action config-fieldset-minus fa fa-minus remove-value button" title="${Omeka.jsTranslate('Remove')}" aria-label="${Omeka.jsTranslate('Remove')}"></button>
                </div>
            `);
        }

        /**
         * Remove sub-collection fieldset on click.
         */
        collection.on('click', '.button.remove-value', function () {
            $(this).closest('fieldset').remove();
        });

        /**
         * Append "Remove" button to each fieldset on load.
         */
        collection.children('.form-collection-target').each(function () {
            addRemoveButton($(this));
        }); 

        /**
         * Prepare button Add.
         */
        const addButton = $(        `
            <div class="fieldset-buttons">
                <button type="button" class="config-fieldset-action config-fieldset-plus fa fa-plus add-value button" title="${Omeka.jsTranslate('Add')}" aria-label="${Omeka.jsTranslate('Add')}"></button>
            </div>
        `);

        /**
         * Add sub-collection fieldset.
         */
        addButton.on('click', function () {
            // Use a random key because the index may not be unique when a
            // fieldset is removed. Ideally, ensure that the index is unique.
            const randomKey = Math.random().toString(36).substr(2, 10);
            const newFieldset = $(template.replace(/__index__/g, randomKey));
            addRemoveButton(newFieldset);
            collection.append(newFieldset);
        });

        /**
         * Append button Add on load.
         */
        collection.after(addButton);

    });

})(jQuery);
