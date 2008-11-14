<?php
/*
              phpLiterAdmin
         http://nosql.110mb.com/
    http://phpliteradmin.sourceforge.net/
         
     I have not really been able to find
    any SQLite 2.x compatible SQL Manager
     so I am working on one to hopefully
           help some people out!
           
*/
session_start();
/* Fix dumb Magic Quotes if need be :P */
fixMagic();
/* Turn Magic Quotes OFF */
@set_magic_quotes_runtime(0);
/* Start how long it took to make the page */
$start = microtime(true);

// Here is a few settings
/* 
  phpLiterAdmin supports more then 1 user! You can add more users in this form:
  'USERNAME' => 'PASSWORD',
*/
$settings['users'] = array(
  'admin' => 'admin'
);

/*
  Show indexes in table list..?
*/
$settings['show_indexes'] = false;

/* 
   Just plain lock this down? 1 = yes, 0 = no
   This is a good thing to have set to 1, as 
   then no one can access your DB this way
*/
$settings['lock_down'] = 0;

/*
   What SQLite Database file do you want to edit?
   You can do multiple by doing:
   $settings['db'] = array('PATH_TO_DB','PATH_TO_ANOTHER','etc');
   As many as you want!
*/
$settings['db'] = array('./db.db','settings.db');

// ----------------------------------------------------------------------
/* 
   You CAN Edit the below, as it is released under the GNU GPL v2 License,
   though I recommend you not edit the below unless you know what you are
   doing, in other words, Get some PHP Knowledge :P
*/
$is_logged = false;
if(!empty($_REQUEST['login'])) {
  /* Get the username and password they submitted */
  $username = $_REQUEST['user'];
  $password = md5($_REQUEST['pass']);
  $r_me = $_REQUEST['r_me'];
  // The hard part :O [Not really :P]
  if(!empty($settings['users'][$username]) && $password ==md5($settings['users'][$username])) {
    /* Set there username and password this will help verify if they are logged in or not */
    $_SESSION['password'] = $password;
    $_SESSION['username'] = $username;
    if(!empty($r_me)) {
      /* They want to be remembered for 30 days (unless they delete there cookies within 30 days) */
      @setcookie("password", $password, time()+(60*60*24*30));
      @setcookie("username", $username, time()+(60*60*24*30));
    }
    /* They are now logged in */
    $is_logged = true;
  }
  else {
    /* Username or Password Wrong! Access DENIED! */
    $settings['error_msg'] = 'Access Denied';
  }
}

// Is the Session Password empty? Also try the Cookie, Mmmmmm... Cookie...
if(empty($_SESSION['password']) && empty($_SESSION['username']) && !empty($_COOKIE['username']) && !empty($_COOKIE['password']))  {
  $_SESSION['password'] = @$_COOKIE['password'];
  $_SESSION['username'] = @$_COOKIE['username'];
}
/* Check if they are logged in, with the write information :D */
if($_SESSION['password']==md5($settings['users'][$_SESSION['username']]) && !empty($settings['users'][$_SESSION['username']]))
  $is_logged = true;

// Please at least don't edit this, Thanks!
$settings['version'] = '0.9.0';

/*
  This is where we make the connection to the SQLite Database ;)
  NOTE: Remember, sqlite_open automatically creates a DB file if
  none is present
*/
if($_REQUEST['act']=='logout') {
  /* 
    Logout, destroy the session, and delete the cookies
    And $is_logged = false; so it will go to the login 
    screen once it is done ;)
  */
  session_destroy();
  @setcookie("password", "", time()-(60*60));
  @setcookie("username", "", time()-(60*60));
  $is_logged = false;
}
if($is_logged) {
  // Need to set the default Show Indexes?
  if(!isset($_SESSION['show_indexes']))
    $_SESSION['show_indexes'] = $settings['show_indexes'];
  $show_results = false;
  /* Changing the Database */
  if($_REQUEST['act']=='db')
    $_SESSION['db'] = $_REQUEST['database'];
  /* The $settings['db'] var is not an array, so they only want to edit 1 db */
  if(count($settings['db'])==1 || !is_array($settings['db'])) {
    if(!is_array($settings['db']))
      $_SESSION['db'] = $settings['db'];
    else
      $_SESSION['db'] = $settings['db'][0];
  }
  // Just incase (I don't know why) we shouldn't connect to the database until we are logged in
  $con = @sqlite_open($_SESSION['db'], $mode, $con_error);
  /* Some other things */
  if(!empty($_POST['vacuum'])) {
    // VACUUM the SQLite Database, kind of like MySQL OPTIMIZE
    sqlite_query($con, 'VACUUM', $result_type, $query_error);
    if(empty($query_error))
      $msg = '<p>VACUUM Done Successfully!</p>';
    else
      $msg = '<p class="error">An Error has occurred while doing VACUUM, Error Message: '. $query_error. '</p>';
  }
  if(!empty($_POST['empty'])) {
    // They want to empty (a) table(s) 
    foreach($_POST['tbl'] as $tbl_name)
      @sqlite_query($con, "DELETE FROM {$tbl_name}");
    $msg = '<p>Emptied the tables '. implode(", ", $_POST['tbl']). ' successfully</p>';
  }
  if(!empty($_POST['show_indexes'])) {
    // Changes whether or not we show indexes ;)
    if(!$_SESSION['show_indexes'])
      $_SESSION['show_indexes'] = true;
    else
      $_SESSION['show_indexes'] = false;
  }
  /* Processing Queries are we? */
  if($_REQUEST['act']=='query') {
    $query = stripslashes($_REQUEST['q']);
    $q_result = @sqlite_exec($con, $query, $query_error);
    if(preg_match('/^drop|delete|insert|replace|update|create|reindex/i', $query) && !empty($query_error)) {
      $msg = '<p class="error">Error: '. $query_error. '</p>';
    }
    elseif(empty($query_error) && !preg_match("/^select|pragma|explain/i", $query)) {  
      $msg = '<p>Done!</p>';
    }
    elseif(preg_match('/^select|pragma|explain/i', $query)) {
      if(empty($query_error))
        $msg = 'Results Displayed';
      else
        $msg = '<p class="error">Error: '. $query_error. '</p>';
      $show_results = true;
    }
    else {
      $msg = '<p class="error">Error: '.$query_error.'</p>';
    }
  }
  /* This imports the file you have selected */
  if(!empty($_REQUEST['import'])) {
    // Get the extension =O
    $name = $_FILES['sqlite_file']['name'];
    if(substr_count($name, ".")>0) {
      $name = explode(".", $name);
      $type = strtolower($name[count($name)-1]);
      // Is it an SQL or GZipped file?
      if($type=='sql') {
        $query = file_get_contents($_FILES['sqlite_file']['tmp_name']);
        @unlink($_FILES['sqlite_file']['tmp_name']);
        @sqlite_exec($con, $query, $import_error);
        if(empty($import_error)) {
          $msg = '<p>Import Successful!</p>';
        }
        else {
          $msg = '<p class="error">Import Error: '. $import_error. '<br />If you have a backup of your database with DEBUG MODE, try that backup instead</p>';
        }      
      }
      elseif($type=='gz') {
        $content = gzfile($_FILES['sqlite_file']['tmp_name']);
        $query = '';
        foreach($content as $line)
          $query .= $line;
        @unlink($_FILES['sqlite_file']['tmp_name']);
        @sqlite_exec($con, $query, $import_error);
        if(empty($import_error)) {
          $msg = '<p>Import Successful!</p>';
        }
        else {
          $msg = '<p class="error">Import Error: '. $import_error. '<br />If you have a backup of your database with DEBUG MODE, try that backup instead</p>';
        }  
      }
      else {
        $msg = '<p class="error">File Error! Could not determine file type.</p>';
      }
    }
    else {
      $msg = '<p class="error">File Error! Could not determine file type.</p>';
    }
  }
  /* Drop the selected tables! */
  if(!empty($_REQUEST['drop_tables'])) {
    foreach($_REQUEST['tbl'] as $tbl_name) {
      $tbl_name = sqlite_escape_string($tbl_name);
      // We need to see if this is an index >.<
      $result = sqlite_query($con, "SELECT * FROM sqlite_master WHERE name = '$tbl_name'");
      if(sqlite_num_rows($result)) {
        $row = sqlite_fetch_array($result, SQLITE_ASSOC);
        if($row['type'] == 'table')
          sqlite_query($con, "DROP TABLE $tbl_name");
        else
          sqlite_query($con, "DROP INDEX $tbl_name");
      }
    }
    $msg = '<p>The tables '. implode(", ", $_REQUEST['tbl']). ' have been dropped</p>';
  }
  if(!empty($_POST['save_rows'])) {
    // Saving rows from a row edit..?
    // Make the Queries...
    $num_queries = count($_POST['orig_value']);
    $queries = array();
    $tbl_name = $_POST['tbl_name'];
    $unique_field = $_POST['unique_field'];
    // Get all the columns...
    $cols = array_values($_POST['col']);
    for($i = 0; $i < $num_queries; $i++) {
      $set_data = array();
      // Go through all the columns...
      foreach($cols as $col) {
        $set_data[] = "'{$col}' = '{$_POST[$col][$i]}'";
      }
      $set_data = implode(', ', $set_data);
      $queries[] = "UPDATE '{$tbl_name}' SET {$set_data} WHERE {$unique_field} = {$_POST['orig_value'][$i]}";
    }
    $errors = array();
    foreach($queries as $query) {
      @sqlite_query($con, $query, SQLITE_ASSOC, $error_msg);
      if(!empty($error_msg))
        $errors[] = $error_msg;
    }
    if(count($errors)) {
      $msg = '<p class="error">The following errors occurred: '. implode(', ', $errors). '</p>';
    }
    else {
      $msg = '<p>Rows updated successfully!</p>';
    }
  }
}

// Okay, we got the simple stuff done, now onto the rest :P
  // Are they logged in? 
  if($is_logged && !$settings['lock_down']) {
    // Okay, what do they want to do though? o-O
    if($_REQUEST['act'] == 'export') {
      if(empty($_REQUEST['sa']))
        print_export();
      else
        do_export();
    }
    elseif($_REQUEST['act'] == 'import') {
      print_import();
    }
    elseif($_REQUEST['act'] == 'sct') {
      print_sct();
    }
    elseif($_REQUEST['act'] == 'help') {
      faq();
    }
    elseif($_REQUEST['act'] == 'insert') {
      print_insert();
    }
    elseif($_REQUEST['act'] == 'edit_rows') {
      print_edit();
    }
    elseif($_REQUEST['act'] == 'edit_rows2') {
      print_edit_save();
    }
    elseif($_REQUEST['act'] == 'server_info') {
      print_server_info();
    }
    else {
      // Nothing...
      print_main();
    }
  }
  elseif($settings['lock_down']) {
    // Sorry buddy, you can't even TRY!
    print_lockdown();
  }
  else {
    // They are not logged in, login form =D
    print_login();
  }


/* This is where the real coding is done! Function Time! */

/* The login screen, the actual login processing is above */
function print_login() {
global $settings;  
  template_header('Login');
  echo '
  <br /><br /><br />
  <div id="login_area">
    <h1>Login</h1>
    <div align="center">
      <form action="', $_SERVER['PHP_SELF'], '" method="post">
        ', $settings['error_msg'] ? '<p class="error">'. $settings['error_msg']. '</p>' : '', '
        <legend for="user">Username:</legend>
          <input name="user" id="user" type="text"/>
        <br /><br />
        <legend for="pass">Password:</legend>
          <input name="pass" id="pass" type="password"/>
        <br />
        <legend for="r_me">Remember for 30 days</legend><input name="r_me" type="checkbox" value="1" checked="checked"/>
        <br />
        <input name="login" type="submit" value="Login"/>
      </form>
    </div>
  </div>
  ';
  template_footer();
}

/* This Function Prints the Main Page, shows the Database Connection Error or Lists the Tables */
function print_main() {
global $con, $con_error, $settings, $show_results;  
  if(!empty($con_error) || $_SESSION['db']==null)
    template_header('Connection Error', false);
  else
    template_header();
  // Is the Error Message Empty? o-O
  if(!empty($con_error) && $_SESSION['db']!=null) {
    // It isnt!
    echo '
    <br /><br /><br />
    <div id="con_error">
      <p>Database Connection Error! Error Message: ', $con_error, '</p>
    </div>';
  }
  elseif(!$show_results && $_SESSION['db']!=null) {
    // Phew, it is! No Error! Yay!
    list_tables();
  }
  /* Right now no database has been selected, tell them to select one :P */
  elseif($_SESSION['db']==null) {
    echo '
    <br /><br /><br />
    <div id="con_error">
      <p>Please Select a database to edit in the drop down, and hit Edit</p>
    </div>';
  }
  else {
    show_select();
  }
  template_footer();
}

/* list_tables(); will list all tables in the Selected SQLite Database */
function list_tables() {
global $con, $settings, $msg;
  // Okay, sqlite_master is the master SQLite table, you cannot see it (I think :P), or write or delete from it
  $result = sqlite_query($con, "SELECT * FROM sqlite_master ORDER BY tbl_name ASC", $result_type, $master_error);
  if(!empty($master_error)) {
    // An Error? Oh Noes!
    echo '
    <div id="con_error">
      <p>An Error has occurred while reading from the SQLite Master Table!<br />Error Message: ', $master_error, '</p>
    </div>';
  }
  else { 
    echo '
  <div id="info_center">';
    // Hmmm, what should we show? :o
    if(empty($msg)) {
      echo '<p>Database Size: ', format_size($_SESSION['db']), '</p>';
    }
    else {
      echo $msg;
    }
  /* Show some actions they can do, like VACUUM, Empty, etc. and other stuff */
  echo '
  </div>
  <form action="', $_SERVER['PHP_SELF'], '" name="tbl_list" method="post">
    <table>
      <tr>
        <td>
          <input name="vacuum" type="submit" title="Execute VACUUM (Like Optimize)" value="Vacuum"/>
        </td>
        <td>
          <input onClick="return confirm(\'Are you sure you want to empty the tables? It cannot be undone!\');" name="empty" type="submit" title="Empty the selected Tables" value="Empty"/>
        </td>
        <td>
          <input onClick="return confirm(\'Are you sure you want to drop the selected tables? All Data will be lost forever!\');" name="drop_tables" type="submit" title="Drop the selected tables" value="Drop"/>
        </td>
        <td>
          <input name="show_indexes" type="submit" value="', (isset($_SESSION['show_indexes']) && $_SESSION['show_indexes']) ? 'Don\'t show Indexes' : 'Show Indexes', '"/>
        </td>
      </tr>
    </table>
    <table cellspacing="0px" cellpadding="0px">
      <tr>
        <th><input name="select_all" type="checkbox" onClick="select_tables(this.form);"/></th><th>Table Name</th><th>&nbsp;</th><th>&nbsp;</th><th>Records</th><th>Type</th>
      </tr>';
    // Show the tables :D  
      while($row = sqlite_fetch_array($result)) {
        if(showTable($row['type'])) {
          $i++;
          echo '
          <tr class="', is_odd($i) ? 'tr_1' : 'tr_2', '">
            <td><input name="tbl[]" type="checkbox" value="', $row['name'], '"/></td><td><a href="', $_SERVER['PHP_SELF'], '?act=query&q=SELECT+*+FROM+', $row['tbl_name'], '">', $row['name'], '</a></td><td>[<a href="', $_SERVER['PHP_SELF'], '?act=sct&tbl=', $row['name'], '" title="Show Create Table for ', $row['name'], '">SCT</a>]</td><td class="center">[<a href="', $_SERVER['PHP_SELF'], '?act=insert&tbl=', $row['tbl_name'], '" title="Insert a row into ', $row['tbl_name'], '">Insert</a>]<td class="center">', num_recs($row['name'], $row['type']), '</td><td class="center">', $row['type'], '</td>
          </tr>';
        }
      }
    /* Show some actions they can do, like VACUUM, Empty, etc. */
    echo '
    </table>
    <table>
      <tr>
        <td>
          <input name="vacuum" type="submit" title="Execute VACUUM (Like Optimize)" value="Vacuum"/>
        </td>
        <td>
          <input onClick="return confirm(\'Are you sure you want to empty the tables? It cannot be undone!\');" name="empty" type="submit" title="Empty the selected Tables" value="Empty"/>
        </td>
        <td>
          <input onClick="return confirm(\'Are you sure you want to drop the selected tables? All Data will be lost forever!\');" name="drop_tables" type="submit" title="Drop the selected tables" value="Drop"/>
        </td>
        <td>
          <input name="show_indexes" type="submit" value="', (isset($_SESSION['show_indexes']) && $_SESSION['show_indexes']) ? 'Don\'t show Indexes' : 'Show Indexes', '"/>
        </td>  
      </tr>
    </table>    
  </form>';
  }
}

/* Returns the number of records in a table */
function num_recs($tbl, $type) {
global $con;
  // Is it an index? Ignore if so
  if($type == 'table') {
    $result = @sqlite_query($con, "SELECT * FROM {$tbl}");
    return sqlite_num_rows($result);
  }
  else
    return '--';
}

/* Tells if a number is odd or even, used for alternating table backgrounds */
function is_odd($number) {
   return $number&1;
}

/* 
  A more readable file size
  I did not make this function:
  http://us2.php.net/manual/en/function.filesize.php#84034 
*/
function format_size($file) {
  $size = @filesize($file);
  $sizes = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
  $total = count($sizes);
  for($i=0; $size > 1024 && $i < $total; $i++) 
    $size /= 1024;
  return round($size,2).$sizes[$i];
}

/* The Function for exporting your SQLite Database */
function print_export() {
global $con, $settings;
  template_header('Export Database', false);
  echo '
  <br /><br /><br />
  <div id="export">
    <h1>Export Database</h1>
      <form action="" method="post">
        <table align="center">
          <tr>
            <td><label for="struc">Export with Structure</label> [<a href="javascript:void(0);" onClick="return faq(\'',$_SERVER['PHP_SELF'], '?act=help&faq=export_struc\');">?</a>]</td><td><input id="struc" name="struc" id="struc" type="checkbox" checked="checked" value="1"/></td>
          </tr>
          <tr>
            <td><label for="data">Export with Data</label> [<a href="javascript:void(0);" onClick="return faq(\'',$_SERVER['PHP_SELF'], '?act=help&faq=export_data\');">?</a>]</td><td><input id="data" name="data" id="data" type="checkbox" checked="checked" value="1"/></td>
          </tr>
          <tr>
            <td><label for="drop">Add DROP TABLE</label> [<a href="javascript:void(0);" onClick="return faq(\'',$_SERVER['PHP_SELF'], '?act=help&faq=drop_table\');">?</a>]</td><td><input id="drop" name="drop" type="checkbox" value="1"/></td>
          </tr>
          <tr>
            <td><label for="debug_mode">Debug Mode</label> [<a href="javascript:void(0);" onClick="return faq(\'',$_SERVER['PHP_SELF'], '?act=help&faq=debug_mode\');">?</a>]</td><td><input id="debug_mode" name="debug_mode" type="checkbox" checked="checked"/></td>
          </tr>          
          <tr>
            <td><label for="transaction">Add TRANSACTIONs</label> [<a href="javascript:void(0);" onClick="return faq(\'', $_SERVE['PHP_SELF'], '?act=help&faq=transaction\');">?</a>]</td><td><input id="transaction" name="transaction" type="checkbox" checked="checked"/></td>
          </tr>
          <tr>
            <td colspan="2">Export as:</td>
          </tr>
          <tr>
            <td><input name="type" value="sql" id="sql" type="radio" checked="checked"/> <label for="sql">SQL</label></td><td><input name="type" id="gz" value="gz" type="radio"/> <label for="gz">GZipped</label></td>
          </tr>
          <tr align="center">
            <td colspan="2"><input type="button" onClick="window.location=\'', $_SERVER['PHP_SELF'], '\'" value="Cancel"/>&nbsp;&nbsp;&nbsp;<input name="export" type="submit" value="Download"/></td>
          </tr>
        </table>
        <input name="sa" type="hidden" value="export"/>
      </form>
  </div>';
  template_footer();
}

/* do_export(); exports the SQLite Database with the options you set */
function do_export() {
global $con, $settings;
  $result = @sqlite_query($con, "SELECT * FROM sqlite_master");
  $db = $_SESSION['db'];
  // Get the /'s out of the name ;] (if need be)
  if(substr_count($db, "/")) {
    $tmp = explode("/", $db);
    $db = $tmp[count($tmp)-1];
  }
  /* Get the . out of the name (the last one at least which is the database extension (which we do not need) */
  if(substr_count($db, ".")>0) {
    $tmp = explode(".", $db);
    if(count($tmp)>2) {
      $db_name = '';
      for($i = 0; $i <= (count($tmp)-1); $i++) {
        $db_name .= $tmp[$i].'.';
      }
    }
    else
      $db_name = $tmp[0];
  }   
  $export = "/*\n  phpLiterAdmin Dump (http://nosql.110mb.com/)\n  phpLiterAdmin Version: {$settings['version']}\n  Export Date: ".date("m/d/Y")." \n  Database File: {$db}\n*/ \n\n";;
  while($row = sqlite_fetch_array($result)) {
    $export .= show_create($row);
    if($row['type']=='table')  
      $export .= insert_data($row['tbl_name']);
  }  
  /* The headers to properly download the SQLite Data Dump */
  $mime = 'text/sql';
  $ext = '.sql';
  if($_REQUEST['type']=='gz') {
    $export = gzencode($export);
    $mime = 'application/x-gzip';
    $ext = '.sql.gz';
  }
  header('Pragma: public');  
  header('Expires: 0'); 
  header('Cache-Control: must-revalidate, post-check=0, pre-check=0'); 
  header('Cache-Control: private',false); // required for certain browsers 
  header('Content-Transfer-Encoding: binary'); 
  header("Content-Type: $mime"); 
  header("Content-Disposition: attachment; filename=\"". $db_name. "_". date("m_d_Y"). $ext. "\";" ); 
    echo $export;
  exit;
}
/* The below function aid do_export(); */
function show_create($row) {
global $con;
  $return = '';
  /* Do they even want the DROP TABLE {tbl_name}? */
  if(!empty($_REQUEST['drop'])) {
    $return .= "----\n-- Drop table {$row['tbl_name']}\n----\n\n";
    $return .= "DROP TABLE ". $row['tbl_name']. "; \n\n";
  }
  /* Do they want the Table Structure? (CREATE TABLE/INDEX/UNIQUE etc) */
  if(!empty($_REQUEST['struc'])) {
    $return .= "----\n-- Table Structure for {$row['tbl_name']}\n----\n\n";
    $return .= $row['sql']."; \n\n";
  }
  return $return;
}
function insert_data($tbl_name) {
global $con;
  if(!empty($_REQUEST['data'])) {
    /* Select the data from the table */    
    $result = sqlite_query($con, "SELECT * FROM {$tbl_name}");
    if(sqlite_num_rows($result)>0) {
      /* Comments that might be somewhat useful */
      $return = "----\n-- Data Dump for {$tbl_name}\n----\n\n";
      /* Begin Transaction! (If option is chosen) */
      if(!empty($_REQUEST['transaction']))
        $return .= "BEGIN TRANSACTION;\n";      
      $insert = array();
      while($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
        $query = array();
        foreach($row as $value) {
          if(!empty($_REQUEST['debug_mode']))
            $query[] = sqlite_escape_string($value);
          else
            $query[] = $value;
        }
        $query = "'". implode("','", $query). "'";
        $insert = "({$query})";
        $return .= "INSERT INTO {$tbl_name} VALUES{$insert}; \n";
      }
      /* End the Transaction (If option is chosen) */
      if(!empty($_REQUEST['transaction']))  
        $return .= "COMMIT;\n\n";
    }
  }
  return $return;
} 
/* End Functions that aid do_export(); */

/* Shows the results of the SQLite SELECT Query... */
function show_select() {
global $con;
  /* 
    The Query into the database
  */
  $query = $_REQUEST['q'];
  $result = @sqlite_query($con, $query, $result_type, $select_error);
  $is_join = is_join($query);
  if(empty($select_error)) {
    echo '
  <form action="', $_SERVER['PHP_SELF'], '?act=edit_rows" method="post">  
    <table cellspacing="1px" cellpadding="0px" width="100%">
      <tr>';
    if(!$is_join)
      echo '
      <th>&nbsp;</th>';
    /* Show the Column Names */
    for($i = 0; $i <= (sqlite_num_fields($result)-1); $i++) {
      if(!$is_join)
        echo '<th><input title="Choose this column as Unique Identifier" id="id_', $i, '" name="unique_id" type="radio" value="', $i, '"/><label for="id_', $i, '" title="Choose this column as Unique Identifier">', sqlite_field_name($result, $i), '</label></th>';
      else
        echo '<th>', sqlite_field_name($result, $i), '</th>';
    }
    echo '</tr>';
    /* Now show the values (if any) of the rows, also htmlentities() and shorten the row values */
    $i = 0;
    while($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
      echo '
      <tr class="', is_odd($i) ? 'tr_1' : 'tr_2', '">
        ', !$is_join ? '<td align="center"><input id="edit_'. $i. '" name="edit['. $i. ']" type="checkbox" value="1" title="Edit this Row"/></td>' : '';
      foreach($row as $key => $value) {
        $value = htmlentities(strchop($value));
        echo '
        <td ', is_numeric($value) ? 'class="center"' : '', '><label for="edit_', $i, '" style="display: block; width: 100%;">', $value, '</label></td>';
      }
      echo '
      </tr>';
      $i++;
    }
    echo '
      ', !$is_join ? '<tr>
        <td><input name="edit_rows" onClick="return check_unique();" type="submit" value="Edit Rows"/>
      </tr>' : '', '
    </table>
    <input name="query" type="hidden" value="', htmlspecialchars($_REQUEST['q'], ENT_QUOTES), '"/>
  </form>';
  }
  else {
    // An Error! D:
    echo '
    <div id="info_center">
      <p class="error">Error: ', $select_error, '</p>
    </div>';
  }
}

/* Shortens a string to the specified length */
function strchop($str, $max = 50) {
  /* This Function is used to shorten a row's value so it won't take forever to load */
  $rep = '...';
  if(strlen($str) > $max) {
    $leave = $max - strlen($rep);
    return substr_replace($str, $rep, $leave);
  }
  else {
    return $str;
  }
}

/* Figures whether or not a query has a JOIN in it or not ;) */
function is_join($query) {
  // Make sure its not a PRAGMA :P
  $query = trim($query);
  if(!preg_match('/^pragma/i', $query)) {
    if(preg_match('/(LEFT |RIGHT )?JOIN/is', $query)) {
      return true;
    }
    else
      return false;
  }
  else
    return true;
}

/* The Import Function! */
function print_import() {
global $con;
  template_header('Import Database', false);
  echo '
  <br /><br /><br />
  <div id="import">
    <h1>Import Database</h1>
    <form action="', $_SERVER['PHP_SELF'], '" enctype="multipart/form-data" method="post">
      <table align="center">
        <tr>
          <td>Database File:</td><td><input name="sqlite_file" type="file"/></td>
        </tr>    
        <tr>
          <td colspan="2" style="text-align: center;">.gz and .sql files only</td>
        </tr>    
        <tr align="center">
          <td colspan="2"><input type="button" onClick="window.location=\'', $_SERVER['PHP_SELF'], '\'" value="Cancel"/>&nbsp;&nbsp;&nbsp;<input onClick="return confirm(\'Are you sure?\');" name="import" type="submit" value="Upload &amp; Import"/>
        </tr>
      </table>
    </form>
  </div>';
  template_footer();
}

/* You can't even try to access this! =D LOCK DOWN! */
function print_lockdown() {
  template_header('Locked Down');
  echo '
  <br /><br /><br />
  <div id="lockdown">
    <h1>Locked Down</h1>
      <p>Sorry! You can\'t even try to login! This has been locked down, if you have file access, open up the file and change the $settings[\'lock_down\'] variable to 0, and refresh</p>
  </div>';
  template_footer();
}

/* Show Create Table MySQL Equal =D */
function print_sct() {
global $con, $settings;
  template_header('Show Create Table for ', $_REQUEST['tbl']);
  $tbl_name = sqlite_escape_string($_REQUEST['tbl']);
  $result = sqlite_query($con, "SELECT sql FROM sqlite_master WHERE name = '{$tbl_name}'");
  if(sqlite_num_rows($result)>0) {
    $create_table = sqlite_fetch_single($result);
    echo '
    <table width="100%">
      <tr>
        <th>Table</th><th>Create Table</th>
      </tr>
      <tr class="tr_1">
        <td>', $tbl_name, '</td><td><pre>', $create_table, '</td>
      </tr>
    </table>';
  }
  else
    echo '
    <div id="info_center">
      <p class="error">The table ', $tbl_name, ' does not exist!</p>
    </div>';
  template_footer();
}

/* Allows you to insert a row into a selected table ;) */
function print_insert() {
global $con, $settings;
  $tbl_name = sqlite_escape_string($_REQUEST['tbl']);
  // So does this table exist or not?
  $result = sqlite_query($con, "SELECT * FROM sqlite_master WHERE tbl_name = '$tbl_name'");
  if(sqlite_num_rows($result)) {
    template_header('Insert a row', false);
    echo '
    <div id="insert">
      <h3>Insert a row</h3>
      <p>Here you can insert a row into the table ', htmlspecialchars($_REQUEST['tbl'], ENT_QUOTES), '</p>';
      
      // We need to get all the types :P
      $types = sqlite_fetch_column_types($tbl_name, $con, SQLITE_ASSOC);
            
      // We inserting..?
      if(!empty($_REQUEST['insert_row'])) {
        $values = array();
        // Now get out the data :P
        foreach($types as $col => $type) {
          // Get the value ;)
          $value = !empty($_REQUEST[$col]) ? $_REQUEST[$col] : '';
          // We need to do anything fancy to the string..?
          $func = $_REQUEST['functionStr'][$col];
          $func = trim($func);
          if(!empty($func)) {
            if($func != 'time')
              $value = $func($value);
            else
              $value = time();
          }
          $values[] = $value;
        }
        // Now implode! >:D
        $insert_values = '(\''. implode("','", $values). '\')';
        @sqlite_query($con, "INSERT INTO $tbl_name VALUES{$insert_values}", SQLITE_ASSOC, $error_msg);
        if(!empty($error_msg)) {
          echo '<p class="error">', $error_msg, '</p>';
        }
        else {
          echo '<p style="color: green; text-align: center;">Row Inserted Successfully!</p>';
          unset($_REQUEST);
        }
      }
      
      echo '
      <form action="" method="post">
        <table width="90%" align="center">
          <tr>
            <th>Column Name</th><th>Data Type</th><th>Function*</th><th>Value to Insert</th>
          </tr>';
        foreach($types as $colName => $dataType) {
          echo '
          <tr>
            <td class="insert">', $colName, '</td><td class="insert">', strtoupper($dataType), '</td><td align="center">', buildFunctionList($colName), '</td><td align="center">', showInput($dataType, $colName), '</td>
          </tr>';
        }
      echo '
          <tr>
            <td colspan="4" align="right"><input name="insert_row" type="submit" value="Insert Row"/></td>
          </tr>
        </table>
      </form>
      <p class="lil_msg">
        * When you choose a function, before the data is inserted, the function is called upon with the value as the parameter<br />
        ** When you use this function, the value you entered is replaced with what this function returns
      </p>
    </div>';
    template_footer();
  }
  else {
    template_header('Insert row Error!', false);
    echo '
    <div id="insert">
      <p class="error">Error! The table you have requested to insert a row for does not exist!</p>
    </div>';
    template_footer();
  }
}

/* Builds a list of functions that can be done to the data to be inserted */
function buildFunctionList($colName = '') {
  return '
    <select name="functionStr['.$colName.']">
      <option value=""></option>
      <option value="sqlite_escape_string">sqlite_escape_string</option>
      <option value="htmlentities">htmlentities</option>
      <option value="htmlspecialchars">htmlspecialchars</option>
      <option value="base64_encode">base64_encode</option>
      <option value="md5">md5</option>
      <option value="sha1">sha1</option>
      <option value="trim">trim</option>
      <option value="addslashes">addslashes</option>
      <option value="stripslashes">stripslashes</option>
      <option value="strtolower">strtolower</option>
      <option value="strtoupper">strtoupper</option>
      <option value="time">time**</option>
    </select>';
}

/* Returns the right kind of input for the type of data ;) */
function showInput($type, $name, $value = '') {
  $value = !empty($_REQUEST[$name]) ? $_REQUEST[$name] : $value;
  $type = strtoupper($type);
  // Remove the ( and )
  if(substr_count($type, '(')) {
    $length = substr($type, strpos($type, '(') + 1, strlen($type));
    $length = substr($length, 0, strlen($length) - 1);
    $type = substr($type, 0, strpos($type, '('));
  }
  // Now we have the real data type :P
  // TEXT, BLOB and CLOB are textarea's,
  // rest are regular inputs :P
  $textareas = array('TEXT','CLOB','BLOB');
  if(in_array($type, $textareas)) {
    return '<textarea name="'. $name. '" cols="17" rows="5">'.$value.'</textarea>';
  }
  else
    return '<input name="'. $name. '" type="text" value="'.$value.'"/>';
}

/* Allows you to edit rows :) yay */
function print_edit() {
global $con, $settings;
  // Get the Unique Identifier
  $unique_id_key = (int)$_POST['unique_id'];
  // The query
  $query = htmlspecialchars_decode($_POST['query'], ENT_QUOTES);
  // Which rows we gonna edit?
  $edit_rows = $_POST['edit'];
  // Before we go any farther, make sure this isn't
  // a join query, those are out of phpLiterAdmins
  // editing league :P
  if(!is_join($query)) {
    // Extract the table from the query
    $tbl_name = get_table($query);
    # tbl_name empty..? We cannot proceed :X
    if(empty($tbl_name)) {
      template_header('Edit Rows Error!', false);
      echo '
      <div id="edit_rows">
        <p class="error">Sorry, we could not extract the table name from the query</p>
      </div>';
      template_footer();
    }
    else {
      template_header('Edit Rows', false);
      // Query the old query :P
      $result = sqlite_query($con, $query, SQLITE_ASSOC);
      // Get all them results :P!
      $results = array();
      while($row = sqlite_fetch_array($result, SQLITE_ASSOC)) {
        $results[] = $row;
      }
      // Data Types plz :D
      $datatypes = sqlite_fetch_column_types($tbl_name, $con, SQLITE_ASSOC);
      // Get the unique field name ;)
      $unique_field = sqlite_field_name($result, $unique_id_key);
      echo '
      <div id="edit_rows">
        <h3>Edit Rows</h3>
        <p>You are currently editing ', count($edit_rows), ' row(s) from the table ', $tbl_name, '. <br />The Unique Identifier column is <strong>', $unique_field, '</strong></p>
        <form action="', $_SERVER['PHP_SELF'], '" method="post">
          <table align="center" width="95%">';
          // Only the rows we need :P
          $i = 0;
          foreach($edit_rows as $row_key => $crap) {
            echo '
            <tr>
              <th>Field Name</th><th>Data Type</th><th>Function</th><th>Value</th>
            </tr>';
            $keys = array_keys($results[$row_key]);
            $values = array_values($results[$row_key]);
            foreach($keys as $key) {
              echo '
            <tr>
              <td class="insert">', ($key == $unique_field) ? '<em>'. $key. '</em>' : $key, '</td><td class="insert">', strtoupper($datatypes[$key]), '</td><td align="center">', buildFunctionList(), '</td><td align="center">', showInput($datatypes[$key], $key.'['.$i.']', $results[$row_key][$key]), '</td>
            </tr>';
            }
            // Gotta keep the original value :P!
            echo '
            <input name="orig_value[', $i, ']" type="hidden" value="', htmlspecialchars($values[$unique_id_key], ENT_QUOTES), '"/>';
            $i++;
          }
          // *Needs a column list :P*
          for($i = 0; $i < sqlite_num_fields($result); $i++)
            echo '
            <input name="col[]" type="hidden" value="', sqlite_field_name($result, $i), '"/>';
        echo '
            <tr>
              <td colspan="4" align="right"><input name="save_rows" type="submit" value="Save Rows"/></td>
            </tr>
          </table>
          <input name="tbl_name" type="hidden" value="', $tbl_name, '"/>
          <input name="unique_key_id" type="hidden" value="', $unique_key_id, '"/>
          <input name="unique_field" type="hidden" value="', $unique_field, '"/>
        </form>
      </div>';
      template_footer();
    }
  }
  else {
    template_header('Edit Rows Error!', false);
    echo '
    <div id="edit_rows">
      <p class="error">Sorry! But phpLiterAdmin cannot edit rows that come from a JOIN Query</p>
    </div>';
  }
}

/* Extracts the table from the query :) */
function get_table($query) {
  // Regex O.o
  preg_match('/ FROM (.*?)( AS (.*))? WHERE/is', $query, $matches);
  // Since I am bad at regex, check if $matches[1] is nothing
  // if it is, we might have done something wrong :P
  // so lets add WHERE to the end ^^
  if(empty($matches[1]))
    preg_match('/ FROM (.*?) (WHERE|AS|LIMIT|GROUP|ORDER)/is', $query.' WHERE', $matches);
  return $matches[1];
}

/* Generates the SQLite Database List, if there is only 1 DB, it doesn't show it */
function db_list() {
global $is_logged, $settings;
  // First off, are they logged in?
  if($is_logged) {
    // K, they are, now, is $settings['db'] an array or not? They need to be treated differently
    if(is_array($settings['db'])) {
      // It is an array, but is there even more then 1 database?
      if(count($settings['db'])>1) {
        $db_list = array();
        foreach($settings['db'] as $db) {
          // Okay, now lets not show the whole path, just the DB
          if(substr_count($db, "/")>0) {
            /* Its got / in it, we don't want to show those or else the selection drop down might be HUGE! */
            $tmp = explode("/", $db);
            $db_name = $tmp[count($tmp)-1];
            $db_list[$db] = $db_name;
          }
          else {
            /* Its in this directory (or at least has no /) so do nothing to it */
            $db_list[$db] = $db;
          }
        }
        echo '<td style="color: #ffffff;">Database to Manage:</td>
              <form action="', $_SERVER['PHP_SELF'], '" method="post">
              <td><select name="database">';
          foreach($db_list as $path => $name) {
            echo '<option value="', $path, '"'; if($_SESSION['db']==$path) { echo ' selected="yes"'; } echo '>', $name, '</option>';
          }
        echo '</select></td><td><input name="go" type="submit" value="Edit"/><input name="act" type="hidden" value="db"/></td></form>';
      }
      // They did array('1_DB'); with only 1 DB in it, do nothing again...
    }
    // We don't show anything if it isn't an array, since we are only handling 1 DB
  }
}

/* 
  Simple function :P just returns true or false
  if the table type is allowed to be shown or not
*/
function showTable($type) {
  if($_SESSION['show_indexes'])
    return true;
  elseif(!$_SESSION['show_indexes'] && $type == 'table')
    return true;
  else
    return false;
}

/* FAQ Function which describes what something is for */
function faq() {
  $faq = $_REQUEST['faq'];
  faq_header();
  if($faq=='debug_mode') {
    echo '
    <h1>Debug Mode</h1>
    <p>Debug Mode may help when you go to import your SQLite Database, it will be less error prone. I recommend you download an backup with and without it.</p>';
  }
  elseif($faq=='drop_table') {
    echo '
    <h1>DROP TABLEs</h1>
    <p>If you choose this option, it will include DROP TABLE table; if the table does not exist upon import, you may get errors, though you can always delete these later</p>';
  }
  elseif($faq=='export_data') {
    echo '
    <h1>Export Data</h1>
    <p>Choosing this option when you export will include the actual data included in your tables, the INSERT SQLite Command in other words, this is the import stuff. <br />If not sure, leave this option checked</p>';
  }
  elseif($faq=='export_struc') {
    echo '
    <h1>Export Strcture</h1>
    <p>This will, upon exporting, contain the Structure of your tables, such as CREATE TABLE, CREATE INDEX, CREATE UNIQUE, etc.<br />If you are not sure, leave this option checked.</p>';
  }
  elseif($faq=='transaction') {
    echo '
    <h1>Add TRANSACTIONs</h1>
    <p>This is a COMMAND in almost all SQL (<a href="http://mysql.com/" target="_blank">MySQL</a>, <a href="http://www.microsoft.com/sql/" target="_blank">MSSQL</a> etc.) have. Putting it simply, it an All or Nothing option, if when Queries are being parsed, and 1 error happens, it is all undone!</p>';
  }
  elseif($faq=='query') {
    echo '
    <h1>Query Box</h1>
    <p>In this box you can run Queries against your SQLite Database, you can do multiple Queries be separating them by semicolons.<br /><br /><a href="http://nosql.110mb.com/page.php?p=2" target="_blank">Need a Tutorial?</a></p>';
  }
  faq_footer();
}

/* FAQ Layout */
function faq_header($title = '') {
echo '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>phpLiterAdmin FAQ</title>
	<meta name="robots" content="noindex"/>
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
h1 {
  color: #ffffff;
  background: #5E9FBF;
  padding: 5px;
  width: 100%;
  font-size: 14px;
}
p {
  padding: 5px;
  text-align: center;
}
</style>
</head>
<body>';
}
function faq_footer() {
echo '
  <p><a href="javascript:window.close();">Close</a></p>
</body>
</html>';
}

/* Shows the Server Info, like SQLite Version and what not */
function print_server_info() {
global $con, $settings;
  template_header('Server Info', false);
  echo '
  <div id="server_info">
    <h3>Server Info</h3>
    <table align="center">
      <tr align="center">
        <td class="var">SQLite Version</td><td>', sqlite_libversion(), '</td>
      </tr>
      <tr align="center">
        <td class="var">SQLite Encoding</td><td>', sqlite_libencoding(), '</td>
      </tr>
      <tr align="center">
        <td class="var">PHP Version</td><td>', PHP_VERSION, '</td>
      </tr>
      <tr align="center">
        <td class="var">PHP OS</td><td>', PHP_OS, '</td>
      </tr>
      <tr align="center">
        <td class="var">Last Error Code</td><td>', sqlite_last_error($con), '</td>
      </tr>
      <tr align="center">
        <td class="var">Last Error</td><td>', sqlite_error_string(sqlite_last_error($con)), '</td>
      </tr>
    </table>
  </div>';
  template_footer();
}

/* Layouts */
function template_header($title = '', $show_q = true) {
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
</style>
</head>
<body>
  <div id="header">
    <div id="left">
      <table>
        <tr>
          <td><p><a href="http://nosql.110mb.com/" class="bold">phpLiterAdmin v', $settings['version'], '</a></p></td>
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
        <textarea id="q_input" name="q" rows="10" cols="70">', @stripslashes($_REQUEST['q']), '</textarea>
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

/* The template footer =D */
function template_footer() {
global $settings, $start, $is_logged;
  echo '
  <br />
  <div id="powered_by">
    <p>Powered by phpLiterAdmin v', $settings['version'], ' by <a href="http://nosql.110mb.com/">NoSQL</a> | It took ', round(microtime(true) - $start, 5), ' seconds to make this page</p>
  </div>
  </div>
</body>
</html>';  
}

/* Fixes dumb magic quotes if need be :) */
function fixMagic() {
global $_GET, $_POST, $_COOKIE, $_REQUEST;
  if(get_magic_quotes_gpc()) {
    // Magic Wizard! Weee
    $_GET = wizardMagic($_GET);
    $_POST = wizardMagic($_POST);
    $_COOKIE = wizardMagic($_COOKIE);
    $_REQUEST = wizardMagic($_REQUEST);
  }
}

/* Assists fixMagic() :) */
function wizardMagic($array) {
  $new_array = array();
  foreach($array as $key => $value) {
    if(!is_array($value)) {
      $new_array[$key] = stripslashes($value);
    }
    else {
      $new_array[$key] = array();
      foreach($value as $key_2 => $value_2) {
        $new_array[$key][$key_2] = stripslashes($value_2);
      }
    }
  }
  return $new_array;
}

/* Scramble everything, just incase.. */
foreach($settings as $key => $value) {
  // The users array needs a little more attention
  if($key == 'users') {
    foreach($settings[$key] as $user => $pass) {
      $settings[$key][md5($user)] = md5($pass);
    }
  }
  else {
    // Is it an array? They need to be scrambled another way ;)
    if(!is_array($settings[$key]))
      $settings[$key] = md5($value);
    else
      foreach($settings[$key] as $sub => $v)
        $settings[$key][$sub] = md5($v);
  }
}
?>