<?php

require("mcc_common.php");
require("mcc_scripts.php");

function page_refresh_items($p_form, $p_tip, $p_game, $p_lastmovesecs, $p_player, $p_opp) {
	// Is the viewer participating to the game ?
	if ( $p_game->hasPlayer($p_player) ) {
		if ( $p_opp->isRobot() ) {
			// The opponent a robot
			$auto_refresh = FALSE;
		} else if ( $p_player == $p_game->getNextPlayer() ) {
			// It is the viewer's turn
			// Can opponent stil undo its move ?
			if ( $p_lastmovesecs < UNDO_DELAY_MINS * 60 ) {
				$auto_refresh = TRUE;
			}
			else {
				$auto_refresh = FALSE;
			}
		}
		else {
			$auto_refresh = TRUE;
		}
	} else {
		$auto_refresh = TRUE;
	}

	$html = "";

	if ( $auto_refresh ) {
		$refreshScript = mcc_JS_smartrefresh($p_game, $p_form);

		if ( $p_tip ) {
			$html .= "<div class='inline_tip'>$p_tip</div>";
		}

		$html .= <<<EOT
			<script language="javascript" type="text/javascript">
			$refreshScript
			</script>
EOT;
	}

	return $html;
}

$current_player = mcc_check_login();
$username = $current_player->getIdentifier();

$gameid         = mcc_get_page_parameter("gameid");
$boardview_mode = mcc_get_page_parameter("boardview", BOARDVIEW_STANDARD);

try {
	$current_game = new Game($gameid);
} catch ( Exception $e ) {
	$current_game = NULL;
}

if ( !$current_game ) {
	mcc_http_redirect("club.php");
}

$player_w = $current_game->getWhitePlayer();
$player_b = $current_game->getBlackPlayer();


// If nobody connected, we want to see the whites down

if ( $current_player != $player_b ) {
	$player_opp = $player_b;
	$player_color = "w";
}
else {
	$player_opp = $player_w;
	$player_color = "b";
}

/* MCC Note: These are modified by handle_move.php functions... This behaviour should
 *           change ASAP...   */

$browsing_mode = 0;
$ac_move = "";
$w_figures = array();
$b_figures = array();
$attackers = array();
$board = array( 
	"  ", "  ", "  ", "  ", "  ", "  ", "  ", "  ",
	"  ", "  ", "  ", "  ", "  ", "  ", "  ", "  ",
	"  ", "  ", "  ", "  ", "  ", "  ", "  ", "  ",
	"  ", "  ", "  ", "  ", "  ", "  ", "  ", "  ",
	"  ", "  ", "  ", "  ", "  ", "  ", "  ", "  ",
	"  ", "  ", "  ", "  ", "  ", "  ", "  ", "  ",
	"  ", "  ", "  ", "  ", "  ", "  ", "  ", "  ",
	"  ", "  ", "  ", "  ", "  ", "  ", "  ", "  " );

$chessmove      = mcc_get_page_parameter("chessmove");
$chesscomment   = mcc_get_page_parameter("chesscomment");
$drawoffer      = mcc_get_page_parameter("drawoffer");
$cmd_move       = strlen($chessmove) > 0;
$cmd_draw       = mcc_get_page_parameter("accept_draw");
$cmd_delete     = mcc_get_page_parameter("delete_game");
$cmd_undo       = mcc_get_page_parameter("undo_move");
$cmd_archive    = mcc_get_page_parameter("move_to_archive");
$cmd_savenote   = mcc_get_page_parameter("refresh_and_save_note");
define("NULLNOTE", "=== No note ====");
$chessnote      = mcc_get_page_parameter("chessnote", NULLNOTE);

LogDebug("DRAW OFFER: " . $drawoffer);
if ( $drawoffer ) {
	$drawoffer = 1;
}
else {
	$drawoffer = 0;
}

// Hack to avoid problems with game history and positional draw
if ( $cmd_move ) {
	$current_game->purgeLegacyVariablesCache();
}

$game = $current_game->initializeLegacyVariables();

/* ***** COMPUTE WHETHER UNDO OKAY ***** */
// The first time, we test if we can validate an undo submission

$lastmoveseconds = mcc_db_time() - $current_game->getLastMoveDate();

if ( $lastmoveseconds < UNDO_DELAY_MINS * 60
	&& $current_game->isOpen()
	&& $current_game->getNextPlayer() == $player_opp
	&& ( $current_player == $player_w || $current_player == $player_b ) ) {
	$may_undo = 1;
}
else {
	$may_undo = 0;
}

LogDebug("%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%");
$next = $current_game->getNextPlayer();
LogDebug(" lastmoveseconds " . $lastmoveseconds);
LogDebug(" delay    " . UNDO_DELAY_MINS * 60);
LogDebug(" drawoffer " . $current_game->isDrawOffered());
LogDebug(" isopen " . $current_game->isOpen());
LogDebug(" opp " . $player_opp->getIdentifier());
LogDebug(" next " . $next->getIdentifier());
LogDebug(" w " . $player_w->getIdentifier());
LogDebug(" b " . $player_b->getIdentifier());
LogDebug(" May undo " . $may_undo);
LogDebug("%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%");

/* ***** CHECK A SUBMITTED MOVE OR UNDO ***** */

if ( $cmd_delete ) {
	if ( $current_player != $player_w && $current_player != $player_b ) {
		$move_result = "ERROR: You do not participate in this game!";
	} else if ( $current_game->getNextPlayer() == $current_player ) {
		$move_result = "ERROR: You cannot delete a game, if it is your turn!";
	} else if ( ! $current_game->isOpen() ) {
		$move_result = "ERROR: Only open games may be deleted!";
	} else if ( $current_game->isDeleted() ) {
		$move_result = "ERROR: Game already deleted !";
	} else if ( $lastmoveseconds < DROP_DELAY_MINS * 60 ) {
		$move_result = "ERROR: Your opponents move must be two weeks or older to delete the game!";
	} else {
		$move_result = "Game deleted.";
		$pl_w = $current_game->getWhitePlayer();
		$pl_b = $current_game->getBlackPlayer();

		$player_is_w = ( $pl_w == $current_player );
		$player_is_b = ( $pl_b == $current_player );

		if ( ($player_is_w && $current_game->getMoveCount() >= 2) ||
				 ($player_is_b && $current_game->getMoveCount() >= 1 ) ) {

			if ( $player_is_w ) {
				updateStats( $pl_w->getIdentifier(), $pl_b->getIdentifier(), 'w' );
			} else {
				updateStats( $pl_w->getIdentifier(), $pl_b->getIdentifier(), 'b' );
			}
		}

		$current_game->delete();
	}
}
else if ( $cmd_undo ) {
	// $move_result = handleUndo( $game );
	if ( $may_undo ) {
		$current_game->performUndo();
		$move_result = "Move " . trim($ac_move, "?") . " undone !";
		$game = $current_game->initializeLegacyVariables();
	}
	else {
		$move_result = "You cannot undo !";
	}
}
else if ( $cmd_draw ) {
	if ( $current_game->isDrawOffered() ) {
		$current_game->draw();
		$pl_w = $current_game->getWhitePlayer();
		$pl_b = $current_game->getBlackPlayer();
		updateStats($pl_w->getIdentifier(), $pl_b->getIdentifier(), '-');
	}
}
else {
	if ( $cmd_archive ) {
		/* only allow if user is active (which it may not be if
			 undo function was used) */

		LogDebug("move_to_archive is set...");

		if ( ! $current_game->isOpen() ) {
			LogDebug("moving to archive !");
			$current_game->setArchived(TRUE);
		}
	}
	
	if ( $cmd_move && !empty($chessmove) && $current_game->getNextPlayer() == $current_player ) {
		$move_result = handleMove($game, $board, $w_figures, $b_figures, $username,
				$ac_move, $res_games, $chessmove, $chesscomment, $current_game);
	}

	if ( $chessnote != NULLNOTE && $current_game->hasPlayer($current_player) ) {
		$note = new Note($current_player, $current_game);
		$note->setText($chessnote);
	}
}

if ( isset($move_result) ) {
	$current_game->updateFromLegacyVariables($game, $move_result, $chesscomment, $drawoffer);
}

/*
 * Now the player move is completely processed.
 * We check if the opponent is a robot that could play at once.
 */

if ( $cmd_move && isset($move_result) && $current_game->isOpen() ) {
	$nextplayer = $current_game->getNextPlayer();
	if ( $nextplayer->isRobot() ) {
		//$computed_move = ComputeNextMove_Gnuchess($current_game->getMoves(), 5);
		$computed_move = $nextplayer->computeNextMove($current_game);
		$computed_comment = NULL;

		if ( $computed_move ) {
			$move_result = handleMove($game, $board, $w_figures, $b_figures,
						$nextplayer->getIdentifier(),
						$ac_move, $res_games, $computed_move,
						$computed_comment, $current_game);
			$current_game->updateFromLegacyVariables($game, $move_result,
						$computed_comment, FALSE);

			// Reset the chess move which will be diplayed in the player form
			$chessmove = '';

			// The draw offer flag also has to be reset
			$drawoffer = 0;

			/*
			 * If the game is over after a robot move, archive it.
			 */

			 if ( ! $current_game->isOpen() ) {
				$current_game->setArchived(TRUE);
			 }
		}
	}
}


/* ***** COMPUTE WHETHER UNDO OKAY ***** */
// The 2nd time, we decide if we must display an undo form

// The last move may not be the same since we last computed it since
// moves might have been played by the player and a robot opponent.
$lastmoveseconds = mcc_db_time() - $current_game->getLastMoveDate();

if ( $lastmoveseconds < UNDO_DELAY_MINS * 60
	&& $current_game->isOpen()
	&& $current_game->getNextPlayer() == $player_opp
	&& ( $current_player == $player_w || $current_player == $player_b ) ) {
	$may_undo = 1;
}
else {
	$may_undo = 0;
}

$move_id     = $current_game->getTurnCount();
$move_player = $current_game->getNextPlayer();
$move_color  = ($move_player == $player_w)?"White":"Black";

$disp_move_id = $move_id;

if ( $move_player == $player_w ) {
	$disp_move_id++;
}

$name_w = $player_w->getIdentifier();
$name_b = $player_b->getIdentifier();

$my_url = "chess.php?gameid=" . $current_game->getId() . "&amp;boardview=$boardview_mode";

$js_rendermove = mcc_JS_renderMove();
$js_dragndrop  = mcc_JS_dragndrop();
$js_moveslist  = mcc_JS_moveslist($current_game);

$html_body  =  "";
$html_left  =  "";
$html_right =  "";
$html_center = "";

$html_body .= <<<EOT
<script language="javascript" type="text/javascript">
<!--
$js_moveslist
$js_rendermove
$js_dragndrop

dnd_setHandler(dndHandler);

var moveparts = new Array();

// Mouse click handler
function clickHandler ( part ) {
	var currentmove = window.document.cmdform.chessmove.value;

	var partistarget = part.charAt(0) == '-' || part.charAt(0) == 'x';

	if ( moveparts.length == 0 ) {
		if ( !partistarget ) {
			moveparts[0] = part;
			assembleCmd(moveparts);
		}
	}
	else {
		if ( partistarget ) {
			moveparts[1] = part;
			assembleCmd(moveparts);
		}
		else {
			moveparts[0] = part;
			assembleCmd(moveparts);
		}
	}
}

// Drag'n'Drop handler
function dndHandler ( parts ) {
	return assembleCmd(parts, true);
}

// Main move building management
function assembleCmd ( parts, do_forceform ) {

	// Process the part and update the array

	var force_form = false;

	if ( parts.length == 1 ) {
		moveparts = parts;
	}
	else if ( parts.length == 2 && parts[1] != null
		&& parts[0] != parts[1]
		&& ( parts[1].charAt(0) == '-' || parts[1].charAt(0) == 'x' )
		) {
		moveparts = parts;
		force_form = do_forceform;
	}

	// Convert the array to the actual move

	window.document.cmdform.chessmove.value = '';

	if ( moveparts.length > 0 ) {
		window.document.cmdform.chessmove.value += moveparts[0];
	}

	if ( moveparts.length > 1 ) {
		window.document.cmdform.chessmove.value += moveparts[1];
	}

	// If required, submit the move form

	if ( force_form ) {
		window.document.cmdform.submit();
	}

	return force_form;
}

function confirm_undo() {
	if (confirm("Are you sure you want to undo your last move?")) {
		return true; 
	} else {
		return false;
	}
}

function confirm_delete() {
	if (confirm("Are you sure you want to delete this game?")) {
		return true; 
	} else {
		return false;
	}
}

//-->
</script>
EOT;


$html_body .= "<div style=\"height: 2px;\"></div>\n";

$html_left .= "<p class=\"header\" style=\"text-align: center; margin-top: 0;\">";
$html_left .= "<strong>$name_w</strong>";
$html_left .= " versus ";
$html_left .= "<strong>$name_b</strong>";
$html_left .= "<br /> Move $disp_move_id ($move_color) </p>";

$is_playing = 0;

if ( $current_player == $move_player && $current_game->isOpen() ) {
	$is_playing = 1;
}

if ( $current_player != $move_player
		&& $lastmoveseconds > DROP_DELAY_MINS * 60
		&& $current_game->isOpen()
		&& ( $current_player == $player_w || $current_player == $player_b ) ) {
	$html_left .= "<p><form class=\"warning\" onsubmit=\"return confirm_delete();\" method=\"post\" action=\"$my_url\">";
	$html_left .= "Since your opponent did not move for more than one month, you may delete this abandoned game. 
	It will be counted as win, if your opponent did move at least once.<br /><div align=\"center\">
	<input type=\"submit\" name=\"delete_game\" value=\"Delete This Abandoned Game.\" /></div>";
	$html_left .= "</form></p>";
}


/* ***** MOVE DESCRIPTION ***** */

$last_move = $current_game->getLastMove();

if ( $current_game->isOpen() && $last_move ) {

	if ( isset($move_result) && $move_result != $last_move->getLong() ) {
		$html_left .= "<p><strong>$move_result</strong></p>\n";
	}

	$html_left .= "<form style=\"color: #8888ff;\" onsubmit=\"return confirm_undo();\" method=\"post\" action=\"$my_url\">\n";

	if ( $may_undo ) {
		$html_left .= "<input style=\"float:right; margin-top: 4px;\" type=\"submit\" name=\"undo_move\" value=\"Undo\" />\n";
	}

	if ( $move_color == "Black" ) {
		$html_left .= "<strong>White's";
	}
	else {
		$html_left .= "<strong>Black's";
	}

	$html_left .= " last move:<br />" . $last_move->getLong() . "</strong><br />\n";

	$html_left .= "</form>\n";
}

/* ***** CHATTER ***** */

if ( $last_move && $last_move->getChatter() ) {
	$chat = $last_move->getChatter();
	$chat = str_replace("\n", "<br />", $chat);
}
else {
	$chat = "No chatter";
}

$html_left .= "<div class='chatter'>$chat</div>";

if ( ! $current_game->isOpen() ) {
	$result = $current_game->getWinner();

	if ( $current_game->isDeleted() ) {
		$game_result = "game deleted !";
	}
	else if ( $result == $current_game->WINNER_DRAW ) {
		$game_result = "draw";
	}
	else {
		if ( $result == $current_game->WINNER_WHITE ) {
			if ( $player_w == $current_player ) {
				$game_result = "you won";
			}
			else {
				$game_result = $player_w->getIdentifier() . " won";
			}
		}
		else {
			if ( $player_b == $current_player ) {
				$game_result = "you won";
			}
			else {
				$game_result = $player_b->getIdentifier() . " won";
			}
		}
	}

	$html_left .= "<p class=\"warning\">This game is over: $game_result!</p>"; 

	if ( $current_game->hasPlayer($current_player)
		&& ( $move_player == $current_player || $player_opp->isRobot() )
		&& ! $current_game->isArchived()
		&& ! $current_game->isDeleted() ) {
		$html_left .= <<<EOT
		<form method="post" action="$my_url"><p>
			<input type="submit" name="move_to_archive" value="Move To Archive" />
			</p></form>
EOT;
	}
}
else if ( $move_player == $current_player ) {
/* Draw offer form */
	if ( $current_game->isDrawOffered() ) {
		$opponent = $player_opp->getIdentifier();

		$html_left .= <<<EOT
<p>
<span class="warning">
	$opponent offers a draw. Do you accept&nbsp;?
</span><br />
<form method="post" action="$my_url">
<table style="width: 100%;" summary="Draw offer"><tr><td>
	<input type="submit" name="accept_draw" value="Accept Draw" />
</td></tr></table>
</form>
</p>
<p>Otherwise, just play your next move.</p>
EOT;
	}

/* MOVE form BEGIN */

$note = new Note($current_player, $current_game);
$notebox = mcc_template_notebox($note, FALSE);

if ( $drawoffer ) {
	$drawchecked = "checked";
}
else {
	$drawchecked = "";
}

$refresh = page_refresh_items("cmdform", "This page will be refreshed if your opponent undoes its move.",
				$current_game, $lastmoveseconds,
				$current_player, $player_opp);

$html_left .= <<<EOT
<form name="cmdform" id="cmdform" method="post" action="$my_url">

<table style="width: 100%;" summary="Chess Move">
<tr>
<td>Your Move:</td>
<td><input type="text" size="10" name="chessmove" value="$chessmove" /></td>
</tr>
<tr>
<td />
<td><input type="checkbox" size="10" name="drawoffer" $drawchecked /> Offer draw</td>
</tr>
</table>

$refresh

Your Comment:<br />
<textarea class="playercomment" name="chesscomment">$chesscomment</textarea><br />

$notebox

</form>
EOT;

} else if ( $current_player != $player_w && $current_player != $player_b ) {
	if ( $current_game->getNextPlayer() == $current_game->getWhitePlayer() ) {
		$html_left .= "<p class=\"warning\">It is white's turn. (You do not participate in this game.)</p>";
	}
	else {
		$html_left .= "<p class=\"warning\">It is black's turn. (You do not participate in this game.)</p>";
	}

	$html_left .= page_refresh_items(NULL, "This page will refresh automatically on the next move.",
						$current_game, $lastmoveseconds,
						$current_player, $player_opp);
}
else
{
$note = new Note($current_player, $current_game);
$notebox = mcc_template_notebox($note, TRUE);
$opponent = $player_opp->getIdentifier();
$refresh = page_refresh_items("note-form",
				"This page will refresh itself automatically on your opponent's next move.",
				$current_game, $lastmoveseconds,
				$current_player, $player_opp);

$html_left .= <<<EOT
<p class="warning">
It is $opponent's turn. You have to wait until this user made its move.
</p>
$refresh
<form method="post" id='note-form' action="$my_url">
$notebox
</form>
EOT;
}

$html_right .= mcc_template_move_history($current_game);

/* ***** FILL CHESS BOARD ARRAY ***** */
$diff = fillChessBoard($board, $w_figures, $b_figures, trim($game[2]), trim($game[3]) );

$html_center .= mcc_template_board($current_game, $player_color == "b", $boardview_mode, $is_playing, $board, $diff, FALSE);

if ( $boardview_mode != BOARDVIEW_STRATEGIC ) {
$html_center .= <<<EOT
<script language="javascript" type="text/javascript">
<!--
var lastmove_offset = moves.length - 3;
var movecount = Math.floor(moves.length / 3);

if ( movecount > 0 ) {
	if ( movecount % 2 == 0 ) {
		renderMove(-1, -1,
			moves[lastmove_offset], moves[lastmove_offset + 1],
				false);
	}
	else {
		renderMove(moves[lastmove_offset], moves[lastmove_offset + 1],
				-1, -1, false);
	}
}
// -->
</script>
EOT;
}

$html_body .= mcc_template_columns(array($html_left, $html_center, $html_right));

if ( $boardview_mode == BOARDVIEW_STRATEGIC ) {
	$pagetitle = "Strategic Chessboard View";
}
else {
	$pagetitle = "Chessboard View";
}

if ( $current_game->isOpen()
	&& $current_game->hasPlayer($current_player)
	&& $move_player != $current_player ) {

}

$menu_data = array("chess", $current_game->getId(), $boardview_mode);
echo mcc_template_page($html_body, $menu_data, $pagetitle);

?>
