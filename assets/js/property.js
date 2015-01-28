/**
 * Enquiry bool - stops concurrent requests
 */
var enquiring = false;

/**
* Standard getPrice callback function
*
* @param startDay Starting date of the holiday period
* @param endDay   Finish date of the holiday period
*
* @return void
*/
var getPrice = function(startDay, endDay) {
    if (!enquiring) {
        
        _getEnquiryResponse().removeClass('alert-danger').removeClass('alert-success').html('Please wait...');
        
        var startStr = startDay.toString();
        var endStr = endDay.toString();
        $form = _getEnquiryForm();
        _getEnquiryFormFrom().val(startStr);
        _getEnquiryFormTo().val(endStr);
        enquiring = true;

        jQuery.getJSON($form.attr('action'), $form.serialize(), function(json) {
            if (json.status === 'ok') {
                msg = '';
                jQuery.each(json.enquiry, function(i, v) {
                    msg += i + ': £' + v + "<br>";
                });
                _getEnquiryResponse().addClass('alert-success').html(
                    msg
                );
            } else {
                _getEnquiryResponse().addClass('alert-danger').html(
                    json.message
                );
            }
            enquiring = false;
        });
    }
    
    return false;
};

function _getEnquiryForm() {
    return jQuery('#enquiryform');
}

function _getEnquiryFormFrom() {
    return jQuery("input[name=from]", _getEnquiryForm());
}

function _getEnquiryFormTo() {
    return jQuery("input[name=to]", _getEnquiryForm());
}

function _getEnquiryResponse() {
    return jQuery(".enquiry-response");
}

jQuery(document).ready(function() {
    
    // Create availability calendar functions
    var cal = jQuery('.availability_calendars').availabilityCalendar({
        clickCallBack: getPrice,
        nightsDropDownId: '#nights'
    });

    // Attach highlight function to change event
    jQuery('select', _getEnquiryForm()).change(function() {
        cal.data('plugin_availabilityCalendar')._highlightCalendar(
            jQuery('#' + _getEnquiryFormFrom().val())
        );
    });
});