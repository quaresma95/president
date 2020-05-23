<?php
/**
 * Copyright (c) 2020. Quaresma.
 */

class action_president extends APP_GameAction
{
    // Constructor: please do not modify
    public function __default()
    {
        if (self::isArg('notifwindow')) {
            $this->view = "common_notifwindow";
            $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
        } else {
            $this->view = "president_president";
        }
    }

    public function playCard()
    {
        self::setAjaxMode();

        $card_ids_raw = self::getArg("cards", AT_numberlist, true);
        if( substr( $card_ids_raw, -1 ) == ';' ) {
            $card_ids_raw = substr($card_ids_raw, 0, -1);
        }
        if( $card_ids_raw == '' ) {
            $card_ids = array();
        } else {
            $card_ids = explode( ';', $card_ids_raw );
        }

        $this->game->playCards($card_ids);
        self::ajaxResponse();
    }

    public function passTurn()
    {
        self::setAjaxMode();
        $this->game->passTurn();
        self::ajaxResponse();
    }

    public function swapCards()
    {
        self::setAjaxMode();

        $card_ids_raw = self::getArg("cards", AT_numberlist, true);
        if( substr( $card_ids_raw, -1 ) == ';' ) {
            $card_ids_raw = substr($card_ids_raw, 0, -1);
        }
        if( $card_ids_raw == '' ) {
            $card_ids = array();
        } else {
            $card_ids = explode( ';', $card_ids_raw );
        }
        $this->game->swapCards($card_ids);
        self::ajaxResponse();
    }

    function argGiveCards()
    {
        return array ();
    }
}
