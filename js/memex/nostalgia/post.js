/**
 * Module for post controller pages.
 */
$module('Memex.Nostalgia.Post', function() {
    return {
        initialize: function() {
            this.parent();

        },
        onReady: function() {

            $$('#post #title').each(function(el) {
                if (el.get('value')) {
                    // Focus tags if there's a title.
                    $$('#post #tags')[0].focus();
                } else {
                    // Otherwise, focus the title.
                    el.focus();
                }
            }, this);

        }
    };
}());
