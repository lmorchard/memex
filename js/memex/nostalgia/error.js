/**
 * Module for error controller pages.
 */
Memex.Nostalgia.Error = function() {
    return {

        init: function() {
            $log("Memex.Nostalgia.Error.init()");
            window.addEvent('domready', this.onReady.bind(this));
            return this;
        },

        onReady: function() {

        },

        EOF: null
    };
}().init();
