-- MySQL dump 10.11
--
-- Host: localhost    Database: net_mkgi_chess
-- ------------------------------------------------------
-- Server version	5.0.51a-6-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `mcc_article`
--

DROP TABLE IF EXISTS `mcc_article`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `mcc_article` (
  `art_id` int(11) NOT NULL auto_increment,
  `art_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `art_lang` char(2) NOT NULL default '',
  `art_author` varchar(24) NOT NULL default '',
  `art_category` varchar(8) NOT NULL default '',
  `art_text` text NOT NULL,
  `art_title` varchar(32) default NULL,
  PRIMARY KEY  (`art_id`),
  KEY `fk_author` (`art_author`),
  KEY `catlang_ix` (`art_category`,`art_lang`),
  CONSTRAINT `mcc_article_ibfk_1` FOREIGN KEY (`art_author`) REFERENCES `mcc_player` (`pl_identifier`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `mcc_email_token`
--

DROP TABLE IF EXISTS `mcc_email_token`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `mcc_email_token` (
  `et_player` varchar(24) NOT NULL default '',
  `et_token` varchar(8) NOT NULL default '',
  `et_email_address` varchar(64) NOT NULL default '',
  `et_date` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`et_token`),
  KEY `fk_player` (`et_player`),
  CONSTRAINT `mcc_email_token_ibfk_1` FOREIGN KEY (`et_player`) REFERENCES `mcc_player` (`pl_identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `mcc_game`
--

DROP TABLE IF EXISTS `mcc_game`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `mcc_game` (
  `gm_id` int(8) unsigned NOT NULL auto_increment,
  `gm_player_white` varchar(24) NOT NULL default '',
  `gm_player_black` varchar(24) NOT NULL default '',
  `gm_date_start` datetime NOT NULL default '0000-00-00 00:00:00',
  `gm_date_notification` datetime NOT NULL default '0000-00-00 00:00:00',
  `gm_status` varchar(8) NOT NULL default 'open',
  `gm_is_archived` tinyint(1) NOT NULL default '0',
  `gm_is_deleted` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`gm_id`),
  KEY `archived_idx` (`gm_is_archived`),
  KEY `deleted_idx` (`gm_is_deleted`),
  KEY `date_start_idx` (`gm_date_start`),
  KEY `player_white_idx` (`gm_player_white`),
  KEY `player_black_idx` (`gm_player_black`),
  CONSTRAINT `mcc_game_ibfk_1` FOREIGN KEY (`gm_player_white`) REFERENCES `mcc_player` (`pl_identifier`),
  CONSTRAINT `mcc_game_ibfk_2` FOREIGN KEY (`gm_player_black`) REFERENCES `mcc_player` (`pl_identifier`)
) ENGINE=InnoDB AUTO_INCREMENT=8247 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `mcc_game_cache`
--

DROP TABLE IF EXISTS `mcc_game_cache`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `mcc_game_cache` (
  `gc_game` int(8) unsigned NOT NULL default '0',
  `gc_cache` mediumblob NOT NULL,
  PRIMARY KEY  (`gc_game`),
  CONSTRAINT `mcc_game_cache_ibfk_1` FOREIGN KEY (`gc_game`) REFERENCES `mcc_game` (`gm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `mcc_history_connection`
--

DROP TABLE IF EXISTS `mcc_history_connection`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `mcc_history_connection` (
  `hc_identifier` varchar(24) NOT NULL default '',
  `hc_date` datetime NOT NULL default '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `mcc_history_score`
--

DROP TABLE IF EXISTS `mcc_history_score`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `mcc_history_score` (
  `hs_identifier` varchar(24) NOT NULL default '',
  `hs_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `hs_score_points` int(6) unsigned NOT NULL default '1200',
  KEY `hs_identifier` (`hs_identifier`),
  CONSTRAINT `mcc_history_score_ibfk_1` FOREIGN KEY (`hs_identifier`) REFERENCES `mcc_player` (`pl_identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `mcc_invitation`
--

DROP TABLE IF EXISTS `mcc_invitation`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `mcc_invitation` (
  `iv_id` int(10) unsigned NOT NULL auto_increment,
  `iv_player` varchar(24) NOT NULL default '',
  `iv_invited_address` varchar(64) NOT NULL default '',
  `iv_invited_name` varchar(32) NOT NULL default '',
  `iv_invited_player` varchar(24) default NULL,
  `iv_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `iv_opened` tinyint(1) NOT NULL default '0',
  `iv_clicked` tinyint(1) NOT NULL default '0',
  `iv_retried` tinyint(1) NOT NULL default '0',
  `iv_deleted` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`iv_id`),
  KEY `iv_player` (`iv_player`),
  CONSTRAINT `mcc_invitation_ibfk_1` FOREIGN KEY (`iv_player`) REFERENCES `mcc_player` (`pl_identifier`)
) ENGINE=InnoDB AUTO_INCREMENT=156 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `mcc_move`
--

DROP TABLE IF EXISTS `mcc_move`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `mcc_move` (
  `mv_id` int(10) unsigned NOT NULL auto_increment,
  `mv_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `mv_short` varchar(10) NOT NULL default '',
  `mv_long` varchar(64) NOT NULL default '',
  `mv_chat` text,
  `mv_drawoffer` int(1) unsigned NOT NULL default '0',
  `mv_game` int(8) unsigned NOT NULL default '0',
  `mv_teachermove` varchar(128) character set ascii default NULL,
  `mv_teacherrate` int(6) default NULL,
  `mv_score` int(6) default NULL,
  PRIMARY KEY  (`mv_id`),
  KEY `game_idx` (`mv_game`),
  KEY `date_idx` (`mv_date`),
  CONSTRAINT `mcc_move_ibfk_1` FOREIGN KEY (`mv_game`) REFERENCES `mcc_game` (`gm_id`)
) ENGINE=InnoDB AUTO_INCREMENT=387672 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `mcc_note`
--

DROP TABLE IF EXISTS `mcc_note`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `mcc_note` (
  `nt_owner` varchar(24) NOT NULL default '',
  `nt_game` int(8) unsigned NOT NULL default '0',
  `nt_text` text NOT NULL,
  UNIQUE KEY `owner_game_idx` (`nt_owner`,`nt_game`),
  KEY `nt_game` (`nt_game`),
  CONSTRAINT `mcc_note_ibfk_1` FOREIGN KEY (`nt_owner`) REFERENCES `mcc_player` (`pl_identifier`),
  CONSTRAINT `mcc_note_ibfk_2` FOREIGN KEY (`nt_game`) REFERENCES `mcc_game` (`gm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `mcc_player`
--

DROP TABLE IF EXISTS `mcc_player`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `mcc_player` (
  `pl_identifier` varchar(24) NOT NULL default '',
  `pl_password` varchar(16) character set latin1 collate latin1_bin NOT NULL default '',
  `pl_real_name` varchar(32) default NULL,
  `pl_gender` char(1) default NULL,
  `pl_age` tinyint(3) unsigned default NULL,
  `pl_country` char(2) default NULL,
  `pl_email_address` varchar(64) default NULL,
  `pl_creation_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `pl_score_wins` int(6) unsigned NOT NULL default '0',
  `pl_score_draws` int(6) unsigned NOT NULL default '0',
  `pl_score_losses` int(6) unsigned NOT NULL default '0',
  `pl_score_points` int(6) unsigned NOT NULL default '1200',
  `pl_notification_delay` char(2) NOT NULL default '01',
  `pl_newsletter` tinyint(1) unsigned NOT NULL default '1',
  `pl_is_admin` tinyint(1) unsigned NOT NULL default '0',
  `pl_is_active` tinyint(1) unsigned NOT NULL default '1',
  `pl_is_validated` tinyint(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (`pl_identifier`),
  KEY `id_score_idx` (`pl_identifier`,`pl_score_points`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `mcc_player_portrait`
--

DROP TABLE IF EXISTS `mcc_player_portrait`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `mcc_player_portrait` (
  `pp_player` varchar(24) NOT NULL default '',
  `pp_type` varchar(16) NOT NULL default '',
  `pp_data` blob NOT NULL,
  PRIMARY KEY  (`pp_player`),
  CONSTRAINT `mcc_player_portrait_ibfk_1` FOREIGN KEY (`pp_player`) REFERENCES `mcc_player` (`pl_identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2008-07-23 21:46:15
