<?php
require("mcc_common.php");
require("mcc_enums.php");

$current_player = mcc_check_login();

// Fetch form parameters

$opponent = mcc_get_page_parameter("opponent");
$color    = mcc_get_page_parameter("color");

// Ok, let's go to work

$html_body = "";

$current = $current_player->getIdentifier();

$opponent_player = new Player($opponent);

$message = NULL;
$target  = NULL;

if ( ! $opponent_player ) {
	$message = "There is no player named $opponent.";
}
else if( $opponent_player == $current_player ) {
	$message = "You can't open a game against yourself.";
}
else if ( $color ) {
	if ( $color == "white" ) {
		$current_game = new Game(NULL, $current_player, $opponent_player); 
	}
	else {
		$current_game = new Game(NULL, $opponent_player, $current_player); 

		if ( $opponent_player->isRobot() ) {
			$computed_move = $opponent_player->computeNextMove($current_game);
			$computed_comment = NULL;

			if ( $computed_move ) {
				$game = $current_game->initializeLegacyVariables();
				$move_result = handleMove($game, $board, $w_figures, $b_figures,
							$opponent_player->getIdentifier(),
							$ac_move, $res_games, $computed_move,
							$computed_comment, $current_game);
				$current_game->updateFromLegacyVariables($game, $move_result,
							$computed_comment, FALSE);
			}
		}
	}

	if ( $current_game ) {
		$gameid = $current_game->getId();
		$target = "chess.php?gameid=$gameid";
	}
	else {
		$message = "Technical error. Please contact us to report this problem !";
	}
}
else {
	$infos_current  = mcc_template_userinfos($current_player);
	$infos_opponent = mcc_template_userinfos($opponent_player);

	$expected_percent = round(100 * computeExpectedScore($current_player, $opponent_player));

	$url_w = mcc_build_url(NULL, "color", "white");
	$url_b = mcc_build_url(NULL, "color", "black");
	
	$html_body .= <<<EOT
	<table class="newgame">
	<tr>
	<td class="newgame_cell">
		<div style="font-weight: bold; font-size:14pt; font-style: italic;">$current</div>
		$infos_current
	</td>
	<td class="newgame_cell" style="font-size: 18pt; font-weight: bold;">
		VS
	</td>
	<td class="newgame_cell">
		<div style="font-weight: bold; font-size:14pt; font-style: italic;">$opponent</div>
		$infos_opponent
	</td>
	</tr>
	<tr>
	<td colspan="3" class="newgame_cell">
		<img src="player_graphs.php?player=$current&opponent=$opponent" alt="Players Graphs" />
	</td>
	</tr>
	<tr>
	<td colspan="3" class="newgame_cell">
		<p>Your chances to win: $expected_percent%</p>
		<p>
		[
		<a href="$url_w">Start this game as White</a>
		|
		<a href="$url_b">Start this game as Black</a>
		|
		<a href="club.php">Cancel</a>
		]
	</td>
	</tr>
	</table>
EOT;
}

if ( $message ) {
	$html_body .= "<p>&nbsp;</p>\n";
	$html_body .= "<p style=\"text-align: center;\">$message</p>\n";
	$html_body .= "<p>&nbsp;</p>\n";
	$html_body .= "<p style=\"text-align: center;\"><a href=\"club.php\">Back to games overview</a></p>\n";
	$html_body .= "<p>&nbsp;</p>\n";
}


if ( $target ) {
	mcc_http_redirect($target);
}
else {
	echo mcc_template_page($html_body, "newgame", "New Game");
}

?>
