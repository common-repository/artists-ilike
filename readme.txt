=== Plugin Name ===
Contributors: Robert McLeod
Donate link: http:///
Tags: ilike, music, bands
Requires at least: 2.5.1
Tested up to: 2.6.5
Stable tag: 1.3.4

Displays the artists you have "ilike'd" from your ilike.com profile on your blog.

== Description ==

Please note this plugin has broken since iLike changed their HTML and has not yet
been fixed.  I hope to fix it when I get time.

New in 1.3.4
* New collage mode that shows just thumbnails
* Local caching of thumbnails so less traffic used every pageload

Fixed in 1.3.2
* Artist map actually generates now
* Tells you when you last generated

Artists iLike is a plugin that displays all the artists that you have "iLike'd" in your iLike.com profile, and displays them on your blog where ever you put the &lt;!-- artists ilike --&gt; tag.

* Stores your iLike username to the WordPress database
* When you click the regenerate users button the plugin will store your artists to the WordPress database
* Locally cached artist map means your server doesn't have to load the iLike site every time you view your artists
* Uses cURL and simplephpdom libraries

Please note you will need to make your ilike profile publicly visible.  You can do this in your iLike profile options on the iLike.com site.

Demo, more info, comments, feedback, and changelogs at http://www.hamstar.co.nz/?page_id&#61;104

== Installation ==

Please note that you must have cURL installed on your system.

1. Download the Artists iLike Plugin
1. Unzip the plugin to your wp-content/plugins folder
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place &lt;!-- artists ilike --&gt; where ever you want your artists list displayed

== Frequently Asked Questions ==

No questions yet

== Screenshots ==

1. This is the collage mode
2. This is the normal mode
3. This is the admin page
