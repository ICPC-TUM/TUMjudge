-- This is the database upgrade tables file needed for TUMjudge.

ALTER TABLE `problem`
ADD COLUMN `difficulty` varchar(255) NOT NULL COMMENT 'Estimated difficulty of problem',
ADD COLUMN `author` varchar(255) NOT NULL COMMENT 'Author of problem',
ADD COLUMN `source` varchar(255) NOT NULL COMMENT 'Source of problem',
ADD COLUMN `topic` varchar(255) NOT NULL COMMENT 'Topic of problem';


ALTER TABLE `contest`
ADD COLUMN `shuffle` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Is scoreboard shuffle enabled?';