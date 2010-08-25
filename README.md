iTunesServer
============

Where <a href="http://github.com/iamcal/iTunesRemote">iTunesRemote</a> meets <a href="http://github.com/lhl/songclub">SongClub</a>.

iTunesServer allows you to manage and play a (large) music collection over the web.
Just install it on your web server, point it at your music folder and go.


Installation
------------

This is a work in progress, but basically:

* Make sure your server is running Apache and mod_php
* You'll need MySQL too
* Load <code>schema.sql</code> into a database
* Copy <code>include/config.php.example</code> to <code>include/config.php</code> and modify the settings in it
* Point your browser at <code>feed/feed.php</code> (this will be automated later)
* Point your browser at <code>feed/crawl.php</code> (ditto)
* Ok go!
