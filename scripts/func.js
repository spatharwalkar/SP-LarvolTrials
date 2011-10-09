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
		$('#addedtoright').click(function(){sh(this,0,1);}).css('cursor','pointer');	
	}
	else
	{
		$('#addedtoright').css('background-color','#DDDDDD').css('cursor','default').css('color','#777777');
	}	
	//help tab
	var slideout = '<div class="slide-out-div"><a class="handle" href="#help">Content</a><table cellpadding="0" cellspacing="0" class="table-slide"> <tr><td><img src="images/black-diamond.png"/></td><td>Click for data release</td></tr> <tr><td><img src="images/red-diamond.png"/></td><td>Click for data release (new)</td></tr> <tr><td><img src="images/hourglass.png"/></td><td>Results pending</td></tr> <!--<tr><td><img src="images/lbomb.png"/></td><td>Click for anticipated milestone details</td></tr>--> <tr><td><img src="images/down.png"/></td><td>Click for additional milestones</td></tr> </table> </div> ';
	$('body').append(slideout);	
    $(function(){
        $('.slide-out-div').tabSlideOut();

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