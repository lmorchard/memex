/**
 * Main JS bootstrap for nostalgia theme
 */
dojo.provide("memex.nostalgia.main");
memex.nostalgia.main = function() {

    return {

        init: function() {
            dojo.addOnLoad(this, 'onLoad');
        },

        onLoad: function() {

            // see: http://www.dustindiaz.com/input-element-css-woes-are-over/
            dojo.query('input[type]').forEach(function(el) {
                dojo.addClass(el, dojo.attr(el, 'type'));
            }, this);

        },

        EOF: null
    };
}().init();
