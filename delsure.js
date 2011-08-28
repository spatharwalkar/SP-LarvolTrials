function delsure(){ return confirm("Deleting an item will purge all associated data. Are you sure?"); }

function chkbox(nocount,klass){
	if(klass==undefined)
		klass='delrep';
	count = 0;
	$('.'+klass).each(function(){
		if(this.checked==true)
		count ++;
	});
	if(count == 0 && nocount!=0)
		{
		alert('No reports selected.');
		return false;
		}
	if(count > 0 )
	return confirm("Deleting an item will purge all associated data. Are you sure?");
}