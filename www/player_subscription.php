<?php
require("mcc_common.php");

// ------------- Parse input form variables and assign default fallback values -----

$f_identifier = mcc_get_page_parameter("f_identifier");
$f_password1  = mcc_get_page_parameter("f_password1");
$f_password2  = mcc_get_page_parameter("f_password2");
$f_emailaddr  = mcc_get_page_parameter("f_emailaddr");
$f_validation = mcc_get_page_parameter("f_validation");
$f_invitation = mcc_get_page_parameter("invitation");

if ( $f_invitation ) {
	$inv = new Invitation(intval($f_invitation));
	$inv->setClicked();
}

// ------------- Input data integrity tests ----------------------------------------

$errors = array();

if ( $f_validation ) {
	if ( !$f_identifier || !$f_password1 || !$f_password2 || !$f_emailaddr ) {
		$errors[] = "You must fill all the values !";
	}
	else {
		if ( strlen($f_identifier) < 3 || strlen($f_identifier) >24 ) {
			$errors[] = "Username length must be between 3 and 24 characters.";
		}

		for ( $i = 0; $i < strlen($f_identifier); $i++ ) {
			if ( ! strstr(IDENTIFIER_ALLOWED_CHARS, $f_identifier[$i]) ) {
				$errors[] = "Character '" . $f_identifier[$i]
						. "' not allowed in username.";
			}
		}

		if ( strncasecmp("robot", $f_identifier, 5) == 0 ) {
			$errors[] = "A real username can't start with 'robot...'";
		}

		if ( $f_password1 != $f_password2 ) {
			$errors[] = "Passwords don't match.";
		}
		else {
			if ( strlen($f_password1) < 4 || strlen($f_password1) > 16 ) {
				$errors[] = "Password length must be between 4 and 16 characters.";
			}
		}

		if ( !mcc_check_email($f_emailaddr) ) {
			$errors[] = "The E-Mail address is incorrect.";
		}
	}

	if ( count($errors) == 0 ) {
		try {
			$p = new Player($f_identifier, $f_password1, $f_emailaddr);

			if ( $f_invitation ) {
				$inv = new Invitation(intval($f_invitation));
				$inv->setInvitedPlayer($p);
			}
		} catch ( Exception $e ) {
			$errors[] = "The identifier '$f_identifier' already exists.";
		}
	}
}

if ( count($errors) == 0 ) {
	$message = "To create your account, please fill the following fields...";
}
else {
	$message = "<div align=\"center\" style=\"text-align: left;\"><ul>\n";

	foreach ( $errors as $error ) {
		$message .= "<li class=\"warning\">$error</li>\n";
	}

	$message .= "</ul></div>\n";
}

// ------------- Success/Problem Html ----------------------------------------------

$success_html = <<<EOT

<div style="margin: 3em 0 3em 0;">
<p style="text-align: center;">
	Your informations have been taken into account.<br />
	A confirmation code has been sent by email to <strong>$f_emailaddr</strong>.
</p>
</div>

EOT;

$problem_html = <<<EOT

<div style="margin: 3em 0 3em 0;">
<p style="text-align: center;">
	We encountered a technical problem.<br />
	We couldn't send you the confirmation code...<br />
	Please try again later !
</p>
<p style="text-align: center;">
	We apologize for the inconvenience...
</p>
</div>

EOT;

// ------------- Input form --------------------------------------------------------

$fields = array (
	array("Username", "f_identifier", $f_identifier, "text", "size=\"20\"",
		"This is the user name by which you will be known while you
		 are connected on the server."),

	array("Password", "f_password1", $f_password1, "password", "size=\"20\"",
		"Select a secret password that will allow you to log on the server."),

	array("Password<br /><span style=\"font-size: smaller;\">(confirm)</span>",
		"f_password2", $f_password2, "password", "size=\"20\"",
		"We ask you to enter the desired password twice in order to
		avoid input problems."),

	array("E-Mail", "f_emailaddr", $f_emailaddr, "text", "size=\"20\"",
		"The confirmation code will be sent to this address. Later
		you'll be able to use it to recieve notifications."),

	array("invitation", "invitation", $f_invitation, "hidden", "", ""),

	array(NULL, "f_validation", "Validate Subscription", "submit", "", NULL)

);

$input_form = mcc_template_form("player_subscription.php", "post", $message, $fields, "subscribe");

// ---------------------------------------------------------------------------------

$html_body  = "";

if ( !$f_validation || count($errors) > 0 ) {
	$html_body .= $input_form;
}
else {
	$player = new UnvalidatedPlayer($f_identifier);
	$send_status = $player->sendSubscriptionResult();

	if ( $send_status ) {
		$html_body .= $success_html;
	}
	else {
		$player->delete();
		$html_body .= $problem_html;
	}
}

$html_body .= <<<EOT
<p style="text-align: center;">
	<a href="login.php">Back to login page</a>
</p>
EOT;

// ---------------------------------------------------------------------------------

echo mcc_template_page($html_body, NULL, "Subscribe to this club");

?>
