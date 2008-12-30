/**
 *
 */
var Memex_Nostalgia_Main = function() {

    return {

        init: function() {

            // see: http://www.dustindiaz.com/input-element-css-woes-are-over/
            var els = document.getElementsByTagName('input');
            var elsLen = els.length;
            var i = 0;
            for (i=0; i<elsLen; i++) {
                if (els[i].getAttribute('type')) {
                    els[i].className = els[i].getAttribute('type');
                }
            }

            return this;
        },

        EOF: null

    };

}().init();
