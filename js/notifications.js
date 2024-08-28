var notificationData = 0;

var globalTimeZone = "America/Chicago";

var watchTimer = null;
var inputTimer = null;
var waitTimer = null;

//var oldTMState = true;
//var firstLoad = true;

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
    
    // Download notifications based on date selection 
	// Sample to run from browser: Start Date/Time 08/29/2019 12:05 AM, End Date/Time 08/30/2019 11:59 PM
	// http://localhost/helpers/notificationsHelper.php?action=downloadjson&startDateTime=08/30/2019 12:00 AM&endDateTime=08/30/2019 11:59 PM
	$("#download").button().click(function()
    {
        var startDate = $("#startDateTime").val();
        var endDate = $("#endDateTime").val();

		var url = "helpers/notificationsHelper.php?action=downloadjson&startDateTime=" + startDate + "&endDateTime=" + endDate;

        window.open(url,'download')
	
    });
	
    // Download all active notifications
	$("#downloadactive").button().click(function()
    {
        //var startDate = $("#startDateTime").val();
        //var endDate = $("#endDateTime").val();

		// Download all active notifications
		// Sample to run from browser:
		// http://localhost/helpers/notificationsHelper.php?action=downloadactivejson
		var url = "helpers/notificationsHelper.php?action=downloadactivejson";

        window.open(url,'download')
	
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

	// show all notifications based on date range
	$("#submit").click(function() {
		var start = $("#startDateTime").val();
		var end = $("#endDateTime").val();
		
		loadInitialData(start, end);
	});

	// show currently active notifications. Date not used even if entered	
	$("#submitactive").click(function() {
		var start = $("#startDateTime").val();
		var end = $("#endDateTime").val();
		
		loadActiveData();
	});	
	
	/*
	$("#dialog-confirm-hang").dialog({
		autoOpen: false,
        closeText: '×',
		buttons: {
			Yes: function() {
				oldTMState = !oldTMState;
				$("#optionInclude option").each(function() {
					if ($(this).val() == "t") {
						if (oldTMState)
							$(this).prop("selected", true);
						else
							$(this).prop("selected", false);
					}
				});
				$("#optionInclude").trigger("chosen:updated");

				filterRows();
                hiliteHandler();
                
				$(this).dialog("close");
			},
			Cancel: function() {
				$(this).dialog("close");
			}
		}
	});
	*/
});

function hiliteHandler() 
{
	var hilite = $("#hilite").val();
	
	if (hilite == "") {
		$(".history-wait-col").each(function() {
			$(this).removeClass("history-hilite");
		});
	} else {			
		$(".history-wait-col").each(function() {			
			if(+$(this).text() > +hilite)
				$(this).addClass("history-hilite");
            else
                $(this).removeClass("history-hilite");
		});
	}
}

function objCount(obj) {
    var count = 0;

    for (var prop in obj) 
        if (obj.hasOwnProperty(prop))
            count++;

    return count;
}

function filterRows()
{
    $("#historyTable>tbody>tr").each(function()
    {
       var showType = checkInclude($(this));
       var showWithLimits = checkLimits($(this));
       var showWithWait = checkWait($(this));
       
       if(showType && showWithLimits && showWithWait)
           $(this).show();
       else
           $(this).hide();
    });
}

function checkWait(element)
{
    if(!element.hasClass("history-row-movement"))
        return true;
    
    var waitFilter = $("#waitfilter").val();
    
    if(waitFilter == "")
        return true;
    
    var showCount = 0;
    
    element.children(".history-wait-col").each(function() 
    {
        if (+$(this).text() > +waitFilter)
            showCount++;
    });
    
    if(showCount > 0)
        return true;
    else
        return false;
}

function checkLimits(element)
{
    if(!element.hasClass("history-row-movement"))
        return true;
    
    var valArray = $("#optionMovements").val();
    
    if (valArray == null)
        return true;
    
    var movements = element.attr("movement").split("-");
    
    for(var i=0; i < movements.length; i++)       
        if ($.inArray(movements[i], valArray) != -1)
            return true;
    
    return false;
}

function checkInclude(element) 
{
	var valArray = $("#optionInclude").val();
		
    if(element.hasClass("history-row-movement"))
    {
        if ($.inArray("t", valArray) == -1)
            return false;
        else
            return true;
    }
    
    if(element.hasClass("history-row-pedestrian"))
    {
        if ($.inArray("ped", valArray) == -1)
            return false;
        else
            return true;
    }
    
    if(element.hasClass("history-row-error"))
    {
        if ($.inArray("e", valArray) == -1)
            return false;
        else
            return true;
    }

    if(element.hasClass("history-row-success"))
    {
        if ($.inArray("s", valArray) == -1)
            return false;
        else
            return true;
    }
    
    if(element.hasClass("history-row-period"))
    {
        if ($.inArray("per", valArray) == -1)
            return false;
        else
            return true;
    }
    
    if(element.hasClass("history-row-tunnel"))
    {
        if ($.inArray("tun", valArray) == -1)
            return false;
        else
            return true;
    }
    
    return false;
}

function loadInitialData(start, end) 
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
        $.getJSON('/helpers/notificationsHelper.php',
        {action:"load", start: start, end: end},
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

                initialHTML += "<tr><th class='h-time'>Time</th><th class='h-item'>Item</th><th class='h-message'>Message</th></tr></thead><tbody>";

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
                //filterRows();
                //hiliteHandler();

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

// load notifications that are currently active
function loadActiveData() 
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
        $.getJSON('/helpers/notificationsHelper.php',
        {action:"loadactive"},
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

                initialHTML += "<tr><th class='h-time'>Time</th><th class='h-item'>Item</th><th class='h-message'>Message</th>";

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
                //filterRows();
                //hiliteHandler();

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
		var errorItem = item.item;

		$.each(item.data, function (key, value)
		{
			var formattedTime = value["DateTime:"];
			var message = value["Event:"];
			html += "<tr class='history-row-movement'><td class='h-time'>" + formattedTime + "</td><td class='h-item'><b>" + errorItem + "</b></td><td class='h-message'>" + message + "</td></tr>";
			errorItem = "";			

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
