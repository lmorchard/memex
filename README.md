## Memex, a personal web memory

### Overview

This hopes one day to grow up to be an Open Source social bookmarking web
application.  

### Requirements

* PHP 5+
* MySQL 5+


### Installation

* Visiting `index.php` should present a quick installer that will create the database tables and attempt to create a configuration file.
* Or, manually edit `system/config/local.php` to reflect your environment.
    * There is an example `system/config/local.php-dist` you can use as a starting point.
    * Use `system/schema/mysql.sql` to create the database tables.
* Make sure the `system` directory is not web readable. 
    * The `.htaccess` should already do this out of the box on Apache.
    * Modify `index.php` to point to new path, if you move the `system` directory.

[kf]: http://kohanaphp.com/download

### Credits / Colophon

* Leslie Michael Orchard - <http://decafbad.com/> - <mailto:l.m.orchard@pobox.com>
* Using [Kohana Framework][kf] v2.3.1
* Using [MooTools][mootools] [1.2.1][moodownload]
* Using [Markdown PHP][markdown] for documentation.
* Using [UUID implementation][uuid] from OmniTI 
* "nostalgia" theme inspired by [del.icio.us][del], circa 2005

[mootools]: http://mootools.net/
[moodownload]: http://mootools.net/download/
[zf]: http://framework.zend.com/
[del]: http://del.icio.us/
[markdown]: http://michelf.com/projects/php-markdown/
[uuid]: https://labs.omniti.com/trac/alexandria/browser/trunk/OmniTI/UUID.php?rev=7

### License

<a rel="license" href="http://creativecommons.org/licenses/by-sa/3.0/"><img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by-sa/3.0/88x31.png" /></a><br />This work is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by-sa/3.0/">Creative Commons Attribution-Share Alike 3.0 Unported License</a>.
