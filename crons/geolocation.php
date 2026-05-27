<?php
date_default_timezone_set('Africa/Johannesburg');

// Include database connection
include '/home/candybirdco/public_html/dbh.inc.php';

function isBot($userAgent) {
    $botPatterns = [
        'googlebot', 'bingbot', 'slackbot', 'twitterbot', 'facebookexternalhit',
        'baidu', 'yahoo', 'yandex', 'sogou', 'duckduckbot', 'crawler', 'spider',
        'robot', 'curl', 'bot', 'archiver', 'wget'
    ];

    foreach ($botPatterns as $botPattern) {
        if (stripos($userAgent, $botPattern) !== false) {
            return true;
        }
    }

    return false;
}

function getGeolocation($ip) {
    $apiKey = '1530db8962494ca99e480a567d49d6bb'; // Replace with your actual API key
    $url = "https://api.ipgeolocation.io/ipgeo?apiKey=$apiKey&ip=$ip";
    
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    
    // Execute the request
    $response = curl_exec($ch);
    
    // Check for cURL errors
    if ($response === false) {
        // echo "cURL Error: " . curl_error($ch) . "\n";
        // echo "<br>";
        curl_close($ch);
        return null;
    }
    
    curl_close($ch);

    // Decode and check response
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // echo "Error decoding JSON response: " . json_last_error_msg() . "\n";
        // echo "<br>";
        return null;
    }
    
    return $data;
}

function updateGeolocationData($conn, $ip) {
    $location = getGeolocation($ip);
    
    if ($location) {
        $city = $location['city'];
        $country = $location['country_name'];
        $latitude = $location['latitude'];
        $longitude = $location['longitude'];
        $continentCode = $location['continent_code'];
        $continentName = $location['continent_name'];
        $countryCode2 = $location['country_code2'];
        $countryCode3 = $location['country_code3'];
        $countryCapital = $location['country_capital'];
        $stateProv = $location['state_prov'];
        $district = $location['district'];
        $zipcode = $location['zipcode'];
        $isEu = $location['is_eu'];
        $callingCode = $location['calling_code'];
        $countryTld = $location['country_tld'];
        $languages = $location['languages'];
        $countryFlag = $location['country_flag'];
        $isp = $location['isp'];
        $connectionType = $location['connection_type'];
        $organization = $location['organization'];
        $currencyCode = $location['currency']['code'];
        $currencyName = $location['currency']['name'];
        $currencySymbol = $location['currency']['symbol'];
        $timeZoneName = $location['time_zone']['name'];
        $timeZoneOffset = $location['time_zone']['offset'];
        $timeZoneCurrentTime = $location['time_zone']['current_time'];
        $timeZoneIsDst = $location['time_zone']['is_dst'];
        $timeZoneDstSavings = $location['time_zone']['dst_savings'];
        $checkedAt = date('Y-m-d H:i:s');

        // Log current location to history table
        $stmt = $conn->prepare("
            INSERT INTO ip_geolocation_history (
                ip_address, city, country, latitude, longitude,
                continent_code, continent_name, country_code2, country_code3,
                country_capital, state_prov, district, zipcode, is_eu,
                calling_code, country_tld, languages, country_flag,
                isp, connection_type, organization, currency_code,
                currency_name, currency_symbol, time_zone_name, time_zone_offset,
                time_zone_current_time, time_zone_is_dst, time_zone_dst_savings,
                checked_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if (!$stmt) {
            // echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
            // echo "<br>";
            return;
        }
        
        $stmt->bind_param(
            "sssddsssssssssssssssssssssssss",
            $ip, $city, $country, $latitude, $longitude, $continentCode, $continentName, $countryCode2, $countryCode3,
            $countryCapital, $stateProv, $district, $zipcode, $isEu, $callingCode, $countryTld, $languages, $countryFlag,
            $isp, $connectionType, $organization, $currencyCode, $currencyName, $currencySymbol, $timeZoneName,
            $timeZoneOffset, $timeZoneCurrentTime, $timeZoneIsDst, $timeZoneDstSavings, $checkedAt
        );
        
        if ($stmt->execute()) {
            // echo "Successfully added row into ip_geolocation_history\n";
            // echo "<br>";

            // Insert a record into the cronjob table upon successful email sending
            $stmtNested = $conn->prepare("INSERT INTO cronjobs (job_name, description) VALUES (?,?)");
            $job_name = 'geolocation.php';
            $job_description = 'script executed successfully';
            if ($stmtNested) {
                $stmtNested->bind_param('ss', $job_name, $job_description);
                $stmtNested->execute();
                $stmtNested->close();
            } else {
                error_log("Failed to prepare statement for cronjob logging.");
            }

        } else {
            // echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
            // echo "<br>";
        }

        $stmt->close();

        // Update current geolocation data
        $stmt = $conn->prepare("INSERT INTO ip_geolocation (ip_address, city, country, latitude, longitude, last_checked) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE city = VALUES(city), country = VALUES(country), latitude = VALUES(latitude), longitude = VALUES(longitude), last_checked = VALUES(last_checked)");
        
        if (!$stmt) {
            // echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
            // echo "<br>";
            return;
        }
        
        $stmt->bind_param("sssdds", $ip, $city, $country, $latitude, $longitude, $checkedAt);
        
        if ($stmt->execute()) {
            // echo "Successfully added row into ip_geolocation\n";
            // echo "<br>";
        } else {
            // echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
            // echo "<br>";
        }
        
        $stmt->close();
    }
}

// Fetch distinct IP addresses and user agents
$sql = "SELECT DISTINCT ip_address, user_agent
        FROM action_logs
        WHERE created_at >= NOW() - INTERVAL 10 MINUTE";
$result = $conn->query($sql);

if ($result === false) {
    // echo "Query failed: (" . $conn->errno . ") " . $conn->error;
    // echo "<br>";
    exit;
}

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $ip = $row['ip_address'];
        $userAgent = $row['user_agent'];

        if (!isBot($userAgent)) {
            updateGeolocationData($conn, $ip);
        } else {
            // echo "Bot detected: $userAgent\n";
            // echo "<br>";
        }
    }
} else {
    // echo "No rows found\n";
    // echo "<br>";
}

$conn->close();
?>
