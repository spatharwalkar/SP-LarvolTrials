window.onscroll = function (oEvent) {
    columnHeaders.style.top = (document.documentElement.scrollTop/2)+'px';
    columnCategories.style.top = (document.documentElement.scrollTop)+'px';
    rowHeaders.style.left = (document.documentElement.scrollLeft || document.body.scrollLeft)+'px';
    sections.style.left = (document.documentElement.scrollLeft || document.body.scrollLeft)+'px';
	spacer.style.top = (document.documentElement.scrollTop || (document.body.scrollTop))+'px';
	spacer.style.left = (document.documentElement.scrollLeft || document.body.scrollLeft)+'px';
  }