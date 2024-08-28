// stores all img we are tracking for refresh
var refreshRate;
var filterOpt = "normal";
var qualityOpt = 75;
var disablePausing = false;
var paused = false;
var bAutoPaused = false;
var isPageVisible = true;

function disablePause(option)
{
    disablePausing = option;
}

function stopImageTimer(elem)
{
    clearTimeout($(elem).data("timer"));
    $(elem).data("timer", null);
}

// Use the Page Visibility API to auto-pause refreshing images when the page is hidden (minimized browser
// window or going to another tab).
// Based on: http://stackoverflow.com/questions/1060008/is-there-a-way-to-detect-if-a-browser-window-is-not-currently-active
(function() {
    var hidden = "hidden";

    // Standards:
    if (hidden in document)
        document.addEventListener("visibilitychange", onchange);	// IE 10, IE 11, Chrome
    else if ((hidden = "mozHidden") in document)
        document.addEventListener("mozvisibilitychange", onchange);
    else if ((hidden = "webkitHidden") in document)
        document.addEventListener("webkitvisibilitychange", onchange);
    else if ((hidden = "msHidden") in document)
        document.addEventListener("msvisibilitychange", onchange);
    else if ('onfocusin' in document && navigator.appVersion.indexOf("MSIE") == -1)
        document.onfocusin = document.onfocusout = onchange;
    else  // IE9 and earlier, and all others
        { ; }	// do nothing, we won't perform "auto-pause" in this browser

    function onchange (evt) {
	
        var v = 'visible', h = 'hidden',
            evtMap = { focus:v, focusin:v, pageshow:v, blur:h, focusout:h, pagehide:h };

        evt = evt || window.event;
		
        if (evt.type in evtMap) {
			isPageVisible = evtMap[evt.type] == "visible";
		} else {
			isPageVisible = !(this[hidden]);
		}

		if (!isPageVisible)	{
			// Page is being hidden
			if (!bAutoPaused && !paused) {
				toggleRefresh();
				bAutoPaused = true;
			}
		} else {
			// Page is being shown
			if (bAutoPaused) {
				toggleRefresh();
			}
			bAutoPaused = false;
		}
    }
})();

/**
 * Toggles image refreshing on/off
 */
function toggleRefresh() {

    paused = !paused;

    $('img[enablerefresh=true]').each(function() {
        $(this).data("paused", $(this).data("cannot_pause") ? false : paused);

        // If resuming, start updating
        if (!paused) {
            refreshImage(this);
        }
    });

    return paused;
}

function enableRefresh(elem, initTime, customClick)
{
    var updImage = $(elem);

    if (initTime === undefined) {
        initTime = new Date().getTime();
    }

    if (updImage.data("timer") === undefined) {
        updImage.attr("refreshtime", initTime);
		
		if (customClick != null)
			updImage.click(function() {customClick(updImage);});
		else
			updImage.click(function() {cameraClick(updImage);});
			
        updImage.data("timer", null);
        updImage.data("paused", false);
        updImage.data("cannot_pause", false);

        // Create closeure that can be used for sucess or failure
        // Cap retry rate at once per second after the original load
        // has failed to complete.
        handlerFunc = function(isError){
            var imageElem = $(this);
            var currentTime = new Date().getTime();
            var imageStartLoadTime = imageElem.attr("refreshtime");
            var imageRefreshRate = refreshRate;

            if (imageElem.data("refresh_rate") !== undefined) {
                imageRefreshRate = imageElem.data("refresh_rate");
            }
            
            var refreshDelay = imageRefreshRate - (currentTime - imageStartLoadTime);
            if (refreshDelay < 0) {
                if (isError) {
                    // Don't retry immediately if the result was an error
                    refreshDelay = Math.max(1000, imageRefreshRate);
                } else {
                    refreshDelay = 0;
                }
            }

            stopImageTimer(imageElem);
            imageElem.data("timer", setTimeout(function(){refreshImage(imageElem);}, refreshDelay));

            // Successful image load, save the value so I can access the cached value for hover images
            if (!isError) {
                imageElem.data("last_loaded_image", imageElem.attr("src"));
            }
        };
        $(updImage).load($.proxy(handlerFunc, updImage, false));
        $(updImage).error($.proxy(handlerFunc, updImage, true));
    }

    setTimeout(function(){refreshImage(updImage);},0);
}

function initImageRefresher(imgRefreshRate, customClick)
{
    var updateImages = $('img[enablerefresh=true]');
    refreshRate = imgRefreshRate;
    
    var initTime = new Date().getTime();
    
    updateImages.each(function(index, elem) {
        enableRefresh(elem, initTime, customClick);
    });
}

function refreshImage(elem, force_update)
{    
    if (force_update === undefined) {
        force_update = false;
    }

    var imgElement = $(elem);
    stopImageTimer(imgElement);
    var currentTime = new Date().getTime();
    
    if(imgElement === null || imgElement === undefined)
        return;
    
    if(imgElement.attr("refreshtime") === undefined)
        return;
    
    // If the refresh was disabled, go ahead and return
    if(imgElement.attr("enablerefresh") === undefined
            || imgElement.attr("enablerefresh") === "false")
        return;
	
    // If the image is paused, go ahead and return
    if(imgElement.data("paused") && !force_update)
        return;
	
    imgElement.attr("refreshtime", currentTime);

    var imgSrc = imgElement.attr("baseURL");

    imgSrc += "&filter=" + filterOpt;
    
    if(imgElement.attr("quality") !== undefined)
        imgSrc += "&quality=" + imgElement.attr("quality");
    else
        imgSrc += "&quality=" + qualityOpt;

    imgSrc += "&width=" + Math.floor(imgElement.width());
    // Force maintained aspect ratio
    imgSrc += "&height=" + Math.floor(imgElement.width() * 0.75);

    imgSrc += "&time=" + currentTime;

    imgElement.attr("src", imgSrc);
}

function cameraClick(elem)
{
    if(disablePausing)
        return;
    var clickedImage = $(elem);

    if(clickedImage.attr("enablerefresh") === "true")
    {
        if (clickedImage.data("cannot_pause")) {
            return;
        }
    
        stopImageTimer(clickedImage);

        clickedImage.attr("enablerefresh", "false");
    
        clickedImage.attr("src", "/img/overlay-paused.png");
    }
    else
    {
        clickedImage.attr("enablerefresh", "true");
        clickedImage.data("paused", paused);
    
        refreshImage(clickedImage, true);
    }
}

function changeRefreshRate(rate)
{
    var oldRate = refreshRate;
	
    refreshRate = parseInt(rate);
	
    // now force an update of all img's that are being updated
    var updateImages = $('img[enablerefresh=true]');
    for(var i=0;i < updateImages.length; i++)
    {
        var imgElement = $(updateImages[i]);
        refreshImage(imgElement);
    }
	
    return oldRate;
}

function setFilter(filter)
{
    filterOpt = filter;
}

function setQuality(quality)
{
    qualityOpt = quality;
}

$(function() {
    $(document).keypress(function(event) 
    {
        if(event.charCode == 112 || event.keyCode == 112)
        {
            var paused = toggleRefresh();

            if (paused)
                $('#pauseButton').addClass('play-btn').removeClass('pause-btn');
            else
                $('#pauseButton').removeClass('play-btn').addClass('pause-btn');
        }
    });

    // Add handler for pause button if there is one on the page.
    $("#pauseButton").button().click(function() {
        var paused = toggleRefresh();

        if (paused) {
            $('#pauseButton').addClass('play-btn').removeClass('pause-btn');
        } else {
            $('#pauseButton').removeClass('play-btn').addClass('pause-btn');
        }
    });
});
        
