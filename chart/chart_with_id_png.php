<?php
 include("./class/pData.class.php"); 
 include("./class/pDraw.class.php"); 
 include("./class/pImage.class.php"); 

if(isset($_GET['id']))
{
        $id=$_GET['id'];
 }

$width = 950;
$height = 450;
$weight = 1.5;
$font = 9;
$font_t = 12;

if(isset($_GET['width'])) $width = $_GET['width'];
if(isset($_GET['height'])) $height = $_GET['height'];
if(isset($_GET['weight'])) $weight = $_GET['weight'];
if(isset($_GET['font'])) $font = $_GET['font'];
if(isset($_GET['font_t'])) $font_t = $_GET['font_t'];


$MyData = new pData();
//read sql info and store
$db1 = mysql_connect("localhost","root","fk@ipcdbserver");
mysql_select_db("chart_conf");
$sql_s=mysql_query("SELECT * FROM chart_id_sql where id={$id}");
while($ris[]=mysql_fetch_row($sql_s));
	
	//store sql info
	$sql_chart = $ris[0][3];
	$user = $ris[0][4];
	$pwd = $ris[0][5];
	$host = $ris[0][6];
	$chart_png = $ris[0][7];
	$chart_title = $ris[0][8];

mysql_close();
$db2 = mysql_connect($host,$user,$pwd);
$result=mysql_query($sql_chart);


//取有几个字段
$fields=mysql_num_fields($result);
$row_number=mysql_num_rows($result);
//if($row_number<60){$row_number=0;}
//$t = "<table>";
//echo $fields;
for($count=0;$count<$fields;$count++)
{
        $field=mysql_fetch_field($result,$count);
        $forfields[$count]=$field->name;
}
//取data
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
        $MyData->setSerieWeight($field->name,$weight); 
}
 $myPicture = new pImage($width,$height,$MyData); 

 /* Turn of Antialiasing */ 
 $myPicture->Antialias = TRUE; 

 /* Draw the background */ 
 //$myPicture->drawFilledRectangle(3,0,$width-1,$height-4,array("R"=>120, "G"=>185, "B"=>255, "Dash"=>0, "DashR"=>255, "DashG"=>255, "DashB"=>255)); 
 $myPicture->drawFilledRectangle(3,0,$width-1,$height-4,array("R"=>120, "G"=>215, "B"=>180, "Dash"=>0, "DashR"=>255, "DashG"=>255, "DashB"=>255)); 
 $myPicture->drawRectangle(3,0,$width-1,$height-4,array("R"=>0,"G"=>0,"B"=>0));
 
 $myPicture->drawFilledRectangle(0,3,$width-4,$height-1,array("R"=>255, "G"=>255, "B"=>255, "Alpha"=>85)); 
 $myPicture->drawRectangle(0,3,$width-4,$height-1,array("R"=>0,"G"=>0,"B"=>0));
 
/* Overlay with a gradient */ 
// $Settings = array("StartR"=>219, "StartG"=>231, "StartB"=>139, "EndR"=>1, "EndG"=>138, "EndB"=>68, "Alpha"=>50); 
// $myPicture->drawGradientArea(0,0,800,350,DIRECTION_VERTICAL,$Settings); 
// $myPicture->drawGradientArea(0,0,700,20,DIRECTION_VERTICAL,array("StartR"=>0,"StartG"=>0,"StartB"=>0,"EndR"=>50,"EndG"=>50,"EndB"=>50,"Alpha"=>80)); 

 /* Set the default font */ 
$myPicture->setFontProperties(array("FontName"=>"./fonts/simhei.ttf","FontSize"=>$font_t,"R"=>0,"G"=>0,"B"=>0)); 
$myPicture->drawText(5,5+$font_t*2,$chart_title);  

$myPicture->setFontProperties(array("FontName"=>"./fonts/simhei.ttf","FontSize"=>$font,"R"=>0,"G"=>0,"B"=>0));
$myPicture->setGraphArea(55,15+$font_t*2,$width*0.9-50,$height-50);

 /* Draw the scale */ 
 $scaleSettings = array("FLOATING"=>TRUE,"GridR"=>0,"GridG"=>0,"GridB"=>0,"GridAlpha"=>10,"DrawSubTicks"=>TRUE,"Mode"=>SCALE_MODE_START0,"CycleBackground"=>FALSE,"LabelRotation"=>30,"LabelSkip"=>round($row_number/($width/30))); 
 $myPicture->drawScale($scaleSettings); 

 /* Enable shadow computing */ 
 $myPicture->setShadow(TRUE,array("X"=>1.5,"Y"=>1.5,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10)); 

 /* Draw the line chart */ 
 $myPicture->drawLineChart(); 
 //$myPicture->drawPlotChart(array("DisplayValues"=>TRUE,"PlotBorder"=>TRUE,"BorderSize"=>2,"Surrounding"=>-60,"BorderAlpha"=>80)); 

 /* Write the data bounds */
 //$myPicture->writeBounds();

 /* Write the chart legend */ 
 $myPicture->setFontProperties(array("FontName"=>"./fonts/simsun.ttc","FontSize"=>$font,"R"=>0,"G"=>0,"B"=>0));
 $myPicture->drawLegend($width*0.9-40,30+$font_t*2,array("Style"=>LEGEND_BOX,"Mode"=>LEGEND_VERTICAL,"Alpha"=>10,"Margin"=>4,"R"=>255, "G"=>255, "B"=>255)); 
 
  /* Render the picture (choose the best way) */ 
 // $myPicture->Render("pictures/$chart_png.png");
 $myPicture->Stroke();
?>
