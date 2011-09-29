<?php
    
include("chart_header.php");

function makeColorArray()
{
    $quant = array("00", "88", "FF");

    $ret = array();
    foreach ($quant as $r)
    {
        foreach ($quant as $g)
        {
            foreach ($quant as $b)
            {
                $ret[] = "$r$g$b";
            }
        }
    }

    return $ret;
}

function byteFormat($bytes)
{
    $denoms = array(
        "TB" => 1099511627776,
        "GB" => 1073741824,
        "MB" => 1048576,
        "KB" => 1024);

    foreach ($denoms as $d => $b)
    {
        if ($bytes >= $b) return round($bytes / $b, 1) . " $d";
    }

    return "$bytes B";
}

$awk = '{print $2}';
$disk_size = shell_exec("df /data |grep -v Filesystem |awk '$awk'") * 1024;

$title_string = "Disk Usage in a " . byteFormat($disk_size) . " data partition";
$title = new OFC_Elements_Title($title_string);
$title->set_style("font-size:18px;");
$chart->set_title( $title );

//randomize colors
$colors_in = makeColorArray();
$colors = array();
foreach ($colors_in as $i => $c)
{
    $colors[] = "#" . $colors_in[($i * 37) % 27];
}

$pie = new OFC_Charts_Pie();
$pie->colours = $colors;


$slices = array();
$totalused = 0;

foreach (new DirectoryIterator("/data") as $file)
{
    if (!$file->isDot() && $file->isDir())
    {

        $awk = '{print $1}';
        $usage = shell_exec("du -s /data/{$file->getFilename()} 2>/dev/null|awk '$awk'") * 1024;
        $slices[] = new OFC_Charts_Pie_Value(intval($usage / 1024), 
                                             "{$file->getFilename()}: "
                                             . byteFormat($usage));

        $totalused += $usage / 1024;
    }
}

$leftover = ($disk_size / 1024) - $totalused;
array_unshift($slices, new OFC_Charts_Pie_Value($leftover, "Excess Space: " 
                                       . byteFormat($leftover)));

$pie->values = $slices;

$chart->add_element($pie);

echo $chart->toString();
//echo $chart->toPrettyString();


?>
