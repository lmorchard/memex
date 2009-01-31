/**
 * Module for post controller pages.
 */
Memex.Nostalgia.Post = function() {
    return {

        init: function() {
            window.addEvent('domready', this.onReady.bind(this));
        },

        onReady: function() {
            $$('#post #tags').forEach(function(el) { el.focus() });
        },

        EOF: null
    };
}().init();
