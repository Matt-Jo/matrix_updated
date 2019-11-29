jQuery(document).ready(function() {
	jQuery('.productListing-data a').live('click', function(event) {
		var context = 'PLIST', which, act, prodid, listpos;

		if (/^product-info-link-\d+$/.test(jQuery(this).attr('id'))) {
			// image click
			which = 'image';
			act = 'view';
			prodid = jQuery(this).attr('id').replace(/^product-info-link-(\d+)$/, '$1');
			listpos = jQuery(this).closest('tr').index()/2+1;
		}
		else if (/-p-\d+\.html$/.test(jQuery(this).attr('href'))) {
			// title click
			which = 'title';
			act = 'view';
			prodid = jQuery(this).attr('href').replace(/^.+-p-(\d+)\.html$/, '$1');
			listpos = jQuery(this).closest('tr').index()/2+1;
		}
		else if (jQuery(this).hasClass('direct-add')) {
			// buy now click
			which = 'directadd';
			if (jQuery(this).hasClass('bn')) act = 'buynow';
			else if (jQuery(this).hasClass('atc')) act = 'addtocart';
			if (jQuery(this).hasClass('tc')) act += '-addcart';
			else if (jQuery(this).hasClass('mo')) act += '-viewopts';
			
			if (jQuery(this).hasClass('col-red')) act += '--col-red';
			else if (jQuery(this).hasClass('col-blue')) act += '--col-blue';
			else if (jQuery(this).hasClass('col-green')) act += '--col-green';
			
			//which = 'buynow';
			//act = 'addcart';
			prodid = jQuery(this).attr('href').replace(/.*products_id=(\d+)$/, '$1');
			listpos = jQuery(this).closest('tr').index()/2+1;
		}
		else if ('[details]' == jQuery(this).html()) {
			// details interaction
			which = 'stock_details';
			prodid = jQuery(this).attr('href').replace(/.*'-(\d+)'.*/, '$1');
			act = 'toggle';
			//act = jQuery('#product_availability_details_-'+prodid).is(':visible')?'open':'close';
			listpos = jQuery(this).closest('tr').index()/2+1;
		}

		var category = '--'+context+'--'+search_control.context+'--'+search_control.page;
		var action = '--'+which+'--'+act;
		var label = '--pid['+prodid+']--pos['+listpos+']';

		ga('send', 'event', category, action, label);
		//_gaq.push(['_trackEvent', category, action, label]);
	});

	jQuery('#ck_featured_prods_content .direct-add').live('click', function(event) {
		var context = 'HOME', which, act, prodid, listpos;
		// buy now click
		which = 'directadd';
		if (jQuery(this).hasClass('bn')) act = 'buynow';
		else if (jQuery(this).hasClass('atc')) act = 'addtocart';
		if (jQuery(this).hasClass('tc')) act += '-addcart';
		else if (jQuery(this).hasClass('mo')) act += '-viewopts';

		if (jQuery(this).hasClass('col-red')) act += '--col-red';
		else if (jQuery(this).hasClass('col-blue')) act += '--col-blue';
		else if (jQuery(this).hasClass('col-green')) act += '--col-green';

		//which = 'buynow';
		//act = 'addcart';
		prodid = jQuery(this).attr('href').replace(/.*products_id=(\d+)$/, '$1');
		listpos = jQuery(this).closest('tr').index()/2+1;

		var category = '--'+context;
		var action = '--'+which+'--'+act;
		var label = '--pid['+prodid+']--pos['+listpos+']';

		ga('send', 'event', category, action, label);
		//_gaq.push(['_trackEvent', category, action, label]);
	});

	jQuery('.subcat-block a').live('click', function(event) {
		var context = 'CATBLOCK', cat, variation, posx, posy;

		cat = jQuery(this).html();
		variation = jQuery(this).closest('.subcat-block').attr('class').replace(/\s*subcat-block\s*/, '');

		var pos = jQuery(this).index('.cat-link')+1;
		posx = (pos % 4);
		posy = Math.ceil(pos/4);

		var category = '--'+context+'--'+search_control.context+'--'+search_control.page;
		var action = '--'+cat+'--'+variation;
		var label = '--posx['+posx+']--posy['+posy+']';

		ga('send', 'event', category, action, label);
		//_gaq.push(['_trackEvent', category, action, label]);
	});

	jQuery('.wt-rotator a.rt-link').live('click', function(event) {

		var category = '--ROTATOR--index.php';
		var action = '--ctx[follow-link]--pos['+(jQuery(this).index('.wt-rotator a.rt-link'))+']';
		var label = '--page['+jQuery(this).attr('href')+']';

		ga('send', 'event', category, action, label);
		//_gaq.push(['_trackEvent', category, action, label]);
	});

	// this block seems to reference the correct items, but it won't fire.
	/*jQuery('.wt-rotator .c-panel .thumbnails a').live('click', function(event) {
		alert('blah');
		var category = '--ROTATOR--index.php';
		var action = '--ctx[rotator-control]--pos['+(jQuery(this).index('.wt-rotator .c-panel .thumbnails li'))+']';
		var label = '--title['+jQuery(this).attr('title')+']';

		ga('send', 'event', category, action, label);
		//_gaq.push(['_trackEvent', category, action, label]);
	});*/

	jQuery('.main-add-to-cart, .main-view-options, .options-add-to-cart').live('click', function(event) {
		var category = '--PINFO--product_info.php';
		var action = '--'+jQuery(this).attr('class');
		var label = '--pid['+window.location.pathname.replace(/^.+-p-(\d+)\.html$/, '$1')+']';

		ga('send', 'event', category, action, label);
		//_gaq.push(['_trackEvent', category, action, label]);
	});
});