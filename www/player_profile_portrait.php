<?php
require "mcc_common.php";

$current_player = mcc_check_login();

// ------------- Parse input form variables and assign default fallback values -----

$f_validation = (isset($_POST) && isset($_POST["f_validation"]))?$_POST["f_validation"]:"";


// ---------------------------------------------------------------------------------

$message = "";

if ( $f_validation && isset($_FILES['f_portrait']) && $_FILES['f_portrait']['size'] > 0 ) {
	if (  $_FILES['f_portrait']['size'] < 20 * 1024 ) {
		$filename = $_FILES['f_portrait']['tmp_name'];
		$handle = fopen($filename, "r");
		$data = fread($handle, filesize($filename));
		fclose($handle);

		$data = addslashes($data);
		$current_player->setPortrait($_FILES['f_portrait']['type'], $data);
	}
	else {
		$message = "Your file is too big... (it must be under 20kb)";
	}
}

// ---------------------------------------------------------------------------------

$id = $current_player->getIdentifier();

$html_body  = "";

$fields = array (
	array(NULL, "MAX_FILE_SIZE", 50 * 1024, "hidden", "", ""),
	array("Your image", "f_portrait", "", "file", "", ""),

	array(NULL, "f_validation", "Upload", "submit", "", NULL)
);

$input_form = mcc_template_form("player_profile_portrait.php", "post", NULL, $fields, "profile_edit");

$im_type = $current_player->getPortraitType();
$im_size = $current_player->getPortraitSize();

$html_body .= <<<EOT
<div style="margin-top: 10px; margin-bottom: 32px;">
<img src="player_portrait.php?player=$id"
     alt="$id portrait"
     class="profile_portrait"
     style="float: right;
	    margin: 5px 0 20px 20px;"  />

<p class="warning">$message &nbsp;</p>

<p style="font-family: monospace; margin-top: 20px;">
Image type: $im_type<br />
Image size: $im_size
</p>

$input_form

<p>Warning: your image file must be less than 20kb</p>

</div>
EOT;

if ( $f_validation ) {
}



// ---------------------------------------------------------------------------------

echo mcc_template_page($html_body, "profile_edit", "Player Picture Edition");

?>
