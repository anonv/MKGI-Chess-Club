<?php

function AjaxRefreshUrl ( $game ) {
	global $mcc_server_root;
	return $mcc_server_root . "service/gameStatus.php?gameid=" . $game->getId();
}

?>
