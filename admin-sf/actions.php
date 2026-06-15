<?php

include '../session_logins.php';

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    $redirect_url = "actions";
    header("Location: admin_login?redirect=" . urlencode($redirect_url)); // Redirect to the login page
    exit(); // Stop further execution
}

// Function to format the time difference
function timeAgo($timestamp) {
    $currentTime = new DateTime();
    $actionTime = new DateTime($timestamp);
    $interval = $currentTime->diff($actionTime);

    $timeComponents = array();

    if ($interval->y > 0) {
        $timeComponents[] = $interval->y . ' year' . ($interval->y > 1 ? 's' : '');
    }
    if ($interval->m > 0) {
        $timeComponents[] = $interval->m . ' month' . ($interval->m > 1 ? 's' : '');
    }
    if ($interval->d > 0) {
        $timeComponents[] = $interval->d . ' day' . ($interval->d > 1 ? 's' : '');
    }
    if ($interval->h > 0) {
        $timeComponents[] = $interval->h . ' hour' . ($interval->h > 1 ? 's' : '');
    }
    if ($interval->i > 0) {
        $timeComponents[] = $interval->i . ' minute' . ($interval->i > 1 ? 's' : '');
    }
    if ($interval->s > 0) {
        $timeComponents[] = $interval->s . ' second' . ($interval->s > 1 ? 's' : '');
    }

    // Keep only the first two components
    $timeComponents = array_slice($timeComponents, 0, 2);

    return implode(' ', $timeComponents) . ' ago';
}

// Function to generate HTML table
function generateTable($groupedData, $rowNumberStart = 1) {
    static $tableCount = 0;
    $tableCount++;
    $tableId = "dataTable$tableCount";

    echo "<table id='$tableId' class='table display responsive nowrap' style='width:100%'>
            <thead>
                <tr>
                    <th>#</th>
                    <th>IP Address</th>
                    <th>Last Action</th>
                    <th>Actions and Details</th>
                    <th>Location</th>
                    <th>Device</th>
                    <th>Browser</th>
                    <th>User ID</th>
                    <th>Guest Identifier</th>
                    <th>User Agent</th>
                </tr>
            </thead>
            <tbody>";

    $rowNumber = $rowNumberStart;
    foreach ($groupedData as $data) {
        $actionsDetails = implode('<br>', $data['actions_details']);

        echo "<tr>
                <td><span class='btn btn-info'>{$rowNumber}</span></td>
                <td>{$data['ip_address']}</td>
                <td>{$data['time_of_last_action']}</td>
                <td>{$actionsDetails}</td>
                <td>{$data['city']}, {$data['province']}, {$data['country']}</td>
                <td>{$data['device_type']}</td>
                <td>{$data['browser']}</td>
                <td>{$data['user_id']}</td>
                <td>{$data['guest_identifier']}</td>
                <td>{$data['user_agent']}</td>
              </tr>";

        $rowNumber++;
    }

    echo "</tbody></table>";
}


// Function to fetch and group data based on different conditions
function fetchAndGroupData($conn, $whereConditions, $rowNumberStart = 1) {
    $query = "
        SELECT 
        al.ip_address,
        al.action AS last_action,
        al.details AS action_detail,
        al.user_id,
        al.guest_identifier,
        igh.city,
        igh.country,
        igh.state_prov AS province,
        al.user_agent,
        CASE
            WHEN al.user_agent LIKE '%Mobile%' THEN 'Mobile'
            ELSE 'PC'
        END AS device_type,
        CASE
            WHEN al.user_agent LIKE '%Chrome%' THEN 'Chrome'
            WHEN al.user_agent LIKE '%Firefox%' THEN 'Firefox'
            WHEN al.user_agent LIKE '%Safari%' AND al.user_agent NOT LIKE '%Chrome%' THEN 'Safari'
            WHEN al.user_agent LIKE '%Edge%' THEN 'Edge'
            WHEN al.user_agent LIKE '%Trident%' OR al.user_agent LIKE '%MSIE%' THEN 'Internet Explorer'
            ELSE 'Other'
        END AS browser,
        MAX(al.created_at) AS time_of_last_action
    FROM 
        action_logs al
    LEFT JOIN 
        ip_geolocation_history igh ON al.ip_address = igh.ip_address
    WHERE 
        $whereConditions
        AND al.user_agent NOT LIKE '%bot%'
        AND al.user_agent NOT LIKE '%crawler%'
        AND al.user_agent NOT LIKE '%spider%'
        AND al.user_agent NOT LIKE '%slackbot%'
        AND al.user_agent NOT LIKE '%twitterbot%'
        AND al.user_agent NOT LIKE '%facebookexternalhit%'
        AND al.user_agent NOT LIKE '%facebookplatform%'
        AND al.user_agent NOT LIKE '%LinkedInBot%'
        AND al.user_agent NOT LIKE '%Pinterest%'
        AND al.user_agent NOT LIKE '%Googlebot%'
        AND al.user_agent NOT LIKE '%Bingbot%'
        AND al.user_agent NOT LIKE '%MSNBot%'
        AND al.user_agent NOT LIKE '%Slurp%'
        AND al.user_agent NOT LIKE '%DuckDuckBot%'
        AND al.user_agent NOT LIKE '%Baiduspider%'
        AND al.user_agent NOT LIKE '%YandexBot%'
        AND al.user_agent NOT LIKE '%Sogou Spider%'
        AND al.user_agent NOT LIKE '%Exabot%'
        AND al.user_agent NOT LIKE '%AlexaBot%'
        AND al.user_agent NOT LIKE '%SemrushBot%'
        AND al.user_agent NOT LIKE '%AhrefsBot%'
        AND al.user_agent NOT LIKE '%MozBot%'
        AND al.user_agent NOT LIKE '%Updater%'
        AND al.user_agent NOT LIKE '%UpdateBot%'
        AND al.user_agent NOT LIKE '%DotBot%'
        AND al.user_agent NOT LIKE '%Go-http-client/2.0%'
    GROUP BY 
        al.ip_address, al.user_id, al.guest_identifier, al.action, al.details, igh.city, igh.country, igh.state_prov, al.user_agent, device_type, browser
    ORDER BY 
        time_of_last_action DESC;
    ";

    $result = $conn->query($query);

    $groupedData = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $ipAddress = $row['ip_address'];

            if (!isset($groupedData[$ipAddress])) {
                $groupedData[$ipAddress] = [
                    'ip_address' => $ipAddress,
                    'actions_details' => [],
                    'user_id' => $row['user_id'],
                    'guest_identifier' => $row['guest_identifier'],
                    'city' => $row['city'],
                    'country' => $row['country'],
                    'province' => $row['province'],
                    'user_agent' => $row['user_agent'],
                    'device_type' => $row['device_type'],
                    'browser' => $row['browser'],
                    'time_of_last_action' => $row['time_of_last_action'],
                ];
            }

            $groupedData[$ipAddress]['actions_details'][] = "{$row['last_action']}: {$row['action_detail']}";
        }
    }

    return $groupedData;
}

// Define conditions
$conditionsSA = "igh.country = 'South Africa' AND al.action != 'Browsing' 
";
$conditionsNonSA = "igh.country != 'South Africa' 
";
$conditionsSABrowsing = "igh.country = 'South Africa' AND al.action = 'Browsing' 
";


include 'header.php';
include 'page_menues.php';
?>

<style>
    table.dataTable td, table.dataTable th {
/*        max-width: 150px;*/
        word-wrap: break-word;
        white-space: normal;
    }

    /* Ensure table doesn't exceed container width */
    .dataTables_wrapper {
        width: 100%;
        overflow-x: auto;
    }

    /* For responsive design, ensure proper spacing */
    @media (max-width: 768px) {
        table.dataTable td, table.dataTable th {
            max-width: 100px;
        }
    }
</style>

<div class="container">
    <div class="row">
      <div class="col-12">

<?php
// Fetch and display South African results
$groupedDataSA = fetchAndGroupData($conn, $conditionsSA);
echo "<h2>South African Results (Excluding Browsing)</h2>";
generateTable($groupedDataSA);

// Fetch and display Non-South African results
$groupedDataNonSA = fetchAndGroupData($conn, $conditionsNonSA, count($groupedDataSA) + 1);
echo "<h2>Non-South African Results</h2>";
generateTable($groupedDataNonSA, count($groupedDataSA) + 1);

// Fetch and display South African Browsing results
$groupedDataSABrowsing = fetchAndGroupData($conn, $conditionsSABrowsing);
echo "<h2>South African Browsing Results</h2>";
generateTable($groupedDataSABrowsing, count($groupedDataSA) + count($groupedDataNonSA) + 1);
?>

        </div>
    </div>
</div>


<!-- Include jQuery library -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

<script>
$(document).ready(function() {
    $('#dataTable1, #dataTable2, #dataTable3').DataTable({
        responsive: true,
        autoWidth: false,
        pageLength: 10, // Number of rows to display per page
        order: [[2, 'desc']] // Order by Time of Last Action (column 11) descending
    });
});

</script>
<?php
include '../footer.php';
?>

