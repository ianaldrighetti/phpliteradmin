<?php
#
#                phpLiterAdmin v1.0
#             http://nosql.110mb.com/
#       http://phpliteradmin.googlecode.com/
#
#   phpLiterAdmin is a SQLite Manage wihich works
#   with SQLite 2.x. It has not been tested with
#   SQLite v3 as I have yet to have any access to
#   an SQLite v3 installed server. I do plan though
#   to hopefully make phpLiterAdmin v3 compatible!
#
#   phpLiterAdmin is released under the GNU GPL v2
#   License. This script is provided "AS IS" with no
# warranty whatso ever. We (the phpLiterAdmin creators,
#  and everyone else  in the world) are not responsible
#  for what may occur with using this script, whether it
# be to your server, your host, your data, your databases,
# and so on and so forth, ITS NOT OUR RESPONSIBILITY AND
#               WE ARE NOT LIABLE! :)
#
# Installation:
# http://code.google.com/p/phpliteradmin/wiki/Installation
#

# We must start our session :)
session_start();

# Magic Quotes ON? Fix the dumb thing!
fixMagic();

if(function_exists('set_magic_quotes_runtime'))
  @set_magic_quotes_runtime(false);

# How long did this take? :P
$start_time = microtime(true);

# More information about each config var is available at
# the Google Code project wiki pages.
# http://code.google.com/p/phpliteradmin/wiki/Config_vars

# Now the settings for your phpLiterAdmin install.
#   Your users that can access your databases, add them
#   to the array in this setup:
#   'USERNAME' => 'PASSWORD',
#   Please note that when they have access, they will
#   have access to each database!
$config['users'] = array(
  'admin' => 'admin',
);

# Show indexes on the table list by default?
$config['show_indexes'] = false;

# Lock down this phpLiterAdmin install? 1 = yes, 0 = no
# Whats this do? It won't allow ANYONE no matter what to
# even attempt to login and access your SQLite databases.
# The only way to undo this is to change it to 0 :P
$config['lock_down'] = 0;

# The SQLite databases you wish to manage... be sure to include
# the correct path to them. Please note that if the database doesn't
# exist, it is automatically created, due to how sqlite_open works.
$config['db'] = array('db.db', 'settings.db');

# Cookie name for if someone wants to be remembered... Should
# be changed if you have multiple phpLiterAdmin's under the same
# domain...
$config['cookie_name'] = 'phpLiterAdmin432';

# Use a persistent handle to an SQLite database?
# This can speed up load times... but for more information
# checkout www.php.net/sqlite_popen if you set this to true
$config['persist'] = true;

# Allow people (Who are logged in!) to view the PHP Info...
# Which shows everything in phpinfo(); (1 = yes, 0 = no)
$config['phpinfo'] = 1;

# --------------------------------------------------
# You can edit the below, you know, since its released
# under the GNU GPL v2 License, but I wouldn't recommend
# it unless you really want to or you know what your doing :P
# --------------------------------------------------

# Are you logged in?
$config['is_logged'] = false;

# Maybe you are attempting to login? Fine.
# Also you can only do this if this isn't locked down!
if(!empty($_REQUEST['login']) && empty($config['lock_down']))
{
  # Get the username and password you submitted.
  $username = !empty($_REQUEST['user']) ? $_REQUEST['user'] : '';
  $password = !empty($_REQUEST['pass']) ? $_REQUEST['pass'] : '';
  $remember_me = !empty($_REQUEST['remember_me']);

  # Now check to see if this user with that password exists...
  if(!empty($config['users'][$username]) && $password == $config['users'][$username])
  {
    # Save your session information and what not.
    $_SESSION['username'] = $username;
    $_SESSION['password'] = $password;

    # Want to be remembered?
    if($remember_me)
      @setcookie($config['cookie_name'], serialize(array('username' => $username, 'password' => $password)), time() + 2592000);

    # And now you are logged in!
    $config['is_logged'] = true;
  }
  else
    # Sorry bub, wrong something!
    $config['error_msg'] = 'Access Denied';
}

# Session data empty? Maybe they have a cookie... How nice... mmmmm...
if(empty($_SESSION['username']) && empty($_SESSION['password']) && !empty($_COOKIE[$config['cookie_name']]) && empty($config['lock_down']))
{
  # Remember, our cookie is a serialized array.
  $data = unserialize($_COOKIE[$config['cookie_name']]);

  # Make sure nothing went wrong.
  if($data !== false && !empty($data['username']) && !empty($data['password']))
  {
    # Nope, save the session data!
    $_SESSION['username'] = $data['username'];
    $_SESSION['password'] = $data['password'];
  }
}

# Check if they are logged in...
if(!empty($_SESSION['username']) && !empty($_SESSION['password']) && !empty($config['users'][$_SESSION['username']]) && $_SESSION['password'] == $config['users'][$_SESSION['username']] && empty($config['lock_down']))
  # Yup, your logged in!
  $config['is_logged'] = true;

# Don't edit this please :)
$config['version'] = '1.0 Beta 3';

# Logging out?
if($config['is_logged'] && !empty($_GET['act']) && $_GET['act'] == 'logout')
{
  # Log them out :D
  session_destroy();
  @setcookie($config['cookie_name'], '', time() - 3600);
  $config['is_logged'] = false;
}

# Now all the actions you can do like querying, importing, and what not.
if($config['is_logged'])
{
  # You can override this setting without editing :)
  # But did you/have you?
  if(!isset($_SESSION['show_indexes']))
    $_SESSION['show_indexes'] = (bool)$config['show_indexes'];

  # We have nothing to show :P
  $config['show_results'] = false;

  # Changing the database? Better do it before we open the database.
  if(!empty($_REQUEST['act']) && $_REQUEST['act'] == 'db')
  {
    # Make sure you can select the database!
    if(in_array($_REQUEST['database'], $config['db']))
    {
      # Yup, you can touch it XD.
      $_SESSION['db'] = $_REQUEST['database'];
    }
    else
      $config['db_not_allowed'] = true;
  }

  # We should make sure you can access the database in your session
  # still... Someone might have changed it :P
  if(!@in_array($_SESSION['db'], $config['db']) && is_array($config['db']))
  {
    # Unset it...
    unset($_SESSION['db']);

    # If there is more then one database, show a message...
    # otherwise, why bother if we are going to choose one?
    if(count($config['db']) > 1)
      $config['db_not_allowed'] = true;
  }
  elseif($_SESSION['db'] != $config['db'] && !is_array($config['db']))
  {
    # Unset it... but don't say its not allowed ;)
    unset($_SESSION['db']);
  }

  # But wait... only one database? Select that!
  if(count($config['db']) == 1 || !is_array($config['db']))
  {
    if(!is_array($config['db']))
      $_SESSION['db'] = $config['db'];
    else
      $_SESSION['db'] = $config['db'][array_rand($config['db'])];
  }

  # Now open the database! :) Now we're cookin!
  if(empty($config['persist']))
    $config['con'] = @sqlite_open($_SESSION['db'], 0666, $config['con_error']);
  else
    $config['con'] = @sqlite_popen($_SESSION['db'], 0666, $config['con_error']);

  # Now that we are connected, you might be doing something fancy XD
  # VACUUMing up the place?
  if(!empty($_POST['vacuum']))
  {
    # VACUUM the database ;)
    sql_query('VACUUM', $query_error, $time_taken);

    if(empty($query_error))
      $config['msg'] = '<p>VACUUM done successfully! ('. round($time_taken, 5). ' seconds).</p>';
    else
      $config['msg'] = '<p class="error">An error occurred while doing VACUUM, Error: '. $query_error. '</p>';
  }
  # Deleting everything in a table? 0.0
  elseif(!empty($_POST['empty']) && count($_POST['tbl']))
  {
    # So they do... its in an array...
    $time_taken = 0;
    foreach($_POST['tbl'] as $tbl_name)
    {
      sql_query("DELETE FROM '{$tbl_name}' WHERE 1", $query_error, $single_time);

      # Add the time taken to the total.
      $time_taken += $single_time;
    }

    # Our pretty message :)
    $config['msg'] = '<p>Emptied the table'. (count($_POST['tbl']) > 1 ? 's' : ''). ' '. implode(', ', $_POST['tbl']). ' successfully. ('. round($time_taken, 5). ' seconds)</p>';
  }
  # Changing whether you see indexes?
  elseif(!empty($_POST['show_indexes']))
  {
    # Reverse it!
    $_SESSION['show_indexes'] = empty($_SESSION['show_indexes']) ? true : false;
  }
  # Querying the database?
  elseif(!empty($_REQUEST['act']) && $_REQUEST['act'] == 'query')
  {
    # Get our query...
    $query = ltrim($_REQUEST['q']);

    # Comment maybe..?
    if(substr($query, 0, 2) == '--')
    {
      # So get the comment and then the actual query ;)
      @list($comment, $query) = explode("\n", $query, 2);

      if(empty($query))
        # Just incase ;)
        $query = '';

      # Trim it up...
      $query = trim($query);

      # A new feature is that you can control a feature from a comment.
      # I would like to have more, but idk what else XD.
      $command = trim(substr($comment, 2, strlen($comment)));

      # If the first character is a ! we take it as a command.
      if(substr($command, 0, 1) == '!')
      {
        # Okay, now get the command XD.
        $command = trim(substr($command, 1, strlen($command)));
        if(preg_match('~^(no )?show full( )?texts?~i', $command, $matches))
        {
          # No..?
          if(strtolower(trim($matches['1'])) == 'no')
            $_REQUEST['fulltext'] = false;
          else
            $_REQUEST['fulltext'] = true;
        }
      }
    }

    # Run the query through the database...
    $start_time = microtime(true);
    $config['query_result'] = @sqlite_exec($config['con'], $query, $query_error);
    $time_taken = round(microtime(true) - $start_time, 5);

    # Lets see what kind of query it is ;)
    if(preg_match('~^(?:DROP|DELETE|INSERT|REPLACE|UPDATE|CREATE|REINDEX)~i', trim($query)))
    {
      # No errors! Good!
      if(empty($query_error))
        $config['msg'] = '<p>Query executed successfully. '. sqlite_changes($config['con']). ' rows affected, completed in '. $time_taken. ' seconds.</p>';
      else
        # Errors? D:!
        $config['msg'] = '<p class="error">Query Error: '. $query_error. '</p>';
    }
    elseif(preg_match('~^(?:SELECT|PRAGMA|EXPLAIN)~i', trim($query)))
    {
      if(empty($query_error))
      {
        $config['msg'] = 'Results Displayed. Query took '. $time_taken. ' seconds.</p>';

        # We do have results to display...
        $config['show_results'] = true;
      }
      else
        $config['msg'] = '<p class="error">Query Error: '. $query_error. '</p>';
    }
    elseif(!empty($query_error))
      $config['msg'] = '<p class="error">Query Error: '.$query_error.'</p>';
    else
      $config['msg'] = '<p>Query executed successfully.</p>';
  }
  # Importing a database file? Cool! :D
  elseif(!empty($_REQUEST['import']))
  {
    # We need to get the extension, we might need to extract it.
    $name = $_FILES['sqlite_file']['name'];

    if(strpos($name, '.') !== false)
    {
      $name = explode('.', $name);
      $type = strtolower($name[count($name) - 1]);

      # No message yet!
      $config['msg'] = null;

      # Regular SQL or Gzipped file?
      if($type == 'sql' || $type == 'db')
        # Ready the query... super easy with regular files XD
        $query = @file_get_contents($_FILES['sqlite_file']['tmp_name']);
      elseif($type == 'gz')
        # A little more to this... but not much...
        $query = implode("\n", gzfile($_FILES['sqlite_file']['tmp_name']));
      else
        # We don't know! :(
        $config['msg'] = '<p class="error">Import Error! Could not determine the file type.</p>';
  
      # No message? Continue the import!
      if(empty($config['msg']))
      {
        # Try to remove the temp file, we don't need it anymore...
        @unlink($_FILES['sqlite_file']['tmp_name']);
  
        # Now import!
        $start_time = microtime(true);
        @sqlite_exec($config['con'], $query, $import_error);
        $time_taken = round(microtime(true) - $start_time, 5);
  
        # Any errors? :|
        if(empty($import_error))
          $config['msg'] = '<p>Import completed successfully in '. $time_taken. ' seconds.</p>';
        else
          # An import error? .-.
          $config['msg'] = '<p class="error">Import Error: '. $import_error. '<br />If you have a backup of your database with DEBUG MODE, try that backup instead.</p>';
      }
    }
    else
      # We don't know! :(
      $config['msg'] = '<p class="error">Import Error! Could not determine the file type.</p>';
  }
  # Dropping tables..? Does indexes too!
  elseif(!empty($_POST['drop_tables']) && count($_POST['tbl']))
  {
    # So we need to see if its a index or table.
    $time_taken = 0;
    foreach($_POST['tbl'] as $name)
    {
      $result = sql_query("SELECT type FROM sqlite_master WHERE name = '$name'");

      # Does the index/table exist? o.0
      if(sqlite_num_rows($result))
      {
        @list($type) = sqlite_fetch_array($result, SQLITE_NUM);
        if($type == 'table')
          sql_query("DROP TABLE '$name'", $query_error, $single_time);
        else
          sql_query("DROP INDEX '$name'", $query_error, $single_time);

        # Add to the total time!
        $time_taken += $single_time;
      }
    }

    # Our message XD
    $config['msg'] = '<p>The indexes/tables '. implode(', ', $_POST['tbl']). ' have been dropped.</p>';
  }
  # Editing rows? This is where the real magic happens :P
  elseif(!empty($_POST['update_rows']))
  {
    # How many UPDATEs will we have?
    $num_rows = count($_POST['orig_value']);

    # An array of functions that can be parsed... Just incase :P
    $allowed_func = array('sqlite_escape_string', 'htmlentities', 'htmlspecialchars', 'base64_encode',
                          'md5', 'sha1', 'trim', 'addslashes', 'stripslashes', 'strtolower', 'strtoupper', 'time');

    # Our table name ;)
    $tbl_name = $_POST['tbl_name'];

    # We need our unique identifiers...
    $unique_ids = array();
    foreach($_POST['unique_id'] as $key => $value)
      $unique_ids[] = (int)$value;

    # We needs our column names ;)
    $colNames = array();
    foreach($_POST['col'] as $colName)
      $colNames[] = $colName;

    # Just for quick creation...
    $update_tpl = 'UPDATE '. $tbl_name. ' SET ';

    # Holds all our queries!
    $queries = array();

    # Hold on! This is gonna be a bumpy ride! o.o
    # Now loop through them...
    for($i = 0; $i < $num_rows; $i++)
    {
      # So lets make it!
      $tmp = $update_tpl;

      # Loop through EACH column...
      $cols = array();
      foreach($colNames as $colName)
      {
        # The new values!
        $value = $_POST['values'][$i][$colName];

        # Any function? Is it allowed?
        if(!empty($_POST['functionStr'][$i][$colName]) && in_array($_POST['functionStr'][$i][$colName], $allowed_func))
        {
          # Is it time?
          if($_POST['functionStr'][$i][$colName] == 'time')
            $value = time();
          else
            $value = $_POST['functionStr'][$i][$colName]($value);
        }

        # Add the column.
        $cols[] = $colName. ' = \''. $value. '\'';
      }

      # Implode the new values! :)
      $tmp = $tmp. implode(', ', $cols). ' WHERE ';

      # Now the unique ones :P
      $cols = array();
      foreach($unique_ids as $id)
      {
        # So get it XD
        $cols[] = $_POST['col'][$id]. ' = \''. $_POST['orig_value'][$i][$id]. '\'';
      }

      $queries[] = $tmp. implode(' AND ', $cols). ';';
    }

    # Yay! We did it! Or so we hope...
    # Why query more then once if its the same?
    $queries = array_unique($queries);

    # We want to put it in the query box...
    $_REQUEST['q'] = implode("\r\n", $queries);

    # Now query it.
    $result = sql_query(implode($queries), $query_error, $time_taken);

    # Anything bad happen?
    if(empty($query_error))
      $config['msg'] = '<p>Query executed successfully. '. sqlite_changes($config['con']). ' rows affected, completed in '. $time_taken. ' seconds.</p>';
    else
      # Errors? D:!
      $config['msg'] = '<p class="error">Query Error: '. $query_error. '</p>';
  }
  # Deleting? Oh noes!
  elseif(!empty($_REQUEST['act']) && $_REQUEST['act'] == 'delete_rows')
  {
    # Get our query...
    $query = htmlspecialchars_decode($_POST['query'], ENT_QUOTES);

    # It cannot be a JOIN!
    if(!is_join($query))
    {
      # Get our table...
      $tbl_name = get_table($query);

      # Did we get one? Or any unique identifiers?
      if($tbl_name !== false || empty($_POST['unique_id']) || !count($_POST['unique_id']) || empty($_POST['select']) || !count($_POST['select']))
      {
        # Now run the query...
        $result = sql_query($query, $query_error);

        # Any errors?
        if(empty($query_error))
        {
          # Get the ones we want to be selected...
          $selected = array();
          foreach($_POST['select'] as $id => $value)
            $selected[] = (int)$id;

          # Our unique identifiers...
          $unique_ids = array();
          foreach($_POST['unique_id'] as $id => $value)
            $unique_ids[] = (int)$id;

          # Get our column names...
          $colNames = array();
          $numCols = sqlite_num_fields($result);
          for($i = 0; $i < $numCols; $i++)
            $colNames[] = sqlite_field_name($result, $i);

          # Now we need to build all the queries...
          $delete_tpl = 'DELETE FROM '. $tbl_name. ' WHERE ';
          $queries = array();
          $i = 0;
          while($row = sqlite_fetch_array($result, SQLITE_NUM))
          {
            # Do we need this one?
            if(in_array($i, $selected))
            {
              # Ya, it is.
              # Get the unique identifiers...
              $cols = array();
              foreach($unique_ids as $id)
              {
                $cols[] = $colNames[$id]. ' = \''. $row[$id]. '\'';
              }

              # Add the query...
              $queries[] = $delete_tpl. implode(' AND ', $cols). ';';
            }

            # Why continue if we got them all?
            if(count($queries) >= count($selected))
              break;

            $i++;
          }

          # So make sure they are unique :P
          $queries = array_unique($queries);

          # Make it appear in the query box.
          $_REQUEST['q'] = implode("\r\n", $queries);

          # Now query it.
          $time_taken = 0;
          $rows_deleted = 0;
          foreach($queries as $query)
          {
            $result = sql_query($query, $query_error, $single_time);
            $time_taken += $single_time;
            $rows_deleted += sqlite_changes($config['con']);
            if(!empty($query_error))
              break;
          }

          # Anything bad happen?
          if(empty($query_error))
            $config['msg'] = '<p>Query executed successfully. '. $rows_deleted. ' rows affected, completed in '. $time_taken. ' seconds.</p>';
          else
            # Errors? D:!
            $config['msg'] = '<p class="error">Query Error: '. $query_error. '</p>';
        }
        else
          $config['msg'] = '<p class="error">Query Error: '. $query_error. '</p>';
      }
      elseif($tbl_name === false)
        $config['msg'] = '<p class="error">Could not extract the table from the query.</p>';
      else
        $config['msg'] = '<p class="error">No unique identifier or rows selected. <a href="'. $_SERVER['PHP_SELF']. '?act=query&amp;q='. urlencode($query). '">Go back</a>.</p>';
    }
    else
      # What did I say?!
      $config['msg'] = '<p class="error">Cannot delete from a JOIN query.</p>';
  }
  # OooOoO! Creating a table?
  elseif(!empty($_POST['create_execute']))
  {
    # Number of columns...
    $numCols = (int)count($_POST['col']);

    # Table name?
    $tbl_name = !empty($_POST['tbl_name']) ? $_POST['tbl_name'] : '';

    # Can't have 0 or less columns :P
    if($numCols < 1)
      $config['msg'] = '<p class="error">Can not create a table with less than 1 column.</p>';
    elseif(empty($tbl_name))
      $config['msg'] = '<p class="error">Table name can not be left empty.</p>';
    else
    {
      # Start our template XD
      $create_tpl = 'CREATE TABLE \''. $tbl_name. '\''. "\r\n(\r\n";

      # Data types, and whether or not they can have a length ;)
      $types = array('INTEGER' => false, 'INT' => true, 'SMALLINT' => true, 'FLOAT' => false, 'NUMERIC' => false,
                     'TIMESTAMP' => false, 'DATE' => false, 'TEXT' => false, 'BLOB' => false, 'CLOB' => false,
                     'VARCHAR' => true, 'NVARCHAR' => true);

      # Now get the names...
      $cols = array();
      for($i = 0; $i < $numCols; $i++)
      {
        # Can't have an empty column name...
        if(empty($_POST['col'][$i]))
        {
          $config['msg'] = '<p class="error">Column #'. ($i + 1). ' name empty.</p>';
          break;
        }

        # Valid datatype?
        if(isset($types[$_POST['datatype'][$i]]))
        {
          $datatype = $_POST['datatype'][$i];

          # Length?
          if($types[$_POST['datatype'][$i]] && !empty($_POST['length'][$i]))
            $datatype .= '('. $_POST['length'][$i]. ')';
        }
        else
        {
          $config['msg'] = '<p class="error">Column #'. ($i + 1). ' has an invalid data type.</p>';
          break;
        }

        $cols[] = rtrim('  \''. $_POST['col'][$i]. '\' '. $datatype. ' '. (!empty($_POST['null'][$i]) ? '' : 'NOT NULL '). (!empty($_POST['default'][$i]) ? 'DEFAULT \''. sqlite_escape_string($_POST['default'][$i]). '\'' : ''));
      }

      # Add the columns...
      $create_tpl .= implode(",\r\n", $cols);

      # Almost done... Primary Keys?
      if(isset($_POST['primary']) && count($_POST['primary']))
      {
        # Our array will hold the column name...
        $keys = array();

        foreach($_POST['primary'] as $key => $dummy)
        {
          $keys[] = $_POST['col'][$key];
        }

        # Add it now...
        $create_tpl .= ",\r\n  PRIMARY KEY('". implode('\',\'', $keys). "')";
      }

      # Add the last );
      $create_tpl .= "\r\n);";

      # Parse it... and we got it!
      $result = sql_query($create_tpl, $query_error);

      # Make the query appear...
      $_REQUEST['q'] = $create_tpl;

      if(empty($query_error))
        # No errors, yay!
        $config['msg'] = '<p>Table '. htmlspecialchars($tbl_name, ENT_QUOTES). ' created successfully.</p>';
      else
        $config['msg'] = '<p class="error">Error: '. $query_error. '</p>';
    }
  }
  # Creating an index..?
  elseif(!empty($_POST['create_index']))
  {
    # Table name...
    $tbl_name = !empty($_POST['tbl_name']) ? $_POST['tbl_name'] : '';

    # Columns...
    if(!empty($_POST['col']) && count($_POST['col']))
    {
      $cols = array();
      foreach($_POST['col'] as $colName => $dummy)
        $cols[] = $colName;

      # Ok. Get ready...
      $query = 'CREATE '. (!empty($_POST['index']) && $_POST['index'] == 'unique' ? 'UNIQUE ' : ''). 'INDEX ';

      # Create a name... The table name, column names and type :)
      $index_name = $tbl_name. '_'. implode('_', $cols). '_'. (!empty($_POST['index']) && $_POST['index'] == 'unique' ? 'unique_index' : 'index');

      # Add it... and the rest of the stuff.
      $query .= '\''. $index_name. '\' ON \''. $tbl_name. '\' (\''. implode('\',\'', $cols). '\');';

      # Okay... now query...
      $result = sql_query($query, $query_error);

      # Make it appear in the query box.
      $_REQUEST['q'] = $query;

      # Any errors?
      if(empty($query_error))
        $config['msg'] = '<p>The index '. $index_name. ' was successfully created.</p>';
      else
        $config['msg'] = '<p class="error">Error: '. $query_error. '</p>';
    }
    else
      $config['msg'] = '<p class="error">No columns for the index selected.</p>';
  }
}

# And now... our switch, which handles a lot of things :P
phpLiter_main();

# And the source XD
function phpLiter_main()
{
  global $config;

  # Locked down..? Tisk tisk!
  if(!empty($config['lock_down']))
  {
    # Show that we are in lock down...
    print_lockdown();
    exit;
  }
  # Not logged in? You gotta be!
  elseif(!$config['is_logged'])
  {
    # So login! ._.
    print_login();
    exit;
  }

  # Now for the main course! Nummy!
  $actions = array(
    'create' => 'createSwitch',
    'edit_rows' => 'print_edit',
    'export' => 'exportSwitch',
    'help' => 'print_help',
    'import' => 'print_import',
    'insert' => 'print_insert',
    'phpinfo' => 'phpinfo',
    'server_info' => 'print_server_info',
    'sct' => 'print_sct',
    'struc' => 'print_struc',
  );

  # PHP Info allowed?
  if(!$config['phpinfo'])
    # Nope, so remove it ;)
    unset($actions['phpinfo']);

  # Any action..?
  if(!empty($_REQUEST['act']) && !empty($actions[$_REQUEST['act']]))
    # The action must exist... We hope :P
    $actions[$_REQUEST['act']]();
  else
    # Just the main :)
    print_main();
}

# Now all our functions which are called on... Magical :D!

# Our login screen...
function print_login()
{
  global $config;

  template_header('Login');

  echo '
  <div id="login_area">
    <h1>Login</h1>
      <div align="center">
        <form action="', $_SERVER['PHP_SELF'], '" method="post">';

    # Any errors to output?
    if(!empty($config['error_msg']))
      echo '
          <p class="error">', $config['error_msg'], '</p>';

  echo '
          <legend for="user">Username:</legend>
            <input name="user" type="text" value="', !empty($_REQUEST['user']) ? htmlspecialchars($_REQUEST['user'], ENT_QUOTES) : '', '" />
          <br /><br />
          <legend for="pass">Password:</legend>
            <input name="pass" type="password" value="" />
          <br />
          <legend for="remember_me">Remember me for 30 days</legend><input name="remember_me" type="checkbox" value="1" checked="checked" />
          <br />
          <input name="login" type="submit" value="Login" />
        </form>
      </div>
  </div>';

  template_footer();
}

# The main page of phpLiterAdmin... shows table list, results, etc.
function print_main()
{
  global $config;

  # Connection error? Or no database selected..?
  if(!empty($config['con_error']) || empty($_SESSION['db']))
  {
    template_header('Connection Error', false);

    # Show the error in our little message center :)
    if(empty($_SESSION['db']))
      $config['error_msg'] = 'Please select a database to edit in the drop down, then hit Edit.';
    else
      $config['error_msg'] = 'Database Connection Error! Error: '. $config['error_msg'];

    echo '
    <br /><br /><br />
    <div id="con_error">
      <p>', $config['error_msg'], '</p>
    </div>';
  }
  else
  {
    template_header();

    # Nothing seemed to go wrong XD
    # So if no results are to be showed... display a table list.
    if(empty($config['show_results']))
      # List them, na na na na na! You can list it XD
      list_tables();
    else
      # Just show a select... or explain, or whatever!
      show_select();
  }

  # Output the footer here :)
  template_footer();
}

# Our List Tables function... its very useful XD.
function list_tables()
{
  global $config;

  # The sqlite_master table contains all we need to list all the tables.
  $result = sql_query('SELECT * FROM sqlite_master '. (empty($_SESSION['show_indexes']) ? 'WHERE type = \'table\' ' : ''). 'ORDER BY tbl_name ASC', $query_error);

  # Any error occur? Thats bad! :S
  if(!empty($query_error) || !$result)
    echo '
    <div id="con_error">
      <p>An error occurred while attempting to read from the SQLite Master table.<br />Error: ', $query_error, '</p>
    </div>';
  elseif($config['db_not_allowed'])
  {
    echo '
    <div id="con_error">
      <p>Sorry, but the database you are attempting to select isn\'t allowed.</p>
    </div>';
  }
  else
  {
    # Nothing went wrong... just like it should :P
    echo '
    <div id="info_center">
      <p>', empty($config['msg']) ? 'Database Size: '. format_size($_SESSION['db']) : $config['msg'], '</p>
    </div>';

    echo '
  <form action="', $_SERVER['PHP_SELF'], '" name="tbl_list" method="post">';

    # Why do it twice when you can use a function? XD
    table_options();

    # The actual table list...
    echo '
    <table cellspacing="0px" cellpadding="0px">
      <tr>
        <th class="checkbox"><input name="select_all" accesskey="s" type="checkbox" onClick="select_tables(this.form);" title="Invert all (Alt + S)" /></th><th class="left">Table Name</th><th colspan="3">&nbsp;</th><th>Rows</th><th>Type</th>
      </tr>';

      # To alter the backgrounds XD
      $i = 0;
      while($tbl = sqlite_fetch_array($result, SQLITE_ASSOC))
      {
        echo '
      <tr class="', $i == 0 ? 'tr_1' : 'tr_2', '">
        <td><input name="tbl[]" type="checkbox" value="', $tbl['name'], '"/></td><td><a href="', $_SERVER['PHP_SELF'], '?act=query&amp;q=SELECT+*+FROM+', $tbl['tbl_name'], '">', $tbl['name'], '</a></td><td>[<a href="', $_SERVER['PHP_SELF'], '?act=struc&amp;tbl=', $tbl['tbl_name'], '" title="Table Structure for ', $tbl['tbl_name'], '">Structure</a>]</td><td>[<a href="', $_SERVER['PHP_SELF'], '?act=sct&amp;tbl=', $tbl['name'], '" title="Show Create Table for ', $tbl['name'], '">SCT</a>]</td><td class="center">[<a href="', $_SERVER['PHP_SELF'], '?act=insert&amp;tbl=', $tbl['tbl_name'], '" title="Insert a row into ', $tbl['tbl_name'], '">Insert</a>]<td class="center">', num_rows($tbl['name'], $tbl['type']), '</td><td class="center">', $tbl['type'], '</td>
      </tr>';

        # Make 0 be 1, and 1 be 0 :)
        $i = $i == 0 ? 1 : 0;
      }

    echo '
    </table>';

    table_options();

    echo '
  </form>';
  }
}

# Our table options function... Like VACUUM EMPTY DELETE, etc.
function table_options()
{
  echo '
    <table>
      <tr>
        <td>
          <input name="vacuum" accesskey="v" type="submit" title="Optimize the database (Alt + V)" value="Vacuum" />
        </td>
        <td>
          <input onClick="return confirm(\'Are you sure you want to empty the tables? It cannot be undone!\');" name="empty" type="submit" title="Empty the selected tables" value="Empty"/>
        </td>
        <td>
          <input onClick="return confirm(\'Are you sure you want to drop the selected tables? All data will be lost forever!\');" name="drop_tables" type="submit" title="Drop the selected tables" value="Drop"/>
        </td>
        <td>
          <input name="show_indexes" accesskey="i" type="submit" title="', (isset($_SESSION['show_indexes']) && $_SESSION['show_indexes'] ? 'Don\'t show indexes' : 'Show indexes'), ' (Alt + I)" value="', (isset($_SESSION['show_indexes']) && $_SESSION['show_indexes']) ? 'Don\'t show indexes' : 'Show indexes', '"/>
        </td>
      </tr>
    </table>';
}

# The number of records in a table... XD.
function num_rows($tbl_name, $type)
{
  # Only if its a table...
  if($type == 'table')
  {
    # We used to do a sqlite_num_rows(), but thats not very efficient :P
    $result = sql_query("SELECT COUNT(*) FROM '{$tbl_name}'");

    # Get it XD
    @list($num_rows) = sqlite_fetch_array($result, SQLITE_NUM);
    return $num_rows;
  }
  else
    return '--';
}

# File size formatting function.
function format_size($file, $not_file = false)
{
  # I didn't make this! Credit www.php.net/manual/en/function.filesize.php#84034 
  # File?
  if(!$not_file)
    $size = @filesize($file);
  else
    # Nope...
    $size = (int)$file;
  $sizes = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
  $total = count($sizes);

  for($i = 0; $size > 1024 && $i < $total; $i++) 
    $size /= 1024;

  return round($size, 2). $sizes[$i];
}

# Row editing...
function print_edit()
{
  global $config;

  # Get our query ;)
  $query = htmlspecialchars_decode($_POST['query'], ENT_QUOTES);

  # No errors yet...
  $edit_error = null;

  # It cannot be a join!
  if(is_join($query))
  {
    $title = 'Edit Rows Error!';
    $edit_error = 'Sorry! But you cannot edit rows that come from a JOIN query.';
  }
  # Can't get the table name?
  elseif(($tbl_name = get_table($query)) === false)
  {
    $title = 'Edit Rows Error!';
    $edit_error = 'Sorry, but the table name could not be extracted from the query.';
  }
  # No unique identifiers?
  elseif(empty($_POST['unique_id']) || !count($_POST['unique_id']))
  {
    $title = 'Edit Rows Error!';
    $edit_error = 'No unique identifiers were selected. <a href="'. $_SERVER['PHP_SELF']. '?act=query&amp;q='. urlencode($query). '">Go back and select some</a>.';
  }
  # Any rows selected?
  elseif(empty($_POST['select']) || !count($_POST['select']))
  {
    $title = 'Edit Rows Error!';
    $edit_error = 'No rows selected. <a href="'. $_SERVER['PHP_SELF']. '?act=query&amp;q='. urlencode($query). '">Go back and select some</a>.';
  }

  template_header(!empty($title) ? $title : 'Edit Rows', false);

  if(!empty($edit_error))
    # Oh noes! An error!
    echo '
    <div id="edit_rows">
      <p class="error">', $edit_error, '</p>
    </div>';
  else
  {
    # Get our unique identifiers...
    $unique_ids = array();
    foreach($_POST['unique_id'] as $id => $value)
      $unique_ids[] = (int)$id;

    # Now get the rows we want to edit :P
    # This might not be super efficient... but I think its good enough.
    $row_ids = array();
    foreach($_POST['select'] as $id => $value)
      $row_ids[] = (int)$id;

    # Now query the query and get the rows we want to edit :P
    $result = sql_query($query);

    # We need all the column data...
    $colNames = array();
    $numCols = sqlite_num_fields($result);
    for($i = 0; $i < $numCols; $i++)
    {
      $colNames[] = sqlite_field_name($result, $i);
    }

    $colTypes = sqlite_fetch_column_types($tbl_name, $config['con']);

    # We need to know which to save and which to not ;)
    $index = 0;
    $rows = array();
    while($row = sqlite_fetch_array($result, SQLITE_ASSOC))
    {
      if(in_array($index, $row_ids))
        $rows[] = $row;

      # Why continue on if we got them all?
      if(count($rows) >= count($row_ids))
        break;

      $index++;
    }

    # Now we show you the editing form :)
      echo '
      <div id="edit_rows">
        <h3>Edit Rows</h3>
        <p>You are currently editing <strong>', count($row_ids), ' row', count($row_ids) > 1 ? 's' : '', '</strong> from the table <strong>', $tbl_name, '</strong>.<br />Unique identifier columns are in <em><strong>italics</strong></em>.</p>
        <form action="', $_SERVER['PHP_SELF'], '" method="post">
          <table align="center" width="95%">';

        # Now the rows!
        $index = 0;
        foreach($rows as $row)
        {
          echo '
            <tr>
              <th>Column Name</th><th>Data type</th><th>Function*</th><th>Value</th>
            </tr>';

          # The values...
          $i = 0;
          foreach($colNames as $key => $colName)
          {
            echo '
            <tr>
              <td class="insert">', (in_array($key, $unique_ids)) ? '<em>'. $colName. '</em>' : $colName, '</td><td class="insert">', strtoupper($colTypes[$colName]), '</td><td align="center">', buildFunctionList($index, $colName), '</td><td align="center">', showInput($colTypes[$colName], 'values['. $index. ']['. $colName. ']', $row[$colName]), '</td>
            </tr>';

            # Don't forget the original value!
            echo '
              <input name="orig_value[', $index, '][', $i, ']" type="hidden" value="', htmlspecialchars($row[$colName], ENT_QUOTES), '" />';
            $i++;
          }

          $index++;
        }

        # We need a column list for later ;)
        foreach($colNames as $colName)
          echo '
          <input name="col[]" type="hidden" value="', $colName, '" />';

        # We need the unique identifiers!
        foreach($unique_ids as $id)
          echo '
          <input name="unique_id[]" type="hidden" value="', $id, '" />';

        echo '
            <tr>
              <td colspan="4" align="right"><input name="save_rows" type="submit" value="Save Rows" /></td>
            </tr>
          </table>
          <input name="tbl_name" type="hidden" value="', $tbl_name, '" />
          <input name="update_rows" type="hidden" value="1" />
        </form>
        <p class="lil_msg">
          * When you choose a function, before the data is inserted, the function is called upon with the value as the parameter<br />
          ** When you use this function, the value you entered is replaced with what this function returns
        </p>
      </div>';
  }

  template_footer();
}

# This simple function builds a list of functions that can
# be done on the value XD
function buildFunctionList($i = 0, $colName = '')
{
  return '
    <select name="functionStr['. $i. ']['. $colName. ']">
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

# Our input maker :)
function showInput($type, $name, $value = '')
{
  $type = strtoupper($type);

  # Remove the ( and )
  if(strpos($type, '(') !== false)
  {
    $length = substr($type, strpos($type, '(') + 1, strlen($type));
    $length = substr($length, 0, strlen($length) - 1);
    $type = substr($type, 0, strpos($type, '('));
  }

  # Now we have the real data type :P
  # TEXT, BLOB and CLOB are textarea's,
  # rest are regular inputs :P
  $textareas = array('TEXT','CLOB','BLOB');
  if(in_array($type, $textareas))
  {
    return '<textarea name="'. $name. '" cols="17" rows="5">'. $value. '</textarea>';
  }
  else
    return '<input name="'. $name. '" type="text" value="'. $value. '"/>';
}

# Our exporting switch... we might be exporting,
# or we might be displaying options ;)
function exportSwitch()
{
  # This is fast...
  if(!isset($_GET['export']))
    print_export();
  else
    do_export();
}

# Exporting options...
function print_export()
{
  global $config;

  template_header('Export Database', false);

  echo '
  <div id="export">
    <h1>Export Database</h1>
      <form action="', $_SERVER['PHP_SELF'], '?act=export&amp;export" method="post">
        <table align="center">
          <tr>
            <td><label for="struc">Export with Structure</label> [<a href="javascript:void(0);" onClick="return faq(\'',$_SERVER['PHP_SELF'], '?act=help&amp;faq=export_struc\');">?</a>]</td><td><input id="struc" name="struc" id="struc" type="checkbox" checked="checked" value="1"/></td>
          </tr>
          <tr>
            <td><label for="data">Export with Data</label> [<a href="javascript:void(0);" onClick="return faq(\'',$_SERVER['PHP_SELF'], '?act=help&amp;faq=export_data\');">?</a>]</td><td><input id="data" name="data" id="data" type="checkbox" checked="checked" value="1"/></td>
          </tr>
          <tr>
            <td><label for="drop">Add DROP TABLE</label> [<a href="javascript:void(0);" onClick="return faq(\'',$_SERVER['PHP_SELF'], '?act=help&amp;faq=drop_table\');">?</a>]</td><td><input id="drop" name="drop" type="checkbox" value="1"/></td>
          </tr>
          <tr>
            <td><label for="debug_mode">Debug Mode</label> [<a href="javascript:void(0);" onClick="return faq(\'',$_SERVER['PHP_SELF'], '?act=help&amp;faq=debug_mode\');">?</a>]</td><td><input id="debug_mode" name="debug_mode" type="checkbox" checked="checked"/></td>
          </tr>          
          <tr>
            <td><label for="transaction">Add TRANSACTIONs</label> [<a href="javascript:void(0);" onClick="return faq(\'', $_SERVE['PHP_SELF'], '?act=help&amp;faq=transaction\');">?</a>]</td><td><input id="transaction" name="transaction" type="checkbox" checked="checked"/></td>
          </tr>
          <tr>
            <td colspan="2">Export as:</td>
          </tr>
          <tr>
            <td><input name="type" value="sql" id="sql" type="radio" checked="checked" /> <label for="sql">SQL</label></td><td><input name="type" id="gz" value="gz" type="radio" ', !function_exists('ob_gzhandler') ? 'disabled="disabled" ' : '', '/> <label for="gz">GZipped</label> [<a href="javascript:void(0);" onClick="return faq(\'', $_SERVER['PHP_SELF'], '?act=help&amp;faq=zlib\');">?</a>]</td>
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

# The function which does the actual exporting...
function do_export()
{
  global $config;

  # So we need to have a file name, which is the database
  # name, so we need to remove the /'s and .'s out...
  $db_name = $_SESSION['db'];

  # But are there any /?
  if(strpos($db_name, '/') !== false)
  {
    # Get it all by itself...
    $db_name = explode('/', $db_name);
    $db_name = $db_name[count($db_name) - 1];
  }

  # How about any .?
  if(strpos($db_name, '.') !== false)
  {
    # So we will remove the last one ;)
    $db_name = explode('.', $db_name);

    # Just unset it!
    unset($db_name[count($db_name) - 1]);

    # Impode it, and we got it!
    $db_name = implode('.', $db_name);
  }

  # Start output buffering...
  ob_start();

  # We are about to send out the headers, but...
  $mime_type = 'text/sql';
  $ext = '.sql';
  $do_gz = false;

  # Okay, we might change it if you want it to be GZipped...
  if($_POST['type'] == 'gz' && function_exists('ob_gzhandler'))
  {
    $mime_type = 'application/x-gzip';
    $ext = '.sql.gz';
    $do_gz = true;
  }

  # Line break variable...
  $lb = "\r\n";

  # Output our comments...
  echo '----', $lb,
       '-- phpLiterAdmin database dump (http://phpliteradmin.googlecode.com/)', $lb,
       '-- phpLiterAdmin version: ', $config['version'], $lb,
       '-- Exported on ', date('M jS, Y, h:i:sA'), $lb,
       '-- Database file: ', $db_name, $lb,
       '----', $lb, $lb;

  # Now this is where we output all the stuffs!
  $table_result = sql_query('SELECT * FROM sqlite_master');

  while($tbl = sqlite_fetch_array($table_result, SQLITE_ASSOC))
  {
    # Do they want to do a drop table?
    if(!empty($_POST['drop']) && !empty($tbl['sql']))
      echo '----', $lb,
           '-- Drop ', $tbl['type'] == 'index' ? 'index' : 'table', ' for ', $tbl['name'], $lb,
           '----', $lb,
           'DROP ', $tbl['type'] == 'index' ? 'INDEX' : 'TABLE', ' \'', $tbl['name'], '\';', $lb;
    # The structure..?
    if(!empty($_POST['struc']) && !empty($tbl['sql']))
      echo '----', $lb,
           '-- ', $tbl['type'] == 'index' ? 'Index' : 'Table', ' structure for ', $tbl['name'], $lb,
           '----', $lb,
           $tbl['sql'], ';', $lb, $lb;

    # The data? Must be a table! ^^
    if(!empty($_POST['data']) && $tbl['type'] == 'table')
    {
      # Yup... the data... If anything...
      # Now the data... select it all!
      $result = sql_query("SELECT * FROM '". $tbl['name']. "'");
      if(sqlite_num_rows($result) > 0)
      {
        echo '----', $lb,
             '-- Data dump for ', $tbl['name'], $lb,
             '----', $lb;

        # Do you want transactions?
        if(!empty($_POST['transaction']))
          echo 'BEGIN TRANSACTION;', $lb;

        # Build an array of column names...
        $colNames = array();
        $numCols = sqlite_num_fields($result);
        for($i = 0; $i < $numCols; $i++)
          $colNames[] = sqlite_field_name($result, $i);

        while($row = sqlite_fetch_array($result, SQLITE_NUM))
        {
          # SQLite doesn't support extended inserts... though they
          # do make up for it for the fact you can execute multiple
          # queries at a time XD! Anyways...

          # Debug Mode..? Though all it does is sqlite_escape_string :P
          if(!empty($_POST['debug_mode']))
            foreach($row as $key => $value)
              $row[$key] = sqlite_escape_string($value);

          echo 'INSERT INTO ', $tbl['name'], ' (\''. implode('\',\'', $colNames). '\') VALUES(\''. implode('\',\'', array_values($row)). '\');', $lb;
        }

        # Finish with a line break... but maybe a COMMIT :)
        echo !empty($_POST['transaction']) ? 'COMMIT;' : '', $lb, $lb;
      }
    }
  }

  # We need to get all the contents :P
  $backup_sql = ob_get_contents();

  # Clean it up...
  ob_end_clean();

  # GZip it?
  if($do_gz)
    $backup_sql = gzencode($backup_sql);

  # Now the headers!
  header('Pragma: public');
  header('Expires: 0');
  header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
  header('Cache-Control: private', false);
  header('Content-Transfer-Encoding: binary');
  header('Content-Type: '. $mime_type);
  header('Content-Disposition: attachment; filename="'. $db_name. '-'. date('m-d-Y'). $ext. '";');

  echo $backup_sql;

  # And we are DONE!
  exit;
}

# Importing a SQL backup?
function print_import()
{
  global $config;

  template_header('Import Database', false);

  echo '
  <div id="import">
    <h1>Import Database</h1>
    <form action="', $_SERVER['PHP_SELF'], '" enctype="multipart/form-data" method="post">
      <table align="center">
        <tr>
          <td>Database File:</td><td><input name="sqlite_file" type="file"/></td>
        </tr>    
        <tr>
          <td colspan="2" style="text-align: center;">', function_exists('ob_gzhandler') ? '.gz and ' : '', '.sql files only</td>
        </tr>    
        <tr align="center">
          <td colspan="2"><input type="button" onClick="window.location=\'', $_SERVER['PHP_SELF'], '\'" value="Cancel"/>&nbsp;&nbsp;&nbsp;<input onClick="return confirm(\'Are you sure you want to import the backup?\');" name="import" type="submit" value="Upload &amp; Import"/>
        </tr>
      </table>
    </form>
  </div>';

  template_footer();
}

# Show SELECT, EXPLAIN, etc. function...
function show_select()
{
  global $config;

  # So get our query...
  $query = $_REQUEST['q'];

  $result = sql_query($query, $query_error, $time_taken);

  # Whether or not its a simple select...
  $is_join = is_join($query);

  # No error? Good!
  if(empty($query_error))
  {
    # Now show the results!
    echo '
  <div id="info_center">
    <p>Query executed in ', round($time_taken, 5), ' seconds. ', sqlite_num_rows($result), ' rows displayed.</p>
  </div>
  <form action="', $_SERVER['PHP_SELF'], '" method="post">
    <table cellspacing="1px" cellpadding="0px" width="100%">
      <tr>';

    # If it isn't a JOIN, we need a spacer XD
    if(!$is_join)
    {
      echo '
        <th class="checkbox" align="center"><input name="select_all" type="checkbox" onClick="select_rows(this.form);" /></th>';

      # Anyways... We might as well leech off this IF statement
      # What we need to do now is find any PRIMARY KEYs, which
      # we will use as default Unique Identifiers for editing! :D
      # But we need to get the table name!
      $table_name = get_table($query);

      # So if its false, don't do it :(
      if($table_name !== false)
      {
        # Yay!
        $pragma = sql_query("PRAGMA table_info('". $table_name. "')");

        # Now we build a primary key array...
        $primary = array();
        while($row = sqlite_fetch_array($pragma, SQLITE_ASSOC))
          # Is it primary?
          if($row['pk'] == 1)
            $primary[] = $row['name'];
      }
    }
    else
      # Make a dummy :P
      $primary = array();

    # Now a row of column names...
    $numCols = sqlite_num_fields($result);
    for($i = 0; $i < $numCols; $i++)
    {
      # Get the fields name!
      $colName = htmlspecialchars(sqlite_field_name($result, $i), ENT_QUOTES);

      # Now if it isn't a JOIN/EXPLAIN/etc. we can show a checkbox
      # Next to the name :)
      if(!$is_join)
        echo '<th><input id="id_', $i, '" name="unique_id[', $i, ']" type="checkbox" title="Select ', $colName, ' as a unique identifier" value="1" ', @in_array($colName, $primary) ? 'checked="checked" ' : '', '/> <label for="id_', $i, '" title="Select ', $colName, ' as a unique identifier">', $colName, '</label></th>';
      else
        # Just show the name...
        echo '<th>', $colName, '</th>';
    }

    echo '
      </tr>';

    # Now all our actual data... from the table :|
    $i = 0;
    while($row = sqlite_fetch_array($result, SQLITE_ASSOC))
    {
      echo '
      <tr class="', $i % 2 == 0 ? 'tr_1' : 'tr_2', '">
      ';

      # Select box..? (Checkbox :P)
      if(!$is_join)
        echo '<td align="center" width="5%"><input id="select_', $i, '" name="select[', $i, ']" type="checkbox" title="Select this row" value="1" /></td>';

      foreach($row as $key => $value)
      {
        # Show all text or just some?
        if(empty($_REQUEST['fulltext']))
          $value = substr($value, 0, 50);

        # Turn entities into entities! So it displays like
        # it really is :P No HTML XD
        $value = htmlspecialchars($value, ENT_QUOTES);

        # Now output the column and its data.
        echo '<td><label for="select_', $i, '" style="display: block; width: 100%;">', $value, '</label></td>';
      }

      echo '
      </tr>';

      $i++;
    }

    # If its not a join show a couple buttons...
    if(!$is_join)
      echo '
      <tr>
        <td colspan="', $numCols + 1, '"><input name="edit_rows" accesskey="e" onClick="return select_form(this.form, \'edit\');" type="submit" value="Edit selected" title="Edit the selected rows (Alt + E)" /> <input name="delete_rows" accesskey="d" onClick="return select_form(this.form, \'delete\');" type="submit" value="Delete selected" title="Delete the selected rows (Alt + D)" /></td>
      </tr>';

    echo '
      <input name="query" type="hidden" value="', htmlspecialchars($query, ENT_QUOTES), '" />
    </table>
  </form>';
  }
  else
    # An error! Oh noes!
    echo '
    <div id="info_center">
      <p class="error">Error: ', $select_error, '</p>
    </div>';
}

# Figures out whether its a simple SELECT...
# Probably not very good, but =P!
function is_join($query)
{
  # But wait, filter something out!
  if(stripos($query, 'FROM') === false)
    # It might be like a SELECT 'hi' ;) you can't edit that!
    return true;

  # We won't need to do this with a PRAGMA or EXPLAIN
  if(!preg_match('~^PRAGMA|EXPLAIN~i', trim($query)))
    if(preg_match('~(?:LEFT (OUTER)?|RIGHT ) JOIN~is', trim($query)))
      return true;
    else
      return false;

  # Nothing returned? You can't edit EXPLAIN or PRAGMAs XD
  return true;
}

# Attempts to get the table from a query...
function get_table($query)
{
  global $config;

  # Lets try a EXPLAIN... Though SQLite.org says it should ONLY
  # be used for debugging and can change from version to version
  # pooey on them XD. (Not really, jk :P)
  $result = sql_query('EXPLAIN '. $query);

  $explain = array();
  $num_openReads = 0;
  if($result)
    while($row = sqlite_fetch_array($result, SQLITE_ASSOC))
    {
      if(!empty($row['opcode']) && strtolower($row['opcode']) == 'openread')
        $num_openReads++;
      $explain[strtolower(!empty($row['opcode']) ? $row['opcode'] : '')] = !empty($row['p3']) ? $row['p3'] : '';
    }

  # More then one OpenRead..? That means its NOT a Simple Select O.o
  if($num_openReads > 1)
    return false;
  elseif(!empty($explain['openread']))
    # Sweet! One was found :)
    return $explain['openread'];

  # Still going? Dang! We have to do it the regex way!
  preg_match('/ FROM (.*?)( AS (.*))? WHERE/is', $query, $matches);

  # Since I am bad at regex, check if $matches[1] is nothing
  # if it is, we might have done something wrong :P
  # so lets add WHERE to the end ^^
  if(empty($matches[1]))
    preg_match('/ FROM (.*?) (WHERE|AS|LIMIT|GROUP|ORDER)/is', $query.' WHERE', $matches);

  return !empty($matches[1]) ? $matches[1] : false;
}

# db_list(); will build and display a list of databases you can edit :)
function db_list()
{
  global $config;

  # But only if you are logged in ;)
  if($config['is_logged'])
  {
    # And if you have multiple databases...
    if(is_array($config['db']) && count($config['db']) > 1)
    {
      # So we need to build a list, but we don't want the paths
      # and crap in it :D Just the database name...
      $db_list = array();
      foreach($config['db'] as $db_name)
      {
        # We need to save the database path...
        $db_path = $db_name;

        # So any / in it?
        if(strpos($db_name, '/') !== false)
        {
          # Quick and dirty!
          $db_name = explode('/', $db_name);
          $db_name = $db_name[count($db_name) - 1];
        }

        # Add it to the array XD
        $db_list[] = array($db_name, $db_path);
      }

      echo '
          <td style="color: #FFFFFF;">Database:</td>
          <form action="', $_SERVER['PHP_SELF'], '" method="post">
            <td><select name="database">';

        foreach($db_list as $db)
          echo '
                  <option value="', $db[1], '"', !empty($_SESSION['db']) && $_SESSION['db'] == $db[1] ? ' selected="yes"' : '', '>', $db[0], '</option>';

      echo '
                </select></td>
            <td><input name="go" type="submit" value="Edit" /></td>
            <input name="act" type="hidden" value="db" />
          </form>';
    }
  }
}

# Display the table structure... Useful stuff!
function print_struc()
{
  global $config;

  # Table structure! :D
  $tbl_name = $_REQUEST['tbl'];

  # Our header ^^
  template_header('Table Structure');

  # So get the table info!
  $result = sql_query('PRAGMA table_info(\''. $tbl_name. '\')');

  # Any table?
  if(sqlite_num_rows($result))
  {
    $columns = array();
    while($row = sqlite_fetch_array($result, SQLITE_ASSOC))
    {
      $columns[$row['name']] = array(
        'id' => $row['cid'],
        'name' => $row['name'],
        'type' => strtoupper($row['type']),
        'null' => $row['notnull'] == 0 ? true : false,
        'default' => htmlspecialchars($row['dflt_value'], ENT_QUOTES),
        'keys' => $row['pk'] ? array('<abbr title="Primary Key">PRI</abbr>') : array(),
      );
    }

    # We only get Primary Keys, lets get Unique indexes and just indexes ;)
    # If any XD
    $result = sql_query('PRAGMA index_list(\''. $tbl_name. '\')');
    if(sqlite_num_rows($result))
    {
      # Yup... there is some! Get them!
      $indexes = array();
      while($row = sqlite_fetch_array($result, SQLITE_ASSOC))
      {
        # Get the index name and whether its unique ;)
        $indexes[] = array(
          'id' => $row['seq'],
          'name' => $row['name'],
          'unique' => $row['unique'] == 1 ? true : false,
          'cids' => array(),
          'columns' => array(),
        );
      }

      # Now we need to get the indexes information...
      foreach($indexes as $key => $index)
      {
        $result = sql_query('PRAGMA index_info(\''. $index['name']. '\')');
        while($row = sqlite_fetch_array($result, SQLITE_ASSOC))
        {
          # Add the information to the index :)
          $indexes[$key]['cids'][] = $row['cid'];
          $indexes[$key]['columns'][] = $row['name'];
        }
      }

      # And last but not least, we need to add the indexes to the right column ;)
      foreach($indexes as $index)
      {
        # Gosh... We are going loopy aren't we? .-.
        foreach($index['columns'] as $col)
        {
          $columns[$col]['keys'][] = $index['unique'] ? '<abbr title="Unique Index'. (isset($index['id']) ? ', Seq: '. $index['id'] : ''). '">UNI</abbr>' : '<abbr title="Index'. (isset($index['id']) ? ', Seq: '. $index['id'] : ''). '">KEY</abbr>';
        }
      }
    }

    # So output all the information :)
    echo '
    <form action="', $_SERVER['PHP_SELF'], '" method="post">
      <table width="100%" cellspacing="1px" cellpadding="0px">
        <tr>
          <th colspan="6">Table Structure for ', $tbl_name, '</th>
        </tr>
        <tr>
          <th class="struc">&nbsp;</th><th>Column Name</th><th>Data type</th><th>Null?</th><th>Default Value</th><th>Keys</th>
        </tr>';

    $i = 0;
    foreach($columns as $column)
    {
      echo '
        <tr class="tr_', $i == 0 ? '1' : '2', '">
          <td class="struc"><input name="col[', $column['name'], ']" type="checkbox" value="1" title="Add index to ', $column['name'], '" /></td><td class="center">', $column['name'], '</td><td class="center">', $column['type'], '</td><td class="center">', $column['null'] ? 'Yes' : 'No', '</td><td class="center">', $column['default'], '</td><td class="center">', implode(' ', $column['keys']), '</td>
        </tr>';
      $i = $i == 0 ? 1 : 0;
    }

    echo '
        <tr>
          <td height="14px" colspan="6" align="left" valign="middle" class="left">Create a <select name="index"><option value="index">regular</option><option value="unique">unique</option></select> index. <input name="create_index" type="submit" value="Go" /></td>
        </tr>
      </table>
      <input name="tbl_name" type="hidden" value="', urlencode($tbl_name), '" />
    </form>
    <p class="center" style="margin-top: 5px;">Options: [<a href="', $_SERVER['PHP_SELF'], '?act=sct&amp;tbl=', $tbl_name, '" title="Show create table for ', $tbl_name, '">SCT</a>] [<a href="', $_SERVER['PHP_SELF'], '?act=query&amp;q=SELECT+*+FROM+', $tbl_name, '" title="Select all from ', $tbl_name, '">SELECT</a>] [<a href="', $_SERVER['PHP_SELF'], '?act=insert&amp;tbl=', $tbl_name, '" title="Insert a row into ', $tbl_name, '">INSERT</a>]</p>';
  }
  else
    echo '
    <div id="info_center">
      <p class="error">The table ', htmlspecialchars($tbl_name), ' does not exist!</p>
    </div>';

  template_footer();
}

# Show the create table :) SQLite doesn't have a command
# like SHOW CREATE TABLE like MySQL, but you can get it from
# the SQLite Master table.
function print_sct()
{
  global $config;

  template_header('Show Create Table');

  # Get the table name...
  $tbl_name = $_REQUEST['tbl'];

  # Now the master table :D
  $result = sql_query('SELECT sql FROM sqlite_master WHERE name = \''. $tbl_name. '\'');

  # So does this table exist?
  if(sqlite_num_rows($result) > 0)
  {
    # Fetch the query YOU originally made to create the table :|
    $create_table = sqlite_fetch_single($result);

    # But wait! It might not be one YOU made! It *might* be a PRIMARY KEY!
    # Which has no query, but it still appears...
    if(!empty($create_table))
    {
      # I hate double line breaked queries ¬.¬
      $create_table = explode("\n", $create_table);
      if(count($create_table) > 1)
      {
        $tmp = array();
        foreach($create_table as $line)
          if(trim($line) != '')
            $tmp[] = rtrim($line);
        $create_table = $tmp;
      }
      echo '
    <table width="100%" cellspacing="1px" cellpadding="0px">
      <tr>
        <th>Table</th><th>Create Table</th>
      </tr>
      <tr class="tr_1">
        <td valign="top">', $tbl_name, '</td><td><pre>', htmlspecialchars(trim(implode("\r\n", $create_table)), ENT_QUOTES), ';</td>
      </tr>
    </table>';
    }
    else
      echo '
    <div id="info_center">
      <p class="error">The index ', $tbl_name, ' is a Primary Key. Nothing to display.</p>
    </div>';
  }
  else
    echo '
    <div id="info_center">
      <p class="error">The table ', $tbl_name, ' does not exist!</p>
    </div>';

  template_footer();
}

# Our quick and easy insertion function :)
function print_insert()
{
  global $config;

  # Get our table.
  $tbl_name = $_REQUEST['tbl'];

  # So lets see if the table exists.
  $result = sql_query('SELECT * FROM sqlite_master WHERE tbl_name = \''. $tbl_name. '\'');
    $allowed_func = array('sqlite_escape_string', 'htmlentities', 'htmlspecialchars', 'base64_encode',
                          'md5', 'sha1', 'trim', 'addslashes', 'stripslashes', 'strtolower', 'strtoupper', 'time');
  # Any rows?
  if(sqlite_num_rows($result))
  {
    template_header('Insert a row', false);
    echo '
    <div id="insert">
      <h3>Insert a row</h3>
      <p>Here you can insert a row into the table ', htmlspecialchars($_REQUEST['tbl'], ENT_QUOTES), '</p>';

    # Get all the column types and what not...
    $types = sqlite_fetch_column_types($tbl_name, $config['con'], SQLITE_ASSOC);

    $colNames = array();
    foreach($types as $colName => $type)
      $colNames[] = $colName;

    # Inserting a new row or what?
    if(!empty($_REQUEST['insert_row']))
    {
      # Yes, yes we are!
      $query = 'INSERT INTO '. $tbl_name. ' (\''. implode('\',\'', $colNames). '\') VALUES';

      # An array of functions that can be parsed... Just incase :P
      $allowed_func = array('sqlite_escape_string', 'htmlentities', 'htmlspecialchars', 'base64_encode',
                            'md5', 'sha1', 'trim', 'addslashes', 'stripslashes', 'strtolower', 'strtoupper', 'time');

      # Loop through all the columns to get the values :)
      $values = array();
      foreach($colNames as $colName)
      {
        # The value, plz!
        $value = $_REQUEST['value'][$colName];

        # Any function? If so, is it allowed?
        if(!empty($_REQUEST['functionStr'][$colName][0]) && in_array($_REQUEST['functionStr'][$colName][0], $allowed_func))
        {
          if($_REQUEST['functionStr'][$colName][0] == 'time')
            $value = time();
          else
            $value = $_REQUEST['functionStr'][$colName][0]($_REQUEST['value'][$colName]);
        }

        # Empty? Lets do it!!!
        if(empty($value))
          $value = 'NULL';
        else
          $value = '\''. $value. '\'';

        # Add the value to the array...
        $values[] = $value;
      }

      # Now complete the query.
      $query = $query. '('. implode(',', $values). ');';

      # It can now be ran through the database :D!
      $result = sql_query($query, $query_error);

      # But was it a success?
      if(empty($query_error))
        echo '<p style="color: green;" class="center">Row Inserted Successfully!</p>';
      else
        echo '<p class="error center">Query Error: ', $query_error, '</p>';
    }

      echo '
      <form action="" method="post">
        <table width="90%" align="center">
          <tr>
            <th>Column Name</th><th>Data type</th><th>Function*</th><th>Value to Insert</th>
          </tr>';

        foreach($types as $colName => $dataType)
        {
          echo '
          <tr>
            <td class="insert">', $colName, '</td><td class="insert">', strtoupper($dataType), '</td><td align="center">', buildFunctionList($colName, 0), '</td><td align="center">', showInput($dataType, 'value['. $colName. ']'), '</td>
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
  else
  {
    template_header('Insert row Error!', false);
    echo '
    <div id="insert">
      <p class="error">Error! The table you have requested to insert a row for does not exist!</p>
    </div>';
    template_footer();
  }
}

# Server information? Anyone?
function print_server_info()
{
  global $config;

  template_header('Server Info', false);

  echo '
  <div id="server_info">
    <h1>Server Info</h1>
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
        <td class="var">Last Error Code</td><td>', sqlite_last_error($config['con']), '</td>
      </tr>
      <tr align="center">
        <td class="var">Last Error</td><td>', sqlite_error_string(sqlite_last_error($config['con'])), '</td>
      </tr>
      <tr align="center">
        <td class="var"><abbr title="Max Execution Time">Max Exec Time</abbr></td><td>', (int)@ini_get('max_execution_time'), ' seconds</td>
      </tr>
      <tr align="center">
        <td class="var">Safe Mode</td><td>', @ini_get('safe_mode') ? 'On' : 'Off', '</td>
      </tr>
      <tr align="center">
        <td class="var">Zlib Support [<a href="javascript:void(0);" onClick="return faq(\'', $_SERVER['PHP_SELF'], '?act=help&amp;faq=zlib\');">?</a>]</td><td>', function_exists('ob_gzhandler') ? 'Enabled' : 'Disabled', '</td>
      </tr>';

      # PHP Info? Perhaps...
      if($config['phpinfo'])
        echo '
      <tr align="center">
        <td class="var center" colspan="2"><a href="', $_SERVER['PHP_SELF'], '?act=phpinfo" target="_blank">View lots of PHP Info.</a></td>
      </tr>';

  echo '
    </table>
  </div>';

  template_footer();
}

# Lockdown screen!
function print_lockdown()
{
  template_header('Locked Down');

  echo '
  <div id="lockdown">
    <h1>Locked Down</h1>
      <p>Sorry! You can\'t even try to login! This has been locked down, if you have file access, open up the file and change the $config[\'lock_down\'] variable to 0, and refresh.</p>
  </div>';

  template_footer();
}

# Our Create Table switch...
function createSwitch()
{
  # Gotta get options! :P
  if(!isset($_GET['table']))
    print_createOptions();
  else
    print_createTable();
}

# So get the table name and the number of columns!
function print_createOptions()
{
  global $config;

  template_header('Create a table', false);

  echo '
  <div id="create_table">
    <h1>Create a table</h1>
    <p>First things first, we need a couple things from you.</p>
    <form action="', $_SERVER['PHP_SELF'], '?act=create&amp;table" method="post">
      <table cellpadding="0px" cellspacing="1px" align="center">
        <tr>
          <td>Table name</td><td><input name="tbl_name" type="text" value="" /></td>
        </tr>
        <tr>
          <td>Number of columns</td><td><input name="num_cols" type="text" value="" /></td>
        </tr>
        <tr align="center" class="center">
          <td colspan="2" align="center" class="center"><input type="submit" value="Continue..." /></td>
        </tr>
      </table>
    </form>
  </div>';

  template_footer();
}

# Now the more complicated thing :P Not really... XD
function print_createTable()
{
  global $config;

  # Lets get the table name...
  $tbl_name = !empty($_POST['tbl_name']) ? $_POST['tbl_name'] : '';

  # Name taken?
  $result = sql_query('SELECT * FROM sqlite_master WHERE name = \''. $tbl_name. '\'');

  if(empty($tbl_name) || sqlite_num_rows($result) > 0)
    # Oh noes!
    $error_msg = 'The table name you have entered is either invalid, empty or in use.';
  elseif(empty($_POST['num_cols']) || (string)$_POST['num_cols'] != (string)(int)$_POST['num_cols'] || (int)$_POST['num_cols'] < 1)
    $error_msg = 'The number of columns you have entered is invalid or to low.';

  # Any error?
  if(!empty($error_msg))
  {
    template_header('Create table error', false);

    echo '
  <div id="create_table">
    <h1>Create table error</h1>
    <p class="error center">', $error_msg, '</p>
    <p class="center"><a href="', $_SERVER['PHP_SELF'], '?act=create">Go Back</a></p>
  </div>';

    template_footer();
  }
  else
  {
    template_header('Create a table', false);

    # Adding more columns? Thats fine with me!
    if(!empty($_POST['more_cols']) && (string)$_POST['more_cols'] == (string)(int)$_POST['more_cols'])
    {
      # Just make sure...
      $tmp = $_POST['num_cols'];
      $_POST['num_cols'] += (int)$_POST['more_cols'];

      # Sure I could do it another way, but eh.
      if($_POST['num_cols'] < 1)
        $_POST['num_cols'] = $tmp;
    }

    echo '
  <div id="create_table">
    <h1>Create a table</h1>
    <p>You are currently creating the table <strong>', htmlspecialchars($tbl_name, ENT_QUOTES), '</strong> with <strong>', (int)$_POST['num_cols'], ' columns</strong>.</p>

    <form action="', $_SERVER['PHP_SELF'], '" method="post">
      <table width="90%" align="center" cellpadding="0px" cellspacing="1px" style="margin-top: 5px; margin-bottom: 5px;">
        <tr>
          <th>Column Name</th><th>Data type</th><th>Length*</th><th>Null?</th><th>Default value**</th><th><abbr title="Primary Key">PK***</abbr></th>
        </tr>';

    # Now we need to show some stuff so you can make the table XD!
    for($i = 0; $i < (int)$_POST['num_cols']; $i++)
    {
      echo '
        <tr>
          <td><input name="col[', $i, ']" type="text" value="', htmlspecialchars(!empty($_POST['col'][$i]) ? $_POST['col'][$i] : '', ENT_QUOTES), '" /></td><td>', buildDataTypeList($i, !empty($_POST['datatype'][$i]) ? $_POST['datatype'][$i] : ''), '</td><td align="center" class="center"><input name="length[', $i, ']" type="text" size="3" value="', !empty($_POST['length'][$i]) ? (int)$_POST['length'][$i] : '', '" /></td><td align="center" class="center"><input name="null[', $i, ']" type="checkbox" value="1" ', !empty($_POST['null'][$i]) ? 'checked="checked" ' : '', '/></td><td><input name="default[', $i, ']" type="text" value="', htmlspecialchars(!empty($_POST['default'][$i]) ? $_POST['default'][$i] : '', ENT_QUOTES), '" /></td><td align="center" class="center"><input name="primary[', $i, ']" type="checkbox" value="1" ', !empty($_POST['primary'][$i]) ? 'checked="checked" ' : '', '/></td>
        </tr>';
    }

    echo '
        <tr>
          <td align="right" colspan="6"><input name="create_execute" type="submit" value="Create Table" /> Add <input name="more_cols" type="text" size="2" value="" /> more columns <input name="add_cols" type="submit" onClick="changeAction(this.form);" value="Go" /></td>
        </tr>
        <input name="num_cols" type="hidden" value="', (int)$_POST['num_cols'], '" />
        <input name="tbl_name" type="hidden" value="', urlencode($_POST['tbl_name']), '" />
      </table>
    </form>
    <p class="lil_msg">
      * Not required or usable on all data types<br />
      ** The default value will be escaped with <a href="http://www.php.net/sqlite_escape_string" target="_blank">sqlite_escape_string</a><br />
      *** If you want a column to Auto Increment, select it as the ONLY Primary Key and a data type of INTEGER.
    </p>
  </div>';

    template_footer();
  }
}

# Builds a list of data types... to make it easy :P
function buildDataTypeList($i = 0, $selected = '')
{
  # An array of valid ones...
  $types = array('INTEGER', 'INT', 'SMALLINT', 'FLOAT', 'NUMERIC', 'TIMESTAMP', 'DATE', 'TEXT', 'BLOB', 'CLOB', 'VARCHAR', 'NVARCHAR');

  $return = '<select name="datatype['. $i. ']">';

  foreach($types as $type)
  {
    if(strtoupper($selected) == $type)
      $return .= '<option value="'. $type. '" selected="yes">'. $type. '</option>';
    else
      $return .= '<option value="'. $type. '">'. $type. '</option>';
  }

  $return .= '</select>';

  return $return;
}

# Our template functions... This one is quite obviously our header XD
function template_header($title = '', $show_q = true)
{
  global $config;

  echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>phpLiterAdmin ', $title ? '&raquo; '. $title : '', '</title>
	<meta name="robots" content="noindex"/>
	<script language="javascript" type="text/javascript">
  function faq(url)
  {
	  newwindow = window.open(url, \'name\', \'height=200, width=150\');
	  if (window.focus)
	  {
	    newwindow.focus();
	  }
	  return false;
  }
  function select_tables(input)
  {
    first = 0;
    for(i = 0; i < input.length; i++) 
    {
      if(input[i].type == "checkbox")
      {
        if(first == 0)
          first = i;
        else
        {
          if(input[i].checked)
            input[i].checked = \'\';
          else
            input[i].checked = \'checked\';
        }
      }
    }
  }
  function select_rows(input)
  {
    first = 0;
    for(i = 0; i < input.length; i++) 
    {
      if(input[i].type == "checkbox")
      {
        if(first == 0)
          first = i;
        else if(input[i].name.substring(0, 1) == "s")
        {
          if(input[i].checked)
            input[i].checked = \'\';
          else
            input[i].checked = \'checked\';
        }
      }
    }
  }
  function clear_input(input_id)
  {
    handle = document.getElementById(input_id);
    handle.value = \'\';
  }
  function select_form(formHandle, what)
  {
    if(what == \'edit\')
    {
      formHandle.action += \'?act=edit_rows\';
    }
    else
    {
      // Make sure you want to do this :P
      if(confirm(\'Are you sure you want to delete the selected rows?\nThis cannot be undone!\'))
      {
        formHandle.action += \'?act=delete_rows\';
        return true;
      }
      else
        return false;
    }
  }
  function changeAction(handle)
  {
    handle.action += \'?act=create&amp;table\';
  }
  function check_query(handle)
  {
    query = handle.q.value;

    // You sure? :P
    if((query.substring(0, 4)).toUpperCase() == "DROP" || (query.substring(0, 6)).toUpperCase() == "DELETE")
      return confirm(\'Are you sure you want to continue?\');

    return true;
  }
  </script>
  <style type="text/css">
    *
    {
      margin: 0px;
      padding: 0px;
    }
    body
    {
      font-family: Verdana, Arial, Helvetica, sans-serif;
      font-size: 12px;
      background: #E5E1C6;
    }
    #header
    {
      background: #5E9FBF;
      padding: 5px;
    }
    #header #left
    {
      padding-left: 5px;
      float: left;
    }
    #header #right
    {
      margin-top: 10px;
      padding-right: 5px;
      float: right;
    }
    #header a
    {
      color: #ffffff;
      text-decoration: none;
    }
    #header a:hover
    {
      text-decoration: underline;
    }
    a
    {
	    color: #006699;
    }
    a:hover
    {
      color: #0099CC;
    } 
    #login_area
    {
      width: 250px;
      padding: 10px;
      margin-top: 10%;
      margin-right: auto;
      margin-left: auto;
      background: #ffffff;
      border: 1px solid #D5D1B8;
    }
    #con_error
    {
      width: 700px;
      padding: 10px;
      margin-right: auto;
      margin-left: auto;
      background: #ffffff;
      border: 1px solid #D5D1B8;
      color: #CC0000;
      text-align: center;
    }
    #info_center
    {
      width: 99%;
      padding: 3px;
      background: #FFFFFF;
      margin-bottom: 10px;
      border: 1px solid #D5D1B8;
    }
    #export
    {
      width: 400px;
      padding: 10px;
      margin-top: 20px;
      margin-right: auto;
      margin-left: auto;
      background: #ffffff;
      border: 1px solid #D5D1B8;
    }
    #import
    {
      width: 400px;
      padding: 10px;
      margin-top: 20px;
      margin-right: auto;
      margin-left: auto;
      background: #ffffff;
      border: 1px solid #D5D1B8;
    }
    #insert
    {
      width: 650px;
      padding: 10px;
      margin-right: auto;
      margin-left: auto;
      background: #ffffff;
      border: 1px solid #D5D1B8;
      margin-top: 30px;
    }
    #edit_rows
    {
      width: 650px;
      padding: 10px;
      margin-right: auto;
      margin-left: auto;
      background: #ffffff;
      border: 1px solid #D5D1B8;
      margin-top: 30px;
    }
    #server_info
    {
      width: 400px;
      padding: 10px;
      margin-right: auto;
      margin-left: auto;
      background: #ffffff;
      border: 1px solid #D5D1B8;
      margin-top: 30px;
    }
    #server_info .var
    {
      font-weight: bold;
    }
    #server_info table td
    {
      text-align: center;
    }
    #lockdown
    {
      width: 500px;
      padding: 10px;
      margin-top: 20px;
      margin-right: auto;
      margin-left: auto;
      background: #ffffff;
      text-align: center;
      border: 1px solid #D5D1B8;
    }
    #create_table
    {
      width: 650px;
      padding: 10px;
      margin-top: 20px;
      margin-right: auto;
      margin-left: auto;
      background: #ffffff;
      border: 1px solid #D5D1B8;
    }
    #pragma
    {
      width: 500px;
      padding: 10px;
      margin-top: 20px;
      margin-right: auto;
      margin-left: auto;
      background: #ffffff;
      border: 1px solid #D5D1B8;
    }
    #powered_by
    {
      text-align: right;
      font-weight: bold;
      padding-right: 5px;
    }
    .break
    {
      clear: both;
    }
    .bold
    {
      font-weight: bold;
    }
    h1
    {
      color: #005784;
      font-size: 16px;
    }
    .error
    {
      text-align: center;
      color: #CC0000;
    }
    th
    {
      background: #0C608C;
      padding: 1px 8px 1px 8px;
      color: #ffffff;
    }
    th.checkbox
    {
      padding: 0px;
    }
    tr.tr_1
    {
      background: #FFFFFF;
    }
    tr.tr_2
    {
      background: #97C0D4;
    }
    td
    {
      padding: 2px 4px 2px 4px;
    }
    .left
    {
      text-align: left;
    }
    .center
    {
      text-align: center;
    }
    .right
    {
      text-align: right;
    }
    .insert
    {
      text-align: center;
      font-weight: bold;
    }
    .lil_msg
    {
      font-size: 10px;
      text-align: center;
    }
    .struc
    {
      padding: 0px;
      width: 20px;
    }
  </style>
</head>
<body>
  <div id="header">
    <div id="left">
      <table>
        <tr>
          <td><p><a href="http://phpliteradmin.googlecode.com/" class="bold" target="_blank" title="phpLiterAdmin, the better SQLite Manager">phpLiterAdmin v', $config['version'], '</a></p></td>', db_list(), '
        </tr>
      </table>
    </div>
    <div id="right">';

    # No menu for you if you aren't logged in :P
    if($config['is_logged'])
      echo '<p><a href="', $_SERVER['PHP_SELF'], '">Show Tables</a> | <a href="', $_SERVER['PHP_SELF'], '?act=create" title="Create a table">Create a table</a> | <a href="', $_SERVER['PHP_SELF'], '?act=export" title="Export database">Export</a> | <a href="', $_SERVER['PHP_SELF'], '?act=import" title="Import database">Import</a> | <a href="', $_SERVER['PHP_SELF'], '?act=server_info" title="Server information">Server Info</a> | <a href="', $_SERVER['PHP_SELF'], '?act=logout" title="Logout">Logout</a></p>';

  echo '    
    </div>
    <div class="break">
    </div>
  </div>';

  # Don't show the query box if it is not request or if they are not logged in ;)
  if($config['is_logged'] && $show_q)
    echo '
  <br /><br /><br />
  <div align="center">
    <p>SQLite queries to run through the database: (Queries separated by semicolons) [<a href="javascript:void(0);" onClick="return faq(\'', $_SERVER['PHP_SELF'], '?act=help&amp;faq=query\');">?</a>]</p>
      <form action="', $_SERVER['PHP_SELF'], '" method="post">
        <textarea id="q_input" name="q" rows="10" cols="70">', htmlspecialchars($_REQUEST['q'], ENT_QUOTES), '</textarea>
        <table>
          <tr>
            <td><input type="button" onClick="clear_input(\'q_input\');" value="Clear"/></td>
            <td><input name="go" type="submit" onClick="return check_query(this.form);" value="Process Queries!"/></td>
            <td><input name="fulltext" id="fulltext" type="checkbox" value="1" ', !empty($_REQUEST['fulltext']) ? 'checked="checked" ' : '', '/> <label for="fulltext">Show Full texts</label> [<a href="javascript:void(0);" onClick="return faq(\'', $_SERVER['PHP_SELF'], '?act=help&amp;faq=fulltexts\');">?</a>]</td>
          </tr>
          <input name="act" type="hidden" value="query"/>
        </table>
      </form>
  </div>
  <div id="main">';
}

# The template footer...
function template_footer()
{
  global $config, $start_time;

  echo '
  <br />
  <div id="powered_by">
    <p>Powered by <a href="http://phpliteradmin.googlecode.com/" target="_blank">phpLiterAdmin</a> v', $config['version'], ' by <a href="http://nosql.110mb.com/" target="_blank">NoSQL</a> | Page generated in ', round(microtime(true) - $start_time, 5), ' seconds.</p>
  </div>
  </div>
</body>
</html>';  
}

# FAQ Function, usefulness!
function print_help()
{
  $faq = !empty($_REQUEST['faq']) ? $_REQUEST['faq'] : '';
  faq_header();

  if($faq == 'debug_mode')
  {
    echo '
    <h1>Debug Mode</h1>
    <p>Debug Mode isn\'t exactly "debugging", what it does is sanitize the data retrieved from the database with <a href="http://www.php.net/sqlite_escape_string" target="_blank">sqlite_escape_string</a>. It is recommended to have this option selected.</p>';
  }
  elseif($faq == 'drop_table')
  {
    echo '
    <h1>DROP TABLEs</h1>
    <p>If you choose this option, it will include DROP TABLE table; if the table does not exist upon import, you may get errors, though you can always delete these later.</p>';
  }
  elseif($faq == 'export_data')
  {
    echo '
    <h1>Export Data</h1>
    <p>Choosing this option when you export will include the actual data included in your tables, the INSERT SQLite command in other words, this is the import stuff. <br />If not sure, leave this option checked</p>';
  }
  elseif($faq == 'export_struc')
  {
    echo '
    <h1>Export Structure</h1>
    <p>This will, upon exporting, contain the structure of your tables, such as CREATE TABLE, CREATE INDEX, CREATE UNIQUE, etc.<br />If you are not sure, leave this option checked.</p>';
  }
  elseif($faq == 'transaction')
  {
    echo '
    <h1>Add TRANSACTIONs</h1>
    <p>This is a command that almost all Structured Query Languages (<a href="http://mysql.com/" target="_blank">MySQL</a>, <a href="http://www.microsoft.com/sql/" target="_blank">MSSQL</a> etc.) have. Putting it simply, it is an <em>All</em> or <em>Nothing</em> option, if when queries are being executed, and 1 error occurs, all data already inserted (inside the transaction) is removed!</p>';
  }
  elseif($faq == 'query')
  {
    echo '
    <h1>Query Box</h1>
    <p>In this box you can run queries against your SQLite database, you can do multiple Queries be separating them by semicolons.<br /><br /><a href="http://nosql.110mb.com/page.php?p=2" target="_blank">Need a Tutorial?</a></p>';
  }
  elseif($faq == 'fulltexts')
  {
    echo '
    <h1>Show Full texts</h1>
    <p>Sometimes fields have a lot of information in them... In order to make the page look cleaner, you can leave this option unchecked and not all of the value is displayed. It is chopped off so it won\'t take up so much room. However, if you wish to view it all simply check this box.</p>';
  }
  elseif($faq == 'zlib')
  {
    echo '
    <h1><a href="http://www.php.net/zlib" target="_blank">Zlib</a> Support</h1>
    <p>Zlib is required by phpLiterAdmin for gzipping backups and extracting them from gzips. While phpLiterAdmin will work just fine without it, its always a good idea to have it enabled ;)<br /><br />Zlib appears to be <strong>', function_exists('ob_gzhandler') ? 'enabled' : 'disabled', '</strong>.</p>';
  }

  faq_footer();
}

/* FAQ Layout */
function faq_header()
{
  echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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
function faq_footer()
{
  echo '
  <p><a href="javascript:window.close();">Close</a></p>
</body>
</html>';
}

# I hate Magic quotes... ._.
# Its to help stop noobs from SQL Injections but you know
# thats kind of a problem with phpLiterAdmin because it needs
# to inject into your database XD lol.
function fixMagic()
{
  global $_COOKIE, $_GET, $_POST, $_REQUEST, $_SERVER;

  # So is it even on?
  if((function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc() == 1) || @ini_get('magic_quotes_sybase'))
  {
    # Do some magic on it XD
    $_COOKIE = wizardMagic($_COOKIE);
    $_GET = wizardMagic($_GET);
    $_POST = wizardMagic($_POST);
    $_REQUEST = wizardMagic($_REQUEST);
    $_SERVER = wizardMagic($_SERVER);
  }
}

# This assists fixMagic(); in undoing the EVIL!
function wizardMagic($array)
{
  # Make a temporary new array...
  $new_array = array();
  foreach($array as $key => $value)
  {
    $key = stripslashes($key);
    # If its not an array, add it :)
    if(!is_array($value))
      $new_array[$key] = stripslashes($value);
    else
    {
      # Its an array... We could do recursion, but phpLiterAdmin
      # Only needs to an array at 1 deep :P
      $new_array[$key] = array();
      foreach($value as $sub_key => $sub_value)
      {
        $new_array[$key][stripslashes($sub_key)] = stripslashes($sub_value);
      }
    }
  }

  # Return the new array :)
  return $new_array;
}

# Our SQL Query function... It makes it a bit faster for us :D
function sql_query($db_query, &$query_error = null, &$time_taken = 0)
{
  global $config;

  # Start to take time XD
  $start_time = microtime(true);

  # Query it...
  $result = @sqlite_query($config['con'], $db_query, SQLITE_BOTH, $query_error);

  $time_taken = microtime(true) - $start_time;

  # Return the result...
  return $result;
}
?>