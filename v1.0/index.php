<?php
/*
              phpLiterAdmin
         http://nosql.110mb.com/
    http://phpliteradmin.sourceforge.net/
         
     I have not really been able to find
    any SQLite 2.x compatible SQL Manager
     so I am working on one to hopefully
           help some people out!
           
            v1.0 Private Beta
           
*/

define('LiterAdmin', true);
$current_dir = dirname(__FILE__);
$start_time = microtime(true);
# We need to include some stuff :)
require_once($current_dir. '/litersettings.php');
require_once($current_dir. '/literadmin/db_functions.php');
require_once($current_dir. '/literadmin/liter_init.php');

$act = !empty($_REQUEST['act']) ? $_REQUEST['act'] : '';

# So what are we doing..?
# Lets build an array of actions we can
# do :) but only if you are logged in.
if($is_logged && !$settings['locked_down']) {

}
elseif(!$is_logged && !$settings['locked_down']) {
  # Not logged in? !!!! D:
  require_once($current_dir. '/literadmin/liter_login.php');
  literLogin();
}
else {
  # We are in lock down :P can't touch this! ^^
  # Uh, yeah, we are just gonna set you as not
  # logged in :)
  $is_logged = false;
  template_header('phpLiterAdmin Locked Down', false);
  
  template_footer();
}
?>