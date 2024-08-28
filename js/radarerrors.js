var notificationData = 0;

var globalTimeZone = "America/Chicago";

var watchTimer = null;

$(document).ready(function() 
{
    timezoneJS.timezone.zoneFileBasePath = '/js/plugins/tz';
    timezoneJS.timezone.init();

	$('.chosen-multiselect').chosen({
		width: "100%",
		disable_search: true,
		single_backstroke_delete: false,
		inherit_select_classes: true
	});

	$("#startDateTime").datetimepicker({
		hideIfNoPrevNext: true,
		timeFormat: "hh:mm tt",
		maxDate: '0'
	});

	$("#endDateTime").datetimepicker({
		hideIfNoPrevNext: true,
		timeFormat: "hh:mm tt",
		maxDate: '0'
	});

	// show all errors based on date range
	$("#submit").click(function() {
		var start = $("#startDateTime").val();
		var end = $("#endDateTime").val();
		var filtertext = $("#entertext").val();
		loadInitialData(start, end, filtertext);
	});
});

function objCount(obj) {
    var count = 0;

    for (var prop in obj) 
        if (obj.hasOwnProperty(prop))
            count++;

    return count;
}

function loadInitialData(start, end, filtertext) 
{
	notificationData = "";

	$("#notificationContents").html('<center><div id="loading-div">Loading data...<br /><img src="/img/history-spinner.gif"/></div></center>');
	
    if (!isScrolledIntoView($("#notificationContents"))) {
        $('html, body').animate({
            scrollTop: $("#notificationContents").offset().top
        }, 1000);
    }

    setTimeout(function() 
    {  
        $.getJSON('/helpers/radarerrorsHelper.php',
        {action:"load", start: start, end: end, filtertext: filtertext},
        function(data) 
        {   
            if(data.error != undefined)
            {
                popupNotification(data.error, 5000);
                $("#loading-div").remove();
                return;
            }
            
			notificationData = data;
			var entryCount = objCount(notificationData);
            if (entryCount == 0)
            {
                    popupNotification("No data for this time period!", 3500);
                    $("#loading-div").hide();
            }
            else 
            {
                var dataArray = new Array();
                var count = 0;
                $.each(notificationData, function(item,value) {
                        dataArray[count] = new Array();
                        dataArray[count]["item"] = item;
                        dataArray[count]["data"] = value;
                        count++;
                });
			
				notificationData = dataArray;
			
                var initialHTML = "<table id='historyTable' class='history-table'><thead id='historyTableHeader'>";

				initialHTML += "<tr><th class='h-time'>Time</th><th class='h-description'>Description</th></tr></thead><tbody>";

				//initialHTML += "<th class='h-time'>&nbsp;</th><th class='h-description'>&nbsp;</th></tr></thead><tbody>";

                globalNumMax = 0;
                if (entryCount > globalNumMax)
                        globalNumMax = entryCount;

                globalNumDisplayed = 100;
                if (globalNumMax < globalNumDisplayed)
                        globalNumDisplayed = globalNumMax;
				
				initialHTML += addNotificationRows(0, globalNumDisplayed);

                initialHTML += "</tbody></table>";			
                initialHTML += '<center><div id="loading-div">Loading data...<br /><img src="/img/history-spinner.gif" /></div></center>';

                $("#notificationContents").html(initialHTML);

                watchTimer = setInterval(checkLoaderVisible,300);

                // Don't change this to a constant or you break the kiosk page
                var topOffset = $('header').height();
                if (topOffset == null) {
                    topOffset = 0;
                }
                 $("#historyTable").stickyTableHeaders({fixedOffset: topOffset});                
            }
        })
        .fail(function(jqXHR, textStatus, errorThrown) 
        {
            popupNotification("There was an unhandled error while processing your request. Please contact Rhythm Engineering if this problem persists.", 5000);
            $("#loading-div").remove();
        });
    }, isScrolledIntoView('#notificationContents') ? 0 : 1000);
}

function addNotificationRows(start, end) 
{
	var html = "";
	
	for (var i = start; i < end; i++) 
	{
		var item = notificationData[i];

		//console.log(item.item);
		//console.log(arrayitem.data.length);
		//var errorItem = item.item;

		$.each(item.data, function (key, value)
		{
			var formattedTime = value["DateTime:"];
			var message = value["Event:"];
			
			html += "<tr class='history-row-movement'><td class='h-time'>" + formattedTime + "</td><td class='h-message'>" + message + "</td></tr>";

		});

	}
	return html;
}


function checkLoaderVisible() {
	if (isScrolledIntoView($("#loading-div"))) {
		clearInterval(watchTimer);

		if (globalNumDisplayed < globalNumMax) {			
			var max = globalNumDisplayed+100;
			if (max > globalNumMax)
				max = globalNumMax;
			
			updateHTML = addNotificationRows(globalNumDisplayed, max);
			
			globalNumDisplayed += 100;
			
			$("#historyTable>tbody").append(updateHTML);

			//filterRows();
            //hiliteHandler();
			
			watchTimer = setInterval(checkLoaderVisible,300);
		}
		else
			$("#loading-div").remove();
	}
}

function isScrolledIntoView(elem) {
    var rect = $(elem).get(0).getBoundingClientRect();

    return (
        rect.top >= 0 &&
        rect.bottom <= $(window).height()
        );
}
