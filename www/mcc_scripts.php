<?php
require("mcc_ajax.php");

function mcc_JS_renderMove() {
return <<<EOT
function isBetween ( Ax, Ay, Bx, By, Px, Py ) {
	result = false;

	Dx = Bx - Ax;
	Dy = By - Ay;

	if ( Px < Math.min(Ax, Bx) || Px > Math.max(Ax, Bx) ||
	     Py < Math.min(Ay, By) || Py > Math.max(Ay, By) ) {
		result = false;
	} else if ( Dx == 0 ) {
		result = (Px == Ax);
	} else if ( Dy == 0 ) {
		result = (Py == Ay);
	} else if ( Math.abs(Dx) == 2 && Math.abs(Dy) == 1 ) {
		result = (Py == Ay || Px == Bx);
	} else if ( Math.abs(Dx) == 1 && Math.abs(Dy) == 2 ) {
		result = (Px == Ax || Py == By);
	} else if ( Math.abs(Dx) == Math.abs(Dy) ) {
		result = (Math.abs(Px - Ax) == Math.abs(Py - Ay));
	}

	return result;
}

function distanceSegment ( Ax, Ay, Bx, By, Px, Py ) {
	if ( Ax == Px && Ay == Py ) {
		result = 0;
	} else if ( Ax == Px && Ay == Py ) {
		result = 3;
	}
	else {
		distance = Math.abs(Ax-Bx) + Math.abs(Ay-By);
		part     = Math.abs(Ax-Px) + Math.abs(Ay-Py);
		result   = 1 + Math.floor(2 * part / distance);
	}

	return result;
}

function renderMove ( w_source, w_dest, b_source, b_dest, lastmovewhite ) {
	if ( w_source > 63 ) {
		/* castling is special */
		w_source = Math.floor(w_source%100);
		w_dest   = Math.floor(w_dest%100);
	}

	if ( b_source > 63 ) {
		/* castling is special */
		b_source = Math.floor(b_source%100);
		b_dest   = Math.floor(b_dest%100);
	}

	w_source_x = Math.floor(w_source % 8);
	w_source_y = Math.floor(w_source / 8);
	w_dest_x = Math.floor(w_dest % 8);
	w_dest_y = Math.floor(w_dest / 8);

	b_source_x = Math.floor(b_source % 8);
	b_source_y = Math.floor(b_source / 8);
	b_dest_x = Math.floor(b_dest % 8);
	b_dest_y = Math.floor(b_dest / 8);

	for ( i = 0; i < 64; i++ ) {
		lx = Math.floor(i % 8);
		ly = Math.floor(i / 8);

		if ( w_source == w_dest ) {
			iswpathcell = false;
		} else {
			iswpathcell = isBetween(w_source_x, w_source_y,
						w_dest_x, w_dest_y,
						lx, ly);
		}

		if ( b_source == b_dest ) {
			isbpathcell = false;
		} else {
			isbpathcell = isBetween(b_source_x, b_source_y,
						b_dest_x, b_dest_y,
						lx, ly);
		}

		iswhitecell = ( (lx + ly%2)%2 == 1 );

		if ( iswpathcell && isbpathcell ) {
			if ( lastmovewhite ) {
				isbpathcell = false;
			}
			else {
				iswpathcell = false;
			}
		}

		classname = 'b_';

		if ( iswpathcell ) {
			classname += 'path_w' + distanceSegment(w_source_x, w_source_y,
								w_dest_x, w_dest_y, lx, ly);
		}
		else if ( isbpathcell ) {
			classname += 'path_b' + distanceSegment(b_source_x, b_source_y,
								b_dest_x, b_dest_y, lx, ly);
		}
		else {
			classname += 'std';
		}

		if ( iswhitecell ) {
			classname += '_white';
		} else {
			classname += '_black';
		}

		cell = document.getElementById('xb' + i);
		cell.setAttribute('class', classname);
		cell.setAttribute('className', classname);
	}
}
EOT;
}


function mcc_JS_movedetails ( $current_game ) {
	$result = "";
	$notavail = "<cite>Not available yet...<\/cite>";

	$result .= "var movedetails = new Array();\n";

	$moves = $current_game->getMoves();

	$moveindex = 0;
	$pl_is_white = TRUE;

	for ( $moveix = 0; $moveix < count($moves); $moveix++ ) {
		$move = $moves[$moveix];

		$hidehints = $current_game->isOpen() && $moveix >= count($moves) - HINTS_HIDING;

		if ( $pl_is_white ) {
			$move_player = $current_game->getWhitePlayer();
		}
		else {
			$move_player = $current_game->getBlackPlayer();
		}

		$details = "<dl>";

		$details .= "<dt>Player {$move_player->getIdentifier()}\\'s move:<\/dt>";
		$details .= "<dd><strong>{$move->getShort()}<\/strong><br \/><br \/>";
		$details .= "{$move->getLong()}<\/dd>";

		$details .= "<dt>Move Rating:<\/dt>";
		if ( ! $hidehints && $move->isAnalysisAvailable() ) {
			$details .= "<dd>{$move->getTeacherRate()}<\/dd>";
		}
		else {
			$details .= "<dd>{$notavail}<\/dd>";
		}

		if ( $move->getShort() != $move->getTeacherMove() ) {
			$details .= "<dt>Suggested move:<\/dt>";

			if ( !$hidehints && $move->isAnalysisAvailable() ) {
				$tmove = $move->getTeacherMove();
				$tpv   = $move->getTeacherPrincipalVariation();

				$details .= "<dd>{$tmove}";

				if ( strlen($tpv) > strlen($tmove) ) {
					$pv = substr($tpv, strlen($tmove));
					$details .= "<br \/><cite> ... $pv<\/cite>";
				}

				$details .= "<\/dd>";
			}
			else {
				$details .= "<dd>{$notavail}<\/dd>";
			}
		}

		$details .= "<\/dl>";

		$result .= "movedetails[$moveindex] = '" . $details . "';\n";
		$moveindex++;
		$pl_is_white = ! $pl_is_white;
	}

	return $result;
}


function mcc_JS_moveslist ( $current_game ) {
	$result = "";

	$game = $current_game->initializeLegacyVariables();
	$result .= "var moves = new Array();\n"; /* moves*3 = src,dest,kill pairs */

	$browsing_mode = 1;
	$ac_move = "";
	$w_figures = array();
	$b_figures = array();
	$board = array( 
		"  ", "  ", "  ", "  ", "  ", "  ", "  ", "  ",
		"  ", "  ", "  ", "  ", "  ", "  ", "  ", "  ",
		"  ", "  ", "  ", "  ", "  ", "  ", "  ", "  ",
		"  ", "  ", "  ", "  ", "  ", "  ", "  ", "  ",
		"  ", "  ", "  ", "  ", "  ", "  ", "  ", "  ",
		"  ", "  ", "  ", "  ", "  ", "  ", "  ", "  ",
		"  ", "  ", "  ", "  ", "  ", "  ", "  ", "  ",
		"  ", "  ", "  ", "  ", "  ", "  ", "  ", "  " );

	/* ***** BUILD HEADER ***** */

	/* HEADER:
	 * white_name black_name turn active_player:[wb]
	 * status:[wb-?] w_short_castle_ok w_long_castle_ok
	 * b_short_castle_ok b_long_castle_ok
	 * w_long_pawn_move:[a-h] b_long_pawn_move:[a-h]
	 *
	 * init 2tile pawn move is only stored for one turn
	 * to enable en passant rule */

	$headline     = explode( " ", trim($game[1]) );
	$player_w     = $headline[0];
	$player_b     = $headline[1];

	$move_id = $headline[2]; /* current number of move */


	/* ***** FILL CHESS BOARD ARRAY ***** */

	fillChessBoard(
		$board, $w_figures, $b_figures,
		"Ra1 Nb1 Bc1 Qd1 Ke1 Bf1 Ng1 Rh1 Pa2 Pb2 Pc2 Pd2 Pe2 Pf2 Pg2 Ph2",
		"Ra8 Nb8 Bc8 Qd8 Ke8 Bf8 Ng8 Rh8 Pa7 Pb7 Pc7 Pd7 Pe7 Pf7 Pg7 Ph7");

	/* ***** BUILD JAVASCRIPT MOVES ***** */

	$js_index = 0; $invalid_move = 0;

	for ( $i = 1, $line = 4; $i <= $move_id; $i++, $line++ ) {
		$moves = explode( " ", trim($game[$line]) );
		for ( $j = 1, $color="w"; $j <= 2; $j++, $color="b" ) {
			if (  count($moves) > $j )
			{
				if ( $moves[$j] == "draw" || $moves[$j] == "mate" ||
					   $moves[$j] == "stalemate" || $moves[$j] == "---" ||
					   $moves[$j] == "resigned" || $moves[$j] == "positionaldraw" ) {
					$result .= "moves[$js_index]=0;";   $js_index++;
					$result .= "moves[$js_index]=0;";   $js_index++;
					$result .= "moves[$js_index]=0;\n"; $js_index++;
					$invalid_move = 1;
					break;
				}

				$src = 0; $dest = 0; $kill = 0;

				if ( $moves[$j][strlen($moves[$j])-1] == "+" ) {
					$moves[$j] = substr($moves[$j],0,strlen($moves[$j])-1);
				}

				if ( $moves[$j] == CASTLE_SHORT ) {
					if ( $color=='w' ) { $src = 407;  $dest = 605;  }
					else               { $src = 6063; $dest = 6261; }

					$ac_move = CASTLE_SHORT;
				}
				else if ( $moves[$j] == CASTLE_LONG ) {
					if ( $color=='w' ) { $src = 400;  $dest = 203;  }
					else               { $src = 6056; $dest = 5859; }

					$ac_move = CASTLE_LONG;
				}
				else {
					$ac_error = completeMove($board, $ac_move, $w_figures,
							$b_figures, $browsing_mode, $color, $moves[$j] );
				}

				if ( $ac_error == "" ) {
					if ($src == 0 && $dest == 0 ) {
						$src  = boardCoordToIndex(substr($ac_move,1,2));
						$dest = boardCoordToIndex(substr($ac_move,4,2));
					}

					/* $src, $dest will not be changed when castling: */
					$kill = quickMove( $board, $w_figures, $b_figures,
								$color, $ac_move, $src, $dest );

					/* modify $dest to reflect chessman promotion if any */
					$c = $moves[$j][strlen($moves[$j])-1];

					if ( $c == 'Q' || $c == 'R' || $c == 'B' || $c == 'N' ) {
						switch ( $c ) {
							case 'N': $dest += 100; break;
							case 'B': $dest += 200; break;
							case 'R': $dest += 300; break;
							case 'Q': $dest += 400; break;
						}
					}
				}
				else {
					$result .= "move_count=$js_index/2;\n";
					$result .= "parse_error='$js_index: $color: $ac_move: $ac_error';\n";
					$invalid_move = 1;
					break;
				}

				$result .= "moves[$js_index]=$src;";    $js_index++;
				$result .= "moves[$js_index]=$dest;";   $js_index++;
				$result .= "moves[$js_index]=$kill;\n"; $js_index++;
			}
		}

		if ( $invalid_move ) {
			break;
		}
	}

	return $result;
}

function mcc_JS_dragndrop() {
	return <<<EOT

function getMouseOffset(target, ev){
	ev = ev || window.event;

	var docPos    = getPosition(target);
	var mousePos  = mouseCoords(ev);
	return { x:mousePos.x - docPos.x, y:mousePos.y - docPos.y };
}

function getPosition(e){
	var left = 0;
	var top  = 0;

	while (e.offsetParent){
		left += e.offsetLeft;
		top  += e.offsetTop;
		e     = e.offsetParent;
	}

	left += e.offsetLeft;
	top  += e.offsetTop;

	return { x:left, y:top };
}

function mouseCoords(e){
	if( typeof( e.pageX ) == 'number' ) {
		//most browsers
		var xcoord = e.pageX;
		var ycoord = e.pageY;
	} else if( typeof( e.clientX ) == 'number' ) {
		//Internet Explorer and older browsers
		//other browsers provide this, but follow the pageX/Y branch
		var xcoord = e.clientX;
		var ycoord = e.clientY;
		var badOldBrowser = ( window.navigator.userAgent.indexOf( 'Opera' ) + 1 ) ||
			( window.ScriptEngine && ScriptEngine().indexOf( 'InScript' ) + 1 ) ||
			( navigator.vendor == 'KDE' );
		if( !badOldBrowser ) {
			if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
				//IE 4, 5 & 6 (in non-standards compliant mode)
				xcoord += document.body.scrollLeft;
				ycoord += document.body.scrollTop;
			} else if( document.documentElement && ( document.documentElement.scrollLeft
				|| document.documentElement.scrollTop ) ) {
				//IE 6 (in standards compliant mode)
				xcoord += document.documentElement.scrollLeft;
				ycoord += document.documentElement.scrollTop;
			}
		}
	}

	return { x: xcoord, y: ycoord };
}

var dnd_dragPiece   = null;
var dnd_mouseOffset = null;
var dnd_originalPos = null;
var dnd_handler = null;
var dnd_lastsourcedata = null;

var dnd_sources = new Array();
var dnd_targets = new Array();

// Low level event handlers

function dnd_mouseUp ( ev ) {
	ev = ev || window.event;

	if ( dnd_handler != null ) {
		var mousePos = mouseCoords(ev);
		targetdata = null;

		for ( i = 0; i < dnd_targets.length; i++ ) {
			targetPos = getPosition(dnd_targets[i].object);
			w = dnd_targets[i].object.offsetWidth;
			h = dnd_targets[i].object.offsetHeight;

			if ( dnd_targets[i].object != dnd_dragPiece
			  && mousePos.x > targetPos.x
			  && mousePos.y > targetPos.y 
			  && mousePos.x < targetPos.x + w
			  && mousePos.y < targetPos.y + h ) {
				targetdata = dnd_targets[i].data;
			}
		}

		if ( ! dnd_handler([dnd_lastsourcedata, targetdata]) ) {
			dnd_dragPiece.style.position = 'absolute';
			dnd_dragPiece.style.left = dnd_originalPos.x + "px";
			dnd_dragPiece.style.top  = dnd_originalPos.y + "px";
		}

		dnd_dragPiece.style.cursor = 'pointer';

		dnd_dragPiece = null;
	}

	return false;
}

function dnd_mouseDown ( ev ) {
	ev = ev || window.event;

	if ( dnd_handler != null ) {
		dnd_dragPiece = ev.target || ev.srcElement;
		dnd_dragPiece.style.cursor = 'move';
		dnd_mouseOffset = getMouseOffset(dnd_dragPiece, ev);
		dnd_originalPos = getPosition(dnd_dragPiece);

		var data = null;

		for ( i = 0; i < dnd_sources.length; i++ ) {
			if ( dnd_sources[i].object == dnd_dragPiece ) {
				data = dnd_sources[i].data;
			}
		}

		dnd_handler([data]);
		dnd_lastsourcedata = data;
	}

	return false;
}

function dnd_mouseMove ( ev ) {
	ev           = ev || window.event;
	var mousePos = mouseCoords(ev);

	if ( dnd_dragPiece != null ) {
		dnd_dragPiece.style.position = 'absolute';
		dnd_dragPiece.style.left = (mousePos.x-dnd_mouseOffset.x) + "px";
		dnd_dragPiece.style.top  = (mousePos.y-dnd_mouseOffset.y) + "px";

		return false;
	}
}

document.onmousemove = dnd_mouseMove;

// Public API

function dnd_registerSource ( objectref, objectdata ) {
	objectref.onmouseup   = dnd_mouseUp;
	objectref.onmousedown = dnd_mouseDown;
	dnd_sources.push({object: objectref, data: objectdata});
}

function dnd_registerTarget ( objectref, objectdata ) {
	dnd_targets.push({object: objectref, data: objectdata});
}

function dnd_setHandler ( h ) {
	dnd_handler = h;
}

EOT;
}

function mcc_JS_smartrefresh ( $p_game, $p_formid = "" ) {
	global $mcc_ajax_interval_fast, $mcc_ajax_interval_slow;
	$url = AjaxRefreshUrl($p_game);

	if ( $p_game->isRealtime() ) {
		$interval = $mcc_ajax_interval_fast;
	}
	else {
		$interval = $mcc_ajax_interval_slow;
	}

	return <<<EOT
var refreshGameId  = '{$p_game->getId()}';
var refreshGameTs  = '{$p_game->getLastMoveDate()}';
var refreshGameUrl = '{$url}';
var refreshFormId  = '{$p_formid}';
var refreshInterval = $interval;

setTimeout("CheckForRefresh()", refreshInterval);

function CheckForRefresh() {
	var request = new XMLHttpRequest();

	request.open("GET", refreshGameUrl, false);
	request.send(null);

	if ( request.readyState == 4 ) {
		var games = request.responseXML.getElementsByTagName("game");
		for ( var i = 0; i < games.length; i++ ) {
			var game = games[i];
			var id = game.getAttribute("id");
			var ts = game.getAttribute("lastMoveTimestamp");

			if ( id == refreshGameId && ts != refreshGameTs ) {
				if ( refreshFormId.length > 0 ) {
					var form = document.getElementById(refreshFormId);
					form.submit();
				} else {
					window.location.reload(false);
				}
			}
		}
	}

	setTimeout("CheckForRefresh()", refreshInterval);
}

EOT;
}

?>
