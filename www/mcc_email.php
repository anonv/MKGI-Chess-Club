<?php

//--- Mail Sending Procedure ------------------------------------------------

function mcc_mail ( $p_rcpt, $p_subject, $p_message )
{
	global $mcc_server_from;

	LogDebug("======== mcc_mail ===============================");
	LogDebug("To: $p_rcpt");
	LogDebug("Subject: $p_subject");
	LogDebug("Mesage ---\n$p_message");

	$result = false;

	// 'email' is a wrapper function used when hosting on free/proxad
	if ( ! $result && function_exists('email')  ) {
		$result = email("webmaster", $p_rcpt, $subject, $p_message);
	}


	if ( ! $result && function_exists('mail')  ) {
		$result = mail($p_rcpt, $p_subject, $p_message,
				"MIME-Version: 1.0\n" .
				"Content-type: text/html; charset=iso-8859-1\n" .
				"From: $mcc_server_from\n" .
				"Reply-To: $mcc_server_from\n" .
				"X-Mailer: PHP/" . phpversion()
			);
	}

	LogDebug("   mcc_mail RESULT: $result");
	LogDebug("=================================================");

	return $result;
}


?>
