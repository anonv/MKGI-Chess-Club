<?php

function count_occurences ( $p_seed, $p_array )
{
	$result = 0;

	foreach ( $p_array as $item ) {
		if ( $p_seed == $item ) {
			$result ++;
		}
	}

	return $result;
}

/**************************************************************
 * return the array of adjacent tiles, thus 8 at the most 
 **************************************************************/

function getAdjTiles( $fig_pos )
{
	$adj_tiles = array(); $i = 0;

	$x = $fig_pos % 8; $y = floor( $fig_pos / 8 );
	
	if ( $x > 0 && $y > 0 ) $adj_tiles[$i++] = $fig_pos-9;
	if (           $y > 0 ) $adj_tiles[$i++] = $fig_pos-8;
	if ( $x < 7 && $y > 0 ) $adj_tiles[$i++] = $fig_pos-7;
	if ( $x < 7           ) $adj_tiles[$i++] = $fig_pos+1;
	if ( $x < 7 && $y < 7 ) $adj_tiles[$i++] = $fig_pos+9;
	if (           $y < 7 ) $adj_tiles[$i++] = $fig_pos+8;
	if ( $x > 0 && $y < 7 ) $adj_tiles[$i++] = $fig_pos+7;
	if ( $x > 0           ) $adj_tiles[$i++] = $fig_pos-1;

	/* DEBUG:  foreach( $adj_tiles as $tile )
		echo "adj: $tile "; */
 
	return $adj_tiles;
}


/**************************************************************
 * Check a series of tiles given a start, an end tile
 * which is not included to the check and a position
 * change for each iteration. return true if not blocked. 
 * all values are given for 1dim board.
 **************************************************************/

function pathIsNotBlocked( &$board, $start, $end, $change )
{
	for ( $pos = $start; $pos != $end; $pos += $change )
	{
		/* DEBUG: echo "path: $pos: '$board[$pos]' "; */
		if ( $board[$pos] != "  " ) {
			 return 0;
		}
	}

	return 1;
}


/**************************************************************
 * get the empty tiles between start and end as an 1dim array.
 * whether the path is clear is not checked.
 **************************************************************/

function getPath( $start, $end, $change )
{
	$path = array(); $i = 0;
	for ( $pos = $start; $pos != $end; $pos += $change )
		$path[$i++] = $pos;
	return $path;
}


/**************************************************************
 * get the change value that must be added to create
 * the 1dim path for figure moving from fig_pos to
 * dest_pos. it is assumed that the movement is valid!
 * no additional checks as in tileIsReachable are
 * performed. rook, queen and bishop are the only
 * units that can have empty tiles in between.
 **************************************************************/

function getPathChange( $fig, $fig_pos, $dest_pos )
{
	$change = 0;
	$fy = floor($fig_pos/8); $fx = $fig_pos%8;
	$dy = floor($dest_pos/8); $dx = $dest_pos%8;
	switch ( $fig )
	{
		/* bishop */
		case 'B':
			if ( $dy < $fy ) $change = -8; else $change =  8;
			if ( $dx < $fx ) $change -= 1; else $change += 1;
			break;
		/* rook */
		case 'R':
			if ( $fx==$dx ) 
			{
				if ( $dy<$fy ) $change = -8; else $change = 8;
			}
			else {
				if ( $dx<$fx ) $change = -1; else $change = 1;
			}
			break;
		/* queen */
		case 'Q':
			if ( abs($fx-$dx) == abs($fy-$dy) )
			{
				if ( $dy < $fy ) $change = -8; else $change =  8;
				if ( $dx < $fx ) $change -= 1; else $change += 1;
			}
			else if ( $fx==$dx ) {
				if ( $dy<$fy ) $change = -8; else $change = 8;
			} 
			else
			{
				if ( $dx<$fx ) $change = -1; else $change = 1;
			}
			break;
	}
	return $change;
}

/**************************************************************
 * check whether dest_pos is in reach for unit of fig_type
 * at tile fig_pos. it is not checked whether the tile
 * itself is occupied but only the tiles in between. 
 * this function does not check pawns.
 **************************************************************/

function tileIsReachable( &$board, $fig, $fig_pos, $dest_pos, $ignore_oppking = FALSE )
{
	if ( $fig_pos==$dest_pos) return;
	$result = 0;
	$fy = floor($fig_pos/8); $fx = $fig_pos%8;
	$dy = floor($dest_pos/8); $dx = $dest_pos%8;
	/* DEBUG:  echo "$fx,$fy --> $dx,$dy: "; */
	switch ( $fig )
	{
		/* knight */
		case 'N':
			if ( abs($fx-$dx)==1 && abs($fy-$dy)==2 )
				$result = 1;
			if ( abs($fy-$dy)==1 && abs($fx-$dx)==2 )
				$result = 1;
			break;
		/* bishop */
		case 'B':
			if ( abs($fx-$dx) != abs($fy-$dy) )
				break;
			if ( $dy < $fy ) $change = -8; else $change =  8;
			if ( $dx < $fx ) $change -= 1; else $change += 1;
			if ( pathIsNotBlocked($board, $fig_pos+$change, $dest_pos, $change ) )
				$result = 1;
			break;
		/* rook */
		case 'R':
			if ( $fx!=$dx && $fy!=$dy )
				break;
			if ( $fx==$dx ) 
			{
				if ( $dy<$fy ) $change = -8; else $change = 8;
			}
			else {
				if ( $dx<$fx ) $change = -1; else $change = 1;
			}
			if ( pathIsNotBlocked($board, $fig_pos+$change, $dest_pos, $change ) )
				$result = 1;
			break;
		/* queen */
		case 'Q':
			if ( abs($fx-$dx) != abs($fy-$dy) && $fx!=$dx && $fy!=$dy )
				break;
			if ( abs($fx-$dx) == abs($fy-$dy) )
			{
				if ( $dy < $fy ) $change = -8; else $change =  8;
				if ( $dx < $fx ) $change -= 1; else $change += 1;
			}
			else if ( $fx==$dx ) {
				if ( $dy<$fy ) $change = -8; else $change = 8;
			} 
			else
			{
				if ( $dx<$fx ) $change = -1; else $change = 1;
			}
			if ( pathIsNotBlocked($board, $fig_pos+$change, $dest_pos, $change ) )
				$result = 1;
			break;
		/* king */
		case 'K':
			if ( abs($fx-$dx) > 1 || abs($fy-$dy) > 1 ) break;
			if ( ! $ignore_oppking ) {
				$kings = 0;
				$adj_tiles = getAdjTiles( $dest_pos );
				foreach( $adj_tiles as $tile ) {
					if ( $board[$tile][1] == 'K' ) $kings++;
				}
				if ( $kings == 2 ) break;
			}
			$result = 1;
			break;
	}

	/* DEBUG: echo " $result<BR>"; */

	return $result;
}


/**************************************************************
 * check whether pawn at figpos may attack destpos.
 * by meaning whether it is diagonal.
 **************************************************************/

function checkPawnAttack( &$board, $fig_pos, $dest_pos )
{
	if ( $board[$fig_pos][0] == 'w' )
	{
		if ( ($fig_pos % 8) > 0 && $dest_pos == $fig_pos+7 )
			return 1;
		if ( ($fig_pos % 8) < 7 && $dest_pos == $fig_pos+9 )
			return 1;
	}
	else if ( $board[$fig_pos][0] == 'b' )
	{
		if ( ($fig_pos % 8) < 7 && $dest_pos == $fig_pos-7 )
			return 1;
		if ( ($fig_pos % 8) > 0 && $dest_pos == $fig_pos-9 )
			return 1;
	}
	return 0;
}


/**************************************************************
 * check whether pawn at figpos may move to destpos.
 * first move may be two tiles instead of just one. 
 * again the last tile is not checked but just the path
 * in between.
 **************************************************************/
function checkPawnMove( &$board, $fig_pos, $dest_pos )
{
	$first_move = 0;
	
	if ( $board[$fig_pos][0] == 'w' )
	{
		if ( $fig_pos >= 8 && $fig_pos <= 15 )
			$first_move = 1;
		if ( $dest_pos==$fig_pos+8 )
			return 1;
		if ( $first_move && ( $dest_pos==$fig_pos+16 ) )
		if ( $board[$fig_pos+8] == "  " )
			return 1;
	}
	else if ( $board[$fig_pos][0] == 'b' )
	{
		if ( $fig_pos >= 48 && $fig_pos <= 55 )
			$first_move = 1;
		if ( $dest_pos==$fig_pos-8 )
			return 1;
		if ( $first_move && ( $dest_pos==$fig_pos-16 ) )
		if ( $board[$fig_pos-8] == "  " )
			return 1;
	}
	return 0;
}


/**************************************************************
 * check all figures of 'opp' whether they attack
 * the given position
 **************************************************************/

function tileIsUnderAttack( &$board, $opp, $dest_pos, $ignore_oppking = FALSE )
{
	for ( $i = 0; $i < 64; $i++ ) {
		if ( $board[$i][0] == $opp ) {
			if ( ($board[$i][1]=='P' && checkPawnAttack($board,$i,$dest_pos)) ||
					 ($board[$i][1]!='P' && 
						    tileIsReachable($board,$board[$i][1],$i,$dest_pos, $ignore_oppking)) ) {
				/*DEBUG: echo "attack test: $i: ",$opp,"P<BR>"; */
				return 1;
			}
		}
	}

	return 0;
}


/**************************************************************
 * check all figures of 'opp' whether they attack
 * the given position and count the attacks
 **************************************************************/

function countTileAttacks( &$board, $opp, $dest_pos )
{
	$result = 0;

	for ( $i = 0; $i < 64; $i++ )
		if ( $board[$i][0] == $opp )
		{
			if ( ($board[$i][1]=='P' && checkPawnAttack($board,$i,$dest_pos)) ||
				 ($board[$i][1]!='P' && 
					    tileIsReachable($board,$board[$i][1],$i,$dest_pos)) ) {
				$result += 1;
			}
		}

	return $result;
}


/**************************************************************
 * check all figures of 'opp' whether they attack
 * the king of player
 **************************************************************/

function kingIsUnderAttack( &$board, $player, $opp )
{
	for ( $i = 0; $i < 64; $i++ )
		if ( $board[$i][0] == $player
				&& $board[$i][1] == 'K' ) {
			$king_pos = $i;
			break;
		}
	/*DEBUG: echo "$player king is at $king_pos<BR>"; */
	
	return tileIsUnderAttack( $board, $opp, $king_pos );
}


/**************************************************************
 * check whether player's king is check mate
 **************************************************************/

function isCheckMate( &$board, $player, $opp )
{
	for ( $i = 0; $i < 64; $i++ )
		if ( $board[$i][0] == $player && $board[$i][1] == 'K' )
		{
			$king_pos = $i;
			$king_x = $i % 8;
			$king_y = floor($i/8);
			break;
		}

	/* test adjacent tiles while king is temporarly removed */
	$adj_tiles = getAdjTiles( $king_pos );
	$contents = $board[$king_pos]; $board[$king_pos] = "  ";

	foreach ( $adj_tiles as $dest_pos ) {
		if ( $board[$dest_pos][0] == $player ) continue;
		if ( tileIsUnderAttack( $board,$opp,$dest_pos) ) continue;
		$board[$king_pos] = $contents;
		return 0;
	}

	$board[$king_pos] = $contents;

	/* DEBUG:  echo "King cannot escape by itself! "; */

	/* get all figures that attack the king */
	$attackers = array(); $count = 0;
	for ( $i = 0; $i < 64; $i++ )
		if ( $board[$i][0] == $opp )
		{
			if ( ($board[$i][1]=='P' && checkPawnAttack($board,$i,$king_pos)) ||
					 ($board[$i][1]!='P' && 
						    tileIsReachable($board,$board[$i][1],$i,$king_pos)) )
			{
					$attackers[$count++] = $i;
			}
		}
	/* DEBUG: 
	for( $i = 0; $i < $count; $i++ )
		echo "Attacker: $attackers[$i] ";
	echo "Attackercount: ",count($attackers), " "; */
 
	/* if more than one there is no chance to escape */
	if ( $count > 1 ) return 1;

	/* check whether attacker can be killed by own figure */
	$dest_pos = $attackers[0];
	for ( $i = 0; $i < 64; $i++ )
		if ( $board[$i][0] == $player )
		{
			if ( ($board[$i][1]=='P' && checkPawnAttack($board,$i,$dest_pos)) ||
					 ($board[$i][1]!='P' && $board[$i][1]!='K' &&
						  tileIsReachable($board,$board[$i][1],$i,$dest_pos)) ||
					 ($board[$i][1]=='K' && 
						  tileIsReachable($board,$board[$i][1],$i,$dest_pos) &&
						  !tileIsUnderAttack( $board,$opp,$dest_pos)) )
			{
				/* DEBUG: echo "candidate: $i "; */
				$can_kill_atk = 0;
				$contents_def = $board[$i];
				$contents_atk = $board[$dest_pos];
				$board[$dest_pos] = $board[$i];
				$board[$i] = "  ";
				if ( !tileIsUnderAttack( $board,$opp,$king_pos) )
					$can_kill_atk = 1;
				$board[$i] = $contents_def;
				$board[$dest_pos] = $contents_atk;
				if ( $can_kill_atk )
				{
					/* DEBUG: echo "$i can kill attacker"; */
					return 0;
				}    
			}
		}
 
	/* check whether own unit can block the way */
	
	/* if attacking unit is a knight there
	 * is no way to block the path */
	if ( $board[$dest_pos][1] == 'N' ) return 1;

	/* if enemy is adjacent to king there is no
	 * way either */
	$dest_x = $dest_pos % 8;
	$dest_y = floor($dest_pos/8);
	if ( abs($dest_x-$king_x)<=1 && abs($dest_y-$king_y)<=1 )
		return 1;

	/* get the list of tiles between king and attacking
	 * unit that can be blocked to stop the attack */
	$change = getPathChange($board[$dest_pos][1],$dest_pos,$king_pos);
	/* DEBUG:  echo "path change: $change "; */
	$path = getPath($dest_pos+$change,$king_pos,$change);
	/* DEBUG: foreach( $path as $tile ) echo "tile: $tile "; */
	foreach( $path as $pos )
	{
		for ( $i = 0; $i < 64; $i++ )
			if ( $board[$i][0] == $player )
			{
				if ( ($board[$i][1]=='P' && checkPawnMove($board, $i,$pos)) ||
						 ($board[$i][1]!='P' && $board[$i][1]!='K' &&
						      tileIsReachable($board,$board[$i][1],$i,$pos)) ) {
						$board[$pos] = $board[$i];
						$old = $board[$i]; 
						$board[$i] = "  ";
						$is_bound = kingIsUnderAttack($board, $player, $opp );
						$board[$i] = $old;
						$board[$pos] = "  ";

						if ( !$is_bound ) {
						    /* DEBUG: echo "$i can block "; */
						    return 0;
						}
				}
			}
	}
	return 1;
}


/**************************************************************
 * HACK: this function checks whether en-passant is possible
 **************************************************************/

function en_passant_okay( &$board, $player, $pos, $dest, $opp_ep_flag )
{
		if ( $opp_ep_flag != 'x' )
		if ( $dest%8 == $opp_ep_flag )
		/* if ( checkPawnAttack($board,$pos,$dest) ) right now this is not required as we only use this
						      function in isStaleMate which uses correct dests */
		if ( ($player=='w' && floor($dest/8)==5) ||
				 ($player=='b' && floor($dest/8)==2) )
			return 1;
		return 0;
}


/**************************************************************
 * move chessman from pos to dest, check whether king is under attack and restore
 *	 the old board settings. whether pos -> dest is a valid move is NOT checked!
 **************************************************************/

function move_is_okay( &$board, $player, $opp, $pos, $dest )
{
	/* DEBUG: echo "$player-$opp: $pos -> $dest: "; */
	$old_pos = $board[$pos];
	$old_dest = $board[$dest];
	$board[$dest] = $board[$pos];
	$board[$pos] = "  ";
	if ( kingIsUnderAttack($board, $player, $opp ) ) $ret = 0; else $ret = 1;
	$board[$pos] = $old_pos;
	$board[$dest] = $old_dest;
	/* DEBUG: echo "$ret<BR>"; */
	return $ret;
}


/**************************************************************
 * check whether there is no further move possible
 **************************************************************/

function isStaleMate( &$board, $player,$opp, $w_ep, $b_ep /*line of en-passant*/ )
{
	for ( $i = 0; $i < 64; $i++ )
		if ( $board[$i][0] == $player ) {
			/* can the figure move theoretically thus is there at least
				 one tile free for one figure ? */
			switch ($board[$i][1] )
			{
				case 'K':
					$adj_tiles = getAdjTiles( $i );
					foreach ( $adj_tiles as $pos ) {
						if ( $board[$pos][0] == $player ) continue;
						if ( tileIsUnderAttack($board,$opp,$pos,TRUE) ) continue;
						return 0;
					}
					/* DEBUG: echo "King cannot escape by itself! "; */
					break;
				case 'P':
					if ( $player == 'w' ) {
						if ( $board[$i+8] == "  " && move_is_okay($board,$player,$opp,$i,$i+8) ) return 0;
						if ( ($i%8) > 0 && ($board[$i+7][0] == $opp || en_passant_okay($board,'w',$i,$i+7,$b_ep)) ) 
						  if ( move_is_okay($board,$player,$opp,$i,$i+7) )
						    return 0;
						if ( ($i%8) < 7 && ($board[$i+9][0] == $opp || en_passant_okay($board,'w',$i,$i+9,$b_ep)) ) 
						  if ( move_is_okay($board,$player,$opp,$i,$i+9) )
						    return 0;
					}
					else {
						if ( $board[$i-8] == "  " && move_is_okay($board,$player,$opp,$i,$i-8) ) return 0;
						if ( ($i%8) > 0 && ($board[$i-9][0] == $opp || en_passant_okay($board,'b',$i,$i-9,$w_ep)) ) 
						  if ( move_is_okay($board,$player,$opp,$i,$i-9) )
						    return 0;
						if ( ($i%8) < 7 && ($board[$i-7][0] == $opp || en_passant_okay($board,'b',$i,$i-7,$w_ep)) ) 
						  if ( move_is_okay($board,$player,$opp,$i,$i-7) ) 
						    return 0;
					}
					break;
				case 'B':
					if ( $i-9 >= 0  && $board[$i-9][0] != $player && move_is_okay($board,$player,$opp,$i,$i-9) ) return 0;
					if ( $i-7 >= 0  && $board[$i-7][0] != $player && move_is_okay($board,$player,$opp,$i,$i-7) ) return 0;
					if ( $i+9 <= 63 && $board[$i+9][0] != $player && move_is_okay($board,$player,$opp,$i,$i+9) ) return 0;
					if ( $i+7 <= 63 && $board[$i+7][0] != $player && move_is_okay($board,$player,$opp,$i,$i+7) ) return 0;
					break;
				case 'R':
					if ( $i-8 >= 0  && $board[$i-8][0] != $player && move_is_okay($board,$player,$opp,$i,$i-8) ) return 0;
					if ( $i-1 >= 0  && $board[$i-1][0] != $player && move_is_okay($board,$player,$opp,$i,$i-1) ) return 0;
					if ( $i+8 <= 63 && $board[$i+8][0] != $player && move_is_okay($board,$player,$opp,$i,$i+8) ) return 0;
					if ( $i+1 <= 63 && $board[$i+1][0] != $player && move_is_okay($board,$player,$opp,$i,$i+1) ) return 0;
					break;
				case 'Q':
					$adj_tiles = getAdjTiles( $i );
					foreach ( $adj_tiles as $pos )
						if ( $board[$pos][0] != $player ) 
						  if ( move_is_okay($board,$player,$opp,$i,$pos) )
						    return 0;
					break;
				case 'N':
					if ( $i-17 >= 0  && $board[$i-17][0] != $player && move_is_okay($board,$player,$opp,$i,$i-17) ) return 0; 
					if ( $i-15 >= 0  && $board[$i-15][0] != $player && move_is_okay($board,$player,$opp,$i,$i-15) ) return 0;
					if ( $i-6  >= 0  && $board[$i-6][0]  != $player && move_is_okay($board,$player,$opp,$i,$i-6) ) return 0;
					if ( $i+10 <= 63 && $board[$i+10][0] != $player && move_is_okay($board,$player,$opp,$i,$i+10) ) return 0;
					if ( $i+17 <= 63 && $board[$i+17][0] != $player && move_is_okay($board,$player,$opp,$i,$i+17) ) return 0;
					if ( $i+15 <= 63 && $board[$i+15][0] != $player && move_is_okay($board,$player,$opp,$i,$i+15) ) return 0;
					if ( $i+6  <= 63 && $board[$i+6][0]  != $player && move_is_okay($board,$player,$opp,$i,$i+6) ) return 0;
					if ( $i-10 >= 0  && $board[$i-10][0] != $player && move_is_okay($board,$player,$opp,$i,$i-10) ) return 0;
					break;
			}
		}

	return 1;
}


/**************************************************************
 * allow normal chess notation and translate into a full
 * move description which is then parsed. return an error
 * if any and store the result in global ac_move
 **************************************************************/

function completeMove( &$board, &$ac_move, &$w_figures, &$b_figures, &$browsing_mode,
			 $player, $move )
{
	/*
	 * [a-h][1-8|a-h][RNBQK]              pawn move/attack
	 * [PRNBQK][a-h][1-8]                 figure move 
	 * [PRNBQK][:x][a-h][1-8]             figure attack
	 * [PRNBQK][1-8|a-h][a-h][1-8]        ambigous figure move
	 * [a-h][:x][a-h][1-8][[RNBQK]        ambigous pawn attack 
	 * [PRNBQK][1-8|a-h][:x][a-h][1-8]    ambigous figure attack
	 */
	$error = "format is totally unknown!";

	/* strip away # from possible PGN move */
	if ( $move[strlen($move)-1] == '#' )
		$move = substr( $move, 0, strlen($move)-1 );

	$ac_move = $move;

	if ( strlen($move)>=6 ) {
		/* full move: a pawn requires a ? in the end
		 * to automatically choose a queen on last line */
		if ( $move[0] == 'P' )
		if ( $move[strlen($move)-1]<'A' || $move[strlen($move)-1]>'Z' )
			$ac_move = "$move?";
		return "";
	}

	/* allow last letter to be a capital one indicating
	 * the chessmen a pawn is supposed to transform into,
	 * when entering the last file. we split this character
	 * to keep the autocompletion process the same. */
	$pawn_upg = "?";
	if ( $move[strlen($move)-1]>='A' && $move[strlen($move)-1]<='Z' ) {
		$pawn_upg = $move[strlen($move)-1];
		$move = substr( $move, 0, strlen($move)-1 );
	}
	if ( $pawn_upg == "P" || $pawn_upg == "K" )
		return "A pawn may only become either a knight, a bishop, a rook or a queen!";

	if ( $move[0]>='a' && $move[0]<='h' ) {
		/* pawn move. either it's 2 or four characters as 
		 * listed above */
		if ( strlen($move) == 4 ) {
			if ( $move[1] != 'x' )
				return "use x to indicate an attack";
			$dest_x = $move[2];
			$dest_y = $move[3];
			$src_x  = $move[0];
			if ( $player == 'w' )
				$src_y  = $dest_y-1;
			else
				$src_y  = $dest_y+1;
			$ac_move = sprintf( "P%s%dx%s%d%s", 
					      $src_x,$src_y,$dest_x,$dest_y,
					      $pawn_upg );
			return "";
		}
		else if (strlen($move) == 2 ) {
			$fig = sprintf( "%sP", $player );
			if ( $move[1] >= '1' && $move[1] <= '8' ) {
				$not_found = 0;
				/* pawn move */
				$pos = boardCoordToIndex( $move );
				if ( $pos == 64 ) return "coordinate $move is invalid";
				if ( $player == 'w' )
				{
					while( $pos >= 0 && $board[$pos] != $fig ) $pos -= 8;
					if ( $pos < 0 ) $not_found = 1;
				}
				else
				{
					while( $pos <= 63 && $board[$pos] != $fig ) $pos += 8;
					if ( $pos > 63 ) $not_found = 1;
				}
				$pos = boardIndexToCoord( $pos );
				if ( $not_found || $pos == "" )
					return "cannot find $player pawn in column $move[0]";
				else {
					$ac_move = sprintf( "P%s-%s%s", $pos, $move, $pawn_upg );
					return "";
				}
			}
			else {
				/* notation: [a-h][a-h] for pawn attack no longer allowed 
				 * except for history browser */
				if ( $browsing_mode == 0 )
					return "please use denotation [a-h]x[a-h][1-8] for pawn attacks (see help for more information)";
				/* pawn attack must be only one pawn in column! */
				$pawns = 0;
				$start = boardCoordToIndex( sprintf( "%s1", $move[0] ) );
				if ( $start == 64 ) return "coordinate $move[0] is invalid";
				for ( $i = 1; $i <= 8; $i++, $start+=8 )
					if ( $board[$start] == $fig ) 
					{
						$pawns++;
						$pawn_line = $i;
					}
				if ( $pawns == 0 )
					return "there is no pawn in column $move[0]";
				else if ( $pawns > 1 )
					return "there is more than one pawn in column $move[0]";
				else {
					if ( $player == 'w' )
						$dest_line = $pawn_line+1;
					else
						$dest_line = $pawn_line-1;
					$ac_move = sprintf( "P%s%dx%s%d", 
						                $move[0],$pawn_line,$move[1],$dest_line );
					return "";
				}
			}
		}
	}
	else {
		/* figure move */
		$dest_coord = substr( $move, strlen($move)-2, 2 );
		$action     = "";

		if ( strlen($move) >= 3 ) {
			$action = $move[strlen($move)-3];
		}

		if ( $action != 'x' ) $action = '-';
		if ( $player == 'w' ) 
			$figures = $w_figures;
		else
			$figures = $b_figures;

		$fig_count = 0;
		foreach( $figures as $figure ) {
			if ( $figure[0] == $move[0] )
			{
				$fig_count++;
				if ( $fig_count == 1 )
					$pos1 = substr( $figure, 1, 2 );
				else
					$pos2 = substr( $figure, 1, 2 );
			}
		}

		if ( $fig_count == 0 ) {
			return sprintf( "there is no figure %s = %s", 
					  $move[0], getFullFigureName($move[0]) );
		}
		else if ( $fig_count == 1 ) {
			 $ac_move = sprintf( "%s%s%s%s",
					  $move[0], $pos1, $action, $dest_coord ); 
			 return "";
		}
		else {
			$fig1_can_reach = 0;
			$fig2_can_reach = 0;

			/* two figures which may cause ambiguity */
			$dest_pos = boardCoordToIndex( $dest_coord );
			if ( $dest_pos == 64 ) 
				return "coordinate $dest_coord is invalid";
			if ( tileIsReachable($board, $move[0], boardCoordToIndex($pos1), $dest_pos ) )
				$fig1_can_reach = 1;
			if ( tileIsReachable($board, $move[0], boardCoordToIndex($pos2), $dest_pos ) )
				$fig2_can_reach = 1;
			if ( !$fig1_can_reach && !$fig2_can_reach )
				return sprintf( "neither of the %s = %s can reach %s",
						    $move[0], getFullFigureName($move[0]),
						    $dest_coord );
			else
			if ( $fig1_can_reach && $fig2_can_reach )
			{
				/* ambiguity - check whether a hint is given */
				if ( ($action=='-' && strlen($move)==4) ||
						 ($action=='x' && strlen($move)==5) )
					$hint = $move[1];
				if ( empty($hint) )
					return sprintf( "both of the  %s = %s can reach %s",
						              $move[0], getFullFigureName($move[0]),
						              $dest_coord );
				else {
					$move_fig1 = FALSE;
					$move_fig2 = FALSE;

					if ( $hint>='1' && $hint<='8' ) {
						if ( $pos1[1]==$hint && $pos2[1]!=$hint )
						  $move_fig1 = 1;
						if ( $pos2[1]==$hint && $pos1[1]!=$hint )
						  $move_fig2 = 1;
					}
					else {
						if ( $pos1[0]==$hint && $pos2[0]!=$hint )
						  $move_fig1 = 1;
						if ( $pos2[0]==$hint && $pos1[0]!=$hint )
						  $move_fig2 = 1;
					}

					if ( !$move_fig1 && !$move_fig2 )
						return "ambiguity is not properly resolved";

					if ( $move_fig1 )
						$ac_move = sprintf( "%s%s%s%s",
						              $move[0], $pos1, $action, $dest_coord );
					else
						$ac_move = sprintf( "%s%s%s%s",
						              $move[0], $pos2, $action, $dest_coord );
					return;
				}
			}
			else
			{
				if ( $fig1_can_reach )
					$ac_move = sprintf( "%s%s%s%s",
						            $move[0], $pos1, $action, $dest_coord ); 
				else
					$ac_move = sprintf( "%s%s%s%s",
						            $move[0], $pos2, $action, $dest_coord ); 
				return "";
			}
		}
	}

	return $error;
}


/**************************************************************
 * a hacky function that uses autocomplete to short
 * a full move. if this fails there is no warning
 * but the move is kept anchanged
 **************************************************************/

function convertFullToChessNotation(&$board, &$w_figures, &$b_figures, $player, $move, &$ac_move)
{
	$new_move = $move;

	$old_ac_move = $ac_move; /* backup required as autocomplete
				    will overwrite it */
						      
	/* valid pawn moves are always non-ambigious */
	if ( $move[0] == 'P' )
	{
		/* skip P anycase. for attacks skip source digit
			 and for moves skip source pos and - */
		if ( $move[3] == '-' ) {
			if ( $move[1] == $move[4] ) {
				// Forward standard move
				$new_move = substr( $move, 4 );
			}
			else {
				// En passant
				$new_move = sprintf("%sx%s", $move[1], substr( $move, 4 ) );
			}
		}
		else if ( $move[3] == 'x' ) {
			$new_move = sprintf("%s%s", $move[1], substr( $move, 3 ) );
		}
	}
	else
	{
		/* try to remove the source position and check whether it
		 * is a non-ambigious move. if it is add one of the components
		 * and check again */
		if ( $move[3] == '-' )
			$dest = substr( $move, 4 );
		else if ( $move[3] == 'x' )
			$dest = substr( $move, 3 );
		else
			$dest = '';

		$new_move = sprintf("%s%s", $move[0], $dest );
		if ( completeMove($board, $ac_move, $w_figures, $b_figures, $browsing_mode, $player,$new_move) != "" )
		{
			/* add a component */
			$new_move = sprintf("%s%s%s", $move[0], $move[1], $dest );
			if ( completeMove($board, $ac_move, $w_figures, $b_figures, $browsing_mode, $player,$new_move) != "" )
			{
				/* add other component */
				$new_move = sprintf("%s%s%s", $move[0], $move[2], $dest );
				if ( completeMove($board, $ac_move, $w_figures, $b_figures, $browsing_mode, $player,$new_move) != "" )
					 $new_move = $move; /* give up */
			}
		}
	}
	
	$ac_move = $old_ac_move;

	LogDebug("=============== convertFullToChessNotation ================");
	LogDebug("Move: $move");
	LogDebug("ac_move: $ac_move");
	LogDebug("old_ac_move: $old_ac_move");
	LogDebug("new_move: $new_move");
	LogDebug("===========================================================");

	return $new_move;
}


function saveGame ( &$board, &$game, $headline, $comment )
{
	$game[1] = implode(" ", $headline);
	$game[2] = "";
	$game[3] = "";

	for ( $i = 0; $i < 64; $i++ ) {
		if ( $board[$i] != "  " && substr($board[$i],0,1) == "w" ) {
			$fig = substr($board[$i],1,1);
			$coord = boardIndexToCoord($i);
			$game[2] .= "$fig$coord ";
		}
	}

	$game[2] .= "\n";

	for ( $i = 0; $i < 64; $i++ ) {
		if ( $board[$i] != "  " && substr($board[$i],0,1) == "b" ) {
			$fig = substr($board[$i],1,1);
			$coord = boardIndexToCoord($i);
			$game[3] .= "$fig$coord ";
		}
	}

	$game[3] .= "\n";
}


/**************************************************************
 * check whether it is user's turn and the move is valid. 
 * if the move is okay update the game file.
 **************************************************************/

function handleMove( &$game, &$board, &$w_figures, &$b_figures, $username, &$ac_move, &$res_games,
			$move, $comment, &$current_game, $p_updatestats = TRUE )
{
	/* DEBUG: echo "HANDLE: $move, $comment<BR>"; */

	$result = "undefined";
	$move_handled = 0;
	$draw_handled = 0;

	/* get number of move and color of current player and 
	 * whether castling is possible. */
	$headline = explode( " ", trim($game[1]) );
	$player_w = $headline[0];
	$player_b = $headline[1];
	$cur_move = $headline[2];
	$cur_player = $headline[3]; /* b or w */

	/*
	if ( ($cur_player=="w" && $username!=$player_w) ||
			 ($cur_player=="b" && $username!=$player_b) ) {
		LogDebug("cur_player = $cur_player");
		LogDebug("player_w = $player_w");
		LogDebug("player_b = $player_b");
		LogDebug("username = $username");

		return "It is not your turn!";
	}
	*/

	if ( $cur_player == "w" ) {
		$cur_opp = "b";
	}
	else {
		$cur_opp = "w";
	}

	if ( $headline[4] != "?" && $headline[4] != "D" ) { 
		return "This game is over. It is not 
			possible to enter any further moves.";
	}

	/* headline castling meaning: 0 - rook or king moved
						                    1 - possible
						                    9 - performed */
	if ( $cur_player=="w" ) {
		$may_castle_short = $headline[5];
		$may_castle_long  = $headline[6];
	}
	else {
		$may_castle_short = $headline[7];
		$may_castle_long  = $headline[8];
	}
	
	/* DEBUG echo "HANDLE: w=$player_w, b=$player_b, c=$cur_player, ";
	echo "m=$cur_move, may_castle=$may_castle_short, ";
	echo "$may_castle_long  <BR>";*/
	
	/* fill chess board */
	fillChessBoard($board, $w_figures, $b_figures, trim($game[2]), trim($game[3]) );

	// XBoard commands    ex: d2d4
	// We WANT to be compatible to use most chess engines...
	//   so we convert it to standard pawn/piece move

	if ( ( strlen($move) == 4 || strlen($move) == 5 ) &&
		$move[0]>='a' && $move[0]<='h' &&
		$move[2]>='a' && $move[2]<='h' &&
		$move[1]>='1' && $move[1]<='8' &&
		$move[3]>='1' && $move[3]<='8' ) {

		$src_idx = boardCoordToIndex(substr($move, 0, 2));
		$dst_idx = boardCoordToIndex(substr($move, 2, 2));

		$piece = $board[$src_idx][1];

		if ( $piece == 'P' && strlen($move) == 5 ) {
			$prom = strtoupper(substr($move, 4, 1));
		}
		else {
			$prom = '';
		}

		// Check for en-passant
		$en_passant = FALSE;

		if ( $piece == 'P' && $move[0] != $move[2] && $board[$dst_idx] == '  ' ) {
			$en_passant = TRUE;
		}

		if ( $board[$dst_idx] == '  ' && ! $en_passant ) {
			$move = $piece . substr($move, 0, 2) . "-" . substr($move, 2, 2) . $prom;
		}
		else {
			$move = $piece . substr($move, 0, 2) . "x" . substr($move, 2, 2) . $prom;
		}

		LogDebug("XBOARD: Built move $move");
		$ac_move = $move;
	}

	/* allow two-step of king to indicate castling */
	if ( $cur_player == 'w' && $move == "Ke1-g1" )
		$move = CASTLE_SHORT;
	else
	if ( $cur_player == 'w' && $move == "Ke1-c1" )
		$move = CASTLE_LONG;
	else
	if ( $cur_player == 'b' && $move == "Ke8-g8" )
		$move = CASTLE_SHORT;
	else
	if ( $cur_player == 'b' && $move == "Ke8-c8" )
		$move = CASTLE_LONG;
	/* accept --- although it is now called resign */
	if ( $move == "---" || $move == "resign" )
		$move = "resigned";
 
	/* backup full move input for game history before
	 * splitting figure type apart */
	$history_move = $move;
	 
	/* clear last move - won't be saved yet if anything 
		 goes wrong */
	$headline[11] = "x";
	$headline[12] = 'x';
		
	/* HANDLE MOVES:
	 * resign                            resign
	 * O-O                               short castling
	 * O-O-O                             long castling
	 * draw?                             offer a draw
	 * accept_draw                       accept the draw
	 * refuse_draw                       refuse the draw
	 * [PRNBQK][a-h][1-8][-:x][a-h][1-8] unshortened move
	 */
	if ( $move == "DELETE" ) {
		if ( ($cur_player == 'w' && $cur_move == 0) ||
			 ($cur_player == 'b' && $cur_move == 1) ) {
			$current_game->delete();
			$result = "You deleted the game.";
		}
		else {
			$result = "ERROR: You cannot delete a game when you have already moved!";
		}
	}
	else if ( $move == "draw?" && ! $current_game->isDrawOffered() ) {
		$headline[4] = "D";
		$result = "You have offered a draw.";
		$draw_handled = 1;
		$headline[11] = "DrawOffered";
		$current_game->setDrawOffer(TRUE);
	}
	else if ( $move == "refuse_draw" && $current_game->isDrawOffered() ) {
		$headline[4] = "?";
		$draw_handled = 1;
		$result = "You refused the draw.";
		$headline[11] = "DrawRefused";
		$current_game->setDrawOffer(FALSE);
	}
	else if ( $move == "accept_draw" && $current_game->isDrawOffered() ) {
		$headline[4] = "-";
		$draw_handled = 1;
		$result = "You accepted the draw.";
		$headline[11] = "DrawAccepted";
		if ( $headline[3] == "b" ) {
			/* new move started as white offered the draw */
			$headline[2]++;
			$game[3+$headline[2]] = sprintf( "%03d\n", $headline[2] );
		}
		$game[3+$headline[2]] = sprintf( "%s draw\n", 
						 trim($game[3+$headline[2]]) );

		$current_game->draw();
	}
	else if ( $move == "resigned" )
	{
		/* surrender */
		$headline[4] = $cur_opp;
		$result = "You have resigned.";
		$move_handled = 1;
		$headline[11] = "Resignation";

		if ( $cur_opp == 'w' ) {
			$player_opp = $current_game->getWhitePlayer();
		}
		else {
			$player_opp = $current_game->getBlackPlayer();
		}

		$current_game->resignation($player_opp);
	} 
	else if ( $move == CASTLE_SHORT )
	{
		/* short castling */
		if ( $may_castle_short != 1 || $may_castle_long == 9 ) {
			return "ERROR: You cannot castle short any longer!";
		}

		if ( $cur_player=="b" && $board[61]=="  " && $board[62]=="  " )
		{
			if ( kingIsUnderAttack($board, "b", "w" ) ) {
				return "ERROR: You cannot escape check by castling!";
			}

			if ( tileIsUnderAttack( $board, "w", 62 ) || 
					 tileIsUnderAttack( $board, "w", 61 ) ) {
				return "ERROR: Either king or rook would be under attack after short castling!";
			}

			$may_castle_short = 9;
			$board[60] = "  ";
			$board[62] = "bK";
			$board[61] = "bR";
			$board[63] = "  ";
		}

		if ( $cur_player=="w" && $board[5]=="  " && $board[6]=="  " )
		{
			if ( kingIsUnderAttack($board, "w", "b" ) ) {
				return "ERROR: You cannot escape check by castling!";
			}

			if ( tileIsUnderAttack( $board, "b", 5 ) || 
					 tileIsUnderAttack( $board, "b", 6 ) ) {
				return "ERROR: Either king or rook would be under attack after short castling!";
			}

			$may_castle_short = 9;
			$board[4] = "  ";
			$board[6] = "wK";
			$board[5] = "wR";
			$board[7] = "  ";
		}

		if ( $may_castle_short != 9 ) {
			return "ERROR: Cannot castle short because the way is blocked!";
		}

		$result = "Castled short.";
		$move_handled = 1;
		$headline[11] = CASTLE_SHORT;
	}
	else if ( $move == CASTLE_LONG )
	{
		/* long castling */
		if ( $may_castle_long != 1 || $may_castle_short == 9 ) {
			return "ERROR: You cannot castle long any longer!";
		}

		if ( $cur_player=="b"  && $board[57]=="  " &&
				 $board[58]=="  "    && $board[59]=="  " ) {

			if ( kingIsUnderAttack($board, "b", "w" ) ) {
				return "ERROR: You cannot escape check by castling!";
			}

			if ( tileIsUnderAttack( $board, "w", 58 ) || 
					 tileIsUnderAttack( $board, "w", 59 ) ) {
				return "ERROR: Either king or rook would be under attack after short castling!";
			}

			$may_castle_long = 9;
			$board[56] = "  ";
			$board[58] = "bK";
			$board[59] = "bR";
			$board[60] = "  ";
		}

		if ( $cur_player=="w" && $board[1]=="  " && 
				 $board[2]=="  "    && $board[3]=="  " ) {

			if ( kingIsUnderAttack($board, "w", "b" ) ) {
				return "ERROR: You cannot escape check by castling!";
			}

			if ( tileIsUnderAttack( $board, "b", 2 ) || 
					 tileIsUnderAttack( $board, "b", 3 ) ) {
				return "ERROR: Either king or rook would be under attack after short castling!";
			}

			$may_castle_long = 9;
			$board[0] = "  ";
			$board[2] = "wK";
			$board[3] = "wR";
			$board[4] = "  ";
		}

		if ( $may_castle_long != 9 ) {
			return "ERROR: Cannot castle long because the way is blocked!";
		}

		$result = "Castled long.";
		$move_handled = 1;
		$headline[11] = CASTLE_LONG;
	}
	else
	{

		/* [PRNBQK][a-h][1-8][-:x][a-h][1-8][RNBQK] full move */

		/* allow short move description by autocompleting to
		 * full description */
		$ac_error = completeMove($board, $ac_move, $w_figures, $b_figures, $browsing_mode,  $cur_player, trim($move) );
		if ( $ac_error != "" )
			return "ERROR: autocomplete: $ac_error";
		else 
			$move = $ac_move;

		$headline[11] = str_replace( "?", "", $move );
		
		/* a final captial letter may only be N,B,R,Q for the
		 * appropiate chessman */
		$c = $move[strlen($move)-1];
		if ( $c >= 'A' && $c <= 'Z' )
		if ( $c != 'N' && $c != 'B' && $c != 'R' && $c != 'Q' )
			return "ERROR: only N (knight), B (bishop), R (rook) and Q (queen) are valid chessman identifiers";
		
		/* if it is a full move, try to shorten the history move */
		if ( strlen( $history_move ) >= 6 ) {
			$history_move = convertFullToChessNotation($board, $w_figures, $b_figures, $cur_player,$history_move,$ac_move);
			/* DEBUG: echo "Move: $move ($history_move)<BR>"; */
		}
		
		/* validate figure and position */
		$fig_type = $move[0];
		$fig_name = getFullFigureName( $fig_type );
		if ( $fig_name == "empty" )
			return "ERROR: Figure $fig_type is unknown!";
		$fig_coord = substr($move,1,2);
		$fig_pos = boardCoordToIndex( $fig_coord );
		if ( $fig_pos == 64 ) return "ERROR: $fig_coord is invalid!";
		/* DEBUG  echo "fig_type: $fig_type, fig_pos: $fig_pos<BR>"; */
		if ( $board[$fig_pos] == "  " )
			return "ERROR: Tile $fig_coord is empty.";
		if ( $board[$fig_pos][0] != $cur_player )
			return "ERROR: Figure does not belong to you!";
		if ( $board[$fig_pos][1] != $fig_type )
			return "ERROR: Figure does not exist!";
		
		/* get target index */
		$dest_coord = substr($move,4,2);
		$dest_pos = boardCoordToIndex( $dest_coord );
		if ( $dest_pos == 64 )
			return "ERROR: $dest_coord is invalid!";
		if ( $dest_pos == $fig_pos )
			return "ERROR: Current position and destination are equal!";
		$dest_fig = $board[$dest_pos][1];
		$dest_fig_name = getFullFigureName($dest_fig);
		/* DEBUG  echo "dest_pos: $dest_pos<BR>"; */

		/* get action */
		$action = $move[3];
		if ( $move[3] == "-" ) 
			$action = 'M'; /* move */
		else if ( $move[3] == 'x' )
			$action = 'A'; /* attack */
		else
			return "ERROR: $action is unknown! Please use - for a move
						  and x for an attack.";
		/* replace - with x if this is meant to be en-passant */
		if ( $fig_type == 'P' )
		if ( abs($fig_pos-$dest_pos) == 7 || abs($fig_pos-$dest_pos) == 9 ) 
			$action = 'A'; /* attack */

		/* if attack an enemy unit must be present on tile
		 * and if move then tile must be empty. in both cases
		 * the king must not be checked after moving. */
		 
		/* check whether the move is along a valid path and
		 * whether all tiles in between are empty thus the path
		 * is not blocked. the final destination tile is not 
		 * checked here. */
		if ( $fig_type != 'P' )
		{
				if ( !tileIsReachable($board, $fig_type, $fig_pos, $dest_pos ) )
					return "ERROR: Tile $dest_coord is out of moving range for $fig_name at $fig_coord!";
		}
		else {
			if ( $action == 'M' && !checkPawnMove($board,  $fig_pos, $dest_pos ) )
				return "ERROR: Tile $dest_coord is out of moving range for $fig_name at $fig_coord!";
			if ( $action == 'A' && !checkPawnAttack($board, $fig_pos, $dest_pos ) )
				return "ERROR: Tile $dest_coord is out of attacking range for $fig_name at $fig_coord!";
		}
		 
		$en_passant_okay = 0;
		/* check action */
		if ( $action == 'M' && $board[$dest_pos] != "  " )
				return "ERROR: Tile $dest_coord is occupied. You cannot move there.";
		if ( $action == 'A' && $board[$dest_pos] == "  " ) {
			/* en passant of pawn? */
			if ( $fig_type == 'P' ) {
				if ( $cur_player == 'w' )
				{
					if ( $headline[10] != 'x' )
					if ( $dest_pos%8 == $headline[10] )
					if ( floor($dest_pos/8) == 5 )
						$en_passant_okay = 1;
				}
				else
				{
					if ( $headline[9] != 'x' )
					if ( $dest_pos%8 == $headline[9] )
					if ( floor($dest_pos/8) == 2 )
						$en_passant_okay = 1;
				}
				if ( $en_passant_okay == 0 )
					return "ERROR: En-passant is not possible!";
			}
			else
				return "ERROR: Tile $dest_coord is empty. You cannot attack it.";
		}
		if ( $action == 'A' && $board[$dest_pos][0] == $cur_player )
			return "ERROR: You cannot attack own unit at $dest_coord.";
			
		/* backup affected tiles */
		$old_fig_tile = $board[$fig_pos];
		$old_dest_tile = $board[$dest_pos];

		/* perform move */
		$board[$fig_pos] = "  ";
		if ( $board[$dest_pos] != "  " )
			$headline[12] = sprintf("%s%s",$board[$dest_pos],$dest_pos);
		$board[$dest_pos] = "$cur_player$fig_type";
		if ( $en_passant_okay ) {
			/* kill pawn */
			if ( $cur_player == 'w' ) 
			{
				$board[$dest_pos-8] = "  ";
				$headline[12] = sprintf("bP%s",$dest_pos-8);
			}
			else
			{
				$board[$dest_pos+8] = "  ";
				$headline[12] = sprintf("wP%s",$dest_pos+8);
			}
		}

		/* check check :) */
		if ( kingIsUnderAttack($board, $cur_player, $cur_opp ) )
		{
			$board[$fig_pos] = $old_fig_tile;
			$board[$dest_pos] = $old_dest_tile;
			if ( $en_passant_okay ) {
			 /* respawn en-passant pawn */
				if ( $cur_player == 'w' ) 
					$board[$dest_pos-8] = "bP";
				else
					$board[$dest_pos+8] = "wP";
			}
			return "ERROR: Move is invalid because king would be under attack then.";
		}

		/* check whether this forbids any castling */
		if ( $fig_type == 'K' ) {
			if ( $may_castle_short == 1 )
				$may_castle_short = 0;
			if ( $may_castle_long == 1 )
				$may_castle_long  = 0;
		}
		if ( $fig_type == 'R' ) {
			if ( $may_castle_long == 1 && ($fig_pos%8) == 0 )
				$may_castle_long = 0;
			if ( $may_castle_short == 1 && ($fig_pos%8) == 7 )
				$may_castle_short = 0;
		}

		/* if a pawn moved two tiles this will allow 'en passant'
		 * for next turn. */
		if ( $fig_type == 'P' && abs($fig_pos-$dest_pos) == 16 )
		{
			if ( $cur_player == 'w' )
				$headline[9]  = $fig_pos%8;
			else
				$headline[10] = $fig_pos%8;
		}
		else 
		{
			/* clear 'en passant' of OUR last move */
			if ( $cur_player == 'w' )
				$headline[9]  = 'x';
			else
				$headline[10] = 'x';
		}
	
		if ($action == 'M' )
			$result = "$fig_name moved from $fig_coord to $dest_coord.";
		else
			$result = "$fig_name attacked $dest_fig_name at $dest_coord from $fig_coord.";
		$result = ucfirst($result);
		
		/* if pawn reached last line convert into a queen */
		if ( $fig_type == 'P' )
		{
			if ( ($cur_player=='w' && $dest_pos>= 56) || 
					 ($cur_player=='b' && $dest_pos<= 7 ) )
			{
				$pawn_upg = $move[strlen($move)-1];
				if ( $pawn_upg == '?' ) 
				{
					$pawn_upg = 'Q';
					$history_move = sprintf( "%sQ", $history_move );
				}
				$board[$dest_pos] = "$cur_player$pawn_upg";
				$result = sprintf( "%s.. and became a %s!", 
						               $result, getFullFigureName( $pawn_upg ) );
			}
		}
	
		$move_handled = 1;
	}
	
	/* if a legal move was performed test whether you
	 * check the opponent or even check-mate him. then 
	 * update castling and en-passant flags, select the
	 * next player and add the move to the history. */
	if ( $move_handled ) 
	{
		$mate_type = 0;

		$current_game->game_board_history[] = $board;

		if ( count_occurences($board, $current_game->game_board_history) >= 3 ) {
			$headline[4] = "-";
			$mate_type = 3;
			$current_game->draw();
		}
		else if ( count_occurences("  ", $board) == 62 ) {
			$headline[4] = "-";
			$mate_type = 3;
			$current_game->draw();
		}
		else if ( kingIsUnderAttack($board, $cur_opp, $cur_player ) ) {
			/* if this is check mate finish the game. if not
			 * just add a + to the move. */
			if ( isCheckMate($board, $cur_opp, $cur_player ) ) {
				$headline[4] = $cur_player;
				$mate_type = 1;
			}
			else {
				$result = "$result.. CHECK!";
			}

			$history_move = sprintf( "%s+", $history_move );
		}
		else if ( isStaleMate($board, $cur_opp, $cur_player, $headline[9], $headline[10] ) ) {
			$headline[4] = "-";
			$mate_type = 2;
		}

		/* store possible castling modification */
		if ( $cur_player=="w" ) {
			$headline[5] = $may_castle_short;
			$headline[6] = $may_castle_long;
		}
		else {
			$headline[7] = $may_castle_short;
			$headline[8] = $may_castle_long;
		}
	 
		/* update move and current player in headline and
		/* save move */
		if ( $headline[3] == "w" ) {
			/* new move started */
			$headline[2]++;
			$game[3+$headline[2]] = sprintf( "%03d\n", $headline[2] );
			/* DEBUG: echo $game[3+$headline[2]]; */
		}

		$game[3+$headline[2]] = sprintf( "%s %s\n", 
						 trim($game[3+$headline[2]]),
						 $history_move );
		/* if other player can't any more moves end the
		 * game and enter his move automatically */
		if ( $mate_type > 0 ) {
			if ( $mate_type == 1 ) {
				$mate_name = "mate";
				$result = "$result.. CHECKMATE!";
			}
			else if ( $mate_type == 2 ) {
				$mate_name = "stalemate";
				$result = "$result.. STALEMATE!";
			}
			else {
				$mate_name = "positionaldraw";
				$result = "$result.. POSITIONAL DRAW!";
			}

			if ( $headline[3] == "b" ) {
				/* new move started */
				$headline[2]++;
				$game[3+$headline[2]] = sprintf( "%03d\n", $headline[2] );
			}

			$game[3+$headline[2]] = sprintf( "%s %s\n", 
							   trim($game[3+$headline[2]]),
							   $mate_name );
		}
	}

	if ( $move_handled || $draw_handled )
	{
		/* update stats when game is over. includes resignation */
		if ( $p_updatestats && $headline[4] != "?" && $headline[4] != "D" ) {
			updateStats( $headline[0], $headline[1], $headline[4] );
		}

		/* set next player */
		if ( $headline[3] == "b" )
			$headline[3] = "w";
		else
			$headline[3] = "b";
 
		/* save game */
		saveGame($board, $game, $headline, $comment);
	}

	return $result;
}

/* perform a move without any checks. used for the animated
 * chessboard. the provided move history must be completely
 * valid. */
function findFigure( $figures, $name )
{
	for ( $i = 0; $i < count($figures); $i++ )
		if ( $figures[$i] == $name ) return $i;
	return -1;
}

function quickMove( &$board, &$w_figures, &$b_figures, $player, $move, $src, $dest )
{
	$fig_changed = false;
	$kill = 0;

	if ( $player == 'w' )
	{
		$figures = $w_figures;
		$opp_figures = $b_figures;
	}
	else
	{
		$figures = $b_figures;
		$opp_figures = $w_figures;
	}
		
	if ( $move == CASTLE_SHORT )
	{
		if ( $player == 'w' )
		{
			$board[4] = "  ";
			$board[6] = "wK";
			$board[5] = "wR";
			$board[7] = "  ";
			$i = findFigure( $figures, "Ke1" );
			$j = findFigure( $figures, "Rh1" );
			if ( $i >= 0 && $j >= 0 )
			{
				$figures[$i] = "Kg1";
				$figures[$j] = "Rf1";
				$fig_changed = true;
			}
		}
		else
		{
			$board[60] = "  ";
			$board[62] = "bK";
			$board[61] = "bR";
			$board[63] = "  ";
			$i = findFigure( $figures, "Ke8" );
			$j = findFigure( $figures, "Rh8" );
			if ( $i >= 0 && $j >= 0 )
			{
				$figures[$i] = "Kg8";
				$figures[$j] = "Rf8";
				$fig_changed = true;
			}
		}
	}
	else
	if ( $move == CASTLE_LONG )
	{
		if ( $player == 'w' )
		{
			$board[0] = "  ";
			$board[2] = "wK";
			$board[3] = "wR";
			$board[4] = "  ";
			$i = findFigure( $figures, "Ke1" );
			$j = findFigure( $figures, "Ra1" );
			if ( $i >= 0 && $j >= 0 )
			{
				$figures[$i] = "Kc1";
				$figures[$j] = "Rd1";
				$fig_changed = true;
			}
		}
		else
		{
			$board[56] = "  ";
			$board[58] = "bK";
			$board[59] = "bR";
			$board[60] = "  ";
			$i = findFigure( $figures, "Ke8" );
			$j = findFigure( $figures, "Ra8" );
			if ( $i >= 0 && $j >= 0 )
			{
				$figures[$i] = "Kc8";
				$figures[$j] = "Rd8";
				$fig_changed = true;
			}
		}
	}
	else
	{
		$name = substr($move,0,3);
		$i = findFigure( $figures, $name );
		if ( $i >= 0 )
		{
			/* pawn promotion? */
			$c = $move[strlen($move)-1];
			if ( $c != 'Q' && $c != 'R' && $c != 'B' && $c != 'N' )
				$c = $move[0];
			else
				$board[$src] = "$player$c";
			
			$figures[$i] = sprintf("%s%s", $c, substr($move,4,2));

			// MCC: This crashes Game->updateLegacyVariables
			//echo "/*$name --> $figures[$i]*/ ";

			/* if this was attack kill figure */
			if ( $move[3] == 'x' )
			{
				$name = sprintf("%s%s",$board[$dest][1],substr($move,4,2));
				$i = findFigure( $opp_figures, $name );
				if ( $i >= 0 ) 
				{
					$opp_figures[$i] = "xxx";
					// MCC: This crashes Game->updateLegacyVariables
					// echo "/*$name --> xxx*/ ";
				}
				if ( $board[$dest] == "  " )
				{
					/* en passant kill */
					if ( $player == 'w' )
					{
						$kill = 100*($dest-8)+7;
						$board[$dest-8] = "  ";
					}
					else
					{
						$kill = 100*($dest+8)+1;
						$board[$dest+8] = "  ";
					}
				}
				else
				{
					if ( $board[$dest][0] == 'w' )
						$kill = 1;
					else
						$kill = 7;
					switch ( $board[$dest][1] )
					{
						case 'P': $kill += 0; break;
						case 'N': $kill += 1; break;
						case 'B': $kill += 2; break;
						case 'R': $kill += 3; break;
						case 'Q': $kill += 4; break;
						case 'K': $kill += 5; break;
					}
				}
			}
			$fig_changed = true;
		}
		$board[$dest] = $board[$src]; $board[$src] = "  ";
	}
	if ( $fig_changed )
	{
		if ( $player == 'w' ) 
		{
			$w_figures = $figures;
			$b_figures = $opp_figures;
		}
		else
		{
			$b_figures = $figures;
			$w_figures = $opp_figures;
		}
	}
	return $kill;
}

?>
