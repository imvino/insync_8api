;(function(window, document, $) {

    var events = 'click.fndtn',
    $menuClose = $('.menu-close');

    if ($menuClose.length > 0) {
        $('.menu-close').on(events, function(e) {
            e.preventDefault();
            $('#sidebar').animate({'margin-left': '-=' + $('#sidebar').width()}, function() { $menuClose.parent('ul').hide(); $('#sidebar').hide(); });
        });
    }

}(this, document, jQuery));
