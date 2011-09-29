<html>
 <head>
  <title>Contact sheets for images in /data/images</title>
 </head>
<body>

<h2>Image directories, reverse-chronological</h2>
<?php

//this is the hard-coded location of stored images on the AUV
$aDir = new DirectoryIterator("/data/images");

$dirs = array();
foreach ($aDir as $aFile)
{
    if ($aFile->isDir() && !$aFile->isDot())
    {
        $dirs[] = $aFile->getFilename();
    }

}


rsort($dirs);

echo "<ul>\n";
foreach ($dirs as $d)
{
    echo " <li><a href='/contactsheet?data_dir=/data/images/$d'>$d</a></li>\n";
}

echo "</ul>\n";

?>

</body>
</html>
