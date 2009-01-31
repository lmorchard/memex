if (typeof window.Memex == 'undefined') 
    window.Memex = {};
if (typeof window.Memex.Nostalgia == 'undefined') 
    window.Memex.Nostalgia = {};

/**
 * Main JS bootstrap for nostalgia theme
 */
Memex.Nostalgia.Main = function() {
    return {

        init: function() {
            window.addEvent('domready', this.onReady.bind(this));

            // Toss classes onto form elements based on their types.
            $$('input[type]').forEach(function(el) {
                el.addClass(el.get('type'));
            }, this);
        },

        onReady: function() {
            $$('p.hint').highlight();
        },

        EOF: null
    };
}().init();
