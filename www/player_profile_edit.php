<?php
require("mcc_common.php");
require("mcc_enums.php");

$current_player = mcc_check_login();

// ------------- Parse input form variables and assign default fallback values -----

$f_realname   = (isset($_POST) && isset($_POST["f_realname"]  ))?$_POST["f_realname"]  :$current_player->getRealName();
$f_country    = (isset($_POST) && isset($_POST["f_country"]   ))?$_POST["f_country"]   :$current_player->getCountry();
$f_age        = (isset($_POST) && isset($_POST["f_age"]       ))?$_POST["f_age"]       :$current_player->getAge();
$f_gender     = (isset($_POST) && isset($_POST["f_gender"]    ))?$_POST["f_gender"]    :$current_player->getGender();
$f_validation = (isset($_POST) && isset($_POST["f_validation"]))?$_POST["f_validation"]:"";

$f_realname  = htmlspecialchars($f_realname);


// ------------- Input data integrity tests ----------------------------------------

$player = NULL;

$errors = array();

if ( $f_validation ) {
	if ( ! array_key_exists($f_country, $COUNTRIES) ) {
		$errors[] = 'Unknown country';
	}

	if ( ! array_key_exists($f_age, $AGES) ) {
		$errors[] = 'Unknown age';
	}

	if ( ! array_key_exists($f_gender, $GENDERS) ) {
		$errors[] = 'Unknown gender';
	}
}

if ( count($errors) == 0 ) {
	$message = "To change your $mcc_server_name informations, please fill this form.";
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
	array("Real Name", "f_realname", $f_realname, "text",   "style=\"width: 200px;\"", ""),
	array("Gender",    "f_gender",   $f_gender,   "select", "style=\"width: 200px;\"", "", $GENDERS),
	array("Country",   "f_country",  $f_country,  "select", "style=\"width: 200px;\"", "", $COUNTRIES),
	array("Age",       "f_age",      $f_age,      "select", "style=\"width: 200px;\"", "", $AGES),


	array(NULL, "f_validation", "Save Informations", "submit", "", NULL)
);

$input_form = mcc_template_form("player_profile_edit.php", "post", $message, $fields, "profile_edit");

// ---------------------------------------------------------------------------------

$success_html = <<<EOT

<div style="margin: 3em 0 3em 0;">
<p style="text-align: center;">
        Your informations were updated
</p>
</div>

EOT;

// ---------------------------------------------------------------------------------

$html_body  = "";

if ( $f_validation && count($errors) == 0 ) {
	$current_player->setRealName($f_realname);
	$current_player->setCountry($f_country);
	$current_player->setGender($f_gender);
	$current_player->setAge($f_age);
	$html_body .= $success_html;
}
else {
	$html_body .= $input_form;
}

// ---------------------------------------------------------------------------------

echo mcc_template_page($html_body, "profile_edit", "Player Profile Edition");

?>
