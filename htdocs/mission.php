<?php 

include("report_arginit.php");

//all information about a given mission


//boilerplate input validation
$mid = "";

if (isset($GET->mission_id))
{
    $mid = $GET->mission_id->int;
}

$title = "Mission $mid";
include("report_header.php"); 

if (!isset($GET->mission_id))
{
    echo "<h1>You didn't specify a mission ID</h1>\n";
    include("report_footer.php");
    die();
}

//actual printing of the page starts here

$r = $dbo->mission->ID($mid);
$thedate = date("l M j, Y", strtotime($r->date));

$a = a("missiongroup.php",
        "group_id", "{$r->date},{$r->location},{$r->vehicle_name}",
        "mission_id", NULL);

echo "<h2>Mission $mid - {$r->label}</h2>\n";
echo "<h3>$thedate - ";
echo "<a href=\"$a\">{$r->vehicle_name} at {$r->location}</a>";
echo " ({$r->time})</h3>\n";

$r2d = 180 / pi();



echo makeNotesHtml();

//print camera info if it exists
$q = "
    select count(*) 
    from app_messages 
    where varname='ICAMERA_SAVEDFILE' 
      and mission_id = $mid
    ";

if (0 < $db->getOne($q))
{
    echo "<br />";

    $a = "contactsheet.php?mission_id=$mid&showevery=";
    echo "<h3><img src='/img/icon_camera.png' style='vertical-align:middle;'/> ";
    echo "<a href='{$a}1'>Image Contact Sheet</a> ";
    echo "<a href='{$a}2'>/2</a> ";
    echo "<a href='{$a}4'>/4</a> ";
    echo "<a href='{$a}8'>/8</a> ";
    echo "<a href='{$a}16'>/16</a> ";
    echo "<a href='{$a}32'>/32</a> ";
    echo "</h3>";

    echo makeCameraHtml();


    echo "<br />";

}

echo "<hr />";



//start the form for multiple charts on a page
echo "<form action='manycharts.php' method='get' >\n";
echo "<input type='hidden' name='mission_id' value='$mid' />\n";



// the format of this page is meant to be easy to edit to suit your 
//  needs in the field -- adding, updating, or removing calls to 
//  the makeClickChartHtml function as needed.  




///////////////// NAV

echo makeClickChartHtml("Nav + GPS", "icon_nav.png",
                        a("/charts/nav.php",
                            "group_id", NULL,
                            "xypairs", array("NAV_X:NAV_Y", "GPS_X:GPS_Y"),
                            "waypoints", array("WAYPOINT_X:WAYPOINT_Y")),
                        850, 650); 


echo makeClickChartHtml("Nav X vs Nav Y", "icon_nav.png",
                        a("/charts/nav.php",
                            "group_id", NULL,
                            "xypairs", array("NAV_X:NAV_Y"),
                            "waypoints", array("WAYPOINT_X:WAYPOINT_Y")),
                        850, 650); 

echo makeClickChartHtml("EKF X vs EKF Y", "icon_nav.png",
                        a("/charts/nav.php",
                            "group_id", NULL,
                            "xypairs", array("EKF_X:EKF_Y")),
                        850, 650); 

echo makeClickChartHtml("GPS X vs GPS Y", "icon_nav.png",
                        a("/charts/nav.php",
                            "group_id", NULL,
                            "xypairs", array("GPS_X:GPS_Y")),
                        850, 650); 




//////////////////// ACTUATION

echo makeClickChartHtml("RTU Performance", "icon_chart.png",
                        a("/charts/numeric.php", 
                            "group_id", NULL,
                            "varlist", array("desired_rtu", "rtu_position")),
                        850, 650) ;

echo makeClickChartHtml("Waypoint Performance", "icon_chart.png",
                        a("/charts/numeric.php", 
                            "group_id", NULL,
                            "varlist", array("waypoint_distance")),
                        850, 650) ;


echo makeClickChartHtml("Heading Performance", "icon_chart.png",
                        a("/charts/numeric.php", 
                            "group_id", NULL,
                            //"varlist", array("desired_heading", "ins_heading", "dvl_heading")),
                            "varlist", array("desired_heading", "ins_heading")),
                        850, 650) ;

echo makeClickChartHtml("Depth Performance", "icon_chart.png",
                        a("/charts/numeric.php", 
                            "group_id", NULL,
                            "varlist", array("desired_depth:-1", "nav_depth:-1")),
                        850, 650) ;

echo makeClickChartHtml("Altitude Performance", "icon_chart.png",
                        a("/charts/numeric.php", 
                            "group_id", NULL,
                            "varlist", array("desired_altitude", "nav_altitude")),
                        850, 650);

echo makeClickChartHtml("Altitude Sensors", "icon_chart.png",
                        a("/charts/numeric.php", 
                            "group_id", NULL,
                            "varlist", array("dvl_altitude", "range_altitude")),
                        850, 650);

echo makeClickChartHtml("Attitude", "icon_chart.png",
                        a("/charts/numeric.php",
                            "group_id", NULL,
                            "varlist", array("ins_pitch:$r2d", "ins_roll:$r2d")),
                        850, 650); 

$thrust2deg = 90.0 / 1600.0;
/*
echo makeClickChartHtml("Roll Fail", "icon_chart.png",
                        a("/charts/numeric.php",
                            "group_id", NULL,
                            "varlist", array(//"rtu_position",
                                            "desired_rtu",
                                            "desired_port_vectored:$thrust2deg",
                                            "desired_starboard_vectored:$thrust2deg", 
                                            "ins_roll:$r2d"
                                            )),
                        850, 650); 
*/

/////////////////// BATTERY

echo makeClickChartHtml("Battery Voltages", "icon_battery.png",
                        a("/charts/battery_voltage.php", "group_id", NULL), 
                        850, 650);
echo makeClickChartHtml("Battery Temperatures", "icon_battery.png",
                        a("/charts/battery_temperature.php", "group_id", NULL), 
                        850, 650);
echo makeClickChartHtml("Battery Current", "icon_battery.png",
                        a("/charts/numeric.php", 
                            "group_id", NULL,
                            "varlist", array("battery_current")),
                        850, 650) ;




//////////////////////  VISUAL SERVO

/*
echo makeClickChartHtml("Pipe Width", "icon_chart.png",
                        a("/charts/numeric.php", 
                            "group_id", NULL,
                            "varlist", array("pipe_width")),
                        850, 650);

echo makeClickChartHtml("Pipe Range", "icon_chart.png",
                        a("/charts/numeric.php", 
                            "group_id", NULL,
                            "varlist", array("pipe_range")),
                        850, 650);

echo makeClickChartHtml("Pipe Bearing", "icon_chart.png",
                        a("/charts/numeric.php", 
                            "group_id", NULL,
                            "varlist", array("pipe_bearing:$r2d", "ins_heading")),
                        850, 650);

*/



echo "
<hr>
Draw selected charts in new window, sized  
 <input type='text' name='width' value='850' size='5' /> by
 <input type='text' name='height' value='650' size='5' />
 <input type='submit' value='Go!' />
 </form>

<br />
<br />
<br />

";

echo makeCustomGraphHtml();

echo makeMissionFileHtml();

echo makeLogHtml();


function makeCameraHtml()
{
    global $db;
    global $dbo;
    global $mid;


    $camerastuff = "<img src='/img/icon_camera.png' style='vertical-align:middle;'/> ";
    $camerastuff .= " Camera Captures and Altitudes table"; 

    $q = "
        select elapsed_time, 
            varname, 
            message 
        from (
            select elapsed_time, 
                varname, 
                message 
            from app_messages 
            where mission_id = $mid 
              and varname='ICAMERA_SAVEDFILE' 
            union 
            select elapsed_time, 
                varname, 
                value message 
            from app_data 
            where mission_id=$mid 
              and varname='NAV_ALTITUDE'
           ) aunion 
        order by elapsed_time asc
        ";

    $frames = "<div style=' height:40em; overflow:auto;'>\n";
    $frames .= "<h3><a href='contactsheet.php?mission_id=$mid'>Contact Sheet</a></h3>";
    
    $frames .= "<table cellspacing='1' border='1' cellpadding='3'>\n";
    $frames .= " <tr>\n";
    $frames .= "  <th valign='top'>Elapsed Time</th>\n";
    $frames .= "  <th valign='top'>Altitude</th>\n";
    $frames .= "  <th valign='top'>Image</th>\n";
    $frames .= " </tr>\n";

    $rs = $db->query($q);
    $alt = 0;
    while ($r = $rs->fetchRow(MDB2_FETCHMODE_OBJECT))
    {
        if ("NAV_ALTITUDE" == $r->varname)
        {
            $alt = $r->message;
        }
        else //print out frame that we just got
        {
            $img = strrchr($r->message, "/");
            $frames .= " <tr>\n";
            $frames .= "  <td>{$r->elapsed_time}</td>\n";
            $frames .= "  <td>$alt</td>\n";
            $frames .= "  <td><a href='/img/viewimage.php?FOR={$r->message}'>$img</a></td>\n";
            $frames .= " </tr>\n";
        }

    }
    $frames .= "</table>\n</div>\n";


    return makeClickBoxHtml($camerastuff, $frames); 

}


function makeNotesHtml()
{
    global $db;
    global $dbo;
    global $mid;

    $notes = $dbo->mission->notes->Of($mid);
    $gid = isset($_GET["group_id"]) ? $_GET["group_id"] : "";
    
    $form = "<b>Notes:</b><br />";
    $form .= "<form action='updatenotes.php' method='post'>";
    $form .= "<textarea name='notes' rows='20' cols='80' wrap='virtual'>";
    $form .= "$notes</textarea>";

    $form .= "<input type='hidden' name='mission_id' value='$mid' />";
    $form .= "<input type='hidden' name='group_id' value='$gid' />";
    $form .= "<br />";
    $form .= "<input type='submit' value='Update Notes' />";
    $form .= "</form>";


    return makeClickBoxHtml("<b>Notes:</b> " . nl2br($notes), $form); 
}


function makeLogHtml()
{
    global $db;
    global $dbo;
    global $mid;

    $logstuff = "<img src='/img/icon_log.gif' style='vertical-align:middle;'/> ";
    $logstuff .= "Debug Messages"; 


    $log = "<h3>Debug Messages</h3>";

    $q = "
       select elapsed_time, message
       from app_messages
       where mission_id = $mid
         and varname = 'MOOS_DEBUG'
       order by elapsed_time asc
    ";

    $log .= "<table cellspacing='1' border='1' cellpadding='3'>\n";
    $log .= " <tr>\n  <th valign='top'>Elapsed Time</th>\n";
    $log .= "  <th valign='top'>Message</th>\n </tr>\n";    $rs = $db->query($q);
    while ($r = $rs->fetchRow(MDB2_FETCHMODE_OBJECT))
    {
        $log .= " <tr>\n  <td valign='top'>{$r->elapsed_time}</td>\n";
        $log .= "  <td valign='top'>{$r->message}</td>\n </tr>\n";
    }
    $log .= "</table>\n";

    return makeClickBoxHtml($logstuff, $log);
}



function makeMissionFileHtml()
{
    global $db;
    global $dbo;
    global $mid;

    $missionfiles = "<img src='/img/icon_config.png' style='vertical-align:middle;'/>";
    $missionfiles .= " Mission Files"; 

    $missionfiles_content = "Files used in this mission: <ul>";

    $q = "
    select file_name 
    from text_files 
    where mission_id = $mid 
    order by file_name asc
    ";

    $rs = $db->query($q);
    while ($r = $rs->fetchRow(MDB2_FETCHMODE_OBJECT))
    {
        $href = "getfile.php?mission_id=$mid&file_name={$r->file_name}";
        $missionfiles_content .= "<li><a href='$href'>{$r->file_name}</a></li>\n";
    }
    $missionfiles_content .= "</ul>";
    
    return makeClickBoxHtml($missionfiles, $missionfiles_content);

}

function makeCustomGraphHtml()
{
    global $db;
    global $dbo;
    global $mid;

    $customgraphs =
        "
    Fine then.  Make your own.<br />

    <form action='/passthrough.php'>

     <input type='hidden' name='mission_id' value='$mid' />
     <input type='hidden' name='chart' value='/charts/numeric.php' />
     <select name='varlist[]' multiple='multiple' size='15'>
    ";


    $vars = $dbo->app_data->varname->Distinct(
        array("mission_id" => "= $mid"),
        array("varname" => "asc"));

    foreach ($vars as $v)
    {
        $customgraphs .= " 
      <option>$v</option>";
    }
    
    $customgraphs .= "
     </select>
     <input type='text' name='width' value='850' size='5' /> by
     <input type='text' name='height' value='650' size='5' />
     <input type='submit' value='Move Along' />
    </form>
    ";

    return makeClickBoxHtml("<h3>These Aren't The Graphs I'm Looking For</h3>", 
                            $customgraphs);

}


include("report_footer.php"); 

?>
