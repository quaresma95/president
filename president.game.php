<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Fixes and variants implementation: © ufm <tel2tale@gmail.com>
 * 
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * ----
 */

require_once(APP_GAMEMODULE_PATH.'module/table/table.game.php');

class President extends Table {
    function __construct() {
        // Your global variables labels:
        // Here, you can assign labels to global variables you are using for this game.
        // You can use any number of global variables with IDs between 10 and 99.
        // If your game has options (variants), you also have to associate here a label to
        // the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        self::initGameStateLabels([
            "regular_revolution" => 15,
            "temporary_revolution" => 20,
            "reversed" => 21,
            "skip_turn" => 22,
            "suit_lock_prep" => 23,
            "suit_lock_complete" => 24,
            "target_score" => 100,
            "scoring_rule" => 113,
            "rule_set" => 114,
            "revolution" => 102,
            "joker" => 103,
            "first_player_mode" => 104,
            "same_rank_skip" => 101,
            "sequence" => 106,
            "suit_lock" => 112,
            "ender_8" => 107,
            "reversing_9" => 108,
            "jack_back" => 109,
            "illegal_finish" => 110,
            "downfall" => 111,
            "automatic_skip" => 105,
        ]);
        $this->cards = self::getNew("module.common.deck");
        $this->cards->init("card");
    }

    protected function getGameName() {return "president";}	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */

    protected function setupNewGame($players, $options = []) {    
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];
 
        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = [];
        foreach ($players as $player_id => $player) {
            $color = array_shift($default_colors);
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes($player['player_name'])."','".addslashes($player['player_avatar'])."')";
        }
        $sql .= implode(',', $values);
        self::DbQuery($sql);
        self::reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        // Rule set initialization
        $rule_set = self::getGameStateValue("rule_set");
        switch ($rule_set) {
            default:
                self::setGameStateInitialValue("revolution", 1);
                self::setGameStateInitialValue("joker", 1);
                self::setGameStateInitialValue("first_player_mode", 0);
                self::setGameStateInitialValue("same_rank_skip", 1);
                self::setGameStateInitialValue("sequence", 1);
                self::setGameStateInitialValue("suit_lock", 1);
                self::setGameStateInitialValue("ender_8", 1);
                self::setGameStateInitialValue("reversing_9", 1);
                self::setGameStateInitialValue("jack_back", 1);
                self::setGameStateInitialValue("illegal_finish", 1);
                self::setGameStateInitialValue("downfall", 1);
                break;
            case 1:
                self::setGameStateInitialValue("revolution", 0);
                self::setGameStateInitialValue("joker", 0);
                self::setGameStateInitialValue("first_player_mode", 1);
                self::setGameStateInitialValue("same_rank_skip", 0);
                self::setGameStateInitialValue("sequence", 0);
                self::setGameStateInitialValue("suit_lock", 0);
                self::setGameStateInitialValue("ender_8", 0);
                self::setGameStateInitialValue("reversing_9", 0);
                self::setGameStateInitialValue("jack_back", 0);
                self::setGameStateInitialValue("illegal_finish", 0);
                self::setGameStateInitialValue("downfall", 0);
                break;
        }
        if (count($players) == 2) {
            self::setGameStateValue("reversing_9", 0);
            self::setGameStateValue("downfall", 0);
        }
        if (self::getGameStateValue('scoring_rule')) $this->DbQuery("UPDATE player SET player_score = ".(self::getGameStateValue('target_score') * 5));

        // Init game statistics
        self::initStat('table', 'round_number', 0);
        self::initStat('player', 'first_rank', 0);
        if (count($players) > 3) {
            self::initStat('player', 'second_rank', 0);
            self::initStat('player', 'second_last_rank', 0);
        }
        self::initStat('player', 'last_rank', 0);
        if (self::getGameStateValue('revolution')) self::initStat('player', 'revolution', 0);
        if (self::getGameStateValue('suit_lock')) self::initStat('player', 'suit_lock', 0);
        if (self::getGameStateValue('illegal_finish')) self::initStat('player', 'illegal_finish', 0);
        if (self::getGameStateValue('downfall')) self::initStat('player', 'downfall', 0);

        // Create cards
        $cards = [];
        for ($color = 0; $color < 4; $color++)
            for ($value = 1; $value <= 13; $value++)
                $cards[] = ['type' => $color, 'type_arg' => $value, 'nbr' => 1];
        if (self::getGameStateValue('joker')) $cards[] = ['type' => 4, 'type_arg' => 0, 'nbr' => 1];
        $this->cards->createCards($cards, 'deck');

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all information about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */

    protected function getAllDatas() {
        $result = [];
        $current_player_id = self::getCurrentPlayerId(); // !! We must only return information visible by this player !!
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $result['players'] = self::getCollectionFromDb("SELECT player_id id, player_score score, player_has_passed pass, player_role role FROM player");
  
        // Gather all information about current game situation (visible by player $current_player_id).
        $result['hand_count'] = $this->cards->countCardsByLocationArgs('hand');
        $result['hand'] = $this->cards->getPlayerHand($current_player_id);
        $result['table_combination'] = $this->evaluateCombination($this->cards->getCardsInLocation('cardsontable'));

        $result['regular_revolution'] = self::getGameStateValue("regular_revolution");
        $result['temporary_revolution'] = self::getGameStateValue("temporary_revolution");
        $result['reversed'] = self::getGameStateValue("reversed");
        $result['scoring_rule'] = self::getGameStateValue("scoring_rule");
        $result['target_score'] = self::getGameStateValue("target_score");
        $result['revolution'] = self::getGameStateValue("revolution");
        $result['sequence'] = self::getGameStateValue("sequence");
        $result['suit_lock'] = self::getGameStateValue("suit_lock");
        $result['suit_lock_complete'] = self::getGameStateValue("suit_lock_complete");
        $result['ender_8'] = self::getGameStateValue("ender_8");
        $result['reversing_9'] = self::getGameStateValue("reversing_9");
        $result['jack_back'] = self::getGameStateValue("jack_back");
        $result['illegal_finish'] = self::getGameStateValue("illegal_finish");

        $result['colors'] = $this->colors;
        $result['values_label'] = $this->values_label;
        $result['audio_list'] = $this->audio_list;
  
        return $result;
    }

    /*
        getGameProgression:

        Compute and return the current game progression.
        The number returned must be an integer between 0 (=the game just started) and
        100 (= the game is finished or almost finished).

        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */

    function getGameProgression() {
        $target_score = self::getGameStateValue("target_score");
        if (self::getGameStateValue("scoring_rule")) {
            $target_score *= 5;
            return floor(($target_score - self::getUniqueValueFromDb("SELECT MIN(player_score) FROM player")) / $target_score * 100);
        } else return floor(self::getUniqueValueFromDb("SELECT MAX(player_score) FROM player") / ($target_score * self::getPlayersNumber()) * 100);
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    function dbGetScore ($player_id) {return $this->getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id = '$player_id'");}
    function dbSetScore ($player_id, $count) {$this->DbQuery("UPDATE player SET player_score = '$count' WHERE player_id = '$player_id'");}
    function dbIncScore ($player_id, $inc) {
        $count = $this->dbGetScore($player_id);
        if ($inc != 0) {
            $count += $inc;
            $this->dbSetScore($player_id, $count);
        }
        return $count;
    }

    function sortCards ($a, $b): int {return ($a['type_arg'] ?: 20) * 100 + $a['type'] <=> ($b['type_arg'] ?: 20) * 100 + $b['type'];}
    function sortCardsByRank ($a, $b): int {return ($b['type_arg'] ?: 20) * 100 + $b['type'] <=> ($a['type_arg'] ?: 20) * 100 + $a['type'];}

    function getSurvivingNextPlayer ($player_id) {
        $remaining_players = array_keys($this->cards->countCardsByLocationArgs('hand'));
        $reversed = self::getGameStateValue("reversed");
        $next_player = $reversed ? self::getPlayerBefore($player_id) : self::getPlayerAfter($player_id);
        while (!in_array($next_player, $remaining_players) && $next_player != $player_id)
            $next_player = $reversed ? self::getPlayerBefore($next_player) : self::getPlayerAfter($next_player);
        return $next_player;
    }

    function evaluateCombination ($cards): array {
        if (!$cards) return [];
        else {
            $current_revolution_status = (bool)self::getGameStateValue("regular_revolution") ^ (bool)self::getGameStateValue("temporary_revolution");
            $multiplier = $current_revolution_status ? -1 : 1;
            $jokers = [];
            $suit_list = [];
            $value_list = [];
            $card_list = [];
            foreach ($cards as $key => $card) {
                if ($card['type'] == 4) {
                    $jokers[] = $card;
                    unset($cards[$key]);
                } else {
                    if (isset($value_list[$card['type_arg']])) $value_list[$card['type_arg']]++;
                    else $value_list[$card['type_arg']] = 1;
                    if (isset($suit_list[$card['type']])) $suit_list[$card['type']]++;
                    else $suit_list[$card['type']] = 1;
                    $card_list[$card['type_arg']][] = $card;
                }
            }
            foreach ($card_list as $key => $list) usort($card_list[$key], [$this, "sortCards"]);
            if (!$value_list) return ['type' => 0, 'value' => 14, 'card_list' => $jokers];
            else if (count($value_list) == 1) {
                usort($cards, [$this, "sortCards"]);
                return ['type' => 0, 'value' => array_flip($value_list)[count($cards)] * $multiplier, 'card_list' => array_merge($cards, $jokers)];
            } else if (count($suit_list) == 1 && count($value_list) == count($cards) && (count($cards) + count($jokers)) >= 3 && (count($cards) + count($jokers)) <= 13) {
                if ($current_revolution_status) krsort($value_list);
                else ksort($value_list);
                $remaining_jokers = count($jokers);
                $current_value = null;
                $new_card_list = [];
                foreach ($value_list as $key => $value) {
                    if (!isset($current_value)) {
                        $current_value = $key;
                        $new_card_list = [$card_list[$key][0]];
                    } else if ($key != $current_value + $multiplier) {
                        $gap = ($key - $current_value) * $multiplier - 1;
                        if ($gap <= $remaining_jokers) {
                            $remaining_jokers -= $gap;
                            $current_value = $key;
                            for ($m = 0; $m < $gap; $m++) {
                                $new_card = array_shift($jokers);
                                $new_card_list[] = $new_card;
                            }
                            $new_card_list[] = $card_list[$key][0];
                        } else {
                            $current_value = null;
                            break;
                        }
                    } else {
                        $current_value = $key;
                        $new_card_list[] = $card_list[$key][0];
                    }
                }
                if ($current_value) {
                    if ($remaining_jokers) {
                        if ($current_revolution_status) {
                            while ($remaining_jokers > 0 && $current_value > 1) {
                                $remaining_jokers--;
                                $current_value--;
                                $new_card = array_shift($jokers);
                                $new_card_list[] = $new_card;
                            }
                            if ($remaining_jokers) $new_card_list = array_merge($jokers, $new_card_list);
                        } else {
                            while ($remaining_jokers > 0 && $current_value < 13) {
                                $remaining_jokers--;
                                $current_value++;
                                $new_card = array_shift($jokers);
                                $new_card_list[] = $new_card;
                            }
                            if ($remaining_jokers) $new_card_list = array_merge($jokers, $new_card_list);
                        }
                        return ['type' => 1, 'value' => $current_value * $multiplier, 'card_list' => $new_card_list];
                    } else return ['type' => 1, 'value' => $current_value * $multiplier, 'card_list' => $new_card_list];
                } else return [];
            } else return [];
        }
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in president.action.php)
    */

    function giveCard ($card_ids) {
        self::checkAction('giveCard');
        $cards = $this->cards->getCards($card_ids);
        if (!$cards) throw new BgaVisibleSystemException(self::_("Please select cards"));
        $player_id = self::getActivePlayerId();
        foreach ($cards as $card) if ($card['location'] != 'hand' || $card['location_arg'] != $player_id) throw new BgaVisibleSystemException(self::_("You selected an invalid option"));

        $player_count = self::getPlayersNumber();
        $role = $this->getUniqueValueFromDB("SELECT player_role FROM player WHERE player_id = $player_id");
        $other_role = $player_count - ($role == 1 ? 0 : 1);
        $other_player_id = $this->getUniqueValueFromDB("SELECT player_id FROM player WHERE player_role = $other_role");
        $card_count = $player_count < 4 ? 1 : ($role == 1 ? 2 : 1);
        if (count($cards) != $card_count) throw new BgaVisibleSystemException(self::_("Please select correct number of cards"));

        $other_hand = $this->cards->getPlayerHand($other_player_id);
        usort($other_hand, [$this, "sortCardsByRank"]);
        $other_cards = [];
        $other_keys = [];
        for ($i = 0; $i < $card_count; $i++) {
            $other_card = array_shift($other_hand);
            $other_cards[] = $other_card;
            $other_keys[] = $other_card['id'];
        }
        $this->cards->moveCards($other_keys, 'hand', $player_id);
        $this->cards->moveCards($card_ids, 'hand', $other_player_id);

        usort($cards, [$this, "sortCards"]);
        $logs = [];
        $args = [];
        foreach ($cards as $card) {
            $logs[] = '${color'.$i.'}${value'.$i.'}';
            $args['i18n'][] = 'color'.$i;
            $args['color'.$i] = $this->colors[$card['type']];
            $args['value'.$i] = $this->values_label[$card['type_arg']];
            $i++;
        }
        $full_log_high = ['log' => implode(', ', $logs), 'args' => $args];
        $other_cards = array_reverse($other_cards);
        $logs = [];
        $args = [];
        $i = 0;
        foreach ($other_cards as $card) {
            $logs[] = '${color'.$i.'}${value'.$i.'}';
            $args['i18n'][] = 'color'.$i;
            $args['color'.$i] = $this->colors[$card['type']];
            $args['value'.$i] = $this->values_label[$card['type_arg']];
            $i++;
        }
        $full_log_low = ['log' => implode(', ', $logs), 'args' => $args];

        $player_name = self::getActivePlayerName();
        $other_player_name = self::getPlayerNameById($other_player_id);
        self::notifyPlayer($player_id, 'privateExchange', clienttranslate('You passed ${card_list_given} to ${player_name} and received ${card_list_received}'), [
            'player_id' => $other_player_id,
            'player_name' => $other_player_name,
            'card_list_given' => $full_log_high,
            'cards_given' => $cards,
            'card_list_received' => $full_log_low,
            'cards_received' => $other_cards,
        ]);
        self::notifyPlayer($other_player_id, 'privateExchange', clienttranslate('You passed ${card_list_given} to ${player_name} and received ${card_list_received}'), [
            'player_id' => $player_id,
            'player_name' => $player_name,
            'card_list_given' => $full_log_low,
            'cards_given' => $other_cards,
            'card_list_received' => $full_log_high,
            'cards_received' => $cards,
        ]);
        self::notifyAllPlayers('giveCard', $card_count == 2 ? clienttranslate('${player_name} and ${player_name2} exchanges two cards') : clienttranslate('${player_name} and ${player_name2} exchanges a card'), [
            'player_id' => $player_id,
            'other_player_id' => $other_player_id,
            'player_name' => $player_name,
            'player_name2' => $other_player_name,
            'card_count' => $card_count,
        ]);

        self::giveExtraTime($player_id);
        $this->gamestate->nextState('');
    }

    function playCard ($card_ids) {
        self::checkAction('playCard');
        $cards = $this->cards->getCards($card_ids);
        if (!$cards) throw new BgaVisibleSystemException(self::_("Please select cards"));
        $player_id = self::getActivePlayerId();
        $suit_count = [0, 0, 0, 0];
        $count_joker = 0;
        foreach ($cards as $card) {
            if ($card['type'] == 4) $count_joker++;
            else $suit_count[$card['type']]++;
            if ($card['location'] != 'hand' || $card['location_arg'] != $player_id) throw new BgaVisibleSystemException(self::_("You selected an invalid option"));
        }

        $table_cards = $this->cards->getCardsInLocation('cardsontable');
        $table_combination = $this->evaluateCombination($table_cards);
        $hand_combination = $this->evaluateCombination($cards);
        $suit_lock_complete = self::getGameStateValue("suit_lock_complete");
        if (!$hand_combination) throw new BgaUserException(self::_("Please play a valid combination"));
        else if ($hand_combination['type'] == 1 && !self::getGameStateValue("sequence")) throw new BgaUserException(self::_("Same suit sequences are not allowed in this table"));
        if ($table_combination) {
            if ($hand_combination['type'] != $table_combination['type'] || count($cards) != count($table_cards)) throw new BgaUserException(self::_("You cannot play this combination now"));
            else if ($hand_combination['value'] < $table_combination['value']) throw new BgaUserException(self::_("This combination cannot beat the previous combination"));
            else if ($hand_combination['value'] == $table_combination['value'] && !self::getGameStateValue("same_rank_skip")) throw new BgaUserException(self::_("Same rank skip is not allowed in this table"));
            if ($suit_lock_complete) {
                $lacking_count = 0;
                $locked_count = [];
                $locked_count[0] = $suit_lock_complete % 100;
                $locked_count[1] = floor(($suit_lock_complete % 10000) / 100);
                $locked_count[2] = floor(($suit_lock_complete % 1000000) / 10000);
                $locked_count[3] = floor($suit_lock_complete / 1000000);
                for ($i = 0; $i <= 3; $i++) {
                    if ($suit_count[$i] > $locked_count[$i]) throw new BgaUserException(self::_("This combination does not follow the previous suit combination"));
                    else if ($suit_count[$i] < $locked_count[$i]) $lacking_count += $locked_count[$i] - $suit_count[$i];
                }
                if ($lacking_count != $count_joker) throw new BgaUserException(self::_("This combination does not follow the previous suit combination"));
            }
        }
        
        $this->cards->moveAllCardsInLocationKeepOrder('cardsontable', 'discard');
        $this->cards->moveCards($card_ids, 'cardsontable', $player_id);
        $this->DbQuery("UPDATE player SET player_has_passed = 0");

        $revolution = self::getGameStateValue("revolution");
        $ender_8 = self::getGameStateValue("ender_8");
        $reversing_9 = self::getGameStateValue("reversing_9");
        $jack_back = self::getGameStateValue("jack_back");
        $regular_revolution = self::getGameStateValue("regular_revolution");
        $temporary_revolution = self::getGameStateValue("temporary_revolution");
        $current_revolution_status = (bool)$regular_revolution ^ (bool)$temporary_revolution;
        $count_8 = 0;
        $count_9 = 0;
        $count_jack = 0;
        $count_2 = 0;
        $count_3 = 0;

        $logs = [];
        $args = [];
        $i = 0;
        foreach ($hand_combination['card_list'] as $card) {
            switch ($card['type_arg']) {
                case 1:
                    $count_3++;
                    break;
                case 6:
                    $count_8++;
                    break;
                case 7:
                    $count_9++;
                    break;
                case 9:
                    $count_jack++;
                    break;
                case 13:
                    $count_2++;
                    break;
            }

            $logs[] = '${color'.$i.'}${value'.$i.'}';
            $args['i18n'][] = 'color'.$i;
            $args['color'.$i] = $this->colors[$card['type']];
            $args['value'.$i] = $this->values_label[$card['type_arg']];
            $i++;
        }
        $full_log = ['log' => implode('-', $logs), 'args' => $args];

        $lock = false;
        if (!$suit_lock_complete && self::getGameStateValue("suit_lock") && !($ender_8 && $count_8)) {
            $suit_lock_prep = self::getGameStateValue("suit_lock_prep");
            if ($suit_lock_prep && !$count_joker) {
                $prep_count = [];
                $prep_count[0] = $suit_lock_prep % 100;
                $prep_count[1] = floor(($suit_lock_prep % 10000) / 100);
                $prep_count[2] = floor(($suit_lock_prep % 1000000) / 10000);
                $prep_count[3] = floor($suit_lock_prep / 1000000);
                $lock = true;
                for ($i = 0; $i <= 3; $i++) if ($suit_count[$i] != $prep_count[$i]) {
                    $lock = false;
                    break;
                }
                if ($lock) self::setGameStateValue("suit_lock_complete", $suit_count[0] + $suit_count[1] * 100 + $suit_count[2] * 10000 + $suit_count[3] * 1000000);
                else self::setGameStateValue("suit_lock_prep", $suit_count[0] + $suit_count[1] * 100 + $suit_count[2] * 10000 + $suit_count[3] * 1000000);
            } else self::setGameStateValue("suit_lock_prep", $count_joker ? 0 : ($suit_count[0] + $suit_count[1] * 100 + $suit_count[2] * 10000 + $suit_count[3] * 1000000));
        }

        $player_name = self::getActivePlayerName();
        self::notifyAllPlayers('playCard', clienttranslate('${player_name} plays ${card_list}'), [
            'player_id' => $player_id,
            'player_name' => $player_name,
            'card_list' => $full_log,
            'combination' => $hand_combination,
            'suit_lock' => $lock,
        ]);

        if (!$this->cards->countCardInLocation('hand', $player_id)) {
            $remaining_players = array_keys($this->cards->countCardsByLocationArgs('hand'));
            if (self::getGameStateValue("illegal_finish") && ($count_joker || (!$current_revolution_status && $count_2) || ($current_revolution_status && $count_3) || ($ender_8 && $count_8)))
                $rank = 100 - self::getUniqueValueFromDB("SELECT count(*) FROM player WHERE player_role >= 71 AND player_id != $player_id AND player_id NOT IN (".implode(',', $remaining_players).")");
            else $rank = 1 + self::getUniqueValueFromDB("SELECT count(*) FROM player WHERE player_role < 50 AND player_id != $player_id AND player_id NOT IN (".implode(',', $remaining_players).")");
            $this->DbQuery("UPDATE player SET player_role = $rank WHERE player_id = $player_id");
            if ($rank >= 71) self::incStat(1, 'illegal_finish', $player_id);
            self::notifyAllPlayers("goOut", $rank >= 71 ? clienttranslate('${player_name} goes out by illegal finish and is disqualified!') : clienttranslate('${player_name} goes out!'), [
                'player_id' => $player_id,
                'player_name' => $player_name,
                'rank' => $rank,
            ]);
            $player_count = self::getPlayersNumber();
            if ($rank == 1 && self::getGameStateValue("downfall") && $player_count > 2) {
                $downfall_player = self::getUniqueValueFromDB("SELECT player_id FROM player WHERE player_role = 1 AND player_id IN (".implode(',', $remaining_players).")");
                if ($downfall_player) {
                    self::incStat(1, 'downfall', $downfall_player);
                    $this->DbQuery("UPDATE player SET player_role = 70 WHERE player_id = $downfall_player");
                    $this->cards->moveAllCardsInLocation('hand', 'removed', $downfall_player, $downfall_player);
                    self::notifyAllPlayers("goOut", clienttranslate('The former ${role_name} ${player_name} fails to get the first place and is disqualified!'), [
                        'i18n' => ['role_name'],
                        'player_id' => $downfall_player,
                        'player_name' => self::getPlayerNameById($downfall_player),
                        'role_name' => $player_count > 3 ? clienttranslate('President') : clienttranslate('Minister'),
                        'rank' => 70,
                    ]);
                }
            }
        }

        $hand_player_count = count($this->cards->countCardsByLocationArgs('hand'));
        if ($revolution && count($cards) >= 4 && $hand_player_count > 1) {
            self::setGameStateValue("regular_revolution", 1 - $regular_revolution);
            self::incStat(1, 'revolution', $player_id);
            self::notifyAllPlayers("noSound", clienttranslate('Playing 4 or more cards reverses the card rank during this round!'), []);
        }
        if ($jack_back && $count_jack && !($ender_8 && $count_8) && $hand_player_count > 1) {
            self::setGameStateValue("temporary_revolution", 1 - $temporary_revolution);
            self::notifyAllPlayers("noSound", clienttranslate('J effect reverses the card rank during this trick!'), []);
        }
        if ($reversing_9 && $count_9) {
            self::setGameStateValue("reversed", 1 - self::getGameStateValue("reversed"));
            self::notifyAllPlayers("noSound", clienttranslate('9 effect reverses the turn order permanently!'), []);
        }
        if ($ender_8 && $count_8 && $hand_player_count > 1) self::notifyAllPlayers("noSound", clienttranslate('8 effect ends this trick immediately!'), []);
        if ($table_combination) {
            if ($hand_combination['value'] == $table_combination['value'] && !($ender_8 && $count_8) && $hand_player_count > 1) {
                self::setGameStateValue("skip_turn", 1);
                $next_player = $this->getSurvivingNextPlayer($player_id);
                $this->DbQuery("UPDATE player SET player_has_passed = 1 WHERE player_id = $next_player");
                self::notifyAllPlayers("passTurn", clienttranslate('Playing the same rank skips ${player_name}\'s turn!'), [
                    'player_id' => $next_player,
                    'player_name' => self::getPlayerNameById($next_player),
                ]);
            }
        }
        if ($lock && $hand_player_count > 1) {
            self::incStat(1, 'suit_lock', $player_id);
            self::notifyAllPlayers("noSound", clienttranslate('Playing the same suit combination locks the suit during this trick!'), []);
        }

        self::giveExtraTime($player_id);
        $this->gamestate->nextState('');
    }

    function passTurn() {
        self::checkAction('passTurn');
        if (!$this->cards->countCardInLocation('cardsontable')) throw new BgaVisibleSystemException(self::_("You selected an invalid option"));

        $player_id = self::getActivePlayerId();
        $this->DbQuery("UPDATE player SET player_has_passed = 1 WHERE player_id = '$player_id'");
        self::notifyAllPlayers("passTurn", '', ['player_id' => $player_id]);

        self::giveExtraTime($player_id);
        $this->gamestate->nextState('');
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    function argGiveCard() {
        $player_id = self::getActivePlayerId();
        $role = $this->getUniqueValueFromDB("SELECT player_role FROM player WHERE player_id = $player_id");
        $other_role = self::getPlayersNumber() - ($role == 1 ? 0 : 1);
        $other_player_id = $this->getUniqueValueFromDB("SELECT player_id FROM player WHERE player_role = $other_role");
        return [
            'otherplayer' => self::getPlayerNameById($other_player_id),
            'otherplayer_id' => $other_player_id,
        ];
    }

    function argPlayerTurn() {return ['passable' => $this->cards->countCardInLocation('cardsontable') > 0];}

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */

    function stStartRound() {
        self::incStat(1, 'round_number');
        $this->DbQuery("UPDATE player SET player_has_passed = 0");
        self::setGameStateValue("regular_revolution", 0);
        self::setGameStateValue("temporary_revolution", 0);
        self::setGameStateValue("skip_turn", 0);
        self::setGameStateValue("suit_lock_prep", 0);
        self::setGameStateValue("suit_lock_complete", 0);
        $this->cards->moveAllCardsInLocation(null, 'deck');
        $this->cards->shuffle('deck');
        $deck_count = $this->cards->countCardInLocation('deck');
        $hand_count_list = [];
        $rank_list = [];

        if (self::getStat("round_number") > 1) {
            $player_list = self::getCollectionFromDb("SELECT player_id id, player_role role FROM player ORDER BY player_role DESC", true);
            $player_count = count($player_list);
            $deal_count = $player_count == 2 ? 14 : floor($deck_count / $player_count);
            $deck_count -= $deal_count * $player_count;
            $current_rank = $player_count;
            $hand_card_list = [];
            $first_rank = null;
            foreach ($player_list as $player_id => $rank) {
                if ($rank != $current_rank) $this->DbQuery("UPDATE player SET player_role = $current_rank WHERE player_id = $player_id");
                $rank_list[$player_id] = $current_rank;
                if ($current_rank == 1) $first_rank = $player_id;
                $current_rank--;

                if ($deck_count > 0) {
                    $deck_count--;
                    $hand_count_list[$player_id] = $deal_count + 1;
                    $hand_card_list[$player_id] = $this->cards->pickCards($deal_count + 1, 'deck', $player_id);
                } else {
                    $hand_count_list[$player_id] = $deal_count;
                    $hand_card_list[$player_id] = $this->cards->pickCards($deal_count, 'deck', $player_id);
                }
            }

            foreach ($player_list as $player_id => $player) self::notifyPlayer($player_id, 'newHand', '', ['cards' => $hand_card_list[$player_id]]);
            self::notifyAllPlayers('newRound', clienttranslate('A new round starts'), [
                'hand_count' => $hand_count_list,
                'rank_list' => $rank_list,
            ]);
            $this->gamestate->changeActivePlayer($first_rank);
            $transition = $player_count > 3 ? 'presidentGive' : 'ministerGive';
        } else {
            $player_list = self::getCollectionFromDb("SELECT player_id id, player_role role FROM player ORDER BY player_no", true);
            $player_count = count($player_list);
            $deal_count = $player_count == 2 ? 14 : floor($deck_count / $player_count);
            $deck_count -= $deal_count * $player_count;
            foreach ($player_list as $player_id => $player) {
                $rank_list[$player_id] = 0;
                if ($deck_count > 0) {
                    $deck_count--;
                    $hand_count_list[$player_id] = $deal_count + 1;
                    self::notifyPlayer($player_id, 'newHand', '', ['cards' => $this->cards->pickCards($deal_count + 1, 'deck', $player_id)]);
                } else {
                    $hand_count_list[$player_id] = $deal_count;
                    self::notifyPlayer($player_id, 'newHand', '', ['cards' => $this->cards->pickCards($deal_count, 'deck', $player_id)]);
                }
            }
            self::notifyAllPlayers('newRound', clienttranslate('${player_name} starts the first round'), [
                'player_name' => self::getActivePlayerName(),
                'hand_count' => $hand_count_list,
                'rank_list' => $rank_list,
            ]);
            $transition = 'playerTurn';
        }
        $this->gamestate->nextState($transition);
    }

    function stGiveEnd() {
        $player_id = self::getActivePlayerId();
        $player_count = self::getPlayersNumber();
        $role = $this->getUniqueValueFromDB("SELECT player_role FROM player WHERE player_id = $player_id");
        if ($role == 2 || $player_count < 4) {
            $first_player_mode = self::getGameStateValue("first_player_mode");
            $first_player = $this->getUniqueValueFromDB("SELECT player_id FROM player WHERE player_role = ".(self::getGameStateValue("first_player_mode") ? 1 : $player_count));
            $this->gamestate->changeActivePlayer($first_player);
            self::notifyAllPlayers('noSound', clienttranslate('${player_name} starts the first round as ${role_name}'), [
                'i18n' => ['role_name'],
                'player_name' => self::getPlayerNameById($first_player),
                'role_name' => $first_player_mode ? ($player_count < 4 ? clienttranslate('Minister') : clienttranslate('President')) : ($player_count < 4 ? clienttranslate('Peasant') : clienttranslate('Beggar')),
            ]);
            $transition = 'playerTurn';
        } else {
            $this->gamestate->changeActivePlayer($this->getUniqueValueFromDB("SELECT player_id FROM player WHERE player_role = 2"));
            $transition = 'ministerGive';
        }
        $this->gamestate->nextState($transition);
    }

    function stNextPlayer() {
        $hand_player_count = count($this->cards->countCardsByLocationArgs('hand'));
        $transition = 'nextPlayer';

        if ($hand_player_count <= 1) $transition = 'endRound';
        else {
            $player_id = self::getActivePlayerId();
            $table_cards = $this->cards->getCardsInLocation('cardsontable');
            $table_card_list = $table_cards;
            $table_card_player = array_shift($table_card_list)['location_arg'] ?? 0;
            $next_player = self::getSurvivingNextPlayer($player_id);
            if (self::getGameStateValue("skip_turn")) {
                self::setGameStateValue("skip_turn", 0);
                $next_player = self::getSurvivingNextPlayer($next_player);
            }
            $ender_8 = self::getGameStateValue("ender_8");
            $found_8 = false;
            foreach ($table_cards as $card) if ($card['type_arg'] == 6) {
                $found_8 = true;
                break;
            }
            if ($ender_8 && $found_8) $transition = 'endTrick';
            else if ($next_player == $table_card_player || $hand_player_count <= self::getUniqueValueFromDB("SELECT count(*) FROM player WHERE player_has_passed > 0")) $transition = 'endTrick';
            else {
                $table_combination = $this->evaluateCombination($table_cards);
                $this->gamestate->changeActivePlayer($next_player);
                $autoplay = false;
                $autopass = false;
                if ($table_combination) {
                    $hand = $this->cards->getCardsInLocation('hand', $next_player);
                    $hand_combination = $this->evaluateCombination($hand);
                    $playable = (bool)$hand_combination;
                    
                    $suit_count = [0, 0, 0, 0];
                    $suit_lock_complete = self::getGameStateValue("suit_lock_complete");
                    $sequence = self::getGameStateValue("sequence");
                    $same_rank_skip = self::getGameStateValue("same_rank_skip");
                    $locked_count = [];
                    $count_joker = 0;
                    $count_8 = 0;
                    $count_9 = 0;
                    $count_jack = 0;
                    $count_2 = 0;
                    $count_3 = 0;
                    if ($playable) foreach ($hand as $card) {
                        if ($card['type'] == 4) $count_joker++;
                        else $suit_count[$card['type']]++;
                        switch ($card['type_arg']) {
                            case 1:
                                $count_3++;
                                break;
                            case 6:
                                $count_8++;
                                break;
                            case 7:
                                $count_9++;
                                break;
                            case 9:
                                $count_jack++;
                                break;
                            case 13:
                                $count_2++;
                                break;
                        }
                    }

                    if ($suit_lock_complete) {
                        $lacking_count = 0;
                        $locked_count[0] = $suit_lock_complete % 100;
                        $locked_count[1] = floor(($suit_lock_complete % 10000) / 100);
                        $locked_count[2] = floor(($suit_lock_complete % 1000000) / 10000);
                        $locked_count[3] = floor($suit_lock_complete / 1000000);
                        for ($i = 0; $i <= 3; $i++) {
                            if ($suit_count[$i] > $locked_count[$i]) $playable = false;
                            else if ($suit_count[$i] < $locked_count[$i]) $lacking_count += $locked_count[$i] - $suit_count[$i];
                        }
                        if ($lacking_count != $count_joker) $playable = false;
                    }

                    if ($playable && $hand_combination['type'] == 1 && !$sequence) $playable = !$playable;
                    if ($playable) {
                        if ($hand_combination['type'] != $table_combination['type'] || count($hand) != count($table_cards)) $playable = false;
                        if ($hand_combination['value'] < $table_combination['value'] || ($hand_combination['value'] == $table_combination['value'] && !$same_rank_skip)) $playable = false;
    
                        $regular_revolution = self::getGameStateValue("regular_revolution");
                        $temporary_revolution = self::getGameStateValue("temporary_revolution");
                        if ($playable) {
                            $current_revolution_status = (bool)$regular_revolution ^ (bool)$temporary_revolution;
                            if (self::getGameStateValue("illegal_finish") && ($count_joker || (!$current_revolution_status && $count_2) || ($current_revolution_status && $count_3) || ($ender_8 && $count_8))) $playable = false;
                        }
    
                        if ($playable) {
                            $revolution = self::getGameStateValue("revolution");
                            $reversing_9 = self::getGameStateValue("reversing_9");
                            $jack_back = self::getGameStateValue("jack_back");
                            $this->cards->moveAllCardsInLocationKeepOrder('cardsontable', 'discard');
                            $this->cards->moveAllCardsInLocation('hand', 'cardsontable', $next_player, $next_player);
                            $this->DbQuery("UPDATE player SET player_has_passed = 0");
    
                            $logs = [];
                            $args = [];
                            $i = 0;
                            foreach ($hand_combination['card_list'] as $card) {
                                $logs[] = '${color'.$i.'}${value'.$i.'}';
                                $args['i18n'][] = 'color'.$i;
                                $args['color'.$i] = $this->colors[$card['type']];
                                $args['value'.$i] = $this->values_label[$card['type_arg']];
                                $i++;
                            }
                            $full_log = ['log' => implode('-', $logs), 'args' => $args];
    
                            $lock = false;
                            if (!$suit_lock_complete && self::getGameStateValue("suit_lock") && !($ender_8 && $count_8)) {
                                $suit_lock_prep = self::getGameStateValue("suit_lock_prep");
                                if ($suit_lock_prep && !$count_joker) {
                                    $prep_count = [];
                                    $prep_count[0] = $suit_lock_prep % 100;
                                    $prep_count[1] = floor(($suit_lock_prep % 10000) / 100);
                                    $prep_count[2] = floor(($suit_lock_prep % 1000000) / 10000);
                                    $prep_count[3] = floor($suit_lock_prep / 1000000);
                                    $lock = true;
                                    for ($i = 0; $i <= 3; $i++) if ($suit_count[$i] != $prep_count[$i]) {
                                        $lock = false;
                                        break;
                                    }
                                    if ($lock) self::setGameStateValue("suit_lock_complete", $suit_count[0] + $suit_count[1] * 100 + $suit_count[2] * 10000 + $suit_count[3] * 1000000);
                                    else self::setGameStateValue("suit_lock_prep", $suit_count[0] + $suit_count[1] * 100 + $suit_count[2] * 10000 + $suit_count[3] * 1000000);
                                } else self::setGameStateValue("suit_lock_prep", $count_joker ? 0 : ($suit_count[0] + $suit_count[1] * 100 + $suit_count[2] * 10000 + $suit_count[3] * 1000000));
                            }

                            $next_player_name = self::getActivePlayerName();
                            self::notifyAllPlayers('playCard', clienttranslate('${player_name} plays ${card_list}'), [
                                'player_id' => $next_player,
                                'player_name' => $next_player_name,
                                'card_list' => $full_log,
                                'combination' => $hand_combination,
                                'suit_lock' => $lock,
                            ]);

                            $remaining_players = array_keys($this->cards->countCardsByLocationArgs('hand'));
                            $rank = 1 + self::getUniqueValueFromDB("SELECT count(*) FROM player WHERE player_role < 50 AND player_id != $next_player AND player_id NOT IN (".implode(',', $remaining_players).")");
                            $this->DbQuery("UPDATE player SET player_role = $rank WHERE player_id = $next_player");
                            self::notifyAllPlayers("goOut", clienttranslate('${player_name} goes out!'), [
                                'player_id' => $next_player,
                                'player_name' => $next_player_name,
                                'rank' => $rank,
                            ]);
                            $player_count = self::getPlayersNumber();
                            if ($rank == 1 && self::getGameStateValue("downfall") && $player_count > 2) {
                                $downfall_player = self::getUniqueValueFromDB("SELECT player_id FROM player WHERE player_role = 1 AND player_id IN (".implode(',', $remaining_players).")");
                                if ($downfall_player) {
                                    self::incStat(1, 'downfall', $downfall_player);
                                    $this->DbQuery("UPDATE player SET player_role = 70 WHERE player_id = $downfall_player");
                                    $this->cards->moveAllCardsInLocation('hand', 'removed', $downfall_player, $downfall_player);
                                    self::notifyAllPlayers("goOut", clienttranslate('The former ${role_name} ${player_name} fails to get the first place and is disqualified!'), [
                                        'i18n' => ['role_name'],
                                        'player_id' => $downfall_player,
                                        'player_name' => self::getPlayerNameById($downfall_player),
                                        'role_name' => $player_count > 3 ? clienttranslate('President') : clienttranslate('Minister'),
                                        'rank' => 70,
                                    ]);
                                }
                            }
    
                            $hand_player_count = count($this->cards->countCardsByLocationArgs('hand'));
                            if ($revolution && count($hand) >= 4 && $hand_player_count > 1) {
                                self::setGameStateValue("regular_revolution", 1 - $regular_revolution);
                                self::incStat(1, 'revolution', $player_id);
                                self::notifyAllPlayers("noSound", clienttranslate('Playing 4 or more cards reverses the card rank during this round!'), []);
                            }
                            if ($jack_back && $count_jack && !($ender_8 && $count_8) && $hand_player_count > 1) {
                                self::setGameStateValue("temporary_revolution", 1 - $temporary_revolution);
                                self::notifyAllPlayers("noSound", clienttranslate('J effect reverses the card rank during this trick!'), []);
                            }
                            if ($reversing_9 && $count_9) {
                                self::setGameStateValue("reversed", 1 - self::getGameStateValue("reversed"));
                                self::notifyAllPlayers("noSound", clienttranslate('9 effect reverses the turn order permanently!'), []);
                            }
                            if ($ender_8 && $count_8 && $hand_player_count > 1) self::notifyAllPlayers("noSound", clienttranslate('8 effect ends this trick immediately!'), []);
                            if ($table_combination) {
                                if ($hand_combination['value'] == $table_combination['value'] && !($ender_8 && $count_8) && $hand_player_count > 1) {
                                    self::setGameStateValue("skip_turn", 1);
                                    $next_next_player = $this->getSurvivingNextPlayer($next_player);
                                    $this->DbQuery("UPDATE player SET player_has_passed = 1 WHERE player_id = $next_next_player");
                                    self::notifyAllPlayers("passTurn", clienttranslate('Playing the same rank skips ${player_name}\'s turn!'), [
                                        'player_id' => $next_next_player,
                                        'player_name' => self::getPlayerNameById($next_next_player),
                                    ]);
                                }
                            }
                            if ($lock && $hand_player_count > 1) {
                                self::incStat(1, 'suit_lock', $player_id);
                                self::notifyAllPlayers("noSound", clienttranslate('Playing the same suit combination locks the suit during this trick!'), []);
                            }
    
                            $autoplay = true;
                            $transition = $hand_player_count <= 1 ? 'endRound' : 'autoPlay';
                        }
                    }

                    if (!$autoplay) {
                        if (count($hand) < count($table_cards) || $table_combination['value'] == 14 || (count($table_cards) == 1 && count($hand) == 1)) $autopass = true;
                        else if (self::getGameStateValue("automatic_skip")) {
                            $current_revolution_status = (bool)self::getGameStateValue("regular_revolution") ^ (bool)self::getGameStateValue("temporary_revolution");
                            $multiplier = $current_revolution_status ? -1 : 1;
                            $hand_cards = array_merge($this->cards->getCardsInLocation('hand'), $this->cards->getCardsInLocation('deck'), $this->cards->getCardsInLocation('removed'));
                            $remaining_joker_count = 0;
                            if ($suit_lock_complete) {
                                $locked_suit_types = [];
                                for ($i = 0; $i <= 3; $i++) if ($locked_count[$i] > 0) $locked_suit_types[] = $i;
                                foreach ($hand_cards as $key => $card)
                                    if (!in_array($card['type'], $locked_suit_types)) {
                                        unset($hand_cards[$key]);
                                        if ($card['type'] == 4) $remaining_joker_count++;
                                    }
                            } else {
                                foreach ($hand_cards as $key => $card)
                                    if ($card['type'] == 4) {
                                        unset($hand_cards[$key]);
                                        $remaining_joker_count++;
                                    }
                            }

                            switch ($table_combination['type']) {
                                default:
                                    $value_color_array = [];
                                    foreach ($hand_cards as $card) {
                                        if (!isset($value_color_array[$card['type_arg']])) $value_color_array[$card['type_arg']] = [$card['type']];
                                        else $value_color_array[$card['type_arg']][] = $card['type'];
                                    }
                                    $autopass = count($table_cards) > $remaining_joker_count;
                                    if ($autopass) foreach ($value_color_array as $value => $colors) {
                                        $true_value = $value * $multiplier;
                                        if (count($colors) + $remaining_joker_count >= count($table_cards) && ($true_value > $table_combination['value'] || ($same_rank_skip && $true_value == $table_combination['value']))) {
                                            $autopass = false;
                                            break;
                                        }
                                    }
                                    break;
                                case 1:
                                    $color_value_array = [];
                                    foreach ($hand_cards as $card) {
                                        if (!isset($color_value_array[$card['type']])) $color_value_array[$card['type']] = [$card['type_arg']];
                                        else $color_value_array[$card['type']][] = $card['type_arg'];
                                    }
                                    $autopass = true;
                                    $current_check_value = $table_combination['value'] * $multiplier + ($same_rank_skip ? 0 : $multiplier);
                                    foreach ($color_value_array as $values) {
                                        if ($current_revolution_status) {
                                            while ($current_check_value > 0) {
                                                $gap = 0;
                                                for ($i = $current_check_value; $i < $current_check_value + count($table_cards); $i++)
                                                    if (!in_array($i, $values)) $gap++;
                                                if ($gap <= $remaining_joker_count) {
                                                    $autopass = false;
                                                    break 2;
                                                }
                                                $current_check_value--;
                                            }
                                        } else {
                                            while ($current_check_value < 14) {
                                                $gap = 0;
                                                for ($i = $current_check_value; $i > $current_check_value - count($table_cards); $i--)
                                                    if (!in_array($i, $values)) $gap++;
                                                if ($gap <= $remaining_joker_count) {
                                                    $autopass = false;
                                                    break 2;
                                                }
                                                $current_check_value++;
                                            }
                                        }
                                    }
                                    break;
                            }
                        }

                        if ($autopass) {
                            $transition = 'autoPass';
                            $this->DbQuery("UPDATE player SET player_has_passed = 1 WHERE player_id = '$next_player'");
                            self::notifyAllPlayers("passTurn", '', ['player_id' => $next_player]);
                        }
                    }
                }
            }
        }
        $this->gamestate->nextState($transition);
    }

    function stEndTrick() {
        $transition = 'nextTrick';
        $table_cards = $this->cards->getCardsInLocation('cardsontable');
        $table_card_list = $table_cards;
        $player_id = array_shift($table_card_list)['location_arg'];
        $player_name = self::getPlayerNameById($player_id);
        
        $this->cards->moveAllCardsInLocationKeepOrder('cardsontable', 'discard');
        $this->DbQuery("UPDATE player SET player_has_passed = 0");
        self::setGameStateValue("temporary_revolution", 0);
        self::setGameStateValue("skip_turn", 0);
        self::setGameStateValue("suit_lock_prep", 0);
        self::setGameStateValue("suit_lock_complete", 0);
        self::notifyAllPlayers("endTrick", clienttranslate('${player_name} wins this trick'), [
            'player_id' => $player_id,
            'player_name' => $player_name,
        ]);

        $hand_player_count = $this->cards->countCardsByLocationArgs('hand');
        if (count($hand_player_count) <= 1) $transition = 'endRound';
        else {
            if (!$this->cards->countCardInLocation('hand', $player_id)) $player_id = self::getSurvivingNextPlayer($player_id);
            $this->gamestate->changeActivePlayer($player_id);
            $hand = $this->cards->getCardsInLocation('hand', $player_id);
            $hand_combination = $this->evaluateCombination($hand);
            $playable = (bool)$hand_combination;
            if ($playable && $hand_combination['type'] == 1 && !self::getGameStateValue("sequence")) $playable = !$playable;
            if ($playable) {
                $ender_8 = self::getGameStateValue("ender_8");
                $suit_count = [0, 0, 0, 0];
                $count_joker = 0;
                $count_8 = 0;
                $count_9 = 0;
                $count_jack = 0;
                $count_2 = 0;
                $count_3 = 0;
                foreach ($hand as $card) {
                    if ($card['type'] == 4) $count_joker++;
                    else $suit_count[$card['type']]++;
                    switch ($card['type_arg']) {
                        case 1:
                            $count_3++;
                            break;
                        case 6:
                            $count_8++;
                            break;
                        case 7:
                            $count_9++;
                            break;
                        case 9:
                            $count_jack++;
                            break;
                        case 13:
                            $count_2++;
                            break;
                    }
                }
                $regular_revolution = self::getGameStateValue("regular_revolution");
                $temporary_revolution = self::getGameStateValue("temporary_revolution");
                $illegal_finish = self::getGameStateValue("illegal_finish");
                $current_revolution_status = (bool)$regular_revolution ^ (bool)$temporary_revolution;
                if ($illegal_finish && count($hand) > 1) {
                    if ($count_joker || (!$current_revolution_status && $count_2) || ($current_revolution_status && $count_3) || ($ender_8 && $count_8)) $playable = false;
                }
                if ($playable) {
                    $transition = 'autoPlay';
                    $revolution = self::getGameStateValue("revolution");
                    $reversing_9 = self::getGameStateValue("reversing_9");
                    $jack_back = self::getGameStateValue("jack_back");
                    $this->cards->moveAllCardsInLocation('hand', 'cardsontable', $player_id, $player_id);

                    $logs = [];
                    $args = [];
                    $i = 0;
                    foreach ($hand_combination['card_list'] as $card) {
                        $logs[] = '${color'.$i.'}${value'.$i.'}';
                        $args['i18n'][] = 'color'.$i;
                        $args['color'.$i] = $this->colors[$card['type']];
                        $args['value'.$i] = $this->values_label[$card['type_arg']];
                        $i++;
                    }
                    $full_log = ['log' => implode('-', $logs), 'args' => $args];

                    if (self::getGameStateValue("suit_lock") && !($ender_8 && $count_8) && !$count_joker)
                        self::setGameStateValue("suit_lock_prep", $suit_count[0] + $suit_count[1] * 100 + $suit_count[2] * 10000 + $suit_count[3] * 1000000);

                    $player_name = self::getActivePlayerName();
                    self::notifyAllPlayers('playCard', clienttranslate('${player_name} plays ${card_list}'), [
                        'player_id' => $player_id,
                        'player_name' => $player_name,
                        'card_list' => $full_log,
                        'combination' => $hand_combination,
                        'suit_lock' => false,
                    ]);

                    $remaining_players = array_keys($this->cards->countCardsByLocationArgs('hand'));
                    if (self::getGameStateValue("illegal_finish") && ($count_joker || (!$current_revolution_status && $count_2) || ($current_revolution_status && $count_3) || ($ender_8 && $count_8)))
                        $rank = 100 - self::getUniqueValueFromDB("SELECT count(*) FROM player WHERE player_role >= 71 AND player_id != $player_id AND player_id NOT IN (".implode(',', $remaining_players).")");
                    else $rank = 1 + self::getUniqueValueFromDB("SELECT count(*) FROM player WHERE player_role < 50 AND player_id != $player_id AND player_id NOT IN (".implode(',', $remaining_players).")");
                    $this->DbQuery("UPDATE player SET player_role = $rank WHERE player_id = $player_id");
                    if ($rank >= 71) self::incStat(1, 'illegal_finish', $player_id);
                    self::notifyAllPlayers("goOut", $rank >= 71 ? clienttranslate('${player_name} goes out by illegal finish and is disqualified!') : clienttranslate('${player_name} goes out!'), [
                        'player_id' => $player_id,
                        'player_name' => $player_name,
                        'rank' => $rank,
                    ]);
                    $player_count = self::getPlayersNumber();
                    if ($rank == 1 && self::getGameStateValue("downfall") && $player_count > 2) {
                        $downfall_player = self::getUniqueValueFromDB("SELECT player_id FROM player WHERE player_role = 1 AND player_id IN (".implode(',', $remaining_players).")");
                        if ($downfall_player) {
                            self::incStat(1, 'downfall', $downfall_player);
                            $this->DbQuery("UPDATE player SET player_role = 70 WHERE player_id = $downfall_player");
                            $this->cards->moveAllCardsInLocation('hand', 'removed', $downfall_player, $downfall_player);
                            self::notifyAllPlayers("goOut", clienttranslate('The former President ${player_name} fails to get the first place and is disqualified!'), [
                                'i18n' => ['role_name'],
                                'player_id' => $downfall_player,
                                'player_name' => self::getPlayerNameById($downfall_player),
                                'role_name' => $player_count > 3 ? clienttranslate('President') : clienttranslate('Minister'),
                                'rank' => 70,
                            ]);
                        }
                    }

                    $hand_player_count = count($this->cards->countCardsByLocationArgs('hand'));
                    if ($revolution && count($hand) >= 4 && $hand_player_count > 1) {
                        self::setGameStateValue("regular_revolution", 1 - $regular_revolution);
                        self::incStat(1, 'revolution', $player_id);
                        self::notifyAllPlayers("noSound", clienttranslate('Playing 4 or more cards reverses the card rank during this round!'), []);
                    }
                    if ($jack_back && $count_jack && !($ender_8 && $count_8) && $hand_player_count > 1) {
                        self::setGameStateValue("temporary_revolution", 1 - $temporary_revolution);
                        self::notifyAllPlayers("noSound", clienttranslate('J effect reverses the card rank during this trick!'), []);
                    }
                    if ($reversing_9 && $count_9) {
                        self::setGameStateValue("reversed", 1 - self::getGameStateValue("reversed"));
                        self::notifyAllPlayers("noSound", clienttranslate('9 effect reverses the turn order permanently!'), []);
                    }
                    if ($ender_8 && $count_8 && $hand_player_count > 1) self::notifyAllPlayers("noSound", clienttranslate('8 effect ends this trick immediately!'), []);

                    $transition = $hand_player_count <= 1 ? 'endRound' : 'autoPlay';
                }
            }
        }
        $this->gamestate->nextState($transition);
    }

    function stEndRound() {
        $scoring_rule = self::getGameStateValue("scoring_rule");
        $target_score = self::getGameStateValue("target_score");
        $hand_players = array_keys($this->cards->countCardsByLocationArgs('hand'));
        $hand_player = array_shift($hand_players);
        $this->DbQuery("UPDATE player SET player_role = 20 WHERE player_id = $hand_player");

        $rank_list = self::getCollectionFromDb("SELECT player_id id, player_role role FROM player ORDER BY player_role", true);
        $player_count = count($rank_list);
        $current_rank = 1;
        $player_list = [];
        foreach ($rank_list as $player_id => $rank) {
            $player_list[$player_id] = $current_rank;
            $current_rank++;
        }

        $nameRow = [''];
        $roundRankRow = [['str' => clienttranslate('Round rank'), 'args' => []]];
        $roundScoreRow = [['str' => clienttranslate('Round score'), 'args' => []]];
        $totalScoreRow = [['str' => clienttranslate('Total score'), 'args' => []]];
        foreach ($player_list as $player_id => $rank) {
            $nameRow[] = [
                'str' => '${player_name}',
                'args' => ['player_name' => self::getPlayerNameById($player_id)],
                'type' => 'header'
            ];
            $roundRankRow[] = $rank;
            if ($scoring_rule) {
                if ($rank == 1) $score = $player_count > 3 ? 1 : 0;
                else if ($rank == $player_count) $score = $player_count > 3 ? -3 : -2;
                else if ($rank == 2 && $player_count > 3) $score = 0;
                else if ($rank == $player_count - 1 && $player_count > 3) $score = -2;
                else $score = -1;
            } else $score = $player_count - $rank;
            
            if ($rank == 1) self::incStat(1, 'first_rank', $player_id);
            else if ($rank == $player_count) self::incStat(1, 'last_rank', $player_id);
            else if ($rank == 2 && $player_count > 3) self::incStat(1, 'second_rank', $player_id);
            else if ($rank == $player_count - 1 && $player_count > 3) self::incStat(1, 'second_last_rank', $player_id);

            $roundScoreRow[] = $score;
            $totalScoreRow[] = $this->dbIncScore($player_id, $score);
            if ($score) self::notifyAllPlayers('scoreChange', abs($score) == 1 ? clienttranslate('${player_name} scores ${nb} point') : clienttranslate('${player_name} scores ${nb} points'), [
                'player_id' => $player_id,
                'player_name' => self::getPlayerNameById($player_id),
                'nb' => $score,
            ]);
        }

        self::notifyAllPlayers('tableWindow', '', [
            'id' => 'roundScoring',
            "title" => clienttranslate("Round end summary"),
            'table' => [$nameRow, $roundRankRow, $roundScoreRow, $totalScoreRow],
            "closing" => clienttranslate("Close"),
        ]);

        if ($scoring_rule) $transition = self::getUniqueValueFromDb("SELECT MIN(player_score) FROM player") <= 0 ? 'endGame' : 'nextRound';
        else $transition = self::getUniqueValueFromDb("SELECT MAX(player_score) FROM player") >= $target_score * $player_count ? 'endGame' : 'nextRound';
        if ($transition == 'endGame') foreach ($player_list as $player_id => $rank) $this->DbQuery("UPDATE player SET player_score_aux = -$rank WHERE player_id = $player_id");
        $this->gamestate->nextState($transition);
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

    function zombieTurn ($state, $active_player) {
        $statename = $state['name'];

        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                case 'presidentGive':
                    $all_ids = self::getObjectListFromDB("SELECT card_id FROM card WHERE card_location = 'hand' AND card_location_arg = $active_player", true);
                    shuffle($all_ids);
                    $card_ids = array_slice($all_ids, 0, 2);
                    $this->giveCard($card_ids);
                    break;
                case 'ministerGive':
                    $all_ids = self::getObjectListFromDB("SELECT card_id FROM card WHERE card_location = 'hand' AND card_location_arg = $active_player", true);
                    shuffle($all_ids);
                    $card_id = array_shift($all_ids);
                    $this->giveCard([$card_id]);
                    break;
                case 'playerTurn':
                    $this->DbQuery("UPDATE player SET player_has_passed = 1 WHERE player_id = '$active_player'");
                    self::notifyAllPlayers("passTurn", '', [
                        'player_id' => $active_player,
                        'player_name' => self::getPlayerNameById($active_player),
                    ]);
                    $this->gamestate->nextState('');
                    break;
            }
            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive($active_player, '');
            return;
        }

        throw new feException("Zombie mode not supported at this game state: ".$statename);
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
    
    function upgradeTableDb ($from_version) {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345

        if ($from_version <= 2102022025) {
            self::setGameStateValue("scoring_rule", 1);
            self::applyDbUpgradeToAllDB("UPDATE DBPREFIX_card SET card_type = card_type - 1");
            self::applyDbUpgradeToAllDB("UPDATE DBPREFIX_card SET card_type_arg = card_type_arg - ".(self::getGameStateValue("automatic_skip") ? 1 : 2));
            self::applyDbUpgradeToAllDB("UPDATE DBPREFIX_card SET card_type_arg = 0 WHERE card_type = 4");
            self::applyDbUpgradeToAllDB("UPDATE DBPREFIX_player SET player_score = player_score / 5");
        }
    }    
}