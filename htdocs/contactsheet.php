<?php

include("report_functions.php");
include("report_arginit.php");
include("report_dbinit.php");

//this prints a contact sheet of images in a given directory

$thumbs = array();

if (isset($GET->data_dir))
{
    $dd = $GET->data_dir->path;
    $title = $dd;
    $h2 = "<a href='$dd'>$dd</a>";

    $aDir = new DirectoryIterator($dd);
   
    $files = array(); 
    foreach ($aDir as $aFile)
    {
        if ($aFile->isFile())
        {
            
            $files[] = $aFile->getFilename();
        }    
    }

    sort($files);

    foreach ($files as $f)
    {
        $thumb["imgsrc"] = "/img/viewimage.php?FOR=$dd/$f";
        $thumb["caption"] = $f;
        $thumbs[] = $thumb;
    }
}
else if (isset($GET->mission_id))
{
    $mid = $GET->mission_id->int;
    $title = "mission $mid";
    $h2 = "<a href='/mission.php?mission_id=$mid'>mission $mid</a>";

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
    
    
    $rs = $db->query($q);
    $alt = 0;

    while ($r = $rs->fetchRow(MDB2_FETCHMODE_OBJECT))
    {
        if ("NAV_ALTITUDE" == $r->varname)
        {
            $alt = "NAV_ALTITUDE = {$r->message}";
        }
        else //print out frame that we just got
        {
            $filename = strrchr($r->message, "/");
            $thumb["imgsrc"] = "/img/viewimage.php?FOR={$r->message}";
            $thumb["caption"] = "<span title='$alt'>$filename</span>";
            $thumbs[] = $thumb;
        }
    
    }

}
else
{
    die("No mission_id or data_dir provided");
}

//code to print a selected contact sheet
$showevery = 1;
if (isset($GET->showevery))
{
    $showevery = $GET->showevery->int;
}

echo "<html>
<head>
 <title>Contact sheet for $title</title></head>
 <link rel='stylesheet' type='text/css' href='/style.css' />

<body>
<h2>Contact sheet for $h2</h2>
";

//print some google-style numbers for how many images to skip
echo "<h3>Show every: ";

foreach (array(1,2,4,8,16,32,64,128) as $i)
{
    $a = a("contactsheet.php", "showevery", $i);
    if ($i == $showevery)
    {
        echo "$i ";
    }
    else
    {
        echo "<a href='$a'>$i</a> ";
    }
}

$a = a("contactsheet.php", "showevery", 1);
echo " | <a href='$a'>ALL</a>";

echo "</h3>\n";


// the sheet itself
echo "<div class='contactsheet'>\n";
$i = 0;
foreach ($thumbs as $thumb)
{
    if (0 == ($i % $showevery))
    {
        echo "<div class='imgbox'>";
        echo "<a href='{$thumb["imgsrc"]}'><img src='{$thumb["imgsrc"]}&max_x=240&max_y=120' /></a>";
        echo "<div class='caption'>{$thumb["caption"]}</div>";
        echo "</div>\n"; 
    }

    $i++;
}
echo "</div>";





echo "</body></html>";

?>
