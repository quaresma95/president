<?php

/**
 * Copyright (c) 2020. Quaresma.
 */

$stats_type = array(
    // Statistics global to table
    "table" => array(
        "Hand_number" => [
            "id"   => 10,
            "name" => totranslate("Number of hands"),
            "type" => "int"
        ],
    ),
    
    // Statistics existing for each player
    "player" => array(
        "player_president_stat" => [
            "id"   => 10,
            "name" => totranslate("player finished president"),
            "type" => "int"
        ],
        "player_beggar_stat" => [
            "id"   => 11,
            "name" => totranslate("player finished beggar"),
            "type" => "int"
        ],
        "player_pass_turn" => [
            "id"   => 12,
            "name" => totranslate("player passes"),
            "type" => "int"
        ],
        "player_round_win" => [
            "id"   => 13,
            "name" => totranslate("player round won"),
            "type" => "int"
        ],
        "player_revolution" => [
            "id"   => 14,
            "name" => totranslate("player made a revolution"),
            "type" => "int"
        ],
    )
);
