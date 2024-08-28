var drawingImages = {};
var imagesLoaded = false;

// drawing globals
var playbackCanvas;
var drawingContext;
var G_vmlCanvasManager;

function initCanvas()
{
	var canvasElement = document.getElementById('playbackCanvas');
	playbackCanvas = $("#playbackCanvas");

	if (G_vmlCanvasManager !== undefined)
		G_vmlCanvasManager.initElement(canvasElement);
	
	drawingContext = canvasElement.getContext("2d");
	
	drawingContext.canvas.width = playbackCanvas.width();
	drawingContext.canvas.height = playbackCanvas.height();
	
	var sources = {
		car: '/img/vehicle-playback/car.png',
	
		EBL: '/img/history/EastBoundLeftTurn.png',
		EBT: '/img/history/EastBoundThrough.png',
		WBL: '/img/history/WestBoundLeftTurn.png',
		WBT: '/img/history/WestBoundThrough.png',
		NBL: '/img/history/NorthBoundLeftTurn.png',
		NBT: '/img/history/NorthBoundThrough.png',
		SBL: '/img/history/SouthBoundLeftTurn.png',
		SBT: '/img/history/SouthBoundThrough.png'
	};
	
	loadImages(sources, function()
		{
			imagesLoaded = true;
		});
}

function loadImages(sources, callback)
{
	var loadedImages = 0;
	var numImages = 0;
	
	// get num of sources
	for (var src in sources)
		numImages++;

	for (var src in sources) 
	{
		drawingImages[src] = new Image();
		drawingImages[src].onload = function() 
		{
			if (++loadedImages >= numImages)
				callback(drawingImages);
		};
		drawingImages[src].src = sources[src];
	}
}

function drawCanvas(timestamp)
{	
	while(!imagesLoaded)
	{
		setTimeout(drawCanvas, 500, timestamp);
		return;
	}
    
    if(phaseAssociation == undefined)
        return;
	
	var hw = drawingContext.canvas.width / 2;
	var hy = drawingContext.canvas.height / 2;
	
	drawingContext.fillStyle = "#EFEBE2";
	drawingContext.fillRect(0, 0, drawingContext.canvas.width, drawingContext.canvas.height);
    drawingContext.fillStyle = "#FFFFFF";
	drawingContext.fillRect(hw-90, hy-90, 180, 180);
	
	drawingContext.strokeStyle = "#BBBBBB";
	
	var roadLength = 200;
	var roadOffset = 90;
	
	drawingContext.font = '18px arial';
	drawingContext.fillStyle = "black";
	
	if(phaseAssociation.EBL != undefined)
	{	
        drawingContext.fillStyle = "#FFFFFF";
		drawingContext.fillRect(hw-roadLength+1, hy-30, hw-roadOffset, 60);
		
		drawingContext.beginPath();
		drawingContext.drawLine(hw-roadOffset, hy-30, hw-roadLength, hy-30);
		drawingContext.drawLine(hw-roadOffset, hy+30, hw-roadLength, hy+30);		
		drawingContext.dashedLine(hw-roadOffset, hy, hw-roadLength, hy, [5, 5]);	
		drawingContext.stroke();
		
		drawingContext.drawImage(drawingImages.EBL, hw-roadOffset-50, hy-19);
		
		drawingContext.textAlign = 'left';
        drawingContext.fillStyle = "#000000";
		var count = drawCount(timestamp, hw-85, hy+5, phaseAssociation.EBL);
		
		if(count != 0)
			drawingContext.drawImage(drawingImages.car, hw-185, hy-15);
	}
	else
    {
        drawingContext.beginPath();
		drawingContext.drawLine(hw-90, hy-30, hw-90, hy+30);
        drawingContext.stroke();
    }
	
	if(phaseAssociation.EBT != undefined)
	{
        drawingContext.fillStyle = "#FFFFFF";
		drawingContext.fillRect(hw-roadLength, hy+30, hw-roadOffset+1, 60);
		drawingContext.fillRect(hw+roadOffset-1, hy+30, hw+roadLength+1, 60);
		
		drawingContext.beginPath();
		drawingContext.drawLine(hw-roadOffset, hy+30, hw-roadLength, hy+30);
		drawingContext.drawLine(hw-roadOffset, hy+90, hw-roadLength, hy+90);		
		drawingContext.dashedLine(hw-roadOffset, hy+60, hw-roadLength, hy+60, [5, 5]);
		
		drawingContext.drawLine(hw+roadOffset, hy+30, hw+roadLength, hy+30);
		drawingContext.drawLine(hw+roadOffset, hy+90, hw+roadLength, hy+90);		
		drawingContext.dashedLine(hw+roadOffset, hy+60, hw+roadLength, hy+60, [5, 5]);
		drawingContext.stroke();

		drawingContext.drawImage(drawingImages.EBT, hw-roadOffset-50, hy+35);
		
		drawingContext.textAlign = 'left';
        drawingContext.fillStyle = "#000000";
		var count = drawCount(timestamp, hw-85, hy+65, phaseAssociation.EBT);
		
		if(count != 0)
			drawingContext.drawImage(drawingImages.car, hw-185, hy+45);
	}
	else
	{
        drawingContext.beginPath();
		drawingContext.drawLine(hw-90, hy+30, hw-90, hy+90);
		drawingContext.drawLine(hw+90, hy+30, hw+90, hy+90);
        drawingContext.stroke();
	}
	
	if(phaseAssociation.WBL != undefined)
	{
        drawingContext.fillStyle = "#FFFFFF";
		drawingContext.fillRect(hw+roadOffset-1, hy-30, hw+roadLength, 60);
		
		drawingContext.beginPath();
		drawingContext.drawLine(hw+roadOffset, hy-30, hw+roadLength, hy-30);
		drawingContext.drawLine(hw+roadOffset, hy+30, hw+roadLength, hy+30);		
		drawingContext.dashedLine(hw+roadOffset, hy, hw+roadLength, hy, [5, 5]);
		drawingContext.stroke();

		drawingContext.drawImage(drawingImages.WBL, hw+roadOffset, hy-28);
		
		drawingContext.textAlign = 'right';
        drawingContext.fillStyle = "#000000";
		var count = drawCount(timestamp, hw+85, hy+5, phaseAssociation.WBL);
		
		if(count != 0)
		{
			drawingContext.save();
			drawingContext.translate(hw+185, hy+15);
			drawingContext.rotate(3.14159265);
			drawingContext.drawImage(drawingImages.car, 0, 0);
			drawingContext.restore();
		}
	}
	else
    {
        drawingContext.beginPath();
		drawingContext.drawLine(hw+90, hy-30, hw+90, hy+30);
        drawingContext.stroke();
    }
	
	if(phaseAssociation.WBT != undefined)
	{
        drawingContext.fillStyle = "#FFFFFF";
		drawingContext.fillRect(hw+roadOffset, hy-90, hw+roadLength+1, 60);
		drawingContext.fillRect(hw-roadLength, hy-90, hw-roadOffset+1, 60);
		
		drawingContext.beginPath();
		drawingContext.drawLine(hw+roadOffset, hy-30, hw+roadLength, hy-30);
		drawingContext.drawLine(hw+roadOffset, hy-90, hw+roadLength, hy-90);
		drawingContext.dashedLine(hw+roadOffset, hy-60, hw+roadLength, hy-60, [5, 5]);
		
		drawingContext.drawLine(hw-roadOffset, hy-30, hw-roadLength, hy-30);
		drawingContext.drawLine(hw-roadOffset, hy-90, hw-roadLength, hy-90);
		drawingContext.dashedLine(hw-roadOffset, hy-60, hw-roadLength, hy-60, [5, 5]);
		drawingContext.stroke();
		
		drawingContext.drawImage(drawingImages.WBT, hw+roadOffset, hy-85);
		
		drawingContext.textAlign = 'right';
        drawingContext.fillStyle = "#000000";
		var count = drawCount(timestamp, hw+85, hy-55, phaseAssociation.WBT);
		
		if(count != 0)
		{
			drawingContext.save();
			drawingContext.translate(hw+185, hy-45);
			drawingContext.rotate(3.14159265);
			drawingContext.drawImage(drawingImages.car, 0, 0);
			drawingContext.restore();
		}
	}
	else
	{
        drawingContext.beginPath();
		drawingContext.drawLine(hw+90, hy-30, hw+90, hy-90);
		drawingContext.drawLine(hw-90, hy-30, hw-90, hy-90);
        drawingContext.stroke();
	}
	
	if(phaseAssociation.NBL != undefined)
	{	
        drawingContext.fillStyle = "#FFFFFF";
		drawingContext.fillRect(hw-30, hy+roadOffset-1, 60, hy+roadLength+1);
		
		drawingContext.beginPath();
		drawingContext.drawLine(hw-30, hy+roadOffset, hw-30, hy+roadLength);
		drawingContext.drawLine(hw+30, hy+roadOffset, hw+30, hy+roadLength);
		drawingContext.dashedLine(hw, hy+roadOffset, hw, hy+roadLength, [5, 5]);
		drawingContext.stroke();

		drawingContext.drawImage(drawingImages.NBL, hw-27, hy+90);
		
		drawingContext.textAlign = 'center';
        drawingContext.fillStyle = "#000000";
		var count = drawCount(timestamp, hw, hy+85, phaseAssociation.NBL);
		
		if(count != 0)
		{
			drawingContext.save();
			drawingContext.translate(hw-15, hy+185);
			drawingContext.rotate(4.7123889);
			drawingContext.drawImage(drawingImages.car, 0, 0);
			drawingContext.restore();
		}
	}
	else
    {
        drawingContext.beginPath();
		drawingContext.drawLine(hw-30, hy+90, hw+30, hy+90);
        drawingContext.stroke();
    }
	
	if(phaseAssociation.NBT != undefined)
	{
        drawingContext.fillStyle = "#FFFFFF";
		drawingContext.fillRect(hw+30, hy+roadOffset-1, 60, hy+roadLength);
		drawingContext.fillRect(hw+30, hy-roadLength-roadOffset, 60, roadLength);
		
		drawingContext.beginPath();
		drawingContext.drawLine(hw+30, hy+roadOffset, hw+30, hy+roadLength);
		drawingContext.drawLine(hw+90, hy+roadOffset, hw+90, hy+roadLength);
		drawingContext.dashedLine(hw+60, hy+roadOffset, hw+60, hy+roadLength, [5, 5]);
		
		drawingContext.drawLine(hw+30, hy-roadOffset, hw+30, hy-roadLength);
		drawingContext.drawLine(hw+90, hy-roadOffset, hw+90, hy-roadLength);
		drawingContext.dashedLine(hw+60, hy-roadOffset, hw+60, hy-roadLength, [5, 5]);
		drawingContext.stroke();
		
		drawingContext.drawImage(drawingImages.NBT, hw+36, hy+90);
		
		drawingContext.textAlign = 'center';
        drawingContext.fillStyle = "#000000";
		var count = drawCount(timestamp, hw+60, hy+85, phaseAssociation.NBT);
		
		if(count != 0)
		{
			drawingContext.save();
			drawingContext.translate(hw+45, hy+185);
			drawingContext.rotate(4.7123889);
			drawingContext.drawImage(drawingImages.car, 0, 0);
			drawingContext.restore();
		}
	}
	else
	{
        drawingContext.beginPath();
		drawingContext.drawLine(hw+30, hy+90, hw+90, hy+90);
		drawingContext.drawLine(hw+30, hy-90, hw+90, hy-90);
        drawingContext.stroke();
	}
	
	if(phaseAssociation.SBL != undefined)
	{		
        drawingContext.fillStyle = "#FFFFFF";
		drawingContext.fillRect(hw-30, hy-roadLength, 60, hy-roadOffset+1);
		
		drawingContext.beginPath();
		drawingContext.drawLine(hw-30, hy-roadOffset, hw-30, hy-roadLength);
		drawingContext.drawLine(hw+30, hy-roadOffset, hw+30, hy-roadLength);
		drawingContext.dashedLine(hw, hy-roadOffset, hw, hy-roadLength, [5, 5]);
		drawingContext.stroke();

		drawingContext.drawImage(drawingImages.SBL, hw-26, hy-140);
		
		drawingContext.textAlign = 'center';
        drawingContext.fillStyle = "#000000";
		var count = drawCount(timestamp, hw, hy-73, phaseAssociation.SBL);
		
		if(count != 0)
		{
			drawingContext.save();
			drawingContext.translate(hw+15, hy-185);
			drawingContext.rotate(1.570796325);
			drawingContext.drawImage(drawingImages.car, 0, 0);
			drawingContext.restore();
		}
	}
	else
    {
        drawingContext.beginPath();
		drawingContext.drawLine(hw-30, hy-90, hw+30, hy-90);
        drawingContext.stroke();
    }
	
	if(phaseAssociation.SBT != undefined)
	{
        drawingContext.fillStyle = "#FFFFFF";
		drawingContext.fillRect(hw-90, hy-roadOffset-roadLength, 60, roadLength);
		drawingContext.fillRect(hw-90, hy+roadOffset, 60, roadLength);
		
		drawingContext.beginPath();
		drawingContext.drawLine(hw-30, hy-roadOffset, hw-30, hy-roadLength);
		drawingContext.drawLine(hw-90, hy-roadOffset, hw-90, hy-roadLength);
		drawingContext.dashedLine(hw-60, hy-roadOffset, hw-60, hy-roadLength, [5, 5]);
		
		drawingContext.drawLine(hw-30, hy+roadOffset, hw-30, hy+roadLength);
		drawingContext.drawLine(hw-90, hy+roadOffset, hw-90, hy+roadLength);
		drawingContext.dashedLine(hw-60, hy+roadOffset, hw-60, hy+roadLength, [5, 5]);
		drawingContext.stroke();
		
		drawingContext.drawImage(drawingImages.SBT, hw-84, hy-140);
		
		drawingContext.textAlign = 'center';
        drawingContext.fillStyle = "#000000";
		var count = drawCount(timestamp, hw - 60, hy-73, phaseAssociation.SBT);
		
		if(count != 0)
		{
			drawingContext.save();
			drawingContext.translate(hw-45, hy-185);
			drawingContext.rotate(1.570796325);
			drawingContext.drawImage(drawingImages.car, 0, 0);
			drawingContext.restore();
		}
	}
	else
	{
        drawingContext.beginPath();
		drawingContext.drawLine(hw-30, hy-90, hw-90, hy-90);
		drawingContext.drawLine(hw-30, hy+90, hw-90, hy+90);
        drawingContext.stroke();
	}
}

function drawCount(timestamp, x, y, id)
{
	id = id - 1;
	
	var returnCount = 0;
    
    if(typeof vehicleData[id] == 'undefined')
        return;
	
	for(var i=0; i < vehicleData[id].data.length; i++)
	{
		if(vehicleData[id].data[i][0] == timestamp)
		{
			drawingContext.fillText(vehicleData[id].data[i][1], x, y);
			returnCount = vehicleData[id].data[i][1];
			break;
		}
	}
	
	return returnCount;
}

var CP = window.CanvasRenderingContext2D && CanvasRenderingContext2D.prototype;
if (CP && CP.lineTo) 
{
	CP.drawLine = function(x, y, x2, y2)
	{
		this.moveTo(x, y);
		this.lineTo(x2, y2);
	}

	CP.dashedLine = function(x, y, x2, y2, dashArray) 
	{
		if (!dashArray)
			dashArray = [10, 5];
		if (dashLength == 0)
			dashLength = 0.001; // Hack for Safari
		var dashCount = dashArray.length;
		this.moveTo(x, y);
		var dx = (x2 - x), dy = (y2 - y);
		if(dx == 0)
		{
			if(dy < 0)
				slope = -90;
			else
				slope = 90;
		}
		else
			slope = dy / dx;
		var distRemaining = Math.sqrt(dx * dx + dy * dy);
		var dashIndex = 0, draw = true;
		
		while (distRemaining >= 0.1) 
		{
			var dashLength = dashArray[dashIndex++ % dashCount];
			if (dashLength > distRemaining)
				dashLength = distRemaining;
			var xStep = Math.sqrt(dashLength * dashLength / (1 + slope * slope));
			if (dx < 0)
				xStep = -xStep;
			x += xStep
			y += slope * xStep;
			this[draw ? 'lineTo' : 'moveTo'](x, y);
			distRemaining -= dashLength;
			draw = !draw;
		}
	}
}