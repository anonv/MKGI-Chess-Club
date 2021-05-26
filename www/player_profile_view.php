<?php

require "mcc_common.php";
require "mcc_enums.php";

$current_player = mcc_check_login();

$f_identifier = mcc_get_page_parameter("player");

if ( $f_identifier ) {
	try {
		$profile_player = new Player($f_identifier);
	} catch ( Exception $e ) {
		$profile_player = NULL;
	}
}
else {
	$profile_player = $current_player;
}

// ---------------------------------------------------------------------------------

$html_body  = "";

// Fetch profile player data

$pp_identifier  = $profile_player->getIdentifier();

if ( $profile_player == $current_player ) {
$html_body .= <<<EOT
<p style="text-align: center;">
 [
   <a href="player_profile_edit.php">Change Your Informations</a>
|
   <a href="player_profile_portrait.php">Change Your Picture</a>
|
   <a href="player_change_password.php">Change Your Password</a>
|
   <a href="player_change_email.php">Change Your Email Settings</a>
 ]
</p>
EOT;
}
else {
$html_body .= <<<EOT
<p style="text-align: center;">
 [
   <a href="newgame.php?opponent=$pp_identifier">Start a new game against $pp_identifier</a>
 ]
</p>
EOT;
}

$userinfos = mcc_template_userinfos($profile_player);

$html_body .= <<<EOT

<table style="margin: 4px auto 20px auto;" summary="User Informations">
<tr>
<td colspan="2" style="font-weight: bold; text-align: center; padding: 10px 0 10px 0;">
$pp_identifier's profile
</td>
</tr>

<tr>
<td style="padding-left: 30px;">
	$userinfos
</td>

<td style="text-align: right;">
<img src="player_portrait.php?player=$pp_identifier"
     alt="$pp_identifier portrait"
     class="profile_portrait"
     style="margin: 5px 0 20px 20px;"  />
</td>
</tr>

<tr>
<td colspan="2" style="text-align: center; padding: 10px;">
<img src="player_graphs.php?player=$pp_identifier"
     alt="Graphs for player $pp_identifier" /><br />
Score evolution for player $pp_identifier
</td>
</tr>


EOT;

$opengames = new GameSet($profile_player);

$lastgames = new GameSet($profile_player);
$lastgames->setLocation('archive');
$lastgames->setOrder(GAMESET_ORDER_LAST);
$lastgames->setColor('anycolor');
$lastgames->setOpponent("");
$lastgames->setLimit(5);
$lastgames->setName("Recent games");

/*
$html_body .= '<tr><td style="vertical-align: top;">';   # Main frame cell
$html_body .= mcc_template_gameslist($opengames, NULL, FALSE, TRUE, FALSE, TRUE);
$html_body .= '</td><td style="vertical-align: top;">';
$html_body .= mcc_template_gameslist($lastgames, NULL, FALSE, TRUE, FALSE, TRUE);
$html_body .= '</td></tr>';
*/

$html_body .= '<tr><td colspan="2" style="padding: 12px 0 12px 0;">';   # Main frame cell
$html_body .= mcc_template_gameslist($opengames, NULL, FALSE, TRUE, FALSE, FALSE);
$html_body .= '</td></tr><tr><td colspan="2" style="padding: 12px 0 12px 0;">';
$html_body .= mcc_template_gameslist($lastgames, NULL, FALSE, TRUE, FALSE, FALSE);
$html_body .= '</td></tr>';

$opponents = $profile_player->getOpponentsAndScores();

$profile_id = $profile_player->getIdentifier();

$html_body .= '<tr><td colspan="2">';    # Main frame cell

if ( count($opponents) > 0 ) {
	$html_body .= '<table summary="Opponents encountered" class="profile" style="margin: 16px auto 0 auto;">';
	$html_body .= "<tr>";
	$html_body .= "<td colspan=\"5\" class=\"profile_label\">Opponents encountered</td>";
	$html_body .= "</tr>";
	$html_body .= "<tr>";
	$html_body .= "<td class=\"profile_label\">Opponent</td>";
	$html_body .= "<td class=\"profile_label\">Wins</td>";
	$html_body .= "<td class=\"profile_label\">Draws</td>";
	$html_body .= "<td class=\"profile_label\">Losses</td>";
	$html_body .= "<td class=\"profile_label\">&nbsp;</td>";
	$html_body .= "</tr>";

	foreach ( $opponents as $opp_id => $opp_score ) {
		$html_body .= "<tr>";
		$html_body .= "<td class=\"profile_label\"><a href=\"player_profile_view.php?player=$opp_id\">$opp_id</a></td>";
		$html_body .= "<td class=\"profile\" style=\"text-align: right;\">"
				. $opp_score[0] . "</td>";
		$html_body .= "<td class=\"profile\" style=\"text-align: right;\">"
				. $opp_score[1] . "</td>";
		$html_body .= "<td class=\"profile\" style=\"text-align: right;\">"
				. $opp_score[2] . "</td>";
		$html_body .= "<td class=\"profile\">"
				. "<a href=\"search.php?location=archive&amp;player=$profile_id&amp;color=anycolor&amp;opponent=$opp_id&amp;search=Search+games\">"
				. "Search these games</a>"
				. "</td>";
		$html_body .= "</tr>";
	}

	$html_body .= "<tr>";
	$html_body .= "<td colspan=\"4\" style=\"text-align: center; font-size: 8pt;\">";
	$html_body .= "<strong>Note:</strong> Deleted games do not appear here";
	$html_body .= "</td>";
	$html_body .= "</tr>";

	$html_body .= "</table>\n";
}

$html_body .= '</td></tr>';
$html_body .= "</table>\n";   # Main frame end

// ---------------------------------------------------------------------------------

echo mcc_template_page($html_body, "profile_view", "View Player Profile");

?>
