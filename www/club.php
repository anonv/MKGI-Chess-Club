<?php

require("mcc_common.php");

$current_player = mcc_check_login();
$current_gameset = new GameSet($current_player);

$html_body = "";
$menuinfos = "index";

$current_gameset->setLocation('games');
$current_gameset->setPlayer($current_player->getIdentifier());
$current_gameset->setColor('anycolor');
$current_gameset->setOpponent('');
$current_gameset->setName("My Games");

$gameslist   = mcc_template_gameslist($current_gameset, $current_player, TRUE, FALSE);
$suggestions = mcc_template_suggestions($current_player);
$news        = mcc_template_news(new ArticleSet(3));

$tipbox = mcc_template_tip(new Tip());

$game_human = mcc_template_sidebox("Play against someone", <<<EOT
	<form action="newgame.php" method="get">
	To play against someone, just enter the name of the player you
	want to challenge below.
	<br />
	<input style="font-size: 8pt; margin: 4px; width: 110px;" type="text" name="opponent" value="" />
	</form>
EOT
);

$game_robot = mcc_template_sidebox("Play against a robot", <<<EOT
<table style="width:100%;"><tr>
<td style="font-size: 8pt;">
	<strong>GnuChess</strong><br />
	<a href="newgame.php?opponent=robot_gnuchess_01">Level 1</a><br />
	<a href="newgame.php?opponent=robot_gnuchess_02">Level 2</a><br />
	<a href="newgame.php?opponent=robot_gnuchess_03">Level 3</a><br />
	<a href="newgame.php?opponent=robot_gnuchess_04">Level 4</a><br />
	<a href="newgame.php?opponent=robot_gnuchess_05">Level 5</a><br />
	<a href="newgame.php?opponent=robot_gnuchess_06">Level 6</a><br />
	<a href="newgame.php?opponent=robot_gnuchess_07">Level 7</a><br />
</td>
<td style="font-size: 8pt;">
	<strong>Phalanx</strong><br />
	<a href="newgame.php?opponent=robot_phalanx_01">Level 1</a><br />
	<a href="newgame.php?opponent=robot_phalanx_02">Level 2</a><br />
	<a href="newgame.php?opponent=robot_phalanx_03">Level 3</a><br />
	<a href="newgame.php?opponent=robot_phalanx_04">Level 4</a><br />
	<a href="newgame.php?opponent=robot_phalanx_05">Level 5</a><br />
	<a href="newgame.php?opponent=robot_phalanx_06">Level 6</a><br />
	<a href="newgame.php?opponent=robot_phalanx_07">Level 7</a><br />
</td>
</tr></table>
EOT
);

$sidebar_ad = mcc_template_adsidebar();

$html_body .= <<<EOT

<table>
<tr>
<td style="vertical-align: top;">

	<div style="padding: 4px 0 4px 0;">$gameslist</div>
	<div style="padding: 4px 0 4px 0;">$suggestions</div>
	<div style="padding: 4px 0 4px 0;">$news</div>

</td>
<td style="
	vertical-align: top;
	padding: 12px 0 10px 20px;
	">

	<div style="padding: 2px 0 4px 0;">$tipbox</div>
	<div style="padding: 4px 0 4px 0;">$game_human</div>
	<div style="padding: 4px 0 2px 0;">$game_robot</div>
	<div style="padding: 4px 0 2px 0;">$sidebar_ad</div>
</td>
</tr>
</table>

EOT;

echo mcc_template_page($html_body, $menuinfos);

?>
