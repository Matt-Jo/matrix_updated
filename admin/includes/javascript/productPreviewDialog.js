// first we create the template string for the innerHTML portion of the widget
var ppd_html_string_template = "<table><tr><td valign=\"top\"><img src=\'%ppd_image_url%\' border=\'0\'/></td>" + 
								"<td valign=\"top\"><span style=\'font-size: 10px; font-family: arial;\'><b>%ppd_name%<br>$%ppd_price%</b><br><i>%ppd_description%</i></span></td></tr></table>";

var ppd_yui_panel = null;

var ppd_init = function(){	
	// now we create the YUI Panel that we will use for the preview dialog
	ppd_yui_panel = new YAHOO.widget.Panel("ppd", 
											{width:"300px", 
												visible:false,
												draggable:false, 
												close:true
											});
											
	ppd_yui_panel.setBody(" ");
	//now we insert the markup into the page
	ppd_yui_panel.render(document.body);
	
}

YAHOO.util.Event.addListener(window, "load", ppd_init);

//now we do the function to show the preview
function ppd_show(product_id, e){

	ppd_hide();
	
	//determine the location of the cursor for display purposes
	var posx = 0;
	var posy = 0;
	if (!e) e = window.event;
	if (e.pageX || e.pageY) 	{
		posx = e.pageX;
		posy = e.pageY;
	}
	else if (e.clientX || e.clientY) 	{
		posx = e.clientX + document.body.scrollLeft
			+ document.documentElement.scrollLeft;
		posy = e.clientY + document.body.scrollTop
			+ document.documentElement.scrollTop;
	}
	
	posx = posx + 150; //move the box 150px to the right of the cursor
	posy = posy - 50; // move the box up 50px from the cursor
	
	var args = [product_id, posx, posy];
	var callback = {
		success: function(o){
			var ppd_obj = eval(o.responseText);
			var body_content = ppd_html_string_template.replace("%ppd_image_url%", ppd_obj.imageUrl);
			body_content = body_content.replace("%ppd_name%", ppd_obj.name);
			body_content = body_content.replace("%ppd_description%", ppd_obj.description);
			body_content = body_content.replace("%ppd_price%", ppd_obj.price);
			
			ppd_yui_panel.setBody(body_content);
			ppd_yui_panel.moveTo(o.argument[1], o.argument[2]);
			ppd_yui_panel.show();
		},
		failure: function(o){
			if(o.responseText !== undefined){
				alert("Get product preview failed: " + o.responseText);
			}
			else{
				alert("Get product preview failed: no error message available");
			}
		},
		argument: args
	};
	
	var url = "ppd_getProductPreview.php?product_id=" + product_id;
	
	YAHOO.util.Connect.asyncRequest('GET', url, callback);
	
}

function ppd_hide(){
	ppd_yui_panel.hide();
}
