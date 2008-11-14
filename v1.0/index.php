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
  $literActions = array(
    'edit_rows' => array('liter_edit.php','print_edit'),
    'export' => array('liter_export.php','export_db'),
    'help' => array('liter_help.php','FAQ'),
    'import' => array('liter_import.php','print_import'),
    'insert' => array('liter_insert.php','print_insert'),
    'logout' => array('liter_login.php','literLogout'),
    'server_info' => array('liter_info.php','print_info')
  );
  if(is_array($literActions[$act])) {
    # We are doing something... very
    # interesting
    require_once($current_dir. '/literadmin/'. $literActions[$act][0]);
    $literActions[$act][1]();
  }
  else {
    # Hmm... Show the Table List ;)
    require_once($current_dir. '/literadmin/tbl_list.php');
    print_main();
  }
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
  
  echo '
  <div id="lockdown">
    <h1>Lock Down</h1>
    <p class="error">Sorry! But phpLiterAdmin is currently on <strong>Lock Down</strong> and cannot be accessed until the Setting is changed in the litersettings.php file</p>
  </div>';
  
  template_footer();
}
?>