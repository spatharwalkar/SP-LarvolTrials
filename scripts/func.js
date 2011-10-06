$(document).ready(function(){
						   
	$('#addtoright').after('&nbsp;&nbsp;&nbsp;<span id="addedtoright" >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;All Milestones</span>');
	var image;
	if($('#upmstyle').val() == 'expand') {
		image = 'down.png';
	} else {
		$('.upms').hide();
		image = 'up.png';
	}
	
	$('#addedtoright').css('background-image','url(\'./images/'+image+'\')')
	.css('background-repeat','no-repeat')
	.css('background-position','left center')
	.css('border','1px solid')
	.css('padding','2px')
	.css('margin-left','200px');
	
	if($('.trialtitles').length>0)
	{
		$('#addedtoright').attr('onclick','sh(this,0,1)').css('cursor','hand');	
	}
	else
	{
		$('#addedtoright').css('background-color','#DDDDDD').css('cursor','default').css('color','#777777');
	}	
	//help tab
	var slideout = '<div class="slide-out-div"><a class="handle" href="#help">Content</a><table cellpadding="0" cellspacing="0" class="table-slide"> <tr><td><img src="images/black-diamond.png"/></td><td>Click to view data release</td></tr> <tr><td><img src="images/red-diamond.png"/></td><td>Indicates new data relase. Click to view details</td></tr> <tr><td><img src="images/hourglass.png"/></td><td>Event has occured with results pending</td></tr> <tr><td><img src="images/lbomb.png"/></td><td>Click for anticipated milestone details</td></tr> <tr><td><img src="images/down.png"/></td><td>Click for additional milestones</td></tr> </table> </div> ';
	$('body').append(slideout);	
    $(function(){
        $('.slide-out-div').tabSlideOut({
            tabHandle: '.handle',                     //class of the element that will become your tab
            pathToTabImage: 'images/help.png', //path to the image for the tab //Optionally can be set using css
            imageHeight: '106px',                     //height of tab image           //Optionally can be set using css
            imageWidth: '25px',                       //width of tab image            //Optionally can be set using css
            tabLocation: 'right',                      //side of screen where tab lives, top, right, bottom, or left
            speed: 100,                               //speed of animation
            action: 'click',                          //options: 'click' or 'hover', action to trigger animation
            topPos: '50px',                          //position from the top/ use if tabLocation is left or right
            leftPos: '20px',                          //position from left/ use if tabLocation is bottom or top
            fixedPosition: true                     //options: true makes it stick(fixed position) on scroll
        });

    });	
	
});

function sh(obj,key,all)
{
	var updown = $(obj).css('background-image').toString().search(/up.png/i);
	if(updown>0)
		{
			dir = 'url(\'./images/down.png\')';
		}
	else
		{
			dir = 'url(\'./images/up.png\')';
		}
	if(all==undefined)
	{	
		$(obj).css('background-image',dir);
		$('#addedtoright').css('background-image',scansh());
		if(updown>0)
		{	
			$('.upms.'+key).show();
		}
		else
		{
			$('.upms.'+key).hide();	
		}
	}
	if(all==1)
	{
		$('.upmpointer').css('background-image',dir);
		$('#addedtoright').css('background-image',scansh());
		if(updown>0)
		$('.upms').show();
		else
		$('.upms').hide();
	}
}
function scansh()
{
	var upflag=0;
	var downflag=0;
	$('.upmpointer').each(function(){
		var dir = $(this).css('background-image').toString().search(/up.png/i);
		if(dir>0)
			{
				upflag=1;
				dir = 'url(\'./images/down.png\')';
			}
		else
			{
				downflag=1;
				dir = 'url(\'./images/up.png\')';
			}
	});
	if(downflag==1)
		{
			dir = 'url(\'./images/down.png\')';
			return dir;
		}
	if(upflag==1)
	{
		dir = 'url(\'./images/up.png\')';
		return dir;
	}	

}