jQuery(document).ready(function() {
	//$('<div class="mob_only"></div>').appendTo('#ck-topnav .section-content');
	//$('<a class="chat">Chat</a>').appendTo('#ck-topnav .mob_only');
	//$('.chat').attr('href',$('#chat a').attr('href'));
	//$('#contact-us a').clone().appendTo('#ck-topnav .mob_only');
	//$('#ck-login a').clone().appendTo('#ck-topnav .mob_only');

	// $('.menu-button').on('click', function(e){
	// 	e.preventDefault();
     //    // var HeaderHeight = $('#ck-header').height();
     //    // if($(window).width() >= 890) {
     //    //     HeaderHeight = $('#ck-header').height() + $('#ck-header').prev().height();
     //    // }
	// 	// $('#ck-topnav').css('top',HeaderHeight);
	// 	// $('html,body').scrollTop(0);
	// 	$('html').toggleClass('nav_visible');
	// });
	// $('.search_btn').on('click', function(e){
	// 	e.preventDefault();
	// 	$('#search-form').toggleClass('active');
	// });
	$('#ck-topnav .tn-dropdown').parent().parent().append('<div class="more"></div>');
	$('#ck-topnav .tn-popout').parent().parent().append('<div class="more-inner"></div>');
	$('#ck-topnav').on('click','.more, .more-inner', function(){
		$(this).parent().toggleClass('active');
	});
	$('.cat_select').on('click', function(){
		$(this).parent().toggleClass('expanded');
	});
	/// Slider update
	$(window).resize(function(){
		$('#hero-rotator img').css('width', $('#hero-rotator').width());
	}).resize();

	$('#hero-rotator .hero-container').slick({
	  infinite: true,
	  slidesToShow: 1,
	  slidesToScroll: 1,
      autoplay: true,
      autoplaySpeed: 4000,
	  appendArrows: $('#hero-rotator'),
	  prevArrow: '<a class="control left" id="rotator-prev" href="#prev"><picture><source srcset="/images/static/img/rotator-prev.webp?optimg" type="image/webp"><img src="/images/static/img/rotator-prev.png" alt="previous"></picture></a>',
	  nextArrow: '<a class="control right" id="rotator-next" href="#next"><picture><source srcset="/images/static/img/rotator-next.webp?optimg" type="image/webp"><img src="/images/static/img/rotator-next.png" alt="next"></picture></a>'
	});

	$('.pai_header').click(function(){
		if($(window).width() <=767) {
			$(this).toggleClass('active');
			$(this).parent().find('.tab_details').toggle();
		}
	})

	$('.same-height').matchHeight();

	$('#Zoomer').on("click", function(e) {
		//Disable modal on mobile
		if($(window).width() < 767) {
			e.preventDefault();
			return false;
		}
	})

	$('#review-q-holder .yotpo.bottomLine').on("click", function() {
		$('#reviews').find('.pai_header').addClass('active');
		$('#reviews').find('.tab_details').show();
	})

	$('#review-q-holder .yotpo.QABottomLine').on("click", function() {
		$.scrollTo('#qanda');
		$('#qanda').find('.pai_header').addClass('active');
		$('#qanda').find('.tab_details').show();
	})

	$('.option-help').click(function(){
		if($(window).width() < 767) {
			var offset = $(this).offset().top - $('.helper-modals').offset().top;
			$('#' + $(this).data('desc') + '-help').css('marginTop', offset);
		} else {
			$('#' + $(this).data('desc') + '-help').css('marginTop', 'auto');
		}
	})
})
