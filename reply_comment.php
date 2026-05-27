<?php
session_start();

include 'dbh.inc.php';

$parent_id = $_GET['parent_id'] ?? NULL;
$recipe_id = 1; // Example post ID

// Fetch the parent comment to display it as context
$sql = "SELECT rc.*, u.username FROM recipe_comments rc LEFT JOIN users u ON rc.user_id = u.id WHERE rc.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $parent_id);
$stmt->execute();
$result = $stmt->get_result();
$parent_comment = $result->fetch_assoc();

if (!$parent_comment) {
    die("Parent comment not found.");
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reply to Comment</title>
</head>
<body>

<h2>Reply to Comment</h2>
<div class="parent-comment">
    <p><strong><?php echo htmlspecialchars($parent_comment['username'] ?: $parent_comment['name']); ?></strong></p>
    <p><?php echo htmlspecialchars($parent_comment['comment_text']); ?></p>
</div>

<h3>Your Reply</h3>
<form action="add_comment.php" method="post">
    <textarea name="comment_text" required></textarea><br>
    <input type="hidden" name="recipe_id" value="<?php echo $recipe_id; ?>">
    <input type="hidden" name="parent_id" value="<?php echo $parent_id; ?>">
    <?php if (isset($_SESSION['user_id'])): ?>
        <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
    <?php else: ?>
        <input type="text" name="name" placeholder="Your Name" required><br>
        <input type="email" name="email" placeholder="Your Email" required><br>
        <input type="text" name="website" placeholder="Your Website"><br>
    <?php endif; ?>
    <input type="submit" value="Submit">
</form>

</body>
</html>