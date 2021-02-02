<?php
/**
 * Copyright (c) 2020. Quaresma.
 */

$this->colors = array(
    1 => array( 'name' => clienttranslate('club'),
        'nametr' => self::_('club') ),
    2 => array( 'name' => clienttranslate('diamond'),
        'nametr' => self::_('diamond') ),
    3 => array( 'name' => clienttranslate('heart'),
        'nametr' => self::_('heart') ),
    4 => array( 'name' => clienttranslate('spade'),
        'nametr' => self::_('spade') )
);

$this->special_cards = [
    [
        'type'   => 5,
        'value'  => 933,
        'name'   => clienttranslate('joker'),
        'nametr' => self::_('joker'),
        'nbr'    => 1
    ],
    [
        'type'   => 5,
        'value'  => 934,
        'name'   => clienttranslate('joker'),
        'nametr' => self::_('joker'),
        'nbr'    => 1
    ]
];

$this->card_names = [
    2 => '2',
    3 => '3',
    4 => '4',
    5 => '5',
    6 => '6',
    7 => '7',
    8 => '8',
    9 => '9',
    10 => '10',
    11 => clienttranslate('J'),
    12 => clienttranslate('Q'),
    13 => clienttranslate('K'),
    14 => clienttranslate('A'),
    15 => 2,
    933 => clienttranslate('Jocker'),
    934 => clienttranslate('Jocker')
];

$this->card_count_label = [
    0 => '',
    1 => '',
    2 => clienttranslate('double'),
    3 => clienttranslate('triple'),
    4 => clienttranslate('quadruple'),
];

$this->players_roles = [
    4 => [
        1 => clienttranslate('president'),
        2 => clienttranslate('prime minister'),
        3 => clienttranslate('peasant'),
        4 => clienttranslate('beggar'),
    ],
    5 => [
        1 => clienttranslate('president'),
        2 => clienttranslate('prime minister'),
        3 => clienttranslate('citizen'),
        4 => clienttranslate('peasant'),
        5 => clienttranslate('beggar'),
    ],
    6 => [
        1 => clienttranslate('president'),
        2 => clienttranslate('prime minister'),
        3 => clienttranslate('citizen'),
        4 => clienttranslate('citizen'),
        5 => clienttranslate('peasant'),
        6 => clienttranslate('beggar'),
    ],
    7 => [
        1 => clienttranslate('president'),
        2 => clienttranslate('prime minister'),
        3 => clienttranslate('citizen'),
        4 => clienttranslate('citizen'),
        5 => clienttranslate('citizen'),
        6 => clienttranslate('peasant'),
        7 => clienttranslate('beggar'),
    ],
    8 => [
        1 => clienttranslate('president'),
        2 => clienttranslate('prime minister'),
        3 => clienttranslate('citizen'),
        4 => clienttranslate('citizen'),
        5 => clienttranslate('citizen'),
        6 => clienttranslate('citizen'),
        7 => clienttranslate('peasant'),
        8 => clienttranslate('beggar'),
    ],
];

$this->icons_infos = [
    1 => clienttranslate('iconPresident'),
    2 => clienttranslate('iconPrimeMinister'),
    3 => clienttranslate('iconCitizen'),
    4 => clienttranslate('iconPeasant'),
    5 => clienttranslate('iconBeggar'),
];

$this->icons_per_position = [
    4 => [
        1 => 'iconPresident',
        2 => 'iconPrimeMinister',
        3 => 'iconPeasant',
        4 => 'iconBeggar',
    ],
    5 => [
        1 => 'iconPresident',
        2 => 'iconPrimeMinister',
        3 => 'iconCitizen',
        4 => 'iconPeasant',
        5 => 'iconBeggar',
    ],
    6 => [
        1 => 'iconPresident',
        2 => 'iconPrimeMinister',
        3 => 'iconCitizen',
        4 => 'iconCitizen',
        5 => 'iconPeasant',
        6 => 'iconBeggar',
    ],
    7 => [
        1 => 'iconPresident',
        2 => 'iconPrimeMinister',
        3 => 'iconCitizen',
        4 => 'iconCitizen',
        5 => 'iconCitizen',
        6 => 'iconPeasant',
        7 => 'iconBeggar',
    ],
    8 => [
        1 => 'iconPresident',
        2 => 'iconPrimeMinister',
        3 => 'iconCitizen',
        4 => 'iconCitizen',
        5 => 'iconCitizen',
        6 => 'iconCitizen',
        7 => 'iconPeasant',
        8 => 'iconBeggar',
    ],
];

$this->game_duration = [
    1 => [
        'type' => 'score',
        'default_score' => 50
    ],

    2 => [
        'type' => 'score',
        'default_score' => 100
    ],

    3 => [
        'type' => 'round',
        'default_score' => 100,
        'max_round' => 5,
    ],

    4 => [
        'type' => 'round',
        'default_score' => 100,
        'max_round' => 10,
    ],
];

$this->points_per_position = [
    4 => [
        1 => -5,
        2 => 0,
        3 => 10,
        4 => 15,
    ],
    5 => [
        1 => -5,
        2 => 0,
        3 => 5,
        4 => 10,
        5 => 15,
    ],
    6 => [
        1 => -5,
        2 => 0,
        3 => 5,
        4 => 5,
        5 => 10,
        6 => 15,
    ],
    7 => [
        1 => -5,
        2 => 0,
        3 => 5,
        4 => 5,
        5 => 5,
        6 => 10,
        7 => 15,
    ],
    8 => [
        1 => -5,
        2 => 0,
        3 => 5,
        4 => 5,
        5 => 5,
        6 => 5,
        7 => 10,
        8 => 15,
    ],
];
