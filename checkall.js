function checkAll()
{
	var divs = document.getElementsByTagName("input");
	for(var i = 0; i < divs.length; i++)
	{
		if(divs[i].type == 'checkbox' && divs[i].className == 'dispCheck')
		{
			divs[i].checked = 'checked';
		}
	}
}

function uncheckAll()
{
	var divs = document.getElementsByTagName("input");
	for(var i = 0; i < divs.length; i++)
	{
		if(divs[i].type == 'checkbox' && divs[i].className == 'dispCheck')
		{
			divs[i].checked = '';
		}
	}
}