<?php
session_start();

include 'dbh.inc.php';

$comment_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$guestIdentifier = $_SESSION['guest_identifier'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $comment_text = $_POST['comment_text'];

    $sql = "UPDATE recipe_comments SET comment_text = ? WHERE id = ? AND (user_id = ? OR guest_identifier = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('siis', $comment_text, $comment_id, $user_id, $guestIdentifier);

    if ($stmt->execute()) {
        echo "Comment updated successfully";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    header("Location: recipe"); // Redirect back to the comments page
    exit();
} else {
    $sql = "SELECT comment_text FROM recipe_comments WHERE id = ? AND (user_id = ? OR guest_identifier = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iis', $comment_id, $user_id, $guestIdentifier);
    $stmt->execute();
    $stmt->bind_result($comment_text);
    $stmt->fetch();
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Comment</title>
</head>
<body>

<h2>Edit Comment</h2>
<form action="edit_comment?id=<?php echo $comment_id; ?>" method="post">
    <textarea name="comment_text" required><?php echo htmlspecialchars($comment_text); ?></textarea><br>
    <input type="submit" value="Update">
</form>

</body>
</html>