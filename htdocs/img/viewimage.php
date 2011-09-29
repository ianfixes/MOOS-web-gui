<?php

require_once("utils/cCachedFile.php");

// return an image for a file on disk, thumbnailing as appropriate

//define("CONVERT_OPTIONS", "-channel Red -contrast-stretch 0% -channel Green -contrast-stretch 0% -channel Blue -contrast-stretch 0% -channel RGB -unsharp 0x1.6+1+0");
define("CONVERT_OPTIONS", "");

function exif2gd($exif_code)
{
    switch ($exif_code)
    {
        case IMAGETYPE_GIF: 
            return IMG_GIF;
        case IMAGETYPE_JPEG: 
            return IMG_JPG;
        case IMAGETYPE_PNG: 
            return IMG_PNG;
        case IMAGETYPE_WBMP: 
            return IMG_WBMP;
        default: 
            return -1;
    }
}

function createErrorImage($text)
{
    $x = isset($_GET['max_x']) ? $_GET['max_x'] : 200;
    $y = isset($_GET['max_y']) ? $_GET['max_y'] : 20;
    
    $im = imagecreate($x, $y);
    $background_color = imagecolorallocate($im, 255, 255, 255);
    $text_color = imagecolorallocate($im, 233, 14, 91);
    imagestring($im, 1, 5, 5, $text, $text_color);

    header("Content-type: image/jpeg");
    header("Content-Disposition: inline; filename=error.jpg");

    imagejpeg($im);
    imagedestroy($im);
    exit;
}

//for cCachedFile -- returns true for success
function renderImageFromCache($cached_contents)
{
    // defined elsewhere in script...
    global $myFilename;

    $im = @imagecreatefromstring($cached_contents);

    header("Content-type: image/jpeg");
    header("Content-Disposition: inline; filename=$myFilename");

    imagejpeg($im);
    imagedestroy($im);

    return true;
}

//----------------------------------- beginning of script

//check existence of necessary var
if (!isset($_GET['FOR']) || empty($_GET['FOR']))
{
    //$_GET['FOR'] is not set or empty
    $im = createErrorImage("No image");
}

$imagePath = $_GET['FOR'];

$path_parts = pathinfo($imagePath);
$myFilename = $path_parts["filename"];
if (!empty($_GET['max_x']) && !empty($_GET['max_y']))
{
    $myFilename .= "_{$_GET['max_x']}x{$_GET['max_y']}";
}




//set up caching
$CACHEDIR = "/data/tmp";
$nextWeek = time() + (7 * 24 * 60 * 60);

$cached = new cCachedFile();
$cached->setCachedir($CACHEDIR);
$cached->setFilename($myFilename);
$cached->setLastupdate(filemtime($imagePath));
$cached->setExpiry($nextWeek);
$cached->setEtag("img-" . str_replace("/", ".", $myFilename));
$cached->setHashseed($imagePath);

//DETECT CACHED IMAGE, AND BAIL 
//help the browser by not re-downloading an unchanged image
if ($cached->TryClientCache()) exit;
if ($cached->TryServerCache("renderImageFromCache")) exit;

//OK OK... actually CREATE the image

list($orig_x, $orig_y, $type, $attr) = getimagesize($imagePath);
    
//make sure image type is supported
if (!(imagetypes() & exif2gd($type)))
{
    $slice = preg_split("/\./", $imagePath);
    $extension = $slice[count($slice) - 1];
    createErrorImage("Invalid extension '$extension'");
}
        
$contents = file_get_contents($imagePath);

if (false === $contents)
{
    createErrorImage('Empty Image ');
}


$im = @imagecreatefromstring($contents);

//if libGD can't open it, convert it to something that will work and open that instead
if (false === $im)
{
    $newname = "$CACHEDIR/" . basename($imagePath) . ".jpg";
    $o = CONVERT_OPTIONS;
    $x = `convert $imagePath $o $newname`;
    $imagePath = $newname;
    $im = @imagecreatefromstring(file_get_contents($newname)); 
    list($orig_x, $orig_y, $type, $attr) = getimagesize($imagePath);
}


if (false === $im)
{
    createErrorImage("Failed to create image from $imagePath");
}

$x = imagesx($im);
$y = imagesy($im);

//scale if we need it scaeld
if (isset($_GET['max_x']) && isset($_GET['max_y'])
    && ($orig_x > $_GET['max_x'] || $orig_y > $_GET['max_y']))
{
    $max_x = $_GET['max_x'];
    $max_y = $_GET['max_y'];
    
    if (($max_x / $max_y) < ($orig_x / $orig_y)) 
    {
        $new_x = $orig_x / ($orig_x / $max_x);
        $new_y = $orig_y / ($orig_x / $max_x);
    }
    else 
    {
        $new_x = $orig_x / ($orig_y / $max_y);
        $new_y = $orig_y / ($orig_y / $max_y);
    }
        
    $save = imagecreatetruecolor($new_x, $new_y);
    if (false === $save)
    {
        createErrorImage("Failed to create new image");
    }

    $bg = imagecolorallocate($save, 255, 255, 255);

    if (false !== $bg)
    {
        imagefill($save, 0, 0, $bg);
        
        imagecopyresampled($save, $im, 0, 0, 0, 0, 
            $new_x, $new_y, $orig_x, $orig_y);
    
        imagedestroy($im);
        $im = $save;
    }
    else
    {
        imagedestroy($save);
        createErrorImage("Failed to alloc");
    }
}



$cached->MakeCacheHeaders();

header("Content-type: image/jpeg");
header("Content-Disposition: inline; filename=$myFilename");


imagejpeg($im);
imagejpeg($im, $cached->CacheFile(), 100);
imagedestroy($im);

?>
