<?php
/**
 * Copyright (c) 2020. Quaresma.
 */
  
  require_once( APP_BASE_PATH."view/common/game.view.php" );
  
  class view_president_president extends game_view
  {
    function getGameName() {
        return "president";
    }    
  	function build_page( $viewArgs )
  	{		
        global $g_user;
  	    // Get players & players number
        $players = [];
        $allPlayers = $this->game->loadPlayersBasicInfos();
        $current_player_id = $g_user->get_id();
        $nb_cards = $this->game->cards->countCardsByLocationArgs('hand');
        
        $players[$current_player_id] = $allPlayers[$current_player_id];
        for ($i = 0 ; $i < 4; $i++) {
          $current_player_id = $this->game->getPlayerAfter( $current_player_id );
          $players[$current_player_id] = $allPlayers[$current_player_id];
        }

        $players_nbr = count( $players );
        /*********** Place your code below:  ************/

        $template = self::getGameName() . "_" . self::getGameName();

        $i = 1;
        // this will inflate our player block with actual players data
        $this->page->begin_block($template, "player");
        foreach ( $players as $player_id => $info ) {
          $this->page->insert_block("player", [
            "PLAYER_ID" => $player_id,
            "PLAYER_NAME" => $players [$player_id] ['player_name'],
            "PLAYER_COLOR" => $players [$player_id] ['player_color'],
            "NB_CARDS" => isset($nb_cards[$player_id]) ? isset($nb_cards[$player_id]) : 0,
            "NB_PLAYER" => $players_nbr,
            "PLACE_ID" => $i,
          ]);
          $i++;
        }

        $this->tpl['MY_HAND'] = self::_("My hand");

        /*********** Do not change anything below this line  ************/
  	}
  }
  

