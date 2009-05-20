/**
 * Main JS module for Delicious module
 */
$module('Memex.Delicious.PostSave', function() {
    return {

        options: {
            key_delay: 300
        },

        key_timer: null,
        is_loading: false,

        initialize: function() {
            this.parent();
        },

        onReady: function() {
            var url_change = this.handleUrlChange.bindWithEvent(this);
            $$('.ctrl_post_act_save #url').addEvents({
                keydown: url_change,
                change: url_change,
            });
            $$('.delicious_tag_suggestions').addEvents({
                click: this.handleTagClick.bindWithEvent(this)
            });
            this.fetchSuggestions();
        },

        handleUrlChange: function(ev) {
            if (this.key_timer) $clear(this.key_timer);
            if (this.is_loading) return;
            this.key_timer = this.fetchSuggestions
                .delay(this.options.key_delay, this);
        },

        handleTagClick: function(ev) {
            if (!ev.target.getParent().hasClass('tag')) return;

            var tags_el  = $$('.ctrl_post_act_save #tags')[0],
                tags_val = tags_el.get('value'),
                tags     = (''+tags_val).split(' ');

            tags.push(ev.target.get('text'));
            
            var seen = {};
            tags_el.set('value', tags
                .filter(function(t) { 
                    return seen[t] ? false : (seen[t] = 1); 
                })
                .join(' ')
            );

            ev.stop();
        },

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

        resetSuggestions: function() {
            this.is_loading = false;
            $$('.delicious_tag_suggestions')
                .removeClass('loading');
            $$('.delicious_tag_suggestions .tag')
                .filter(function (el) { return !el.hasClass('template') })
                .dispose();
        },

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
