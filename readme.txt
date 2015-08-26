=== tao-schedule-update ===
Contributors: romanweinberger, syberspace
Donate link: http://software.tao.at/
Tags: publishing, timing, cron, schedule, unattended
Requires at least: 3.7.0
Tested up to: 4.3
Stable tag: trunk
License: MIT
License URI: http://opensource.org/licenses/MIT

Take a copy of an arbitrary post/page/cpt, change it and make it replace the original post at a given date and time in the future.

== Description ==


A simple Wordpress Plugin to Schedule Content Updates

= Motivation: =

These days Wordpress is scarcely used as a pure blog. Most of the time it is used as a full blown CMS with many additional requirements. Especially the publishing workflow for posts and pages as well as their changes becomes demanding. Using plugins like visual composer or advaced custom fields with their flexbox addon it becomes common to build huge startpages, subsites and langpages using a simple, understandable visual editor insted of bolting them together using custom post types. If this is a good and DRY way to go is not to discuss here :)

A Problem arises as soon as you try updating such a complex single page at a specific date in the future - there is no easy way so schedule changes to already published wordpress pages inside the wordpress core functionality. Existing plugins that try to tackle this problem are often far from feature complete and try to re-use the revision system. The experience we made with our customers showed, that the revisions approach is hard to grasp for less technical users and also rather error prone.

These are the main reasons for us to brew our own solution.

= Features: =

TAO Scheduled change is a really small plugin. It only does one thing, but tries to do this the right way: Take a copy of an arbitrary post/page/cpt, change it and make it replace the original post at a given date and time in the future.

![Feature](https://raw.githubusercontent.com/tao-software/tao-schedule-update/master/tao-schedule-update.gif)


== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. No 3rd step neccessary

== Screenshots ==

1. The Plugin in Action

== Changelog ==

= 1.05 =

* fix bug where kses filters would run on automatically published posts
* add check for empty array in copy_meta_and_terms function.

= 1.04.01 =

* fix bug where publishing a post overrides other content
* fix potential breakeage when cloning a post without meta infos

= 1.04 =

* directly go to edit screen after creating a scheduled post
* terms are now also copied over to the new post
* fixed bug where multiple meta keys with the same name were not correctly copied back to the original post
* removed possibility to schedule updates for posts in trash

= 1.03 =

* TAO_ScheduleUpdate::create_publishing_post now returns the created posts's ID

= 1.02.01 =

* fix scheduled posts appearing in search
* fix problems with l10n textdomain

= 1.02 =

* PHP 5.3 Support

= 1.01 =

* Readme update

= 1.0 =

* The Initial Release

