## Memex, a personal web memory

### Overview

This hopes one day to grow up to be an Open Source social bookmarking web
application.  

Or, at least, it will help me learn the Zend Framework.

### Installation

* Get a copy or a link of the latest [Zend Framework][zf] into the `system/library/` directory.
* Try using `system/scripts/load.sqlite.php` to create the database.
* Make sure the `system` directory is not web readable.  
* Modify `index.php` to point to new path, if you move `system`.

[zf]: http://framework.zend.com/download/latest

### Credits / Colophon

* Leslie Michael Orchard - <http://decafbad.com/> - <mailto:l.m.orchard@pobox.com>
* Liberal portions stolen from <http://github.com/weierophinney/bugapp/tree/master>
* Using [Zend Framework][zf] v1.7.1
* Using [Dojo Toolkit][dojo] [v1.2.3][dojodl]
* Using [Markdown PHP][markdown] for documentation.
* Using [UUID implementation][uuid] from OmniTI 
* "nostalgia" theme inspired by [del.icio.us][del], circa 2005

[zf]: http://framework.zend.com/
[dojo]: http://dojotoolkit.org/
[dojodl]: http://download.dojotoolkit.org/release-1.2.3/
[del]: http://del.icio.us/
[markdown]: http://michelf.com/projects/php-markdown/
[uuid]: https://labs.omniti.com/trac/alexandria/browser/trunk/OmniTI/UUID.php?rev=7

### License

<a rel="license" href="http://creativecommons.org/licenses/by-sa/3.0/"><img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by-sa/3.0/88x31.png" /></a><br />This work is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by-sa/3.0/">Creative Commons Attribution-Share Alike 3.0 Unported License</a>.
