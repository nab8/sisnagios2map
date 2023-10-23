<?php

// Nagios Configuration
// Nagios URL, most likely https://fqdn/nagios/
$nagios_url = "";
// Nagios authentication, assumes you are using BASIC auth (popup login)
$nagios_user = "";
$nagios_pass = "";
# If your pattern is SITE_DEVICE then these are the recommended defaults
$nagios_site_explode = "_";
$nagios_site_explode_position = 0;

// SIS Configuration
// Which SIS API environment do you want to use, sis or sistest
$sis_api_key = "";
$sis_env = "";
# Can be comma seperated
$sis_ownercode = "";
$sis_page_size = ;


// Map Configuration
// Google Maps API Key
$maps_api = "";
// Google Maps Central Coordinates & Zoom
$maps_latitude = "";
$maps_longitude = "";
$maps_zoom = 6;
// Default map view, should be host or service
$map_default_view = "host";
