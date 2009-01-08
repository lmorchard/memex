/**
 * Main JS bootstrap for nostalgia theme
 */
dojo.provide("memex.nostalgia.main");
memex.nostalgia.main = function() {

    return {

        init: function() {

            // Toss classes onto form elements based on their types.
            // HACK: This assumes this is loaded at the end of the document,
            // and enough of the DOM is already available.
            // see: http://www.dustindiaz.com/input-element-css-woes-are-over/
            dojo.query('input[type]').forEach(function(el) {
                dojo.addClass(el, dojo.attr(el, 'type'));
            }, this);

            dojo.addOnLoad(this, 'onLoad');
        },

        onLoad: function() {

            // Flash some paragraphs for attention, generally messages
            // from forms.
            dojo.query('p.hint').forEach(function(el) {
                dojo.animateProperty({
                    node: el, duration: 1000, properties: {
                        backgroundColor: { start: '#ff8', end: '#fff' }
                    }
                }).play();
            }, this);

        },

        EOF: null
    };
}().init();
