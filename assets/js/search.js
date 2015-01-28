function formPreSearch() {
    
    var options = {},   // Local options
        plugin;         // Global plugin reference
    
    /**
     * Extend the options of the class
     * 
     * @access public
     *
     * @return array
     */
    this.extend = function() {
        for(var i = 1; i < arguments.length; i++) {
            for(var key in arguments[i]) {
                if(arguments[i].hasOwnProperty(key)) {
                    arguments[0][key] = arguments[i][key];
                }
            }
        }
        return arguments[0];
    };
    
    /**
     * Initialise the plugin.  Call this to go!
     * 
     * @param {Array} args
     * 
     * @returns {null}
     */
    this.init = function(args) {

        // Make this globally accessible
        plugin = this;
        
        // Get options
        options = plugin.extend({}, _getDefaults(), args);
    };
    
    /**
     * Get the defaults
     * 
     * @returns {Array}
     */
    var _getDefaults = function() {
        return {};
    };
}

var fps = new formPreSearch();
fps.init((typeof Drupal !== 'undefined' && Drupal.settings.toccsearchmap) || {});