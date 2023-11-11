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

$stats_type = [
    "table" => [
        "round_number" => [
            "id" => 10,
            "name" => totranslate("Number of rounds"),
            "type" => "int",
        ],
    ],

    "player" => [
        "first_rank" => [
            "id" => 10,
            "name" => totranslate("First ranked rounds"),
            "type" => "int",
        ],
        "second_rank" => [
            "id" => 15,
            "name" => totranslate("Second ranked rounds"),
            "type" => "int",
        ],
        "second_last_rank" => [
            "id" => 16,
            "name" => totranslate("Second last ranked rounds"),
            "type" => "int",
        ],
        "last_rank" => [
            "id" => 11,
            "name" => totranslate("Last ranked rounds"),
            "type" => "int",
        ],
        "revolution" => [
            "id" => 17,
            "name" => totranslate("Revolution"),
            "type" => "int",
        ],
        "suit_lock" => [
            "id" => 18,
            "name" => totranslate("Suit lock"),
            "type" => "int",
        ],
        "illegal_finish" => [
            "id" => 19,
            "name" => totranslate("Illegal finish"),
            "type" => "int",
        ],
        "downfall" => [
            "id" => 20,
            "name" => totranslate("Downfall"),
            "type" => "int",
        ],
    ],
];
