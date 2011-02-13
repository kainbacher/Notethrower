<?php

include_once('../Includes/Init.php');
include_once('../Includes/Paginator.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/Artist.php');
include_once('../Includes/DB/AudioTrack.php');
include_once('../Includes/DB/News.php');

$loginErrorMsg = '';

$visitorArtistId = -1;

$userIsLoggedIn = false;
$artist = Artist::new_from_cookie();
if ($artist) {
    $visitorArtistId = $artist->id;
    $logger->info('visitor artist id: ' . $visitorArtistId);

    $userIsLoggedIn = true;
    $logger->info('user is logged in');

} else {
    $logger->info('user is NOT logged in');

    if (get_param('action') == 'login') {
        $logger->info('login request received');
        if (get_param('username') && get_param('password')) {
            $artist = Artist::fetch_for_username_password(get_param('username'), get_param('password'));
            if ($artist && $artist->status == 'active') {
                $artist->doLogin();
                $logger->info('login successful, reloading page to set cookie');
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;

            } else {
                $loginErrorMsg = 'Login failed! Please try again.';
                $logger->info('login failed');
            }

        } else {
            $loginErrorMsg = 'Please provide a username and password!';
            $logger->info('username and/or password missing');
        }
    }
}

$trackCount = AudioTrack::count_all(false, false, $visitorArtistId);
$logger->info('track count: ' . $trackCount);

$newsCount = News::count_all();

writePageDoctype();

?>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <?php writePageMetaTags(); ?>
    <title><?php writePageTitle(); ?></title>
    <link rel="stylesheet" href="../Styles/main.css" type="text/css">
    <link rel="stylesheet" href="../Styles/ajaxpagination.css" type="text/css">
    <script type="text/javascript" src="../Javascripts/jquery-1.3.2.min.js"></script>
    <script type="text/javascript" src="../Javascripts/jquery.main.js"></script>

    <!--
    <script src="../Javascripts/cufon/cufon-yui.js" type="text/javascript"></script>
    <script src="../Javascripts/cufon/Fertigo_Pro_400.font.js" type="text/javascript"></script>
    <script type="text/javascript">
        /* Cufon.replace('h3'); */
        /* Cufon.replace('.fertigo'); */
    </script>
    -->
    <script src="../Javascripts/ajaxpagination.js" type="text/javascript">

    /***********************************************
    * Ajax Pagination script- (c) Dynamic Drive DHTML code library (www.dynamicdrive.com)
    * This notice MUST stay intact for legal use
    * Visit Dynamic Drive at http://www.dynamicdrive.com/ for full source code
    ***********************************************/

    </script>
    <script language="javascript">

// default mode is mostRecent
var mode = 'mostRecent';

// the count of the tracks
var tracksCount = <?php echo $trackCount; ?>;

// count of pages
var pageCount = Math.ceil(tracksCount / 16);

// the json object for the pagination
var trackLinks = {pages: [], selectedpage: 0};

// method that adds the pages to the trackLinks json object
// based on how many pages have to be displayed
function initTrackLinks() {
	for (var i=0; i < pageCount; i++) {
		trackLinks.pages[i] = 'tracksSnippet.php?page=' + (i+1) + '&mode=' + mode;
	}
}

// inits the tracksPagination
var tracksPagingInstance;
function initTracksPagination() {
	initTrackLinks();
	tracksPagingInstance = new ajaxpageclass.createBook(trackLinks, "tracksContent", ["pagination"]);
	tracksPagingInstance.selectpage(0);
	tracksPagingInstance.paginatepersist = false;
}

function toggleMode() {
	if (mode == 'mostRecent') {
		mode = 'mostDownloaded';
		$('#trackGridHeadline').html('Most downloaded tracks:');
		$('#trackGridHeadlineControls').html('<a href="#" onclick="toggleMode()">Show most recent</a>');
		initTracksPagination();
	} else {
		mode = 'mostRecent';
		$('#trackGridHeadline').html('Most recent tracks:');
		$('#trackGridHeadlineControls').html('<a href="#" onclick="toggleMode()">Show most downloaded</a>');
		initTracksPagination();
	}
}

function featuredTrackClicked(tid) {
    var showHideMode = '';
    $('#trackGrid').hide(showHideMode);
    $('#trackGridHeadlineContainer').hide(showHideMode);

    // load track details html
    $.ajax({
        type: 'POST',
        url: 'getTrackDetails.php',
        data: 'tid=' + tid,
        dataType: 'html',
        cache: false,
        timeout: 10000, // 10 seconds
        beforeSend: function(xmlHttpRequest) {
            $('#chosenTrackDetails').html('<table><tr><td valign="middle"><!-- FIXME - put loading anim gif here --></td><td valign="middle">' +
                    'Loading ...</td></tr></table>');
        },
        error: function(xmlHttpRequest, textStatus, errorThrown) {
            $('#chosenTrackDetails').html('<b>ERROR:<br>' +
                    textStatus + '<br>' +
                    errorThrown + '</b>');
        },
        success: function(html) {
            $('#chosenTrackDetails').html(html);
        }
    });

    $('#chosenTrackDetailsContainer').show(showHideMode);
    $('#chosenTrackHeadlineContainer').show(showHideMode);
}

function getFlashContent(name) {
    if (navigator.appName.indexOf("Microsoft") != -1) {
        return window[name];
    } else {
        return document[name];
    }
}

function reloadDataInWidget(aid, tid) {
    getFlashContent("NTWidget").reloadData(aid, tid);
}

function showLogin() {
    //document.getElementById("loginFormDiv").style.display="block";
    $('#loginFormDiv').toggle('fast');
}

/* !documentready fuction */
/* ---------------------------------------------------------------------- */

/*
$(document).ready(function(){

	alert('hover');

    $('.topMenuItem a').hover(function(){
    	
    	$(this).next().show();
    },fuction(){
    	$(this).next().hide();
    });
   
});
*/


    </script>
	</head>
	<body>
		<div id="bodyWrapperStart">
            <? include ("pageHeader.php"); ?>
            <? include ("mainMenu.php"); ?>

    <div id="contentTopStart">

        <div class="contentTopStartOuter">
        <div class="contentTopStartInner">
 
            <div class="contentTopEntry">
    
    		     <div id="startpageLeftColumn">
    		     	<div id="startpageContent">
    		     	     
    		     	     
    		     	     <div id="startpageText">
    		     	        <div class="fertigo">
    		     	            <br/>
    		     	            <br/>
    		     	            <br/>
    		     	            <h1>Music Collaboration</h1>
<!--
    		     	            <p>
    		     	            Create your Free profile, Upload your original music and
                                track stems. Start working with our community of Musicians, Writers and Producers.
							    </p>
-->
                                <h2>Download, Record, Upload</h2>
                                <br/>
							</div>
    		     	        <div class="fertigo">
    		     	            <h1>Music Licensing</h1>
<!--
    		     	            <p>
    		     	            Sell your original work and remixes from other Notethrower artists for licensing
    		     	             in Film/TV, Games, and many more. <b>Keep 90% of the profit!</b>
    		     	            </p>
-->
                                <h2>License and sell your music</h2>
                                <br/>
							</div>
    		     	        <div class="fertigo">
    		     	            <h1>Share Your Frequency</h1>
<!--
    		     	            <p>
    		     	            Spread your new music collaboration and licensing widget anywhere you want.  Just copy and paste the embed code to your favorite social network, blog or website to post it.  Login to update your music anytime, and  your widget automatically updates on every page it lives across the web!
    		     	            </p>
-->
    		     	            <h2>get your free widget and place it anywhere</h2>
    		     	            <br/>
    		     	            <br/>
    		     	        </div>
    		     	     </div>
    		     	     
    		     	     <div id="startpageButtonsWrapper">
    		     	         <div id="startpageButtonStart">
    		     	             <a href="index.php" class="buttonbig">&raquo; go to the music</a>
    		     	         </div>
    		     	         <div id="startpageButtonTour">
    		     	             <a href="/" class="buttonbig">&raquo; learn more</a>
    		     	         </div>    		     	         
    		     	         <div class="clear"></div>
    		     	     </div>
    		     	     
    		     	</div>
    		     </div> <!-- trackGridWrapper -->
                 
          	     <div id="startpageRightColumn">
          	          <div id="startpageImage">
    	               
    	               <img src="../Images/sorrow__02.png" alt="sorrow__02" width="400" height="297" />
    	                <br/>
                        <h3>Make Music and make money<br/>with our featured artists</h3>
      		            <br/>
      		            <a href="http://www.notethrower.com/NT/Site/artistInfo.php?aid=89" class="button">Collaborate with "The Sorrow"</a>
      		        
      		          </div>
      		     </div> <!-- startpageRightColumn -->
                 
      	         <div class="clear"></div>

            </div> <!-- contentTopEntry -->


            <div class="contentTopEntry">
    
    		     <div id="startpageLeftColumn">
    		     	<div id="startpageContent">
    		     	    <div class="startpageContentStepText">
    		     	        <h1>Music Collaboration</h1>
    		     	        <br/>
    		     	        <p>Upload a high quality Mp3 of an original piece of music you created and or own the copyright to, then upload the full production stems and entire sessions and let your music grow! (watch video for an example)
	
						Download other Notethrower artist tracks from their widget, and upload your remix to their profile on Notethrower.<br><br/>Private or Public tracks - Allow anyone access to your music, or keep your work in progress private with your bandmates. Release and collaborate with the world when you are ready. 
					
						  </p>
    		     	     </div>
    		     	     
    		     	     <div id="startpagePaginatorWrapper">
    		     	         <ul>
    		     	            <li id="startpagePaginatorBack"><a href="/"></a></li>
    		     	            <li id="startpagePaginator1Act"><a href="/"></a></li>
    		     	            <li id="startpagePaginator2"><a href="/"></a></li>
    		     	            <li id="startpagePaginator3"><a href="/"></a></li>
    		     	         </ul>
    		     	     </div>
    		     	     
    		     	</div>
    		     </div> <!-- trackGridWrapper -->
                 
          	     <div id="startpageRightColumn">
          	          <div id="startpageImage">
          	          
<object width="450" height="350"><param name="allowfullscreen" value="true" />
<param name="allowscriptaccess" value="always" /><param name="movie" 
value="http://vimeo.com/moogaloop.swf?clip_id=9684737&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=&amp;fullscreen=1" />
<embed src="http://vimeo.com/moogaloop.swf?clip_id=9684737&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=&amp;fullscreen=1" type="application/x-shockwave-flash" allowfullscreen="true" 
allowscriptaccess="always" width="400" height="300"></embed></object><p></p>

    	               
<!-- <img src= "../Images/startpage.png" alt="startpage" width="400" height="400"/> -->  
    	               

      		          </div>
      		     </div> <!-- startpageRightColumn -->
                 
      	         <div class="clear"></div>

            </div> <!-- contentTopEntry -->


            <div class="contentTopEntry">
    
    		     <div id="startpageLeftColumn">
    		     	<div id="startpageContent">
    		     	    <div class="startpageContentStepText">
    		     	        <h1>Music Licensing</h1>
    		     	        <br/>
    		     	        <p>You set the licensing price for your work.  We find business that need your music for commercial use. You keep 90% of the profit.  When a remix of your work sells, you split the profit 50/50 with the remixer. Each new version of your work is another way to get paid.  All you need is a free <a href="http://www.paypal.com" target="_blank">paypal</a> account to get paid.</p>
    		     	     </div>
    		     	     
    		     	     <div id="startpagePaginatorWrapper" class="startpagePaginatorWrapper">
    		     	         <ul>
    		     	            <li id="startpagePaginatorBack"><a href="/"></a></li>
    		     	            <li id="startpagePaginator1"><a href="/"></a></li>
    		     	            <li id="startpagePaginator2Act"><a href="/"></a></li>
    		     	            <li id="startpagePaginator3"><a href="/"></a></li>
    		     	         </ul>
    		     	     </div>
    		     	     
    		     	</div>
    		     </div> <!-- trackGridWrapper -->
                 
          	     <div id="startpageRightColumn">
          	          <div id="startpageImage">
    	                <img src="../Images/startpage.png" alt="startpage" width="400" height="400"/>
      		          </div>
      		     </div> <!-- startpageRightColumn -->
                 
      	         <div class="clear"></div>

            </div> <!-- contentTopEntry -->
            
            
            <div class="contentTopEntry">
    
    		     <div id="startpageLeftColumn">
    		     	<div id="startpageContent">
    		     	    <div class="startpageContentStepText">
    		     	        <h1>Music Distribution</h1>
    		     	        <br/>
    		     	        <p>Share Your Frequency Your personal music collaboration and licensing platform can be passed along as easy as a YouTube video.  Anyone can copy and paste your “share” code into their own personal web site, blog, and social network like Twitter, Myspace and Facebook.<br><br/>Make the list!
						Keeping the music conversation going with the wider Notethrower community keeps our growing list of music licensing partners, music fans and artists up to date in real time. 
We believe that by using free and powerful social tools available online, we can connect and collaborate in ways that were not available just a short time ago. We want Notethrower to be a strong connected community of music artists, writers and producers.  For these reasons, we encourage you to create a free “twitter” account (if you don’t already have one) and connect with us on twitter.  We will post updates and your latest musical additions to our <a href="http://twitter.com/notethrower/music" target="_blank">@notethrower/music</a> list
                            </p>
    		     	     </div>
    		     	     
    		     	     <div id="startpagePaginatorWrapper">
    		     	         <ul>
    		     	            <li id="startpagePaginatorBack"><a href="/"></a></li>
    		     	            <li id="startpagePaginator1"><a href="/"></a></li>
    		     	            <li id="startpagePaginator2"><a href="/"></a></li>
    		     	            <li id="startpagePaginator3Act"><a href="/"></a></li>
    		     	         </ul>
    		     	     </div>
    		     	     
    		     	</div>
    		     </div> <!-- trackGridWrapper -->
                 
          	     <div id="startpageRightColumn">
          	          <div id="startpageImage">
    	                <img src="../Images/startpage.png" alt="startpage" width="400" height="400"/>
      		          </div>
      		     </div> <!-- startpageRightColumn -->
                 
      	         <div class="clear"></div>

            </div> <!-- contentTopEntry -->            
            
            
            
            
            
            <div class="clear"></div>

        </div> <!-- contentTopStartInner  -->
        </div> <!-- contentTopStartOuter -->        

	</div> <!-- contentTopStart -->
	
	<div id="pageMainContentWrapper" class="startpageMainContentWrapper">
		<div id="pageMainContent">

		<div id="startColumnLeft">

            <div id="standardInfoDiv">
                <div id="container">
      
        
                    <br/>
                    <h1>What is Notethrower?</h1>
                    <br/>            
                    <h2>Music Collaboration</h2>
                    <p>
                     We provide a powerful set of online tools for musicicians, writers and producers. Our service is <b>FREE</b> to sign up. Members can collaborate with other artists on original music, then sell and license their new work.
                    </p>
                    
                    <br/>
                    <br/>
                    
                    <h2>Music Licensing</h2>
                    
                    <p>Notethrower finds music publishing and placement deals in Film and TV, Games, and many other areas for our members.  Notethrower gives musicians a unique legal environment to <b>co-write</b> with others, watch their work evolve and spread across the web, then collect profits from the sale of each track. <b>Artists set the price for licensing and keep 90%</b> profit from sale of their music.
                    </p>
                    
                    <br/>

                </div>
      
            </div> <!-- standrardInfoDiv -->      
      
        </div> <!-- startColumnLeft -->



		<div id="startColumnMiddle">

            <div id="standardInfoDiv">
                <div id="container">
      
        
                    <br>
                    <h1>Testimonials</h1>
                    <br>
                    <p>"a revolution in music licensing"</p>
                    <br/>
                    <p>"Notethrower simplifies the business of music licensing and placement 
                    deals for musicians."</p> 
                    <br/>
                    <p>"Notethrower gives any musician the tools to crowdsource their next song, and then helps find licensing and placement deals for their music!"</p>
                    <br/>
                    <p>"Musicianship and Co-writing for the digital age"</p>
                    <br>
                    <h2>Share your frequency!</h2>
                    <br/>
                    <p>
                    Your personal music collaboration and licensing platform can be shared and passed along as easy as a YouTube video.
                          Login to update your music anytime, and your widget automatically updates on every page it lives across the web!
                    </p>
                    <br/>
                    <br/>

                </div>
      
            </div> <!-- standrardInfoDiv -->      
      
        </div> <!-- startColumnMiddle -->




        <div id="startColumnRight">
        
            <div id="icons">
                <div id="iconsDivStart"></div>
                <div id="iconsDiv">
                	<div id="container">
            
                        <br/>
                        
                        <script src="http://widgets.twimg.com/j/2/widget.js"></script>
                        <script>
                        new TWTR.Widget({
                          version: 2,
                          type: 'profile',
                          rpp: 4,
                          interval: 6000,
                          width: 290,
                          height: 400,
                          theme: {
                            shell: {
                              background: '#333333',
                              color: '#ffffff'
                            },
                            tweets: {
                              background: '#42403d',
                              color: '#ffffff',
                              links: '#d9a515'
                            }
                          },
                          features: {
                            scrollbar: false,
                            loop: true,
                            live: false,
                            hashtags: true,
                            timestamp: true,
                            avatars: false,
                            behavior: 'default'
                          }
                        }).render().setUser('Notethrower').start();
                        </script>
                        
                        <br/>
                        
                        <a class="a2a_dd" href="http://www.addtoany.com/share_save?linkname=Notethrower&amp;linkurl=http%3A%2F%2Fwww.notethrower.com"><img src="http://static.addtoany.com/buttons/share_save_171_16.png" width="171" height="16" border="0" alt="Share/Bookmark"/></a><script type="text/javascript">a2a_linkname="Notethrower";a2a_linkurl="http://www.notethrower.com";a2a_onclick=1;a2a_show_title=1;a2a_hide_embeds=0;a2a_num_services=16;a2a_color_main="D7E5ED";a2a_color_border="AECADB";a2a_color_link_text="333333";a2a_color_link_text_hover="333333";a2a_prioritize=["twitter","facebook","myspace","linkedin","tumblr","blogger_post","posterous","wordpress","digg","reddit","typepad_post","squidoo","netvibes_share","friendfeed","livejournal","read_it_later","shoutwire","stumbleupon","delicious","yahoo_buzz","newsvine","box.net","google_reader","google_gmail","technorati_favorites","google_bookmarks","orkut","mister-wong","slashdot","hotmail","tinyurl","windows_live_favorites","aol_mail","yahoo_bookmarks","aim","ask.com_mystuff","bebo","bit.ly","mixx","spurl","tr.im","yahoo_messenger","readwriteweb","ping","multiply","identi.ca"];</script><script type="text/javascript" src="http://static.addtoany.com/menu/page.js"></script>
                        
                        <br/>
                        <br/>
    	               </div>
                    </div>
                <div id="iconsDivEnd"></div>
            </div> <!-- icons -->
		</div> <!-- startColumnRight -->
		
      	<div style="clear:both"></div>
    

	</div> <!-- pageMainContent -->
	</div> <!-- pageMainContentWrapper -->
	
	
	<? include ("footer.php"); ?>
	

    <script type="text/javascript">

var uservoiceOptions = {
    /* required */
    key: 'notethrower',
    host: 'notethrower.uservoice.com',
    forum: '34211',
    showTab: true,

    /* optional */
    alignment: 'left',
    background_color: '#888888',
    text_color: 'white',
    hover_color: '#ff9900',
    lang: 'en'
};

function _loadUserVoice() {
    var s = document.createElement('script');

    s.setAttribute('type', 'text/javascript');
    s.setAttribute('src', ("https:" == document.location.protocol ? "https://" : "http://") + "cdn.uservoice.com/javascripts/widgets/tab.js");

    document.getElementsByTagName('head')[0].appendChild(s);
}

_loadSuper = window.onload;
window.onload = (typeof window.onload != 'function') ? _loadUserVoice : function() { _loadSuper(); _loadUserVoice(); };

    </script>

		</div> <!-- bodyWrapperStart -->
<!-- <script src='http://cdn.wibiya.com/Loaders/Loader_28362.js' type='text/javascript'></script> -->


<div class="prealoader">
    <img src="../Images/paginator/paginator_01_hover.png" alt="paginator_01_hover" width="70" height="70"/>
    <img src="../Images/paginator/paginator_02_hover.png" alt="paginator_02_hover" width="70" height="70"/>
    <img src="../Images/paginator/paginator_03_hover.png" alt="paginator_03_hover" width="70" height="70"/>
    <img src="../Images/paginator/paginator_back_hover.png" alt="paginator_back_hover" width="70" height="70"/>
    <img src="../Images/button_start_hover.png" alt="button_start_hover" width="230" height="55"/>
    <img src="../Images/button_tour_hover.png" alt="button_tour_hover" width="230" height="55"/>
    <img src="../Images/login_button.png" alt="login_button" width="80" height="18"/>
    <img src="../Images/main_menu_background_act.png" alt="main_menu_background_act" width="1" height="36"/>
</div>


	</body>
</html>
