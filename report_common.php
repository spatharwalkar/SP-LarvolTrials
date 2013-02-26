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
			
			$slickgrid_data = slickgrid_data();
			$out =  '<script src="scripts/slickgrid/lib/firebugx.js"></script>'
					.'<script src="scripts/slickgrid/lib/jquery.event.drag-2.0.min.js"></script>'
					.'<script src="scripts/slickgrid/slick.core.js"></script>'
					.'<script src="scripts/slickgrid/slick.formatters.js"></script>'
					.'<script src="scripts/slickgrid/slick.editors.js"></script>'
					.'<script src="scripts/slickgrid/plugins/slick.cellrangedecorator.js"></script>'
					.'<script src="scripts/slickgrid/plugins/slick.cellrangeselector.js"></script>'
					.'<script src="scripts/slickgrid/plugins/slick.cellselectionmodel.js"></script>'
					.'<script src="scripts/slickgrid/slick.grid.js"></script>'
					.'<script src="scripts/slickgrid/slick.groupitemmetadataprovider.js"></script>'
					.'<script src="scripts/slickgrid/slick.dataview.js"></script>'
					.'<link rel="stylesheet" href="scripts/slickgrid/slick.grid.css" type="text/css"/>'
					.'<link rel="stylesheet" href="scripts/slickgrid/css/smoothness/jquery-ui-1.8.16.custom.css" type="text/css"/>'
					.'<style>
						#HMReports_Tree{
							font-family: arial;
    						font-size: 8pt;
    						border: 1px solid gray;
						}
					
					    .cell-title {
					      font-weight: bold;	
					    }
					
					    .cell-effort-driven {
					      text-align: center;
					    }
					
					    .toggle {
					      height: 12px;
					      width: 9px;
					      display: inline-block;
					    }
					
					    .toggle.expand {
					      background: url(scripts/slickgrid/images/expand.gif) no-repeat center center;
					    }
					
					    .toggle.collapse {
					      background: url(scripts/slickgrid/images/collapse.gif) no-repeat center center;
					    }
					    
					    .grid-header {
						  border: 1px solid gray;
						  border-bottom: 0;
						  border-top: 0;
						  background: url("scripts/slickgrid/images/header-bg.gif") repeat-x center top;
						  color: black;
						  height: 32px;
						  line-height: 24px;
						}
						
						.grid-header label {
						  display: inline-block;
						  font-weight: bold;
						  margin: auto auto auto 6px;
						  background:none;
						  color:#04408C;
						  font-family:Verdana,Geneva,sans-serif;
						}
						
						.grid-header .ui-icon {
						  margin: 4px 4px auto 6px;
						  background-color: transparent;
						  border-color: transparent;
						}
						
						.grid-header .ui-icon.ui-state-hover {
						  background-color: white;
						}
						
						.grid-header #txtSearch {
						  margin: 0 4px 0 4px;
						  padding: 2px 2px;
						  -moz-border-radius: 2px;
						  -webkit-border-radius: 2px;
						  border: 1px solid silver;
						}
					.slick-group-toggle.expanded {
					    background: url("scripts/slickgrid/images/collapse.gif") no-repeat scroll center center transparent;
					}	
					.slick-group-toggle.collapsed {
					    background: url("scripts/slickgrid/images/expand.gif") no-repeat scroll center center transparent;
					}
					.slick-group-toggle {
					    height: 9px;
					    margin-right: 5px;
					    width: 9px;
					}
					.slick-group-toggle {
					    display: inline-block;
					}
					  </style>'
					.'<script src="scripts/slickgrid/slick_grid.js"></script>'
					.'<div style="width:570px;padding:10px;">
				    <div style="width:100%" class="grid-header">
				      <form style="float:left;margin:0px;padding:0px;" action="master_heatmap.php" method="post"><label>Master Heatmap Reports</label> &nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" font-weight:bold"="" border-color:#009966;="" ;="" style="float:none; background-color:#009933; color:#FFFFFF" value="+ New" name="makenew"></form>
				      <span onclick="toggleWholeGrid()" title="Toggle Grid" class="grid-showhide ui-icon ui-icon-carat-1-s ui-corner-all" style="float:right"></span>
				      <span onclick="toggleFilterRow()" title="Toggle search panel" class="ui-icon ui-icon-search ui-state-default ui-corner-all" style="float:right"></span>
				    </div>'
				    .'<div id="inlineFilterPanel" style="display:none;background:#dddddd;padding:3px;color:black;">
					  Show reports with name including <input type="text" id="txtSearch2">
					</div>'
					.'<div id="HMReports_Tree" style="float:left;padding:0px; width:570px;height:300px;"></div>'
					.'</div>';
					?>			
					 
					<script type="text/javascript">
					$(function () {
						
						  var indent = 0;
						  var parents = [];
						  var parent_order = '';	

						  // prepare the data
						  <?php  for ($i = 0; $i < count($slickgrid_data); $i++){?>
						      var d = (data[<?php echo $i;?>] = {});
						      indent = '<?php echo $slickgrid_data[$i]['indent']?>';
						      if (indent == 0) {
						        parent = null;
						        parent_order = '<?php echo $i;?>';
						      }
						      if(indent == 1 && parent_order != ''){
						    	  parent = parent_order;
							  }
						      d["id"] = "id_" + <?php echo $i;?>;
						      d["indent"] = indent;
						      d["parent"] = parent;
						      d["expanded"] = '<?php echo $slickgrid_data[$i]['expanded']?>';;
						      d["mhmcategory"] = '<?php echo $slickgrid_data[$i]['mhmcategory']?>';
						      d["owner"] = '<?php echo $slickgrid_data[$i]['owner'] ?>';
						      d["rows"] = '<?php echo $slickgrid_data[$i]['rows'] ?>';
						      d["cols"] = '<?php echo $slickgrid_data[$i]['cols'] ?>';
						      <?php } ?>

						  var groupItemMetadataProvider = new Slick.Data.GroupItemMetadataProvider();
						  // initialize the model
						  dataView = new Slick.Data.DataView({ inlineFilters: true, groupItemMetadataProvider: groupItemMetadataProvider });
						  dataView.beginUpdate();
						  dataView.setItems(data);
						  dataView.setFilter(myFilter);
						  dataView.endUpdate();

						  // initialize the grid
						  grid = new Slick.Grid("#HMReports_Tree", dataView, columns, options);
						  grid.registerPlugin(groupItemMetadataProvider);
						  grid.onCellChange.subscribe(function (e, args) {
						    dataView.updateItem(args.item.id, args.item);
						  });
						  grid.onClick.subscribe(function (e, args) {
						    if ($(e.target).hasClass("toggle")) {
						      var item = dataView.getItem(args.row);
							// save parent state of collapse in cookie 
							tree_grid_cookie(item.mhmcategory);
						      if (item) {
						        if (!item._collapsed) {
						          //tree_grid_cookie(item.mhmcategory);
						          item._collapsed = true;
						        } else {
						          item._collapsed = false;
						        }

						        dataView.updateItem(item.id, item);
						      }
						      e.stopImmediatePropagation();
						    }
						  });

						    grid.onSort.subscribe(function (e, args) {
						        sortdir = args.sortAsc ? 1 : -1;
						        sortcol = args.sortCol.field;

						        if ($.browser.msie && $.browser.version <= 8) {
						          // using temporary Object.prototype.toString override
						          // use numeric sort of % and lexicographic for everything else
						          dataView.fastSort(sortcol, args.sortAsc);
						        }
						        else {
						          // using native sort with comparer
						          // preferred method but can be very slow in IE with huge datasets
						          dataView.sort(comparer, args.sortAsc);
						        }
						   });

						  // wire up model events to drive the grid
						  dataView.onRowCountChanged.subscribe(function (e, args) {
						    grid.updateRowCount();
						  });

						  dataView.onRowsChanged.subscribe(function (e, args) {
						    grid.invalidateRows(args.rows);
						    grid.render();
						  });
						  var h_runfilters = null;
						  // wire up the search textbox to apply the filter to the model
						  $("#txtSearch2").keyup(function (e) {
						    Slick.GlobalEditorLock.cancelCurrentEdit();

						    // clear on Esc
						    if (e.which == 27) {
						      this.value = "";
						    }

						    searchString = this.value;
						    dataView.refresh();
						  })

						  // manage search and arrow icon hover
							$(".grid-header .ui-icon").addClass("ui-state-default ui-corner-all").mouseover(function (e) {
					          $(e.target).addClass("ui-state-hover")
					        }).mouseout(function (e) {
					          $(e.target).removeClass("ui-state-hover")
					        });
        
						// move the filter panel defined in a hidden div into grid top panel
						  $("#inlineFilterPanel").appendTo(grid.getTopPanel()).show();
						// save parent collapse state from cookie
						  collapseAllGroups();
						  // manage collapsible grid arrow direction
							$(".grid-showhide").click(function(){
								if($(".grid-showhide").hasClass("ui-icon-carat-1-s")){
									$(".grid-showhide").removeClass("ui-icon-carat-1-s").addClass("ui-icon-carat-1-n");
								}else{
									$(".grid-showhide").removeClass("ui-icon-carat-1-n").addClass("ui-icon-carat-1-s");
								}
							});
						});

					</script>
	<?php
					

			break;
	}
	return $out;
}

