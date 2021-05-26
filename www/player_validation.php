<?php
require "mcc_common.php";

// ------------- Parse input form variables and assign default fallback values -----

$validation = mcc_get_page_parameter("validation");

// ---------------------------------------------------------------------------------

$fields = array (
	array("Validation code", "validation", $validation, "text", "size=\"20\"",
			"The validation code you recieved by email."),
	array(NULL, "submitbtn", "Validate", "submit", "", NULL)
	);

$input_form = mcc_template_form("player_validation.php", "get", NULL, $fields, "subscribe");

// ---------------------------------------------------------------------------------

$html_body  = "";


$message = NULL;

if ( !$validation ) {
	$html_body .= $input_form;
}
else {
	try {
		$token = new EmailToken(NULL, $validation, NULL);
	} catch ( Exception $e ) {
		$token = NULL;
	}

	if ( $token ) {
		try {
			$player = new Player($token->getPlayerIdentifier());
		} catch ( Exception $e ) {
			$player = new UnvalidatedPlayer($token->getPlayerIdentifier());
		}

		$player->setEmailAddress($token->getEmailAddress());

		if ( $player->isValidated() ) {
			$message  = "Your new email address <strong>"
					. $token->getEmailAddress()
					. "</strong> is now effective.<br />";
		}
		else {
			$player->validate();
			$identifier = $player->getIdentifier();
			$message  = "Your username &nbsp;<strong>$identifier</strong>&nbsp; is now validated.<br />";
			$message .= "You can now log on the server and start playing.";
		}

		$token->remove();
	}
	else {
		$message = "The confirmation code is incorrect.";
	}
}

if ( $message ) {
$html_body .= <<<EOT
<p style="text-align: center;">
	$message
</p>
EOT;
}

$html_body .= <<<EOT
<p style="text-align: center;">
	<a href="login.php">Go to login page</a>
</p>
EOT;

// ---------------------------------------------------------------------------------

echo mcc_template_page($html_body, NULL, "Subscription validation");

?>
