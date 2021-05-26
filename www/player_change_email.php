<?php
require("mcc_common.php");
require("mcc_enums.php");

$current_player = mcc_check_login();


// ------------- Parse input form variables and assign default fallback values -----

$f_address      = mcc_get_page_parameter("f_address", $current_player->getEmailAddress());
$f_notification = mcc_get_page_parameter("f_notification", $current_player->getNotificationDelay());
$f_validation   = mcc_get_page_parameter("f_validation");

// ------------- Input data integrity tests ----------------------------------------

$errors = array();

if ( $f_validation ) {
	if ( $f_address != $current_player->getEmailAddress() ) {
		if ( mcc_check_email($f_address) ) {
			$token = new EmailToken($current_player->getIdentifier(), NULL, $f_address);
			$current_player->sendEmailValidation($token);
		}
		else {
			$errors[] = "The email address is invalid.";
		}
	}

	if ( array_key_exists($f_notification, $NOTIFICATIONS) ) {
		$current_player->setNotificationDelay($f_notification);
	}
	else {
		$errors[] = "The notification delay is invalid.";
	}
}

if ( count($errors) == 0 ) {
	$message = "To change your $mcc_server_name notification settings, please fill this form.";
}
else {
	$message = "<div align=\"center\" style=\"text-align: left;\"><ul>\n";

	foreach ( $errors as $error ) {
		$message .= "<li class=\"warning\">$error</li>\n";
	}

	$message .= "</ul></div>\n";
}

// ------------- Input form --------------------------------------------------------

$fields = array (
	array("Email Address", "f_address", $f_address, "text", "style=\"width: 200px;\"",
		"Your new email address."),

	array("Game Notification", "f_notification", $f_notification, "select", "style=\"width: 200px;\"",
		"How long after your opponent played would you like to be notified ?", 
		$NOTIFICATIONS),

	array(NULL, "f_validation", "Update settings", "submit", "", NULL)
);

$input_form = mcc_template_form("player_change_email.php", "post", $message, $fields, "profile_edit");

// ---------------------------------------------------------------------------------

$success_html = <<<EOT

<div style="margin: 3em 0 3em 0;">
<p style="text-align: center;">
        Your notification settings have been modified.
	<br />
	If you changed your email address, you'll soon receive a confirmation code by email.
</p>
</div>
<p style="text-align: center;">
	<a href="player_profile_view.php">Back to your profile</a>
</p>

EOT;

// ---------------------------------------------------------------------------------

$html_body  = "";

if ( $f_validation && count($errors) == 0 ) {
	// ...
	$html_body .= $success_html;
}
else {
	$html_body .= $input_form;
}

// ---------------------------------------------------------------------------------

echo mcc_template_page($html_body, "profile_edit", "Notification Settings");

?>
