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
?>