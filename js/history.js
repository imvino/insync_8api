var globalActivePhases = "";
var globalNumPhases = 0;
var globalHistoryData = "";
var globalNumDisplayed = 0;
var globalNumMax = 0;
var globalPhaseMovementAssociation = null;

var globalTimeZone = "America/Chicago";

var watchTimer = null;
var inputTimer = null;
var waitTimer = null;

var oldTMState = true;
var firstLoad = true;

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
    
    $("#download").button().click(function()
    {
        var startDate = $("#startDateTime").val();
        var endDate = $("#endDateTime").val();
        var incArray = $("#optionInclude").val();
        var moveArray = $("#optionMovements").val();
        var waitFilter = $("#waitfilter").val();
        
        var url = "helpers/historyHelper.php?action=downloadcsv&startDateTime=" + startDate + "&endDateTime=" + endDate + "&incArray=" + incArray + "&moveArray=" + moveArray + "&waitFilter=" + waitFilter;

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
	
	$("#optionInclude").change(function() {
		var valArray = $("#optionInclude").val();
		
		if ($.inArray("t", valArray) == -1 && oldTMState && globalNumDisplayed > 500) {
			$("#dialog-confirm-hang").dialog("open");
			$("#optionInclude option").each(function() {
				if ($(this).val() == "t")
					$(this).prop("selected", true);
			});
			$(this).trigger("chosen:updated");
		} else if ($.inArray("t", valArray) != -1 && !oldTMState && globalNumDisplayed > 500) {
			$("#dialog-confirm-hang").dialog("open");
			$("#optionInclude option").each(function() {
				if ($(this).val() == "t")
					$(this).prop("selected", false);
			});
			$(this).trigger("chosen:updated");
		} else {
            if ($.inArray("t", valArray) == -1)
                oldTMState = false;
            else
                oldTMState = true;
            
			filterRows();
            hiliteHandler();
        }
	});
	
	$("#optionMovements").change(function() {
		filterRows();
        hiliteHandler();
	});
	
	$(window).keySequenceDetector('mchammer', function() {
		jQuery.each($(document).find("img"), function() {
			if ($(this).attr("src") == "/img/history/Ped.PNG")
				$(this).attr("src", "/img/history/PedAlt.gif");
		});
	});
	
	$("#hilite").keyup(function() {
		clearInterval(inputTimer);
		inputTimer = setTimeout(hiliteHandler, 1000);
	});
    
    $("#waitfilter").keyup(function() {
		clearInterval(waitTimer);
		waitTimer = setTimeout(filterRows, 1000);
	});
	
	$("#submit").click(function() {
		var start = $("#startDateTime").val();
		var end = $("#endDateTime").val();
		
		loadInitialData(start, end);
	});
	
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
	globalHistoryData = "";
	globalActivePhases = "";
	globalNumPhases = 0;
	globalNumMax = 0;
	globalNumDisplayed = 0;
	
	$("#historyContents").html('<center><div id="loading-div">Loading data...<br /><img src="/img/history-spinner.gif"/></div></center>');

    if (!isScrolledIntoView($("#historyContents"))) {
        $('html, body').animate({
            scrollTop: $("#historyContents").offset().top
        }, 1000);
    }

    setTimeout(function() 
    {  
        $.getJSON('/helpers/historyHelper.php',
        {action:"load", start: start, end: end},
        function(data) 
        {   
            if(data.error != undefined)
            {
                popupNotification(data.error, 5000);
                $("#loading-div").remove();
                return;
            }
            
            globalActivePhases = data.activePhases;
            globalNumPhases = objCount(globalActivePhases);
            globalHistoryData = data.data;
            globalPhaseMovementAssociation = data.phaseMovementAssociation;
            globalTimeZone = data.timezone;

            var entryCount = objCount(globalHistoryData);

            if (entryCount == 0)
            {
                    popupNotification("No data for this time period!", 3500);
                    $("#loading-div").hide();
            }
            else 
            {
                var dataArray = new Array();
                var count = 0;
                $.each(globalHistoryData, function(timestamp,value) {
                        dataArray[count] = new Array();
                        dataArray[count]["ts"] = timestamp;
                        dataArray[count]["data"] = value;
                        count++;
                });

                globalHistoryData = dataArray;

                entryCount = globalHistoryData.length;

                var initialHTML = "<table id='historyTable' class='history-table'><thead id='historyTableHeader'>";

                initialHTML += "<tr><th class='h-time'>Time</th><th class='h-duration'>Duration</th><th class='h-movement'>Movement</th>";

                for (phase in globalActivePhases)
                        initialHTML += "<th colspan='2' class='h-phase01'>Phase " + phase + "<br />(" + globalActivePhases[phase]["short"] + ")</th>";

                initialHTML += "<th class='h-period'>Period</th></tr><tr>";
                initialHTML += "<th class='h-time'>&nbsp;</th><th class='h-duration'>&nbsp;</th><th class='h-movement'>&nbsp;</th>";

                for (phase in globalActivePhases)
                        initialHTML += "<th class='h-phase02'>Q</th><th class='h-phase02'>W</th>";

                initialHTML += "<th class='h-period'>&nbsp;</th></tr></thead><tbody>";

                globalNumMax = 0;
                if (entryCount > globalNumMax)
                        globalNumMax = entryCount;

                globalNumDisplayed = 100;
                if (globalNumMax < globalNumDisplayed)
                        globalNumDisplayed = globalNumMax;

                initialHTML += addHistoryRows(0, globalNumDisplayed);
                initialHTML += "</tbody></table>";			
                initialHTML += '<center><div id="loading-div">Loading data...<br /><img src="/img/history-spinner.gif" /></div></center>';

                $("#historyContents").html(initialHTML);

                filterRows();
                hiliteHandler();

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
    }, isScrolledIntoView('#historyContents') ? 0 : 1000);
}

function addHistoryRows(start, end) 
{
	var html = "";
	
	for (var i = start; i < end; i++) 
    {
		var item = globalHistoryData[i];

		var formattedTime = timestampToTime(item.ts);

		// movement
		if (item.data.t == "I") 
        {				
			var duration = "";

			for (var j=i+1; j < globalNumMax; j++) {
				if (item.data.t == globalHistoryData[j].data.t) {
					duration = (globalHistoryData[j].ts - item.ts) / 1000;
					break;
				}
			}
            
            if (item.data.md.m != 'undefined' && item.data.md.m == "AllRed")
                continue;            
			
			var movements = item.data.md.m.split("-");
			var associatedPhases = new Array();
			
			$.each(movements, function(index, value) {						
				associatedPhases.push(+globalPhaseMovementAssociation[value]);
			});

			var arrows = getArrowForMovement(item.data.md.m);

			html += "<tr class='history-row-movement' movement='" + item.data.md.m + "'><td class='h-time'>" + formattedTime + "</td><td class='h-duration'>" + duration + "</td><td class='h-movement'>" + arrows + "</td>";

			$.each(item.data.md.pd, function(index, value) {
				if (value.error != undefined)
					html += "<td class='h-phase02 history-error' title='" + value.error + "'>" + value.queue + "</td><td class='h-phase02 history-error history-wait-col' title='" + value.error + "'>" + value.wait + "</td>";
				else {
					if ($.inArray(+index, associatedPhases) != -1)
						html += "<td class='h-phase02 history-movementphase'>" + value.queue + "</td><td class='h-phase02 history-movementphase history-wait-col'>" + value.wait + "</td>";
					else
						html += "<td class='h-phase02'>" + value.queue + "</td><td class='h-phase02 history-wait-col'>" + value.wait + "</td>";
				}
			});

			if (item.data.p != undefined)
				html += "<td class='h-period'>" + item.data.p + "</td>";
			else
				html += "<td class='h-period'>&nbsp</td>";

			html += "</tr>";
		}
		
		// period changing
		else if (item.data.t == "P") 
        {
			var numCols = 2 + (globalNumPhases*2);
			html += "<tr class='history-row-period'><td>" + formattedTime + "</td><td colspan='" + numCols + "' class='history-period-change'>Period length changed to " + item.data.p + "</td><td>&nbsp;</td></tr>";
		}
		
		// success
		else if (item.data.t == "S") 
        {			
			var numCols = 2 + (globalNumPhases*2);
			html += "<tr class='history-row-success'><td>" + formattedTime + "</td><td colspan='" + numCols + "' class='history-success'>" + item.data.d + "</td><td>&nbsp;</td></tr>";
		}
        
        // tunnel
		else if (item.data.t == "T") 
        {			
			var numCols = 2 + (globalNumPhases*2);
			html += "<tr class='history-row-tunnel'><td>" + formattedTime + "</td><td colspan='" + numCols + "' class='history-success'>" + item.data.d + "</td><td>&nbsp;</td></tr>";
		}
		
		// error or PED (dumb, but I didn't do it.)
		else if (item.data.t == "E") 
        {			
			var dataParts = item.data.d.split(",");
			
			if (dataParts[0] == "Ped") 
            {
				var Unscheduled = false;
				if(dataParts[1].indexOf("Unscheduled") != -1) {
					Unscheduled = true;
                }

				html += "<tr class='history-row-pedestrian'><td>" + formattedTime + "</td><td>&nbsp;</td>";
				
				if (dataParts[1].indexOf("sent") != -1)
					html += "<td>Ped Sent</td>";
				else if(Unscheduled === true)
					html += "<td>Unexpected Walk</td>";
				else
					html += "<td>Ped Called</td>";
				
				if(Unscheduled === false) {
					dataParts[2] = $.trim(dataParts[2]);				
					var targetPhase = dataParts[2].slice(-1);
				}
				else {
					dataParts[1] = $.trim(dataParts[1]);				
					var targetPhase = dataParts[1].slice(17,18);
				}
                
                $.each( globalActivePhases, function(index, value)
                {
                    if (index == targetPhase) 
                    {
						if (dataParts[1].indexOf("sent") != -1)
							html += "<td colspan='2'><img src='/img/history/Ped.png'/></td>";
						else if(Unscheduled === true)
							html += "<td colspan='2'><img src='/img/history/PedError.png'/></td>";
						else
							html += "<td colspan='2'><img src='/img/history/PedPush.png'/></td>";
					} 
                    else
						html += "<td colspan='2'>&nbsp;</td>";
                });
				
				html += "<td>&nbsp;</td></tr>";
			} else {
				var numCols = 2 + (globalNumPhases*2);
				html += "<tr class='history-row-error'><td>" + formattedTime + "</td><td colspan='" + numCols + "' class='history-error'>" + item.data.d + "</td><td>&nbsp;</td></tr>";
			}
		}
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
			
			updateHTML = addHistoryRows(globalNumDisplayed, max);
			
			globalNumDisplayed += 100;
			
			$("#historyTable>tbody").append(updateHTML);

			filterRows();
            hiliteHandler();
			
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

function timestampToTime(ts) {
	var dateObj = new timezoneJS.Date(+ts, globalTimeZone);
				
	var hour = dateObj.getHours();
	var am = true;
	if (hour >= 12)
		am = false;
	if (hour > 12)
		hour = hour - 12;
	if (hour == 0)
		hour = 12;
	if (hour < 10)
		hour = "0" + hour;
	var min = dateObj.getMinutes();
	if (min < 10)
		min = "0" + min;
	var sec = dateObj.getSeconds();
	if (sec < 10)
		sec = "0" + sec;

	var formattedTime = hour + ":" + min + ":" + sec + " ";

	if (am)
		formattedTime += "AM";
	else
		formattedTime += "PM";
	
	return formattedTime;
}

function getArrowForMovement(statetodraw) {
	drawoutput = "";
	drawnphases = new Array();
	drawnphases['SouthBoundThrough'] = false;
	drawnphases['NorthBoundThrough'] = false;
	drawnphases['EastBoundThrough'] = false;
	drawnphases['WestBoundThrough'] = false;
	drawnphases['SouthBoundLeftTurn'] = false;
	drawnphases['NorthBoundLeftTurn'] = false;
	drawnphases['EastBoundLeftTurn'] = false;
	drawnphases['WestBoundLeftTurn'] = false;

	if (statetodraw.indexOf("SouthBoundThrough") != -1 && statetodraw.indexOf("SouthBoundLeftTurn") != -1 && drawnphases['SouthBoundThrough'] == false && drawnphases['SouthBoundLeftTurn'] == false) {
		drawnphases['SouthBoundThrough'] = true;
		drawnphases['SouthBoundLeftTurn'] = true;
		drawoutput += "<img src='img/history/SouthBoundLeftTurn-SouthBoundThrough.png'/>";
	}
	if (statetodraw.indexOf("SouthBoundThrough") != -1 && statetodraw.indexOf("NorthBoundThrough") != -1 && drawnphases['SouthBoundThrough'] == false && drawnphases['NorthBoundThrough'] == false) {
		drawnphases['SouthBoundThrough'] = true;
		drawnphases['NorthBoundThrough'] = true;
		drawoutput += "<img src='img/history/NorthBoundThrough-SouthBoundThrough.png'/>";
	}
	if (statetodraw.indexOf("NorthBoundThrough") != -1 && statetodraw.indexOf("NorthBoundLeftTurn") != -1 && drawnphases['NorthBoundLeftTurn'] == false && drawnphases['NorthBoundThrough'] == false) {
		drawnphases['NorthBoundThrough'] = true;
		drawnphases['NorthBoundLeftTurn'] = true;
		drawoutput += "<img src='img/history/NorthBoundThrough-NorthBoundLeftTurn.png'/>";
	}
	if (statetodraw.indexOf("SouthBoundLeftTurn") != -1 && statetodraw.indexOf("NorthBoundLeftTurn") != -1 && drawnphases['SouthBoundLeftTurn'] == false && drawnphases['NorthBoundLeftTurn'] == false) {
		drawnphases['SouthBoundLeftTurn'] = true;
		drawnphases['NorthBoundLeftTurn'] = true;
		drawoutput += "<img src='img/history/SouthBoundLeftTurn-NorthBoundLeftTurn.png'/>";
	}
	if (statetodraw.indexOf("SouthBoundThrough") != -1 && drawnphases['SouthBoundThrough'] == false) {
		drawnphases['SouthBoundThrough'] = true;
		drawoutput += "<img src='img/history/SouthBoundThrough.png'/>";
	}
	if (statetodraw.indexOf("SouthBoundLeftTurn") != -1 && drawnphases['SouthBoundLeftTurn'] == false) {
		drawnphases['SouthBoundLeftTurn'] = true;
		drawoutput += "<img src='img/history/SouthBoundLeftTurn.png'/>";
	}
	if (statetodraw.indexOf("NorthBoundLeftTurn") != -1 && drawnphases['NorthBoundLeftTurn'] == false) {
		drawnphases['NorthBoundLeftTurn'] = true;
		drawoutput += "<img src='img/history/NorthBoundLeftTurn.png'/>";
	}
	if (statetodraw.indexOf("NorthBoundThrough") != -1 && drawnphases['NorthBoundThrough'] == false) {
		drawnphases['NorthBoundThrough'] = true;
		drawoutput += "<img src='img/history/NorthBoundThrough.png'/>";
	}
	if (statetodraw.indexOf("WestBoundThrough") != -1 && statetodraw.indexOf("WestBoundLeftTurn") != -1 && drawnphases['WestBoundThrough'] == false && drawnphases['WestBoundLeftTurn'] == false) {
		drawnphases['WestBoundThrough'] = true;
		drawnphases['WestBoundLeftTurn'] = true;
		drawoutput += "<img src='img/history/WestBoundThrough-WestBoundLeftTurn.png'/>";
	}
	if (statetodraw.indexOf("WestBoundThrough") != -1 && statetodraw.indexOf("EastBoundThrough") != -1 && drawnphases['WestBoundThrough'] == false && drawnphases['EastBoundThrough'] == false) {
		drawnphases['WestBoundThrough'] = true;
		drawnphases['EastBoundThrough'] = true;
		drawoutput += "<img src='img/history/WestBoundThrough-EastBoundThrough.png'/>";
	}
	if (statetodraw.indexOf("EastBoundThrough") != -1 && statetodraw.indexOf("EastBoundLeftTurn") != -1 && drawnphases['EastBoundThrough'] == false && drawnphases['EastBoundLeftTurn'] == false) {
		drawnphases['EastBoundThrough'] = true;
		drawnphases['EastBoundLeftTurn'] = true;
		drawoutput += "<img src='img/history/EastBoundLeftTurn-EastBoundThrough.png'/>";
	}
	if (statetodraw.indexOf("EastBoundLeftTurn") != -1 && statetodraw.indexOf("WestBoundLeftTurn") != -1 && drawnphases['EastBoundLeftTurn'] == false && drawnphases['WestBoundLeftTurn'] == false) {
		drawnphases['EastBoundLeftTurn'] = true;
		drawnphases['WestBoundLeftTurn'] = true;
		drawoutput += "<img src='img/history/EastBoundLeftTurn-WestBoundLeftTurn.png'/>";
	}
	if (statetodraw.indexOf("WestBoundLeftTurn") != -1 && drawnphases['WestBoundLeftTurn'] == false) {
		drawnphases['WestBoundLeftTurn'] = true;
		drawoutput += "<img src='img/history/WestBoundLeftTurn.png'/>";
	}
	if (statetodraw.indexOf("WestBoundThrough") != -1 && drawnphases['WestBoundThrough'] == false) {
		drawnphases['WestBoundThrough'] = true;
		drawoutput += "<img src='img/history/WestBoundThrough.png'/>";
	}
	if (statetodraw.indexOf("EastBoundLeftTurn") != -1 && drawnphases['EastBoundLeftTurn'] == false) {
		drawnphases['EastBoundLeftTurn'] = true;
		drawoutput += "<img src='img/history/EastBoundLeftTurn.png'/>";
	}
	if (statetodraw.indexOf("EastBoundThrough") != -1 && drawnphases['EastBoundThrough'] == false) {
		drawnphases['EastBoundThrough'] = true;
		drawoutput += "<img src='img/history/EastBoundThrough.png'/>";
	}

	return drawoutput;
}
