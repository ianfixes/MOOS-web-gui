<?php
   //kml for styles

    $s = $_SERVER['SERVER_NAME'];
    echo "  
  <Style id=\"waypointIcon\">
   <IconStyle>
    <Icon>
     <href>http://$s/img/google_marker_yellow.png</href>
    </Icon>
   </IconStyle>
  </Style>
  <Style id=\"pathStartIcon\">
   <IconStyle>
    <Icon>
     <href>http://$s/img/google_marker_green.png</href>
    </Icon>
   </IconStyle>
  </Style>
  <Style id=\"pathEndIcon\">
   <IconStyle>
    <Icon>
     <href>http://$s/img/google_marker_red.png</href>
    </Icon>
   </IconStyle>
  </Style>
  <Style id=\"auvIcon\">
   <IconStyle>
    <Icon>
     <href>http://$s/img/google_marker_auv.png</href>
    </Icon>
   </IconStyle>
  </Style>
  <Style id=\"pathGPS\">
   <LineStyle>
    <color>7f00ff00</color>
    <width>3</width>
   </LineStyle>
   <PolyStyle>
    <color>7f00ffff</color>
   </PolyStyle>
  </Style>
  <Style id=\"pathDR\">
   <LineStyle>
    <color>7f0149ff</color>
    <width>4</width>
   </LineStyle>
  </Style>
  <Style id=\"pathList\">
   <ListStyle>
    <listItemType>checkHideChildren</listItemType>
   </ListStyle>
  </Style>
";
?>
