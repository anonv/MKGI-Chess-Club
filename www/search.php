<?php
require "mcc_common.php";

$current_player = mcc_check_login();

$html_body  = "";

$location = mcc_get_page_parameter("location");
$player   = mcc_get_page_parameter("player");
$color    = mcc_get_page_parameter("color");
$opponent = mcc_get_page_parameter("opponent");
$action   = mcc_get_page_parameter("search");

if ( $action ) {
	$current_gameset = new GameSet();
	$current_gameset->setLocation($location);
	$current_gameset->setPlayer($player);
	$current_gameset->setColor($color);
	$current_gameset->setOpponent($opponent);
	$current_gameset->setName("Search results");
	$current_gameset->setOrder(GAMESET_ORDER_START);
	$html_body .= mcc_template_gameslist($current_gameset, $current_player, TRUE, FALSE);
}

$html_body .= <<<EOT
<table><tr><td valign="top" align="center">
EOT;

$html_body .= mcc_template_searchbox($location, $player, $color, $opponent);

$html_body .= <<<EOT
</table>
EOT;

echo mcc_template_page($html_body, "search", "Search");

?>
