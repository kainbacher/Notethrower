


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


    /* !artistInfo semdMessage */
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
    
   
});


































