// holds the schedule of current and future recordings
var schedule = {};

var intervalID = 0;

function initScripts(driveList) 
{
    for(var i=0; i < driveList.length; i++)
    {
        var target = driveList[i];
        $("#drive_" + target).change(function()
        {
            updateDrive(target);
        });
    }
    
	$("#add-recording").button().click(function() {
		$("#dialog-add").dialog("open");
		intervalID = setInterval(updateDiskCost, 500);
	});

	$('#users').tooltip({
        position: {
            my: 'center bottom-15',
            at: 'center top',
            using: function(position, feedback) {
                $(this).css(position);
                $('<div>')
                    .addClass('tooltip-arrow')
                    .addClass(feedback.vertical)
                    .addClass(feedback.horizontal)
                    .appendTo(this);
            }
        }
    });

	$('.chosen-dialog').chosen({
		disable_search: true,
		single_backstroke_delete: false,
		inherit_select_classes: true
	});

	$("#specificDateInput").datepicker({
		hideIfNoPrevNext: true,
		minDate: '0'
	});
	$('#ui-datepicker-div').addClass('dialog-datepicker');
	$("#duration").spinner();

	$("#time").slider({
		min: 0,
		max: 1439,
		slide: function(event, ui) {
			var mins = ui.value;

			var today = new Date();
			var hours = Math.floor(mins / 60);
			var minutes = mins - (hours * 60);

			var value = new Date(today.getFullYear(), today.getMonth(), today.getDate(), hours, minutes, 0);

			if (hours < 10)
				hours = "0" + hours;
			if (minutes < 10)
				minutes = "0" + minutes;

			$("#timeVal").text(hours + ":" + minutes);
		}
	});

	$("#dialog-add").dialog({
		dialogClass: 'add-recording-dialog',
		autoOpen: false,
        resizable: false,
        modal: true,
        closeText: '×',
        width: 680,
		buttons: {
			"Add": function() {
				var camera = $("#camera").val();
				var framerate = $("#framerate").val();
				var timestamp = $('input[name=timestamp]:checked').val();
				var dateType = $('input[name=recordDate]:checked').val();
				var time = $("#timeVal").text();
				var duration = $("#duration").spinner("value")
				var storage = $("#storageReq").text();

				if (parseInt(duration) < 1) {
					$("#duration").spinner("value", 1);
					alert("Duration must be 1 or more minutes.");
					return false;
				}
				
				duration *= 60;

				if (timestamp == "on")
					timestamp = "Y";
				else
					timestamp = "N";

				if (dateType == "specific") {
					var date = $("#specificDateInput").datepicker("getDate");

					var month = date.getMonth() + 1;
					var day = date.getDate();
					var year = date.getFullYear();

					if (month < 10)
						month = "0" + month;
					if (day < 10)
						day = "0" + day;

					$.get('/helpers/recordingHelper.php?action=add&startDay=' + month+"/"+day+"/"+year + "&cam=" + camera + "&fps=" + framerate + "&startTime=" + time + "&duration=" + duration + "&timestamp=" + timestamp , function(ret) {
						$.get('/helpers/recordingHelper.php?action=view', function(data) {
							$('#users tbody').html(data);
						});
					});
				} else if (dateType == "recurring") {
					var sun = $('input[name=sunday]:checked').val();
					var mon = $('input[name=monday]:checked').val();
					var tues = $('input[name=tuesday]:checked').val();
					var wed = $('input[name=wednesday]:checked').val();
					var thurs = $('input[name=thursday]:checked').val();
					var fri = $('input[name=friday]:checked').val();
					var sat = $('input[name=saturday]:checked').val();

					if (sun == undefined && mon == undefined && tues == undefined && wed == undefined && thurs == undefined && fri == undefined && sat == undefined) {
						alert("No days selected");
						return false;
					}

					if (sun == false && mon == false && tues == false && wed == false && thurs == false && fri == false && sat == false) {
						alert("No days selected");
						return false;
					}

					if (sun == 'off' && mon == 'off' && tues == 'off' && wed == 'off' && thurs == 'off' && fri == 'off' && sat == 'off') {
						alert("No days selected");
						return false;
					}

					var days = "";
					if (sun == 'on')
						days += "S";
					if (mon == 'on')
						days += "M";
					if (tues == 'on')
						days += "T";
					if (wed == 'on')
						days += "W";
					if (thurs == 'on')
						days += "H";
					if (fri == 'on')
						days += "F";
					if (sat == 'on')
						days += "A";

					$.get('/helpers/recordingHelper.php?action=add&startDay=' + days + "&cam=" + camera + "&fps=" + framerate + "&startTime=" + time + "&duration=" + duration + "&timestamp=" + timestamp , function(ret) {
						$.get('/helpers/recordingHelper.php?action=view', function(data) {
							$('#users tbody').html(data);
						});
					});
				}
				$(this).dialog("close");
			},
			Cancel: function() {
				$(this).dialog("close");
				clearInterval(intervalID);
			}
		},
		close: function() {
			clearInterval(intervalID);
		}
	});
	
	$.get('/helpers/recordingHelper.php?action=view', function(data) {
		$('#users tbody').html(data);
	});
	
	updateDrive('');
}

function updateDrive(driveLetter) 
{    
	if (driveLetter.length == 0) {
		$.get('/helpers/recordingHelper.php?action=getdrive', function(data) {
            if (data == "") {
                $("#add-recording").hide();
                $("#errorNotice").show();
                return;
            }
            
			$("#drive_"+data).attr('checked', 'checked').next().addClass("checked");
            $("#errorNotice").hide();
            $("#add-recording").show();
		});
	} else {
        $("#errorNotice").hide();
        $("#add-recording").show();
		$.get('/helpers/recordingHelper.php?action=setdrive&drive=' + driveLetter);
    }
}

function reloadData() {
	$.get('/helpers/recordingHelper.php?action=view', function(data) {
		$('#users tbody').html(data);
	});
}

function updateDiskCost() {
	var framerate = $("#framerate").val();
	var duration = $("#duration").spinner("value");

	if (parseInt(duration) < 1) {
		$("#duration").spinner("value", 1);
		return;
	}

	$("#storageReq").text(bytesToSize(framerate * duration * 60 * 20000, 2));
}

function bytesToSize(bytes, precision) {
	var kilobyte = 1024;
	var megabyte = kilobyte * 1024;
	var gigabyte = megabyte * 1024;
	var terabyte = gigabyte * 1024;

	if ((bytes >= 0) && (bytes < kilobyte)) {
		return bytes + ' bytes';

	} else if ((bytes >= kilobyte) && (bytes < megabyte)) {
		return (bytes / kilobyte).toFixed(precision) + ' kilobytes';

	} else if ((bytes >= megabyte) && (bytes < gigabyte)) {
		return (bytes / megabyte).toFixed(precision) + ' megabytes';

	} else if ((bytes >= gigabyte) && (bytes < terabyte)) {
		return (bytes / gigabyte).toFixed(precision) + ' gigabytes';

	} else if (bytes >= terabyte) {
		return (bytes / terabyte).toFixed(precision) + ' terabytes';

	} else {
		return bytes + ' B';
	}
}
