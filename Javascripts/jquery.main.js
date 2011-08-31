


/* !Position */
/* ---------------------------------------------------------------------- */
function position(){


    var windowHeight = $(window).height();
    var windowWidth = $(window).width();

    $('#contentTopStart').css('left', windowWidth);

    $('#sendMessageWrapper').css('left', ((windowWidth-500)/2) );
    $('#sendMessageWrapper').css('top', ((windowHeight-300)/2) );

}

/* !clearInputField */
/* ---------------------------------------------------------------------- */
function ClearInput(id){
    var input = document.getElementById(id);
    input.value = '';
}




/* !popup */
/* ---------------------------------------------------------------------- */

function popup () {
    $('body').append('<div id="popupWrapper"><div id="popupBox"></div></div>')
}








/* !documentready fuction */
/* ---------------------------------------------------------------------- */


$(document).ready(function(){


    /* !position */
    position();

    $(window).resize(function(){
        position();
    });


    /* !top Menu */
    $('.topMenuItem a').hover(function(){
    	$(this).next().show();
    },function(){
    	$(this).next().hide();
    });

    $('.topMenuSub').hover(function(){
    	$(this).show();
    },function(){
    	$(this).hide();
    });


/*
if ($("...").is(":focus")) {
  ...
}
*/



    /* !startpage */
    /* ---------------------------------------------------------------------- */
    $('#startpageButtonTour a').bind('click',function(){
        $('.contentTopStartInner').animate({left:'-1450'}, 1000);
        return false;
    });

    $('#startpagePaginatorBack a').bind('click',function(){
        $('.contentTopStartInner').animate({left:'0'}, 1000);
        return false;
    });

    $('#startpagePaginator1 a').bind('click',function(){
        $('.contentTopStartInner').animate({left:'-1450'}, 1000);
        return false;
    });

    $('#startpagePaginator2 a').bind('click',function(){
        $('.contentTopStartInner').animate({left:'-2900'}, 1000);
        return false;
    });

    $('#startpagePaginator3 a').bind('click',function(){
        $('.contentTopStartInner').animate({left:'-4350'}, 1000);
        return false;
    });

    $('#startpagePaginatorWrapper a').bind('click',function(){
        return false;
    });


    /* !userInfo sendMessage */
    /* ---------------------------------------------------------------------- */
/*
    $('.sendMessageLink a').bind('click', function(){

        $('#sendMessageOverlay').css('display','block');

        var link = $(this).attr("href");
        $("#sendMessageWrapper").load(link + " div#sendMessageInner", function(){

            $('#senMessageClose').bind('click', function(){
                $('#sendMessageOverlay').css('display','none');
            });

        });


        return false;
    });
*/





    /* !toolTip */
    /* ---------------------------------------------------------------------- */



    $('.toolTip').hover(
        function(){
            $(this).next().show();
        }, function(){
            $(this).next().hide();
        }

    );

    $('.toolTipContent').hover(
        function(){
            $(this).show();
        }, function(){
            $(this).hide();
        }

    );


/*
        var tipContent = $(this).next().text();

        $('.toolTip').simpletip({
            content: tipContent,
            fixed: false
        });
*/



    /* ! galleria */
    /* ---------------------------------------------------------------------- */
    // Load the classic theme
    Galleria.loadTheme('../Javascripts/themes/classic/galleria.classic.js');

    // Initialize Galleria
    $('#galleria, .galleria').galleria({
        width:662,
        height:250,
        transition: "slide",
        transitionSpeed: 800,
        thumbCrop: true,
        thumbnails: "empty",
        showInfo: false,
        clicknext: true,
        showCounter: false,
        autoplay: true
    });


    /* !  */
    /* ---------------------------------------------------------------------- */
    $('.trackListItem').hover(function() {
        $(this).addClass('trackListItemHover');
    }, function() {
        $(this).removeClass('trackListItemHover');
    });

    /* ! tabs */
    /* ---------------------------------------------------------------------- */
    $('.tab-1').click(function() {
        $('*').find('.tabsAct').removeClass('tabsAct');
        $(this).addClass('tabsAct');
        $('*').find('.tabcontentAct').removeClass('tabcontentAct');
        $('.tabcontent-1').addClass('tabcontentAct');
        return false;
    });

    $('.tab-2').click(function() {
        $('*').find('.tabsAct').removeClass('tabsAct');
        $(this).addClass('tabsAct');
        $('*').find('.tabcontentAct').removeClass('tabcontentAct');
        $('.tabcontent-2').addClass('tabcontentAct');
        return false;
    });

    $('.tab-3').click(function() {
        $('*').find('.tabsAct').removeClass('tabsAct');
        $(this).addClass('tabsAct');
        $('*').find('.tabcontentAct').removeClass('tabcontentAct');
        $('.tabcontent-3').addClass('tabcontentAct');
        return false;
    });

    $('.tab-4').click(function() {
        $('*').find('.tabsAct').removeClass('tabsAct');
        $(this).addClass('tabsAct');
        $('*').find('.tabcontentAct').removeClass('tabcontentAct');
        $('.tabcontent-4').addClass('tabcontentAct');
        return false;
    });

    $('.tab-5').click(function() {
        $('*').find('.tabsAct').removeClass('tabsAct');
        $(this).addClass('tabsAct');
        $('*').find('.tabcontentAct').removeClass('tabcontentAct');
        $('.tabcontent-5').addClass('tabcontentAct');
        return false;
    });



    /* !popup opener */
    /* ---------------------------------------------------------------------- */
    
    $('.popup').bind('click', function(){
        popup();
        return false;
    });



    /* ! fancybox */
	var recipientid = null;
    /* ---------------------------------------------------------------------- */
    $('body').append('<div style="display:none"><div id="sendmsg"><h1>Message</h1><a onClick="$.fancybox.close()">close</a></div></div>');
    $("a[rel=send_msg]").fancybox({
        'onStart' : function(el){
            recipientid = el.attr('href');
            alert(recipientid);
        },
        'hideOnOverlayClick' : 'false'
    });

});
