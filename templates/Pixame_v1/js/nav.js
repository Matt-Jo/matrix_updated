function initPage()
{
	var navRoot = document.getElementById("nav");
	var lis = navRoot.getElementsByTagName("li");
	for (var i=0; i<lis.length; i++)
	{
		lis[i].onmouseover = function()
		{
			this.className += " hover";
                }
		lis[i].onmouseout = function()
		{
//			this.className = this.className.replace(new RegExp("hover"),"");
            this.className = "";
		}
	}

    for (var i=0; i < navRoot.childNodes.length; i++){
        var elem = navRoot.childNodes[i];
        if(elem.nodeName == 'LI' || elem.nodeName == 'li'){
            elem.onclick = function(){
                this.className += " hover";
            }
        }
    }
}
