# Announcement: Merging with phpLiteAdmin #
The developer of [phpLiteAdmin](http://phpliteadmin.googlecode.com) and I have decided to join teams and work on a single and comprehensive SQLite manager for PHP, which works with SQLite 3 and 2, through built-in PHP functions and PDO as well.

By joining together we can develop a much better SQLite manager with a more rich set of features.

Currently there is no 'one SQLite manager to rule them all' for PHP like phpMyAdmin is, and our goal is to do just that with phpLiteAdmin.

As of now support for all versions of phpLiterAdmin has been dropped, and it is recommended you use [phpLiteAdmin](http://phpliteadmin.googlecode.com). As of this announcement (March 7, 2011), there are some missing features in phpLiteAdmin that are in phpLiterAdmin (and vice versa), but we will be working on resolving that soon.

_**4/26/11 UPDATE:**
All major features present in the latest version of phpLiterAdmin have been implemented in phpLiteAdmin. You can now comfortably use phpLiteAdmin knowing that you aren't missing out on any functionality._

# phpLiterAdmin, the better SQLite Manager #
phpLiterAdmin is an SQLite Manager which uses PHP. Unlike some SQLite Managers, phpLiterAdmin is SQLite v2.x compatible. Many features are in phpLiterAdmin right now, and hopefully more to come.

## Check out our blog! ##
For news and information about the development of phpLiterAdmin, check out our blog on [Blogger](http://phpliteradmin.blogspot.com).

## ALERT: Please update to phpLiterAdmin v1.0 RC1.1 ##
I have been alerted of a [security vulnerability in phpLiterAdmin v1.0 RC1](http://www.htbridge.ch/advisory/authentication_bypass_in_phpliteradmin.html).

**This is now fixed** in RC1.1, however, v0.9.0 is unaffected! So if you are using v1.0 RC1, it is _**urgent**_ you update your phpliteradmin.php file.

### phpLiterAdmin v1.0 ###
The newest version of phpLiterAdmin is currently available, however, it is not officially released, though you can download it through the [Downloads](http://code.google.com/p/phpliteradmin/downloads/list?q=beta) page or get the latest through our [SVN](http://code.google.com/p/phpliteradmin/source/checkout).

**Instructions**:
Download of course, then open the phpliteradmin.php file, find $settings['users'] and change the username and password (Read the comments on how to add more users), this makes it so you can protect your database file then find $settings['db'] and change database.db to the path of your database (You can also put an array of databases, read the file for more information). Now, if you want to be able to write (CREATE TABLE, INSERT, UPDATE, DROP, DELETE, lol, and so on) to the database file, you need to CHMOD the file to 777, otherwise you can only do like SELECT and anything that has nothing to do with altering the file ;]

If you find any security holes, please contact me @ aldo@mschat.net

## Feature List ##
  * Ability to switch databases quickly. (Multiple database support)
  * Password protection with multiple users.
  * Lock down mode
  * Export and import the database
  * Query box, where you can run multiple queries into your database. You can also query a SELECT and have the results displayed.
  * List of tables in the database
  * Quick table tools including: VACUUM, Empty tables, drop tables and show/hide indexes.
  * Quickly insert rows into tables
  * Quickly edit rows
  * Quickly delete rows (Since v1.0)
  * Show Create Table ability
  * Table structure viewer (Since v1.0)
  * Table creation tool (Since v1.0)