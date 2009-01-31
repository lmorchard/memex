/**
 * Module construction utility.
 */
$module = function(name, obj) {

    // Build the hash namespace based on module name.
    var root  = window,
        parts = name.split('.'),
        last  = parts.pop();
    parts.each(function(part) {
        if (typeof root[part] == 'undefined') 
            root[part] = {};
        root = root[part];
    });

    // Create a new subclass on the fly based on the supplied module object.
    var cls = new Class($extend({
        Extends: arguments.callee.base,
        name: name
    }, obj));

    // Stash a new instance of the subclass into the namespace and return it.
    return root[last] = new cls();

};

/**
 * Base class for all modules.
 */
$module.base = new Class({
    Implements: [ Options, Events ],

    log_format: '{name}: {msg}',

    initialize: function() {

        // Copy global and module-specific config options.
        if (Memex.Config['global']) 
            this.setOptions(Memex.Config['global']);
        if (Memex.Config[this.name])
            this.setOptions(Memex.Config[this.name]);

        // Wire up the domready handler where present.
        if (this.onReady) {
            window.addEvent('domready', this.onReady.bind(this));
        }

        // Set up logging, or dummy handler in absense of Firebug
        if ("console" in window && "log" in window.console) {
            this.log = function (msg, lvl) { 
                console.log(this.log_format.substitute({
                    name: this.name, msg: msg
                }));
            }.bind(this);
        } else {
            this.log = function (msg, lvl) { }
        }

        if (this.options.debug) this.log('init');

    }
        
});
