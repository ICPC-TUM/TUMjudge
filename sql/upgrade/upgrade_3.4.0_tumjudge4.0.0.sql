-- This is the database upgrade tables file needed for TUMjudge.

-- @UPGRADE-CHECK@
ALTER TABLE `problem` ADD  COLUMN `difficulty` varchar(255) DEFAULT NULL;
ALTER TABLE `problem` DROP COLUMN `difficulty`;

--problem table
ALTER TABLE `problem`
ADD COLUMN `difficulty` varchar(255) DEFAULT NULL COMMENT 'Estimated difficulty of problem',
ADD COLUMN `author` varchar(255) DEFAULT NULL COMMENT 'Author of problem',
ADD COLUMN `source` varchar(255) DEFAULT NULL COMMENT 'Source of problem',
ADD COLUMN `topic` varchar(255) DEFAULT NULL COMMENT 'Topic of problem';

--bonus points table
CREATE TABLE IF NOT EXISTS `bonus_points` (
  `bonusid` int(11) NOT NULL AUTO_INCREMENT,
  `teamid` int(4) unsigned NOT NULL,
  `cid` int(4) unsigned NOT NULL,
  `probid` int(4) unsigned DEFAULT NULL,
  `points` int(4) NOT NULL,
  `reason` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`bonusid`),
  KEY `teamid` (`teamid`),
  KEY `cid` (`cid`),
  KEY `probid` (`probid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='bonus points assigned to teams' AUTO_INCREMENT=1 ;
ALTER TABLE `bonus_points`
  ADD CONSTRAINT `bonus_points_ibfk_4` FOREIGN KEY (`probid`) REFERENCES `problem` (`probid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `bonus_points_ibfk_2` FOREIGN KEY (`teamid`) REFERENCES `team` (`teamid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `bonus_points_ibfk_3` FOREIGN KEY (`cid`) REFERENCES `contest` (`cid`) ON DELETE CASCADE ON UPDATE CASCADE;

--contest table
ALTER TABLE `contest`
ADD COLUMN `shuffle` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Is scoreboard shuffle enabled?';
