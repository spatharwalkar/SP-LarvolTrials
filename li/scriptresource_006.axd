﻿Type.registerNamespace("Telerik.Web.UI");
Telerik.Web.UI.ScrollerOrientation=function(){};
Telerik.Web.UI.ScrollerOrientation.prototype={Vertical:0,Horizontal:1};
Telerik.Web.UI.ScrollerOrientation.registerEnum("Telerik.Web.UI.ScrollerOrientation");
Telerik.Web.UI.ScrollerSpeed=function(){};
Telerik.Web.UI.ScrollerSpeed.prototype={Invalid:0,Slow:1,Medium:2,Fast:3};
Telerik.Web.UI.ScrollerSpeed.registerEnum("Telerik.Web.UI.ScrollerSpeed");
Telerik.Web.UI.ArrowPosition=function(){};
Telerik.Web.UI.ArrowPosition.prototype={Top:0,Bottom:1,Left:2,Right:3};
Telerik.Web.UI.ArrowPosition.registerEnum("Telerik.Web.UI.ArrowPosition");
Telerik.Web.UI.Scroller=function(b,c,a){this._timerInterval=10;
this._scrolledElement=b;
this._element=c;
this._orientation=a;
this._minPosition=0;
this._maxPosition=null;
this._currentPosition=0;
this._speed=Telerik.Web.UI.ScrollerSpeed.Invalid;
this._direction=0;
this._events=null;
this._timer=null;
this._onTickDelegate=null;
};
Telerik.Web.UI.Scroller.prototype={initialize:function(){this._onTickDelegate=Function.createDelegate(this,this._onTick);
this._timer=new Telerik.Web.Timer();
this._timer.set_interval(this._timerInterval);
this._timer.add_tick(this._onTickDelegate);
},dispose:function(){if(this._timer){this._timer.dispose();
}this._onTickDelegate=null;
this._events=null;
},get_element:function(){return this._element;
},get_events:function(){if(!this._events){this._events=new Sys.EventHandlerList();
}return this._events;
},add_positionChanged:function(a){this.get_events().addHandler("positionChanged",a);
},remove_positionChanged:function(a){this.get_events().removeHandler("positionChanged",a);
},setScrollingLimits:function(a,b){this._minPosition=a;
this._maxPosition=Math.min(this._getElementSize(),b);
},isAtMinPosition:function(){return this._currentPosition<=this._minPosition;
},isAtMaxPosition:function(){return this._currentPosition>=this._maxPosition;
},resetState:function(){this._resetOverflowStyle();
this._scrollTo(0);
},startScroll:function(b,a){this._speed=b;
this._direction=a;
this._timer.set_enabled(true);
},changeScrollSpeed:function(a){this._speed=a;
},stopScroll:function(){this._speed=Telerik.Web.UI.ScrollerSpeed.Invalid;
this._direction=0;
this._timer.set_enabled(false);
},scrollToMaxPosition:function(){this._scrollTo(this._maxPosition);
},_onTick:function(){var a=this._currentPosition+(this._direction*this._speed);
a=Math.max(a,this._minPosition);
a=Math.min(a,this._maxPosition);
this._scrollTo(a);
if(a==this._minPosition||a==this._maxPosition){this.stopScroll();
}},_scrollTo:function(b){var a="left";
if(this._orientation==Telerik.Web.UI.ScrollerOrientation.Vertical){a="top";
}this._currentPosition=b;
this._scrolledElement.style[a]=-b+"px";
this._raiseEvent("positionChanged",Sys.EventArgs.Empty);
},_resetOverflowStyle:function(){if($telerik.isIE){this._element.style.overflow="visible";
if(this._orientation==Telerik.Web.UI.ItemFlow.Vertical){this._element.style.overflowX="visible";
this._element.style.overflowY="hidden";
}else{this._element.style.overflowX="hidden";
this._element.style.overflowY="hidden";
}}else{this._element.style.overflow="hidden";
}},_getElementSize:function(){if(this._orientation==Telerik.Web.UI.ScrollerOrientation.Vertical){return this._scrolledElement.offsetHeight;
}else{return this._scrolledElement.offsetWidth;
}},_raiseEvent:function(b,c){var a=this.get_events().getHandler(b);
if(a){if(!c){c=Sys.EventArgs.Empty;
}a(this,c);
}}};
Telerik.Web.UI.Scroller.registerClass("Telerik.Web.UI.Scroller",null,Sys.IDisposable);
