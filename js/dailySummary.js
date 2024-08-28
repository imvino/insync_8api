var vehicleData = "";
var periodData = "";
var intersectionTotalsData = "";

var vehicleActive = new Array();
var activeVehiclePhases = new Array();
var showVehiclePhases = new Array();

var phasesWithCamera = new Array();

var globalTimeZone = "America/Chicago";

var flotOptions = {
	xaxis: { 
		mode: "time",
		timezone:"browser",
		timeformat:'%I:%M %P',
		twelveHourClock: false,
		zoomRange: null,
		panRange: null
	},
	yaxis: {
		zoomRange: null
	},
	zoom: {
		interactive: true,
		trigger: 'dblclick'
	},
	pan: {
		interactive: true,
		cursor: "move"
	},
	legend: {
		title: "<strong>Show / Hide Phases</strong>",
		labelFormatter: function(label, series) {
			var pipePos = label.indexOf("|");
			
			if (pipePos == -1)
				return label;
			
			var prefix = label.substr(0, pipePos);
			var phaseNum = label.substr(pipePos + 7, 1);
			var realLabel = label.substr(pipePos + 1);
		
			if (prefix == "veh") {
                if ($.inArray(+phaseNum, vehicleActive) == -1)
                   return;
				
				if (showVehiclePhases[phaseNum])
					return '<a style="cursor:pointer;color:#535356;" onclick="togglePhase(' + phaseNum + ',$(this),\'veh\')">' + realLabel + '</a>';
				else
					return '<a style="cursor:pointer;color:#535356;text-decoration:line-through;" onclick="togglePhase(' + phaseNum + ',$(this),\'veh\')">' + realLabel + '</a>';
			}
				
			return label;
		}
	},
	grid: { 
		hoverable: true, 
		clickable: true 
	}
};

$(document).ready(function() {
    timezoneJS.timezone.zoneFileBasePath = '/js/plugins/tz';
    timezoneJS.timezone.init();
    
	// initialize all phases to false
	for (var i=1;i <= 8; i++) {
		activeVehiclePhases[i] = false;		
		showVehiclePhases[i] = false;
	}
	
	$("#datePicker").datepicker({
		hideIfNoPrevNext: true,
		maxDate: '0'
	});
	
	$("#load").button().click(function() {
        $("#displays").fadeIn(250);
        
		vehicleData = "";		
		intersectionTotalsData = "";
        periodData = "";
		
		// hide all phases
		for (var i=1;i <= 8; i++) {
			activeVehiclePhases[i] = false;
			showVehiclePhases[i] = false;
		}
        
        var startDate = $("#datePicker").val();
        
		// get a list of phases with cameras
		$.get("/helpers/FileIOHelper.php?action=phasesWithCamera", function(cameraPhases)
		{
			phasesWithCamera=cameraPhases;

			$.get("/helpers/FileIOHelper.php?action=getSystemConfigurationType", function(systemType)
			{
 				if (systemType == "" || systemType == 0 || phasesWithCamera.length > 0)
				{
					loadVehicleTab(startDate);
					loadTotalsTab(startDate);
					loadPeriodTab(startDate);
				}
				else
				{
					loadPeriodTab(startDate);
				}
			});	
		});
	});
});

function loadTotalsTab(startDate) {
        show_busy_indicator();
	
	$.getJSON('helpers/statisticsHelper.php', 
	{action:"gettotals", startDateTime:startDate + " 12:00 AM", endDateTime:startDate + " 11:59:59 PM"},
	function(data) {
        if (data.error != undefined) {
            popupNotification("Error: Could not load statistics for this date", 5000);
            return;
        }
        
		intersectionTotalsData = data;
		
		var intersectionHTML = "<table class='table table-striped five'><thead><tr><th>Phase</th><th class='text-center'>Total Vehicles</th><th class='text-center'>Total Pedestrians</th></tr></thead><tbody>";
		
		/*
		for (var i=1; i <= 8; i++) {
			if (data['veh'+i] != undefined) {	
				intersectionHTML += "<tr><td>Phase " + i + "</td>";
				intersectionHTML += "<td class='text-center'>" + data['veh'+i] + "</td>";
				
				if (data['ped'+i] != undefined)
					intersectionHTML += "<td class='text-center'>" + data['ped'+i] + "</td>";
				else
					intersectionHTML += "<td class='text-center'></td>";
				
				intersectionHTML += "</tr>";
			}
		} */
		
		
		for (var i=1; i <= 8; i++)
		{
			if (data['veh'+i] != undefined || data['ped'+i] != undefined)
			{
				intersectionHTML += "<tr><td>Phase " + i + "</td>";
				
				if (data['veh'+i] != undefined)
				{
					intersectionHTML += "<td class='text-center'>" + data['veh'+i] + "</td>";
				}
				else
				{
					intersectionHTML += "<td class='text-center'></td>";
				}
				
				if (data['ped'+i] != undefined)
				{
					intersectionHTML += "<td class='text-center'>" + data['ped'+i] + "</td>";
				}
				else
				{
					intersectionHTML += "<td class='text-center'></td>";
				}
				
				intersectionHTML += "</tr>";
			}
		}
		
		intersectionHTML += "</tbody></table>";
		
		$("#intersection_data").html(intersectionHTML);
	})
	.always(function() {
                hide_busy_indicator();
	});
}

function loadVehicleTab(startDate) {
        show_busy_indicator();
	
	$("#vehicle_chart").height($("#vehicle_chart").width()*0.25);
	$("#vehicle_chart_hourly").height($("#vehicle_chart_hourly").width()*0.25);

	$.getJSON('helpers/statisticsHelper.php', 
	{action:"loadjson", startDateTime:startDate + " 12:00 AM", endDateTime:startDate + " 11:59:59 PM", data: 'vehicle'},
	function(data) {
        if (data.error != undefined) {
            popupNotification("Error: Could not load statistics for this date", 5000);
            return;
        }
		
		vehicleData = data.normal;
        vehicleDataHourly = data.hourly;
        vehicleActive = data.active;
        globalTimeZone = data.timezone;
        flotOptions.xaxis.timezone = globalTimeZone;
		
		for (var i=0; i < vehicleData.length; i++) {
			var pipePos = vehicleData[i].label.indexOf("|");
			var phaseNum = vehicleData[i].label.substr(pipePos + 7, 1);
            
            if ($.inArray(i+1, data.active) == -1)
                vehicleData[i].lines = {show: false};

				activeVehiclePhases[phaseNum] = true;
				showVehiclePhases[phaseNum] = true;		
		}
		
        $.plot($("#vehicle_chart"), vehicleData, flotOptions);
		$("#vehicle_chart").bind("plothover", hoverTooltip);
	})
	.always(function() {
                hide_busy_indicator();
	});
}

function loadPeriodTab(startDate, endDate) {
        show_busy_indicator();
	
	$("#period_chart").height($("#period_chart").width()*0.25);

	$.getJSON('helpers/statisticsHelper.php', 
	{action:"loadjson", startDateTime:startDate + " 12:00 AM", endDateTime:startDate + " 11:59:59 PM", data: 'period'},
	function(data) {
        if (data.error != undefined) {
            popupNotification("Error: Could not load period data for this date", 5000);
            return;
        }
        
        globalTimeZone = data.timezone;
        flotOptions.xaxis.timezone = globalTimeZone;
        
        var flotOptionsPeriod = {
            lines: {show:true, steps: true},
            xaxis: { 
                mode: "time",
                timezone:globalTimeZone,
                timeformat:'%I:%M %P',
                twelveHourClock: false,
                zoomRange: null,
                panRange: null
            },
            zoom: {
                interactive: true,
                trigger: 'dblclick'
            },
            pan: {
                interactive: true,
                cursor: "move"
            },
            grid: { 
            	hoverable: true, 
            	clickable: true 
            }
        };
        
		periodData = data.data;
		$.plot($("#period_chart"), periodData, flotOptionsPeriod);
		$("#period_chart").bind("plothover", hoverTooltip);
	})
	.always(function() {
                hide_busy_indicator();
	});
}

function togglePhase(phaseNum, linkObj, chart) {	
	var displayData = new Array();
	var displayDataHourly = new Array();
	
	// vehicle graph
	if (chart == "veh") {
		showVehiclePhases[phaseNum] = !showVehiclePhases[phaseNum];
		for (var i=1;i<=8;i++) {
			if ($.inArray(i, vehicleActive) == -1) {
				showVehiclePhases[i] = false;	
			}	
			
			if (activeVehiclePhases[i]) {
				vehicleData[i-1].lines = {show: showVehiclePhases[i]};
				displayData.push(vehicleData[i-1]);
			}
		}
		
		$.plot($("#vehicle_chart"), displayData, flotOptions);
		$("#vehicle_chart").bind("plothover", hoverTooltip);
	}
}

function timestampToTime(ts) {
	var dateObj = new timezoneJS.Date(+ts, globalTimeZone);
		
	var month = dateObj.getMonth()+1;
	var day = dateObj.getDate();
	var year = dateObj.getFullYear();

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

	var formattedTime = month + "/" + day + "/" + year + " " + hour + ":" + min + " ";

	if (am)
		formattedTime += "AM";
	else
		formattedTime += "PM";
	
	return formattedTime;
}

function hoverTooltip(event, pos, item) {
	if (pos.x == undefined)
		return false;
	
	$("#x").text(pos.x.toFixed(2));
	$("#y").text(pos.y);

	if (item) {
		$("#tooltip").remove();
		var x = item.datapoint[0];
		var y = item.datapoint[1];

		var formattedTime = timestampToTime(x);
		
		var croppedLabel = item.series.label;
		var pipeIndex = croppedLabel.indexOf("|");
		
		if (pipeIndex != -1)
			croppedLabel = croppedLabel.substring(pipeIndex + 1);

		showTooltip(item.pageX, item.pageY,	croppedLabel + ", at " + formattedTime + " = <strong>" + y + "</strong>");
	} else {
		$("#tooltip").remove();
	}
}

function showTooltip(x, y, contents) {
	$('<div id="tooltip">' + contents + '</div>').css({
		position: 'absolute',
		display: 'none',
		top: y + 5,
		left: x + 5,
		border: '1px solid #009827',
		padding: '2px',
		'background-color': '#c4ffc5',
		opacity: 0.80,
		'z-index': 9999999
	}).appendTo("body").fadeIn(0);
}
