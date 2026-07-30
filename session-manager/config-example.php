<?php
define ("DEBUG", false); // if DEBUG==true, display some diagnostic info
define ("SUMASERVER_URL", ""); // full url with no trailing slash, e.g. http://www.example.com/sumaserver, see note about sumaserver security in README.md file
define ("SUMA_REPORTS_URL", ""); // full url with no trailing slash, e.g. http://www.example.com/suma/analysis/reports, see note about sumaserver security in README.md file
define ("MYSQL_HOST", "");     //enter value as second argument
define ("MYSQL_PORT", "");     //enter value as second argument
define ("MYSQL_DATABASE", ""); //enter value as second argument
define ("MYSQL_USER", "");     //enter value as second argument
define ("MYSQL_PASSWORD", ""); //enter value as second argument


/*
Select a JQueryUI Theme. Any of the followint themes may be used:
cupertino, flick, hot-sneaks, humanity, overcast, pepper-grinder, redmond, smoothness, south-street, start,  sunny, ui-lightness
*/
$ui_theme = "pepper-grinder";


$default_init   = "";
$entries_per_page = 100;

/* 
   if $prevent_datepicker_future is true or is not set, the "Select Any Date" 
   calendar will not allow user to select a date in the future
*/
$prevent_datepicker_future = true; 

/*
  If an initiative is intended to usually only have one count per hour, 
  include it in the $one_per_hour_inits array to allow searching on hours
  with multiple entries.
  example: $one_per_hour_inits = array(1,4);
*/

$one_per_hour_inits = array();

/*
  You can use Suma Session Manager to adjust the time of previously-entered
  sessions. The following array controls what options you are given for 
  adjusting the time. You may add, delete or comment-out lines as you wish.
  
  The array values (e.g. "subtime 04:00:00" are given as arguments sent to
  MySQL and are formatted to give a MySQL command. The amount of time to be
  changed is given in HH:MM:SS format. 
 */

$adjust_time_options = array (
                                "-4 hrs"  => "subtime 04:00:00",
                                "-3 hrs"  => "subtime 03:00:00",
                                "-2 hrs"  => "subtime 02:00:00",
                                "-60 min" => "subtime 01:00:00",
                                "-30 min" => "subtime 00:30:00",
                                "-15 min" => "subtime 00:15:00",
                                "-10 min" => "subtime 00:10:00",
                                "-5 min"  => "subtime 00:05:00",
                                "+5 min"  => "addtime 00:05:00",
                                "+10 min" => "addtime 00:10:00",
                                "+15 min" => "addtime 00:15:00",
                                "+30 min" => "addtime 00:30:00",
                                "+60 min" => "addtime 01:00:00"
                                );
?>