# Overview
This is a basic proof-of-concept for fetching devices from SIS & Nagios via API and creating a Google Map view.
## Config

### Nagios Settings
* nagios_url - this is your Nagios URL, should be similar to https://fqdn/nagios/
* nagios_user - this is your Nagios username, a view only account is recommended
* nagios_pass - this is your Nagios password, a view only account is recommended
* nagios_site_explode - this is the explode deliminter for extracting the site name, if you use the pattern SITE_DEVICE you should use "_"
* nagios_site_explode_position - this is the explode position, the first result is 0 so if you use the pattern SITE_DEVICE you should use 0


### SIS Settings
* sis_api_key - this is your SIS API key, see https://anss-sis.scsn.org/sis/api/v1/docs/#about-token-auth for instructions
* sis_env - should either be sis (production) or sistest (testing)
* sis_ownercode - used as the ownercode for equipment lookups and netcode for site-epoch lookups
* sis_page_size - must be 500 or less; this is a tradeoff between the number of API calls and memory usage

### Google Maps Settings
* maps_api - this is your API key for Google Maps
* maps_latitude - this is the latitude you want the map centered on
* maps_longitude - this is the longitude you want the map centered on
* maps_zoom - this is the map zoom level you want to start at, 6 is recommended
* map_default_view - this is which view you wish to default to, should be host or service.  The difference is the color coding of the pins; for hosts it will use the worst host status for that site and for service it will use the worst service status for that site

## Usage
There is a sample map.php to give you an embeddable map but it should be easy enough to incorporate it into existing pages.  

## Notes
* Only devices that are mapped to sites are included as it needs the GPS coordinates for drawing the pin
* You can customize the popup content in the assets/sisnagios2map.php file by editing the createMarkers function.  This can be useful if you wish to add links, additional details, etc.