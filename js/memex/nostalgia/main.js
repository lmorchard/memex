/**
 * Main JS bootstrap for nostalgia theme
 */
$module('Memex.Nostalgia.Main', function() {

    return {

        options: {
        },

        initialize: function() {
            this.parent();
            
            // Toss classes onto form elements based on their types.
            $$('input[type]').forEach(function(el) {
                el.addClass(el.get('type'));
            }, this);
        },

        onReady: function() {
            $$('p.hint').highlight();
            $$('p.highlight').highlight();
        },

        EOF: null
    }
}());
