var currentTarget = "";

var importMethod = "append";
var importedData = "";
var importedType = "xml";
var statusHash = "";
var statusTimer = null;
var documentModified = false;

$(document).ready(function() {

    $('.chosen-dialog').chosen({
        disable_search: true,
        single_backstroke_delete: false,
        inherit_select_classes: true
    });

    // Sub Menu
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
    // --- Sub Menu End

    $(window).on('beforeunload', function() {
        if (documentModified)
            return "This Portal has been modified and not saved. If you leave this page without saving, you will lose your changes!";
    });

    $("#portalContainer").sortable({
        stop: function(event, ui) {
            documentModified = true;
        }
    });

    $("#autoGen").click(function() {
        $("#beginDesign").remove();
        
        currentTarget = "#portalContainer";
        $(currentTarget).empty();

        $.getJSON("helpers/portalDesignerHelper.php?action=generate", function(json) {
            documentModified = true;
            generateCorridor("Generated Management Group", json, false);
        });
    });
    
    $("#clear").click(function() {
        $("#beginDesign").remove();
        $("#dialog-clear").dialog("open");
    });

    $("#addMap").click(function(e) {
        e.preventDefault()
        $("#beginDesign").remove();
        currentTarget = "#portalContainer";
        addMap(currentTarget);
        documentModified = true;
    });

    $("#import").click(function() {
        $("#beginDesign").remove();
        $("#dialog-import").dialog("open");
        $("#dialog-import>div.error-msg").text("");
    });
    
    $("#downloadPortal").click(function() {
        $("#beginDesign").remove();
        var rootNode = $("#portalContainer");

        var tree = buildTree(rootNode);
        var title = $("#portalTitle").val().replace(/\"/g, "&quot;");
        
        $("<form method='post' action='helpers/portalDesignerHelper.php?action=download' target='_blank'><input type='hidden' name='title' value='" + $.base64.encode(title) + "' /><input type='hidden' name='data' value='" + $.base64.encode(JSON.stringify(tree)) + "' /></form>").appendTo('body').submit().remove();
    });
    
    $("#propagatePortal").click(function() {
        $("#beginDesign").remove();
        
        $("#dialog-propagate-confirm").dialog("open");
        $("#dialog-propagate-confirm>div.error-msg").text("");
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
            open: function() { 
                $(this).addClass('status-dialog-button'); 
            }
        }]
    });
    
    $("#dialog-propagate-confirm").dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        closeText: '×',
        buttons: {
            "Yes": function() {
                var rootNode = $("#portalContainer");

                var tree = buildTree(rootNode);
                var title = $("#portalTitle").val().replace(/\"/g, "&quot;");
                
                show_busy_indicator();

                $.post("helpers/portalDesignerHelper.php", {action: "save", title: title, data: tree},
                function(data) {
                    if (data.substr(0,7) == "Success") {
                        documentModified = false;
                        statusHash = data.substr(9);
                        
                        $.ajax({
                            url: "helpers/portalDesignerHelper.php",
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
    
    $("#dialog-clear").dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        closeText: '×',
        buttons: {
            "Yes": function() {
                currentTarget = "#portalContainer";
                $(currentTarget).empty();
                $(this).dialog("close");
                documentModified = true;
            },
            "Cancel": function() {
                $(this).dialog("close");
            }
        }
    });
    
    $("#dialog-import-confirm-csv").dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        closeText: '×',
        buttons: {
            "Yes": function() {
                currentTarget = "#portalContainer";
                $(currentTarget).empty();
                
                $("#portalTitle").val(importedData.title);
                
                for (i=0; i < importedData.data.length; i++) {
                    var corridor = importedData.data[i];
                    
                    generateCorridor(corridor.name, corridor.data);
                    
                    currentTarget = "#portalContainer";
                }
                
                $(this).dialog("close");
                documentModified = true;
            },
            "Cancel": function() {
                $(this).dialog("close");
            }
        }
    });
    
    $("#dialog-import-confirm-portal").dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        width: 450,
        closeText: '×',
        buttons: {
            "Replace": function() {
                currentTarget = "#portalContainer";
                $(currentTarget).empty();
                
                $("#portalTitle").val(importedData.title.replace(/&quot;/g, "\""));
                
                for (i=0; i < importedData.data.length; i++) {
                    var node = importedData.data[i];
                    
                    if (node.type == "map")
                        addMap(currentTarget, node.name, node.url, false);
                    else if (node.type == "corridor") {
                        generateCorridor(node.name, node.data, false);
                    }
                    
                    currentTarget = "#portalContainer";
                }
                
                $(this).dialog("close");
                documentModified = true;
            },
            "Append": function() {
                currentTarget = "#portalContainer";
                for (i=0; i < importedData.data.length; i++) 
                {
                    var node = importedData.data[i];

                    if (node.type == "map")
                        addMap(currentTarget, node.name, node.url, false);
                    else if (node.type == "corridor") {
                        generateCorridor(node.name, node.data, false);
                    }

                    currentTarget = "#portalContainer";
                }
                $(this).dialog("close");
                documentModified = true;
            },
            "Cancel": function() {
                $(this).dialog("close");
            }
        }
    });

    $("#dialog-import-choose").dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        closeText: '×',
        buttons: {
            "Append": function() {
                currentTarget = "#portalContainer";
                generateCorridor("Imported Corridor", importedData);
                documentModified = true;
                $(this).dialog("close");
            },
            "Overwrite": function() {
                currentTarget = "#portalContainer";
                $(currentTarget).empty();
                generateCorridor("Imported Corridor", importedData);
                documentModified = true;
                $(this).dialog("close");
            },
            Cancel: function() {
                $(this).dialog("close");
            }
        }
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
            
            if (data.substr(0,5) == "Error") {
                $("#dialog-import>div.error-msg").text(data);
                return;
            }
            
            data = $.parseJSON(data);
            
            importedType = data.type;
            importedData = data.data;
            
            if (importedType == "csv")
                $("#dialog-import-confirm-csv").dialog("open");
            else if (importedType == "corridor")
                $("#dialog-import-choose").dialog("open");
            else if (importedType == "portal")
                $("#dialog-import-confirm-portal").dialog("open");
            
            documentModified = true;
            hide_busy_indicator();
            $("#dialog-import").dialog("close");
        }
    });
    
    $("#savePortal").click(function() {
        $("#beginDesign").remove();
        var rootNode = $("#portalContainer");

        var tree = buildTree(rootNode);
        var title = $("#portalTitle").val().replace(/\"/g, "&quot;");

        $.post("helpers/portalDesignerHelper.php", {action: "save", title: title, data: tree},
        function(data) {
            if (data.substr(0,7) == "Success") {
                documentModified = false;
                popupNotification("Saved successfully!", 3500, "notice");
            } else
                popupNotification(data, 3500);
        });
    });

    $("#addCorridor").click(function(e) {
        e.preventDefault()
        $("#beginDesign").remove();
        currentTarget = "#portalContainer";
        addCorridor(currentTarget);
        documentModified = true;
    });
});

function isScrolledIntoView(elem) {
    var rect = $(elem).get(0).getBoundingClientRect();

    return (
        rect.top >= 0 &&
        rect.bottom <= $(window).height()
        );
}

function statusChecker() {
    clearInterval(statusTimer);
    
    $.get("helpers/portalDesignerHelper.php", {action: "status", hash: statusHash}, function(data) {
        var xmlReturn = $($.parseXML(data));
        
        var statusHTML = "";
        var corridorStatus = xmlReturn.find("corridor").attr("status");
        
        if (corridorStatus == "working") {
            statusHTML += "<strong>Corridor</strong>: Working...<br /><ul>";
            
            xmlReturn.find("intersection").each(function() {
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
        }
        else if (corridorStatus == "error") {
            $(".status-dialog-button span").text("Close");
            
            statusHTML += "<strong>We failed to sync this portal with the following processors:</strong><ul>";
            
            xmlReturn.find("intersection").each(function() {
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

function generateCorridor(name, json, scroll) {
    var targetID = addCorridor(currentTarget, name.replace(/&quot;/g, "\""), scroll);

    currentTarget = "#" + targetID;

    for (var i = 0; i < json.length; i++) 
    {
        if (json[i].type == "intersection") {
            var intersection = json[i];

            var targetID = addIntersection(currentTarget, intersection.name.replace(/&quot;/g, "\""), intersection.ip, scroll);

            var intersectionTarget = "#" + targetID;
            var addText = "";

            for (var j = 0; j < intersection.data.length; j++) {
                var object = intersection.data[j];

                if (object.type == "camera")
                    addCamera(intersectionTarget, object.name.replace(/&quot;/g, "\""), object.ip, scroll);
                else if (object.type == "relay")
                    addRelay(intersectionTarget, object.name.replace(/&quot;/g, "\""), object.ip, scroll);
            }

            if (typeof intersection.relay != 'undefined')
                addRelay(intersectionTarget, "DIN Relay", intersection.relay, scroll);

            $(intersectionTarget).append(addText);
        }
        else if (json[i].type == "map") {
            var map = json[i];
            addMap(currentTarget, map.name.replace(/&quot;/g, "\""), map.url, scroll);
        }
    }
}

function bindClickEvent(id) {
    $(id).click(function(event) {
        if (event.target != this)
            return;
        
        if ($(this).attr("collapsed") == "true") {
            $(this).parent().children().show();
            $(this).show();
            $(this).attr("collapsed", "false");
            $(this).parent().children(".helper").remove();
        } else {
            $(this).parent().children().hide();
            $(this).show();
            $(this).attr("collapsed", "true");
            $(this).parent().append("<div class='helper'><center><strong>Collapsed. Click above to expand.</strong></center></div>");
        }
    });
}

function addMap(parent, name, url, scroll) {
    var mapID = getUniqueID("map");
    var deleteButton = getUniqueID("deleteButton");
    
    var mappyID = getUniqueID("mappyID");
    var mappyURL = getUniqueID("mappyURL");
    
    if (name == null || url == null)
        $(parent).append("<li class='portal-map' id='" + mapID + "'><a class='delete' id='" + deleteButton + "'>×</a><div class='labelled-input'><label class='label-name'>Map Name:</label><input type='text' class='map-name input-green' placeholder='Map Name'/></div><div class='labelled-input'><label class='label-ipurl'>Map URL:</label><input type='text' class='map-url input-green' placeholder='Map URL'/></div><script>$('#" + deleteButton + "').click(function(){ $(this).parent().remove(); });</script></li>")
    else
        $(parent).append("<li class='portal-map' id='" + mapID + "'><a class='delete' id='" + deleteButton + "'>×</a><div class='labelled-input'><label class='label-name'>Map Name:</label><input type='text' class='map-name input-green' id='" + mappyID + "' placeholder='Map Name'/></div><div class='labelled-input'><label class='label-ipurl'>Map URL:</label><input type='text' class='map-url input-green' id='" + mappyURL + "' placeholder='Map URL'/></div><script>$('#" + deleteButton + "').click(function(){ $(this).parent().remove(); });</script></li>")
    
    $("#"+mappyID).attr("value", name);
    $("#"+mappyURL).attr("value", url);
    
    documentModified = true;
    
    if(scroll !== false)
    {
        if (!isScrolledIntoView($("#" + mapID)))
        {
            $('html, body').animate({
                scrollTop: $("#" + mapID).offset().top
            }, 1000);
        }
    }
    
    return mapID;
}

function addRelay(parent, name, ip, scroll) {
    var deleteButton = getUniqueID("deleteButton");
    var relayID = getUniqueID("relay");
    
    var relID = getUniqueID("relID");
    var relIP = getUniqueID("relIP");
    
    if (name == null || ip == null)
        $(parent).append("<li class='portal-relay' id='" + relayID + "'><a class='delete' id='" + deleteButton + "'>×</a><div class='labelled-input'><label class='label-name'>Relay Name:</label><input type='text' class='relay-name input-green' placeholder='Relay Name' value='DIN Relay'/></div><div class='labelled-input'><label class='label-ipurl'>Relay IP:</label><input type='text' class='relay-ip input-green' placeholder='Relay IP'/></div><script>$('#" + deleteButton + "').click(function(){ $(this).parent().remove(); });</script></li>")
    else
        $(parent).append("<li class='portal-relay' id='" + relayID + "'><a class='delete' id='" + deleteButton + "'>×</a><div class='labelled-input'><label class='label-name'>Relay Name:</label><input type='text' class='relay-name input-green' id='" + relID + "' placeholder='Relay Name'/></div><div class='labelled-input'><label class='label-ipurl'>Relay IP:</label><input type='text' class='relay-ip input-green' id='" + relIP + "' placeholder='Relay IP'/></div><script>$('#" + deleteButton + "').click(function(){ $(this).parent().remove(); });</script></li>")
    
    $("#"+relID).attr("value", name);
    $("#"+relIP).attr("value", ip);
    
    documentModified = true;
    
    if(scroll !== false)
    {
        if (!isScrolledIntoView($("#" + relayID)))
        {
            $('html, body').animate({
                scrollTop: $("#" + relayID).offset().top
            }, 1000);
        }
    }
    
    return relayID;
}

function addCamera(parent, name, ip, scroll) {
    var deleteButton = getUniqueID("deleteButton");
    var cameraID = getUniqueID("camera");
    var camID = getUniqueID("cameraID");
    var camIP = getUniqueID("cameraIP");

    if (name == null || ip == null)
        $(parent).append("<li class='portal-camera' id='" + cameraID + "'><a class='delete' id='" + deleteButton + "'>×</a><div class='labelled-input'><label class='label-name'>Camera Name:</label><input type='text' class='camera-name input-green' placeholder='Camera Name'/></div><div class='labelled-input'><label class='label-ipurl'>Camera IP:</label><input type='text' class='camera-ip input-green' placeholder='Camera IP'/></div><script>$('#" + deleteButton + "').click(function(){ $(this).parent().remove(); });</script></li>")
    else
        $(parent).append("<li class='portal-camera' id='" + cameraID + "'><a class='delete' id='" + deleteButton + "'>×</a><div class='labelled-input'><label class='label-name'>Camera Name:</label><input type='text' class='camera-name input-green' id='" + camID + "' placeholder='Camera Name'/></div><div class='labelled-input'><label class='label-ipurl'>Camera IP:</label><input type='text' class='camera-ip input-green' id='" + camIP + "' placeholder='Camera IP'/></div><script>$('#" + deleteButton + "').click(function(){ $(this).parent().remove(); });</script></li>")
    
    $("#"+camID).attr("value", name);
    $("#"+camIP).attr("value", ip);
    
    documentModified = true;
    
    if(scroll !== false)
    {
        if (!isScrolledIntoView($("#" + cameraID)))
        {
            $('html, body').animate({
                scrollTop: $("#" + cameraID).offset().top
            }, 1000);
        }
    }
    
    return cameraID;
}

function addIntersection(parent, name, ip, scroll) {
    var targetID = getUniqueID("intersection");
    var headerID = getUniqueID("header");
    var addCameraID = getUniqueID("addCamera");
    var addRelayID = getUniqueID("addRelay");
    var deleteButton = getUniqueID("deleteButton");
    var intersectionVal = getUniqueID("intersectionVal");
    var ipVal = getUniqueID("ipVal");

    var addText;
    addText = "<li class='portal-intersection'>";
    addText += "<div class='portal-header' id='" + headerID + "'>";
    addText += "<a id='" + deleteButton + "' class='intersection-delete'>×</a>";
    addText += "<div class='right'>";
    addText += "<button id='" + addCameraID + "' class='btn btn-default btn-add' target='" + targetID + "'><span class='icon icon-default'></span>Camera</button>";
    addText += "<button id='" + addRelayID + "' class='btn btn-default btn-add' target='" + targetID + "'><span class='icon icon-default'></span>Relay</button>";
    addText += "</div>";

    if (name == null || ip == null)
        addText += "<div class='labelled-input'><label>Intersection Name:</label><input type='text' class='intersection-name input-green' placeholder='Intersection Name'/></div><div class='labelled-input'><label>Intersection IP:</label><input type='text' class='intersection-ip input-green' placeholder='Intersection IP'/></div>";
    else
        addText += "<div class='labelled-input'><label>Intersection Name:</label><input type='text' class='intersection-name input-green' id='" + intersectionVal + "' placeholder='Intersection Name'/></div><div class='labelled-input'><label>Intersection IP:</label><input type='text' class='intersection-ip input-green' id='" + ipVal + "' placeholder='Intersection IP'/></div>";

    addText += "</div>";
    addText += "<ul id='" + targetID + "' class='portal-intersection-container'></ul>";
    addText += "<script>";
    addText += "$('#" + deleteButton + "').click(function(){ $(this).parent().parent().remove(); });";
    addText += "$('#" + addCameraID + "').click(function(){ addCamera('#' + $(this).attr('target')); });";
    addText += "$('#" + addRelayID + "').click(function(){ addRelay('#' + $(this).attr('target')); });";
    addText += "$('#" + targetID + "').sortable({stop: function(event, ui){ documentModified = true; }});$('#" + targetID + "');"
    addText += "</script>";
    addText += "</li>";

    $(parent).append(addText);
    
    $('#' + intersectionVal).attr("value", name);
    $('#' + ipVal).attr("value", ip);
    
    bindClickEvent("#" + headerID);
    
    documentModified = true;
    
    if(scroll !== false)
    {
        if (!isScrolledIntoView($("#" + targetID)))
        {
            $('html, body').animate({
                scrollTop: $("#" + targetID).offset().top
            }, 1000);
        }
    }

    return targetID;
}

function addCorridor(parent, name, scroll) {
    var targetID = getUniqueID("corridor");
    var addMapID = getUniqueID("addMap");
    var addIntersectionID = getUniqueID("addIntersection");
    var deleteButton = getUniqueID("deleteButton");
    var headerID = getUniqueID("header");
    var nameID = getUniqueID("corridorName");

    var addText;
    addText = "<li class='portal-corridor'>";
    addText += "<div class='portal-header' id='" + headerID + "'>";
    addText += "<a id='" + deleteButton + "' class='corridor-delete'>×</a>";
    addText += "<div class='right'>";
    addText += "<button id='" + addMapID + "' class='btn btn-default btn-add' target='" + targetID + "'><span class='icon icon-default'></span>Map</button>";
    addText += "<button id='" + addIntersectionID + "' class='btn btn-default btn-add' target='" + targetID + "'><span class='icon icon-default'></span>Intersection</button>";
    addText += "</div>";
    addText += "<div class='labelled-input'><label>Management Group Name:</label>";

    if (name == null)
        addText += "<input type='text' class='corridor-name input-green' placeholder='Management Group Name'/>";
    else
        addText += "<input type='text' class='corridor-name input-green' id='" + nameID + "' placeholder='Management Group Name'/>";

    addText += "</div>";

    addText += "</div>"
    addText += "<ul id='" + targetID + "' class='portal-corridor-container'></ul>";
    addText += "<script>";
    addText += "$('#" + deleteButton + "').click(function(){ $(this).parent().parent().remove(); });";
    addText += "$('#" + addMapID + "').click(function(){ addMap('#' + $(this).attr('target')); });";
    addText += "$('#" + addIntersectionID + "').click(function(){ addIntersection('#' + $(this).attr('target'));  });";
    addText += "$('#" + targetID + "').sortable({stop: function(event, ui){ documentModified = true; }});$('#" + targetID + "');"
    addText += "</script>";
    addText += "</li>";

    $(parent).append(addText);
    
    $('#' + nameID).attr("value",name);
    
    bindClickEvent("#" + headerID);
    
    documentModified = true;
    
    if(scroll !== false)
    {
        if (!isScrolledIntoView($("#" + targetID)))
        {
            $('html, body').animate({
                scrollTop: $("#" + targetID).offset().top
            }, 1000);
        }
    }

    return targetID;
}

function buildTree(node) 
{
    var tree = new Array();

    node.children().each(function() 
    {
        if ($(this).hasClass("portal-map")) 
        {
            var name = $(this).find(".map-name").val().replace(/\"/g, "&quot;");
            var url = $(this).find(".map-url").val();

            var obj = {};
            obj.type = "map";
            obj.name = name;
            obj.url = url;

            tree.push(obj);
        } else if ($(this).hasClass("portal-camera")) 
        {
            var name = $(this).find(".camera-name").val().replace(/\"/g, "&quot;");
            var ip = $(this).find(".camera-ip").val();

            var obj = {};
            obj.type = "camera";
            obj.name = name;
            obj.ip = ip;

            tree.push(obj);
        } else if ($(this).hasClass("portal-relay")) 
        {
            var name = $(this).find(".relay-name").val().replace(/\"/g, "&quot;");
            var ip = $(this).find(".relay-ip").val();

            var obj = {};
            obj.type = "relay";
            obj.name = name;
            obj.ip = ip;

            tree.push(obj);
        } else if ($(this).hasClass("portal-corridor")) 
        {
            var name = $(this).find(".corridor-name").val().replace(/\"/g, "&quot;");

            var obj = {};
            obj.type = "corridor";
            obj.name = name;
            obj.children = buildTree($(this).find(".portal-corridor-container"));

            tree.push(obj);
        } else if ($(this).hasClass("portal-intersection")) 
        {
            var name = $(this).find(".intersection-name").val().replace(/\"/g, "&quot;");
            var ip = $(this).find(".intersection-ip").val();
            
            var obj = {};
            obj.type = "intersection";
            obj.ip = ip;
            obj.name = name;
            obj.children = buildTree($(this).find(".portal-intersection-container"));

            tree.push(obj);
        }
    });

    return tree;
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
