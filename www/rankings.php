<?php

require("mcc_common.php");

$current_player = mcc_check_login();

$username = $current_player->getIdentifier();

$html_body  = "";

$html_body .= mcc_template_rankings($current_player, TRUE);
$html_body .= "<p>&nbsp;</p>\n";

echo mcc_template_page($html_body, "rankings", "Rankings");

?>
