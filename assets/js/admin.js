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
    $('#cypl-add-event-type').on('click', function() {
        var index = $('#cypl-event-types .cypl-event-type-row').length;
        
        // Get translations from localized data
        var i18n = (typeof cyplAdmin !== 'undefined' && cyplAdmin.i18n) ? cyplAdmin.i18n : {
            eventTypeName: 'Event type name',
            background: 'Background',
            text: 'Text',
            auto: 'Auto',
            remove: 'Remove'
        };
        
        var html = '<div class="cypl-event-type-row">' +
            '<input type="text" name="cypl_event_types[' + index + '][name]" placeholder="' + i18n.eventTypeName + '" class="regular-text">' +
            '<div class="cypl-color-picker-group">' +
            '<label class="cypl-color-label">' + i18n.background + '</label>' +
            '<input type="text" name="cypl_event_types[' + index + '][color]" value="#4A90E2" class="cypl-color-picker">' +
            '</div>' +
            '<div class="cypl-color-picker-group">' +
            '<label class="cypl-color-label">' + i18n.text + '</label>' +
            '<input type="text" name="cypl_event_types[' + index + '][text_color]" value="" placeholder="' + i18n.auto + '" class="cypl-color-picker cypl-text-color-picker">' +
            '</div>' +
            '<button type="button" class="button cypl-remove-type">' + i18n.remove + '</button>' +
            '</div>';
        $('#cypl-event-types').append(html);
        
        // Initiera color picker för nya fält
        $('.cypl-color-picker').wpColorPicker();
    });
    
    // Ta bort händelsetyp
    $(document).on('click', '.cypl-remove-type', function() {
        $(this).closest('.cypl-event-type-row').remove();
    });
    
    // Initiera color pickers
    $('.cypl-color-picker').wpColorPicker();
});

