/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Fixes and variants implementation: © ufm <tel2tale@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock",
],
function (dojo, declare) {
    return declare("bgagame.president", ebg.core.gamegui, {
        constructor: function() {
            this.cardwidth = 84;
            this.cardheight = 117;
            this.hand_counters = {};
            this.pass_warning = true;
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

        setup: function (gamedatas) {
            // Audio
            if (this.prefs[102].value != 1) {
                gamedatas.audio_list.forEach(s => {
                    this.dontPreloadImage(this.game_name + '_' + s + '.mp3');
                    this.dontPreloadImage(this.game_name + '_' + s + '.ogg');
                });
            }

            this.regular_revolution = this.gamedatas.regular_revolution > 0;
            this.temporary_revolution = this.gamedatas.temporary_revolution > 0;
            this.reversed = this.gamedatas.reversed > 0;
            this.suit_lock = this.gamedatas.suit_lock_complete > 0;
            this.addTooltip('reverse_text', _('Turn order has been reversed'), '');
            this.addTooltip('lock_text', _('The same suit must be followed'), '');
            this.updateSpecialStatus();
            if (this.reversed) {
                this.gamedatas.playerorder = this.gamedatas.playerorder.reverse();
                this.gamedatas.playerorder.unshift(this.gamedatas.playerorder.pop());
                this.updatePlayerOrdering();
            }

            // Counter and stock initialization
            if (this.isSpectator) document.getElementById("myhand_wrap").style.display = 'none';
            for (let i = 1; i <= 3; i++) if (this.prefs[100].value != i) this.dontPreloadImage('cards_' + i + '.png');
            this.playerHand = new ebg.stock();
            this.playerHand.create(this, $('myhand'), this.cardwidth, this.cardheight);
            this.playerHand.extraClasses = 'stock_card_border card_' + this.prefs[100].value;
            this.playerHand.image_items_per_row = 14;
            this.playerHand.centerItems = true;
            this.playerHand.onItemCreate = dojo.hitch(this, 'setupNewCard');
            this.playerHand.apparenceBorderWidth = '2px';
            this.playerHand.setSelectionMode(2);
            this.playerHand.setSelectionAppearance('class');
            if (this.prefs[101].value != 1) {
                this.playerHand.horizontal_overlap = 28;
                this.playerHand.item_margin = 0;
            }
            for (let color = 0; color < 4; color++)
                for (let value = 1; value <= 13; value++) {
                    let card_type_id = this.getCardUniqueId(color, value);
                    this.playerHand.addItemType(card_type_id, this.getCardWeight(color, value), g_gamethemeurl + 'img/cards_' + this.prefs[100].value + '.png', card_type_id);
                }
            this.playerHand.addItemType(this.getCardUniqueId(4, 0), this.getCardWeight(4, 0), g_gamethemeurl + 'img/cards_' + this.prefs[100].value + '.png', 0);

            if (this.gamedatas.sequence > 0) {
                dojo.connect($('order_by_value'), 'onclick', this, 'onReorderByValue');
                dojo.connect($('order_by_suit'), 'onclick', this, 'onReorderBySuit');
            } else {
                document.getElementById('order_by_value').style.display = 'none';
                document.getElementById('order_by_suit').style.display = 'none';
            }

            // Setting up player boards
            for (let player_id in gamedatas.players) {
                const player = gamedatas.players[player_id];

                if (gamedatas.scoring_rule == 0) {
                    let target_score_tag = document.createElement("span");
                    target_score_tag = ' / ' + (gamedatas.target_score * Object.keys(gamedatas.players).length);
                    document.getElementById('player_score_' + player_id).after(target_score_tag);
                }

                if (player_id != this.player_id) {
                    document.getElementById('playertablecard_' + player_id).classList.add('card_' + this.prefs[100].value);
                    this.hand_counters[player_id] = new ebg.counter();
                    this.hand_counters[player_id].create('hand_' + player_id);
                    if (gamedatas.hand_count[player_id]) {
                        this.roleTextChange(player_id, player.role);
                        this.hand_counters[player_id].setValue(gamedatas.hand_count[player_id]);
                        if (player.pass > 0) document.getElementById('playertable_' + player_id).classList.add('passed');
                    } else {
                        this.roleTextChange(player_id, 0);
                        this.hand_counters[player_id].setValue(0);
                        document.getElementById('playertable_' + player_id).classList.add('went_out');
                        document.getElementById('playertablecard_' + player_id).classList.add('went_out');
                        document.getElementById('hand_' + player_id).classList.add('went_out');
                        document.getElementById('rank_' + player_id).classList.add('went_out');
                        if (player.role == 70) document.getElementById('rank_' + player_id).textContent = '💥';
                        else if (player.role >= 71) document.getElementById('rank_' + player_id).textContent = '🚫';
                        else document.getElementById('rank_' + player_id).textContent = player.role;
                    }
                } else {
                    if (gamedatas.hand_count[player_id]) {
                        this.roleTextChange(player_id, player.role);
                        if (player.pass > 0) document.getElementById('myhand_wrap').classList.add('passed');
                    } else {
                        this.roleTextChange(player_id, 0);
                        document.getElementById('myhand_wrap').classList.add('went_out');
                        if (player.role == 70) document.getElementById('myhand_rank').textContent = '💥';
                        else if (player.role >= 71) document.getElementById('myhand_rank').textContent = '🚫';
                        else document.getElementById('myhand_rank').textContent = player.role;
                    }
                }
            }

            // Cards in hand
            for (let i in gamedatas.hand) {
                let card = gamedatas.hand[i];
                let color = Number(card.type);
                let value = Number(card.type_arg);
                this.playerHand.addToStockWithId(this.getCardUniqueId(color, value), card.id);
            }

            // Cards on table
            const table_combination = gamedatas.table_combination;
            if (typeof table_combination.card_list !== 'undefined') {
                const table_card_count = Object.keys(table_combination.card_list).length;
                for (let i in table_combination.card_list) {
                    let card = table_combination.card_list[i];
                    let color = Number(card.type);
                    let value = Number(card.type_arg);
                    dojo.place(this.format_block('jstpl_card', {
                        id: card.id,
                        x: color == 4 ? 0 : value,
                        y: color == 4 ? 0 : color,
                        card_style: this.prefs[100].value,
                    }), 'played_card_container');
                    const card_div = document.getElementById('card_' + card.id);
                    const coord = this.coordCalculate(i, table_card_count, table_combination.type);
                    card_div.style.left = coord[0] + 'px';
                    card_div.style.top = coord[1] + 'px';
                }
            }

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();
        },
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function (stateName, args) {
            switch (stateName) {
                case 'playerTurn':
                    if (!args.args.passable) {
                        this.gamedatas.gamestate.descriptionmyturn = _('${you} must play a valid combination');
                        this.gamedatas.gamestate.description = _('${actplayer} must play a valid combination');
                        this.updatePageTitle();
                    }
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function (stateName) {
            switch (stateName) {
                case 'playerTurn':
                    this.playerHand.setSelectionMode(0);
                    break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function (stateName, args) {
            if (this.isCurrentPlayerActive()) {
                this.playerHand.setSelectionMode(2);
                switch (stateName) {
                    case 'presidentGive':
                    case 'ministerGive':
                        this.addActionButton('btnGiveCard', _('Give selected'), 'onBtnGiveCard');
                        this.addActionButton('btnResetSelection', _('Reset selection'), 'onBtnResetSelection');
                        break;
                    case 'playerTurn':
                        this.addActionButton('btnPlayCard', _('Play selected'), 'onBtnPlayCard');
                        this.addActionButton('btnResetSelection', _('Reset selection'), 'onBtnResetSelection');
                        if (args.passable) this.addActionButton('btnPassTurn', _('Pass'), 'onBtnPassTurn', null, null, 'red');
                        break;
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */

        getCardUniqueId: function (color, value) {return color == 4 ? 99 : (color * 14 + Number(value));},
        getCardWeight: function (color, value) {
            if (color == 4) return 500;
            else return value * (this.regular_revolution ^ this.temporary_revolution ? -4 : 4) + Number(color);
        },

        onReorderByValue: function (evt) {
            if (evt != null) evt.preventDefault();
            var newWeights = {};
            for (let color = 0; color < 4; color++) {
                for (let value = 1; value <= 13; value++) {
                    var card_type_id = this.getCardUniqueId(color, value);
                    newWeights[card_type_id] = this.getCardWeight(color, value);
                }
            }
            this.playerHand.changeItemsWeight(newWeights);
            document.getElementById('order_by_value').style.display = 'none';
            document.getElementById('order_by_suit').style.display = 'inline';
        },
        onReorderBySuit: function (evt) {
            if (evt != null) evt.preventDefault();
            var newWeights = {};
            for (var color = 0; color < 4; color++) {
                for (var value = 1; value <= 13; value++) {
                    var card_type_id = this.getCardUniqueId(color, value);
                    newWeights[card_type_id] = color * 14 + value * (this.regular_revolution ^ this.temporary_revolution ? -1 : 1);
                }
            }
            this.playerHand.changeItemsWeight(newWeights);
            document.getElementById('order_by_value').style.display = 'inline';
            document.getElementById('order_by_suit').style.display = 'none';
        },

        setupNewCard: function (card_div, card_type_id, card_id) {
            if (card_type_id === 99) this.addTooltip(card_div.id, _('The Joker can be used as the strongest single card which is unaffected by Revolutions, or can be mixed with other cards as a wild.'), '');
            else {
                const value = card_type_id % 14;
                if (value == 6 && this.gamedatas.ender_8 > 0) {
                    card_div.textContent = '🛑';
                    this.addTooltip(card_div.id, _('Playing one or more 8s ends the trick immediately.'), '');
                } else if (value == 7 && this.gamedatas.reversing_9 > 0) {
                    card_div.textContent = '🔄';
                    this.addTooltip(card_div.id, _('Playing one or more 9s reverses the turn order permanently.'), '');
                } else if (value == 9 && this.gamedatas.jack_back > 0) {
                    this.addTooltip(card_div.id, _('Playing one or more Jacks reverses card ranks or cancels the rank reversal during the same trick.'), '');
                    card_div.textContent = '✊';
                }
            }
        },

        updateSpecialStatus: function() {
            document.getElementById('reverse_text').textContent = this.reversed ? '🔄' : '';
            document.getElementById('lock_text').textContent = this.suit_lock ? '🔒' : '';
            if (this.regular_revolution) {
                document.getElementById('revolution_text').style.color = this.temporary_revolution ? 'darkorange' : 'red';
                document.getElementById('revolution_text').textContent = this.temporary_revolution ? _('Revolution Paused') : _('Revolution');
            } else if (this.temporary_revolution) {
                document.getElementById('revolution_text').style.color = 'darkorange';
                document.getElementById('revolution_text').textContent = _('Temporary Revolution');
            } else document.getElementById('revolution_text').textContent = '';
            document.getElementById('status_bar').style.display = !this.reversed && !this.suit_lock && !this.regular_revolution && !this.temporary_revolution ? 'none' : 'unset';
        },

        roleTextChange: function (player_id, role) {
            const player_count = Object.keys(this.gamedatas.players).length;
            const rank_text = document.getElementById('rankname_' + player_id);
            if (role == 1) {
                rank_text.textContent = player_count > 3 ? '👑' : '🎩';
                rank_text.style.display = 'unset';
                this.addTooltip('rankname_' + player_id, player_count > 3 ? _('President') : _('Minister'), '');
            } else if (role == player_count) {
                rank_text.textContent = player_count > 3 ? '💸' : '⛏️';
                rank_text.style.display = 'unset';
                this.addTooltip('rankname_' + player_id, player_count > 3 ? _('Beggar') : _('Peasant'), '');
            } else if (role == 2 && player_count > 3) {
                rank_text.textContent = '🎩';
                rank_text.style.display = 'unset';
                this.addTooltip('rankname_' + player_id, _('Minister'), '');
            } else if (role == player_count - 1 && player_count > 3) {
                rank_text.textContent = '⛏️';
                rank_text.style.display = 'unset';
                this.addTooltip('rankname_' + player_id, _('Peasant'), '');
            } else {
                rank_text.textContent = '';
                rank_text.style.display = 'none';
                this.removeTooltip('rankname_' + player_id);
            }
        },

        coordCalculate: function (i, count, type) {
            let left = 0;
            let top = 0;
            const width = 200;
            const height = 160;
            switch (Number(type)) {
                case 0:
                    left = (width - this.cardwidth) / (count == 1 ? 2 : ((count - 1) / i));
                    top = (height - this.cardheight) / (count == 1 ? 2 : ((count - 1) / i));
                    if (count == 2) left += 36 * (i == 0 ? 1 : -1);
                    break;
                case 1:
                    left = (width - this.cardwidth) / (count - 1) * i;
                    top = (height - this.cardheight) / 2;
                    break;
            }
            return [left, top];
        },

        ///////////////////////////////////////////////////
        //// Player's action
        
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */

        onBtnGiveCard: function () {
            let action = "giveCard";
            if (!this.checkAction(action)) return;

            const selected_cards = this.playerHand.getSelectedItems();
            if (selected_cards.length === 0) {
                this.showMessage(_('Please select cards'), "error");
                return;
            } else if ((this.gamedatas.gamestate.name === 'presidentGive' && selected_cards.length !== 2) || (this.gamedatas.gamestate.name === 'ministerGive' && selected_cards.length !== 1)) {
                this.showMessage(_('Please select correct number of cards'), "error");
                return;
            }

            let card_ids = '';
            selected_cards.forEach(c => {card_ids = card_ids === '' ? c.id : card_ids + ';' + c.id});
            this.ajaxcall("/" + this.game_name + "/" +  this.game_name + "/" + action + ".html", {lock: true, card_ids: card_ids}, this, function (result) {}, function (is_error) {});
        },

        onBtnPlayCard: function () {
            let action = "playCard";
            if (!this.checkAction(action)) return;

            const selected_cards = this.playerHand.getSelectedItems();
            if (selected_cards.length === 0) {
                this.showMessage(_('Please select cards'), "error");
                return;
            }

            let illegal_warning = false;
            let illegal_sure = false;
            if (this.gamedatas.illegal_finish > 0 && this.playerHand.count() > 1) {
                let total_legal = 0;
                let total_illegal = 0;
                let true_illegal = 0;
                const all_cards = this.playerHand.getAllItems();
                for (let i in all_cards) {
                    const card = all_cards[i];
                    if (card.type == 99) {
                        total_illegal++;
                        true_illegal++;
                    } else if (this.gamedatas.ender_8 > 0 && card.type % 14 == 6) {
                        total_illegal++;
                        true_illegal++;
                    } else if (card.type % 14 == 1) {
                        if (this.regular_revolution) total_illegal++;
                        else total_legal++;
                        if (this.regular_revolution ^ this.temporary_revolution) true_illegal++;
                    } else if (card.type % 14 == 13) {
                        if (!this.regular_revolution) total_illegal++;
                        else total_legal++;
                        if (!(this.regular_revolution ^ this.temporary_revolution)) true_illegal++;
                    }
                    else total_legal++;
                }
                if (total_legal > 0 && total_illegal > 0) {
                    let selected_legal = 0;
                    let selected_illegal = 0;
                    for (let i in selected_cards) {
                        const card = selected_cards[i];
                        if (card.type == 99) selected_illegal++;
                        else if (this.gamedatas.ender_8 > 0 && card.type % 14 == 6) selected_illegal++;
                        else if (this.regular_revolution && card.type % 14 == 1) selected_illegal++;
                        else if (!this.regular_revolution && card.type % 14 == 13) selected_illegal++;
                        else selected_legal++;
                    }
                    illegal_warning = selected_legal >= total_legal;
                }

                if (total_legal > 0 && total_illegal > 0 && this.playerHand.count() == selected_cards.length) {
                    illegal_warning = true;
                    illegal_sure = true;
                }
            }

            let card_ids = '';
            selected_cards.forEach(c => {card_ids = card_ids === '' ? c.id : card_ids + ';' + c.id});
            if (illegal_warning) this.confirmationDialog(illegal_sure ? _("You will be disqualified by illegal finish rule!") : _("Leaving only highest cards may lead to disqualification!"), () => this.ajaxcall("/" + this.game_name + "/" +  this.game_name + "/" + action + ".html", {lock: true, card_ids: card_ids}, this, function (result) {}, function (is_error) {}));
            else this.ajaxcall("/" + this.game_name + "/" +  this.game_name + "/" + action + ".html", {lock: true, card_ids: card_ids}, this, function (result) {}, function (is_error) {});
        },

        onBtnResetSelection: function () {this.playerHand.unselectAll();},
        onBtnPassTurn: function () {
            const action = "passTurn";
            if (!this.checkAction(action)) return;
            
            const selected_cards = this.playerHand.getSelectedItems();
            if (this.prefs[103].value == 1 && this.gamedatas.permanent_pass > 0 && this.pass_warning) this.confirmationDialog(_("You cannot play cards in this trick again!<br>(Change preference option to deactivate this warning)"), () => this.ajaxcall("/" + this.game_name + "/" +  this.game_name + "/" + action + ".html", {lock: true}, this, function (result) {}, function (is_error) {}));
            else this.ajaxcall("/" + this.game_name + "/" +  this.game_name + "/" + action + ".html", {lock: true}, this, function (result) {}, function (is_error) {});
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
            const notif_list = ['newRound', 'newHand', 'privateExchange', 'giveCard', 'playCard', 'goOut', 'passTurn', 'endTrick', 'scoreChange', 'noSound'];
            notif_list.forEach(s => dojo.subscribe(s, this, 'notif_' + s));
            this.notifqueue.setSynchronous('newRound', 1000);
            this.notifqueue.setSynchronous('playCard', 500);
            this.notifqueue.setSynchronous('endTrick', 500);
        },

        notif_newRound: function (notif) {
            this.regular_revolution = false;
            this.temporary_revolution = false;
            this.suit_lock = false;
            this.updateSpecialStatus();
            document.querySelectorAll('#played_card_container > .card').forEach(e => this.fadeOutAndDestroy(e.id, 250));
            document.querySelectorAll('.passed').forEach(e => e.classList.remove('passed'));
            document.querySelectorAll('.went_out').forEach(e => e.classList.remove('went_out'));

            for (let player_id in this.gamedatas.players) {
                if (player_id != this.player_id) {
                    document.getElementById('rank_' + player_id).textContent = '';
                    this.hand_counters[player_id].setValue(notif.args.hand_count[player_id]);
                } else document.getElementById('myhand_rank').textContent = '';
                this.roleTextChange(player_id, notif.args.rank_list[player_id]);
            }

            if (this.prefs[102].value == 1 && !g_archive_mode) {
                this.disableNextMoveSound();
                playSound(this.game_name + '_shuffle');
            }
        },

        notif_newHand: function (notif) {
            this.regular_revolution = false;
            this.temporary_revolution = false;
            this.playerHand.removeAll();
            this.playerHand.duration = 0;
            if (document.getElementById('order_by_value').style.display == 'none') this.onReorderByValue(null);
            else this.onReorderBySuit(null);
            this.playerHand.duration = 1000;
            for (let i in notif.args.cards) {
                let card = notif.args.cards[i];
                let color = Number(card.type);
                let value = Number(card.type_arg);
                this.playerHand.addToStockWithId(this.getCardUniqueId(color, value), card.id);
            }
            if (this.prefs[102].value == 1 && !g_archive_mode) this.disableNextMoveSound();
        },

        notif_privateExchange: function (notif) {
            for (let i in notif.args.cards_given) this.playerHand.removeFromStockById(notif.args.cards_given[i].id, 'playertablecard_' + notif.args.player_id);
            for (let i in notif.args.cards_received) {
                const card = notif.args.cards_received[i];
                const color = card.type;
                const value = card.type_arg;
                this.playerHand.addToStockWithId(this.getCardUniqueId(color, value), card.id, 'playertablecard_' + notif.args.player_id);
            }
            if (this.prefs[102].value == 1 && !g_archive_mode) this.disableNextMoveSound();
        },
        notif_giveCard: function (notif) {
            if (this.player_id != notif.args.player_id && this.player_id != notif.args.other_player_id) {
                for (let i = 0; i < notif.args.card_count; i++) {
                    dojo.place(this.format_block('jstpl_card', {
                        id: 'give_' + notif.args.player_id + '_' + i,
                        x: 0,
                        y: 2,
                        card_style: this.prefs[100].value + ' card_back',
                    }), 'playertablecard_' + notif.args.other_player_id);
                    this.placeOnObjectPos('card_give_' + notif.args.player_id + '_' + i, 'playertablecard_' + notif.args.player_id, (i - (notif.args.card_count - 1) / 2) * 40, 0);
                    this.slideToObjectAndDestroy('card_give_' + notif.args.player_id + '_' + i, 'playertablecard_' + notif.args.other_player_id, 500);
                    dojo.place(this.format_block('jstpl_card', {
                        id: 'give_' + notif.args.other_player_id + '_' + i,
                        x: 0,
                        y: 2,
                        card_style: this.prefs[100].value + ' card_back',
                    }), 'playertablecard_' + notif.args.player_id);
                    this.placeOnObjectPos('card_give_' + notif.args.other_player_id + '_' + i, 'playertablecard_' + notif.args.other_player_id, (i - (notif.args.card_count - 1) / 2) * 40, 0);
                    this.slideToObjectAndDestroy('card_give_' + notif.args.other_player_id + '_' + i, 'playertablecard_' + notif.args.player_id, 500);
                }
            }

            if (this.prefs[102].value == 1 && !g_archive_mode) {
                this.disableNextMoveSound();
                playSound(this.game_name + '_give');
            }
        },

        
        notif_playCard: function (notif) {
            document.querySelectorAll('#played_card_container > .card').forEach(e => this.fadeOutAndDestroy(e.id, 250));
            if (this.gamedatas.permanent_pass <= 0) document.querySelectorAll('.passed').forEach(e => e.classList.remove('passed'));

            const card_count = Object.keys(notif.args.combination.card_list).length;
            const combination_type = notif.args.combination.type;
            let found_jack = false;
            let found_9 = false;
            if (this.player_id == notif.args.player_id) {
                for (let i in notif.args.combination.card_list) {
                    const card = notif.args.combination.card_list[i];
                    const color = Number(card.type);
                    const value = Number(card.type_arg);
                    const coord = this.coordCalculate(i, card_count, combination_type);
                    if (value == 9) found_jack = true;
                    if (value == 7) found_9 = true;
                    dojo.place(this.format_block('jstpl_card', {
                        id: card.id,
                        x: color == 4 ? 0 : value,
                        y: color == 4 ? 0 : color,
                        card_style: this.prefs[100].value,
                    }), 'played_card_container');
                    this.placeOnObject('card_' + card.id, 'myhand_item_' + card.id);
                    this.slideToObjectPos('card_' + card.id, 'played_card_container', coord[0], coord[1]).play();
                    this.playerHand.removeFromStockById(card.id);
                }
            } else {
                for (let i in notif.args.combination.card_list) {
                    const card = notif.args.combination.card_list[i];
                    const color = Number(card.type);
                    const value = Number(card.type_arg);
                    const coord = this.coordCalculate(i, card_count, combination_type);
                    if (value == 9) found_jack = true;
                    if (value == 7) found_9 = true;
                    dojo.place(this.format_block('jstpl_card', {
                        id: card.id,
                        x: color == 4 ? 0 : value,
                        y: color == 4 ? 0 : color,
                        card_style: this.prefs[100].value,
                    }), 'played_card_container');
                    this.placeOnObjectPos('card_' + card.id, 'playertable_' + notif.args.player_id, coord[0], coord[1]);
                    this.slideToObjectPos('card_' + card.id, 'played_card_container', coord[0], coord[1]).play();
                }
                this.hand_counters[notif.args.player_id].incValue(-card_count);
            }

            let update_status = false;
            if (notif.args.suit_lock) {
                this.suit_lock = true;
                update_status = true;
            }
            const current_revolution_status = this.regular_revolution ^ this.temporary_revolution;
            if (this.gamedatas.revolution > 0 && card_count >= 4) {
                this.regular_revolution = !this.regular_revolution;
                update_status = true;
            }
            if (this.gamedatas.jack_back > 0 && found_jack) {
                this.temporary_revolution = !this.temporary_revolution;
                update_status = true;
            }
            if (this.gamedatas.reversing_9 > 0 && found_9) {
                this.reversed = !this.reversed;
                update_status = true;
                this.gamedatas.playerorder = this.gamedatas.playerorder.reverse();
                this.gamedatas.playerorder.unshift(this.gamedatas.playerorder.pop());
                this.updatePlayerOrdering();
            }
            if (update_status) this.updateSpecialStatus();
            if (current_revolution_status != this.regular_revolution ^ this.temporary_revolution) {
                if (document.getElementById('order_by_value').style.display == 'none') this.onReorderByValue(null);
                else this.onReorderBySuit(null);
            }

            if (this.prefs[102].value == 1 && !g_archive_mode) {
                this.disableNextMoveSound();
                playSound(this.game_name + '_play');
            }
        },

        notif_goOut: function (notif) {
            if (notif.args.player_id != this.player_id) {
                document.getElementById('playertablecard_' + notif.args.player_id).classList.add('card_' + this.prefs[100].value);
                this.roleTextChange(notif.args.player_id, 0);
                this.hand_counters[notif.args.player_id].setValue(0);
                document.getElementById('playertable_' + notif.args.player_id).classList.add('went_out');
                document.getElementById('playertablecard_' + notif.args.player_id).classList.add('went_out');
                document.getElementById('hand_' + notif.args.player_id).classList.add('went_out');
                document.getElementById('rank_' + notif.args.player_id).classList.add('went_out');
                if (notif.args.rank == 70) document.getElementById('rank_' + notif.args.player_id).textContent = '💥';
                else if (notif.args.rank >= 71) document.getElementById('rank_' + notif.args.player_id).textContent = '🚫';
                else document.getElementById('rank_' + notif.args.player_id).textContent = notif.args.rank;
            } else {
                this.playerHand.removeAll();
                this.roleTextChange(notif.args.player_id, 0);
                document.getElementById('myhand_wrap').classList.add('went_out');
                if (notif.args.rank == 70) document.getElementById('myhand_rank').textContent = '💥';
                else if (notif.args.rank >= 71) document.getElementById('myhand_rank').textContent = '🚫';
                else document.getElementById('myhand_rank').textContent = notif.args.rank;
            }

            if (this.prefs[102].value == 1 && !g_archive_mode) this.disableNextMoveSound();
        },

        notif_passTurn: function (notif) {
            document.getElementById(notif.args.player_id == this.player_id ? 'myhand_wrap' : ('playertable_' + notif.args.player_id)).classList.add('passed');
            if (notif.args.player_id == this.player_id) this.pass_warning = false;
            if (this.prefs[102].value == 1 && !g_archive_mode) this.disableNextMoveSound();
        },

        notif_endTrick: function (notif) {
            document.querySelectorAll('#played_card_container > .card').forEach(e => this.fadeOutAndDestroy(e.id, 500));
            document.querySelectorAll('.passed').forEach(e => e.classList.remove('passed'));

            let update_status = false;
            if (this.suit_lock) {
                this.suit_lock = false;
                update_status = true;
            }
            if (this.temporary_revolution) {
                this.temporary_revolution = false;
                update_status = true;
                if (document.getElementById('order_by_value').style.display == 'none') this.onReorderByValue(null);
                else this.onReorderBySuit(null);
            }
            if (update_status) this.updateSpecialStatus();

            if (this.prefs[102].value == 1 && !g_archive_mode) this.disableNextMoveSound();
        },

        notif_scoreChange: function (notif) {
            this.scoreCtrl[notif.args.player_id].incValue(notif.args.nb);
            if (this.prefs[102].value == 1 && !g_archive_mode) this.disableNextMoveSound();
        },

        notif_noSound: function (notif) {if (this.prefs[102].value == 1 && !g_archive_mode) this.disableNextMoveSound();},
   });             
});
