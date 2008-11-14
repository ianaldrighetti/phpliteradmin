<?php
/*
              phpLiterAdmin
         http://nosql.110mb.com/
    http://phpliteradmin.sourceforge.net/
         
     I have not really been able to find
    any SQLite 2.x compatible SQL Manager
     so I am working on one to hopefully
           help some people out!
           
          File: litersettings.php
*/

# Holds some settings for phpLiterAdmin
# Can't access directly! :P!
if(!defined('LiterAdmin'))
  die('Go Away...');

# You can add users and allow them access
# to the databases :) to add a user simply
# add a row to the array like this:
# 'USERNAME' => 'PASSWORD',
$settings['users'] = array(
  'admin' => 'admin'
);

# Show the indexes in the table list..?
# These indexes are like Unique indexes
# and such...
$settings['show_indexes'] = false;

# Lock down phpLiterAdmin, if you set this
# to 0, then it won't be locked down but if
# you set it to 1, it will be locked down
# which means no matter what no one can
# have access to anything here without setting
# this back to 0...
$settings['locked_down'] = 1;

# A useful feature :D You can add/remove
# SQLite Databases to this array and switch
# from one to another without needing to edit
# this file
$settings['db'] = array('./db.db','settings.db');

# What theme do you want to use..? The themes
# are located in the /literadmin/themes/
# directory, each template is in 1 file and
# should be named THEME.theme.php ;) you can
# of course create your own theme, but if I 
# were you, I would just use the default one :P
$settings['theme'] = 'default';

# phpLiterAdmin version, don't edit please :P
$settings['version'] = '1.0 Private Beta';
?>