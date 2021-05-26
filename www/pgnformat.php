<?php

require("mcc_common.php");

$current_player = mcc_check_login();

$gameid = mcc_get_page_parameter("gameid");

$current_game = new Game($gameid);

header("Content-Type: text/plain");
echo $current_game->getAsPgn();

?>
