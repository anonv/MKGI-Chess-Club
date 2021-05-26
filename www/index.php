<?php

require("mcc_common.php");

try {
	$current_player  = new Player();
	mcc_http_redirect("club.php");
} catch ( Exception $e ) {
	$current_player = NULL;
	mcc_http_redirect("login.php");
}


?>
