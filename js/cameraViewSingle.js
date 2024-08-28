var statusCam = "North Bound";

function customClickHandler(elem)
{
	if (event.ctrlKey && $("#background").length > 0)		// they can Ctrl+Click if they can grab backgrounds...
		$.post("/helpers/cameraioInterface.php", {action: "grab_segment", camera: $("#camera").val(), locationX: event.offsetX, locationY: event.offsetY});
	else
		cameraClick(elem);
}

function initScript() {
	initImageRefresher(2000, customClickHandler);
	
	$("#camera").change(function() {
		var filter = $("#filter").val();
		var newCam = $("#camera").val();
		
		setFilter(filter);		
		$(".cameraView").attr("baseURL", "helpers/insyncInterface.php?action=getImage&viewCamera=" + newCam);
        
        statusCam = newCam;
        
        $("#cameraStatus").find("div").html("Waiting for status from camera...");
		
		setCookie("viewCamera", newCam);
	});
	
	$("#camera").change();
		
	$("#speed").change(function() {
		var speed = $(this).val();			
		changeRefreshRate(speed);			
		setCookie("speed", speed);
	});
		
	$("#filter").change(function() {
		var filter = $("#filter").val();
		var newCam = $("#camera").val();
		
		setFilter(filter);	
		$(".cameraView").attr("baseURL", "helpers/insyncInterface.php?action=getImage&viewCamera=" + newCam);
		
		setCookie("filter", filter);
	});
		
	var oldView = getCookie("viewCamera");
	if (oldView != null) {
		$("#camera").children().each(function() {			
			if ($(this).val() == oldView)
				$(this).prop("selected", 'selected');
			else
				$(this).prop("selected", '');
		});
		
		var filter = $("#filter").val();
		var newCam = $("#camera").val();
        statusCam = newCam;
		setFilter(filter);
		$(".cameraView").attr("baseURL", "helpers/insyncInterface.php?action=getImage&viewCamera=" + newCam);
	}
	
	var filter = getCookie("filter");
	if (filter != null) {	
		$("#filter").children().each(function() {
			if ($(this).val() == filter)
				$(this).prop("selected", 'selected');
			else
				$(this).prop("selected", '');
		});
		
		var filter = $("#filter").val();
		var newCam = $("#camera").val();
		setFilter(filter);
		$(".cameraView").attr("baseURL", "helpers/insyncInterface.php?action=getImage&viewCamera=" + newCam);
	}
	
	var speed = getCookie("speed");
	if (speed != null) {
		$("#speed").children().each(function() {			
			if ($(this).val() == speed)
				$(this).prop("selected", 'selected');
			else
				$(this).prop("selected", '');
		});
		
		changeRefreshRate( speed );
	}
	
	$("#reboot").button().click(function() {
		$.post("/helpers/cameraioInterface.php", {action: "reboot", camera: $("#camera").val()});
	});
		
	$("#background").button().click(function() {
		$.post("/helpers/cameraioInterface.php", {action: "background", camera: $("#camera").val()});
	});
		
	$("#emergency").button().click(function() {
		$.post("/helpers/cameraioInterface.php", {action: "emergency", camera: $("#camera").val()});
	});
		
	$("#fog").button().click(function() {
		$.post("/helpers/cameraioInterface.php", {action: "fog", camera: $("#camera").val()});
	});
		
	$("#record").button().click(function() {
		popupNotification("WARNING: Recording video takes up a lot of disk space, and no checks are made to prevent filling up the drive. Use with caution, and do not record more than 5 minutes of video at a time.", 300000)
		$.post("/helpers/cameraioInterface.php", {action: "record", camera: $("#camera").val()});
        
        getCameraStatus();
	});
        
    setInterval(getCameraStatus, 3000);
    
    $("#cameraStatus").accordion({
		collapsible: true,
		active: false,
		heightStyle: "content"
	});
}


/**
 * Retrieves processor status XML
 */
function getCameraStatus() {
    // do GET request from InSync Interface PHP
	$.getJSON('helpers/insyncInterface.php?action=getCameraStatus&deviceName=' + statusCam, '', function(data) {    
        var statusHTML = "";
        
		$.each(data, function(key, value) {
            statusHTML += "<strong>" + key + "</strong>: " + value + "<br />";
            
            if (key == "Recording" && value == "Yes")
                $("#record").find("span").text("Stop Recording Video");
            else if (key == "Recording" && value == "No")
                $("#record").find("span").text("Record Video");
        });
        
        $("#cameraStatus").find("div").html(statusHTML);
	});
}

function setCookie(c_name, value) {
	var exdate = new Date();
	exdate.setDate(exdate.getDate() + 15);
	var c_value = escape(value) + "; expires=" + exdate.toUTCString();
	document.cookie = c_name + "=" + c_value;
}

function getCookie(c_name) {
	var c_value = document.cookie;
	var c_start = c_value.indexOf(" " + c_name + "=");
	if (c_start == -1) {
		c_start = c_value.indexOf(c_name + "=");
	}
	if (c_start == -1) {
		c_value = null;
	} else {
		c_start = c_value.indexOf("=", c_start) + 1;
		var c_end = c_value.indexOf(";", c_start);
		if (c_end == -1) {
			c_end = c_value.length;
		}
		c_value = unescape(c_value.substring(c_start, c_end));
	}
	return c_value;
}
