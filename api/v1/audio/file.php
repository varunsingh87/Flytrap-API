<?php
$path = "../../audio_uploads/" . $_GET['alpha_id'];
if(file_exists($path)) {
	$fs = filesize($path);

	header("Content-Type: audio/mpeg\n");
	header("Content-Disposition: inline; filename=\"{$_GET['alpha_id']}\"\n");
	header("Content-Length: $fs\n");
  	readfile($path);
} else {
  header("HTTP/1.0 404 Not Found");
}

?>