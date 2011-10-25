function delsure(){ return confirm("Deleting an item will purge all associated data. Are you sure?"); }

function chkbox(nocount,klass,deletename){
	if(klass==undefined)
		klass='delrep';
	if(deletename==undefined)
		deletename='reports';	
	count = 0;
	$('.'+klass).each(function(){
		if(this.checked==true)
		count ++;
	});
	if(count == 0 && nocount!=0 && klass!='delsearch')
		{
		alert('No '+deletename+' selected.');
		return false;
		}
	if(count > 0 )
	return confirm("Deleting an item will purge all associated data. Are you sure?");
}