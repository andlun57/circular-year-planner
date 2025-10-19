/**
 * Admin JavaScript för Circular Year Planner
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Auto-fyll slutdatum när startdatum ändras
    $('#cyp_start_date').on('change', function() {
        var startDate = $(this).val();
        var endDateField = $('#cyp_end_date');
        var currentEndDate = endDateField.val();
        
        // Om slutdatum är tomt eller samma som startdatum, sätt det till startdatum
        if (!currentEndDate || currentEndDate === startDate) {
            endDateField.val(startDate);
        }
    });
    
    // Auto-fyll slutdatum när sidan laddas om slutdatum är tomt
    $(document).ready(function() {
        var startDate = $('#cyp_start_date').val();
        var endDateField = $('#cyp_end_date');
        var currentEndDate = endDateField.val();
        
        // Om startdatum finns men slutdatum är tomt, sätt slutdatum till startdatum
        if (startDate && !currentEndDate) {
            endDateField.val(startDate);
        }
    });
    
    // Validering av datum
    $('#post').on('submit', function(e) {
        var postType = $('#post_type').val();
        
        if (postType === 'cyp_event') {
            var startDate = $('#cyp_start_date').val();
            var endDate = $('#cyp_end_date').val();
            
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
});

