<?php

require_once("utils/cCleanArray.php");

include("report_dbinit.php");
include("report_arginit.php");
include("report_functions.php");


$fulltitle = "Odyssey IV";

if (isset($title))
{
    $fulltitle .= ": $title";
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
   <title><?php echo $fulltitle; ?></title>
   <link rel="stylesheet" type="text/css" href="/style.css" />
   <script type="text/javascript" src="/js/swfobject.js"></script>
  </head>

<body>
<table border="0" cellpadding="0" cellspacing="0">
 <tr>
  <td valign="top">
   <div id="menu">
   <a href="/"><img alt="MIT AUV Logo" src="/img/auvlogo.jpg" border="0" /></a>
   <div style="text-align:center;"><a href="/data/">[ Collected Data ]</a></div>
   <div style="text-align:center;"><a href="/browseimages.php">[ Browse Images ]</a></div>
   <div style="text-align:center;"><a href="/special/">[ Special Pages ]</a></div>
   </div>
    <?php include("report_menu.php"); ?>
  </td> 
  <td id="page" valign="top">
