var columnHeaders = getCSSRule('.col div');
var rowHeaders = getCSSRule('.row div');
var sections = getCSSRule('.hmdata td.sect div');
var spacer = getCSSRule('.spc div');
var columnCategories = getCSSRule('.cat div');
window.onscroll = function (oEvent) {
    columnHeaders.style.top = (document.documentElement.scrollTop || (document.body.scrollTop/2))+'px';
	columnCategories.style.top = (document.documentElement.scrollTop || (document.body.scrollTop))+'px';
    rowHeaders.style.left = (document.documentElement.scrollLeft || document.body.scrollLeft)+'px';
    sections.style.left = (document.documentElement.scrollLeft || document.body.scrollLeft)+'px';
	spacer.style.top = (document.documentElement.scrollTop || (document.body.scrollTop))+'px';
	spacer.style.left = (document.documentElement.scrollLeft || document.body.scrollLeft)+'px';
  }

function timeEnum($timerange)
{
	switch($timerange)
	{
		case 0: $timerange = "now"; break;
		case 1: $timerange = "1 week"; break;
		case 2: $timerange = "2 weeks"; break;
		case 3: $timerange = "1 month"; break;
		case 4: $timerange = "1 quarter"; break;
		case 5: $timerange = "6 months"; break;
		case 6: $timerange = "1 year"; break;
	}
	return $timerange;
}

$(function() 
{
	$("#slider-range-min").slider({	//Double Slider - For LoggedIN Users
		range: false,
		min: 0,
		max: 6,
		step: 1,
		values: [ 0, 3 ],
		slide: function(event, ui) {
			if(ui.values[0] > ui.values[1])/// Switch highlight range when sliders cross each other
			{
				$("#startrange").val(timeEnum(ui.values[1]));
				$("#endrange").val(timeEnum(ui.values[0]));
			}else{
				$("#startrange").val(timeEnum(ui.values[0]));
				$("#endrange").val(timeEnum(ui.values[1]));
			}
		},
		change: function(event, ui) { updatechanges(); }
	});
});

function updatechanges()
{
	var hm = document.getElementById('mainhm');
	var start = normalizeDuration(document.getElementById("startrange").value);
	var end = normalizeDuration(document.getElementById("endrange").value);
	var section=0;
	for(var row = 0; row < (hm.rows.length-2); ++row)
	{
		if(row-section>=changes.length) break;
		if(hm.rows[row+2].cells[0].className == 'sect')
		{
			++section;
			continue;
		}
		for(var cell = 0; cell < (hm.rows[row+2].cells.length-1); ++cell)
		{
			var ch = changes[row-section][cell];
			if(ch == null) continue;
			var borders = [];
			if(ch.hasOwnProperty('b') && ch.b <= start && ch.b >= end) borders.push('bex');
			if(ch.hasOwnProperty('f') && ch.f <= start && ch.f >= end) borders.push('fex');
			if(ch.hasOwnProperty('e') && ch.e <= start && ch.e >= end) borders.push('ex');
			if(ch.hasOwnProperty('p') && ch.p <= start && ch.p >= end) borders.push('pha');
			if(borders.length > 0) $(hm.rows[row+2].cells[cell+1]).addClass('ch'); else $(hm.rows[row+2].cells[cell+1]).removeClass('ch');
			if(hm.rows[row+2].cells[cell+1].children.length > 1)
			{
				var over = hm.rows[row+2].cells[cell+1].children[1];
				for(var change = 0; change < over.children.length; ++change)
				{
					var chclass = over.children[change].className + "";
					chclass = chclass.replace('ch','').replace(' ','');
					if($.inArray(chclass, borders) >= 0)
					{
						$(over.children[change]).addClass('ch');
						if(chclass == 'pha') over.children[change].style.display="";
					}else{
						$(over.children[change]).removeClass('ch');
						if(chclass == 'pha') over.children[change].style.display="none";
					}
				}
			}
		}
	}
}

function updateviewmode()
{
	var hm = document.getElementById('mainhm');
	var mode = document.getElementById('viewmode').value;
	var section=0;
	for(var row = 0; row < (hm.rows.length-2); ++row)
	{
		if(row-section>=cells_active_industry.length) break;
		if(hm.rows[row+2].cells[0].className == 'sect')
		{
			++section;
			continue;
		}
		for(var cell = 0; cell < (hm.rows[row+2].cells.length-1); ++cell)
		{
			var value = '&nbsp;';
			switch(mode)
			{
				case 'ai':  value = cells_active_industry[row-section][cell]; break;
				case 'aos': value = cells_active_os[row-section][cell]; break;
				case 'act': value = cells_active[row-section][cell]; break;
				case 'all': value = cells_all[row-section][cell]; break;
			}
			if(value == null) value='&nbsp;';
			if(hm.rows[row+2].cells[cell+1].children.length>0)
				hm.rows[row+2].cells[cell+1].children[0].innerHTML = value;
		}
	}
}
