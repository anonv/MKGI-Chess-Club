<?php

require("../mcc_common.php");
$current_player = mcc_check_login();

$gameid = mcc_get_page_parameter("gameid");

$xml = new DOMDocument("1.0", "utf-8");
$xml_games = $xml->createElement("games");

if ( $gameid ) {
	try {
		$game = new Game($gameid);
		$xml_game = $xml->createElement("game");
		$xml_game->setAttribute("id", $game->getId());
		$xml_game->setAttribute("nextPlayer", $game->getNextPlayer()->getIdentifier());
		$xml_game->setAttribute("moveCount", $game->getMoveCount());
		$xml_game->setAttribute("lastMoveTimestamp", $game->getLastMoveDate());
		$xml_games->appendChild($xml_game);
	} catch ( Exception $e ) {
		// Silently ignore a bogus game id
	}
}

$xml->appendChild($xml_games);

header("content-type: application/xml");
echo $xml->saveXML();
?>
