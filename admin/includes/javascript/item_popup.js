jQuery("head").append('<link rel="stylesheet" type="text/css" href="css/item_popup.css">');
item_popup={timer:500,count_down:function(a){if(1!=jQuery(a).attr("data-inctx")){jQuery(a).attr("data-inctx",1);var c=setInterval(function(){if(1!=jQuery(a).attr("data-allhalt")){var b=0<jQuery(a).attr("data-lockctr")?1:-1,d=parseInt(jQuery(a).attr("data-popctr")),d=d+b;if(0>=d||100<=d)jQuery(a).attr("data-inctx",0),window.clearInterval(c);0>d||100<d||(jQuery(a).attr("data-popctr",d),jQuery(a).find(".status").css("width",d+"%").css("border-bottom-color","rgb("+(255-Math.ceil(2.55*d))+", "+Math.ceil(2.55*
d)+", 0)"),100<=d?(b="."+jQuery(a).attr("id"),jQuery(b).show().css("top",jQuery(a).position().top+jQuery(a).height()).css("left",jQuery(a).position().left)):1>=d&&(jQuery(a).removeClass("pop"),b="."+jQuery(a).attr("id"),jQuery(b).hide().css("top","").css("left","").removeClass("locked")))}},item_popup.timer/100)}}};
jQuery(document).ready(function(){jQuery(".item_popup").live("mouseover",function(a){jQuery(this).attr("data-allhalt",0);jQuery(this).addClass("pop");a=jQuery(this).attr("data-lockctr");a++;jQuery(this).attr("data-lockctr",Math.max(a,0));item_popup.count_down(this)}).live("click",function(a){if(1!=jQuery(this).attr("data-allhalt")){jQuery(this).attr("data-popctr",100);jQuery(this).find(".status").css("width","100%").css("border-bottom-color","rgb(0, 255, 0)");var c="."+jQuery(this).closest(".item_popup").attr("id");
jQuery(c).show().css("top",jQuery(this).position().top+jQuery(this).height()).css("left",jQuery(this).position().left);a.preventDefault();a.stopPropagation()}});jQuery(".item_popup").live("mouseout",function(a){a=jQuery(this).attr("data-lockctr");a--;jQuery(this).attr("data-lockctr",Math.max(a,0));item_popup.count_down(this)});jQuery(".item_popup_details").live("mouseover",function(a){a=jQuery(this).attr("class").split(/\s+/);for(var c,b=0;b<a.length;b++)"item_popup_details"!=a[b]&&/item_popup_\d+/.test(a[b])&&
(c=a[b]);a=jQuery("#"+c).attr("data-lockctr");a++;jQuery("#"+c).attr("data-lockctr",Math.max(a,0));item_popup.count_down(jQuery("#"+c).get(0))});jQuery(".item_popup_details").live("mouseout",function(a){a=jQuery(this).attr("class").split(/\s+/);for(var c,b=0;b<a.length;b++)"item_popup_details"!=a[b]&&/item_popup_\d+/.test(a[b])&&(c=a[b]);a=jQuery("#"+c).attr("data-lockctr");a--;jQuery("#"+c).attr("data-lockctr",Math.max(a,0));item_popup.count_down(jQuery("#"+c).get(0))});jQuery(".item_popup .ctrl .lock").live("click",
function(a){jQuery(this).attr("class","locked");jQuery(this).closest(".item_popup").addClass("locked");a="."+jQuery(this).closest(".item_popup").attr("id");jQuery(a).addClass("locked");a=jQuery(this).closest(".item_popup").attr("data-lockctr");a++;jQuery(this).closest(".item_popup").attr("data-lockctr",Math.max(a,0))});jQuery(".item_popup .ctrl .locked").live("click",function(a){jQuery(this).attr("class","lock");jQuery(this).closest(".item_popup").removeClass("locked");a="."+jQuery(this).closest(".item_popup").attr("id");
jQuery(a).removeClass("locked");a=jQuery(this).closest(".item_popup").attr("data-lockctr");a--;jQuery(this).closest(".item_popup").attr("data-lockctr",Math.max(a,0))});jQuery(".item_popup .ctrl .close").live("click",function(a){a.preventDefault();a.stopPropagation();jQuery(this).closest(".item_popup").attr("data-allhalt",1);jQuery(this).closest(".item_popup").removeClass("pop").removeClass("locked").attr("data-lockctr",0).attr("data-popctr",0);jQuery(this).siblings(".locked").attr("class","lock");
jQuery(this).closest(".item_popup").find(".status").css("width","0%").css("border-bottom-color","rgb(255, 0, 0)");a="."+jQuery(this).closest(".item_popup").attr("id");jQuery(a).hide().css("top","").css("left","").removeClass("locked")});jQuery(".item_popup_imgs .carousel img:not(.in-context)").live("click",function(a){jQuery(this).closest(".item_popup_imgs").find(".carousel .in-context").removeClass("in-context");jQuery(this).addClass("in-context");jQuery(this).closest(".item_popup_imgs").find(".context_image .in-context").removeClass("in-context");
jQuery(this).closest(".item_popup_imgs").find(".context_image ."+jQuery(this).attr("data-target")).addClass("in-context")})});