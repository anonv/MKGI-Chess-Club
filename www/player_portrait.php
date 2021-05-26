<?php

require("mcc_common.php");

$current_player = mcc_check_login();

$f_identifier = mcc_get_page_parameter("player");

$portrait_player = new Player($f_identifier);

if ( ! $portrait_player ) {
	die();
}

$type = $portrait_player->getPortraitType();
$data = $portrait_player->getPortraitData();

if ( ! $type || !$data ) {
	$portrait = imagecreate(120, 150);

	$c_white = imagecolorallocate($portrait,0xFF,0xFF,0xFF);
	$c_gray  = imagecolorallocate($portrait,0x60,0x60,0x65);

	imagestring($portrait, 1, 12, 75, "Image not available", $c_gray);


	header("Content-Type: image/png");
	imagepng($portrait);
}
else {
	header("Content-Type: $type");
	echo $data;
}

?>
