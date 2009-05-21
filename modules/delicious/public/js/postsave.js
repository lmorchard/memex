/**
 * Delicious module JS for post save form.
 */
$module('Memex.Delicious.PostSave', function() {
    return {

        options: {
            key_delay: 300
        },

        key_timer: null,
        is_loading: false,

        /**
         * Initialize the module, fire off an initial suggestions load.
         */
        initialize: function() {
            this.parent();
            this.fetchSuggestions();
        },

        /**
         * Wire up suggestion fetches to changes in the URL field.
         */
        onReady: function() {
            var url_change = this.handleUrlChange.bindWithEvent(this);
            $$('.ctrl_post_act_save #url').addEvents({
                // keydown: url_change,
                change: url_change
            });
            $$('.delicious_tag_suggestions').addEvents({
                click: this.handleTagSuggestionsClick.bindWithEvent(this)
            });
        },

        /**
         * Respond to URL changes by fetching suggestions.
         */
        handleUrlChange: function(ev) {
            // Schedule suggestion fetches one-at-a-time, and only after the
            // URL has stopped changing for a little while.
            if (this.key_timer) $clear(this.key_timer);
            if (this.is_loading) return;
            this.key_timer = this.fetchSuggestions
                .delay(this.options.key_delay, this);
        },

        /**
         * Respond to clicks on tag suggestions by toggling the presence of
         * that tag in the tag field.
         */
        handleTagSuggestionsClick: function(ev) {

            if (ev.target.getParent().hasClass('tag')) {

                // Grab the current list of tags and the clicked tag.
                var tags_el = $$('.ctrl_post_act_save #tags')[0],
                    tags    = (''+tags_el.get('value')).split(' '),
                    new_tag = ev.target.get('text');
                
                // Toggle the clicked tag in the list.
                if (tags.contains(new_tag)) {
                    tags.erase(new_tag);
                } else {
                    tags.include(new_tag);
                }

                // Update the tag set.
                tags_el.set('value', tags.join(' '));

                ev.stop();
            }

        },

        /**
         * Fire off a request for tag suggestions.
         */
        fetchSuggestions: function() {
            this.updateSuggestions({
                recommended: ['loading'],
                popular:     ['loading'],
                network:     ['loading']
            });

            this.is_loading = true;
            $$('.delicious_tag_suggestions')
                .addClass('loading');

            new Request.JSON({
                url: this.options.base_url + 'delicious/tag_suggestions',
                onComplete: this.updateSuggestions.bind(this)
            }).get({
                url: $$('.ctrl_post_act_save #url').get('value')
            });
        },

        /**
         * Reset suggestions, clearing out existing tags and loading
         * indicators.
         */
        resetSuggestions: function() {
            this.is_loading = false;
            $$('.delicious_tag_suggestions')
                .removeClass('loading');
            $$('.delicious_tag_suggestions .tag')
                .filter(function (el) { return !el.hasClass('template') })
                .dispose();
        },

        /**
         * Handle the arrival of tag suggestions by updating the display.
         */
        updateSuggestions: function(data) {
            this.resetSuggestions();

            ['recommended', 'popular'/*, 'network'*/].each(function(section_name) {

                var section = $$('.tags_' + section_name)[0];
                var tmpl = section.getChildren('.template')[0];

                data[section_name].each(function(tag) {
                    var c = tmpl.clone().removeClass('template');
                    c.getChildren('a').set({ text: tag });
                    section.grab(c);
                }, this);

            }, this);
        },
        
        EOF: null
    }
}());
