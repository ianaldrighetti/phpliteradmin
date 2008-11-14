<?php
/*
              phpLiterAdmin
         http://nosql.110mb.com/
    http://phpliteradmin.sourceforge.net/
         
     I have not really been able to find
    any SQLite 2.x compatible SQL Manager
     so I am working on one to hopefully
           help some people out!
           
         File: db_functions.php
*/

# No direct access :P
if(!defined('LiterAdmin'))
  die('GO Away...');
  
# This is the phpLiterAdmin default theme

# The header of the template...
# You can give it the title and also
# whether or not you want it to show
# the query box or not :)
function theme_header($title = '', $show_q = true) {
global $settings, $is_logged;
  echo '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>phpLiterAdmin ', $title ? '- '.$title : '', '</title>
	<meta name="robots" content="noindex"/>
	<script language="javascript" type="text/javascript">
  <!--
  function faq(url) {
	  newwindow=window.open(url,\'name\',\'height=200,width=150\');
	  if (window.focus) {newwindow.focus()}
	    return false;
  }
  // -->
  function select_tables(input) {
    first = 0;
    for(i = 0; i < input.length; i++) {
      if(input[i].type == "checkbox") {
        if(first == 0)
          first = i;
        else {
          if(input[i].checked)
            input[i].checked = \'\';
          else
            input[i].checked = \'checked\';
        }
      }
    }
  }
  function clear_input(input_id) {
    handle = document.getElementById(input_id);
    handle.value = \'\';
  }
  function check_unique() {
    handle = document.getElementsByName(\'unique_id\');
    selected = false;
    for(i = 0; i < handle.length; i++) {
      if(handle[i].checked)
        selected = true;
    }
    if(!selected) {
      alert(\'You must select a unique identifier!\');
      return false;
    }
    else {
      return true;
    }
  }
  </script>
<style type="text/css">
* {
  margin: 0px;
  padding: 0px;
}
body {
  font-family: Verdana, Arial, Helvetica, sans-serif;
  font-size: 12px;
  background: #E5E1C6;
}
#header {
  background: #5E9FBF;
  padding: 5px;
}
#header #left {
  padding-left: 5px;
  float: left;
}
#header #right {
  margin-top: 10px;
  padding-right: 5px;
  float: right;
}
#header a {
  color: #ffffff;
  text-decoration: none;
}
#header a:hover {
  text-decoration: underline;
}
a {
	color: #006699;
}
a:hover {
	color: #0099CC;
} 
#login_area {
  width: 250px;
  padding: 10px;
  margin-right: auto;
  margin-left: auto;
  background: #ffffff;
  border: 1px solid #D5D1B8;
  margin-top: 30px;
}
#login_area input[type="text"], #login_area input[type="password"] {
  width: 125px;
}
#con_error {
  width: 700px;
  padding: 10px;
  margin-right: auto;
  margin-left: auto;
  background: #ffffff;
  border: 1px solid #D5D1B8;
  color: #CC0000;
  text-align: center;
}
#info_center {
  width: 99%;
  padding: 3px;
  background: #FFFFFF;
  margin-bottom: 10px;
  border: 1px solid #D5D1B8;
}
#export {
  width: 400px;
  padding: 10px;
  margin-right: auto;
  margin-left: auto;
  background: #ffffff;
  border: 1px solid #D5D1B8;
}
#import {
  width: 400px;
  padding: 10px;
  margin-right: auto;
  margin-left: auto;
  background: #ffffff;
  border: 1px solid #D5D1B8;
}
#insert {
  width: 650px;
  padding: 10px;
  margin-right: auto;
  margin-left: auto;
  background: #ffffff;
  border: 1px solid #D5D1B8;
  margin-top: 30px;
}
#edit_rows {
  width: 650px;
  padding: 10px;
  margin-right: auto;
  margin-left: auto;
  background: #ffffff;
  border: 1px solid #D5D1B8;
  margin-top: 30px;
}
#server_info {
  width: 400px;
  padding: 10px;
  margin-right: auto;
  margin-left: auto;
  background: #ffffff;
  border: 1px solid #D5D1B8;
  margin-top: 30px;
}
#server_info .var {
  font-weight: bold;
}
#server_info table td {
  text-align: center;
}
#lockdown {
  width: 500px;
  padding: 10px;
  margin-right: auto;
  margin-left: auto;
  background: #ffffff;
  text-align: center;
  margin-top: 30px;
  border: 1px solid #D5D1B8;
}
#powered_by {
  text-align: right;
  font-weight: bold;
  padding-right: 5px;
}
/* Some Extras */
.break {
  clear: both;
}
.bold {
  font-weight: bold;
}
h1 {
  color: #005784;
  font-size: 16px;
}
.error {
  text-align: center;
  color: #CC0000;
}
th {
  background: #0C608C;
  padding: 1px 8px 1px 8px;
  color: #ffffff;
}
tr.tr_1 {
  background: #FFFFFF;
}
tr.tr_2 {
  background: #97C0D4;
}
td {
  padding: 2px 4px 2px 4px;
}
.center {
  text-align: center;
}
.insert {
  text-align: center;
  font-weight: bold;
}
.lil_msg {
  font-size: 10px;
  text-align: center;
}
fieldset {
  border: none;
}
</style>
</head>
<body>
  <div id="header">
    <div id="left">
      <table>
        <tr>
          <td><p><a href="http://phpliteradmin.googlecode.com/" class="bold">phpLiterAdmin v', $settings['version'], '</a></p></td>
          <td width="10px"></td>
          ', db_list(), '
        </tr>
      </table>
    </div>
    <div id="right">';
      /* Only show the menu if they are logged in... */
      if($is_logged) {
        echo '<p><a href="', $_SERVER['PHP_SELF'], '">Show Tables</a> | <a href="', $_SERVER['PHP_SELF'], '?act=export">Export Database</a> | <a href="', $_SERVER['PHP_SELF'], '?act=import">Import Database</a> | <a href="', $_SERVER['PHP_SELF'], '?act=server_info">Server Info</a> | <a href="', $_SERVER['PHP_SELF'], '?act=logout">Logout</a></p>';
      }
  echo '    
    </div>
    <div class="break">
    </div>
  </div>';
  /* The Query Box shouldn't be shown if they are not logged in, or if specially requested */
  if($is_logged && $show_q)
  echo '
  <br /><br /><br />
  <div align="center">
    <p>SQLite Queries to run through the Database: (Queries Separated by semicolons) [<a href="javascript:void(0);" onClick="return faq(\'', $_SERVER['PHP_SELF'], '?act=help&faq=query\');">?</a>]</p>
      <form action="', $_SERVER['PHP_SELF'], '" method="post">
        <textarea id="q_input" name="q" rows="10" cols="70">', htmlspecialchars($_REQUEST['q'], ENT_QUOTES), '</textarea>
        <table>
          <tr>
            <td><input type="button" onClick="clear_input(\'q_input\');" value="Clear"/></td>
            <td><input name="go" type="submit" value="Process Queries!"/></td>
          </tr>
          <input name="act" type="hidden" value="query"/>
        </table>
      </form>
  </div>
  <div id="main">';
}

# The footer of the template :o
function theme_footer() {
global $settings, $start_time, $num_queries;
  echo '
  <br />
  <div id="powered_by">
    <p>Powered by <a href="http://phpliteradmin.googlecode.com/">phpLiterAdmin v', $settings['version'], '</a> | Page created in ', round(microtime(true) - $start_time, 3), ' seconds with ', $num_queries ? $num_queries : 0, ' queries</p>
  </div>
  </div>
</body>
</html>';  
}
?>