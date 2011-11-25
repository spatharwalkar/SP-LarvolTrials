<?php
/**
* @name reportListCommon()
* @tutorial Common function to generate the report list for rpt_heaptmap,rpt_update,rpt_trial_tracker.
* @return 	string html
* @author Jithu Thomas
**/     
function reportListCommon($reportTable,$disperr=null)
{
	global $db;
	switch($reportTable)
	{
		case 'rpt_heatmap':
			
			$out = '<div style="display:block;float:left;"><form method="post" action="report_heatmap.php" class="lisep">'
					. '<input type="submit" name="makenew" value="Create new" style="float:none;" /></form><br clear="all"/>'
					. '<form name="reportlist" method="post" action="report_heatmap.php" class="lisep" onsubmit="return chkbox(this);">'
					. '<fieldset><legend>Select Report</legend>';
			$out .= '<div class="tar">Del</div><ul>';
			$query = 'SELECT id,name,user,category FROM rpt_heatmap WHERE user IS NULL OR user=' . $db->user->id . ' ORDER BY user';
			$res = mysql_query($query) or die('Bad SQL query retrieving report names');
			$res1 = mysql_query($query) or die('Bad SQL query retrieving report names');
			$categoryArr  = array('');
			$outArr = array();
			while($row = mysql_fetch_array($res1))
			{
				if($row['category'])
				$categoryArr[$row['category']] = $row['category'];
				$outArr[] = $row;
			}
			sort($categoryArr);
			
			foreach($categoryArr as $category)
			{
				//		$out .= '<li>'.ucwords(strtolower($category)).'<ul>';
				//		keep the category as it is, without any change in letter case
				$out .= '<li>'.$category.'<ul>';
				foreach($outArr as $row)
				{
					$ru = $row['user'];
					if($row['category']== $category)
					{
						$out .= '<li' . ($ru === NULL ? ' class="global"' : '') . '><a href="report_heatmap.php?id=' . $row['id'] . '">'
								. htmlspecialchars(strlen($row['name'])>0?$row['name']:('(report '.$row['id'].')')) . '</a>';
						if($ru == $db->user->id || ($ru === NULL && $db->user->userlevel != 'user'))
						{
							$out .= ' &nbsp; &nbsp; &nbsp; <label class="lbldel"><input type="checkbox" class="delrep" name="delrep[' . $row['id']. ']" title="Delete"/></label>';
						}
						$out .= '</li>';				
					}
				}
				$out .='</ul></li>';
			}
			$out .= '</ul>';
			$out .='<div class="tar"><input type="submit" value="Delete" title="Delete"/></div></fieldset></form></div>';
			break;
			
		case 'rpt_trial_tracker':
			
			$out = '<div style="display:block;float:left;"><form method="post" action="report_trial_tracker.php" class="lisep">'
					. '<input type="submit" name="makenew" value="Create new" style="float:none;" /></form><br clear="all"/>'
					. '<form name="reportlist" method="post" action="report_trial_tracker.php" class="lisep" onsubmit="return chkbox();">'
					. '<fieldset><legend>Select Report</legend>';
			$out .= '<div class="tar">Del</div><ul>';
			$query = 'SELECT id,name,user FROM rpt_trial_tracker WHERE user IS NULL OR user=' . $db->user->id . ' ORDER BY user';
			$res = mysql_query($query) or die('Bad SQL query retrieving report names');
			while($row = mysql_fetch_array($res))
			{
				$ru = $row['user'];
				$out .= '<li' . ($ru === NULL ? ' class="global"' : '') . '><a href="report_trial_tracker.php?id=' . $row['id'] . '">'
						. htmlspecialchars(strlen($row['name'])>0?$row['name']:('(report '.$row['id'].')')) . '</a>';
				if($ru == $db->user->id || ($ru === NULL && $db->user->userlevel != 'user'))
				{
					$out .= ' &nbsp; &nbsp; &nbsp; <label class="lbldel"><input type="checkbox" class="delrep" name="delrep[' . $row['id']
						. ']" title="Delete"/></label>';
				}
				$out .= '</li>';
			}
			$out .= '</ul>';
			$out .='<div class="tar"><input type="submit" value="Delete" title="Delete"/></div></fieldset></form></div>';
			break;
			
		case 'rpt_update':
			
			global $activeUpdated;
			$out = '<div style="display:block;float:left;"><form method="post" action="report_update.php" class="lisep">'
					. '<input type="submit" name="makenew" value="Create new" style="float:none;" /></form><br clear="all"/>'
					. '<form name="reportlist" method="post" action="report_update.php" class="lisep">'
					. '<fieldset><legend>Select UpdateReport</legend>';
			mysql_query('BEGIN') or die("Couldn't begin SQL transaction");
			$query = 'SELECT id,name,user FROM rpt_update WHERE user IS NULL or user=' . $db->user->id . ' ORDER BY user';
			$res = mysql_query($query) or die('Bad SQL query retrieving updatereport names');
			$out .= '<table width="100%" class="items"><tr><th>Load</th><th>Del</th></tr>';
			while($row = mysql_fetch_array($res))
			{
				$out .= '<tr><td><ul class="tablelist"><li class="' . ($row['user'] === NULL ? 'global' : '')
						. '"><a href="report_update.php?id=' . $row['id'] . '">'
						. htmlspecialchars(strlen($row['name'])>0?$row['name']:('(report '.$row['id'].')'))
						. '</a></li></ul></td><th>';
				if($row['user'] !== NULL || ($row['user'] == NULL && $db->user->userlevel != 'user'))
				{
					$out .= '<label class="lbldelc"><input type="checkbox" class="delrep" name="delrep[' . $row['id']
							. ']" title="Delete" /></label>';
				}
				$out .= '</th></tr>';
			}
			$out .= '<tr><th>&nbsp;</th><th><div class="tar"><input type="submit" value="Delete" title="Delete" onclick="return chkbox();"/></div></th></tr>';
			mysql_query('COMMIT') or die("Couldn't commit SQL transaction");
			$out .= '</table><br />';
			if(strlen($disperr)) $out .= '<br clear="all"/><span class="error">' . $disperr . '</span>';
			if(strlen($activeUpdated)) $out .= '<br clear="all"/><span class="info">Selections updated!</span>';
			$out .= '</fieldset></form></div>';
			break;
			
		case 'rpt_master_heatmap':
			$out = '<div style="display:block;float:left;"><form method="post" action="master_heatmap.php" class="lisep">'
					. '<input type="submit" name="makenew" value="Create new" style="float:none;" /></form><br clear="all"/>'
					. '<form name="reportlist" method="post" action="master_heatmap.php" class="lisep" onsubmit="return chkbox(this);">'
					. '<fieldset><legend>Select Report</legend>';
			$out .= '<div class="tar">Del</div><ul>';
			$query = 'SELECT id,name,user,category FROM `rpt_masterhm` WHERE user IS NULL OR user=' . $db->user->id . ' ORDER BY user';
			$res = mysql_query($query) or die('Bad SQL query retrieving master heatmap report names');
			$categoryArr  = array('');
			$outArr = array();
			while($row = mysql_fetch_array($res))
			{
				if($row['category'])
				$categoryArr[$row['category']] = $row['category'];
				$outArr[] = $row;
			}
			sort($categoryArr);
			
			foreach($categoryArr as $category)
			{
				$out .= '<li>'.$category.'<ul>';
				foreach($outArr as $row)
				{
					$ru = $row['user'];
					if($row['category']== $category)
					{
						$out .= '<li' . ($ru === NULL ? ' class="global"' : '') . '><a href="master_heatmap.php?id=' . $row['id'] . '">'
								. htmlspecialchars(strlen($row['name'])>0?$row['name']:('(report '.$row['id'].')')) . '</a>';
						if($ru == $db->user->id || ($ru === NULL && $db->user->userlevel != 'user'))
						{
							$out .= ' &nbsp; &nbsp; &nbsp; <label class="lbldel"><input type="checkbox" class="delrep" name="delrep[' 
							. $row['id']. ']" title="Delete"/></label>';
						}
						$out .= '</li>';				
					}
				}
				$out .='</ul></li>';
			}
			$out .= '</ul>';
			$out .='<div class="tar"><input type="submit" value="Delete" title="Delete"/></div></fieldset></form></div>';
			break;
	}
	return $out;
}