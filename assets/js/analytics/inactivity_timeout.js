// inactivity_timeout.js
// For analytical tracking

/************************/
// READ ME 
// To track session end times, you can use a combination of methods to ensure you capture the end of a session under various circumstances:

// Explicit Logout: When a user explicitly logs out, you can update the session end time in the database.
// (THIS FILE) Inactivity Timeout: You can use JavaScript to detect inactivity and update the session end time if the user is inactive for a certain period.
// (ALSO THIS FILE) Browser Close or Tab Close: You can use JavaScript events to detect when a user is closing the browser or tab and update the session end time.
// Regular Heartbeat: Implement a periodic "heartbeat" AJAX request that keeps the session alive and update the end time if the heartbeat stops.

/************************/

var inactivityTime = function () {
    var t;
    window.onload = resetTimer;
    window.onmousemove = resetTimer;
    window.onkeypress = resetTimer;
    window.onmousedown = resetTimer; // Touchscreen presses
    window.ontouchstart = resetTimer;
    window.ontouchmove = resetTimer;
    window.onclick = resetTimer;
    window.addEventListener('scroll', resetTimer, true); // Improved scroll

    function logout() {
        updateEndTime();
        // Redirect or perform any other logout actions
    }

    function resetTimer() {
        clearTimeout(t);
        t = setTimeout(logout, 1800000); // 30 minutes of inactivity
    }
};

inactivityTime();

function updateEndTime() {
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "https://www.fishgelatine.co.za/v2/update_end_time.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.send("session_id=" + sessionId); // Replace sessionId with the actual session ID
}

window.onbeforeunload = updateEndTime;