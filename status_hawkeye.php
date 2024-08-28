<html>
	<head>
		<script src="js/jquery/jquery-1.9.1.min.js"></script>
		<style>
			p {
				font-family: Tahoma, Geneva, sans-serif;
			}
			table {
				border-collapse: collapse;
			}
			th, td {
				border: 1px solid black;
				padding: 10px;
				text-align: right;
			}
			tr.item, tr.radarItem {
				cursor:pointer;
			}
		</style>
	</head>
<body>

<div id="status_div">
</div>

</body>
</html>

<script>
$(document).ready(function() {	
	$('body').on('click', '.item', function() {
		if ($(this).find('span').text() == '(-)') {
			$(this).nextUntil('tr.header').slideUp(50, function(){
			});
		} else {
			$(this).nextUntil('tr.header').slideDown(50, function(){
			});
		}
		$(this).find('span').text(function(_, value){return value=='(-)'?'(+)':'(-)'});
		
		//console.log($(this).find('span').attr('id'));
	});
	
	$('body').on('click', '.radarItem', function() {
		if ($(this).find('span').text() == '(-)') {
			$(this).nextUntil('tr.last').slideUp(50, function(){
			});
		} else {
			$(this).nextUntil('tr.last').slideDown(50, function(){
			});
		}
		$(this).find('span').text(function(_, value){return value=='(-)'?'(+)':'(-)'});		
	});
	RefreshStatus();
});

function GetTimestampInfo(timestampString) {
	var dt = new Date(timestampString);
	var seconds = (Date.now() - dt) / 1000;
	return dt.toLocaleString() + ' (' + seconds.toFixed(3) + 's ago)';
}

function RefreshStatus() {	
	$.getJSON("http://localhost:8800/api/status", {_: new Date().getTime()}, function(data){
		var output_data = '';		
		$.each(data, function(k, s){
			$.each(s.radars, function(radarKey, radar){
				var itemId = 'r' + radar.id;
				output_data += '<p><table id="radar' + radar.id + '">';
				output_data += '<tr bgcolor="#B5F6FF" class="header"><th>Radar</th><th>Name</th><th>IP</th><th>Status</th><th>Timestamp</th><th></th></tr>';
				output_data += '<tr class="radarItem">';
				output_data += '<td><span>(-)</span> ' + radar.id + '</td>';
				output_data += '<td>' + radar.name + '</td>';
				output_data += '<td>' + radar.ip + '</td>';
				output_data += '<td>' + radar.status + '</td>';
				output_data += '<td>' + GetTimestampInfo(radar.timestamp) + '</td>';
				output_data += '<td></td>';
				output_data += '</tr>';
				
				$.each(radar.lanes, function(laneKey, lane){
					output_data += '<tr bgcolor="#D3D396" class="header"><th bgcolor="#FFFFFF"></th><th>Lane ID</th><th>Name</th><th>Phase</th><th>Status</th><th></th></tr>';
					output_data += '<tr class="item">';
					output_data += '<td></td>';
					output_data += '<td><span>(-)</span> ' + lane.id + '</td>';
					output_data += '<td>' + lane.name + '</td>';
					output_data += '<td>' + lane.phase + '</td>';
					output_data += '<td>' + lane.status + '</td>';
					output_data += '<td></td>';
					output_data += '</tr>';
					
					output_data += '<tr bgcolor="#CBFD9C"><th bgcolor="#FFFFFF"></th><th bgcolor="#FFFFFF"></th><th>Zone ID</th><th>Type</th><th>Last Input</th><th>Last Output</th></tr>';
					$.each(lane.zones, function(zoneKey, zone){
						output_data += '<tr>';
						output_data += '<td></td>';
						output_data += '<td></td>';
						output_data += '<td>' + zone.id + '</td>';
						output_data += '<td>' + zone.zoneType + '</td>';
						output_data += '<td>' + GetTimestampInfo(zone.lastInput) + '</td>';
						output_data += '<td>' + GetTimestampInfo(zone.lastOutput) + '</td>';
						output_data += '</tr>';
					});
				});
				
				output_data += '</table></p>';
			});
		});
		
		$('#status_div').html(output_data);
	});
	
	setTimeout(RefreshStatus, 5000);
};

</script>