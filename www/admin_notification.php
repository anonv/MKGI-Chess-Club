<?php

require("mcc_common.php");

mcc_check_admin_host();

header("Content-Type: text/plain");

echo "===================================================================\n";
echo "===             Players notification Script                    ====\n";
echo "===================================================================\n";

$now = mcc_db_time();

$count   = 0;
$gameset = new GameSet();

foreach ( $gameset->getGames() as $game ) {
	$logmsg     = NULL;

	$lastmove   = $game->getLastMoveDate();
	$lastnotif  = $game->getLastNotificationDate();
	$nextplayer = $game->getNextPlayer();

	if ( $nextplayer->isRobot() ) {
		$logmsg = $nextplayer->getIdentifier() . " is a robot.";
	}
	else if ( ! $nextplayer->getNotificationDelay() ) {
		$logmsg = "Player does not want to be notified.";
	}
	else {
		$notifdelay = $nextplayer->getNotificationDelay();

		if ( $notifdelay == NOTIF_NEVER ) {
			$logmsg = $nextplayer->getIdentifier() . " disabled notifications.";
		}
		else {
			if ( $notifdelay == NOTIF_1DAY ) {
				$notiftime = $lastmove +     24 * 3600;
			}
			else if ( $notifdelay == NOTIF_2DAY ) {
				$notiftime = $lastmove + 2 * 24 * 3600;
			}
			else if ( $notifdelay == NOTIF_3DAY ) {
				$notiftime = $lastmove + 3 * 24 * 3600;
			}
			else if ( $notifdelay == NOTIF_4DAY ) {
				$notiftime = $lastmove + 4 * 24 * 3600;
			}
			else {
				$notiftime = $lastmove;
			}
			
			$alerttime = $lastmove + DROP_DELAY_MINS * 60 - 48 * 60 * 60;

			if ( $lastnotif == 0 ) {
				$nextplayer->sendMoveNotification($game);
				$logmsg = $nextplayer->getIdentifier() . " notified (init).";
			}
			else if ( $lastnotif < $alerttime && $now > $alerttime ) {
				$nextplayer->sendMoveNotification($game, true);
				$logmsg = $nextplayer->getIdentifier() . " notified (drop available soon).";
			}
			else if ( $lastnotif < $notiftime && $now > $notiftime ) {
				$nextplayer->sendMoveNotification($game);
				$logmsg = $nextplayer->getIdentifier() . " notified.";
			}
			else {
				$logmsg = "Nothing to notify.";
			}
		}
	}

	if ( $logmsg ) {
		echo sprintf(" %8d : %s (lastmove: %d, lastnotif: %d, now: %d)\n", $game->getId(), $logmsg,
				$lastmove, $lastnotif - $lastmove, $now - $lastmove);
		$count++;
	}
}

echo "===================================================================\n";
echo "    $count games, elapsed time: " . ElapsedTime()/1000 . "s\n";





?>
