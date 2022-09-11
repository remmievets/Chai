{OVERALL_GAME_HEADER}

<div id="gameboard">
<div id="mboard_space" class="anchor">
	<div id="market_area" class="board_area">
		<h3>Market</h3> 
		<div id="market_board" class="shadow">
			<div id="market_1" class="market_row">
			</div>
			<div id="market_2" class="market_row">
			</div>
			<div id="market_3" class="market_row">
			</div>
		</div>
	</div>

	<div id="pantry_area" class="board_area">
		<h3>Pantry</h3>
		<div id="pantry_board" class="shadow">
			<div id="spot_1" class="spot">
			</div>
			<div id="spot_2" class="spot">
			</div>
			<div id="spot_3" class="spot">
			</div>
			<div id="spot_4" class="spot">
			</div>
			<div id="spot_5" class="spot">
			</div>
		</div>
	</div>

	<div id="ability_area" class="board_area">
		<h3>Abilities</h3>
	</div>
</div>

<div id="blank_space" class="anchor">
</div>

<div id="plaza_area" class="anchor center_area">
	<h3>Plaza</h3>
</div>

<div id="tip_area" class="anchor center_area">
	<h3>Tips</h3>
</div>

<div id="blank_space2" class="anchor">
</div>

<div id="pboard_space" class="anchor">
	<!-- BEGIN player_board -->
		<div id="pboard_full_{COLOR}" class="anchor pboard_full">
			<div id="name_{COLOR}">
				<h2 style="color: #{COLOR}">{PLAYER_NAME}</h2>
			</div>
			<div id="pboard_{COLOR}" class="pboard pboard_{COLOR} shadow">
				<div id="pflavor_{COLOR}" class="pflavor">
				</div>
				<div id="padditives_{COLOR}" class="padditives">
				</div>
				<div id="ptea_{COLOR}" class="ptea">
				</div>
			</div>
			<div id="pcards_{COLOR}" class="anchor">
			</div>
		</div>
		<!-- player panel -->
		<div id="playerpanel_{COLOR}" class="playerpanel">
			<div>
				<div id="counter_lemon_{COLOR}" class="counter ppflavor flavor_lemon shadow textoverlay">0</div>
				<div id="counter_mint_{COLOR}" class="counter ppflavor flavor_mint shadow textoverlay">0</div>
				<div id="counter_berries_{COLOR}" class="counter ppflavor flavor_berries shadow textoverlay">0</div>
				<div id="counter_jasmine_{COLOR}" class="counter ppflavor flavor_jasmine shadow textoverlay">0</div>
				<div id="counter_lavender_{COLOR}" class="counter ppflavor flavor_lavender shadow textoverlay">0</div>
				<div id="counter_ginger_{COLOR}" class="counter ppflavor flavor_ginger shadow textoverlay">0</div>
				<div id="counter_wild_{COLOR}" class="counter ppflavor flavor_wild shadow textoverlay">0</div>
				<div id="counter_chai_{COLOR}" class="counter ppadditive additive_chai textoverlay">0</div>
				<div id="counter_vanilla_{COLOR}" class="counter ppadditive additive_vanilla textoverlay">0</div>
				<div id="counter_milk_{COLOR}" class="counter ppadditive additive_milk textoverlay">0</div>
				<div id="counter_sugar_{COLOR}" class="counter ppadditive additive_sugar textoverlay">0</div>
				<div id="counter_honey_{COLOR}" class="counter ppadditive additive_honey textoverlay">0</div>
				<div id="counter_awild_{COLOR}" class="counter ppadditive additive_awild textoverlay">0</div>
				<div id="counter_tea_{COLOR}" class="counter tea_{COLOR} textoverlay">0</div>
			</div>
		</div>
	<!-- END player_board -->
</div>
</div>

<script type="text/javascript">
	// Javascript HTML templates
	var jstpl_token = '<div class="${classes}" id="${id}"></div>';
</script>  

{OVERALL_GAME_FOOTER}
