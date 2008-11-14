<?php
/*
              phpLiterAdmin
         http://nosql.110mb.com/
    http://phpliteradmin.sourceforge.net/
         
     I have not really been able to find
    any SQLite 2.x compatible SQL Manager
     so I am working on one to hopefully
           help some people out!
           
         File: misc_functions.php
*/

# No direct access! Gosh! IDIOT! :D
if(!defined('LiterAdmin'))
  die('Go Away...');

# This fixes the dumb magic quotes, if
# it needs to be that is...
function magicWizard() {
global $_COOKIE, $_GET, $_POST, $_REQUEST;
  # Remember, only if Magic Quotes in on
  # to begin with ;)
  if(get_magic_quotes_gpc()) {
    $_COOKIE = castMagicSpell($_COOKIE);
    $_GET = castMagicSpell($_GET);
    $_POST = castMagicSpell($_POST);
    $_REQUEST = castMagicSpell($_REQUEST);
  }
}

# This casts the magic spell that does...
# I mean, it strips slashes :P
function castMagicSpell($array) {
  $new_array = array();
  if(count($array)) {
    foreach($array as $key => $value) {
      # Is it NOT an array?
      if(!is_array($value)) {
        $new_array[$key] = stripslashes($value);
      }
      else {
        # Yes yes, we could use cool-o
        # recursion, but we don't want
        # to have anyone exploit it and
        # make some kind of an endless
        # loop :P
        foreach($value as $sub_key => $sub_value) {
          $new_array[$key][$sub_key] = is_array($sub_value) ? $sub_value : stripslashes($sub_value);
        }
      }
    }
  }
  # Now return it, whether or not something
  # is actually in that array :)
  return $new_array;
}

# This does the theme_header of the function
function template_header($title = '', $show_q = true) {
global $current_dir, $settings, $is_logged;
  # Verify a few things, such as make sure the
  # theme requested exists and if the theme_header()
  # function of the theme is setup right :) (you know
  # like if it exists)
  if(file_exists($current_dir. '/literadmin/themes/'. $settings['theme']. '.theme.php')) {
    # So it does exist, or at least the file ;)
    require_once($current_dir. '/literadmin/themes/'. $settings['theme']. '.theme.php');
    # Does the theme_header() exist..?
    if(function_exists('theme_header')) {
      theme_header($title, $show_q);
    }
    else
      die('The function theme_header() has not been defined by the '. $settings['theme']. ' theme!');
  }
  else
    die('The theme '. $settings['theme']. ' does not exist!');
}

# This does the theme_footer of the function
# Copy & Paste ftw :P
function template_footer() {
global $current_dir, $settings, $is_logged;
  # Verify a few things, such as make sure the
  # theme requested exists and if the theme_header()
  # function of the theme is setup right :) (you know
  # like if it exists)
  if(file_exists($current_dir. '/literadmin/themes/'. $settings['theme']. '.theme.php')) {
    # So it does exist, or at least the file ;)
    require_once($current_dir. '/literadmin/themes/'. $settings['theme']. '.theme.php');
    # Does the theme_header() exist..?
    if(function_exists('theme_footer')) {
      theme_footer();
    }
    else
      die('The function theme_footer() has not been defined by the '. $settings['theme']. ' theme!');
  }
  else
    die('The theme '. $settings['theme']. ' does not exist!');
}

function db_list() {
global $settings, $is_logged;
}

# This processes (sorta) the Login
# that someone has tried to attempt
# It really just handles the error :P!
function procLiterLogin() {
global $login_error, $is_logged, $settings;
  # Only do this if its requested...
  if(!$is_logged && !empty($_REQUEST['proc_login'])) {
    # Ok, now check :P!
    $user = !empty($_REQUEST['login_username']) ? $_REQUEST['login_username'] : '';
    $pass = !empty($_REQUEST['login_password']) ? $_REQUEST['login_password'] : '';
    # No need to go much farther if nothing
    # is in the users array!!!
    if(count($settings['users'])) {
      # So is it set in the array and do the
      # passwords match?
      if(!empty($settings['users'][$user]) && ($settings['users'][$user] == $pass)) {
        # Set the session up, and also
        # cookies if need be... mmmmmm
        # cookies :)
        $_SESSION['user'] = $user;
        $_SESSION['pass'] = sha1($pass);
        if(!empty($_REQUEST['remember_me'])) {
          setcookie('user', $user, time() + (60 * 60 * 24 * 30));
          # Yes, we encrypt the cookie password
          # just cause I can! >:D
          setcookie('pass', sha1($pass), time() + (60 * 60 * 24 * 30));
        }
      }
      else {
        # Oh noes! Error!
        $login_error = 'Wrong Username or Password';
      }
    }
    else {
      # We will make them think it was a
      # login error... xD
      $login_error = 'Wrong Username or Password';
    }
  }
}
?>