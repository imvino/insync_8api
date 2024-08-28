<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

$title = ": Status";
$breadCrumb = "<h1>Management Group <small>Status</small></h1>";
$menuCategory = "corridor";

$head = <<<HEAD_WRAP
<!-- HEADER -->
<link rel="stylesheet" type="text/css" href="/css/corridorStatus.css"/>
<!-- END HEADER -->
HEAD_WRAP
;

include("includes/header.php");

////////////////////////////////////////////////////////////////////////////////
// PAGE PHP
////////////////////////////////////////////////////////////////////////////////

if(empty($permissions["reports"]))
{
	echo "<h3>Error: You do not have permission to access this page.</h3>";
    include("includes/footer.php");
    exit;
}
?>

<div class="row">
    <div class="inline-block panel">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th style="width: 250px">Intersection</th>
                    <th style="width: 150px">IP</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="status_body">
                <tr>
                    <td>Please Wait...</td>
                    <td>Please Wait...</td>
                    <td>Waiting to communicate with InSync...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
$(function() {
	updateStatus();
    window.setInterval(updateStatus, 1000);
});

var updating = false;



// START OF CODE USED FROM MUSTACHE.JS -- PLEASE REFERENCE THE FOLLOWING LICENSE FOR THIS SECTION

/*
The MIT License

Copyright (c) 2009 Chris Wanstrath (Ruby)
Copyright (c) 2010-2014 Jan Lehnardt (JavaScript)

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

var entityMap = {
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': '&quot;',
    "'": '&#39;',
    "/": '&#x2F;'
  };

function escapeHtml(string) {
    return String(string).replace(/[&<>"'\/]/g, function (s) {
          return entityMap[s];
    });
}

// END OF CODE USED FROM MUSTACHE.JS


function ShowError(msg) {
    var errorHTML = "";
    errorHTML += "<tr class='net-error'>";
    errorHTML += "<td valign='top'>Error</td>";
    errorHTML += "<td valign='top'>Error</td>";
    errorHTML += "<td valign='top'>" + escapeHtml(msg) + "</td>";
    errorHTML += "</tr>";
    $("#status_body").html(errorHTML);
}



function updateStatus() {
    if(updating)
        return;
    
    $.ajax({
        url: "/helpers/insyncInterface.php?action=getConfigurationNetworkStatus",
        beforeSend: function( xhr ) {
            updating = true;
        },
        dataType: "xml"
    })
    .done(function( data ) {
        updating = false;
<?php        
/*
The following is a typical example XML that we'd get back from the system.

If Self="true" then the Intersection is the local machine.  This "Self" Intersection node is special and has a Network node for all intersections associated with the configuration.
Example:
<ConfigurationNetworkStatus>
    <Intersection IP="192.168.31.152" Name="Test Intersection 4" Configuration="Config # 1" IsInCurrentSolution="True" IsFacilitator="true" CorridorHash="108C8B807F648B2CD38E4C3ED5C852E1"/>
    <Intersection IP="192.168.31.153" Self="true" Name="Test Intersection_2" Configuration="Config # 3" IsInCurrentSolution="True" IsFacilitator="false" CorridorHash="108C8B807F648B2CD38E4C3ED5C852E1">
        <Network IP="192.168.31.152" Status="OfflineConsecutiveFailures"/>
        <Network IP="192.168.31.153" Status="OfflineSoft"/>
    </Intersection>
</ConfigurationNetworkStatus>


If a node is not "Self", then the Network nodes underneath that Intersection are only going to be built for each Intersection that is physically unreachable.  This means that you can only infer about failures and not about
successful communication lists.  In this example, 192.168.31.153 is not Self and only reports 192.168.31.154 even though .152 and .153 are also part of the solution.
Example:

<ConfigurationNetworkStatus>
    <Intersection IP="192.168.31.152" Self="true" Name="Test Intersection 4" Configuration="Config # 1" IsInCurrentSolution="True" IsFacilitator="true" CorridorHash="59B9AE477578499A6BB17D5365A535F3">
        <Network IP="192.168.31.152" Status="OfflineSoft"/>
        <Network IP="192.168.31.153" Status="OfflineSoft"/>
        <Network IP="192.168.31.154" Status="OnlineWaiting"/>
    </Intersection>
    <Intersection IP="192.168.31.153" Name="Test Intersection_2" Configuration="Config # 3" IsInCurrentSolution="True" IsFacilitator="false" CorridorHash="59B9AE477578499A6BB17D5365A535F3">
        <Network IP="192.168.31.154" Status="OfflineConsecutiveFailures"/>
    </Intersection>
    <Intersection IP="192.168.31.154" Name="Test Intersection" Configuration="Config # 2" IsInCurrentSolution="True" IsFacilitator="false" CorridorHash=""/>
</ConfigurationNetworkStatus>

Documentation for the XML format is as follows (following a Element/Attribute convention):

Intersection/IP is the IP address for the Intersection
Intersection/Self is "true" or "false" where "true" is the Self and "false" is not the Self
Intersection/Name is the current Intersection Name reported based on what the Self intersection thinks each Intersection is named (and is based on the Corridor file at that intersection).
Intersection/Configuration is the currently active Configuration reported based on what the Self intersection thinks each Intersection should be running (and is based on the Corridor file at that intersection).
Intersection/IsInCurrentSolution is "true" or "false" based on whether the Self intersection thinks that Intersection is involved in the current Configuration (and is based on the Corridor file at that intersection).
Intersection/IsFacilitator is "true" or "false" based on whether the Self intersection thinks that Intersection is the currently active Facilitator for the current Configuration (and is based on the Corridor file at that intersection).
Intersection/CorridorHash represents what the currently reported Corridor Hash is for each intersection.  This data is reported based on what the "Self" intersection thinks each Corridor hash is and is not compiled at request time.

Network/IP is the IP address for the Intersection
Network/Status can be one of the following:
OnlineWaiting  --------------------- Intersection is marked "Online" but we have yet to determine its status.
Online ----------------------------- Intersection is marked "Online" and we have confirmed its status.
OfflineConsecutiveFailures --------- Intersection is unable to be reached and shows consecutive communication failures.
OfflineInconsistentCommunication --- Intersection is unable to be reached and shows an unacceptable failure rate rather than consistent failure.
OfflineSoft ------------------------ Intersection is ensuring the corridor is in an "Offline" status until all communications are verified further.


*/
?>
        var FoundExpected = false;
        $(data).find("ConfigurationNetworkStatus").each(function(index, element) {

            if (typeof $(element).attr("Error") !== 'undefined' && $(element).attr("Error") !== false) {
                ShowError($(element).attr("Error"));
            }
            else {
                ///Found ConfigurationNetworkStatus element without defined Error attribute
                FoundExpected = true;
            }
        });

        if(FoundExpected)
        {
            var outputHTML = "";
            
            var SelfIntersection = null;
            IntersectionDictionary = {};
            IntersectionData = {};

            //Fill Intersection collection and get status for each.  Escape HTML characters as we go...
            $(data).find("Intersection").each(function(index, element) {
                var Self = ($(element).attr("Self") == "true") ? true : false;
                var IntersectionIP = escapeHtml($(element).attr("IP"));
                if(Self)
                {
                    SelfIntersection = IntersectionIP;
                }
                IntersectionDictionary[IntersectionIP] = {};
                IntersectionData[IntersectionIP] = {};
                IntersectionData[IntersectionIP].Name = escapeHtml($(element).attr("Name"));
                IntersectionData[IntersectionIP].Configuration = escapeHtml($(element).attr("Configuration"));
                IntersectionData[IntersectionIP].IsInCurrentSolution = $(element).attr("IsInCurrentSolution") == "true" ? true : false;
                IntersectionData[IntersectionIP].IsFacilitator = $(element).attr("IsFacilitator") == "true" ? true : false;


			    $(element).find("Network").each(function(netindex, netelement) {
                    IntersectionDictionary[IntersectionIP][escapeHtml($(netelement).attr("IP"))] = escapeHtml($(netelement).attr("Status"));
			    });
            });

            if(SelfIntersection != null)
            {
                //Iterate through all the Network Statuses reported by the "Self" Intersection
                $.each(IntersectionDictionary[SelfIntersection], function( key, value ) {

                    if(IntersectionData.hasOwnProperty(key)) {
                        var PerIntersectionHTML = "";
            			var IntersectionStatusClass = "net-ok";

                        //Compute status
                        switch(value) {
                            case "OnlineWaiting":
                            case "Online":
                                IntersectionStatusClass = "net-online";
                                PerIntersectionHTML += "Intersection Online";
                                break;
                            case "OfflineConsecutiveFailures":
                        /*    case "OfflineInconsistentCommunication":	*/
                                IntersectionStatusClass = "net-error";
                                if(key != SelfIntersection) {
                                    PerIntersectionHTML += IntersectionData[SelfIntersection].Name + " is unable to talk to " + IntersectionData[key].Name;
                                }
                            /*    else {
                                    PerIntersectionHTML += IntersectionData[SelfIntersection].Name + " is unable to talk to itself.  InSync is attempting to correct the problem automatically.  Please contact Rhythm Support if you see this message continually for more than several minutes.";	
                                }	*/
                                break;
                        /*    case "OfflineSoft":
                                IntersectionStatusClass = "net-offline-soft";
                                //If this intersection is in Soft Offline mode, find out whether or not it is simply verifying comms (no down intersections) or it is unable to reach a specific intersection.
                                if(IntersectionDictionary.hasOwnProperty(key)) {
                                    var foundOthers = false;
                                    var PerIntersectionAnalysisHTML = "";
                                    for (var skey in IntersectionDictionary[key]) {
                                        if (IntersectionDictionary[key].hasOwnProperty(skey)) {
                                            if(IntersectionData.hasOwnProperty(skey)) {
                                                //Compute status
                                                switch(IntersectionDictionary[key][skey]) {
                                                    case "OfflineConsecutiveFailures":
                                                    case "OfflineInconsistentCommunication":
                                                        foundOthers = true;
                                                        if(key != skey) {
                                                            PerIntersectionAnalysisHTML += "<br />" + IntersectionData[key].Name + " is unable to talk to " + IntersectionData[skey].Name;
                                                        }
                                                        else {
                                                            PerIntersectionAnalysisHTML += "<br />" + IntersectionData[key].Name + " is unable to talk to itself.  InSync is attempting to correct the problem automatically.  Please contact Rhythm Support if you see this message continually for more than several minutes.";
                                                        }
                                                        break;
                                                }
                                            }
                                        }
                                    }
                                    if(!foundOthers) {
                                        PerIntersectionAnalysisHTML = IntersectionData[key].Name + " is currently verifying reliable communications";
                                    }
                                    else {
                                        PerIntersectionAnalysisHTML = IntersectionData[SelfIntersection].Name + " is able to talk to " + IntersectionData[key].Name + " but " + IntersectionData[key].Name + " is unable to talk to Intersection(s) on the management group: " + PerIntersectionAnalysisHTML;
                                    }
                                    PerIntersectionHTML += PerIntersectionAnalysisHTML;
                                }
                                break;	*/
								default:
								    IntersectionStatusClass = "net-online";
									PerIntersectionHTML += "Intersection Online";								

                        }

                        //Compute intersection html
                        outputHTML += "<tr class='" + IntersectionStatusClass + "'>";
                        outputHTML += "<td valign='top'>" + IntersectionData[key].Name + "</td>";
                        outputHTML += "<td valign='top'>" + escapeHtml(key) + "</td>";
                        outputHTML += "<td valign='top'>" + PerIntersectionHTML + "</td>";
                        outputHTML += "</tr>";
                    }


                });
                $("#status_body").html(outputHTML);
            }
            else {
                ShowError("Unable to get current intersection data from InSync...Please wait...");
            }
        }
    })
    .fail(function( data ) {
        ShowError("Unable to get Management Group Status from InSync...Please wait...");
        updating = false;
    });
              
}
</script>
<?php
include("includes/footer.php")
?>
