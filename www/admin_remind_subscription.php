<?php

require("mcc_common.php");

mcc_check_admin_host();

header("Content-Type: text/plain");

echo "===================================================================\n";
echo "===             Players subscription reminder script           ====\n";
echo "===================================================================\n";

$now = mcc_db_time();

$pset = new PlayerSet();

$daysecs = 24 * 3600;

$count = 0;

$playerids = $pset->getUnvalidatedIdentifiers();

foreach ( $playerids as $playerid ) {
	$count++;
	$player = new UnvalidatedPlayer($playerid);
	$creation = $player->getCreationDate();

	if ( $now < $creation + 1 * $daysecs ) {
		// Too early ...
	}
	else if ( $now < $creation + 2 * $daysecs ) {
		// Player subscribed 1 day ago
		$player->sendSubscriptionResult(TRUE);
		echo "Player $playerid reminder sent.\n";
	}
	else if ( $now < $creation + 7 * $daysecs ) {
		// In between...
	}
	else if ( $now < $creation + 8 * $daysecs ) {
		// Player subscribed 1 week ago
		$player->sendSubscriptionResult(TRUE);
		echo "Player $playerid reminder sent.\n";
	}
	else {
		// Too late...
		// Should we delete the player instance ?
	}
}


echo "===================================================================\n";
echo "    $count players, elapsed time: " . ElapsedTime()/1000 . "s\n";


?>
