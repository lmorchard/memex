/**
 * Main JS module for Autotag module
 */
$module('Memex.Autotag.Main', function() {
    return {

        options: {
        },

        /**
         * Wire up controls for autotags on page ready.
         */
        onReady: function() {
            this.showSystemTags('show' == Cookie.read('show_system_tags'));
            $$('.options .autotags .system_tags input[name=visibility]')
                .addEvent('click', this.handleVisibility.bindWithEvent(this));
        },

        /**
         * Handle clicks on system tag hide/show radio buttons.
         */
        handleVisibility: function(ev) {
            this.showSystemTags(('show' == ev.target.get('value')));
        },

        /**
         * Show or hide system tags.
         */
        showSystemTags: function(do_show) {
            Cookie.write('show_system_tags', do_show?'show':'hide', {
                duration: 365 * 5
            });
            
            var method = do_show ? 'addClass' : 'removeClass';
            $$('.posts')[method]('reveal_tag_system');

            var value = do_show ? 'show' : 'hide';
            $$('.options .autotags .system_tags input[value='+value+']')
                .set('checked', true);
        },

        EOF: null
    }
}());
