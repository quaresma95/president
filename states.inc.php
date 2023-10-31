<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Fixes and variants implementation: © ufm <tel2tale@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

$machinestates = [
    1 => [
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => ["" => 20]
    ],

    20 => [
        "name" => "startRound",
        "description" => '',
        "type" => "game",
        "action" => "stStartRound",
        "updateGameProgression" => true,
        "transitions" => ["playerTurn" => 31, "presidentGive" => 50, "ministerGive" => 52]
    ],

    50 => [
        "name" => "presidentGive",
        "description" => clienttranslate('${actplayer} must give 2 cards to ${otherplayer}'),
        "descriptionmyturn" => clienttranslate('${you} must give 2 cards to ${otherplayer}'),
        "type" => "activeplayer",
        "args" => "argGiveCard",
        "possibleactions" => ["giveCard"],
        "transitions" => ["" => 51]
    ],

    51 => [
        "name" => "giveEnd",
        "description" => '',
        "type" => "game",
        "action" => "stGiveEnd",
        "transitions" => ["ministerGive" => 52, "playerTurn" => 31]
    ],

    52 => [
        "name" => "ministerGive",
        "description" => clienttranslate('${actplayer} must give a card to ${otherplayer}'),
        "descriptionmyturn" => clienttranslate('${you} must give a card to ${otherplayer}'),
        "type" => "activeplayer",
        "args" => "argGiveCard",
        "possibleactions" => ["giveCard"],
        "transitions" => ["" => 51]
    ],

    31 => [
        "name" => "playerTurn",
        "description" => clienttranslate('${actplayer} may play a valid combination'),
        "descriptionmyturn" => clienttranslate('${you} may play a valid combination'),
        "type" => "activeplayer",
        "args" => "argPlayerTurn",
        "possibleactions" => ["playCard", "passTurn"],
        "transitions" => ["" => 32]
    ],

    32 => [
        "name" => "nextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stNextPlayer",
        "transitions" => ["nextPlayer" => 31, "autoPlay" => 32, "autoPass" => 32, "endTrick" => 33, "endRound" => 40]
    ],

    33 => [
        "name" => "endTrick",
        "description" => '',
        "type" => "game",
        "action" => "stEndTrick",
        "transitions" => ["nextTrick" => 31, "autoPlay" => 32, "endRound" => 40]
    ],

    40 => [
        "name" => "endRound",
        "description" => '',
        "type" => "game",
        "action" => "stEndRound",
        "transitions" => ["nextRound" => 20, "endGame" => 99]
    ],

    99 => [
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    ]
];