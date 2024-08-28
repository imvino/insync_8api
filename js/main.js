var focusIsSupported = (function() {
 
    // Create an anchor + some styles including ':focus'.
    // Focus the anchor, test if style was applied,
    // if it was then we know ':focus' is supported.
 
    var currentFocus = document.activeElement || null;
    var ud = 't' + +new Date(),
        anchor = $('<a id="' + ud + '" href="#"/>').css({top:'-999px',position:'absolute'}).appendTo('body'),
        style = $('<style>#'+ud+'{font-size:10px;}#'+ud+':focus{font-size:1px !important;}</style>').appendTo('head'),
        supported = anchor.focus().css('fontSize') !== '10px';
    anchor.add(style).remove();
    if (currentFocus) currentFocus.focus();
    return supported;
 
})();

if (!focusIsSupported) {
    $('input,select,textarea,a').on({
        focus: function() {
            $(this).removeClass('blur').addClass('focus');
        },
        blur: function() {
            $(this).removeClass('focus').addClass('blur');
        }
    });
}

function getInternetExplorerVersion() {
// Returns the version of Internet Explorer or a -1
// (indicating the use of another browser).

  var rv = -1; // Return value assumes failure.
  if (navigator.appName === 'Microsoft Internet Explorer') {
    var ua = navigator.userAgent;
    var re  = new RegExp('MSIE ([0-9]{1,}[\.0-9]{0,})');
    if (re.exec(ua) !== null)
      rv = parseFloat( RegExp.$1 );
  }
  return rv;
}

function hideUilityMenu() {
    $('.utility-menu-btn').next('.utility-menu').hide();
}

function hideNavigation() {
    $(window).trigger("hide-navigation");
    $('#sidebar').animate({'margin-left': '-=' + $('#sidebar').width()}, function() { $($('#icon-panel ul').attr('href')).hide(); $('#sidebar').hide(); });
}

$(window).scroll(function() {
   if ($("please-wait").is(":visible")) {
        $("#please-wait").position({of: $(window)});
   } 
});

var busy_timeout = null;

function show_busy_indicator() {
    if (busy_timeout !== null) {
        clearTimeout(busy_timeout);
    }
    busy_timeout = setTimeout(function() {$("#please-wait").show().position({of: $(window)})}, 250);;
}

function hide_busy_indicator() {
    if (busy_timeout !== null) {
        clearTimeout(busy_timeout);
	busy_timeout = null;
    }
    $("#please-wait").fadeOut(100);
}




$(function() {	

	$('.btn').button();

	// Style File Inputs
    if (getInternetExplorerVersion() === -1
            || getInternetExplorerVersion() >= 10) {
        $('input[type=file]').each(function() {
			var inputControl = $(this);
			inputControl.css('display', 'none');
			var fake_id = (inputControl.attr('id') !== undefined ? inputControl.attr('id') : inputControl.attr('name')) + '_fake';
			inputControl.before($('<div class="file_input"><input type="text" id = "' + fake_id + '" disabled=disabled/><button class="btn btn-default green">Choose File</button></div>'));
         
			inputControl.prev().find('button').click(function(e) {
                                e.preventDefault();
				inputControl.focus().click();
			});
			inputControl.change(function() {
				$('#'+fake_id).val(inputControl.val());;
			});
          // Assume button() gets called on the newly created button below
        });
    }


	// Chosen Select Plugin
	$('.chosen-select').chosen({
		disable_search: true,
		single_backstroke_delete: false,
		inherit_select_classes: true
	});


	// Pretty Checkable Plugin
	$('.pretty').prettyCheckable();


	// Placeholders
	if (!Modernizr.input.placeholder) {

		$('[placeholder]').focus(function() {
			var input = $(this);
			if (input.val() == input.attr('placeholder')) {
				input.val('');
				input.removeClass('placeholder');
			}
		}).blur(function() {
			var input = $(this);
			if (input.val() == '' || input.val() == input.attr('placeholder')) {
				input.addClass('placeholder');
				input.val(input.attr('placeholder'));
			}
		}).blur();
		$('[placeholder]').parents('form').submit(function() {
			$(this).find('[placeholder]').each(function() {
				var input = $(this);
				if (input.val() == input.attr('placeholder')) {
					input.val('');
				}
			})
		});
	}
    
    if($(window).keySequenceDetector)
    {
        $(window).keySequenceDetector('frogger', function() {
            $("#game-container").remove();
            $("body").append('<div style="border: solid 10px orange;box-shadow: 0px 0px 15px #888888;height:565px" id="game-container"><script src="/js/fm/game.js"></script><canvas id="game" height="565" width="399"></canvas><br /><center><button id="insyncButton">Enable Adaptive</button></center></div>');
            $("#game-container").css("position","absolute");
            $("#game-container").css("top", Math.max(0, (($(window).height() - $("#game-container").outerHeight()) / 2) + $(window).scrollTop()) + "px");
            $("#game-container").css("left", Math.max(0, (($(window).width() - $("#game-container").outerWidth()) / 2) +  $(window).scrollLeft()) + "px");
            start_game();

            $("#insyncButton").click(function()
            {
                toggleInSync();

                if($("#insyncButton").text() == "Enable Adaptive")
                    $("#insyncButton").text("Disable Adaptive");
                else
                    $("#insyncButton").text("Enable Adaptive");
            })
        });
    }


	// Utility Menu
	$('.utility-menu-btn').click(function(e) {
		e.preventDefault();
		$(this).next('.utility-menu').toggle(); 
		if ($(this).next('.utility-menu').is(':visible')) {
            // After the event handler returns catch the next click to close the menu
	        setTimeout( function() {$(document).one('click', hideUilityMenu);}, 0);
		}
	});
	$('#logout-btn').click(function(e) {
		e.preventDefault();
	});


	// Side Navigation
    $('#icon-panel ul').each(function() {

        var $active, $content, $links = $(this).find('a');

        // Check for active link, else use first link found
        $active = $($links.filter('.active')[0] || $links[0]);
        $active.addClass('active');
        $content = $($active.attr('href'));

        // Hide the remaining content
        $links.not($active).each(function() {
            $($(this).attr('href')).hide();
        });

        // Bind the click event handler
        $(this).on('click', 'a', function(e) {
            if ($(this).text() === $active.text() && $('#sidebar').is(':visible')) {
                $(document).off('click', hideNavigation);
                hideNavigation();
                e.preventDefault();
                return;
            }
            
            // Make the old tab inactive
            $active.removeClass('active');
            $content.hide();

            // Update the variables with the new link and content
            $active = $(this);
            $content = $($(this).attr('href'));

            // Make the tab active
            $active.addClass('active');
            $content.show();
            if (!$('#sidebar').is(':visible')) {
                $(window).trigger('show-navigation');
                $('#sidebar').css('margin-left', -1 * $('#sidebar').width());
                $('#sidebar').show();
                $('#sidebar').animate({'margin-left': '0'});
            } else {
                $(document).off('click', hideNavigation);
            }
            // After this event hanlder completes trap the next click to clock the menu
            setTimeout(function() {$(document).one('click', hideNavigation); }, 0);

            e.preventDefault();
        });
    });
	// --- Side Navigation End


	// --- Logout Dialog Confirm
	$('#dialog-logout-confirm').dialog({
		autoOpen: false,
		resizable: false,
		modal: true,
		closeText: '×',
		width: 400,
		buttons: {
			'Yes': function() {
				window.location.href = '/auth/logoff.php';
			},
			Cancel: function() {
				$(this).dialog('close');
			}
		},
		close: function() {
			$('#username,#password').val('').removeClass('ui-state-error');
		}
	});
	// --- Logout Dialog End
	
	// Commenting CentralSync Dialog related code. This will not be supported from InSync_1.6.x.xxxx onwards.
	// --- CentralSync Dialog
	//$('#dialog-centralsync').dialog({
	//	autoOpen: false,
	//	resizable: false,
	//	modal: true,
	//	closeText: '×',
	//	width: 550,
	//	buttons: {
	//		Continue: function() {
	//			if ($('#hide-centralsync').is(':checked'))
	//				setCookie("hide-centralsync","1",30);
    //            
    //            var urlParts = window.location.href.match(/:\/\/(?:www\.)?(.[^/]+)(.*)/);
    //            
	//			window.location.href = 'rhythm://' + urlParts[1] +  '/CentralSync/v1.4';
    //            
	//			$(this).dialog('close');
	//		},
	//		Cancel: function() {
	//			$(this).dialog('close');
	//		}
	//	}
	//});
	// --- CentralSync Dialog End
	

	//$('#menu-centralsync').click(function() {
	//	if (getCookie('hide-centralsync') != '1')
	//		$('#dialog-centralsync').dialog('open');
	//	else {
	//		var urlParts = window.location.href.match(/:\/\/(?:www\.)?(.[^/]+)(.*)/);
    //        window.location.href = 'rhythm://' + urlParts[1] +  '/CentralSync/v1.4';
	//	}
	//});
    
    var bdDate = new Date();
    if (bdDate.getMonth() == 2 && bdDate.getDate() == 23) {
        $('.brand h1').css({'background-image':'url("../img/insync-logo-bd.png")'});
        $('.brand h1').attr('title', 'First InSync Deployment: 3/23/2009! Happy Birthday!')
    }
});

// Support for instances where the last element in a list needs border radius
$.fn.borderRadius = function(radius) {
	return this.each(function(e) {
		$(this).css({
			'border-radius': radius,
			'-webkit-border-radius': radius
		});
	});
};

function popupNotification(message, delay, type) {
	if (type == '' || type == 'undefined')
		type = 'notice';
	
	var noteID = Math.floor((Math.random()*1000)+100);
	var errorClass = 'page-error';
	
	if (type == 'notice')
		errorClass = 'page-notice';
	
	$('#main .content').prepend('<div class="page-alert ' + errorClass + '" id="popupNote' + noteID + '"><p id="notificationMsg' + noteID + '"></p></div>');
	
	$('#popupNote' + noteID).css({'margin-top': '-100px'});
	$('#popupNote' + noteID).animate({'margin-top': '+=100px'}, 2000);
	$('#notificationMsg' + noteID).text(message);
	var closeTimer = setTimeout(hideNotification, delay, noteID);
	$('#popupNote' + noteID).click(function(e) {
        clearTimeout(closeTimer);
        hideNotification(noteID); 
    });
}

function hideNotification(noteID) {
	$('#popupNote' + noteID).animate({
		'margin-top': '-=100px'
	}, {
		duration: 2000, 
		complete: function() {
			$(this).remove();
		}
	});
}

function setCookie(name, value, expirationDays) {
	var exdate = new Date();
	
	exdate.setDate(exdate.getDate() + expirationDays);
	
	var c_value = escape(value) + ((expirationDays == null) ? "" : "; expires=" + exdate.toUTCString());
	
	document.cookie = name + "=" + c_value;
}

function getCookie(name) {
	var c_value = document.cookie;
	
	var c_start = c_value.indexOf(" " + name + "=");
	
	if (c_start == -1)
		c_start = c_value.indexOf(name + "=");
	
	if (c_start == -1)
		c_value = null;
	else {
		c_start = c_value.indexOf("=", c_start) + 1;
		var c_end = c_value.indexOf(";", c_start);
		if (c_end == -1) {
			c_end = c_value.length;
		}
		c_value = unescape(c_value.substring(c_start, c_end));
	}
	return c_value;
}