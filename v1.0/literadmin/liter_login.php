<?php
/*
              phpLiterAdmin
         http://nosql.110mb.com/
    http://phpliteradmin.sourceforge.net/
         
     I have not really been able to find
    any SQLite 2.x compatible SQL Manager
     so I am working on one to hopefully
           help some people out!
           
          File: liter_login.php
*/

# No direct access :P
if(!defined('LiterAdmin'))
  die('Go Away...');

# Doesn't really do much of anything
# really, just shows the login form
# and if an error, show it...
function literLogin() {
global $login_error;  
  template_header();
  
  echo '
  <div id="login_area">
    <h1>Login</h1>
    <fieldset>
      <form action="', $_SERVER['PHP_SELF'], '" method="post">
        <table>',
        $login_error ? '
          <tr align="center">
            <td colspan="2">'. $login_error. '</td>
          </tr>' : '', '
          <tr>
            <td>Username</td><td><input name="login_username" type="text" value="', !empty($_REQUEST['username']) ? htmlspecialchars($_REQUEST['username'], ENT_QUOTES) : '', '"/></td>
          </tr>
          <tr>
            <td>Password</td><td><input name="login_password" type="password" value=""/></td>
          </tr>
          <tr align="center">
            <td>Remember Me?</td><td><input name="remember_me" type="checkbox" value="1" checked="checked"/></td>
          </tr>
          <tr align="center">
            <td colspan="2"><input name="proc_login" type="submit" value="Login"/></td>
          </tr>
        </table>
      </form>
    </fieldset>
  </div>';
  
  template_footer();
}

# Logout... Destroys the current
# Session and the login cookies
# to ensure you are really logged
# out :)
function literLogout() {
  # So, destroy the Session
  session_destroy();
  # Set the cookies back about
  # a month, that ought to 
  # teach them :P
  setcookie('user', '', time() - (60 * 60 * 24 * 30));
  setcookie('pass', '', time() - (60 * 60 * 24 * 30));
  # And now redirect them 
  # back to the phpLiterAdmin
  # home so they can login xD
  ob_clean();
  header('Location: '. $_SERVER['PHP_SELF']);
  exit;
}
?>