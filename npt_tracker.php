<?php
	require_once('db.php');
	require_once('include.search.php');
	require_once('PHPExcel.php');
	require_once('PHPExcel/Writer/Excel2007.php');
	require_once('include.excel.php');
	require_once 'PHPExcel/IOFactory.php';
	require_once('special_chars.php');
	require_once('include.util.php');
	if($_SERVER['QUERY_STRING'] == 'logout') $db->logout();	
	$phaseValues = array('N/A'=>'#BFBFBF', '0'=>'#00CCFF', '0/1'=>'#99CC00', '1'=>'#99CC00', '1a'=>'#99CC00', '1b'=>'#99CC00', '1a/1b'=>'#99CC00', '1c'=>'#99CC00', '1/2'=>'#FFFF00', '1b/2'=>'#FFFF00', '1b/2a'=>'#FFFF00', '2'=>'#FFFF00', '2a'=>'#FFFF00', '2a/2b'=>'#FFFF00', '2a/b'=>'#FFFF00', '2b'=>'#FFFF00', '2/3'=>'#FF9900', '2b/3'=>'#FF9900','3'=>'#FF9900', '3a'=>'#FF9900', '3b'=>'#FF9900','3/4'=>'#FF0000', '3b/4'=>'#FF0000', '4'=>'#FF0000');
	$tid = NULL;
	$ProductName = "";
	$noOfTrials = array();
	if($_REQUEST['tid'] != NULL && $_REQUEST['tid'] != '' && isset($_REQUEST['tid']))
	{
		$tid = $_REQUEST['tid'];		
		$ProductName = $_REQUEST['nptname'];
		$noOfTrials = explode(",",$tid);
	}
	if(isset($_POST['btnDownload']) && $_POST['btnDownload'] == "Download File"){
		//print_r($_POST);die();
		if($_POST['wFormat'] == "excel"){
			generateExcelFile($_POST['larvol_ids'],$_POST['larvol_name']);exit;
		}elseif($_POST['wFormat'] == "tsv"){
			generateTsvFile($_POST['larvol_ids']);exit;
		}
	}
	require_once('header.php');	
?>
	<style>
	.manage {
		border-bottom: 1px solid #0000FF;
		border-left: 1px solid #0000FF;
		border-top: 1px solid #0000FF;
		table-layout: fixed;
		border-spacing:0px;
		width: 100%;
		font-family:arial;
	}
	.manage th {
		border-right: 1px solid #0000FF;
		color: #54319F;
		height: 25px;
		margin: 0;
		padding: 0;
		background-color: #FFFFFF;
		text-align: center;
	}
	.manage td {
		border-right: 1px solid #0000FF;
		border-top: 1px solid #0000FF;
		margin: 0;
		overflow: hidden;
		padding: 0;
		vertical-align: top;
	}
	.title {
		background-color: #EDEAFF;
	}
	.sectiontitles {
		background-color: #A2FF97 !important;
		text-align: left;
	}
	.rowcollapse a{
		text-decoration:none;
	}
	#slideout table {
		border-spacing:0px;
	}
	#slideout {
		margin: 12px 0 0;
		position: fixed;
		right: 0;
		top: 40px;
		z-index: 999;
	}
	.slideout_inner {
		display: none;
		position: absolute;
		right: -255px;
		top: 40px;
	}
	#slideout:hover .slideout_inner {
		display: block;
		position: absolute;
		right: 0;
		top: 2px;
		width: 280px;
		z-index: 10;
	}
	.table-slide {
		border: 1px solid #000000;
	}
	.table-slide td {
		border-bottom: 1px solid #000000;
		border-right: 1px solid #000000;
		padding: 8px 20px 8px 8px;
		background:none repeat scroll 0 0 #FFFFFF;
	}
	.table-slide tr td img{
		width:auto;
		height:auto;
		border-style:none;
	}
	.gray {
		background-color: #CCCCCC;
		float: left;
		height: 18px;
		margin-right: 1px;
		padding-top: 3px;
		text-align: center;
		width: 35px;
	}
	.blue {
		background-color: #00CCFF;
		float: left;
		height: 18px;
		margin-right: 1px;
		padding-top: 3px;
		text-align: center;
		width: 35px;
	}
	.green {
		background-color: #99CC00;
		float: left;
		height: 18px;
		margin-right: 1px;
		padding-top: 3px;
		text-align: center;
		width: 35px;
	}
	.yellow {
		background-color: #FFFF00;
		float: left;
		height: 18px;
		margin-right: 1px;
		padding-top: 3px;
		text-align: center;
		width: 35px;
	}
	.orange {
		background-color: #FF9900;
		float: left;
		height: 18px;
		margin-right: 1px;
		padding-top: 3px;
		text-align: center;
		width: 35px;
	}
	.red {
		background-color: #FF0000;
		float: left;
		height: 18px;
		margin-right: 1px;
		padding-top: 3px;
		text-align: center;
		width: 35px;
	}
	.controls {
		border: medium none !important;
		font-family: Verdana,Geneva,sans-serif;
	}
	.controls td {
		border-bottom: 1px solid #4444FF;
		border-right: 1px solid #4444FF;
		padding: 10px 0 0 3px;
		vertical-align: top;
	}
	.controls th {
		border-bottom: 1px solid #4444FF;
		border-right: 1px solid #4444FF;
		font-weight: normal;
	}
	.right {
		border-right: 0 none !important;
	}
	.bottom {
		border-bottom: 0 none !important;
	}
	#parent {
		height: 100%;
		margin-bottom: 35px;
		width: 100%;
	}
	#fulltextsearchbox {
		float: left;
		margin: -1px 10px 0 0;
		width: 156px;
	}
	#fulltextsearchbox input {
		height: 17px;
	}
	.advanced {
		border: 1px solid #000000;
		cursor: pointer;
		float: left;
		font-weight: bold;
		margin-right: 10px;
		padding: 2px;
	}
	.records {
		background-color: #EDEAFF;
		float: left;
		font-weight: bold;
		height: 18px;
		padding: 2px 4px;
	}
	#buttons {
		float: left;
		margin-right: 10px;
		margin-top: -2px;
	}
	#osflt_div {
		border-top: 1px solid #4444FF;
		margin: 5px 5px 0 0;
		padding-top: 5px;
	}
	#osflt_div label {
		vertical-align: text-top;
	}
	.searchbutton {
		background-color: #48006F;
		color: #FFFFFF;
		height: 25px;
		width: 63px;
	}
	.resetbutton {
		background-color: #48006F;
		color: #FFFFFF;
		height: 25px;
		width: 61px;
	}
	#outercontainer {
		background-color: #EDEAFF;
		float: left;
		height: 20px;
		line-height: 1.4em;
		margin-right: 10px;
		overflow: auto;
		padding: 0 5px 2px 2px;
		width: 12%;
	}
	.milestones, .export {
		float: left;
		height: 20px;
		vertical-align: bottom;
	}

	.export div {
		background: url("images/save.png") no-repeat scroll left center rgba(0, 0, 0, 0);
		border: 1px solid;
		color: #000000;
		cursor: pointer;
		font-family: arial;
		padding: 2px;
	}
	.dropmenudiv {
		background-color: #FFFFFF;
		border: 1px solid #DDDDDD;
		font: 12px/18px Verdana;
		position: absolute;
		top: 0;
		visibility: hidden;
		width: 50px;
		z-index: 100;
	}
	.viewcount {
		background: url("../images/viewcount.png") no-repeat scroll left center rgba(0, 0, 0, 0);
		float: left;
		height: 16px;
		text-align: center;
		width: 16px;
	}
	.startdatehighlight {
		border-right-color: #FF0000 !important;
	}
	.tag {
		color: #120F3C;
		font-weight: normal;
	}
	.demo #slider-range, .demo #slider-range-min {
		margin: 0 10px;
		width: 382px;
	}
	.demo p {
		margin-bottom: 3px;
	}
	.downldbox {
		font-weight: bold;
		height: auto;
		width: 310px;
	}

	.downldbox ul {
		list-style: none outside none;
		margin: 5px;
		padding: 0;
	}
	.downldbox ul li {
		float: left;
		margin: 2px;
		width: 130px;
	}
	.downldbox ul li label{
		background:transparent;
		font-family:arial;
	}
	</style>
	<script type="text/javascript" src="scripts/chrome.js?t="></script>
	<script type="text/javascript" src="scripts/jquery.hoverIntent.minified.js?t="></script>
	<script>
		$(function() {
		
			var config = {    
				 over: makeTall, // function = onMouseOver callback (REQUIRED)    
				 timeout: 500, // number = milliseconds delay before onMouseOut    
				 out: makeShort// function = onMouseOut callback (REQUIRED)   
			};
			
			//If javascript works override stylesheet  - as hover css and JS tries to execute same time at first instance
			$(".rowcollapse").css("height" , "16px");
			
			function makeTall()
			{  
				//JQuery animate function does not work correctly for 100% height, so first retrieve actual height by setting it to 100% then, give actual height to animate function
				var fullHeight = $(this).css("height" , "100%").height();	//get actual height
				$(this).css("height" , "16px"); //reset back height
				$(this).animate({height:fullHeight}, 500);	//give actual height
			}

			function makeShort(){ $(this).animate({"height":"16px"}, 500);}

			$(".rowcollapse").hoverIntent(config);
		});
	</script>
	<br/>
	<div style="width:95%;margin:0 auto;">
		<div id="parent" style="width: 1272px;">
			<!--<div id="togglefilters" class="advanced"><img style="vertical-align:bottom;" alt="Show Filter" 	src="images/funnel.png">&nbsp;Advanced</div>-->
			<div class="records"><?php echo sizeof($noOfTrials);?>&nbsp;Trials</div>
			<div align="left" id="outercontainer" style="width: 686px;" class="mCustomScrollbar _mCS_1">
				<div style="position:relative; height:100%; overflow:hidden; _width:100%;max-width:100%;" id="mCSB_1" class="mCustomScrollBox mCSB_horizontal">
					<div style="position: relative; left: 0px; width: 78px;" class="mCSB_container mCS_no_scrollbar">
						<p style="overflow:hidden;margin: 0;">
							<span class="filters">
								<label>All Trials</label>
								<a href=""><img alt="Remove Filter" src="images/black-cancel.png"></a>
							</span>
						</p>
					</div>
					<div style="position: absolute; display: none;" class="mCSB_scrollTools">
						<div style="position:relative;" class="mCSB_draggerContainer">
							<div style="position: absolute; left: 0px;" class="mCSB_dragger">
								<div style="position:relative;" class="mCSB_dragger_bar"></div>
							</div>
							<div class="mCSB_draggerRail"></div>
						</div>
					</div>
				</div>
			</div>
			<div id="fulltextsearchbox">
				<input type="text" value="" style="width:153px;" autocomplete="off" name="ss">
			</div>
			<div id="buttons">
				<input type="submit" class="searchbutton" value="Search" id="Show">&nbsp;<a href="" style="display:inline;"><input type="button" onclick="" class="resetbutton" id="reset" value="Reset"></a>
			</div>
			<div style="width:64px;" id="chromemenu" class="export">
				<div>
					<a rel="dropmenu"><b style="margin-left:16px;">Export</b> </a>
				</div>
			</div>
			<div style="width: 310px;" class="dropmenudiv" id="dropmenu">
				<div style="height:180px; padding:6px;">
					<div class="downldbox">
						<div class="newtext">Download Options</div>
						<form action="" target="_self" method="post" name="frmDOptions" id="frmDOptions" >
							<ul>
								<li><label>Number of Studies: </label></li>
								<li><select style="height:54px;" size="2" name="dOption" id="dOption"><option selected="selected" value="shown"><?php echo sizeof($noOfTrials);?> Shown Studies</option><option value="all"><?php echo sizeof($noOfTrials);?> Found Studies</option></select></li>
								<li><label>Which Format: </label></li>
								<li><select style="height:54px;" size="3" name="wFormat" id="wFormat"><option selected="selected" value="excel">Excel</option><option value="tsv">TSV</option></select></li>
							</ul>
							<input type="hidden" value="<?php echo $tid;?>" name="larvol_ids"/>
							<input type="hidden" value="<?php echo $ProductName;?>" name="larvol_name"/>
							<input type="submit" style="margin-left:8px;" value="Download File" name="btnDownload" id="btnDownload">
						</form>
					</div>
				</div>
			</div>
			<script>
				cssdropdown.startchrome("chromemenu");
			</script>
			</div>
			<div style="top:200px;" id="slideout">
				<img alt="Help" src="images/help.png">
				<div class="slideout_inner">
					<table cellspacing="0" cellpadding="0" bgcolor="#FFFFFF" class="table-slide">
						<tbody>
							<tr>
								<td width="15%"><img alt="Data release" src="images/black-diamond.png"></td><td>Click for data release</td>
							</tr>
							<tr>
								<td>
									<img alt="Data release (new)" src="images/red-diamond.png"></td><td>Click for data release (new)
								</td>
							</tr>
							<tr>
								<td>
									<img alt="Results pending" src="images/hourglass.png"></td><td>Results pending
								</td>
							</tr>
							<tr>
								<td><img alt="Milestone result" src="images/black-checkmark.png"></td><td>Click for milestone result</td>
							</tr>
							<tr>
								<td><img alt="Milestone result (new)" src="images/red-checkmark.png"></td><td>Click for milestone result (new)</td>
							</tr>
							<tr>
								<td><img alt="Milestone details" src="images/purple-bar.png"></td><td>Click for milestone details</td>
							</tr>
							<tr>
								<td><img alt="Milestones" src="images/down.png"></td><td>Display milestones</td>
							</tr>
							<tr>
								<td style="padding-right: 1px;" colspan="2">
									<div style="float:left;padding-top:3px;">Phase&nbsp;</div>
									<div class="gray">N/A</div>
									<div class="blue">0</div>
									<div class="green">1</div>
									<div class="yellow">2</div>
									<div class="orange">3</div>
									<div class="red">4</div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div style="width:95%;margin:0 auto;">
			<table cellspacing="0" cellpadding="0" class="manage">
				<tbody>
					<tr>
						<th style="width:70px;">ID</th>
						<th style="width:270px;">Title</th>
						<th title="Red: Change greater than 20%" style="width:30px;">N</th>
						<th title="&quot;RoW&quot; = Rest of World" style="width:64px;">Region</th>
						<th style="width:100px;">Interventions</th>
						<th style="width:90px;">Sponsor</th>
						<th style="width:105px;">Status</th>
						<th style="width:100px;">Conditions</th>
						<th style="width:33px;" title="MM/YY">End</th>
						<th style="width:25px;">Ph</th>
						<th style="width:25px;">Data</th>
						<th style="width:12px;" colspan="3">-</th>
						<th style="width:32px;" colspan="12">2013</th>
						<th style="width:32px;" colspan="12">2014</th>
						<th style="width:32px;" colspan="12">2015</th>
						<th style="width:12px;" colspan="3">+</th>
					</tr>
					<tr>
						<td class="sectiontitles" colspan="53"><?php echo $ProductName; ?></td>
					</tr>
					<?php
						$getTrialList = "SELECT * FROM data_trials where larvol_id IN($tid)";
						$trialsList = mysql_query($getTrialList);
						while($tl = mysql_fetch_array($trialsList)){				
							$nctId = "";
							if(isset($tl['source_id']) && strpos($tl['source_id'], 'NCT') === FALSE){	
								$nctId = $tl['source_id'];
								$nctIdText = unpadnct($nctId);
								$trailLink = 'https://www.clinicaltrialsregister.eu/ctr-search/search?query=' . $nctId;
							}
							else if(isset($tl['source_id']) && strpos($tl['source_id'], 'NCT') !== FALSE)
							{
								$trailLink = 'http://clinicaltrials.gov/ct2/show/' . padnct($nctId);
							}
							else{ 
								$trailLink = 'javascript:void(0)';
							}
							$trailLink = urlencode($trailLink);
							
							$startDate = '';
							$endDate = '';
							$phase = '';
							if($tl["start_date"] != '' && $tl["start_date"] !== NULL && $tl["start_date"] != '0000-00-00'){
								$startDate = date('m/y', strtotime($tl['start_date']));
							}
							if($tl["end_date"] != '' && $tl["end_date"] !== NULL && $tl["end_date"] != '0000-00-00'){
								$endDate = date('m/y', strtotime($tl['end_date']));
							}
							if($tl['phase'] == 'N/A' || $tl['phase'] == '' || $tl['phase'] === NULL){
								$phase = 'N/A';
							}else{
								$phase = str_replace('Phase ', '', trim($tl['phase']));
							}							
					?>
						<tr>
							<td class="title">
								<div class="rowcollapse" style="height: 16px;">
									<a target="_blank" href="edit_trials.php?larvol_id=<?php echo $tl['larvol_id'];?>"><?php echo $tl['source_id'];?></a>
								</div>
							</td>
							<td class="title " rowspan="1">
								<div class="rowcollapse" style="height: 16px;">
									<a target="_blank" <?php echo $trailLink;?> style="color:#000000;"><font id="ViewCount_139317"></font><?php echo $tl['brief_title'];?></a>
								</div>
							</td>
							<td nowrap="nowrap" class="title " rowspan="1">
								<div style="color: rgb(0, 0, 0); height: 16px;" class="rowcollapse"><?php echo $tl['enrollment'];?></div>
							</td>
							<td class="title " rowspan="1">
								<div style="color: rgb(0, 0, 0); height: 16px;" class="rowcollapse"><?php echo $tl['region'];?></div>
							</td>
							<td class="title " rowspan="1">
								<div style="color: rgb(0, 0, 0); height: 16px;" class="rowcollapse"> <?php echo str_replace("`",",",$tl['intervention_name']);?></div>
							</td>
							<td class="title " rowspan="1">
								<div style="color: rgb(0, 0, 0); height: 16px;" class="rowcollapse"> <?php echo $tl['lead_sponsor'];?></div>
							</td>
							<td class="title " rowspan="1">
								<div style="color: rgb(0, 0, 0); height: 16px;" class="rowcollapse"> <?php echo $tl['overall_status'];?></div>
							</td>
							<td class="title " rowspan="1">
								<div style="color: rgb(0, 0, 0); height: 16px;" class="rowcollapse"> <?php echo $tl['condition'];?></div>
							</td>
							<td class="title " rowspan="1">
								<div style="color: rgb(0, 0, 0); height: 16px;" class="rowcollapse"><?php echo $endDate;?></div>
							</td>
							<td class="title " rowspan="1">
								<div style="color: rgb(0, 0, 0); height: 16px;" class="rowcollapse"><?php echo $tl['phase'];?></div>
							</td>
							<td class="">&nbsp;</td>
							<td title="<?php echo $startDate."-".$endDate;?>" style="width:6px;background-color:<?php echo $phaseValues[$tl['phase']];?>;" colspan="3">&nbsp;</td>
							<td colspan="12" style="width:24px;">&nbsp;</td><td colspan="12" style="width:24px;">&nbsp;</td>
							<td colspan="12" style="width:24px;">&nbsp;</td><td colspan="3" style="width:6px;">&nbsp;</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
		<br/>
	</body>
</html>
<?php
	function downloadOptions($shownCnt, $foundCnt, $ottType, $result, $globalOptions) 
	{	
		$downloadOptions = '<div style="height:180px; padding:6px;"><div class="downldbox"><div class="newtext">Download Options</div>'
							. '<form  id="frmDOptions" name="frmDOptions" method="post" target="_self" action="">'
							. '<input type="hidden" name="ottType" value="' . $ottType . '" />';
		foreach($result as $rkey => $rvalue)
		{
			if(is_array($rvalue))
			{
				foreach($rvalue as $rk => $rv)
				{
					$downloadOptions .= '<input type="hidden" name="resultIds[' . $rkey . '][' . $rk . ']" value="' . $rv . '" />';
				}
			}
			else
			{
				$downloadOptions .= '<input type="hidden" name="resultIds[' . $rkey . ']" value="' . $rvalue . '" />';
			}

		}
		foreach($globalOptions as $gkey => $gvalue)
		{	
			if(is_array($gvalue))
			{	
				foreach($gvalue as $gk => $gv)
				{	
					$downloadOptions .= '<input type="hidden" name="globalOptions[' . $gkey . '][' . $gk . ']" value=\'' . $gv . '\' />';
				}
			}
			else
			{	
				$downloadOptions .= '<input type="hidden" name="globalOptions[' . $gkey . ']" value=\'' . $gvalue . '\' />';
			}
		}	
		$downloadOptions .= '<ul><li><label>Number of Studies: </label></li>'
							. '<li><select id="dOption" name="dOption" size="2" style="height:54px;">'
							. '<option value="shown" selected="selected">' . $shownCnt . ' Shown Studies</option>'
							. '<option value="all">' . $foundCnt . ' Found Studies</option></select></li>'
							. '<li><label>Which Format: </label></li>'
							. '<li><select id="wFormat" name="wFormat" size="3" style="height:54px;">'
							. '<option value="excel" selected="selected">Excel</option>'
							//comment the following line to hide pdf export
							//. '<option value="pdf">PDF</option>'
							. '<option value="tsv">TSV</option>'
							. '</select></li></ul>'
							. '<input type="hidden" name="shownCnt" value="' . $shownCnt . '" />'
							. '<input type="submit" id="btnDownload" name="btnDownload" value="Download File" style="margin-left:8px;"  />'
							. '</form></div></div>';
		
		return $downloadOptions;
	}
	function generateTsvFile($larvolIds){	
		$outputStr = "NCT ID \t Title \t N \t Region \t Status \t Sponsor \t Condition \t Interventions \t Start \t End \t Ph \n";
		$getTrialList = "SELECT * FROM data_trials where larvol_id IN($larvolIds)";
		$trialsList = mysql_query($getTrialList);
		while($value = mysql_fetch_array($trialsList)){
			$startDate = '';
			$endDate = '';
			$phase = '';
			if($value["start_date"] != '' && $value["start_date"] !== NULL && $value["start_date"] != '0000-00-00'){
				$startDate =  date('m/Y', strtotime($value["start_date"]));	
			}
			if($value["end_date"] != '' && $value["end_date"] !== NULL && $value["end_date"] != '0000-00-00'){
				$endDate = date('m/Y', strtotime($value["end_date"]));
			}
			if($value['phase'] == 'N/A' || $value['phase'] == '' || $value['phase'] === NULL){
				$phase = 'N/A';
			}else{
				$phase = str_replace('Phase ', '', trim($value['phase']));
			}
			$outputStr .= $value['larvol_id'] . "\t" . $value['brief_title'] . "\t" . $value['enrollment'] . "\t" . $value['region'] . "\t" . $value['overall_status'] . "\t" . $value['lead_sponsor'];
			if($value['lead_sponsor'] != '' && $value['collaborator'] != ''
			&& $value['lead_sponsor'] != NULL && $value['collaborator'] != NULL)
			{
				$outputStr .= ', ';
			}
			$outputStr .= $value['collaborator'] . "\t" . $value['condition'] . "\t" . $value['intervention_name'] 
							. "\t" . $startDate . "\t" . $endDate . "\t". $phase . "\n";
		}
		header("Pragma: public");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");
		header("Content-type: application/force-download"); 
		header("Content-Type: application/tsv");
		header('Content-Disposition: attachment;filename="DTT_Export_' . date('Y-m-d') . '.tsv"');
		header("Content-Transfer-Encoding: binary ");
		echo $outputStr;
		exit();
	}
	function generateExcelFile($larvolIds,$productTitle){
		$getTrialList = "SELECT * FROM data_trials where larvol_id IN($larvolIds)";
		$trialsList = mysql_query($getTrialList);
		$currentYear = date('Y');
		$secondYear	= date('Y')+1;
		$thirdYear	= date('Y')+2;	
		
		$SpaceIcon = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		
		ob_start();
		$objPHPExcel = new PHPExcel();
		$objPHPExcel->setActiveSheetIndex(0);
	
		$objPHPExcel->getActiveSheet()->setCellValue('A1' , 'NCT ID');
		$objPHPExcel->getActiveSheet()->setCellValue('B1' , 'Title');
		$objPHPExcel->getActiveSheet()->setCellValue('C1' , 'N');
		$objPHPExcel->getActiveSheet()->setCellValue('D1' , 'Region');
		$objPHPExcel->getActiveSheet()->setCellValue('E1' , 'Status');
		$objPHPExcel->getActiveSheet()->setCellValue('F1' , 'Sponsor');
		$objPHPExcel->getActiveSheet()->setCellValue('G1' , 'Conditions');
		$objPHPExcel->getActiveSheet()->setCellValue('H1' , 'Interventions');
		$objPHPExcel->getActiveSheet()->setCellValue('I1' , 'Start');
		$objPHPExcel->getActiveSheet()->setCellValue('J1' , 'End');
		$objPHPExcel->getActiveSheet()->setCellValue('K1' , 'Ph');
		$objPHPExcel->getActiveSheet()->setCellValue('L1' , 'Result');
		$objPHPExcel->getActiveSheet()->setCellValue('M1' , '-');
		$objPHPExcel->getActiveSheet()->mergeCells('M1:O1');
		$objPHPExcel->getActiveSheet()->setCellValue('P1' , $currentYear);
		$objPHPExcel->getActiveSheet()->mergeCells('P1:AA1');
		$objPHPExcel->getActiveSheet()->setCellValue('AB1' , $secondYear);
		$objPHPExcel->getActiveSheet()->mergeCells('AB1:AM1');
		$objPHPExcel->getActiveSheet()->setCellValue('AN1' , $thirdYear);
		$objPHPExcel->getActiveSheet()->mergeCells('AN1:AY1');
		$objPHPExcel->getActiveSheet()->setCellValue('AZ1' , '+');
		$objPHPExcel->getActiveSheet()->mergeCells('AZ1:BB1');
		
		$styleThinBlueBorderOutline = array(
			'fill' => array(
						'type'       => PHPExcel_Style_Fill::FILL_SOLID,
						'rotation'   => 0,
						'startcolor' => array('rgb' => 'A2FF97'),
						'endcolor'   => array('rgb' => 'A2FF97')),
			'alignment' => array('horizontal'	=> PHPExcel_Style_Alignment::HORIZONTAL_LEFT,),
			'borders' => array(
				'inside' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF0000FF'),),
				'outline' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF0000FF'),),
			),
			'font'    => array('bold' => true)
		);
		$styleThinBlueBorderOutlineWithShadow = array(
			'fill' => array(
						'type'       => PHPExcel_Style_Fill::FILL_SOLID,
						'rotation'   => 0,
						'startcolor' => array('rgb' => 'DDDDDD'),
						'endcolor'   => array('rgb' => 'AAAAAA')),
			'alignment' => array('horizontal'	=> PHPExcel_Style_Alignment::HORIZONTAL_LEFT,),
			'borders' => array(
				'inside' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF0000FF'),),
				'outline' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF0000FF'),),
			),
			'font'    => array('bold' => true)
		);
		
		$highlightChange =  array('font' => array('color' => array('rgb' => 'FF0000')));
		$manualChange =  array('font' => array('color' => array('rgb' => 'FF7700')));
		
		$objPHPExcel->getActiveSheet()->getStyle('A1:BB1')->getFont()->setSize(8);
		$objPHPExcel->getActiveSheet()->getStyle('A1:BB1')->applyFromArray($styleThinBlueBorderOutlineWithShadow);
		$objPHPExcel->getActiveSheet()->getStyle('A2:BB2')->applyFromArray($styleThinBlueBorderOutline);
		$objPHPExcel->getActiveSheet()->getStyle('A2:BB2')->getFont()->setSize(8);
				
		$objPHPExcel->getProperties()->setCreator("The Larvol Group")
										 ->setLastModifiedBy("TLG")
										 ->setTitle("Larvol Trials")
										 ->setSubject("Larvol Trials")
										 ->setDescription("Excel file generated by Larvol Trials")
										 ->setKeywords("Larvol Trials")
										 ->setCategory("Clinical Trials");

		$bgColor = "D5D3E6";
		
		$objPHPExcel->getActiveSheet()->setCellValue('A2' , $productTitle);
		$objPHPExcel->getActiveSheet()->mergeCells('A2:BB2');
		function changeBackground($evenOdd){
			if($evenOdd % 2){
				return array(
					'fill' => array(
						'type'       => PHPExcel_Style_Fill::FILL_SOLID,
						'rotation'   => 0,
						'startcolor' => array('rgb' => 'EDEAFF'),
						'endcolor'   => array('rgb' => 'EDEAFF')),
					'alignment' => array('horizontal'	=> PHPExcel_Style_Alignment::HORIZONTAL_LEFT,),
					'borders' => array(
						'inside' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF0000FF'),),
						'outline' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF0000FF'),),
					),
				);
			}else{
				return array(
					'fill' => array(
						'type'       => PHPExcel_Style_Fill::FILL_SOLID,
						'rotation'   => 0,
						'startcolor' => array('rgb' => 'D5D3E6'),
						'endcolor'   => array('rgb' => 'D5D3E6')),
					'alignment' => array('horizontal'	=> PHPExcel_Style_Alignment::HORIZONTAL_LEFT,),
					'borders' => array(
						'inside' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF0000FF'),),
						'outline' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF0000FF'),),
					),
				);
			}
		}
		$objPHPExcel->getActiveSheet()->getStyle('A1:K900')->getAlignment()->setWrapText(false);
		$objPHPExcel->getActiveSheet()->getStyle('A1:K900')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
		$objPHPExcel->getActiveSheet()->setCellValue('A1' , 'NCT ID');
		$objPHPExcel->getActiveSheet()->setTitle('Larvol Trials');
		$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setName('Calibri');
		
		$i = 3;
		while($value = mysql_fetch_array($trialsList)){
			$startDate = '';
			$endDate = '';
			$phase = '';
			if($value["start_date"] != '' && $value["start_date"] !== NULL && $value["start_date"] != '0000-00-00'){
				$startDate = date('m/y', strtotime($value['start_date']));
			}
			if($value["end_date"] != '' && $value["end_date"] !== NULL && $value["end_date"] != '0000-00-00'){
				$endDate = date('m/y', strtotime($value['end_date']));
			}
			if($value['phase'] == 'N/A' || $value['phase'] == '' || $value['phase'] === NULL){
				$phase = 'N/A';
			}else{
				$phase = str_replace('Phase ', '', trim($value['phase']));
			}
			$nctId = "";
			if(isset($value['source_id']) && strpos($value['source_id'], 'NCT') === FALSE){	
				$nctId = $value['source_id'];
				$nctIdText = unpadnct($nctId);
				$ctLink = 'https://www.clinicaltrialsregister.eu/ctr-search/search?query=' . $nctId;
			}
			else if(isset($value['source_id']) && strpos($value['source_id'], 'NCT') !== FALSE)
			{
				$ctLink = 'http://clinicaltrials.gov/ct2/show/' . padnct($nctId);
			}
			else{ 
				$ctLink = 'javascript:void(0)';
			}
			//$ctLink = urlencode($ctLink);
			
			$objPHPExcel->getActiveSheet()->setCellValue('A'.$i , $value['larvol_id']);
			$objPHPExcel->getActiveSheet()->getCell('A'.$i , $value['larvol_id'])->getHyperlink()->setUrl($ctLink);
			$objPHPExcel->getActiveSheet()->setCellValue('B'.$i , $value['brief_title']);
			$objPHPExcel->getActiveSheet()->getCell('B'.$i)->getHyperlink()->setUrl($ctLink);
			$objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setTooltip('Source - ClinicalTrials.gov'); 
			$objPHPExcel->getActiveSheet()->setCellValue('C'.$i , $value['enrollment']);
			$objPHPExcel->getActiveSheet()->setCellValue('D'.$i , $value['region']);
			$objPHPExcel->getActiveSheet()->setCellValue('E'.$i , $value['overall_status']);
			$objPHPExcel->getActiveSheet()->setCellValue('F'.$i , $value['lead_sponsor']);
			$objPHPExcel->getActiveSheet()->setCellValue('G'.$i , fix_special_chars($value['condition']));
			$objPHPExcel->getActiveSheet()->setCellValue('H'.$i , $value['intervention_name']);
			$objPHPExcel->getActiveSheet()->setCellValue('I'.$i , $startDate);
			$objPHPExcel->getActiveSheet()->setCellValue('J'.$i , $endDate);
			$objPHPExcel->getActiveSheet()->setCellValue('K'.$i , $phase);
			$objPHPExcel->getActiveSheet()->setCellValue('L'.$i , '');
			$objPHPExcel->getActiveSheet()->setCellValue('M'.$i , '');
			$objPHPExcel->getActiveSheet()->mergeCells('M'.$i.':O'.$i);
			$objPHPExcel->getActiveSheet()->setCellValue('P'.$i , $currentYear);
			$objPHPExcel->getActiveSheet()->mergeCells('P'.$i.':AA'.$i);
			$objPHPExcel->getActiveSheet()->setCellValue('AB'.$i , $secondYear);
			$objPHPExcel->getActiveSheet()->mergeCells('AB'.$i.':AM'.$i);
			$objPHPExcel->getActiveSheet()->setCellValue('AN'.$i , $thirdYear);
			$objPHPExcel->getActiveSheet()->mergeCells('AN'.$i.':AY'.$i);
			$objPHPExcel->getActiveSheet()->setCellValue('AZ'.$i , '+');
			$objPHPExcel->getActiveSheet()->mergeCells('AZ'.$i.':BB'.$i);
			$objPHPExcel->getActiveSheet()->getStyle('A'.$i.':BB'.$i)->applyFromArray(changeBackground($i));
			$objPHPExcel->getActiveSheet()->getStyle('A'.$i.':BB'.$i)->getFont()->setSize(8);
			$i++;
		}		
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(13);
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(50);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(10);
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
		$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
		$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
		$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
		$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(12);
		$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(12);
		$objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(9);
		$objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(12);
		
		$chr = 'M';
		for($c=1; $c<43; $c++)
		{
			$objPHPExcel->getActiveSheet()->getColumnDimension($chr)->setWidth(2);
			$chr++;
		}
				
		$objPHPExcel->setActiveSheetIndex(0);
		$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);

		ob_end_clean(); 
			
		header("Pragma: public");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Type: application/download");
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=UTF-8');
		header('Content-Disposition: attachment;filename="  DTT  _' . date('Y-m-d_H.i.s') . '.xlsx"');
		header("Content-Transfer-Encoding: binary ");
		$objWriter->save('php://output');
		@flush();
	}
?>