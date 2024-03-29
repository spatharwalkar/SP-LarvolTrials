﻿Type.registerNamespace("Telerik.Web.UI");
$telerik.findMultiPage=$find;
$telerik.toMultiPage=function(a){return a;
};
Telerik.Web.UI.RadPageViewCollection=function(a){this._owner=a;
this._data=[];
};
Telerik.Web.UI.RadPageViewCollection.prototype={get_count:function(){return this._data.length;
},_add:function(a){this._insert(this.get_count(),a);
},_insert:function(b,a){Array.insert(this._data,b,a);
a._multiPage=this._owner;
},insert:function(b,a){this._insert(b,a);
this._owner._onPageViewInserted(b,a);
},add:function(a){this.insert(this.get_count(),a);
},getPageView:function(a){return this._data[a]||null;
},removeAt:function(b){var a=this.getPageView(b);
if(a){this.remove(a);
}},remove:function(a){this._owner._onPageViewRemoving(a);
a.unselect();
Array.remove(this._data,a);
this._owner._onPageViewRemoved(a);
}};
Telerik.Web.UI.RadPageViewCollection.registerClass("Telerik.Web.UI.RadPageViewCollection");
Telerik.Web.UI.RadPageView=function(a){this._element=a;
this._defaultButton="";
};
Telerik.Web.UI.RadPageView.prototype={initialize:function(){if(this.get_defaultButton()){this._onKeyPressDelegate=Function.createDelegate(this,this._onKeyPress);
$addHandler(this._element,"keypress",this._onKeyPressDelegate);
}},dispose:function(){if(this._onKeyPressDelegate){$removeHandler(this._element,"keypress",this._onKeyPressDelegate);
}},_onKeyPress:function(a){return WebForm_FireDefaultButton(a.rawEvent,this.get_defaultButton());
},_select:function(b){var a=this.get_multiPage();
if(!a){this._cachedSelected=true;
return;
}a._selectPageViewByIndex(this.get_index(),b);
},hide:function(){var a=this.get_element();
if(!a){return;
}Sys.UI.DomElement.addCssClass(a,"rmpHiddenView");
a.style.display="none";
},show:function(){var a=this.get_element();
if(!a){return;
}Sys.UI.DomElement.removeCssClass(a,"rmpHiddenView");
a.style.display="block";
if(this._repaintCalled){return;
}$telerik.repaintChildren(this);
this._repaintCalled=true;
},get_element:function(){return this._element;
},get_index:function(){return Array.indexOf(this.get_multiPage().get_pageViews()._data,this);
},get_id:function(){return this._id;
},set_id:function(a){this._id=a;
if(this.get_element()){this.get_element().id=a;
}},get_multiPage:function(){return this._multiPage||null;
},get_selected:function(){return this.get_multiPage().get_selectedPageView()==this;
},set_selected:function(a){if(a){this.select();
}else{this.unselect();
}},get_defaultButton:function(){return this._defaultButton;
},set_defaultButton:function(a){this._defaultButton=a;
},select:function(){this._select();
},unselect:function(){if(this.get_selected()){this.get_multiPage().set_selectedIndex(-1);
}}};
Telerik.Web.UI.RadPageView.registerClass("Telerik.Web.UI.RadPageView");
Telerik.Web.UI.RadMultiPage=function(a){Telerik.Web.UI.RadMultiPage.initializeBase(this,[a]);
this._pageViews=new Telerik.Web.UI.RadPageViewCollection(this);
this._selectedIndex=-1;
this._pageViewData=null;
this._changeLog=[];
};
Telerik.Web.UI.RadMultiPage.prototype={_logInsert:function(a){if(!this._trackingChanges){return;
}Array.add(this._changeLog,{type:1,index:a.get_index()});
},_logRemove:function(a){if(!this._trackingChanges){return;
}Array.add(this._changeLog,{type:2,index:a.get_index()});
},_onPageViewRemoving:function(a){this._logRemove(a);
},_onPageViewInserted:function(c,a){var d=a.get_element();
if(!d){d=a._element=document.createElement("div");
}d.style.display="none";
if(a.get_id()){d.id=a.get_id();
}var b=this.get_pageViews().getPageView(c+1);
var e=$get(this.get_clientStateFieldID());
if(b){e=b.get_element();
}this.get_element().insertBefore(d,e);
if(a._cachedSelected){a._cachedSelected=false;
a.select();
}this._logInsert(a);
},_onPageViewRemoved:function(a){if(a.get_element()){this.get_element().removeChild(a.get_element());
}},_selectPageViewByIndex:function(c,d){if(this._selectedIndex==c){return;
}if(!this.get_isInitialized()){this._selectedIndex=c;
return;
}if(c<-1||c>=this.get_pageViews().get_count()){return;
}var b=this.get_selectedPageView();
this._selectedIndex=c;
var a=this.get_selectedPageView();
if(!d){if(b){b.hide();
}if(a){a.show();
}}this.updateClientState();
},trackChanges:function(){this._trackingChanges=true;
},commitChanges:function(){this.updateClientState();
this._trackingChanges=false;
},get_pageViewData:function(){return this._pageViewData;
},set_pageViewData:function(a){this._pageViewData=a;
},initialize:function(){Telerik.Web.UI.RadMultiPage.callBaseMethod(this,"initialize");
var b=this.get_pageViewData();
for(var a=0;
a<b.length;
a++){var c=new Telerik.Web.UI.RadPageView($get(b[a].id));
c._id=b[a].id;
c.set_defaultButton(b[a].defaultButton);
this._pageViews._add(c);
c.initialize();
}},dispose:function(){Telerik.Web.UI.RadMultiPage.callBaseMethod(this,"dispose");
for(var b=0;
b<this.get_pageViews().get_count();
b++){var a=this.get_pageViews().getPageView(b);
a.dispose();
}},findPageViewByID:function(b){for(var a=0;
a<this.get_pageViews().get_count();
a++){var c=this.get_pageViews().getPageView(a);
if(c.get_id()==b){return c;
}}return null;
},get_pageViews:function(){return this._pageViews;
},get_selectedIndex:function(){return this._selectedIndex;
},set_selectedIndex:function(a){this._selectPageViewByIndex(a);
},get_selectedPageView:function(){return this.get_pageViews().getPageView(this.get_selectedIndex());
},saveClientState:function(){var a={};
a.selectedIndex=this.get_selectedIndex();
a.changeLog=this._changeLog;
return Sys.Serialization.JavaScriptSerializer.serialize(a);
}};
Telerik.Web.UI.RadMultiPage.registerClass("Telerik.Web.UI.RadMultiPage",Telerik.Web.UI.RadWebControl);
