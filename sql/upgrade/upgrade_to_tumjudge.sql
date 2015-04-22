-- This is the database upgrade tables file needed for TUMjudge.

ALTER TABLE `problem`
ADD COLUMN `difficulty` varchar(255) DEFAULT NULL COMMENT 'Estimated difficulty of problem',
ADD COLUMN `author` varchar(255) DEFAULT NULL COMMENT 'Author of problem',
ADD COLUMN `source` varchar(255) DEFAULT NULL COMMENT 'Source of problem',
ADD COLUMN `topic` varchar(255) DEFAULT NULL COMMENT 'Topic of problem';