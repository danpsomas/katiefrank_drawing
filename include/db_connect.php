<?php  
//   CONNECT TO DATABASE
require_once 'db_ini.php';


$mysqli = new mysqli($server_info['db_host'], $server_info['db_user'], $server_info['db_pass'], $server_info['db_name']);
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}


?>