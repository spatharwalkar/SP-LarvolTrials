<?php
session_start();
//unset($_SESSION['OHM_array']);
require_once('db.php');
ini_set('memory_limit','-1');
ini_set('max_execution_time','36000');	//10 hours
global $db;
global $now;
if(!isset($_GET['id'])) return;
$id = mysql_real_escape_string(htmlspecialchars($_GET['id']));
if(!is_numeric($id)) return;
$query = 'SELECT `name`, `user`, `footnotes`, `description`, `category`, `shared`, `total`, `dtt`, `display_name` FROM `rpt_masterhm` WHERE id=' . $id . ' LIMIT 1';
$res = mysql_query($query) or die('Bad SQL query getting master heatmap report');
$res = mysql_fetch_array($res) or die('Report not found.');
$total_fld=$res['total'];
$name = $res['name'];
$dtt = $res['dtt'];
$ReportDisplayName=$res['display_name'];
	
$query = 'SELECT `num`,`type`,`type_id`, `display_name`, `category`, `tag` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' ORDER BY num ASC';
$res = mysql_query($query) or die('Bad SQL query getting master heatmap report headers');
$rows = array();
$columns = array();
$entity2Ids = array();
$entity1Ids = array();

$columnsDisplayName = array();
$rowsDisplayName = array();
$columnsCompanyName = array();
$rowsCompanyName = array();
$columnsCategoryName = array();
$rowsCategoryName = array();
$columnsDescription = array();
$rowsDescription = array();
$columnsTagName = array();
$rowsTagName = array();

$ColumnsSpan  = array();
$rowsCategoryEntityIds1 = array();

$prevEntity2Category='';
$prevEntity1Category='';
$prevEntity2='';
$prevEntity1='';
$prevEntity2Span=0;
$prevEntity1Span=0;

$Min_One_Liner=20;
$Char_Size=8.5;
$Bold_Char_Size=9;

while($header = mysql_fetch_array($res))
{
	if($header['type'] == 'column')
	{
		$columnsCompanyName[$header['num']] = '';
		$columnsTagName[$header['num']] = '';
		if($header['type_id'] != NULL)
		{
			$result =  mysql_fetch_assoc(mysql_query("SELECT `id`, `name`, `display_name`, `description`, `class`, `company` FROM `entities` WHERE id = '" . $header['type_id'] . "' "));
			$columns[$header['num']] = $result['name'];
			$columnsEntityType[$header['num']] = $result['class'];
			$type = ''; if($type == 'Institution') $type = 'Company'; else if($type == 'MOA_Category') $type = 'MOA Category'; else $type = $result['class'];
			if($result['company'] != NULL && trim($result['company']) != '' && $type = 'Product')
			{
				$result['company']=str_replace(',',', ',$result['company']);
				$result['company']=str_replace(',  ',', ',$result['company']);
				$columnsCompanyName[$header['num']] = ' / '.$result['company'];
			} 
			//$columnsDisplayName[$header['num']] = $result['display_name'];
			if($type == 'Product')
				$columnsDisplayName[$header['num']] = $result['name'];
			else
			{
				if(trim($header['display_name']) != '' && $header['display_name'] != NULL && $header['display_name'] != 'NULL') //HM LEVEL Display name
					$columnsDisplayName[$header['num']] = $header['display_name'];
				else if(trim($result['display_name']) != '' && $result['display_name'] != NULL && $result['display_name'] != 'NULL') //Global Display name
					$columnsDisplayName[$header['num']] = $result['display_name'];
				else if($type == 'Area')
					$columnsDisplayName[$header['num']] = $type .' '.$result['id'] ;	//For area display class n id
				else
					$columnsDisplayName[$header['num']] = $result['name'] ;	//For for other than Area take actual name
			}
				
			$columnsDescription[$header['num']] = $result['description'];
			$header['category'] = trim($header['category']);
			if($header['category'] == NULL || trim($header['category']) == '')
			$header['category'] = 'Undefined';
		}
		else
		{
			$columns[$header['num']] = $header['type_id'];
			$header['category'] = 'Undefined';
		}
		$entity2Ids[$header['num']] = $header['type_id'];
		
		if($prevEntity2Category == $header['category'])
		{
			$ColumnsSpan[$prevEntity2] = $prevEntity2Span+1;
			$ColumnsSpan[$header['num']] = 0;
			$prevEntity2 = $prevEntity2;
			$prevEntity2Span = $prevEntity2Span+1;
			$last_cat_col = $last_cat_col;
		}
		else
		{
			$ColumnsSpan[$header['num']] = 1;
			$prevEntity2 = $header['num'];
			$prevEntity2Span = 1;
			$second_last_cat_col = $last_cat_col;
			$last_cat_col = $header['num'];
		}
		
		$prevEntity2Category = $header['category'];
		$columnsCategoryName[$header['num']] = $header['category'];
		if($header['tag'] != 'NULL')
		$columnsTagName[$header['num']] = $header['tag'];
		
		$last_category = $header['category'];
		$second_last_num = $last_num;
		$last_num = $header['num'];
		$LastEntity2 = $header['type_id'];
	}
	else
	{
		$rowsCompanyName[$header['num']] = '';
		$rowsTagName[$header['num']] = '';
		if($header['type_id'] != NULL)
		{
			$result =  mysql_fetch_assoc(mysql_query("SELECT `id`, `name`, `display_name`, `description`, `class`, `company` FROM `entities` WHERE id = '" . $header['type_id'] . "' "));
			$rows[$header['num']] = $result['name'];
			$rowsEntityType[$header['num']] = $result['class'];
			$type = ''; if($type == 'Institution') $type = 'Company'; else if($type == 'MOA_Category') $type = 'MOA Category'; else $type = $result['class'];
			if($result['company'] != NULL && trim($result['company']) != '' && $type = 'Product')
			{
				$result['company']=str_replace(',',', ',$result['company']);
				$result['company']=str_replace(',  ',', ',$result['company']);
				$rowsCompanyName[$header['num']] = ' / '.$result['company'];
			}
			
			if($type == 'Product')
				$rowsDisplayName[$header['num']] = $result['name'];
			else
			{
				if(trim($header['display_name']) != '' && $header['display_name'] != NULL && $header['display_name'] != 'NULL') //HM LEVEL Display name
					$rowsDisplayName[$header['num']] = $header['display_name'];
				else if(trim($result['display_name']) != '' && $result['display_name'] != NULL && $result['display_name'] != 'NULL') //Global Display name
					$rowsDisplayName[$header['num']] = $result['display_name'];
				else if($type == 'Area')											//For area display class n id
					$rowsDisplayName[$header['num']] = $type .' '.$result['id'] ;	
				else																//For for other than Area take actual name
					$rowsDisplayName[$header['num']] = $result['name'] ;	
			}
			
			$rowsDescription[$header['num']] = $result['description'];
			$header['category']=trim($header['category']);
			if($header['category'] == NULL || trim($header['category']) == '')
			$header['category'] = 'Undefined';
		}
		else
		{
			$rows[$header['num']] = $header['type_id'];
			$header['category'] = 'Undefined';
		}
		$entity1Ids[$header['num']] = $header['type_id'];
		
		if($prevEntity1Category == $header['category'])
		{
			$RowsSpan[$prevEntity1] = $prevEntity1Span+1;
			$RowsSpan[$header['num']] = 0;
			$prevEntity1 = $prevEntity1;
			$prevEntity1Span = $prevEntity1Span+1;
		}
		else
		{
			$RowsSpan[$header['num']] = 1;
			$prevEntity1 = $header['num'];
			$prevEntity1Span = 1;
		}
		
		$prevEntity1Category = $header['category'];
		$rowsCategoryName[$header['num']] = $header['category'];
		
		$rowsCategoryEntityIds1[$header['category']][] = $header['type_id'];
		if($header['tag'] != 'NULL')
		$rowsTagName[$header['num']] = $header['tag'];
	}
}

/////Rearrange Data according to Category //////////
$new_columns = array();
foreach($columns as $col => $cval)
{
	if($dtt && $last_num == $col)
	{
		array_pop($entity2Ids); //In case of DTT enable skip last column vaules
		$ColumnsSpan[$last_cat_col] = $ColumnsSpan[$last_cat_col] - 1;	/// Decrease last category column span
	}
	else
	{
		$new_columns[$col]=$cval;
	}
	
}

$columns=$new_columns;
/////Rearrange Completes //////////

$row_total=array();
$col_total=array();
$active_total=0;
$count_total=0;
$data_matrix=array();
$Max_ViewCount = 0;

//// Declare Tidy Configuration
$tidy_config = array(
                     'clean' => true,
                     'output-xhtml' => true,
                     'show-body-only' => true,
                     'wrap' => 0,
                    
                     );
$tidy = new tidy(); /// Create Tidy Object

require_once('tcpdf/config/lang/eng.php');
require_once('tcpdf/tcpdf.php');  
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

foreach($rows as $row => $rval)
{
	foreach($columns as $col => $cval)
	{
		if(isset($entity2Ids[$col]) && $entity2Ids[$col] != NULL && isset($entity1Ids[$row]) && $entity1Ids[$row] != NULL)
		{
			$cell_query = 'SELECT * FROM rpt_masterhm_cells WHERE (`entity1`=' . $entity1Ids[$row] . ' AND `entity2`='. $entity2Ids[$col] .') OR (`entity2`=' . $entity1Ids[$row] . ' AND `entity1`='. $entity2Ids[$col] .')';
			$cell_res = mysql_query($cell_query) or die(mysql_error());
			$cell_data = mysql_fetch_array($cell_res);
			
			$col_active_total[$col]=$cell_data['count_active']+$col_active_total[$col];
			$row_active_total[$row]=$cell_data['count_active']+$row_active_total[$row];
			$col_count_total[$col]=$cell_data['count_total']+$col_count_total[$col];
			$row_count_total[$row]=$cell_data['count_total']+$row_count_total[$row];
			$col_indlead_total[$col]=$cell_data['count_active_indlead']+$col_indlead_total[$col];
			$row_indlead_total[$row]=$cell_data['count_active_indlead']+$row_indlead_total[$row];
			$active_total=$cell_data['count_active']+$active_total;
			$count_total=$cell_data['count_total']+$count_total;
			$indlead_total=$cell_data['count_active_indlead']+$indlead_total;
			
			if($cell_data['count_active'] != '' && $cell_data['count_active'] != NULL)
				$data_matrix[$row][$col]['active']=$cell_data['count_active'];
				else
				$data_matrix[$row][$col]['active']=0;
				
				if($cell_data['count_total'] != '' && $cell_data['count_total'] != NULL)
				$data_matrix[$row][$col]['total']=$cell_data['count_total'];
				else
				$data_matrix[$row][$col]['total']=0;
				
				if($cell_data['count_active_indlead'] != '' && $cell_data['count_active_indlead'] != NULL)
				$data_matrix[$row][$col]['indlead']=$cell_data['count_active_indlead'];
				else
				$data_matrix[$row][$col]['indlead']=0;
			
			$data_matrix[$row][$col]['active_prev']=$cell_data['count_active_prev'];
			$data_matrix[$row][$col]['total_prev']=$cell_data['count_total_prev'];
			$data_matrix[$row][$col]['indlead_prev']=$cell_data['count_active_indlead_prev'];
			
			$data_matrix[$row][$col]['phase_explain']=trim($cell_data['phase_explain']);
			/// Clean HTML using Tidy
			$tidy = tidy_parse_string($data_matrix[$row][$col]['phase_explain'], $tidy_config, 'UTF8');
			$tidy->cleanRepair(); 
			$data_matrix[$row][$col]['phase_explain']=trim($tidy);
			
			$data_matrix[$row][$col]['bomb_explain']=trim($cell_data['bomb_explain']);
			/// Clean HTML using Tidy
			$tidy = tidy_parse_string($data_matrix[$row][$col]['bomb_explain'], $tidy_config, 'UTF8');
			$tidy->cleanRepair(); 
			$data_matrix[$row][$col]['bomb_explain']=trim($tidy);
			
			$data_matrix[$row][$col]['filing']=trim($cell_data['filing']);
			/// Clean HTML using Tidy
			$tidy = tidy_parse_string($data_matrix[$row][$col]['filing'], $tidy_config, 'UTF8');
			$tidy->cleanRepair(); 
			$data_matrix[$row][$col]['filing']=trim($tidy);
			
			$data_matrix[$row][$col]['preclinical']=$cell_data['preclinical'];
			
			$data_matrix[$row][$col]['viewcount']=$cell_data['viewcount'];
			
			if($cell_data['count_total'] > 0 && $data_matrix[$row][$col]['viewcount'] > $Max_ViewCount)
			$Max_ViewCount = $data_matrix[$row][$col]['viewcount'];
				
			$Width = 0;
			
			if($cell_data['bomb_auto'] == 'small')
			{
				$data_matrix[$row][$col]['bomb_auto']['value']=$cell_data['bomb_auto'];
				$data_matrix[$row][$col]['bomb_auto']['src']='sbomb.png';
				$data_matrix[$row][$col]['bomb_auto']['alt']='Small Bomb';
				$data_matrix[$row][$col]['bomb_auto']['style']='width:10px; height:11px;';
				$data_matrix[$row][$col]['bomb_auto']['title']='Suggested';
			}
			elseif($cell_data['bomb_auto'] == 'large')
			{
				$data_matrix[$row][$col]['bomb_auto']['value']=$cell_data['bomb_auto'];
				$data_matrix[$row][$col]['bomb_auto']['src']='lbomb.png';
				$data_matrix[$row][$col]['bomb_auto']['alt']='Large Bomb';
				$data_matrix[$row][$col]['bomb_auto']['style']='width:18px; height:20px;';
				$data_matrix[$row][$col]['bomb_auto']['title']='Suggested';
			}
			else
			{
				$data_matrix[$row][$col]['bomb_auto']['value']=$cell_data['bomb_auto'];
				$data_matrix[$row][$col]['bomb_auto']['src']='trans.gif';
				$data_matrix[$row][$col]['bomb_auto']['alt']='None';
				$data_matrix[$row][$col]['bomb_auto']['style']='width:10px; height:11px;';
				$data_matrix[$row][$col]['bomb_auto']['title']='';
			}
			
			
			if($cell_data['bomb'] == 'small')
			{
				$data_matrix[$row][$col]['bomb']['value']=$cell_data['bomb'];
				$data_matrix[$row][$col]['bomb']['src']='new_sbomb.png';
				$data_matrix[$row][$col]['bomb']['alt']='Small Bomb';
				$data_matrix[$row][$col]['bomb']['style']='width:17px; height:17px;';
				$data_matrix[$row][$col]['bomb']['title']='Bomb details';
				
				$Width = $Width + 17 + 1;
			}
			elseif($cell_data['bomb'] == 'large')
			{
				$data_matrix[$row][$col]['bomb']['value']=$cell_data['bomb'];
				$data_matrix[$row][$col]['bomb']['src']='new_lbomb.png';
				$data_matrix[$row][$col]['bomb']['alt']='Large Bomb';
				$data_matrix[$row][$col]['bomb']['style']='width:17px; height:17px;';
				$data_matrix[$row][$col]['bomb']['title']='Bomb details';
				
				$Width = $Width + 17 + 1;
			}
			else
			{
				$data_matrix[$row][$col]['bomb']['value']=$cell_data['bomb'];
				$data_matrix[$row][$col]['bomb']['src']='new_square.png';
				$data_matrix[$row][$col]['bomb']['alt']='None';
				$data_matrix[$row][$col]['bomb']['style']='width:17px; height:17px;';
				$data_matrix[$row][$col]['bomb']['title']='Bomb details';
			}
			
			
			$data_matrix[$row][$col]['phase4_override']=$cell_data['phase4_override'];
			$data_matrix[$row][$col]['phase4_override_lastchanged']=$cell_data['phase4_override_lastchanged'];
			
			$data_matrix[$row][$col]['highest_phase_prev']=$cell_data['highest_phase_prev'];
			$data_matrix[$row][$col]['highest_phase_lastchanged']=$cell_data['highest_phase_lastchanged'];
			$data_matrix[$row][$col]['phase4_override_lastchanged']=$cell_data['phase4_override_lastchanged'];
			
			
			
			if($cell_data['highest_phase'] == 'N/A' || $cell_data['highest_phase'] == '' || $cell_data['highest_phase'] === NULL)
			{
				$data_matrix[$row][$col]['color']='background-color:#BFBFBF;';
				$data_matrix[$row][$col]['color_code']='BFBFBF';
			}
			else if($cell_data['highest_phase'] == '0')
			{
				$data_matrix[$row][$col]['color']='background-color:#00CCFF;';
				$data_matrix[$row][$col]['color_code']='00CCFF';
			}
			else if($cell_data['highest_phase'] == '1' || $cell_data['highest_phase'] == '0/1' || $cell_data['highest_phase'] == '1a' 
			|| $cell_data['highest_phase'] == '1b' || $cell_data['highest_phase'] == '1a/1b' || $cell_data['highest_phase'] == '1c')
			{
				$data_matrix[$row][$col]['color']='background-color:#99CC00;';
				$data_matrix[$row][$col]['color_code']='99CC00';
			}
			else if($cell_data['highest_phase'] == '2' || $cell_data['highest_phase'] == '1/2' || $cell_data['highest_phase'] == '1b/2' 
			|| $cell_data['highest_phase'] == '1b/2a' || $cell_data['highest_phase'] == '2a' || $cell_data['highest_phase'] == '2a/2b' 
			|| $cell_data['highest_phase'] == '2a/b' || $cell_data['highest_phase'] == '2b')
			{
				$data_matrix[$row][$col]['color']='background-color:#FFFF00;';
				$data_matrix[$row][$col]['color_code']='FFFF00';
			}
			else if($cell_data['highest_phase'] == '3' || $cell_data['highest_phase'] == '2/3' || $cell_data['highest_phase'] == '2b/3' 
			|| $cell_data['highest_phase'] == '3a' || $cell_data['highest_phase'] == '3b')
			{
				$data_matrix[$row][$col]['color']='background-color:#FF9900;';
				$data_matrix[$row][$col]['color_code']='FF9900';
			}
			else if($cell_data['highest_phase'] == '4' || $cell_data['highest_phase'] == '3/4' || $cell_data['highest_phase'] == '3b/4')
			{
				$data_matrix[$row][$col]['color']='background-color:#FF0000;';
				$data_matrix[$row][$col]['color_code']='FF0000';	
			}
			
			if($cell_data['phase4_override'])
			{
				$data_matrix[$row][$col]['color']='background-color:#FF0000;';
				$data_matrix[$row][$col]['color_code']='FF0000';
			}
			
			$data_matrix[$row][$col]['last_update']=$cell_data['last_update'];
			
			$data_matrix[$row][$col]['div_start_style'] = $data_matrix[$row][$col]['color'];
			//$data_matrix[$row][$col]['cell_start_title'] = 'Active Trials';
			
			$data_matrix[$row][$col]['count_lastchanged']=$cell_data['count_lastchanged'];
			$data_matrix[$row][$col]['bomb']['style'] = $data_matrix[$row][$col]['bomb']['style'].' vertical-align:middle; cursor:pointer;';
			$data_matrix[$row][$col]['bomb_lastchanged']=$cell_data['bomb_lastchanged'];
			$data_matrix[$row][$col]['filing_lastchanged']=$cell_data['filing_lastchanged'];
			$data_matrix[$row][$col]['phase_explain_lastchanged']=$cell_data['phase_explain_lastchanged'];
			
			$allTrialsStatusArray = array('not_yet_recruiting', 'recruiting', 'enrolling_by_invitation', 'active_not_recruiting', 'completed', 'suspended', 'terminated', 'withdrawn', 'available', 'no_longer_available', 'approved_for_marketing', 'no_longer_recruiting', 'withheld', 'temporarily_not_available', 'ongoing', 'not_authorized', 'prohibited', 'new_trials');
			foreach($allTrialsStatusArray as $status)
			{
				$data_matrix[$row][$col][$status]=$cell_data[$status];
			}
			
			$activeIndleadStatusArray = array('not_yet_recruiting_active_indlead', 'recruiting_active_indlead', 'enrolling_by_invitation_active_indlead', 'active_not_recruiting_active_indlead', 'completed_active_indlead', 'suspended_active_indlead', 'terminated_active_indlead', 'withdrawn_active_indlead', 'available_active_indlead', 'no_longer_available_active_indlead', 'approved_for_marketing_active_indlead', 'no_longer_recruiting_active_indlead', 'withheld_active_indlead', 'temporarily_not_available_active_indlead', 'ongoing_active_indlead', 'not_authorized_active_indlead', 'prohibited_active_indlead');
			foreach($activeIndleadStatusArray as $status)
			{
				$data_matrix[$row][$col][$status]=$cell_data[$status];
			}
			
			$activeStatusArray = array('not_yet_recruiting_active', 'recruiting_active', 'enrolling_by_invitation_active', 'active_not_recruiting_active', 'completed_active', 'suspended_active', 'terminated_active', 'withdrawn_active', 'available_active', 'no_longer_available_active', 'approved_for_marketing_active', 'no_longer_recruiting_active', 'withheld_active', 'temporarily_not_available_active', 'ongoing_active', 'not_authorized_active', 'prohibited_active');
			foreach($activeStatusArray as $status)
			{
				$data_matrix[$row][$col][$status]=$cell_data[$status];
			}
			
			///As stringlength of total will be more in all
			$Width = $Width + (strlen($data_matrix[$row][$col]['total'])*($Char_Size+1));
					
			if(trim($data_matrix[$row][$col]['filing']) != '' && $data_matrix[$row][$col]['filing'] != NULL)
			$Width = $Width + 17 + 1;
			$Width = $Width + 6;
			if($Width_matrix[$col]['width'] < ($Width) || $Width_matrix[$col]['width'] == '' || $Width_matrix[$col]['width'] == 0)
			{
				$Width_extra = 0;
				if(($Width) < $Min_One_Liner)
				$Width_extra = $Min_One_Liner - ($Width);
				$Width_matrix[$col]['width']=$Width + $Width_extra;
			}
		}
		else
		{
			$data_matrix[$row][$col]['active']=0;
			$data_matrix[$row][$col]['total']=0;
			$data_matrix[$row][$col]['indlead']=0;
			$col_active_total[$col]=0+$col_active_total[$col];
			$row_active_total[$row]=0+$row_active_total[$row];
			$col_count_total[$col]=0+$col_count_total[$col];
			$row_count_total[$row]=0+$row_count_total[$row];
			$col_count_indlead[$col]=0+$col_count_indlead[$col];
			$row_count_indlead[$row]=0+$row_count_indlead[$row];
			$data_matrix[$row][$col]['bomb_auto']['src']='';
			$data_matrix[$row][$col]['bomb']['src']='';
			$data_matrix[$row][$col]['bomb_explain']='';
			$data_matrix[$row][$col]['filing']='';
			$data_matrix[$row][$col]['color']='background-color:#DDF;';
			$data_matrix[$row][$col]['color_code']='DDF';
			$data_matrix[$row][$col]['record_update_class']='';
			$Width = 22;
			if($Width_matrix[$col]['width'] < $Width || $Width_matrix[$col]['width'] == '' || $Width_matrix[$col]['width'] == 0)
			$Width_matrix[$col]['width']=22;
		}
	}
}

$Page_Width = 1100;

$Max_entity2StringLength=0;
foreach($columns as $col => $val)
{
	$val = $columnsDisplayName[$col].$columnsCompanyName[$col].((trim($columnsTagName[$col]) != '') ? ' ['.$columnsTagName[$col].']':'');
	if(isset($entity2Ids[$col]) && $entity2Ids[$col] != NULL && !empty($entity1Ids))
	$current_StringLength =strlen($val);
	else $current_StringLength = 0;
	if($Max_entity2StringLength < $current_StringLength)
	$Max_entity2StringLength = $current_StringLength;
}
$Entity2ColHeight = $Max_entity2StringLength * $Bold_Char_Size;
if(($Entity2ColHeight) > 160)
$Entity2ColHeight = 160;

$Max_entity1StringLength=0;
foreach($rows as $row => $rval)
{
	if(isset($entity1Ids[$row]) && $entity1Ids[$row] != NULL && !empty($entity2Ids))
	{
		$current_StringLength =strlen($rowsDisplayName[$row].$rowsCompanyName[$row].((trim($rowsTagName[$row]) != '') ? ' ['.$rowsTagName[$row].']':''));
	}
	else $current_StringLength = 0;
	if($Max_entity1StringLength < $current_StringLength)
	$Max_entity1StringLength = $current_StringLength;
}	

if(($Max_entity1StringLength * $Char_Size) > 400)
$entity1ColWidth = 400;
else
$entity1ColWidth = $Max_entity1StringLength * $Char_Size;

$entity2ColWidth=110;
		
$HColumn_Width = (((count($columns))+(($total_fld)? 1:0)) * ($entity2ColWidth+1));

$RColumn_Width = 0; 

foreach($columns as $col => $val)
{
	$RColumn_Width = $RColumn_Width + $Width_matrix[$col]['width'] + 0.5;
	
	if($Max_ColWidth < $Width_matrix[$col]['width'])
		$Max_ColWidth = $Width_matrix[$col]['width'];	
		
	$CatEntity2Rotation[$col] = 0;
}

if(($HColumn_Width + $entity1ColWidth) > $Page_Width)	////if hm lenth is greater than 1200 than move to rotate mode
{
	$entity1ColWidth = 450;
	if($total_fld) 
	{ 
		$Total_Col_width = ((strlen($count_total) * $Bold_Char_Size) + 1);
		if($Total_Col_width < $Min_One_Liner)
		$Total_Col_width = $Min_One_Liner;
		$RColumn_Width = $RColumn_Width + $Total_Col_width + 1;
	}
	$Rotation_Flg = 1;
}
else
{
	if(($Max_entity1StringLength * $Char_Size) > 400)
	$entity1ColWidth = 400;
	else
	$entity1ColWidth = $Max_entity1StringLength * $Char_Size;
	
	foreach($columns as $col => $val)
	{
		$Width_matrix[$col]['width'] = $entity2ColWidth;
	}
	$Total_Col_width = $entity2ColWidth;
	$Rotation_Flg = 0;
}

$Max_ColWidth = 0;
//$Rotation_Flg = 1;
$Line_Height = 16;
$Max_H_Entity2CatStringHeight = 0;
$Max_V_Entity2CatStringLength = 0;
$CatEntity2Rotation_Flg = 0;
if($Rotation_Flg == 1)	////Adjustment in Entity2 column width as per Entity2 name
{
	foreach($columns as $col => $val)
	{
		if(isset($entity2Ids[$col]) && $entity2Ids[$col] != NULL && !empty($entity1Ids))
		{
			$val = $columnsDisplayName[$col].$columnsCompanyName[$col].((trim($columnsTagName[$col]) != '') ? ' ['.$columnsTagName[$col].']':'');
			$ColsEntity2Space[$col] = ceil(($Entity2ColHeight) / $Bold_Char_Size);
			//$ColsEntity2Lines[$col] = ceil(strlen(trim($val))/$ColsEntity2Space[$col]);
			//$ColsEntity2Lines[$col] = $pdf->getNumLines($val, ($Entity2ColHeight*20/90));
			$ColsEntity2Lines[$col] = getNumLinesHTML($val, $ColsEntity2Space[$col], 'lines');
			$width = ($ColsEntity2Lines[$col] * $Line_Height);
			if($Width_matrix[$col]['width'] < $width)
				$Width_matrix[$col]['width'] = $width;
			
			if($Max_ColWidth < $Width_matrix[$col]['width'] && $ColsEntity2Lines[$col] <= 4) 	//// if column do not hv Entity2 name with more than 4 lines
				$Max_ColWidth = $Width_matrix[$col]['width'];
		}
	}
	
	
	foreach($columns as $col => $val)
	{
		/// Assign same width to all cloumns -  except columns expanding due to number of lines more than 4
		if($Max_ColWidth > $Width_matrix[$col]['width'] && $ColsEntity2Lines[$col] <= 4)
		$Width_matrix[$col]['width'] = $Max_ColWidth;
		$Total_Col_width = $Max_ColWidth;
		
		///// Category height calculation from horizontal and vertical Entity2 names
		if($ColumnsSpan[$col] > 0 && $columnsCategoryName[$col] != 'Undefined')
		{
			$current_StringLength =strlen($columnsCategoryName[$col]);
			if($ColumnsSpan[$col] < 2 && $columnsCategoryName[$col] != 'Undefined')
			{
				$CatEntity2Rotation[$col] = 1;
				$CatEntity2Rotation_Flg = 1;
				if($Max_V_Entity2CatStringLength < $current_StringLength)
				{
					$Max_V_Entity2CatStringLength = $current_StringLength;
				}
			}
			else
			{
				$i = 1; $width = 0; $col_id = $col;
				while($i <= $ColumnsSpan[$col])
				{
					$width = $width + $Width_matrix[$col_id]['width'];
					$i++; $col_id++;
				}
				$CatEntity2Colwidth[$col] = $width +((($ColumnsSpan[$col] == 1) ? 0:1) * ($ColumnsSpan[$col]-1));
				$cols_Cat_Space[$col] = ceil($CatEntity2Colwidth[$col] / $Bold_Char_Size);
				$lines = ceil(strlen(trim($columnsCategoryName[$col]))/$cols_Cat_Space[$col]);
				$height = ($lines * $Line_Height);
				if($height > $Max_H_Entity2CatStringHeight)
					$Max_H_Entity2CatStringHeight = $height;
			}
		}
	}
}



if($Rotation_Flg == 1)	////Create width for Entity2 category cells and put forcefully line break in category text
{
	if($CatEntity2Rotation_Flg)
	{
		/// Assign minimum height to category row
		if($Max_H_Entity2CatStringHeight > 130)	/// if horizontal spanning category requires more height assign it
			$Entity2CatHeight = $Max_H_Entity2CatStringHeight;
		else if(($Max_V_Entity2CatStringLength * $Bold_Char_Size) < 130)	//// if vertical spanning category requires less height assign it
			$Entity2CatHeight = $Max_V_Entity2CatStringLength * $Bold_Char_Size;
		else
			$Entity2CatHeight = 130;	/// Take default height
	}
	
	foreach($columns as $col => $val)
	{
		if($ColumnsSpan[$col] > 0)
		{
			$i = 1; $width = 0; $col_id = $col;
			while($i <= $ColumnsSpan[$col])
			{
				$width = $width + $Width_matrix[$col_id]['width'];
				$i++; $col_id++;
			}
			
			$CatEntity2Colwidth[$col] = $width +((($ColumnsSpan[$col] == 1) ? 0:1) * ($ColumnsSpan[$col]-1));
			
			if($ColumnsSpan[$col] < 2 && $columnsCategoryName[$col] != 'Undefined')
			{
				$cols_Cat_Space[$col] = ceil((($Entity2CatHeight < 130)? ($Entity2CatHeight):($Entity2CatHeight)) / $Bold_Char_Size);
				//$cols_Cat_Lines[$col] = ceil(strlen(trim($columnsCategoryName[$col]))/$cols_Cat_Space[$col]);
				//$cols_Cat_Lines[$col] = $pdf->getNumLines($columnsCategoryName[$col], ($Entity2CatHeight*17/90));
				$cols_Cat_Lines[$col] = getNumLinesHTML($columnsCategoryName[$col], $cols_Cat_Space[$col], 'lines');
				$width = ($cols_Cat_Lines[$col] * $Line_Height);
				if($CatEntity2Colwidth[$col] < $width) /// Assign new width
				{
					$extra_width = $width - $CatEntity2Colwidth[$col];
					$CatEntity2Colwidth[$col] = $width;
					/// Distribute extra width equally to all spanning columns
					$i = 1; $col_id = $col;
					while($i <= $ColumnsSpan[$col])
					{
						$Width_matrix[$col_id]['width'] = $Width_matrix[$col_id]['width'] + ($extra_width/$ColumnsSpan[$col]) - ((($ColumnsSpan[$col] == 1) ? 0:1) * ($ColumnsSpan[$col]-1));
						$i++; $col_id++;
					}
				}
			}
			else
			{
				$CatEntity2Rotation[$col] = 0;
				$cols_Cat_Space[$col] = ceil($CatEntity2Colwidth[$col] / $Bold_Char_Size);
			}
		}
	}
}

//Height Recalculator - Due number of line formation and wrapping actual height taken is sometime less
if($Rotation_Flg == 1)
{
	//category height calculation
	$NewEntity2CatHeight = 0;
	$NewMaxEntity2CatStrLength = 0;
	foreach($columns as $col => $val)
	{
		if($ColumnsSpan[$col] > 0)
		{
			if($columnsCategoryName[$col] != 'Undefined' && $CatEntity2Rotation[$col])
			{
				$LineArray = getNumLinesHTML(trim($columnsCategoryName[$col]), $cols_Cat_Space[$col], 'array');
				foreach($LineArray as $data1)
				{
					$current_StringLength =strlen($data1);
					if($current_StringLength > $NewMaxEntity2CatStrLength)
					$NewMaxEntity2CatStrLength = $current_StringLength;
				}
			}
		}
	}
	
	$NewEntity2CatHeight = $NewMaxEntity2CatStrLength * $Bold_Char_Size;
	//If new height is less assign it
	if($NewEntity2CatHeight < $Entity2CatHeight && $Max_H_Entity2CatStringHeight < $NewEntity2CatHeight)
	$Entity2CatHeight = $NewEntity2CatHeight;
	
	//Entity2 cell height calcuation
	$NewEntity2ColHeight = 0;
	$NewMaxEntity2StringLength = 0;
	foreach($columns as $col => $val)
	{
		$val = $columnsDisplayName[$col].$columnsCompanyName[$col].((trim($columnsTagName[$col]) != '') ? ' ['.$columnsTagName[$col].']':'');
		if(isset($entity2Ids[$col]) && $entity2Ids[$col] != NULL && !empty($entity1Ids))
		{
			$LineArray = getNumLinesHTML(trim($val), $ColsEntity2Space[$col], 'array');
			foreach($LineArray as $data1)
			{
				$current_StringLength =strlen($data1);
				if($NewMaxEntity2StringLength < $current_StringLength)
				$NewMaxEntity2StringLength = $current_StringLength;
			}
		}
	}
	$NewEntity2ColHeight = $NewMaxEntity2StringLength * $Bold_Char_Size;
	//If new height is less assign it
	if($NewEntity2ColHeight < $Entity2ColHeight)
	$Entity2ColHeight = $NewEntity2ColHeight;
}

//Calculate line numbers
function getNumLinesHTML($text, $gap, $type)
{
	$data_array = array();
	if($text != '' && $text != NULL)
	{
		$newtext = wordwrap($text, $gap, "****", true);
		$data_array = array_filter(explode("****", $newtext));
		$lines = (count($data_array));
	}
	else
	{
		$lines = 1;
	}
	if($type == 'lines')
		return $lines;
	else
		return $data_array;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Larvol Trials :: Online Heatmap</title>
<script type="text/javascript" src="scripts/popup-window.js"></script>
<script type="text/javascript" src="scripts/jquery-1.7.1.min.js"></script>
<script type="text/javascript" src="scripts/jquery-ui-1.8.17.custom.min.js"></script>
<script type="text/javascript" src="scripts/chrome.js"></script>
<script type="text/javascript" src="scripts/iepngfix_tilebg.js"></script>
<?php if($db->loggedIn()) { //No Date-Picker for NON-LoggedIN Users  ?>	
<script type="text/javascript" src="date/jquery.date_input.js"></script>
<script type="text/javascript" src="scripts/date/jquery.jdpicker.js"></script>
<script type="text/javascript" src="date/init.js"></script>
<?php } ?>
<link href="date/date_input.css" rel="stylesheet" type="text/css" media="all" />
<link href="scripts/date/jdpicker.css" rel="stylesheet" type="text/css" media="screen" />
<link href="css/popup_form.css" rel="stylesheet" type="text/css" media="all" />
<link href="css/themes/cupertino/jquery-ui-1.8.17.custom.css" rel="stylesheet" type="text/css" media="screen" />
<style type="text/css">
<?php
///Below part is added cause sometimes in some browser does not seems to work as per inline css
if($Rotation_Flg == 1)
{
	print '
		.box_rotate {
		-moz-transform: rotate(270deg); /* For Firefox */
		-o-transform: rotate(270deg); /* For Opera */
		-webkit-transform: rotate(270deg); /* For Safari and Chrome */
		transform: rotate(270deg);
		-ms-transform: rotate(360deg); /* IE 9 */
		-ms-transform-origin:0% 100%; /* IE 9 */
		-moz-transform-origin:0% 100%; /* Firefox */
		-webkit-transform-origin:0% 100%; /* Safari and Chrome */
		transform-origin:0% 100%;
		white-space:nowrap;
		writing-mode: tb-rl; /* For IE */
		filter: flipv fliph;
		/*font-family:"Courier New", Courier, monospace;*/
		margin-bottom:2px;
	}';
	
	foreach($columns as $col => $val)
	{
		print '
		.Entity2_Row_Class_'.$col.' 
		{
			width:'.$Width_matrix[$col]['width'].'px;
			max-width:'.$Width_matrix[$col]['width'].'px;
			height:'.($Entity2ColHeight).'px;
			max-height:'.($Entity2ColHeight).'px;
			_height:'.($Entity2ColHeight).'px;
		}
		';
		
		if($ColumnsSpan[$col] > 0)
		{
			print '
				.Cat_Entity2_Row_Class_'.$col.' 
				{
					width:'.$CatEntity2Colwidth[$col].'px;
					max-width:'.$CatEntity2Colwidth[$col].'px;';
					if($CatEntity2Rotation_Flg)
					{
						print '	height:'.($Entity2CatHeight).'px;
							_height:'.($Entity2CatHeight).'px;';
					}
			print '}';
		}
	}
	
	print '	
		.Total_Row_Class_height 
		{
			height:'.($Entity2ColHeight).'px;
			_height:'.($Entity2ColHeight).'px;
		}
		
		.Total_Row_Class_width 
		{
			width:'.$Total_Col_width.'px;
			max-width:'.$Total_Col_width.'px;
		}
		';
}
else
{
	foreach($columns as $col => $val)
	{
		print '
		.Entity2_Row_Class_'.$col.' 
		{
			width:110px;
			max-width:110px;
		}
		';
	}
	print '	
		.Total_Row_Class_width
		{
			width:110px;
			max-width:110px;
		}
		';	
}
?>
</style>
<style type="text/css">
/* To add support for transparancy of png images in IE6 below htc file is added alongwith iepngfix_tilebg.js */
img { behavior: url("css/iepngfix.htc"); }

body { font-family:Arial; font-size: 13px;}
a, a:hover{/*color:#000000; text-decoration:none;*/}
table { font-size:13px;}
.display td, .display th {font-weight:normal; background-color:#DDF; vertical-align:middle;}
.active{font-weight:bold;}
.total{visibility:hidden;}
.comma_sep{visibility:hidden;}
.result {
	font-weight:bold;
	font-size:18px;
}

.jdpicker {
	vertical-align:middle;
	position:relative;
}

.tooltip {
	color: #000000; outline: none;
	cursor:default; text-decoration: none;
}
.tooltip span {
	border-radius: 5px 5px; -moz-border-radius: 5px; -webkit-border-radius: 5px; 
	box-shadow: 5px 5px 5px rgba(0, 0, 0, 0.1); -webkit-box-shadow: 5px 5px rgba(0, 0, 0, 0.1); -moz-box-shadow: 5px 5px rgba(0, 0, 0, 0.1);
	font-family:Arial; font-size: 12px;
	position: absolute; 
	margin-left: 0; width: 280px; display: none; z-index: 0;
}
.classic { padding: 0.8em 1em; }
.classic {background: #FFFFAA; border: 1px solid #FFAD33; }

#slideout {
	position: fixed;
	_position:absolute;
	top: 40px;
	right: 0;
	margin: 12px 0 0 0;
}

.slideout_inner {
	position:absolute;
	top: 40px;
	right: -255px;
	display:none;
}

#slideout:hover .slideout_inner{
	display : block;
	position:absolute;
	top: 2px;
	right: 0px;
	width: 280px;
	z-index:10;
}

.table-slide{
	border:1px solid #000;
}
.table-slide td{
	border-right:1px solid #000;
	padding:8px;
	padding-right:20px;
	border-bottom:1px solid #000;
}

.gray {
	background-color:#CCCCCC;
	width: 35px;
	height: 18px;
	float: left;
	text-align: center;
	margin-right: 1px;
	padding-top:3px;
}

.blue {
	background-color:#00ccff;
	width: 35px;
	height: 18px;
	float: left;
	text-align: center;
	margin-right: 1px;
	padding-top:3px;
}

.green {
	background-color:#99cc00;
	width: 35px;
	height: 18px;
	float: left;
	text-align: center;
	margin-right: 1px;
	padding-top:3px;
}

.yellow {
	background-color:#ffff00;
	width: 35px;
	height: 18px;
	float: left;
	text-align: center;
	margin-right: 1px;
	padding-top:3px;
}

.orange {
	background-color:#ff9900;
	width: 35px;
	height: 18px;
	float: left;
	text-align: center;
	margin-right: 1px;
	padding-top:3px;
}

.red {
	background-color:#ff0000;
	width: 35px;
	height: 18px;
	float: left;
	text-align: center;
	margin-right: 1px;
	padding-top:3px;
}

.downldbox {
	height:auto;
	width:310px;
	font-weight:bold;
}

.downldbox ul{
	list-style:none;
	margin:5px;
	padding:0px;
}

.downldbox ul li{
	width: 130px;
	float:left;
	margin:2px;
}
.dropmenudiv{
	position:absolute;
	top: 0;
	border: 1px solid #DDDDDD; /*THEME CHANGE HERE*/
	/*border-bottom-width: 0;*/
	font:normal 12px Arial;
	line-height:18px;
	z-index:100;
	background-color: white;
	width: 50px;
	visibility: hidden;
}

.break_words{
	word-wrap: break-word;
}

.tag {
color:#120f3c;
}

.Status_Label_Style {
color:#206040;
}
.Status_Label_Headers {
color:#206040;
}
.Status_Label_values {
color:#000000;
}
.Data_values {
color:#000000;
}
.Status_Changes_Style {
font-weight:900;
}
.Status_ULStyle {
margin-top:0px;
margin-bottom:0px;
}
.Range_Value_TD {
vertical-align:middle;
display:table-row;
}
.Range_Value {
vertical-align:middle;
}
.Range_Value_Style {
color:#f6931f;
border:0;
background-color:#FFFFFF;
font-family:Arial;
font-size:13px;
}
.entity1ColWidthStyle {
min-width:250px;
}
</style>
<script language="javascript" type="text/javascript">
function change_view()
{
	///Date format set cause some date format does not work in IE
	var today = new Date("<?php print date('m/d/Y H:i:s', strtotime('now', $now)); ?>");	// "mm/dd/yyyy hh:mm:ss"  
	var one_week = new Date("<?php print date('m/d/Y H:i:s', strtotime('-1 Week', $now)); ?>");
	var two_week = new Date("<?php print date('m/d/Y H:i:s', strtotime('-2 Weeks', $now)); ?>");
	var one_month = new Date("<?php print date('m/d/Y H:i:s', strtotime('-1 Month', $now)); ?>");
	var three_month = new Date("<?php print date('m/d/Y H:i:s', strtotime('-3 Months', $now)); ?>");
	var six_month = new Date("<?php print date('m/d/Y H:i:s', strtotime('-6 Months', $now)); ?>");
	var one_year = new Date("<?php print date('m/d/Y H:i:s', strtotime('-1 Year', $now)); ?>");
	
	var limit = document.getElementById('Last_HM').value;
	var dwcount = document.getElementById('dwcount');
	var start_range = document.getElementById('startrange').value;
	var end_range = document.getElementById('endrange').value;
	var bk_start_range = document.getElementById('startrange').value;
	var bk_end_range = document.getElementById('endrange').value;
	var report = document.getElementById("id").value;
	
	var st_limit, ed_limit;
	
	var startrangeInputWidth, endrangeInputWidth;
	switch(start_range)
	{
		case 'now': st_limit = today; startrangeInputWidth = 30; break;
		case '1 week': 	st_limit = one_week; startrangeInputWidth = 55; break;
		case '2 weeks': st_limit = two_week; startrangeInputWidth = 60; break;
		case '1 month': st_limit = one_month; startrangeInputWidth = 60; break;
		case '1 quarter': st_limit = three_month; startrangeInputWidth = 65;break;
		case '6 months': st_limit = six_month;  startrangeInputWidth = 65;  break;
		case '1 year': st_limit = one_year; startrangeInputWidth = 45; break;
		default: start_range = start_range.replace(/\s+/g, '') ;	//Remove space in between
				 var date_arr = start_range.split('-'); 
				 var st_limit = date_arr[1] + "/" + date_arr[2] + "/" + date_arr[0] + " 23:59:59";	///As date Picker format is NOT Supported by by Javascript in IE, manual creation in required format
				 var st_limit = new Date(st_limit);
				 startrangeInputWidth = 80;  
				 break;
	}
	
	 //SET Range style
	 document.getElementById("startrange").style.width = startrangeInputWidth + "px";
	 var startrange_TD_exist = document.getElementById("startrange_TD");
	 if(startrange_TD_exist != null && startrange_TD_exist != '') 
	 document.getElementById("startrange_TD").style.width = (startrangeInputWidth + 20) + "px"; 
	 
	switch(end_range)
	{
		case 'now': ed_limit = today; endrangeInputWidth = 40;  break;
		case '1 week': ed_limit = one_week; endrangeInputWidth = 55;  break;
		case '2 weeks': ed_limit = two_week; endrangeInputWidth = 60;  break;
		case '1 month': ed_limit = one_month; endrangeInputWidth = 60;  break;
		case '1 quarter': ed_limit = three_month; endrangeInputWidth = 70;  break;
		case '6 months': ed_limit = six_month; endrangeInputWidth = 70;  break;
		case '1 year': ed_limit = one_year; endrangeInputWidth = 50;  break;
		default: end_range = end_range.replace(/\s+/g, '') ;
				 var date_arr = end_range.split('-');
				 var ed_limit = date_arr[1] + "/" + date_arr[2] + "/" + date_arr[0] + " 00:00:01"; ///As date Picker format is NOT Supported by by Javascript in IE, manual creation in required format
				 var ed_limit = new Date(ed_limit);
				 endrangeInputWidth = 80;  
				 break;
	}
	
	 //SET Range style
	document.getElementById("endrange").style.width = endrangeInputWidth + "px";  
	
	/* If start limit is greater than end limit interchnage them */
	if(st_limit < ed_limit)
	{
		var temp_limit = ed_limit;
		ed_limit = st_limit;
		st_limit = temp_limit;
		
		var temp_range = end_range;
		end_range = start_range;
		start_range = temp_range;
		
		var temp_range = bk_end_range;
		bk_end_range = bk_start_range;
		bk_start_range = temp_range;
	}
		
	var i=1;
	for(i=1;i<=limit;i++)
	{
		var cell_exist=document.getElementById("Cell_values_"+i);
		var qualify_flg = 0;
		var tooltip_flg = 0;
		if(cell_exist != null && cell_exist != '')
		{
		
			var cell_val=document.getElementById("Cell_values_"+i).value;
			var Cell_values_Arr = cell_val.split(',endl,');
			
			/////Change Count
			var font_element=document.getElementById("Font_ID_"+i);	//Ceck if cell has font element so we can chnage cont value
			
			var tot_element=document.getElementById("Tot_ID_"+i); 	// Check if total column exists
			
			var cell_link_val=document.getElementById("Link_value_"+i).value;	//Check in cell has link
			
			var TotalZero_Flg_ele = document.getElementById("TotalZero_Flg_"+i);	//Check in cell has Zero trials
			if(TotalZero_Flg_ele != null && TotalZero_Flg_ele != '')
			TotalZero_Flg = 1;
			else
			TotalZero_Flg = 0;
			
			
			if(cell_link_val != '' && cell_link_val != null)
			{
				var new_link = '';
				if(dwcount.value == 'active')
				{
					new_link = cell_link_val+'&list=1';
					
					if(tot_element != null && tot_element != '')
					document.getElementById("Tot_ID_"+i).innerHTML = Cell_values_Arr[0];
					
					if(font_element != null && font_element != '')
					{
						document.getElementById("Font_ID_"+i).innerHTML = Cell_values_Arr[0];
					}
				}
				else if(dwcount.value == 'total')
				{
					new_link = cell_link_val+'&list=2';
					
					if(tot_element != null && tot_element != '')
					document.getElementById("Tot_ID_"+i).innerHTML = Cell_values_Arr[1];
					
					if(font_element != null && font_element != '')
					{
						document.getElementById("Font_ID_"+i).innerHTML = Cell_values_Arr[1];
					}
				}
				else if(dwcount.value == 'indlead')
				{
					new_link = cell_link_val+'&list=1&itype=0';
					
					if(tot_element != null && tot_element != '')
					document.getElementById("Tot_ID_"+i).innerHTML = Cell_values_Arr[2];
					
					if(font_element != null && font_element != '')
					{
						document.getElementById("Font_ID_"+i).innerHTML = Cell_values_Arr[2];
					}
					
				}	
				
				//if slider has default range dont add these parameters in links as OTT has same default range
				if(start_range != 'now' || end_range != '1 month')	
				new_link = new_link + '&sr='+start_range+'&er='+end_range;
				new_link = new_link + '&hm='+report;
				document.getElementById("Cell_Link_"+i).href = new_link;
				
				if(TotalZero_Flg == 1)
				{
					document.getElementById("Cell_Link_"+i).href = '#';
					document.getElementById("Cell_Link_"+i).style.textDecoration = 'none';
					if(font_element != null && font_element != '')
					document.getElementById("Font_ID_"+i).innerHTML = '&nbsp;';
				}
			}
		
			
			
			if(font_element != null && font_element != '')
			{
				
				///Change Cell Border Color
				var record_cdate= new Date(Cell_values_Arr[6]);	//Record Update Date
				
				///Change Count Color
				var count_cdate= new Date(Cell_values_Arr[8]);	//Count Chnage Date
				
				
				
				
					
				///Change Bomb Color
				var bomb_cdate= new Date(Cell_values_Arr[10]);	//Bomb Chnage Date
				var bomb_ele= document.getElementById("Cell_Bomb_"+i);	//Bomb Element
				
				if(bomb_ele != null && bomb_ele != '')
				{
					if((bomb_cdate <= st_limit) && (bomb_cdate >= ed_limit)) //Compare Bomb Change Dates
					{
						document.getElementById("Cell_Bomb_"+i).title = "Bomb Data Updated On: "+ Cell_values_Arr[11];
						
						if(Cell_values_Arr[15] == 'large')
						{
							document.getElementById("Cell_Bomb_"+i).src = "images/newred_lbomb.png";
							document.getElementById("Bomb_Img_"+i).innerHTML = '<img title="Bomb" src="images/newred_lbomb.png"  style="width:17px; height:17px; vertical-align:middle; cursor:pointer; padding-bottom:2px;" />&nbsp;';
						}
						else if(Cell_values_Arr[15] == 'small')
						{
							document.getElementById("Cell_Bomb_"+i).src = "images/newred_sbomb.png";
							document.getElementById("Bomb_Img_"+i).innerHTML = '<img title="Bomb" src="images/newred_sbomb.png"  style="width:17px; height:17px; vertical-align:middle; cursor:pointer; padding-bottom:2px;" />&nbsp;';
						}
						
						qualify_flg = 1;
						tooltip_flg = 1;
					}
					else
					{
						//document.getElementById("Bomb_CDate_"+i).style.display = "none"
						
						if(Cell_values_Arr[15] == 'large')
						{
							document.getElementById("Cell_Bomb_"+i).src = "images/new_lbomb.png";
							document.getElementById("Cell_Bomb_"+i).title = "Large Bomb";
							document.getElementById("Bomb_Img_"+i).innerHTML = '<img title="Bomb" src="images/new_lbomb.png"  style="width:17px; height:17px; vertical-align:middle; cursor:pointer; padding-bottom:2px;" />&nbsp;';
						}
						else if(Cell_values_Arr[15] == 'small')
						{
							document.getElementById("Cell_Bomb_"+i).src = "images/new_sbomb.png";
							document.getElementById("Cell_Bomb_"+i).title = "Small Bomb";
							document.getElementById("Bomb_Img_"+i).innerHTML = '<img title="Bomb" src="images/new_sbomb.png"  style="width:17px; height:17px; vertical-align:middle; cursor:pointer; padding-bottom:2px;" />&nbsp;';
						}
					}
				}
				
				///Change Filing Color
				var filing_cdate= new Date(Cell_values_Arr[12]);	//Filing Chnage Date
				var filing_ele= document.getElementById("Cell_Filing_"+i);	//Bomb Element
				
				if(filing_ele != null && filing_ele != '')
				{
					if((filing_cdate <= st_limit) && (filing_cdate >= ed_limit)) //Compare Filing Change Dates
					{
						document.getElementById("Cell_Filing_"+i).title = "Filing Data Updated On: "+ Cell_values_Arr[13];
						document.getElementById("Cell_Filing_"+i).src = "images/newred_file.png";
						document.getElementById("Filing_Img_"+i).innerHTML = '<img title="Filing" src="images/newred_file.png"  style="width:17px; height:17px; vertical-align:middle; cursor:pointer; padding-bottom:2px;" />&nbsp;';
						qualify_flg = 1;
						tooltip_flg = 1;
					}
					else
					{
						document.getElementById("Cell_Filing_"+i).title = "Filing Details";
						document.getElementById("Cell_Filing_"+i).src = "images/new_file.png";
						document.getElementById("Filing_Img_"+i).innerHTML = '<img title="Filing" src="images/new_file.png"  style="width:17px; height:17px; vertical-align:middle; cursor:pointer; padding-bottom:2px;" />&nbsp;';
					}
				}
				
				///Change Phase Explain Color
				var phaseexp_cdate= new Date(Cell_values_Arr[16]);	//Filing Chnage Date
				var phaseexp_ele= document.getElementById("Phaseexp_Img_"+i);	//Bomb Element
				
				if(phaseexp_ele != null && phaseexp_ele != '')
				{
					if((phaseexp_cdate <= st_limit) && (phaseexp_cdate >= ed_limit)) //Compare Filing Change Dates
					{
						document.getElementById("Phaseexp_Img_"+i).innerHTML = '<img title="Phase explanation" src="images/phaseexp_red.png"  style="width:17px; height:17px; vertical-align:middle; cursor:pointer; padding-bottom:2px;" />&nbsp;';
						qualify_flg = 1;
						tooltip_flg = 1;
					}
					else
					{
						document.getElementById("Phaseexp_Img_"+i).innerHTML = '<img title="Phase explanation" src="images/phaseexp.png"  style="width:17px; height:17px; vertical-align:middle; cursor:pointer; padding-bottom:2px;" />&nbsp;';
					}
				}
				
				
				///Change Hign Phase Details
				var high_phase_cdate= new Date(Cell_values_Arr[20]);	//High Phase Chnage Date
				var high_phase_ele= document.getElementById("Highest_Phase_"+i);	//high phase Element
				
				if(high_phase_ele != null && high_phase_ele != '')
				{
					if((high_phase_cdate <= st_limit) && (high_phase_cdate >= ed_limit)) //Compare highest phase Change Dates
					{
						document.getElementById("Highest_Phase_"+i).style.display = "inline";
						document.getElementById("Highest_Phase_"+i).title = "Highest Phase";
						qualify_flg = 1;
						tooltip_flg = 1;
					}
					else
					{
						document.getElementById("Highest_Phase_"+i).title = "Highest Phase";
						document.getElementById("Highest_Phase_"+i).style.display = "none"
					}
				}
				
				var viewcount_ele = document.getElementById("ViewCount_value_"+i);
				var maxviewcount_ele = document.getElementById("Max_ViewCount_value");
				if(maxviewcount_ele != null && maxviewcount_ele != '' && TotalZero_Flg != 1)
				{
					var maxview = maxviewcount_ele.value;
					if(viewcount_ele != null && viewcount_ele != '')
					{
						var view = viewcount_ele.value;
						if(view > 0)
						{
							document.getElementById("ViewCount_"+i).innerHTML = '<font class="Status_Label_Headers">Number of views: </font><font class="Data_values">'+view+'</font><input type="hidden" value="'+view+'" id="ViewCount_value_'+i+'" />';
							tooltip_flg = 1;
						}
					}
				}
				
				var New_Trials_ele = document.getElementById("New_Trials_"+i);
				if(New_Trials_ele != '' && New_Trials_ele != null && TotalZero_Flg != 1)
				{
					if(ed_limit == one_month)
					{
						tooltip_flg = 1;
						document.getElementById("New_Trials_"+i).style.display = "inline";
					}
					else
					{
						document.getElementById("New_Trials_"+i).style.display = "none";
					}
				}
				
				var Status_Total_List_ele = document.getElementById("Status_Total_List_"+i);
				if(Status_Total_List_ele != '' && Status_Total_List_ele != null && TotalZero_Flg != 1)
				{
					if(ed_limit == one_month && dwcount.value == 'total')
					{
						tooltip_flg = 1;
						document.getElementById("Status_Total_List_"+i).style.display = "inline";
					}
					else
					{
						document.getElementById("Status_Total_List_"+i).style.display = "none";
					}
				}
				
				var Status_Indlead_List_ele = document.getElementById("Status_Indlead_List_"+i);
				if(Status_Indlead_List_ele != '' && Status_Indlead_List_ele != null && TotalZero_Flg != 1)
				{
					if(ed_limit == one_month && dwcount.value == 'indlead')
					{
						tooltip_flg = 1;
						document.getElementById("Status_Indlead_List_"+i).style.display = "inline";
					}
					else
					{
						document.getElementById("Status_Indlead_List_"+i).style.display = "none";
					}
				}
				
				var Status_Active_List_ele = document.getElementById("Status_Active_List_"+i);
				if(Status_Active_List_ele != '' && Status_Active_List_ele != null && TotalZero_Flg != 1)
				{
					if(ed_limit == one_month && dwcount.value == 'active')
					{
						tooltip_flg = 1;
						document.getElementById("Status_Active_List_"+i).style.display = "inline";
					}
					else
					{
						document.getElementById("Status_Active_List_"+i).style.display = "none";
					}
				}
				
				
				if(tooltip_flg == 1)
				{
					document.getElementById("ToolTip_Visible_"+i).value = "1"
					if(qualify_flg == 1)
					{
						document.getElementById("Cell_ID_"+i).style.border = "#FF0000 solid";
						document.getElementById("Cell_ID_"+i).style.backgroundColor = "#FFFFFF";
					}
					else
					{
						document.getElementById("Cell_ID_"+i).style.border = "#"+Cell_values_Arr[14]+" solid";
						document.getElementById("Cell_ID_"+i).style.backgroundColor = "#"+Cell_values_Arr[14];
					}
				}
				else
				{
					var bomb_presence_ele = document.getElementById("Bomb_Presence_"+i);
					
					var viewcount_ele = document.getElementById("ViewCount_value_"+i);
					if(viewcount_ele != null && viewcount_ele != '')
					var view = viewcount_ele.value;
					else view = 0;
					
					if((phaseexp_ele != null && phaseexp_ele != '') || (filing_ele != null && filing_ele != '') || (bomb_presence_ele != null && bomb_presence_ele != '') || (view > 0))
					{
						document.getElementById("ToolTip_Visible_"+i).value = "1";
					}
					else
					{
						document.getElementById("ToolTip_Visible_"+i).value = "0";
					}
					document.getElementById("Cell_ID_"+i).style.border = "#"+Cell_values_Arr[14]+" solid";
					document.getElementById("Cell_ID_"+i).style.backgroundColor = "#"+Cell_values_Arr[14];
				}
				document.getElementById("Div_ID_"+i).title = '';
				document.getElementById("Cell_Link_"+i).title = '';
				if(bomb_ele != null && bomb_ele != '')
				document.getElementById("Cell_Bomb_"+i).title = '';
				if(filing_ele != null && filing_ele != '')
				document.getElementById("Cell_Filing_"+i).title = '';
			}	///Font Element If Ends
		} /// Cell Data Exists if Ends
	}	/// For Loop Ends
}

function timeEnum($timerange)
	{
		switch($timerange)
		{
			case 0: $timerange = "now"; break;
			case 1: $timerange = "1 week"; break;
			case 2: $timerange = "2 weeks"; break;
			case 3: $timerange = "1 month"; break;
			case 4: $timerange = "1 quarter"; break;
			case 5: $timerange = "6 months"; break;
			case 6: $timerange = "1 year"; break;
		}
		return $timerange;
	}

$(function() 
{

<?php if(!$db->loggedIn()) { ?>		
		$("#slider-range-min").slider({	//Single Slider - For NOT LoogedIN Users
		range: "min",
		value: 3,
		min: 0,
		max: 6,
		step:1,
		slide: function( event, ui ) {
			$("#endrange").val(timeEnum(ui.value));
			change_view();
		}
	});
	$timerange = "1 month";
	$("#endrange").val($timerange);
<?php } else { ?>
//highlight changes slider
		$("#slider-range-min").slider({	//Double Slider - For LoggedIN Users
			range: false,
			min: 0,
			max: 6,
			step: 1,
			values: [ 0, 3 ],
			slide: function(event, ui) {
				if(ui.values[0] > ui.values[1])/// Switch highlight range when sliders cross each other
				{
					$("#startrange").val(timeEnum(ui.values[1]));
					$("#endrange").val(timeEnum(ui.values[0]));
					change_view();
				}
				else
				{
					$("#startrange").val(timeEnum(ui.values[0]));
					$("#endrange").val(timeEnum(ui.values[1]));
					change_view();
				}
			}
		});
<?php } ?>

	
});

function display_tooltip(type, id)
{
	var tooltip_ele = document.getElementById("ToolTip_ID_"+id);
	var tooltip_val_ele = document.getElementById("ToolTip_Visible_"+id);
	if((tooltip_ele != null && tooltip_ele != '') && (tooltip_val_ele != null && tooltip_val_ele != ''))
	{
		if(type =='on' && tooltip_val_ele.value==1)
		{
			tooltip_ele.style.display = "block";
			tooltip_ele.style.zIndex = "99";
			
			///// Start Part - Position the tooltip properly for the cells which are at leftmost edge of window 
			var windowedge=document.all && !window.opera? document.documentElement.scrollLeft+document.documentElement.clientWidth - 25 : window.pageXOffset+window.innerWidth - 25
			var tooltipW = 300
			if (windowedge-tooltip_ele.offsetLeft < tooltipW)  //move menu to the left?
			{
				edgeoffset = tooltipW - document.getElementById("Cell_ID_"+id).offsetWidth + 30
				tooltip_ele.style.left = tooltip_ele.offsetLeft - edgeoffset +"px"
			}
			///// End Part - Position the tooltip properly for the cells which are at leftmost edge of window 
			
			///// Start Part - Position the tooltip properly for the cells which are at bottommost edge of window 
			var tooltipH=document.getElementById("ToolTip_ID_"+id).offsetHeight
			var windowedge=document.all && !window.opera && !window.ActiveXObject? document.documentElement.scrollTop+document.documentElement.clientHeight-25 : window.pageYOffset+window.innerHeight-25;
			if ((windowedge- (tooltip_ele.offsetTop + document.getElementById("Cell_ID_"+id).offsetHeight)) < tooltipH)	//move up?
			{ 	
				edgeoffset = tooltipH + document.getElementById("Cell_ID_"+id).offsetHeight - 8;
				tooltip_ele.style.top = tooltip_ele.offsetTop - edgeoffset +"px";
			}
			///// End Part - Position the tooltip properly for the cells which are at bottommost edge of window 
		}
		else
		{
			tooltip_ele.style.display = "none";
			tooltip_ele.style.zIndex = "0";
			tooltip_ele.style.left = "";
			tooltip_ele.style.top = "";
		}
	}
}

function refresh_data(cell_id)
{
	var Entity1_ele=document.getElementById("Entity1_value_"+cell_id);
	var Entity2_ele=document.getElementById("Entity2_value_"+cell_id);
	Entity1=Entity1_ele.value.replace(/\s+/g, '');
	Entity2=Entity2_ele.value.replace(/\s+/g, '');
	
	var limit = document.getElementById('Last_HM').value;
	var i=1;
	for(i=1;i<=limit;i++)
	{
		var cell_exist=document.getElementById("Cell_values_"+i);
		if(cell_exist != null && cell_exist != '')
		{
			var font_element=document.getElementById("Font_ID_"+i);
			if(font_element != null && font_element != '')
			{
				var current_Entity1_ele=document.getElementById("Entity1_value_"+i);
				var current_Entity2_ele=document.getElementById("Entity2_value_"+i);
			
				if((current_Entity1_ele != null && current_Entity1_ele != '') && (current_Entity2_ele != '' && current_Entity2_ele != null) && (i != cell_id))
				{
					current_Entity1=current_Entity1_ele.value.replace(/\s+/g, '');
					current_Entity2=current_Entity2_ele.value.replace(/\s+/g, '');
				
					if(current_Entity1 == Entity1 && current_Entity2 == Entity2)
					{
						document.getElementById("ViewCount_value_"+i).value=document.getElementById("ViewCount_value_"+cell_id).value;
						change_view();
					}
				}
			}
		}
	}
}
</script>
<script type="text/javascript">
	//Count the Number of View of Records
	function INC_ViewCount(Entity1, Entity2, cell_id)
	{
		 $.ajax({
						type: 'GET',
						url:  'viewcount.php' + '?op=Inc_OHM_ViewCount&entity1=' + Entity1 +'&entity2=' + Entity2 + '&Cell_ID=' + cell_id,
						success: function (data) {
	        					//alert(data);
								$("#ViewCount_"+cell_id).html(data);
								var viewcount_ele = document.getElementById("ViewCount_value_"+cell_id);
								var maxviewcount_ele = document.getElementById("Max_ViewCount_value");
								if(maxviewcount_ele != null && maxviewcount_ele != '')
								{
									var maxview = maxviewcount_ele.value;
									if(viewcount_ele != null && viewcount_ele != '')
									{
										var view = viewcount_ele.value;
										if(view > maxview)
										document.getElementById("Max_ViewCount_value").value = view;
									}
								}
								refresh_data(cell_id);
								change_view();
						}
				});
	        return;
	}
	</script>
    
    <script type="text/javascript">
        var currentFixedHeader;
        var currentGhost;
		var ScrollOn = false;
		
		//Start - Header recreation in case of window resizing
		$(window).resize(function() {
			$.fn.reverse = [].reverse;
			var createGhostHeader = function (header, topOffset, leftOffset) {
                // Recreate heaaderin case of window resizing even if there is current ghost header exists
              if (currentGhost)
                    $(currentGhost).remove();
                
                var realTable = $(header).parents('#hmMainTable');
                
                var headerPosition = $(header).offset();
                var tablePosition = $(realTable).offset();
                
                var container = $('<table border="0" cellspacing="2" cellpadding="0" style="vertical-align:middle; background-color:#FFFFFF;" class="display" id="hmMainTable1"></table>');
                
                // Copy attributes from old table (may not be what you want)
                for (var i = 0; i < realTable[0].attributes.length; i++) {
                    var attr = realTable[0].attributes[i];
					//We are not manually copying table attributes so below line is commented cause it does not work in IE6 and IE7
                    //container.attr(attr.name, attr.value);
                }
                                
                // Set up position of fixed row
                container.css({
                    position: 'fixed',
                    top: -topOffset,
                    left: (-$(window).scrollLeft() + leftOffset),
                    width: $(realTable).outerWidth()
                });
                
                // Create a deep copy of our actual header and put it in our container
                var newHeader = $(header).clone().appendTo(container);
                
                var collection2 = $(newHeader).find('td');
                
                // TODO: Copy the width of each <td> manually
                $(header).find('td').each(function () {
                    var matchingElement = $(collection2.eq($(this).index()));
                    $(matchingElement).width(this.offsetWidth + 0.5);
                });
				
                currentGhost = container;
                currentFixedHeader = header;
                
                // Add this fixed row to the same parent as the table
                $(table).parent().append(currentGhost);
                return currentGhost;
            };

            var currentScrollTop = $(window).scrollTop();

            var activeHeader = null;
            var table = $('#hmMainTable').first();
            var tablePosition = table.offset();
            var tableHeight = table.height();
            
            var lastHeaderHeight = $(table).find('thead').last().height();
            var topOffset = 0;
            
            // Check that the table is visible and has space for a header
            if (tablePosition.top + tableHeight - lastHeaderHeight >= currentScrollTop)
            {
                var lastCheckedHeader = null;
                // We do these in reverse as we want the last good header
                var headers = $(table).find('thead').reverse().each(function () {
                    var position = $(this).offset();
                    
                    if (position.top <= currentScrollTop)
                    {
                        activeHeader = this;
                        return false;
                    }
                    
                    lastCheckedHeader = this;
                });
                
                if (lastCheckedHeader)
                {
                    var offset = $(lastCheckedHeader).offset();
                    if (offset.top - currentScrollTop < $(activeHeader).height())
                        topOffset = $(activeHeader).height() - (offset.top - currentScrollTop) + 1;
                }
            }
            // No row is needed, get rid of one if there is one
            if (activeHeader == null && currentGhost)

            {
                currentGhost.remove();

                currentGhost = null;
                currentFixedHeader = null;
            }
            
            // We have what we need, make a fixed header row
            if (activeHeader)
			{
                createGhostHeader(activeHeader, topOffset, ($('#hmMainTable').offset().left));
			}
		});
		//End - Header recreation in case of window resizing
		
        ///Start - Header creation or align header incase of scrolling
		$(window).scroll(function() {
            $.fn.reverse = [].reverse;
			if(!ScrollOn)
			{
            	setEntity2ColWidth();
				ScrollOn = true;
			}
            var createGhostHeader = function (header, topOffset, leftOffset) {
                // Don't recreate if it is the same as the current one
                if (header == currentFixedHeader && currentGhost)
                {
                    currentGhost.css('top', -topOffset + "px");
					currentGhost.css('left',(-$(window).scrollLeft() + leftOffset) + "px");
                    return currentGhost;
                }
                
                if (currentGhost)
                    $(currentGhost).remove();
                
                var realTable = $(header).parents('#hmMainTable');
                
                var headerPosition = $(header).offset();
                var tablePosition = $(realTable).offset();
                
                var container = $('<table border="0" cellspacing="2" cellpadding="0" style="vertical-align:middle; background-color:#FFFFFF;" class="display" id="hmMainTable1"></table>');
                
                // Copy attributes from old table (may not be what you want)
                for (var i = 0; i < realTable[0].attributes.length; i++) {
                    var attr = realTable[0].attributes[i];
					//We are not manually copying table attributes so below line is commented cause it does not work in IE6 and IE7
                    //container.attr(attr.name, attr.value);
                }
                                
                // Set up position of fixed row
                container.css({
                    position: 'fixed',
                    top: -topOffset,
                    left: (-$(window).scrollLeft() + leftOffset),
                    width: $(realTable).outerWidth()
                });
                
                // Create a deep copy of our actual header and put it in our container
                var newHeader = $(header).clone().appendTo(container);
                
                var collection2 = $(newHeader).find('td');
                
                // TODO: Copy the width of each <td> manually
                $(header).find('td').each(function () {
                    var matchingElement = $(collection2.eq($(this).index()));
                    $(matchingElement).width(this.offsetWidth + 0.5);
                });
				
                currentGhost = container;
                currentFixedHeader = header;
                
                // Add this fixed row to the same parent as the table
                $(table).parent().append(currentGhost);
                return currentGhost;
            };

            var currentScrollTop = $(window).scrollTop();

            var activeHeader = null;
            var table = $('#hmMainTable').first();
            var tablePosition = table.offset();
            var tableHeight = table.height();
            
            var lastHeaderHeight = $(table).find('thead').last().height();
            var topOffset = 0;
            
            // Check that the table is visible and has space for a header
            if (tablePosition.top + tableHeight - lastHeaderHeight >= currentScrollTop)
            {
                var lastCheckedHeader = null;
                // We do these in reverse as we want the last good header
                var headers = $(table).find('thead').reverse().each(function () {
                    var position = $(this).offset();
                    
                    if (position.top <= currentScrollTop)
                    {
                        activeHeader = this;
                        return false;
                    }
                    
                    lastCheckedHeader = this;
                });
                
                if (lastCheckedHeader)
                {
                    var offset = $(lastCheckedHeader).offset();
                    if (offset.top - currentScrollTop < $(activeHeader).height())
                        topOffset = $(activeHeader).height() - (offset.top - currentScrollTop) + 1;
                }
            }
            // No row is needed, get rid of one if there is one
            if (activeHeader == null && currentGhost)

            {
                currentGhost.remove();

                currentGhost = null;
                currentFixedHeader = null;
            }
            
            // We have what we need, make a fixed header row
            if (activeHeader)
			{
                createGhostHeader(activeHeader, topOffset, ($('#hmMainTable').offset().left));
			}
        });
		///End - Header creation or align header incase of scrolling
		
	function setEntity2ColWidth()
	{
		var limit = document.getElementById('Last_HM').value;
		var i=1, k=1, first;
		for(i=1;i<=limit;i++)
		{
			var cell_exist=document.getElementById("Cell_ID_"+i);
			if(cell_exist != null && cell_exist != '')
			{
				var cell_type = document.getElementById("Cell_Type_"+i);
				var cell_row = document.getElementById("Cell_RowNum_"+i);
				var cell_col = document.getElementById("Cell_ColNum_"+i);
				if(cell_type != null && cell_type != '' && cell_row != null && cell_row != '' && cell_col != null && cell_col != '')
				{
					if(cell_type.value.replace(/\s+/g, '') == 'HM_Cell' && cell_row.value.replace(/\s+/g, '') == 1)
					{
						for(k=1;k<=limit;k++)
						{
							var cell_exist2=document.getElementById("Cell_ID_"+k);
							if(cell_exist2 != null && cell_exist2 != '')
							{
								var cell_type2 = document.getElementById("Cell_Type_"+k);
								var cell_col2 = document.getElementById("Cell_ColNum_"+k);
								if(cell_type2 != null && cell_type2 != '' && cell_col2 != null && cell_col2 != '')
								{
									if(cell_type2.value.replace(/\s+/g, '') == 'Entity2' && cell_col2.value.replace(/\s+/g, '') == cell_col.value.replace(/\s+/g, ''))
									{
										cell_exist2.style.width = (cell_exist.offsetWidth) + "px";
										$.browser.chrome = /chrome/.test(navigator.userAgent.toLowerCase()); 
										if(!$.browser.chrome)
										{
											cell_exist2.style.border = 'solid rgb(221, 221, 255)';
											cell_exist2.style.paddingLeft = '1px';
											cell_exist2.style.paddingRight = '1px';
										} // chrome does not need borders to be specified but other browsers need it ?>
									}
								}
							}
						}
					}
					else if(cell_type.value.replace(/\s+/g, '') == 'Entity1' && cell_row.value.replace(/\s+/g, '') == 1)
					{
						first = i;
					}
					
					//when we complete row 1, return back as no need of further processing
					if(cell_row.value.replace(/\s+/g, '') > 1)
					return;
				}
			}
		}
	}
    </script>

</head>

<body bgcolor="#FFFFFF" style="background-color:#FFFFFF;">
<?php 

$online_HMCounter=0;

$name = htmlspecialchars(strlen($name)>0?$name:('report '.$id.''));

$Report_Name = ((trim($ReportDisplayName) != '' && $ReportDisplayName != NULL)? trim($ReportDisplayName):'report '.$id.'');

if( ( (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'larvolinsight') == FALSE) || !isset($_SERVER['HTTP_REFERER']) ) && ( !isset($_REQUEST['LI']) || $_REQUEST['LI'] != 1) )
{
	$htmlContent .= '<table cellspacing="0" cellpadding="0" width="100%" style="background-color:#FFFFFF;">'
				. '<tr><td  width="33%" style="background-color:#FFFFFF;"><img src="images/Larvol-Trial-Logo-notag.png" alt="Main" width="327" height="47" id="header" /></td>'
				. '<td width="34%" align="center" style="background-color:#FFFFFF;" nowrap="nowrap"><span style="color:#ff0000;font-weight:normal;margin-left:0px;">Interface work in progress</span>'
				. '<br/><span style="font-weight:normal;">Send feedback to '
				. '<a style="display:inline;color:#0000FF;" target="_self" href="mailto:larvoltrials@larvol.com">'
				. 'larvoltrials@larvol.com</a></span></td>'
				. '<td width="33%" align="right" style="background-color:#FFFFFF; padding-right:20px;" class="result">Name: ' . htmlspecialchars($Report_Name) . ' Heatmap</td></tr></table><br/>';
}
				
$htmlContent .= '<form action="master_heatmap.php" method="post">'
				. '<table border="0" cellspacing="0" cellpadding="0" align="center">'
				. '<tr>'
				. '<td style="vertical-align:middle; padding-right:8px;"><select id="dwcount" name="dwcount" onchange="change_view()">'
				. '<option value="indlead" selected="selected">Active industry trials</option>'
				. '<option value="active">Active trials</option>'
				. '<option value="total">All trials</option></select></td>'
				. '<td style="background-color:#FFFFFF;">'
				. '<table border="0" cellspacing="0" cellpadding="0"><tr>'
				. '<td style="vertical-align:middle;">Highlight updates:</td>';
				
if(!$db->loggedIn()) 
{ 				
	$htmlContent .= '<td><input type="hidden" id="startrange" name="sr" value="now"/></td>';
}
else
{			
	$htmlContent .= '<td id="startrange_TD" class="Range_Value_TD"><input type="text" id="startrange" name="sr" value="now" readonly="readonly" class="jdpicker Range_Value_Style Range_Value_Align Range_Value" /></td><td style="vertical-align:middle;"><label style="color:#f6931f;">-</label></td>';
}
				
$htmlContent .= '<td class="Range_Value_TD"><input type="text" id="endrange" name="er" value="1 month" readonly="readonly" class="jdpicker Range_Value_Style Range_Value_Align Range_Value" /></td>'
				. '<td style="vertical-align:middle; padding-left:5px;"><div id="slider-range-min" style="width:320px;"></div></td>'
				. '</tr></table>'
				. '</td>'
				. '<td style="vertical-align:middle; padding-left:15px;">'
				. '<div style="border:1px solid #000000; float:right; margin-top: 0px; padding:2px;" id="chromemenu"><a rel="dropmenu"><span style="padding:2px; padding-right:4px; background-position:left center; background-repeat:no-repeat; background-image:url(\'./images/save.png\'); cursor:pointer; ">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Export</b></span></a></div>'
				. '</td>'
				. '</tr>'
				. '</table>'
				. '<br style="line-height:11px;"/>';
				
$htmlContent  .= '<div id="dropmenu" class="dropmenudiv" style="width: 310px;">'
				.'<div style="height:100px; padding:6px;"><div class="downldbox"><div class="newtext">Download options</div>'
				. '<input type="hidden" name="id" id="id" value="' . $id . '" />'
				. '<ul><li><label>Which format: </label></li>'
				. '<li><select id="dwformat" name="dwformat" size="2" style="height:40px">'
				. '<option value="exceldown" selected="selected">Excel</option>'
				. '<option value="pdfdown">PDF</option>'
				. '</select></li>'
				. '</ul>'
				. '<input type="submit" name="download" title="Download" value="Download file" style="margin-left:8px;"  />'
				. '</div></div>'
				.'</div><script type="text/javascript">cssdropdown.startchrome("chromemenu");</script></form>';
						
$htmlContent .= '<div align="center" style="vertical-align:top;">'
			. '<table border="0" cellspacing="2" cellpadding="0" style="vertical-align:middle; background-color:#FFFFFF; ';
			if(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') == FALSE)
				$htmlContent .=' height:100%;';	///100% height causes unwanted stretching of table cell in IE but it requires specially for chrome for div scaling
$htmlContent .='" class="display" id = "hmMainTable">'
			. '<thead id = "hmMainTable_Header"><tr><th id="hmMainTable_HeaderFirstCell" style="background-color:#FFFFFF;"></th>';
						
foreach($columns as $col => $val)
{
	if($ColumnsSpan[$col] > 0)
	{
		$online_HMCounter++;
		$htmlContent .= '<th class="Cat_Entity2_Row_Class_'.$col.'" width="'.$CatEntity2Colwidth[$col].'px" style="'.(($CatEntity2Rotation[$col]) ? 'vertical-align:bottom;':'vertical-align:middle;').'max-width:'.$CatEntity2Colwidth[$col].'px;background-color:#FFFFFF; '.(($columnsCategoryName[$col] != 'Undefined') ? 'border-left:#000000 solid 2px; border-top:#000000 solid 2px; border-right:#000000 solid 2px;':'border:#FFFFFF solid 2px;').'" id="Cell_ID_'.$online_HMCounter.'" colspan="'.$ColumnsSpan[$col].'" '.(($CatEntity2Rotation[$col]) ? 'height="'.$Entity2CatHeight.'px" align="left"':'align="center"').'><div class="'.(($CatEntity2Rotation[$col]) ? 'box_rotate Cat_RowDiv_Class_'.$col.' ':'break_words').'">';
		if($columnsCategoryName[$col] != 'Undefined' && $Rotation_Flg == 1 && $CatEntity2Rotation[$col])
		{
			$cat_name = str_replace(' ',' ',trim($columnsCategoryName[$col]));
			//$cat_name = preg_replace('/([^\s-]{'.$cols_Cat_Space[$col].'})(?=[^\s-])/','$1<br/>',$cat_name);
			$cat_name = wordwrap($cat_name, $cols_Cat_Space[$col], "<br />\n", true);
			$cat_name = str_replace('`',' ',$cat_name);
			$htmlContent .= '<b>'.$cat_name.'</b>';
		}
		else if($columnsCategoryName[$col] != 'Undefined')
		{
			$htmlContent .= '<b>'.$columnsCategoryName[$col].'</b>';	
		}
		$htmlContent .= '</div></th>';
	}
}

if($total_fld)
{
	$htmlContent .= '<th style="background-color:#FFFFFF; border:#FFFFFF solid 2px;" id="CatTotalCol">&nbsp;</th>';
} 
//width="'.$entity1ColWidth.'px" currently not needed
$htmlContent .= '</tr><tr><th '.(($Rotation_Flg == 1) ? 'height="'.$Entity2ColHeight.'px"':'').' class="Entity1_Row_Class" style="background-color:#FFFFFF;">&nbsp;</th>';


foreach($columns as $col => $val)
{
	$online_HMCounter++;
	$val = $columnsDisplayName[$col].$columnsCompanyName[$col].((trim($columnsTagName[$col]) != '') ? ' ['.$columnsTagName[$col].']':'');
	$cdesc = (isset($columnsDescription[$col]) && $columnsDescription[$col] != '')?$columnsDescription[$col]:null;
	$caltTitle = (isset($cdesc) && $cdesc != '')?' alt="'.$cdesc.'" title="'.$cdesc.'" ':null;
	$cat = (isset($columnsCategoryName[$col]) && $columnsCategoryName[$col] != '')? ' ('.$columnsCategoryName[$col].') ':'';
		
	$htmlContent .= '<th style="'.(($Rotation_Flg == 1) ? 'vertical-align:bottom;':'vertical-align:middle;').'  background-color:#DDF;" class="Entity2_Row_Class_'.$col.'" id="Cell_ID_'.$online_HMCounter.'" '.(($Rotation_Flg == 1) ? 'height="'.$Entity2ColHeight.'px" align="left"':'align="center"').' '.$caltTitle.'><div class="'.(($Rotation_Flg == 1) ? 'box_rotate Entity2_RowDiv_Class_'.$col.'':'break_words').'" style="background-color:#DDF;">';
	
	$htmlContent .= '<input type="hidden" value="Entity2" name="Cell_Type_'.$online_HMCounter.'" id="Cell_Type_'.$online_HMCounter.'" />';
	$htmlContent .= '<input type="hidden" value="'.$col.'" name="Cell_ColNum_'.$online_HMCounter.'" id="Cell_ColNum_'.$online_HMCounter.'" />';
	
	if($Rotation_Flg != 1)
	$htmlContent .= '<p style="overflow:hidden; width:'.$Width_matrix[$col]['width'].'px; padding:0px; margin:0px;">';
	
	if(isset($entity2Ids[$col]) && $entity2Ids[$col] != NULL && !empty($entity1Ids))
	{
		$htmlContent .= '<input type="hidden" value="'.$col_active_total[$col].',endl,'.$col_count_total[$col].',endl,'.$col_indlead_total[$col].'" name="Cell_values_'.$online_HMCounter.'" id="Cell_values_'.$online_HMCounter.'" />';
		$htmlContent .= '<input type="hidden" value="'. trim(urlPath()) .'intermediary.php?e2=' . $entity2Ids[$col]. '" name="Link_value_'.$online_HMCounter.'" id="Link_value_'.$online_HMCounter.'" />';
		
		$htmlContent .= '<a id="Cell_Link_'.$online_HMCounter.'" href="'. trim(urlPath()) .'intermediary.php?e2=' . $entity2Ids[$col]. '&list=1&itype=0&hm=' . $id . '" target="_blank" style="text-decoration:underline; color:#000000;">';
		
		if($Rotation_Flg == 1)
		{
			$Entity2Name = str_replace(' ',' ',trim($val));
			//$Entity2Name = preg_replace('/([^\s-]{'.$ColsEntity2Space[$col].'})(?=[^\s-])/','$1<br/>',$Entity2Name);
			$Entity2Name = wordwrap($Entity2Name, $ColsEntity2Space[$col], "<br />\n", true);
			$Entity2Name = str_replace('`',' ',$Entity2Name);
			$htmlContent .= formatBrandName($Entity2Name, 'area').'</a>';
		}
		else
			$htmlContent .= trim(formatBrandName($val, 'area')).'</a>';
		
	if($Rotation_Flg != 1)
	$htmlContent .= '</p>';
			
	}
	$htmlContent .='</div></th>';
}

		
//if total checkbox is selected
if($total_fld)
{
	$online_HMCounter++;
	$htmlContent .= '<th id="Cell_ID_'.$online_HMCounter.'" '.(($Rotation_Flg == 1) ? 'height="'.$Entity2ColHeight.'px" align="left"':'align="center"').' style="'.(($Rotation_Flg == 1) ? 'vertical-align:bottom;':'vertical-align:middle;').' background-color:#DDF; border:#DDF solid 2px;" class="Total_Row_Class_width Total_Row_Class_height"><div class="box_rotate Total_RowDiv_Class">';
	if(!empty($entity1Ids) && !empty($entity2Ids))
	{
		$entity1Ids = array_filter($entity1Ids);
		$entity2Ids = array_filter($entity2Ids);
		$htmlContent .= '<input type="hidden" value="'.$active_total.',endl,'.$count_total.',endl,'.$indlead_total.'" name="Cell_values_'.$online_HMCounter.'" id="Cell_values_'.$online_HMCounter.'" />';
		$htmlContent .= '<input type="hidden" value="'. trim(urlPath()) .'intermediary.php?e1=' . implode(',', $entity1Ids) . '&e2=' . implode(',', $entity2Ids). '" name="Link_value_'.$online_HMCounter.'" id="Link_value_'.$online_HMCounter.'" />';
		
		$htmlContent .= '<a id="Cell_Link_'.$online_HMCounter.'" href="'. trim(urlPath()) .'intermediary.php?e1=' . implode(',', $entity1Ids) . '&e2=' . implode(',', $entity2Ids). '&list=1&itype=0&sr=now&er=1 month&hm=' . $id . '" target="_blank" style="color:#000000;"><b><font id="Tot_ID_'.$online_HMCounter.'">'.$indlead_total.'</font></b></a>';
	}
	$htmlContent .= '</div></th>';
}

$htmlContent .= '</tr></thead>';
				
foreach($rows as $row => $rval)
{
	
	$cat = (isset($rowsCategoryName[$row]) && $rowsCategoryName[$row] != '')? $rowsCategoryName[$row]:'Undefined';
	
	if($RowsSpan[$row] > 0 && $cat != 'Undefined')
	{
		$online_HMCounter++;
		
		$htmlContent .='<tr style="vertical-align:middle; background-color: #A2FF97;"><td align="left" style="vertical-align:middle; background-color: #A2FF97; padding-left:4px;" colspan="'.((count($columns)+1)+(($total_fld)? 1:0)).'" id="Cell_ID_'.$online_HMCounter.'">';
		if($dtt)
		{
			$htmlContent .= '<input type="hidden" value="0,endl,0,endl,0" name="Cell_values_'.$online_HMCounter.'" id="Cell_values_'.$online_HMCounter.'" />';
			$htmlContent .= '<a id="Cell_Link_'.$online_HMCounter.'" href="'. trim(urlPath()) .'intermediary.php?e1=' . implode(',', $rowsCategoryEntityIds1[$cat]) . '&e2=' . $LastEntity2 . '&list=1&sr=now&er=1 month&hm=' . $id . '" target="_blank" class="ottlink" style="color:#000000;">';
			$htmlContent .= '<input type="hidden" value="'. trim(urlPath()) .'intermediary.php?e1=' . implode(',', $rowsCategoryEntityIds1[$cat]) . '&e2=' . $LastEntity2 . '" name="Link_value_'.$online_HMCounter.'" id="Link_value_'.$online_HMCounter.'" />';
		}
		if($cat != 'Undefined')
		{
			$htmlContent .='<b>'.$cat.'</b>';
		}
		if($dtt)
		$htmlContent .= '</a>';
		$htmlContent .='</td></tr>';
	}
	
	$htmlContent .= '<tr style="vertical-align:middle;">';
	
	$online_HMCounter++;
	//$rval = (isset($rowsDisplayName[$row]) && $rowsDisplayName[$row] != '')?$rowsDisplayName[$row]:$rval; //Commente as as planned to ignore display name in Entity1 only
	$rdesc = (isset($rowsDescription[$row]) && $rowsDescription[$row] != '')?$rowsDescription[$row]:null;
	$raltTitle = (isset($rdesc) && $rdesc != '')?' alt="'.$rdesc.'" title="'.$rdesc.'" ':null;
	
	$htmlContent .='<th class="Entity1_col break_words entity1ColWidthStyle" style="padding-left:4px; vertical-align:middle; '.(($Rotation_Flg == 1) ? 'width:'.$entity1ColWidth.'px; max-width:'.$entity1ColWidth.'px;':'').'" id="Cell_ID_'.$online_HMCounter.'" '.$raltTitle.'><div align="left" style="vertical-align:middle;">';
			
	$htmlContent .= '<input type="hidden" value="Entity1" name="Cell_Type_'.$online_HMCounter.'" id="Cell_Type_'.$online_HMCounter.'" />';
	$htmlContent .= '<input type="hidden" value="'.$row.'" name="Cell_RowNum_'.$online_HMCounter.'" id="Cell_RowNum_'.$online_HMCounter.'" />';
	$htmlContent .= '<input type="hidden" value="'.$col.'" name="Cell_ColNum_'.$online_HMCounter.'" id="Cell_ColNum_'.$online_HMCounter.'" />';
	
	if(isset($entity1Ids[$row]) && $entity1Ids[$row] != NULL && !empty($entity2Ids))
	{
		$htmlContent .= '<input type="hidden" value="'.$row_active_total[$row].',endl,'.$row_count_total[$row].',endl,'.$row_indlead_total[$row].'" name="Cell_values_'.$online_HMCounter.'" id="Cell_values_'.$online_HMCounter.'" />';
		$htmlContent .= '<input type="hidden" value="'. trim(urlPath()) .'intermediary.php?e1=' . $entity1Ids[$row] . '" name="Link_value_'.$online_HMCounter.'&list=1&itype=0&sr=now&er=1 month" id="Link_value_'.$online_HMCounter.'" />';
		
		$htmlContent .= '<a id="Cell_Link_'.$online_HMCounter.'" href="'. trim(urlPath()) .'intermediary.php?e1=' . $entity1Ids[$row] . '&e2=' . implode(',', $entity2Ids). '&list=1&hm=' . $id . '" target="_blank" class="ottlink" style="text-decoration:underline; color:#000000;">'.formatBrandName($rowsDisplayName[$row], 'product').$rowsCompanyName[$row].'</a>'.((trim($rowsTagName[$row]) != '') ? ' <font class="tag">['.$rowsTagName[$row].']</font>':'');
	}
	$htmlContent .= '</div></th>';
	
	foreach($columns as $col => $cval)
	{
		$online_HMCounter++;
		
		$Td_Style = '';
		if($data_matrix[$row][$col]['total'] != 0 || $data_matrix[$row][$col]['phase4_override'])
		{
			$Td_Style = 'background-color:#'.$data_matrix[$row][$col]['color_code'].'; border:#'.$data_matrix[$row][$col]['color_code'].' solid;';
		}
		else if($data_matrix[$row][$col]['preclinical'])
		{
			$Td_Style = 'background-color:#aed3dc; border:#aed3dc solid;';
		}
		else
		{
			if(isset($entity2Ids[$col]) && $entity2Ids[$col] != NULL && isset($entity1Ids[$row]) && $entity1Ids[$row] != NULL)
			{
				$Td_Style = 'background-color:#e6e6e6; border:#e6e6e6 solid;';
				$data_matrix[$row][$col]['color_code'] = 'e6e6e6';
				$data_matrix[$row][$col]['div_start_style'] = 'background-color:#e6e6e6;';
			}
			else
			$Td_Style = 'background-color:#ddf; border:#ddf solid;';
		}
		
		$htmlContent .= '<td class="tooltip" valign="middle" id="Cell_ID_'.$online_HMCounter.'" style="'. $Td_Style .' padding:1px; min-width:'.$Width_matrix[$col]['width'].'px;  max-width:'.$Width_matrix[$col]['width'].'px; vertical-align:middle; text-align:center; height:100%;" align="center" onmouseover="display_tooltip(\'on\','.$online_HMCounter.');" onmouseout="display_tooltip(\'off\','.$online_HMCounter.');">';
	
		$htmlContent .= '<input type="hidden" value="HM_Cell" name="Cell_Type_'.$online_HMCounter.'" id="Cell_Type_'.$online_HMCounter.'" />';
		$htmlContent .= '<input type="hidden" value="'.$row.'" name="Cell_RowNum_'.$online_HMCounter.'" id="Cell_RowNum_'.$online_HMCounter.'" />';
		$htmlContent .= '<input type="hidden" value="'.$col.'" name="Cell_ColNum_'.$online_HMCounter.'" id="Cell_ColNum_'.$online_HMCounter.'" />';
	
		if(isset($entity2Ids[$col]) && $entity2Ids[$col] != NULL && isset($entity1Ids[$row]) && $entity1Ids[$row] != NULL)
		{
			
			$htmlContent .= '<div id="Div_ID_'.$online_HMCounter.'" style="'.$data_matrix[$row][$col]['div_start_style'].' width:100%; height:100%; max-height:inherit; _height:100%;  vertical-align:middle; float:none; display:table;">';
			
			$htmlContent .= '<input type="hidden" value="'.$data_matrix[$row][$col]['active'].',endl,'.$data_matrix[$row][$col]['total'].',endl,'.$data_matrix[$row][$col]['indlead'].',endl,'.$data_matrix[$row][$col]['active_prev'].',endl,'.$data_matrix[$row][$col]['total_prev'].',endl,'.$data_matrix[$row][$col]['indlead_prev'].',endl,'.date('m/d/Y H:i:s', strtotime($data_matrix[$row][$col]['last_update'])).',endl,'.date('F d, Y', strtotime($data_matrix[$row][$col]['last_update'])).',endl,'.date('m/d/Y H:i:s', strtotime($data_matrix[$row][$col]['count_lastchanged'])).',endl,'.date('F d, Y', strtotime($data_matrix[$row][$col]['count_lastchanged'])).',endl,'.date('m/d/Y H:i:s', strtotime($data_matrix[$row][$col]['bomb_lastchanged'])).',endl,'.date('F d, Y', strtotime($data_matrix[$row][$col]['bomb_lastchanged'])).',endl,'.date('m/d/Y H:i:s', strtotime($data_matrix[$row][$col]['filing_lastchanged'])).',endl,'.date('F d, Y', strtotime($data_matrix[$row][$col]['filing_lastchanged'])).',endl,'.$data_matrix[$row][$col]['color_code'].',endl,'.$data_matrix[$row][$col]['bomb']['value'].',endl,'.date('m/d/Y H:i:s', strtotime($data_matrix[$row][$col]['phase_explain_lastchanged'])).',endl,'.date('F d, Y', strtotime($data_matrix[$row][$col]['phase_explain_lastchanged'])).',endl,'.date('m/d/Y H:i:s', strtotime($data_matrix[$row][$col]['phase4_override_lastchanged'])).',endl,'.date('F d, Y', strtotime($data_matrix[$row][$col]['phase4_override_lastchanged'])).',endl,'.date('m/d/Y H:i:s', strtotime($data_matrix[$row][$col]['highest_phase_lastchanged'])).',endl,'.date('F d, Y', strtotime($data_matrix[$row][$col]['highest_phase_lastchanged'])).',endl,\''.$data_matrix[$row][$col]['highest_phase_prev'].'\'" name="Cell_values_'.$online_HMCounter.'" id="Cell_values_'.$online_HMCounter.'" />';
			
			$htmlContent .= '<input type="hidden" value="'. trim(urlPath()) .'intermediary.php?e1=' . $entity1Ids[$row] . '&e2=' . $entity2Ids[$col]. '" name="Link_value_'.$online_HMCounter.'" id="Link_value_'.$online_HMCounter.'" />';
			$htmlContent .= '<input type="hidden" value="' . $entity1Ids[$row] . '" name="Entity1_value_'.$online_HMCounter.'" id="Entity1_value_'.$online_HMCounter.'" />';
			$htmlContent .= '<input type="hidden" value="' . $entity2Ids[$col]. '" name="Entity2_value_'.$online_HMCounter.'" id="Entity2_value_'.$online_HMCounter.'" />';
			if($data_matrix[$row][$col]['total'] == 0)
			$htmlContent .= '<input type="hidden" value="1" name="TotalZero_Flg_'.$online_HMCounter.'" id="TotalZero_Flg_'.$online_HMCounter.'" />';
				
			$htmlContent .= '<a onclick="INC_ViewCount(' . trim($entity1Ids[$row]) . ',' . trim($entity2Ids[$col]) . ',' . $online_HMCounter .')" style="color:#000000; '.$data_matrix[$row][$col]['count_start_style'].' vertical-align:middle; padding-top:0px; padding-bottom:0px; line-height:13px; '.(($data_matrix[$row][$col]['total'] != 0) ? 'text-decoration:underline;' : 'text-decoration:none;').'" id="Cell_Link_'.$online_HMCounter.'" href="'. trim(urlPath()) .'intermediary.php?e1=' . $entity1Ids[$row] . '&e2=' . $entity2Ids[$col]. '&list=1&itype=0&sr=now&er=1 month&hm=' . $id . '" target="_blank" title="'. $title .'"><b><font id="Font_ID_'.$online_HMCounter.'" style="color:#000000;">'. (($data_matrix[$row][$col]['total'] != 0) ? $data_matrix[$row][$col]['indlead'] : '&nbsp;') .'</font></b></a>';
					
			if($data_matrix[$row][$col]['bomb']['src'] != 'new_square.png') //When bomb has square dont include it in pdf as size is big and no use
			$htmlContent .= '<img id="Cell_Bomb_'.$online_HMCounter.'" title="'.$data_matrix[$row][$col]['bomb']['title'].'" src="'. trim(urlPath()) .'images/'.$data_matrix[$row][$col]['bomb']['src'].'"  style="'.$data_matrix[$row][$col]['bomb']['style'].' vertical-align:middle; margin-left:1px;" />';				
			
			
			
			if($data_matrix[$row][$col]['filing'] != NULL && $data_matrix[$row][$col]['filing'] != '')
			$htmlContent .= '<img id="Cell_Filing_'.$online_HMCounter.'" src="images/new_file.png" title="Filing Details" style="width:17px; height:17px; vertical-align:middle; cursor:pointer; margin-left:1px;" alt="Filing" />';
				
			
			$htmlContent .= '</div>'; ///Div complete to avoid panel problem
					
			//Tool Tip Starts Here
			$htmlContent .= '<span id="ToolTip_ID_'.$online_HMCounter.'" class="classic" style="text-align:left;">'
							.'<input type="hidden" value="0" name="ToolTip_Visible_'.$online_HMCounter.'" id="ToolTip_Visible_'.$online_HMCounter.'" />';	
				
			if($data_matrix[$row][$col]['bomb']['src'] != 'new_square.png')
			{
				$htmlContent .= '<font class="Data_values" id="Bomb_Img_'.$online_HMCounter.'">'.$data_matrix[$row][$col]['bomb']['alt'].' </font>'.(($data_matrix[$row][$col]['bomb_explain'] != NULL && $data_matrix[$row][$col]['bomb_explain'] != '')? '<font class="Status_Label_Headers">: </font>'. $data_matrix[$row][$col]['bomb_explain'] .'<input type="hidden" value="1" name="Bomb_Presence_'.$online_HMCounter.'" id="Bomb_Presence_'.$online_HMCounter.'" />':'' ).'</br>';
			}
			
			if($data_matrix[$row][$col]['phase_explain'] != NULL && $data_matrix[$row][$col]['phase_explain'] != '')
			{
				$htmlContent .= '<font class="Status_Label_Headers" id="Phaseexp_Img_'.$online_HMCounter.'">Phase explanation </font><font class="Status_Label_Headers">: </font>'. $data_matrix[$row][$col]['phase_explain'] .'</br>';
			}
			
			if($data_matrix[$row][$col]['filing'] != NULL && $data_matrix[$row][$col]['filing'] != '')
			{
				$htmlContent .= '<font class="Status_Label_Headers" id="Filing_Img_'.$online_HMCounter.'">Filing </font><font class="Status_Label_Headers">: </font>'. $data_matrix[$row][$col]['filing'] .'</br>';
			}
			
			
			if($data_matrix[$row][$col]['highest_phase_prev'] != NULL && $data_matrix[$row][$col]['highest_phase_prev'] != '')
			$htmlContent .= '<font id="Highest_Phase_'.$online_HMCounter.'"><font class="Status_Label_Headers">Highest phase updated</font><font class="Status_Label_Headers"> from: </font> <font class="Data_values">Phase '.$data_matrix[$row][$col]['highest_phase_prev'].'</font></br></font>';
							
			
			$Status_Total_Flg=0;
			$Status_Total ='<font id="Status_Total_List_'.$online_HMCounter.'" style="display:none;"><font class="Status_Label_Headers Status_Changes_Style">Status changes to:<br/></font><ul class="Status_ULStyle">';
			
			$allTrialsStatusArray = array('not_yet_recruiting', 'recruiting', 'enrolling_by_invitation', 'active_not_recruiting', 'completed', 'suspended', 'terminated', 'withdrawn', 'available', 'no_longer_available', 'approved_for_marketing', 'no_longer_recruiting', 'withheld', 'temporarily_not_available', 'ongoing', 'not_authorized', 'prohibited');
			
			foreach($allTrialsStatusArray as $currentStatus)
			{
				if($data_matrix[$row][$col][$currentStatus] > 0)
				{
					$Status_Total_Flg=1;
					$Status_Total .= '<li><font class="Status_Label_Style">'.ucfirst(str_replace('_',' ',$currentStatus)).'</font><font class="Status_Label_Style">: </font><font class="Status_Label_values">'. $data_matrix[$row][$col][$currentStatus] .'</font></li>';
				}
			}			
			
			
			if($Status_Total_Flg==1)
			$htmlContent .= $Status_Total.'</ul></font>';
			
			$Status_Indlead_Flg=0;
			$Status_Indlead ='<font id="Status_Indlead_List_'.$online_HMCounter.'" style="display:inline;"><font class="Status_Label_Headers Status_Changes_Style">Status changes to:</font><ul class="Status_ULStyle">';
			
			$activeIndleadStatusArray = array('not_yet_recruiting_active_indlead', 'recruiting_active_indlead', 'enrolling_by_invitation_active_indlead', 'active_not_recruiting_active_indlead', 'completed_active_indlead', 'suspended_active_indlead', 'terminated_active_indlead', 'withdrawn_active_indlead', 'available_active_indlead', 'no_longer_available_active_indlead', 'approved_for_marketing_active_indlead', 'no_longer_recruiting_active_indlead', 'withheld_active_indlead', 'temporarily_not_available_active_indlead', 'ongoing_active_indlead', 'not_authorized_active_indlead', 'prohibited_active_indlead');
			
			foreach($activeIndleadStatusArray as $currentStatus)
			{
				if($data_matrix[$row][$col][$currentStatus] > 0)
				{
					$Status_Indlead_Flg=1;
					$Status_Indlead .= '<li><font class="Status_Label_Style">'.ucfirst(str_replace('_',' ',str_replace('_active_indlead','',$currentStatus))).'</font><font class="Status_Label_Style">: </font><font class="Status_Label_values">'. $data_matrix[$row][$col][$currentStatus] .'</font></li>';
				}
			}
			
			if($Status_Indlead_Flg==1)
			$htmlContent .= $Status_Indlead.'</ul></font>';
			
			$Status_Active_Flg=0;
			$Status_Active ='<font id="Status_Active_List_'.$online_HMCounter.'" style="display:none;"><font class="Status_Label_Headers Status_Changes_Style">Status changes to:<br/></font><ul class="Status_ULStyle">';
			
			$activeStatusArray = array('not_yet_recruiting_active', 'recruiting_active', 'enrolling_by_invitation_active', 'active_not_recruiting_active', 'completed_active', 'suspended_active', 'terminated_active', 'withdrawn_active', 'available_active', 'no_longer_available_active', 'approved_for_marketing_active', 'no_longer_recruiting_active', 'withheld_active', 'temporarily_not_available_active', 'ongoing_active', 'not_authorized_active', 'prohibited_active');
			
			foreach($activeStatusArray as $currentStatus)
			{
				if($data_matrix[$row][$col][$currentStatus] > 0)
				{
					$Status_Active_Flg=1;
					$Status_Active .= '<li><font class="Status_Label_Style">'.ucfirst(str_replace('_',' ',str_replace('_active','',$currentStatus))).'</font><font class="Status_Label_Style">: </font><font class="Status_Label_values">'. $data_matrix[$row][$col][$currentStatus] .'</font></li>';
				}
			}
						
			if($Status_Active_Flg==1)
			$htmlContent .= $Status_Active.'</ul></font>';
			
			
			$htmlContent .= '<font id="ViewCount_'.$online_HMCounter.'">'.(($data_matrix[$row][$col]['viewcount'] > 0) ? '<font class="Status_Label_Headers">Number of views: </font><font class="Data_values">'.$data_matrix[$row][$col]['viewcount'].'</font><input type="hidden" value="'.$data_matrix[$row][$col]['viewcount'].'" id="ViewCount_value_'.$online_HMCounter.'" />':'<input type="hidden" value="'.$data_matrix[$row][$col]['viewcount'].'" id="ViewCount_value_'.$online_HMCounter.'" />' ).'</font>';
							
			$htmlContent .='</span>';	//Tool Tip Ends Here
		}
		else
		{
			$htmlContent .= '<div id="Div_ID_'.$online_HMCounter.'" style="width:100%; height:100%; max-height:inherit; _height:100%;  vertical-align:middle; float:none; display:table;">&nbsp;</div>';
		}
		
		$htmlContent .= '</td>';
	}//Columns For loop Ends
	
	//if total checkbox is selected
	if($total_fld)
	{
		$htmlContent .= '<th class="Total_Row_Class_width break_words" style="background-color:#DDF; border:#DDF solid 2px; min-width:'.$Total_Col_width.'px;  max-width:'.$Total_Col_width.'px; vertical-align:middle; text-align:center; height:100%;" align="center"><div class="Total_RowDiv_Class" style="float:none;">&nbsp;</div></th>';
	}
		
	$htmlContent .= '</tr>';
} //Main Data For loop ends
		
$htmlContent .= '</table><input type="hidden" value="'.$online_HMCounter.'" name="Last_HM" id="Last_HM" /><input type="hidden" value="'.$Max_ViewCount.'" id="Max_ViewCount_value" /></div><br /><br/>';

if(($footnotes != NULL && trim($footnotes) != '') || ($description != NULL && trim($description) != ''))
{
	$htmlContent .='<div align="center"><table align="center" style="vertical-align:middle; padding:10px; background-color:#FFFFFF;">'
				. '<tr>'
				. '<td width="380px" align="left" style="vertical-align:top;  background-color:#DDF;"><b>Footnotes: </b>'. (($footnotes != NULL && trim($footnotes) != '') ? '<br/><div style="padding-left:10px;"><br/>'. $footnotes .'</div>' : '' ).'</td>'
				. '<td width="380px" align="left" style="vertical-align:top;  background-color:#DDF;"><b>Description: </b>'. (($description != NULL && trim($description) != '') ? '<br/><div style="padding-left:10px;"><br/>'. $description .'</div>' : '' ).'</td></tr>'
				. '</table></div>';
}
			
print $htmlContent;
?>
<div id="slideout">
    <img src="images/help.png" alt="Help" />
    <div class="slideout_inner">
        <table bgcolor="#FFFFFF" cellpadding="0" cellspacing="0" class="table-slide">
        <tr><td width="15%"><img title="Bomb" src="images/new_lbomb.png"  style="width:17px; height:17px; cursor:pointer;" /></td><td>Discontinued</td></tr>
        <tr><td><img title="Filing" src="images/new_file.png"  style="width:17px; height:17px; cursor:pointer;" /></td><td>Filing details</td></tr>
        <tr><td><img title="Red Border" src="images/outline.png"  style="width:20px; height:15px; cursor:pointer;" /></td><td>Red border (record updated)</td></tr>
        <tr><td colspan="2" style="padding-right: 1px;">
         <div style="float:left;padding-top:3px;">Phase&nbsp;</div>
         <div class="gray">N/A</div>
         <div class="blue">0</div>
         <div class="green">1</div>
         <div class="yellow">2</div>
         <div class="orange">3</div>
         <div class="red">4</div>
         </td></tr>
        </table>
    </div>
</div>
<?
if($db->loggedIn() && (strpos($_SERVER['HTTP_REFERER'], 'larvolinsight') == FALSE))
{
	$cpageURL = 'http://';
	$cpageURL .= $_SERVER["SERVER_NAME"].urldecode($_SERVER["REQUEST_URI"]);
	echo '<a href="li/larvolinsight.php?url='. $cpageURL .'"><span style="color:red;font-weight:bold;margin-left:10px;">LI view</span></a><br>';
}
?>
</body>
</html>
<script type="text/javascript">
/* Now browser specific width is set using JS browser detection as browser detection via php does not seems to work in LI IFRAME*/
$.browser.ie = /msie/.test(navigator.userAgent.toLowerCase());
<?php
if($Rotation_Flg == 1)
{

	foreach($columns as $col => $val)
	{
		$width = $Width_matrix[$col]['width'] - ($ColsEntity2Lines[$col]*($Line_Height));
		
		print "if(!$.browser.ie) { $('.Entity2_RowDiv_Class_".$col."').css('margin-left','".((($Line_Height)*$ColsEntity2Lines[$col]) + ($width/1.8))."px'); } else { $('.Entity2_RowDiv_Class_".$col."').css('padding-right','".(($width/1.8))."px'); $('.Entity2_RowDiv_Class_".$col."').css('margin-left','1px'); }";

		if($ColumnsSpan[$col] > 0)
		{
			if($CatEntity2Rotation[$col])
			{
				$width = $CatEntity2Colwidth[$col] - ($cols_Cat_Lines[$col]*($Line_Height));
				
				print "if(!$.browser.ie) { $('.Cat_RowDiv_Class_".$col."').css('margin-left','".((($Line_Height)*$cols_Cat_Lines[$col]) + ($width/1.8))."px'); } else { $('.Cat_RowDiv_Class_".$col."').css('padding-right','".(($width/1.8))."px'); }";
			}
		}
	}
	
	$width = $Total_Col_width - $Line_Height;
	print "if(!$.browser.ie) { $('.Total_RowDiv_Class').css('margin-left','".($Line_Height + ($width/2))."px'); } else { $('.Total_RowDiv_Class').css('padding-right','".(($width/2))."px'); }";
	
	print "if(!$.browser.ie) { $('.box_rotate').css('margin-bottom','4px'); } else { $('.box_rotate').css('padding-top','4px'); }";
}
?>
</script>

<script language="javascript" type="text/javascript">
change_view();
var winWidth = $(window).width();
var docWidth = $(document).width();
//adjust for too small resolutions
if (docWidth > winWidth)
{
//alert("Horizontal Scrollbar Present");
$('.Entity1_col').css('max-width','400px');
$('.Entity1_col').css('min-width','400px');
$('.Entity1_col').css('white-space','wrap');
$('.Entity1_col').css('word-wrap','break-word');
$('.Entity1_col').css('_width','400px');
}
// Default size
document.getElementById("startrange").style.width = "30px";
document.getElementById("endrange").style.width = "70px";
</script>