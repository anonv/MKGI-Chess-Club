<?php

$common_scripts = <<<EOT

<script language="Javascript" type="text/javascript">
</script>

EOT;

//--- Emails ----------------------------------------------------------------

function mcc_template_email_subject ( $p_text ) {
	global $mcc_server_name;

	return "[$mcc_server_name] $p_text";
}


function mcc_template_email_subscription ( $p_player, $p_token )
{
	global $mcc_server_name;
	global $mcc_server_root;

	$id = $p_player->getIdentifier();
	$pw = $p_player->getPassword();
	$va = $p_token->getValue();

	$val_url = "{$mcc_server_root}player_validation.php?validation={$va}";

	$contents = <<<EOT
<p>You have been successfully suscribed to $mcc_server_name.</p>
<p>Your username is &nbsp;<strong>$id</strong>&nbsp; and your password
is &nbsp;<strong>$pw</strong>.</p>

<p>The last step required to confirm your subscription is to click on
the link below, or to copy/paste this address to your web browser...</p>

<p><a href="{$val_url}">{$val_url}</a></p>
EOT;

	return mcc_template_email($p_player, $contents);
}


function mcc_template_email_changeaddress ( $p_player, $p_token )
{
	global $mcc_server_name;
	global $mcc_server_root;

	$id = $p_player->getIdentifier();
	$pw = $p_player->getPassword();
	$va = $p_token->getValue();

	$val_url = "{$mcc_server_root}player_validation.php?validation={$va}";

	$contents = <<<EOT
<p>You have asked to replace your current email address on $mcc_server_name.</p>

<p>You still need to confirm this email address by clicking on
	the link below, or by copy/pasting this address to your web browser...</p>

<p><a href="{$val_url}">{$val_url}</a></p>
EOT;

	return mcc_template_email($p_player, $contents);
}


function mcc_template_email_lostpassword ( $p_player )
{
	global $mcc_server_name;
	global $mcc_server_root;

	$id = $p_player->getIdentifier();
	$pw = $p_player->getPassword();

	$contents = <<<EOT

<p>We send you your $mcc_server_name login informations as someone
   requested it.</p>

<p>Your username : &nbsp; <strong>$id</strong> <br />
<p>Your password : &nbsp; <strong>$pw</strong> <br />

<p>Now you can go to <a href="$mcc_server_root">$mcc_server_root</a> and
	log in to play.</p>

EOT;

	return mcc_template_email($p_player, $contents);
}

function mcc_template_email_invitation ( $p_invitation )
{
	global $mcc_server_root;
	global $mcc_server_name;
	global $mcc_server_email;

	$tickerurl = $mcc_server_root . "mail_ticker.php?invitation=" . $p_invitation->getId();
	$joinurl   = $mcc_server_root . "player_subscription.php?invitation=" . $p_invitation->getId();

	$contents = <<<EOT
	<p style="text-align: center;"><a href="$joinurl"><img src="$tickerurl" alt="Site Logo" /></a></p>
	<p>Hello fellow chess enthusiast,</p>
	<p>$mcc_server_name is a Chess website where users play aginst each other and against chess engines.
		What makes it special is that it doesn't take much time to play since games are always
		saved and everyone can play a few moves, even if you don't have a long time to spend.
	</p>
	<p>You'll have a detailed personal profile which will allow you to be present in our permanent ladder.</p>

	<p>Join today, it's 100% free and fun !</p>
	<p><a href="$joinurl">Click here to join $mcc_server_name</a></p>
EOT;
	return mcc_template_email(NULL, $contents);
}


function mcc_template_email_move_notification ( $p_player, $p_game, $p_dropsoon )
{
	global $mcc_server_root;
	global $mcc_server_name;
	global $mcc_server_email;

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

	$game = $p_game->initializeLegacyVariables();
	$diff = fillChessBoard($board, $w_figures, $b_figures, trim($game[2]), trim($game[3]) );


	$html_board = mcc_template_board($p_game, $p_player == $p_game->getBlackPlayer(),
					BOARDVIEW_STANDARD, FALSE, $board, $diff);

	$html_history  = "<div style=\"border: 1px dotted #A0A0B0; padding: 7px;\">\n";
	$html_history .= mcc_template_move_history($p_game);
	$html_history .= "</div>\n";

	$player_games = new GameSet($p_player);

	$html_gameslist = "";

	if ( count($player_games->getGames()) > 1 ) {
		$player_games->setName("Your current games on $mcc_server_name");
		$html_gameslist  = '<div style="margin-top: 40px;">';
		$html_gameslist .= mcc_template_gameslist($player_games, $p_player);
		$html_gameslist .= "</div>\n";
	}

	$opponent = $p_game->getOpponent($p_player);
	$oppname  = $opponent->getIdentifier();

	$lastmove = $p_game->getLastMove();
	if ( $lastmove ) {
		$movedesc = $lastmove->getLong();
		$chatter  = $lastmove->getChatter();
	}
	else {
		$movedesc = "";
		$chatter  = "";
	}

	$gameurl  = $mcc_server_root . "chess.php?gameid=" . $p_game->getId();

	if ( $lastmove ) {
		$movetext = "<p>Your opponent &nbsp; <strong>$oppname</strong>"
				. " &nbsp; played &nbsp; <strong>$movedesc</strong>.</p>";
	}
	else {
		$movetext = "<p style=\"font-style: italic;\">No moves yet...</p>";
	}

	if ( $chatter != "" ) {
		$movechat  = "";
		$movechat .= "<div style=\"background: #F2F2F2; padding: 10px; width: 550px; \">";
		$movechat .= "<p style=\"border-bottom: 1px solid #A0A0B0; margin: 0 0 4px 0;\">Chatter message from $oppname:</p>";
		$movechat .= "<p style=\"margin: 4px 0 0 0; font-style: italic; font-family: monospace;\">";
		$movechat .= str_replace("\n", "<br />", $chatter);
		$movechat .= "</p>\n";
		$movechat .= "</div>\n";
	}
	else {
		$movechat  = "<p><cite>No chatter from $oppname</cite></p>\n";
	}

	if ( $p_dropsoon ) {
		$dropwarning  = "<p class=\"warning\">";
		$dropwarning .= "Warning: If you don't play your move soon, your opponent will be able to cancel";
		$dropwarning .= " this game and win !";
		$dropwarning .= "</p>\n";
	}
	else {
		$dropwarning = "";
	}

	$contents = <<<EOT

<table summary="Chess Game Informations"> <tr> 

<td colspan="2">
	$dropwarning
	$movetext
	$movechat
</td>

</tr> <tr> <!-- -->

<td style="padding: 10px;">
	$html_board
</td>
<td style="padding: 10px 10px 10px 40px;">
	$html_history
</td>

</tr> <tr> <!-- -->

<td colspan="2">
	<p style="text-align: center; margin-top: 16px;">
	[ <a href="$gameurl">Click here to open this game on $mcc_server_name</a> ]
	</p>
</td>

</tr> <tr> <!-- -->

<td colspan="2">
	$html_gameslist
</td>

</tr> </table> <!-- -->

<p style="font-size: smaller;">
If you want to change your email notification settings, you
can do so when logged on the site.<br />
Just click <cite>My Profile / Change Your Email Settings </cite>.
You'll then be able to update your email address and/or change
the notification delay.
</p>

EOT;

	return mcc_template_email($p_player, $contents);
}


function mcc_template_email ( $p_player, $p_contents )
{
	global $mcc_stylesheet_common;
	global $mcc_stylesheet_email;
	global $mcc_version;
	global $mcc_server_name;

	$version = "v" . implode(".", $mcc_version);

	$email  = "";

	$email .= <<<EOT
	<html>
	<head>
	<meta http-equiv='content-type' content='text/html; charset=utf-8' />
	<style type="text/css">
EOT;

	$email .= implode("", file($mcc_stylesheet_common));
	$email .= implode("", file($mcc_stylesheet_email));

	$email .= <<<EOT
	</style>
	</head>
	<body>
EOT;

	if ( $p_player ) {
		$email .= mcc_template_userbar($p_player, "User &nbsp;");
	}

	$year = date('Y');

	$email .= "<div style=\"padding: 13px 15px 10px 15px;\">";
	$email .= $p_contents;

	$email .= <<<EOT

	<p>&nbsp;</p>
	<p>Have fun !</p>

	<p>The $mcc_server_name Team</p>

	</div>

	<table style="width: 100%; border-top: 1px solid #F2F2F2;"
		summary="Signature">
	<tr>
		<td class="tiny" style="vertical-align: top; text-align: left; padding: 3px;">
		  MKGI Chess Club $version &nbsp;
		  &copy; $year
		  <a class="sublink" href="http://www.alcibiade.org/">Alcibiade.org</a>
		</td>
		<td class="tiny" style="vertical-align: top; text-align: right;">
		</td>
	</tr>
	</table>
	</body></html>
EOT;

	return $email;
}

//--- Page Components -------------------------------------------------------

function mcc_template_box ( $p_title, $p_body, $p_width = 600 )
{
	if ( $p_width ) {
		$style = "style='width: ${p_width}px;'";
	}
	else {
		$style = "";
	}

	return <<<EOT
	<table class="mccbox" $style>
	<tr><td class="mccbox_title">
		$p_title
	</td></tr>
	<tr><td class="mccbox_body">
		$p_body
	</td></tr>
	</table> <!-- mccbox end -->
EOT;
}

function mcc_template_sidebox ( $p_title, $p_body )
{
	return <<<EOT
	<table class="mccbox" style="width: 200px;">
	<tr><td class="mccbox_title" style="font-size: 8pt;">
		$p_title
	</td></tr>
	<tr><td class="mccbox_body" style="font-size: 8pt;">
		$p_body
	</td></tr>
	</table> <!-- mccbox end -->
EOT;
}


function mcc_template_suggestions ( $p_player )
{
	$playerset = new PlayerSet();
	$newbies = $playerset->getNewbies();
	$suggest = $playerset->getSuggestedOpponents($p_player);

	$newbies_html = mcc_template_playerlist ( $newbies,
				PLAYER_IDENTIFIER | PLAYER_CREATIONDATE,
				"New Members",
				"These members joined the club recently"
				. " and we suggest that you invite them for"
				. " their first games."
				);

	$suggest_html = mcc_template_playerlist ( $suggest,
				PLAYER_IDENTIFIER | PLAYER_SCOREPOINTS,
				"Members you could play against",
				"They have been selected because we think that"
				. " they could be an interresting match for you."
				);

	$result = "";
	$result .= "<table><tr>\n";
	$result .= "<td style='width: 220px; vertical-align: top; padding-right: 10px;'>$newbies_html</td>\n";
	$result .= "<td style='width: 270px; vertical-align: top;'>$suggest_html</td>\n";
	$result .= "</tr></table>\n";

	$result = mcc_template_box("Suggested Opponents", $result);
	return $result;
}

function mcc_template_playerlist ( $p_players, $p_fields, $p_title, $p_abstract )
{
	$result = "";

	$nbfields = 0;
	if ( $p_fields & PLAYER_IDENTIFIER ) { $nbfields++; }
	if ( $p_fields & PLAYER_CREATIONDATE ) { $nbfields++; }
	if ( $p_fields & PLAYER_SCOREPOINTS ) { $nbfields++; }


	$result .= "<table class='playerlist'>";
	$result .= "<tr>";
	$result .= "<td class='playerlist_title' colspan='$nbfields'>$p_title</td>";
	$result .= "</tr>";
	$result .= "";

	foreach ( $p_players as $player ) {
		$result .= "<tr>";

		if ( $p_fields & PLAYER_IDENTIFIER ) {
			$id = $player->getIdentifier();
			$result .= "<td class='playerlist'>"
				. "<a href='player_profile_view.php?player=$id'>"
				. "$id</a></td>";
		}

		if ( $p_fields & PLAYER_SCOREPOINTS ) {
			$result .= "<td class='playerlist' style='text-align: right;'>"
				. $player->getScorePoints() . "</td>";
		}

		if ( $p_fields & PLAYER_CREATIONDATE ) {
			$result .= "<td class='playerlist'>"
				. date(DATEFORM_DATE, $player->getCreationDate()) . "</td>";
		}

		$result .= "";
		$result .= "</tr>";
	}

	$result .= "<tr>";
	$result .= "<td class='playerlist_abstract' colspan='$nbfields'>$p_abstract</td>";
	$result .= "</tr>";
	$result .= "</table>\n";

	return $result;
}

function mcc_template_news ( $p_articleset )
{
	global $mcc_server_name;
	$result = "";

	$art_count = $p_articleset->size();

	if ( $art_count > 0 ) {
		$newsbody = "<dl class=\"news\">";

		for ( $art = 0; $art < $art_count; $art++ ) {
			$article = $p_articleset->getArticle($art);

			$title  = date(DATEFORM_DATE,$article->getDate());
			$title .= " - " . $article->getTitle();
			$text   = $article->getText();
			$text  .= "<p class=\"news_signature\">"
				. $article->getAuthor() . "</p>\n";

			$newsbody .= "<dt class=\"news\">$title</dt>\n";
			$newsbody .= "<dd class=\"news\">$text</dd>\n";
		}

		$newsbody .= "</dl>";

		$result = mcc_template_box($mcc_server_name . " News", $newsbody);
	}

	return $result;
}


function mcc_template_tip ( $p_tip )
{
	$result = "";
	$text = $p_tip->getText();

	if ( $text ) {
		$result = mcc_template_sidebox("Tip", $text);
	}
	
	return $result;
}


function mcc_template_form ( $p_action, $p_method, $p_message,
				$p_fields, $p_class = FALSE )
{
	if ( $p_class ) {
		$class = "class=\"$p_class\"";
	}
	else {
		$class = "";
	}

	$enctype = '';

	foreach ( $p_fields as $field ) {
		if ( $field[3] == 'file' ) {
			$enctype = 'enctype="multipart/form-data"';
		}
	}

	$result  = "";
	$result .= <<<EOT
<form action="$p_action" method="$p_method" $enctype>
<table $class>
EOT;

	if ( $p_message ) {
		$result .= <<<EOT
<tr>
	<td colspan="3" $class style="text-align: center;">
		$p_message
	</td>
</tr>
EOT;
	}

	foreach ( $p_fields as $field ) {
		$label  = $field[0];
		$name   = $field[1];
		$value  = $field[2];
		$type   = $field[3];
		$attrs  = $field[4];
		$help   = $field[5];

		if ( count($field) > 6 ) {
			$values = $field[6];
		}

		if ( $type == "label" ) {
			$result .= <<<EOT
	<td $class style="text-align: right;">
		$label
	</td>
	<td $class style="text-align: left;">
		$value
	</td>
	<td $class style="text-align: justify; font-size: smaller; width: 200px;">
		$help
	</td>
EOT;
		}
		else if ( $type == "submit" ) {
			$result .= <<<EOT
<tr>
	<td colspan="3" $class style="text-align: center;">
		<input type="submit" name="$name" value="$value" />
	</td>
</tr>
EOT;
		}
		else if ( $type == "hidden" ) {
			$result .= <<<EOT
	<input type="hidden" name="$name" value="$value" />
EOT;
		}
		else if ( $type == "file" ) {
			$result .= <<<EOT
<tr>
	<td colspan="3" $class style="text-align: center;">
		<input type="file" name="$name" />
	</td>
</tr>
EOT;
		}
		else if ( $type == "select" ) {
$result .= <<<EOT
<tr>
	<td $class style="text-align: right;">
		$label
	</td>
	<td $class style="text-align: left;">
EOT;

			$result .= "<select name=\"$name\" $attrs>";
				
			foreach( $values as $v_key => $v_val ) {
				if ( $v_key == $value ) {
					$sel = "SELECTED";
				}
				else {
					$sel = "";
				}

				$result .= "<option value=\"$v_key\" $sel>$v_val</option>";
			}
			  
			$result .= "</select>\n";
		
$result .= <<<EOT
	</td>
	<td $class style="text-align: justify; font-size: smaller; width: 200px;">
		$help
	</td>
</tr>
EOT;
		}
		else {
			$result .= <<<EOT
<tr>
	<td $class style="text-align: right;">
		$label
	</td>
	<td $class style="text-align: left;">
		<input type="$type" $attrs name="$name" value="$value" />
	</td>
	<td $class style="text-align: justify; font-size: smaller; width: 200px;">
		$help
	</td>
</tr>
EOT;
		}
	}

	$result .= <<<EOT
</table>
</form>
EOT;

	return $result;
}


function mcc_template_userinfos ( $p_player )
{
	global $AGES, $GENDERS, $COUNTRIES;

	$pp_identifier  = $p_player->getIdentifier();
	$pp_realname    = $p_player->getRealName();
	$pp_country     = $p_player->getCountry();
	$pp_gender      = $p_player->getGender();
	$pp_age         = $p_player->getAge();
	$pp_points      = $p_player->getScorePoints();
	$pp_games       = $p_player->getScoreGames();
	$pp_wins        = $p_player->getScoreWins();
	$pp_draws       = $p_player->getScoreDraws();
	$pp_losses      = $p_player->getScoreLosses();

	// Player data processing

	if ( $pp_age ) {
		$pp_age = $AGES[$pp_age];
	}

	if ( $pp_gender ) {
		$pp_gender = $GENDERS[$pp_gender];
	}

	if ( $pp_country ) {
		$pp_country = $COUNTRIES[$pp_country];
	}

	return <<<EOT
	<table class="profile">
	<tr>
	<td class="profile_label">Real Name</td>  <td class="profile" colspan="3">$pp_realname</td>
	</tr>
	<tr>
	<td class="profile_label">Elo</td>        <td class="profile">$pp_points</td>
	<td class="profile_label">Games</td>      <td class="profile" style="text-align: right;">$pp_games</td>
	</tr>
	<tr>
	<td class="profile_label">Gender</td>     <td class="profile">$pp_gender</td>
	<td class="profile_label">Wins</td>       <td class="profile" style="text-align: right;">$pp_wins</td>
	</tr>
	<tr>
	<td class="profile_label">Country</td>    <td class="profile">$pp_country</td>
	<td class="profile_label">Draws</td>      <td class="profile" style="text-align: right;">$pp_draws</td>
	</tr>
	<tr>
	<td class="profile_label">Age</td>        <td class="profile">$pp_age</td>
	<td class="profile_label">Losses</td>     <td class="profile" style="text-align: right;">$pp_losses</td>
	</tr>
	</table>
EOT;
}

function mcc_template_menubar ( $p_context )
{
	$baritems = array();

	if ( is_array($p_context) ) {
		$context = $p_context[0];
		$gameid  = $p_context[1];
		$param   = $p_context[2];
	}
	else {
		$context = $p_context;
	}

	if ( $context == "index" || $context == "newgame" ) {
		$baritems['Home'] = 'club.php';
		$baritems['My Profile'] = 'player_profile_view.php';
		$baritems['Search'] = 'search.php';
		$baritems['Rankings'] = 'rankings.php';
		$baritems['Invite a friend'] = 'invitation.php';
		$baritems['Help'] = 'help.php';
		$baritems['Logout'] = 'logout.php';
	}
	else if ( $context == "chess" ) {
		$baritems['Home'] = 'club.php';
		$baritems['History Browser'] = "browser.php?gameid=$gameid";

		if ( $param == 0 ) {
			$baritems['Strategic View'] = "chess.php?gameid=$gameid&amp;boardview=1";
		}
		else {
			$baritems['Normal View'] = "chess.php?gameid=$gameid&amp;boardview=0";
		}

		$baritems['PGN Format'] = "pgnformat.php?gameid=$gameid";
		$baritems['Invite a friend'] = 'invitation.php';
		$baritems['Help'] = 'help.php';
		$baritems['Logout'] = 'logout.php';
	}
	else if ( $context == "browser" ) {
		$baritems['Home'] = 'club.php';
		$baritems['Input Mode'] = "chess.php?gameid=$gameid";
		$baritems['Rotate Board'] = "browser.php?gameid=$gameid&amp;rotate=$param";
		$baritems['PGN Format'] = "pgnformat.php?gameid=$gameid";
		$baritems['Invite a friend'] = 'invitation.php';
		$baritems['Help'] = 'help.php';
		$baritems['Logout'] = 'logout.php';
	}
	else if ( $context == "help" ) {
		$baritems['Home'] = 'club.php';
		$baritems['Invite a friend'] = 'invitation.php';
		$baritems['Logout'] = 'logout.php';
	}
	else if ( $context == "search" || $context == "rankings" ) {
		$baritems['Home'] = 'club.php';
		$baritems['Help'] = 'help.php';
		$baritems['Logout'] = 'logout.php';
	}
	else if ( $context == "profile_edit" ) {
		$baritems['My Profile'] = 'player_profile_view.php';
		$baritems['Home'] = 'club.php';
		$baritems['Rankings'] = 'rankings.php';
		$baritems['Invite a friend'] = 'invitation.php';
		$baritems['Help'] = 'help.php';
		$baritems['Logout'] = 'logout.php';
	}
	else if ( $context == "profile_view" ) {
		$baritems['Home'] = 'club.php';
		$baritems['Rankings'] = 'rankings.php';
		$baritems['Invite a friend'] = 'invitation.php';
		$baritems['Help'] = 'help.php';
		$baritems['Logout'] = 'logout.php';
	}
	else if ( $context == "invitation" ) {
		$baritems['Home'] = 'club.php';
		$baritems['My Profile'] = 'player_profile_view.php';
		$baritems['Search'] = 'search.php';
		$baritems['Rankings'] = 'rankings.php';
		$baritems['Help'] = 'help.php';
		$baritems['Logout'] = 'logout.php';
	}


	$result  = "<div class='menubar'>";
	$result .= "[ ";
	$firstitem = TRUE;

	foreach ( $baritems as $label => $href ) {
		if ( $firstitem ) {
			$firstitem = FALSE;
		}
		else {
			$result .= " | ";
		}

		$result .= "<a href='{$href}'>{$label}</a>";
	}

	$result .= " ]";
	$result .= "</div>\n";

	return $result;
}


function mcc_template_userbar ( $p_user, $p_message = "Logged in as: " )
{
	if ( $p_user ) {
	$id     = $p_user->getIdentifier();
	$gamest = $p_user->getScoreGames();
	$wins   = $p_user->getScoreWins();
	$draws  = $p_user->getScoreDraws();
	$losses = $p_user->getScoreLosses();
	$points = $p_user->getScorePoints();

	$result = <<<EOT
<table class="userbar" style="width: 100%;"> 
<tr>
<td class="userbar_left">$p_message<strong>$id</strong></td>
<td class="userbar_right">
	$wins wins,
	$draws draws,
	$losses losses,
	$points Elo
</td>
</tr></table>
EOT;
	}

	return $result;
}

function mcc_template_notebox ( $p_note, $p_refresh = TRUE )
{
	$note = $p_note->getText();

	if ( $p_refresh ) {
		$button = '<input type="submit" name="refresh_and_save_note" value="Save Note" />';
	}
	else {
		$button = '<input type="submit" name="move_chessman" value="Move!" />';
	}

	return <<<EOT
Your Note: 
<span class="warning"> (Only you can read it.) </span><br />
<textarea class="playernote" name="chessnote">$note</textarea>
<p align="center">
	$button
</p>
EOT;
}

function mcc_template_footer()
{
	global $mcc_version;
	$version = "v" . implode(".", $mcc_version);
	$gentime = ElapsedTime();
	$year = date('Y');

	try {
		$player = new Player();
	} catch ( Exception $e ) {
		$player = NULL;
	}

	if ( $player && $player->isAdmin() ) {
		$gentime_string = "Page built in $gentime ms<br />";
	}
	else {
		$gentime_string = "";
	}

	return <<<EOFOOT
<table style="width: 100%;">
<tr>
	<td class="tiny" style="vertical-align: top; text-align: left;">
	  MKGI Chess Club $version <br />
	  &copy; $year
	  <a href="http://www.alcibiade.org/">Alcibiade.org</a>
	</td>
	<td class="tiny" style="vertical-align: top; text-align: right;">
		$gentime_string
		<a href="mailto:contact@chess.mkgi.net">Contact Us</a>
	</td>
</tr>
</table>
EOFOOT;
}

function mcc_template_searchbox($p_location, $p_player, $p_color, $p_opponent ) {
	$search_games = SEARCH_GAMES;

	if ( $p_location == "archive" ) {
		$sel_games = "";
		$sel_archs = "selected";
	}
	else {
		$sel_games = "selected";
		$sel_archs = "";
	}

	if ( $p_color == "w" ) {
		$sel_a = "";
		$sel_w = "selected";
		$sel_b = "";
	}
	else if ( $p_color == "b" ) {
		$sel_a = "";
		$sel_w = "";
		$sel_b = "selected";
	}
	else {
		$sel_a = "selected";
		$sel_w = "";
		$sel_b = "";
	}

	$search = <<<EOT

<form method="get" action="search.php">
<table class="search">
<tr>
	<td class="search_left">Location:</td>
	<td class="search_right">
		<select name="location" class="search_select">
		  <option $sel_games value="games">Open Games</option>
		  <option $sel_archs value="archive">Archive</option>
		</select>
	</td>
</tr>
<tr>
	<td class="search_left">Player:</td>
	<td class="search_right">
		<input name="player" class="search_text" value="$p_player" />
	</td>
</tr>
<tr>
	<td class="search_left">Player Color:</td>
	<td class="search_right">
		<select name="color" class="search_select">
			 <option $sel_a value="anycolor">Any Color</option>
			 <option $sel_w value="w">White</option>
			 <option $sel_b value="b">Black</option>
		</select>
	</td>
</tr>
<tr>
	<td class="search_left">Opponent:</td>
	<td class="search_right">
		<input name="opponent" class="search_text" value="$p_opponent" />
	</td>
</tr>
<tr>
	<td class="search_middle" colspan="2">
		<input type="submit" name="search" value="${search_games}" />
	</td>
</tr>
</table>
</form>
EOT;

	return mcc_template_box("Search Games", $search);
}


function mcc_template_rankings ( $p_player = NULL, $p_linkprofiles = FALSE ) {
	$filter = mcc_get_page_parameter(RANKING_FILTER_VARNAME);

	if (       $filter != RANKING_FILTER_HUMANS
		&& $filter != RANKING_FILTER_ROBOTS
		&& $filter != RANKING_FILTER_ALL ) {

		$filter = RANKING_FILTER_HUMANS;
	}
	 
	$pset = new PlayerSet();

	$result  = "";

	$result .= <<<EOT
<p style="text-align: center;"> <strong>User Rankings</strong> </p>


<table class="rankings" align="center">
<tr>
	<td class="rankings_title">Rank</td>
	<td class="rankings_title">Name</td>
	<td class="rankings_title" style="text-align: right;">Games</td>
	<td class="rankings_title" style="text-align: right;">Wins</td>
	<td class="rankings_title" style="text-align: right;">Draws</td>
	<td class="rankings_title" style="text-align: right;">Losses</td>
	<td class="rankings_title" style="text-align: right;">Rating</td>
</tr>
EOT;

	$players = $pset->getPlayersByRankDesc(RANKING_GAMES_MINI, $filter);

	$total_pages = ceil(count($players) / RANKING_PAGE);
	LogDebug("rp: " . RANKING_PAGE);
	LogDebug("players: " . count($players));
	LogDebug("Total Pages: " . $total_pages);

	$rpage = mcc_get_page_parameter(RANKING_PAGE_VARNAME);

	if ( $rpage ) {
		if ( $rpage < 1 ) {
			$rpage = 1;
		}

		if ( $rpage > $total_pages ) {
			$rpage = $total_pages;
		}
	}
	else {
		// This is False or in [0:count($players)-1]
		$player_rank = FALSE;

		for ( $r = 0; $r < count($players); $r++ ) {
			if ( $p_player == $players[$r] ) {
				$player_rank = $r;
				break;
			}
		}

		if ( $player_rank ) {
			$rpage = ceil(($player_rank + 1) / RANKING_PAGE);
		}
		else {
			$rpage = 1;
		}
	}

	$rank = 1 + ($rpage - 1) * RANKING_PAGE;

	foreach ( array_slice($players, ($rpage - 1) * RANKING_PAGE, RANKING_PAGE) as $player ) {
		if ( $player == $p_player ) {
			$class = "rankings_selected";
		}
		else {
			$class = "rankings";
		}

		if ( $p_linkprofiles ) {
			$link    = "<a href=\"player_profile_view.php?player="
				. $player->getIdentifier() . "\">";
			$linkend = "</a>";
		}
		else {
			$link    = "";
			$linkend = "";
		}

		$result .= "<tr>";
		$result .= "<td class=\"$class\" style=\"text-align: right;\">$rank.</td>";
		$result .= "<td class=\"$class\">$link" . $player->getIdentifier() . "$linkend</td>";
		$result .= "<td class=\"$class\" style=\"text-align: right;\">" . $player->getScoreGames() . "</td>";
		$result .= "<td class=\"$class\" style=\"text-align: right;\">" . $player->getScoreWins() . "</td>";
		$result .= "<td class=\"$class\" style=\"text-align: right;\">" . $player->getScoreDraws() . "</td>";
		$result .= "<td class=\"$class\" style=\"text-align: right;\">" . $player->getScoreLosses() . "</td>";
		$result .= "<td class=\"$class\" style=\"text-align: right;\">" . $player->getScorePoints() . "</td>";
		$result .= "</tr>\n";
		$rank++;
	}

	if ( $total_pages > 1 ) {
		$result .= "<tr><td class=\"rankings_pager\" colspan=\"7\">";

		if ( $rpage > 1 ) {
			$url = mcc_build_url(NULL, RANKING_PAGE_VARNAME, $rpage - 1);
			$result .= "<a href=\"$url\">&lt;</a> &nbsp;";
		}

		$page_first = max(1, $rpage - floor(SHOW_PAGES / 2));
		$page_last  = min($total_pages, $page_first + SHOW_PAGES);

		$result .= "Page &nbsp;";

		for ( $p = $page_first; $p <= $page_last; $p++ ) {
			if ( $rpage == $p ) {
				$result .= "<strong>$p</strong> &nbsp;";
			}
			else {
				$url = mcc_build_url(NULL, RANKING_PAGE_VARNAME, $p);
				$result .= "<a href=\"$url\">$p</a> &nbsp;";
			}
		}

		if ( $rpage < $total_pages ) {
			$url = mcc_build_url(NULL, RANKING_PAGE_VARNAME, $rpage + 1);
			$result .= "<a href=\"$url\">&gt;</a> ";
		}

		$result .= "</td></tr>";
	}

	$rankmin = RANKING_GAMES_MINI;

	$result .= "</table>";

	#--- Build the filter bar

	$filterbar = "<p style=\"text-align: center;\">[ ";

	if ( $filter == RANKING_FILTER_HUMANS ) {
		$filterbar .= "<strong>Show Only Humans</strong>";
	}
	else {
		$url = mcc_build_url(NULL, RANKING_FILTER_VARNAME, RANKING_FILTER_HUMANS);
		$filterbar .= "<a href=\"$url\">Show Only Humans</a>";
	}

	$filterbar .= " | ";

	if ( $filter == RANKING_FILTER_ROBOTS ) {
		$filterbar .= "<strong>Show Only Robots</strong>";
	}
	else {
		$url = mcc_build_url(NULL, RANKING_FILTER_VARNAME, RANKING_FILTER_ROBOTS);
		$filterbar .= "<a href=\"$url\">Show Only  Robots</a>";
	}

	$filterbar .= " | ";

	if ( $filter == RANKING_FILTER_ALL ) {
		$filterbar .= "<strong>Show Both</strong>";
	}
	else {
		$url = mcc_build_url(NULL, RANKING_FILTER_VARNAME, RANKING_FILTER_ALL);
		$filterbar .= "<a href=\"$url\">Show Both</a>";
	}

	$filterbar .= " ]</p>\n";

	$result .= $filterbar;
	#---

	$result .= <<<EOT
<p class="warning" style="text-align: center;">(You must have finished at least $rankmin games to show up.)</p>
EOT;

	return $result;
}


function mcc_template_move_history ( $p_game, $p_insert_js = FALSE, $p_display_score = FALSE )
{
	$moves = $p_game->getMoves();

	$result  = "";
	$result .= "<table class='movehistory'>\n";

	$js_index = 0;
	$turn     = 1;

	if ( count($moves) == 0 ) {
		$result .= "<tr><td class='movehistory_move_odd'
					colspan='3'
					style='font-style: italic;'>&nbsp;No moves.</td></tr>\n";
	}

	// Beware the lame double i++ !!
	for ( $i = 0; $i < count($moves); ) {
		if ( $turn % 2 == 0 ) {
			$parity = 'even';
		}
		else {
			$parity = 'odd';
		}

		$move = $moves[$i++];

		$hidehints = $p_game->isOpen() && $i >= count($moves) - HINTS_HIDING;
		$quality = '';

		if ( $p_display_score && !$hidehints && $move->isAnalysisAvailable() ) {
			if ( $move->getTeacherRate() < -300 ) {
				$quality = 'bad';
			}
		}

		$result .= "<tr>\n";

		if ( $p_insert_js ) {
			$result .= "<td class='movehistory_mark_$parity'>"
				.  "<img name='histmark{$js_index}' src='images/spacer.png' alt='' /></td>\n";
		}

		$result .= "<td class='movehistory_count_$parity'>$turn.</td>\n";

		if ( $p_insert_js ) {
			$result .= "<td class='movehistory_{$quality}move_$parity'>&nbsp;"
						. "<a href='$js_index' onclick='return gotoMove($js_index);'>"
						. $move->getShort() . "</a></td>\n";
		}
		else {
			$result .= "<td class='movehistory_{$quality}move_$parity'>&nbsp;"
						. $move->getShort() . "</td>\n";
		}

		$js_index++;

		if ( $i < count($moves) ) {
			$move = $moves[$i++];

			$hidehints = $p_game->isOpen() && $i >= count($moves) - HINTS_HIDING;
			$quality = '';

			if ( $p_display_score && !$hidehints && $move->isAnalysisAvailable() ) {
				if ( $move->getTeacherRate() < -300 ) {
					$quality = 'bad';
				}
			}

			if ( $p_insert_js ) {
				$result .= "<td class='movehistory_{$quality}move_$parity'>&nbsp;"
						. "<a href='$js_index' onclick='return gotoMove($js_index);'>"
						. $move->getShort() . "</a></td>\n";
			}
			else {
				$result .= "<td class='movehistory_{$quality}move_$parity'>&nbsp;"
						. $move->getShort() . "</td>\n";
			}
			$js_index++;

			if ( $p_display_score ) {
				if ( !$hidehints && $move->isAnalysisAvailable() ) {
					$result .= "<td class='movehistory_move_$parity'>"
						. mcc_template_scorebar($move->getScore()) . "</td>\n";
				}
				else {
					$result .= "<td class='movehistory_move_$parity'></td>\n";
				}
			}
		}
		else {
			$result .= "<td class='movehistory_move_$parity'></td>\n";
			if ( $p_display_score ) {
				$result .= "<td class='movehistory_move_$parity'></td>\n";
			}
		}


		$result .= "</tr>\n";
		$turn++;
	}

	$result .= "</table>\n";

	if ( $p_insert_js ) {
		$result .= <<<EOT
<script language="Javascript" type="text/javascript">
<!--
	move_count=$js_index;
	orig_move_count = move_count;
// -->
</script>
EOT;
	}

	return mcc_template_box("Move History", $result, FALSE);
}

define("MAX_SCORE", 1000);

function mcc_template_scorebar ( $p_score, $p_width = 30 ) {
	if ( $p_score < 0 ) {
		$score_white = min(-$p_score, MAX_SCORE);
		$score_black = 0;
	}
	else {
		$score_white = 0;
		$score_black = min($p_score, MAX_SCORE);
	}

	$width_white = $p_width * $score_white / MAX_SCORE;
	$width_black = $p_width * $score_black / MAX_SCORE;
	$width_whole = 2 * $p_width + 2 * 8 + 5;

	$result = "";
	$result .= "<div style='width: {$width_whole}px;'>";
	$result .= "<img src='images/scorebar_0.png' style='height: 14px; width: " . ($p_width - $width_white) . "px;' />";
	$result .= "<img src='images/scorebar_a.png' />";
	$result .= "<img src='images/scorebar_b.png' style='height: 14px; width: " . ($width_white) . "px;' />";
	$result .= "<img src='images/scorebar_c.png' style='height: 14px; width: 3px;' />";
	$result .= "<img src='images/scorebar_d.png' style='height: 14px; width: " . ($width_black) . "px;' />";
	$result .= "<img src='images/scorebar_e.png' />";
	$result .= "<img src='images/scorebar_0.png' style='height: 14px; width: " . ($p_width - $width_black) . "px;' />";
	$result .= "</div>";

	return $result;
}

define("BOARDVIEW_STANDARD", 0);
define("BOARDVIEW_STRATEGIC", 1);

function mcc_template_board ( $p_game, $p_black = FALSE, $p_viewmode = BOARDVIEW_STANDARD,
				$p_insert_js = FALSE, &$p_board, &$p_diff, $p_absolute = TRUE )
{
	global $t_frame_color;
	global $mcc_server_root;

	if ( $p_absolute ) {
		$imgroot = $mcc_server_root . "images";
	}
	else {
		$imgroot = "images";
	}

	$result = "";

	$boardview_mode = $p_viewmode;

	if ( $p_black ) {
		$index       =  7;
		$pos_change  = -1;
		$line_change = 16;
	}
	else {
		$index       =  56;
		$pos_change  =   1;
		$line_change = -16;
	}

	$result .= "<table cellpadding=\"4\"><tr>\n";
	$result .= "<td bgcolor=\"$t_frame_color\">\n";
	$result .= "<table class='chessboard' cellspacing=\"0\">\n";

	if ( $boardview_mode == BOARDVIEW_STRATEGIC ) {
		$total_menace_black = 0;
		$total_menace_white = 0;
	}

	for ( $y = 0; $y < 9; $y++ ) {
		$result .= "<tr>\n";
		for ( $x = 0; $x < 9; $x++ ) {
			if ( $y == 8 ) {
				if ( $x > 0 ) {
					if ( ! $p_black )	$c = chr(96+$x);
					else			$c = chr(96+9-$x);

					$result .= "<td class=\"board_cell_coordinate\">$c</td>\n";
				}
				else {
					$result .= "<td></td><td></td>\n";
				}
			} 
			else if ( $x == 0 ) {
				if ( ! $p_black ) {
					$i = 8 - $y;
				}
				else {
					$i = $y + 1;
				}

				// The useless spacer column should be removed...
				$result .= "<td class=\"board_cell_coordinate\">$i</td><td>&nbsp;</td>\n";
			} 
			else {
				$entry = $p_board[$index];
				$color = substr( $entry, 0, 1);
				$name  = getFullFigureName((strlen($entry)>1)?$entry[1]:NULL);
				$tilestyle = '';
				$tileclass = '';

				if ( $boardview_mode == BOARDVIEW_STRATEGIC ) {
					$att_b = countTileAttacks($p_board, "b", $index);
					$att_w = countTileAttacks($p_board, "w", $index);

					$total_menace_black += $att_b;
					$total_menace_white += $att_w;

					$coef = $att_b - $att_w;
					$tile_r = 255;
					$tile_g = 255;
					$tile_b = 255;

					if ( $coef > 0 ) {
						$tile_g -= $coef * 20 + 40;
						$tile_b -= $coef * 20 + 40;
					}
					if ( $coef < 0 ) {
						$tile_r -= -$coef * 20 + 40;
						$tile_g -= -$coef * 20 + 40;
					}

					if ( $tile_r < 0 )      $tile_r = 0;
					if ( $tile_g < 0 )      $tile_g = 0;
					if ( $tile_b < 0 )      $tile_b = 0;

					$tilecol   = sprintf("#%02X%02X%02X", $tile_r, $tile_g, $tile_b);
					$tilestyle = "background: $tilecol;";
				}
				else {
					if ( (($y+1)+($x)) % 2 == 0 ) {
						$tileclass = 'b_std_white';
					}
					else {
						$tileclass = 'b_std_black';
					}
				}

				$cursor  = 'default';
				$imagescript = '';

				if ( $name != "empty" ) {
					$imagesrc    = "$imgroot/wcg/$color$name.png";

					if ( $color == "w" ) {
						$imagealt = "White $name";
					}
					else {
						$imagealt = "Black $name";
					}

					if ( $p_black )		$player_col = "b";
					else			$player_col = "w";

					if ( $player_col != $p_board[$index][0] ) {
						$cmdpart = sprintf( "x%s", boardIndexToCoord($index) );
					}
					else {
						$cmdpart = sprintf( "%s%s", $p_board[$index][1], boardIndexToCoord($index) );
						$cursor = 'pointer';
						$imagescript  = " onload=\"dnd_registerSource(this, '$cmdpart');\"";
					}
				}
				else {
					$imagesrc    = "$imgroot/wcg/empty.png";
					$imagealt    = "Empty";
					$cmdpart     = sprintf( "-%s", boardIndexToCoord($index) );
				}

				$result .= "<td id='xb$index' class='$tileclass' style='$tilestyle'>";
				if ( $p_insert_js ) {
					$result .= "<div style='margin: 0px; padding: 0px; "
							. "width: 36px; height: 36px; cursor: $cursor;' "
							. "onclick='clickHandler(\"$cmdpart\");' >";
					$result .= "<img  src='$imagesrc' alt='$imagealt' $imagescript />";
					$result .= "</div>";
					$result .= "</td>\n";
					$result .= "<script language='JavaScript'>";
					$result .= "dnd_registerTarget(document.getElementById('xb$index'), '$cmdpart');";
					$result .= "</script>";
				}
				else {
					$result .= "<img src='$imagesrc' alt='$imagealt' />";
					$result .= "</td>\n";
				}

				$index += $pos_change;
			}
		}
		$index += $line_change;
		$result .= "</tr>\n";
	}

	$result .= "</table></td>\n";

	if ( !empty($p_diff) ) {
		$diff_names = array( "pawn", "knight", "bishop", "rook", "queen" );

		$result .= '<td style="background: #ffffff;"><img width="10" alt="" src="' . $imgroot . '/spacer.png" /></td>';
		$result .= '<td style="background: #ffffff;">';
		$result .= '<table cellpadding="2" bgcolor="' . $t_frame_color . '">';
		$result .= '<tr><td valign="top">';

		// show superiority at top
		$draw_gap = 0;
		for( $i = 0; $i < 5; $i++ ) {
			$name = $diff_names[4-$i];

			if ( ! $p_black && $p_diff[4-$i]<0 ) {
				$draw_gap = 1;
				for ( $j = 0; $j < -$p_diff[4-$i]; $j++ )
					$result .= "<img alt=\"$name\" src=\"$imgroot/wcg/sb$name.png\" /><br />";
			}
			else if ( $p_black && $p_diff[4-$i]>0) {
				$draw_gap = 1;
				for ( $j = 0; $j < $p_diff[4-$i]; $j++ )
					$result .= "<img alt=\"$name\" src=\"$imgroot/wcg/sw$name.png\" /><br />";
			}
		}
		$result .= "</td></tr><tr><td><img width=\"2\" height=\"40\" src=\"$imgroot/spacer.png\" alt=\"\" /></td></tr><tr><td>";
		/* show superiority at bottom */
		for( $i = 0; $i < 5; $i++ ) {
			$name = $diff_names[$i];

			if ( ! $p_black && $p_diff[$i]>0 ) {
				for ( $j = 0; $j < $p_diff[$i]; $j++ )
					$result .= "<img alt=\"$name\" src=\"$imgroot/wcg/sw$name.png\" /><br />";
			}
			else if ( $p_black && $p_diff[$i]<0) {
				for ( $j = 0; $j < -$p_diff[$i]; $j++ )
					$result .= "<img alt=\"$name\" src=\"$imgroot/wcg/sb$name.png\" /><br />";
			}
		}
		$result .= "</td></tr></table></td>";
	}
	$result .= "</tr></table>";

	/* Build menace Bars... */

	if ( $boardview_mode == BOARDVIEW_STRATEGIC ) {
		$width_menace_white = 4 * $total_menace_white;
		$width_menace_black = 4 * $total_menace_black;

		if ( $width_menace_white > 300 || $width_menace_black > 300 ) {
			$coef = 300 / max($width_menace_white, $width_menace_black);
			$width_menace_white *= $coef;
			$width_menace_black *= $coef;
		}

		$result .= '<div class="menace_bars">';
		$result .= '<div class="menace_barw" style="width: ' . $width_menace_white . 'px;">' . $total_menace_white . '</div>';
		$result .= '<div class="menace_barb" style="width: ' . $width_menace_black . 'px;">' . $total_menace_black . '</div>';
		$result .= '</div>';
	}

	return $result;
}



// Warning: We only manage condensed lists without sortlinks

function mcc_template_gameslist ( $p_gameset, $p_current_player = NULL, $p_sortlinks = FALSE,
					$p_absolute = TRUE, $p_paging = TRUE, $p_condensed = FALSE )
{
	global $mcc_server_root;

	if ( $p_absolute ) {
		$imgroot = $mcc_server_root . "images";
	}
	else {
		$imgroot = "images";
	}

	$sortkey = mcc_get_page_parameter(GAMES_SORT_VARNAME);

	if ( $sortkey ) {
		switch ( $sortkey ) {
		case GAMES_SORT_BYWHITE: $p_gameset->setOrder(GAMESET_ORDER_WHITE);	break;
		case GAMES_SORT_BYBLACK: $p_gameset->setOrder(GAMESET_ORDER_BLACK);	break;
		case GAMES_SORT_BYSTART: $p_gameset->setOrder(GAMESET_ORDER_START);	break;
		case GAMES_SORT_BYMOVES: $p_gameset->setOrder(GAMESET_ORDER_MOVES);	break;
		case GAMES_SORT_BYLAST:  $p_gameset->setOrder(GAMESET_ORDER_LAST);	break;
		}
	}

	$result = "";

	$games = $p_gameset->getGames();

	if ( count($games) < 2 ) {
		$p_sortlinks = FALSE;
	}

	$total_pages = ceil(count($games) / GAMES_PAGE);

	$gpage = mcc_get_page_parameter(GAMES_PAGE_VARNAME);

	if ( $gpage ) {
		if ( $gpage < 1 ) {
			$gpage = 1;
		}

		if ( $gpage > $total_pages ) {
			$gpage = $total_pages;
		}
	}
	else {
		$gpage = 1;
	}

	$gmlbl   = "game" . ((count($games)==1)?"":"s");
	$result .= "<p class=\"games_title\">";
	$result .= $p_gameset->getName() . " (" . count($games). " $gmlbl)";
	$result .= "</p>";
		
	if ( count($games) == 0 ) {
		$result .= "<p style=\"text-align: center;\">No games.</p>"; 

		if ( ! $p_condensed ) {
			$result .= "<p style=\"text-align: center; font-style: italic;\">You can"
				. " look for opponents in the <a href=\"rankings.php\">rankings section</a>.</p>"; 
		}
	}
	else {
		$surl_w = mcc_build_url(NULL, GAMES_SORT_VARNAME, GAMES_SORT_BYWHITE);
		$surl_b = mcc_build_url(NULL, GAMES_SORT_VARNAME, GAMES_SORT_BYBLACK);
		$surl_s = mcc_build_url(NULL, GAMES_SORT_VARNAME, GAMES_SORT_BYSTART);
		$surl_m = mcc_build_url(NULL, GAMES_SORT_VARNAME, GAMES_SORT_BYMOVES);
		$surl_l = mcc_build_url(NULL, GAMES_SORT_VARNAME, GAMES_SORT_BYLAST);

		if ( $p_sortlinks ) {
			$result .= <<<EOT
			<table class="games">
			<tr>
			<td class="games_title"></td>
			<td class="games_title"><a class="games_title" href="$surl_w">White Player</a></td>
			<td class="games_title"><a class="games_title" href="$surl_b">Black Player</a></td>
			<td class="games_title"><a class="games_title" href="$surl_s">Starting Date</a></td>
			<td class="games_title"><a class="games_title" href="$surl_m">Moves</a></td>
			<td class="games_title"><a class="games_title" href="$surl_l">Last Move At</a></td>
			<td class="games_title"></td>
			</tr>
EOT;
		}
		else {
			if ( $p_condensed ) {
				$result .= <<<EOT
				<table class="games" style="width: 300px;">
				<tr>
				<td class="games_title">White</td>
				<td class="games_title">Black</td>
				<td class="games_title">Moves</td>
				<td class="games_title"></td>
				</tr>
EOT;
			}
			else {
				$result .= <<<EOT
				<table class="games">
				<tr>
				<td class="games_title"></td>
				<td class="games_title">White Player</td>
				<td class="games_title">Black Player</td>
				<td class="games_title">Starting Date</td>
				<td class="games_title">Moves</td>
				<td class="games_title">Last Move At</td>
				<td class="games_title"></td>
				</tr>
EOT;
			}
		}

		if ( $p_paging ) {
			$pagegames = array_slice($games, ($gpage - 1) * GAMES_PAGE, GAMES_PAGE);
		}
		else {
			$pagegames = $games;
		}

		foreach ( $pagegames as $game ) {
			$myturn = ! $game->isArchived() && $p_current_player == $game->getNextPlayer();

			if ( $myturn ) {
				$light = "lamp_green.png";
			}
			else {
				$light = "lamp_red.png";
			}

			if ( $game->isArchived() ) {
				$script = "browser.php";
			}
			else {
				$script = "chess.php";
			}

			if ( $p_absolute ) {
				$script = $mcc_server_root . $script;
			}

			$white = $game->getWhitePlayer();
			$black = $game->getBlackPlayer();

			if ( $game->getWinner() == $game->WINNER_WHITE ) {
				$whitehtml = "<span class=\"game_winner\">" . $white->getIdentifier() . "</span>";
			}
			else {
				$whitehtml = $white->getIdentifier();
			}

			if ( $game->getWinner() == $game->WINNER_BLACK ) {
				$blackhtml = "<span class=\"game_winner\">" . $black->getIdentifier() . "</span>";
			}
			else {
				$blackhtml = $black->getIdentifier();
			}

			$whitehtml = '<a href="player_profile_view.php?player='
			              . $white->getIdentifier() . '">' . $whitehtml . '</a>';

			$blackhtml = '<a href="player_profile_view.php?player='
			              . $black->getIdentifier() . '">' . $blackhtml . '</a>';

			$startdate = $game->getStartDate();

			$lastmove  = $game->getLastMove();

			if ( $lastmove ) {
				$lastdate = $lastmove->getDate();
			}
			else {
				$lastdate = $game->getStartDate();
			}

			if ( $startdate ) { // This should always be true
				$starttext = date(DATEFORM_DATE, $startdate);
			}
			else {
				$starttext = "No starting date";
			}

			if ( $lastdate ) {
				$lasttext = date(DATEFORM_FULL, $lastdate);
			}
			else {
				$lasttext = "No move yet";
			}

			$starttext = str_replace(" ", "&nbsp;", $starttext);
			$lasttext  = str_replace(" ", "&nbsp;", $lasttext);

			$turn = $game->getTurnCount();
			$gameid = $game->getId();

			if ( $p_condensed ) {
				$result .= <<<EOT
				<tr>
					<td class="games_data">$whitehtml&nbsp;</td>
					<td class="games_data">$blackhtml&nbsp;</td>
					<td class="games_data" style="text-align: right;">$turn&nbsp;</td>
					<td class="games_data">
					  <a href="$script?gameid=$gameid">[&nbsp;Enter&nbsp;]</a>
					</td>
				</tr>
EOT;
			}
			else {
				$result .= <<<EOT
				<tr>
					<td class="games_data"><img alt="$light" src="$imgroot/$light" />&nbsp;</td>
					<td class="games_data">$whitehtml&nbsp;</td>
					<td class="games_data">$blackhtml&nbsp;</td>
					<td class="games_data">$starttext&nbsp;&nbsp;</td>
					<td class="games_data">$turn&nbsp;</td>
					<td class="games_data">$lasttext&nbsp;&nbsp;</td>
					<td class="games_data">
					  <a href="$script?gameid=$gameid">[&nbsp;Enter&nbsp;]</a>
					</td>
				</tr>
EOT;
			}
		}


		if ( $p_paging && $total_pages > 1 ) {
			$result .= "<tr><td class=\"games_pager\" colspan=\"7\">";

			if ( $gpage > 1 ) {
				$url = mcc_build_url(NULL, GAMES_PAGE_VARNAME, $gpage - 1);
				$result .= "<a href=\"$url\">&lt;</a> &nbsp;";
			}

			$page_first = max(1, $gpage - floor(SHOW_PAGES / 2));
			$page_last  = min($total_pages, $page_first + SHOW_PAGES);

			$result .= "Page &nbsp;";

			for ( $p = $page_first; $p <= $page_last; $p++ ) {
				if ( $gpage == $p ) {
					$result .= "<strong>$p</strong> &nbsp;";
				}
				else {
					$url = mcc_build_url(NULL, GAMES_PAGE_VARNAME, $p);
					$result .= "<a href=\"$url\">$p</a> &nbsp;";
				}
			}

			if ( $gpage < $total_pages ) {
				$url = mcc_build_url(NULL, GAMES_PAGE_VARNAME, $gpage + 1);
				$result .= "<a href=\"$url\">&gt;</a> ";
			}

			$result .= "</td></tr>";
		}

		$result .= "</table>";
	}

	return $result;
}


//--- Page Layouts ----------------------------------------------------------

function mcc_template_columns ( $p_columns_array )
{
	$result  = "";

	$result .= "<table><tr> <!-- COLS -->\n";

	for ( $cidx = 0; $cidx < count($p_columns_array); $cidx++ ) {
		$colhtml = $p_columns_array[$cidx];
		if ( $cidx == 0 ) {
			$colstyle = "vertical-align: top; width: 200px;";
		}
		else if ( $cidx == 1 ) {
			$colstyle = "vertical-align: top; width: 300px;";
		}
		else {
			$colstyle = "vertical-align: top;";
		}

		if ( $cidx == 0 ) {
			$result .= "<td style=\"$colstyle\"> <!-- COLS -->\n";
			$result .= $colhtml;
			$result .= "</td> <!-- COLS -->\n";
		}
		else {
			$result .= "<td style=\"$colstyle padding-left: 20px;\"> <!-- COLS -->\n";
			$result .= $colhtml;
			$result .= "</td> <!-- COLS -->\n";
		}
	}

	$result .= "</tr></table> <!-- COLS -->\n";

	return $result;
}

//--- Main page layout ------------------------------------------------------

function mcc_template_page ($p_body, $p_menu_context = NULL, $p_title = NULL, $p_head = NULL )
{
	global $mcc_server_name;
	global $mcc_stylesheet_web;
	global $mcc_stylesheet_common;
	global $mcc_stylesheet_print;
	global $common_scripts;

	$result  = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
	$result .= "<html>\n";
	$result .= "<head>\n";
	$result .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$mcc_stylesheet_common\" />\n";
	$result .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$mcc_stylesheet_web\" />\n";
	$result .= "<link rel=\"stylesheet\" type=\"text/css\" media=\"print\" href=\"$mcc_stylesheet_print\" />\n";
	$result .= "<meta http-equiv='content-type' content='text/html; charset=utf-8' />\n";
	$result .= "<meta name=\"description\" content=\"MKGI Chess Club is an online chess-playing community. When you log in, you can play with other players and also compete against different chess software. Your profile and your ELO rating will be updated with every game you play and you will even be able to display game analysis made by a strong chess engine.\" />\n";

	if ( $p_title ) {
		$result .= "<title>$mcc_server_name : $p_title</title>\n";
	}
	else {
		$result .= "<title>$mcc_server_name</title>\n";
	}

	if ( $p_head ) {
		$result .= $p_head . "\n";
	}

	$result .= $common_scripts;

	$result .= "</head>\n";

	$result .= "<body>\n";
	$result .= "<div id='pageframe'>\n";
	$result .= "<div id='pagetopA'></div>\n";
	$result .= "<div id='pagetopB'></div>\n";

	$result .= "<div id='pagetopC'>";

	try {
		$result .= mcc_template_userbar(new Player());
	} catch ( Exception $e ) { }

	$result .= "</div>\n";

	if ( $p_menu_context ) {
		$result .= "<div id='pagetopD'>" . mcc_template_menubar($p_menu_context) . "</div>\n";
	}

	$result .= "<div id='pagebody_'>\n";
	$result .= "<div id='pagebody' align='center'>$p_body</div>\n";
	$result .= "</div>\n";
	$result .= "<div id='pagefooter'>" . mcc_template_footer() . "</div>\n";
	$result .= "</div>\n";
	$result .= "</body>\n";
	$result .= "</html>\n";

	return $result;
}

//--- Advertising -----------------------------------------------------------

function mcc_template_adbanner()
{
global $adsense_banner;
if ( isset($adsense_banner) && $adsense_banner ) {
$ad = <<<EOT
<div class="adsenseBanner">
$adsense_banner
</div>
EOT;
}
else {
	$ad = "";
}

return $ad;
}

function mcc_template_adsidebar()
{
global $adsense_sidebar;
if ( isset($adsense_sidebar) && $adsense_sidebar ) {
$ad = <<<EOT
<div class="adsenseSidebar">
$adsense_sidebar
</div>
EOT;
}
else {
	$ad = "";
}

return $ad;
}

//---------------------------------------------------------------------------

?>
