/**
 * Module for post controller pages.
 */
dojo.provide("memex.nostalgia.post");
memex.nostalgia.post = function() {

    return {

        init: function() {
            dojo.addOnLoad(this, 'onLoad');
        },

        onLoad: function() {

            dojo.query('#post #tags').forEach(function(el) {
                el.focus();
            }, this);

        },

        EOF: null
    };
}().init();

