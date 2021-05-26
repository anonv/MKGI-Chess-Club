<?php
require("mcc_common.php");
require("mcc_graph.php");

$months = array("", "January", "February", "March", "April", "May", "June", "July",
		"August", "September", "October", "November", "December");

$current_player = mcc_check_login();

$f_identifier = mcc_get_page_parameter("player");
$f_opponent   = mcc_get_page_parameter("opponent");

if ( $f_identifier ) {
	$profile_player = new Player($f_identifier);
}
else {
	$profile_player = $current_player;
}

if ( $f_opponent ) {
	$opponent_player = new Player($f_opponent);
}
else {
	$opponent_player = NULL;
}

$PIC_WIDTH  = 600;
$PIC_HEIGHT = 150;

$graph = new Graph();

if ( $opponent_player ) {
	$history1_data = $profile_player->getScoreHistory();
	$history2_data = $opponent_player->getScoreHistory();
	$graph->add( $profile_player->getIdentifier(), $history1_data);
	$graph->add($opponent_player->getIdentifier(), $history2_data);
}
else {
	$history1_data = $profile_player->getScoreHistory();
	$graph->add( $profile_player->getIdentifier(), $history1_data);
}

$graph->renderAsPng($PIC_WIDTH, $PIC_HEIGHT);

?>
