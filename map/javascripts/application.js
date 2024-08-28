if(typeof Object.create !== 'function'){
	Object.create = function (o){
		var F = function(){};
		F.prototype = o;
		return new F();
	};
}
function InteractiveMap() {
    this.map =  null;
    this.mapId =  null;
    this.max_signal_zoom_level =  16;
    this.rotateInterval =  1000;
    this.data =  {};
    this.zoom =  { min: 1, max:20 };
    this.showReds =  false;
    this.additionalDataMode =  'None';
    this.showIntersections =  true;
    this.lightUpdateInterval =  1000;
    this.api_protocol =  'https:';
    this.api_server =  null;
    this.signalsAnimated =  false;
    this.lastLightState =  false;
}

        InteractiveMap.prototype.show_error = function(err_str){
            $('#wait_for_login').text(err_str);
        }

	InteractiveMap.prototype.initialize =  function(mapId, config){
            $($.proxy(function(){this.delayed_initialize(mapId,config);}, this));
        }

	InteractiveMap.prototype.delayed_initialize =  function(mapId, config){
            $('#wait_for_login').dialog({ 
            	autoOpen: false,
            	closeText: '×' 
            });
            $('#wait_for_login').dialog('open');

            var processor_ip = config.data.corridor.intersections[0].intersection.processor_ip;

            // If we were loaded from a processor, do all API requests to that processor
            var host = window.location.hostname;
            if ($.type(host) !== "undefined" && $.type(host) !== "null" && host.length > 0) {
                var host_matched = false;

                $(config.data.corridor.intersections).each($.proxy(function(index, intersection){
                    if (intersection.intersection.processor_ip === host) {
                        host_matched = true;
                        return false;
                    }
                }, this));

                if (host_matched) {
                    processor_ip = host;
                    this.api_protocol = window.location.protocol;
                }
            }
            this.api_server = processor_ip;
			
            // Check protocol and force API login
            $.ajax({
              url: this.api_protocol + "//"+processor_ip+"/api/rest/webuiversion.js",
              dataType: "jsonp",
              timeout: 10000,
              statusCode: {
                      404: $.proxy(function() { this.show_error('Invalid configuration data, no response from InSync.'); }, this),
                      401: $.proxy(function() { this.show_error('Invalid user and password.'); }, this),
                      403: $.proxy(function() { this.show_error('Invalid user and password.'); }, this)
              },
              success: $.proxy(function(data, status, jqXHR) { this.maps_initialize(mapId, config); }, this),
              error: $.proxy(function(jqHXR, status, error) { 
                    // if https failed, try http instead
                    if (this.api_protocol === 'https:') {
                        $.ajax({
                          url: "http://"+processor_ip+"/api/rest/webuiversion.js",
                          dataType: "jsonp",
                          timeout: 10000,
                          statusCode: {
                                  404: $.proxy(function() { this.show_error('Invalid configuration data, no response from InSync.'); }, this),
                                  401: $.proxy(function() { this.show_error('Invalid user and password.'); }, this),
                                  403: $.proxy(function() { this.show_error('Invalid user and password.'); }, this)
                          },
                          success: $.proxy(function(data, status, jqXHR) { this.api_protocol = 'http:'; this.maps_initialize(mapId, config); }, this),
                          error: $.proxy(function(jqHXR, status, error) { 
                                this.show_error('Unable to contact InSync or invalid username and/or password.');
                          }, this)
                        });
                    } else {
                        this.show_error('Unable to contact InSync or invalid username and/or password.');
                    }
              }, this)
            });
	};

        InteractiveMap.prototype.maps_initialize = function(mapId, config){
                var mapDiv = $('#' + mapId);
                var mapPosition = mapDiv.offset(); 
                var divWidth = mapDiv.width();

                // Seem to get bad width on IE8 when media queries are enabled
                if (divWidth > $(window).width()) {
                    divWidth = $(window).width() - 120;
                }

                this.borderWidth = $(window).width() - divWidth;
                this.borderHeight = mapPosition.top + 10;
                mapDiv.height($(window).height() - mapPosition.top - 20);

                $('#wait_for_login').dialog("close");
                // Center on USA by Default
                var lat = 37.0625;
                var lng = -95.677068;
                var zoom_level = 4;

                var mapOptions = {
                                zoom: zoom_level,
                                center: new google.maps.LatLng(lat, lng),
                                mapTypeId: google.maps.MapTypeId.ROADMAP,
                                scrollwheel: true,
                                navigationControlOptions: { style: google.maps.NavigationControlStyle.SMALL },
                                mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU}
                };
                map = new google.maps.Map(document.getElementById(mapId), mapOptions);

                google.maps.event.addListener(map, "dragstart", $.proxy(function(){
                        hoverboxClose();
                }, this));

                if(typeof config === "object"){
                        if( config.lat ){ lat = config.lat; }
                        if( config.lng ){ lng = config.lng; }
                        if( config.zoom_level ){ zoom_level = config.zoom_level; }
                        if( config.data ){ this.data = config.data; }
                }

                google.maps.event.addListener(map, "idle", $.proxy(function(){
                        var center = map.getCenter();
                        $("#corridor_lat").val(center.lat());
                        $("#corridor_lng").val(center.lng());
                        $("#corridor_zoom_level").val(map.getZoom());
                }, this));

                map.setCenter(new google.maps.LatLng(lat,lng));
                map.setZoom(zoom_level);
                var min_bound = new google.maps.LatLng(config.latMin, config.lngMin);
                var max_bound = new google.maps.LatLng(config.latMax, config.lngMax);
                var bounds = new google.maps.LatLngBounds(min_bound, max_bound);
                map.fitBounds(bounds);

                this.map = map;
                this.mapId = mapId;

                this.infoWindow = new google.maps.InfoWindow({
                        content: "<div></div>"
                });

                google.maps.event.addListener(this.infoWindow, 'closeclick', $.proxy(function() {
                        if ($("#hoverbox")) {
                                hoverboxClose();
                        }
                }, this));

                if(typeof config === "object" && typeof config.callback === "function"){
                        config.callback(map);
                }
        };

	InteractiveMap.prototype.initializeDataObject = function(){
		if(typeof this.data.obj !== "object"){
			this.data.obj = {};
		}
	};
	InteractiveMap.prototype.timeStampSrc = function(src){
		var timestamp = new Date().getTime();
		var delimiter = "?";
		if(src.match(/\?/)){
			delimiter = "&";
		}
		src += delimiter + "_timestamp=" + timestamp;
		return src;
	};
	InteractiveMap.prototype.withoutTimeStamp = function(src){
		var without = src;
		return without.replace(/[&?]_timestamp=\d+$/,"");
	};
	InteractiveMap.prototype.rotateCameraFeeds = function(){
		var feeds = $(".cameraFeed");
		if(feeds.length > 0){
			$.each(feeds, $.proxy(function(idx, feed){
				var feedObj = $(feed);
				var src = this.withoutTimeStamp(feedObj.attr("src"));
                                // Only reload if previous is complete
                                if (feedObj.get(0).complete) {
                                    feedObj.attr("src",this.timeStampSrc(src));
                                }
			}, this));
		}
	};
	InteractiveMap.prototype.drawIntersections = function(data){
		// Initialize Variables
		this.initializeDataObject();
		this.data.obj.intersections = [];

		// Iterate and draw Intersections
		$.each(data, $.proxy(function(index, entry){
			var intersection = entry.intersection;
			var center = new google.maps.LatLng(intersection.lat, intersection.lng);
			var marker = intersectionMarker(center, {
				draggable: false,
				data: intersection
			});
			marker.setMap(this.map);
                        marker.processor_ip = intersection.processor_ip;
			this.data.obj.intersections.push(marker);
			this.drawTrafficSignals(marker, intersection.traffic_signals);
		}, this));
	};
	InteractiveMap.prototype.scaledSignalLocation = function(intersectionCenter, signal_lat, signal_lng){
		var newLocation = new google.maps.LatLng(signal_lat, signal_lng);
		var intersectionPoint = this.map.getProjection().fromLatLngToPoint(intersectionCenter);
		var markerPoint = this.map.getProjection().fromLatLngToPoint(newLocation);
		var zoomLevel = this.map.getZoom();
		var zoomScale = Math.pow(2, zoomLevel - 1);
		var xdelta = intersectionPoint.x * zoomScale - markerPoint.x * zoomScale;
		var ydelta = intersectionPoint.y * zoomScale - markerPoint.y * zoomScale;
		var screenDistance = Math.sqrt(xdelta * xdelta + ydelta * ydelta);

		var minPixelDistance = 12.0;
		if (screenDistance < minPixelDistance) {
			var distanceBetween = google.maps.geometry.spherical.computeDistanceBetween(intersectionCenter, newLocation);
			distanceBetween *= minPixelDistance / screenDistance;
			var bearing = google.maps.geometry.spherical.computeHeading(intersectionCenter, newLocation);
			newLocation = google.maps.geometry.spherical.computeOffset(intersectionCenter, distanceBetween, bearing);
		}

		return newLocation;
	};
	InteractiveMap.prototype.drawTrafficSignals = function(intersection, data){
		// Initialize Variables
		this.initializeDataObject();
		if(this.data.obj.trafficSignals === undefined){
			this.data.obj.trafficSignals = {};
		}

		// Iterate and draw signals
		var visibility = true;
		if(this.map.getZoom() < this.max_signal_zoom_level){
			visibility = false;
		}

		intersection.existingPhases = [];
		intersection.approaches = {};
		
		$.each(data, $.proxy(function(index, entry) {
			var trafficSignal = entry.traffic_signal;
			
			// Add to intersection phase list
			intersection.existingPhases.push(trafficSignal.phase);
					
			var parts = trafficSignal.name.toLowerCase().split(' ', 2);
			if (parts.length === 2) {
				if (parts[0] in intersection.approaches) {
					var approach = intersection.approaches[parts[0]];

					approach.lat = (approach.lat + trafficSignal.lat) / 2.0;
					approach.lng = (approach.lng + trafficSignal.lng) / 2.0;
					// Doesn't matter, since both states exist, turn is used for
					// single phase only approaches.
					approach.turn = true;
					
					// Make left turn first (if it exists)
					// assumes at most two phases on an approach
					if (trafficSignal.turn === true) {
						approach.phases.unshift(trafficSignal.phase);
						approach.state.unshift("green");
					} else {
						// Use bearing for thru phase if both exist
						approach.bearing = trafficSignal.bearing;
						approach.phases.push(trafficSignal.phase);
						approach.state.push("green");
					}
				} else {
					intersection.approaches[parts[0]] = {
							name: parts[0],
							bearing: trafficSignal.bearing,
							lat: trafficSignal.lat,
							lng: trafficSignal.lng,
							turn: trafficSignal.turn,
							phases: [trafficSignal.phase],
							state: ["green"],
							intersection_id: trafficSignal.intersection_id
					};
				}
			}
		}, this));
		
		intersection.lightInFlash = false;
		
		google.maps.event.addListener(intersection, "lightstatus", $.proxy(function(status){
			if (status.LightStatus === undefined 
                                || status.LightStatus.Intersection === undefined
                                || status.LightStatus.Intersection.State === undefined) {
                            // Ignore invalid data
                            return;
                        }
			if ( status.LightStatus.Intersection.State === 'Normal') {
				intersection.lightInFlash = false;
				var intersectionIcon = new google.maps.MarkerImage('images/markers/circle-blue.png',
						new google.maps.Size(20, 20),
						new google.maps.Point(0, 0),
						new google.maps.Point(10, 10)
				);
				intersection.setIcon(intersectionIcon);
				intersection.setAnimation(null);
				intersection.setVisible(this.showIntersections);
			} else {
				intersection.lightInFlash = true;
				var intersectionIcon = new google.maps.MarkerImage('images/markers/circle-red.png',
						new google.maps.Size(20, 20),
						new google.maps.Point(0, 0),
						new google.maps.Point(10, 10)
				);
				intersection.setIcon(intersectionIcon);
				intersection.setAnimation(google.maps.Animation.BOUNCE);
				intersection.setVisible(true);
			}
		}, this));

		$.each(['northbound', 'southbound', 'eastbound', 'westbound'], $.proxy(function(index, approachName) {
			if (approachName in intersection.approaches) {
				var approach = intersection.approaches[approachName];
				var intersectionCenter = intersection.position;
				var center = this.scaledSignalLocation(intersectionCenter, approach.lat, approach.lng);
				var offset = new google.maps.Point(16, 16);
				var overlay = new CustomMarker(center, getCombinedMarker(approach.turn, approach.state), parseInt(approach.bearing), offset);
				overlay.setVisibility(false);
				overlay.setMap(this.map);
				
				overlay.data = approach;

				var unresponsiveTimer = null;
				
				google.maps.event.addListener(intersection, "lightstatus", $.proxy(function(status){
                                        if (status.LightStatus === undefined 
                                                || status.LightStatus.Intersection === undefined
                                                || status.LightStatus.Intersection.Phase === undefined) {
                                            // Ignore invalid data
                                            return;
                                        }
					var stateMap = {"G":"green", "Y":"yellow", "R":"red"};
					if (unresponsiveTimer !== null) {
						clearTimeout(unresponsiveTimer);
						unresponsiveTimer = null;
					}
					unresponsiveTimer = setTimeout($.proxy(function() {
						overlay.setImage(getUnknownMarker());
					}, this), this.lightUpdateInterval * 2.5);
					
					var phases_for_approach = [];
					$.each(status.LightStatus.Intersection.Phase, $.proxy(function(index, phase){
						var phaseNum = parseInt(phase.Number, 10);
						
						if (overlay.data.phases[0] === phaseNum) {
							overlay.data.state[0] = stateMap[phase.State];
							phases_for_approach.push(phase);
						}
						if (overlay.data.phases.length === 2) {
							if (overlay.data.phases[1] === phaseNum) {
								overlay.data.state[1] = stateMap[phase.State];
								phases_for_approach.push(phase);
							}
						}
					}, this));
					overlay.setPhases(phases_for_approach);

					var redCount = 0;
					if (!this.showReds) {
						$.each(overlay.data.state, function(index, value) {
							if (value === "red") {
								redCount++;
							}
						});
					}
					
					overlay.setImage(getCombinedMarker(overlay.data.turn, overlay.data.state));
					if (redCount === overlay.data.state.length) {
						overlay.setVisibility(false);
					} else {
						if(this.map.getZoom() < this.max_signal_zoom_level){
							overlay.setVisibility(false);
						} else {
							overlay.setVisibility(true);
						}
					}
				}, this));
				
				google.maps.event.addListener(this.map, "zoom_changed", $.proxy(function(){
					var visibility = true;
					if(this.map.getZoom() < this.max_signal_zoom_level){
						visibility = false;
						overlay.setVisibility(visibility);
					}

					var newPosition = this.scaledSignalLocation(intersectionCenter, approach.lat, approach.lng);
					overlay.setPosition(newPosition);
				}, this));
			}
		}, this));
	};
	InteractiveMap.prototype.showHideMarkers = function(markers){
		$.each(markers, $.proxy(function(index, marker){
			if (!marker.lightInFlash) {
				marker.setVisible(this.showIntersections);
			}
		}, this));
	};
        InteractiveMap.prototype.fireNextUpdate = function() {
            if (this.signalsAnimated) {
                var timeDelta = new Date() - this.lastLightState;
                if (timeDelta >= this.lightUpdateInterval) {
                    setTimeout($.proxy(this.getLightState, this), 0);
                } else {
                    setTimeout($.proxy(this.getLightState, this), this.lightUpdateInterval - timeDelta);
                }
            }
        };
        InteractiveMap.prototype.getLightState = function() {
            var success = $.proxy(function(response){
              if (!this.signalsAnimated) {
                  return;
              }
              var resultMap = {};
              $(response.data.result).each(function(index, result) {
                  if (result.http_code === '200') {
                      resultMap[result.ip] = result;
                  }
              });
              $.each(this.data.obj.intersections, $.proxy(function(idx, intersection){
                  if (intersection.processor_ip in resultMap) {
                      google.maps.event.trigger(intersection, "lightstatus", resultMap[intersection.processor_ip]);
                  }
              }, this));
            }, this);
            this.lastLightState = new Date();
            $.ajax({
              url: this.api_protocol + "//"+this.api_server+"/api/rest/lightstate.js",
              dataType: "jsonp",
              data: {"all":"true", "intersections":"all"},
              success: success,
              complete: $.proxy(this.fireNextUpdate, this)
            });
        };
        InteractiveMap.prototype.startLightStateUpdate = function(){
            if (!this.signalsAnimated) {
                this.signalsAnimated = true;      
                this.getLightState();
            }
        };
        InteractiveMap.prototype.stopLightStateUpdate = function(){
            if (this.signalsAnimated) {
                this.signalsAnimated = false;
            }
            $.each(this.data.obj.intersections, $.proxy(function(idx, intersection){
                intersection.lightInFlash = false;
                var intersectionIcon = new google.maps.MarkerImage('images/markers/circle-blue.png',
                                new google.maps.Size(20, 20),
                                new google.maps.Point(0, 0),
                                new google.maps.Point(10, 10)
                );
                intersection.setIcon(intersectionIcon);
                intersection.setAnimation(null);
                intersection.setVisible(this.showIntersections);
            }, this));
        };
        InteractiveMap.prototype.animateTrafficSignals = function(){
            this.signalsAnimated = false;    

            if(this.map.getZoom() >= this.max_signal_zoom_level){
                this.startLightStateUpdate();
            }
        };

var page = new InteractiveMap();

page.signalsAnimated = false;    

page.lastLightState = new Date();

var createCustomMapButton = function(label) {
	// Set CSS for the control border.
	var controlUI = document.createElement('div');
	$(controlUI).addClass('mapButtonOuter');

	// Set CSS for the control interior.
	var controlText = document.createElement('div');
	$(controlText).addClass('mapButton');
	$(controlText).html(label);
	controlUI.appendChild(controlText);    
	controlUI.index = 1;
	map.controls[google.maps.ControlPosition.TOP_RIGHT].push(controlUI);
	
	return controlUI;
};

var createCustomMapDropDown = function(heading, options) {
	// Set CSS for the control border.
	var controlUI = document.createElement('div');
	$(controlUI).addClass('mapButtonOuter');

	// Set CSS for the control interior.
	var dropdown = document.createElement('div');
	$(dropdown).text(heading);
	$(dropdown).addClass('mapButton');

	var panel = document.createElement('div');
	$(panel).addClass('UIControlPanel');
	$(panel).hide();

	$(dropdown).click(function(){$(panel).toggle();});

	$(options).each(function(index, option) {
	    var optionCtrl = document.createElement('div');
	    $(optionCtrl).text(option);
	    $(optionCtrl).addClass('UIControlOption');
	    panel.appendChild(optionCtrl);
	});

	$(panel).children('.UIControlOption').click(function(){
	    $(panel).find('.UIControlOption').toggleClass('UIControlSelected', false);
	    $(this).toggleClass('UIControlSelected', true);
	    $(panel).toggle();
	    google.maps.event.trigger(controlUI, 'choice', $(this).text());
	});

	controlUI.appendChild(dropdown);    
	controlUI.appendChild(panel);    
	controlUI.index = 1;
	map.controls[google.maps.ControlPosition.TOP_RIGHT].push(controlUI);
	
	return controlUI;
};

function resizeWindow() {
	$("#mapCanvas").width($(window).width() - page.borderWidth);
	$("#mapCanvas").height($(window).height() - page.borderHeight);
};

page.onload = function(map){  
	if(typeof page.data === "object"){
		google.maps.event.addListenerOnce(map, "projection_changed", function(){
			page.drawIntersections(page.data.corridor.intersections);
		});
		google.maps.event.addListenerOnce(map, "tilesloaded", function(){
			page.animateTrafficSignals();        
		});
		google.maps.event.addListener(map, 'idle', function(){
			var zoom = map.getZoom();
			if(zoom < page.max_signal_zoom_level){
				if (page.stopLightStateUpdate) {
					page.stopLightStateUpdate();
				}
			} else {
				if (page.startLightStateUpdate) {
					page.startLightStateUpdate();
				}
			}
		});
		
		google.maps.event.addListener(map, 'zoom_changed', function(){
			var zoom = map.getZoom();
			if ( zoom > page.zoom.max){
				map.setZoom(page.zoom.max);
			} else if (zoom < page.zoom.min){
				map.setZoom(page.zoom.min);        
			}
		});

		// Build Links

		// Set CSS for the control border.
		var controlUI = createCustomMapButton('Hide Intersections');
		
		google.maps.event.addDomListener(controlUI, 'click', function(){
			if(typeof page.data.obj.intersections === "object"){
				if (page.showIntersections) {
					$(controlUI).find('.mapButton').html('Show Intersections');
				} else {
					$(controlUI).find('.mapButton').html('Hide Intersections');
				}
				page.showIntersections = !page.showIntersections;
				page.showHideMarkers(page.data.obj.intersections);          
			}
		});
		
		var showRedsUI = createCustomMapButton('Show Reds');
		
		google.maps.event.addDomListener(showRedsUI, 'click', function(){
			if (page.showReds) {
				page.showReds = false;
				$(showRedsUI).find('.mapButton').html('Show Reds');
			} else {
				page.showReds = true;
				$(showRedsUI).find('.mapButton').html('Hide Reds');
			}
		});

		var showTunnelsUI = createCustomMapButton('Show Tunnels');
		
		google.maps.event.addDomListener(showTunnelsUI, 'click', function(){
			if (page.additionalDataMode === 'Tunnel') {
                                page.additionalDataMode = 'None';
				$(showTunnelsUI).find('.mapButton').html('Show Tunnels');
			} else {
                                page.additionalDataMode = 'Tunnel';
				$(showTunnelsUI).find('.mapButton').html('Hide Tunnels');
			}
		});

		$(function() {
			resizeWindow();
			google.maps.event.trigger(page.map, "resize");
			// Do it again after Google sets all its dynamically applied styles
			resizeWindow();
		});
		$(window).resize(function() {
			resizeWindow();
			google.maps.event.trigger(page.map, "resize");
			page.infoWindow.close();
			if ($("#hoverbox")) {
				hoverboxClose();
			}
		});
		setInterval($.proxy(page.rotateCameraFeeds, page), page.rotateInterval);
	}
};
