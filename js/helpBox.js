
// Implements a popup help system for the WebUI
function reloadHelp() {
    $(document).find('div[inlinehelp]').each(function(index) {
        window.hidingTimer = null;
        
        $(this).click(function() {
            var helpText = $(this).attr('inlinehelp');
            showBox($(this), helpText);
        });
        
        $(this).mouseenter($(this).attr('inlinehelp'), function(event) {
            if (event.data != 'undefined') {
                $('#helpBox').remove();
                showBox($(this), event.data);                
                clearTimeout(window.hidingTimer);
            }
        });
        
        $(this).mouseleave(function() {
            window.hidingTimer = setTimeout(hideBox, 500);
        });
        
        function showBox(sourceElement, text) {
            $('body').append('<div class="helpBox" id="helpBox">' + text + '</div>')
                
            var top = sourceElement.offset().top - (sourceElement.height() / 2);
            var left = sourceElement.offset().left + sourceElement.width() + 15;

            var cssParams = {
                top: top,
                left: left
            };

            $('#helpBox').css(cssParams);
            $('#helpBox').fadeIn(150);

            $('#helpBox').mouseenter(function() {
                clearTimeout(hidingTimer);
            });

            $('#helpBox').mouseleave(function() {
                hidingTimer = setTimeout(hideBox, 500);
            });
        }
        
        function hideBox() {
            $('#helpBox').fadeOut(150, function() {
               $(this).remove(); 
            });
        }
    });
}

$(document).ready(function() {
    reloadHelp();
});