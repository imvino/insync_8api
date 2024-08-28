// helper variable for IE6 canvas support
var G_vmlCanvasManager;

var imageCache = [];

function CustomMarker(latlng, imageUrl, rotation, centerOffset) {
	this.latlng_ = latlng;
	this.imageUrl_ = imageUrl;
	this.phase1_ = null;
	this.phase2_ = null;
	this.rotation_ = rotation;
	this.centerOffset_ = centerOffset;
	this.visible_ = true;
	this.div_ = null;
	this.guage_div_ = null;
        this.guage_mode = 'None';

	// Once the LatLng and text are set, add the overlay to the map.  This will
	// trigger a call to panes_changed which should in turn call draw.
	//this.setMap(map);
}

CustomMarker.prototype = new google.maps.OverlayView();

CustomMarker.prototype.onAdd = function() {
	var panes = this.getPanes();

	// Create a overlay text DIV
	var div = this.div_ = document.createElement('canvas');

        if (G_vmlCanvasManager !== undefined) {
            G_vmlCanvasManager.initElement(div);
        }

	// Create the DIV representing our CustomMarker
	div.style.border = "none";
	div.style.position = "absolute";
	div.style.paddingLeft = "0px";
	div.style.cursor = 'pointer';
        div.setAttribute('width', '32');
        div.setAttribute('height', '32');
	$(div).addClass('phase_marker');
	if (this.visible_) {
		$(div).show();
	} else {
		$(div).hide();
	}

        // Create the tunnel marker
        var guage = this.guage_div_ = document.createElement('canvas');

        if (G_vmlCanvasManager !== undefined) {
            G_vmlCanvasManager.initElement(guage);
        }
        var bounds = this.getMap().getBounds();
        var ne = this.getProjection().fromLatLngToDivPixel(bounds.getNorthEast());
        var sw = this.getProjection().fromLatLngToDivPixel(bounds.getSouthWest());
        guage.setAttribute('width', Math.abs(sw.x - ne.x));
        guage.setAttribute('height', Math.abs(sw.y - ne.y));

        guage.style.position = "absolute";
        guage.style.top = 0;
        guage.style.left = 0;

	// Then add the overlay to the DOM
	panes.overlayImage.appendChild(div);
	panes.overlayImage.appendChild(guage);
};

CustomMarker.prototype.draw = function() {
	var div = this.div_;

	if (div){
		// Position the overlay 
		var point = this.getProjection().fromLatLngToDivPixel(this.latlng_);
		if (point) {
			div.style.left = (point.x - this.centerOffset_.x) + 'px';
			div.style.top = (point.y - this.centerOffset_.y) + 'px';
		}

                this.updateImage();

                this.updateGuage();
	}
};

CustomMarker.prototype.refresh = function() {
	var map = this.getMap();
	if (map) {
		this.setMap(null);
		this.setMap(map);
	}
};

CustomMarker.prototype.setPhases = function(phases) {
	if (this.phases_ !== phases) {
		this.phases_ = phases;
		
                this.updateGuage();
	}
};

CustomMarker.prototype.setImage = function(imageUrl) {
	if (page.withoutTimeStamp(this.imageUrl_)
			!== page.withoutTimeStamp(imageUrl)) {
		this.imageUrl_ = imageUrl;
		
		var div = this.div_;
		if (div) {
			this.updateImage();
		} else {
			this.refresh();
		}
	}
};

CustomMarker.prototype.updateGuage = function() {
        var div = this.div_;

        if (div) {
		var point = this.getProjection().fromLatLngToDivPixel(this.latlng_);

                var guage = this.guage_div_;
                var bounds = this.getMap().getBounds();
                var ne = this.getProjection().fromLatLngToDivPixel(bounds.getNorthEast());
                var sw = this.getProjection().fromLatLngToDivPixel(bounds.getSouthWest());
                guage.setAttribute('width', Math.abs(sw.x - ne.x));
                guage.setAttribute('height', Math.abs(sw.y - ne.y));

                guage.style.position = "absolute";
                guage.style.top = 0;
                guage.style.left = 0;

                // Position the overlay 
                var guage_point = point;
                if (guage_point) {
                        guage_point.x -=
                                Math.cos(-1.0 * (this.rotation_ - 90) * Math.PI / 180.0) * this.centerOffset_.x;
                        guage_point.y +=
                                Math.sin(-1.0 * (this.rotation_ - 90) * Math.PI / 180.0) * this.centerOffset_.y;
                }

                var guage_mode = page.additionalDataMode;

                var guage = $(this.guage_div_);
                var ctx = this.guage_div_.getContext('2d');
                ctx.setTransform(1, 0, 0, 1, 0, 0);
                ctx.clearRect(0, 0, guage.width(), guage.height());

                if (guage_mode !== this.guage_mode) {
                    this.guage_mode = guage_mode;
                }

                var zoom = this.getMap().getZoom();
                var scale = 1;
                if (zoom >= 17) {
                        scale = 2;
                }

                var new_width = 0;
                var guage_valid = false;

                $(this.phases_).each(function(index, phase){
                        if (guage_mode === 'Tunnel') {
                                var tunnel = phase.Tunnel;
                                if (tunnel && tunnel.State === 'Running'
                                        && tunnel.Time > new_width) {
                                            new_width = tunnel.Time * scale;
                                    }
                                guage_valid = new_width > 0;
                        }
                });

                if (guage_valid) {
                        var x_unit = -1.0 * Math.cos(-1.0 * (this.rotation_ - 90) * Math.PI / 180.0);
                        var x_delta = x_unit * new_width;
                        var y_unit = Math.sin(-1.0 * (this.rotation_ - 90) * Math.PI / 180.0);
                        var y_delta = y_unit * new_width;
                        ctx.beginPath();
                        ctx.moveTo(guage_point.x - x_unit, guage_point.y - y_unit);
                        ctx.lineTo(guage_point.x + x_delta + x_unit, guage_point.y + y_delta + y_unit);
                        ctx.lineWidth = 12;
                        ctx.strokeStyle = "#000000";
                        ctx.stroke();
                        ctx.beginPath();
                        ctx.moveTo(guage_point.x, guage_point.y);
                        ctx.lineTo(guage_point.x + x_delta, guage_point.y + y_delta);
                        ctx.lineWidth = 10;
                        ctx.strokeStyle = "#33ff00";
                        ctx.stroke();
                }
        }

}

CustomMarker.prototype.updateImage = function() {
    div = this.div_;

    if (div) {
        var ctx = div.getContext('2d');

        var cacheKey = page.withoutTimeStamp(this.imageUrl_);

        ctx.setTransform(1, 0, 0, 1, 0, 0);
        div.width = 32;
        div.height = 32;
        ctx.clearRect(0, 0, div.width, div.height);

        ctx.translate(this.centerOffset_.x, this.centerOffset_.y);
        ctx.rotate(((this.rotation_ + 45) % 360) * Math.PI / 180.0);

        if (cacheKey in imageCache) {
            ctx.drawImage(imageCache[cacheKey], -10, -10);
        } else {
            var img = document.createElement("img");

            $(img).load(function() {
                ctx.drawImage(img, -10, -10);
                imageCache[cacheKey] = img;
            });
            img.src = this.imageUrl_;
        }
    }
};

CustomMarker.prototype.setPosition = function(position) {
	if (this.latlng_ !== position) {
		this.latlng_ = position;
		
		if (this.div_) {
			this.draw();
		} else {
			this.refresh();
		}
	}
};

CustomMarker.prototype.setRotation = function(rotation) {
	this.rotation_ = rotation;

	var div = this.div_;
	if (div) {
            this.updateImage();
            this.updateGuage();
	} else {
		this.refresh();
	}
};

CustomMarker.prototype.setVisibility = function(visibility) {
	this.visible_ = visibility;
	
	if (this.div_) {
		if (visibility) {
			$(this.div_).show();
                        this.updateImage();
		} else {
			$(this.div_).hide();
		}
	} else {
		this.refresh();
	}
};

CustomMarker.prototype.onRemove = function() {
	// Check if the overlay was on the map and needs to be removed.
	if (this.div_) {
		this.div_.parentNode.removeChild(this.div_);
		this.div_ = null;
	}
	if (this.guage_div_) {
		this.guage_div_.parentNode.removeChild(this.guage_div_);
		this.guage_div_ = null;
	}
};

CustomMarker.prototype.getPosition = function() {
	return this.latlng_;
};