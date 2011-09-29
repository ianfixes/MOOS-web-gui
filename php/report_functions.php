<?php


//make a link based on the current link and args
function a($page) //($page, $key1, $val1, $key2, $val2...)
{
    $arrr = func_get_args();

    $page = $arrr[0];
    
    //pick up new args from incoming array
    $newargs = array();
    for ($i = 1; $i + 1 < func_num_args(); $i = $i + 2)
    {
        $newargs[$arrr[$i]] = $arrr[$i + 1];
    }

    //edit copy of _GET array with new vars
    $g = $_GET;
    foreach ($newargs as $k => $v)
    {
        //removals
        if (NULL == $v)
        {
            if (isset($g[$k]))
            {
                unset($g[$k]);
            }
        }
        else
        {
            $g[$k] = $v;
        }
    }

    //build url
    $querystring = "$page?";
    foreach ($g as $k => $v)
    {
        if (!is_array($v))
        {
            $querystring .= "$k=" . urlencode($v) . "&";
        }
        else
        {
            foreach ($v as $vv)
            {
                $querystring .= "{$k}[]=" . urlencode($vv) . "&";
            }
        }
    }

    return $querystring;
}

//this produces code to make workable cross-browser embeds
function makeEmbedJs($source, $width, $height, $id)
{
    return "
    swfobject.embedSWF(
      '/swf/open-flash-chart.swf', '$id', '$width', '$height',
      '9.0.0', 'expressInstall.swf',
      {'data-file':'$source'}
      );
    ";

}

//get an HTML id for a chart that is not in use
function freeChartId()
{
    static $i = 0;
    
    $i++;

    return "chart_number_$i";
}

// make the HTML for a chart object
function makeChartHtml($source, $width, $height, $id=NULL)
{

    $usource = urlencode($source);

    if (NULL == $id)
    {
        $id = freeChartId();
    }   
    
    $ret = "
    <div id='$id'></div>
    <script type='text/javascript'>
    " . makeEmbedJs($usource, $width, $height, $id) . "
    </script>
    <!-- $source -->
    ";

    return $ret;
}

// make HTML for a box that doesn't load until it's clicked
function makeClickBoxHtml($pre_click, $post_click)
{
    static $i = 0;
    $i++;
    $id_par = "clickbox_{$i}_parent";
    $id_pre = "clickbox_{$i}_pre";
    $id_pos = "clickbox_{$i}_pos";
    $myfn = "clickbox_{$i}_action";

    return "
    <div class='clickbox' id='$id_par'>
     <div id='$id_pre' onClick='$myfn();'>$pre_click</div>
     <div id='$id_pos' style='display:none;';>$post_click</div>
     <script type='text/javascript'>
      function $myfn()
      {
        document.getElementById('$id_par').style.cursor = 'Auto';
        document.getElementById('$id_pre').style.display = 'None';
        document.getElementById('$id_pos').style.display = 'Block';
      }
     </script>
    </div>

    ";
    
}

// make HTML for a chart that doesn't load until it's clicked
function makeClickChartHtml($caption, $img, $source, 
                            $width, $height, 
                            $display_extras = true,
                            $id=NULL)
{
    global $GET;

    $mid = isset($GET->mission_id) ? $GET->mission_id->int : 0;

    $usource = urlencode($source);

    if (NULL == $id)
    {
        $id = freeChartId();
    }   
    
    $img = "<img src='/img/$img' alt='' style='vertical-align:middle;' />";
    $target = "new_window_{$mid}_$id";

    $ret = "

    <div class='clickbox' 
        id='$id' onclick=\"" . makeEmbedJs($usource, $width, $height, $id) . "\">
        $img $caption
    </div>
    <!-- $source -->
    ";

    if (!$display_extras) return $ret;

    $ret .= "
    <div style='border:0px solid black;font-size:smaller; margin:0 0 1em 0;'>    

        <a style='float:right;' href='" 
            . a("manycharts.php", "selectchart", array($source)) 
            . "' target='$target'><img style='vertical-align:middle;'
        src='img/icon_external.png' border='0' alt='New Window' /></a>

       <a href='$source' style='float:right;text-decoration:none;  margin:0 1ex;' 
            target='src_$target'>[src]</a>

       <a href='" . a("/smear.php", "charttype", $source, "width", 850, "height", 650) 
            . "' style='float:right;text-decoration:none;' target='smear_$target'>[smear]</a>

        <input type='checkbox' name='selectchart[]' value='$source' id='{$id}_label' />
         <label for='{$id}_label'>$caption</label>
    </div>


    ";

    return $ret;
}

?>
