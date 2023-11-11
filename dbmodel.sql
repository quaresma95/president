-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- Fixes and variants implementation: © ufm <tel2tale@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

CREATE TABLE IF NOT EXISTS `card` (
    `card_id` tinyint(2) unsigned NOT NULL AUTO_INCREMENT,
    `card_type` tinyint(2) NOT NULL,
    `card_type_arg` tinyint(2) NOT NULL,
    `card_location` varchar(16) NOT NULL,
    `card_location_arg` int(11) NOT NULL,
    PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `player` ADD `player_has_passed` tinyint(2) UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player` ADD `player_role` tinyint(2) NOT NULL DEFAULT 0;