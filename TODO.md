## TODO

* Basic stuff
    * Tag suggestions on save form
    * All tags per profile
    * Top tags site-wide
    * Related tags?
* Installation
    * Make *-dist versions of config files, delete originals
    * Implement an installer that can produce local configs
* Tools
    * Schema / data loading tool with option to import posts/all from del
* Documentation
    * Write more docs and help files
    * Bookmarklets
* UI
    * Create a more original theme than "nostalgia"
    * AJAXify post save / edit / delete
    * Tag management
    * Tag bundles
    * Item privacy / visibility
    * [oohembed][oembed] for tumble-log-like media integration?
* Logins / Profiles
    * Email password recovery
    * Prefs, login password change, screen name, bio, etc.
    * Multiple logins per profile
    * OpenID logins for profiles
* API / data access
    * [v1 API][v1api]
    * v2 API
    * AtomPub API
    * {Atom,RSS,JSON} feeds
    * Widgets and whatnot
* Federation
    * Carbon-copy posts to other services (eg. magnolia, twitter, friendfeed)
    * Import from other services
    * Implement network / subscriptions based on RSS/Atom aggregation to login-less local proxy profiles
    * [FeedSync?](http://dev.live.com/feedsync/spec/spec.aspx)
* Misc
    * Add indexes to tables in mysql and sqlite
    * Rename "posts" to "items"?  Not crucial, but odd.
    * More tests, controller tests, filter tests, validator tests, etc
    * Search
    * Bulk import via file upload
    * Fx addon
    * [Message / work queues and deferred processing][queues]
    * Make it scale

[oembed]: http://oohembed.com/
[v1api]: http://delicious.com/help/api
[queues]: http://decafbad.com/blog/2008/07/04/queue-everything-and-delight-everyone
