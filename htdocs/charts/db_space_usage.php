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

$tmp = @filesize("/data/mysql/ibdata1");
$db_size = sprintf("%u", $tmp);

$datadir_size = $db->getOne("
    select sum(data_length + index_length) 
    from information_schema.tables
    ");

if (0 < $db_size)
{
    $title_string = "Disk Usage in a " . byteFormat($db_size) . " database";
}
else
{
    $title_string = "Byte distribution in " . byteFormat($datadir_size) 
        . " of tables (Disk usage N/A)";
}


$title = new OFC_Elements_Title($title_string);
$title->set_style("font-size:18px;");
$chart->set_title( $title );

$colors_in = makeColorArray();
$colors = array();
foreach ($colors_in as $i => $c)
{
    $colors[] = "#" . $colors_in[($i * 37) % 27];
}

$pie = new OFC_Charts_Pie();
$pie->colours = $colors;

$q = "
        select table_schema, 
            table_name, 
            data_length, 
            index_length 
        from information_schema.tables 
        where table_schema not in ('mysql', 'information_schema')
    ";

$rs = $db->query($q);

$slices = array();
$totalsize = 0;
while ($r = $rs->fetchRow(MDB2_FETCHMODE_OBJECT))
{
    $slices[] = new OFC_Charts_Pie_Value(intval($r->data_length), 
                    "{$r->table_schema}.{$r->table_name}: "
                    . byteFormat($r->data_length));

    $totalsize += $r->data_length;

    if (0 < $r->index_length)
    {   
        $slices[] = new OFC_Charts_Pie_Value(intval($r->index_length), 
                    "{$r->table_schema}.{$r->table_name} (indexes): "
                    . byteFormat($r->index_length));

        $totalsize += $r->index_length;
    }
}

$leftover = $db_size - $totalsize;
array_unshift($slices, new OFC_Charts_Pie_Value($leftover, "Excess Space: " 
                                       . byteFormat($leftover)));

$pie->values = $slices;

$chart->add_element($pie);

echo $chart->toString();
//echo $chart->toPrettyString();


?>
