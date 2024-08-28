var adaptiveDisabled;
var readOnly = false;
var sessionID;
var countIncrement = 0;
var stateTimerID = null;

function initManualScript(activePhases, activePeds, sID, readStyle, paramAdaptiveDisabled, emergencyPhases) {    
	if (!readStyle) {
		$(window).on('beforeunload', function() {
            $("#layouts").val(lastLayout).trigger("chosen:updated");
			closeoutJSON(activePhases);
            return "Please close the manual controls before navigating away from this page.";
		});
	}
    adaptiveDisabled = (paramAdaptiveDisabled == '1' ? true : false);
	if (adaptiveDisabled) {
			$("#disableAdaptive").prop("checked", true);

	} else {
			$("#disableAdaptive").prop("checked", false);
	}

	sessionID = sID;
	readOnly = readStyle;
    activePedsGlobal = activePeds;
	makeJson(activePhases);
	makeERJson(activePhases);
	for (var i=0; i < activePhases.length; i++) {
		$("#queue" + activePhases[i]).button().change(function() {
			$(this).button("option", {
				label: this.checked ? 'On' : 'Off'
			});
			
		});
		
        
		$("#call" + activePhases[i]).button().change(function() {
			$(this).button("option", {
				label: this.checked ? 'On' : 'Off'

			});


		});
        

		// For InSync:Hawkeye.
		$("#emergency" + activePhases[i]).button().change(function() {
			$(this).button("option", {
				label: this.checked ? 'On' : 'Off'
			});


		});
	}
	
	// For Pedestrians
	for (var i=0; i < activePeds.length; i++)
	{
		$("#ped" + activePeds[i]).button().change(function() {
			$(this).button("option", {
				label: this.checked ? 'On' : 'Off'
			});
		});
	}	
	
	// For InSync:Hawkeye.
	// If an active phase is in emergency mode, show emergency mode as 'On' in manual calls window when the UI is launched
	
	for (var i=0; i < activePhases.length; i++)
	{

		for (var x=0; x < emergencyPhases.length; x++)
		{
			if (activePhases[i] == emergencyPhases[x])
			{
				$("#emergency" + activePhases[i]).prop("checked", true);
                //To match the button's state
                $("#emergency" + activePhases[i]).next("label").css('outline-color', '#00FF00');
                $("#emergency" + activePhases[i]).next("label").css('outline-style', 'solid');
                $("#emergency" + activePhases[i]).next("label").css('outline-width', 'thin');
				
				var num = activePhases[i];
				jsonERObj = getERJSON();
				jsonERObj['E'+num] = 1;
				setERJSON(jsonERObj);
				PostERCalls(jsonERObj);
				
				break;				
			}
			else
			{
				var num = activePhases[i];
				jsonERObj = getERJSON();
				jsonERObj['E'+num] = 0;
				setERJSON(jsonERObj);
				PostERCalls(jsonERObj);
				

			}
				
		}
	}
	
	//for (var i=0; i < activePeds.length; i++)
	//	$("#ped" + activePeds[i]).button();
	
	    	$("#disableAdaptive").button().change(function(e) {	
		if (adaptiveDisabled) {
			$(this).button("option", { 
				label: 'Disable Adaptive'
			});
			adaptiveDisabled = false;
            
            jsonObj = getJSON();
            jsonObj['Disable_Adaptive'] = 0;
            setJSON(jsonObj);
            PostCalls(jsonObj);

		} else {
			$(this).prop('checked', false);
			$(this).button("refresh");
			
			$("#dialog-confirm").dialog("open");
		}
        

	});
	
	$("#dialog-confirm").dialog({
		autoOpen: false,
        resizable: false,
        modal: true,
        closeText: '×',
		buttons: {
			"Yes": function() {
				$(this).dialog("close");
				
				$("#disableAdaptive").button("option", { 
					label: 'Enable Adaptive'
				});
				
                jsonObj = getJSON();
				adaptiveDisabled = true;
				jsonObj['Disable_Adaptive'] = 1;

                setJSON(jsonObj);
                PostCalls(jsonObj);

                
				$("#disableAdaptive").prop('checked', true);
				$("#disableAdaptive").button("refresh");
			},
			Cancel: function() {
				$(this).dialog("close");
			}
		}

	});


    sendState();

}



//Makes the Json file dymanically 

var globalactivePhases;
var activePedsGlobal;

function makeJson(activePhases) {
        
        var jsonObj = {};
        
        jsonObj['lock_holder'] = "";
        
        if (adaptiveDisabled)
            jsonObj['Disable_Adaptive'] = 1;
        else
        	jsonObj['Disable_Adaptive'] = 0;
    
        
        for (var i=0; i < activePhases.length; i++)
        {
            var num = activePhases[i];
            jsonObj['Q'+num] = 0;
            jsonObj['C'+num] = 0;
          //  jsonObj['E'+num] = 0;
         }
         
            masterJSON = jsonObj;
            globalactivePhases = activePhases;
}

function makeERJson(activePhases) {
		var jsonERObj = {};
		
		for (var i=0; i < activePhases.length; i++)
        {
            var num = activePhases[i];
            jsonERObj['E'+num] = 0;
        }
		
		masterERJSON = jsonERObj;
        
		
}       
         
//When closing the manualcalls interface.     
function closeoutJSON(activePhases)
{
    //Get the current obj
    var jsonObj = getJSON();
    
    //Leave adaptive and lock holder alone. 
    
    //Set all the calls to zero
    for (var i=0; i < activePhases.length; i++)
        {
            var num = activePhases[i];
            jsonObj['Q'+num] = 0;
            jsonObj['C'+num] = 0;
         //   jsonObj['E'+num] = 0;
         }
        
        //needs to stay here to be safe?                    
         for (var i=0; i < activePedsGlobal.length; i++)
         {
            num = activePedsGlobal[i];
            jsonObj['PedButton'+num] = 0;
         
         }
            masterJSON = jsonObj;
            //Make the post request to clear out the calls.
            PostCalls(jsonObj);
}

     var masterJSON;
	 
	 var masterERJSON;
	
	function setERJSON(jsonERObj)
    {
        masterERJSON = jsonERObj;
    }
	
	
     function setJSON(jsonObj)
    {
        masterJSON = jsonObj;
    }

	function getERJSON()
    {
        return masterERJSON;
    }

     function getJSON()
    {
        return masterJSON;
    }
    
    
function PostCalls(jsonObj)
{
         xmlDoc = "<manualCalls>";
         for (key in jsonObj)
         {
            if(key == "Disable_Adaptive")
                xmlDoc += "<row key='" + key + "' value='" + jsonObj[key] + "' />";
            else if(jsonObj[key] == 1 && key.substring(0,9) != 'E')
                xmlDoc += "<row key='" + key + "'/>";
                
            
         }
         xmlDoc += "</manualCalls>";
         xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><request><command type=\"setmanualcalls\"/>" + xmlDoc + "</request>";

        var sendData = {};	
        sendData['action'] = 'manualcalls';	
        sendData['message'] = xml;
        sendData['increment'] = countIncrement++;
        
        //For timing the code.
        //var n = new Date().getTime();
        //console.log("The UTC time is: ", n);
        
        $.post("/helpers/insyncInterface.php", sendData, 
            function(data) {
                displayState(data);
            }, 'json');
        
         displayState(jsonObj);
}

function PostERCalls(jsonObj)
{
    
    var sendData = {};	
    sendData['action'] = 'ercalls';	
    sendData['message'] = jsonObj;
    sendData['increment'] = countIncrement++;
    $.post("/helpers/insyncInterface.php", sendData, 
        function(data) {
            displayState(data);
        }, 'json');
     
        displayState(jsonObj); 

}

const sendState = function() {
	if (readOnly)
		return false;
	
    
	var jsonObj = getJSON();
	var jsonERObj = getERJSON();
	
	jsonObj['lock_holder'] = sessionID;
	
    $('#manualControlContainer').on('change', function(e) { 
        var eid = e.target.id.substring(0, e.target.id.length - 1);
        var num = e.target.id.slice(-1);
        jsonObj = getJSON();
        // force presence
        if (eid == "queue") {
            if (e.target.checked)
                jsonObj['Q'+num] = 1;
            else
                jsonObj['Q'+num] = 0;
            
            setJSON(jsonObj);
            PostCalls(jsonObj);
            }
        
        // manual call
        if(eid == "call") {
            if (e.target.checked){
                jsonObj['C'+num] = 1;
            }
            else
                jsonObj['C'+num] = 0;
                
            setJSON(jsonObj);
            PostCalls(jsonObj);
            }
        
        // Emergency mode for hawkeye
        if (eid == "emergency" ) {
            if (e.target.checked)
                jsonERObj['E'+num] = 1;
            else
                jsonERObj['E'+num] = 0;
                
				setERJSON(jsonERObj);
                PostERCalls(jsonERObj);
            }	
              
            // pedestrian
        if (eid == "ped") {
            //Does it this way to maintain 1 shot behavior and not turn off manual calls. 
            //jsonObj['PedButton'+num] = 1;
            //PostCalls(jsonObj);
            //jsonObj['PedButton'+num] = 0;
            //PostCalls(jsonObj);
			if (e.target.checked)
				jsonObj['PedButton'+num] = 1;
			else
				jsonObj['PedButton'+num] = 0;
			
			setJSON(jsonObj);
			PostCalls(jsonObj);			
            }   
        
    }); 
    
}


//Not used anymore.
function stateTimer() {	

		
	sendState();
}

function displayState(data) {
    
    
    if (data == null)
        return;
    
	$.each(data, 
	function(name, value) {
    
		if (name == "lock_holder" && !readOnly) {			
			// someone done stoled our write access!!1!!
			if (value != sessionID) {				
				// hide all user inputs
				$("#manualControlContainer").children().find("input").each(function() { 
					$(this).attr("disabled", "true");
				});
					
				$("#disableAdaptive").hide(250);
				$("#disableAdaptive").next('label').hide(250);
					
				readOnly = true;
				
				$("#closeWindow").hide();
				
				$("#dialog-manual-controls").append("<p><span style='color:red;'>Someone else has opened the manual controls. You have been moved to read-only mode.</span></p>");
				$(window).unbind('beforeunload');
				window.onbeforeunload = null;
			}
		}
		
		if (name.substring(0, 1) == "Q") {
			var phaseNumber = name.slice(-1);
			
			if (value == "1") {
                $("#queue" + phaseNumber).next("label").css('outline-color', '#00FF00');
                $("#queue" + phaseNumber).next("label").css('outline-style', 'solid');
                $("#queue" + phaseNumber).next("label").css('outline-width', 'thin');
            } else
                $("#queue" + phaseNumber).next("label").css('outline-style', 'none');
		}
		
		if (name.substring(0, 1) == "C") {
			var phaseNumber = name.slice(-1);

			if (value == "1") {
                $("#call" + phaseNumber).next("label").css('outline-color', '#00FF00');
                $("#call" + phaseNumber).next("label").css('outline-style', 'solid');
                $("#call" + phaseNumber).next("label").css('outline-width', 'thin');
            } else
                $("#call" + phaseNumber).next("label").css('outline-style', 'none');
		}
		
		// For Pedestrians
			if (name.substring(0, 9) == "PedButton") {
				var pedNumber = name.slice(9);

				if (value == "1") {
					$("#ped" + pedNumber).next("label").css('outline-color', '#00FF00');
					$("#ped" + pedNumber).next("label").css('outline-style', 'solid');
					$("#ped" + pedNumber).next("label").css('outline-width', 'thin');
				} else
					$("#ped" + pedNumber).next("label").css('outline-style', 'none');
		}				
		
		if (name.substring(0, 1) == "E") {
			var phaseNumber = name.slice(-1);

			if (value == "1") {
                $("#emergency" + phaseNumber).next("label").css('outline-color', '#00FF00');
                $("#emergency" + phaseNumber).next("label").css('outline-style', 'solid');
                $("#emergency" + phaseNumber).next("label").css('outline-width', 'thin');
            } else
                $("#emergency" + phaseNumber).next("label").css('outline-style', 'none');
		}		
	});
}

function updateUI(data) {
	var jsonObj = data;

	$.each(jsonObj,
	function(name, value) {
		if (name == "Disable_Adaptive") {
			if (value == "1") {
				$("#disableAdaptive").button("option", { 
					label: 'Enable Adaptive'
				});
				
				$("#disableAdaptive").prop("checked", true);
				$("#disableAdaptive").button("refresh");
				
				adaptiveDisabled = true;
			} else {
				$("#disableAdaptive").button("option", { 
					label: 'Disable Adaptive'
				});
				
				$("#disableAdaptive").prop("checked", false);
				$("#disableAdaptive").button("refresh");
				
				adaptiveDisabled = false;
			}
		}
	});
}