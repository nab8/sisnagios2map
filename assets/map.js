var markers_host = [];

// Set the initally active window to null since we don't have one
activeWindow = null;
function setMarkers(map, oms, markers) {
  // Process the markers array
  for (let i = 0; i < markers.length; i++) {
    const marker = markers[i];
    // Create unique popup window names based on site name
    window[marker[0]+"_window"] = new google.maps.InfoWindow({
      content: marker[4],
    });
    // Create unique marker names based on site name
    window[marker[0]+"_marker"] = new google.maps.Marker({
      // Pull fields from markers as needed
      position: { lat: marker[1], lng: marker[2] },
      map,
      title: marker[0],
      type: "host",
      icon: {
        url: "//maps.google.com/mapfiles/ms/icons/"+marker[3],
        scaledSize: new google.maps.Size(50, 50),
        labelOrigin: new google.maps.Point(25, 15),
      },
      scale: 2,
      label: {
        text: marker[0],
        fontSize: '9px',
        fontWeight: 'bold',
     },            
    });
    // Create listener for click events 
    window[marker[0]+"_marker"].addListener("spider_click", () => {
      // If the clicked window is not our active one close the last active window first
      if(activeWindow != null) activeWindow.close(); 
      // Open the window
      window[marker[0]+"_window"].open(map, window[marker[0]+"_marker"]);
      // Sett the new active window
      activeWindow = window[marker[0]+"_window"];
    });
    // Add to global markers list
    markers_host.push(window[marker[0]+"_marker"]);
    // Add to spider spread tracking list
    oms.addMarker(window[marker[0]+"_marker"]);
  }
}