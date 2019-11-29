let current_ajax;
function open_cart_flyout() {
	jQuery('#cart-flyout').animate({right: "0"}, "fast" );
	jQuery('#flyout-darken-back').fadeIn("fast");
	if (jQuery('#cart-flyout').width() == jQuery(window).width()) {
		jQuery('#ck-topnav').toggle();
		jQuery('html').css('overflow-y', 'hidden');
	}
	else jQuery('html').css('overflow-y', 'auto');
}

function close_cart_flyout() {
	jQuery('#cart-flyout').animate({right: "-110%"}, "slow" );
	jQuery('#flyout-darken-back').fadeOut();
}

jQuery('#cart-flyout-icon').on('click', function () {
	initial_cart_load();
	open_cart_flyout();
});

jQuery('#close-cart-flyout').on('click', function () {
	close_cart_flyout();
});

function initial_cart_load() {
	if (current_ajax) current_ajax.abort();
	current_ajax = jQuery.ajax({
		url: '/cart-flyout',
		dataType: 'json',
		data: { ajax: 1 },
		type: 'POST',
		success: function (data) {
			if (data.success != false) cart_flyout(data);
		}
	});
}

function cart_flyout(data) {
	let new_product_line_template = ``;
	let no_product_display =
		`<div id="cart-flyout-empty-cart">
			<div>You have no products in your cart. Let's get to shopping!</div>
			<i class="far fa-people-carry"></i>
		</div>`;

	// start by removing all the items from the flyout. We'll rebuild what we need
	jQuery('#cart-flyout-products').empty();

	if (!data.cart_content) {
		jQuery('#cart-flyout-products').append(no_product_display);
		jQuery('.cart-items-count').hide();
	}
	else if (data.cart_content.length > 0) { // if we have products then we'll populate the flyout
		jQuery('.cart-items-count').show();
		data.cart_content.forEach(function (data) {
			new_product_line_template +=
				`<div class="cart-flyout-section" id="product-${data.cart_product_id}">
					<div class="cart-flyout-product">
						<i class="fa fa-times delete-product-from-cart" data-cart-product-id="${data.cart_product_id}"></i>
						<div class="cart-flyout-organize"><div class="has-included-items"><div class="image-plus-content">
						<a href="${data.product_url}">
							<img class="cart-flyout-product-image" src="${data.media}/${data.products_image}">
						</a>
					<div class="cart-flyout-content">
						<a href="${data.product_url}"><div class="content-products-model">${data.products_model}</div></a>
						<div class="content-products-title">${data.products_name}</div>
					</div>
				</div>`;
			if (data.has_included_options) {
				new_product_line_template += `<div class="cart-flyout-included-products">`;
				data.included_options.forEach(function (data) {
					new_product_line_template += `<div class="included-option-note">${data.name} - included</div>`;
				});
				new_product_line_template += `</div>`;
			}
			new_product_line_template += `</div><div>`;
			if (data.product_on_special) {
				new_product_line_template += `<div class="cart-flyout-special-price"><div class="price-original product-price">${data.products_price_original}</div><div class="price-special">${data.products_price_display}</div></div>`;
			}
			else {
				new_product_line_template += `<div class="cart-flyout-price"><div class="price-display product-price">${data.products_price_display}</div><span class="each-item">ea</span></div>`;
			}
			new_product_line_template += `</div></div></div>
				<div class="quantity-subtotal">
					<div class="quantity-button-container">
						<button class="quantity-button-minus adjust-quantity-button" id="quantity-button-number-minus-${data.cart_product_id}" data-cart-product-id="${data.cart_product_id}" data-adjust-quantity-action="decrement" data-products-id="${data.products_id}" data-current-quantity="${data.products_quantity}">
							<i class="fas fa-minus"></i>
						</button>
						<input class="quantity-button-number" id="quantity-button-input-${data.cart_product_id}" data-cart-product-id="${data.cart_product_id}" data-current-quantity="${data.products_quantity}" data-products-id="${data.products_id}" value="${data.products_quantity}">
						<button class="quantity-button-plus adjust-quantity-button" id="quantity-button-number-plus-${data.cart_product_id}"  data-cart-product-id="${data.cart_product_id}" data-adjust-quantity-action="increment" data-products-id="${data.products_id}" data-current-quantity="${data.products_quantity}">
							<i class="fas fa-plus"></i>
						</button>
					</div>
					<div class="subtotal-flyout-section"><span class="line-item-desc">Item Total</span><span class="line-item-subtotal">${data.line_subtotal}</span></div>
				</div>`;
			new_product_line_template += `<div class="product_options">`;
			if (data.product_options) {
				data.product_options.forEach(function (data) {
					new_product_line_template +=
						`<div class="cart-flyout-product">
							<i class="fa fa-times delete-product-from-cart" data-cart-product-id="${data.cart_product_id}"></i>
							<div class="cart-flyout-organize">
								<div class="has-included-items">
									<div class="image-plus-content">
										<a href="${data.product_url}">
											<img class="cart-flyout-product-image" src="${data.media}/${data.products_image}">
										</a>
										<div class="cart-flyout-content">
											<a href="${data.product_url}">
												<div class="content-products-model">
													<span style="color:#000000; font-weight:bold;">Option: </span>
													${data.products_model}
												</div>
											</a>
											<div class="content-products-title">${data.products_name}</div>
										</div>
									</div>`;
					if (data.has_included_options) {
						new_product_line_template += `<div class="cart-flyout-included-products">`;
						data.included_options.forEach(function (data) {
							new_product_line_template += `<div class="included-option-note">${data.name} - included</div>`;
						});
						new_product_line_template += `</div>`;
					}
					new_product_line_template += `</div><div>`;
					if (data.product_on_special) {
						new_product_line_template +=
							`<div class="cart-flyout-special-price">
									<div class="price-original product-price">${data.products_price_original}</div>
									<div class="price-special">${data.products_price_display}</div>
								</div>`;
					}
					else {
						new_product_line_template +=
							`<div class="cart-flyout-price">
									<div class="price-display product-price">${data.products_price_original}</div>
								</div>`;
					}
					new_product_line_template += `</div></div></div>
								<div class="quantity-button-container">
									<button class="quantity-button-minus adjust-quantity-button" id="quantity-button-number-minus-${data.cart_product_id}" data-cart-product-id="${data.cart_product_id}" data-adjust-quantity-action="decrement" data-products-id="${data.products_id}" data-parent-products-id="${data.parent_products_id}" data-option-type="${data.option_type}" data-current-quantity="${data.products_quantity}">
										<i class="fas fa-minus"></i>
									</button>
									<input class="quantity-button-number" id="quantity-button-input-${data.cart_product_id}" data-cart-product-id="${data.cart_product_id}" data-current-quantity="${data.products_quantity}" data-products-id="${data.products_id}" value="${data.products_quantity}" data-parent-products-id="${data.parent_products_id}" data-option-type="${data.option_type}">
									<button class="quantity-button-plus adjust-quantity-button" id="quantity-button-number-plus-${data.cart_product_id}"  data-cart-product-id="${data.cart_product_id}" data-adjust-quantity-action="increment" data-products-id="${data.products_id}" data-parent-products-id="${data.parent_products_id}" data-option-type="${data.option_type}" data-current-quantity="${data.products_quantity}">
										<i class="fas fa-plus"></i>
									</button>
							</div>`;
				});
			}
			new_product_line_template += `</div></div>`;
		});
	}

	jQuery('#cart-flyout-total-cost').html(data.cart_totals.display);
	jQuery('#cart-flyout-total-cost').attr('data-raw-total', data.cart_totals.raw_total);
	update_shipping_progress_bar(data.cart_totals.raw_total, data.free_shipping_eligible);
	if (new_product_line_template) jQuery('#cart-flyout-products').append(new_product_line_template);
	open_cart_flyout();
}

function update_shipping_progress_bar(raw_total, eligible=true) {
	if (eligible) {
		jQuery('#free-shipping-eligible').show();
		jQuery('#free-shipping-not-eligible').hide();
		let width = 0;
		if (raw_total != null && raw_total > 0) width = raw_total + 1;

		if (width > 100) {
			width = 100;
			jQuery('#free-shipping-animation').slideDown();
			jQuery('#progress-bar-title').hide();
		}
		else {
			jQuery('#free-shipping-animation').hide();
			jQuery('#progress-bar-title').show();
		}

		jQuery('#shipping-progress-bar').css('width', width + '%');
		return;
	}
	jQuery('#free-shipping-eligible').hide();
	jQuery('#free-shipping-not-eligible').show();
	return;
}

function update_quantity (input_field) {
	if (current_ajax) current_ajax.abort();
	let new_product_quantity;
	let parent_products_id;
	let option_type;
	if (input_field.attr('data-parent-products-id')) parent_products_id = input_field.attr('data-parent-products-id');
	if (input_field.attr('data-option-type')) option_type = input_field.attr('data-option-type');
	let cart_product_id = input_field.attr('data-cart-product-id');
	let action = input_field.attr('data-adjust-quantity-action');
	let current_product_quantity = input_field.attr('data-current-quantity');
	let user_input_quantity = input_field.val();
	let products_id = input_field.attr('data-products-id');
	if (isNaN(user_input_quantity) || user_input_quantity < 0) {
		input_field.css('background-color', 'red');
		return;
	}
	if (Number(current_product_quantity) != Number(user_input_quantity)) {
		current_ajax = jQuery.ajax({
			url: '/cart-flyout',
			dataType: 'json',
			data: { ajax: 1, cart_product_id: cart_product_id, action:'update-product-quantity', quantity:user_input_quantity, products_id:products_id, parent_products_id:parent_products_id, option_type:option_type },
			type: 'POST',
			success: function (data) {
				if (data.success != false) {
					cart_flyout(data);
					jQuery('#quantity-button-input-'+cart_product_id).css('background-color', '#90EE90');
					setTimeout(function () {
						jQuery('#quantity-button-input-'+cart_product_id).css('background-color', '#ffffff');
					}, 1000);
				}
			}
		});
	}
}

jQuery('.delete-product-from-cart').live('click', function () {
	let cart_product_id = jQuery(this).attr('data-cart-product-id');
	if (current_ajax) current_ajax.abort();
	current_ajax = jQuery.ajax({
		url:'/cart-flyout',
		dataType: 'json',
		data: { ajax:1, cart_product_id:cart_product_id, action:'remove-product' },
		type: 'POST',
		success: function (data) {
			if (data.success != false) cart_flyout(data);
		}
	});
});

jQuery('.quantity-button-number').live('blur', function () {
	update_quantity(jQuery(this));
});

jQuery('.quantity-button-number').live('keypress', function (event) {
	if (event.which == 13) update_quantity(jQuery(this));
});

jQuery('.adjust-quantity-button').live('click', function () {
	if (current_ajax) current_ajax.abort();
	let new_product_quantity = 0;
	let parent_products_id;
	let option_type;
	if (jQuery(this).attr('data-parent-products-id')) parent_products_id = jQuery(this).attr('data-parent-products-id');
	if (jQuery(this).attr('data-option-type')) option_type = jQuery(this).attr('data-option-type');

	let cart_product_id = jQuery(this).attr('data-cart-product-id');
	let action = jQuery(this).attr('data-adjust-quantity-action');
	let current_product_quantity = jQuery(this).attr('data-current-quantity');
	let products_id = jQuery(this).attr('data-products-id');

	if (action == 'increment') new_product_quantity = Number(current_product_quantity) + 1;
	else if (action == 'decrement') new_product_quantity = Number(current_product_quantity) - 1;

	current_ajax = jQuery.ajax({
		url:'/cart-flyout',
		dataType: 'json',
		data: { ajax:1, cart_product_id:cart_product_id, action:'update-product-quantity', quantity:new_product_quantity, products_id:products_id, parent_products_id:parent_products_id, option_type:option_type },
		type: 'POST',
		success: function (data) {
			if (data.success != false) {
				cart_flyout(data);
				jQuery('#quantity-button-input-'+cart_product_id).css('background-color', '#90EE90');
				setTimeout(function () {
					jQuery('#quantity-button-input-'+cart_product_id).css('background-color', '#ffffff');
				}, 1000);
			}
		}
	});
});

function add_product_to_cart() {
	ck.ajaxify.form(jQuery('.add-to-cart-form'), function(data) {
			if (data.success) cart_flyout(data);
		},
		function(jqXHR, textStatus, errorThrown) {
			if (textStatus == 'abort') {
				jQuery('.main-body-inner-container').animate({ backgroundColor: 'transparent' }, 500, 'swing');
				return;
			}
			jQuery('#add-to-cart-result').addClass('failure').html('There was a problem adding this item to your cart.');
		},
		function() {
			jQuery('#add-to-cart-result').removeClass('success').removeClass('failure');
		});
}

add_product_to_cart();

jQuery('#ck-new-search-bar-button').on('click', function () {
	jQuery('#ck-new-mobile-search-bar-container').slideToggle();
	jQuery('#ck-new-mobile-search-bar-container input').focus();
});

jQuery('.slide-down-button').on('mouseover', function () {
	let slide_down = jQuery(this).data('type');
	jQuery('#'+slide_down+'-slide-down').slideDown('fast');
});

jQuery('.slide-down-container').on('mouseleave', function () {
	let slide_down = jQuery(this).data('type');
	jQuery('#'+slide_down+'-slide-down').slideUp('fast');
});

jQuery('.menu-button').on('click', function(){
	jQuery('html').toggleClass('nav_visible');
	jQuery('#ck-new-mobile-search-bar-container').hide();
});

jQuery(document).on('click', function (event) {
	if (!jQuery(event.target).closest('#cart-flyout, #cart-flyout-icon').length) close_cart_flyout();
});