/**
 * Main JS module for Memex overall.
 */
$module('Memex.Main', function() {
    return {

        options: {
            check_queue_enable: true,
            check_queue_delay_success: 500,
            check_queue_delay_fail:    5000,
        },

        initialize: function() {
            this.parent();
            this.checkQueue();
        },

        /**
         * Repeatedly hit the message queue worker.
         */
        checkQueue: function() {
            var fn = arguments.callee.bind(this),
                that = this;

            if (this.options.check_queue_enabled) {
                var req = new Request({

                    method: 'POST',
                    url:    this.options.base_url + '/queue/run/json',

                    onSuccess: function(txt, xml) {
                        this.log("MQ: success " + txt);
                        fn.delay(this.options.check_queue_delay_success);
                    }.bind(this),

                    onFailure: function() {
                        if (req.status == 304) {
                            this.log("MQ: no messages");
                        } else {
                            this.log("MQ: failure " + req.status);
                        }
                        fn.delay(this.options.check_queue_delay_fail);
                    }.bind(this)

                }).send();
            }

        },

        EOF: null
    }
}());
