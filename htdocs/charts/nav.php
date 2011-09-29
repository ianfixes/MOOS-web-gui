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

function makeXYsql($mission_id, $xvar, $yvar)
{
    $mid = intval($mission_id);
    $xvar = strtoupper($xvar);
    $yvar = strtoupper($yvar);
    return "
        select distinct
            var_x.value x_value, 
            var_y.value y_value
        from mission 
            left join (
                    select * 
                    from app_data 
                    where varname='$xvar' 
                      and mission_id = $mid
                  ) var_x 
                on (mission.mission_id = var_x.mission_id) 
            left join (
                    select * 
                    from app_data 
                    where varname='$yvar' 
                      and mission_id = $mid
                  ) var_y 
                on (mission.mission_id = var_y.mission_id 
                    and var_x.elapsed_time = var_y.elapsed_time) 
        where mission.mission_id = $mid 
        order by mission.date, 
            mission.time, 
            var_x.elapsed_time
        ";
}


//input checking
if (!isset($GET->mission_id))
{
    $chart->set_title(new OFC_Elements_Title("Error: Mission ID not specified"));
    echo $chart->toPrettyString();
    die();
}

if (!isset($GET->xypairs))
{
    $chart->set_title(new OFC_Elements_Title("Error: xypairs not specified"));
    echo $chart->toPrettyString();
    die();
}

$mission_id = $GET->mission_id->int;

$title_string = "Navigation in mission $mission_id - {$dbo->mission->label->Of($mission_id)}";
$title = new OFC_Elements_Title($title_string);
$title->set_style("font-size:18px;");
$chart->set_title( $title );


$colors = makeColorArray();
$xypairs = $_GET["xypairs"];

$min_x = $min_y = 99999999;
$max_x = $max_y = -99999999;
foreach ($xypairs as $i => $v)
{
    $parts = explode(":", $v);

    $xvar = $parts[0];
    $yvar = $parts[1];

    $color = $colors[($i * 37) % 27];
    $plot = new OFC_Charts_Scatter_Line("#$color", 3);
    $plot->set_width(1);
    $d = new OFC_Dots_Solid();
    $d->set_size(2);
    $d->set_halo_size(0);
    $plot->set_default_dot_style($d);
    $plot->set_key("$xvar vs $yvar", 14);


    //do sql query
    $q = makeXYsql($mission_id, $xvar, $yvar);
    $rs = $db->query($q);

    //while loop
    $points = array();
    $firstx = NULL;
    $firsty = NULL;
    while ($r = $rs->fetchRow(MDB2_FETCHMODE_OBJECT))
    {
        $x = sprintf("%f", $r->x_value);
        $y = sprintf("%f", $r->y_value);
    
        if (NULL == $firstx)
        {
            $firstx = $x;
        }

        if (NULL == $firsty)
        {
            $firsty = $y;
        }

        $min_x = min($min_x, $x);
        $min_y = min($min_y, $y);
        $max_x = max($max_x, $x);
        $max_y = max($max_y, $y);
        
        $point = new OFC_Charts_Scatter_Value($x, $y);
        $points[] = $point;
    }
   
    if ($firstx && $firsty)
    {
        $plot->set_values($points);
        $chart->add_element($plot);

        //draw origin
        $plot = new OFC_Charts_Scatter("#$color", 2);
        $d = new OFC_Dots_Anchor();
        $d->set_sides(3);
        $d->set_size(8);
        $plot->set_default_dot_style($d);
        $plot->set_values(array(new OFC_Charts_Scatter_Value($firstx, $firsty)));
        $chart->add_element($plot);
    }
}


//STICK SOME WAYPOINTS ON THERE
$waypoints = isset($_GET["waypoints"]) ? $_GET["waypoints"] : array();
foreach ($waypoints as $i => $v)
{
    $parts = explode(":", $v);

    $xvar = $parts[0];
    $yvar = $parts[1];

    $plot = new OFC_Charts_Scatter("#FF8800", 3);
    $d = new OFC_Dots_Star();
    $d->set_size(10);
    $d->set_hollow(true);
    $plot->set_default_dot_style($d);


    //do sql query
    $q = makeXYsql($mission_id, $xvar, $yvar);
    //die($q);
    $rs = $db->query($q);

    //while loop
    $points = array();
    while ($r = $rs->fetchRow(MDB2_FETCHMODE_OBJECT))
    {
        if (!is_null($r->x_value) && !is_null($r->y_value))
        {
            $x = sprintf("%f", $r->x_value);
            $y = sprintf("%f", $r->y_value);
        
            $min_x = min($min_x, $x);
            $min_y = min($min_y, $y);
            $max_x = max($max_x, $x);
            $max_y = max($max_y, $y);
        
            $point = new OFC_Charts_Scatter_Value($x, $y);
            $points[] = $point;
        }
    }
   
    $plot->set_values($points);
    $chart->add_element($plot);
}

//if x range is bigger, increase y to make it square
$diff = abs(($max_x - $min_x) - ($max_y - $min_y)) / 2;
if ($max_x - $min_x > $max_y - $min_y)
{
   $min_y = $min_y - $diff;
   $max_y = $max_y + $diff;
}
else //y range is bigger...
{
    $min_x = $min_x - $diff;
    $max_x = $max_x + $diff;
}

$pad = $diff * 0.02;
$min_x -= $pad;
$min_y -= $pad;
$max_x += $pad;
$max_y += $pad;

$x = new OFC_Elements_Axis_X();
$x->set_range( $min_x - 10, $max_x + 10 );
$x->set_steps(50);
$chart->set_x_axis( $x );


$y = new OFC_Elements_Axis_Y();
$y->set_range( $min_y - 10, $max_y + 10);
$y->set_steps(50);
$chart->add_y_axis( $y );


//echo $chart->toString();
echo $chart->toPrettyString();


?>
