
/*
 * SqlQueryBuilder v 0.06 for jQuery
 *
 * Copyright (c) 2009 Ismail ARIT / K Sistem Ltd. iarit@ksistem.com
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 */



   
(function ($) {


/************* Tree View Functions ***********/
    $.fn.sqlsimpletreeview = function (options) {

        $.fn.sqlsimpletreeview.defaults = {
            name: 'mytree',
            onselect: null
        };
        var opts = $.extend({}, $.fn.sqlsimpletreeview.defaults, options);


        return this.each(function () {

            this.opts = opts;

            var tree = $(this);
            tree.find('ul.treeview li.list').addClass('expanded').find('>ul').toggle();
            //node.find('>ul').toggle();
            tree.click(function (e) {
                //is the click on me or a child
                var node = $(e.target);
                //check the link is a directory
                if (node.is("li.list")) { //Is it a directory listitem that fired the click?
                    //do collapse/expand
                    if (node.hasClass('collapsed')) {
                        node.find('>ul').toggle(); //need the > selector else all child nodes open
                        node.removeClass('collapsed').addClass('expanded');
                    }
                    else if (node.hasClass('expanded')) {
                        node.find('>ul').toggle();
                        node.removeClass('expanded').addClass('collapsed');
                    }
                    //its one of our directory nodes so stop propigation
                    e.stopPropagation();
                } else if (node.attr('href') == '#' | node.hasClass('item')) {
                    /*//its a file node with a href of # so execute the call back
                    // if the item that fired the click is not either a folder or a file it cascades as normal
                    // so that contained links behave like normal*/
                    opts.onselect(node);
                }

            });


        });


    };

/******************** Tree View Functions End *************/

/************* SQL Builder Functions ***************/
    $.fn.extend({
        getSQBClause: function (ctype) {
            var tt = this[0];
            //alert($(tt).html());
            switch (ctype) {
                case 'where':
                    return $('.sqlwheredata', $(tt)).text();
                case 'sort':
                    return $('.sqlsortdata', $(tt)).text();
                    // case 'group':
                    //   return $('.sqlgroupbydata',$(tt)).text();
                case 'column':
                    return $('.sqlcolumndata', $(tt)).text();
                case 'all':
                    return $('.sqlalldata', $(tt)).text();
            }
        },
        checkMatchingBraces: function () {
            var $tt = this[0];
            var openAndClose = 0;
            for (i = 0; i <= $tt.opts.counters[3]; i++) {
                var chainTag = $("a[class=addnewsqlwherechain][id='" + i + "']");
                if (chainTag.text().indexOf('(') != -1) {
                    openAndClose++;
                }

                if (chainTag.text().indexOf(')') != -1) {
                    openAndClose--;
                }
            }
            return openAndClose;

        },   
        getSQBParam: function (prm) {
            var $tt = this[0];
            if (!prm)
                return $tt.opts;
            else
                return ($tt.opts[prm] ? $tt.opts[prm] : null);

        },
        setSQBParam: function (newprms) {
            return this.each(function () {
                if (typeof (newprms) === "object") {
                    $.extend(true, this.opts, newprms);
                }
            });
        },
        loadSQB: function (jsonstr) {
            //alert('in load sqb');
            var $tt = this[0];

            $('.sqlcolumn').remove();
            $('.sqlwhere').remove();
            //	$('.sqlgroup').remove();
            $('.sqlsort').remove();

            $('#override').val('');
            var j = eval('(' + jsonstr + ')');
            $('#override').val(j.override);
           

            var coldiv = $(".addnewsqlcolumn");
            var sortdiv = $('.addnewsqlsort');
            //	var groupdiv=$('.addnewsqlgroup');
            var wherediv = $('.addnewsqlwhere');
            
            var columnHash = new Array();
            var opHash = new Array();
            var chainHash = new Array();
            
            
            for (var i = 0; i < $tt.opts.fields.length; i++) {
             columnHash[$tt.opts.fields[i].field]=i;   
            }
            for (var i = 0; i < $tt.opts.operators.length; i++) {
            	opHash[$tt.opts.operators[i].name]=i;   
             }
            for (var i = 0; i < $tt.opts.chain.length; i++) {
            	chainHash[$tt.opts.chain[i].name]=i;   
            }

            /*rebuild col data*/
            for (var i = 0; i < j.columndata.length; i++) {
                //j.columndata[i].columnslot, j.columndata[i].columnvalue
            	var name = j.columndata[i].columnname;
                var slot = columnHash[name];
                if(slot == null)continue;
                coldiv[0].opts.onselect(slot, coldiv, { columnas: j.columndata[i].columnas });
            }
            /*rebuild sort data*/
            for (var i = 0; i < j.sortdata.length; i++) {
                //j.sortdata[i].columnslot, j.sortdata[i].columnas
            	var name = j.sortdata[i].columnname;
                var slot = columnHash[name];
                if(slot == null)continue;
                sortdiv[0].opts.onselect(slot, sortdiv, { columnas: j.sortdata[i].columnas });
            }
            /*rebuild group by data*/
            for (var i = 0; i < j.groupdata.length; i++) {
                //j.groupdata[i].columnslot, 
            	var name = j.groupdata[i].columnname;
                var slot = columnHash[name];
                if(slot == null)continue;
                groupdiv[0].opts.onselect(slot, groupdiv, null);
            }
            /*rebuild where data*/
            for (var i = 0; i < j.wheredata.length; i++) {
                //j.wheredata[i].columnslot, j.wheredata[i].opslot,j.wheredata[i].chainslot,j.wheredata[i].columnvalue
            	var col_name = j.wheredata[i].columnname;
                var col_slot = columnHash[col_name];
                if(col_slot == null)continue;
            	var op_name = j.wheredata[i].opname;
                var op_slot = opHash[op_name];
                if(op_slot == null)continue;
            	var chain_name = j.wheredata[i].chainname;
                var chain_slot = chainHash[chain_name];
                if(chain_slot == null)continue;

                
                wherediv[0].opts.onselect(col_slot, wherediv, { columnslot: col_slot, opslot: op_slot, chainslot: chain_slot, columnvalue: j.wheredata[i].columnvalue });

            }

        }
    });

    /************* SQL Builder Functions ***************/
    var mouseX = 0, mouseY = 0;
    $().mousemove(function (e) { mouseX = e.pageX; mouseY = e.pageY; });


    /*********** Menu to Show when an item is clicked in sql builder i.e field popup ***/
    $.fn.sqlsimplemenu = function (options) {
        $.fn.sqlsimplemenu.defaults = {
            menu: 'kmenu',
            mtype: 'menu',
            menuid: 0,
            checkeditems: '',
            checkalltext: 'Select all',
            onselect: null,
            onselectclose: null,
            onselectablelist: null,
            oncheck: null,
            fields: [],
            exclusion:[]
        };
        $('input:text:first').focus();
        var opts = $.extend({}, $.fn.sqlsimplemenu.defaults, options);

        function buildsimplemenu() {
            /*console.log("buildsimplemenu: %o", this);*/
            //alert(opts);
        	var exclHash = new Array();
        	 if (opts.exclusion.length > 0) {
                 for (var j = 0; j < opts.exclusion.length; j++) 
                 {
                	 var name = opts.exclusion[j].name;
                	 exclHash[name]=name;
                 }
             }
        	
            var mmenu = '';
            if (opts.fields.length > 0) {
                for (var i = 0; i < opts.fields.length; i++) {
                	var dispName = opts.fields[i].name;
                	if(exclHash[dispName])
                	{
                		continue;
                	}
                	
                	if(opts.fields[i].displayname)
                	{
                		dispName = opts.fields[i].displayname;
                	}
                    if (opts.fields[i].ftype == '{')
                        mmenu = mmenu + '<li><a href="#' + i + '">' + dispName + '</a><ul>';
                    else if (opts.fields[i].ftype == '}')
                        mmenu = mmenu + '</ul></li>';
                    else mmenu = mmenu + '<li><a href="#' + i + '">' + dispName + '</a></li>';
                }
            }
            //remove any visible menus
            $("#sqlmenulist").remove();
            $("#operatorlist").remove();
            $("#chainlist").remove();
            if (opts.menu == 'sqlmenulist') {
                
                return '<div id="' + opts.menu + '" class="sqlsimplemenu" style="height:450px; width:550px;padding-bottom:10px;overflow:scroll;padding-left:5px;padding-top:10px;">' +
        		'<input class="txtsearch" id="txtsearch" name="txtsearch" size="20" type="text" style="width:250px;"/>' +
                //        		'<input type="submit" id="btnsearch" class="btnsearch" value="Search" size="10" style="height:25px; width:60px;padding-bottom:10px;" />'+
        		'<input type="image" id="imgcancel" class="imgcancel" src="images/cancel.gif" name="image" style="height:25px;width:20px;padding-bottom:10px;float:right;right:50px;">' +
                  '<ul class="clicklist" id="ulsqlmenulist">' +
                    mmenu +
                  '</ul>' +
                '</div>';
            }
            else {
                 return '<div id="' + opts.menu + '" class="sqlsimplemenu">' +
		        		'<input type="image" id="imgcancel" class="imgcancel" src="images/cancel.gif" name="image" style="height:25px;width:20px;padding-bottom:10px;float:right;right:50px;">' +
		                  '<ul class="clicklist">' +
		                    mmenu +
		                  '</ul>' +
                '</div>';
            }

        }

        function buildselectboxmenu() {

            var mmenu = '';

            if (opts.onselectablelist) {
                fieldvals = opts.onselectablelist(opts.menuid);
                var farray = fieldvals.split(',');
                var ff = new Array();

                for (h = 0; h < farray.length; h++)
                    ff[h] = { 'name': farray[h] };

                opts.fields = ff;
            }


            if (opts.fields.length > 0) {
                mmenu = mmenu + '<li><input type="checkbox" href="#0" id="' + opts.checkalltext + '">' + opts.checkalltext + '</li>';
                for (var i = 0; i < opts.fields.length; i++) {
                    mmenu = mmenu + '<li><input type="checkbox" ' + (opts.checkeditems.indexOf(opts.fields[i].name) != -1 ? ' checked ' : '') + 'href="#' + (i + 1) + '" id="' + opts.fields[i].name + '">' + opts.fields[i].name + '</li>';
                }
            }

            return '<div id="' + opts.menu + '" class="sqlsimplemenu">' +
        '<input class="txtsearch" id="txtsearch" name="txtsearch" size="20" type="text" style="height:450px; width:550px;padding-bottom:10px;" />' +
            //           '<input type="submit" value="suhmit" size="10" style="height:450px; width:550px;padding-bottom:10px; />'+
                  '<ul>' +
                    mmenu +
                  '</ul>' +
                '</div>';

        }


        return this.each(function () {
            //debugger;
            this.opts = opts;
            ////				 /*console.log("sqlsimplemenu:this.each: %o",this);*/
            ////				
            ////                 var sm= opts.mtype=='selectbox'? buildselectboxmenu():buildsimplemenu();    

            ////                 $(document.body).after(sm);//add to body
            ////                 $('div#'+opts.menu).hide();//hide




            $(this).click(function (e) {
                //debugger;
                var srcelement = $(this);
                //                          alert($(this).text());
                /*console.log("sqlsimplemenu:this.each:click: %o",this);*/
                e.stopPropagation();
                /*console.log("sqlsimplemenu:this.each: %o",this);*/

                var sm = opts.mtype == 'selectbox' ? buildselectboxmenu() : buildsimplemenu();
                $("body").prepend(sm); //add to body
                $('div#' + opts.menu).hide(); //hide  

                if (!e.pageX) { e.pageX = mouseX; e.pageY = mouseY; }
                $('div#' + opts.menu).css({ top: e.pageY + 5, left: e.pageX + 5, position: 'absolute' }).slideToggle(200);

                $(document).unbind('click').click(function (e) {
                    if (opts.fields.name != undefined) {
                        /*console.log("sqlsimplemenu:this.each:click:unbind:"+'div#'+opts.menu+": %o",this);*/
                        $('div#' + opts.menu).slideUp(200, function () {
                            if (opts.onselectclose)
                                opts.onselectclose($(this));
                        });
                        $(document).unbind('click');
                        e.stopPropagation();
                        return false;
                    }
                });

 


                $('div#' + opts.menu).find('input[type=checkbox]').unbind('click')
                                                                     .click(function (e) {
                                                                         e.stopPropagation();
                                                                         var vhref = $(this).attr('href');
                                                                         vhref = vhref.split('#')[1];
                                                                         if (vhref == 0)
                                                                             $('div#' + opts.menu).find('input[type=checkbox]').attr('checked', $(this).attr('checked') ? true : false);
                                                                         else
                                                                             $(this).attr('checked', $(this).attr('checked') ? true : false);

                                                                         var items = new Array();
                                                                         var k = 0;
                                                                         $('div#' + opts.menu).find('input[type=checkbox]').each(function () {
                                                                             //if not select all(first item in the list...)
                                                                             var v1href = $(this).attr('href');
                                                                             v1href = v1href.split('#')[1];
                                                                             if (v1href != '0') {
                                                                                 items[k] = ($(this).attr('checked') ? $(this).attr('id') : '');
                                                                                 if ($(this).attr('checked')) k++;
                                                                             }
                                                                         });

                                                                         if (k == 0) items[0] = '[]'; //if empt put etleast [] in it..
                                                                         var items_str = items.join(',');
                                                                         //alert(items_str.substr(-1,1));
                                                                         //alert(items_str.substr(0,items_str.length-1));
                                                                         var v2href = items_str.substring(items_str.length - 1, items_str.length);
                                                                         if (v2href == ',')
                                                                             items_str = items_str.substring(0, items_str.length - 1);


                                                                         var v3href = $(this).attr('href');
                                                                         v3href = v3href.split('#')[1];

                                                                         if (opts.onselect) opts.oncheck(v3href, $(srcelement), $(this), items_str);
                                                                         //return false;
                                                                     });




                $('div#' + opts.menu).find('a').unbind('click')
                                                  .click(function (e) {
                                                      var selitem = $(this);
                                                      var value = jQuery.trim($(this).text());
                                                      if ($.browser.msie == true) {
                                                          if (value != 'Global fields >' && value != 'EudraCT >' && value != 'NCT >' &&
						               value != 'Annotations >' && value != 'PubMed >') {
                                                              $('div#' + opts.menu).slideUp(200, function () {
                                                                  /*console.log("sqlsimplemenu:this.each:click:find(a):unbind:"+'div#'+opts.menu+":slideup(200): %o",this);
                                                                  if( opts.onselect ) opts.onselect( $(selitem).attr('href').substr(1), $(srcelement),null );*/
                                                              });

                                                              var v5href = $(selitem).attr('href');
                                                              v5href = v5href.split('#')[1];
                                                              if (opts.onselect) opts.onselect(v5href, $(srcelement), null);

                                                              return false;
                                                          }
                                                      }
                                                      else {
                                                          if (value != 'Global fields  >' && value != 'EudraCT  >' && value != 'NCT  >' &&
						                                           value != 'Annotations  >' && value != 'PubMed  >') {
                                                              //alert($(this).text());
                                                              /*console.log("sqlsimplemenu:this.each:click:find(a):unbind:"+'div#'+opts.menu+": %o",this);*/

                                                              $('div#' + opts.menu).slideUp(200, function () {
                                                                  /*console.log("sqlsimplemenu:this.each:click:find(a):unbind:"+'div#'+opts.menu+":slideup(200): %o",this);
                                                                  if( opts.onselect ) opts.onselect( $(selitem).attr('href').substr(1), $(srcelement),null );*/
                                                              });
                                                              var v4href = $(selitem).attr('href');
                                                              v4href = v4href.split('#')[1];
                                                              if (opts.onselect) opts.onselect(v4href, $(srcelement), null);

                                                              return false;
                                                          }
                                                      }
                                                  });




                $("#txtsearch").keyup(function (e) {
                    //alert($("input#txtsearch")[3].value);
                    var searchstring = jQuery.trim($("#txtsearch").val());
                    var resultstring = '';
                    var optstring = '';
                    var mmenu = '<ul>';
                    var ulist = $("#ulsqlmenulist");
                    $('#ulsqlmenulist li').remove();
                    for (var i = 0; i < opts.fields.length; i++) {
                        if (searchstring == '') {
                            $('#ulsqlmenulist ul').remove();

                            if (opts.fields[i].ftype == '{')
                                mmenu = mmenu + '<li><a href="#' + i + '">' + opts.fields[i].name + '</a><ul>';
                            else if (opts.fields[i].ftype == '}')
                                mmenu = mmenu + '</ul></li>';
                            else mmenu = mmenu + '<li><a href="#' + i + '">' + opts.fields[i].name + '</a></li>';

                        }
                        else {
                            optstring = opts.fields[i].name;
                            if (optstring.indexOf(searchstring) >= 0) {
                                mmenu += '<li><a href="#' + i + '">' + opts.fields[i].name + '</a></li>';
                            }
                        }
                    }
                    $("ul#ulsqlmenulist").append(mmenu);

                    var aa = $('div#' + opts.menu).find('#ulsqlmenulist');
                    aa.find('a').unbind('click')
                                                  .click(function (e) {

                                                      var selitem = $(this);
                                                      // var srcelement=$(this);        
                                                      var value = jQuery.trim($(this).text());
                                                      if ($.browser.msie == true) {
                                                          if (value != 'Global fields >' && value != 'EudraCT >' && value != 'NCT >' &&
						               value != 'Annotations >' && value != 'PubMed >') {
                                                              $('div#' + opts.menu).slideUp(200, function () {
                                                                  /*console.log("sqlsimplemenu:this.each:click:find(a):unbind:"+'div#'+opts.menu+":slideup(200): %o",this);
                                                                  if( opts.onselect ) opts.onselect( $(selitem).attr('href').substr(1), $(srcelement),null );*/
                                                              });

                                                              var v5href = $(selitem).attr('href');
                                                              v5href = v5href.split('#')[1];
                                                              if (opts.onselect) opts.onselect(v5href, $(srcelement), null);

                                                              return false;
                                                          }
                                                      }

                                                      else {
                                                          if (value != 'Global fields  >' && value != 'EudraCT  >' && value != 'NCT  >' &&
						     value != 'Annotations  >' && value != 'PubMed  >') {
                                                              //alert($(this).text());
                                                              /*console.log("sqlsimplemenu:this.each:click:find(a):unbind:"+'div#'+opts.menu+": %o",this);*/

                                                              $('div#' + opts.menu).slideUp(200, function () {
                                                                  /*console.log("sqlsimplemenu:this.each:click:find(a):unbind:"+'div#'+opts.menu+":slideup(200): %o",this);
                                                                  if( opts.onselect ) opts.onselect( $(selitem).attr('href').substr(1), $(srcelement),null );*/
                                                              });

                                                              var v5href = $(selitem).attr('href');
                                                              v5href = v5href.split('#')[1];
                                                              if (opts.onselect) opts.onselect(v5href, $(srcelement), null);

                                                              return false;
                                                          }
                                                      }
                                                  });
                });


                $('div#' + opts.menu).find('input[type=image]').unbind('click')
                                                  .click(function (e) {

                                                      //alert($(this).text());
                                                      /*console.log("sqlsimplemenu:this.each:click:find(a):unbind:"+'div#'+opts.menu+": %o",this);*/

                                                      $('div#' + opts.menu).slideUp(200, function () {
                                                      });
                                                      //								var v6href= $(this).attr('href');
                                                      //								alert($(this));
                                                      //								v6href=v6href.split('#')[0];
                                                      //								alert(v6href);
                                                      //								if( opts.onselect ) opts.onselect( $(this)., $(srcelement),null );

                                                      //								return false;


                                                      return false;
                                                  });



            });
            //			var id=null;
            //			var idl=null;	
            //                   $(".sqlcolumn").find(".addnewsqlcolumn").mouseenter(function(){
            //                            id =$(this).attr('id');
            //                            $('#'+id).find("#imgarrowcolumn").attr("style", "visibility:show");
            //                            id--;
            //                    }).mouseleave(function(){
            //                            idl=$(this).attr('id');
            //                            $('#'+idl).find("#imgarrowcolumn").attr("style", "visibility:hidden");
            //                            idl++;
            //                   });	
            //                   $(".sqlwhere").find(".addnewsqlwhere").mouseenter(function(){
            //                             id=$(this).attr('id');
            //                             $('#'+id).find("#imgarrowwhere").attr("style", "visibility:show");
            //                             id--;
            //                    }).mouseleave(function(){
            //                             idl=$(this).attr('id');
            //                             $('#'+idl).find("#imgarrowwhere").attr("style", "visibility:hidden");
            //                             idl++;
            //                    });	
            //                    $(".sqlsort").find(".addnewsqlsort").mouseenter(function(){
            //                            id=$(this).attr('id');
            //                            $('#'+id).find("#imgarrowsort").attr("style", "visibility:show");
            //                    }).mouseleave(function(){
            //                            idl=$(this).attr('id');
            //                            $('#'+idl).find("#imgarrowsort").attr("style", "visibility:hidden");
            //                    });	

            ////	   $("#btnsearch").click( function(e){
            ////	  		     //alert($("input#txtsearch")[3].value);
            ////                 alert('opts');
            ////	  		    var searchstring=$("input#txtsearch")[3].value;
            ////	  		    var resultstring='';
            ////	  		    var optstring='';
            ////	  		     var mmenu=''; 
            ////				 var ulist = $("#ulsqlmenulist");
            ////				 $('#ulsqlmenulist li').remove();
            ////	  		   for (var i=0;i<opts.fields.length;i++)
            ////	  		    {
            ////	  		        optstring=opts.fields[i].name;
            ////	  		        if(optstring.indexOf(searchstring)>=0)
            ////	  		        {
            ////						mmenu += '<li><a href="#'+i+'">'+opts.fields[i].name+'</a></li>';
            ////	  		        }
            ////	  		    }			                   
            ////                    $("ul#ulsqlmenulist").append(mmenu); 
            ////                    var aa=$('div#'+opts.menu).find('#ulsqlmenulist');
            ////                      	aa.find('a').unbind('click')
            ////                                                  .click( function(e) {
            ////                                              
            ////                                                  var selitem=$(this);
            ////                                                 var srcelement=$(this);        
            ////                             if($(this).text()!='Global fields  >' && $(this).text()!='EudraCT  >' && $(this).text()!='NCT  >' &&
            ////						     $(this).text()!='Annotations  >' && $(this).text()!='PubMed  >')		
            ////						     {
            ////						     //alert($(this).text());
            ////					                /*console.log("sqlsimplemenu:this.each:click:find(a):unbind:"+'div#'+opts.menu+": %o",this);*/
            ////												  
            ////							$('div#'+opts.menu).slideUp(200,function(){								
            ////					                /*console.log("sqlsimplemenu:this.each:click:find(a):unbind:"+'div#'+opts.menu+":slideup(200): %o",this);
            ////								            if( opts.onselect ) opts.onselect( $(selitem).attr('href').substr(1), $(srcelement),null );*/
            ////								});
            ////								
            ////								if( opts.onselect ) opts.onselect( $(selitem).attr('href').substr(1), $(srcelement),null );

            ////								return false;
            ////						     }				        
            ////					 });	
            ////			});
            ////			  $('div#'+opts.menu).find('input[type=image]').unbind('click')
            ////                                                  .click( function(e) {
            ////                                        
            ////						     //alert($(this).text());
            ////					                /*console.log("sqlsimplemenu:this.each:click:find(a):unbind:"+'div#'+opts.menu+": %o",this);*/
            ////												  
            ////							$('div#'+opts.menu).slideUp(200,function(){								
            ////					               							});
            ////								
            ////								if( opts.onselect ) opts.onselect( $(selitem).attr('href').substr(1), $(srcelement),null );

            ////								return false;
            ////						   
            ////				
            ////			});
        });

    };


/***************** Menu/Pop functions END *******************/

/**************** Main SQL Builder Functions ***************/
    $.fn.sqlquerybuilder = function (options) {
        $.fn.sqlquerybuilder.defaults = {
            reportid: 0,
            counters: [0, 0, 0, 0], //we have four counters..
            sqldiv: null, //where sql clause will be put..
            presetlistdiv: null, //where saved sqls are listed...
            reporthandler: null, //this is the .php to query to get the ul treeview to show previuosly saved sqls...
            datadiv: null, //where we put data, so that data can be saved..
            statusmsgdiv: null, //where we put error strs..
            whereinput: null,
            sortinput: null,
            groupinput: null,
            columninput: null,
            allinput: null,
            reportnameprompt: 'Report name',
            reportnameinput: 'type your report name here',
            columntitle: 'Result Columns ',
            addnewcolumn:'<img src="images/add.gif" style="border:none;">'+'Add Result Column',
            showcolumn: true,
            addtext: '+',
            wheretitle: 'Select records where all of the following apply',
           addnewwhere:'<img src="images/add.gif" style="border:none;">'+'Add Condition',
		showwhere:true,
		sorttitle:'Sort By',
		addnewsort:'<img src="images/add.gif" style="border:none;">'+'Add Sort Column',
		showsort:true,
		grouptitle:'Group columns by..',
		addnewgroup:'<img src="images/add.gif" style="border:none;">'+'Add Group Column',
		showgroup:true,
		deletetext:'<img src="images/minus.gif" style="border:none;">',
		animate:true,
            onchange: null,
            onselectablelist: null,
            fields: [],
            joinrules: [],
            extrawhere: '',
            operators: [
		 { name: 'EqualTo', displayname: '=', op: "%f='%s'", multipleval: false },
		 { name: 'NotEqualTo',displayname: '!=', op: "%f!='%s'", multipleval: false },
		 { name: 'StartsWith',displayname: 'StartsWith', op: "%f like '%s%'", multipleval: false },
		 { name: 'NotStartsWith',displayname: 'NotStartsWith', op: "not(%f like '%s%')", multipleval: false },
		 { name: 'Contains', displayname: 'Contains', op: "%f like '%%s%'", multipleval: false },
		 { name: 'NotContains',displayname: 'NotContains', op: "not(%f like '%%s%')", multipleval: false },
		 { name: 'BiggerThan',displayname: '>', op: "%f>'%s'", multipleval: false },
		 { name: 'BiggerOrEqualTo',displayname: '>=', op: "%f>='%s'", multipleval: false },
		 { name: 'SmallerThan', displayname: '<',op: "%f<'%s'", multipleval: false },
		 { name: 'SmallerOrEqualTo',displayname: '<=', op: "%f<='%s'", multipleval: false },
		 { name: 'InBetween', displayname: 'InBetween',op: "%f between '%s1' and '%s2'", multipleval: true, info: '' },
		 { name: 'NotInBetween',displayname: 'EqualTo', op: "not(%f between '%s1' and '%s2')", multipleval: true, info: '' },
		 { name: 'IsIn', displayname: 'IsIn', op: "%f in (%s)", multipleval: false, selectablelist: true, info: '' },
		 { name: 'IsNotIn',displayname: 'IsNotIn', op: "not(%f in (%s))", multipleval: false, selectablelist: true, info: '' },
		 { name: 'IsNull', displayname: 'IsNull', op: " %f is null", multipleval: false },
		 { name: 'NotNull', displayname: 'NotNull', op: " %f not null", multipleval: false },
		 { name: 'Regex', displayname: 'Regex', op: " %f ='%s'", multipleval: false },
		 { name: 'NotRegex', displayname: 'NotRegex', op: " %f !='%s'", multipleval: false }
		],
            chain: [
		 { name: 'AND', op: 'AND' },
		 { name: 'OR', op: 'OR' },
		 { name: 'AND (', op: 'AND (' },
		 { name: 'OR (', op: 'OR (' },
		 { name: ') AND', op: ') AND' },
		 { name: ') OR', op: ') OR' },
		 { name: ') .', op: ')' },
		 { name: '.', op: '' }

		],
            astagpre: '"',
            astagsuf: '"'
        };
        var opts = $.extend({}, $.fn.sqlquerybuilder.defaults, options);
        var sqlwidget = $(this);

        var howmany = opts.amount;


        function addnewsqlwhere() {

            var sql_text = "<br/>" + opts.wheretitle + "<br/><br/>";
            //add predefined rules here too...
            //...

            return sql_text;
        }

        function addnewsqlcolumn() {

            //var sql_text = "<br/>" + opts.columntitle + "<br/><br/>";
            //add predefined columns here too...
            //...

            if(opts.showcolumn)
            {
                      return opts.columntitle ;
            }
            return "";
        }


        function addnewsqlgroup() {

            var sql_text = "<br/>" + opts.grouptitle + "<br/><br/>";
            //add predefined group here too...
            //...

            return sql_text;
        }



        function addnewsqlsort() {

            //var sql_text = "<br/>" + opts.sorttitle + "<br/><br/>";
            //add predefined sort here too...
            //...
            if(opts.showsort)
            {
                      return opts.sorttitle;
            }
            return "";
        }


        function onchangeevent(type) {
            //debugger;
            //$.get('/callback/', {cache: true});
            if (opts.datadiv) {


                var data = '{' +
                         '"reportid":"' + opts.reportid + '",';
                var override = $('#override').val();
                data = data + '"override":"' + override + '",';
                data = data + '"columndata":[';
                $('span.sqlcolumn').each(function () {
                    var col_slot = $(this).find('a.addnewsqlcolumn').attr('href');
                    col_slot = col_slot.split('#')[1];
                    var col_name = $(this).find('a.addnewsqlcolumn').text();
                    var col_as = $(this).find('input.addnewsqlcolumnvalue').val();
                    var columndata = '{' +
                                 // 'columnslot:' + col_slot + ',' +
                                  '"columnname":"' + col_name + '",' +
                                  '"columnas":"' + col_as + '"' +
                                '},';
                    data = data + columndata;
                });
                data = data.replace(/,$/, '');
                data = data + '],'; //close columns data   


                data = data + '"sortdata":[';
                $('span.sqlsort').each(function () {
                    var col_slot = $(this).find('a.addnewsqlsort').attr('href');
                    col_slot = col_slot.split('#')[1];
                    var col_name = $(this).find('a.addnewsqlsort').text();
                    var col_as = $(this).find('span.addnewsqlsortvalue').html();
                    var columndata = '{' +
                                  //'columnslot:' + col_slot + ',' +
                                  '"columnname":"' + col_name + '",' +
                                  '"columnas":"' + col_as + '"' +
                                '},';
                    data = data + columndata;
                });
                data = data.replace(/,$/, '');
                data = data + '],'; //close sort data   

                data = data + '"groupdata":[';
                $('span.sqlgroup').each(function () {
                    var col_slot = $(this).find('a.addnewsqlgroup').attr('href');
                    col_slot = col_slot.split('#')[1];
                    var col_name = $(this).find('a.addnewsqlgroup').text();
                    var columndata = '{' +
                                  //'columnslot:' + col_slot + ',' +
                                  '"columnname":"' + col_name + '",' +
                                '},';
                    data = data + columndata;
                });
                data = data.replace(/,$/, '');
                data = data + '],'; //close group data   

                
                data = data + '"wheredata":[';
                $('span.sqlwhere').each(function () {
                    //debugger;

                    var col_slot = $(this).find('a.addnewsqlwhere').attr('href');
                    var index = $(this).find('a.addnewsqlwhere').attr('id');
                    var col_name = $(this).find('a.addnewsqlwhere').text();
                    col_slot = col_slot.split('#')[1];
                    //alert(col_slot);
                    var op_slot = $(this).find('a.addnewsqlwhereoperator').attr('href');
                    op_slot = op_slot.split('#')[1];
                    //var op_name = $(this).find('a.addnewsqlwhereoperator').text();
                    var op_name = opts.operators[op_slot].name;
                    var chain_slot = $(this).find('a.addnewsqlwherechain').attr('href');
                    chain_slot = chain_slot.split('#')[1];
                    var chain_name = $(this).find('a.addnewsqlwherechain').text();
                    var col_value = '';
                    if(opts.operators[op_slot].multipleval){
                       	if ($(this)[0].innerHTML.toLowerCase().indexOf('input') != -1) {
                    		var col_value1 = $(this).find('input.addnewsqlwherevalue[id=' + index + '_1]').val();
                    		var col_value2 = $(this).find('input.addnewsqlwherevalue[id=' + index + '_2]').val();
                    		col_value = col_value1 + 'and;endl' + col_value2;
                    	}
                    	else if ($(this)[0].innerHTML.toLowerCase().indexOf('select') != -1) {
                    		//TODO if necessary
                      		var col_value1 = $(this).find('select.addnewsqlwherevalue[id=' + index + '_1] option:selected').val();
                    		var col_value2 = $(this).find('select.addnewsqlwherevalue[id=' + index + '_2] option:selected').val();
                    		col_value = col_value1 + 'and;endl' + col_value2;
                    	}
                    }
                    else
                    {
                    	if ($(this)[0].innerHTML.toLowerCase().indexOf('input') != -1) {
                    		col_value = $(this).find('input.addnewsqlwherevalue').val();
                    	}
                    	else if ($(this)[0].innerHTML.toLowerCase().indexOf('select') != -1) {
                    		col_value = $(this).find(":selected").text();
                    	}
                    }

                    var columndata = '{' +
                                  //'columnslot:' + col_slot + ',' +
                                  '"columnname":"' + col_name + '",' +
                                  //'opslot:' + op_slot + ',' +
                                  '"opname":"' + op_name + '",' +
                                  //'chainslot:' + chain_slot + ',' +
                                  '"chainname":"' + chain_name + '",' +
                                  '"columnvalue":"' + col_value + '"' +
                                '},';
                    data = data + columndata;
                });
                data = data.replace(/,$/, '');
                data = data + '],'; //close where data   

                data = data.replace(/,$/, '');
                data = data + '}'//close full json;   

                $('.sqldata', $(sqlwidget)).html(data);

            }

            //create sql clause
            //if(opts.sqldiv)
            {


                //get columns....
                var columns = new Array();
                var ccount = 0;
                var tablehash = new Array();
                $('span.sqlcolumn').each(function () {
                    var col_slot = $(this).find('a.addnewsqlcolumn').attr('href');
                    col_slot = col_slot.split('#')[1];
                    var col_as = $(this).find('input.addnewsqlcolumnvalue').val();
                    var fieldstr = opts.fields[col_slot].field;
                    if (col_as.indexOf(':') != -1) {
                        var colfuncarray = col_as.split(':');
                        var colfunc = colfuncarray[1]; //syntax is fieldname:func like invtrans.quantity:sum(%f) 
                        fieldstr = colfunc.replace('%f', fieldstr);
                        col_as = colfuncarray[0];
                    }

                    columns[ccount++] = fieldstr + ' as ' + opts.astagpre + col_as + opts.astagsuf;
                    var xx = opts.fields[col_slot].field.split('.'); //table.field
                    tablehash[xx[0]] = xx[0];
                });
                var colstr = columns.join(',');
                if (ccount == 0) colstr = ' * ';
                $('.sqlcolumndata', $(sqlwidget)).html(colstr);
                if (opts.columninput) $(opts.columninput).val(colstr);


                //get sorts......... 
                var sorts = new Array();
                var scount = 0;
                $('span.sqlsort').each(function () {
                    var col_slot = $(this).find('a.addnewsqlsort').attr('href');
                    col_slot = col_slot.split('#')[1];
                    var col_as = $(this).find('span.addnewsqlsortvalue').html();
                    sorts[scount++] = opts.fields[col_slot].field + '  ' + (col_as == 'Descending' ? 'desc' : '');
                    var xx = opts.fields[col_slot].field.split('.'); //table.field
                    tablehash[xx[0]] = xx[0];
                });
                var sortstr = sorts.join(',');
                $('.sqlsortdata', $(sqlwidget)).html(sortstr);
                if (opts.sortinput) $(opts.sortinput).val(sortstr);


                //get group bys....
                var groups = new Array();
                var gcount = 0;
                $('span.sqlgroup').each(function () {
                    var col_slot = $(this).find('a.addnewsqlgroup').attr('href');
                    col_slot = col_slot.split('#')[1];
                    groups[gcount++] = opts.fields[col_slot].field;
                    var xx = opts.fields[col_slot].field.split('.'); //table.field
                    tablehash[xx[0]] = xx[0];
                });
                var groupstr = groups.join(',');
                $('.sqlgroupbydata', $(sqlwidget)).html(groupstr);
                if (opts.groupinput) $(opts.groupinput).val(groupstr);




                //get where str...
                var wheres = new Array();
                var wcount = 0;
                var prevchain = ' ', prevchainstr = ' ';
                $('span.sqlwhere').each(function () {
                    var col_slot = $(this).find('a.addnewsqlwhere').attr('href');
                    col_slot = col_slot.split('#')[1];
                    var op_slot = $(this).find('a.addnewsqlwhereoperator').attr('href');
                    op_slot = op_slot.split('#')[1];
                    var chain_slot = $(this).find('a.addnewsqlwherechain').attr('href');
                    chain_slot = chain_slot.split('#')[1];
                    //debugger;
                    //                   var col_value=$(this).find('span.addnewsqlwherevalue').html();
                    var col_value = '';
                    if ($(this)[0].innerHTML.toLowerCase().indexOf('input') != -1) {
                        col_value = $(this).find('input.addnewsqlwherevalue').val();
                    }
                    else if ($(this)[0].innerHTML.toLowerCase().indexOf('select') != -1) {
                        col_value = $(this).find(":selected").text();
                    }

                    var xx = opts.fields[col_slot].field.split('.'); //table.field
                    tablehash[xx[0]] = xx[0];

                    var wstr = prevchain + opts.operators[op_slot].op;
                    wstr = wstr.replace('%f', opts.fields[col_slot].field);
                    if (opts.operators[op_slot].multipleval) {
                        var xx = col_value.split('and');
                        wstr = wstr.replace('%s1', xx[0]);
                        wstr = wstr.replace('%s2', xx[1]);
                    } else {
                        if (opts.operators[op_slot].selectablelist) {
                            var xx = col_value.split(',');
                            for (k in xx) {
                                xx[k] = "'" + xx[k] + "'";
                            }
                            col_value = xx.join(',');
                        }
                        wstr = wstr.replace('%s', col_value);
                    }


                    prevchain = opts.chain[chain_slot].op;
                    prevchainstr = opts.chain[chain_slot].name;

                    wheres[wcount++] = wstr;

                });

                var wherestr = wheres.join(' ');
                $('.sqlwheredata', $(sqlwidget)).html(wherestr);
                if (opts.whereinput) $(opts.whereinput).val(wherestr);


                if (prevchainstr.indexOf('.') != -1)
                    wherestr += prevchain;

                if (wcount) wherestr = wherestr + ' ' + opts.extrawhere;
                else if (opts.extrawhere) wherestr = opts.extrawhere;



                //table names
                var tcount = 0; var tables = new Array();
                for (tablename in tablehash) {
                    tables[tcount++] = tablename;
                }
                var tablestr = tables.join(',');
                if (tcount > 1) {
                    tablestr = tables[0] + ' ';
                    for (j = 0; j < tcount; j++) {
                        for (k = 0; k < opts.joinrules.length; k++) {
                            if (tables[0] == opts.joinrules[k].table1 &&
                       tables[j] == opts.joinrules[k].table2)
                                tablestr += opts.joinrules[k].rulestr + ' ';
                        }
                    }
                }




                if (opts.sqldiv) $(opts.sqldiv).html(wherestr + (gcount ? (' group by ' + groupstr) : '') + (scount ? (' order by ' + sortstr) : ''));
                $('.sqlalldata', $(sqlwidget)).html(wherestr);
                if (opts.allinput) $(opts.allinput).val('select ' + colstr + ' from ' + tablestr + wherestr + (gcount ? (' group by ' + groupstr) : '') + (scount ? (' order by ' + sortstr) : ''));

            }

            //if(opts.onchange)opts.onchange(type);   

        }




        return this.each(function () {
            //debugger;
            this.opts = opts;

            var columnmarkup = addnewsqlcolumn();
            var wheremarkup = addnewsqlwhere();
            var sortmarkup = addnewsqlsort();
            var groupmarkup = addnewsqlgroup();
            var sqlbuildelement = $(this);

            /*load before-saved sqls*/
            if (opts.presetlistdiv && opts.reporthandler) {
                //debugger;

//removing listing function here since we have our own grid now            	
//                $.ajax({
//                    type: 'POST',
//                    url: opts.reporthandler + '?op=list',
//                    data: 'reportid=' + opts.reportid,
//                    error: function () { if (opts.statusmsgdiv) $(opts.statusmsgdiv).html("Can't load preset"); },
//                    success: function (data) {
//
//                        $(opts.presetlistdiv).html(data);
//                        $(opts.presetlistdiv).find('ul.treeview li.list').after("<li><input type=" + "submit" + " value=" + "save" + " style=" + "visibility:hidden" + " id='save_" + opts.reportid + "'/></li>");
//                        $(opts.presetlistdiv).find("#save_" + opts.reportid).click(function () {
//                            var name = prompt(opts.reportnameprompt, opts.reportnameinput);
//                            if (!name) return false;
//                            $.ajaxSetup({ cache: false });
//
//                            $.ajax({
//                                type: 'POST',
//                                url: opts.reporthandler + '?op=save&reportid=' + opts.reportid + '&reportname=' + encodeURIComponent(name),
//                                data: 'querytosave=' + $('.sqldata', $(sqlbuildelement)).html(),
//                                error: function () { if (opts.statusmsgdiv) $(opts.statusmsgdiv).html("Can't save the report sql"); },
//                                success: function (data) { if (opts.statusmsgdiv) $(opts.statusmsgdiv).html(data); }
//                            });
//                            return false;
//                        });

                        //debugger;		
                      


//                    }
//                });
 
            
            
            
            
            
            
            
            
            
            
            
            }
            //debugger;           
            $(this).html(
            		'<fieldset style="float:left;width:35em;"><legend>NCTid Override</legend>'
            				+ 'Enter a comma-delimited list of NCTids of records that must be returned by this search regardless of criteria<br />'
            				+ '<input type="text" id="override" name="override" value="" /></fieldset>' 
            				+ '<br clear="all"/>' +

                    '<p class=sqldata></p>' +
                    '<p class=sqlwheredata></p>' +
                    '<p class=sqlsortdata></p>' +
                    '<p class=sqlcolumndata></p>' +
                    '<p class=sqlgroupbydata></p>' +
                    '<p class=sqlalldata></p>' +
                    '<font size="4" face="Bold" color="Grey">Conditions</font>' +
                    '<p class=sqlbuilderwhere>' + '<br/>' + '<a class="addnewsqlwhere" id=9999 href="#">' + '<br/>' + opts.addnewwhere + '</a>' + '<br/><br/><br/>' + '</p>' +
                    '<br/><br/>' +
                    '<font size="4" face="Bold" color="Grey">' + addnewsqlcolumn()  + '</font>' +
                    '<p class=sqlbuildercolumn>' + '<br/><br/>' + '<a class="addnewsqlcolumn" id=9999 href="#">' + opts.addnewcolumn + '</a>' + '<br/><br/><br/>' + '</p>' +
                    '<br/>' +
                    '<font size="4" face="Bold" color="Grey">' + addnewsqlsort() + '</font>' +
                    '<p class=sqlbuildersort>' + '<br/>' + '<a class="addnewsqlsort" id=9999 href="#">' + '<br/>' + opts.addnewsort + '</a>' + '<br/><br/><br/>' + '</p>' +
                    '<br/><br/>' +
                   

                    '<p class=sqlbuildergroup>' + '<br/>' + '<a class="addnewsqlgroup" id=9999 href="#">' + '<br/>' + opts.addnewgroup + '</a>' + '<br/><br/><br/>' + '</p>' +
                    '<br/>' 
                   );



            $(".sqldata").hide();
            $(".sqlalldata").hide();
            $(".sqlcolumndata").hide();
            $(".sqlwheredata").hide();
            $(".sqlsortdata").hide();
            $(".sqlgroupbydata").hide();

            if (!opts.showcolumn)
                $(".sqlbuildercolumn").hide();
            if (!opts.showsort)
                $(".sqlbuildersort").hide();
            if (!opts.showgroup)
                $(".sqlbuildergroup").hide();
            if (!opts.showwhere)
                $(".sqlbuilderwhere").hide();
            $('input:text:first').focus();


            $("#override").change(
		               function (e) {
		            	   $('#override').blur(function () {
		                       $('.sqlsyntaxhelp').remove();
		                       onchangeevent('change');



		                   });

		               });


            /*************************************************************************************************************/

            //column or sort click handling is here..... 
            $(".addnewsqlcolumn,.addnewsqlsort,.addnewsqlgroup").sqlsimplemenu({
                menu: 'sqlmenulist',
                fields: opts.fields,
                onselect: function (action, el, defval) {
                    /*console.log(".addnewsqlcolumn,.addnewsqlsort,.addnewsqlgroup: %o",this);*/
                    //debugger;
                    var menutype = ''; //$(el).hasClass('addnewsqlcolumn')?'column':'sort';
                    //var iscolumn= $(el).hasClass('addnewsqlcolumn')?true:false;			
                    var countertype = 0;
                    if ($(el).hasClass('addnewsqlcolumn')) { menutype = 'column'; countertype = 0; }
                    else if ($(el).hasClass('addnewsqlsort')) { menutype = 'sort'; countertype = 1; }
                    else if ($(el).hasClass('addnewsqlgroup')) { menutype = 'group'; countertype = 2; } //where counter id is 3

                    var sqlline = '';
                    if (menutype == 'column') {
                        sqlline =
					          '<span class="sql' + menutype + '" id=' + (opts.counters[countertype]) + '>' +
				                 '<a class="addnewsql' + menutype + 'delete" id=' + (opts.counters[countertype]) + ' href="#' + action + '">' + opts.deletetext + '</a>&nbsp;' +
				                 '<a class="addnewsql' + menutype + '" id=' + (opts.counters[countertype]) + ' href="#' + action + '">' + opts.fields[action].name + '</a>' + (countertype == 2 ? '' : '&nbsp;as &nbsp;') +
   			                     '<input type="text" class="addnewsql' + menutype + 'value" id=' + (opts.counters[countertype]) + ' value="' + (defval ? defval.columnas : opts.fields[action].name) + '" />' +
				                 '</span>';
                    }
                    else {

                        sqlline =
				                 '<span class="sql' + menutype + '" id=' + (opts.counters[countertype]) + '>' +
				                 '<a class="addnewsql' + menutype + 'delete" id=' + (opts.counters[countertype]) + ' href="#' + action + '">' + opts.deletetext + '</a>&nbsp;' +
				                 '<a class="addnewsql' + menutype + '" id=' + (opts.counters[countertype]) + ' href="#' + action + '">' + opts.fields[action].name + '</a>' + (countertype == 2 ? '' : '&nbsp;as &nbsp;') +
   			                     '<span class="addnewsql' + menutype + 'value" id=' + (opts.counters[countertype]) + ' href="#0">' + ((countertype == 0 || countertype == 2) ? (countertype == 0 ? (defval ? defval.columnas : opts.fields[action].name) : '') : (defval ? defval.columnas : 'Ascending')) + '</span>&nbsp;' +
				                 '</span>';

                    }

                    var item = $(sqlline).hide();
                    $('[class=addnewsql' + menutype + '][id=9999]').before(item);
                    if (opts.animate) $(item).animate({ opacity: "show", height: "show" }, 150, "swing", function () { $(item).animate({ height: "+=3px" }, 75, "swing", function () { $(item).animate({ height: "-=3px" }, 50, "swing"); onchangeevent('new'); }); });
                    else { $(item).show(); onchangeevent('new'); }



                    //on click edit value
                    if (countertype == 1) {


                        $("span[class=addnewsql" + menutype + "value][id='" + (opts.counters[countertype]) + "']").sqlsimplemenu({
                            menu: 'sortmenu',
                            fields: [
		                                            { name: 'Ascending' },
		                                            { name: 'Descending' }
		                                           ],
                            onselect: function (action, el) {
                                //alert(action+'---- val:'+$(el).text());
                                $(el).text(action == 0 ? 'Ascending' : 'Descending');
                                onchangeevent('change');
                            }
                        });




                    } else {
                        $("span[class=addnewsql" + menutype + "value][id='" + (opts.counters[countertype]) + "']").click(
				               function (e) {

				                   //debugger;

				                   e.stopPropagation();

				                   var element = $(this);


				                   var vhref = $('a[class=addnewsql' + menutype + '][id=' + element.attr('id') + ']').attr('href');
				                   var fieldid = vhref.split('#')[1];

				                   var slotid = element.attr('id');


				                   if (element.hasClass("editing") || element.hasClass("disabled")) {
				                       return;
				                   }

				                   element.addClass("editing");


				                   //in place edit...
				                   var oldhtml = $(this).html();

				                   $(this).html('<input type="text" class="editfield" id=99><span class="sqlsyntaxhelp"></span>');
				                   $('.editfield').val(oldhtml.replace(/^\s+|\s+$/g, ""));

				                   $('.editfield').blur(function () {
				                       element.html($(this).val().replace(/^\s+|\s+$/g, ""));
				                       element.removeClass("editing");
				                       element.attr("disabled", "disabled");
				                       $('.sqlsyntaxhelp').remove();
				                       onchangeevent('change');
				                   });

				                   $('.editfield', element).keyup(function (event) {
				                       if (event.which == 13) { // enter
				                           element.html($(this).val());
				                           element.removeClass("editing");
				                           element.removeAttr("disabled");
				                           $('.sqlsyntaxhelp').remove();
				                           onchangeevent('change');
				                       }
				                       return true;
				                   });
				                   element.attr("disabled", "disabled");

				                   $('input[class=editfield][id=99]').focus().select();

				               });
                    }


                    //on click delete remove p for the condition...
                    //debugger;
                    $("[class=addnewsql" + menutype + "delete][id='" + (opts.counters[countertype]) + "']").click(
				               function () {
				                   var item = $('span[class=sql' + menutype + '][id=' + $(this).attr('id') + ']');
				                   if (opts.animate) $(item).animate({ opacity: "hide", height: "hide" }, 150, "swing", function () { $(this).hide().remove(); onchangeevent('change'); });
				                   else { $(item).hide().remove(); onchangeevent('change'); }
				               });



                    //add a menu to newly added operator
                    $("[class=addnewsql" + menutype + "][id='" + (opts.counters[countertype]) + "']").sqlsimplemenu({
                        menu: 'sqlmenulist',
                        fields: opts.fields,
                        onselect: function (action, el) {

                            $("[class=addnewsql" + menutype + "][id=" + $(el).attr('id') + "]")
				            .html(opts.fields[action].name)
				            .attr('href', "#" + action);
                            onchangeevent('change');

                        }
                    });


                    opts.counters[countertype]++;
                    //if(iscolumn) opts.columncount++; else opts.sortcount++;


                }

            }); //end of column handling....

 
           //Creates TextBox or Selectboxes as needed based on the field type and column type
           function getSQLWhereValueHtml(col_slot, op_slot, column_value, counter_id)
           {
        	   if(opts.operators[op_slot].name == 'IsNull' || opts.operators[op_slot].name == 'NotNull')
               {
        		   return '';
               }
        	   
               var valstr='';
               var vals;
               var col_val='';
               
               if(opts.operators[op_slot].multipleval)
               {
            	column_value =( column_value == '' ? 'and;endl': column_value);  
               	vals=column_value.split('and;endl');
               	col_val=vals[0];
               }
               else
               {
               	col_val=column_value;
               }
               if (opts.fields[col_slot].type == 'enum') {
               	
               	valstr = '<select class="addnewsqlwherevalue" id="' + counter_id + '_1">' + col_val + '>';
                   
                   var options = opts.fields[col_slot].values.replace('enum(', '');
                   options = options.replace( /s*$/, "" );//right trim
                   options = options.substring(0, options.length - 1);//remove last )
                   
                  
                   var myOptions = eval("[" + options + "]");

                   //alert(myOptions.length);

                   for (value = 0; value < myOptions.length; value++) {
                   	var myVal = myOptions[value];
                       if (col_val == myVal) {
                       	valstr += '<option value="' + value + '" selected="true">' + myVal + '</option>';
                       }
                       else {
                       	valstr += '<option value="' + value + '">' + myVal + '</option>';
                       }

                   };

                   valstr += '</select>&nbsp;'
                   if(opts.operators[op_slot].multipleval)
                   {
                   	col_val=vals[1];
                      	valstr += ' and <select class="addnewsqlwherevalue" id="' + counter_id + '_2">' + col_val + '>';
                       for (value = 0; value < myOptions.length; value++) {
                       	var myVal = myOptions[value];
                       	myVal = myVal.slice(1);
                       	myVal= myVal.substring(0, myVal.length - 1);
                           if (col_val == myVal) {
                           	valstr += '<option value="' + value + '" selected="true">' + myVal + '</option>';
                           }
                           else {
                           	valstr += '<option value="' + value + '">' + myVal + '</option>';
                           }

                       };
                       valstr += '</select>&nbsp;'
                   }
                   
                   

               }
//               else if (opts.fields[action].type == 'date') {
//               	valstr = '<input type="text" class="addnewsqlwherevalue" id="' + counter_id + '_1"/>&nbsp;';
//               	 if(opts.operators[(defval ? defval.opslot : 0)].multipleval)
//                    {
//                    	col_val=vals[1];
//                    	valstr += ' and <input type="text" class="addnewsqlwherevalue" id="' + counter_id + '_2" value="' + col_val + '" />&nbsp;';
//                    }
//               }
               else {
                   valstr = '<input type="text" class="addnewsqlwherevalue" id="' + counter_id + '_1" value="' + col_val + '" />&nbsp;';
                   if(opts.operators[op_slot].multipleval)
                   {
                   	col_val=vals[1];
                   	valstr += ' and <input type="text" class="addnewsqlwherevalue" id="' + counter_id + '_2" value="' + col_val + '" />&nbsp;';
                   }
               }
//             if (opts.fields[action].type == 'date') {
//             $('#' + counter_id + '_1').jdPicker();
//             if(opts.operators[(defval ? defval.opslot : 0)].multipleval)
//             {
//             	$('#' + counter_id + '_2').jdPicker();
//             }
//             }
    
               
               return valstr;

           }
           
           
             function indent() {
        	   var padleft = 0;
        	   var inc = 30;
               $('span.sqlwhere').each(function () {
            	   var chainTag = $(this).find('a.addnewsqlwherechain').text();
            	   var index = $(this).find('a.addnewsqlwhere').attr('id');

                   
                   var span1Tag = $(this).find('span.sqlwhere1');
                   span1Tag.css("padding-left", (parseInt(padleft)));
                   var span2Tag = $(this).find('span.sqlwhere2');
                   span2Tag.css("padding-left", (parseInt(padleft)));
                   if (chainTag.indexOf(')') != -1) {
                	   span2Tag.css("padding-left", (parseInt(padleft) - inc));
                   }
                   
            	   if (chainTag.indexOf('(') != -1) {
                       padleft+=inc;
                   }
                   if (chainTag.indexOf(')') != -1) {
                       padleft-=inc;
                   }
                 
               });
               
             
           }

           
           function createSQLWhereValueChangeEvents(i)
           {
        	   var counter_id = i;
               $("input[class=addnewsqlwherevalue][id='" + counter_id + "_1']").click(
		               function (e) {
		                   $('.addnewsqlwherevalue').blur(function () {
		                       $('.sqlsyntaxhelp').remove();
		                       onchangeevent('change');



		                   });

		               });
            $("input[class=addnewsqlwherevalue][id='" + counter_id + "_2']").click(
		               function (e) {
		                   $('.addnewsqlwherevalue').blur(function () {
		                       $('.sqlsyntaxhelp').remove();
		                       onchangeevent('change');



		                   });

		               });

            $("select[class=addnewsqlwherevalue][id='" + counter_id + "_1']").change(
		               function (e) {
		            	   
		                   $('.addnewsqlwherevalue').blur(function () {
		                       $('.sqlsyntaxhelp').remove();
		                       onchangeevent('change');



		                   });

		               });
            

            $("select[class=addnewsqlwherevalue][id='" + counter_id + "_2']").change(
		               function (e) {
		            	   
		                   $('.addnewsqlwherevalue').blur(function () {
		                       $('.sqlsyntaxhelp').remove();
		                       onchangeevent('change');



		                   });

		               });


           }

           
           function createSQLWhereDeleteEvent(counter_id)
           {
               //on click delete remove p for the condition...
               $("[class=addnewsqlwheredelete][id='" + counter_id + "']").click(
			               function () {
			                   var item = $('span[class=sqlwhere][id=' + $(this).attr('id') + ']');
			                   if (opts.animate) $(item).animate({ opacity: "hide", height: "hide" }, 150, "swing", function () { $(this).hide().remove(); onchangeevent('change'); });
			                   else { $(item).hide().remove(); onchangeevent('change'); }

			               });
           }
           
           function createSQLWhereColumnEvent(counter_id)
           {
               //add a menu to newly added operator
               $("[class=addnewsqlwhere][id='" + counter_id + "']").sqlsimplemenu({
                   menu: 'sqlmenulist',
                   fields: opts.fields,
                   onselect: function (action, el, defval) {

                       $("[class=addnewsqlwhere][id=" + $(el).attr('id') + "]")
			            .html(opts.fields[action].name)
			            .attr('href', "#" + action);
                        var op_slot1 = $("[class=addnewsqlwhereoperator][id=" + $(el).attr('id') + "]")
			            .attr('href').substr(1);
                        var col_slot1 = action;
                        var counter_id1 = $(el).attr('id');
                        var valstr = getSQLWhereValueHtml(col_slot1, op_slot1, "", counter_id1);
                        $("[class=divnewsqlwherevalue][id=" + $(el).attr('id') + "]").html(valstr);
                        createSQLWhereValueChangeEvents(counter_id1);
                        onchangeevent('change');

                   }
               });
           }
           
           //We can exclude operators with this function based on column type
           function getOperatorsExclusion(column_type)
           {
        	   var exclusion = [];
        	   switch(column_type)
        	   {
        	   case 'int':
        	   case 'tinyint':
        	   case 'enum':
        	   case 'datetime':
        	   case 'date':
        		   exclusion = [
        	        			 { name: 'Contains'},
        	        			 { name: 'NotContains'},
        	        			 { name: 'IsIn'},
        	        			 { name: 'IsNotIn'},
        	        			 { name: 'Regex'},
        	        			 { name: 'NotRegex'}
        	        			 ];
        		   break;
        	   case 'char':
        	   case 'varchar':
        	   case 'text':
        		   exclusion = [
       	        			 { name: 'BiggerThan'},
       	        			 { name: 'BiggerOrEqualTo'},
       	        			 { name: 'SmallerThan'},
       	        			 { name: 'SmallerOrEqualTo'},
       	        			 { name: 'InBetween'},
       	        			 { name: 'NotInBetween'}
       	        			 ];
        	    break;
        	   default:
        		break;
        	   }
        	   return exclusion;
           }
           
           function createSQLWhereOperatorEvent(counter_id, column_type)
           {
        	   var exclusion = getOperatorsExclusion(column_type);
        	   
               $("[class=addnewsqlwhereoperator][id='" + counter_id + "']").sqlsimplemenu({
                   menu: 'operatorlist',
                   fields: opts.operators,
                   exclusion: exclusion,
                   onselect: function (action, el) {
                 $("[class=addnewsqlwhereoperator][id=" + $(el).attr('id') + "]")
		            .html(opts.operators[action].displayname)
		            .attr('href', "#" + action);
                  var col_slot1 = $("[class=addnewsqlwhere][id=" + $(el).attr('id') + "]")
		            .attr('href').substr(1);
                  var op_slot1 = action;
                  var counter_id1 = $(el).attr('id');
                  var valstr = getSQLWhereValueHtml(col_slot1, op_slot1, "", counter_id1);
                 $("[class=divnewsqlwherevalue][id=" + $(el).attr('id') + "]").html(valstr);
                 createSQLWhereValueChangeEvents(counter_id1);
                 onchangeevent('change');


               }
                });
           }
           
           function createSQLWhereChainEvent(counter_id)
           {
               //add a menu to newly added chain
               $("[class=addnewsqlwherechain][id='" + counter_id + "']").sqlsimplemenu({
                   menu: 'chainlist',
                   fields: opts.chain,
                   onselect: function (action, el) {
                       $("[class=addnewsqlwherechain][id=" + $(el).attr('id') + "]")
			            .html(opts.chain[action].name)
			            .attr('href', "#" + action);
                       onchangeevent('change');
                       indent();
                   }
               });

           }

           function getSQLWhereLine(col_slot, op_slot, chain_slot, column_value, counter_id)
           {
          	    var valstr = getSQLWhereValueHtml(col_slot, op_slot, column_value, counter_id);
        	    var sqlwherevalue = '<div class="divnewsqlwherevalue" id="' + counter_id + '">' + valstr + '</div>&nbsp;';
        	    var sqlline =
			                 '<span class="sqlwhere" id=' + counter_id + '>' +
                             '<span class="sqlwhere1" id=' + counter_id + '>' +
                             '<a class="addnewsqlwhereadd" id=' + counter_id + ' href="#' + col_slot + '">' + '<img src="images/add.gif" style="border:none;">' + '</a>&nbsp;' +
			                 '<a class="addnewsqlwheredelete" id=' + counter_id + ' href="#' + col_slot + '">' + opts.deletetext + '</a>&nbsp;' +
			                 '<div class="divnewsqlwhere" id=' + counter_id + '>' +
			                '<a class="addnewsqlwhere" id=' + counter_id + ' href="#' + col_slot + '">' + opts.fields[col_slot].name + '</a></div>&nbsp;' +
			                '<div class="divnewsqlwhereoperator" id=' + counter_id + '>' +
			                 '<a class="addnewsqlwhereoperator" id=' + counter_id + ' href="#' + op_slot + '">' + opts.operators[op_slot].displayname + '</a></div>&nbsp;' +
			                 sqlwherevalue +
                             '</span><br />' +
                             '<span class="sqlwhere2" id=' + counter_id + '>' +
			                 '<a class="addnewsqlwherechain" id=' + counter_id + ' href="#' + chain_slot + '">' + opts.chain[chain_slot].name + '</a>&nbsp;' +
                              '</span>' +
			                 '</span>';
                return sqlline;
           }
           
           function createAddSQLWhereAddEvent(parent_counter_id)
           {
               $("[class=addnewsqlwhereadd][id='" + parent_counter_id + "']").sqlsimplemenu({
                   menu: 'sqlmenulist',
                   fields: opts.fields,
                   onselect: function (action, el, defval) {
                       //                    debugger;
               	    var counter_id = opts.counters[3];
               	    var col_slot = action;
               	    var op_slot = (defval ? defval.opslot : 0);
               	    var column_value = (defval ? defval.columnvalue : opts.fields[action].defaultval);
               	     var col_type = opts.fields[col_slot].type;
                       var chain_slot = (defval ? defval.chainslot : '0') ;
                       var sqlline = getSQLWhereLine(col_slot, op_slot, chain_slot, column_value, counter_id);
                       var item = $(sqlline).hide();
                       $("[class=sqlwhere][id='" + parent_counter_id + "']").after(item);
                       if (opts.animate) $(item).animate({ opacity: "show", height: "show" }, 150, "swing", function () { $(item).animate({ height: "+=3px" }, 75, "swing", function () { $(item).animate({ height: "-=3px" }, 50, "swing", function () { onchangeevent('new'); }); }); });
                       else { $(item).show(); onchangeevent('new'); }

                      
                       createSQLWhereValueChangeEvents(counter_id);
                       createSQLWhereDeleteEvent(counter_id);
                       createSQLWhereColumnEvent(counter_id);
                       createSQLWhereOperatorEvent(counter_id, col_type);
                       createSQLWhereChainEvent(counter_id);
                       createAddSQLWhereAddEvent(counter_id);
                       indent();
                       opts.counters[3]++; //where counters...


                   }

               }); /*end of where handling....*/
        	   
           }


            /*************************************************************************************************************/
            //where click handling is here..... 
            $("[class=addnewsqlwhere][id=9999]").sqlsimplemenu({
                menu: 'sqlmenulist',
                fields: opts.fields,
                onselect: function (action, el, defval) {
                    //                    debugger;
            	    var counter_id = opts.counters[3];
            	    var col_slot = action;
            	    var op_slot = (defval ? defval.opslot : 0);
            	    var column_value = (defval ? defval.columnvalue : opts.fields[action].defaultval);
            	    var col_type = opts.fields[col_slot].type;
                    var chain_slot = (defval ? defval.chainslot : '0') ;
                    var sqlline = getSQLWhereLine(col_slot, op_slot, chain_slot, column_value, counter_id, counter_id);
                    var item = $(sqlline).hide();
                    $('[class=addnewsqlwhere][id=9999]').before(item);
                    
                    if (opts.animate) $(item).animate({ opacity: "show", height: "show" }, 150, "swing", function () { $(item).animate({ height: "+=3px" }, 75, "swing", function () { $(item).animate({ height: "-=3px" }, 50, "swing", function () { onchangeevent('new'); }); }); });
                    else { $(item).show(); onchangeevent('new'); }

                   
                    createSQLWhereValueChangeEvents(counter_id);
                    createSQLWhereDeleteEvent(counter_id);
                    createSQLWhereColumnEvent(counter_id);
                    createSQLWhereOperatorEvent(counter_id, col_type);
                    createSQLWhereChainEvent(counter_id);
                    createAddSQLWhereAddEvent(counter_id);
                    indent();
                    opts.counters[3]++; //where counters...


                }

            }); /*end of where handling....*/








        });
    };

})(jQuery);