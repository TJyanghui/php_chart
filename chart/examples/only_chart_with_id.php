<html><head><title>图形显示</title>
<style type=text/css>
                body {font-size:12px; color:Black;background-color:White}
                table {border-collapse:collapse; table-layout:fixed; font-size:12px;}
                table,th,td {border:1px solid Black;background-color:White; padding:3px;}
                th {background-color:#B4D2FF; width:auto;white-space:nowrap; text-align:center; color: Black}
                td {background-color:#EBF0FF; width:auto;white-space:nowrap; text-align:center;}
        </style>
 <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
</head>
<body>
<br>

<?php
 include("../class/pData.class.php"); 
 include("../class/pDraw.class.php"); 
 include("../class/pImage.class.php"); 

if(isset($_GET['id']))
{
	$id=$_GET['id'];
 }


$MyData = new pData();
$db = mysql_connect("localhost","root","fk@ipcdbserver");
mysql_select_db("message_account");
$sql_s=mysql_query("SELECT * FROM php_id_sql where id={$id}");
while($ris[]=mysql_fetch_row($sql_s));
$result=mysql_query($ris[0][2]);
//echo $ris[0][2];
$chart_name=$ris[0][5];
//取列
$fields=mysql_num_fields($result);
$row_number=mysql_num_rows($result);
if($row_number<60){$row_number=0;}
//echo $fields;
for($count=0;$count<$fields;$count++)
{
$field=mysql_fetch_field($result,$count);
$forfields[$count]=$field->name;
//echo "<p> $field->name $field->type($field->max_length)</p>";
//echo "<p></p>";
//echo $forfields[$count];
}
//data
$rows=0;
while($row = mysql_fetch_array($result,MYSQL_NUM))
{
	for($count=0;$count<$fields;$count++)
	{
		$timestamp[$count][$rows] = $row[$count];
	}
	$rows++;
}

 $MyData->addPoints(array_reverse($timestamp[0]),"Labels");
 $MyData->setAbscissa("Labels"); 
 
for($count=1;$count<$fields;$count++)
{
$field=mysql_fetch_field($result,$count);
$forfields[$count]=$field->name;
$MyData->addPoints(array_reverse($timestamp[$count]),$field->name);
$MyData->setSerieWeight($field->name,1); 
 
}


 $myPicture = new pImage(1500,450,$MyData); 

 /* Turn of Antialiasing */ 
 $myPicture->Antialias = TRUE; 

 /* Draw the background */ 
 $Settings = array("R"=>255, "G"=>255, "B"=>255, "Dash"=>0, "DashR"=>255, "DashG"=>255, "DashB"=>255); 
 $myPicture->drawFilledRectangle(0,0,1500,450,$Settings); 


 /* Overlay with a gradient */ 
// $Settings = array("StartR"=>219, "StartG"=>231, "StartB"=>139, "EndR"=>1, "EndG"=>138, "EndB"=>68, "Alpha"=>50); 
// $myPicture->drawGradientArea(0,0,1000,450,DIRECTION_VERTICAL,$Settings); 
// $myPicture->drawGradientArea(0,0,1000,20,DIRECTION_VERTICAL,array("StartR"=>0,"StartG"=>0,"StartB"=>0,"EndR"=>50,"EndG"=>50,"EndB"=>50,"Alpha"=>80)); 

 /* Add a border to the picture */ 
 //$myPicture->drawRectangle(1500,450,0,0,array("R"=>0,"G"=>0,"B"=>0)); 
  
 /* Write the chart title */  
// $myPicture->setFontProperties(array("FontName"=>"../fonts/simsun.ttc","FontSize"=>8,"R"=>255,"G"=>255,"B"=>255)); 
 //$myPicture->drawText(10,16,"Average recorded temperature",array("FontSize"=>11,"Align"=>TEXT_ALIGN_BOTTOMLEFT)); 

 /* Set the default font */ 
 $myPicture->setFontProperties(array("FontName"=>"../fonts/simsun.ttc","FontSize"=>8,"R"=>0,"G"=>0,"B"=>0)); 

 /* Define the chart area */ 
 $myPicture->setGraphArea(60,40,1000,400); 

 /* Draw the scale */ 
 $scaleSettings = array("XMargin"=>10,"YMargin"=>10,"Floating"=>TRUE,"GridR"=>200,"GridG"=>200,"GridB"=>200,"DrawSubTicks"=>TRUE,"CycleBackground"=>TRUE,"LabelRotation"=>45,"LabelSkip"=>$row_number/60); 
 $myPicture->drawScale($scaleSettings); 

 /* Turn on Antialiasing */ 
 $myPicture->Antialias = TRUE; 

 /* Enable shadow computing */ 
 $myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10)); 

 /* Draw the line chart */ 
 $myPicture->drawLineChart(); 
 //$myPicture->drawPlotChart(array("DisplayValues"=>TRUE,"PlotBorder"=>TRUE,"BorderSize"=>2,"Surrounding"=>-60,"BorderAlpha"=>80)); 

 /* Write the chart legend */ 
// $myPicture->drawLegend(590,9,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL,"FontR"=>255,"FontG"=>255,"FontB"=>255)); 
// $myPicture->drawLegend(100,40,array("Style"=>LEGEND_FAMILY_BOX,"Mode"=>LEGEND_VERTICAL)); 
 $myPicture->drawLegend(1000,150,0,255,255,255); 
 /* Render the picture (choose the best way) */ 
// $myPicture->autoOutput("pictures/example.drawLineChart.plots.png"); 
$filename="pictures/".$chart_name.".png";
$myPicture->Render($filename);
echo "<img src=$filename />";
//$myPicture->Render("pictures/example.drawLineChart2.plots.rand().png");
//echo "<img src=\"pictures/example.drawLineChart2.plots.png\" />";
?>
</body></html>
