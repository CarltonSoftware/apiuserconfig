/**
 * Availability class for property calendars
 *
 * the semi-colon before function invocation is a safety net against concatenated
 * scripts and/or other plugins which may not be closed properly.
 */
;(function ( $, window, document, undefined ) {

    // undefined is used here as the undefined global variable in ECMAScript 3 is
    // mutable (ie. it can be changed by someone else). undefined isn't really being
    // passed in so we can ensure the value of it is truly undefined. In ES5, undefined
    // can no longer be modified.

    // window and document are passed through as local variable rather than global
    // as this (slightly) quickens the resolution process and can be more efficiently
    // minified (especially when both are regularly referenced in your plugin).
    
    // Create the defaults once
    var pluginName = "availabilityCalendar",
        defaults = {
            url: '',                      // Calendar url (this is to request 
                                          // the html tables
            dateParam: 'date',            // Query string parameter key name for
                                          // the date variable
            calendarCell: 'td',           // Type of cell object for the 
                                          // calendar object
            nightsDropDownId: '#nights',  // Id of the dropdown to determine the
                                          // holiday length
            availableClass: 'available',  // Class of available elements
            highlightClass: 'selected',   // Class to add when elements 
                                          // are selected
            clickCallBack: null           // Call back function for interpreting
                                          // the price
        };
        
        

    // The actual plugin constructor
    function Plugin( element, options ) {
        this.element = element;

        // jQuery has an extend method which merges the contents of two or
        // more objects, storing the result in the first object. The first object
        // is generally empty as we don't want to alter the default options for
        // future instances of the plugin
        this.options = $.extend( {}, defaults, options );
        this._defaults = defaults;
        this._name = pluginName;
        this._oneDay = 1000*60*60*24;
        this.init();
        return this;
    }
    
    /**
     * Add additional functionality
     */
    Plugin.prototype = {
        
        /**
         * Constructor
         */
        init: function() {
            // Place initialization logic here
            // You already have access to the DOM element and
            // the options via the instance, e.g. this.element
            // and this.options
            // you can add more functions like the one below and
            // call them like so:
            //  this.yourOtherFunction(this.element, this.options).
            var plugin = this;
            
            // Attach click handler
            this._attachClickHandler(plugin);
        },
        
        /**
         * Attach highlight function
         *
         * @param plugin (this) context
         *
         * @return void
         */
        _attachClickHandler: function(plugin) {
            // Attach click handler
            $(this.options.calendarCell + '.' + this.options.availableClass, this.element).each(function() {
                $(this).on(
                    'click', 
                    function() {
                        plugin._highlightCalendar($(this));
                    }
                );
            });
        },
        
        /**
         * Get the holiday length from the value stored in a page element
         *
         * @return integer
         */
        _getHolidayLength: function() {
            if ($(this.options.nightsDropDownId)) {
                try {
                    var nights = parseInt($(this.options.nightsDropDownId).val());
                    if (nights > 0) {
                        return nights;
                    }
                } catch(e) {}
            }
            return 7;
        },
        
        /**
         * Highlight the calendar by the amount of days
         *
         * @param ele Clicked Element
         *
         * @return void
         */
        _highlightCalendar: function(ele) {
            // Check element exists
            if (typeof ele == 'object' && ele.attr('id')) {
                // Click existing highlighted elements
                this._clearHighlights();

                // Get selected holiday length
                var length = this._getHolidayLength();
                
                // Create starting date
                var startDay = ele.attr('id').getDateFromIdString();

                // Create finish date
                var endDay = this._createNewDate(startDay, length);
                
                // Is finished bool, will, be set to true if calendar look hits
                // a break
                var isFinished = false;
                
                // Add Start highlight class
                jQuery('#' + startDay.getDateId()).addClass(
                    this.options.highlightClass + "datestart"
                );

                for (var i = 0; i < length; i++) {
                    var currentDay = this._createNewDate(startDay, i);
                    var id = currentDay.getDateId();

                    try {
                        if(this._highlightCalTd(jQuery('#' + id), i)) {
                            // Add finish highlight class
                            jQuery('#' + currentDay.getDateId()).addClass(
                                this.options.highlightClass + "datefinish"
                            );
                            isFinished = true;
                            endDay = currentDay;
                            break;
                        }
                    }
                    catch(err) {}
                }
                
                // Add finish highlight class if loop is whole
                if (!isFinished) {
                    jQuery('#' + endDay.getDateId()).addClass(
                        this.options.highlightClass + "datefinish"
                    );
                }

                // Do calendar click callback
                if (typeof this.options.clickCallBack == 'function') {
                    this.options.clickCallBack(startDay, endDay);
                }
            }
        },
        
        /**
         * Function used to create a new date object from an existing date given
         * a specified period length
         * 
         * @param date   Existing Date object
         * @param length Period length
         * 
         * @return Date
         */
        _createNewDate: function(date, length) {
            // http://stackoverflow.com/questions/3674539/javascript-date-increment-question
            var tzOff = date.getTimezoneOffset() * 60 * 1000;
            var t = date.getTime();
            t += (1000 * 60 * 60 * 24) * length;
            var d = new Date();
            d.setTime(t);
            var tzOff2 = d.getTimezoneOffset() * 60 * 1000;
            if (tzOff != tzOff2) {
                var diff = tzOff2 - tzOff;
                t += diff;
                d.setTime(t);
            }
            return d;
        },
        
        /**
         * Function used to add a highlight element onto an available element
         * 
         * @param ele Clicked Element
         * @param i   Index in loop
         * 
         * @return boolean
         */
        _highlightCalTd: function(ele, i) {
            if(ele.hasClass(this.options.availableClass)) {
                ele.addClass(this.options.highlightClass);
                if(i == 0) {
                    ele.addClass(this.options.highlightClass + "start");
                } else if(i == (this._getHolidayLength() - 1)) {
                    ele.addClass(this.options.highlightClass + "end");
                }
                
                return false;
            } else {
                return true;
            }
        },
        
        /**
         * Clear all of the highlighted elements from the calendar
         *
         * @return void
         */
        _clearHighlights: function() {
            var plugin = this;
            $(this.options.calendarCell, this.element).each(function() {
                plugin._clearHighlight($(this))
            });
        },
        
        /**
         * Clear highlighed element
         
         * @param ele Current Element
         *
         * @return void
         */
        _clearHighlight: function(ele) {
            ele.removeClass(this.options.highlightClass)
                .removeClass(this.options.highlightClass + "start")
                .removeClass(this.options.highlightClass + "datestart")
                .removeClass(this.options.highlightClass + "end")
                .removeClass(this.options.highlightClass + "datefinish");
        },

        /**
         * Count the about of elements within a provided object
         *
         * @return integer
         */
        _countJson: function(obj) {
            var count = 0;
            for (var prop in obj) {
                count++;
            }
            return count;
        }
    };

    // A really lightweight plugin wrapper around the constructor,
    // preventing against multiple instantiations
    $.fn[pluginName] = function ( options ) {
        return this.each(function () {
            if (!$.data(this, "plugin_" + pluginName)) {
                $.data(this, "plugin_" + pluginName, new Plugin( this, options ));
            }
        });
    };

})( jQuery, window, document );

/**
 * Date prototype to return string format with leading zeros
 *
 * @return string
 */
Date.prototype.getDateId = function()
{
    return ('0' + this.getDate()).slice(-2) +           // Day
        '-' + ('0' + (this.getMonth() + 1)).slice(-2) + // Month
        '-' + this.getFullYear();                       // Year
}

/**
 * Date prototype to return string format with leading zeros
 *
 * @return string
 */
Date.prototype.toString = function()
{
    return ('0' + this.getDate()).slice(-2) +           // Day
        '-' + ('0' + (this.getMonth() + 1)).slice(-2) + // Month
        '-' + this.getFullYear();                       // Year
}

/**
 * String prototype function to create a date from an id string
 *
 * @return Date
 */
String.prototype.getDateFromIdString = function()
{
    // Create starting date
    var arrTmp = this.split("-");
    var startDay = new Date(arrTmp[2], (arrTmp[1]-1), arrTmp[0]);
    startDay.setHours(0, 0, 0, 0);
    return startDay;
}