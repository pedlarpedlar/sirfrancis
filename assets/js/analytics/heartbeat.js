// heartbeat.js
// For analytical tracking

/************************/
// READ ME 
// To track session end times, you can use a combination of methods to ensure you capture the end of a session under various circumstances:

// Explicit Logout: When a user explicitly logs out, you can update the session end time in the database.
// Inactivity Timeout: You can use JavaScript to detect inactivity and update the session end time if the user is inactive for a certain period.
// Browser Close or Tab Close: You can use JavaScript events to detect when a user is closing the browser or tab and update the session end time.
// (THIS FILE) Regular Heartbeat: Implement a periodic "heartbeat" AJAX request that keeps the session alive and update the end time if the heartbeat stops.

/************************/

function sendHeartbeat() {
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "https://www.fishgelatine.co.za/v2/update_end_time.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.send("session_id=" + sessionId); // Replace sessionId with the actual session ID
}

// Send heartbeat every 5 minutes
setInterval(sendHeartbeat, 300000);