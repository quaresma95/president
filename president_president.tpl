{OVERALL_GAME_HEADER}


<div id="player_table" class="player_table_{NB_PLAYER}">
    <div id="revolution_box" class="revolution qHidden"></div>

    <!-- BEGIN player -->
    <div class="player_card card_place_{NB_PLAYER}_{PLACE_ID}">
        <p class="player_name whiteblock" title="{PLAYER_NAME}" style="color:#{PLAYER_COLOR}">
            {PLAYER_NAME}
        </p>
        <div id="player_cards_{PLAYER_ID}" class="card_places">
        </div>
    </div>
    <!-- END player -->

    <div id="table">
        <div class="tableCard" id="tableCard"></div>
    </div>
</div>


<div id="myhand_wrap" class="whiteblock">
    <h3 style="padding: 5px">{MY_HAND}</h3>
    <div id="myhand">
    </div>
</div>

<script type="text/javascript">

    // Javascript HTML templates
    var jstpl_plays = '<div class="cardsOnTable" id="play_${play_id}"></div>';
    var jstpl_cardontable = '<div class="cardOnTable" id="cardontable_${card_id}" style="background-position:-${x}px -${y}px; margin:${margin}"></div>';
    var jstpl_counterHand = '<div class="cardIcon" id="playerCardCount_p${id}" style="width: 32px; right:29%; bottom:30%;">x ${nbCards}</div>';
    var jstpl_role = '<div class="cardIcon" id="playerCardRole_p${id}" style="right:30%; bottom:0%;"><span style="margin-right: 0px;" class="${roleClass}"></span></div>';
    var jstpl_player_board = '<div class="cards_board" id="cards_board_p${id}" style="padding: 3px">' +
    '<div class="icon16 icon16_hand cards_count"></div><span class="qIcon" id="card_count_p${id}">${count}</span>' +
    '<div id="playing_icon_p${id}" class="icon20 icon20_want_to_play qIcon playingClass ${playingClass}"></div>' +
    '<div id="pass_icon_p${id}" class="icon20 icon20_know_game qIcon passClass ${passClass}"></div>' +
    '<div id="role_icon_p${id}" class="${roleClass}"></div></div>';
</script>

{OVERALL_GAME_FOOTER}
