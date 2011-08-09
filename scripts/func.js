$(document).ready(function(){
	$('.upms').hide();
	
});

function sh(obj,key)
{
	var dir = $(obj).css('background-image').toString().search(/up.png/i);
	if(dir>0)
		{
			dir = 'url(\'./images/down.png\')';
		}
	else
		{
			dir = 'url(\'./images/up.png\')';
		}
	$(obj).css('background-image',dir);
	$('.upms.'+key).toggle();
}