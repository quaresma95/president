{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- Fixes and variants implementation: © ufm <tel2tale@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------
-->
<div id="variant_wrap" class="whiteblock" style="text-align:center;">
    <b>{REVOLUTION}{GAP_1}{JOKER}{GAP_2}{FIRST_PLAYER_MODE}{GAP_3}{SAME_RANK_SKIP}{GAP_4}{UNSKIPPABLE_23}{GAP_5}{SEQUENCE}{GAP_6}{SUIT_LOCK}{GAP_7}{ENDER_8}{GAP_8}{REVERSING_9}{GAP_9}{JACK_BACK}{GAP_10}{ILLEGAL_FINISH}{GAP_11}{DOWNFALL}{GAP_12}{PERMANENT_PASS}</b>
</div>
<div id="game_board" class="{SPECTATOR} {GAME_BOARD_WIDTH}">
    <div class="whiteblock" id="played_card">
        <div id="played_card_container"></div>
        <div class="playertablename" id="status_bar"><span id="lock_text"></span> <span id="reverse_text"></span> <span id="revolution_text"></span></div>
    </div>
    <!-- BEGIN player -->
    <div class="playertable whiteblock playertable_{DIR}" id="playertable_{PLAYER_ID}">
        <div class="playertablename" style="color:#{PLAYER_COLOR}"><span id="rankname_{PLAYER_ID}" style="text-shadow: none;"></span> {PLAYER_NAME}</div>
        <div class="cardspace hand_card card card_back" id="playertablecard_{PLAYER_ID}">
            <div class="cardspace hand_count" id="hand_{PLAYER_ID}"></div>
            <div class="cardspace rank_count" id="rank_{PLAYER_ID}"></div>
        </div>
    </div>
    <!-- END player -->
</div>

<div id="myhand_wrap" class="whiteblock">
    <div id="myhand_rank"></div>
    <b><span id="rankname_{THIS_PLAYER_ID}" style="text-shadow: none;"></span> {MY_HAND}</b>
    <a href="#" class="reordercards" id="order_by_value" style="display:none;">[{REORDER_CARDS_BY_VALUE}]</a>
    <a href="#" class="reordercards" id="order_by_suit">[{REORDER_CARDS_BY_SUIT}]</a>
    <div id="myhand"><div id="myhand_placeholder"></div></div>
</div>

<!-- BEGIN audio_list -->
<audio id="audiosrc_{GAME_NAME}_{AUDIO}" src="{GAMETHEMEURL}img/{GAME_NAME}_{AUDIO}.mp3" preload="none" autobuffer></audio>
<audio id="audiosrc_o_{GAME_NAME}_{AUDIO}" src="{GAMETHEMEURL}img/{GAME_NAME}_{AUDIO}.ogg" preload="none" autobuffer></audio>
<!-- END audio_list -->

<script type="text/javascript">
var jstpl_card = '<div class="cardspace card card_${card_style}" id="card_${id}" style="background-position: -${x}00% -${y}00%"></div>';
</script>  

{OVERALL_GAME_FOOTER}