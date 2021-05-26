<?php
require "mcc_common.php";

function mcc_template_invitations ( $p_player ) {
	$inv_set = new InvitationSet($p_player);
	$invitations = $inv_set->getInvitations();

	$list = "<table>";
	$list .= "<tr>";
	$list .= "<td class='invitation_title'>Date</td>";
	$list .= "<td class='invitation_title'>Name</td>";
	$list .= "<td class='invitation_title'>Address</td>";
	$list .= "<td class='invitation_title'>Open</td>";
	$list .= "<td class='invitation_title'>Click</td>";
	$list .= "<td class='invitation_title'>Join</td>";
	$list .= "</tr>\n";

	if ( count($invitations) == 0 ) {
		$list .= "<tr><td colspan='8'>";
		$list .= "<p style='margin: 24px; text-align: center;'>No invitations</p>";
		$list .= "</td></tr>\n";
	}

	foreach ( $invitations as $invitation ) {
		$list .= "<tr>\n";
		$list .= "<td class='invitation_data'>"
			. date(DATEFORM_DATE, $invitation->getDate()) . "</td>";
		$list .= "<td class='invitation_data'>" . $invitation->getName() . "</td>";
		$list .= "<td class='invitation_data'>" . $invitation->getAddress() . "</td>";

		if ( $invitation->isOpened() ) {
			$list .= "<td class='invitation_data' style='text-align: center;'>"
				. "<img src='images/lamp_green.png' alt='Yes' /></td>";
		}
		else {
			$list .= "<td class='invitation_data' style='text-align: center;'>"
				. "<img src='images/lamp_red.png' alt='No' /></td>";
		}

		if ( $invitation->isClicked() ) {
			$list .= "<td class='invitation_data' style='text-align: center;'>"
				. "<img src='images/lamp_green.png' alt='Yes' /></td>";
		}
		else {
			$list .= "<td class='invitation_data' style='text-align: center;'>"
				. "<img src='images/lamp_red.png' alt='No' /></td>";
		}

		if ( $invitation->isJoined() ) {
			$list .= "<td class='invitation_data' style='text-align: center;'>"
				. "<img src='images/lamp_green.png' alt='Yes' /></td>";
		}
		else {
			$list .= "<td class='invitation_data' style='text-align: center;'>"
				. "<img src='images/lamp_red.png' alt='No' /></td>";
		}

		$list .= "<td class='invitation_data' style='text-align: center; font-size: 8pt;'>";

		$list .= "<a href='invitation.php?action=delete&invitation="
			. $invitation->getId() . "'>[&nbsp;Delete&nbsp;]</a> ";

		if ( $invitation->retryAllowed() ) {
			$list .= "<a href='invitation.php?action=retry&invitation="
				. $invitation->getId() . "'>[&nbsp;Retry&nbsp;]</a> ";
		}

		$invplayer = $invitation->getInvitedPlayer();
		if ( $invplayer ) {
			$list .= "<a href='player_profile_view.php?player="
					. $invplayer->getIdentifier() . "'>"
					. "[&nbsp;Player&nbsp;profile&nbsp;]</a>\n";
		}

		$list .= "</td>";

		$list .= "</tr>\n";
	}

	$list .= "</table>\n";

	$list .= "<p style='font-size: smaller; margin-left: 24px; margin-top: 24px;'>";
	$list .= "<strong>Open</strong>: The invitation email was opened by the recipient.<br />\n";
	$list .= "<strong>Click</strong>: The link in the email was clicked.<br />\n";
	$list .= "<strong>Join</strong>: The person you invited joined the club.</p>\n";

	$result = mcc_template_box("Invitations sent", $list);

	return $result;
}

$current_player = mcc_check_login();

$f_name       = mcc_get_page_parameter("f_name");
$f_emailaddr  = mcc_get_page_parameter("f_emailaddr");
$f_validation = mcc_get_page_parameter("f_validation");
$f_action     = mcc_get_page_parameter("action");
$f_invitation = mcc_get_page_parameter("invitation");

// ----------------- Actions ------------------------

$form_message = NULL;

if ( $f_invitation ) {
	$invitation = new Invitation(intval($f_invitation));

	if ( $invitation ) {
		$inv_owner = $invitation->getPlayer();

		if (  $inv_owner->getIdentifier() != $current_player->getIdentifier() ) {
			$invitation = NULL;
		}
	}
}


if ( $f_action == "delete" && $invitation ) {
	$invitation->delete();
}
else if ( $f_action == "retry" && $invitation ) {
	$invitation->retry();
}

if ( $f_validation ) {
	$name      = trim($f_name);
	$emailaddr = mcc_canonical_email($f_emailaddr);
	$ivset = new InvitationSet($current_player);

	if ( ! mcc_check_email($emailaddr) ) {
		$form_message = "<p class='warning'>This email address is incorrect.</p>";
	}
	else if ( ! $ivset->checkEmailAddress($emailaddr) ) {
		$form_message = "<p class='warning'>You already sent an invitation to $emailaddr</p>";
	}
	else {
		$inv = new Invitation(NULL, $current_player, $emailaddr, $name);
		$inv->invite();
	}
}


// ----------------- Format Page --------------------

$html_body  = "";

$list = mcc_template_invitations($current_player);

$fields = array (
	array("Name", "f_name", $f_name, "text", "size=\"20\"",
		"The name of the person you want to invite."),

	array("E-Mail", "f_emailaddr", $f_emailaddr, "text", "size=\"20\"",
		"The email address of this person."),

	array(NULL, "f_validation", "Send an Invitation", "submit", "", NULL)
);

$form = mcc_template_box("Send an invitation",
	mcc_template_form("invitation.php", "post", $form_message, $fields, "subscribe")
);




$html_body .= <<<EOT
<div style="margin: 10px;">
	$list
	<div style="margin-top: 32px; margin-bottom: 24px;">
	$form
	</div>
</div>
EOT;

echo mcc_template_page($html_body, "invitation", "Invitation");

?>
