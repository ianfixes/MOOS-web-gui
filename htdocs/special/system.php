<?php 

$title = "System";
include("report_header.php"); 

echo "<h2>System Health</h2>";

echo makeChartHtml("/charts/db_space_usage.php", 850, 650);
echo makeChartHtml("/charts/disk_space_usage.php", 850, 650);

include("report_footer.php"); 

?>
