var taskTimerID = 0;
var sortBy = "name";
var sortDir = true;
var selectedPID = -1;

var data = {};
var selectDisplay = " - - - - Select - - - -";
var systemRefIP = "";
var intersectionIPIDs = [];
var intersectionIPIDs_minusSelf = [];
var intersectionIPs = [];

$(document).ready(function()
{
    $('#startDate').datetimepicker({
        hideIfNoPrevNext: true,
        timeFormat: 'hh:mm TT',
        maxDate: '0'
    });

    $('#endDate').datetimepicker({
        hideIfNoPrevNext: true,
        timeFormat: 'hh:mm TT',
        maxDate: '0'
    });

    $("#bootLog").button().click(function() {
        $("#boot-log-dialog").dialog("open");
    });
    
	 $("#intrafficLog").button().click(function() {
        $("#intraffic-sync-dialog").dialog("open");
    });
	
    $("#diskHealth").button().click(function() {
        
		$("#disk-dialog").html("Please wait while the disk check is being run.<br /><br />This can take several minutes...");
		$("#disk-dialog").dialog("open");
        
        $.get("/helpers/troubleshootingHelper.php?action=diskstatus", function(data)
        {
            $("#disk-dialog").html(data);
        });
    });
    
    $("#rdp").button().click(function() {
        window.open("/helpers/rdpHelper.php");
    });
    
    $("#disk-dialog").dialog({
        autoOpen: false,
        resizable: true,
        modal: false,
        closeText: '×',
        width: 800,
        buttons: {
            Close: function() {
                $(this).dialog("close");
            }
        }
    });
    
    $("#network-dialog").dialog({
        autoOpen: false,
        resizable: true,
        modal: false,
        closeText: '×',
        width: 800,
        buttons: {
            Close: function() {
                $(this).dialog("close");
            },
            "Start Test": function() {
                $("#network-dialog").siblings('.ui-dialog-buttonpane').find('button').eq(1).hide();
                $("#network-dialog").html("<div style='max-height:200px;overflow:auto;border:solid 1px white;padding:5px;margin:5px'></div>");
                
                // test insync
                $.each(testData.intersections, function (key, value)
                {
                    var dID = key.replace(/\./g, "");
                    
                    $("#network-dialog>div").append("Testing InSync on " + key + "... <span id='intersection-" + dID + "'>Please wait...</span><br />");
                    
                    $.ajax(
                    {
                        url: "/helpers/troubleshootingHelper.php?action=testinsync&ip=" + key,
                        success: function(data)
                        {                            
                            if(data != "VV,OK,END")
                                $("#intersection-" + dID).html("<font color='red'>FAILED: " + data + "</font>");
                            else
                                $("#intersection-" + dID).html("<font color='green'>PASSED</font>");
                        },
                        error: function(jqXHR, status, error)
                        {
                            $("#intersection-" + dID).html("<font color='red'>FAILED: " + status + "</font>");
                        }        
                    });
                });
                
                $("#network-dialog>div").append("<br />");
                
                $.each(testData.cameras, function (value, key)
                {
                    var randNum = Math.ceil(Math.random()*999999999);
                    var dID = key.replace(/\./g, "");
                    
                    $("#network-dialog>div").append("Testing camera '" + value + "' on " + key + "... <span id='camera-" + dID + randNum + "'>Please wait...</span><br />");
                    
                    $.ajax(
                    {
                        url: "/helpers/troubleshootingHelper.php?action=testcamera&ip=" + key,
                        success: function(data)
                        {
                            if(data.substr(0,6) == "PASSED")
                                $("#camera-" + dID + randNum).html("<font color='green'>" + data + "</font>");
                            else
                                $("#camera-" + dID + randNum).html("<font color='red'>" + data + "</font>");
                        },
                        error: function(jqXHR, status, error)
                        {
                            $("#camera-" + dID + randNum).html("<font color='red'>FAILED: " + status + "</font>");
                        }
                    });
                });
                
                $("#network-dialog>div").append("<br />");
                
                $.each(testData.contextcameras, function (value, key)
                {
                    var randNum = Math.ceil(Math.random()*999999999);
                    var dID = key.replace(/\./g, "");
                    
                    $("#network-dialog>div").append("Testing context-camera '" + value + "' on " + key + "... <span id='camera-" + dID + randNum + "'>Please wait...</span><br />");
                    
                    $.ajax(
                    {
                        url: "/helpers/troubleshootingHelper.php?action=testcamera&ip=" + key,
                        success: function(data)
                        {
                            if(data.substr(0,6) == "PASSED")
                                $("#camera-" + dID + randNum).html("<font color='green'>" + data + "</font>");
                            else
                                $("#camera-" + dID + randNum).html("<font color='red'>" + data + "</font>");
                        },
                        error: function(jqXHR, status, error)
                        {
                            $("#camera-" + dID + randNum).html("<font color='red'>FAILED: " + status + "</font>");
                        }
                    });
                });
                
                $("#network-dialog>div").append("<br />");
                
                $.each(testData.cyclopsdetector, function (value, key)
                {
                    var randNum = Math.ceil(Math.random()*999999999);
                    var dID = key.replace(/\./g, "");
                    
                    $("#network-dialog>div").append("Testing Cyclops Device on " + key + "... <span id='camera-" + dID + randNum + "'>Please wait...</span><br />");
                    
                    $.ajax(
                    {
                        url: "/helpers/troubleshootingHelper.php?action=testcyclops&ip=" + key,
                        success: function(data)
                        {
                            if(data.substr(0,6) == "PASSED")
                                $("#camera-" + dID + randNum).html("<font color='green'>" + data + "</font>");
                            else
                                $("#camera-" + dID + randNum).html("<font color='red'>" + data + "</font>");
                        },
                        error: function(jqXHR, status, error)
                        {
                            $("#camera-" + dID + randNum).html("<font color='red'>FAILED: " + status + "</font>");
                        }
                    });
                });				
				
                $("#network-dialog>div").append("<br />");								
				
                if(testData.ntp)
                {
                    $("#network-dialog>div").append("Testing NTP server on " + testData.ntp + "... <span id='ntpResult'>Please wait...</span><br />");
                    
                    $.ajax(
                    {
                        url: "/helpers/troubleshootingHelper.php?action=testntp&ip=" + testData.ntp,
                        success: function(data)
                        {
                            if(data != "PASSED")
                                $("#ntpResult").html("<font color='red'>FAILED</font>");
                            else
                                $("#ntpResult").html("<font color='green'>PASSED</font>");
                        },
                        error: function(jqXHR, status, error)
                        {
                            $("#ntpResult").html("<font color='red'>FAILED: " + status + "</font>");
                        }  
                    });
                    
                    $("#network-dialog>div").append("<br />");
                }
                
                if(testData.smtp)
                {
                    $("#network-dialog>div").append("Testing SMTP server on " + testData.smtp + "... <span id='smtpResult'>Please wait...</span><br />");
                    
                    $.ajax(
                    {
                        url: "/helpers/troubleshootingHelper.php?action=testsmtp&ip=" + testData.smtp,
                        success: function(data)
                        {
                            if(data != "PASSED")
                                $("#smtpResult").html("<font color='red'>FAILED</font>");
                            else
                                $("#smtpResult").html("<font color='green'>PASSED</font>");
                        },
                        error: function(jqXHR, status, error)
                        {
                            $("#smtpResult").html("<font color='red'>FAILED: " + status + "</font>");
                        }  
                    });
                }
            }
        }
    });
    
    $("#networkTester").button().click(function() {
        $("#network-dialog").dialog("open");
        $("#network-dialog").siblings('.ui-dialog-buttonpane').find('button').eq(1).hide();
        
        $.getJSON("/helpers/troubleshootingHelper.php?action=gettestips", function(data)
        {            
            if(data.error)
            {
                $("#network-dialog").text(data.error);
                return;
            }
            
            testData = data;
            
            var setHTML = "The following will be tested:<br /><div style='max-height:200px;overflow:auto;border:solid 1px white;padding:5px;margin:5px'>";
            
            setHTML += "The following intersections were found in your Management Group:";
            setHTML += "<ul>"
            
            $.each(data.intersections, function (key, value)
            {
                setHTML += "<li>" + key + " - " + value + "</li>";
            });
            
            setHTML += "</ul>";
            
            setHTML += "The following cameras were found in your local configuration:";
            setHTML += "<ul>"
            
            $.each(data.cameras, function (key, value)
            {
                setHTML += "<li>" + value + " - " + key + "</li>";
            });
            
            setHTML += "</ul>";
			
			setHTML += "The following context-cameras were found in your local configuration:";
            setHTML += "<ul>"
			
			$.each(data.contextcameras, function (key, value)
            {
                setHTML += "<li>" + value + " - " + key + "</li>";
            });
			
			setHTML += "</ul>";			
            
			setHTML += "The following Cyclop Device was found in your local configuration:";
            setHTML += "<ul>"
			$.each(data.cyclopsdetector, function (key, value)
            {
                setHTML += "<li>" + value + "</li>";
            });
			setHTML += "</ul>";						
			
            if(testData.ntp)
            {
                setHTML += "The following NTP server was found in your local configuration:";
                setHTML += "<ul>"
                setHTML += "<li>" + testData.ntp + "</li>";
                setHTML += "</ul>";
            }
            
            if(testData.smtp)
            {
                setHTML += "The following SMTP server was found in your local configuration:";
                setHTML += "<ul>"
                setHTML += "<li>" + testData.smtp + "</li>";
                setHTML += "</ul>";
            }
            
            setHTML += "</div>";
            
            setHTML += "Click Start Test to begin the test!";
            
            $("#network-dialog").html(setHTML);
            $("#network-dialog").siblings('.ui-dialog-buttonpane').find('button').eq(1).show();
        });
    });

    $("#boot-log-dialog").dialog({
        autoOpen: false,
        resizable: false,
        modal: false,
        closeText: '×',
        width: 420,
        buttons: {
            Close: function() {
                $(this).dialog("close");
            }
        }
    });

    $("#intraffic-sync-dialog").dialog({
        autoOpen: false,
        resizable: false,
        modal: false,
        closeText: '×',
        width: 720,
        buttons: {
            Close: function() {
                $(this).dialog("close");
            }
        }
    });

    $('#download').button().click(function() {
        window.open("helpers/troubleshootingHelper.php?action=download&startDate=" + $('#startDate').val() + "&endDate=" + $('#endDate').val());
    });

    $("#taskManager").button().click(function() {
        $("#task-dialog").dialog("open");
        selectedPID = -1;
        taskTimerID = setTimeout(taskTimer, 1000);
        $("#task-dialog").siblings('.ui-dialog-buttonpane').find('button').eq(1).hide();
    });
    
    $("#videoManager").button().click(function() {
        $("#video-dialog").dialog("open");
        
        $.get("/helpers/troubleshootingHelper.php?action=getvideos", function(data)
        {
            $("#video-content-wrap").html(data);
        });
    });

    $("#clearStorage").button().click(function() {
		$.get('/helpers/troubleshootingHelper.php?action=clearstorage', function(data) 
        {
			if (data == "Success")
                popupNotification("Cleared processor history.", 3500, "notice");
            else
                popupNotification(data, 3500);
		});
	});
	
    $("#timeSyncStatus").button().click(function() {
        $("#time-dialog").dialog("open");
        $("#time-dialog").html("<div style='max-height:100%;overflow:auto;border:solid 1px white;padding:5px;margin:5px; width=100%;height=100%'></div>");	

        // set the header row
        var processorNTPDataTable = "<table class='table table-bordered' style='color:black'>";
        processorNTPDataTable += "<thead>";
        processorNTPDataTable += "<tr>";
        processorNTPDataTable += "<th>Processor IP</th>";
        processorNTPDataTable += "<th>Time</th>";
        processorNTPDataTable += "<th>Offset [s]</th>";
        processorNTPDataTable += "<th>NTP Server</th>";
        processorNTPDataTable += "<th>Server Status</th>";
        processorNTPDataTable += "<th>Sync</th>";
        processorNTPDataTable += "<th>Sync Response</th>";
        processorNTPDataTable += "</tr>";
        processorNTPDataTable += "</thead>";
        processorNTPDataTable += "<tbody>";
        
        // load drop down ip list
        var altIpList = $('<select id="alt_ip_select" class="chosen-select single-select" />'); 
        $('<option />', {value: selectDisplay, text:selectDisplay }).appendTo(altIpList);
        
        intersectionIPIDs = [];
        intersectionIPIDs_minusSelf = [];
        intersectionIPs = [];

        $.get("/helpers/timetoolsHelper.php?action=getmyIP", function(myIP)
        {
            // set the rows of processor IPs and div data placeholders
            $.getJSON("/helpers/timetoolsHelper.php?action=getIntersectionIPs", function(intersectionIPData)
            {          
                // create first row as myself
                processorNTPDataTable += GetProcessorNTPDataTableRow(myIP, myIP);

                // save reference to my own ip
                systemRefIP = myIP;
                var myID = myIP.replace(/\./g, "");

                // add my own ip to the intersectionIPIDs array
                intersectionIPIDs.push(myID);
                intersectionIPs.push(myIP);
               
                // add the rows for the rest of the intersections
                $.each(intersectionIPData, function (key, value)
                {    
                    // create the next rows for each other intersection
                    processorNTPDataTable += GetProcessorNTPDataTableRow(key, myIP);

                    var dID = key.replace(/\./g, "");

                    // add the intersection ID to the arrays
                    intersectionIPIDs_minusSelf.push(dID);
                    intersectionIPIDs.push(dID);
                    intersectionIPs.push(key);
                });
                
                // set the alt ip drop down html
                $("#time-dialog>div").append("<div class='row'>");
                $("#time-dialog>div").append("<div class='inline-block'><label>Peer IP to Sync on Failure:</label></div>")
                $("#time-dialog>div").append("<div class='inline-block'>");
                $("#time-dialog>div").append(altIpList);
                $("#time-dialog>div").append("</div>");
                $("#time-dialog>div").append("</div>");
                $("#time-dialog>div").append("</br></br>");

                // set the processor NTP info html next
                $("#time-dialog>div").append(processorNTPDataTable);

                // define checkbox change handler
                $("input[type='checkbox']").change(function() {
                    RefreshAltIPDropDown();
                });

                // refresh/update the time and ntp data
                RefreshNTPData();

                // refresh the offsets from processor ref data
                RefreshOffsetsFromProcessorRef(systemRefIP);
                
                // refresh the alt-ip drop down list
                RefreshAltIPDropDown();
            });
        });
    });
    
    function RefreshAltIPDropDown()
    {
        // holds the IP from the drop down box
        // get the selected ip from dropdown		
        var dropDownSelected = ($("#alt_ip_select :selected").text());

        // array to hold the ips that are checked
        var checkedIPs = [];
        
        // iterate thru all checkboxes to determine
        // what's checked
        $('input[type=checkbox]').each(function() {
            if (this.checked) 
            {        
                var idofCheckbox = this.id;
                var checkboxIP = getselectedIP(idofCheckbox);
                checkedIPs.push(checkboxIP);
                
                // if the ip selected in the dropdown matches
                // an ip that is checked, we have to 
                // select the "default, selectDisplay" as the
                // selected value in the drop down
                if (dropDownSelected == checkboxIP)
                {
                    dropDownSelected = selectDisplay;
                } 
            }
        });
        
        // clear the altIP list and init...
        $("#alt_ip_select").empty();

        // add the selectDisplay value
        $('<option />', {value: selectDisplay, text:selectDisplay }).appendTo($("#alt_ip_select")); 	

        // add remaining IPs to the option list
        // that are not checked
        for (var i = 0; i < intersectionIPs.length; i++)
        {
            var localIP = intersectionIPs[i];
            var dID = localIP.replace(/\./g, "");
            if ($.inArray(localIP, checkedIPs) == -1)
            {
                $('<option />', {value: dID, text: localIP}).appendTo($("#alt_ip_select"));
            }
        }

        // set the selected option
        $('#alt_ip_select option')
                .filter(function() {return $.trim($(this).text()) == dropDownSelected})
                .attr('selected', 'selected');
    }
    
    function GetProcessorNTPDataTableRow(ip, myIP)
    {
        var dID = ip.replace(/\./g, "");
        
        var processorNTPDataTableRow = "";
        processorNTPDataTableRow += "<tr>";

        processorNTPDataTableRow += "<td id='ip-" + dID + "'>" + ip + "</td>";
        processorNTPDataTableRow += "<td id='time-" + dID + "'/>";

        if (ip == myIP)
        {
            processorNTPDataTableRow += "<td id='offset-" + dID + "'> [System Ref] </td>";
        }
        else
        {
            processorNTPDataTableRow += "<td id='offset-" + dID + "'></td>";
        }

        processorNTPDataTableRow += "<td id='ntp_server-" + dID + "'/>";
        processorNTPDataTableRow += "<td id='ntp_status-" + dID + "'/>";
        processorNTPDataTableRow += "<td style='text-align:center' id='selection-" + dID + "'>";
        processorNTPDataTableRow += "<input type=checkbox id='checkbox-" + dID + "' />";
        processorNTPDataTableRow += "</td>";
        processorNTPDataTableRow += "<td id='sync_response-" + dID + "'></div>";

        processorNTPDataTableRow += "</tr>";
        
        return processorNTPDataTableRow;
    }
    
    function RefreshNTPData()
    {
        for (var i = 0; i < intersectionIPIDs.length; i++)
        {
            $("#time-" + intersectionIPIDs[i]).html("Waiting...");	
            $("#ntp_server-" + intersectionIPIDs[i]).html("Waiting...");	
            $("#ntp_status-" + intersectionIPIDs[i]).html("Waiting...");
        }
        
        // refresh the data
        $.getJSON("/helpers/timetoolsHelper.php?action=getalldata", function(specialCallsNTPData)
        {        
            $.each(specialCallsNTPData, function (key, value)
            {    
                var dID = key.replace(/\./g, "");
		$("#time-" + dID).html(value.time);
                $("#ntp_server-" + dID).html(value.ntp_server);
                $("#ntp_status-" + dID).html(value.ntp_status);
            });
        });
    }
    
    function RefreshOffsetsFromProcessorRef(refIP)
    {
        for(var i = 0; i < intersectionIPIDs_minusSelf.length; i++)
        {
            $("#offset-" + intersectionIPIDs_minusSelf[i]).html("Waiting...");	
        }
        
        //get all intersection ip address 
        $.getJSON("/helpers/timetoolsHelper.php?action=getIntersectionIPs", function(intersectionIPs) {

            $.each(intersectionIPs, function (key, value)
            {
                var dID = key.replace(/\./g, "");
                
                $.get("/helpers/timetoolsHelper.php?action=getOffsetFromOtherSystemExecution&iptoCompare=" + refIP + "&remoteIP="+key, function(offsetData) {
                    $("#offset-" + dID).html(offsetData);	
                });
            });	
        });	
    }

    function SyncTime()
    {
        var ipfromDropdown = ($("#alt_ip_select :selected").text());

        //read from selected checkboxes
        $('input[type=checkbox]').each(function() {
            if (this.checked) 
            {
                var spanid = this.id;
                var splitSpanid = spanid.split('-');
                var checkboxIP = $("#ip-" + splitSpanid[1]).text();
                var dID = checkboxIP.replace(/\./g, "");
                
                $("#sync_response-" + dID).html("Sync in Progress...");

                // Perform Time Sync by passing processor IP, NTP server IP and alternate IP address to Sync to
                $.get("/helpers/timetoolsHelper.php?action=performTimeSync&processortoSyncfromCheckbox=" + checkboxIP + "&altNtpServer=" + ipfromDropdown, function(syncResponse)
                {
                    if (syncResponse.indexOf("Successful") > -1)
                    {
                        $("#sync_response-" + dID).html("<font color='green'>"+syncResponse+"</font>");			
                    }
                    else
                    {
                        $("#sync_response-" + dID).html("<font color='red'>"+syncResponse+"</font>");
                    }
                });	
            }
        });
    }
    
    $("#task-dialog").dialog({
        autoOpen: false,
        resizable: true,
        modal: false,
        closeText: '×',
        width: 620,
        height: 520,
        buttons: {
            Close: function() {
                $(this).dialog("close");
                clearInterval(taskTimerID);
                taskTimerID = 0;
            },
            "End Process": function() {
                endProcess();
            }
        }
    });
    
    $("#video-dialog").dialog({
        autoOpen: false,
        resizable: true,
        modal: false,
        closeText: '×',
        width: 620,
        height: 520,
        buttons: {
            Close: function() {
                $(this).dialog("close");
                clearInterval(taskTimerID);
                taskTimerID = 0;
            }
        }
    });
    
    $("#time-dialog").dialog({
        autoOpen: false,
        resizable: true,
        modal: false,
        closeText: '×',
        width: 1000,
        height: 600,
        buttons: {
            Close: function() {
                $(this).dialog("close");
                clearInterval(taskTimerID);
                taskTimerID = 0;
            },

            // refresh all data	
            "Refresh": function() {
                RefreshNTPData();
                RefreshOffsetsFromProcessorRef(systemRefIP);
                
                // clear all of the sync response data
                for (var i = 0; i < intersectionIPIDs.length; i++)
                {
                    $("#sync_response-" + intersectionIPIDs[i]).html("");
                }
            },

            // Time Sync button click
            "Sync Time": function() {
                SyncTime();	
            }
        }

    });



    $("#sortProcess").button().click(function()
    {
        sortBy = "name";
        sortDir = !sortDir;
    });

    $("#sortDesc").button().click(function()
    {
        sortBy = "desc";
        sortDir = !sortDir;
    });

    $("#sortCPU").button().click(function()
    {
        sortBy = "cpu";
        sortDir = !sortDir;
    });

    $("#sortRAM").button().click(function()
    {
        sortBy = "ram";
        sortDir = !sortDir;
    });

    $("#sortPID").button().click(function()
    {
        sortBy = "pid";
        sortDir = !sortDir;
    });
});


function deleteVideo(path)
{
    $.get("/helpers/troubleshootingHelper.php?action=delete&path=" + path, function(data)
    {
        $.get("/helpers/troubleshootingHelper.php?action=getvideos", function(data)
        {
            $("#video-content-wrap").html(data);
        });
    });
}

function endProcess()
{
    if(selectedPID != -1)
    {
        $.get("/helpers/troubleshootingHelper.php?action=endprocess&pid="+selectedPID, function(data)
        {
            alert(data);
        });
    }
}

function compareName(a, b)
{
    if (a.name.toLowerCase() < b.name.toLowerCase())
        return (sortDir) ? -1 : 1;
    if (a.name.toLowerCase() > b.name.toLowerCase())
        return (sortDir) ? 1 : -1;

    return 0;
}

function compareDesc(a, b)
{
    if (a.desc.toLowerCase() < b.desc.toLowerCase())
        return (sortDir) ? -1 : 1;
    if (a.desc.toLowerCase() > b.desc.toLowerCase())
        return (sortDir) ? 1 : -1;

    return 0;
}

function compareCPU(a, b)
{
    if (a.cpu < b.cpu)
        return (sortDir) ? -1 : 1;
    if (a.cpu > b.cpu)
        return (sortDir) ? 1 : -1;

    return 0;
}

function compareRAM(a, b)
{
    if (a.ram < b.ram)
        return (sortDir) ? -1 : 1;
    if (a.ram > b.ram)
        return (sortDir) ? 1 : -1;

    return 0;
}

function comparePID(a, b)
{
    if (a.pid < b.pid)
        return (sortDir) ? -1 : 1;
    if (a.pid > b.pid)
        return (sortDir) ? 1 : -1;

    return 0;
}

function taskTimer()
{
    $.get("/helpers/troubleshootingHelper.php?action=gettasks", function(data)
    {
        var memoryUsed = 0;
        var dataSet = [];
        $(data).find("ProcessQuery").children("Process").each(function()
        {
            var cpu = +$(this).attr("CPU");
            var ram = +$(this).attr("RAM");
            
            memoryUsed += ram;
            
            ram /= 1024;

            var obj = {
                name: $(this).attr("Name"),
                desc: $(this).attr("Description"),
                cpu: +cpu,
                ram: +ram,
                pid: +$(this).attr("PID")
            };
            
            if(obj.name == "ProcessQuery.exe")
                return;

            dataSet.push(obj);
        });
        
        $("#processCount").text("Processes: " + dataSet.length);
        
        var load = +$(data).find("ProcessQuery>CPU").attr("Load");
        $("#cpuUsage").text("CPU Usage: " + load.toFixed(0) + "%");
        
        memoryUsed /= 1048576;
        $("#memoryUsage").text("RAM Usage: " + memoryUsed.toFixed(0) + "mb");

        var temp = +$(data).find("ProcessQuery>CPU").attr("Temp");
        $("#cpuTemp").html("CPU Temp: " + temp.toFixed(1) + "&deg;C");

        if (sortBy == "name")
            dataSet.sort(compareName);
        if (sortBy == "desc")
            dataSet.sort(compareDesc);
        if (sortBy == "cpu")
            dataSet.sort(compareCPU);
        if (sortBy == "ram")
            dataSet.sort(compareRAM);
        if (sortBy == "pid")
            dataSet.sort(comparePID);

        var addHTML = "";

        for (var i = 0; i < dataSet.length; i++)
        {
            addHTML += "<tr id='process" + dataSet[i].pid + "'>";

            var ramString = "";
            if (dataSet[i].ram > 1024)
                ramString = (+dataSet[i].ram / 1024.0).toFixed(1) + "mb";
            else
                ramString = +dataSet[i].ram.toFixed(0) + "k";

            addHTML += "<td>" + dataSet[i].name + "</td>";
            addHTML += "<td>" + dataSet[i].desc + "</td>";
            addHTML += "<td>" + dataSet[i].cpu.toFixed(1) + "%</td>";
            addHTML += "<td>" + ramString + "</td>";
            addHTML += "<td>" + dataSet[i].pid + "</td>";

            addHTML += "</tr>";
        }

        $("#task-list-body").html(addHTML);

        for (var i = 0; i < dataSet.length; i++)
        {            
            $("#process" + dataSet[i].pid).click(function()
            {
                selectedPID = +$(this).attr("id").substring(7);
                
                $("#task-list-body>tr").each(function()
                {
                    $(this).css({"background-color": "white"});
                });
                
                $(this).css({"background-color": "#D2FCFA"});
            });

            $("#process" + dataSet[i].pid).css({"background-color": "white"});
        }
        
        $("#process" + selectedPID).css({"background-color": "#D2FCFA"});
    }, "xml");
    
    if(selectedPID == -1)
        $("#task-dialog").siblings('.ui-dialog-buttonpane').find('button').eq(1).hide();
    else
        $("#task-dialog").siblings('.ui-dialog-buttonpane').find('button').eq(1).show();

    if (taskTimerID != 0)
        taskTimerID = setTimeout(taskTimer, 1000);
}

function getselectedIP(spanIdofCheckbox) {
	var splitSpanid = spanIdofCheckbox.split('-');
	checkboxIP = $("#ip-" + splitSpanid[1]).text();
	return checkboxIP;	
}

