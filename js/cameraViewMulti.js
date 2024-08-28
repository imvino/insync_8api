var oldRefreshRate = 200;
var filter = "normal";
var viewMode = "quad";
var editTarget = "";
var globalRefreshRate = 200;
var readOnly = false;
var globalZoom = 1;
var lastLayout = "quad";
var pauseEnableTimer = 0;

/**
 * Retrieves status XML from InSync
 */
function getStatus() {    
	// do GET request from InSync Interface PHP
	$.get('helpers/insyncInterface.php?action=getStatus', '', function(data) {
		var xmlDoc = data;		
		var xml = $(xmlDoc);
        
        if($(xml).children("error").length != 0)
        {
            status = "Error communicating with InSync";
            $("#insyncStatus").find("div").html(status);
            return;
        }
		
		var status = "";
		var recovery = "No";
		
		if ($(xml).children().find("Intersection").attr("Recovery") == "False")
			recovery = 'No';
		else
			recovery = 'Yes';
		
		status += "<strong>Build:</strong> " + $(xml).children().attr("version") + "<br />";
		status += "<strong>Mode:</strong> " + $(xml).children().attr("Mode") + "<br />";
		status += "<strong>Intersection Name:</strong> " + $(xml).children().find("Intersection").attr("Name") + "<br />";
        
        var ntpStatus = $(xml).children().find("NTP").attr("Status");
        if (ntpStatus == "NOSERVICE")        
            status += "<strong>NTP Status:</strong> <span style='color:red;'>Error communicating with NTP service!</span><br />";
        else if (ntpStatus == "NORESPONSE")        
            status += "<strong>NTP Status:</strong> <span style='color:red;'>Unable to communiate with NTP servers!</span><br />";
        else if (ntpStatus == "EXCEPTION")        
            status += "<strong>NTP Status:</strong> <span style='color:red;'>EXCEPTION THROWN while attempting to communicate with NTP service!</span><br />";
        else if (ntpStatus == "WAITING")        
            status += "<strong>NTP Status:</strong> Waiting to communicate with server...<br />";
        else if (ntpStatus == "NTPNOTCONFIGURED")
            status += "<strong>NTP Status:</strong> <span style='color:red;'>NTP Server not configured!</span><br />";
        else if (ntpStatus == "GOOD")        
            status += "<strong>NTP Status:</strong> <span style='color:green;'>Communicating Properly</span><br />";
        else
            status += "<strong>NTP Status:</strong> " + ntpStatus + "<br />";
        
		status += "<strong>Time:</strong> " + $(xml).children().find("Time").attr("Now") + "<br />";
		status += "<strong>Configuration:</strong> " + $(xml).children().find("Optimizer").attr("CurrentConfiguration") + "<br />";
        
        if($(xml).children().find("Intersection").attr("RailRoad") != "None")
            status += "<strong>Railroad Preemption:</strong> " + $(xml).children().find("Intersection").attr("RailRoad") + "<br />";
		
        if($(xml).children().find("Intersection").attr("Preempt") != "None")
            status += "<strong>Emergency Preemption:</strong> " + $(xml).children().find("Intersection").attr("Preempt") + "<br />";
        
		if($(xml).children().find("Intersection").attr("TSP") == "True")
			status += "<strong>TSP:</strong> " + $(xml).children().find("Intersection").attr("TSPPriority") + " Priority<br />";

		$(xml).children().find("Intersection").find("LightStatus").each(function(index) {
			var TSP = $(this).attr("TSP");
            if (TSP == "True")
                status += "<span style='color:blue;'><strong>Phase " + $(this).attr("Number") + " TSP Actuated</strong></span><br />";
		});

        if(recovery != "No")
            status += "<strong>Recovery Mode:</strong> " + recovery + "<br />";
		
	
		$(xml).children().find("Devices").find("IOBoard").each(function(index) {

			var statusColor = "red";
			if ($(this).attr("Color") == "Color [Green]")
				statusColor = "green";		
		
			status += "<strong>" + $(this).attr("Type") + ":</strong> <span style='color:" + statusColor + ";'>" + $(this).attr("Status") + "</span><br />";
			status += "<strong>&nbsp;&nbsp;Firmware:</strong> " + $(this).attr("Firmware") + "<br />";
		});
		
		var allResponding = true;
		
		$(xml).children().find("Intersection").find("LightStatus").each( function(index) {
			if ($(this).attr("AnalyzerResponding") == "False") {
				allResponding = false;
				return false;
			}
		});
        
        if($(xml).children().find("OfflineMode").attr("Offline") != "False")
        {
            status += "<strong>Offline Mode:</strong> <font color='red'>YES</font><br />";
            status += "<strong>Seconds Until Online:</strong> " + $(xml).children().find("OfflineMode").attr("SecondsUntilOnline") + "<br />";
        }
		
		if (allResponding)
			status += "<strong>Volume Analyzer:</strong> Okay<br />";
		else
			status += "<strong>Volume Analyzer:</strong> Unresponsive<br />";
			
		$(xml).children().find("Intersection").find("LightStatus").each(function(index) {
			var responding = $(this).attr("AnalyzerResponding");

			$(this).find("SubPhase").each( function(spIndex) {
				var sphParent = $(this).attr("ParentPhase");
				var sphOrder = $(this).attr("Order");
				var invalidCount = $(this).attr("InvalidCount");
				var noChange = $(this).attr("NoDetChange");
				var noChangeMax = $(this).attr("NoDetChangeMax");
				
				if (invalidCount == "True")
                    status += "<span style='color:red;'><strong>Subphase " + sphParent + "-" + sphOrder + "</strong>: Invalid Count!</span><br />";
                if (noChange == "True")
                    status += "<span style='color:blue;'><strong>Subphase " + sphParent + "-" + sphOrder + "</strong>: No Detector Change!</span><br />";
                if (noChangeMax == "True")
                    status += "<span style='color:red;'><strong>Subphase " + sphParent + "-" + sphOrder + "</strong>: Failed!</span><br />";
                if (responding == "False")
                    status += "<span style='color:red;'><strong>Subphase " + sphParent + "-" + sphOrder + "</strong>: Unresponsive!</span><br />";
			});
		});
		
		$(xml).children().find("Devices").find("VideoProcessor").each(function(index) {
			if ($(this).attr("Status") != "OK")
				status += "<strong>Video Processor  (" + $(this).attr("IP") + ")</strong>: " + $(this).attr("Status") + "<br />";
		});
        
		$(xml).children().find("Devices").find("InSyncCamera").each(function(index) {
			if ($(this).attr("Status") != "OK")
				status += "<strong>Camera " + $(this).attr("Name") + " (" + $(this).attr("IP") + ")</strong>: " + $(this).attr("Status") + "<br />";
		});        



		$(xml).children().find("Devices").find("ExternalDetector").each(function(index)
        {
			if ($(this).attr("Status") != "OK")
            {
                if($(this).attr("Name").indexOf("Faux") == -1)
                    status += "<strong>Detector " + $(this).attr("Name") + "</strong>: " + $(this).attr("Status") + "<br />";
            }
		});

		$(xml).children().find("Devices").find("Panomorph").each(function(index) {
			if ($(this).attr("Status") != "OK")
				status += "<strong>Panomorph " + $(this).attr("Name") + " (" + $(this).attr("IP") + ")</strong>: " + $(this).attr("Status") + "<br />";
		});

		/*$(xml).children().find("Optimizer").find("SendDownstream").each(function(index) {
			status += "<strong>Send Downstream IP</strong>: " + $(this).attr("IP") + " on Phase " + $(this).attr("Phase") + "<br />";
		});
		
		$(xml).children().find("Optimizer").find("SendUpstream").each(function(index) {
			status += "<strong>Send Upstream IP</strong>: " + $(this).attr("IP") + " on Phase " + $(this).attr("Phase") + "<br />";
		});
		
		$(xml).children().find("Optimizer").find("ReceiveDownstream").each(function(index) {
			status += "<strong>Receive Downstream IP</strong>: " + $(this).attr("IP") + " on Phase " + $(this).attr("Direction") + "<br />";
		});
		
		$(xml).children().find("Optimizer").find("ReceiveUpstream").each(function(index) {
			status += "<strong>Receive Upstream IP</strong>: " + $(this).attr("IP") + " on Phase " + $(this).attr("Direction") + "<br />";
		});*/
        
        var network = $(xml).children().find("Network");
        
        status += "<strong>Intersection IP</strong>: " + network.attr("IP") + "<br />";
        status += "<strong>Intersection Subnet</strong>: " + network.attr("SubnetMask") + "<br />";
        status += "<strong>Intersection Gateway</strong>: " + network.attr("Gateway") + "<br />";
        status += "<strong>Intersection DNS</strong>: " + network.attr("DNS") + "<br />";
        
        if($(xml).children().find("Triggers").find("ActiveTrigger").attr("ID") != undefined)
        {	
        	status += "<strong>Active Triggers</strong>: " + "<br />";
			$(xml).children().find("Triggers").find("ActiveTrigger").each(function(index)
        	{
        		var TriggerType = "";
        		var ConfigName = "";
        		var OmmitedVPhases = "";
        		var OmittedPPhases = "";
        		if ($(this).attr("TriggerType") == "POT")
        		{
        			TriggerType = "Phase Omit";
        			ConfigName = "N/A";
        			OmittedVPhases = $(this).attr("OmittedVehPhases");
        			OmittedPPhases = $(this).attr("OmittedPedPhases");
        		}
        		else
        		{
        			TriggerType = "Configuration Run";
        			ConfigName = $(this).attr("ConfigurationName");
        			OmittedVPhases = "N/A";
        			OmittedPPhases = "N/A";
        		}

               	status += "<strong>Type</strong>: " + TriggerType + 
               			  "<strong> Priority</strong>: " + $(this).attr("Priority") + 
               			  "<strong> Cabinet Signal</strong>: " + $(this).attr("CabinetSignal") + 	
               			  "<strong> Start Condition</strong>: " + $(this).attr("StartCondition") +			
               			  "<strong> End Condition</strong>: " + $(this).attr("EndCondition") +			
               			  "<strong> Configuration Name</strong>: " + ConfigName +		
               			  "<strong> Omitted Vehicle Phases</strong>: " + OmittedVPhases +		
               			  "<strong> Omitted Ped Phases</strong>: " + OmittedPPhases + "<br />";
			});
    	}
		
		$("#insyncStatus").find("div").html(status);
		
	}, 'xml');
}

/**
 * Retrieves processor status XML
 */
function getProcessorStatus() {
	// do GET request from InSync Interface PHP
	$.get('helpers/insyncInterface.php?action=getProcessorStatus', '', function(data) {
		var xmlDoc = data;		
		var xml = $(xmlDoc);
        
        if($(xml).children("error").length != 0)
        {
            status = "Error communicating with InSync";
            $("#processorStatus").find("div").html(status);
            return;
        }
		
		var cores = {};
		var numCores = 0;
		
		$(xml).find("CPU").children().each( function() {
			if (cores[$(this).attr("Core")] == undefined) {
				cores[$(this).attr("Core")] = {};
				numCores++;
			}
			
			if ($(this)[0].nodeName == "Temperature")
				cores[+$(this).attr("Core")].temp = $(this).attr("Temperature") + " &deg;" + $(this).attr("TemperatureScale");
			if ($(this)[0].nodeName == "Load")
				cores[+$(this).attr("Core")].load = $(this).attr("Current") + "%";
		});
		
		var status = "";
		for (var i=0;i<numCores;i++)
			status += "Core #" + i + " - Temp: " + cores[i].temp + ", Load: " + cores[i].load + "<br />";
		
		if(status == "")
		{
			status = "Processor Performance Data Unavailable!"
		}

		$("#processorStatus").find("div").html(status);
		
	}, 'xml');
}

function addRow(cols) {
	for (var i=0; i < cols; i++) {
		var randID = 999+Math.floor(Math.random()*99999999);
		$("#original_items").append('<li><img src="/img/camera-placeholder.png" name="placeholder" width="320" height="240" enablerefresh="false" id="img' + randID + '"/></li>');
	}
	
	saveLayout(true);
}

function deleteRow(cols) {
	for (var i=0; i < cols; i++)
		$("#original_items").children().last().remove();	
	
	saveLayout(true);
}

function resetLayout() {
	$.get('/helpers/cameraViewHelper.php?action=reset&mode=' + viewMode, function(data) {
		location.reload();
	});
}

function expirationReset() {
    expirationTimer = 900;
}

function initScript(refreshRate, mode, fullscreen, zoom, customImgClickHandler) {
	viewMode = mode;
	globalRefreshRate = refreshRate;
	globalZoom = zoom;
    lastLayout = $("#layouts").val();
	
    /// THIS IS REQUIRED ONLY FOR THE KIOSK PAGE
    $('#tabs').tabs({
        active: $('#tabs ul li a').index($('#tabs ul li a[href^="#"]')),
        beforeActivate: function(event, ui){
            var target = ui.newTab.find('a').attr('href');
            if (target.charAt(0) !== '#') {
                window.location.href = target;
            }
        }
    });
    $('#tabs').show();
    /// THIS IS REQUIRED ONLY FOR THE KIOSK PAGE
	
	$("#layouts").change(function() {
        
        if(fullscreen)
            window.location.href = "/cameraViewMulti.php?cameraView=" + $(this).val() + "&fullscreen=true";
        else
            window.location.href = "/cameraViewMulti.php?cameraView=" + $(this).val();
        
		setCookie("viewMode", $(this).val(), 30);
	});
		
	$("#refresh").change(function() {
		var refresh = $(this).val();
		
		// fix for IE being a terrible browser
		if (refresh < 1000)
			if (!jQuery.support.leadingWhitespace)
				refresh = 500;

		changeRefreshRate(refresh);
		setCookie(viewMode, refresh, 30);
	});
	
	$("#manual").button().click(function() {
		$("#dialog-manual-controls").dialog("open");
		$("#dialog-manual-controls").html("Please wait, loading manual controls...");
        
        $("#manual").attr("disabled", "disabled");
		
		$.getJSON('helpers/manualControlHelper.php', {action: 'acquirelock', override: 'false'}, function(data) {
			if (data.error != undefined) {	
				if (data.error == 'locked')
					$("#dialog-manual-controls-override").dialog("open");
			} else {
				if (data.success != undefined) {	
					$.get('helpers/manualControlHelper.php', {action: 'gethtml', readonly: 'false'}, function(data) {
						$("#dialog-manual-controls").html(data);
					});
				}
			}
		});
	});
		
	$("#dialog-manual-controls").dialog({
		autoOpen: false,
        resizable: true,
        modal: false,
        closeText: '×',
        width: 500,
        open: function(event, ui)
        {
            $("#dialog-manual-controls").dialog("option", "position", { my: "left top", at: "right top", of: $("#view_container") });
        },
        beforeClose: function(event, ui)
        {
                //Can use manualControls.js functions no problem, by property of JS
                closeoutJSON(globalactivePhases);

                
            if (!readOnly)
                $.get("/helpers/manualControlHelper.php?action=close&increment=" + (countIncrement++));

            clearInterval(stateTimerID);
            $(window).unbind('beforeunload');
            window.onbeforeunload = null;  
            
            $("#manual").removeAttr("disabled");
        },
		buttons: {
			Close: function() {
                //Can use manualControls.js functions no problem, by property of JS
                closeoutJSON(globalactivePhases);

                $(this).dialog("close");
                $("#manual").removeAttr("disabled");
			}
		}
	});
		
	$("#dialog-manual-controls-override").dialog({
		autoOpen: false,
        resizable: false,
        modal: false,
        closeText: '×',
        width: 445,
		buttons: {
			"Yes": function() {
				$.getJSON('helpers/manualControlHelper.php', {action: 'acquirelock', override: 'true'}, function(data) {
					$.get('helpers/manualControlHelper.php', {action: 'gethtml', readonly: 'false'}, function(data) {
						$("#dialog-manual-controls").html(data);
					});
				});
				
				$(this).dialog("close");
			},
			"Read Only": function() {
				readOnly = true;
				$.get('helpers/manualControlHelper.php', {action: 'gethtml', readonly: 'true'}, function(data) {
					$("#dialog-manual-controls").html(data);
				});
				$(this).dialog("close");
			},
			"No": function() {
				$("#dialog-manual-controls").dialog("close");
				$(this).dialog("close");
			}
		}
	});
		
	var filterCookie = getCookie("filter");
	if (filterCookie !== null) {	
		$("#filter").val(filterCookie).trigger("chosen:updated");
		
		var filter = $("#filter").val();
		setFilter(filter);
	}
	
	$("#filter").change(function() {
		var filter = $(this).val();
		
		setFilter(filter);	
		
		setCookie("filter", filter);
	});

	$("#cameraView").find("a").first().css({
		padding: 3, 
		height: 33
	});
	
	if (refreshRate < 1000) {
        if (!jQuery.support.leadingWhitespace) {
            refreshRate = 1000;
            $("#refresh").val(1000);
            $("#refresh").trigger("chosen:updated");
        }
	}		

	initImageRefresher(refreshRate, customImgClickHandler);

	$("#fullscreen").button().click(function() {			
        var currentLocation = window.location.href;

        if (currentLocation.indexOf("?") == -1)
            window.open(currentLocation + "?fullscreen=true","fs","fullscreen=yes")
        else
            window.open(currentLocation + "&fullscreen=true","fs","fullscreen=yes")
	});
	
	$("#insyncStatus").accordion({
		collapsible: true,
		active: false,
		icons: false,
		heightStyle: "content"
	});
	
	$("#processorStatus").accordion({
		collapsible: true,
		active: false,
		icons: false,
		heightStyle: "content"
	});
	
	$("#zoom-out").button().click(function(e) {
			var newZoom = globalZoom - 1;
			if (newZoom < 0)
				return false;
                        var newWidth = (40 * newZoom) + 320;
                        var newHeight = (30 * newZoom) + 240;
			
			$.get('/helpers/cameraViewHelper.php?action=savezoom&mode=' + viewMode + '&zoom=' + newZoom, function(data) {
				if (data == "Success") {
					$('#view_container img')
                                                .width(newWidth)
                                                .height(newHeight);
                                        $('#view_container').width((newWidth + image_padding) * image_columns + image_extra);
		                        if (newZoom === 0) {
		                            $('#zoom-out').button('disable');
		                        }
                                        globalZoom = newZoom;
                                        updateClones();
                                } else {
					popupNotification("Failed to save new zoom level!", 3500);
                                }
			});
                        e.preventDefault();
		});
		
	$("#zoom-in").button().click(function(e) {
			var newZoom = globalZoom + 1;
                        var newWidth = (40 * newZoom) + 320;
                        var newHeight = (30 * newZoom) + 240;
			
			$.get('/helpers/cameraViewHelper.php?action=savezoom&mode=' + viewMode + '&zoom=' + newZoom, function(data) {
				if (data == "Success") {
					$('#view_container img')
                                                .width(newWidth)
                                                .height(newHeight);
                                        $('#view_container').width((newWidth + image_padding) * image_columns + image_extra);
		                        $('#zoom-out').button('enable');
                                        globalZoom = newZoom;
                                        updateClones();
                                } else {
					popupNotification("Failed to save new zoom level!", 3500);
                                }
			});
                        e.preventDefault();
		});
	
	setInterval(getStatus, 3000);
	setInterval(getProcessorStatus, 3000);
	
    if (mode != "wide-quad") {
        $("#original_items").sortable({
            opacity: 0.5,
            tolerance: "pointer",
            cursor: "move"
        });
        $("#original_items").disableSelection();
    }

    if (fullscreen)
        setInterval(expirationReset, 10000);

    cookieValue = getCookie(viewMode);

    if (cookieValue != null && cookieValue != -1) {
            $("#refresh").val(cookieValue).trigger("chosen:updated");
            if (cookieValue == 200 || cookieValue == 500 || cookieValue == 1000 || cookieValue == 2000 || cookieValue == 5000 || cookieValue == 30000 || cookieValue == 60000)
                    changeRefreshRate(cookieValue);
    }
  
    // hack to make firefox position elements correctly
    setTimeout(updateClones,0);

    // commented out because setTimeout appears to work as well
    //$("#original_items").children("li").children("img").last().one("load", function()
    //{
    //      updateClones();
    //});
	
}

function updateClones() 
{   	
    $("#cloned_items").empty();
    
    // loop through the original items...
    jQuery("#original_items li").each(function() {
        
        // clone the original items to make their
        // absolute-positioned counterparts...
        var item = jQuery(this);
        var item_clone = item.clone();
        // 'store' the clone for later use...
        item.data("clone", item_clone);

        // set the initial position of the clone
        var position = item.position();
        
        item_clone.css("left", position.left);
        item_clone.css("top", position.top);
        item_clone.css("position", "absolute");
        item_clone.css("opacity", "");
        item_clone.css("visibility", "");
        item_clone.css("z-index", "");

        item_clone.find("img[enablerefresh=true]").each(function() {
            enableRefresh(this);
            refreshImage(this);
            $(this).data("paused", "true");
        });
       
        // append the clone...
        jQuery("#cloned_items").append(item_clone);
    });

    // create our sortable as usual...
    // with some event handler extras...
    $("#original_items").sortable(
    {
        // on sorting start, hide the original items...
        // only adjust the visibility, we still need
        // their float positions..!
        start: function(e, ui) 
        { 
            $("#cloned_items").show();
            $("#cloned_items img[enablerefresh=true]").each(function() {
                $(this).data("paused", false);
                var imageElem = this;
                setTimeout( function() {refreshImage(imageElem);}, 1 );
            });
            // loop through the items, except the one we're
            // currently dragging, and hide it...
            ui.helper.addClass("exclude-me");
            jQuery("#original_items li:not(.exclude-me)")
                .css("visibility", "hidden");

            // get the clone that's under it and hide it...
            ui.helper.data("clone").hide();

            disablePause(true);
        },

        stop: function(e, ui) {
            // get the item we were just dragging, and
            // its clone, and adjust accordingly...
            jQuery("#original_items li.exclude-me").each(function() {
                var item = jQuery(this);
                var clone = item.data("clone");
                var position = item.position();

                // move the clone under the item we've just dropped...
                clone.css("left", position.left);
                clone.css("top", position.top);
                clone.show();

                // remove unnecessary class...
                item.removeClass("exclude-me");
            });

            // make sure all our original items are visible again...
            jQuery("#original_items li").css("visibility", "visible");
            // Hide cloned items again
            $("#cloned_items").hide();
            $("#cloned_items img[enablerefresh=true]").data("paused", true);

            saveLayout(false);

	    // more hacky code to fix firefox bugs.
            clearTimeout(pauseEnableTimer);
            pauseEnableTimer = setTimeout(function() { disablePause(false); }, 0);
        },

        // here's where the magic happens...
        change: function(e, ui) {
            // get all invisible items that are also not placeholders
            // and process them when ordering changes...
            jQuery("#original_items li:not(.exclude-me, .ui-sortable-placeholder)").each(function() {
                var item = jQuery(this);
                var clone = item.data("clone");

                // stop current clone animations...
                clone.stop(true, false);

                // get the invisible item, which has snapped to a new
                // location, get its position, and animate the visible
                // clone to it...
                var position = item.position();
                clone.animate({
                    left: position.left,
                    top: position.top
                }, 300);
            });
        }
    });
}

function saveLayout(refresh) {
	var docImages = $('#original_items img');
	var prefs = "";

	for (var i=0; i < docImages.length; i++) {
		var imgName = $(docImages[i]).attr("name");
		prefs += imgName + ",";
	}

	$.get('/helpers/cameraViewHelper.php?action=saveprefs&mode=' + viewMode + '&prefs=' + prefs, function() {
		if (refresh)
			location.reload();
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
