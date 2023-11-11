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

$custom_only = ['type' => 'otheroption', 'id' => 114, 'value' => 100];
$game_options = [
    100 => [
        'name' => totranslate('Game length'),
        'values' => [
            2 => [
                'name' => totranslate('Short game'),
                'description' => totranslate('2x player count for positive scoring, 10 starting points for negative scoring'),
                'tmdisplay' => totranslate('Short game'),
            ],
            3 => [
                'name' => totranslate('Medium game'),
                'description' => totranslate('3x player count for positive scoring, 15 starting points for negative scoring'),
                'tmdisplay' => totranslate('Medium game'),
            ],
            4 => [
                'name' => totranslate('Long game'),
                'description' => totranslate('4x player count for positive scoring, 20 starting points for negative scoring'),
                'tmdisplay' => totranslate('Long game'),
            ],
        ],
        'default' => 3,
    ],
    113 => [
        'name' => totranslate('Scoring rule'),
        'values' => [
            0 => [
                'name' => totranslate('Positive scoring'),
                'description' => totranslate('Each player scores points equal to the number of players they have beaten in the round. The game ends when someone reaches the target score.'),
                'tmdisplay' => totranslate('Positive scoring'),
            ],
            1 => [
                'name' => totranslate('Negative scoring'),
                'description' => totranslate('President gains 1 point bonus, each Citizen loses 1 point, Peasant loses 2 points, and Beggar loses 3 points. The game ends when someone drops to 0 points or below.'),
                'tmdisplay' => totranslate('Negative scoring'),
            ],
        ],
    ],
    114 => [
        'name' => totranslate('Rule set'),
        'values' => [
            0 => [
                'name' => totranslate('Moderate variant'),
                'description' => totranslate('Playing 4 or more cards reverses card ranks or cancels the rank reversal during the round.
<br>Add a Joker which can be used as the strongest single or a wild.
<br>A player who plays the same combination of the same rank as the previous play skips the next person who would have played.
<br>A players went out by playing 2 when card ranks are normal or 3 when card ranks are reversed, or the Joker (even as a wild card), is disqualified and is ranked the lowest. If several players are disqualified by this rule in the same round, the player who went out later is ranked higher.
<br>If the President fails to get the first place, the player is disqualified immediately and is ranked the lowest unless someone else is disqualified by illegal finish.
<br>From the second round, the lowest ranked player plays first.'),
                'tmdisplay' => totranslate('Moderate variant'),
            ],
            1 => [
                'name' => totranslate('Full variant'),
                'description' => totranslate('Activates all card-related variants and Downfall.'),
                'tmdisplay' => totranslate('Full variant'),
            ],
            2 => [
                'name' => totranslate('No variant'),
                'description' => totranslate('Removes all variants.'),
                'tmdisplay' => totranslate('No variant'),
            ],
            100 => [
                'name' => totranslate('Custom'),
                'description' => totranslate('Table creator can customize variant options.'),
            ],
        ],
        'startcondition' => [2 => [['type' => 'otheroptionisnot', 'id' => 201, 'value' => 2, 'message' => totranslate('This option cannot be selected in Arena mode')]]],
    ],
    102 => [
        'name' => totranslate('Revolution'),
        'values' => [
            0 => ['name' => totranslate('Disabled')],
            1 => [
                'name' => totranslate('Enabled'),
                'description' => totranslate('Playing 4 or more cards reverses card ranks or cancels the rank reversal during the round.'),
                'tmdisplay' => totranslate('Revolution'),
            ],
        ],
        'displaycondition' => [$custom_only],
        'startcondition' => [0 => [['type' => 'otheroptionisnot', 'id' => 201, 'value' => 2, 'message' => totranslate('This option cannot be selected in Arena mode')]]],
        'default' => 1,
    ],
    103 => [
        'name' => totranslate('Joker'),
        'values' => [
            0 => ['name' => totranslate('Disabled')],
            1 => [
                'name' => totranslate('Enabled'),
                'description' => totranslate('1 Joker is added. The Joker can be used as the strongest single card which is unaffected by Revolutions, or can be mixed with other cards as a wild.'),
                'tmdisplay' => totranslate('Joker'),
            ],
        ],
        'displaycondition' => [$custom_only],
        'default' => 1,
    ],
    104 => [
        'name' => totranslate('First player'),
        'values' => [
            1 => [
                'name' => totranslate('Highest player first'),
                'description' => totranslate('From the second round, the highest ranked player plays first.'),
                'tmdisplay' => totranslate('Highest player first'),
            ],
            0 => [
                'name' => totranslate('Lowest player first'),
                'description' => totranslate('From the second round, the lowest ranked player plays first.'),
                'tmdisplay' => totranslate('Lowest player first'),
            ],
        ],
        'displaycondition' => [$custom_only],
        'startcondition' => [1 => [['type' => 'otheroptionisnot', 'id' => 201, 'value' => 2, 'message' => totranslate('This option cannot be selected in Arena mode')]]],
        'default' => 0,
    ],
    101 => [
        'name' => totranslate('Same rank skip'),
        'values' => [
            0 => [
                'name' => totranslate('Disabled'),
                'description' => totranslate('The same combination of the same rank as the previous play cannot be played.'),
            ],
            1 => [
                'name' => totranslate('Enabled'),
                'description' => totranslate('A player who plays the same combination of the same rank as the previous play skips the next person who would have played. In permanent pass variant, a player skipped by this rule may play later unless the trick ends before their turn.'),
                'tmdisplay' => totranslate('Same rank skip'),
            ],
        ],
        'displaycondition' => [$custom_only],
        'default' => 1,
    ],
    116 => [
        'name' => totranslate('Unskippable 2/3'),
        'values' => [
            0 => ['name' => totranslate('Disabled')],
            1 => [
                'name' => totranslate('Enabled'),
                'description' => totranslate('2s (or 3s during card rank reversal) cannot be beaten by the same rank.'),
                'tmdisplay' => totranslate('Unskippable 2/3'),
            ],
        ],
        'displaycondition' => [$custom_only, ['type' => 'otheroption', 'id' => 101, 'value' => 1]],
    ],
    106 => [
        'name' => totranslate('Same suit sequence'),
        'values' => [
            0 => ['name' => totranslate('Disabled')],
            1 => [
                'name' => totranslate('Enabled'),
                'description' => totranslate('3 or more cards of consecutive rank and the same suit can be played as a valid combination. If applicable, all special effects of cards in the combination are applied.'),
                'tmdisplay' => totranslate('Same suit sequence'),
            ],
        ],
        'displaycondition' => [$custom_only],
        'default' => 1,
    ],
    112 => [
        'name' => totranslate('Suit lock'),
        'values' => [
            0 => ['name' => totranslate('Disabled')],
            1 => [
                'name' => totranslate('Enabled'),
                'description' => totranslate('If a player plays a combination of the same suit as the previous play, all further plays during the same trick must be the same suit combination. The Joker cannot activate a suit lock, but can be used as the required suit during a locked trick.'),
                'tmdisplay' => totranslate('Suit lock'),
            ],
        ],
        'displaycondition' => [$custom_only],
        'default' => 1,
    ],
    107 => [
        'name' => totranslate('Ender 8'),
        'values' => [
            0 => ['name' => totranslate('Disabled')],
            1 => [
                'name' => totranslate('Enabled'),
                'description' => totranslate('Playing one or more 8s ends the trick immediately.'),
                'tmdisplay' => totranslate('Ender 8'),
            ],
        ],
        'displaycondition' => [$custom_only],
        'default' => 1,
    ],
    108 => [
        'name' => totranslate('Reversing 9'),
        'values' => [
            0 => ['name' => totranslate('Disabled')],
            1 => [
                'name' => totranslate('Enabled'),
                'description' => totranslate('Playing one or more 9s reverses the turn order permanently.'),
                'tmdisplay' => totranslate('Reversing 9'),
            ],
        ],
        'displaycondition' => [$custom_only, ['type' => 'minplayers', 'value' => [3, 4, 5, 6, 7, 8]]],
        'default' => 1,
    ],
    109 => [
        'name' => totranslate('Jack back'),
        'values' => [
            0 => ['name' => totranslate('Disabled')],
            1 => [
                'name' => totranslate('Enabled'),
                'description' => totranslate('Playing one or more Jacks reverses card ranks or cancels the rank reversal during the same trick.'),
                'tmdisplay' => totranslate('Jack back'),
            ],
        ],
        'displaycondition' => [$custom_only],
        'default' => 1,
    ],
    110 => [
        'name' => totranslate('Illegal finish'),
        'values' => [
            0 => ['name' => totranslate('Disabled')],
            1 => [
                'name' => totranslate('Enabled'),
                'description' => totranslate('A players went out by playing 2 when card ranks are normal, 3 when card ranks are reversed, 8 when Ender 8 option is active, or the Joker (even as a wild card), is disqualified and is ranked the lowest. If several players are disqualified by this rule in the same round, the player who went out later is ranked higher.'),
                'tmdisplay' => totranslate('Illegal finish'),
            ],
        ],
        'displaycondition' => [$custom_only],
        'startcondition' => [0 => [['type' => 'otheroptionisnot', 'id' => 201, 'value' => 2, 'message' => totranslate('This option cannot be selected in Arena mode')]]],
        'default' => 1,
    ],
    111 => [
        'name' => totranslate('Downfall'),
        'values' => [
            0 => ['name' => totranslate('Disabled')],
            1 => [
                'name' => totranslate('Enabled'),
                'description' => totranslate('If the President (or the Minister in 3-player) fails to get the first place, the player is disqualified immediately and is ranked the lowest unless someone else is disqualified by illegal finish.'),
                'tmdisplay' => totranslate('Downfall'),
            ],
        ],
        'displaycondition' => [$custom_only, ['type' => 'minplayers', 'value' => [3, 4, 5, 6, 7, 8]]],
        'startcondition' => [0 => [['type' => 'otheroptionisnot', 'id' => 201, 'value' => 2, 'message' => totranslate('This option cannot be selected in Arena mode')]]],
        'default' => 1,
    ],
    115 => [
        'name' => totranslate('Permanent pass'),
        'values' => [
            0 => ['name' => totranslate('Disabled')],
            1 => [
                'name' => totranslate('Enabled'),
                'description' => totranslate('A player passed their turn is completely out of the trick and cannot play cards again during that trick.'),
                'tmdisplay' => totranslate('Permanent pass'),
            ],
        ],
        'displaycondition' => [$custom_only],
    ],
    105 => [
        'name' => totranslate('Automatic turn skip'),
        'values' => [
            0 => ['name' => totranslate('Disabled')],
            1 => [
                'name' => totranslate('Enabled'),
                'description' => totranslate('The game checks only public information to determine whether a player has any chance of beating the current combination regardless of the cards in hand. If all relevant higher cards are discarded already, the turn is skipped automatically.'),
                'tmdisplay' => totranslate('Automatic turn skip'),
            ],
        ],
        'level' => 'additional',
        'default' => 1,
    ],
];

$game_preferences = [
    100 => [
        'name' => totranslate('Card style'),
        'needReload' => true,
        'values' => [
            1 => ['name' => totranslate('Small indexes')],
            2 => ['name' => totranslate('Large indexes')],
            3 => ['name' => totranslate('Cartoonish')],
        ],
    ],
    101 => [
        'name' => totranslate('Overlap cards in hand'),
        'needReload' => true,
        'values' => [
            1 => ['name' => totranslate('Disabled')],
            2 => ['name' => totranslate('Enabled')],
        ],
    ],
    102 => [
        'name' => totranslate('Play sound effects'),
        'needReload' => true,
        'values' => [
            1 => ['name' => totranslate('Yes')],
            2 => ['name' => totranslate('No')],
        ],
    ],
    103 => [
        'name' => totranslate('Permanent pass warning'),
        'needReload' => true,
        'values' => [
            1 => ['name' => totranslate('Yes')],
            2 => ['name' => totranslate('No')],
        ],
    ],
];