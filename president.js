/**
 * Copyright (c) 2020. Quaresma.
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock"
],

function (dojo, declare) {
    return declare("bgagame.president", ebg.core.gamegui, {
        constructor: function(){
            this.cardwidth = 89;
            this.cardheight = 129;
            this.revolutionTrick = 0;
        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
        setup: function( gamedatas )
        {
            // Player hand
            this.handler = null;
            this.revolutionTrick = this.gamedatas.currentOrder;  
            this.playerHand = new ebg.stock(); // new stock object for hand
            this.playerHand.create( this, $('myhand'), this.cardwidth, this.cardheight );
            this.playerHand.image_items_per_row = 13;
            this.handler = dojo.connect( this.playerHand, 'onClickOnItem', this, 'onSelectCard' );

            for (var color = 1; color <= 4; color++) {
                for (var value = 3; value <= 15; value++) {
                    // Build card type id
                    var card_type_id = this.getCardUniqueId(color, value);
                    this.playerHand.addItemType(card_type_id, parseInt(value), g_gamethemeurl + 'img/cards.jpg', card_type_id);
                }
            }
            //add jockers
            var jockerR = this.getCardUniqueId(5, 3);
            var jockerB = this.getCardUniqueId(5, 4);

            this.playerHand.item_margin=10;
            this.playerHand.addItemType(jockerR, 33, g_gamethemeurl + 'img/cards.jpg', jockerR);
            this.playerHand.addItemType(jockerB, 34, g_gamethemeurl + 'img/cards.jpg', jockerB);

            // Cards in player's hand
            for ( var i in this.gamedatas.hand) {
                var card = this.gamedatas.hand[i];
                var color = card.type;
                var value = card.type_arg;

                if (color == 5) {
                    value -= 930;
                }

                this.playerHand.addToStockWithId(this.getCardUniqueId(color, value), card.id);
            }

            // Cards played on table
            if (this.gamedatas.cardsontable) {
                for (i in this.gamedatas.cardsontable) {
                    var cards = this.gamedatas.cardsontable[i];
                    this.playCardOnTable(cards[0].location_arg, cards);
                }
            }

            if (this.revolutionTrick == 1) {
                this.setupRevolutionTrick();
            }

            //setup players board
            this.setupPlayersBoard();

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            this.setupToolTips();
        },

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            switch( stateName )
            {
                case 'dummmy':
                    break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            switch( stateName )
            {
                case 'newRound':
                    this.resetRound();
                    break;
                case 'dummmy':
                    break;
                }
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
                    case 'playerTurn':
                        if (this.handler) {
                            dojo.disconnect( this.handler);
                        }
                        this.handler = dojo.connect( this.playerHand, 'onClickOnItem', this, 'onSelectCard' );
                        this.addActionButton( 'play', _('play cards'), 'onPlayerHandSelectionChanged' );
                        this.addActionButton( 'pass', _('pass turn'), 'onPassTurn', null, false, 'red');
                        break;
                    case 'presidentSwapTurn' :
                    case 'primeMinisterSwapTurn' :
                        if (this.handler) {
                            dojo.disconnect( this.handler);
                        }
                        this.handler = dojo.connect( this.playerHand, 'onClickOnItem', this, 'onSelectSwap' );
                        this.addActionButton( 'swap', _('swap card(s)'), 'onSwapCards' );
                        break;
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods

        resetRound: function () {
            // clean table
            dojo.query('.cardOnTable').remove();

            // reset players status
            dojo.query( '.cards_board').remove();
            var iconsPerRole = this.gamedatas.icons_per_position[this.gamedatas.nb_players];
            for ( var i in this.gamedatas.nb_cards) {
                var nbCards = this.gamedatas.nb_cards[i].nb_cards;
                var player = this.gamedatas.players[i];

                dojo.place(this.format_block('jstpl_isPlaying', {
                    playingClass : nbCards > 0 ? 'icon20 icon20_want_to_play' : 'icon20 icon20_know_game',
                }), $('playerIsPlaying_p'+i), 'only');

                dojo.place( this.format_block('jstpl_player_board', {
                    id : i,
                    count : nbCards,
                    playingClass : player.pass == '0' && nbCards > 0 ? '' : 'qHidden',
                    passClass : player.pass == '0' || nbCards == 0 ? 'qHidden' : '',
                    roleClass : player.role == '0' ? 'qHidden' : iconsPerRole[player.role]
                }), $('player_board_'+i) );
            }
        },

        setupToolTips: function () {
            this.addTooltipToClass( 'cards_count', '', _('Number of cards in hand'), '' );
            this.addTooltipToClass( 'revolution', '', _('Revolution the ranking of cards are reversed'), '' );
            this.addTooltipToClass( 'passClass', '', _('This player are out of the round'), '' );
            this.addTooltipToClass( 'playingClass', '', _('This player is still playing'), '' );
            this.addTooltipToClass( 'iconPresident', '', _('At the beginning of each game the "president" will receive the 2 best cards of the "beggar" and must give 2 cards of his choice'));
            this.addTooltipToClass( 'iconPrimeMinister', '', _('At the beginning of each game the "prime minister" will receive the best card of the "peasant" and must give 1 card of his choice'));
            this.addTooltipToClass( 'iconCitizen', '', _('The "Citizen" don\'t swap cards'));
            this.addTooltipToClass( 'iconPeasant', '', _('The "Peasant" must give his best card to the "Prime-Minister"'));
            this.addTooltipToClass( 'iconBeggar', '', _('The "Beggar" must give his 2 best cards to the "President"'));
        },

        updatePlayersBoard: function(notif) {
            var player_id = notif.args.player_id;
            var player = this.gamedatas.players[player_id];
            var cards_played = notif.args.cards ? notif.args.cards.length : 0;
            var iconsPerRole = this.gamedatas.icons_per_position[this.gamedatas.nb_players];
            dojo.query( '#cards_board_p'+player_id).remove();
            this.gamedatas.nb_cards[player_id].nb_cards -= cards_played;
            var nbCards = this.gamedatas.nb_cards[player_id].nb_cards;

            dojo.place(this.format_block('jstpl_isPlaying', {
                playingClass : notif.type == 'passTurn' ? 'icon20 icon20_know_game' : 'icon20 icon20_want_to_play',
            }), $('playerIsPlaying_p'+player_id), 'only');

            dojo.place(this.format_block('jstpl_counterHand', {
                nbCards : nbCards,
            }), $('playerCardCount_p'+player_id), 'only');

            dojo.place( this.format_block('jstpl_player_board', {
                id : player_id,
                count : nbCards,
                playingClass : notif.type == 'passTurn' ? 'qHidden' : '',
                passClass : notif.type == 'passTurn' ? '' : 'qHidden',
                roleClass : player.role == '0' ? 'qHidden' : iconsPerRole[player.role]
            }), $('player_board_'+player_id) );

            this.setupToolTips();
        },

        setupPlayersBoard: function() {
            dojo.query( '.cards_board').remove();
            var iconsPerRole = this.gamedatas.icons_per_position[this.gamedatas.nb_players];
            for ( var i in this.gamedatas.nb_cards) {
                var nbCards = this.gamedatas.nb_cards[i].nb_cards;
                var player = this.gamedatas.players[i];

                dojo.place(this.format_block('jstpl_isPlaying', {
                    playingClass : player.pass == '0' && nbCards > 0 ? 'icon20 icon20_want_to_play' : 'icon20 icon20_know_game',
                }), $('playerIsPlaying_p'+i));

                dojo.place(this.format_block('jstpl_counterHand', {
                    nbCards : nbCards,
                }), $('playerCardCount_p'+i));

                if (player.role != '0') {
                    dojo.place(this.format_block('jstpl_role', {
                        roleClass : iconsPerRole[player.role]
                    }), $('playerCardRole_p'+i));
                }

                dojo.place( this.format_block('jstpl_player_board', {
                    id : i,
                    count : nbCards,
                    playingClass : player.pass == '0' && nbCards > 0 ? '' : 'qHidden',
                    passClass : player.pass == '0' || nbCards == 0 ? 'qHidden' : '',
                    roleClass : player.role == '0' ? 'qHidden' : iconsPerRole[player.role]
                }), $('player_board_'+i) );
            }

            // init table round count
            this.setupRoundCount();
        },
        // Get card unique identifier based on its color and value
        getCardUniqueId : function(color, value) {
            return (color - 1) * 13 + (value - 3);
        },

        setupRevolutionTrick: function() {
            if (this.revolutionTrick == 1) {
                // insert logo
                dojo.removeClass( 'revolution_box', 'qHidden' );        
            } else {
                //remove logo
                dojo.addClass( 'revolution_box', 'qHidden' );
            }

            // revert cards order
            var weight = 100;
            var items_reversed = {};
            var items = this.playerHand.getAllItems();

            for ( var i in items) {
                var color = Math.floor(items[i].type / 13) + 1;  
                if (color == 5) {
                    items_reversed[items[i].type] = 934;
                } else {
                    items_reversed[items[i].type] = weight;
                    weight--;
                }
            }

            this.playerHand.changeItemsWeight(items_reversed);
        },

        setupRoundCount: function() {
            var round = this.gamedatas.nb_round;
            if (this.gamedatas.max_round) {
                round = round + "/" + this.gamedatas.max_round;
            }
            $('round_count').innerHTML = round;
        },

        ///////////////////////////////////////////////////
        //// Player's action

        onSelectSwap : function (evt) {
            if (this.gamedatas.gamestate.args && this.gamedatas.gamestate.args[this.getCurrentPlayerId()]) {
                var last_card_selected_id = evt.target.id.replace('myhand_item_', '');
                var nb_cards = this.gamedatas.gamestate.args[this.getCurrentPlayerId()].nbr;
                var items = this.playerHand.getSelectedItems();
                if ( items.length > nb_cards) {
                    this.playerHand.unselectAll();
                    this.playerHand.selectItem(last_card_selected_id);
                }
            }
        },

        onSelectCard : function ( evt, item_id ) {
            var items = this.playerHand.getSelectedItems();
            var card_selected_value = null;
            var last_card_selected_id = evt.target.id.replace('myhand_item_', '');

            for (var i in items) {
                var type = items[i].type;
                var card_value = type % 13 + 3;

                if (card_selected_value && card_selected_value == card_value) {
                    continue;
                } else {
                    if (card_selected_value) {
                        this.playerHand.unselectAll();
                        this.playerHand.selectItem(last_card_selected_id);
                    }
                    card_selected_value = card_value;
                }
            }
        },

        onSwapCards : function () {
            if (this.checkAction('swapCards', true)) {
                this.qItems = this.playerHand.getSelectedItems();
                if (this.qItems.length > 0) {
                    var cards = "";
                    for (var i in this.qItems) {
                        cards += this.qItems[i].id+";"
                    }
                    this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/swapCards.html", {
                        cards: cards,
                        lock: true
                    }, this, function (result) {
                    }, function (is_error) {
                    });
                }
            }
        },

        onPassTurn : function () {
            if (this.checkAction('passTurn', true)) {
                this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/passTurn.html", {
                    lock: true
                }, this, function (result) {
                }, function (is_error) {
                });
            }
        },

        onPlayerHandSelectionChanged : function() {
            var items = this.playerHand.getSelectedItems();

            if (items.length > 0) {
                if (this.checkAction('playCard', true)) {
                    var cards = "";
                    for (var i in items) {
                        cards += items[i].id+";"
                    }
                    // Can play a card
                    this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/playCard.html", {
                        cards : cards,
                        lock : true
                    }, this, function(result) {
                    }, function(is_error) {
                        if (is_error) {
                        }
                    });

                    this.playerHand.unselectAll();
                } else {
                    this.playerHand.unselectAll();
                }
            }
        },

        playCardOnTable : function(player_id, items) {
            var cards = [];
            var margins = [
                ['0px'],
                ['-7px', '8px'],
                ['-15px', '0px', '15px'],
                ['-22px', '-7px', '8px', '23px'],
            ];
            var margin = margins[items.length-1]

            var play_id = (Date.now().toString(36) + Math.random().toString(36).substr(2, 9));
            dojo.place(this.format_block('jstpl_plays', {
                play_id : play_id
            }), 'tableCard');

            for (var i in items) {
                cards.push(items[i]);
                if (items[i].color) {
                    var color = items[i].color;
                    var value = items[i].value;
                } else {
                    if (items[i].type_arg) {
                        var color = items[i].type;
                        var value = items[i].type_arg;
                    } else {
                        continue;
                    }
                }

                if (color == 5) {
                    value -= 930;
                    dojo.query('.cardOnTable').remove();
                    dojo.place(this.format_block('jstpl_plays', {
                        play_id : play_id
                    }), 'tableCard');
                }

                dojo.place(this.format_block('jstpl_cardontable', {
                    x : this.cardwidth * (value - 3),
                    y : this.cardheight * (color - 1),
                    margin : margin[i],
                    card_id : items[i].id,
                }), 'play_'+play_id);
            }

            if (player_id != this.player_id) {
                // Some opponent played a card
                // Move card from player panel
                this.placeOnObject('play_'+play_id, 'player_cards_' + player_id);
            } else {
                for (var i in cards) {
                    var card_id = cards[i].card_id;
                    // You played a card. If it exists in your hand, move card from there and remove
                    // corresponding item
                    if ($('myhand_item_' + card_id)) {
                        this.placeOnObject('play_'+play_id, 'myhand_item_' + card_id);
                        this.playerHand.removeFromStockById(card_id);
                    }
                }
            }
            
            // In any case: move it to its final destination
            this.slideToObjectPos('play_'+play_id, 'tableCard', 0, 0).play();
        },

        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your president.game.php file.
        
        */
        setupNotifications: function() {
            dojo.subscribe('newHand', this, "notif_newHand");
            dojo.subscribe('playCard', this, "notif_playCard");
            dojo.subscribe('passTurn', this, "notif_passTurn");
            dojo.subscribe('swapCards', this, "notif_swapCards");
            dojo.subscribe('resetCounters', this, "notif_resetCounters");
            dojo.subscribe('revolutionTrick', this, "notif_revolution");
            dojo.subscribe( 'roundWin', this, "notif_roundWin" );
            this.notifqueue.setSynchronous( 'roundWin', 2000 );
            dojo.subscribe( 'giveAllCardsToPlayer', this, "notif_giveAllCardsToPlayer" );
            dojo.subscribe( 'newScores', this, "notif_newScores" );
            dojo.subscribe( 'playerFinished', this, "notif_playerFinished" );
            dojo.subscribe( 'playerWon', this, "notif_playerWon" );
            this.notifqueue.setSynchronous( 'notif_playerWon', 2000 );
        },

        notif_newHand : function(notif) {
            // reset revolution state
            this.revolutionTrick = 0;
            dojo.addClass( 'revolution_box', 'qHidden' );

            //increment round counter
            this.gamedatas.nb_round++;
            this.setupRoundCount();

            //clean table
            dojo.query('.cardOnTable').remove();
            this.playerHand.removeAll();
            
            var items_weight = {};
            for ( var i in notif.args.cards) {
                var card = notif.args.cards[i];
                var color = card.type;
                var value = card.type_arg;
                var uniqueId = this.getCardUniqueId(color, value);

                if (color == 5) {
                    value -= 930;
                    uniqueId = this.getCardUniqueId(color, value);
                    items_weight[uniqueId] = 934;
                } else {
                    items_weight[uniqueId] = parseInt(value);
                }

                this.playerHand.addToStockWithId(uniqueId, card.id);
            }

            this.playerHand.changeItemsWeight(items_weight);
            this.setupToolTips();
        },

        notif_resetCounters : function(notif) {
            for (var player_id in notif.args.nb_cards) {
                dojo.query( '#cards_board_p'+player_id).remove();
                var role = notif.args.players[player_id].role;
                var iconRole = this.gamedatas.icons_per_position[this.gamedatas.nb_players][role];
                this.gamedatas.nb_cards[player_id].nb_cards = notif.args.nb_cards[player_id];
                this.gamedatas.players[player_id].role = role;
                var nbCards = this.gamedatas.nb_cards[player_id].nb_cards;

                dojo.place( this.format_block('jstpl_player_board', {
                    id : player_id,
                    count : nbCards,
                    playingClass : '',
                    passClass : 'qHidden',
                    roleClass : iconRole
                }), $('player_board_'+player_id) );

                dojo.place(this.format_block('jstpl_isPlaying', {
                    playingClass : nbCards > 0 ? 'icon20 icon20_want_to_play' : 'icon20 icon20_know_game',
                }), $('playerIsPlaying_p'+player_id), 'only');

                dojo.place(this.format_block('jstpl_counterHand', {
                    nbCards : nbCards,
                }), $('playerCardCount_p'+player_id), 'only');
                
                dojo.place(this.format_block('jstpl_role', {
                    roleClass : iconRole
                }), $('playerCardRole_p'+player_id), 'only');
            }
        },

        notif_playerFinished : function(notif) {
        },

        notif_passTurn : function(notif) {
            // player pass is turn
            this.updatePlayersBoard(notif);
        },

        notif_playCard : function(notif) {
            // Play a card on the table
            this.playCardOnTable(notif.args.player_id, notif.args.cards);
            this.updatePlayersBoard(notif);
        },

        notif_swapCards : function(notif) {
            // Play a card on the table
            if (notif.args.cards) {
                for (var i in notif.args.cards) {
                    var card = notif.args.cards[i];
                    if (card.type == 5) {
                        card.type_arg -= 930;
                    }
                    this.playerHand.addToStockWithId(this.getCardUniqueId(card.type, card.type_arg), card.id);
                }
            }
            if (notif.args.cardsSent) {
                for (var i in notif.args.cardsSent) {
                    var card = notif.args.cardsSent[i];
                    this.playerHand.removeFromStockById(card.id);
                }
            }
        },

        notif_playerWon : function(notif) {
            // We do nothing here (just wait in order players can view the 4 cards played before they're gone.
        },

        notif_roundWin : function(notif) {
            // We do nothing here (just wait in order players can view the 4 cards played before they're gone.
        },

        notif_giveAllCardsToPlayer : function(notif) {
            // Move all cards on table to given table, then destroy them
            var winner_id = notif.args.player_id;
            for ( var player_id in this.gamedatas.players) {
                var anim = this.slideToObject('cardontable_' + player_id, 'overall_player_board_' + winner_id);
                dojo.connect(anim, 'onEnd', function(node) {
                    dojo.destroy(node);
                });
                anim.play();
            }
        },

        notif_newScores : function(notif) {
            // Update players' scores
            for ( var player_id in notif.args.newScores) {
                this.scoreCtrl[player_id].toValue(notif.args.newScores[player_id]);
            }
        },

        notif_revolution : function(notif) {
            this.revolutionTrick = this.revolutionTrick == 0 ? 1 : 0;
            this.setupRevolutionTrick();
        },
   });             
});
