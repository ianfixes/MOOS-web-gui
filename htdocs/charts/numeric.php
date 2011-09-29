<?php
    
include("chart_header.php");

//HACK to prevent float value from printing out as "9.9e-5"
function to_float($v)
{
    if (abs($v) < floatval("10e-5"))
        return 0;
    else
        return floatval(sprintf("%f", $v));
}

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

//input checking
if (!isset($GET->mission_id))
{
    $chart->set_title(new OFC_Elements_Title("Error: Mission ID not specified"));
    echo $chart->toPrettyString();
    die();
}

if (!isset($GET->varlist))
{
    $chart->set_title(new OFC_Elements_Title("Error: varlist not specified"));
    echo $chart->toPrettyString();
    die();
}

$mission_id = $GET->mission_id->int;

$title_string = "Mission $mission_id - {$dbo->mission->label->Of($mission_id)}";
$title = new OFC_Elements_Title($title_string);
$title->set_style("font-size:18px;");
$chart->set_title( $title );


$colors = makeColorArray();
$varlist = $_GET["varlist"];

$plots = array();
$attributes = array();
$scalefactors = array();
$varnames_sql = array();
foreach ($varlist as $i => $v)
{
    $parts = explode(":", $v);
    if ("" == @$parts[1]) $parts[1] = 1;

    $varname = strtoupper($parts[0]);
    $scalefactor = $parts[1];

    $scalefactors[$varname] = $scalefactor;
    $varnames_sql[] = "'" . strtoupper($varname) . "'";

    $plot = new OFC_Charts_Scatter_Line("#" . $colors[($i * 37) % 27], 3);
    $plot->set_width(1);
    $d = new OFC_Dots_Solid();
    $d->set_size(2);
    $d->set_halo_size(0);
    $plot->set_default_dot_style($d);
    $plot->set_key($varname, 14);

    $plots[$varname] = $plot;
    $attributes[$varname] = array();
}

$q = "
    select mission.date, 
        mission.time, 
        app_data.elapsed_time, 
        app_data.varname,
        app_data.value 
    from mission 
        inner join app_data using (mission_id) 
    where app_data.varname in (" . implode(",", $varnames_sql) . ")
      and mission.mission_id = $mission_id
    order by mission.date, 
        mission.time, 
        app_data.elapsed_time  
    ";


$rs = $db->query($q);


$min_time = 0;
$max_time = 1;
$min_attr = 99999999;
$max_attr = -99999999;

while ($r = $rs->fetchRow(MDB2_FETCHMODE_OBJECT))
{
    $thetime = strtotime($r->date) + strtotime($r->time) + $r->elapsed_time;
    $max_time = $thetime;
    if (NULL == $min_time)
    {
        $min_time = $thetime;
    }
    
    $varname = strtoupper($r->varname);
    $v = to_float($r->value * $scalefactors[$varname]);
    $t = to_float($thetime - $min_time);

    $min_attr = min($min_attr, $v);
    $max_attr = max($max_attr, $v);
    $attributes[$varname][] = new OFC_Charts_Scatter_Value($t, $v);
}


foreach ($plots as $varname => $plot)
{
    $plot->set_values($attributes[$varname]);
    $chart->add_element($plot);
}


$x = new OFC_Elements_Axis_X();
$xrng = $max_time - $min_time;
$x->set_range( 0, $xrng);
if ($xrng > 7200)
{
    $x->set_steps(900);
    $duration = "15 min / tick";
}
elseif ($xrng > 3600)
{
    $x->set_steps(600);
    $duration = "10 min / tick";
}
elseif ($xrng > 1800)
{
    $x->set_steps(300);
    $duration = "5 min / tick";
}
else
{
    $x->set_steps(60);
    $duration = "1 min / tick";
}

$x->set_labels_from_array(array($duration));

$chart->set_x_axis( $x );


$y = new OFC_Elements_Axis_Y();

//auto-stepsize
$mag = floor(log($max_attr - $min_attr, 10));
$mult = pow(10, 0 - $mag);

$y->set_range( to_float(floor($min_attr * $mult) / $mult), to_float(ceil($max_attr * $mult) / $mult ));
$y->set_steps(to_float(pow(10, $mag - 1)));
$chart->add_y_axis( $y );

/*
echo "range is $min_attr to $max_attr<br />";
echo "log of " . ($max_attr - $min_attr) . " is " . log($max_attr - $min_attr, 10) . "<br />";
echo "mag is $mag<br />";
die();
*/

if (isset($GET->debug))
{
    echo $chart->toPrettyString();
}
else
{
    echo $chart->toString();
}

?>
