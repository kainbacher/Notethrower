<?php

error_reporting(E_ALL | E_STRICT);

$zip = new ZipArchive();
$filename = "/home/benso/oneloudr.com/OL/Content/UserImages/test.zip";

if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
    exit("cannot open <$filename>\n");
}

$zip->addFile("/home/benso/oneloudr.com/OL/Content/UserImages/1d18a9b35a46b853bf62eb2114aacd56/1d18a9b35a46b853bf62eb2114aacd56.jpg", "Files/img.jpg");
$zip->addFile("/home/benso/oneloudr.com/OL/Content/UserImages/1d18a9b35a46b853bf62eb2114aacd56/1d18a9b35a46b853bf62eb2114aacd56_thumb.jpg", "Files/thumb.jpg");
echo "numfiles: " . $zip->numFiles . "\n";
echo "status:" . $zip->status . "\n";
$zip->close();

?>
