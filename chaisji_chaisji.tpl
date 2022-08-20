{OVERALL_GAME_HEADER}

<div id="mboard_space" class="anchor">
	<div id="market_area" class="board_area">
		<h3>Market</h3> 
		<div id="market_board" class="shadow">
			<div id="row_1" class="market_row">
			</div>
			<div id="row_2" class="market_row">
			</div>
			<div id="row_3" class="market_row">
			</div>
		</div>
	</div>

	<div id="pantry_area" class="board_area">
		<h3>Pantry</h3>
		<div id="pantry_board" class="shadow">
			<div id="spot_0" class="spot">
			</div>
			<div id="spot_1" class="spot">
			</div>
			<div id="spot_2" class="spot">
			</div>
			<div id="spot_3" class="spot">
			</div>
			<div id="spot_4" class="spot">
			</div>
		</div>
	</div>
    
    <div id="ability_area" class="board_area">
    </div>
</div>

<div id="blank_space" class="anchor">
</div>

<div id="plaza_area" class="anchor">
</div>

<div id="tip_area" class="anchor">
</div>

<div id="blank_space2" class="anchor">
</div>

<div id="pboard_space" class="anchor">
	<!-- BEGIN player_board -->
		<div id="pboard_full_{COLOR}" class="pboard_full">
			<div class="nameslot">
				<h3 style="color: #{COLOR}">{PLAYER_NAME}</h3>
			</div>
			<div id="pboard_{COLOR}" class="pboard pboard_{COLOR} shadow">
			</div>
			<div id="pcards_{COLOR}">
			</div>
		</div>
	<!-- END player_board -->
</div>

<script type="text/javascript">
    // Javascript HTML templates
    var jstpl_token = '<div class="${classes}" id="${id}"></div>';
</script>  

{OVERALL_GAME_FOOTER}
