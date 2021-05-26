<?php

require("mcc_common.php");

mcc_check_admin_host();

header("Content-Type: text/plain");

echo "===================================================================\n";
echo "===             Players activity detector                      ====\n";
echo "===================================================================\n";

$q = sql_query("select gm_id, gm_player_white, gm_player_black, max(mv_date) as maxdate
		from mcc_move left join mcc_game on ( gm_id = mv_game)
		group by gm_id, gm_player_white, gm_player_black
		having max(mv_date) > now() - interval '1' month");

$active_players = array();


while ( $q && $row = mysql_fetch_row($q) ) {
	if ( ! in_array($row[1], $active_players) ) {
		$active_players[] = $row[1];
	}
	if ( ! in_array($row[2], $active_players) ) {
		$active_players[] = $row[2];
	}
}

$now = mcc_db_time();

$count = 0;

$pset      = new PlayerSet();
$playerids = $pset->getIdentifiers();

foreach ( $playerids as $playerid ) {
	$player = new Player($playerid);

	if ( $now - $player->getCreationDate() < 15 * 24 * 3600
			|| $player->isRobot()
			|| in_array($playerid, $active_players) ) {
		echo "1 - $playerid\n";
		$player->setActivityFlag(1);
	}
	else {
		echo "0 - $playerid\n";
		$player->setActivityFlag(0);
	}

	$count++;
}


echo "===================================================================\n";
echo "    $count players, elapsed time: " . ElapsedTime()/1000 . "s\n";





?>
