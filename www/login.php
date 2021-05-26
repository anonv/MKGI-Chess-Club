<?php

require("mcc_common.php");

try {
	$current_player  = new Player();
} catch ( Exception $e ) {
	$current_player = NULL;
}

$adbanner = mcc_template_adbanner();

$current_gameset = new GameSet($current_player);

$html_body = "";

$login_target   = mcc_get_page_parameter("login_target");
$login_username = mcc_get_page_parameter("login_username");
$login_password = mcc_get_page_parameter("login_password");
$login_force    = mcc_get_page_parameter("force");

if ( ! mcc_url_is_local($login_target) ) {
	$login_target = NULL;
}

$errmsg = "";
$menuinfos = NULL;

if ( ! $current_player && $login_username ) {
	/* No active session and form data...
	 *    ... we are validating the login form ! */

	if ( !empty($login_username) || !empty($login_password) ) {
		$result = "ok";
		
		if ( empty($login_username) ) {
			$result = "Username is missing!";
		}

		if ( empty($login_password) ) {
			$result = "Password is missing!";
		}

		try {
			$loginplayer = new Player($login_username);
		} catch ( Exception $e ) {
			$loginplayer = NULL;
		}

		if ( !$loginplayer || strcasecmp($loginplayer->getPassword(), $login_password) != 0 ) {
			$result = "Invalid username or password!";
		}
			
		if ( $result == "ok" ) {
			$current_player = $loginplayer;
			$current_player->registerSession();

			if ( $login_target && mcc_url_is_local($login_target) ) {
				mcc_http_redirect($login_target);
				exit();
			}
			else {
				mcc_http_redirect("club.php");
				exit();
			}
		}
		else {
			$errmsg .= "<b class=\"warning\">$result</b><br /><br />\n";
		}
	}
}

if ( $current_player ) {
	mcc_http_redirect("club.php");
	exit();
}

if ( isset($maintenance_mode) && $maintenance_mode && !$login_force ) {
	$html_body .= '<p class="warning">The site is temporarily closed for maintenance.<br />'
	. 'Please try again in a few minutes.</p>';
}
else { // Maintenance mode

if ( strlen($login_username) > 0 ) {
	$message = '<p class="warning">(Cookies must be enabled to login.)</p>';
}
else {
	$message = "";
}

$login_form = <<<EOT
<form method="post" action="login.php">
<table align="center"
	summary="Login form">
<tr><td style="text-align: right; padding-bottom: 10px;">
		Username: &nbsp;&nbsp;
		<input type="text" size="20" name="login_username" value="" />
</td></tr>
<tr><td style="text-align: right; padding-bottom: 10px;">
		Password: &nbsp;&nbsp;
		<input type="password" size="20" name="login_password" value="" />
</td></tr>
<tr><td style="text-align: center; padding-bottom: 10px;">
	<input type="submit" name="submit" value="Login" />
	$message
<input type="hidden" name="login_target" value="$login_target" />
</td></tr></table>
</form>

<div class="tiny" style="padding-bottom: 200px;">
  <a href="player_subscription.php">New User</a>
  &nbsp; | &nbsp;
  <a href="player_lostpassword.php">Lost Password ?</a>
</div>
EOT;

$html_body .= <<<EOT

<table>
<tr>
<td style="border-right: 1px dotted gray; padding: 24px 12px 24px 12px; text-align: center;">
	$errmsg
	$login_form
</td>
<td style="padding: 12px; text-align: center;">

<div id="loginfaq">
<dl>
<dt>What is MKGI Chess Club ?</dt>
<dd>
It is an online chess-playing community. When you log in, you can play with other players and also compete
against different chess software. Your profile and your ELO rating will be updated with every game you play
and you will even be able to display game analysis made by a strong chess engine.
</dd>
<dt>What makes it different ?</dt>
<dd>
MKGI Chess Club is played completely over your web browser. You can log in from
anywhere and play in your open games without installing any extra software.<br />
You will also be notified by email on a regular basis of the progress of your games.
</dd>
<dt>How do I join ?</dt>
<dd>
Just click on the <a href="player_subscription.php">New User</a> link to the left
of this page.
</dd>
<dt>How much does it cost ?</dt>
<dd>
Nothing at all. MKGI Chess Club is completely free.
</dd>
</dl>
</div>

<div style="margin-top: 32px;">
	<a href="images/shot_index.jpg"
		rel="lightbox" title="Main Chess Club view" 
		><img src="images/shot_index_icon.png" alt="Screenshot" /></a>
	<a href="images/shot_chess.jpg"
		rel="lightbox" title="Chess Board view" 
		><img src="images/shot_chess_icon.png" alt="Screenshot" /></a>
	<a href="images/shot_profile.jpg"
		rel="lightbox" title="Player profile view" 
		><img src="images/shot_profile_icon.png" alt="Screenshot" /></a>
	<a href="images/shot_browser.jpg"
		rel="lightbox" title="Game history and analysis browser"
		><img src="images/shot_browser_icon.png" alt="Screenshot" /></a>
</div>

$adbanner

</td>
</tr>
</table>

EOT;

} // end maintenance mode test

$headers = <<<EOT
<script type="text/javascript" src="js/prototype.js"></script>
<script type="text/javascript" src="js/scriptaculous.js?load=effects"></script>
<script type="text/javascript" src="js/lightbox.js"></script>
<link rel="stylesheet" href="css/lightbox.css" type="text/css" media="screen" />
EOT;

echo mcc_template_page($html_body, $menuinfos, "Login", $headers);

?>
