<?php
// Start or resume the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $userId = $_GET['id'];
    } else {
        $userId = null;
    }

    $redirect_url = "users?id=".$userId;
    header("Location: admin_login?redirect=" . urlencode($redirect_url)); // Redirect to the login page
    exit(); // Stop further execution
}

// Fetch admin_id from the session
$admin_id = $_SESSION['admin_id'];

include 'dbh.inc.php';

include 'header.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css">


<style>
    /* Custom CSS for inline cards */
    .card-container {
        display: flex;
        flex-wrap: wrap;
        gap: 20px; /* Adjust the gap between cards */
    }

    .card {
        flex: 1 1 300px; /* Adjust card width */
        //max-width: 300px; /* Maximum width for each card */
        max-height: 300px; /* Set a max height for vertical scrolling */
        overflow-y: auto; /* Enable vertical scrolling */
    }

</style>

<title>User Details</title>


<?php
include 'page_menues.php';

// Function to check if user is online based on sessions table
function isUserOnline($conn, $userId) {
    $query = "SELECT * FROM sessions WHERE user_id = $userId ORDER BY start_time DESC LIMIT 1";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $session = $result->fetch_assoc();
        if ($session['end_time'] === null) {
            // End time is null, user is online
            return true;
        }
    }

    // Default to offline if no active session found
    return false;
}

// Function to fetch the latest page view of the user
function getLatestPageView($conn, $userId) {
    $query = "SELECT pv.*
        FROM page_views pv
        INNER JOIN sessions s ON pv.session_id = s.id
        WHERE s.user_id = $userId
        ORDER BY pv.timestamp DESC
        LIMIT 1";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return null;
}

// Function to format session time and calculate time ago
function formatSessionTime($dateTimeStr) {
    $dateTime = new DateTime($dateTimeStr);
    $formattedTime = $dateTime->format('j F');
    $formattedTime .= $dateTime->format(' Y') != date(' Y') ? $dateTime->format(', Y') : '';
    $formattedTime .= ', ' . $dateTime->format('g.ia');
    
    // Calculate time ago
    $currentTime = new DateTime();
    $timeDiff = $dateTime->diff($currentTime);
    $daysAgo = $timeDiff->d;
    $hoursAgo = $timeDiff->h;
    $minutesAgo = $timeDiff->i;
    
    if ($daysAgo > 0) {
        $timeAgo = "({$daysAgo} days ago)";
    } elseif ($hoursAgo > 0) {
        $timeAgo = "({$hoursAgo} hours {$minutesAgo} minutes ago)";
    } elseif ($minutesAgo > 0) {
        $timeAgo = "({$minutesAgo} minutes ago)";
    } else {
        $timeAgo = "(just now)";
    }

    return [
        'formattedTime' => $formattedTime,
        'timeAgo' => $timeAgo
    ];
}

// Function to classify device, browser, and OS from user agent
function classifyDevice($userAgent) {
    // Initialize variables
    $deviceType = 'Unknown';
    $browser = 'Unknown';
    $browserVersion = '';
    $os = 'Unknown';

    // Detect device type
    if (preg_match('/(tablet|ipad|playbook)|(android(?!.*mobile))/i', $userAgent)) {
        $deviceType = 'Tablet';
    } elseif (preg_match('/Mobile|Android|iPhone|BlackBerry/', $userAgent)) {
        $deviceType = 'Mobile';
    } else {
        $deviceType = 'PC';
    }

    // Detect browser and version
    if (preg_match('/MSIE/i', $userAgent) && !preg_match('/Opera/i', $userAgent)) {
        $browser = 'Internet Explorer';
        preg_match('/MSIE ([0-9.]+)/i', $userAgent, $matches);
        if ($matches) {
            $browserVersion = $matches[1];
        }
    } elseif (preg_match('/Firefox/i', $userAgent)) {
        $browser = 'Firefox';
        preg_match('/Firefox\/([0-9.]+)/i', $userAgent, $matches);
        if ($matches) {
            $browserVersion = $matches[1];
        }
    } elseif (preg_match('/Chrome/i', $userAgent)) {
        $browser = 'Chrome';
        preg_match('/Chrome\/([0-9.]+)/i', $userAgent, $matches);
        if ($matches) {
            $browserVersion = $matches[1];
        }
    } elseif (preg_match('/Safari/i', $userAgent)) {
        $browser = 'Safari';
        preg_match('/Version\/([0-9.]+)/i', $userAgent, $matches);
        if ($matches) {
            $browserVersion = $matches[1];
        }
    } elseif (preg_match('/Opera/i', $userAgent)) {
        $browser = 'Opera';
        preg_match('/Opera\/([0-9.]+)/i', $userAgent, $matches);
        if ($matches) {
            $browserVersion = $matches[1];
        }
    }

    // Detect operating system
    if (preg_match('/Windows NT 10.0/i', $userAgent)) {
        $os = 'Windows 10';
    } elseif (preg_match('/Windows NT 6.3/i', $userAgent)) {
        $os = 'Windows 8.1';
    } elseif (preg_match('/Windows NT 6.2/i', $userAgent)) {
        $os = 'Windows 8';
    } elseif (preg_match('/Windows NT 6.1/i', $userAgent)) {
        $os = 'Windows 7';
    } elseif (preg_match('/Windows NT 6.0/i', $userAgent)) {
        $os = 'Windows Vista';
    } elseif (preg_match('/Windows NT 5.1/i', $userAgent)) {
        $os = 'Windows XP';
    } elseif (preg_match('/Macintosh/i', $userAgent)) {
        $os = 'Macintosh';
    } elseif (preg_match('/Linux/i', $userAgent)) {
        $os = 'Linux';
    }

    // Format and return device info
    $deviceInfo = "{$deviceType} ({$browser} {$browserVersion}), {$os}";
    return $deviceInfo;
}


// Function to fetch data from related tables
function fetchRelatedData($conn, $userId) {
    // Fetch sessions data
    $sessionsSql = "
        SELECT *
        FROM sessions
        WHERE user_id = $userId
    ";
    $sessionsResult = $conn->query($sessionsSql);
    $sessionsData = ($sessionsResult->num_rows > 0) ? $sessionsResult->fetch_all(MYSQLI_ASSOC) : [];

    // Fetch page views data
    $pageViewsSql = "
        SELECT pv.*
        FROM page_views pv
        JOIN sessions s ON pv.session_id = s.id
        WHERE s.user_id = $userId
        ORDER BY timestamp DESC
    ";
    $pageViewsResult = $conn->query($pageViewsSql);
    $pageViewsData = ($pageViewsResult->num_rows > 0) ? $pageViewsResult->fetch_all(MYSQLI_ASSOC) : [];

    // Fetch search terms data
    $searchTermsSql = "
        SELECT st.*
        FROM search_terms st
        JOIN sessions s ON st.session_id = s.id
        WHERE s.user_id = $userId
    ";
    $searchTermsResult = $conn->query($searchTermsSql);
    $searchTermsData = ($searchTermsResult->num_rows > 0) ? $searchTermsResult->fetch_all(MYSQLI_ASSOC) : [];

    // Fetch login attempts data
    $loginAttemptsSql = "
        SELECT *
        FROM login_attempts
        WHERE user_id = $userId
    ";
    $loginAttemptsResult = $conn->query($loginAttemptsSql);
    $loginAttemptsData = ($loginAttemptsResult->num_rows > 0) ? $loginAttemptsResult->fetch_all(MYSQLI_ASSOC) : [];

    // Fetch transactions data
    $transactionsSql = "
        SELECT *
        FROM transactions
        WHERE user_id = $userId
    ";
    $transactionsResult = $conn->query($transactionsSql);
    $transactionsData = ($transactionsResult->num_rows > 0) ? $transactionsResult->fetch_all(MYSQLI_ASSOC) : [];

    // Fetch cart actions data
    $cartActionsSql = "
        SELECT *
        FROM cart_actions ca
        JOIN sessions s ON ca.session_id = s.id
        WHERE s.user_id = $userId
    ";
    $cartActionsResult = $conn->query($cartActionsSql);
    $cartActionsData = ($cartActionsResult->num_rows > 0) ? $cartActionsResult->fetch_all(MYSQLI_ASSOC) : [];

    // Fetch wishlist actions data
    $wishlistActionsSql = "
        SELECT *
        FROM wishlist_actions wa
        JOIN sessions s ON wa.session_id = s.id
        WHERE s.user_id = $userId
    ";
    $wishlistActionsResult = $conn->query($wishlistActionsSql);
    $wishlistActionsData = ($wishlistActionsResult->num_rows > 0) ? $wishlistActionsResult->fetch_all(MYSQLI_ASSOC) : [];

    // Fetch compare actions data
    $compareActionsSql = "
        SELECT *
        FROM compare_actions ca
        JOIN sessions s ON ca.session_id = s.id
        WHERE s.user_id = $userId
    ";
    $compareActionsResult = $conn->query($compareActionsSql);
    $compareActionsData = ($compareActionsResult->num_rows > 0) ? $compareActionsResult->fetch_all(MYSQLI_ASSOC) : [];

    return [
        'sessions' => $sessionsData,
        'page_views' => $pageViewsData,
        'search_terms' => $searchTermsData,
        'login_attempts' => $loginAttemptsData,
        'transactions' => $transactionsData,
        'cart_actions' => $cartActionsData,
        'wishlist_actions' => $wishlistActionsData,
        'compare_actions' => $compareActionsData
    ];
}

?>

<div class="container mt-4">
    <div class="card-container">

<?php

// Fetch user details based on user ID from GET parameter
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $userId = $_GET['id'];

    // Query to fetch user details
    $userSql = "
        SELECT u.*, 
               (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count,
               (SELECT COUNT(*) FROM reviews r WHERE r.user_id = u.id) AS review_count,
               (SELECT COUNT(*) FROM cart c WHERE c.user_id = u.id) AS cart_count,
               (SELECT COUNT(*) FROM wishlist w WHERE w.user_id = u.id) AS wishlist_count,
               (SELECT COUNT(*) FROM compare cm WHERE cm.user_id = u.id) AS compare_count
        FROM users u
        WHERE u.id = $userId
    ";

    $userResult = $conn->query($userSql);

    if ($userResult->num_rows > 0) {
        $userData = $userResult->fetch_assoc();


        // Bootstrap card for user details
        echo '<div class="card">';
        echo '<div class="card-body">';
        echo "<h2 class='card-title'>User Details for {$userData['username']}</h2>";
        echo "<p class='card-text'>Email: {$userData['email']}</p>";
        echo "<p class='card-text'>Status: {$userData['status']}</p>";
        echo "<p class='card-text'>Created At: {$userData['created_at']}</p>";

        // Check if user is online based on sessions table
        $isOnline = isUserOnline($conn, $userData['id']); // Assuming $userData['id'] contains the user's ID

        // Display online status indicator
        if ($isOnline) {
            echo '<p class="card-text">Online <span class="badge bg-success">Online</span></p>';

            // Get the latest page view of the user
            $latestPageView = getLatestPageView($conn, $userData['id']);
            if ($latestPageView) {
                echo "<p class='card-text'>Viewing Page: {$latestPageView['url']}</p>";
            } else {
                echo "<p class='card-text'>Viewing Page: Unknown</p>";
            }
        } else {
            echo '<p class="card-text">Offline</p>';
        }

        // Add more user details as needed
        echo '</div>';
        echo '</div>';

        // Include profile picture if available
        if (!empty($userData['profile_picture'])) {
            echo '<div class="card">';
            echo '<div class="card-body">';
            echo "<h5 class='card-title'>Profile Picture</h5>";
            echo "<img src='{$userData['profile_picture']}' class='card-img-top' alt='Profile Picture'>";
            echo '</div>';
            echo '</div>';
        }













        // Fetch and display related data
        $relatedData = fetchRelatedData($conn, $userId);

        // Bootstrap card for Sessions data
        echo '<div class="card">';
        echo '<div class="card-body">';
        echo "<h3 class='card-title'>Sessions</h3>";
        if (!empty($relatedData['sessions'])) {
            echo "<table id='sessionsTable' class='table table-striped table-bordered'>";
            echo "<thead>";
            echo "<tr>";
            echo "<th>Session ID</th>";
            echo "<th>Start Time</th>";
            echo "<th>End Time</th>";
            echo "<th>Device Info</th>"; // Updated column for Device Info
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";
            foreach ($relatedData['sessions'] as $session) {
                $startTime = formatSessionTime($session['start_time']);
                $endTime = !empty($session['end_time']) ? formatSessionTime($session['end_time']) : ['formattedTime' => 'N/A', 'timeAgo' => 'N/A'];
                $deviceInfo = classifyDevice($session['user_agent']);

                echo "<tr>";
                echo "<td>{$session['session_id']}</td>";
                echo "<td>{$startTime['formattedTime']} {$startTime['timeAgo']}</td>";
                echo "<td>{$endTime['formattedTime']} {$endTime['timeAgo']}</td>";
                echo "<td>{$deviceInfo}</td>"; // Display device info
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
        } else {
            echo "<p class='card-text'>No sessions found.</p>";
        }
        echo '</div>';
        echo '</div>';







        // Repeat the card structure for Page Views data
       echo '<div class="card">';
        echo '<div class="card-body">';
        echo "<h3 class='card-title'>Page Views</h3>";
        if (!empty($relatedData['page_views'])) {
            echo "<table id='pageViewsTable' class='table table-striped table-bordered'>";
            echo "<thead>";
            echo "<tr>";
            echo "<th>URL</th>";
            echo "<th>Timestamp</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";
            foreach ($relatedData['page_views'] as $pageView) {
                echo "<tr>";
                echo "<td>{$pageView['url']}</td>";
                echo "<td>{$pageView['timestamp']}</td>";
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
        } else {
            echo "<p class='card-text'>No page views found.</p>";
        }
        echo '</div>';
        echo '</div>';

        // Repeat the card structure for Search Terms data
        echo '<div class="card">';
        echo '<div class="card-body">';
        echo "<h3 class='card-title'>Search Terms</h3>";
        if (!empty($relatedData['search_terms'])) {
            echo "<ul class='card-text'>";
            foreach ($relatedData['search_terms'] as $searchTerm) {
                echo "<li>Term: {$searchTerm['term']} - Results Count: {$searchTerm['results_count']}</li>";
                // Add more search terms details as needed
            }
            echo "</ul>";
        } else {
            echo "<p class='card-text'>No search terms found.</p>";
        }
        echo '</div>';
        echo '</div>';

        // Repeat the card structure for Login Attempts data
        echo '<div class="card">';
        echo '<div class="card-body">';
        echo "<h3 class='card-title'>Login Attempts</h3>";
        if (!empty($relatedData['login_attempts'])) {
            echo "<ul class='card-text'>";
            foreach ($relatedData['login_attempts'] as $loginAttempt) {
                $successText = $loginAttempt['success'] ? "Success" : "Failure";
                echo "<li>Timestamp: {$loginAttempt['timestamp']} - {$successText}</li>";
                // Add more login attempts details as needed
            }
            echo "</ul>";
        } else {
            echo "<p class='card-text'>No login attempts found.</p>";
        }
        echo '</div>';
        echo '</div>';

        // Repeat the card structure for Transactions data
        echo '<div class="card">';
        echo '<div class="card-body">';
        echo "<h3 class='card-title'>Transactions</h3>";
        if (!empty($relatedData['transactions'])) {
            echo "<ul class='card-text'>";
            foreach ($relatedData['transactions'] as $transaction) {
                echo "<li>Amount: {$transaction['amount']} - Payment Method: {$transaction['payment_method']} - Timestamp: {$transaction['timestamp']}</li>";
                // Add more transactions details as needed
            }
            echo "</ul>";
        } else {
            echo "<p class='card-text'>No transactions found.</p>";
        }
        echo '</div>';
        echo '</div>';

        // Repeat the card structure for Cart Actions data
        echo '<div class="card">';
        echo '<div class="card-body">';
        echo "<h3 class='card-title'>Cart Actions</h3>";
        if (!empty($relatedData['cart_actions'])) {
            echo "<ul class='card-text'>";
            foreach ($relatedData['cart_actions'] as $cartAction) {
                echo "<li>Action Type: {$cartAction['action_type']} - Product ID: {$cartAction['product_id']} - Timestamp: {$cartAction['timestamp']}</li>";
                // Add more cart actions details as needed
            }
            echo "</ul>";
        } else {
            echo "<p class='card-text'>No cart actions found.</p>";
        }
        echo '</div>';
        echo '</div>';

        // Repeat the card structure for Wishlist Actions data
        echo '<div class="card">';
        echo '<div class="card-body">';
        echo "<h3 class='card-title'>Wishlist Actions</h3>";
        if (!empty($relatedData['wishlist_actions'])) {
            echo "<ul class='card-text'>";
            foreach ($relatedData['wishlist_actions'] as $wishlistAction) {
                echo "<li>Action Type: {$wishlistAction['action_type']} - Product ID: {$wishlistAction['product_id']} - Timestamp: {$wishlistAction['timestamp']}</li>";
                // Add more wishlist actions details as needed
            }
            echo "</ul>";
        } else {
            echo "<p class='card-text'>No wishlist actions found.</p>";
        }
        echo '</div>';
        echo '</div>';

        // Repeat the card structure for Compare Actions data
        echo '<div class="card">';
        echo '<div class="card-body">';
        echo "<h3 class='card-title'>Compare Actions</h3>";
        if (!empty($relatedData['compare_actions'])) {
            echo "<ul class='card-text'>";
            foreach ($relatedData['compare_actions'] as $compareAction) {
                echo "<li>Action Type: {$compareAction['action_type']} - Product ID: {$compareAction['product_id']} - Timestamp: {$compareAction['timestamp']}</li>";
                // Add more compare actions details as needed
            }
            echo "</ul>";
        } else {
            echo "<p class='card-text'>No compare actions found.</p>";
        }
        echo '</div>';
        echo '</div>';

    } else {
        echo "<p class='alert alert-danger'>User not found.</p>";
    }

} else {
    echo "<p class='alert alert-warning'>Invalid user ID.</p>";
}
?>

    </div>
</div>

<?php
include '../footer.php';
?>