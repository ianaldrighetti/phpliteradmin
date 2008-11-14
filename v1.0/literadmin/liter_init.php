<?php
/*
              phpLiterAdmin
         http://nosql.110mb.com/
    http://phpliteradmin.sourceforge.net/
         
     I have not really been able to find
    any SQLite 2.x compatible SQL Manager
     so I am working on one to hopefully
           help some people out!
           
          File: liter_init.php
*/

# No direct access :P
if(!defined('LiterAdmin'))
  die('Go Away...');

# Start up the session...
session_start();

# OB_START :P
if(function_exists('ob_gzhandler'))
  ob_start('ob_gzhandler');
else
  ob_start();

# Get some more misc. functions
require_once($current_dir. '/literadmin/misc_functions.php');

# Grrr... Magic Quotes causes more problems
# then it fixes >.<!
magicWizard();

# Just set it off, idk why, why not?
@set_magic_quotes_runtime(0);

# Just process the login, really all this does
# is error checking :P!
procLiterLogin();

# Check if like they are attempting to login
# or possibly renew their session :P
if(empty($_SESSION['user']) && empty($_SESSION['pass']) && !empty($_COOKIE['user']) && !empty($_COOKIE['pass'])) {
  $_SESSION['user'] = $_COOKIE['user'];
  $_SESSION['pass'] = $_COOKIE['pass'];
}

# Now verify their login details :)
$is_logged = false;
if(!empty($settings['users'][$_SESSION['user']]) && $_SESSION['pass'] == sha1($settings['users'][$_SESSION['user']]))
  $is_logged = true;
?>