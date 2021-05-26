<?php

require("mcc_common.php");
require("mcc_scripts.php");

$current_player = mcc_check_login();

$username = $current_player->getIdentifier();

$gameid = mcc_get_page_parameter("gameid");
$rot_id = mcc_get_page_parameter("rotate", 0);

if ( $rot_id ) {
	$opp_rot_id = 0;
}
else {
	$opp_rot_id = 1;
}

$js_rendermove = mcc_JS_renderMove();

$html_left   = "";
$html_right  = "";
$html_center = "";
$html_body   = "";

$menu_data = array("browser", $gameid, $opp_rot_id);
$html_body .= "<div style=\"height: 2px;\"></div>\n";

$html_body .= <<<EOT
<script language="javascript" type="text/javascript">
<!--
var preload = new Image(); 
preload.src = "images/h_white.png";
preload.src = "images/h_black.png";
for ( index = 0; index < 10; index++)
	preload.src = "images/d" + index + ".png";

var parse_error = "";
var cur_move = -1, move_count=0, orig_move_count = 0;
var bottom = "w";
var name = "", draw_gap = 0, slot_id = 0;
var diff = Array( 0,0,0,0,0 );
var diff_names = Array( "pawn", "knight", "bishop", "rook", "queen" );
var d1, d2;
var board = new Array(
	0,0,0,0,0,0,0,0,
	0,0,0,0,0,0,0,0,
	0,0,0,0,0,0,0,0,
	0,0,0,0,0,0,0,0,
	0,0,0,0,0,0,0,0,
	0,0,0,0,0,0,0,0,
	0,0,0,0,0,0,0,0,
	0,0,0,0,0,0,0,0
);

/* move 0 is handled special to allow initialization:
 * board is cleared and first move is performed */
function gotoMove( move_id )
{
	if ( move_id < 0 ) move_id = 0;
	if ( move_id >= move_count ) 
	{
		move_id = move_count-1;
		if ( move_count < orig_move_count )
			alert( "Only "+move_count+" moves were parsed. Then an error occured: "+parse_error );
	}
	if ( move_id == cur_move ) return false;
	if ( move_id == 0 )
	{
		cur_move = 0;
		board = new Array( 
			/* 0=a1 - 63=h8 */
			/* chessmen codes: 0 empty,1-6 white PNbr /QK,7-12 black PNbr /QK */
			4, 2,3,5, 6, 3,2,4,
			1, 1,1,1, 1, 1,1,1,
			0, 0,0,0, 0, 0,0,0,
			0, 0,0,0, 0, 0,0,0,
			0, 0,0,0, 0, 0,0,0,
			0, 0,0,0, 0, 0,0,0,
			7, 7,7,7, 7, 7,7,7,
			10,8,9,11,12,9,8,10 );
		board[moves[1]] = board[moves[0]]; board[moves[0]] = 0;
	}
	else
	{
		//alert( moves[move_id*3]+"-"+moves[move_id*3+1]+" ("+moves[move_id*3+2]+")" );
		/* go forward or backward */
		if ( cur_move > move_id )
			while( cur_move > move_id )
				moveBackward();
		else
			while( cur_move < move_id )
				moveForward();
	}
	if ( move_id%2 == 0 )
		document.images["colorpin"].src = "images/h_white.png";
	else
		document.images["colorpin"].src = "images/h_black.png";

	setRoundNumber(Math.floor(move_id / 2)+1);
	renderBoard();

	// Render move on the board

	if ( cur_move % 2 == 0 ) {
		if ( cur_move > 0 ) {
			renderMove(moves[cur_move * 3],     moves[cur_move * 3 + 1],
				   moves[cur_move * 3 - 3], moves[cur_move * 3 - 2], true);
		}
		else {
			renderMove(moves[cur_move * 3], moves[cur_move * 3 + 1], -1, -1, true);
		}
	}
	else {
			renderMove(moves[cur_move * 3 - 3], moves[cur_move * 3 - 2],
				   moves[cur_move * 3],     moves[cur_move * 3 + 1], false);
	}

	// Fill move details

	if ( movedetails.length > cur_move ) {
		var d = document.getElementById("movedetails");
		d.innerHTML = movedetails[cur_move];
	}

	// Update the histmark

	for ( i = 0; i < move_count; i += 2 ) {
		histmarkname = "histmark" + i;
		document.images[histmarkname].src = "images/spacer.png";
	}

	histmarkname = "histmark" + (cur_move - cur_move%2);

	if ( move_id%2 == 0 ) {
		document.images[histmarkname].src = "images/histmark_w.png";
	}
	else {
		document.images[histmarkname].src = "images/histmark_b.png";
	}

	return false;
}

function setRoundNumber( round )
{
	d1 = Math.floor(round/10); d2 = Math.floor(round%10);
	document.images["digit1"].src = "images/d"+d1+".png";
	document.images["digit2"].src = "images/d"+d2+".png";
}

function moveForward()
{
	if (cur_move == move_count-1 ) return;
	cur_move++; pos = cur_move*3;
	if ( moves[pos] == 0 && moves[pos+1] == 0 ) return;
	/* castling is special */
	if ( moves[pos] > 63 )
	{
		rook_start = Math.floor(moves[pos]  %100);
		rook_end   = Math.floor(moves[pos+1]%100);
		king_start = Math.floor(moves[pos]  /100);
		king_end   = Math.floor(moves[pos+1]/100);

		//alert( rook_start+"-"+rook_end+"  "+king_start+"-"+king_end );

		board[rook_end] = board[rook_start]; 
		board[rook_start] = 0;
		board[king_end] = board[king_start]; 
		board[king_start] = 0;
	}
	else
	{
		if ( moves[pos+1] > 63 )
		{
			/*promotion*/
			dest = Math.floor(moves[pos+1]%100);
			upg = Math.floor(moves[pos+1]/100);
			board[dest] = board[moves[pos]]+upg;
		}
		else
			board[moves[pos+1]] = board[moves[pos]]; 
		board[moves[pos]] = 0;
		if ( moves[pos+2] > 63 )
		{
			pawn_pos = Math.floor(moves[pos+2]/100);
			board[pawn_pos] = 0;
		}
	}
}

function moveBackward()
{
	if (cur_move == 0 ) return;
	pos = cur_move*3; cur_move-=1;
	if ( moves[pos] == 0 && moves[pos+1] == 0 ) return;
	/* castling is special */
	if ( moves[pos] > 63 )
	{
		rook_start = Math.floor(moves[pos]  %100);
		rook_end   = Math.floor(moves[pos+1]%100);
		king_start = Math.floor(moves[pos]  /100);
		king_end   = Math.floor(moves[pos+1]/100);

		//alert( rook_start+"-"+rook_end+"  "+king_start+"-"+king_end );

		board[rook_start] = board[rook_end]; 
		board[rook_end] = 0;
		board[king_start] = board[king_end]; 
		board[king_end] = 0;
	}
	else
	{
		if ( moves[pos+1] > 63 )
		{
			dest = Math.floor(moves[pos+1]%100);
			upg = Math.floor(moves[pos+1]/100);
			board[moves[pos]] = board[dest]-upg; 
		}
		else
		{
			dest = moves[pos+1];
			board[moves[pos]] = board[dest];
		}
		if ( moves[pos+2] > 0 )
		{
			if ( moves[pos+2] > 12 )
			{
				/* en passant move */
				pawn_pos = Math.floor(moves[pos+2]/100);
				chessman = Math.floor(moves[pos+2]%100);
				board[pawn_pos] = chessman;
				board[dest] = 0;
			}
			else
				board[dest] = moves[pos+2];
		}
		else
			board[dest] = 0;
	}
}

function showDiff()
{
	for ( i = 0; i < 15; i++ )
		document.images["tslot"+i].src = "images/spacer.png";

	for ( i = 0; i < 5; i++ ) diff[i] = 0;
	for ( i = 0; i < 64; i++ )
		if ( board[i] > 0 )
		{
			if ( board[i] >= 7 && board[i] < 12 )
				diff[board[i]-7] -= 1;
			else
			if ( board[i] >= 1 && board[i] < 6 )
				diff[board[i]-1]++;
		}

	/* show superiority at top */
	slot_id = 0;
	for( i = 0; i < 5; i++ )
	{
		name = diff_names[4-i];
		if ( bottom=="b" && diff[4-i]<0 ) 
		{
			for ( j = 0; j < -diff[4-i]; j++ )
			{
				document.images["tslot"+slot_id].src = "images/wcg/sb"+name+".png";
				slot_id++;
			}
		}
		else
		if (bottom=="w" && diff[4-i]>0)
		{
			for ( j = 0; j < diff[4-i]; j++ )
			{
				document.images["tslot"+slot_id].src = "images/wcg/sw"+name+".png";
				slot_id++;
			}
		}
	}
	/* show superiority at bottom */
	if ( slot_id > 0 )
	{
		document.images["tslot"+slot_id].src="images/wcg/sempty.png";
		slot_id++;
	}
	for( i = 0; i < 5; i++ )
	{
		name = diff_names[4-i];
		if ( bottom=="b" && diff[4-i]>0 ) 
		{
			for ( j = 0; j < diff[4-i]; j++ )
			{
				document.images["tslot"+slot_id].src = "images/wcg/sw"+name+".png";
				slot_id++; 
			}
		}
		else
		if (bottom=="w" && diff[4-i]<0)
		{
			for ( j = 0; j < -diff[4-i]; j++ )
			{
				document.images["tslot"+slot_id].src = "images/wcg/sb"+name+".png";
				slot_id++;
			}
		}
	}
}

$js_rendermove

function renderBoard()
{
	for ( i = 0; i < 64; i++ )
	{
		if ( board[i] == 0 )
		{
			document.images["b"+i].src = "images/wcg/empty.png";
			continue;
		}
		value = board[i];
		if ( value >= 7 ) 
		{
			pref = "b"; 
			value -= 6;
		}
		else 
			pref = "w";
		switch (value)
		{
			case 1: chessman = "pawn.png"; break;
			case 2: chessman = "knight.png"; break;
			case 3: chessman = "bishop.png"; break;
			case 4: chessman = "rook.png"; break;
			case 5: chessman = "queen.png"; break;
			case 6: chessman = "king.png"; break;
		}
		document.images["b"+i].src = "images/wcg/"+pref+chessman;
	}
	showDiff();   
}
// -->
</script>
EOT;

/* ***** LOAD GAME ***** */

try {
	$current_game = new Game($gameid);
} catch ( Exception $e ) {
	$current_game = NULL;
}

if ( !$current_game ) {
	mcc_http_redirect("club.php");
}

$whitep = $current_game->getWhitePlayer();
$blackp = $current_game->getBlackPlayer();

if ( $current_player->getIdentifier() == $whitep->getIdentifier() ) {
	$displaywhiteside = ! $rot_id;
}
else {
	$displaywhiteside = $rot_id;
}

$html_left .= <<<EOT
<p style="text-align: center;" class="header">
	<strong>{$whitep->getIdentifier()}</strong> versus <strong>{$blackp->getIdentifier()}</strong>
EOT;



/* ***** GAME RESULT ***** */

if ( ! $current_game->isOpen() ) {
	$winner = $current_game->getWinner();
	if ( $winner == $current_game->WINNER_DRAW ) {
		$game_result = "draw";
	}
	else {
		if ( $current_game->getWinner() == $current_game->WINNER_WHITE ) {
			if ( $current_player->getIdentifier() == $whitep->getIdentifier() ) {
				$game_result = "you won";
			}
			else {
				$game_result = "{$whitep->getIdentifier()} won";
			}
		}
		else {
			if ( $current_player->getIdentifier() == $blackp->getIdentifier() ) {
				$game_result = "you won";
			}
			else {
				$game_result = "{$blackp->getIdentifier()} won";
			}
		}

		$html_left .= "<p><span class=\"warning\">This game is over: {$game_result}&nbsp;!</span></p>";
	}
}

$html_left .= "</p>";

/* ***** CONTROL BUTTONS ***** */
$html_left .= <<<EOT
<p style="text-align: center; width: 200px;">
<a href="first" onclick="return gotoMove(0);"><img alt="" src="images/h_first.png" /></a>
<a href="first" onclick="return gotoMove(cur_move-1);"><img alt="" src="images/h_backward.png" /></a>

<img width="2" height="2" alt="" src="images/spacer.png" />
<img name="colorpin" alt="" src="images/h_white.png" /><img name="digit1" alt="" src="images/d0.png" /><img name="digit2" alt="" src="images/d1.png" /><img alt="" src="images/h_right.png" />
<img width="2" height="2" alt="" src="images/spacer.png" />

<a href="first" onclick="return gotoMove(cur_move+1);"><img alt="" src="images/h_forward.png" /></a>
<a href="first" onclick="return gotoMove(move_count-1);"><img alt="" src="images/h_last.png" /></a>
</p>
EOT;

$html_left .= mcc_template_sidebox("Move details", "<div id='movedetails'></div>");

/* ***** MOVE HISTORY ***** */

$html_right .= mcc_template_move_history($current_game, TRUE, TRUE);

$html_left .= "<script language=\"javascript\" type=\"text/javascript\">\n<!--\n";
$html_left .= mcc_JS_movedetails($current_game);
$html_left .= mcc_JS_moveslist($current_game);
$html_left .= "-->\n</script>\n";

/* ***** BUILD CHESS BOARD Table ***** */

$html_center .= "<table style=\"width: 10px;\" summary=\"Chess Board\">";
$html_center .= "<tr><td bgcolor=\"$t_frame_color\">";
$html_center .= "<table class='chessboard' cellspacing='0' style=\"width: 10px;\" summary=\"Chess Board\">";

if ( $displaywhiteside ) {
	$index       =  56;
	$pos_change  =  1;
	$line_change = -16;
}
else {
	$index       =  7;
	$pos_change  = -1;
	$line_change =  16;
}

for ( $y = 0; $y < 9; $y++ ) {
	$html_center .= "<tr>";

	for ( $x = 0; $x < 9; $x++ ) {
		if ( $y == 8 ) {
			if ( $x > 0 ) {
				if ( $displaywhiteside ) {
					$c = chr(96 + $x);
				} else {
					$c = chr(96 + 9 - $x);
				}

				$html_center .= "<td class=\"board_cell_coordinate\">$c</td>";
			}
			else {
				$html_center .= "<td></td><td></td>";
			}
		} 
		else if ( $x == 0 ) {
			if ( $displaywhiteside ) {
				$i = 8 - $y;
			} else {
				$i = $y + 1;
			}

			$html_center .= "<td class=\"board_cell_coordinate\">$i</td><td>&nbsp;</td>";

		} 
		else {
			if ( (($y+1)+($x)) % 2 == 0 ) {
				$tile = "b_std_white";
			}
			else {
				$tile = "b_std_black";
			}

			$html_center .= "<td id=\"xb$index\" class=\"$tile\"><img name=\"b$index\" src=\"images/wcg/empty.png\" alt=\"\" /></td>";
			$index += $pos_change;
		}
	}

	$index += $line_change;
	$html_center .= "</tr>\n";
}

$html_center .= "</table>\n</td></tr>";
$html_center .= "<tr><td><img height=\"2\" src=\"images/spacer.png\" alt=\"\" /></td></tr>";
$html_center .= "<tr><td style='height: 21px;' bgcolor=\"$t_frame_color\">";

for ($i = 0; $i < 15; $i++ ) {
	$html_center .= "<img name=\"tslot$i\" src=\"images/wcg/sempty.png\" alt=\"\" />";
}

$html_center .= "</td></tr></table>\n";

if ( $displaywhiteside ) {
	$player_color = 'w';
} else {
	$player_color = 'b';
}

$html_right .= <<<EOT
<script language="javascript" type="text/javascript">
<!--
bottom="{$player_color}";
gotoMove(0);
gotoMove(move_count-1);
renderBoard();
// -->
</script>
EOT;


$html_body .= mcc_template_columns(array($html_left, $html_center, $html_right));

echo mcc_template_page($html_body, $menu_data, "History Browser");

?>
