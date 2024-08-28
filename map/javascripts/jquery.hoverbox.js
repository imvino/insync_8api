var stopHoverRefreshTimer = function() {
	var timer = jQuery('#hoverbox').attr('timer');
	if (timer) {
		jQuery('#hoverbox').attr('timer', null);
		clearTimeout(timer);
	}
};
var hoverboxClose = function() {
	if (jQuery('#hoverbox').length > 0) {
		jQuery('#hoverbox').attr('running', 'false');
		jQuery('#hoverbox').children('img').remove();
		jQuery('#hoverbox').hide();
		stopHoverRefreshTimer();
	}
};
(function($) {
	jQuery.fn.refreshhoverbox = function() {
		var hoverbox = jQuery('#hoverbox');
		
		if (hoverbox && hoverbox.attr('running') == 'true' && hoverbox.children('img').length) {
			var src = page.withoutTimeStamp(hoverbox.children('img').attr('src'));
			
			if (src && hoverbox.attr('imageSrc') == src) {
				var newImage = new Image();
				newImage.src = page.timeStampSrc(src);
				newImage.onerror = function() {
					if (jQuery('#hoverbox').attr('running') == 'true') {
						stopHoverRefreshTimer();
						jQuery('#hoverbox').attr('timer', setTimeout(jQuery.fn.refreshhoverbox,10));
					}
				};
				newImage.onload = function() {
					if (jQuery('#hoverbox').attr('running') == 'true') {
						stopHoverRefreshTimer();
						if (jQuery('#hoverbox img').length > 0) {
							jQuery('#hoverbox img').remove();
						}
						jQuery('#hoverbox').append(newImage);

						jQuery('#hoverbox').attr('timer', setTimeout(jQuery.fn.refreshhoverbox,10));
					}
				};
			}
		}
	};
	jQuery.fn.hoverbox = function(options) {
		return this
				.each(function(index, element) {
					var targetelement = jQuery(element);
					var showHover = function(event) {
						hoverboxClose();
						// Check if modal background
						// exist
						if (!document.getElementById('hoverbox')) {
							jQuery('body').append('<div id="hoverbox"></div>');
							jQuery('#hoverbox').mouseleave(function(event) {
								hoverboxClose();
							});
							jQuery('#hoverbox').click(function(event) {
								hoverboxClose();
							});
						}
						var basePos = jQuery(event.target).offset();
						basePos.left += jQuery(event.target).width() / 2;
						basePos.left -= 320 / 2;
						basePos.top += jQuery(event.target).height() / 2;
						basePos.top -= 240 / 2;
						jQuery('#hoverbox').css('top', basePos.top);
						jQuery('#hoverbox').css('left', basePos.left);
						jQuery('#hoverbox').offset(basePos);
						jQuery('#hoverbox').show();
						// Move after show in to ensure proper location
						jQuery('#hoverbox').css('top', basePos.top);
						jQuery('#hoverbox').css('left', basePos.left);
						jQuery('#hoverbox').offset(basePos);
						var imageurl = targetelement.attr('href');
						jQuery('#hoverbox').attr('imageSrc', page.withoutTimeStamp(imageurl));
						var lightboximage = new Image();
						lightboximage.src = imageurl;
						lightboximage.onerror = function() {
							hoverboxClose();
						};
						lightboximage.onload = function() {
							jQuery('#hoverbox').attr('running', 'true');
							if (jQuery('#hoverbox').attr('running') == 'true') {
								jQuery('#hoverbox').css('top', basePos.top);
								jQuery('#hoverbox').css('left', basePos.left);
								jQuery('#hoverbox').offset(basePos);
								stopHoverRefreshTimer();
								if (jQuery('#hoverbox img').length > 0) {
									jQuery('#hoverbox img').remove();
								}
								jQuery('#hoverbox').append(lightboximage);
								jQuery('#hoverbox').attr('timer', setTimeout(jQuery.fn.refreshhoverbox,10));
							}
						};
					};
					targetelement.click(function(event) {
						event.preventDefault();
						event.stopPropagation();
						showHover(event);
					});
					targetelement.mouseenter(function(event) {
						event.preventDefault();
						event.stopPropagation();
						showHover(event);
					});
				});
	};
})(jQuery);