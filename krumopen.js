var divs = document.getElementsByTagName("div");
for(var i = 0; i < divs.length; i++)
{
	if(divs[i].className == 'krumo-element krumo-expand')
		krumo.toggle(divs[i]);
}
