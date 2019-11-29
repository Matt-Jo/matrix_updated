function initPage()
{
	if (typeof VSA_initScrollbars == 'function') VSA_initScrollbars();
	if (typeof initTabs == 'function') initTabs();
}
if (window.addEventListener)
	window.addEventListener("load", initPage, false);
else if (window.attachEvent)
	window.attachEvent("onload", initPage);
