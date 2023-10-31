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
  
require_once(APP_BASE_PATH."view/common/game.view.php");
  
class view_president_president extends game_view {
    protected function getGameName() {return "president";}
    
    function build_page ($viewArgs) {
        // Get players & players number
        global $g_user;
        $game_name = self::getGameName();
        $template = $game_name . "_" . $game_name;
        $players = $this->game->loadPlayersBasicInfos();
        $players_nbr = count($players);

        /*********** Place your code below:  ************/

        $this->tpl['MY_HAND'] = self::_("My hand");
        $this->tpl['REORDER_CARDS_BY_VALUE'] = self::_("Sort cards by number");
        $this->tpl['REORDER_CARDS_BY_SUIT'] = self::_("Sort cards by suit");

        $variant = false;
        
        $revolution = $this->game->getGameStateValue("revolution");
        if ($revolution) {
            $this->tpl['REVOLUTION'] = self::_("Revolution");
            $variant = true;
        } else $this->tpl['REVOLUTION'] = '';

        $joker = $this->game->getGameStateValue("joker");
        if ($joker) {
            $this->tpl['GAP_1'] = $variant ? ", " : "";
            $this->tpl['JOKER'] = self::_("Joker");
            $variant = true;
        } else {
            $this->tpl['GAP_1'] = "";
            $this->tpl['JOKER'] = "";
        }

        $first_player_mode = $this->game->getGameStateValue("first_player_mode");
        $this->tpl['GAP_2'] = $variant ? ", " : "";
        $this->tpl['FIRST_PLAYER_MODE'] = $first_player_mode ? self::_("Highest player first") : self::_("Lowest player first");

        $variant_data = [
            ["state_name" => "same_rank_skip", "label" => "SAME_RANK_SKIP", "variant" => clienttranslate('Same rank skip')],
            ["state_name" => "sequence", "label" => "SEQUENCE", "variant" => clienttranslate('Same suit sequence')],
            ["state_name" => "suit_lock", "label" => "SUIT_LOCK", "variant" => clienttranslate('Suit lock')],
            ["state_name" => "ender_8", "label" => "ENDER_8", "variant" => clienttranslate('Ender 8')],
            ["state_name" => "reversing_9", "label" => "REVERSING_9", "variant" => clienttranslate('Reversing 9')],
            ["state_name" => "jack_back", "label" => "JACK_BACK", "variant" => clienttranslate('Jack back')],
            ["state_name" => "illegal_finish", "label" => "ILLEGAL_FINISH", "variant" => clienttranslate('Illegal finish')],
            ["state_name" => "downfall", "label" => "DOWNFALL", "variant" => clienttranslate('Downfall')],
        ];
        for ($i = 0; $i < count($variant_data); $i++) {
            if ($this->game->getGameStateValue($variant_data[$i]['state_name'])) {
                $this->tpl['GAP_'.($i + 3)] = ", ";
                $this->tpl[$variant_data[$i]['label']] = self::_($variant_data[$i]['variant']);
            } else {
                $this->tpl['GAP_'.($i + 3)] = "";
                $this->tpl[$variant_data[$i]['label']] = "";
            }
        }

        $this->tpl['GAME_BOARD_WIDTH'] = '';
        switch ($players_nbr) {
            case 2:
                $this->tpl['GAME_BOARD_WIDTH'] = 'two_player';
                $directions = ['S', 'N'];
                break;
            case 3:
                $this->tpl['GAME_BOARD_WIDTH'] = 'three_player';
                $directions = ['S', 'NNW', 'NNE'];
                break;
            case 4:
                $this->tpl['GAME_BOARD_WIDTH'] = 'shrink';
                $directions = ['S', 'W', 'N', 'E'];
                break;
            case 5:
                $this->tpl['GAME_BOARD_WIDTH'] = 'shrink';
                $directions = ['S', 'W', 'NNW', 'NNE', 'E'];
                break;
            case 6:
                $this->tpl['GAME_BOARD_WIDTH'] = 'shrink';
                $directions = ['S', 'W', 'NWW', 'N', 'NEE', 'E'];
                break;
            case 7:
                $directions = ['S', 'W', 'NWW', 'NNW', 'NNE', 'NEE', 'E'];
                break;
            case 8:
                $directions = ['S', 'W', 'NWW', 'NNW', 'N', 'NNE', 'NEE', 'E'];
                break;
        }

        $this->page->begin_block($template, "player");
        if ($this->game->isSpectator()) {
            $this->tpl['THIS_PLAYER_ID'] = '';
            $this->tpl['SPECTATOR'] = 'spectator';
            foreach ($players as $player_id => $info) {
                $dir = array_shift($directions);
                $this->page->insert_block("player", [
                    "PLAYER_ID" => $player_id,
                    "PLAYER_NAME" => $info['player_name'],
                    "PLAYER_COLOR" => $info['player_color'],
                    "DIR" => $dir,
                ]);
            }
        } else {
            $player_id = $g_user->get_id();
            $this->tpl['THIS_PLAYER_ID'] = $player_id;
            $this->tpl['SPECTATOR'] = '';
            $player_id = $this->game->getPlayerAfter($player_id);
            array_shift($directions);
            for ($i = 1; $i < $players_nbr; $i++) {
                $dir = array_shift($directions);
                $this->page->insert_block("player", [
                    "PLAYER_ID" => $player_id,
                    "PLAYER_NAME" => $players[$player_id]['player_name'],
                    "PLAYER_COLOR" => $players[$player_id]['player_color'],
                    "DIR" => $dir,
                ]);
                $player_id = $this->game->getPlayerAfter($player_id);
            }
        }

        $this->page->begin_block($template, "audio_list");
        $audio_list = $this->game->audio_list;
        foreach ($audio_list as $audio) {
            $this->page->insert_block("audio_list", [
                "GAME_NAME" => $game_name,
                "AUDIO" => $audio,
            ]);
        }
    }
}