<?php

//--- Global parameters -----------------------------------------------------

$mcc_version      = array(2,2,0);
$mcc_stylesheet_common = "css/style_common.css";
$mcc_stylesheet_email  = "css/style_email.css";
$mcc_stylesheet_web    = "css/style_web.css";
$mcc_stylesheet_print  = "css/style_print.css";
$log_debug        = FALSE;

define("NOTIFICATION_ENABLED",  0);  // Emails are sent
define("NOTIFICATION_SUSPEND",  1);  // Emails are not sent, notification status is kept
define("NOTIFICATION_DISABLED", 2);  // Emails are not sent but notification flags are set
$notification_status = NOTIFICATION_ENABLED;


define("IDENTIFIER_ALLOWED_CHARS", "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_.");

define("MCC_TOKEN_CHARS", "ABCDEFGHIJKLMNOP01234567890");
define("MCC_TOKEN_LENGTH", 8);

define("UNDO_DELAY_MINS",    20);
define("DROP_DELAY_MINS",    31 * 24 * 60);
define("DATEFORM_FULL",      'Y-M-d H:i');
define("DATEFORM_DATE",      'F jS');
define("NOTIFICATION_MINS",  24 * 60);
define("INVITATION_RETRY_MINS", 2 * 24 * 60);

define("HINTS_HIDING", 3);

define("CASTLE_SHORT", "O-O");
define("CASTLE_LONG",  "O-O-O");

define("RANKING_GAMES_MINI", 3);
define("RANKING_PAGE", 15);
define("RANKING_PAGE_VARNAME", "rankpage");
define("RANKING_FILTER_VARNAME", "view");
define("RANKING_FILTER_HUMANS", "humans");
define("RANKING_FILTER_ROBOTS", "robots");
define("RANKING_FILTER_ALL",    "all");

define("GAMES_PAGE", 15);
define("GAMES_PAGE_VARNAME", "glistpage");
define("GAMES_SORT_VARNAME", "glistsort");
define("GAMES_SORT_BYWHITE", "white");
define("GAMES_SORT_BYBLACK", "black");
define("GAMES_SORT_BYSTART", "start");
define("GAMES_SORT_BYMOVES", "moves");
define("GAMES_SORT_BYLAST",  "last");

define("SUGGESTED_PLAYERS", 7);

define("SEARCH_GAMES",   "Search games");

define("SHOW_PAGES", 10);

define("NOTIF_NEVER",     "nv");
define("NOTIF_1DAY",      "01");
define("NOTIF_2DAY",      "02");
define("NOTIF_3DAY",      "03");
define("NOTIF_4DAY",      "04");
define("NOTIF_IMMEDIATE", "im");

// These constants are ORed together to specify player list fields
define("PLAYER_IDENTIFIER",   1);
define("PLAYER_CREATIONDATE", 2);
define("PLAYER_SCOREPOINTS",  4);

// Used to check text input max size
define("MAX_TEXT_LENGTH", 2048);

require("local_settings.php");
require("mcc_robots.php");

//--- Debug tool ------------------------------------------------------------

function LogDebug ( $p_message )
{
	global $log_debug;

	if ( $log_debug ) {
		echo "\n<!-- LOG: $p_message -->";
	}
}

$querycount = 0;

function sql_query ( $p_query )
{
	global $log_debug;
	global $querycount;

	if ( $log_debug ) {
		list($usec, $sec) = explode(" ", microtime());
		$query_start = ((float)$usec + (float)$sec); 
	}

	$result = mysql_query($p_query);

	if ( $log_debug ) {
		list($usec, $sec) = explode(" ", microtime());
		$query_end = ((float)$usec + (float)$sec); 
		$querycount++;

		LogDebug(
			sprintf("SQL QUERY %02d: %3dms - %s",
				$querycount,
				floor(1000 * ($query_end - $query_start)),
				$p_query));
	}

	return $result;
}

//--- Time management -------------------------------------------------------

function ElapsedTime()
{
	global $_time_start;
	list($usec, $sec) = explode(" ", microtime());
	$now = ((float)$usec + (float)$sec);

	return floor(1000. * ( $now - $_time_start ));
}

function mcc_db_time()
{
	$q = sql_query("select UNIX_TIMESTAMP(now())");
	$res = mysql_fetch_row($q);
	return $res[0];
}

//---------------------------------------------------------------------------

function mcc_get_page_parameter ( $p_name, $p_default = NULL )
{
	$value = $p_default;

	if ( isset($_GET) ) {
		if ( isset($_GET[$p_name]) ) {
			$value = $_GET[$p_name];
		}
	}

	if ( isset($_POST) ) {
		if ( isset($_POST[$p_name]) ) {
			$value = $_POST[$p_name];
		}
	}

	if ( $value ) {
		$value = stripslashes($value);
		$value = htmlentities($value, ENT_QUOTES, "UTF-8");
	}

	LogDebug("mcc_get_page_parameter(" . $p_name . ") = " . $value);

	return $value;
}

/* Makes an HTTP redirection to a given URL.
 * If the parameter is a relative URL, it will
 * first be converted to an absolute one on the
 * current chess server
 */
function mcc_http_redirect ( $p_target )
{
	global $mcc_server_root;

	// Is the target an absolute url ?
	if ( strncmp($p_target, $mcc_server_root, strlen($mcc_server_root)) == 0 ) {
		$target = $p_target;
	}
	else {
		$target = $mcc_server_root . $p_target;
	}

	header("Location: " . $target);
}

/* Check if a user is currently connected. If it is the case, his
 * Player instance is returned (new in v1.2.3). Otherwise, the session
 * is redirected to the login page.
 */
function mcc_check_login()
{
	global $maintenance_mode;

	if ( $maintenance_mode ) {
		session_destroy();
		$p = NULL;
	}
	else {
		try {
			$p = new Player();
		} catch ( Exception $e ) {
			$p = NULL;
		}
	}

	if ( ! $p ) {
		$dirs   = explode("/", $_SERVER["REQUEST_URI"]);
		$last   = array_slice($dirs, -1);
		$target = $last[0];

		mcc_http_redirect("login.php?target=" . urlencode($target));
		exit();
	}

	return $p;
}

function mcc_check_admin_host()
{
	global $mcc_admin_host;
/*
	if ( isset($_SERVER["REMOTE_ADDR"])
			&& $mcc_admin_host != $_SERVER["REMOTE_ADDR"] ) {
		header("Location: login.php");
		exit();
	}
*/
}

function mcc_url_is_local ( $p_url )
{
	$result = TRUE;

	if ( strstr($p_url, '/') != NULL ) {
		$result = FALSE;
	}

	return $result;
}

function mcc_build_url ( $p_url, $p_varname, $p_varvalue )
{
	$uri     = $p_url;
	$varName = $p_varname;
	$varVal  = $p_varvalue;

	$result	 = '';
	$beginning = '';
	$ending	 = '';
  
	if (is_null($uri)) {//Piece together uri string
		$beginning = $_SERVER['PHP_SELF'];
		$ending = ( isset($_SERVER['QUERY_STRING']) ) ? $_SERVER['QUERY_STRING'] : '';
	} else {
		 $qstart = strpos($uri, '?');
		 if ($qstart === false) {
			  $beginning = $uri; //$ending is '' anyway
		 } else {
			  $beginning = substr($uri, 0, $qstart);
			  $ending = substr($uri, $qstart);
		 }
	}
  
	if (strlen($ending) > 0) {
		 $vals = array();
		 $ending = str_replace('?','', $ending);
		 parse_str($ending, $vals);
		 $vals[$varName] = $varVal;
		 $ending = '';
		 $count = 0;
		 foreach($vals as $k => $v) {
			  if ($count > 0) { $ending .= '&amp;'; }
			  else { $count++; }
			  $ending .= "$k=" . urlencode($v);
		 }
	} else {
		 $ending = $varName . '=' . urlencode($varVal);
	}
  
	$result = $beginning . '?' . $ending;
  
	return $result;
}

function mcc_check_email ( $p_email ) {
	$result = TRUE;

	if ( !strstr($p_email, "@") || !strstr($p_email, ".") ) {
		$result = FALSE;
	}

	return $result;
}

function mcc_canonical_email ( $p_email ) {
	return trim($p_email);
}

//--- Submodules ------------------------------------------------------------

require("mcc_templates.php");
require("mcc_email.php");


//--- Classes definitions ---------------------------------------------------

$_valid_playerids = array();

class Player {
	public $player_identifier;

	/* There are 3 ways to construct a Player instance:
	 *
	 * Player() => Tries to get the current session player
	 * Player(ident) => Open a given player
	 *     These 2 will fail if the player is not in the database
	 *
	 * Player(ident, pw...) => Build a new player and fill the database
	 *	  This will fail if the identifier already exists
	 *
	 * A random validation token is then generated...
	 * The player must be validated !
	 */

	function Player ( $p_identifier = NULL, $p_password = NULL,
				$p_email = NULL, $p_admin = FALSE ) {

		global $mysql_table_player;
		global $_valid_playerids;

		$admin = ($p_admin)?(1):(0);

		if ( $p_identifier && $p_password && $p_email ) {

			// We create a new player...
			sql_query("insert into $mysql_table_player set "
				. " pl_identifier='$p_identifier',"
				. " pl_password='$p_password',"
				. " pl_email_address='$p_email',"
				. " pl_is_validated=0,"
				. " pl_is_admin='$admin',"
				. " pl_notification_delay='" . NOTIF_1DAY . "',"
				. " pl_is_active=1,"
				. " pl_creation_date=now()");

			if ( mysql_affected_rows() != 1 ) {
				throw new Exception("Player insertion failed");
			}

			$this->player_identifier = $p_identifier;
		}
		else {
			$identifier = NULL;

			if ( $p_identifier ) {
				$identifier = $p_identifier;
			}
			else if ( isset( $_SESSION["identifier"] ) ) {
				$identifier = $_SESSION["identifier"];
			}

			if ( $identifier == NULL ) {
				throw new Exception("Player identifier not supplied");
			}

			$this->player_identifier = $identifier;

			if ( ! in_array($identifier, $_valid_playerids) ) {
				$q = sql_query("select pl_identifier from $mysql_table_player"
						. " where pl_identifier='$identifier'"
						. "   and pl_is_validated = 1");

				$qres = mysql_fetch_row($q);

				if ( $qres == FALSE ) {
					throw new Exception("Could not fetch player from database");
				}
				else {
					$this->player_identifier = $qres[0];
					$_valid_playerids[] = $this->player_identifier;
				}
			}
		}
	}

	function getIdentifier() {
		return $this->player_identifier;
	}

	function getPassword() {
		return $this->getPlayerAttribute('pl_password');
	}

	function setPassword ( $p_password ) {
		return $this->setPlayerAttribute('pl_password', $p_password);
	}

	function getEmailAddress() {
		return $this->getPlayerAttribute('pl_email_address');
	}

	function setEmailAddress ( $p_address ) {
		return $this->setPlayerAttribute('pl_email_address', $p_address);
	}

	function getRealName() {
		return $this->getPlayerAttribute('pl_real_name');
	}

	function setRealName ( $p_name ) {
		return $this->setPlayerAttribute('pl_real_name', $p_name);
	}

	function getCountry() {
		return $this->getPlayerAttribute('pl_country');
	}

	function setCountry ( $p_country ) {
		return $this->setPlayerAttribute('pl_country', $p_country);
	}

	function getGender() {
		return $this->getPlayerAttribute('pl_gender');
	}

	function setGender ( $p_gender ) {
		return $this->setPlayerAttribute('pl_gender', $p_gender);
	}

	function getAge() {
		return $this->getPlayerAttribute('pl_age');
	}

	function setAge ( $p_age ) {
		return $this->setPlayerAttribute('pl_age', $p_age);
	}

	function getNotificationDelay() {
		return $this->getPlayerAttribute('pl_notification_delay');
	}

	function setNotificationDelay ( $p_notification_delay ) {
		return $this->setPlayerAttribute('pl_notification_delay', $p_notification_delay);
	}

	function getNewsletterSubscription() {
		return $this->getPlayerAttribute('pl_newsletter');
	}

	function setNewsletterSubscription ( $p_newsletter ) {
		return $this->setPlayerAttribute('pl_newsletter', $p_newsletter);
	}

	function getCreationDate() {
		return $this->getPlayerAttribute('unix_timestamp(pl_creation_date)');
	}

	function getActivityFlag() {
		return $this->getPlayerAttribute('pl_is_active');
	}

	function setActivityFlag ( $p_flag ) {
		return $this->setPlayerAttribute('pl_is_active', $p_flag);
	}

	function isValidated() {
		return $this->getPlayerAttribute('pl_is_validated');
	}

	function validate() {
		return $this->setPlayerAttribute('pl_is_validated', 1);
	}

	function isRobot() {
		return $this->getPlayerAttribute('pl_email_address') == "";
	}

	function isAdmin() {
		return $this->getPlayerAttribute('pl_is_admin') == 1;
	}

	function registerSession() {
		global $mysql_table_h_connection;

		$_SESSION["identifier"] = $this->player_identifier;

		if ( ! $this->isRobot() ) {
			sql_query("insert into $mysql_table_h_connection set "
					. " hc_identifier='" . $this->getIdentifier() . "',"
					. " hc_date=now()");
		}
	}

	function getScoreWins() {
		return $this->getPlayerAttribute('pl_score_wins');
	}

	function setScoreWins ( $p_value ) {
		return $this->setPlayerAttribute('pl_score_wins', $p_value);
	}

	function getScoreDraws() {
		return $this->getPlayerAttribute('pl_score_draws');
	}

	function setScoreDraws ( $p_value ) {
		return $this->setPlayerAttribute('pl_score_draws', $p_value);
	}

	function getScoreLosses() {
		return $this->getPlayerAttribute('pl_score_losses');
	}

	function setScoreLosses ( $p_value ) {
		return $this->setPlayerAttribute('pl_score_losses', $p_value);
	}

	function getScoreGames() {
		return $this->getPlayerAttribute('pl_score_wins + pl_score_draws + pl_score_losses');
	}

	function getScorePoints() {
		return $this->getPlayerAttribute('pl_score_points');
	}

	function setScorePoints ( $p_value ) {
		global $mysql_table_h_score;

		sql_query("insert into $mysql_table_h_score set "
				. " hs_identifier='" . $this->getIdentifier() . "',"
				. " hs_date=now(), hs_score_points=$p_value");

		return $this->setPlayerAttribute('pl_score_points', $p_value);
	}

	// --- Internal API

	function getPlayerAttribute ( $p_attname ) {
		global $mysql_table_player;
		$result = NULL;

		$id = $this->player_identifier;

		$q = sql_query("select $p_attname from $mysql_table_player"
				. " where pl_identifier='$id'");

		if ( $q ) {
			$row = mysql_fetch_row($q);
			$result = $row[0];
		}

		return $result;
	}

	function setPlayerAttribute ( $p_attname, $p_attvalue ) {
		global $mysql_table_player;
		$id = $this->player_identifier;

		if ( $p_attvalue === NULL ) {
			$p_attvalue = "NULL";
		}
		else {
			$p_attvalue = "'" . $p_attvalue . "'";
		}

		sql_query("update $mysql_table_player set $p_attname=$p_attvalue"
				. " where pl_identifier='$id'");

		return mysql_affected_rows() == 1;
	}

	//-- Portrait -----------------------

	function getPortraitType() {
		global $mysql_table_player_portrait;
		$id = $this->player_identifier;

		$q = sql_query("select pp_type from $mysql_table_player_portrait"
				. " where pp_player='$id'");

		if ( $q ) {
			$row = mysql_fetch_row($q);
			$result = $row[0];
		}

		return $result;
	}

	function getPortraitSize() {
		global $mysql_table_player_portrait;
		$id = $this->player_identifier;

		$q = sql_query("select length(pp_data) from $mysql_table_player_portrait"
				. " where pp_player='$id'");

		if ( $q ) {
			$row = mysql_fetch_row($q);
			$result = $row[0];
		}

		return $result;
	}

	function getPortraitData() {
		global $mysql_table_player_portrait;
		$id = $this->player_identifier;

		$q = sql_query("select pp_data from $mysql_table_player_portrait"
				. " where pp_player='$id'");

		if ( $q ) {
			$row = mysql_fetch_row($q);
			$result = $row[0];
		}

		return $result;
	}

	function setPortrait ( $p_type, $p_data ) {
		global $mysql_table_player_portrait;
		$id = $this->player_identifier;

		sql_query("replace $mysql_table_player_portrait set "
			     . " pp_player='$id', pp_type='$p_type',"
			     . " pp_data='$p_data'");
	}

	//-- History ------------------------

	function getScoreHistory() {
		global $mysql_table_h_score;
		$id = $this->player_identifier;

		$q = sql_query("select unix_timestamp(hs_date), hs_score_points"
			        . " from $mysql_table_h_score"
				. " where hs_identifier='$id'"
				. " order by hs_date");

		$result = array();

		while ( $q && $row = mysql_fetch_row($q) ) {
			$result[$row[0]] = $row[1];
		}

		return $result;
	}

	//-- Profile data -------------------

	function getOpponentsAndScores() {
		global $mysql_table_game;
		$id = $this->player_identifier;

		$result = array();

		$q = sql_query("select gm_player_white, gm_player_black, gm_status"
				. " from $mysql_table_game"
				. " where (gm_player_white='$id' or gm_player_black='$id')"
				. "       and gm_status != 'open'");

		while ( $q && $row = mysql_fetch_row($q) ) {
			// I was white...
			if ( $id == $row[0] ) {
				$opponent = $row[1];
			}
			// I was black...
			else {
				$opponent = $row[0];
			}

			if ( isset($result[$opponent]) ) {
				$score = $result[$opponent];
			}
			else {
				$score = array(0,0,0);
			}

			if ( $row[2] == 'draw' ) {
				$score[1]++;
			}
			else if (  $row[0] == $id && $row[2] == 'whitewon'
				or $row[1] == $id && $row[2] == 'blackwon' ) {

				$score[0]++;
			}
			else {
				$score[2]++;
			}

			$result[$opponent] = $score;
		}

		uasort($result, 'sort_getOpponentsAndScores');

		return $result;
	}

	//-- Emails -------------------------

	function sendSubscriptionResult ( $p_again = FALSE ) {
		$subject = "Subscription";

		if ( $p_again ) {
			$subject .= " (reminder)";
		}
		
		$token = new EmailToken($this->player_identifier,
					NULL, $this->getEmailAddress());

		$email_subject = mcc_template_email_subject($subject);
		$email_body    = mcc_template_email_subscription($this, $token);
		$send_status = mcc_mail ( $this->getEmailAddress(),
					  $email_subject, $email_body);

		return $send_status;
	}

	function sendLostPassword() {
		$email_subject = mcc_template_email_subject("Lost Password");
		$email_body    = mcc_template_email_lostpassword($this);
		$send_status = mcc_mail ( $this->getEmailAddress(),
					  $email_subject, $email_body);

		return $send_status;
	}

	function sendEmailValidation ( $p_emailtoken ) {
		$email_subject = mcc_template_email_subject("Confirm email address");
		$email_body    = mcc_template_email_changeaddress($this, $p_emailtoken);
		$send_status = mcc_mail ( $p_emailtoken->getEmailAddress(),
					  $email_subject, $email_body);
		return $send_status;
	}

	function sendMoveNotification ( $p_game, $p_dropsoon = false ) {
		global $notification_status;

		$opponent = $p_game->getOpponent($this);

		if ( $p_game->getMoveCount() >= 2 ) {
			$subject = "Your game against " . $opponent->getIdentifier();
		}
		else {
			$subject = "New game started against " . $opponent->getIdentifier();
		}

		if ( $p_dropsoon ) {
			$subject .= " (Warning !)";
		}

		$email_subject = mcc_template_email_subject($subject);
		$email_body    = mcc_template_email_move_notification($this, $p_game, $p_dropsoon);

		if ( $notification_status == NOTIFICATION_ENABLED ) {
			$send_status = mcc_mail ( $this->getEmailAddress(),
						  $email_subject, $email_body);
		}
		else {
			echo "*** Mail '$email_subject' not sent to " . $this->getEmailAddress() . "\n";
			$send_status = TRUE;
		}

		if ( $send_status && $notification_status != NOTIFICATION_SUSPEND ) {
			$p_game->setLastnotificationDate();
		}

		return $send_status;
	}

	//-- Robots -------------------------

	function getDepth() {
		$depth = NULL;

		if ( $this->isRobot() ) {
			$ending = strrchr($this->player_identifier, '_');
			$depth_str = substr($ending, 1);
			$depth = intval($depth_str);
		}

		return $depth;
	}

	function computeNextMove ( $p_game ) {
		if ( strpos($this->player_identifier, "gnuchess") != false ) {
			$move = ComputeNextMove_Gnuchess($p_game->getMoves(), $this->getDepth());
		} else if ( strpos($this->player_identifier, "phalanx") != false ) {
			$move = ComputeNextMove_Phalanx($p_game->getMoves(), $this->getDepth());
		}

		return $move;
	}
};

class UnvalidatedPlayer extends Player {
	/* An unvalidated player is a temporary state between subscription
	 * and validation. We must define this specific constructor in
	 * order to be able to create a Player object
	 */
	function UnvalidatedPlayer ( $p_identifier = NULL ) {
		global $mysql_table_player;
		$this->player_identifier = $p_identifier;

		$q = sql_query("select pl_identifier from $mysql_table_player"
				. " where pl_identifier='$p_identifier'"
				. "   and pl_is_validated = 0");

		if ( mysql_fetch_row($q) == FALSE ) {
			throw new Exception("Could not fetch player from database");
		}
	}

	function delete () {
		global $mysql_table_player;
		$id = $this->player_identifier;
		$q = sql_query("delete from $mysql_table_player where pl_identifier='$id'");
		return $q != FALSE;
	}
};


class PlayerSet {

	function getIdentifiers() {
		global $mysql_table_player;
		$result = array();

		$q = sql_query("select pl_identifier from $mysql_table_player where pl_is_validated = 1 order by pl_identifier");

		while ( $q && $row = mysql_fetch_row($q) ) {
			$result[] = $row[0];
		}

		return $result;
	}

	function getUnvalidatedIdentifiers() {
		global $mysql_table_player;
		$result = array();

		$q = sql_query("select pl_identifier from $mysql_table_player where pl_is_validated = 0 order by pl_identifier");

		while ( $q && $row = mysql_fetch_row($q) ) {
			$result[] = $row[0];
		}

		return $result;
	}

	function getPlayersByRankDesc ( $p_mingames = 0, $p_filter ) {
		global $mysql_table_player;
		global $mysql_table_game;

		$result = array();

		if ( $p_filter == RANKING_FILTER_HUMANS ) {
			$where = " and pl_email_address is not NULL";
		}
		else if ( $p_filter == RANKING_FILTER_ROBOTS ) {
			$where = " and pl_email_address is NULL";
		}
		else {
			$where = "";
		}

		$q = sql_query("select pl_identifier, count(gm_id)"
				. " from $mysql_table_player left join $mysql_table_game on"
				. " ( gm_player_white = pl_identifier or gm_player_black=pl_identifier )"
				. " where pl_is_active = 1 and gm_status != 'open'" . $where
				. " group by pl_identifier"
				. " order by pl_score_points desc");


		while ( $q && $row = mysql_fetch_row($q) ) {
			$p = new Player($row[0]);

			if ( $p && $row[1] >= $p_mingames ) {
				$result[] = $p;
			}
		}

		return $result;
	}

	function getNewbies ( $p_nbplayers = SUGGESTED_PLAYERS ) {
		global $mysql_table_player;
		$result = array();

		$q = sql_query("select pl_identifier"
				. " from $mysql_table_player"
				. " where pl_is_active = 1"
				. "       and pl_is_validated = 1"
				. "       and pl_email_address is not null"
				. " order by pl_creation_date desc"
				. " limit $p_nbplayers");

		while ( $q && $row = mysql_fetch_row($q) ) {
			$result[] = new Player($row[0]);
		}

		return $result;
	}

	function getSuggestedOpponents ( $p_player, $p_nbplayers = SUGGESTED_PLAYERS ) {
		global $mysql_table_player;
		global $mysql_table_game;

		$result = array();

		$q = sql_query("select pl_identifier, abs(" . $p_player->getScorePoints() . " - pl_score_points) * (count(gm_id)+1)"
				. " from $mysql_table_player"
				. "  left join $mysql_table_game on"
					. " ( pl_identifier in (gm_player_black, gm_player_white)"
					. "   and '" . $p_player->getIdentifier() . "' in (gm_player_black, gm_player_white))"
				. " where pl_is_active = 1 and pl_is_validated = 1 and pl_email_address is not null"
					. " and pl_identifier != '" . $p_player->getIdentifier() . "'"
				. " group by pl_identifier"
				. " order by 2 asc"
				. " limit $p_nbplayers");


		while ( $q && $row = mysql_fetch_row($q) ) {
			$result[] = new Player($row[0]);
		}

		return $result;
	}
};


class Move {
	public $_db_fields;

	function Move ( $p_fields ) {
		$this->_db_fields = $p_fields;
	}

	function getId() {
		return $this->_db_fields['mv_id'];
	}

	function getShort() {
		return $this->_db_fields['mv_short'];
	}

	function getLong() {
		return $this->_db_fields['mv_long'];
	}

	function getChatter() {
		return $this->_db_fields['mv_chat'];
	}

	function isAnalysisAvailable() {
		return $this->_db_fields['mv_score'] != NULL;
	}

	function getTeacherMove() {
		$pv = $this->_db_fields['mv_teachermove'];
		$offs_space = strpos($pv, ' ');

		if ( $offs_space == FALSE ) {
			$move = $pv;
		}
		else {
			$move = substr($pv, 0, $offs_space);
		}

		return $move;
	}

	function getTeacherPrincipalVariation() {
		return $this->_db_fields['mv_teachermove'];
	}

	function getScore() {
		return $this->_db_fields['mv_score'];
	}

	function getTeacherRate() {
		return $this->_db_fields['mv_teacherrate'];
	}

	function getDate() {
		return $this->_db_fields['mv_stamp'];
	}

	function getSecondsSinceMove() {
		$date = $this->getDate();
		$result = mcc_db_time() - $date;

		LogDebug("getSecondsSinceMove = $result");
		return $result;
	}
};

class Game {
	public $game_id;
	public $game_board_history;
	public $_att_cache;
	public $_moves_cache;

	public $STATUS_OPEN   = 'open';

	public $WINNER_WHITE  = 'whitewon';
	public $WINNER_BLACK  = 'blackwon';
	public $WINNER_DRAW   = 'draw';

	function Game ( $p_gameid, $p_white = NULL, $p_black = NULL ) {
		global $mysql_table_game;

		$this->game_board_history = array();
		$this->_att_cache         = NULL;
		$this->game_id            = $p_gameid;
		$this->deleted            = FALSE;
		$this->_moves_cache       = NULL;

		if ( $p_white && $p_black && $p_white != $p_black ) {

			// We create a new game...

			$white_id = $p_white->getIdentifier();
			$black_id = $p_black->getIdentifier();

			sql_query("insert into $mysql_table_game set "
				. " gm_player_white='$white_id',"
				. " gm_player_black='$black_id',"
				. " gm_date_start=now()");

			$this->game_id = mysql_insert_id();

			if ( mysql_affected_rows() != 1 ) {
				throw new Exception("Game database insertion failed");
			}
		}
		else {
			// We check if p_gameid is a valid id...

			$q = sql_query("select gm_id from $mysql_table_game"
					. " where gm_id='$p_gameid'");

			if ( mysql_fetch_row($q) == FALSE ) {
				throw new Exception("Could not fetch game from database");
			}
		}
	}

	function getId() {
		return $this->game_id;
	}

	function getWhitePlayer() {
		return new Player($this->getGameAttribute('gm_player_white'));
	}

	function getBlackPlayer() {
		return new Player($this->getGameAttribute('gm_player_black'));
	}

	function getOpponent ( $p_player ) {
		if ( $this->getGameAttribute('gm_player_white') == $p_player->getIdentifier() ) {
			$result = new Player($this->getGameAttribute('gm_player_black'));
		}
		else {
			$result = new Player($this->getGameAttribute('gm_player_white'));
		}

		return $result;
	}

	function hasPlayer ( $p_player ) {
		$id = $p_player->getIdentifier();
		return $this->getGameAttribute('gm_player_white') == $id 
		    || $this->getGameAttribute('gm_player_black') == $id;
	}

	function getStartDate() {
		return $this->getGameAttribute('UNIX_TIMESTAMP(gm_date_start)');
	}

	function isDrawOffered() {
		$result = 0;

		$this->loadMovesInCache();

		$nbmoves = count($this->_moves_cache);

		if ( $nbmoves > 0 ) {
			$last   = $this->_moves_cache[count($this->_moves_cache) - 1];
			$result = $last['mv_drawoffer'];
		}

		LogDebug("isDrawOffered = $result");

		return $result;
	}

	function getLastNotificationDate() {
		return $this->getGameAttribute('UNIX_TIMESTAMP(gm_date_notification)');
	}

	function setLastNotificationDate ( $p_date = 'now()' ) {
		return $this->setGameAttribute('gm_date_notification', $p_date);
	}

	// This is a PRIVATE method !
	function dropMovesCache() {
		$this->_moves_cache = NULL;
	}

	// This is a PRIVATE method !
	function loadMovesInCache() {
		global $mysql_table_move;

		if ( ! $this->_moves_cache ) {
			$gid = $this->game_id;

			LogDebug("loadMovesInCache: Loading moves for game $gid");

			$this->_moves_cache = array();

			$q = sql_query("select UNIX_TIMESTAMP(mv_date) as mv_stamp,"
					. " mv_short, mv_long, mv_chat, mv_drawoffer,"
					. " mv_teachermove, mv_teacherrate, mv_score"
					. " from $mysql_table_move"
					. " where mv_game='$gid' order by mv_id");

			while ( $q && $row = mysql_fetch_assoc($q) ) {
				LogDebug("  - Move loaded: " . implode(", ", $row));
				$this->_moves_cache[] = $row;
			}
		}
	}

	function isOpen() {
		return (   $this->getGameAttribute('gm_status') == $this->STATUS_OPEN
			&& $this->getGameAttribute('gm_is_deleted') == 0 );
	}

	function setArchived ( $p_value ) {
		return $this->setGameAttribute('gm_is_archived', ($p_value)?1:0);
	}

	function isArchived() {
		$result = $this->getGameAttribute('gm_is_archived');
		LogDebug("isArchived() = $result");
		return $result == 1;
	}

	function getWinner() {
		return $this->getGameAttribute('gm_status');
	}

	function setWinner ( $p_winner ) {
		return $this->setGameAttribute('gm_status', $p_winner);
	}

	function getNextPlayer() {
		$result = NULL;

		$count = $this->getMoveCount();

		if ( fmod($count, 2) == 0 ) {
			$result = $this->getWhitePlayer();
		}
		else {
			$result = $this->getBlackPlayer();
		}

		return $result;
	}

	function getMoveCount() {
		$this->loadMovesInCache();
		$result = count($this->_moves_cache);

		LogDebug("getMoveCount() = $result");

		return $result;
	}

	function getTurnCount() {
		$result = floor(($this->getMoveCount() + 1) / 2);
		return $result;
	}

	function isRealtime() {
		$this->loadMovesInCache();
		if ( count($this->_moves_cache) < 2 ) {
			$basetime = $this->getStartDate();
		}
		else {
			$last2row = $this->_moves_cache[count($this->_moves_cache) - 2];
			$last2move = new Move($last2row);
			$basetime = $last2move->getDate();
		}

		$now = mcc_db_time();

		return $now - $basetime < 900;
	}

	function getMoves () {
		$this->loadMovesInCache();
		$result = array();

		foreach ( $this->_moves_cache as $movedata ) {
			$result[] = new Move($movedata);
		}

		return $result;
	}

	function getLastMoveDate() {
		$result = FALSE;
		$lastmove = $this->getLastMove();

		if ( $lastmove ) {
			$result = $lastmove->getDate();
		}
		else {
			$result = $this->getStartDate();
		}

		return $result;
	}

	function getLastMove() {
		$this->loadMovesInCache();

		$nbmoves = count($this->_moves_cache);
		$result  = NULL;

		if ( $nbmoves > 0 ) {
			$last   = $this->_moves_cache[count($this->_moves_cache) - 1];
			$result = new Move($last);
		}

		return $result;
	}

	function getAsPgn() {
		global $mcc_server_name;
		global $_SERVER;

		$dt = date( "Y.m.d", $this->getStartDate());
		$move_count = $this->getTurnCount();
		$white = $this->getWhitePlayer();
		$black = $this->getBlackPlayer();

		$winner = $this->getWinner();

		switch ( $winner ) {
			case $this->WINNER_WHITE: $pgnresult = "1-0";     break;
			case $this->WINNER_BLACK: $pgnresult = "0-1";     break;
			case $this->WINNER_DRAW:  $pgnresult = "1/2-1/2"; break;
			default:		  $pgnresult = "*";       break;
		}

		$result  = "";
		$result .= "[Event \"$mcc_server_name game\"]\n";
		$result .= "[Site \"". $_SERVER['SERVER_NAME'] ."\"]\n";
		$result .= "[Date \"$dt\"]\n";
		$result .= "[Round \"$move_count\"]\n";
		$result .= "[White \"". $white->getIdentifier()."\"]\n";
		$result .= "[Black \"". $black->getIdentifier()."\"]\n";
		$result .= "[Result \"$pgnresult\"]\n";

		$moves = $this->getMoves();

		$idx = 0;

		foreach ( $moves as $m ) {
			if ( ($idx % 10) == 0 ) {
				$result .= "\n";
			}

			if ( ($idx % 2) == 0 ) {
				$result .= ($idx/2 + 1) . ". ";
			}

			$result .= $m->getShort() . " ";
			$idx++;
		}

		$result .= "\n";

		return $result;
	}

	function addMove ( $p_short, $p_long, $p_chat, $p_drawoffer ) {
		global $mysql_table_move;
		$gid = $this->game_id;

		if ( strlen($p_chat) > MAX_TEXT_LENGTH ) {
			$p_chat = substr($p_chat, 0, MAX_TEXT_LENGTH);
		}

		$p_chat = stripslashes($p_chat);
		$p_chat = mysql_escape_string($p_chat);

		if ( $p_chat ) {
			sql_query("insert into $mysql_table_move"
					. " set mv_game='$gid', mv_short='$p_short',"
					. " mv_long='$p_long',"
					. " mv_drawoffer='$p_drawoffer',"
					. " mv_date=now(), mv_chat='$p_chat'");
		}
		else {
			sql_query("insert into $mysql_table_move"
					. " set mv_game='$gid', mv_short='$p_short',"
					. " mv_long='$p_long',"
					. " mv_drawoffer='$p_drawoffer',"
					. " mv_date=now()");
		}

		$this->dropMovesCache();
		$this->purgeLegacyVariablesCache();

		return mysql_affected_rows() == 1;
	}

	function performUndo() {

		// Warning: Potential deadlock -> This should be atomic...
		//	  We could use delete from ... order by ... limit 1, but
		//	  this would break MySQL 3.X compatibility.

		global $mysql_table_move;

		$gid    = $this->game_id;
		$result = FALSE;

		$q = sql_query("select mv_id from $mysql_table_move"
				. " where mv_game='$gid' order by mv_id desc limit 1");

		if ( $q ) {
			$row = mysql_fetch_row($q);
			$mid = $row[0];
			sql_query("delete from $mysql_table_move where mv_id='$mid'");
			$result = TRUE;
		}

		$this->dropMovesCache();
		$this->purgeLegacyVariablesCache();

		return $result;
	}

	function draw() {
		$this->addMove('draw', 'Draw accepted', NULL, 0);
		$this->purgeLegacyVariablesCache();
		return $this->setWinner($this->WINNER_DRAW);
	}

	function resignation ( $p_player ) {
		if ( $p_player == $this->getWhitePlayer() ) {
			return $this->setWinner($this->WINNER_BLACK);
		}

		if ( $p_player == $this->getBlackPlayer() ) {
			return $this->setWinner($this->WINNER_WHITE);
		}
		$this->purgeLegacyVariablesCache();
	}

	function delete() {
		$this->purgeLegacyVariablesCache();
		return $this->setGameAttribute('gm_is_deleted', 1);
	}

	function isDeleted() {
		return $this->getGameAttribute('gm_is_deleted') == 1;
	}

	function getCache() {
		global $mysql_table_game_cache;
		$result = NULL;

		$res = sql_query("select gc_cache from $mysql_table_game_cache"
			. " where gc_game=" . $this->game_id);
		if ( $res && ($row = mysql_fetch_row($res)) ) {
			$result = $row[0];
		}

		return $result;
	}

	function setCache ( $p_data ) {
		global $mysql_table_game_cache;
		sql_query("replace into $mysql_table_game_cache"
			. " set gc_game=" . $this->game_id . ","
			. " gc_cache='" . $p_data . "'");
	}

	//
	// ---  These functions are a cache to the legacy method...
	//

	function initializeLegacyVariablesFromCache() {
		global $board, $ac_move, $w_figures, $b_figures;
		$cache = $this->getCache();
		$game  = NULL;

		if ( $cache != "" ) {
			$gamedata  = unserialize($cache);
			$game      = $gamedata[0];
			$board     = $gamedata[1];
			$ac_move   = $gamedata[2];
			$w_figures = $gamedata[3];
			$b_figures = $gamedata[4];
			$this->game_board_history = $gamedata[5];
		}

		LogDebug("initializeLegacyVariablesFromCache()");
		LogDebug("Cache: $cache");

		return $game;
	}

	function storeLegacyVariablesInCache ( $p_game ) {
		global $board, $ac_move, $res_games, $w_figures, $b_figures;
		$gamedata = array($p_game, $board, $ac_move,
					$w_figures, $b_figures,
					$this->game_board_history);
		$this->setCache(serialize($gamedata));
	}

	// This must be called every time we alter the moves list
	function purgeLegacyVariablesCache() {
		$this->setCache('');
	}

	function initializeLegacyVariables() {
		global $board, $ac_move, $res_games, $w_figures, $b_figures;

		$game = $this->initializeLegacyVariablesFromCache();

		if ( $game ) {
			return $game;
		}

		// Note that username is not modified... We don't want to
		// make any confusion between our local var and the true
		// global one.

		LogDebug("======== initializeLegacyVariables =============");

		$player_w = $this->getWhitePlayer();
		$player_b = $this->getBlackPlayer();

		$id_w = $player_w->getIdentifier();
		$id_b = $player_b->getIdentifier();

		$game   = array();
		$game[] = "DUMMY DATES";
		$game[] = "$id_w $id_b 0 w ? 1 1 1 1 x x x x\n";
		$game[] = "Ra1 Nb1 Bc1 Qd1 Ke1 Bf1 Ng1 Rh1 Pa2 Pb2 Pc2 Pd2 Pe2 Pf2 Pg2 Ph2\n";
		$game[] = "Ra8 Nb8 Bc8 Qd8 Ke8 Bf8 Ng8 Rh8 Pa7 Pb7 Pc7 Pd7 Pe7 Pf7 Pg7 Ph7\n";
		$game[] = "no chatter";

		$board = array( 
			"  ", "  ", "  ", "  ", "  ", "  ", "  ", "  ",
			"  ", "  ", "  ", "  ", "  ", "  ", "  ", "  ",
			"  ", "  ", "  ", "  ", "  ", "  ", "  ", "  ",
			"  ", "  ", "  ", "  ", "  ", "  ", "  ", "  ",
			"  ", "  ", "  ", "  ", "  ", "  ", "  ", "  ",
			"  ", "  ", "  ", "  ", "  ", "  ", "  ", "  ",
			"  ", "  ", "  ", "  ", "  ", "  ", "  ", "  ",
			"  ", "  ", "  ", "  ", "  ", "  ", "  ", "  " );

		fillChessBoard($board, $w_figures, $b_figures, $game[2], $game[3]);

		$this->game_board_history   = array();
		$this->game_board_history[] = $board;

		$mymoves = $this->getMoves();

		$username = $id_w;
		$color    = "w";

		foreach ( $mymoves as $m ) {
			//$err = completeMove($color, $m);
			//LogDebug("$m ==> $ac_move (err is $err)");
			$ac_move = $m->getShort();

			if ( substr($ac_move, -1) == "+" ) {
				$ac_move = substr($ac_move, 0, -1);
			}

			if ( $ac_move == "stalemate" || $ac_move == "mate"
				|| $ac_move == "resigned" || $ac_move == "positional" ) {
				continue;
			}

			$move_res = handleMove( $game, $board, $w_figures, $b_figures,
						$username, $ac_move, $res_games, $ac_move,
						"", $this, FALSE );

			LogDebug("Game status AFTER handleMove()");
			foreach ( $game as $gameline ) {
				LogDebug("game: " . trim($gameline));
			}

			LogDebug("board = " . implode(", ", $board));
			LogDebug("w_figures = " . implode(", ", $w_figures));
			LogDebug("b_figures = " . implode(", ", $b_figures));
			LogDebug("MOVE: $ac_move - $move_res");

			if ( $username == $id_w ) {
				$username = $id_b;
				$color    = "b";
			}
			else {
				$username = $id_w;
				$color    = "w";
			}

			LogDebug("====================================================");
		}

		$this->storeLegacyVariablesInCache($game);

		return $game;
	}

	function updateFromLegacyVariables ( $p_game, $p_move_result, $p_chat, $p_drawoffer ) {
		LogDebug("======== updateFromLegacyVariables =============");
		foreach ( $p_game as $gameline ) {
			LogDebug("game: " . trim($gameline));
		}

		$headline = explode(' ', $p_game[1]);

		$moves = $this->getMoves();
		$moveidx = 0;

		foreach ( array_slice($p_game, 4, $headline[2]) as $gameline ) {
			$moveitems = explode(" ", trim($gameline));

			foreach ( array_slice($moveitems, 1) as $move ) {
				if ( $moveidx >= count($moves) ) {
					if ( $move == 'draw' ) {
						// do nothing
					}
					else if ( $move == 'stalemate' ) {
						$this->setWinner($this->WINNER_DRAW);
					}
					else if ( $move == 'mate' || $move == 'resigned' ) {
						if ( $moveidx % 2 == 1 ) {
							$this->setWinner($this->WINNER_WHITE);
						}
						else {
							$this->setWinner($this->WINNER_BLACK);
						}

						if ( $move == 'resigned' ) {
							$this->addMove($move, $p_move_result, $p_chat, $p_drawoffer);
						}
					}
					else {
						$this->addMove($move, $p_move_result, $p_chat, $p_drawoffer);
					}

					$p_chat = NULL;	// If we have 2 entries, chat only once
				}

				$moveidx++;
			}
		}

		$this->storeLegacyVariablesInCache($p_game);
	}

	// --- Internal API

	function getGameAttribute ( $p_attname ) {
		global $mysql_table_game;
		$result = NULL;

		if ( $this->_att_cache == NULL ) {
			LogDebug("getGameAttribute($p_attname)  // Cache reload");

			$id = $this->game_id;

			$q = sql_query("select *,UNIX_TIMESTAMP(gm_date_start),"
						. "UNIX_TIMESTAMP(gm_date_notification)"
					. " from $mysql_table_game"
					. " where gm_id='$id'");

			if ( $q ) {
				$this->_att_cache = mysql_fetch_assoc($q);
			}
		}

		$result = $this->_att_cache[$p_attname];

		LogDebug("getGameAttribute($p_attname) = $result");

		return $result;
	}

	function setGameAttribute ( $p_attname, $p_attvalue ) {
		global $mysql_table_game;

		$this->_att_cache = NULL;

		$id = $this->game_id;

		// If value is not a MySQL function, add quotes
		if ( strchr($p_attvalue, '(') == NULL ) {
			$p_attvalue = mysql_escape_string($p_attvalue);
			$p_attvalue = "'$p_attvalue'";
		}

		sql_query("update $mysql_table_game set $p_attname=$p_attvalue"
				. " where gm_id='$id'");

		LogDebug("setGameAttribute($p_attname, $p_attvalue)");

		return mysql_affected_rows() == 1;
	}
};

define("GAMESET_ORDER_WHITE", "gm_player_white,gm_player_black,gm_date_start");
define("GAMESET_ORDER_BLACK", "gm_player_black,gm_player_white,gm_date_start");
define("GAMESET_ORDER_START", "gm_date_start desc,gm_player_white,gm_player_black");
define("GAMESET_ORDER_MOVES", "gm_moves desc,gm_date_start desc");
define("GAMESET_ORDER_LAST",  "gm_last_move desc,gm_date_start desc");

class GameSet {

	public $gameset_name;
	public $gameset_location;
	public $gameset_player;
	public $gameset_color;
	public $gameset_opponent;
	public $gameset_order;
	public $gameset_limit;

	function GameSet ( $p_player = NULL ) {
		$this->gameset_name = "";

		$this->gameset_player = "";
		$this->gameset_color = "";
		$this->gameset_opponent = "";
		$this->gameset_location = 'games';
		$this->gameset_order = GAMESET_ORDER_START;
		$this->gameset_limit = NULL;

		if ( $p_player ) {
			$this->gameset_player   = $p_player->getIdentifier();
			$this->gameset_opponent = "";
			$this->gameset_color    = 'anycolor';
			$this->gameset_name     = "Current games for player " . $this->gameset_player;
			$this->gameset_limit    = NULL;
			$this->gameset_location = 'games';
		}
	}

	function getGames() {
		global $mysql_table_game;
		global $mysql_table_move;
		$result = array();

		$wheres = array();

		$wheres[] = "( gm_is_deleted = 0 )";

		if ( $this->gameset_player ) {
			$pl = $this->gameset_player;
			$wheres[] = "( gm_player_white='$pl' or gm_player_black='$pl' )";
		}

		if ( $this->gameset_opponent ) {
			$pl = $this->gameset_opponent;
			$wheres[] = "( gm_player_white='$pl' or gm_player_black='$pl' )";
		}

		if ( $this->gameset_color != "anycolor" ) {
			$col = $this->gameset_color;
			$pl = $this->gameset_player;

			if ( $col == "white" ) {
				$wheres[] = "( gm_player_white='$pl' )";
			}

			if ( $col == "black" ) {
				$wheres[] = "( gm_player_white='$pl' )";
			}
		}

		if ( $this->gameset_location == 'games' )  {
			$wheres[] = "( gm_is_archived=0 )";
		}
		else if ( $this->gameset_location == 'archive' )  {
			$wheres[] = "( gm_is_archived=1 )";
		}

		$where_exp = "where " . implode(" and ", $wheres);
		$query = "select gm_id, count(mv_id) as gm_moves, max(mv_date) as gm_last_move"
				. " from $mysql_table_game left join $mysql_table_move"
					. " on ( gm_id = mv_game )"
				. " $where_exp"
				. " group by gm_id"
				. " order by " . $this->gameset_order;

		if ( $this->gameset_limit ) {
			$query .= " limit " . $this->gameset_limit;
		}

		$q = sql_query($query);

		while ( $q && $row = mysql_fetch_row($q) ) {
			$result[] = new Game($row[0]);
		}

		return $result;
	}

	function getName() {
		return $this->gameset_name;
	}

	function setName ( $p_name ) {
		$this->gameset_name = $p_name;
	}

	function setPlayer ( $p_player ) {
		$this->gameset_player = $p_player;
	}

	function setColor ( $p_color ) {
		$this->gameset_color = $p_color;
	}

	function setOpponent ( $p_opponent ) {
		$this->gameset_opponent = $p_opponent;
	}

	function setLocation ( $p_location ) {
		$this->gameset_location = $p_location;
	}

	function setOrder ( $p_order ) {
		$this->gameset_order = $p_order;
	}

	function setLimit ( $p_limit ) {
		$this->gameset_limit = $p_limit;
	}
};


class Note {
	public $note_owner;
	public $note_game;

	function Note ( $p_owner, $p_game ) {
		$this->note_owner = $p_owner;
		$this->note_game  = $p_game;
	}

	function getText () {
		global $mysql_table_note;

		$owner = $this->note_owner->getIdentifier();
		$game  = $this->note_game->getId();

		$q = sql_query("select nt_text from $mysql_table_note"
				. " where nt_owner='$owner' and nt_game='$game'");

		if ( $q ) {
			$res = mysql_fetch_row($q);
			$res = $res[0];
		}
		else {
			$res = "";
		}

		return $res;
	}

	function setText ( $p_text ) {
		global $mysql_table_note;

		if ( strlen($p_text) > MAX_TEXT_LENGTH ) {
			$p_text = substr($p_text, 0, MAX_TEXT_LENGTH);
		}
		
		$p_text = stripslashes($p_text);
		$p_text = mysql_escape_string($p_text);

		$owner = $this->note_owner->getIdentifier();
		$game  = $this->note_game->getId();

		sql_query("replace into $mysql_table_note"
				. " set nt_owner='$owner', nt_game='$game',"
				. " nt_text='$p_text'");

		return mysql_affected_rows() == 1;
	}
};


class InvitationSet {
	public $iset_player;

	function InvitationSet ( $p_player ) {
		$this->iset_player = $p_player;
	}

	function getInvitations() {
		global $mysql_table_invitation;
		$result = array();

		$q = sql_query("select * from $mysql_table_invitation"
				. " where iv_player='" . $this->iset_player->getIdentifier() . "'"
				. "      and iv_deleted=0"
				. " order by iv_date desc");

		while ( $q && ($r = mysql_fetch_assoc($q)) ) {
			$result[] = new Invitation($r['iv_id']);
		}

		return $result;
	}

	function checkEmailAddress ( $p_address ) {
		global $mysql_table_invitation;
		$result = FALSE;

		$q = sql_query("select count(*) from $mysql_table_invitation"
				. " where iv_player='" . $this->iset_player->getIdentifier() . "'"
				. "      and iv_invited_address='$p_address'");

		if ( $q && ($r = mysql_fetch_array($q)) ) {
			$result = ($r[0] == 0);
		}

		return $result;
	}
};


class Invitation {
	public $invitation_id;
	public $invitation_player;
	public $invitation_address;
	public $invitation_name;
	public $invitation_date;
	public $invitation_clicked;
	public $invitation_iplayer;
	public $invitation_deleted;

	function Invitation ( $p_id, $p_player = NULL, $p_address = NULL, $p_name = NULL ) {
		global $mysql_table_invitation;

		if ( ! $p_id && ! $p_player ) {
			throw new Exception("Must supply at least an id or a player");
		}

		if ( ! $p_id ) {
			sql_query("insert into $mysql_table_invitation"
					. " set iv_player='" . $p_player->getIdentifier() . "',"
					. "     iv_invited_address='$p_address',"
					. "     iv_invited_name='$p_name',"
					. "     iv_date=now()");

			$p_id = mysql_insert_id();
		}

		$q = sql_query("select iv_id, iv_player, iv_invited_address,"
				. "  iv_invited_name, UNIX_TIMESTAMP(iv_date) as iv_date,"
				. "  iv_clicked, iv_opened, iv_invited_player,"
				. "  iv_retried, iv_deleted"
				. " from $mysql_table_invitation"
				. " where iv_id='$p_id'"
				. " order by iv_date desc");

		if ( $q ) {
			$r = mysql_fetch_assoc($q);

			if ( $r ) {
				$this->invitation_id      = $r["iv_id"];
				$this->invitation_player  = $r["iv_player"];
				$this->invitation_address = $r["iv_invited_address"];
				$this->invitation_name    = $r["iv_invited_name"];
				$this->invitation_iplayer = $r["iv_invited_player"];
				$this->invitation_date    = $r["iv_date"];
				$this->invitation_clicked = $r["iv_clicked"];
				$this->invitation_opened  = $r["iv_opened"];
				$this->invitation_retried = $r["iv_retried"];
				$this->invitation_deleted = $r["iv_deleted"];
			}
		}
	}

	function getId()      { return $this->invitation_id; }
	function getPlayer()  { return new Player($this->invitation_player); }
	function getAddress() { return $this->invitation_address; }
	function getName()    { return $this->invitation_name; }
	function getDate()    { return $this->invitation_date; }
	function isClicked()  { return $this->invitation_clicked; }
	function isJoined()   { return $this->invitation_iplayer; }
	function isOpened()   { return $this->invitation_opened; }
	function isRetried()  { return $this->invitation_retried; }
	function isDeleted()  { return $this->invitation_deleted; }

	function getInvitedPlayer() {
		$result = NULL;

		if ( $this->invitation_iplayer ) {
			try {
				$result = new Player($this->invitation_iplayer);
			} catch ( Exception $e ) {}
		}

		return $result;
	}

	function retryAllowed() {
		$result = ! $this->isRetried();

		if ( $this->invitation_iplayer ) {
			$result = FALSE;
		}

		if ( $result &&
			( time() - $this->getDate() ) < INVITATION_RETRY_MINS * 60 ) {
			$result = FALSE;
		}

		return $result;
	}

	function retry() {
		global $mysql_table_invitation;
		sql_query("update $mysql_table_invitation"
			. " set iv_retried=1"
			. " where iv_id=".$this->getId());

		return $this->invite();
	}

	function invite() {
		global $notification_status;
		$player = $this->getPlayer();

		$subject = $player->getIdentifier() . " sends you an invitation";

		$email_subject = mcc_template_email_subject($subject);
		$email_body    = mcc_template_email_invitation($this);

		$send_status = mcc_mail ( $this->getAddress(),
					  $email_subject, $email_body);

		return $send_status;
	}

	function setOpened() {
		global $mysql_table_invitation;
		sql_query("update $mysql_table_invitation"
			. " set iv_opened=1"
			. " where iv_id=".$this->getId());
	}

	function setClicked() {
		global $mysql_table_invitation;
		sql_query("update $mysql_table_invitation"
			. " set iv_clicked=1"
			. " where iv_id=".$this->getId());
	}

	function delete() {
		global $mysql_table_invitation;
		sql_query("update $mysql_table_invitation"
			. " set iv_deleted=1"
			. " where iv_id=".$this->getId());
	}

	function setInvitedPlayer ( $p_player ) {
		global $mysql_table_invitation;
		sql_query("update $mysql_table_invitation"
			. " set iv_invited_player='" . $p_player->getIdentifier() . "'"
			. " where iv_id=".$this->getId());
	}
};


class Tip {
	public $tip_text;

	function Tip ( $p_lang = "EN") {
		global $mysql_table_article;

		$q = sql_query("select art_text from $mysql_table_article"
				. " where art_category='tips' and art_lang='$p_lang'"
				. " order by rand() limit 1");
		$this->tip_text = NULL;

		if ( $q ) {
			$r = mysql_fetch_assoc($q);

			if ( $r ) {
				$this->tip_text = $r["art_text"];
			}
		}
	}

	function getText() {
		return $this->tip_text;
	}
};


class Article {
	public $art_author;
	public $art_title;
	public $art_date;
	public $art_text;

	/* An article can be constructed in 2 different ways:
	 *   - By giving its ID (not implemented yet)
	 *   - By giving all its components
	 */
	function Article ( $p_id, $p_author = NULL, $p_title = NULL,
				$p_date = NULL, $p_text = NULL ) {
		if ( $p_id ) {
			// TODO: Load details from database
		}
		else {
			$this->art_author = $p_author;
			$this->art_title  = $p_title;
			$this->art_date   = $p_date;
			$this->art_text   = $p_text;
		}
	}

	function getAuthor() {
		return ucfirst($this->art_author);
	}

	function getTitle() {
		return ucfirst($this->art_title);
	}

	function getDate() {
		return $this->art_date;
	}

	function getText() {
		return $this->art_text;
	}
};


class ArticleSet {
	public $aset_articles;

	function ArticleSet ( $p_count, $p_lang = "EN" ) {
		global $mysql_table_article;
		$this->aset_articles = array();

		$q = "select art_author, art_title, UNIX_TIMESTAMP(art_date), art_text"
			. " from $mysql_table_article"
			. " where art_category='news' and art_lang='$p_lang'"
			. "   and art_date > now() - interval '1' month"
			. " order by art_date desc limit $p_count";

		$r = sql_query($q);

		while ( $r && ($row = mysql_fetch_row($r)) ) {
			$art = new Article(NULL, $row[0], $row[1], $row[2], $row[3]);
			$this->aset_articles[] = $art;
		}
	}

	function size() {
		return count($this->aset_articles);
	}

	function getArticle ( $p_index ) {
		return $this->aset_articles[$p_index];
	}
};


/* There are 2 ways to instantiate an EmailToken:
 *
 * Player(identifier, NULL or Token, email_address)
 *   Creates a new Token in the database. If no token
 *   text is given, a new one is generated.
 *
 * Player(NULL, Token, NULL)
 *   Loads a token from the database.
 */
class EmailToken {
	public $token_player;
	public $token_value;
	public $token_email_address;
	public $token_date;

	function EmailToken ( $p_player, $p_value, $p_email ) {
		global $mysql_table_email_token;

		if ( ! $p_value ) {
			$p_value = $this->NewTokenValue();
		}

		if ( ! $p_player || ! $p_email ) {
			$fetch_query = "select et_player, et_token, et_email_address, "
					. "UNIX_TIMESTAMP(et_date) from $mysql_table_email_token "
					. "where et_token='$p_value'";

			$res = sql_query($fetch_query);

			if ( $res && ( $row = mysql_fetch_row($res) ) ) {
				$this->token_player        = $row[0];
				$this->token_value         = $row[1];
				$this->token_email_address = $row[2];
				$this->token_date          = $row[3];
			}
			else {
				throw new Exception("Token not found");
			}
		}
		else {
			$insert_query = "insert into $mysql_table_email_token"
					. " set et_player='$p_player', et_token='$p_value',"
					.     " et_email_address='$p_email', et_date=now()";

			sql_query($insert_query);

			$this->token_player        = $p_player;
			$this->token_value         = $p_value;
			$this->token_email_address = $p_email;
			$this->token_date          = NULL;
		}
	}

	// For public use only
	function NewTokenValue() {
		$token = "";

		while ( strlen($token) < MCC_TOKEN_LENGTH ) {
			$charidx = rand(0, strlen(MCC_TOKEN_CHARS) - 1);
			$token  .= substr(MCC_TOKEN_CHARS, $charidx, 1);
		}

		return $token;
	}

	function getPlayerIdentifier() {
		return $this->token_player;
	}

	function getEmailAddress() {
		return $this->token_email_address;
	}

	function getValue() {
		return $this->token_value;
	}

	function getDate() {
		return $this->token_date;
	}

	function remove() {
		global $mysql_table_email_token;
		sql_query("delete from $mysql_table_email_token where et_token='"
				. $this->token_value . "'");
	}
};


//--- Sort functions --------------------------------------------------------

function sort_getOpponentsAndScores ( $a, $b )
{
	$diff_a = ($a[0] - $a[2]) / ($a[0] + $a[1] + $a[2]);
	$diff_b = ($b[0] - $b[2]) / ($b[0] + $b[1] + $b[2]);

	if ( $diff_a == $diff_b ) {
		$diff_a = $a[0] + $a[1]/2 - $a[2];
		$diff_b = $b[0] + $b[1]/2 - $b[2];
	}

	return 1000 * ($diff_b - $diff_a);
}

//--- Global initialization -------------------------------------------------

if ( ! isset($__mcc_init_done) ) {

	ini_set('display_errors', '1');
	ini_set('error_reporting', E_ALL|E_STRICT);

	date_default_timezone_set("Europe/Paris");

	list($usec, $sec) = explode(" ", microtime());
	$_time_start = ((float)$usec + (float)$sec); 
	srand($_time_start);

	session_set_cookie_params(365*24*3600);
	session_start();

	mysql_pconnect($mysql_host, $mysql_user, $mysql_pass);
	mysql_select_db($mysql_db);

	require("handle_move.php");

	$__mcc_init_done = TRUE;
}

//##################### OCC COMPATIBILITY (deprecated API)

// I hope this will be removed soon !

$t_credits = "The chessmen graphics were taken from
<A class=\"sublink\" href=\"http://www.tommasovitale.it/my/wcg/\">
WCG</A> with kind permission of Tommaso Vitale.";
$t_main_table_width = 600;
$t_frame_color = "#eeeeff";
$t_coord_color = "#9999aa";



// These are still userful... (taken from misc.php)


/* create chess board from figure information
 * from game file and return the list of differences
 * in chessmen. positive value indicates white
 * superiority and negative values a black one.
 * list order is pawn,knight,bishop,rook,queen */
function fillChessBoard( &$board, &$w_figures, &$b_figures, $white_figs, $black_figs )
{
	$diff = array(0,0,0,0,0);

	$w_figures = explode( " ", $white_figs );
	foreach ( $w_figures as $figure )
	{
	  $type = $figure[0];
	  switch ( $type )
	  {
	    case 'P': $diff[0]++; break;
	    case 'N': $diff[1]++; break;
	    case 'B': $diff[2]++; break;
	    case 'R': $diff[3]++; break;
	    case 'Q': $diff[4]++; break;
	  }
	  $board[boardCoordToIndex(substr($figure,1))] = "w$type";
	}
	/* black figures */
	$b_figures = explode( " ", $black_figs );
	foreach ( $b_figures as $figure )
	{
	  $type = $figure[0];
	  switch ( $type )
	  {
	    case 'P': $diff[0]--; break;
	    case 'N': $diff[1]--; break;
	    case 'B': $diff[2]--; break;
	    case 'R': $diff[3]--; break;
	    case 'Q': $diff[4]--; break;
	  }
	  $board[boardCoordToIndex(substr($figure,1))] = "b$type";
	}

	if ( $diff[0] != 0 || $diff[1] != 0 || $diff[2] != 0 ||
	     $diff[3] != 0 || $diff[4] != 0 )
	  return $diff;
	else
	  return 0;
}


/* convert board coords [a-h][1-8] to 1dim index [0..63] */
function boardCoordToIndex( $coord )
{
	//echo $coord," --> ";
	switch ( $coord[0] )
	{
		case 'a': $x = 0; break;
		case 'b': $x = 1; break;
		case 'c': $x = 2; break;
		case 'd': $x = 3; break;
		case 'e': $x = 4; break;
		case 'f': $x = 5; break;
		case 'g': $x = 6; break;
		case 'h': $x = 7; break;
		default: return 64; /* erronous coord */
	}  
	$y = $coord[1]-1;
	if ( $y < 0 || $y > 7 )
		return 64; /* erronous coord */
	$index = $y * 8 + $x;
	//echo "$index | ";
	return $index;
}
	 
	 
/* convert board index [0..63] to coords [a-h][1-8] */
function boardIndexToCoord( $index )
{
	//echo $index," --> ";
	if ( $index < 0 || $index > 63 )
		return "";
	$y = floor($index/8)+1;
	$x = chr( ($index%8)+97 );
	$coord = "$x$y";
	//echo "$coord | ";
	return $coord;
}




function getFullFigureName( $short )
{
	$name = "empty";

	switch ( $short )
	{
		case 'P': $name = "pawn"; break;
		case 'R': $name = "rook"; break;
		case 'N': $name = "knight"; break;
		case 'B': $name = "bishop"; break;
		case 'K': $name = "king"; break;
		case 'Q': $name = "queen"; break;
	}

	return $name;
}


// This was completely rewritten in MCC to use an ELO ranking

function computeExpectedScore ( $player, $opponent )
{
	return 1. / ( 1. + pow(10., ( $opponent->getScorePoints() - $player->getScorePoints() ) / 400.) );
}

function updateStats( $white, $black, $result )
{
	$pl_w = new Player($white);
	$pl_b = new Player($black);

	$user_white = array( $white,$pl_w->getScoreWins(),$pl_w->getScoreDraws(),$pl_w->getScoreLosses(),$pl_w->getScorePoints(),0,0 );
	$user_black = array( $black,$pl_b->getScoreWins(),$pl_b->getScoreDraws(),$pl_b->getScoreLosses(),$pl_b->getScorePoints(),0,0 );


	/* translate result and update both players */
	if ( $result == '-' ) {
		$score = 0.5;
		$pl_w->setScoreDraws($pl_w->getScoreDraws() + 1);
		$pl_b->setScoreDraws($pl_b->getScoreDraws() + 1);
	}
	else if ( $result == 'w' ) {
		$score = 1.0;
		$pl_w->setScoreWins($pl_w->getScoreWins() + 1);
		$pl_b->setScoreLosses($pl_b->getScoreLosses() + 1);
	}
	else {
		$score = 0.0;
		$pl_w->setScoreLosses($pl_w->getScoreLosses() + 1);
		$pl_b->setScoreWins($pl_b->getScoreWins() + 1);
	}

	$expected_w = computeExpectedScore($pl_w, $pl_b);
	$delta_w = floor(48 * ( $score - $expected_w ));
	$delta_b = -$delta_w;

	$pl_w->setScorePoints( $pl_w->getScorePoints() + $delta_w);
	$pl_b->setScorePoints( $pl_b->getScorePoints() + $delta_b);
}

?>
