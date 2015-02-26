<?php
	include("./class/pData.class.php"); 
	include("./class/pDraw.class.php"); 
	include("./class/pImage.class.php"); 
	include("./class/pPie.class.php"); 
	include("./colorSet.php");

	/* 数据处理 */
	// 初始化参数
	$id = 0;
	$r2c = 0;
	$r2c_order = 1;
	$sql_charset = 'latin1';

	// get方式，使用本地数据库已配置sql
	if(isset($_GET['id']))
	{
		$id=$_GET['id'];
		//read sql info and store
		$db1 = mysql_connect("localhost","root","fk@ipcdbserver");
		//mysql_unbuffered_query("set names utf8");
		$sql_s=mysql_query("SELECT * FROM chart_conf.chart_id_sql where id={$id}");
		if(mysql_num_rows($sql_s) == 1)
		{
			$ris=mysql_fetch_array($sql_s);
			//store sql info
			$host = $ris['host_port'];
			$user = $ris['user'];
			$pwd = $ris['pwd'];
			$sql_chart = $ris['sql_s'];
			$sql_charset = $ris['sql_charset'];
			$chart_title = $ris['chart_chinese'];
			$r2c = $ris['r2c'];
			if($ris['chart_cols']!='') $chart_cols = explode(',',$ris['chart_cols']);
			if($ris['chart_cols2']!='') $chart_cols2 = explode(',',$ris['chart_cols2']);
			if($ris['table_cols']!='') $table_cols = explode(',',$ris['table_cols']);
		}
		else
		{
			$err = "chart config error: id=$id";
		}
	}
	// post方式，参数配置sql
	else if(isset($_GET['method']) && strtolower($_GET['method'])=='post')
	{
		if(isset($_POST['host']) && isset($_POST['user']) && isset($_POST['pwd']) && isset($_POST['sql']) && isset($_POST['title']))
		{
			$host = iconv('gb2312','UTF-8',$_POST['host']);
			$user = iconv('gb2312','UTF-8',$_POST['user']);
			$pwd = iconv('gb2312','UTF-8',$_POST['pwd']);
			$sql_chart = iconv('gb2312','UTF-8',$_POST['sql']);
			$chart_title = iconv('gb2312','UTF-8',$_POST['title']);
			if(isset($_POST['png'])) $id=$_POST['png'];
		}
		else
		{
			$err = "method=post, url param error (host, user, pwd, sql, title)";
		}
	}
	// get方式，参数配置sql
	else if(isset($_GET['method']) && strtolower($_GET['method'])=='get')
	{
		if(isset($_GET['host']) && isset($_GET['user']) && isset($_GET['pwd']) && isset($_GET['sql']) && isset($_GET['title']))
		{
			$host = $_GET['host'];
			$user = $_GET['user'];
			$pwd = $_GET['pwd'];
			$sql_chart = $_GET['sql'];
			$chart_title = $_GET['title'];
			if(isset($_POST['png'])) $id=$_POST['png'];
		}
		else
		{
			$err = "method=get, url param error (host, user, pwd, sql, title)";
		} 
	}
	else
	{
		$err = "url param error,please check";
	}

	/* 数据提取和处理 */
	if(!isset($err))
	{
		// 数据处理相关参数获取

		// 根据输入sql_charset参数，确定sql语句执行的编码环境
		if(isset($_GET['sql_charset'])) $sql_charset = explode(',',$_GET['sql_charset']);
		if(isset($_POST['sql_charset'])) $sql_charset = explode(',',$_POST['sql_charset']);

		// 根据输入r2c参数，确定是否作行列转换
		if(isset($_GET['r2c'])) $r2c = $_GET['r2c'];
		if(isset($_POST['r2c'])) $r2c = $_POST['r2c'];

		if(isset($_GET['r2c_order'])) $r2c_order = $_GET['r2c_order'];
		if(isset($_POST['r2c_order'])) $r2c_order = $_POST['r2c_order'];

		// 根据输入chart_cols参数，确定chart显示列
		if(isset($_GET['chart_cols'])) $chart_cols = explode(',',$_GET['chart_cols']);
		if(isset($_POST['chart_cols'])) $chart_cols = explode(',',$_POST['chart_cols']);

		// 根据输入chart_cols2参数，确定chart显示列（特殊），注：当charttype=line,特殊列bar;当charttype=area2/bar/bar2,特殊列line
		if(isset($_GET['chart_cols2'])) $chart_cols2 = explode(',',$_GET['chart_cols2']);
		if(isset($_POST['chart_cols2'])) $chart_cols2 = explode(',',$_POST['chart_cols2']);

		// 根据输入table_cols参数，确定table显示列
		if(isset($_GET['table_cols'])) $table_cols = explode(',',$_GET['table_cols']);
		if(isset($_POST['table_cols'])) $table_cols = explode(',',$_POST['table_cols']);

		// 根据输入chart_limit参数，确定chart显示行数
//		$chart_limit = 1000;
		if(isset($_GET['chart_limit'])) $chart_limit = $_GET['chart_limit'];
		if(isset($_POST['chart_limit'])) $chart_limit = $_POST['chart_limit'];

		// 根据输入table_limit参数，确定table显示行数
		$table_limit = 1000;
		if(isset($_GET['table_limit'])) $table_limit = $_GET['table_limit'];
		if(isset($_POST['table_limit'])) $table_limit = $_POST['table_limit'];

		$sql_chart=str_ireplace('\\\'',"'",$sql_chart);

		// 提取数据
		$db2 = mysql_connect($host,$user,$pwd);
		mysql_query("set names $sql_charset",$db2);
		$result = mysql_query($sql_chart,$db2);

		if(!$result)
		{
			$err = "sql query error";
//			echo $sql_chart;
		}
		else if (mysql_num_rows($result)<=0)
		{
			$err = "empty result";
		}
	}

	// 结果数据入数组(按行)
	if(!isset($err))
	{
		// 行列转置
		if($r2c > 0)
		{
			$arrFields[] = mysql_fetch_field($result)->name;
			while($row = mysql_fetch_array($result))
			{
				$arrDataKey[$row[1]] += $row[2];
				$arrRows[$row[0]][$arrFields[0]] = $row[0];
				$arrRows[$row[0]][$row[1]] = $row[2];
			}
			if($r2c_order > 0) arsort($arrDataKey);
			$arrFields = array_merge($arrFields,array_keys($arrDataKey));
		}
		else
		{
			while($field = mysql_fetch_field($result)->name)
			{
				$arrFields[] = $field;
			}
			while($row = mysql_fetch_array($result))
			{
				$arrRows[] = $row;
			}
		}
		$numRows = count($arrRows);
		if(isset($table_limit)) $table_limit = min($table_limit, $numRows);
		$chart_limit = min($chart_limit, $numRows);
	}

	// 结果数据入数组(按列)&画表
	if(!isset($err))
	{
		// 画表&表标题
		$table_html = "<table><caption>$chart_title</caption>";

		// 画字段名
		$table_html .= "<tr>";
		while(list($idx,$field) = each($arrFields))
		{
			if(!isset($table_cols) or in_array($idx,$table_cols))
			{
				$table_html .= "<th>$field</th>";
			}
		}
		$table_html .= "</tr>";

		// 取每行数据至二维数组(按列)，画表数据
		$nTableRow = 0;
		foreach($arrRows as $row)
		{
			// 表行数控制
			$nTableRow += 1;
			if($nTableRow <= $table_limit)
			{
				$table_html .= "<tr>";
			}
			reset($arrFields);
			while(list($idx,$field) = each($arrFields))
			{
                // 数组行列转换

				if($idx == 0)
				{
					$arrCols[$field][] = $row[$field];
				}
				else
                {
                    $row[$field]=$row[$field]*100;
					$arrCols[$field][] = floatval($row[$field]);
				}
				// 表列和行数控制
				if((!isset($table_cols) or in_array($idx,$table_cols)) and $nTableRow <= $table_limit)
				{
					$table_html .= "<td>$row[$field]</td>";
				}
			}
			// 表行数控制
			if($nTableRow <= $table_limit)
			{
				$table_html .= "</tr>";
			}
		}
		$table_html .= "</table>";
	}

	// 结果类型：图chart/表table/图加表charttable
	$resultType = 'chart';
	if(isset($_GET['r'])) $resultType = strtolower($_GET['r']);

	/* 表table */
	if($resultType=='table')
	{
		echo '<html>';
		echo '<head>';
		echo '<meta http-equiv="content-type" content="text/html; charset=utf-8" />';
		echo '<link rel="stylesheet" type="text/css" href="./datatb.css" />';
		echo '<title>pchart_config</title>';
		echo '</head>';
		echo '<body>';
		if(isset($err))
		{
			echo $err;
		}else
		{
			echo $table_html;
		}
		echo '</body>';
		echo '</html>';
		exit;
	}

	/* 图chart */

	// 图片相关参数获取
	// 长宽
	$width = 950;
	$height = 450;
	if(isset($_GET['width'])) $width = max($_GET['width'],200);
	if(isset($_GET['height'])) $height = max($_GET['height'],150);

	// 外框和背景
	$bg = '1';
	if(isset($_GET['bg'])) $bg = $_GET['bg'];

	// 字号&标题字号
	$font = 9;
	$font_t = 12;
	if(isset($_GET['font'])) $font = $_GET['font'];
	if(isset($_GET['font_t'])) $font_t = $_GET['font_t'];

	// 图表类型
	$chartType = 'line';
	if(isset($_GET['charttype'])) $chartType = strtolower($_GET['charttype']);

	// 图表方向
	$chartRotation = 0;
	if(isset($_GET['rotation'])) $chartRotation = strtolower($_GET['rotation']);

	// label跳跃（-1即auto）
	$labelSkip = -1;
	if(isset($_GET['skip'])) $labelSkip = strtolower($_GET['skip']);

	// 图例
	$legend = 1;
	if(isset($_GET['legend'])) $legend = $_GET['legend'];

	// PIE数据值
	$pieValue = 2;
	if(isset($_GET['pievalue'])) $pieValue = $_GET['pievalue'];

	// PIE数据总量
	$pieSum = 1;
	if(isset($_GET['piesum'])) $pieSum = $_GET['piesum'];

	// 折线粗细
	$weight = 1.5;
	if(isset($_GET['weight'])) $weight = $_GET['weight'];

	// 线图数据点/值
	$plot = 0;
	if(isset($_GET['plot'])) $plot = $_GET['plot'];

	// 线图数据点/值(形状)
	$plotShape = -1;
	if(isset($_GET['plotshape'])) $plotShape = $_GET['plotshape'];

	// 最值
	$bound = 'none';
	if(isset($_GET['bound'])) $bound = strtolower($_GET['bound']);

	// 纵坐标单位(主坐标轴)
	$unitY = '';
	if(isset($_GET['unity'])) $unitY = strtolower($_GET['unity']);

	// 颜色
	if(isset($_GET['color'])) $palette = $paletteSet[$_GET['color']];

	/* Create and populate the pData object */
	$myData = new pData();

	/* Create the pChart object */
	$myPicture = new pImage($width,$height,$myData); 

	/* Turn of Antialiasing */ 
	$myPicture->Antialias = TRUE; 

	$myPicture->setFontProperties(array("FontName"=>"./fonts/simhei.ttf","FontSize"=>$font_t,"R"=>0,"G"=>0,"B"=>0));
	
	/* Draw the background */
	$bgcolor=array("R"=>180, "G"=>180, "B"=>180);
	$scaleRGB = array("GridR"=>200,"GridG"=>200,"GridB"=>200);
	if($bg == 0)
	{
		$myPicture->drawRectangle(1,0,$width-1,$height-1,array("R"=>0,"G"=>0,"B"=>0));
	}
	else if($bg == 1)
	{
		if(isset($palette)) $bgcolor=$palette['bgcolor'];
		$myPicture->drawFilledRectangle(3,0,$width-1,$height-4,array("R"=>$bgcolor['R'],"G"=>$bgcolor['G'],"B"=>$bgcolor['B'],"Dash"=>0, "DashR"=>255, "DashG"=>255, "DashB"=>255)); 
		$myPicture->drawRectangle(3,0,$width-1,$height-4,array("R"=>0,"G"=>0,"B"=>0));
		$myPicture->drawFilledRectangle(0,3,$width-4,$height-1,array("R"=>255, "G"=>255, "B"=>255, "Alpha"=>90)); 
		$myPicture->drawRectangle(0,3,$width-4,$height-1,array("R"=>0,"G"=>0,"B"=>0));

		$scaleRGB = array("GridR"=>0,"GridG"=>0,"GridB"=>0,"GridAlpha"=>10);
	 }
	else if($bg == 2)
	{
		$myPicture->drawGradientArea(0,0,$width,$height,DIRECTION_VERTICAL,array("StartR"=>240,"StartG"=>240,"StartB"=>240,"EndR"=>80,"EndG"=>80,"EndB"=>80,"Alpha"=>100));
		$myPicture->drawGradientArea(0,0,$width,$height,DIRECTION_HORIZONTAL,array("StartR"=>240,"StartG"=>240,"StartB"=>240,"EndR"=>80,"EndG"=>80,"EndB"=>80,"Alpha"=>20));
		$myPicture->drawRectangle(0,0,$width-1,$height-1,array("R"=>0,"G"=>0,"B"=>0));

		$myPicture->setFontProperties(array("FontName"=>"./fonts/msyh.ttf","FontSize"=>$font_t,"R"=>0,"G"=>0,"B"=>0));
		$scaleRGB = array("GridR"=>200,"GridG"=>200,"GridB"=>200,"GridAlpha"=>100);
	}
	else if($bg == 3)
	{
		$myPicture->drawGradientArea(0,0,$width,$height,DIRECTION_VERTICAL,array("StartR"=>240,"StartG"=>240,"StartB"=>240,"EndR"=>180,"EndG"=>180,"EndB"=>180,"Alpha"=>100));
		$myPicture->drawGradientArea(0,0,$width,$height,DIRECTION_HORIZONTAL,array("StartR"=>240,"StartG"=>240,"StartB"=>240,"EndR"=>180,"EndG"=>180,"EndB"=>180,"Alpha"=>20));
		$myPicture->drawRectangle(0,0,$width-1,$height-1,array("R"=>0,"G"=>0,"B"=>0));

		$myPicture->setFontProperties(array("FontName"=>"./fonts/msyh.ttf","FontSize"=>$font_t,"R"=>0,"G"=>0,"B"=>0));
		$scaleRGB = array("GridR"=>200,"GridG"=>200,"GridB"=>200);
	}

	/* Draw Title */
	$marg = min(min($width,$height)*0.04,10);
	$myPicture->drawText($marg,$marg,$chart_title,array("Align"=>TEXT_ALIGN_TOPLEFT));

	/* Deal Chart Data and Draw Chart*/
	if(isset($err))
	{
		$myPicture->drawText($width*0.3,$height*0.5,$err);
	}else
	{
		/* Draw PieChart */
		if($chartType=='pie3d' or $chartType=='pie2d')
		{
			/* Deal Chart Data */
			if(!isset($chart_limit)) $chart_limit = 1;
			$rowPie = $chart_limit - 1;
			reset($arrFields);
			while(list($idx,$field) = each($arrFields))
			{
				if($idx == 0)
				{
					$pieValueKeyLabel = $field;
					$pieValueKey = $arrCols[$field][$rowPie];
				}
				else if(!isset($chart_cols) or in_array($idx,$chart_cols))
				{
					$arrPieLabels[] = $field;
					$arrPieValues[] = $arrCols[$field][$rowPie];
				}
			}
			$myData->addPoints($arrPieLabels,'pieLabel');
			$myData->addPoints($arrPieValues,$pieValueKey);
			$abscissa = 'pieLabel';
			$myData->setAbscissa($abscissa);

			/* Create the pPie object */
			$PieChart = new pPie($myPicture,$myData);
			$myPicture->setFontProperties(array("FontName"=>"./fonts/msyh.ttf","FontSize"=>$font,"R"=>0,"G"=>0,"B"=>0)); 

			/* Define the slice color */
			// 若设置了色板，配入饼图
			if(isset($palette))
			{
				for($slice = 0; $slice < count($arrPieLabels); $slice ++)
				{
					$PieChart->setSliceColor($slice,$palette['serie'][$slice % $palette['serie_num']]);
				}
			}

			/* Draw Pie */
			// 设置数值显示类型，1：显示数值，2：显示比例，否则不显示;
			$WriteValues = NULL;
			if($pieValue == 1) $WriteValues = PIE_VALUE_NATURAL;
			if($pieValue == 2) $WriteValues = PIE_VALUE_PERCENTAGE;

			// 显示总量
			if($pieSum == 1) $myPicture->drawText($width-$marg-2,$marg+2,$pieValueKeyLabel.":".$pieValueKey." 总量:".array_sum($arrPieValues),array("Align"=>TEXT_ALIGN_TOPRIGHT, "BorderOffset"=>5, "R"=>55, "G"=>55, "B"=>55, "DrawBox"=>TRUE, "BoxRounded"=>TRUE, "BoxR"=>100, "BoxG"=>100, "BoxB"=>100, "BoxAlpha"=>20));

			$myPicture->setShadow(TRUE,array("X"=>2,"Y"=>4,"R"=>150,"G"=>150,"B"=>150,"Alpha"=>80));

			$pieSettings = array("DrawLabels"=>TRUE,"LabelStacked"=>FALSE,"LabelColor"=>PIE_LABEL_COLOR_MANUAL,"WriteValues"=>$WriteValues,"ValuePosition"=>PIE_VALUE_INSIDE);
			// 画2D饼图
			if($chartType == 'pie2d') $PieChart->draw2DPie($width*0.5,$height*0.53,array("Radius"=>min($height,$width*0.7)*0.3,"Border"=>TRUE,"ValueR"=>255,"ValueG"=>255,"ValueB"=>255,"ValueAlpha"=>100,"LabelStacked"=>FALSE) + $pieSettings);
			// 画3D饼图
			if($chartType == 'pie3d') $PieChart->draw3DPie($width*0.5,$height*0.57,array("Radius"=>min($height,$width*0.7)*0.4,"SliceHeight"=>min($height,$width*0.7)*0.08,"DataGapAngle"=>5,"DataGapRadius"=>min($height,$width*0.7)*0.02,"ValueR"=>0,"ValueG"=>0,"ValueB"=>0,"ValueAlpha"=>60,"LabelStacked"=>FALSE) + $pieSettings);

			/* Draw Pie Legend */
			// 根据设置显示图例，1：纵向，2：横向，否则不显示
			if($legend == 1) $PieChart->drawPieLegend($width*0.9-60,$marg+$font_t*3,array("Align"=>TEXT_ALIGN_TOPRIGHT,"Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_VERTICAL));
			if($legend == 2) $PieChart->drawPieLegend($marg,$height-$marg-$font*2,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL));
		}
		/* Draw Other Chart */
		else
		{
			$nSerie = 0;
			if(!isset($chart_limit)) $chart_limit=1000;
			$plotShapes = array(SERIE_SHAPE_FILLEDCIRCLE,SERIE_SHAPE_FILLEDTRIANGLE,SERIE_SHAPE_FILLEDSQUARE,SERIE_SHAPE_FILLEDDIAMOND);
			reset($arrFields);
			while(list($idx,$field) = each($arrFields))
			{
				if(!isset($chart_cols) or $idx == 0 or in_array($idx,$chart_cols))
				{
					$arrSeries[] = $field;
					$myData->addPoints(array_reverse(array_slice($arrCols[$field],0,$chart_limit)),$field);
					$myData->setSerieWeight($field,$weight);

					if($plotShape >= 0 and $plotShape < count($plotShapes)) 
					{
						$myData->setSerieShape($field,$plotShapes[$plotShape]);
					}
					else
					{
						$myData->setSerieShape($field,$plotShapes[array_rand($plotShapes,1)]);
					}

					if(isset($palette)) $myData->setPalette($field,$palette['serie'][($nSerie-1)%$palette['serie_num']]);

					if($nSerie == 0)
					{
						$abscissa = $field;
						$myData->setAbscissa($abscissa);
					}
					else if(isset($chart_cols2) and in_array($idx,$chart_cols2))
					{
						$arrSeriesY2[] = $field;
						$myData->setSerieOnAxis($field,1);
						$myData->setSerieDescription($field,"右）$field");
					}
					$nSerie += 1;
				}
			}
			if(isset($arrSeriesY2)) $myData->setAxisPosition(1,AXIS_POSITION_RIGHT);
			$myData->setAxisUnit(0,$unitY);

			/* Write the chart legend */ 
			$sizeLegend = $myPicture->getLegendSize(array("Mode"=>LEGEND_VERTICAL));
			$widthLegend = min($sizeLegend['Width'],max(120,$width*0.25));
			if($legend>0)
			{
				$myPicture->setFontProperties(array("FontName"=>"./fonts/simsun.ttc","FontSize"=>$font,"R"=>0,"G"=>0,"B"=>0));
				$myPicture->drawLegend($width-5-$widthLegend,5+$font_t*3,array("Style"=>LEGEND_BOX,"Mode"=>LEGEND_VERTICAL,"Alpha"=>10,"Margin"=>4,"R"=>255, "G"=>255, "B"=>255)); 
			}

			$myPicture->setFontProperties(array("FontName"=>"./fonts/simhei.ttf","FontSize"=>$font,"R"=>0,"G"=>0,"B"=>0));
			/* Define the chart area */
			$width_offset = 0;
			if($legend > 0) $width_offset += max($widthLegend,50);
			if(isset($arrSeriesY2)) $width_offset += 15+$font*3;
			$myPicture->setGraphArea(28+$font*3,3+$font_t*3,$width-15-$width_offset,$height-23-$font*3);

			/* Draw the scale */ 
			$scaleMode = ($chartType=='bar2' or $chartType=='area2') ? SCALE_MODE_ADDALL_START0 : SCALE_MODE_START0;
			if($labelSkip == -1) $labelSkip = round($myData->getSerieCount($abscissa)/(($width-15-$width_offset)/30));
			$scaleSettings = $scaleRGB + array("FLOATING"=>TRUE,"DrawSubTicks"=>TRUE,"Mode"=>$scaleMode,"CycleBackground"=>TRUE,"LabelRotation"=>30,"LabelSkip"=>$labelSkip);
			if($chartRotation == 1) $scaleSettings = array("Pos"=>SCALE_POS_TOPBOTTOM,"LabelRotation"=>0) + $scaleSettings;
			$myPicture->drawScale($scaleSettings); 

			// 隐藏副坐标轴数据
			if(isset($arrSeriesY2))
			{
				foreach($arrSeriesY2 as $serie)
				{
					$myData->setSerieDrawable($serie,FALSE);
				}
			}

			$arrDisplayValues = array("DisplayValues"=>FALSE);
			if($plot==2) $arrDisplayValues["DisplayValues"] = TRUE;

			/* Enable shadow computing */ 
			$myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10)); 

			/* Draw the stacked area chart */
			if($chartType=='area2')
			{
				$myPicture->drawStackedAreaChart();
			}
			/* Draw the bar chart */
			else if($chartType=='bar')
			{
				$myPicture->drawBarChart(array("Surrounding"=>-15,"InnerSurrounding"=>15)+$arrDisplayValues);
			}
			/* Draw the stacked bar chart */
			else if($chartType=='bar2')
			{
				$myPicture->drawStackedBarChart();
			}
			/* Draw the line chart(default) */
			else
			{
				$myPicture->drawLineChart(); 
				if($plot > 0) $myPicture->drawPlotChart($arrDisplayValues+array("PlotSize"=>$weight*1.5+3,"PlotBorder"=>TRUE,"BorderSize"=>0.8,"BorderAlpha"=>75,"Surrounding"=>-30));
			}

			// 隐藏主坐标轴数据，画副坐标轴数据
			if(isset($arrSeriesY2))
			{
				foreach($arrSeries as $serie)
				{
					if(in_array($serie,$arrSeriesY2))
					{
						$myData->setSerieDrawable($serie,TRUE);
					}
					else
					{
						$myData->setSerieDrawable($serie,FALSE);
					}
				}
				$myPicture->drawLineChart();
				if($plot==1) $myPicture->drawPlotChart(array("DisplayValues"=>FALSE,"PlotSize"=>$weight*1.5+3,"PlotBorder"=>TRUE,"BorderSize"=>0.8,"BorderAlpha"=>75,"Surrounding"=>-30));
				if($plot==2) $myPicture->drawPlotChart(array("DisplayValues"=>TRUE,"PlotSize"=>$weight*1.5+3,"PlotBorder"=>TRUE,"BorderSize"=>0.8,"BorderAlpha"=>75,"Surrounding"=>-30));
			}
			$myData->drawAll();

			/* Write the data bounds */
			$boundSetting=array("DrawBox"=>FALSE,"MaxLabelTxt"=>'Max:',"MinLabelTxt"=>'Min:',"MinDisplayR"=>0,"MinDisplayG"=>0,"MinDisplayB"=>0,"DisplayOffset"=>5);
			if($bound=='min') $myPicture->writeBounds(BOUND_MIN,$boundSetting);
			if($bound=='max') $myPicture->writeBounds(BOUND_MAX,$boundSetting);
			if($bound=='both') $myPicture->writeBounds(BOUND_BOTH,$boundSetting);
		}
	}

	/* Render the picture (choose the best way) */ 
	if($resultType=='charttable')
	{
		$myPicture->Render("pictures/chart_png_$id.png");
		echo '<html>';
		echo '<head>';
		echo '<meta http-equiv="content-type" content="text/html; charset=utf-8" />';
		echo '<link rel="stylesheet" type="text/css" href="./datatb.css" />';
		echo '<title>pchart_config</title>';
		echo '</head>';
		echo '<body>';
		echo "<img src='pictures/chart_png_$id.png' />";
		if(isset($err))
		{
			echo $err;
		}else
		{
			echo $table_html;
		}
		echo '</body>';
		echo '</html>';
		exit;
	}

	if($resultType=='png')
	{
		$myPicture->Render("pictures/chart_png_$id.png");
		exit;
	}

	$myPicture->Stroke();
?>
