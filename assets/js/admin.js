/**
 * Admin JavaScript för Circular Year Planner
 */

jQuery(document).ready(function($) {
    'use strict';
    
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

