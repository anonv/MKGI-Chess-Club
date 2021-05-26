<?php
require "mcc_common.php";

// ------------- Parse input form variables and assign default fallback values -----

$f_identifier = (isset($_POST) && isset($_POST["f_identifier"]))?$_POST["f_identifier"]:"";
$f_validation = (isset($_POST) && isset($_POST["f_validation"]))?$_POST["f_validation"]:"";

$f_identifier = htmlspecialchars($f_identifier);

// ------------- Input data integrity tests ----------------------------------------

$player = NULL;

$errors = array();

if ( $f_validation ) {
	if ( !$f_identifier ) {
		$errors[] = "You must give a valid username !";
	}
	else {
		try {
			$player = new Player($f_identifier);
		} catch ( Exception $e ) {
			$errors[] = "There is no player with this a username...";
			$errors[] = "If you can't remember you player username, contact us by email.";
		}
	}
}

if ( count($errors) == 0 ) {
	$message = "To have your password sent to you by email, please enter your identifier below.";
}
else {
	$message = "<div align=\"center\" style=\"text-align: left;\"><ul>\n";

	foreach ( $errors as $error ) {
		$message .= "<li class=\"warning\">$error</li>\n";
	}

	$message .= "</ul></div>\n";
}

// ------------- Success Html ------------------------------------------------------

$success_html = <<<EOT

<div style="margin: 3em 0 3em 0;">
<p style="text-align: center;">
	Your password was sent to your email address.
</p>
</div>

EOT;

$techpb_html = <<<EOT

<div style="margin: 3em 0 3em 0;">
<p style="text-align: center;">
	<strong>Technical error: </strong> please try again later...
</p>

</div>

EOT;


// ------------- Input form --------------------------------------------------------

$fields = array (
	array("Username", "f_identifier", $f_identifier, "text", "size=\"20\"",
		"This is the user name that you registered on the server, and for
		 which you'll recieve the password by email at the last registered
		 address"),

	array(NULL, "f_validation", "Send password", "submit", "", NULL)

);

$input_form = mcc_template_form("player_lostpassword.php", "post", $message, $fields, "subscribe");

// ---------------------------------------------------------------------------------

$html_body  = "";

if ( !$f_validation || count($errors) > 0 || !$player ) {
	$html_body .= $input_form;
}
else {
	if ( $player->sendLostPassword() ) {
		$html_body .= $success_html;
	}
	else {
		$html_body .= $techpb_html;
	}
}


$html_body .= <<<EOT
<p style="text-align: center;">
	<a href="login.php">Back to login page</a>
</p>
EOT;

// ---------------------------------------------------------------------------------

echo mcc_template_page($html_body, NULL, "Retrieve your lost password");

?>
