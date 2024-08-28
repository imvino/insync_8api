var readonly = false;

function enableCancel() {
        $("#cancel").prop("disabled", readonly);
        $("#save").prop("disabled", readonly);
}

function initScripts() {
	$(document).ready(function() { 
                $("#cancel").prop("disabled", true);
                $("#save").prop("disabled", true);
		
		$.get("/helpers/gpsHelper.php?action=get", function(data) {
			if(data.indexOf("Unconfigured") == 0)
			{
					$("#error").text("System GPS Coordinate not set!  Please pick an approximate location.");

                    $("#lat").change(enableCancel);
                    $("#lon").change(enableCancel);

                    $("#lat").prop("disabled", false);
                    $("#lon").prop("disabled", false);
                    $("#viewport").mapbox({mousewheel: true}); 
					$("#viewport").mapbox("zoom",10);
					$("#viewport").mapbox("left",950);
					$("#viewport").mapbox("up",400);
			}
			else if(data.indexOf("Error") == 0)
			{
				$("#error").text(data);
                $("#viewport").hide();

			}
			else
			{
                readonly = /^ReadOnlyCoordinates: /.test(data);
                var coordinates = /^Coordinates: /.test(data);
				if (readonly || coordinates) {
					var coords = (data.split(":"))[1];
					var parts = coords.split(",");
				
					coords = {lat: parseFloat(parts[0]), lon: parseFloat(parts[1])};
	                $("#lat").prop("disabled", readonly);
	                $("#lon").prop("disabled", readonly);

		                $("#viewport").mapbox({mousewheel: true, installHandlers: !readonly}); 
		    
						$("#viewport").mapbox("zoom",10);
						$("#viewport").mapbox("left",950);
						$("#viewport").mapbox("up",400);


						$("#viewport").mapbox("setCoords", coords);
				
						$("#lat").val(coords.lat);
						$("#lon").val(coords.lon);

					if(!readonly) {
		                $("#lat").change(enableCancel);
		                $("#lon").change(enableCancel);

				
						$("#latLabel").text("");
						$("#lonLabel").text("");

					}
					else {
						$("#lat").val(coords.lat);
						$("#lon").val(coords.lon);

                        $("#error").text("Please make additional coordinate changes in In|Traffic.");

                    }
				}
			}
		});
    });
    
    $("#cancel").button().click(function() {
       location.reload(); 
    });
	
	$("#save").button().click(function() {
		if ($("#viewport").mapbox("markerVisible")) {
			var coords = $("#viewport").mapbox("markerCoords");
			
			var textLat = $("#lat").val();
			var textLon = $("#lon").val();
			
			if (textLat != coords.lat || textLong != coords.lon) {
				coords = {lat: textLat, lon: textLon};
				$("#viewport").mapbox("setCoords", coords);
			}
		} else {
			var textLat = $("#lat").val();
			var textLon = $("#lon").val();
			
			if (textLat.length == 0 || textLon.length == 0) {
				popupNotification("Invalid coordinates!", 3500);
				return false;
			}
			
			textLat = parseFloat(textLat);
			textLon = parseFloat(textLon);
			
			if (textLat < -90 || textLat > 90 || textLon < -180 || textLon > 180) {
				popupNotification("Invalid coordinates!", 3500);
				return false;
			}
			
			coords = {lat: textLat, lon: textLon};
			$("#viewport").mapbox("setCoords", coords);
		}
		
		$.get("helpers/gpsHelper.php?action=save&lat="+textLat+"&lon="+textLon, function( data )
		{
			if(data != "Success")
				popupNotification(data, 2500);
			else
                $("#cancel").prop("disabled", true);
                $("#save").prop("disabled", true);
				$("#error").text("");
				popupNotification("Coordinates saved!", 2500, "notice");


		});
	});
}
