<?php

include("report_functions.php");
include("report_arginit.php");
include("report_dbinit.php");

// the DB includes a table for storing text files.  this reads them.

if (!isset($GET->mission_id))
{
    die("No mission_id provided");
}

if (!isset($GET->file_name))
{
    die("No file_name provided");
}

$mid = $GET->mission_id->int;
$fs = $GET->file_name->sql;
$f = $GET->file_name->url;

$q = "
    select file
    from text_files 
    where mission_id = $mid
      and file_name = '$fs'
    ";

$rs = $db->query($q);


while ($r = $rs->fetchRow(MDB2_FETCHMODE_OBJECT))
{
    $suffix = strrchr($f, ".");
    $prefix = substr($f, 0, strlen($f) - strlen($suffix));
    $newname = "{$prefix}.mission$mid{$suffix}";
    
    header("Content-type: text/plain");
    header("Content-disposition: inline;filename=$newname");
    echo $r->file;
}

?>
