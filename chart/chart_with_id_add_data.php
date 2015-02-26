<html><head><title></title>
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
 include("./class/pData.class.php"); 
 include("./class/pDraw.class.php"); 
 include("./class/pImage.class.php"); 

if(isset($_GET['id']))
{
        $id=$_GET['id'];
 }

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
if($row_number<60){$row_number=0;}
//data_lin1
$t = "<table>";
//echo $fields;
for($count=0;$count<$fields;$count++)
{
        $field=mysql_fetch_field($result,$count);
        $forfields[$count]=$field->name;
}
//data_lin2
$t = $t . "<tr><th>" . implode("</th><th>",$forfields) . "</th></tr>";
//取data
$rows=0;
while($row = mysql_fetch_array($result,MYSQL_NUM))
{
//data_lin3
$t = $t . "<tr><td>" . implode("</td><td>",$row) . "</td></tr>"; 
        for($count=0;$count<$fields;$count++)
        {
                $timestamp[$count][$rows] = $row[$count];
        }
        $rows++;
}
//data_line4
$t = $t . "</table>";
$MyData->addPoints(array_reverse($timestamp[0]),"Labels");
$MyData->setAbscissa("Labels"); 
 
for($count=1;$count<$fields;$count++)
{
        $field=mysql_fetch_field($result,$count);
        $forfields[$count]=$field->name;
        $MyData->addPoints(array_reverse($timestamp[$count]),$field->name);
        $MyData->setSerieWeight($field->name,1); 
}
 $myPicture = new pImage(950,450,$MyData); 

 /* Turn of Antialiasing */ 
 $myPicture->Antialias = TRUE; 

 /* Draw the background */ 
 $Settings = array("R"=>255, "G"=>255, "B"=>255, "Dash"=>0, "DashR"=>255, "DashG"=>255, "DashB"=>255); 
 $myPicture->drawFilledRectangle(0,0,700,450,$Settings); 


 /* Overlay with a gradient */ 
// $Settings = array("StartR"=>219, "StartG"=>231, "StartB"=>139, "EndR"=>1, "EndG"=>138, "EndB"=>68, "Alpha"=>50); 
// $myPicture->drawGradientArea(0,0,800,350,DIRECTION_VERTICAL,$Settings); 
// $myPicture->drawGradientArea(0,0,700,20,DIRECTION_VERTICAL,array("StartR"=>0,"StartG"=>0,"StartB"=>0,"EndR"=>50,"EndG"=>50,"EndB"=>50,"Alpha"=>80)); 

 /* Add a border to the picture */ 
 //$myPicture->drawRectangle(800,350,0,0,array("R"=>0,"G"=>0,"B"=>0)); 
  
 /* Write the chart title */  
// $myPicture->setFontProperties(array("FontName"=>"./fonts/simsun.ttc","FontSize"=>8,"R"=>255,"G"=>255,"B"=>255)); 
 //$myPicture->drawText(10,16,"Average recorded temperature",array("FontSize"=>11,"Align"=>TEXT_ALIGN_BOTTOMLEFT)); 

 /* Set the default font */ 
 $myPicture->setFontProperties(array("FontName"=>"./fonts/simsun.ttc","FontSize"=>10,"R"=>0,"G"=>0,"B"=>0)); 

 /* Define the chart area */ 
$myPicture->drawText(200,60,$chart_title);  
$myPicture->setFontProperties(array("FontName"=>"./fonts/simsun.ttc","FontSize"=>8,"R"=>0,"G"=>0,"B"=>0));
$myPicture->setGraphArea(40,60,800,400); 

 /* Draw the scale */ 
 $scaleSettings = array("XMargin"=>10,"YMargin"=>10,"Floating"=>TRUE,"GridR"=>200,"GridG"=>200,"GridB"=>200,"Mode"=>SCALE_MODE_START0,"DrawSubTicks"=>TRUE,"CycleBackground"=>TRUE,"LabelRotation"=>45,"LabelSkip"=>$row_number/60); 
 $myPicture->drawScale($scaleSettings); 

 /* Turn on Antialiasing */ 
 $myPicture->Antialias = TRUE; 

 /* Enable shadow computing */ 
 $myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10)); 

 /* Draw the line chart */ 
 $myPicture->drawLineChart(); 
 //$myPicture->drawPlotChart(array("DisplayValues"=>TRUE,"PlotBorder"=>TRUE,"BorderSize"=>2,"Surrounding"=>-60,"BorderAlpha"=>80)); 

 /* Write the chart legend */ 
//$myPicture->drawLegend(590,9,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL,"FontR"=>255,"FontG"=>255,"FontB"=>255)); 
//$myPicture->drawLegend(100,40,array("Style"=>LEGEND_FAMILY_BOX,"Mode"=>LEGEND_VERTICAL)); 
 $myPicture->drawLegend(800,150,0,255,255,255); 
 
  /* Render the picture (choose the best way) */ 
 $myPicture->Render("pictures/$chart_png.png");
 echo "<img src=\"pictures/$chart_png.png\" />";
//data_line5
echo $t;
?>
</body></html>
