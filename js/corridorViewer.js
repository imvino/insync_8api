var zoomTimer = null;
var colSize = 160;
var rotated = false;

function initHovers()
{    
    if(getCookie("rotated") == "true")
    {
        rotated = true;
        
        $("#corridorContainer>div").each(function()
        {
            $(this).css("clear","both");
            $(this).css("width","100%");
            $(this).find("ul>li").css("float","left");
            $(this).css("display","block");
        });
    }
            
    
    $(window).on("show-navigation", function() {
        $(".imgOverlay").attr("enablerefresh", "false");
        $(".imgOverlay").hide();
    });
    
    $("#corridorContainer img").mouseover(function()
    {        
        if($(this).attr("camname") === "gap")
            return;
        
        clearTimeout(zoomTimer);
        
        // hack to work with stupid IE7
        var obj = $(this);        
        zoomTimer = setTimeout(function() { imageOver(obj); }, 200);
    });

    $("#corridorContainer img").mouseleave(function()
    {        
        clearTimeout(zoomTimer);
        zoomTimer = null;
    });
    
    $("#fullscreen").button().click(
		function()
		{
            var currentLocation = window.location.href;

            if(currentLocation.indexOf("?") === -1)
                window.open(currentLocation + "?fullscreen=true","fs","fullscreen=yes,scrollbars=yes");
            else
                window.open(currentLocation + "&fullscreen=true","fs","fullscreen=yes,scrollbars=yes");
            
            if(!paused)
            {
                toggleRefresh();
                $('#pauseButton').addClass('play-btn').removeClass('pause-btn');
            }
        });
        
    $("#rotateButton").button().click(function() 
    {
        if(!rotated)
        {
            rotated = true;
            
            $("#corridorContainer>div").each(function()
            {
                $(this).css("clear","both");
                $(this).css("width","100%");
                $(this).find("ul>li").css("float","left");
                $(this).css("display","block");
            });
            
            setCookie("rotated", "true", 999999);
        }
        else
        {
            rotated = false;
            
            $("#corridorContainer>div").each(function()
            {
                $(this).css("clear","none");
                $(this).css("width",colSize+"px");
                $(this).find("ul>li").css("float","none");
                $(this).css("display","inline-block");
            });
            
            setCookie("rotated", "false", 999999);
        }
        
        resizeHandler();
    });

    $("#hidecontrols").button().click(function() {
        $("#corridorControls").slideUp(300);
        $("#collapsedControls").slideDown(300, function() {$(window).resize();});
    });
        
    $("#showcontrols").button().click(function() {
        $("#collapsedControls").slideUp(300);
        $("#corridorControls").slideDown(300, function() {$(window).resize();});
    });
        
    $("#refresh").change(function()
		{
                    var refresh = $(this).val();
                    changeRefreshRate(refresh);
            });
        
    var resizeHandler = function() 
    { 
        var targetWidth = $("#corridorContainer").width() - 5;
        var targetHeight = $(window).height() - $("#corridorContainer>div:first").offset().top - 5;
        
        var rows = 0;
        var cols = 0;
        
        $("#corridorContainer").children().each(function() 
        { 
            cols++;
            
            var len = $(this).find("li").length;
            
            if(len > rows)
                rows = len;
        });

        if(!rotated)
            colSize = targetWidth / cols;
        else
            colSize = targetWidth / rows;
        
        if(!rotated)
        {
            if (Math.round(colSize * 0.75) * rows > targetHeight) {
                colSize = Math.floor(targetHeight / rows / 0.75);
            }
        }
        else
        {
            if (Math.round(colSize * 0.75) * cols > targetHeight) {
                colSize = Math.floor(targetHeight / cols / 0.75);
            }
        }
        
        if(colSize > 320)
            colSize = 320;

        resizeCols();
    };

    $(window).resize(function() {setTimeout(resizeHandler, 0);});    
    $(window).resize();
}

function resizeCols()
{   
    $("#corridorContainer").find(".corridor-intersection").each(function()
    {        
        if(!rotated)
            $(this).css({width:Math.round(colSize)});
        
        $(this).find(".intersection-header").width(Math.round(colSize));
        $(this).find(".intersection-name").width(Math.round(colSize-15));
        $(this).find(".intersection-ip").width(Math.round(colSize-15));        

        $(this).find(".camera,.gap").width(Math.round(colSize));
        $(this).find(".camera,.gap").height(Math.floor(colSize*0.75));

        $(this).find(".camera img").width(Math.round(colSize));
        $(this).find(".camera img").height(Math.floor(colSize*0.75));
        $(this).find(".gap img").width(Math.round(colSize));
        $(this).find(".gap img").height(Math.floor(colSize*0.75));
    });

    $("#corridorContainer").find(".corridor-col").each(function()
    {     
        if(!rotated)
        {
            $(this).css({width:Math.round(colSize)});
            $(this).attr("width", Math.round(colSize));
        }
        
        $(this).find(".column-header").width(Math.round(colSize));
        $(this).find(".column-name").width(Math.round(colSize-15));   

        $(this).find(".camera,.gap").width(Math.round(colSize));
        $(this).find(".camera,.gap").height(Math.floor(colSize*0.75));

        $(this).find(".camera img").width(Math.round(colSize));
        $(this).find(".camera img").height(Math.floor(colSize*0.75));
        $(this).find(".gap img").width(Math.round(colSize));
        $(this).find(".gap img").height(Math.floor(colSize*0.75));
    });
}

var currentHoverImage = null;

function imageOver(obj)
{        
    var overlay = $(".imgOverlay");
    overlay.width(320);
    overlay.height(240);
    overlay.attr("src", $(obj).attr("src"));
    overlay.attr("baseUrl", $(obj).attr("baseUrl"));
    overlay.attr("enablerefresh", "true");
    overlay.css({"z-index": 999});
    overlay.show();

    currentHoverImage = obj;

    overlay.position({my: "center", at: "center", of: $(obj), within: "#corridorContainer", collision: "fit"});

    closeoverlay = function() {
        if (mouseDelayTimer !== null) {
            clearTimeout(mouseDelayTimer);
            mouseDelayTimer = null;
        }
        // Don't update the overlay when not visible
        overlay.attr("enablerefresh", "false");    
        overlay.hide();
        clearTimeout(zoomTimer);
        overlay.off("mouseleave");
        overlay.off("mousemove");
    };
    
    overlay.off("mouseleave");
    overlay.mouseleave(closeoverlay);
    var mouseDelayTimer = null;

    var mouseMoveHandler = function(e) {
        var mouseX = e.pageX;
        var mouseY = e.pageY;

        $("#corridorContainer img").each(function(index, image) {
            var $image = $(image);
           var imageOffset = $image.offset(); 
           var imageWidth = $image.width();
           var imageHeight = $image.height();

           if (mouseX >= imageOffset.left && mouseX < imageOffset.left + imageWidth
                   && mouseY >= imageOffset.top && mouseY < imageOffset.top + imageHeight) {
               if ($image.get(0) !== $(currentHoverImage).get(0)) {
                   currentHoverImage = $image.get(0);
                   overlay.attr("src", $image.data("last_loaded_image"));
                   setTimeout(function() {imageOver($image);}, 0);
               }
           }
        });
    };

    overlay.off("mousemove");
    overlay.mousemove(function(e) {
        if (mouseDelayTimer !== null) {
            clearTimeout(mouseDelayTimer);
            mouseDelayTimer = null;
        }
        mouseDelayTimer = setTimeout(function(){
            mouseMoveHandler(e);
        }, 10);
    });
    
    overlay.off("click");
    overlay.click(closeoverlay);

    // The image overlay can never be paused when showing
    overlay.data("paused", false);
    overlay.data("cannot_pause", true);
    overlay.data("refresh_rate", 200);
    enableRefresh(overlay);
}

function addIntersection(ip, data, name)
{
    if (ip === undefined)
        ip = "127.0.0.1";

    var newHTML = "";

    newHTML += "<div class='corridor-intersection'>";
    newHTML += "<ul class='camera-container'>";

    if (data !== undefined && typeof data.list !== "undefined" && data.list !== "")
    {
        for (var i = 0; i < data.list.length; i++)
        {
            if(data.list[i] === "gap")
                newHTML += "<li class='gap'></li>";
            else
                newHTML += "<li class='camera'><img srcip='" + ip + "' camname='" + data.list[i] + "' baseURL='/helpers/corridorViewerHelper.php?action=getremoteimage&ip=" + ip + "&cam=" + data.list[i] + "' enablerefresh='true' /></li>";
        }
    }

    newHTML += "</ul>";
    newHTML += "</div>";

    $("#corridorContainer").append(newHTML);
}

function addColumn(data, name)
{
    var newHTML = "";

    newHTML += "<div class='corridor-col'>";
    newHTML += "<ul class='camera-container'>";
    
    if (data !== undefined && typeof data.list !== "undefined" && data.list !== "")
    {
        for (var i = 0; i < data.list.length; i++)
        {
            if(data.list[i].name === "gap")
                newHTML += "<li class='gap'></li>";
            else
                newHTML += "<li class='camera'><img srcip='" + data.list[i].ip + "' camname='" + data.list[i].name + "' baseURL='/helpers/corridorViewerHelper.php?action=getremoteimage&ip=" + data.list[i].ip + "&cam=" + data.list[i].name + "' enablerefresh='true' /></li>";
        }
    }

    newHTML += "</ul>";
    newHTML += "</div>";

    $("#corridorContainer").append(newHTML);
}