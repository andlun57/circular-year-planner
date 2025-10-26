/**
 * Admin JavaScript för Circular Year Planner
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Auto-fyll slutdatum när startdatum ändras
    $('#cypl_start_date').on('change', function() {
        var startDate = $(this).val();
        var endDateField = $('#cypl_end_date');
        var currentEndDate = endDateField.val();
        
        // Om slutdatum är tomt eller samma som startdatum, sätt det till startdatum
        if (!currentEndDate || currentEndDate === startDate) {
            endDateField.val(startDate);
        }
    });
    
    // Auto-fyll slutdatum när sidan laddas om slutdatum är tomt
    $(document).ready(function() {
        var startDate = $('#cypl_start_date').val();
        var endDateField = $('#cypl_end_date');
        var currentEndDate = endDateField.val();
        
        // Om startdatum finns men slutdatum är tomt, sätt slutdatum till startdatum
        if (startDate && !currentEndDate) {
            endDateField.val(startDate);
        }
    });
    
    // Validering av datum
    $('#post').on('submit', function(e) {
        var postType = $('#post_type').val();
        
        if (postType === 'cypl_event') {
            var startDate = $('#cypl_start_date').val();
            var endDate = $('#cypl_end_date').val();
            
            if (!startDate || !endDate) {
                alert('Vänligen fyll i både startdatum och slutdatum.');
                e.preventDefault();
                return false;
            }
            
            if (new Date(startDate) > new Date(endDate)) {
                alert('Slutdatum kan inte vara före startdatum.');
                e.preventDefault();
                return false;
            }
        }
    });
    
    // Settings page functionality
    // Lägg till händelsetyp
    $('#cyp-add-event-type').on('click', function() {
        var index = $('#cyp-event-types .cyp-event-type-row').length;
        
        // Get translations from localized data
        var i18n = (typeof cyplAdmin !== 'undefined' && cyplAdmin.i18n) ? cyplAdmin.i18n : {
            eventTypeName: 'Event type name',
            background: 'Background',
            text: 'Text',
            auto: 'Auto',
            remove: 'Remove'
        };
        
        var html = '<div class="cyp-event-type-row">' +
            '<div class="regular-text-container">' +
            '<label class="cyp-color-label">' + 'Name' + '</label>' +
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
        
        // Initiera color picker för nya fält
        $('.cyp-color-picker').wpColorPicker();
    });
    
    // Ta bort händelsetyp
    $(document).on('click', '.cyp-remove-type', function() {
        $(this).closest('.cyp-event-type-row').remove();
    });
    
    // Initiera color pickers
    $('.cyp-color-picker').wpColorPicker();
});

