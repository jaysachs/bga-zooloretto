{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- Zooloretto implementation : © <Your name here> <Your email address here>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    zooloretto_zooloretto.tpl
    
    This is the HTML template of your game.
    
    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.
    
    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format
    
    See your "view" PHP file to check how to set variables and control blocks
    
    Please REMOVE this comment before publishing your game on BGA
-->


<div class="container1" id = "container1">
	<div class="container2" id = "container2">
		<div class="wagons" id = "wagons"></div>
		<div class="tiles" id = "tiles"></div>
		<div class="tilesdeck" id = "tilesdeck"></div>
	</div>

	<div class="container3" id = "container3">
		<div id="board" class="board">
			<!-- BEGIN cell -->
				<div id="cell_{PNO}_{X}_{Y}" class="cell" style="left: {LEFT}%; top: {TOP}%;"></div>
			<!-- END cell -->
			<div id="stall" class="stall" style="left: 20%; top: 82%;"></div>
		</div>

		<div id="leftpanel" class="leftpanel">
			<div id="playerscards" class="playerscards">
				<!-- BEGIN playercards -->
					<div id="playercards_{X}" class="playercards whiteblock" style="left: {LEFT}%; top: {TOP}%;">
						<div id ="playername_{X}" class="playernameclass"></div>
						<div id="board_{X}" class="board">
						<!-- BEGIN cell2 -->
							<div id="cell_{PNO}_{X}_{Y}" class="cell" style="left: {LEFT}%; top: {TOP}%;"></div>
						<!-- END cell2 -->
						<div id="stall_{X}" class="stall" style="left: {A}%; top: {B}%;"></div>
						</div>
					</div>
				<!-- END playercards -->
			</div>
		</div>
	</div>

	<div class="playeraid" id = "playeraid">
	</div>
	
</div>


<script type="text/javascript">

	var jstpl_player_number='<div class="plnomoney"><div class="player_number" id="player_number_${player_number}">${player_number_text}</div><div id="money_${player_number}" class="plnomoney2"></div></div>';
	var jstpl_wagon3='<div class="wagon wagon${wagon_size}" id="wagon_${id}"><div class="cellwagon3" id="wagon_${id}_1" style="left:5%; top:14%"></div><div class="cellwagon3" id="wagon_${id}_2" style="left:36%; top:14%"></div><div class="cellwagon3" id="wagon_${id}_3" style="left:67%; top:14%"></div></div>';
	var jstpl_wagon2='<div class="wagon wagon${wagon_size}" id="wagon_${id}"><div class="cellwagon2" id="wagon_${id}_1" style="left:5%; top:14%"></div><div class="cellwagon2" id="wagon_${id}_2" style="left:54%; top:14%"></div></div>';
	var jstpl_wagon1='<div class="wagon wagon${wagon_size}" id="wagon_${id}"><div class="cellwagon1" id="wagon_${id}_1" style="left:5%; top:14%"></div></div>';
	var jstpl_money='<div class="money money${player_no}" id="money_instance_${player_no}_${id}"></div>';
	var jstpl_tile='<div class="tile tile${val}" id="tile_${player_no}_${id}_${val}_${x}_${y}"></div>';
	var jstpl_disk='<div class="disk" id="disk"></div>';
	var jstpl_back='<div class="back" id="backtile_${id}"></div>';
	var jstpl_back2='<div class="back" id="backtile2_${id}"></div>';
	var jstpl_lastround='<div class="head_info" id="zooloretto_last_round_1" style="height: auto; overflow: hidden"><div class="head_infomsg_close" id="zooloretto_last_round_2"><i class="fa fa-close" aria-hidden="true"></i></div><div class="head_infomsg_item">${message}</div></div>';
	var jstpl_tilesleft='<div class="tilesleft" id="tilesleft">${val}</div>';
</script>  

{OVERALL_GAME_FOOTER}
