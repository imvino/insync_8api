<?php

// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

header("Content-type: application/javascript"); 

$timeout = authSystem::getUserTimeout($permissions["username"])*60;

?>

var expirationTimer = <?=$timeout?>;
var timerID;
var lastExtended = (new Date()).getTime();

$(document).ready(function() {
	$('#dialog-timeout-notice').dialog({
		resizable: false,
		autoOpen: false,
		modal: true,
		closeText: '×',
		width: 400,
		buttons: {
			Cancel: function() {
				$(this).dialog('close');
				resetTimer();
			}
		}
	});

	$(document).click(resetTimer);
	$(document).mousemove(resetTimer);
	$(document).keypress(resetTimer);

	if (window.location.href.search('/auth/login.php') == -1)
		timerID = setInterval(sessionTimer, 1000);
});

function clearTimer() {
    clearInterval(timerID);
}

function resetTimer() 
{
	if ($('#dialog-timeout-notice').dialog('isOpen'))
		$('#dialog-timeout-notice').dialog('close');
        
    var currentTime = (new Date()).getTime();
    
	if (expirationTimer <= 60 || currentTime-lastExtended >= 15000)
    {
        lastExtended = currentTime;
        
        $.ajax({
            url: '../auth/extendSession.php'
        })
        .always(function( xhr )
        {
            if(xhr.status == 503)
            {
                alert("The InSync WebUI cannot connect to the authentication server. You will be redirected to the login page.");
                $(window).unbind('beforeunload');
                window.location = '/auth/login.php?target=' + window.location;
            }
        });
    }
        
	expirationTimer = <?=$timeout?>;
}

function sessionTimer() {
	// session has expired, logoff user
	if (expirationTimer <= 0) {
		$(window).unbind('beforeunload');
		window.location = '/auth/login.php?target=' + window.location;
	}

	// session will expire in <60s
	if (expirationTimer <= 60) {
		if ($('#dialog-timeout-notice').dialog('isOpen'))
			$('#dialog-timeout-notice').html('<p>You will be logged off due to inactivity in ' + expirationTimer + ' seconds.</p><p>Press Cancel or move your mouse to extend your session.</p>')
		else
			$('#dialog-timeout-notice').dialog('open');
	}

	expirationTimer--;
}
