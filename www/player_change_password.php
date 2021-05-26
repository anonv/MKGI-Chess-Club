<?php
require "mcc_common.php";

$current_player = mcc_check_login();


// ------------- Parse input form variables and assign default fallback values -----

$f_passwordo  = mcc_get_page_parameter("f_passwordo", "");
$f_password1  = mcc_get_page_parameter("f_password1", "");
$f_password2  = mcc_get_page_parameter("f_password2", "");
$f_validation  = mcc_get_page_parameter("f_validation", "");

// ------------- Input data integrity tests ----------------------------------------

$player = NULL;

$errors = array();

if ( $f_validation ) {
	if ( $f_passwordo != $current_player->getPassword() ) {
		$errors[] = 'Your current password is incorrect.';
	}

	if ( $f_password1 != $f_password2 ) {
		$errors[] = 'Your new passwords did not match.';
	}
	else {
		if ( strlen($f_password1) < 4 || strlen($f_password1) > 16 ) {
			$errors[] = "Password length must be between 4 and 16 characters.";
		}
	}
}

if ( count($errors) == 0 ) {
	$message = "To change your $mcc_server_name password, please fill this form.";
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
	array("Current Password", "f_passwordo", $f_passwordo, "password", "size=\"20\"",
		"Your current password (for security reasons)."),

	array("New Password", "f_password1", $f_password1, "password", "size=\"20\"",
		"Select a secret password that will allow you to log on the server."),

	array("New Password<br /><span style=\"font-size: smaller;\">(confirm)</span>",
		"f_password2", $f_password2, "password", "size=\"20\"",
		"We ask you to enter the desired password twice in order to
		avoid input problems."),

	array(NULL, "f_validation", "Change password", "submit", "", NULL)

);

$input_form = mcc_template_form("player_change_password.php", "post", $message, $fields, "profile_edit");

// ---------------------------------------------------------------------------------

$success_html = <<<EOT

<div style="margin: 3em 0 3em 0;">
<p style="text-align: center;">
        Your password has been modified.
</p>
</div>
<p style="text-align: center;">
	<a href="player_profile_view.php">Back to your profile</a>
</p>

EOT;

// ---------------------------------------------------------------------------------

$html_body  = "";

if ( $f_validation && count($errors) == 0 ) {
	$current_player->setPassword($f_password1);
	$html_body .= $success_html;
}
else {
	$html_body .= $input_form;
}

// ---------------------------------------------------------------------------------

echo mcc_template_page($html_body, "profile_edit", "Password Change");

?>
