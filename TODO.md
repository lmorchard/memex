## TODO

* Dog-food Annoyances
    * No tag recommendations
* Basic stuff
    * Tag suggestions on save form
    * All tags per profile
        * ..and do not forget feeds.
    * Top tags site-wide
    * Related tags?
    * Implement automatically applied system:* tags. (plugin?)
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
* Federation
    * Daily blog poster
    * Carbon-copy posts to other services (eg. magnolia, twitter, friendfeed)
    * Import from other services
    * Implement network / subscriptions based on RSS/Atom aggregation to login-less local proxy profiles
    * [FeedSync?](http://dev.live.com/feedsync/spec/spec.aspx)
* Misc
    * Enclosures for Atom / RSS feeds for podcast support.
    * Etag and Last-Modified for feeds
    * Bookmarklet for discovering and bookmarking feeds
    * DB prefixes for MySQL?
    * Rename "posts" to "items"?  Not crucial, but odd.
    * More tests, controller tests, filter tests, validator tests, etc
    * Tag descriptions
    * Search
    * Bulk import via file upload
    * Fx addon
    * [Message / work queues and deferred processing][queues]
    * Maybe change license to BSD?  MIT?
    * Make it scale

[oembed]: http://oohembed.com/
[v1api]: http://delicious.com/help/api
[queues]: http://decafbad.com/blog/2008/07/04/queue-everything-and-delight-everyone
