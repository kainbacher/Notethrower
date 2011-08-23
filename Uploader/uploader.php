<?php

include_once('../Includes/Init.php');

include_once('../Includes/Snippets.php');

$projectId      = get_numeric_param('pid');
$singleFileOnly = get_numeric_param('sf');

if (!$projectId) {
    show_fatal_error_and_exit('pid param is missing!');
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
<title>Plupload - Queue widget example</title>
<style type="text/css">
	body {
		font-family:Verdana, Geneva, sans-serif;
		font-size:13px;
		color:#333;
		background:url(bg.jpg);
	}
</style>
<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/themes/base/jquery-ui.css" type="text/css" />
<link rel="stylesheet" href="js/jquery.ui.plupload/css/jquery.ui.plupload.css" type="text/css" />

<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js"></script>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/jquery-ui.min.js"></script>
<script type="text/javascript" src="http://bp.yahooapis.com/2.4.21/browserplus-min.js"></script>

<script type="text/javascript" src="js/plupload.js"></script>
<script type="text/javascript" src="js/plupload.gears.js"></script>
<script type="text/javascript" src="js/plupload.silverlight.js"></script>
<script type="text/javascript" src="js/plupload.flash.js"></script>
<script type="text/javascript" src="js/plupload.browserplus.js"></script>
<script type="text/javascript" src="js/plupload.html4.js"></script>
<script type="text/javascript" src="js/plupload.html5.js"></script>
<script type="text/javascript" src="js/jquery.ui.plupload/jquery.ui.plupload.js"></script>

</head>
<body>

<form action="upload.php">
  <div id="uploader">
    <p>You browser doesn't have Flash, Silverlight, Gears, BrowserPlus or HTML5 support.</p>
  </div>
</form>

<script type="text/javascript">
var uploadComplete = false;

// Convert divs to queue widgets when the DOM is ready
$(function() {
	$("#uploader").plupload({
		// General settings
		runtimes : 'browserplus,silverlight,gears,flash,html5,html4', // TODO - test all runtimes (html 4 makes problems in chrome)
		url : 'upload.php',
		max_file_size : '500mb',
		max_file_count: 20, // user can add no more then 20 files at a time
		chunk_size : '1mb',
		unique_names : true,
		multiple_queues : true,
		<?= $singleFileOnly ? 'multi_selection : false,' : '' ?>

		// Rename files by clicking on their titles
		rename: false,

		// Sort files
		sortable: true,

		// Specify what files to browse for
		filters : [
			{title : "Audio files and ZIP archives", extensions : "mp3,wav,aiff,flac,zip"}
		],

		// Flash settings
		flash_swf_url : 'js/plupload.flash.swf',

		// Silverlight settings
		silverlight_xap_url : 'js/plupload.silverlight.xap',

        // Post init events, bound after the internal events
		init : {
            FilesAdded: function(up, files) {
                uploadComplete = false;
<?php

if ($singleFileOnly) {

?>
                plupload.each(files, function(file) {
                    if (up.files.length > 1) {
                        up.removeFile(file);
                    }
                });
                if (up.files.length >= 1) {
                    $('#uploader_browse').fadeOut('slow');
                }
<?php

} // end of if ($singleFileOnly)

?>
            },

            FilesRemoved: function(up, files) {
                uploadComplete = false;
<?php

if ($singleFileOnly) {

?>
                if (up.files.length < 1) {
                    $('#uploader_browse').fadeIn('slow');
                }
<?php

} // end of if ($singleFileOnly)

?>
            },
            
            FileUploaded: function(up, file, info) {
                // when a single file is uploaded completely, move the file to the final location and persist the info in the database (done in an ajax action)
			    if (file.status == plupload.DONE) {
			        processSingleUploadedFile(file.target_name, file.name);
			    }
            },

			UploadProgress: function(up, file) {
				// this seems to be called twice at the end of the queued uploads,
				// so we do an extra check to avoid refreshing the opener twice.
				if (!uploadComplete) {
    				if (up.total.queued == 0) { // queue was processed completely
    				    uploadComplete = true;
    				    if (up.total.failed > 0) {
    				        alert('Sorry, but something went wrong in your upload!');

    				    } else {
    				        var projectFilesSectionDiv = window.opener.jQuery("#projectFilesSection");
                            if (projectFilesSectionDiv != null) {
                                window.opener.refreshProjectFilesSection(<?= $projectId ?>);
                                window.close();
                            }
    				    }
    				}
    			}
			}
        }
	});

	// Client side form validation
	$('form').submit(function(e) {
	    var uploader = $('#uploader').plupload('getUploader');

		// Validate number of uploaded files
		if (uploader.total.uploaded == 0) {
			// Files in queue upload them first
			if (uploader.files.length > 0) {
				// When all files are uploaded submit form
				uploader.bind('UploadProgress', function() {
					if (uploader.total.uploaded == uploader.files.length)
						$('form').submit();
				});

				uploader.start();
			} else
				alert('You must at least upload one file.');

			e.preventDefault();
		}
	});
	
	function processSingleUploadedFile(filename, origFilename) {
	    $.ajax({
            type: 'POST',
            url: 'processUploadedFile.php',
            data: 'action=process' +
                  '&pid=<?= $projectId ?>' +
                  '&filename='     + encodeURIComponent(filename) + 
                  '&origFilename=' + encodeURIComponent(origFilename),
            dataType: 'text',
            cache: false,
            timeout: 15000, // 15 seconds
            error: function(xmlHttpRequest, textStatus, errorThrown) {
                alert('ERROR: ' + textStatus + ' - ' + errorThrown);
            },
            success: function(data, textStatus) {
                // noop
            }
        });
	}

});
</script>
</body>
</html>
