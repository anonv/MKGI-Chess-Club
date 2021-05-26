<?php
require "mcc_common.php";

$ivid = mcc_get_page_parameter("invitation");

if ( $ivid ) {
	try {
		$invitation = new Invitation(intval($ivid));
	} catch ( Exception $e ) {
		$invitation = NULL;
	}

	if ( $invitation ) {
		$invitation->setOpened();
	}
}

header("Content-Type: image/png");
readfile("images/club_logo.png");


?>
