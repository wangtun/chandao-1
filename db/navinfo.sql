ALTER TABLE `zt_task` ADD `source` varchar(10) NOT NULL AFTER `fromBug`;
ALTER TABLE `zt_bug` ADD `discoverPhase` varchar(30) NOT NULL AFTER `keywords`;
ALTER TABLE `zt_bug` ADD `toIssue` mediumint(8) NOT NULL AFTER `toStory`;
ALTER TABLE `zt_story` ADD `customPlan` text NOT NULL AFTER `plan`;
ALTER TABLE `zt_story` ADD `testDate` date NOT NULL AFTER `customPlan`;
ALTER TABLE `zt_story` ADD `specialPlan` date NOT NULL AFTER `testDate`;
ALTER TABLE `zt_story` ADD `reviewed` enum('','0','1','2') NOT NULL DEFAULT '' AFTER `reviewStatus`;
ALTER TABLE `zt_story` ADD  `storyReviewID` varchar(50) NOT NULL AFTER `reviewed`;
ALTER TABLE `zt_story` ADD `ifLinkStories` varchar(10) NOT NULL COMMENT '是否有关联需求' AFTER `childStories`;
ALTER TABLE `zt_story` ADD `testStatus` varchar(30) NOT NULL AFTER `status`;
ALTER TABLE `zt_story` ADD `verifyStatus` varchar(30) NOT NULL AFTER `testStatus`;
ALTER TABLE `zt_project` ADD `lockStory` enum('0','1') NOT NULL DEFAULT '0' AFTER `stage`;
ALTER TABLE `zt_project` ADD `lockPatchBuild` enum('0','1') NOT NULL COMMENT '补丁锁定' AFTER `lockStory`;
