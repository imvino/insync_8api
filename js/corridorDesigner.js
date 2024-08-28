var documentModified = false;
var currentTarget = "";
var importData = null;
var statusHash = "";
var colSize = 155;
var intersectionIPs = [];
var rotated = false;

function setIPs(ips) {
    intersectionIPs = ips;
}

$(document).ready(function() {

    $('.chosen-dialog').chosen({
        disable_search: true,
        single_backstroke_delete: false,
        inherit_select_classes: true
    });

     $('#corridorContainer').tooltip({
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

    // Utility Menu
    $('#btn-add').click(function() {
        if ($('#btn-add').hasClass('active')) {
            $('#btn-add').removeClass('active');
            $('#submenu-add').hide();
        }
        else {
            $('#btn-add').addClass('active');
            $('#submenu-add').show();
        }
    }); 

    // clicking 'off' the div hides it. 
    $('html').click(function() {
    $('#btn-add').removeClass('active');
        $('#submenu-add').hide();
    });

    $('#btn-add').click(function(e) {
        e.stopPropagation();
    })
    // --- Utility Menu End
    

    $("#intersection-ip").autocomplete({
        source: intersectionIPs,
        position: {
            my: 'center top+1',
            at: 'center bottom'
        }
    });
    $("#intersection-column-ip").autocomplete({
        source: intersectionIPs,
        position: {
            my: 'center top+1',
            at: 'center bottom'
        }
    });

    $(window).on('beforeunload', function() {
        if (documentModified)
            return "This Management Group has been modified and not saved. If you leave this page without saving, you will lose your changes!";
    });

    $("#corridorContainer").sortable({
        stop: function(event, ui) {
            documentModified = true;
        }
    });

    $("#addIntersection").click(function(e) {
        e.preventDefault();
        $("#beginDesign").remove();
        $("#dialog-add-intersection>div.error-msg").text("");
        $("#dialog-add-intersection").dialog("open");
    });

    $("#rotate").click(function(e)
    {
        e.preventDefault();
        
        rotated = !rotated;

        setRotatedStyle();
        
        if(rotated)
            setCookie("rotated","true");
        else
            setCookie("rotated","false");        
    });

    $("#addCustomColumn").click(function(e) {
        e.preventDefault();
        $("#beginDesign").remove();

        addColumn();
        
        $(window).resize();
    });

    $("#save").click(function() {
        var domTree = buildSaveTree("#corridorContainer");
        var title = $("#corridorTitle").val();

        $.post("helpers/corridorDesignerHelper.php", {action: "save", title: title, data: domTree},
        function(data) {
            if (data.substr(0, 7) == "Success") {
                documentModified = false;
                popupNotification("Saved successfully!", 3500, "notice");
            } else
                popupNotification(data, 3500);
        });
    });

    $("#clear").click(function() {
        $("#beginDesign").remove();
        $("#dialog-clear").dialog("open");
    });

    $("#download").click(function() {
        $("#beginDesign").remove();

        var domTree = buildSaveTree("#corridorContainer");
        var title = $("#corridorTitle").val();

        $("<form method='post' action='helpers/corridorDesignerHelper.php?action=download' target='_blank'><input type='hidden' name='title' value='" + title + "' /><input type='hidden' name='data' value='" + JSON.stringify(domTree) + "' /></form>").appendTo('body').submit().remove();
    });

    $("#dialog-clear").dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        closeText: '×',
        width: 500,
        buttons: {
            "Yes": function() {
                $("#corridorContainer").empty();
                $(this).dialog("close");
                documentModified = true;
            },
            "Cancel": function() {
                $(this).dialog("close");
            }
        }
    });

    $("#dialog-configure").dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        closeText: '×',
        width: 400,
        buttons: {
            "Save": function() {
                var newName = $("#camList").val();
                
                var cameraID = getUniqueID("camera");
                $("#" + currentTarget).append("<li class='camera' id='" + cameraID + "'>Unconfigured Camera</li>")

                $("#" + cameraID).attr("camname", newName);
                $("#" + cameraID).html("<div class='thumbnail-text'>" + newName + " <a onclick='$(this).parent().parent().remove();documentModified=true;' class='thumbnail-delete'>×</a></div>");

                var targetIP = $("#" + cameraID).parent().parent().find(".intersection-ip").val();

                $("#" + cameraID).css({"background-image": "url('/helpers/corridorDesignerHelper.php?action=getremoteimage&ip=" + targetIP + "&cam=" + newName + "')", width: colSize+"px", height: (colSize*0.75)+"px"});
                
                setRotatedStyle();

                documentModified = true;

                $(this).dialog("close");
            },
            "Cancel": function() {
                $(this).dialog("close");
            }
        }
    });
    
    function addIntersectionSubmit()
    {
        var ipAddress = $("#intersection-ip").val();

        if (ipAddress == "") {
            addIntersection();
            $(this).dialog("close");
            return;
        }

        show_busy_indicator();

        $.getJSON("helpers/corridorDesignerHelper.php", {action: "getcameralist", ip: ipAddress}, function(data) {
            if (typeof data.error != "undefined")
                $("#dialog-add-intersection>div.error-msg").text("Could not retrieve list from " + ipAddress + "!")
            else
            {
                addIntersection(ipAddress, data, data.name);
                $("#dialog-add-intersection").dialog("close");
            }
        })
        .always(function() {
            hide_busy_indicator();
        });
    }

    $("#dialog-add-intersection").dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        closeText: '×',
        width: 575, 
        buttons: {
            "OK": addIntersectionSubmit
        }
    });
    
    $('#dialog-add-intersection').keypress(function(e) {
        if (e.keyCode == $.ui.keyCode.ENTER) 
            addIntersectionSubmit();
    });
    
    $("#getList").click(function() {
        $("#addColumnCamName").empty();
        
        var ipAddress = $("#intersection-column-ip").val();

        if (ipAddress == "") {
            alert("Please enter a valid processor IP address.");
            return;
        }

        show_busy_indicator();

        $.getJSON("helpers/corridorDesignerHelper.php", {action: "getcameralist", ip: ipAddress}, function(data) {
            if (typeof data.error != "undefined") {
                $("#addColumnCamName").empty();
                $("#addColumnCamName").append("<option value='No Cameras Found'>No Cameras Found</option>");
                $("#addColumnCamName").trigger("chosen:updated");
                alert("Could not retrieve list from " + ipAddress + "!");
            } else {
                $("#addColumnCamName").empty();
                $("#addColumnCamName").append("<option value='No Cameras Found'>No Cameras Found</option>");
                
                if (typeof data != "undefined" && typeof data.list != "undefined" && data.list != "") {
                    $("#addColumnCamName").empty();
                    
                    for (var i = 0; i < data.list.length; i++)
                        $("#addColumnCamName").append("<option value='" + data.list[i] + "'>" + data.list[i] + "</option>");
                    
                    if (data.list != "")
                        $("#addColumnCamName").removeProp("disabled");
                }
                
                $("#addColumnCamName").trigger("chosen:updated");
            }
        })
        .always(function(data) {
            hide_busy_indicator();
        });
    });
    
    $("#dialog-add-column-camera").dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        closeText: '×',
        width: 550,
        buttons: {
            "Save": function() {
                var targetIP = $("#intersection-column-ip").val();
                var newName = $("#addColumnCamName").val();
                
                if (newName == null || newName == "No Cameras Found") {
                    alert("Invalid camera.");
                    return;
                }
                
                var parent = currentTarget;
                currentTarget = getUniqueID("camera");
                $("#" + parent).append("<li class='camera' id='" + currentTarget + "'><strong>Click to configure this camera.</strong></li>")
                
                $("#" + currentTarget).attr("camname", newName);
                $("#" + currentTarget).attr("ipsrc", targetIP);
                $("#" + currentTarget).html("<div class='thumbnail-text'>" + newName + " <a href='#' onclick='$(this).parent().parent().remove();documentModified=true;' class='thumbnail-delete'>×</a></div>");

                $("#" + currentTarget).css({"background-image": "url('/helpers/corridorDesignerHelper.php?action=getremoteimage&ip=" + targetIP + "&cam=" + newName + "')", width: colSize+"px", height: (colSize*0.75)+"px"});

                documentModified = true;
                
                setRotatedStyle()

                $(this).dialog("close");
            },
            "Cancel": function() {
                $(this).dialog("close");
            }
        }
    });

    $("#import").click(function() {
        $("#beginDesign").remove();
        $("#dialog-import>div.error-msg").text("");
        $("#dialog-import").dialog("open");
    });

    $("#dialog-import").dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        closeText: '×',
        width: 500,
        buttons: {
            Cancel: function() {
                $(this).dialog("close");
            }
        }
    });

    $('#uploadForm').ajaxForm({
        beforeSend: function() {
            show_busy_indicator();
        },
        success: function() {
            hide_busy_indicator();
        },
        complete: function(xhr) {
            data = xhr.responseText;

            if (data.substr(0, 5) == "Error") {
                $("#dialog-import>div.error-msg").text(data);
                return;
            }

            data = $.parseJSON(data);

            importData = data;

            var title = "Imported Corridor";
            if (typeof importData.title != "undefined")
                title = importData.title;

            $("#corridorTitle").val(title);

            if (typeof importData.list != "undefined") {
                $.each(importData.list, function(index, value) {
                    var camData = {};
                    camData.list = new Array();

                    $.each(value.cameras, function(index, value) {
                        camData.list.push(value);
                    });

                    addIntersection(value.ip, camData, value.name);
                });
            }
            
            setRotatedStyle();

            documentModified = true;
            hide_busy_indicator();
            $("#dialog-import").dialog("close");
        }
    });

    $("#propagate").click(function() {
        $("#beginDesign").remove();

        $("#dialog-propagate-confirm").dialog("open");
        $("#dialog-propagate-confirm>div.error-msg").text("");
    });

    $("#dialog-propagate-status").dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        closeText: '×',
        width: 400,
        buttons: [{
            text: "Cancel",
            open: function() { $(this).addClass('status-dialog-button') },
            click: function() {
                $(this).dialog("close");
            }
        }]
    });

    $("#dialog-propagate-confirm").dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        closeText: '×',
        width: 400,
        buttons: {
            "Yes": function() {
                show_busy_indicator();

                var domTree = buildSaveTree("#corridorContainer");
                var title = $("#corridorTitle").val();

                $.post("helpers/corridorDesignerHelper.php", {action: "save", title: title, data: domTree},
                function(data) {
                    if (data.substr(0, 7) == "Success") {
                        documentModified = false;
                        statusHash = data.substr(9);

                        $.ajax({
                            url: "helpers/corridorDesignerHelper.php",
                            data: {action: "propagate"},
                            timeout: 10000,
                            dataType: 'xml'
                        }).always(function(data) {
                            hide_busy_indicator();
                            $("#dialog-propagate-status").dialog("open");
                            statusTimer = setInterval(statusChecker, 500);
                        });
                        
                        $("#dialog-propagate-confirm").dialog("close");
                        
                    } else {
                        hide_busy_indicator();
                        $("#dialog-propagate-confirm>div.error-msg").text(data);
                    }
                });
            },
            "Cancel": function() {
                $(this).dialog("close");
            }
        }
    });
    
    $(window).resize(function() { 
        var targetWidth = $("#corridorContainer").width();
        
        var intersections = $("#corridorContainer").find(".corridor-intersection");
        var columns = $("#corridorContainer").find(".corridor-col");
        
        colSize = targetWidth / (intersections.length + columns.length + 3);
        
        if (colSize > 160)
            colSize = 160;
    
        resizeCols(); 
    });    
    
    $(window).resize();
    
    if(getCookie("rotated") == "true")
    {
        rotated = true;
        setRotatedStyle();
    }
});

function setRotatedStyle()
{
    if (rotated)
    {
        var newWidth = $("#corridorContainer").width() - 30;

        $("#corridorContainer").children().each(function()
        {
            $(this).find(".control-header").width(newWidth);
            
            $(this).css("margin-left","0px");
            $(this).find("ul.controls").css("display","inline")
            $(this).find("ul.controls>li").css("margin-top","10px")
            $(this).find("ul:not(.controls)>li").addClass("rotated-sort");
        });
    }
    else
    {
        var newWidth = colSize;

        $("#corridorContainer").children().each(function()
        {
            $(this).find(".control-header").width(newWidth);
            $(this).find(".control-header>input").width(newWidth - 20)
            
            $(this).css("margin-left","12px");
            $(this).find("ul.controls").css("display","block")
            $(this).find("ul.controls>li").css("margin-top","0px")
            $(this).find("ul:not(.controls)>li").removeClass("rotated-sort");
        });
    }
}

function resizeCols()
{

    if (rotated)
    {
        $("#corridorContainer").find(".control-header").each(function() {
            $(this).width($("#corridorContainer").width()-20);
        });
    }
    else
    {
        $("#corridorContainer").find(".corridor-intersection").each(function() {
            $(this).find(".control-header").width(colSize);
            $(this).find(".intersection-name").width(colSize - 25);
            $(this).find(".intersection-ip").width(colSize - 25);

            $(this).find(".camera,.gap").width(Math.round(colSize));
            $(this).find(".camera,.gap").height(Math.round(colSize * 0.75));
        });

        $("#corridorContainer").find(".corridor-col").each(function() {
            $(this).find(".control-header").width(colSize);
            $(this).find(".column-name").width(colSize - 25);

            $(this).find(".camera,.gap").width(Math.round(colSize));
            $(this).find(".camera,.gap").height(Math.round(colSize * 0.75));
        });
    }
}

function statusChecker() {
    clearInterval(statusTimer);

    $.get("helpers/corridorDesignerHelper.php", {action: "status", hash: statusHash}, function(data) {
        var xmlReturn = $($.parseXML(data));

        var statusHTML = "";
        var corridorStatus = xmlReturn.find("corridor").attr("status");

        if (corridorStatus == "working") {
            statusHTML += "<strong>Corridor</strong>: Working...<br /><ul>";

            xmlReturn.find("intersection").each(function() {
                if ($(this).attr("status") == "working")
                    statusHTML += "<li><strong>" + $(this).attr("ip") + "</strong>: Working...</li>";
                else if ($(this).attr("status") == "error")
                    statusHTML += "<li><strong>" + $(this).attr("ip") + "</strong>: <font color='red'>Error!</font></li>";
                else if ($(this).attr("status") == "completed")
                    statusHTML += "<li><strong>" + $(this).attr("ip") + "</strong>: <font color='green'>Completed!</font></li>";
            });
            statusHTML += "</ul>";

            $("#propagationStatusContainer").html(statusHTML);
            statusTimer = setInterval(statusChecker, 500);
        } else if (corridorStatus == "error") {
            $(".status-dialog-button span").text("Close");

            statusHTML += "<strong>We failed to sync this management group view with the following processors:</strong><br /><ul>";

            xmlReturn.find("intersection").each(function() {
                if ($(this).attr("status") == "error")
                    statusHTML += "<li><strong>" + $(this).attr("ip") + "</strong>: <font color='red'>Error!</font></li>";
            });
            statusHTML += "</ul><br />";

            statusHTML += "<strong>Save and Propagate</strong> again to retry.<ul>";

            $("#propagationStatusContainer").html(statusHTML);
            clearInterval(statusChecker);
        } else if (corridorStatus == "completed") {
            $(".status-dialog-button span").text("Close");
            statusHTML += "<strong>Propagation finished successfully!</strong>";
            $("#propagationStatusContainer").html(statusHTML);
            clearInterval(statusChecker);
        }
    });
}

function buildSaveTree(root) {
    var domTree = new Array();

    $(root).children().each(function() {
        if ($(this).hasClass("corridor-intersection")) {
            var intersectionName = $(this).find(".intersection-name").first().val();
            var intersectionIP = $(this).find(".intersection-ip").first().val();
            var intersectionObj = {};
            
            intersectionObj.type = "intersection";
            intersectionObj.name = intersectionName;
            intersectionObj.cameras = new Array();
            intersectionObj.ip = intersectionIP;

            $(this).children("ul").children().each(function() {
                intersectionObj.cameras.push($(this).attr("camname"));
            });

            domTree.push(intersectionObj);
        } else if ($(this).hasClass("corridor-col")) {
            var columnName = $(this).find(".column-name").first().val();
            var colObj = {};

            colObj.type = "column";
            colObj.name = columnName;
            colObj.list = new Array();

            $(this).children("ul").children().each(function() {
                if ($(this).attr("camname") == "gap")
                    colObj.list.push({"ip":$(this).attr("ipsrc"), "name":$(this).attr("camname")});
                else
                    colObj.list.push({"name":$(this).attr("camname")});
            });

            domTree.push(colObj);
        }
    });

    return domTree;
}

function addGap(parent) {
    currentTarget = getUniqueID("camera");
    $("#" + parent).append("<li class='gap' id='" + currentTarget + "'><div class='thumbnail-text'>&nbsp;<a onclick='$(this).parent().parent().remove();documentModified=true;' class='thumbnail-delete'>×</a></div></li>")
    $("#" + currentTarget).attr("camname", "gap");
    $("#" + currentTarget).css({width: colSize+"px", height: (colSize*0.75)+"px"});
    
    setRotatedStyle();

    documentModified = true;
}

function addColumnCamera(parent) {
    currentTarget = parent;
    $("#dialog-add-column-camera").dialog("open");
    
    setRotatedStyle();
    
    documentModified = true;
}

function addCamera(parent, ip) {
    currentTarget = parent;
    
    $("#camList").empty();
    
    show_busy_indicator();
    
    $.getJSON("helpers/corridorDesignerHelper.php", {action: "getcameralist", ip: ip}, function(data) {
        if (typeof data.error != "undefined")
            popupNotification("Could not retrieve camera list from " + ip + "!");
        else
        {
            for (var i = 0; i < data.list.length; i++) 
            {
                if (data.list[i] == "gap")
                    continue;

                $("#camList").append("<option value='" + data.list[i] + "'>" + data.list[i] + "</option>");
            }

            $("#dialog-configure").dialog("open");
            $("#camList").trigger("chosen:updated");

            setRotatedStyle();

            documentModified = true;
        }
    })
    .always(function() {
        hide_busy_indicator();
    });
}

function addColumn(data, name) {
    var colID = getUniqueID("col");
    var addCameraID = getUniqueID("camera");
    var addGapID = getUniqueID("gap");
    var deleteID = getUniqueID("delete");
    var ulID = getUniqueID("list");
    var nameID = getUniqueID("name");

    var newHTML = "";

    newHTML += "<div id='" + colID + "' class='corridor-col'>";
    newHTML += "<div class='control-header'>";
    newHTML += "<ul class='controls'><li><a id='" + addCameraID + "' class='ctrls-camera' target='" + ulID + "' title='Add Camera'><span class='icon icon-default'></span></a></li>";
    newHTML += "<li><a id='" + addGapID + "' class='ctrls-gap' target='" + ulID + "' title='Add Gap'><span class='icon icon-default'></span></a></li>";
    newHTML += "<li><a id='" + deleteID + "' class='ctrls-delete'>×</a></li></ul>";

    if (typeof name != "undefined")
        newHTML += "<input type='text' class='column-name input-green' id='" + nameID + "' placeholder='Column Name'/>";
    else
        newHTML += "<input type='text' class='column-name input-green' value='Unnamed Column' placeholder='Column Name'/>";

    newHTML += "</div>";
    newHTML += "<ul id='" + ulID + "' class='camera-container'>";

    if (typeof data != "undefined" && typeof data.list != "undefined" && data.list != "") {
        for (var i = 0; i < data.list.length; i++) {            
            if(data.list[i].name == "gap")
                newHTML += "<li class='gap' camname='" + data.list[i].name + "' style='width:" + colSize + "px;height:" + (colSize*0.75) + "px;'><div class='thumbnail-text'>&nbsp;<a onclick='$(this).parent().parent().remove();documentModified=true;' class='thumbnail-delete'>×</a></div></li>";
            else
                newHTML += "<li class='camera' ipsrc='" + data.list[i].ip + "' camname='" + data.list[i].name + "' style='background-image: url(\"/helpers/corridorDesignerHelper.php?action=getremoteimage&ip=" + data.list[i].ip + "&cam=" + data.list[i].name + "\")'><div class='thumbnail-text'>" + data.list[i].name + " <a onclick='$(this).parent().parent().remove();documentModified=true;' class='thumbnail-delete'>×</a></div></li>";
        }
    }

    newHTML += "</ul>";
    newHTML += "</div>";

    $("#corridorContainer").append(newHTML);
    
    $("#"+nameID).attr("value", name);

    $("#" + ulID).sortable({
        tolerance: "pointer",
        stop: function(event, ui) {
            documentModified = true;
        }});

    $('#' + addCameraID).click(function() {
        addColumnCamera($(this).attr('target'));
    });
    $('#' + addGapID).click(function() {
        addGap($(this).attr('target'));
    });
    $('#' + deleteID).click(function() {
        $(this).closest('.corridor-col').remove();
        $(window).resize();
    });

    documentModified = true;

    $(window).resize();
    
    setRotatedStyle();

    return ulID;
}

function addIntersection(ip, data, name) {
    var intersectionID = getUniqueID("intersection");
    var addCameraID = getUniqueID("camera");
    var addGapID = getUniqueID("gap");
    var deleteID = getUniqueID("delete");
    var ulID = getUniqueID("list");
    
    var nameID = getUniqueID("name");
    var ipID = getUniqueID("ip");

    if (typeof ip == "undefined")
        ip = "127.0.0.1";

    var newHTML = "";

    newHTML += "<div id='" + intersectionID + "' class='corridor-intersection'>";
    newHTML += "<div class='control-header' style='width:" + colSize + "px;'>";
    newHTML += "<ul class='controls'><li><a id='" + addCameraID + "' class='ctrls-camera' target='" + ulID + "' title='Add Camera'><span class='icon icon-default'></span></a></li>";
    newHTML += "<li><a id='" + addGapID + "' class='ctrls-gap' target='" + ulID + "' title='Add Gap'><span class='icon icon-default'></span></a></li>";
    newHTML += "<li><a id='" + deleteID + "' class='ctrls-delete'>×</a></li></ul>";

    if (typeof name != "undefined")
        newHTML += "<input type='text' class='intersection-name input-green' id='" + nameID + "' style='width:" + colSize + "px;' placeholder='Intersection Name'/>";
    else
        newHTML += "<input type='text' class='intersection-name input-green' value='Unnamed Intersection' style='width:" + colSize + "px;' placeholder='Intersection Name'/>";

    if (typeof ip != "undefined")
        newHTML += "<input type='text' class='intersection-ip input-green' id='" + ipID + "' style='width:" + colSize + "px;' placeholder='Intersection IP'/>";
    else
        newHTML += "<input type='text' class='intersection-ip input-green' value='' placeholder='Intersection IP'/>";

    newHTML += "</div>";
    newHTML += "<ul id='" + ulID + "' class='camera-container'>";

    if (typeof data != "undefined" && typeof data.list != "undefined" && data.list != "") {
        for (var i = 0; i < data.list.length; i++) {
            if (data.list[i] == "gap")
                newHTML += "<li class='gap' camname='" + data.list[i] + "' style='width:" + colSize + "px;height:" + (colSize*0.75) + "px;'><div class='thumbnail-text'>&nbsp;<a onclick='$(this).parent().parent().remove();documentModified=true;' class='thumbnail-delete'>×</a></div></li>";
            else
                newHTML += "<li class='camera' camname='" + data.list[i] + "' style='width:" + colSize + "px;height:" + (colSize*0.75) + "px;background-image: url(\"/helpers/corridorDesignerHelper.php?action=getremoteimage&quality=25&ip=" + ip + "&cam=" + data.list[i] + "\")'><div class='thumbnail-text'>" + data.list[i] + " <a onclick='$(this).parent().parent().remove();documentModified=true;' class='thumbnail-delete'>×</a></div></li>";
        }
    }

    newHTML += "</ul>";
    newHTML += "</div>";

    $("#corridorContainer").append(newHTML);
    
    $("#"+nameID).attr("value", name);
    $("#"+ipID).attr("value", ip);

    $("#" + ulID).sortable({
        tolerance: "pointer",
        stop: function(event, ui) {
            documentModified = true;
        }});

    $('#' + addCameraID).click(function() {
        addCamera($(this).attr('target'), ip);
    });
    $('#' + addGapID).click(function() {
        addGap($(this).attr('target'));
    });
    $('#' + deleteID).click(function() {
        $(this).closest('.corridor-intersection').remove();
        $(window).resize();
    });

    documentModified = true;
    
    $(window).resize();
    
    setRotatedStyle();

    return ulID;
}

function getUniqueID(prefix) {
    var id = prefix + randNum(1000, 999999999);

    while ($("#" + id).length != 0)
        id = prefix + randNum(1000, 999999999);

    return id;
}

function randNum(min, max) {
    return Math.floor((Math.random() * max) + min);
}
