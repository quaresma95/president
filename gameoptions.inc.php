<?php

/**
 * Copyright (c) 2020. Quaresma.
 */
const OptGameDuration = 100;
const OptSkipOn = 101;
const OptRevolutionOn = 102;
const OptJokersOn = 103;
const OptMaxCardsPerPlayerHand = 104;
const OptHighestCard = 105;

$game_options = [
    // note: game variant ID should start at 100 (ie: 100, 101, 102, ...). The maximum is 199.
    OptGameDuration => [
        'name' => totranslate('Duration of the game'),    
        'values' => [
            1 => ['name' => totranslate('50 points') ],
            2 => ['name' => totranslate('100 points') ],
            3 => ['name' => totranslate('5 round max') ],
            4 => ['name' => totranslate('10 round max') ]
        ]
    ],
    OptSkipOn => [
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
    ],
    OptRevolutionOn => [
        'name' => totranslate('Revolution'),
        'values' => [
            0 => [
                'name' => totranslate('Off'),
                'description' => totranslate('Playing 4 cards will not trigger (or cancel) Revolution.')
            ],
            1 => [
                'name' => totranslate('On'),
                'description' => totranslate('When a player plays 4 identical cards, Revolution is triggered and the value-order of all cards (except Jokers) is reversed - until another player plays 4 cards.')
            ]
        ]
    ],
    OptJokersOn => [
        'name' => totranslate('Jokers'),
        'values' => [
            False => [
                'name' => totranslate('Off'),
                'description' => totranslate('No Jokers will be added to the deck')
            ],
            True => [
                'name' => totranslate('On'),
                'description' => totranslate('2 Jokers will be added to the deck; A Joker beats any hand (except a Joker) and is unaffected by Revolution')
            ]
        ]
    ],
    OptMaxCardsPerPlayerHand => [
        'name' => totranslate('Max Number of Cards per Player'),
        'values' => [
            14 => [
                'name' => totranslate('Unlimited'),
                'description' => totranslate('A full deck of cards will be used (plus optionally Jokers); this translates to up to 14 cards per player in a 4-player game')
            ],
            8 => [
                'name' => totranslate('8'),
                'description' => totranslate('Players will start with up to 8 hands in hand. Irrelevant for 7 and 8 player games, as there are not enough cards in a deck to deal that many anyway')
            ],
        ]
    ],
    OptHighestCard => [
        'name' => totranslate('Highest card in the deck'),
        'values' => [
            15 => [
                'name' => totranslate('2'),
                'description' => totranslate('The 2 is the highest card, then Ace, King etc.')
            ],
            14 => [
                'name' => totranslate('Ace'),
                'description' => totranslate('Standard card ranking, with Ace highest, 2 lowest')
            ],
        ]
    ]
];


