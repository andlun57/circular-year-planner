/**
 * Händelseformulär med upprepning för Planly
 */

jQuery(document).ready(function($) {
    'use strict';
    
    var form = $('#cypl-event-form');
    if (!form.length) {
        return;
    }
    
    var i18n = (typeof cyplEventForm !== 'undefined' && cyplEventForm.i18n) ? cyplEventForm.i18n : {};
    var startField = $('#cypl_start_date');
    var endField = $('#cypl_end_date');
    var freqField = $('#cypl_recurrence_freq');
    var previousStart = startField.val();
    
    // En befintlig regel ska inte skrivas över av startdatumet
    var followStartDate = !freqField.val();
    
    function parseDate(value) {
        var parts = (value || '').split('-');
        if (parts.length !== 3) {
            return null;
        }
        return new Date(Date.UTC(+parts[0], +parts[1] - 1, +parts[2]));
    }
    
    // ISO-veckodag, 1 = måndag … 7 = söndag
    function isoWeekday(date) {
        return date.getUTCDay() || 7;
    }
    
    function showPanel() {
        var freq = freqField.val();
        $('.cypl-recurrence-panel').each(function() {
            var active = $(this).data('freq') === freq;
            $(this).prop('hidden', !active).prop('disabled', !active);
        });
        updateModeFields();
    }
    
    function updateModeFields() {
        var monthly = $('.cypl-recurrence-panel[data-freq="monthly"]');
        var monthlyMode = monthly.find('input[name="cypl_recurrence[monthly][mode]"]:checked').val() || 'each';
        monthly.find('select[data-mode]').each(function() {
            $(this).prop('disabled', $(this).data('mode') !== monthlyMode);
        });
        
        var yearly = $('.cypl-recurrence-panel[data-freq="yearly"]');
        var yearlyOnThe = yearly.find('.cypl-yearly-onthe').is(':checked');
        yearly.find('select[data-mode]').prop('disabled', !yearlyOnThe);
    }
    
    function syncWithStartDate() {
        var start = parseDate(startField.val());
        if (!followStartDate || !start) {
            return;
        }
        
        var weekday = String(isoWeekday(start));
        $('input[name="cypl_recurrence[weekly][weekday]"][value="' + weekday + '"]').prop('checked', true);
        $('select[name="cypl_recurrence[monthly][monthday]"]').val(String(start.getUTCDate()));
        $('input[name="cypl_recurrence[yearly][month]"][value="' + (start.getUTCMonth() + 1) + '"]').prop('checked', true);
        $('select[name$="[day_kind]"]').val(weekday);
    }
    
    function validateDates() {
        var start = startField.val();
        var end = endField.val();
        var invalid = start && end && end < start;
        endField[0].setCustomValidity(invalid ? (i18n.endBeforeStart || 'The end date cannot be before the start date.') : '');
    }
    
    startField.on('change', function() {
        var start = startField.val();
        var end = endField.val();
        
        if (!end || end === previousStart || (start && end < start)) {
            endField.val(start);
        }
        
        previousStart = start;
        syncWithStartDate();
        validateDates();
    });
    
    endField.on('change', validateDates);
    freqField.on('change', showPanel);
    form.on('change', 'input[name="cypl_recurrence[monthly][mode]"], .cypl-yearly-onthe', updateModeFields);
    
    // Användaren har valt själv – sluta följa startdatumet
    form.on('change', '.cypl-recurrence-panel :input:not(.cypl-interval)', function() {
        followStartDate = false;
    });
    
    if (startField.val() && !endField.val()) {
        endField.val(startField.val());
    }
    
    syncWithStartDate();
    showPanel();
    validateDates();
});
