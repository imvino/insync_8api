if(!String.prototype.startsWith){
    String.prototype.startsWith = function (str) {
        return !this.indexOf(str);
    };
}
function alphaMarker(latLng, config){
  var defaultConfig = {
    position: latLng,
    draggable:false,
    autoPan:false
  };

  if(config === undefined){
    config = defaultConfig;
  } else {
    config = $.extend(defaultConfig, config);
  }
  var marker = new google.maps.Marker(config);
  marker.destory = function(){return false;};

  // Setup Events
  var events = ["click", "infowindowbeforeclose", "mouseover", "dragstart",
    "drag", "dragend", "dblclick", "mouseout"];
  var eventFn = {
    click: function(latLng){
      return false;
    },
    dragstart: function(){
      return false;
    }
  };

  // Bind Events
  $.each(events, function(idx, event){
    if(config[event] || eventFn[event]){
      var func = config[event] || eventFn[event];
      google.maps.event.addListener(marker, event, func);
    }
  });

  return marker;
};

var getUnknownMarker = function() {
  return 'images/markers/unknown.png';
};

var getCombinedMarker = function(turn, state){   
  var url = 'images/markers/unknown.png';
  var folder = page.showReds ? 'markers_with_reds' : 'markers';
  
  if (state.length === 2) {
	  url = 'images/' + folder + '/combined/left' + state[0] + 'thru' + state[1] + '.png';
  } else if (turn) {
	  url = 'images/' + folder + '/left' + state[0] + '.png';
  } else {
	  url = 'images/' + folder + '/thru' + state[0] + '.png';
  }
  
  return url;
};

var intersectionMarker = function(latLng, config){
  if(config === undefined){ config = {};};

  var intersectionIcon = new google.maps.MarkerImage('images/markers/circle-blue.png',
    new google.maps.Size(20, 20),
    new google.maps.Point(0, 0),
    new google.maps.Point(10, 10)
  );

  var passConfig = $.extend(config, {
    icon: intersectionIcon,
    title: config.data ? config.data.name : "",
    click: function(latlng){
      if(marker.data){
        if ($("#hoverbox")) {
          $("#hoverbox").attr('running', 'false');
          $("#hoverbox").children().remove();
        }
        page.infoWindow.setContent(marker.infoHTML());
        page.infoWindow.open(page.map, marker);
        $('.camera a').hoverbox();
      }
    }
  });
  var marker = alphaMarker(latLng, passConfig);
  marker.data = passConfig.data;
  marker.infoHTML = function(){
    var html = $('<div/>').addClass('infowindow');

    var label = $('<div/>').addClass('intersection_label');
    label.append($('<a/>').attr('href', page.api_protocol + "//"+marker.data.processor_ip).attr('target','_blank').text(marker.data.name));
    html.append(label);

    html.append($('<hr/>'));

    var cameraset = $('<div/>').addClass('cameraset');
    
    // Always show cameras NSEW order
    var camerasDisplayed = 0;
    var camerasPerRow = 2;
    if (marker.data.cameras.length > 6) {
    	camerasPerRow = 4;
    } else if (marker.data.cameras.length > 4 ||
    		marker.data.cameras.length === 3) {
    	camerasPerRow = 3;
    }
    $.each(["north", "south", "east", "west"], function(index, direction){
    	$.each(marker.data.cameras, function(idx, data){
    		if (data.camera.name.toLowerCase().startsWith(direction)){
                        var camera = $('<div/>').addClass('camera');
                        var thumb = $('<div/>').addClass('thumb');
                        thumb.append($('<a/>').attr('href', page.api_protocol
                                        + "//" + page.api_server
                                        + '/api/rest/cameraimage.jpg/'
                                        + escape(data.camera.name)
                                        + "?intersections="
                                        + marker.data.processor_ip)
                                        .attr('rel', 'lightbox')
                                        .append($('<img/>').attr('alt', 'camera view').addClass('cameraFeed')
                                        .attr('src', page.api_protocol
                                            + "//" + page.api_server
                                            + '/api/rest/cameraimage.jpg/'
                                            + escape(data.camera.name)
                                            + "?height=99&width=132&intersections="
                                            + marker.data.processor_ip)
                                        .attr('width', '132px')
                                        .attr('hegith', '99px')))
                        camera.append(thumb);
                        cameraset.append(camera);

        		camerasDisplayed++;
        		if (camerasDisplayed > 0 && camerasDisplayed % camerasPerRow === 0) {
                                cameraset.append($('<br/>'));
        		}
    		}
    	});
    });

    html.append(cameraset);

    return html.html();
  };

  return marker;
};
