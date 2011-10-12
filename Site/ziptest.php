<?php

$zip = new ZipArchive();
$filename = "./test.zip";

if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
    exit("cannot open <$filename>\n");
}

$zip->addFile("./Content/UserImages/1d18a9b35a46b853bf62eb2114aacd56/1d18a9b35a46b853bf62eb2114aacd56.jpg", "./Content/UserImages/1d18a9b35a46b853bf62eb2114aacd56/1d18a9b35a46b853bf62eb2114aacd56_thumb.jpg");
echo "numfiles: " . $zip->numFiles . "\n";
echo "status:" . $zip->status . "\n";
$zip->close();

?>