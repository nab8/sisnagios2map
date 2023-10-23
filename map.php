<?php
require_once('config/config.php');
require_once('assets/sisnagios2map.php'); 
if( isset($_GET['view']) ){
	$map_default_view = $_GET['view'];
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>SIS/Nagios Map</title>
	<!-- Overlapping Markers Fix -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/OverlappingMarkerSpiderfier/1.0.3/oms.min.js"></script>
	<link rel="stylesheet" href="assets/map.css" type="text/css">
	<script src="assets/map.js"></script>
</head>
<body>
	<script>
		<?php echo createMarkers($map_default_view); ?>
	</script>
	<div id="map"></div>
	<script>
  		// Create Initial Map
		function initMap() {
    		// Create basic map, greedy = allowing mouse whell zoom control
			var map_options =  {
				zoom: <?php echo $maps_zoom; ?>,
				center: {lat: <?php echo $maps_latitude; ?>, lng: <?php echo $maps_longitude; ?>},
				gestureHandling: 'greedy'
			};
    		// Bind to map_host and map_service divs
			const map_host = new google.maps.Map(document.getElementById("map"), map_options);
    		// Create spider spreading of markers on both maps
			var oms_host = new OverlappingMarkerSpiderfier(map_host, {
				markersWontMove: true,
				markersWontHide: true,
				basicFormatEvents: true
			});
    		// Create markers on both maps
			setMarkers(map_host, oms_host, stationList, 3);
		}
	</script>
	<script async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo $maps_api; ?>&callback=initMap"></script>
</body>
</html>
