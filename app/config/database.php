<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$active_group = 'default';
$active_record = TRUE;

$db_host = '127.0.0.1';
$db_user = 'wahz';
$db_passwd = 'wahz!#123!!';



$common_db_config = array(
        "hostname"  =>  $db_host,   "username"  => $db_user,    "password"  => $db_passwd,
        "dbdriver"  =>  "mysql",    "dbprefix"  =>  "",
        "pconnect"  =>  FALSE,      "db_debug"  =>  TRUE,       "cachedir"  =>  "",
        "char_set"  =>  "utf8",     "dbcollat"  =>  "utf8_general_ci",
        "swap_pre"  =>  "",         "autoinit"  =>  TRUE,       "stricton"  =>  FALSE);



$db['wahz'] = array_merge(array( "database"  =>  "wahz" ), $common_db_config);
