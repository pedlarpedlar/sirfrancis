<?php
include 'session_logins.php';
include 'header.php';

$recipe_id = 1; // Example post ID

// Prepare the SQL statement
$sql = "SELECT rc.*, u.username 
        FROM recipe_comments rc 
        LEFT JOIN users u ON rc.user_id = u.id 
        WHERE rc.recipe_id = ? AND rc.approved = TRUE 
        ORDER BY rc.likes DESC, rc.created_at ASC";

// Prepare the statement
$stmt = $conn->prepare($sql);

// Bind the recipe ID
$stmt->bind_param('i', $recipe_id);

// Execute the statement
$stmt->execute();

// Get the result
$result = $stmt->get_result();

$comments = [];
while ($row = $result->fetch_assoc()) {
    $comments[] = $row;
}

// Get the total number of comments (including replies)
$comment_count = $result->num_rows;

// Function to display comments and their replies
function display_comments($comments, $recipe_id, $userId, $parent_id = NULL, $level = 0) {
    foreach ($comments as $comment) {
        if ($comment['parent_id'] == $parent_id) {
            $userReaction = getUserReaction($comment['id']); // Fetch user reaction for this comment
            echo '<div class="single-review comment" data-comment-id="' . $comment['id'] . '" data-user-reaction="' . $userReaction . '" style="margin-left:' . ($level * 20) . 'px;">';
            
                echo '<div class="review-img"><img src="assets/img/testimonial-image/1.png" alt="" /></div>';
                echo '<div class="review-content" >';
                

                    echo '<div class="review-top-wrap">
                      <div class="review-left">
                        <div class="review-name text-warning">
                          <h4>'.htmlspecialchars($comment['username'] ?: $comment['name']).'</h4>
                          <span class="date">'.date('M j, Y \a\t g:i a', strtotime($comment['created_at'])).'</span>
                        </div>
                      </div>
                      ';
                    echo '
                    </div>';

                
                    echo '<div class="review-left">';
                        if ($comment['deleted']) {
                            echo '<p><em>Comment removed or deleted</em></p>';
                        } else {
                            echo '<p>' . htmlspecialchars($comment['comment_text']) . '</p>';
                        }

                        echo '</div>';

                    echo '<div class="review-top-wrap"><div class="review-left"><a href="#form_'.$comment['id'].'" class="toggle-reply-form px-3 py-3" data-comment-id="' . $comment['id'] . '">Reply</a></div></div>';

                    if ($comment['user_id'] == $userId) {
                        echo '<div><span class="py-3"><a href="edit_comment.php?id=' . $comment['id'] . '">Edit</a> <a href="delete_comment.php?id=' . $comment['id'] . '">Delete</a></span></div>';
                    }

                        echo '<div class="my_actions">';
                        echo '<a href="#" class="like-comment" data-comment-id="' . $comment['id'] . '">Like (<span class="likes-count">' . $comment['likes'] . '</span>)</a>';
                        echo '<a href="#" class="dislike-comment ml-2" data-comment-id="' . $comment['id'] . '">Dislike (<span class="dislikes-count">' . $comment['dislikes'] . '</span>)</a>';
                        echo '</div>';

                // Check if there are replies and show "Show replies" link if yes
                $repliesCount = countReplies($comments, $comment['id']);
                if ($repliesCount > 0) {
                    echo '<a href="#" class="toggle-replies" data-comment-id="' . $comment['id'] . '">Show replies (' . $repliesCount . ')</a>';
                    echo '<div class="replies review-bottom py-3" style="display: none;">';
                    display_comments($comments, $recipe_id, $userId, $comment['id'], $level + 1);
                    echo '</div>';
                }


                // Reply form section
                echo '
                <div class="blog-comment-form reply-form review-bottom" style="margin-left:' . (($level + 1) * 20) . 'px; display: none;">
                  <h2 class="comment-heading">Reply</h2>
                  <p>
                    Your email address will not be published. Required fields are marked
                    *
                  </p>
                  <div class="row">
                  <form action="add_comment.php" method="post" ">
                        <div class="col-md-12">
                          <div class="single-form">
                            <label>Your Reply:</label>
                            <textarea name="comment_text" required></textarea>
                            <input type="hidden" name="recipe_id" value="' . $recipe_id . '">
                            <input type="hidden" name="parent_id" value="' . $comment['id'] . '">
                          </div>
                        </div>';
                            if ($userId != null) {
                                echo '<input type="hidden" name="user_id" value="' . $userId . '">';
                            } else {
                                echo '<div class="col-md-4"><div class="single-form"><label>Name:</label><input type="text" name="name" placeholder="Your Name" required></div></div>';
                                echo '<div class="col-md-4"><div class="single-form"><label>Email:</label><input type="email" name="email" placeholder="Your Email" required></div></div>';
                            }
                            echo '<div class="col-md-12"><div class="single-form"><input type="submit" value="Submit"></div></div>';
                    echo '</form>';
                    echo '</div>';
                echo '</div>';
                    

                
                


                
            
            echo '</div>';
            echo '</div>';
        }
    }
}

// Fetch user reaction for a specific comment (example implementation)
function getUserReaction($commentId) {
    global $conn;
    // Replace with your actual SQL query to fetch user reaction
    $sql = "SELECT action FROM recipe_comment_likes WHERE comment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $commentId);
    $stmt->execute();
    $stmt->bind_result($action);
    $stmt->fetch();
    $stmt->close();
    return $action;
}

// Count all replies including nested ones
function countReplies($comments, $commentId) {
    $count = 0;
    foreach ($comments as $comment) {
        if ($comment['parent_id'] == $commentId) {
            $count++;
            $count += countReplies($comments, $comment['id']);
        }
    }
    return $count;
}

?>

<?php
$limitedDescription = "Delicious recipe for this page.";


$page_url_canonical = "https://www.candybird.co.za/recipe?id=".$recipe_id;
$title_og = 'Recipe - CandyBird';
$page_url_og = "https://www.candybird.co.za/recipe?id=".$recipe_id;
$description_og = htmlspecialchars($limitedDescription, ENT_QUOTES, 'UTF-8');
$description_meta = htmlspecialchars($limitedDescription, ENT_QUOTES, 'UTF-8');
?>

<!-- Canonical URL to Avoid Duplicate Content Issues -->
<link rel="canonical" href="<?=$page_url_canonical?>">

<!-- Meta Description Tag -->
<meta name="description" content="<?=$description_meta?>">

<!-- Open Graph Meta Tags for Facebook, Twitter, etc. -->
<meta property="og:title" content="<?=$title_og?>">
<meta property="og:description" content="<?=$description_og?>">
<meta property="og:image" content="<?=$image_url_og?>">
<meta property="og:url" content="<?=$page_url_og?>">
<meta property="og:type" content="website">

<title>Recipes - CandyBird</title>


<style>
    .comment {
/*        margin-bottom: 10px;*/
/*        padding: 10px;*/
        // border: 1px solid #ccc;
/*        border-radius: 5px;*/
    }

    .reply-form {
       // border: 1px solid #ccc; 
    }

    .replies {
        display: none;
    }

    .like-comment.clicked,
    .dislike-comment.clicked {
        font-weight: bold;
        color: blue; /* or any other color to indicate it is clicked */
    }

</style>


<?php
include 'page_menues.php';
?>


<!-- product tab start -->
<section class="blog-section py-80">
  <div class="container">
    <div class="row">
      <div class="col-lg-9 mb-30">
        <div class="comment-area">
            <h2 class="comment-heading"><?=$comment_count?> Comments</h2>
            <div class="review-wrapper">
                <?php 
                        
                    // Assuming $comments, $recipe_id, and $userId are defined appropriately
                    display_comments($comments, $recipe_id, $userId);

                ?>
            </div>
        </div>
      </div>
    </div>
  </div>
</section>


<h3>Add a Comment</h3>
<form action="add_comment.php" method="post">
    <textarea name="comment_text" required></textarea><br>
    <input type="hidden" name="recipe_id" value="<?php echo $recipe_id; ?>">
    <input type="hidden" name="parent_id" value="">
    <?php if ($userId !== null): ?>
        <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
    <?php else: ?>
        <input type="text" name="name" placeholder="Your Name" required><br>
        <input type="email" name="email" placeholder="Your Email" required><br>
    <?php endif; ?>
    <input type="submit" value="Submit">
</form>




<!-- Include jQuery library -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>


<script>

$(document).ready(function() {

    $('.toggle-replies').click(function(e) {
        e.preventDefault();
        var commentId = $(this).data('comment-id');
        $('[data-comment-id="' + commentId + '"] .replies').toggle();
    });

    $('.toggle-reply-form').click(function(e) {
        e.preventDefault();
        var commentId = $(this).data('comment-id');
        $('[data-comment-id="' + commentId + '"] .reply-form').toggle();
    });

    // Handle like and dislike actions
    $(document).on('click', '.like-comment, .dislike-comment', function(e) {
        e.preventDefault();
        var commentId = $(this).data('comment-id');
        var action = $(this).hasClass('like-comment') ? 'like' : 'dislike';
        var $button = $(this);
        var $otherButton = action === 'like' ? $button.siblings('.dislike-comment') : $button.siblings('.like-comment');

        var data = { comment_id: commentId, action: action };
        if (!<?php echo isset($userId) ? 'true' : 'false'; ?>) {
            data.guest_identifier = '<?php echo $guestIdentifier; ?>'; // Use guest identifier
        }

        $.post('like_comment.php', data, function(response) {
            if (response.success) {
                // Update likes/dislikes count display
                $button.closest('.comment').find('.likes-count').text(response.likes);
                $button.closest('.comment').find('.dislikes-count').text(response.dislikes);

                // Update button appearance
                if (action === 'like') {
                    $button.addClass('clicked');
                    $otherButton.removeClass('clicked');
                } else {
                    $button.addClass('clicked');
                    $otherButton.removeClass('clicked');
                }
            } else {
                alert(response.error);
            }
        }, 'json');
    });

    // Initial load: highlight the buttons based on user reactions
    $('.comment').each(function() {
        var commentId = $(this).data('comment-id');
        var userReaction = $(this).data('user-reaction');

        if (userReaction === 'like') {
            $(this).find('.like-comment').addClass('clicked');
        } else if (userReaction === 'dislike') {
            $(this).find('.dislike-comment').addClass('clicked');
        }
    });
});

</script>

<?php
include 'footer.php';
?>