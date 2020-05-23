<?php
/**
 * Copyright (c) 2020. Quaresma.
 */

$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array( "newHand" => 20 )
    ),

    /// New hand
    20 => array(
        "name" => "newHand",
        "description" => "",
        "type" => "game",
        "action" => "stNewHand",
        "updateGameProgression" => true,
        "transitions" => array( "playerTurn" => 31, "presidentSwapTurn" => 50)
    ),

    // Trick
    30 => array(
        "name" => "newRound",
        "description" => "",
        "type" => "game",
        "action" => "stNewRound",
        "transitions" => array( "playerTurn" => 31)
    ),

    31 => array(
        "name" => "playerTurn",
        "description" => clienttranslate('${actplayer} must play or pass'),
        "descriptionmyturn" => clienttranslate('${you} must play or pass'),
        "type" => "activeplayer",
        "possibleactions" => array( "playCard", "passTurn" ),
        "transitions" => array( "newRound" => 30, "nextPlayer" => 32, "passTurn" => 33 )
    ),

    32 => array(
        "name" => "nextPlayer",
        "description" => "",
        "type" => "game",
        "action" => "stNextPlayer",
        "transitions" => array( "playerTurn" => 31, "newRound" => 30, "endHand" => 40 )
    ),

    33 => array(
        "name" => "passTurn",
        "description" => "",
        "type" => "game",
        "action" => "stNextPlayer",
        "transitions" => array( "nextPlayer" => 31, "newRound" => 30, "endHand" => 40 )
    ),

    // End of the hand (scoring, etc...)
    40 => array(
        "name" => "endHand",
        "description" => "",
        "type" => "game",
        "action" => "stEndHand",
        "transitions" => array( "newHand" => 20, "endGame" => 99 )
    ),

    50 => array(
        "name" => "presidentSwapTurn",
        "description" => clienttranslate("Other players must swap cards"),
        "descriptionmyturn" => clienttranslate('${you} must swap 2 cards'),
        "possibleactions" => array( "swapCards"),
        "type" => "activeplayer",
        "args" => "argSwapCards",
        "transitions" => array( "endPresidentSwap" => 51)
    ),

    51 => array(
        "name" => "endPresidentSwap",
        "description" => "",
        "type" => "game",
        "action" => "stSwapCards",
        "transitions" => array( "primeMinisterSwapTurn" => 52)
    ),

    52 => array(
        "name" => "primeMinisterSwapTurn",
        "description" => clienttranslate("Other players must swap cards"),
        "descriptionmyturn" => clienttranslate('${you} must swap 1 card'),
        "possibleactions" => array( "swapCards"),
        "type" => "activeplayer",
        "args" => "argSwapCards",
        "transitions" => array( "endPrimeMinisterSwapTurn" => 53)
    ),

    53 => array(
        "name" => " ",
        "description" => "",
        "type" => "game",
        "action" => "stSwapCards",
        "transitions" => array( "playerTurn" => 31)
    ),

    // Final state.
    // Please do not modify (and do not overload action/args methods).
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);



