<?php

/**
 * Copyright (c) 2020. Quaresma.
 */

$game_options = [
    // note: game variant ID should start at 100 (ie: 100, 101, 102, ...). The maximum is 199.
    100 => [
        'name' => totranslate('Duration of the game'),    
        'values' => [
            1 => ['name' => totranslate('50 points') ],
            2 => ['name' => totranslate('100 points') ],
            3 => ['name' => totranslate('5 round max') ],
            4 => ['name' => totranslate('10 round max') ]
        ]
    ],
    101 => [
        'name' => totranslate('Skip'),
        'values' => [
            0 => [
                'name' => totranslate('Off'),
                'description' => totranslate('You can\'t plays cards of the same rank as the previous player.')
            ],
            1 => [
                'name' => totranslate('On'),
                'description' => totranslate('When a player plays the same number of cards of the same rank as the previous player skips the next person who would have played (excluding the best card 2 or 3 in case of revolution and jokers).')
            ]
        ]
    ]
];


