var uploadedHash = "";
var propagationWatcher = null;
var propagationComplete = false;
var executionWatcher = null;
var executionComplete = false;
var restoreFile = "";
var extensionTimer = null;
var numIntersectionIPs = 0;

var progressTimerID = null;

function stopTasks() {
    clearInterval(executionWatcher);
    executionWatcher = null;
    clearInterval(propagationWatcher);
    propagationWatcher = null;
    clearInterval(progressTimerID);
    progressTimerID = null;
    clearInterval(extensionTimer);
    extensionTimer = null;
}

function initScripts(numIPs, hasVideo)
{	
    numIntersectionIPs = numIPs;
    
    $('.chosen-dialog').chosen({
		disable_search: true,
		single_backstroke_delete: false,
		inherit_select_classes: true
	});
 
    
    $("#restartKiosk").button().click(function() {
		$.get('/helpers/maintenanceHelper.php?action=restartkiosk', function(data) 
        {
			if (data == "Success")
                popupNotification("Restarted kiosk process.", 3500, "notice");
            else
                popupNotification(data, 3500);
		});
	});
	
	$("#autoRestoreButton").button().click(function() {
		$("#dialog-restore-auto").dialog("open");
            
        if ($("#autoRestore").length == 0)
            $("#dialog-restore-auto").siblings('.ui-dialog-buttonpane').find('button').eq(0).hide();
        else
            $("#dialog-restore-auto").siblings('.ui-dialog-buttonpane').find('button').eq(0).show();
	});
		
	$("#uploadRestoreButton").button().click(function() {
		$("#dialog-restore-upload").dialog("open");
        $("#dialog-restore-upload>div.error-msg").text("");
        $("#dialogStart").show(0);
        $("#dialogProgress").hide(0);
	});		
	
	$("#archiveDownload").button().click(function() {
		var logs = $("#includeStats").is(":checked");
		
		window.open("/helpers/maintenanceHelper.php?action=archive&logs=" + logs + "&download=true");
	});
        
    $("#deployStart").button().click(function() {
        $("#dialog-deploy-pick").dialog("open");
    });
    
    $("#clearProc").button().click(function() {
		 $("#dialog-clearProc-confirm").dialog("open");
         $("#dialog-clearProc-confirm>div.error-msg").text("");
	});    
    $("#dialog-clearProc-confirm").dialog({
		autoOpen: false,
        resizable: false,
        modal: true,
		closeText: '×',
		width: 400,
		buttons: {
			"Yes": function() {
                $.get('/helpers/maintenanceHelper.php?action=clearProc', function(data) {
					if (data.substr(0,5) == "Error") {
                        $("#dialog-clearProc-confirm>div.error-msg").text(data)
                    }
                    else
                    {
                        popupNotification(data, 7000, "notice");
                        $("#dialog-clearProc-confirm").dialog("close");
                    }
				});
			},
			Cancel: function() {
                            stopTasks();
				$(this).dialog("close");
			}
		}
	});


    $("#restartProc").button().click(function() {
		 $("#dialog-restart-confirm").dialog("open");
         $("#dialog-restart-confirm>div.error-msg").text("");
	});    
    
    $("#dialog-restart-confirm").dialog({
		autoOpen: false,
        resizable: false,
        modal: true,
		closeText: '×',
		width: 400,
		buttons: {
			"Yes": function() {
                $.get('/helpers/maintenanceHelper.php?action=restartpc', function(data) {
					if (data.substr(0,5) == "Error") {
                        $("#dialog-restart-confirm>div.error-msg").text(data)
                    }
                    else
                    {
                        popupNotification(data, 7000, "notice");
                        $("#dialog-restart-confirm").dialog("close");
                    }
				});
			},
			Cancel: function() {
                            stopTasks();
				$(this).dialog("close");
			}
		}
	});
    
    $("#pingarp").button().click(function() {
		 $("#dialog-network").dialog("open");
	});
    
    $("#dialog-network").dialog({
		autoOpen: false,
        resizable: false,
		closeText: '×',
		width: 680,
		buttons: {
			Close: function() {
				$(this).dialog("close");
			}
		}
	});
    
    $("#network-display, #networkForm").click(function()
    {
       $("#commandPrompt").focus();
    });
    
    $("#networkForm").ajaxForm({
        beforeSend: function()
        {
          $("#commandPrompt").val("\\");
          $("#commandPrompt").blur();
          progressTimerID = setInterval(progressTimer, 200);
        },
		complete: function(xhr) {
            $("#network-display").append(xhr.responseText);
            $("#network-display").animate({ scrollTop: $('#network-display')[0].scrollHeight}, 250);
            clearInterval(progressTimerID);
            $("#commandPrompt").val("");
            $("#commandPrompt").focus();
        }
	}); 

    $("#enableRdp").button().click(function() {
		$.get('/helpers/maintenanceHelper.php?action=enableRdp', function(data) 
        {
			if (data == "Success")
                popupNotification("Remote Desktop Access Enabled.", 3500, "notice");
            else
                popupNotification(data, 3500);
		});
	});	
	
    $("#disableRdp").button().click(function() {
		$.get('/helpers/maintenanceHelper.php?action=disableRdp', function(data) 
        {
			if (data == "Success")
                popupNotification("Remote Desktop Access Disabled.", 3500, "notice");
            else
                popupNotification(data, 3500);
		});
	});
        
    $("#dialog-deploy-upload").dialog({
		autoOpen: false,
        resizable: false,
        modal: true,
		closeText: '×',
		width: 400,
		buttons: {
			"Upload": function() {
                clearTimer();
				$("#appDeployment").submit();
                $("#dialog-deploy-upload").siblings('.ui-dialog-buttonpane').find('button').eq(0).hide();
			},
			Cancel: function() {
                            stopTasks();
				$(this).dialog("close");
			}
		}
	});
    
    $("#dialog-deploy-deploy").dialog({
		autoOpen: false,
        resizable: false,
        modal: true,
        width: 500,
		closeText: '×',
		buttons: {
			"This Intersection": function() {
                $.get('/helpers/deploymentHelper.php?action=execute&hash=' + uploadedHash, function(data) {
					if (data == "Success") {
						popupNotification("Update in progress, it will take a few minutes to complete.", 3500, "notice");
                        $("#dialog-deploy-deploy").dialog("close");
                    } else
                        $("#dialog-deploy-deploy>div.error-msg").html("Error executing file.<br />" + data)
				});
			},
            "Corridor": function() {
                $("#dialog-deploy-deploy").dialog("close");
                $("#dialog-deploy-propagate").dialog("open");
                deployUpload();
			},
			Cancel: function() {
                            stopTasks();
				$(this).dialog("close");
			}
		}
	});
        
    $("#dialog-restore-upload").dialog({
		autoOpen: false,
        resizable: false,
        modal: true,
		closeText: '×',
		width: 400,
		buttons: {
			"Upload": function() {
				$("#restoreUpload").submit();
			},
			Cancel: function() {
                            stopTasks();
				$(this).dialog("close");
			}
		}
	});
    
    $("#dialog-restore-finish").dialog({
		autoOpen: false,
        resizable: false,
        modal: true,
		closeText: '×',
		width: 400,
		buttons: {
			"Restore": function() {
                $("#dialog-restore-finish").siblings('.ui-dialog-buttonpane').find('button').eq(0).hide();   
                $("#dialog-restore-finish>div.content-msg").hide();
                $("#dialog-restore-finish>div.wait-msg").show();
                $("#restoreProgressBar").progressbar({value: false});

				$.get('/helpers/maintenanceHelper.php?action=restore&file=' + restoreFile, function(data) 
                {
					if (data == "Success")
                    {
                        $("#dialog-restore-finish").dialog("close");
						popupNotification("Restoration completed!", 3500, "notice");
                    }
					else
                    {
                        $("#dialog-restore-finish>div.error-msg").html("Error in restoration.<br />" + data);
                        $("#dialog-restore-finish>div.content-msg").hide();
                        $("#dialog-restore-finish>div.wait-msg").hide();
                    }
				});
			},
			Cancel: function() {
                            stopTasks();
				$(this).dialog("close");
			}
		}
	});
		
	$("#restoreUpload").ajaxForm({
		beforeSend: function() {
            $("#dialogStart").hide(0);
            $("#dialogProgress").show(0);
			$("#dialogProgress").html('Uploading, please wait... <div id="uploadRestoreProgress"><div id="progressLabel" style="width:100%;text-align:center;">0%</div></div>');
			$("#uploadRestoreProgress").progressbar({value: 0});
		},
		uploadProgress: function(event, position, total, percentComplete) {
			$("#uploadRestoreProgress").progressbar({value: percentComplete});
            $("#progressLabel").text(percentComplete + "%");
		},
		success: function() {
			$("#uploadRestoreProgress").progressbar({value: 100});
		},
		complete: function(xhr) {
            $("#uploadRestoreProgress").remove();
			var tempVar = xhr.responseText;
			if (tempVar.substr(0,9) == "Success: ") {
				restoreFile = tempVar.substr(9);
				$("#dialog-restore-upload").dialog("close");
                $("#dialog-restore-finish").dialog("open");
                $("#dialog-restore-finish>div.content-msg").show();
                $("#dialog-restore-finish>div.wait-msg").hide();
                $("#dialog-restore-finish").siblings('.ui-dialog-buttonpane').find('button').eq(0).show();   
                $("#dialog-restore-finish>div.error-msg").text("");
			} else {
                $("#dialog-restore-upload>div.error-msg").text(tempVar);
            }
		}
	}); 
    
    $("#appDeployment").ajaxForm({
		beforeSend: function() {
            $("#deployInfo").hide(0);
            $("#deployProgress").show(0);
			$("#deployProgress").html('Uploading, please wait... <div id="uploadRestoreProgress"><div id="progressLabel" style="width:100%;text-align:center;">0%</div></div>');
			$("#uploadRestoreProgress").progressbar({value: false});
            
            extensionTimer = setInterval(extendSession, 60000);
		},
		uploadProgress: function(event, position, total, percentComplete) {
			$("#uploadRestoreProgress").progressbar({value: percentComplete});
            $("#progressLabel").text(percentComplete + "%");
            
            if(percentComplete >= 100)
            {
                if(hasVideo)
                    $("#deployProgress").html('Checking file and uploading to video processor, please wait as this may take a while... <div id="uploadRestoreProgress"></div>');
                else
                    $("#deployProgress").html('Checking file, please wait... <div id="uploadRestoreProgress"></div>');
                
                $("#uploadRestoreProgress").progressbar({value: false});
            }
		},
		success: function() {
			$("#uploadRestoreProgress").progressbar({value: 100});
            clearInterval(extensionTimer);
            extensionTimer = setInterval(sessionTimer, 1000);
		},
		complete: function(xhr) {
            $("#uploadRestoreProgress").remove();
            
            clearInterval(extensionTimer);
            extensionTimer = setInterval(sessionTimer, 1000);
            
            if (xhr.responseText.indexOf("Error") != -1) {
                $("#deployProgress").hide();
                $("#dialog-deploy-upload>div.error-msg").text(xhr.responseText);
            } else {
                uploadedHash = xhr.responseText;
                
    			$("#dialog-deploy-upload").dialog("close");
                $("#dialog-deploy-deploy").dialog("open");
                $("#dialog-deploy-deploy>div.error-msg").text("");
                
                if(numIntersectionIPs == 0)
                {
                    $("#dialog-deploy-deploy").siblings('.ui-dialog-buttonpane').find('button').eq(0).text("Yes");
                    $("#dialog-deploy-deploy").siblings('.ui-dialog-buttonpane').find('button').eq(1).hide();   
                    $("#dialog-deploy-deploy").text("Are you sure you want to deploy this upload?");
                }
            }
		}
	}); 
    
    $("#dialog-restore-auto").dialog({
		autoOpen: false,
        resizable: false,
        modal: true,
		closeText: '×',
		width: 400,
		buttons: {
			"Restore": function() {
                $("#dialog-confirm .error-msg").text("");
                $("#dialog-confirm>p").text("Are you sure you want to restore over the existing configuration?");
                $("#dialog-confirm").siblings('.ui-dialog-buttonpane').find('button').eq(0).show();
                $("#dialog-confirm").siblings('.ui-dialog-buttonpane').find('button').eq(1).text("Cancel");
    
				$("#dialog-confirm").dialog("open");
                restoreFile = $("#autoRestore").val();
			},
			Cancel: function() {
                            stopTasks();
				$(this).dialog("close");
			}
		}
	});
    
    $("#dialog-deploy-selectiveexecute").dialog({
		autoOpen: false,
        resizable: false,
        modal: true,
		closeText: '×',
		width: 400,
		buttons: {
            "Yes": function() {
                $.get('/helpers/deploymentHelper.php?action=executeall&hash='+uploadedHash, function(data) {
                });

				$("#dialog-deploy-execute").dialog("open");
                $("#dialog-deploy-selectiveexecute").dialog("close");

                stopTasks();
                executionComplete = false;
                executionWatcher = setInterval(executionStatus, 1000);                
			},
			Close: function() {
				$(this).dialog("close");
			}
		}
	});
    
    $("#dialog-deploy-pick").dialog({
		autoOpen: false,
        resizable: false,
        modal: true,
		closeText: '×',
		width: 600,
		buttons: {
            "Upload New": function() {
                $(this).dialog("close");
                $("#dialog-deploy-upload").dialog("open");
                $("#dialog-deploy-upload").siblings('.ui-dialog-buttonpane').find('button').eq(0).show();
                $("#deployInfo").show(0);
                $("#dialog-deploy-upload>div.error-msg").text("");
			},
            "Already Uploaded": function() {
                $(this).dialog("close");
                $("#dialog-deploy-existing").dialog("open");
                
                $.get('/helpers/deploymentHelper.php?action=getexistinginstallers', function(data) 
                {
                    if (data.substr(0,5) != "Error") 
                    {
                        var parts = data.split("|");
                        $("#dialog-deploy-existing").html("<center><span id='existingStart' class='btn btn-default green'>Install " + parts[0] + "</span></center>");
                        $("#existingStart").button().click(function()
                        {
                            $("#dialog-deploy-existing").dialog("close");
                            uploadedHash = parts[1]
                            $("#dialog-deploy-deploy").dialog("open");
                            $("#dialog-deploy-deploy>div.error-msg").text("");

                            if(numIntersectionIPs == 0)
                            {
                                $("#dialog-deploy-deploy").siblings('.ui-dialog-buttonpane').find('button').eq(0).text("Yes");
                                $("#dialog-deploy-deploy").siblings('.ui-dialog-buttonpane').find('button').eq(1).hide();   
                                $("#dialog-deploy-deploy").text("Are you sure you want to deploy this upload?");
                            }
                        });
                    }
                    else
                        $("#dialog-deploy-existing").text(data);
                });
			},
			Cancel: function() {
                            stopTasks();
				$(this).dialog("close");
			}
		}
	});
    
    $("#dialog-deploy-existing").dialog({
		autoOpen: false,
        resizable: false,
        modal: true,
		closeText: '×',
		width: 400,
		buttons: {
			Close: function() {
				$(this).dialog("close");
			}
		}
	});
    
    $("#dialog-deploy-execute").dialog({
		autoOpen: false,
        resizable: false,
        modal: true,
		closeText: '×',
		width: 400,
		buttons: {
			Close: function() {
				$(this).dialog("close");
			}
		}
	});
    
    $("#dialog-deploy-propagate").dialog({
		autoOpen: false,
        resizable: false,
        modal: true,
		closeText: '×', 
		width: 400,
		buttons: {
			Cancel: function() {
                            stopTasks();
				$(this).dialog("close");
			}
		}
	});
		
	$("#dialog-confirm").dialog({
		autoOpen: false,
        resizable: false,
        modal: true,
		closeText: '×',
		width: 400,
		buttons: {
			"Yes": function() {
                $("#dialog-restore-auto").dialog("close");
                $("#dialog-restore-thumb").dialog("close");
				$("#dialog-confirm>p").text("Please wait a moment while Restoring...");                
                $("#dialog-confirm").siblings('.ui-dialog-buttonpane').find('button').eq(0).hide();

				$.get('/helpers/maintenanceHelper.php?action=restore&file=' + restoreFile, function(data) {
					if (data == "Success")
					{
						popupNotification("Restoration completed!", 3500, "notice");
						$("#dialog-confirm").dialog("close");
					}
 					else
                    {
						$("#dialog-confirm .error-msg").text(data);
                        $("#dialog-confirm").siblings('.ui-dialog-buttonpane').find('button').eq(1).text("Close");
                        $("#dialog-confirm>p").text("");
                    }
				});
			},
			Cancel: function() {
                            stopTasks();
				$(this).dialog("close");
			}
		}
	});
}

function progressTimer()
{
    var current = $("#commandPrompt").val();
    
    if(current == "\\")
        $("#commandPrompt").val("|");
    else if(current == "|")
        $("#commandPrompt").val("/");
    else if(current == "/")
        $("#commandPrompt").val("-");
    else if(current == "-")
        $("#commandPrompt").val("\\");
}

function extendSession() {
    $.get("/auth/extendSession.php");
}

function restoreUpload() {	
	$("#dialog-confirm").dialog("open");
}

function deployUpload() {
	$("#dialog-deploy-propagate").text("Propagating, please wait...");
	
        propagationComplete = false;
	$.ajax({
		type: "POST",
		url: "/helpers/deploymentHelper.php?action=propagatefile",
		data: {hash: uploadedHash},
		timeout: 0,
                error: function(jqXHR, textStatus, errorThrown) {
                    stopTasks();
                    propagationComplete = true;
                    var status = textStatus;

                    if (status === null) {
                        status = "Unknown error.";
                    } else if (status === "error") {
                        status = "Error: " + errorThrown;
                    }
                    $("#dialog-deploy-propagate").html(status);
                }
	});
	
        stopTasks();
	propagationWatcher = setInterval(propagationStatus, 1000);
}

function propagationStatus() {
        if (propagationComplete) {
            return;
        }
	$.get('/helpers/deploymentHelper.php?action=status&hash='+uploadedHash, function(data) {
		var status = "";
		var xmlDoc = data;		
		var xml = $(xmlDoc);
		
        if ($(xml).siblings().attr("status") == "checking")
            status += "Corridor: Checking for file...<br />";
        else if ($(xml).siblings().attr("status") == "checked")
            status += "Corridor: Working...<br />";
        else
            status += "Corridor: " + $(xml).siblings().attr("status") + "<br />";
		
		status += "<ul style='list-style-type:circle;list-style-position:inside;'>";
		
		$(xml).children().each(function() {
			if ($(this).attr("status") == "error")
				status += "<li>" + $(this).attr("ip") + ": <strong>Error: </strong>" + $(this).attr("message") + "</li>";
			else if ($(this).attr("status") == "working")
				status += "<li>" + $(this).attr("ip") + ": Trying to send...</li>";
            else if ($(this).attr("status") == "skip")
				status += "<li>" + $(this).attr("ip") + ": Processor has file already...</li>";
            else if ($(this).attr("status") == "checking")
				status += "<li>" + $(this).attr("ip") + ": Checking for file...</li>";
            else if ($(this).attr("status") == "sending")
				status += "<li>" + $(this).attr("ip") + ": Sending file...</li>";
			else
				status += "<li>" + $(this).attr("ip") + ": " + $(this).attr("status") + "</li>";
		});
		
		status += "</ul>";
			
		$("#dialog-deploy-propagate").html(status);
        
        if ($(xml).siblings().attr("status") == "sent") {
            stopTasks();
            propagationComplete = true;
            
            $.get('/helpers/deploymentHelper.php?action=executeall&hash='+uploadedHash, function(data) {
                popupNotification("File propagated and executed on all processors", 60000, "notice");
                $("#dialog-deploy-propagate").dialog("close");
            });
        } else if ($(xml).siblings().attr("status") == "error") {
            stopTasks();
            propagationComplete = true;
            
            $("#dialog-deploy-propagate").dialog("close");
            $("#dialog-deploy-selectiveexecute").dialog("open");
            
            status = "<ul style='list-style-type:circle;list-style-position:inside;'>";
            $(xml).children().each(function() {
                if($(this).attr("status") == "error")
                    status += "<li>" + $(this).attr("ip") + "</li>";
            });
            status += "</ul>";
            
            $("#propagate-fail-status").html(status);
        }
	});
}

function executionStatus() {    
        if (executionComplete) {
            return;
        }
	$.get('/helpers/deploymentHelper.php?action=status&hash='+uploadedHash, function(data) {
		var status = "";
		var xmlDoc = data;		
		var xml = $(xmlDoc);
		
        if ($(xml).siblings().attr("status") == "executing") {
            status += "Corridor: Executing files...<br />";
            
            status += "<ul style='list-style-type:circle;list-style-position:inside;'>";
		
            $(xml).children().each(function() {
                if ($(this).attr("status") == "error")
                    status += "<li>" + $(this).attr("ip") + ": <strong>Error: </strong>" + $(this).attr("message") + "</li>";
                else if ($(this).attr("status") == "executing")
                    status += "<li>" + $(this).attr("ip") + ": Starting file...</li>";
                else if ($(this).attr("status") == "done")
                    status += "<li>" + $(this).attr("ip") + ": Finished!</li>";
                else
                    status += "<li>" + $(this).attr("ip") + ": " + $(this).attr("status") + "</li>";
            });

            status += "</ul>";
        } else if ($(xml).siblings().attr("status") == "done") {
            executionComplete = true;
            status += "Corridor: Finished!<br />";
            stopTasks();
        } else if ($(xml).siblings().attr("status") == "error") {
            executionComplete = true;
            stopTasks();
            
            status += "Execution failed on the following machines:<br />";
            
            status += "<ul style='list-style-type:circle;list-style-position:inside;'>";
		
            $(xml).children().each(function() {
                if ($(this).attr("status") == "error")
                    status += "<li>" + $(this).attr("ip") + ": <strong>Error: </strong>" + $(this).attr("message") + "</li>";
            });

            status += "</ul>";
        } else {
            status += "Corridor: " + $(xml).siblings().attr("status") + "<br />";
            
            $(xml).children().each(function() {
                if ($(this).attr("status") == "error")
                    status += "<li>" + $(this).attr("ip") + ": <strong>Error: </strong>" + $(this).attr("message") + "</li>";
                else if ($(this).attr("status") == "executing")
                    status += "<li>" + $(this).attr("ip") + ": Starting file...</li>";
                else
                    status += "<li>" + $(this).attr("ip") + ": " + $(this).attr("status") + "</li>";
            });
        }
			
		$("#dialog-deploy-execute").html(status);
	});
}
