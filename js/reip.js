var documentModified = false;
var currentTarget = "";
var importData = null;
var statusHash = "";

$(document).ready(function() {
    $("#menuButtons").hcSticky({top: $('header').height()});

    $(window).on('beforeunload', function() {
        if (documentModified)
            return "This document has been modified and not saved. If you leave this page without saving, you will lose your changes!";
    });

    $("#documentContainer").sortable({
        stop: function(event, ui) {
            documentModified = true;
        }
    });
    $("#documentContainer").disableSelection();

    $("#addIntersection").button().click(function() {
        $("#beginDesign").remove();
        
        var target = addIntersection();
        
        addCamera(target);
        addCamera(target);
        addCamera(target);
        addCamera(target);
        addRelay(target);
    });

    $("#save").button().click(function() {
        var domTree = buildSaveTree("#documentContainer");

        $.post("helpers/reipHelper.php", {action: "save", data: domTree},
        function(data) {
            if (data.substr(0, 7) == "Success") {
                documentModified = false;
                popupNotification("Saved successfully!", 3500, "notice");
            } else
                popupNotification(data, 3500);
        });
    });

    $("#clear").button().click(function() {
        $("#beginDesign").remove();
        $("#dialog-clear").dialog("open");
    });

    $("#download").button().click(function() {
        $("#beginDesign").remove();

        var domTree = buildSaveTree("#documentContainer");
        var title = $("#corridorTitle").val();

        $("<form method='post' action='helpers/reipHelper.php?action=download' target='_blank'><input type='hidden' name='data' value='" + JSON.stringify(domTree) + "' /></form>").appendTo('body').submit().remove();
    });

    $("#dialog-clear").dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        closeText: '×',
        buttons: {
            "Yes": function() {
                $("#documentContainer").empty();
                $(this).dialog("close");
                documentModified = true;
            },
            "Cancel": function() {
                $(this).dialog("close");
            }
        }
    });

    $("#import").button().click(function() {
        $("#beginDesign").remove();
        $("#dialog-import").dialog("open");
    });

    $("#dialog-import").dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        closeText: '×',
        buttons: {
            Cancel: function() {
                $(this).dialog("close");
            }
        }
    });
    
    $("#generate").button().click(function() {
        $("#beginDesign").remove();
        
        currentTarget = "#documentContainer";
        $(currentTarget).empty();

        $.getJSON("helpers/reipHelper.php?action=generate", function(json) {
            documentModified = true;
            generateCorridor(json);
        });
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
                popupNotification(data, 3500);
                return;
            }

            data = $.parseJSON(data);

            importData = data;

            $.each(data, function(key, value) {
                addIntersection(value.data, value.ip, value.subnet, value.gateway);
            });

            documentModified = true;
            hide_busy_indicator();
            $("#dialog-import").dialog("close");
        }
    });

    $("#propagate").button().click(function() {
        $("#beginDesign").remove();

        $("#dialog-propagate-confirm").dialog("open");
    });

    $("#dialog-propagate-status").dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        closeText: '×',
        buttons: [{
            text: "Cancel",
            click: function() {
                $(this).dialog("close");
            },
            class: 'status-dialog-button'
        }]
    });

    $("#dialog-propagate-confirm").dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        closeText: '×',
        buttons: {
            "Yes": function() {
                show_busy_indicator();

                var domTree = buildSaveTree("#documentContainer");
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
                    } else {
                        hide_busy_indicator();
                        popupNotification(data, 3500);
                    }
                });

                $(this).dialog("close");
            },
            "Cancel": function() {
                $(this).dialog("close");
            }
        }
    });
});

function generateCorridor(json) {    
    for (var i = 0; i < json.length; i++) {
        if (json[i].type == "intersection") {
            var intersection = json[i];

            var targetID = addIntersection(currentTarget, intersection.ip, intersection.subnet, intersection.gateway);

            var intersectionTarget = targetID;
            var addText = "";

            for (var j = 0; j < intersection.data.length; j++) {
                var object = intersection.data[j];
                
                if (object.type == "camera")
                    addCamera(intersectionTarget, object.ip);
                else if (object.type == "relay")
                    addRelay(intersectionTarget, object.ip);
            }

            if (typeof intersection.relay != 'undefined')
                addRelay(intersectionTarget, "DIN Relay", intersection.relay);

            $(intersectionTarget).append(addText);
        }
    }
}

function statusChecker() {
    clearInterval(statusTimer);

    $.get("helpers/corridorDesignerHelper.php", {action: "status", hash: statusHash}, function(data) {
        var xmlReturn = $(data);

        var statusHTML = "";
        var corridorStatus = $(xmlReturn.siblings()[0]).attr("status");

        if (corridorStatus == "working") {
            statusHTML += "<strong>Corridor</strong>: Working...<br /><ul>";

            xmlReturn.children().each(function() {
                if ($(this).attr("status") == "working")
                    statusHTML += "<li><strong>" + $(this).attr("ip") + "</strong>: Working...</li>";
                else if ($(this).attr("status") == "error")
                    statusHTML += "<li><strong>" + $(this).attr("ip") + "</strong>: <span style='color:red;'>Error!</span></li>";
                else if ($(this).attr("status") == "completed")
                    statusHTML += "<li><strong>" + $(this).attr("ip") + "</strong>: <span style='color:green;'>Completed!</span></li>";
            });
            statusHTML += "</ul>";

            $("#propagationStatusContainer").html(statusHTML);
            statusTimer = setInterval(statusChecker, 500);
        } else if (corridorStatus == "error") {
            $(".status-dialog-button span").text("Close");

            statusHTML += "<strong>We failed to sync this management group view with the following processors:</strong><br /><ul>";

            xmlReturn.children().each(function() {
                if ($(this).attr("status") == "error")
                    statusHTML += "<li><strong>" + $(this).attr("ip") + "</strong>: <span style='color:red;'>Error!</span></li>";
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
        var intersectionOld = $(this).find(".intersection-old").first().val();
        var intersectionNew = $(this).find(".intersection-new").first().val();
        var intersectionSubnet = $(this).find(".intersection-subnet").first().val();
        var intersectionGateway = $(this).find(".intersection-gateway").first().val();
        var intersectionObj = {};

        intersectionObj.type = "intersection";
        intersectionObj.ip = intersectionOld;
        intersectionObj.new = intersectionNew;
        intersectionObj.subnet = intersectionSubnet;
        intersectionObj.gateway = intersectionGateway;
        intersectionObj.children = new Array();

        $(this).children("ul").children().each(function() {
            if ($(this).hasClass("intersection-camera")) {            
                var oldIP = $(this).find(".camera-old").val();
                var newIP = $(this).find(".camera-new").val();

                intersectionObj.children.push({"type":"camera", "old": oldIP, "new": newIP});
            } else if($(this).hasClass("intersection-relay")) {            
                var oldIP = $(this).find(".relay-old").val();
                var newIP = $(this).find(".relay-new").val();

                intersectionObj.children.push({"type":"relay", "old": oldIP, "new": newIP});
            }
        });

        domTree.push(intersectionObj);
    });

    return domTree;
}

function addCamera(parent, ip) {
    var deleteButton = getUniqueID("deleteButton");
    
    if (ip == null)
        $("#" + parent).append("<li class='intersection-camera'><button class='pd-button-delete' id='" + deleteButton + "'>Delete Camera</button> Camera Old IP: <input type='text' class='camera-old' />  New IP: <input type='text' class='camera-new' /></li>")
    else
        $("#" + parent).append("<li class='intersection-camera'><button class='pd-button-delete' id='" + deleteButton + "'>Delete Camera</button> Camera Old IP: <input type='text' class='camera-old' value='" + ip + "' />  New IP: <input type='text' class='camera-new' /></li>")
    
    $("#" + deleteButton).button({icons:{primary: 'icon-cancel'},text: false}).click(function(){ $(this).parent().remove(); });
    
    documentModified = true;
}

function addRelay(parent, ip) {
    var deleteButton = getUniqueID("deleteButton");
    
    if (ip == null)
        $("#" + parent).append("<li class='intersection-relay'><button class='pd-button-delete' id='" + deleteButton + "'>Delete Relay</button> Relay Old IP: <input type='text' class='relay-old' />  New IP: <input type='text' class='relay-new' /></li>")
    else
        $("#" + parent).append("<li class='intersection-relay'><button class='pd-button-delete' id='" + deleteButton + "'>Delete Relay</button> Relay Old IP: <input type='text' class='relay-old' value='" + ip + "' />  New IP: <input type='text' class='relay-new' /></li>")
    
    $("#" + deleteButton).button({icons:{primary: 'icon-cancel'},text: false}).click(function(){ $(this).parent().remove(); });
    
    documentModified = true;
}

function addIntersection(data, ip, subnet, gateway) {
    var intersectionID = getUniqueID("intersection");
    var deleteID = getUniqueID("delete");
    var addCameraID = getUniqueID("camera");
    var addRelayID = getUniqueID("relay");
    var ulID = getUniqueID("list");

    var newHTML = "";

    newHTML += "<div id='" + intersectionID + "' class='reip-intersection'>";
    newHTML += "<div class='intersection-header'>";
    newHTML += "<button id='" + deleteID + "' class='pd-button'>Delete</button>";

    if (ip == null)
        newHTML += "Current IP: <input type='text' class='intersection-old' value='' />";
    else
        newHTML += "Current IP: <input type='text' class='intersection-old' value='" + ip + "' />";
    
    newHTML += "New IP: <input type='text' class='intersection-new' value='' />";
    
    if (gateway == null)
        newHTML += "New Gateway: <input type='text' class='intersection-gateway' value='' />";
    else
        newHTML += "New Gateway: <input type='text' class='intersection-gateway' value='" + gateway + "' />";
    
    if (subnet == null)
        newHTML += "New Subnet: <input type='text' class='intersection-subnet' value='' />";
    else
        newHTML += "New Subnet: <input type='text' class='intersection-subnet' value='" + subnet + "' />";

    newHTML += "<button id='" + addCameraID + "' class='pd-button' target='" + ulID + "'>Add Camera</button>";
    newHTML += "<button id='" + addRelayID + "' class='pd-button' target='" + ulID + "'>Add Relay</button>";
    newHTML += "</div>";
    newHTML += "<ul id='" + ulID + "' class='camera-container'>";

    if (typeof data != "undefined") {
        for (var i = 0; i < data.length; i++) {
            var deleteButton = getUniqueID("deleteButton");
            
            if (data[i].type == "camera")
                newHTML += "<li class='intersection-camera'><button class='pd-button-delete' id='" + deleteButton + "'>Delete Camera</button> Camera Old IP: <input type='text' class='camera-old' value='" + data[i].ip + "' />  New IP: <input type='text' class='camera-new' value='" + data[i].new + "' /><script>$('#" + deleteButton + "').button({icons:{primary: 'icon-cancel'},text: false}).click(function(){ $(this).parent().remove(); });</script></li>";
            else if (data[i].type == "relay")
                newHTML += "<li class='intersection-relay'><button class='pd-button-delete' id='" + deleteButton + "'>Delete Relay</button> Relay Old IP: <input type='text' class='relay-old' value='" + data[i].ip + "' />  New IP: <input type='text' class='relay-new' value='" + data[i].new + "' /><script>$('#" + deleteButton + "').button({icons:{primary: 'icon-cancel'},text: false}).click(function(){ $(this).parent().remove(); });</script></li>";
        }
    }

    newHTML += "</ul>";
    newHTML += "<div style='clear:both;'></div>"
    newHTML += "</div>";

    $("#documentContainer").append(newHTML);

    $("#" + ulID).sortable({
        tolerance: "pointer",
        stop: function(event, ui)
        {
            documentModified = true;
        }});

    $('#' + addCameraID).button({icons: {primary: 'icon-camera'}, text: false}).click(function() {
        addCamera(ulID);
    });
    $('#' + addRelayID).button({icons: {primary: 'icon-off'}, text: false}).click(function() {
        addRelay(ulID);
    });
    $('#' + deleteID).button({icons: {primary: 'icon-cancel'}, text: false}).click(function() {
        $(this).parent().parent().remove();
    });

    documentModified = true;

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