/**
 * Admin JavaScript för Circular Year Planner
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Settings page functionality
    // Fältnamnen måste vara unika även efter att rader tagits bort
    var nextIndex = $('#cyp-event-types .cyp-event-type-row').length;
    
    // Typerna numreras om i ordning när inställningarna sparas
    function renumberEventTypes() {
        $('#cyp-event-types .cyp-type-index-value').each(function(position) {
            $(this).text(position);
        });
    }
    
    // Lägg till händelsetyp
    $('#cyp-add-event-type').on('click', function() {
        var index = nextIndex++;
        
        // Get translations from localized data
        var i18n = (typeof cyplAdmin !== 'undefined' && cyplAdmin.i18n) ? cyplAdmin.i18n : {
            index: 'Index',
            name: 'Name',
            eventTypeName: 'Event type name',
            background: 'Background',
            text: 'Text',
            auto: 'Auto',
            remove: 'Remove'
        };
        
        var html = '<div class="cyp-event-type-row">' +
            '<div class="cyp-type-index">' +
            '<span class="cyp-color-label">' + i18n.index + '</span>' +
            '<span class="cyp-type-index-value"></span>' +
            '</div>' +
            '<div class="regular-text-container">' +
            '<label class="cyp-color-label">' + i18n.name + '</label>' +
            '<input type="text" name="cypl_event_types[' + index + '][name]" placeholder="' + i18n.eventTypeName + '" class="regular-text">' +
            '</div>' +
            '<div class="cyp-color-picker-group">' +
            '<label class="cyp-color-label">' + i18n.background + '</label>' +
            '<input type="text" name="cypl_event_types[' + index + '][color]" value="#4A90E2" class="cyp-color-picker">' +
            '</div>' +
            '<div class="cyp-color-picker-group">' +
            '<label class="cyp-color-label">' + i18n.text + '</label>' +
            '<input type="text" name="cypl_event_types[' + index + '][text_color]" value="" placeholder="' + i18n.auto + '" class="cyp-color-picker cyp-text-color-picker">' +
            '</div>' +
            '<button type="button" class="button cyp-remove-type">' + i18n.remove + '</button>' +
            '</div>';
        $('#cyp-event-types').append(html);
        renumberEventTypes();
        
        // Initiera color picker för nya fält
        $('.cyp-color-picker').wpColorPicker();
    });
    
    // Ta bort händelsetyp
    $(document).on('click', '.cyp-remove-type', function() {
        $(this).closest('.cyp-event-type-row').remove();
        renumberEventTypes();
    });
    
    // Initiera color pickers
    $('.cyp-color-picker').wpColorPicker();
});

