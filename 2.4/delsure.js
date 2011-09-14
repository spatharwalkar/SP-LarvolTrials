function delsure(){ return confirm("Deleting an item will purge all associated data. Are you sure?"); }

function chkbox(obj){
	count = 0;
	$('.delrep').each(function(){
		if(this.checked==true)
		count ++;
	});
	if(count == 0)
		{
		alert('No reports selected.');
		return false;
		}
	return confirm("Deleting an item will purge all associated data. Are you sure?");
}