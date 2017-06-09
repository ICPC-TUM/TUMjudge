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

--configuration values
INSERT INTO `configuration` (`name`, `value`, `type`, `description`) VALUES
('links', '{}', 'array_keyval', 'Links to website that should be listed in the menu of participants. List the link names on the left and the URLs on the right.'),
('sso_secret', '"replacemeasap"', 'string', 'The secret to use for SSO login.');

--octave
INSERT INTO `executable` (`execid`, `md5sum`, `zipfile`, `description`, `type`) VALUES
('octave', 'c222793126c4cc0e8987c7be500c781a', 0x504b03040a00000000002f527e48d08595fc1f0000001f00000005001c006275696c645554090003b9a7fb562ca5fb5675780b00010421000000042100000023212f62696e2f73680a23206e6f7468696e6720746f20636f6d70696c650a504b03041400000008002f527e48c60db8d6fd020000a205000003001c0072756e5554090003b9a7fb562ca5fb5675780b0001042100000004210000006d546d6fd33010fe9e5f714bc7cacbd26e7c2c6c0875012ab155629d40e24dae73692c523bb29d7515e2bff3d869b632910f497cbed7e7b9bbc1c178a9f4d8554932a0b9f4e296499a75a36aa68d154dc33673d2aac653692c0d777723570d47b0b866265f094f7b3a6eabbdb823a10b5a1bcba47469a00bed45a55caf591876a48d27217d2bea7a4be9ce770a8f4cceb456f2312d5b8f449487b6800b57715df73e62e48d82a06cb5f4ca68122e5af31dcbd68b65cd13da54ac49220417c7a43c9c44934e85f78241eeab7896c65a961ea97bb68d65bc77651de3be75f0017f66a3f40a6714f5a058f4cebca12553eb20f15668d708bc3dea448aaadc2582ff5dd5453010e43c7013b5d14ce045d86d87dcd57c319be6932ed8cec2f63048a103907db4fb2a6eaef39fd30f9fe6f3053c48a34bb56aad883099267e7c6b352c8c3ea0a737ba66e7a8b1069ce3c38f2c4a3889d854d6201aeb5b658d5eb3f654a18c2503652f7e45acc1ba2947cf92e422bf5e9ca587a729bd0275aaf4c9657ef97176397b2c7d3bbbba9edf7c9ae6511e9a715ab1fcd535dde060f80f15b5d2e0b5307ae83b1e8066800fd7b7211fcf621d382aad5953239c0b3c7525bba017cad8f3374ac0c7ca7243c31f21547af8904e4ae7e3826fc7ba45cbbc3c3f3a45ca30d709e16159194a736b8d9dfcdb2b5e780ed03c75cf5042ab8b491a2d62944cff2f50e7f10e959c26a57a40a06ec5aef726c900074959f3c872907f992da6f38bfcecf04d32f88adb5e902218d3097da7a3a3cef9fd5588f0390cd66e1202465d434d1289b93a879b405f4aaf5fe7f377c9e07e530ce83d6b465f84660ff3d86f0840db4f95e956c93e2868a26e344621f4056498b042592dd61cb785ac845e7110ed93296b10183745049238c0ed2267a8f4dbe1ef9327e3e77f523a380ba79394beef31248b3d95086b48b0cf2ecb1ce60831b24c9b6cd5aaee0733e68ddd7607a595cfcab00e1f8e8dc084c563e8c48c0b15d18b120744f7f4b1220ab3c9dcd6a121a2c8ca47ec05701359ad4d41e2c55d8f7ac814749d247f01504b01021e030a00000000002f527e48d08595fc1f0000001f000000050018000000000001000000ed81000000006275696c645554050003b9a7fb5675780b000104210000000421000000504b01021e031400000008002f527e48c60db8d6fd020000a2050000030018000000000001000000ed815e00000072756e5554050003b9a7fb5675780b000104210000000421000000504b0506000000000200020094000000980300000000, 'octave', 'compile');

INSERT INTO `language` (`langid`, `name`, `extensions`, `allow_submit`, `allow_judge`, `time_factor`, `compile_script`) VALUES
('octave', 'Octave', '["m"]', 0, 1, 1, 'octave');
