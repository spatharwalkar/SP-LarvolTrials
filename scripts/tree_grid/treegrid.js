/*

This file is part of Ext JS 4

Copyright (c) 2011 Sencha Inc

Contact:  http://www.sencha.com/contact

GNU General Public License Usage
This file may be used under the terms of the GNU General Public License version 3.0 as published by the Free Software Foundation and appearing in the file LICENSE included in the packaging of this file.  Please review the following information to ensure the GNU General Public License version 3.0 requirements will be met: http://www.gnu.org/copyleft/gpl.html.

If you are unsure which license is appropriate for your use, please contact the sales department at http://www.sencha.com/contact.

*/
Ext.require([
    'Ext.data.*',
    'Ext.grid.*',
    'Ext.tree.*'
]);

Ext.onReady(function() {
    //we want to setup a model and store instead of using dataUrl
    Ext.define('MHMCategory', {
        extend: 'Ext.data.Model',
        fields: [
            {name: 'mhmcategory', type: 'string'},
            {name: 'owner',  	  type: 'string'},
			{name: 'rows', 		  type: 'string'},
            {name: 'cols', 		  type: 'string'}
        ]
    });

    var store = Ext.create('Ext.data.TreeStore', {
        model: 'MHMCategory',
        proxy: {
            type: 'ajax',
            //the store will get the content from the .json file
            url: 'treegrid_data.php'
        },
        folderSort: true
    });

    //Ext.ux.tree.TreeGrid is no longer a Ux. You can simply use a tree.TreePanel
    var tree = Ext.create('Ext.tree.Panel', {
        title: '<form method="post" action="master_heatmap.php" style="float:left;margin:0px;padding:0px;">Master Heatmap Reports &nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="makenew" value="+ New" style="float:none; background-color:#009933; color:#FFFFFF"; border-color:#009966; font-weight:bold" /></form>',
        width: 600,
        height: 200,
        renderTo: document.getElementById("HMReports_Tree"),
        collapsible: true,
        useArrows: true,
        rootVisible: false,
        store: store,
        multiSelect: true,
        singleExpand: false,
        //the 'columns' property is now 'headers'
        columns: [{
            xtype: 'treecolumn', //this is so we know which column will show the tree
            text: 'Name',
            flex: 5,
            sortable: true,
            dataIndex: 'mhmcategory'
        },{
            text: 'Owner',
            flex: 1.4,
            dataIndex: 'owner',
            sortable: true
        },{
            text: 'Rows',
            flex: 1,
			align: 'center',
            dataIndex: 'rows',
            sortable: true
        },{
            text: 'Columns',
            flex: 1,
			align: 'center',
            dataIndex: 'cols',
            sortable: true
        }]
    });
});

