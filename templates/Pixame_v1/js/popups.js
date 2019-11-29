function initPopups()
{
	// hide all popups
	var sets = document.getElementsByTagName("div");
	for (var i = 0; i < sets.length; i++)
	{
		if (sets[i].className.indexOf("popup") != -1)
			sets[i].style.display = "none";
	}

	// get all popup raise links
	var _infolists = document.getElementsByTagName("ul");
	for (var j = 0; j < _infolists.length; j++)
	{
		if (_infolists[j].className.indexOf("item-list") != -1) {
			var _raiselinks = _infolists[j].getElementsByTagName("a");
			for (var k = 0; k < _raiselinks.length; k++)
			{
				if (_raiselinks[k].className.indexOf("quick-view") != -1) {
					_raiselinks[k].onclick =  function() {
						popup1.style.display = "block";
						return false;
					}
				}
			}
		}
	}

	// set actions for all close buttons
	var _closelinks = document.getElementsByTagName("a");
	for (var m = 0; m < _closelinks.length; m++)
	{
		if (_closelinks[m].className.indexOf("btn-close") != -1)
			_closelinks[m].onclick = function() {
				this.parentNode.parentNode.style.display = "none";
				return false;
			}
	}

	// set actions for popup opener links
	var _openerlinks = document.getElementsByTagName("a");
	for (var n = 0; n < _openerlinks.length; n++)
	{
		if (_openerlinks[n].className.indexOf("popup-opener") != -1)
			_openerlinks[n].onclick = function() {
				var _body = document.getElementsByTagName("body").item(0);
				
				if(this.className.indexOf("popup-link") > 0 ) {
					var _parentpopup = this.parentNode;
					var _cname = _parentpopup.className;
					
					while (_cname.indexOf("popup")<0)
					{
						if(_parentpopup.parentNode) {
							_parentpopup = _parentpopup.parentNode;
							_cname = _parentpopup.className;
						}
						else {
							break;
						}
					}
					_parentpopup.style.display = "none";
				}
				

				var _popup = document.getElementById(this.href.substr(this.href.indexOf("#") + 1));
				_popup.style.display = "block";
				return false;
			}
	}

}

if (window.addEventListener)
	window.addEventListener("load", initPopups, false);
else if (window.attachEvent && !window.opera)
	window.attachEvent("onload", initPopups);
