var backgroundNum = 5;

function encode_utf8(s) 
{
  return unescape(encodeURIComponent(s));
}

function updateTips(t) 
{
    $(".login-validation")
        .html(t)
        .addClass('ui-state-highlight');
    setTimeout(function() {
        $(".login-validation").removeClass('ui-state-highlight');
    }, 2500);
}
    
function initLogin(bgNum) 
{
    backgroundNum = bgNum;
    
    var theWindow        = $(window),
        $bg              = $("#login-bg"),
        aspectRatio      = $bg.width() / $bg.height();

    function resizeBg() {
        if ((theWindow.width() / theWindow.height()) < aspectRatio) {
            $bg
                .toggleClass('bgwidth', false)
                .toggleClass('bgheight', true);
        } else {
            $bg
                .toggleClass('bgwidth', true)
                .toggleClass('bgheight', false);
        }           
    }                     
    theWindow.resize(resizeBg).trigger("resize");
    
    var username = $('#username'),
        password = $('#password'),
        allFields = $([]).add(username).add(password);
    
    $(".login-form").submit(function(e)
    {
        e.preventDefault();
        
        var bValid = true;
        
        if (username.val().length == 0) {
            updateTips('Please enter a username.');
            $("#username").focus();
            bValid = false;
            return;
        }

        if (password.val().length == 0) {
            updateTips('Please enter a password.');
            $("#password").focus();
            bValid = false;
            return;
        }

        if (bValid) 
        {            
            $.ajax({
                type: 'POST',
                data: {username: $.base64.encode(encode_utf8(username.val())), password: $.base64.encode(encode_utf8(password.val()))},
                url: '/auth/login.php?submit=Login',
                complete: function(xhr, status) 
                {							
                    if (xhr.status === 302 || xhr.status == 200) 
                    {
                        var redirect_target = $('#redirect_target').val();

                        if (redirect_target.length == 0)
                            window.location.href = '/index.php';
                        else
                            window.location.href = decodeURIComponent(redirect_target);
                    }
                    else if (xhr.status === 503) 
                    {
                        updateTips('<span class="ui-state-highlight">- Unable to contact login server. -<br /><br />Please contact Rhythm Engineering support if this problem persists.</span>');
                    }
                    else {
                        $('#login-dialog').dialog('widget').effect('shake', {times: 3}, 400);
                        updateTips('Invalid username or password.');
                    }
                }
            });
        }
    });    
    
    if(bgNum == 5)
    {        
        for(var i=0;i<6;i++)
        {
            animateDiv($("#glowbug_" + i), $("#bug_container_1"));
        }
    }
}

function makeNewPosition(container)
{
    var h = container.height();
    var w = container.width();
    
    var nh = Math.floor(Math.random() * h);
    var nw = Math.floor(Math.random() * w);
    
    return [nh,nw];
}

function animateDiv(element, container){
    var newq = makeNewPosition(container);
    var oldq = element.offset();
    var speed = calcSpeed([oldq.top, oldq.left], newq);
    
    element.animate({ top: newq[0], left: newq[1] }, { duration: speed, queue: true, complete: function(){
      animateDiv(element, container);        
    }});
};

function calcSpeed(prev, next) 
{    
    var x = Math.abs(prev[1] - next[1]);
    var y = Math.abs(prev[0] - next[0]);
    
    var greatest = x > y ? x : y;    
    var speedModifier = 0.1 + (Math.random() * 0.1);
    var speed = Math.ceil(greatest/speedModifier);

    return speed;
}
