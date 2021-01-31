<?php
/**
 * Copyright (c) 2020. Quaresma.
 */

require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );

class GS {
    public const numPlayers = "nbPlayers";
    public const isFirstRound = "firstRound";
    public const finishOrder = "finishOrder";
    public const defaultScore = "defaultScore";
    public const lastCardValue = "lastCardValue";
    public const isRevolutionTrick = "revolutionTrick";
    public const currentHandType = "currentHandType";
    public const lastPlayerPlayedId = "lastPlayerPlayedId";
    public const presidentSwapCards = "presidentSwapCards";
    public const gameDuration = "gameDuration";
    public const optSkipOn = "optSkipOn";
    public const optRevolutionOn = "optRevolutionOn";
    public const optJokersOn = "optJokersOn";
}

class Opt {
    public const gameDuration = 100;
    public const skipOn = 101;
    public const revolutionOn = 102;
    public const jokersOn = 103;
}

class President extends Table
{
    
	function __construct( )
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        
        self::initGameStateLabels([
            GS::numPlayers => 10,
            GS::isFirstRound => 11,
            GS::finishOrder => 12,
            GS::defaultScore => 13,
            GS::lastCardValue => 14,
            GS::isRevolutionTrick => 15,
            GS::currentHandType => 16,
            GS::lastPlayerPlayedId => 17,
            GS::presidentSwapCards => 18,
            GS::gameDuration => Opt::gameDuration,
            GS::optSkipOn => Opt::skipOn,
            GS::optRevolutionOn => Opt::revolutionOn,
            GS::optJokersOn => Opt::jokersOn,
        ]);

        $this->cards = self::getNew( "module.common.deck" );
        $this->cards->init( "card" );
    }
	
    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "president";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = [] )
    {    
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        self::setGameStateInitialValue(GS::isFirstRound, 1);
        self::setGameStateInitialValue(GS::finishOrder, 0);
        self::setGameStateInitialValue(GS::defaultScore, 50);
        self::setGameStateInitialValue(GS::lastCardValue, 0);
        self::setGameStateInitialValue(GS::currentHandType, 0);
        self::setGameStateInitialValue(GS::isRevolutionTrick, 0);
        self::setGameStateInitialValue(GS::lastPlayerPlayedId, 0);
        self::setGameStateInitialValue(GS::presidentSwapCards, 0);
        self::setGameStateInitialValue(GS::numPlayers, count($players));

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.

        $gameDurationOption = $this->gamestate->table_globals[Opt::gameDuration];
        self::setGameStateValue(GS::defaultScore, $this->game_duration[$gameDurationOption]['default_score']);

        $values = [];
        $default_score = self::getGameStateValue(GS::defaultScore);

        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."','$default_score')";
        }

        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar, player_score) VALUES ";
        $sql .= implode( $values, ',' ); //FIXME order
        self::DbQuery( $sql );

        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        // Init global values with their initial values
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );
        
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        self::initStat('table', 'Hand_number', 0);
        self::initStat('player', 'player_pass_turn', 0);
        self::initStat('player', 'player_round_win', 0);
        self::initStat('player', 'player_revolution', 0);
        self::initStat('player', 'player_beggar_stat', 0);
        self::initStat('player', 'player_president_stat', 0);

        // Create cards
        $cards = [];
        foreach ( $this->colors as $color_id => $color ) {
            // spade, heart, diamond, club
            for ($value = 3; $value <= 15; $value ++) {
                //  2, 3, 4, ... K, A
                $cards [] = ['type' => $color_id,'type_arg' => $value,'nbr' => 1 ];
            }
        }

        $optionJokersOn = $this->gamestate->table_globals[Opt::jokersOn];
        if ($optionJokersOn) {
            foreach ($this->special_cards as $special_card) {
                $cards [] = [
                    'type' => $special_card['type'],
                    'type_arg' => $special_card['value'],
                    'nbr' => $special_card['nbr']
                ];
            }
        }

        $this->cards->createCards( $cards, 'deck' );

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = [];
    
        $gameDurationOption = $this->gamestate->table_globals[Opt::gameDuration];
        
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score, player_has_passed pass, player_role role FROM player";
        $result['players'] = self::getCollectionFromDb( $sql );

        $result['currentHandType'] = self::getGameStateValue(GS::currentHandType);
        $result['currentOrder'] = self::getGameStateValue(GS::isRevolutionTrick);

        // Cards in player hand
        $result['nb_players'] = self::getGameStateValue(GS::numPlayers);
        $result['nb_round'] = self::getStat('Hand_number'); // Counds like an anti-pattern to use stat as game data

        if ($this->game_duration[$gameDurationOption]['type'] == 'round') {
            $result['max_round'] = $this->game_duration[$gameDurationOption]['max_round'];
        }

        // Cards in player hand
        $sql = <<<EOT
           SELECT `card_location_arg`, count(*) as `nb_cards`
           FROM `card` 
           WHERE `card_location` = 'hand' 
           GROUP by card_location_arg;
EOT;

        $result['nb_cards'] = self::getCollectionFromDb( $sql );

        $result['icons_per_position'] = $this->icons_per_position;

        // Cards in player hand
        $result['hand'] = $this->cards->getCardsInLocation( 'hand', $current_player_id );

        // Cards played on the table
        $cards_on_table = [];
        $all_cards_played = $this->cards->getCardsInLocation( 'cardsontable', null, 'card_type_arg');

        if (!empty($all_cards_played) && $result['currentHandType'] > 0) {
            while (!empty($all_cards_played)) {
                $cards_left = array_splice($all_cards_played, $result['currentHandType']);
                $cards_on_table[] = $all_cards_played;
                $all_cards_played = $cards_left;
            }
            $result['cardsontable'] = $cards_on_table;
        }

        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        $state = $this->gamestate->state();
        if( $state['name'] == 'gameEnd' ) {
            $progression = 100;
        } else {
            $gameDurationOption = $this->gamestate->table_globals[Opt::gameDuration];
            if ($this->game_duration[$gameDurationOption]['type'] == 'round') {
                $actual_round = self::getStat('Hand_number') - 1;
                $max_round = $this->game_duration[$gameDurationOption]['max_round'];
                if ($max_round == 5) {
                    $actual_round = $actual_round * 2;
                }
                $progression = $actual_round * 10;
            } else {
                $default_score = self::getGameStateValue(GS::defaultScore);
                $minScore = self::getUniqueValueFromDB( "SELECT MIN(player_score) as min FROM player" );
                if ($minScore == $default_score) {
                    $progression = 0;
                } else {
                    if ($default_score == 50) {
                        $progression = 100 - ($minScore * 2);
                    } else {
                        $progression = 100 - $minScore;
                    }
                }
            }
        } 


        return $progression;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    
    private function setUpNewRound() {
        $best_value_player_id = self::getGameStateValue(GS::lastPlayerPlayedId);
        // Active this player => he's the one who starts the next trick


        // Move all cards to "cardswon" of the given player
        $this->cards->moveAllCardsInLocation('cardsontable', 'cardswon', null, $best_value_player_id);

        // Reset global before new round
        self::setGameStateValue(GS::lastCardValue, 0);
        self::setGameStateValue(GS::currentHandType, 0);
        self::setGameStateValue(GS::lastPlayerPlayedId, 0);

        $sql = "UPDATE player SET player_has_passed='0'";
        self::DbQuery( $sql );

        // Notify
        // Note: we use 2 notifications here in order we can pause the display during the first notification
        //  before we move all cards to the winner (during the second)

        // add stat round won
        self::incStat(1, 'player_round_win', $best_value_player_id);

        $players = self::loadPlayersBasicInfos();
        self::notifyAllPlayers( 'roundWin', clienttranslate('${player_name} wins the trick'), [
            'player_id' => $best_value_player_id,
            'player_name' => $players[ $best_value_player_id ]['player_name']
        ]);
    }

    private function getNextActivePlayer() {
        $i = 0;
        $nextActivePlayerId = "";
        $playerId = self::getActivePlayerId();
        $tableOrder = self::getNextPlayerTable();
        $nbPlayers = self::getGameStateValue(GS::numPlayers);
        $players = self::getCollectionFromDb( "SELECT player_id id, player_has_passed pass, player_role role FROM player" );


        while ($nbPlayers > $i) {
            $playerId = $tableOrder [$playerId];
            if ($players[$playerId]['pass'] == 0 && !empty($this->cards->getCardsInLocation( 'hand', $playerId ))) {
                $nextActivePlayerId = $playerId;
                break;
            }
            $i++;
        }

        if (empty($nextActivePlayerId)) {
            $nextActivePlayerId = self::getGameStateValue(GS::lastPlayerPlayedId);
        }

        return $nextActivePlayerId;
    }

    private function checkHand($cards) {
        $error = false;
        $optionSkipEnabled = $this->gamestate->table_globals[Opt::skipOn] == 1 ? True : False;
        $lastCardValue = self::getGameStateValue(GS::lastCardValue);
        $currentHandType = self::getGameStateValue(GS::currentHandType);
        $currentOrder = self::getGameStateValue(GS::isRevolutionTrick);
        $best_card_current_hand = $currentOrder == 0 ? 15 : 3;

        if (empty($cards)) {
            $error = true;
        } else {
            $card = current($cards);
            // check if last card is joker
            if (in_array($lastCardValue, [933, 934])) {
                $error = true;
            } elseif (count($cards) != $currentHandType && $card['type'] != 5) {
                $error = true;
            } elseif ($lastCardValue) {
                if ($currentOrder == 0) {
                    if ($lastCardValue > $card['type_arg']) {
                        $error = true;
                    } else if ($lastCardValue == $card['type_arg']) {
                        if (($optionSkipEnabled && in_array($card['type_arg'], [933, 934, $best_card_current_hand])) || !$optionSkipEnabled) {
                            $error = true;
                        }
                    }
                } else {
                    if ($lastCardValue < $card['type_arg'] && !in_array($card['type_arg'], [933, 934])) {
                        $error = true;
                    } else if ($lastCardValue == $card['type_arg']) {
                        if (($optionSkipEnabled && in_array($card['type_arg'], [933, 934, $best_card_current_hand])) || !$optionSkipEnabled) {
                            $error = true;
                        }
                    }
                }
            }
        } 

        return $error;
    }

    private function checkEndGame() {
        $res = false;
        if ($this->cards->countCardInLocation('hand') == 0) {
            $res = true;
        }
        $players_in_game = 0;
        $hands = $this->cards->countCardsByLocationArgs( 'hand' );
        foreach ($hands as $hand) {
            if ($hand > 0) {
                $players_in_game++;
            }
            if ($players_in_game > 1) {
                break;
            }
        }
        if ($players_in_game <= 1) {
            $res = true;
        }
        return $res;
    }

    private function getBestCardsByLocation($playerId, $nbCards) {
        $i = 0;
        $res = [];
        $sql = "SELECT card_id id, card_type type, card_type_arg type_arg FROM card WHERE card_location_arg='$playerId' ORDER BY card_type_arg DESC";
        $cards = self::getCollectionFromDb( $sql );

        foreach ($cards as $card) {
            if ($i >= $nbCards) {
                break;
            }
            $res[$card['id']] = $card;
            $i++;
        }

        return $res;
    }

    private function getCardsByLocationOrderedByValue($playerId, $nbCards, $order = 'ASC') {
        $i = 0;
        $res = [];
        $sql = <<<EOT
                      SELECT card_id id, card_type type, card_type_arg type_arg FROM card 
                      WHERE card_location_arg='$playerId' AND `card_location`= 'hand' 
                      ORDER BY card_type_arg $order; 
EOT;

        $cards = self::getCollectionFromDb( $sql );

        foreach ($cards as $card) {
            if ($i >= $nbCards) {
                break;
            }
            $res[$card['id']] = $card;
            $i++;
        }

        return $res;
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    function passTurn() 
    {
        self::checkAction("passTurn");

        if (self::getGameStateValue(GS::currentHandType) == 0) {
            throw new BgaUserException( self::_("You must play") );
            $this->gamestate->nextState('');
            return;
        }

        $player_id = self::getActivePlayerId();
        self::notifyAllPlayers('passTurn', clienttranslate('${player_name} pass'), [
            'player_name' => self::getActivePlayerName(),
            'player_id' => $player_id,
        ]);

        // add stat player passes
        self::incStat(1, 'player_pass_turn', $player_id);

        $sql = "UPDATE player SET player_has_passed='1' WHERE player_id='$player_id'";
        self::DbQuery( $sql );

        // Next player
        $this->gamestate->nextState('nextPlayer');
    }

    function checkRevolutionTrick($currentOrder, $player_id, $card_ids) 
    {
        $optionRevolutionEnabled = $this->gamestate->table_globals[Opt::revolutionOn] == 1 ? True : False;
        if ($optionRevolutionEnabled && count($card_ids) == 4) {
            //notif revolution
            self::setGameStateValue(GS::isRevolutionTrick, ($currentOrder == 0 ? 1 : 0));

            //add stat revolution
            self::incStat(1, 'player_revolution', $player_id);

            self::notifyAllPlayers('revolutionTrick', clienttranslate('${player_name} made a revolution'), [
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
            ]);
        }
    }

    function checkIfPlayerHasFinished($player_id) 
    {
        if (empty($this->cards->getPlayerHand($player_id))) {
            $position = self::incGameStateValue( GS::finishOrder, 1);
            if ($position == 1) {
                // add stat president
                self::incStat(1, 'player_president_stat', $player_id);
            }
            $sql = "UPDATE player SET player_role='$position' WHERE player_id='$player_id'";
            self::DbQuery( $sql );

            $role = $this->players_roles[self::getGameStateValue(GS::numPlayers)][$position];
            self::notifyAllPlayers('playerFinished', clienttranslate('${player_name} becomes ${role}'), [
                'role' => $role,
                'role_position' => $position,
                'player_name' => self::getActivePlayerName(),
                'i18n' => ['role'],
            ]);
        }
    }

    function checkPlayerLeft() {
        $nbPlayers = self::getGameStateValue(GS::numPlayers);
        $finishOrder = self::getGameStateValue(GS::finishOrder);
        return $nbPlayers - $finishOrder;
    }

    function playCards($card_ids)
    {
        self::checkAction("playCard");
        $cards = [];
        $skipped = 0;
        $currentCard = null;
        $nb_cards = count($card_ids);
        $player_id = self::getActivePlayerId();
        $lastCardValue = self::getGameStateValue(GS::lastCardValue);
        $currentHandType = self::getGameStateValue(GS::currentHandType);
        $currentOrder = self::getGameStateValue(GS::isRevolutionTrick);
        $best_card_current_hand = $currentOrder == 0 ? 15 : 3;
        $optionSkipEnabled = $this->gamestate->table_globals[Opt::skipOn] == 1 ? True : False;

        foreach ($card_ids as $card_id) {
            $currentCard = $this->cards->getCard($card_id);
            $currentCard['card_id'] = $card_id;
            $cards[] = $currentCard;
        }

        if( $currentHandType == 0) {
            self::setGameStateValue(GS::currentHandType, $nb_cards);
        } elseif ($error = $this->checkHand($cards)) {
            if ($currentOrder == 0) {
                if ($optionSkipEnabled && $best_card_current_hand != $currentCard['type_arg']) {
                    throw new BgaUserException( self::_("You must play card(s) stronger or equal than a {$this->nb_card_label[$currentHandType]} {$this->values_label[$lastCardValue]}") );
                } else {
                    throw new BgaUserException( self::_("You must play card(s) stronger than a {$this->nb_card_label[$currentHandType]} {$this->values_label[$lastCardValue]}") );
                }
            } else {
                if ($optionSkipEnabled && $best_card_current_hand != $currentCard['type_arg']) {
                    throw new BgaUserException( self::_("You must play card(s) weaker or equal than a {$this->nb_card_label[$currentHandType]} {$this->values_label[$lastCardValue]}") );
                } else {
                    throw new BgaUserException( self::_("You must play card(s) weaker than a {$this->nb_card_label[$currentHandType]} {$this->values_label[$lastCardValue]}") );  
                }
                  
            }
            $this->gamestate->nextState('');
            return;
        }

        // check skip option
        if ($currentCard['type_arg'] == $lastCardValue && $optionSkipEnabled) {
            $skipped = 1;
        }
         
        // check revolution
        $this->checkRevolutionTrick($currentOrder, $player_id, $card_ids);
        $this->cards->moveCards($card_ids, 'cardsontable', $player_id);
        self::setGameStateValue(GS::lastCardValue, $currentCard['type_arg']);
        self::setGameStateValue(GS::lastPlayerPlayedId, $player_id);

        // And notify
        self::notifyAllPlayers('playCard', clienttranslate('${player_name} plays a ${nb_cards} ${value_displayed}'), [
            'i18n' => ['nb_cards', 'value_displayed'],
            'cards' => $cards,
            'card_id' => $currentCard['id'],
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'value' => $currentCard ['type_arg'],
            'value_displayed' => $this->values_label [$currentCard ['type_arg']],
            'color' => $currentCard ['type'],
            'nb_cards' => $this->nb_card_label[$nb_cards],
        ]);

        // check if player has finished
        $this->checkIfPlayerHasFinished($player_id);

        if ($skipped && $this->checkPlayerLeft() > 1) {
            $players = self::loadPlayersBasicInfos();
            $next_player_id = $this->getNextActivePlayer();
            if ($next_player_id != $player_id) {
                self::notifyAllPlayers('playerSkipped', clienttranslate('${player_name} skip ${player_name_skipped}'), [
                    'player_name' => self::getActivePlayerName(),
                    'player_name_skipped' => $players[ $next_player_id ]['player_name']
                ]);
                // skip next player
                $this->gamestate->nextState('nextPlayer');
            }
        }

        // Next player
        $this->gamestate->nextState('nextPlayer');
    }

    function swapCards($card_ids)
    {
        self::checkAction("swapCards");
        $msg_error = "";
        $nb_cards = count($card_ids);
        $player_id = self::getActivePlayerId();
        $nbPlayers = self::getGameStateValue(GS::numPlayers);

        $sql = "SELECT player_role role, player_id id FROM player ORDER BY role ASC";
        $players_by_position = self::getCollectionFromDb( $sql , true );

        if ($players_by_position[1] == $player_id) {
            if ($nb_cards  != 2) {
                $msg_error = self::_("You have to swap 2 cards");
            }
        } else if ($players_by_position[2] == $player_id) {
            if ($nb_cards  != 1) {
                $msg_error = self::_("You have to swap 1 card");
            }
        } else {
            $msg_error = self::_("You can't swap cards");
        }

        if ($msg_error) {
            throw new BgaUserException( $msg_error);
            $this->gamestate->nextState('');
            return;
        }

        //SWAP
        if (self::getGameStateValue(GS::presidentSwapCards) == 0) {
            $beggarId = $players_by_position[$nbPlayers];
            $cardsForPresident = self::getBestCardsByLocation($beggarId, 2);
            $cardsForBeggar = $this->cards->getCards($card_ids);

            $this->cards->moveCards($card_ids, 'hand', $beggarId);
            $this->cards->moveCards(array_keys($cardsForPresident), 'hand', $player_id);

            self::notifyPlayer( $player_id, 'swapCards', '', [
                'cards' => $cardsForPresident,
                'cardsSent' => $cardsForBeggar,
                'destination' => $beggarId
            ]);
            self::notifyPlayer( $beggarId, 'swapCards', '', [
                'destination' => $player_id,
                'cards' => $cardsForBeggar,
                'cardsSent' => $cardsForPresident
            ]);

            self::setGameStateValue(GS::presidentSwapCards, 1);
            $this->gamestate->nextState('endPresidentSwap');
        } else {
            // Next player
            $peasantId = $players_by_position[$nbPlayers-1];
            $cardsForPrimeMinister = self::getBestCardsByLocation($peasantId, 1);
            $cardsForPeasant = $this->cards->getCards($card_ids);

            $this->cards->moveCards($card_ids, 'hand', $peasantId);
            $this->cards->moveCards(array_keys($cardsForPrimeMinister), 'hand', $player_id);

            self::notifyPlayer( $player_id, 'swapCards', '', [
                'cards' => $cardsForPrimeMinister,
                'cardsSent' => $cardsForPeasant,
                'destination' => $peasantId,
            ]);
            self::notifyPlayer( $peasantId, 'swapCards', '', [
                'destination' => $player_id,
                'cards' => $cardsForPeasant,
                'cardsSent' => $cardsForPrimeMinister
            ]);

            self::setGameStateValue(GS::presidentSwapCards, 0);
            $this->gamestate->nextState('endPrimeMinisterSwapTurn');
        }
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state arguments
    ////////////
    /*
 * Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
 * These methods function is to return some additional information that is specific to the current
 * game state.
 */
    function argSwapCards() {
        $cardsToGive = [1 => 2, 2 => 1];
        $players = self::getCollectionFromDb( "SELECT `player_id`, `player_role` from player WHERE `player_role` IN (1 ,2)" );

        foreach ($players as $player_id => $player) {
            $res[$player_id] = ['nbr' => $cardsToGive[$player['player_role']]];
        }

        $sql = "SELECT player_role role, player_id id FROM player ORDER BY role ASC";
        $res['players_by_position'] = self::getCollectionFromDb( $sql , true );
        return $res;
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stNewHand()
    {
        $next_player_id = "";
        $activePlayer = self::getActivePlayerId();
        $nb_payers = self::getGameStateValue(GS::numPlayers);
        $firstRound = self::getGameStateValue(GS::isFirstRound);
        self::setGameStateValue(GS::isRevolutionTrick, 0);

        // add stat new hand
        self::incStat(1, 'Hand_number');

        // Take back all cards (from any location => null) to deck
        $this->cards->moveAllCardsInLocation(null, "deck");
        $this->cards->shuffle('deck');

        // Deal 13 cards to each players
        // Create deck, shuffle it and give 13 initial cards
        $i = 0;
        while ($next_player_id != $activePlayer) {
            $notifData = [];
            $player_id = $next_player_id != '' ? $next_player_id : $activePlayer;

            $notifData['cards'] = $this->cards->pickCards( $this->cards_per_player[$nb_payers][$i], 'deck', $player_id );

            // Notify player about his cards
            self::notifyPlayer( $player_id, 'newHand', '', $notifData);
            $next_player_id = self::getPlayerAfter( $player_id );
            $i++;
        }

        if ($firstRound == 0) {
            $sql = "SELECT player_role role, player_id id FROM player ORDER BY role ASC";
            $players_by_position = self::getCollectionFromDb( $sql , true );

            self::notifyAllPlayers('resetCounters', '', [
                'nb_cards' => $this->cards->countCardsByLocationArgs('hand'),
                'players' => self::getCollectionFromDb( "SELECT player_id id,player_role role FROM player" ),
            ]);

            $this->gamestate->changeActivePlayer( $players_by_position[1] );
            $this->gamestate->nextState("presidentSwapTurn");
        } else {
            $this->gamestate->nextState("playerTurn");
        }
    }

    function stNewRound()
    {
        $this->setUpNewRound();
        $player_id = self::getActivePlayerId();
        if (empty($this->cards->getPlayerHand($player_id))) {
            $next_player_id = $this->getNextActivePlayer();
            $this->gamestate->changeActivePlayer( $next_player_id );
        }
        $this->gamestate->nextState('playerTurn');
    }

    function stNextPlayer()
    {
        $next_player_id = $this->getNextActivePlayer();
        $last_card = self::getGameStateValue(GS::lastCardValue);
        $last_player_played = self::getGameStateValue(GS::lastPlayerPlayedId);
        $best_card_current_hand = self::getGameStateValue(GS::isRevolutionTrick) == 0 ? 15 : 3;

        if ($this->checkEndGame()) {
            // End of the game
            $this->gamestate->nextState("endHand");
        } else {
            if (in_array($last_card, [933, 934]) || ($next_player_id == $last_player_played)) {
                if ($next_player_id == $last_player_played) {
                    $this->gamestate->changeActivePlayer( $next_player_id );
                }
                $this->gamestate->nextState("newRound");
            } else if ($last_card == $best_card_current_hand && empty($this->cards->getCardsOfTypeInLocation(5, null, 'hand'))) {
                $this->gamestate->nextState("newRound");
            } else {
                // Standard case (not the end of the trick)
                // => just active the next player
                self::giveExtraTime(self::getActivePlayerId());
                $this->gamestate->changeActivePlayer( $next_player_id );
                $this->gamestate->nextState('playerTurn');
            }
        }
    }

    function stEndHand() {
        // Count and score points, then end the game or go to the next hand.
        $cards = $this->cards->getCardsInLocation( 'hand');
        $position = self::incGameStateValue(GS::finishOrder, 1);
        $nbPlayers = self::getGameStateValue(GS::numPlayers);
        $players = self::loadPlayersBasicInfos();
        $card = current($cards);
        $lastPlayer = $players[$card['location_arg']];

        // set up beggar
        $players = self::getCollectionFromDb( "SELECT player_id, player_name, player_has_passed, player_role FROM player" );
        $sql = "UPDATE player SET player_role='$position' WHERE player_id='{$lastPlayer['player_id']}'";
        self::DbQuery( $sql );

        // add stat beggar
        self::incStat(1, 'player_beggar_stat', $lastPlayer['player_id']);

        $this->setUpNewRound();
        $role = $this->players_roles[$nbPlayers][$position];
        self::notifyAllPlayers('playerFinished', clienttranslate('${player_name} become\'s ${role}'), [
            'role' => $role,
            'role_position' => $position,
            'player_name' => $lastPlayer['player_name'],
            'i18n' => ['role'],
        ]);

        $this->gamestate->changeActivePlayer( $lastPlayer['player_id'] );
        self::setGameStateValue(GS::finishOrder, 0);
        self::setGameStateValue(GS::isFirstRound, 0);

        // Apply scores to player
        foreach ( $players as $player_id => $player ) {
            $message = self::_('${player_name} loses ${nbr} points');
            if ($lastPlayer['player_id'] == $player_id) {
                $position = $nbPlayers;
            } else {
                $position = $player['player_role'];
            }
            $points = $this->points_per_position[$nbPlayers][$position];
            $sql = "UPDATE player SET player_score=player_score-$points WHERE player_id='$player_id'";
            self::DbQuery($sql);
            if (gmp_sign($points) == '-1') {
                $message = self::_('${player_name} won ${nbr} points');
            }
            self::notifyAllPlayers("points", $message, [
                'player_name' => $player ['player_name'],
                'player_id' => $player_id,
                'nbr' => abs($points) ]
            );
        }
        $newScores = self::getCollectionFromDb("SELECT player_id, player_score FROM player", true );
        self::notifyAllPlayers( "newScores", '', ['newScores' => $newScores] );

        ///// Test if this is the end of the game by score
        foreach ( $newScores as $player_id => $score ) {
            if ($score <= 0) {
                $this->gamestate->nextState("endGame");
                return;
            }
        }

        ///// Test if this is the end of the game by round
        $gameDurationOption = $this->gamestate->table_globals[Opt::gameDuration];
        if ($this->game_duration[$gameDurationOption]['type'] == 'round') {
            if ($this->game_duration[$gameDurationOption]['max_round'] == self::getStat('Hand_number')) {
                $this->gamestate->nextState("endGame");
                return;
            }
        }

        $this->gamestate->nextState("newHand");
    }

    function stSwapCards() {
        $sql = "SELECT player_role role, player_id id FROM player ORDER BY role ASC";
        $players_by_position = self::getCollectionFromDb( $sql , true );

        if (self::getGameStateValue(GS::presidentSwapCards) == 1) {
            $this->gamestate->changeActivePlayer( $players_by_position[2] );
            $this->gamestate->nextState('primeMinisterSwapTurn');
        } else {
            $this->gamestate->changeActivePlayer( $players_by_position[self::getGameStateValue(GS::numPlayers)] );
            $this->gamestate->nextState('playerTurn');
        }
    }

    function stEndGame() {
        $players = self::getCollectionFromDb( "SELECT `player_id`, `player_name` from player WHERE " +
            "`player_score` = (SELECT MAX(`player_score`) from player) ORDER BY `player_role` ASC" );
        $player = current($players);

        self::notifyAllPlayers('playerWon', clienttranslate('${player_name} won'), [
            'player_name' => $player['name'],
        ]);
    }
//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn( $state, $active_player )
    {
        if (!self::isCurrentPlayerZombie()) {
            return;
        }

    	$statename = $state['name'];
        if ($state['type'] === "game") {
            switch ($statename) {
                case 'newHand':
                case 'endHand':
                case 'gameEnd':
                case 'newRound':
                case 'passTurn':
                case 'nextPlayer':
                case 'endPresidentSwap':
                case 'endPrimeMinisterSwapTurn':
                    break;
            }

            return;
        }

        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                case 'playerTurn':
                    $cardsIds = [];
                    $lastCardValue = '';
                    $lastCardPlayed = self::getGameStateValue(GS::lastCardValue);
                    $currentHandType = self::getGameStateValue(GS::currentHandType);
                    $revolutionTrick = self::getGameStateValue(GS::isRevolutionTrick);

                    if ($revolutionTrick == 0) {
                        $cards = $this->getCardsByLocationOrderedByValue( $active_player, 20);
                    } else {
                        $cards = $this->getCardsByLocationOrderedByValue( $active_player, 20, 'DESC');
                    }

                    foreach ($cards as $card) {
                        if ($currentHandType == 0) {
                            if ($lastCardValue) {
                                if ($lastCardValue == $card['type_arg']) {
                                    $cardsIds[] = $card['id'];
                                } else {
                                    break;
                                }
                            } else {
                                $cardsIds[] = $card['id'];
                                $lastCardValue = $card['type_arg'];
                            }
                        } else {
                            if ($lastCardValue) {
                                if (count($cardsIds) == $currentHandType) {
                                    break;
                                } else {
                                    if ($lastCardValue == $card['type_arg']) {
                                        $cardsIds[] = $card['id'];
                                    } else {
                                        $cardsIds = [$card['id']];
                                        $lastCardValue = $card['type_arg'];
                                    }
                                }
                            } else {
                                if ($revolutionTrick == 0) {
                                    if ($card['type_arg'] > $lastCardPlayed) {
                                        $cardsIds[] = $card['id'];
                                        $lastCardValue = $card['type_arg'];
                                    }
                                } else {
                                    if ($card['type_arg'] < $lastCardPlayed) {
                                        $cardsIds[] = $card['id'];
                                        $lastCardValue = $card['type_arg'];
                                    }
                                }
                            }
                        }
                    }

                    if (count($cardsIds) == $currentHandType || $currentHandType == 0) {
                        $this->playCards($cardsIds);
                    } else {
                        $this->passTurn();
                    }
                    break;
                case 'presidentSwapTurn':
                    $cardsIds = [];
                    $cards = $this->getCardsByLocationOrderedByValue( $active_player, 2);
                    foreach ($cards as $card) {
                        $cardsIds[] = $card['id'];
                    }
                    $this->swapCards($cardsIds);
                    break;
                case 'primeMinisterSwapTurn':
                    $cardsIds = [];
                    $cards = $this->getCardsByLocationOrderedByValue( $active_player, 1);
                    foreach ($cards as $card) {
                        $cardsIds[] = $card['id'];
                    }
                    $this->swapCards($cardsIds);
                    break;
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );
            
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }
    
///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */
    
    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }    
}
