var edited = false;
var statusHash = "";
var propTimer = 0;
var corridorIPCount = 0;
var currentUser = "";

function initScripts(ipCount, username)
{
    corridorIPCount = ipCount;
    currentUser = username;
    
     $(window).on('beforeunload', function() {
        if (edited && corridorIPCount != 0)
            return "You have modified user accounts locally. You will still need to Sync for these changes to take effect on your management group.";
    });
    
    $("#addUser").button().click(function() {
        $("#addUserDialog").dialog("open");
    });

    $("#sync").button().click(function() {
        $("#syncConfirm").dialog("open");
    });

    $('#userTableBody').tooltip({
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

    $("#synchronizingDialog").dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        closeText: '×',
        buttons: {
            "Close": function() {
                $(this).dialog("close");
            }
        }
    });
    
    $("#syncConfirm").dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        closeText: '×',
        width: 400,
        buttons: {
            "Yes": function() {
                edited = false;
                
                show_busy_indicator();

                $.post('/helpers/editUserHelper.php?action=sync', function(data) {
                    $("#addUserDialog").dialog("close");
                    hide_busy_indicator();
                    $("#syncConfirm").dialog("close");

                    if (data.substr(0, 5) == "Error")
                        alert(data);
                    else {
                        statusHash = data;
                        $("#synchronizingDialog").dialog("open")
                        
                        setTimeout(propagationWatcher, 500);
                    }
                });
            },
            Cancel: function() {
                $(this).dialog("close");
            }
        }
    });

    $("#addUserDialog").dialog({
        dialogClass: 'add-user-dialog',
        autoOpen: false,
        resizable: false,
        modal: true,
        width: 500,
        closeText: '×',
        buttons: {
            "Add": function() {
                var user = $("#addUsername").val();
                
                var pass = $("#addPassword").val();
                var pass2 = $("#addPassword2").val();
                
                if (pass != pass2) {
                    alert("The two passwords you have entered are not the same.");
                    return;
                }
                
                var enabled = $("#addEnabled").prop('checked');
                var reports = $("#addReports").prop('checked');
                var maintenance = $("#addMaintenance").prop('checked');
                var corridor = $("#addCorridor").prop('checked');
                var configure = $("#addConfiguration").prop('checked');
                var manual = $("#addManual").prop('checked');
                var admin = $("#addAdmin").prop('checked');
                
                var webapi = $("#addWebAPI").val();
                var cams = $("#addCameras").val();
                var timeout = $("#addSession").val();
                
                var cameras = false, cameracontrols = false, web = false, api = false;
                
                if (cams == "v")
                    cameras = true;

                else if (cams == "vc") {
                    cameras = true;
                    cameracontrols = true;
                }
                
                if (webapi == "api")
                    api = true;

                else if (webapi == "webapi") {
                    web = true;
                    api = true;
                }

                var sendData = {
                    action: 'addUser',
                    username: user,
                    password: pass,
                    enabled: enabled,
                    reports: reports,
                    cameras: cameras,
                    cameracontrols: cameracontrols,
                    maintenance: maintenance,
                    corridor: corridor,
                    configure: configure,
                    manual: manual,
                    admin: admin,
                    web: web,
                    api: api,
                    timeout: timeout
                };
                
                show_busy_indicator();

                $.post('/helpers/editUserHelper.php', sendData, function(data) {
                    $("#addUserDialog").dialog("close");
                    hide_busy_indicator();

                    if (data.substr(0, 5) == "Error")
                        alert(data);
                    
                    reloadTableData();
                });
                
                edited = true;
            },
            Cancel: function() {
                $(this).dialog("close");
            }
        }
    });

    $("#editUserDialog").dialog({
        dialogClass: 'edit-user-dialog',
        autoOpen: false,
        resizable: false,
        modal: true,
        width: 500,
        closeText: '×',
        buttons: {
            "Save": function() {
                var pass = $("#editPassword").val();
                var pass2 = $("#editPassword2").val();
                
                if (pass != pass2) {
                    alert("The two passwords you have entered are not the same.");
                    return;
                }
                
                var user = $("#editName").val();
                var enabled = $("#editEnabled").prop('checked');
                var reports = $("#editReports").prop('checked');
                var maintenance = $("#editMaintenance").prop('checked');
                var corridor = $("#editCorridor").prop('checked');
                var configure = $("#editConfiguration").prop('checked');
                var manual = $("#editManual").prop('checked');
                var admin = $("#editAdmin").prop('checked');
                
                var webapi = $("#editWebAPI").val();
                var cams = $("#editCameras").val();
                var timeout = $("#editSession").val();
                
                var cameras = false, cameracontrols = false, web = false, api = false;
                
                if (cams == "v")
                    cameras = true;

                else if (cams == "vc") {
                    cameras = true;
                    cameracontrols = true;
                }
                
                if (webapi == "api")
                    api = true;

                else if (webapi == "webapi") {
                    web = true;
                    api = true;
                }

                var sendData = {
                    action: 'editUser',
                    username: user,
                    password: pass,
                    enabled: enabled,
                    reports: reports,
                    cameras: cameras,
                    cameracontrols: cameracontrols,
                    maintenance: maintenance,
                    corridor: corridor,
                    configure: configure,
                    manual: manual,
                    admin: admin,
                    web: web,
                    api: api,
                    timeout: timeout
                };

                show_busy_indicator();

                $.post('/helpers/editUserHelper.php', sendData, function(data) {
                    $("#editUserDialog").dialog("close");
                    hide_busy_indicator();
                    if (data.substr(0, 5) == "Error")
                        alert(data);
                    
                    reloadTableData();
                });
                
                edited = true;
            },
            Cancel: function() {
                $(this).dialog("close");
            }
        }
    });

    $("#deleteUserDialog").dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        closeText: '×',
        width: 400,
        buttons: {
            "Yes": function() {
                var user = $("#deleteUsername").text();

                var sendData = {
                    action: 'deleteUser',
                    username: user
                };
                
                show_busy_indicator();

                $.post('/helpers/editUserHelper.php', sendData, function(data) {
                    $("#deleteUserDialog").dialog("close");
                    hide_busy_indicator();

                    if (data.substr(0, 5) == "Error")
                        alert(data);
                    
                    reloadTableData();
                });
                
                edited = true;
            },
            Cancel: function() {
                $(this).dialog("close");
            }
        }
    });

    reloadTableData();
}


function propagationWatcher() 
{
    $.get("helpers/editUserHelper.php", {action: "status", hash: statusHash}, function(data) 
    {
        var xmlReturn = $($.parseXML(data));

        var statusHTML = "";
        var corridorStatus = xmlReturn.find("corridor").attr("status");

        if (corridorStatus == "working") {
            statusHTML += "<strong>Corridor</strong>: Working...<br /><ul>";

            xmlReturn.find("intersection").each(function()
            {
                if ($(this).attr("status") == "working")
                    statusHTML += "<li><strong>" + $(this).attr("ip") + "</strong>: Working...</li>";
                else if ($(this).attr("status") == "error")
                    statusHTML += "<li><strong>" + $(this).attr("ip") + "</strong>: <font color='red'>Error!</font></li>";
                else if ($(this).attr("status") == "completed")
                    statusHTML += "<li><strong>" + $(this).attr("ip") + "</strong>: <font color='green'>Completed!</font></li>";
            });
            statusHTML += "</ul>";

            $("#synchronizingDialog").html(statusHTML);
            setTimeout(propagationWatcher, 500);
        } else if (corridorStatus == "error") {
            $(".status-dialog-button span").text("Close");

            statusHTML += "<strong>We failed to sync to the following processors:</strong><br /><ul>";
            
            xmlReturn.find("intersection").each(function() 
            {
                if ($(this).attr("status") == "error")
                    statusHTML += "<li><strong>" + $(this).attr("ip") + "</strong>: <font color='red'>Error!</font></li>";
            });
            statusHTML += "</ul><br />";

            statusHTML += "<strong>Propagate Users</strong> again to retry.<ul>";

            $("#synchronizingDialog").html(statusHTML);
        } else if (corridorStatus == "completed") {
            $(".status-dialog-button span").text("Close");
            statusHTML += "<strong>Syncing finished successfully!</strong>";
            $("#synchronizingDialog").html(statusHTML);
        }
    });
}

function reloadTableData() {
    $("#userTableBody").append("<tr><td colspan='3'>Updating, please wait...</td></tr>");

    var sendData = {
        action: 'getUserTable'
    };

    $.post('/helpers/editUserHelper.php', sendData, function(data) {
        $("#userTableBody").html(data);
    });
}

function editUser(userName) {
    $("#editUserDialog").html("Please wait...");
    $("#editUserDialog").dialog("open");

    $.getJSON('/helpers/editUserHelper.php?action=viewUser&name=' + userName, function(data) {
        $("#editUserDialog").html('<div class="form-horizontal"><p><small>Note: Leave both password fields blank to keep the current password.</small></p><div class="control-group"><label for="editPassword" class="control-label">Password</label><div class="controls"><input type="password" id="editPassword" class="input-large input-green" placeholder="Password"/></div></div><div class="control-group"><label for="editPassword2" class="control-label">Confirm Password</label><div class="controls"><input type="password" id="editPassword2" class="input-large input-green" placeholder="Confirm Password"/></div></div><div class="control-group"><label for="editWebAPI" class="control-label">Web / API Access</label><div class="controls"><div style="position:relative;z-index:1004;display:inline;"><select id="editWebAPI" class="chosen-dynamic single-select span3"><option value="api">API Only</option><option value="webapi">WebUI &amp; API</option></select></div><div class="inline-help" inlinehelp="<ul><li><strong>API Only:</strong> User has access to the InSync WebUI API only.</li><li><strong>WebUI &amp; API:</strong> User has access to both options.</li></ul>"><span class="icon-default"></span><span class="icon-hover"></span></div></div></div><div class="control-group"><label for="editCameras" class="control-label">Cameras</label><div class="controls"><div style="position:relative;z-index:1003;display:inline;"><select id="editCameras" class="chosen-dynamic single-select span3"><option value="d">Disabled</option><option value="v">View Only</option><option value="vc">View / Control</option></select></div><div class="inline-help" inlinehelp="<ul><li><strong>Disabled:</strong> No access to cameras.</li><li><strong>View Only:</strong> User can view cameras but not control or record.</li><li><strong>View &frasl; Record:</strong> User has full access to view, control, and record cameras.</li></ul>"> <span class="icon-default"></span><span class="icon-hover"></span></div></div></div><div class="control-group">            <label for="editSession" class="control-label">Session Timeout</label>            <div class="controls">                <select id="editSession" class="chosen-dynamic single-select span2">                    <option value="0">Disabled</option>                    <option value="5">5</option>                    <option value="10">10</option>                    <option value="15">15</option>                    <option value="30">30</option>                    <option value="45">45</option>                    <option value="60">60</option>                    <option value="180">120</option>                    <option value="540">540</option>                </select> Minutes                <div class="inline-help" inlinehelp="<ul><li>The number of minutes your browser can sit</li><li>idle before you are logged out of the WebUI</li></ul>">					<span class="icon-default"></span>					<span class="icon-hover"></span>				</div>            </div>        </div><table class="table edit-user-options"><tbody><tr><td><label for="editEnabled">User Enabled</label></td><td><input type="checkbox" id="editEnabled" class="pretty-dynamic" checked/><div class="inline-help" inlinehelp="<ul><li>Chooses if the user is active &frasl; inactive.</li><li>If they are not enabled, they cannot login.</li></ul>"><span class="icon-default"></span><span class="icon-hover"></span></div></td><td><label for="editReports">View Reports</label></td><td><input type="checkbox" id="editReports" class="pretty-dynamic"/><div class="inline-help" inlinehelp="Enables &frasl; disables viewing of Reports pages."><span class="icon-default"></span><span class="icon-hover"></span></div></td></tr><tr><td><label for="editMaintenance">InSync Maintenance</label></td><td><input type="checkbox" id="editMaintenance" class="pretty-dynamic"/><div class="inline-help" inlinehelp="Enables &frasl; disables access to the Maintenance page."><span class="icon-default"></span> <span class="icon-hover"></span></div></td><td><label for="editCorridor">Management Group Maintenance</label></td><td><input type="checkbox" id="editCorridor" class="pretty-dynamic"/><div class="inline-help" inlinehelp="Enables &frasl; disables access to Management Group Maintenance."><span class="icon-default"></span><span class="icon-hover"></span></div></td></tr><tr><td><label for="editCorridor">Configure Intersection</label></td><td><input type="checkbox" id="editConfiguration" class="pretty-dynamic"/><div class="inline-help" inlinehelp="<ul><li>Enables &frasl; disables configuration via</li><li>the &quot;Configure Detectors&quot; web utility.</li></ul>"><span class="icon-default"></span><span class="icon-hover"></span></div></td><td><label for="editManual">Manual Traffic Calls</label></td><td><input type="checkbox" id="editManual" class="pretty-dynamic"/><div class="inline-help" inlinehelp="<ul><li>Enables &frasl; disables placing of manual</li><li>calls, queues, and pedestrian button pushes.</li></ul>"><span class="icon-default"></span><span class="icon-hover"></span></div></td></tr><tr><td><label for="editAdmin">Administer Users</label></td><td><input type="checkbox" id="editAdmin" class="pretty-dynamic"/><div class="inline-help" inlinehelp="<ul><li>Enables &frasl; disables administration of users.</li><li>With this flag, users can add, edit, or delete any user!</li></ul>"><span class="icon-default"></span><span class="icon-hover"></span></div></td><td>&nbsp;</td><td>&nbsp;</td></tr></tbody></table><input type="hidden" value="" id="editName"/></div>');

        $("#editUserDialog").dialog({
            position: {
                my: "center", 
                at: "center", 
                of: window
            }
        });

        $('.pretty-dynamic').prettyCheckable();

        $('.chosen-dynamic').chosen({
            disable_search: true,
            inherit_select_classes: true
        });

        var username = data["name"];

        $("#editUserDialog").dialog({title: "Edit User " + username});
        var dataHolder = data["permissions"].split(",")

        var permissions = {};

        for (var i = 0; i < dataHolder.length; i++) {
            permissions[dataHolder[i]] = true;
        }

        
        $("#editEnabled").prop('checked', false).next().removeClass("checked");
        $("#editReports").prop('checked', false).next().removeClass("checked");
        $("#editRecord").prop('checked', false).next().removeClass("checked");
        $("#editMaintenance").prop('checked', false).next().removeClass("checked");
        $("#editCorridor").prop('checked', false).next().removeClass("checked");
        $("#editConfiguration").prop('checked', false).next().removeClass("checked");
        $("#editManual").prop('checked', false).next().removeClass("checked");
        $("#editAdmin").prop('checked', false).next().removeClass("checked");

        $("#editName").val(username);
        $("#editEnabled").prop('checked', permissions['enabled']);
        $("#editReports").prop('checked', permissions['reports']);
        
        if (permissions['enabled'])
            $("#editEnabled").next().addClass("checked");
        if (permissions['reports'])
            $("#editReports").next().addClass("checked");
        if (permissions['cameras'] == 'undefined' || permissions['cameras'] == undefined)
            permissions['cameras'] = false;
        if (permissions['cameracontrols'] == 'undefined' || permissions['cameracontrols'] == undefined)
            permissions['cameracontrols'] = false;
        
        if(currentUser == username)
            $("#editEnabled").attr('disabled','disabled').parent().addClass('disabled');
        else
            $("#editEnabled").removeProp('disabled').parent().removeClass('disabled');
        
        var timeout = -1;
        
        if(data["settings"].timeout !== undefined)
            timeout = data["settings"].timeout;
        
        if(timeout == -1)
            $("#editSession").val("30").trigger("chosen:updated");
        else
            $("#editSession").val(timeout).trigger("chosen:updated");
        
        if (!permissions['cameras'] && !permissions['cameracontrols'])
            $("#editCameras").val("d").trigger("chosen:updated");
        else if (permissions['cameras'] && !permissions['cameracontrols'])
            $("#editCameras").val("v").trigger("chosen:updated");
        else if (permissions['cameras'] && permissions['cameracontrols'])
            $("#editCameras").val("vc").trigger("chosen:updated");

        $("#editMaintenance").prop('checked', permissions['maintenance']);
        $("#editCorridor").prop('checked', permissions['corridor']);
        $("#editConfiguration").prop('checked', permissions['configure']);
        $("#editManual").prop('checked', permissions['manual']);
        $("#editAdmin").prop('checked', permissions['adminUsers']);
        
        if (permissions['maintenance']) $("#editMaintenance").next().addClass("checked");
        if (permissions['corridor']) $("#editCorridor").next().addClass("checked");
        if (permissions['configure']) $("#editConfiguration").next().addClass("checked");
        if (permissions['manual']) $("#editManual").next().addClass("checked");
        if (permissions['adminUsers']) $("#editAdmin").next().addClass("checked");
        if (permissions['web'] == 'undefined' || permissions['web'] == undefined)
            permissions['web'] = false;
        if (permissions['api'] == 'undefined' || permissions['api'] == undefined)
            permissions['api'] = false;
        
        if (!permissions['web'] && permissions['api'])
            $("#editWebAPI").val("api").trigger("chosen:updated");
        else if (permissions['web'] && permissions['api'])
            $("#editWebAPI").val("webapi").trigger("chosen:updated");
        
        reloadHelp();
    });
}

function deleteUser(userName) {
    $("#deleteUserDialog").dialog("open");
    $("#deleteUsername").text(userName);
}
