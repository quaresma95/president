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

$this->colors = [
    0 => '<span style="color:black">&spades;</span>',
    1 => '<span style="color:red">&hearts;</span>',
    2 => '<span style="color:black">&clubs;</span>',
    3 => '<span style="color:red">&diams;</span>',
    4 => clienttranslate('Joker'),
];

$this->values_label = [
    0 => '',
    1 => '3',
    2 => '4',
    3 => '5',
    4 => '6',
    5 => '7',
    6 => '8',
    7 => '9',
    8 => '10',
    9 => 'J',
    10 => 'Q',
    11 => 'K',
    12 => 'A',
    13 => '2',
];

$this->audio_list = ['give', 'play', 'shuffle'];