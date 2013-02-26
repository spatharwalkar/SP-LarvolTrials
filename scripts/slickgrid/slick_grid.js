var TaskNameFormatter = function (row, cell, value, columnDef, dataContext) {
// value = value.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;");
  var spacer = "<span style='display:inline-block;height:1px;width:" + (15 * dataContext["indent"]) + "px'></span>";
  var idx = dataView.getIdxById(dataContext.id);
  if (data[idx + 1] && data[idx + 1].indent > data[idx].indent) {
    if (dataContext._collapsed) {
      return spacer + " <span class='toggle expand'>&nbsp;&nbsp;&nbsp;&nbsp;"+ value +"</span>";
    } else {
      return spacer + " <span class='toggle collapse'>&nbsp;&nbsp;&nbsp;&nbsp;"+ value +"</span>&nbsp;";
    }
  } else {
    return spacer + " <span class='toggle'>&nbsp;&nbsp;&nbsp;&nbsp;"+ value +"</span>&nbsp;";
  }
};

var dataView;
var grid;
var data = [];

var columns = [
               {id: "mhmcategory", name: "Name", field: "mhmcategory", width: 300, cssClass: "cell-title", formatter: TaskNameFormatter, editor: Slick.Editors.Text, sortable: false},
               {id: "owner", name: "Owner", field: "owner", editor: Slick.Editors.Text, sortable: false},
               {id: "rows", name: "Rows", field: "rows", minWidth: 60, sortable: false},
               {id: "cols", name: "Columns", field: "cols", minWidth: 60, sortable: false},
             ];

var options = {
  //editable: true,
  //enableAddRow: true,
  enableCellNavigation: true,
  enableColumnReorder: false,
  multiColumnSort: false
};

var sortcol = "mhmcategory";
var sortdir = 1;
var percentCompleteThreshold = 0;
var searchString = "";

function myFilter(item) {

	  item_to_search = item["mhmcategory"].replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").toLowerCase();
	  searchString = searchString.toLowerCase();
	  
	  if (searchString != "" && item_to_search.indexOf(searchString) == -1) {
	    return false;
	  }

	  if (item.parent != null) {
	    var parent = data[item.parent];

	    while (parent) {
	      if (parent._collapsed || (searchString != "" && item_to_search.indexOf(searchString) == -1)) {
	        return false;
	      }

	      parent = data[parent.parent];
	    }
	  }
	  
	  return true;
	}

function comparer(a, b) {
	  var x = a[sortcol], y = b[sortcol];
	  return (x == y ? 0 : (x > y ? 1 : -1));
	}

function toggleFilterRow() {
	  grid.setTopPanelVisibility(!grid.getOptions().showTopPanel);
	}

function toggleWholeGrid(){
	$("#HMReports_Tree").slideToggle("slow");
}

function updateFilter() {
  dataView.setFilterArgs({
    searchString: searchString
  });
  dataView.refresh();
}

function collapseAllGroups() {
	  dataView.beginUpdate();
	  for (var i = 0; i < dataView.getLength(); i++) {
		  if(dataView.getItem(i).indent == '0' && dataView.getItem(i).expanded == "false" ){
			  item = dataView.getItem(i);
			  item._collapsed = true;
			  dataView.updateItem(item.id, item);
		  }
	  }
	  dataView.endUpdate();
	}
