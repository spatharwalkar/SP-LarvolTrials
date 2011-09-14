var progressId = null;
var hasConnected = false;

var path = document.location.toString();
path = path.substring(0, path.lastIndexOf('/'));
path += '/progress/progress.php';

function updateProgress(what)
{
	if(!what) what = 'parse';
	document.getElementById('success').innerHTML = '';
	var temp = document.getElementById('error');
	if(temp != null) temp.innerHTML = '';
	temp = document.getElementById('krumo');
	if(temp != null) temp.innerHTML = '';
	if(progressId == null)
	{
		var req = new XMLHttpRequest();  
		req.open('GET', path + '?what=' + what, false);   
		req.send(null);  
		if(req.status == 200)
		{
			var resp = eval(req.responseText);	//Response is JSON
			if(resp.fail == 0)
			{
				progressId = resp.id;
			}
		}else{
			setTimeout('updateProgress("' + what + '")',1000);
			return;
		}
	}
	
	var req = new XMLHttpRequest();
	req.open('GET', path + '?id=' + progressId, true);
	req.onreadystatechange = function (aEvt)
	{
		if(req.readyState == 4)
		{
			if(req.status == 200)
			{
				var resp = eval(req.responseText);	//Response is JSON
				var prodiv = document.getElementById('progress');
				if(resp.progress == null || resp.fail != 0)
				{
					prodiv.style.width = '0';
					if(hasConnected)
					{
						prodiv.innerHTML = 'Done.';
						return;
					}else{
						prodiv.innerHTML = 'Preparing Download...';
					}
				}else{
					hasConnected = true;
					prodiv.style.width = Math.round(resp.progress/resp.maximum*100) + '%';
					prodiv.innerHTML = resp.progress + ' of ' + resp.maximum;
				}
				
			}else{
				document.getElementById('progress').innerHTML = '?';
			}
		}
	};
	req.send(null);
	setTimeout('updateProgress("' + what + '")',1000);
}
