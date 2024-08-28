var vehicleData = "";
var vehicleActive = new Array();
var vehicleDataHourly = "";
var pedestrianData = "";
var pedestrianDataHourly = "";

var activeVehiclePhases = new Array();
var activeVehiclePhasesHourly = new Array();
var activePedestrianPhases = new Array();
var activePedestrianPhasesHourly = new Array();

var showVehiclePhases = new Array();
var showVehiclePhasesHourly = new Array();
var showPedestrianPhases = new Array();
var showPedestrianPhasesHourly = new Array();

var tmcDrawn = false;
var periodData = "";
var tempData = "";
var loadData = "";
var speedData = "";
var splitData = "";
var intersectionTotalsData = "";

var phaseAssociation = "";

var globalTimeZone = "America/Chicago";

var playbackTimer = null;

var phasesWithCamera = new Array();

var flotOptions = {
	xaxis: { 
		mode: "time",
		timezone:"browser",
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
			
			if (prefix == "veh_hourly") {
                if ($.inArray(+phaseNum, vehicleActive) == -1)
                   return;
               
				if (showVehiclePhasesHourly[phaseNum])
					return '<a style="cursor:pointer;color:#535356;" onclick="togglePhase(' + phaseNum + ',$(this),\'veh_hourly\')">' + realLabel + '</a>';
				else
					return '<a style="cursor:pointer;color:#535356;text-decoration:line-through;" onclick="togglePhase(' + phaseNum + ',$(this),\'veh_hourly\')">' + realLabel + '</a>';
			}
			
			if (prefix == "ped") {
				if (showPedestrianPhases[phaseNum])
					return '<a style="cursor:pointer;color:#535356;" onclick="togglePhase(' + phaseNum + ',$(this),\'ped\')">' + realLabel + '</a>';
				else
					return '<a style="cursor:pointer;color:#535356;text-decoration:line-through;" onclick="togglePhase(' + phaseNum + ',$(this),\'ped\')">' + realLabel + '</a>';
			}
			
			if (prefix == "ped_hourly") {
				if (showPedestrianPhasesHourly[phaseNum])
					return '<a style="cursor:pointer;color:#535356;" onclick="togglePhase(' + phaseNum + ',$(this),\'ped_hourly\')">' + realLabel + '</a>';
				else
					return '<a style="cursor:pointer;color:#535356;text-decoration:line-through;" onclick="togglePhase(' + phaseNum + ',$(this),\'ped_hourly\')">' + realLabel + '</a>';
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
		activeVehiclePhasesHourly[i] = false;
		activePedestrianPhases[i] = false;
		activePedestrianPhasesHourly[i] = false;
		
		showVehiclePhases[i] = false;
		showVehiclePhasesHourly[i] = false;
		showPedestrianPhases[i] = false;
		showPedestrianPhasesHourly[i] = false;
	}
	
	$("#startDate").datetimepicker({
		hideIfNoPrevNext: true,
		timeFormat: "hh:mm tt",
		maxDate: '0',
        stepMinute: 15
	});
    
	$("#endDate").datetimepicker({
		hideIfNoPrevNext: true,
		timeFormat: "hh:mm tt",
		maxDate: '0',
        stepMinute: 15
	});
    
    $.getJSON("/helpers/statisticsHelper.php?action=getoldest", function(data)
    {
        if(data.month != -1 && data.year != -1 && data.day != -1)
        {
            $("#startDate").datetimepicker("destroy");
            
            $("#startDate").datetimepicker({
                hideIfNoPrevNext: true,
                timeFormat: "hh:mm tt",
                maxDate: '0',
                minDate: new Date(data.year, data.month-1, data.day),
                stepMinute: 15
            });
            
            $("#endDate").datetimepicker("destroy");
            
            $("#endDate").datetimepicker({
                hideIfNoPrevNext: true,
                timeFormat: "hh:mm tt",
                maxDate: '1',
                minDate: new Date(data.year, data.month-1, data.day),
                stepMinute: 15
            });
        }
    });
	
	$("#tabs").hide();
	$("#tabs").tabs();

	$('.ui-tabs .ui-tabs-nav li:last-child').borderRadius('0 3px 0 0');
	
	$("a[href=#tabs-1]").click(function() {		
		if (vehicleData.length != 0 && vehicleDataHourly.length != 0)
			return;
		
		var startDate = $("#startDate").val();
		var endDate = $("#endDate").val();
		
		loadVehicleTab(startDate, endDate);
	});
	
	$("a[href=#tabs-2]").click(function() {
		if (tempData.length != 0 && loadData.length != 0)
			return;
		
		var startDate = $("#startDate").val();
		var endDate = $("#endDate").val();
		
		loadProcessorTab(startDate, endDate);
	});
	
	$("a[href=#tabs-3]").click(function() {		
		if (pedestrianData.length != 0 && pedestrianDataHourly.length != 0)
			return;
		
		var startDate = $("#startDate").val();
		var endDate = $("#endDate").val();
		
		loadPedestrianTab(startDate, endDate);
	});
	
	$("a[href=#tabs-4]").click(function() {		
		if (periodData.length != 0)
			return;
		
		var startDate = $("#startDate").val();
		var endDate = $("#endDate").val();
		
		loadPeriodTab(startDate, endDate);
	});
	
	$("a[href=#tabs-5]").click(function() {		
		if (intersectionTotalsData.length != 0)
			return;
		
		var startDate = $("#startDate").val();
		var endDate = $("#endDate").val();
		
		loadTotalsTab(startDate, endDate);
	});
	
	$("a[href=#tabs-6]").click(function() {
        
		if (vehicleData.length != 0 && tmcDrawn == true)
			return;
		
		var startDate = $("#startDate").val();
		var endDate = $("#endDate").val();
		
                initCanvas();
		loadTMCTab(startDate, endDate);
	});
    
    $("a[href=#tabs-7]").click(function() {		
		if (splitData.length != 0)
			return;
		
		var startDate = $("#startDate").val();
		var endDate = $("#endDate").val();
		
		loadSplitTab(startDate, endDate);
	});
	
	$("#load").button().click(function() {
		$("#tabs").fadeIn(350);
        	$("#tabs").children("ul").children("li").css("list-style","none");
		
		vehicleData = "";
		vehicleDataHourly = "";
		pedestrianData = "";
		pedestrianDataHourly = "";
		periodData = "";
		
		intersectionTotalsData = "";
		tempData = "";
		loadData = "";
					
		// hide all phases
		for (var i=1;i <= 8; i++) {
			activeVehiclePhases[i] = false;
			activeVehiclePhasesHourly[i] = false;
			activePedestrianPhases[i] = false;
			activePedestrianPhasesHourly[i] = false;

			showVehiclePhases[i] = false;
			showVehiclePhasesHourly[i] = false;
			showPedestrianPhases[i] = false;
			showPedestrianPhasesHourly[i] = false;
		}
		
		var selectedTab = $("#tabs").tabs('option', 'active');
		var startDate = $("#startDate").val();
		var endDate = $("#endDate").val();
		
		// get a list of phases with cameras
		$.get("/helpers/FileIOHelper.php?action=phasesWithCamera", function(cameraPhases)
		{
			phasesWithCamera=cameraPhases;

			// based on the system type and phases with cameras show the different reporting tabs
			$.get("/helpers/FileIOHelper.php?action=getSystemConfigurationType", function(systemType)
			{
				if (systemType == "" || systemType == 0 || phasesWithCamera.length > 0)
				{
					// vehicle tab
					if (selectedTab == 0)
						loadVehicleTab(startDate, endDate);
					// TMC tab
					else if (selectedTab == 1)
						loadTMCTab(startDate, endDate);
					// vehicle tab
					else if (selectedTab == 2)
						loadSplitTab(startDate, endDate);
					// ped tab
					else if (selectedTab == 3)
						loadPedestrianTab(startDate, endDate);
					// period tab
					else if (selectedTab == 4)
						loadPeriodTab(startDate, endDate);
					// totals tab
					else if (selectedTab == 5)
						loadTotalsTab(startDate, endDate);
					// processor tab
					else if (selectedTab == 6)
						loadProcessorTab(startDate, endDate);
				}
				else
				{
					// period tab
					if (selectedTab == 0)
						loadPeriodTab(startDate, endDate);
					// splits tab
					else if (selectedTab == 1)
						loadSplitTab(startDate, endDate);
					// ped tab
					else if (selectedTab == 2)
						loadPedestrianTab(startDate, endDate);				
					// processor tab
					else if (selectedTab == 3)
						loadProcessorTab(startDate, endDate);				
				}
			
			});
		
		});
		
	});
	
	$("#downloadDialog").dialog({
		autoOpen: false,
		resizable: false,
		modal: true,
		closeText: '×',
		width: 565,
		buttons: {
			"Download CSV": function() {
				var startDate = $("#startDate").val();
				var endDate = $("#endDate").val();
				var formData = $("#downloadForm").serialize();
				var url = "helpers/statisticsHelper.php?action=downloadcsv&startDateTime=" + startDate + "&endDateTime=" + endDate + "&" + formData;
				
				window.open(url,'download')
			},
			//"Download Raw Files": function() {
			//	var startDate = $("#startDate").val();
			//	var endDate = $("#endDate").val();
			//	var url = "helpers/statisticsHelper.php?action=downloadraw&startDateTime=" + startDate + "&endDateTime=" + endDate;
				
			//	window.open(url,'download')
			//},
			"Cancel": function() {
				$(this).dialog("close");
			}
		}
	});
		
	$("#playbackSlider").slider({
		value: 0,
		min: 0,
		max: 86400000,
		step: 900000,
		slide: function(event, ui) {
			var formattedTime = timestampToTime(ui.value);
			$("#playbackTime").text(formattedTime);
			
			drawCanvas(ui.value);
		}
	});
	
	$("#playbackToggle").button().click(function() {
		if (playbackTimer == null) {
			playbackTimer = setInterval(playbackAdvance, 1000);
			$(this).text("Pause");
		} else {
			clearInterval(playbackTimer);
			playbackTimer = null;
			$(this).text("Play");
		}
	});
});

function playbackAdvance() {
	var curVal = $("#playbackSlider").slider("option", "value");
	var maxVal = $("#playbackSlider").slider("option", "max");
	
	if (curVal >= maxVal) {
		clearInterval(playbackTimer);
		playbackTimer = null;
		return;
	}
	
	curVal += 900000;
	
	$("#playbackSlider").slider("option", "value", curVal);
	
	var formattedTime = timestampToTime(curVal + 900000);
	$("#playbackTime").text(formattedTime);

	drawCanvas(curVal);
}


function loadSplitTab(startDate, endDate) {
        show_busy_indicator();
			
	$.getJSON('helpers/statisticsHelper.php', 
	{action:"getsplits", startDateTime:startDate, endDateTime:endDate},
	function(data) {
        if (data.error != undefined) {
            popupNotification("Error: Could not load statistics for this date", 5000);
            return;
        }
        
        splitData = data;
        
        var flotOptionsSplits = {
            grid: {
                hoverable: true
            },
            yaxis: {
                axisLabel: "<img src='/img/chart-splits-helper.png' style='width:45px;'/>",
                axisLabelUseCanvas: false
            },
            xaxis: { 
                ticks: data.chart1.ticks,
                font: {
                	size: 12, 
                	color: "#535356"
                }
            }
        };
        
        function labelFormatter(label, series) {
            return "<div style='font-size:16px;text-align:center;padding:2px;'>" + label + "<br/>" + Math.round(series.percent) + "%</div>";
        }
        
        function pieChartOptions(color) {
            var flotOptionsPie = {
                series: {
                    pie: { 
                        show: true,
                        label: {
                            show: true,
                            radius: 1,
                            formatter: labelFormatter
                        }
                    },
                    color: color
                },
                legend: {
                    show: false
                }
            };
            return flotOptionsPie;
        }
        
        function chartDataOptions(barData, errorData, color, fillColor) {
            var chartData = [{
                data: barData,
                color: color, 
                bars: { 
                    show: true, 
                    align: "center", 
                    barWidth: 0.9, 
                    fillColor: fillColor 
				}
			}, {
                data: errorData,
                color: "#535356",
                points: {
                    show: true,
                    radius: 10,
                    errorbars: "y", 
                    yerr: {
                        show:true,
                        asymmetric:true,
                        upperCap: "-", 
                        lowerCap: "-"}
                }
			}];
            return chartData;
        }
        
        $.plot("#split_chart_1", chartDataOptions(data.chart1.bars, data.chart1.errors, "#00990A", "rgba(61,204,0,0.7)"), flotOptionsSplits);
        $.plot("#split_chart_2", data.chart2.data, pieChartOptions("rgba(61,204,0,0.8)"));
        
        $(window).resize(function() {
            $.plot("#split_chart_1", chartDataOptions(data.chart1.bars, data.chart1.errors, "#00990A", "rgba(61,204,0,0.7)"), flotOptionsSplits);
            $.plot("#split_chart_2", data.chart2.data, pieChartOptions("rgba(61,204,0,0.8)"));
        });
        
        $("#split_chart_1").bind("plothover", hoverTooltipSplits);
        
        $(".axisLabels").css({top: "-=90px"});
	})
	.always(function() {
                hide_busy_indicator();
	})
    .fail(function() {
        popupNotification("There was an error loading your request. If this problem persists, please contact Rhythm Engineering.", 5000);
    });
}

function loadTotalsTab(startDate, endDate) {
        show_busy_indicator();
			
	$.getJSON('helpers/statisticsHelper.php', 
	{action:"gettotals", startDateTime:startDate, endDateTime:endDate},
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
	})
    .fail(function() {
        popupNotification("There was an error loading your request. If this problem persists, please contact Rhythm Engineering.", 5000);
    });
}

function loadProcessorTab(startDate, endDate) {
        show_busy_indicator();
	
	$("#temp_chart").height($("#temp_chart").width()*0.25);
	$("#load_chart").height($("#load_chart").width()*0.25);
    
    if($("#speed_chart").length != 0)
        $("#speed_chart").height($("#speed_chart").width()*0.25);
	
	$.getJSON('helpers/statisticsHelper.php', 
	{action:"loadjson", startDateTime:startDate, endDateTime:endDate, data: 'tempload'},
	function(data) {
        if (data.error != undefined) {
            popupNotification("Error: Could not load CPU statistics for this date", 5000);
            return;
        }
        
		tempData = data.temp;
        loadData = data.load;
        speedData = data.speed;
        globalTimeZone = data.timezone;
        
        function findMaxMin(data) {
            var minY = 1000;
            var maxY = 0;
        
            for (var i=0; i < data.length; i++) {
                for (var j=0; j < data[i].data.length; j++) {
                    if (data[i].data[j][1] > maxY)
                        maxY = data[i].data[j][1];

                    if (data[i].data[j][1] < minY)
                        minY = data[i].data[j][1];
                }
            }
            return {maxY: maxY, minY: minY};
        }
        
        var tempMaxMin = findMaxMin(tempData);
        var loadMaxMin = findMaxMin(loadData);     
        var speedMaxMin = findMaxMin(speedData);  
        
        var flotOptionsCPU = {
            xaxis: { 
                mode: "time",
                timezone: globalTimeZone,
                timeformat: '%I:%M %P',
                twelveHourClock: false,
                zoomRange: null,
                panRange: null
            },
            yaxis: {
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
		
		flotOptionsCPU.xaxis.panRange = [tempData[0].data[0][0], tempData[0].data[tempData[0].data.length - 1][0]];
		flotOptionsCPU.yaxis.panRange = [tempMaxMin.minY - 2, tempMaxMin.maxY + (tempMaxMin.maxY * 0.0274)];
		$.plot($("#temp_chart"), tempData, flotOptionsCPU);
		$("#temp_chart").bind("plothover", hoverTooltip);
        
        flotOptionsCPU.xaxis.panRange = [loadData[0].data[0][0], loadData[0].data[loadData[0].data.length - 1][0]];
		flotOptionsCPU.yaxis.panRange = [loadMaxMin.minY - 2, loadMaxMin.maxY + (loadMaxMin.maxY * 0.0274)];
		$.plot($("#load_chart"), loadData, flotOptionsCPU);
		$("#load_chart").bind("plothover", hoverTooltip);
        
        if($("#speed_chart").length != 0)
        {
            flotOptionsCPU.xaxis.panRange = [speedData[0].data[0][0], speedData[0].data[speedData[0].data.length - 1][0]];
            flotOptionsCPU.yaxis.panRange = [speedMaxMin.minY - 2, speedMaxMin.maxY + (speedMaxMin.maxY * 0.0274)];
            $.plot($("#speed_chart"), speedData, flotOptionsCPU);
            $("#speed_chart").bind("plothover", hoverTooltip);
        }
	})
	.always(function() {
                hide_busy_indicator();
	})
    .fail(function() {
        popupNotification("There was an error loading your request. If this problem persists, please contact Rhythm Engineering.", 5000);
    });
}

function loadTMCTab(startDate, endDate) 
{
        show_busy_indicator();

	$.getJSON('helpers/statisticsHelper.php', 
	{action:"loadjson", startDateTime:startDate, endDateTime:endDate, data: 'vehicle'},
	function(data) {		
        if (data.error != undefined) {
            popupNotification("Error: Could not load statistics for this date", 5000);
            return;
        }
        
		vehicleData = data.normal;
        globalTimeZone = data.timezone;        
			
		$("#playbackSlider").slider("option", "min", vehicleData[0].data[0][0]);
		$("#playbackSlider").slider("value", vehicleData[0].data[0][0]);
		$("#playbackSlider").slider("option", "max", vehicleData[0].data[vehicleData[0].data.length - 1][0]);
		
		var formattedTime = timestampToTime(vehicleData[0].data[0][0]);
		$("#playbackTime").text(formattedTime);
		
		$.getJSON('helpers/statisticsHelper.php', 
		{action:"getactivedirections"},
		function(pdata) {		
			phaseAssociation = pdata;
		
			drawCanvas(vehicleData[0].data[0][0]);
            
            tmcDrawn = true;
		});
	})
	.always(function() {
                hide_busy_indicator();
	})
    .fail(function() {
        popupNotification("There was an error loading your request. If this problem persists, please contact Rhythm Engineering.", 5000);
    });
}

function loadVehicleTab(startDate, endDate) {
        $("#please-wait").show().position({of: $(window)});
	
	$("#vehicle_chart").height($("#vehicle_chart").width()*0.25);
	$("#vehicle_chart_hourly").height($("#vehicle_chart_hourly").width()*0.25);

	$.getJSON('helpers/statisticsHelper.php', 
	{action:"loadjson", startDateTime:startDate, endDateTime:endDate, data: 'vehicle'},
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
        
        for (var i=0; i < vehicleDataHourly.length; i++) {            
			var pipePos = vehicleDataHourly[i].label.indexOf("|");
			var phaseNum = vehicleDataHourly[i].label.substr(pipePos + 7, 1);
            
            if ($.inArray(i+1, data.active) == -1)
                vehicleDataHourly[i].lines = {show: false};

				activeVehiclePhasesHourly[phaseNum] = true;
				showVehiclePhasesHourly[phaseNum] = true;
		}
		
		$.plot($("#vehicle_chart_hourly"), vehicleDataHourly, flotOptions);
		$("#vehicle_chart_hourly").bind("plothover", hoverTooltip);
	})
	.always(function() {
                hide_busy_indicator();
	})
    .fail(function() {
        popupNotification("There was an error loading your request. If this problem persists, please contact Rhythm Engineering.", 5000);
    });
}

function loadPedestrianTab(startDate, endDate) {
        show_busy_indicator();
	
	$("#pedestrian_chart").height($("#pedestrian_chart").width()*0.25);
	$("#pedestrian_chart_hourly").height($("#pedestrian_chart_hourly").width()*0.25);

	$.getJSON('helpers/statisticsHelper.php', 
	{action:"loadjson", startDateTime:startDate, endDateTime:endDate, data: 'pedestrian'},
	function(data) {
        if (data.error != undefined) {
            popupNotification("Error: Could not load statistics for this date", 5000);
            return;
        }
        
		pedestrianData = data.normal;
        pedestrianDataHourly = data.hourly;
        globalTimeZone = data.timezone;
        flotOptions.xaxis.timezone = globalTimeZone;

		for (var i=0; i < pedestrianData.length; i++)
        {
			var pipePos = pedestrianData[i].label.indexOf("|");
			var phaseNum = pedestrianData[i].label.substr(pipePos + 7, 1);
            
            pedestrianData[i].lines = {show: true};
			
			activePedestrianPhases[phaseNum] = true;            
			showPedestrianPhases[phaseNum] = true;
		}
		
		$.plot($("#pedestrian_chart"), pedestrianData, flotOptions);
		$("#pedestrian_chart").bind("plothover", hoverTooltip);
        
        for (var i=0; i < pedestrianDataHourly.length; i++) 
        {
			var pipePos = pedestrianDataHourly[i].label.indexOf("|");
			var phaseNum = pedestrianDataHourly[i].label.substr(pipePos + 7, 1);
            
            pedestrianDataHourly[i].lines = {show: true};
			
			activePedestrianPhasesHourly[phaseNum] = true;
			showPedestrianPhasesHourly[phaseNum] = true;
		}
        
        $.plot($("#pedestrian_chart_hourly"), pedestrianDataHourly, flotOptions);
		$("#pedestrian_chart_hourly").bind("plothover", hoverTooltip);
	})
	.always(function() {
                hide_busy_indicator();
	})
    .fail(function() {
        popupNotification("There was an error loading your request. If this problem persists, please contact Rhythm Engineering.", 5000);
    });
}

function loadPeriodTab(startDate, endDate) {
        show_busy_indicator();
	
	$("#period_chart").height($("#period_chart").width()*0.25);

	$.getJSON('helpers/statisticsHelper.php', 
	{action:"loadjson", startDateTime:startDate, endDateTime:endDate, data: 'period'},
	function(data) {
        if (data.error != undefined) {
            popupNotification("Error: Could not load statistics for this date", 5000);
            return;
        }
        
        globalTimeZone = data.timezone;
        flotOptions.xaxis.timezone = globalTimeZone;
        
        var flotOptionsPeriod = {
            lines: {
            	show: true, 
            	steps: true
            },
            xaxis: { 
                mode: "time",
                timezone: globalTimeZone,
                timeformat: '%I:%M %P',
                twelveHourClock: false,
                zoomRange: null,
                panRange: null,
                min: (new Date(startDate)).getTime(),
                max: (new Date(endDate)).getTime()
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
		$.plot($("#period_chart"), data.data, flotOptionsPeriod);
		$("#period_chart").bind("plothover", hoverTooltip);
	})
	.always(function() {
                hide_busy_indicator();
	})
    .fail(function() {
        popupNotification("There was an error loading your request. If this problem persists, please contact Rhythm Engineering.", 5000);
    });
}

function togglePhase(phaseNum, linkObj, chart)
{    
    function checkVehiclePhases(chart, showPhases, activePhases, data) 
    {
        var displayData = new Array();
        
        showPhases[phaseNum] = !showPhases[phaseNum];
       		
		for (var i=1;i<=8;i++) 
        {	
			if ($.inArray(i, vehicleActive) == -1) {
				showPhases[i] = false;	
			}
			
			if (activePhases[i]) 
            {
				data[i-1].lines = {show: showPhases[i]};
				displayData.push(data[i-1]);
			}
		}
        
        $.plot($(chart), displayData, flotOptions);
		$(chart).bind("plothover", hoverTooltip);
    }
    
    function checkPedPhases(chart, showPhases, activePhases, data) 
    {
        var displayData = new Array();
        
        showPhases[phaseNum] = !showPhases[phaseNum];

        for(var i=0;i<showPhases.length;i++)
        {
            var dataTarget = -1;
            
            for(var j=0;j<data.length;j++)
            {
                if(data[j].label.indexOf("Phase " + i) != -1)
                {
                    dataTarget = j;
                    break;
                }
            }
            
            if(dataTarget == -1)
                continue;
            
            if(showPhases[i])
                data[dataTarget].lines = {show: true};
            else
                data[dataTarget].lines = {show: false};
            
            displayData.push(data[dataTarget]);
        }

        $.plot($(chart), displayData, flotOptions);
		$(chart).bind("plothover", hoverTooltip);
    }
	
	// vehicle graph
	if (chart == "veh")
        checkVehiclePhases("#vehicle_chart", showVehiclePhases, activeVehiclePhases, vehicleData);
	
	// vehicle hourly graph
	if (chart == "veh_hourly")
	    checkVehiclePhases("#vehicle_chart_hourly", showVehiclePhasesHourly, activeVehiclePhasesHourly, vehicleDataHourly);
	
	// pedestrian graph
	if (chart == "ped")
        checkPedPhases("#pedestrian_chart", showPedestrianPhases, activePedestrianPhases, pedestrianData);
	
	// pedestrian hourly graph
	if (chart == "ped_hourly")
        checkPedPhases("#pedestrian_chart_hourly", showPedestrianPhasesHourly, activePedestrianPhasesHourly, pedestrianDataHourly);
}

function hoverTooltipSplits(event, pos, item) {
	if (pos.x == undefined)
		return false;
 
	$("#x").text(pos.x.toFixed(2));
	$("#y").text(pos.y);

	if (item) {
		$("#tooltip").remove();
		var max = item.datapoint[1];
		var min = item.datapoint[2];
        
        if (item.datapoint[3] != undefined)
            showTooltip(item.pageX, item.pageY, "Average: " + max);
        else
            showTooltip(item.pageX, item.pageY, "Maximum: " + max + " <br />Minimum: " + min);
	} else {
		$("#tooltip").remove();
	}
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

		formattedTime = timestampToTime(x);

		var croppedLabel = item.series.label;
		var pipeIndex = croppedLabel.indexOf("|");
		
		if (pipeIndex != -1)
			croppedLabel = croppedLabel.substring(pipeIndex + 1);

		showTooltip(item.pageX, item.pageY,	croppedLabel + ", at " + formattedTime + " = <strong>" + y + "</strong>");
	} else {
		$("#tooltip").remove();
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
