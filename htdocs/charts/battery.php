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

//error conditions
if (!isset($ATTRIBUTE))
{
    $chart->set_title(new OFC_Elements_Title('Error: battery $ATTRIBUTE not specified'));
    echo $chart->toPrettyString();
    die();
}
else
{
    switch (strtoupper($ATTRIBUTE))
    {
        case "VOLTAGES":
        case "TEMPERATURES":
            break;
        default: 
            $chart->set_title(new OFC_Elements_Title("Error: battery Attribute $ATTRIBUTE not supported"));
            echo $chart->toPrettyString();
            die();
    
    }
}

//input checking
$title_string  = "";
if (isset($GET->mission_id))
{
    $title_string .= "Battery $ATTRIBUTE in mission {$GET->mission_id->int}";

    $filter = "
      and mission.mission_id = {$GET->mission_id->int}
      ";

}
elseif (isset($GET->group_id))
{
    $g = new cCleanArray(explode(",", $GET->group_id->string));
    $title_string .= "Battery $ATTRIBUTE {$g->__get(0)->string}: ";
    $title_string .= "{$g->__get(2)->string} at {$g->__get(1)->string}";

    $filter = "
      and mission.date = '{$g->__get(0)->sql}' 
      and mission.location='{$g->__get(1)->sql}' 
      and mission.vehicle_name = '{$g->__get(2)->sql}' 
    ";
}
else
{
    $chart->set_title(new OFC_Elements_Title("Error: Group_ID not specified"));
    echo $chart->toPrettyString();
    die();
}




$title = new OFC_Elements_Title($title_string);
$title->set_style("font-size:24px;");
$chart->set_title( $title );


$q = "
    select mission.date, 
        mission.time, 
        app_messages.elapsed_time, 
        app_messages.message 
    from mission 
        inner join app_messages using (mission_id) 
    where app_messages.varname=ucase('BATTERY_$ATTRIBUTE')
    $filter
    order by mission.date, 
        mission.time, 
        app_messages.elapsed_time  
    ";

$rs = $db->query($q);

$colors = makeColorArray();
$batteries = array();
$attributes = array();
for ($i = 0; $i < 24; $i++)
{
    $plot = new OFC_Charts_Scatter_Line("#" . $colors[$i], 3);
    $plot->set_width(1);
    $d = new OFC_Dots_Solid();
    $d->set_size(2);
    $d->set_halo_size(1);
    $plot->set_default_dot_style($d);
    $plot->set_key("Cell " . ($i + 1), 10);

    $batteries[$i] = $plot;
    $attributes[$i] = array();
}

$min_time = 0;
$max_time = 1;

switch (strtoupper($ATTRIBUTE))
{
    case "VOLTAGES":
        $min_attr = 4.5;
        $max_attr = 2.5;
        break;
    case "TEMPERATURES":
        $min_attr = 200;
        $max_attr = -30;
        break;

}

$printed_lastrow = array();
for ($i = 0; $i < 24; $i++)
{
    $printed_lastrow[$i] = true;
}
$timestamps = array();
$last_row = NULL;
$last_time = NULL;
while ($r = $rs->fetchRow(MDB2_FETCHMODE_OBJECT))
{
    $thetime = strtotime($r->date) + strtotime($r->time) + $r->elapsed_time;
    $max_time = $thetime;
    if (NULL == $min_time)
    {
        $min_time = $thetime;
    }

    if ("No Response" == $r->message)
    {
        $individuals = array(); //ignore
    }
    else
    {
        $individuals = explode(" ", $r->message);
    }

    foreach ($individuals as $i => $v)
    {
        //save some client CPU cycles by only printing updates
        if (@$last_row[$i] == $v)
        {
            $printed_lastrow[$i] = false;
        }
        else
        {
            if (!$printed_lastrow[$i])
            {
                $t = sprintf("%f", $last_time - $min_time);
                $v = sprintf("%f", $last_row[$i]);
    
                $attributes[$i][] = new OFC_Charts_Scatter_Value($t, $v);
            }

            $t = sprintf("%f", $thetime - $min_time);
            $v = sprintf("%f", $v);

            $min_attr = min($min_attr, $v);
            $max_attr = max($max_attr, $v);
            $attributes[$i][] = new OFC_Charts_Scatter_Value($t, $v);
            $printed_lastrow[$i] = true;
        }
        $last_row[$i] = $v;
    }
    
    $last_time = $thetime;
    
}



for ($i = 0; $i < 24; $i++)
{
    $batteries[$i]->set_values($attributes[$i]);
    $chart->add_element($batteries[$i]);
}


$x = new OFC_Elements_Axis_X();
$xrng = max($max_time - $min_time, 1);
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
$y->set_range( floor($min_attr * 10) / 10, ceil($max_attr * 10) / 10 );
$y->set_steps(.1);
$chart->add_y_axis( $y );


echo $chart->toPrettyString();
//echo $chart->toString();


?>
