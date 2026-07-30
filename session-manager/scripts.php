<?php
function ConnectPDO () {
    $db = new PDO('mysql:host='.MYSQL_HOST.';port='.MYSQL_PORT.';dbname='.MYSQL_DATABASE.';charset=utf8', MYSQL_USER, MYSQL_PASSWORD);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;
}
function HandleExceptionPDO($e) {
    print '<div class="alert">'.PHP_EOL;
    print '<p>An Error occured on <b>'.$e->getFile().' line '.$e->getLine().'</b></p>'.PHP_EOL;
    print '<p>'.$e->getMessage().'</p>'.PHP_EOL;
    print '</div>'.PHP_EOL;
}

function ShowEntries ($init, $offset=0, $entries_per_page=60, $and_where="", $hour_focus="") {

    if (is_object($and_where)) {
        $and_where_string = $and_where->AndWhereString();
    }
    else {
        $and_where_string = "";
    }

    try {
        $db = ConnectPDO();
        $q = 'SELECT `session`.*,sum(`number`) as Counts FROM `session`,`count` WHERE session.fk_initiative = :init AND session.id = count.fk_session '.$and_where_string.' GROUP BY fk_session ORDER BY `session`.`start` DESC LIMIT :offset, :entries_per_page';
        $stmt = $db->prepare($q);
        $stmt->bindParam(':init', $init, PDO::PARAM_INT);
        if (is_object($and_where))  {
            $stmt = $and_where->Bind($stmt);
        }
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':entries_per_page', $entries_per_page, PDO::PARAM_INT);
        $stmt->execute();
        
        //display forward and back controls by date or by sessions
        if (isset($_REQUEST['date_search'])) {
            print '<h2>Sessions for '.date("l, F j, Y",strtotime($_REQUEST['date_search'])).'</h2>'.PHP_EOL;
            list ($year, $month, $day) = preg_split ("/\-/", $_REQUEST['date_search']);
            if (isset($day) && strlen($day)==2) {
                $date = $_REQUEST['date_search'];
                $next_date= date("Y-m-d", strtotime($date . '+ 1 day'));
                $previous_date= date("Y-m-d", strtotime($date . '- 1 day'));
            }
            else { $date = ''; } // don't set date if not a fully qualified date
            
            print '<form action="?" method="post"><input type="hidden" name="date_search" value="'.$previous_date.'"><input type="submit" value="&laquo; '.$previous_date.'" class="button"></form>'.PHP_EOL;
//            print '<a target="suma_analysis" href="'.SUMA_REPORTS_URL.'/#/hourly?id='.$init.'&sdate='.$date.'&edate='.$date.'&classifyCounts=count&wholeSession=no&zeroCounts=no&requireActs=&excludeActs=&requireActGrps=&excludeActGrps=&excludeLocs=&days=mo,tu,we,th,fr,sa,su" class="button">Examine Day in Suma Reports: '.$date.'</a>'.PHP_EOL;
            $suma_day_url = SUMA_REPORTS_URL.'/#/hourly?id='.$init.'&sdate='.$date.'&edate='.$date.'&classifyCounts=count&wholeSession=no&zeroCounts=no&requireActs=&excludeActs=&requireActGrps=&excludeActGrps=&excludeLocs=&days=mo,tu,we,th,fr,sa,su';
            print '<form><input type="button" class="button" id="suma-day-link" value="Examine Day in Suma Reports: '.$date.'" data-url="'.$suma_day_url.'"></form>'.PHP_EOL;            
            print '<form action="?" method="post"><input type="hidden" name="date_search" value="'.$next_date.'"><input type="submit" value="'.$next_date.' &raquo;" class="button"></form>'.PHP_EOL;
        }
        else {
            $next_offset_older = $offset+$entries_per_page;
            print '<form action="?" method="post"><input type="hidden" name="offset" value="'.$next_offset_older.'"><input type="submit" value="&laquo; Previous '.$entries_per_page.' Entries" class="button"></form>'.PHP_EOL;
            
            if ($offset > 0) { 
                $next_offset_newer = $offset-$entries_per_page;
                if ($next_offset_newer < 0) { $next_offset_newer = 0; }
                print '<form action="?" method="post"><input type="hidden" name="offset" value="'.$next_offset_newer.'"><input type="submit" value="Next Newer '.$entries_per_page.' Entries &raquo;" class="button"></form>'.PHP_EOL;
            }
        }
        
        // Display Date-Picker
?>
<form id="date-select-form">
     <input type="button" id="dp-text" value="Select Any Date" class="button"><input type="hidden" id="datepicker" />
<input type="hidden" id="date-search" name="date_search" />
</form>
  <?php      
        $rows = "";
        $header = "";
        $headers = array();
        while ($myrow = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $headers = array_keys($myrow);
            if (isset($hour_focus) && (preg_match("/$hour_focus/", $myrow['start']))) {
                $class = ' class="hour-focus"';
            }
            else { $class =''; }
            
            $rows .= ' <tr'.$class.'>'.PHP_EOL;
            foreach ($headers as $k) {
                $rows .= '  <td class="'.$k.'">'.$myrow[$k].'</td>'.PHP_EOL;
            }
            if ($myrow['deleted'] == 0) {
                $rows .= '<td><form action="?" method="post"><input type="hidden" name="action" value="delete_session"><input type="hidden" name="session_id" value="' .$myrow['id'] .'">'.HiddenFieldsForDateSearch().'<input type="submit" value="Delete" class="button"></form></td>'.PHP_EOL;
            }
            elseif ($myrow['deleted'] == 1) {
                $rows .= '<td><form action="?" method="post"><input type="hidden" name="action" value="undelete_session"><input type="hidden" name="session_id" value="' .$myrow['id'] .'">'.HiddenFieldsForDateSearch().'<input type="submit" value="Undelete" class="button"></form></td>'.PHP_EOL;
            }
            $rows .= '  <td><form action="?" method="post"><input type="hidden" name="action" value="move_session"><input type="hidden" name="session_id" value="' .$myrow['id'] .'">'.HiddenFieldsForDateSearch().'<input type="hidden" name="transaction_id" value="'. $myrow['fk_transaction'] .'">Adjust Time by: ' . DisplayAdjustor() . '</form></td>'. PHP_EOL;
            $rows .= ' </tr>'.PHP_EOL;
        } // end foreach query as myrow
        $header = join('</th><th>',$headers);
        $header = '<tr><th>'.$header.'</th></tr>'.PHP_EOL;
        $rows = '<table>'. $header . $rows .'</table>'.PHP_EOL;
        print ($rows);
    } catch(PDOException $e) {
        HandleExceptionPDO($e);
    }
} //end function ShowEntries

function DisplayAdjustor() {
    global $adjust_time_options;
    $opts = $adjust_time_options;
    $select = "<option>Choose One:</option>\n";

    foreach ($opts as $disp => $val) {
        $select .= "<option value=\"$val\">$disp</option>\n";
    }

    return '<select class="row-select" name="time_shift">'.$select.'</select> <button class="adjust-time button">Go</button>'.PHP_EOL; 
}

function HiddenFieldsForDateSearch() { //used by ShowEntries
    $fields = "";
    if (isset($_REQUEST['date_search'])) {
        $fields = '<input type="hidden" name="date_search" value="'.$_REQUEST['date_search'].'">'.PHP_EOL;
    }
    if (isset($_REQUEST['hour_focus'])) {
        $fields .= '<input type="hidden" name="hour_focus" value="'.$_REQUEST['hour_focus'].'">'.PHP_EOL;
    }
    return $fields;
}

function MoveSession($session_id, $transaction_id, $time_shift) {
    list($action, $hms) = split(" ", $time_shift);
    try {
        $db = ConnectPDO();
        
        $q1 = 'UPDATE `session` SET `start` = '.$action.' (`start`, :hms), `end` = '.$action.' (`end`, :hms) WHERE `id` = :session_id';
        $stmt = $db->prepare($q1);
        $stmt->bindParam(':hms', $hms, PDO::PARAM_STR);
        $stmt->bindParam(':session_id', $session_id, PDO::PARAM_STR);
        if ($stmt->execute()) { print '<li>SUCCESS: Updated session table</li>'.PHP_EOL;}
        else { print ($stmt->errorCode()); }

        $q2 = 'UPDATE `transaction` SET `start` = '.$action.' (`start`, :hms), `end` = '.$action.' (`end`, :hms) WHERE `id` = :transaction_id';
        $stmt = $db->prepare($q2);
        $stmt->bindParam(':hms', $hms, PDO::PARAM_STR);
        $stmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_STR);
        if ($stmt->execute()) { print '<li>SUCCESS: Updated transaction table</li>'.PHP_EOL;}
        else { print ($stmt->errorCode()); }

        $q3 = 'UPDATE `count` SET `occurrence` = '.$action.' (`occurrence`, :hms) WHERE `fk_session` = :session_id';
        $stmt = $db->prepare($q3);
        $stmt->bindParam(':hms', $hms, PDO::PARAM_STR);
        $stmt->bindParam(':session_id', $session_id, PDO::PARAM_STR);
        if ($stmt->execute()) { print '<li>SUCCESS: Updated count table</li>'.PHP_EOL;}
        else { print ($stmt->errorCode()); }
    } catch(PDOException $e) {
        HandleExceptionPDO($e);
    }
}


function DeleteUndelete($action, $id) {
    if ($action == "delete") { $dvalue = 1; }
    elseif ($action == "undelete") { $dvalue = 0; }
    
    try {
        $db = ConnectPDO();
        $q = 'UPDATE session SET `deleted` = :dvalue WHERE `id` = :id';
        $stmt = $db->prepare($q);
        $stmt->bindParam(':dvalue',$dvalue, PDO::PARAM_INT);
        $stmt->bindParam(':id',$id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            print '<p>SUCCESS: '.$q.'</p>'.PHP_EOL;
        }
        else {
            print '<p>FAILED TO EXECUTE: '. $q .'</p>'.PHP_EOL;
        }
    } catch(PDOException $e) {
        HandleExceptionPDO($e);
    }
} //end function DeleteUndelete


function ShowMultiHours($init) {
    try { 
        $db = ConnectPDO();
        $q = "SELECT CONCAT( DATE(`start`) , ' ', HOUR(`start`) ) AS DateHour, count( * ) AS HourCount FROM `session` WHERE fk_initiative = '".$init."' and deleted = 0 GROUP BY HOUR(`start`) , DATE(`start`) HAVING HourCount > 1 ORDER BY DateHour DESC";
        $stmt = $db->prepare($q);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
        print '<p>No hours with multiple entries found</p>';
        }
        else {
            while ($myrow = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (! ($headers)) 
                    $headers = array_keys($myrow);
                $rows .=  '<tr>'.PHP_EOL;
                foreach ($headers as $k) {
                    if ($k == "DateHour") {
                        list ($date,$hour) = preg_split("/ /",$myrow[$k]);
                        if ($hour < 10) { $display_hour = '0'.$hour; }
                        else { $display_hour = $hour; }
                        $myrow[$k] = '<a href="?date_search='.$date.'&hour_focus='.$date.' '.$display_hour.'">' .$date. ' '.$display_hour.'00</a>';
                    }
                    $rows .= '  <td class="'.$k.'">'.$myrow[$k].'</td>'.PHP_EOL;
                }
                $rows .= ' </tr>'.PHP_EOL;
            } // end while myrow
            $header = join('</th><th>',$headers);
            $header = '<tr><th>'.$header.'</th></tr>'.PHP_EOL;
            if ($table_id != '') { $id = ' id="'.$table_id.'"'; }
            $rows = '<table id="multi-hours">'.$header.$rows.'</table>'.PHP_EOL;
            print($rows);
        }
    } catch(PDOException $e) {
        HandleExceptionPDO($e);
    }
} //end ShowMutliHours


function SelectInitiative($default_init) {
$url = SUMASERVER_URL . "/clientinit";
if ($json = file_get_contents($url)) {
$response = json_decode($json);
$opts = " <option value=\"\">Select an initiative</option>\n";
foreach ($response as $init) {
    if ($init->initiativeId == $default_init)  {
        $selected=" selected";
    }
    else { $selected = ""; }
$opts.=' <option value="'. $init->initiativeId.'"'.$selected.'>'. $init->initiativeTitle .'</option>\n';
}
$select = "<label for=\"initiative\">Initiative</label> <select name=\"initiative\" id=\"initiative-selector\">\n$opts</select>\n";
return ($select);
} //end if good response
else {
return '<div class="alert"><h3>Unable to connect to Suma Server</h3><p>Unable to connect to the Suma Server using the url defined as <strong>$sumaserver_url = '.$sumaserver_url.'</strong> in the <strong>config.php</strong> file. Please check this url.</p> <p>A useful test of correctness is this: if you can add <strong>/clientinit</strong> to the url, you should get a list of your suma initiatives, e.g. <a href="'.$url.'">'.$url.'</a>.' . PHP_EOL;
} //end if unable to reach sumaserver
} //end function SelectInitiative

function PostJSON ($url, $json) {
  if (function_exists('curl_version')) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					       'Content-Type: application/json', 
					       'Content-Length: ' . strlen($json),
					       'User-Agent: Suma-Session-Manager',
					       )
		); 
    $result = curl_exec($ch);
    return $result;
  } //end if curl_version exists
  else {
    return "CURL is not available in this PHP installation";
  }
} //end function PostJSON

function RenderMarkdown ($text) {
  if (function_exists('curl_version')) {
    $api="https://api.github.com/markdown";
    $array = array ( "mode" => "markdown",
		     "text" => $text
		     );
    $json = json_encode($array);
    $html = PostJSON($api, $json);
    return $html;
  }
  else { return "<pre>$text</pre>"; }
}

    function CheckInstall () {
    $installation_problem = false;
    $errors = "";
    if (! is_readable("config.php")) {
        $errors .= '<div class="alert"><h3>Config file not readable</h3><p>The file <strong>config.php</strong> is not present or not readable. Please copy the file <strong>config-example.php</strong> to <strong>config.php</strong> and add your local Suma Server URL to activate this service.</p></div>';
        $installation_problem = true;
    }
    else { 
        $required_constants = array ('SUMASERVER_URL','MYSQL_HOST','MYSQL_DATABASE','MYSQL_USER', 'MYSQL_PASSWORD');

        foreach ($required_constants as $k) {
            if (! defined($k) || constant($k) == ""){
                $errors .= '<div class="alert"><h3>'.$k.' not set</h3><p>The <strong>'.$k.'</strong> constant in <strong>config.php</strong> is not set. Please set this constant in order to use the service.</p></div>';
                $installation_problem = true;
            }
        } //end foreach
    } //end else if config found
    
    $result = array ("installation_problem" => $installation_problem,
                     "errors" => $errors);
    return $result;
} //end CheckInstall


?>