## TODO

* Basic stuff
    * All tags per profile
        * ..and do not forget feeds.
    * Top tags site-wide
    * Related tags?
* Logins / Profiles
    * Email password recovery
    * Prefs, login password change, screen name, bio, etc.
    * Multiple logins per profile
    * OpenID logins for profiles
    * Private group / friends access
        * Use invite codes?
* API / data access
    * [v1 API][v1api]
    * v2 API / AtomPub API
    * Widgets and whatnot
* Tools
    * Schema / data loading tool with option to import posts/all from del
    * Work / message queue process
* Documentation
    * Write more docs and help files
    * Bookmarklets
* Admin tools
    * ACLs
    * CRUD on all models
* UI
    * Create a more original theme than "nostalgia"
    * AJAXify post save / edit / delete
    * Tag management
    * Tag bundles
    * Item privacy / visibility
    * [oohembed][oembed] for tumble-log-like media integration?
    * autotags
        * Switch to reveal/hide system tags.
    * delicious tag suggestions
        * ajax loading indicator
* Federation
    * for:* 
        * tags between sites?
        * make for: tags private?
    * Daily blog poster
    * Carbon-copy posts to other services (eg. magnolia, twitter, friendfeed)
    * Import from other services
    * Implement network / subscriptions based on RSS/Atom aggregation to login-less local proxy profiles
    * [FeedSync?](http://dev.live.com/feedsync/spec/spec.aspx)
* Misc
    * Message queue
        * admin pages with queue list and stats
        * per-profile status page list of waiting messages
    * UI notification queue
        * global and per-profile notifications
        * flash / toaster popups
        * piggy-backed on response to JSON message queue check?
        * use as feedback when jobs done (ie. import, tag rename, etc)
    * Event log
        * more persistent version of notification queue?
        * show per-profile log of events
    * Enclosures for Atom / RSS feeds for podcast support.
    * Etag and Last-Modified for feeds
    * Bookmarklet for discovering and bookmarking feeds
    * DB prefixes for MySQL?
    * Rename "posts" to "items"?  Not crucial, but odd.
    * More tests, controller tests, filter tests, validator tests, etc
    * Tag descriptions
    * Web hooks on common messages / events
    * Search
    * Bulk import via file upload
    * Fx addon
        * Track via:* by tracking clicks on links?
    * [Message / work queues and deferred processing][queues]
    * Maybe change license to BSD?  MIT?
    * Make it scale

    * Tag suggestions from co-current tags?

[oembed]: http://oohembed.com/
[v1api]: http://delicious.com/help/api
[queues]: http://decafbad.com/blog/2008/07/04/queue-everything-and-delight-everyone
