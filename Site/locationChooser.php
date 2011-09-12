<?php

include_once('../Includes/Init.php'); // this needs to be included with a relative path

include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');

$formName   = isParamSet('formName')   ? get_param('formName')   : 'na';
$latFieldId = isParamSet('latFieldId') ? get_param('latFieldId') : 'na';
$lngFieldId = isParamSet('lngFieldId') ? get_param('lngFieldId') : 'na';

?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
<title>Pick location</title>
<link href="../Styles/googlemap.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
<script type="text/javascript">

// default to nashville
var startLatitude  = 36.166;
var startLongitude = -86.784;

if (window.opener && window.opener.document.<?= $formName ?>) {
    if (
        window.opener.document.<?= $formName ?>.<?= $latFieldId ?>.value &&
        window.opener.document.<?= $formName ?>.<?= $lngFieldId ?>.value
    ) {
        startLatitude  = window.opener.document.<?= $formName ?>.<?= $latFieldId ?>.value;
        startLongitude = window.opener.document.<?= $formName ?>.<?= $lngFieldId ?>.value;
    }
}

var startLoc = ['', startLatitude, startLongitude];

var geocoder;
var geocodedAddress;
var map;
var marker;

function initialize() {
    geocoder = new google.maps.Geocoder();

    geocodedAddress = new google.maps.LatLng(startLatitude, startLongitude);

    var myOptions = {
        zoom: 10,
        center: geocodedAddress,
        mapTypeId: google.maps.MapTypeId.ROADMAP
    }
    map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);

    setMarker(map, startLoc);
}

function geocodeAddress(addressStr) {
    if (!addressStr) return;

    geocoder.geocode({
        'address': addressStr
    }, function(results, status) {
        if (status == google.maps.GeocoderStatus.OK) {
            geocodedAddress = results[0].geometry.location;
            panToGeocodedAddress();
            setLocationInOpenerForm();

        } else {
            alert("Unable to find address! Please enter more details.");
        }
    });
}

function panToGeocodedAddress() {
    marker.setPosition(geocodedAddress);
    map.panTo(geocodedAddress);
}

function setMarker(map, loc) {
    var myLatLng = new google.maps.LatLng(loc[1], loc[2]);
    marker = new google.maps.Marker({
        position: myLatLng,
        map: map,
        //shadow: shadow,
        //icon: image,
        //shape: shape,
        //title: loc[0],
        zIndex: 1,
        draggable: true
    });

    google.maps.event.addListener(marker, 'dragend', function() {
        setLocationInOpenerForm();
    });
}

function setLocationInOpenerForm() {
    if (window.opener && window.opener.document.<?= $formName ?>) {
        window.opener.document.<?= $formName ?>.<?= $latFieldId ?>.value = marker.position.lat();
        window.opener.document.<?= $formName ?>.<?= $lngFieldId ?>.value = marker.position.lng();
    }
}

</script>
</head>
<body onload="initialize()">
  <div id="jumpToLocationDiv" style="margin-top:5px;text-align:left;">
    Jump to address:&nbsp;
    <input type="text" name="destAddress" size="60" onChange="geocodeAddress(this.value)">
  </div>
  <div id="map_canvas" style="top:30px"></div>
</body>
</html> 