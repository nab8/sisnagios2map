<?php

class Site
{
    public $lookupcode;
    public $latitude;
    public $longitude;
    public $host_status = 0;
    public $service_status = 0;
    public $devices = array();
    public function __construct($lookupcode, $latitude, $longitude)
    {
       $this->lookupcode = $lookupcode;
       $this->latitude = $latitude;
       $this->longitude = $longitude;
    }
    public function setHoststatus($status)
    {
        $this->host_status = $status;
    }
    public function setServicestatus($status)
    {
        $this->service_status = $status;
    }
    public function addDevice($device)
    {
        $this->devices[] = $device;
    }
    public function getHoststatus()
    {
        return $this->host_status;
    }
    public function getServicestatus()
    {
        return $this->service_status;
    }
    public function getLatitude()
    {
        return $this->latitude;
    }
    public function getLongitude()
    {
        return $this->longitude;
    }
    public function getLookupcode()
    {
        return $this->lookupcode;
    }
    public function getDevices()
    {
        return $this->devices;
    }
}

class Device
{
    public $hostname;
    public $hoststatus;
    public $services = array();
    public function __construct($hostname, $hoststatus) 
    {
       $this->hostname = $hostname;
       $this->hoststatus = $hoststatus;
    }
    public function addService($service, $service_status)
    {
        $this->services[] = array("service" => $service, "status" => $service_status);
    }
    public function getServices()
    {
        return $this->services;
    }
}

// Get SIS GPS Coordinates
function getSISGPS()
{
    global $sis_ownercode, $sis_api_key, $sis_env, $sisurl, $sis_page_size;
    $sisurl = "https://anss-sis.scsn.org/$sis_env/api/v1/site-epochs?netcode=$sis_ownercode&page[size]=1";
    $header = array();
    $header[] = "Authorization: Bearer $sis_api_key";
    // Create Curl Request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$sisurl");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // Fetch JSON Response
    $json_response = curl_exec($ch);
    $json_response = json_decode($json_response, true);
    curl_close($ch);
    $sis_pages = ceil($json_response['meta']['pagination']['count'] / $sis_page_size);
    $sites = array();
    for ($i = 1; $i <= $sis_pages; $i++) {
        $sisurl = "https://anss-sis.scsn.org/$sis_env/api/v1/site-epochs?netcode=$sis_ownercode&page[size]=$sis_page_size&page[number]=$i";
        // Create Curl Request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$sisurl");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // Fetch JSON Response
        $json_response = curl_exec($ch);
        $json_response = json_decode($json_response, true);
        curl_close($ch);
        //echo "<pre>"; print_r($json_response); echo "</pre>";
        foreach ($json_response['data'] as $site) {
            $sites[$site['attributes']['lookupcode']] = new Site(
                $site['attributes']['lookupcode'],
                bcdiv($site['attributes']['latitude'], 1, 6),
                bcdiv($site['attributes']['longitude'], 1, 6)
            );
        }
    }
    return($sites);
}

// Get Nagios Statuses 
function getNagiosStatus($sites)
{
    global $nagios_url, $nagios_user, $nagios_pass, $nagios_site_explode, $nagios_site_explode_position;
    // Get Nagios Hosts, Services, & Statuses
    $opts = array(
      'http' => array(
        'method' => "GET",
        'header' => "Authorization: Basic " . base64_encode("$nagios_user:$nagios_pass")
      )
    );
    $context = stream_context_create($opts);
    $devices = array();
    $nagios_host_status = array(1 => "PENDING", 2 => "UP", 4 => "DOWN", 8 => "UNREACHABLE");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$nagios_url/cgi-bin/statusjson.cgi?query=hostlist");
    curl_setopt($ch, CURLOPT_USERPWD, $nagios_user . ":" . $nagios_pass);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $json_response = curl_exec($ch);
    $host_json = json_decode($json_response, true);
    curl_close($ch);
    foreach ($host_json['data']['hostlist'] as $device => $status) {
        $devices[$device] = new Device(
            $device,
            $status
        );
        $site_name = explode($nagios_site_explode, $device)[$nagios_site_explode_position];
        if (isset($sites[$site_name])) {
            $sites[$site_name]->addDevice($device);
            if ($status > $sites[$site_name]->getHoststatus()) {
                $sites[$site_name]->setHoststatus($status);
            }
        }
    }
    $nagios_service_status = array(1 => "PENDING", 2 => "OK", 4 => "WARNING", 8 => "UNKNOWN", 16 => "CRITICAL");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$nagios_url/cgi-bin/statusjson.cgi?query=servicelist");
    curl_setopt($ch, CURLOPT_USERPWD, $nagios_user . ":" . $nagios_pass);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $json_response = curl_exec($ch);
    $service_json = json_decode($json_response, true);
    curl_close($ch);
    foreach ($service_json['data']['servicelist'] as $device => $services) {
        foreach ($services as $service => $status) {
            $devices[$device]->addService(
                $service,
                $status
            );
        }
        $site_name = explode($nagios_site_explode, $device)[$nagios_site_explode_position];
        if (isset($sites[$site_name])) {
            if ($status > $sites[$site_name]->getServicestatus()) {
                $sites[$site_name]->setServicestatus($status);
            }
        }
    }
    return($devices);
}

// Create Markers
function createMarkers($view)
{
    global $host_array, $service_array;
    // Create Data for Mapping
    $host_array = array(
        1 => array("status" => "Pending", "color" => "gray"),
        2 => array("status" => "Up", "color" => "green"),
        4 => array("status" => "Down", "color" => "red"),
        8 => array("status" => "UNREACHABLE", "color" => "orange")
    );
    $service_array = array(
        1 => array("status" => "Pending", "color" => "gray"),
        2 => array("status" => "OK", "color" => "green"),
        4 => array("status" => "Warning", "color" => "yellow"),
        8 => array("status" => "Unknown", "color" => "orange"),
        16 => array("status" => "Critical", "color" => "red")
    );
    $sites = getSISGPS();
    $devices = getNagiosStatus($sites);
    $google_array = "const stationList = [\r\n";
    foreach ($sites as $key => $site) {
        if (count($site->getDevices()) > 0) {
            $popup_content = "<h3>{$site->getLookupcode()}</h3><table class='popup_content'>";
            foreach ($site->getDevices() as $device) {
                $popup_content .= "<tr><th colspan='2'>$device</th></tr>";
                foreach ($devices[$device]->getServices() as $service) {
                    $popup_content .= "<tr><td>{$service["service"]}</td>";
                    $popup_content .= "<td class='status_{$service_array[$service["status"]]['color']}'>{$service_array[$service["status"]]['status']}</td>";
                    $popup_content .= "</tr>";
                }
            }
            $popup_content .= "</table>";
            if ($view == "host") {
                $google_array .= "     [\"{$site->getLookupcode()}\", {$site->getLatitude()}, {$site->getLongitude()}, \"{$host_array[$site->getHoststatus()]['color']}.png\", \"$popup_content\" ],\r\n";
            } else {
                $google_array .= "     [\"{$site->getLookupcode()}\", {$site->getLatitude()}, {$site->getLongitude()}, \"{$service_array[$site->getServicestatus()]['color']}.png\", \"$popup_content\" ],\r\n";
            }
        }
    }
    $google_array .= "\r\n];\r\n";
    return($google_array);
}
