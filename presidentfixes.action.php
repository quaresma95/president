<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Fixes and variants implementation: © ufm <tel2tale@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 */

class action_president extends APP_GameAction {
    public function __default() {
        if(self::isArg('notifwindow')) {
            $this->view = "common_notifwindow";
            $this->viewArgs['table'] = self::getArg("table", AT_posint, true);
        } else {
           $this->view = "president_president";
           self::trace("Complete reinitialization of board game");
        }
    }

    public function giveCard() {
        self::setAjaxMode();
        $card_ids_raw = self::getArg("card_ids", AT_numberlist, true);
        if (substr($card_ids_raw, -1 ) == ';') $card_ids_raw = substr($card_ids_raw, 0, -1);
        $card_ids = $card_ids_raw == '' ? [] : explode(';', $card_ids_raw);
        $this->game->giveCard($card_ids);
        self::ajaxResponse();
    }

    public function playCard() {
        self::setAjaxMode();
        $card_ids_raw = self::getArg("card_ids", AT_numberlist, true);
        if (substr($card_ids_raw, -1 ) == ';') $card_ids_raw = substr($card_ids_raw, 0, -1);
        $card_ids = $card_ids_raw == '' ? [] : explode(';', $card_ids_raw);
        $this->game->playCard($card_ids);
        self::ajaxResponse();
    }

    public function passTurn() {
        self::setAjaxMode();
        $this->game->passTurn();
        self::ajaxResponse();
    }
}