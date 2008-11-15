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

# This file simplifies SQL Queries because of
# well, I want to, and makes it more convienient :D

function db_connect() {
global $con, $is_logged, $query_error, $settings;
  # They logged in..?
  if($is_logged) {
    # Before we connect we might
    # need to switch the database
    # we are using, maybe ;)
    if(!empty($_REQUEST['proc_db_switch'])) {
      $_SESSION['current_db'] = !empty($_REQUEST['switch_db']) ? htmlspecialchars_decode($_REQUEST['switch_db'], ENT_QUOTES) : '';
    }
    # Connect to the SQLite DB :)
    $db_name = !empty($_SESSION['current_db']) ? $_SESSION['current_db'] : '';
    $con = @sqlite_open($db_name, 0666, $query_error);
  }
}

# Queries the database ;)
function db_query($query) {
global $con, $num_queries, $query_error;
  # Query with the connection and all :)
  $result = @sqlite_query($con, $query, SQLITE_ASSOC, $query_error);
  # Increment query count..?
  if(!isset($num_queries))
    $num_queries = 0;
  $num_queries++;
  if(!$result)
    return false;
  else
    return $result;
}

# The SQLite error that has happened during
# this page load, if one has happened...
function db_error($error_no = false) {
global $con, $query_error;
  # If false, the one from this page...
  if($error_no === false) {
    # If nothing, return false
    if(empty($query_error))
      return false;
    else
      return $query_error;
  }
  else {
    # An SQLite Number Error..?
    # But if db_error = 0, the last one
    if($error_no == 0)
      $error_no = sqlite_last_error($con);
    return sqlite_error_string($error_no);
  }
}

# Close the connection...
function db_close() {
global $con;
  sqlite_close($con);
}

# Escape the string with sqlite_escape_string
function db_escape_string($str) {
  return sqlite_escape_string($str);
}

# This executes multiple SQLite Queries with
# one simple function, isn't SQLite Cool!?
function db_exec($queries) {
global $con, $query_error;
  $result = sqlite_exec($con, $queries, $query_error);
  # Return true or false :)
  return (bool)$result;
}

# Fetches the array from a given result from a query
function db_fetch_array($result) {
  return sqlite_fetch_array($result, SQLITE_BOTH);
}

# Fetches the assoc. from a given result, sorta like
# fetch_array just the fact it won't include number
# indices :) I recommend using this and not fetch_array
function db_fetch_assoc($result) {
  # PHP doesn't have an exact function for this :P
  return sqlite_fetch_array($result, SQLITE_ASSOC);
}

# Gets the column types from a given table ;)
function db_fetch_types($tbl_name) {
global $con;
  return sqlite_fetch_column_types($tbl_name, $con, SQLITE_ASSOC);
}

# Gets the field name from a resource
function db_field_name($result, $index = 0) {
  return sqlite_field_name($result, $index);
}

# The last insert row ID, you never know ;)
function db_last_insert_id() {
global $con;
  return sqlite_last_insert_rowid($con);
}

# The encoding of your SQLite Library Encoding
function db_encoding($strtoupper = true) {
  $extra_func = '';
  if($strtoupper)
    $extra_func = 'strtoupper';
  return $extra_func(sqlite_libencoding());
}

# Your SQLite Version
function db_version() {
  return sqlite_libversion();
}

# Number of fields from a given result set
function db_num_fields($result) {
  return sqlite_num_fields($result);
}

# Number of rows from a result
function db_num_rows($result) {
  return sqlite_num_rows($result);
}
?>