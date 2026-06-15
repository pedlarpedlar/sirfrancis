<?php
include 'session_logins.php';
include 'header.php';

// Fetch blog ID from URL (replace 'your_blog_id_from_url' with your actual code)
$blogIdFromURL = $_GET['id'];

// Prepare the SQL query with a parameter placeholder
$blogsQuery = "SELECT b.*, c.comment
               FROM blogs b
               LEFT JOIN blog_comments c ON b.id = c.blog_id
               WHERE b.id = ?";
$statement = $conn->prepare($blogsQuery);

// Bind the parameter
$statement->bind_param("i", $blogIdFromURL);

// Execute the query
$statement->execute();

// Get the result set
$blogsResult = $statement->get_result();

$blog_tags = "";

// Check if the query was successful
if ($blogsResult) {
    // Fetch data from the 'blogs' table along with comments
    while ($row = $blogsResult->fetch_assoc()) {
        $blogId = $row['id'];

        $blog_url = "https://www.fishgelatine.co.za/v2/blog?id=".$blogId;

        $title = $row['title'];
        $content = $row['content'];
        $authorId = $row['author_id'];
        $author = $row['author'];
        $imageUrl = !empty($row['image_url']) ? $row['image_url'] : "assets/img/blog-post/large-blog.jpg";

        $image_url_encoded = str_replace(' ', '%20', $imageUrl);

        $image_url_og = /*"https://www.fishgelatine.co.za/v2/" .*/ $image_url_encoded;

        $tags = $row['tags'];

        // Explode the comma-separated tags into an array
        $tagsArray = explode(',', $tags);

        // Output the tags with <a> tags
        $tagsCount = count($tagsArray);
        foreach ($tagsArray as $key => $tag) {
            $tag = trim($tag); // Remove leading/trailing spaces
            $blog_tags .= "<li><a href='#'>$tag";

            // Add a comma if it's not the last tag
            if ($key < $tagsCount - 1) {
                $blog_tags .= ", ";
            }

            $blog_tags .= "</a></li>";
        }

        // Format the display date
        $displayDate = date_create($row['display_date']);
        $formattedDate = date_format($displayDate, 'M d, Y');
        $createdAt = $row['created_at'];
    }

} else {
    echo "Error fetching data: " . $conn->error;
}


$page_url_canonical = "https://www.fishgelatine.co.za/v2/blog";
$title_og = $title.' - Sir Francis';
$page_url_og = "https://www.fishgelatine.co.za/v2/blog"
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

<title><?=$title?> - Sir Francis</title>
<?php
include 'page_menues.php';
?>


<!-- product tab start -->
<section class="blog-section pt-80 pb-80">
  <div class="container">
    <div class="row">
      <div class="col-12 col-lg-10 mx-auto">
        <div class="blog-posts">
          <div class="single-blog-post blog-grid-post">
            <div class="blog-post-media">
              <div class="blog-image single-blog">
                <a href="#"
                  ><img
                    class="object-fit-none"
                    src="<?=$imageUrl?>"
                    alt="blog-thumb-nail"
                /></a>
              </div>
            </div>
            <div class="blog-post-content-inner">
              <h4 class="blog-title"><?=$title?></h4>
              <ul class="blog-page-meta">
                <li>
                  <a href="#"><i class="ion-person"></i> <?=$author?></a>
                </li>
                <li>
                  <a href="#"><i class="ion-calendar"></i> <?=$formattedDate?></a>
                </li>
              </ul>
            </div>

            <div class="single-post-content">
              <?=$content?>
              <!--  use this for a nice quote style --> <!-- <p class="quate-speech"></p> -->
            </div>
          </div>
          <!-- single blog post -->
        </div>
        <div class="blog-single-tags-share d-sm-flex justify-content-between">
          <div class="blog-single-tags d-flex">
            <span class="title">Tags: </span>
            <ul class="tag-list">
              <?=$blog_tags?>
            </ul>
          </div>
          <div class="blog-single-share d-flex">
            <span class="title">Share:</span>
            <ul class="social">
              <li>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?=$blog_url?>" target="_blank" rel="noopener noreferrer"><i class="ion-social-facebook"></i></a>
              </li>
              <li>
                <a href="https://twitter.com/intent/tweet?url=<?=$blog_url?>&text=Check out this amazing product!" target="_blank" rel="noopener noreferrer"><i class="ion-social-twitter"></i></a>
              </li>
              <li>
                <a target="_blank" href="https://www.pinterest.com/pin/create/button/"
                   data-pin-do="buttonBookmark"
                   data-pin-custom="true"
                   data-pin-save="true"
                   data-pin-url="<?=$blog_url?>"
                   data-pin-media="<?=$image_url_og?>"
                   >
                   <i class="ion-social-pinterest"></i>
                  </a>
              </li>
            </ul>
          </div>
        </div>
        <?php
        // Query to fetch random 3 or 4 blogs (where id is not equal to the current post)
$relatedBlogsQuery = "SELECT * FROM blogs WHERE id != ? ORDER BY RAND() LIMIT 3";
$relatedBlogsStatement = $conn->prepare($relatedBlogsQuery);
$relatedBlogsStatement->bind_param("i", $blogIdFromURL);
$relatedBlogsStatement->execute();
$relatedBlogsResult = $relatedBlogsStatement->get_result();

// Check if the query was successful
if ($relatedBlogsResult) {
    // Echo the HTML structure
    echo '<div class="blog-related-post">
            <div class="row">
              <div class="col-md-12 text-center">
                <div class="section-title underline-shape">
                  <h2>Related Posts</h2>
                </div>
              </div>
            </div>
            <div class="row">';
    
    // Fetch data from the related blogs
    while ($relatedBlog = $relatedBlogsResult->fetch_assoc()) {
        $relatedBlogId = $relatedBlog['id'];
        $relatedBlogTitle = $relatedBlog['title'];
        $relatedBlogImageUrl = !empty($relatedBlog['image_url']) ? $relatedBlog['image_url'] : "assets/img/blog-post/1.png";
        $relatedBlogDisplayDate = date_format(date_create($relatedBlog['display_date']), 'M d, Y');
        $relatedBlogAuthor = $relatedBlog['author'];
        $relatedBlogCreatedAt = date_format(date_create($relatedBlog['created_at']), 'M d, Y');

        // Echo each related blog's HTML structure
        echo '<div class="col-md-4 mb-4 mb-md-0">
                <div class="blog-post-media">
                  <div class="blog-image single-blog">
                    <a href="blog.php?id='.$relatedBlogId.'"><img src="' . $relatedBlogImageUrl . '" alt="blog"></a>
                  </div>
                </div>
                <div class="blog-post-content">
                  <h3 class="title mb-15">
                    <a href="blog.php?id=' . $relatedBlogId . '">' . $relatedBlogTitle . '</a>
                  </h3>
                  <p class="sub-title">
                    Posted by
                    <a class="theme-color d-inline-block mx-1" href="#">' . $relatedBlogAuthor . '</a>
                    ' . $relatedBlogCreatedAt . '
                  </p>
                </div>
              </div>';
    }

    // Close the HTML structure
    echo '</div>
          </div>';
    
    // Free the result set
    $relatedBlogsResult->free();
} else {
    echo "Error fetching related blogs: " . $conn->error;
}

// Close the statement
$relatedBlogsStatement->close();
// Query to fetch comments for the current blog
$commentsQuery = "SELECT * FROM blog_comments WHERE blog_id = ? AND status = 'approved' ORDER BY created_at DESC";
$commentsStatement = $conn->prepare($commentsQuery);
$commentsStatement->bind_param("i", $blogIdFromURL);
$commentsStatement->execute();
$commentsResult = $commentsStatement->get_result();

// Check if the query was successful
if ($commentsResult) {
    // Echo the HTML structure for comments
    echo '<div class="comment-area">
            <h2 class="comment-heading">' . $commentsResult->num_rows . ' Comments</h2>
            <div class="review-wrapper">';

    // Fetch data from the comments
    $imageIndex = 1; // Variable to track the image index
    while ($comment = $commentsResult->fetch_assoc()) {
        $commentId = $comment['id'];
        $commentAuthor = $comment['name'];
        $commentDate = date_format(date_create($comment['created_at']), 'M d, Y \a\t g:i a');
        $commentContent = $comment['comment'];
        $commentUserId = $comment['user_id'];
        $commentGuestIdentifier = $comment['guest_identifier'];

        // Choose the image URL based on the image index
        $imageURL = "assets/img/testimonial-image/" . ($imageIndex % 2 + 1) . ".png";

        // Echo each comment's HTML structure
        echo '<div class="single-review" data-comment-id="' . $commentId . '">
                <div class="review-img">
                  <img src="' . $imageURL . '" alt="User Image" />
                </div>
                <div class="review-content">
                  <div class="review-top-wrap">
                    <div class="review-left">
                      <div class="review-name">
                        <h4>' . $commentAuthor . '</h4>
                        <span class="date">' . $commentDate . '</span>
                      </div>
                      </div>';

                      // Check if the current user is the owner of the comment
                      if (($userId && $userId == $commentUserId) || ($guestIdentifier && $guestIdentifier == $commentGuestIdentifier)) {
                          echo '<div class="review-left">
                                  <a href="#commentContent" class="comment-edit-link">Edit</a>
                                </div>';
                      }

                  echo '</div>
                  <div class="review-bottom">
                    <p>' . $commentContent . '</p>
                  </div>
                </div>
              </div>';

        // Increment the image index for the next comment
        $imageIndex++;
    }

    // Close the HTML structure for comments
    echo '</div>
          </div>';
    
    // Free the result set
    $commentsResult->free();
} else {
    echo "Error fetching comments: " . $conn->error;
}

// Close the statement
$commentsStatement->close();

?>
        <div class="blog-comment-form">
          <form id="commentForm" method="post" action="submit_comment.php">
            <input type="hidden" name="blog_id" value="<?=$blogIdFromURL?>">
            <input id="edit_comment" type="hidden" name="comment_id" value="">
            <h2 class="comment-heading">Leave a Reply</h2>
            <p>
              Your email address will not be published. Required fields are marked
              *
            </p>
            <div class="row">
              <div class="col-md-12">
                <div class="single-form">
                  <label>Your Comment:</label>
                  <textarea id="commentContent" placeholder="Write a comment" name="comment" required ></textarea>
                </div>
              </div>
              <div class="col-md-4">
                <div class="single-form">
                  <label>Name:</label>
                  <input type="text" placeholder="Name" name="name" required />
                </div>
              </div>
              <div class="col-md-4">
                <div class="single-form">
                  <label>Email:</label>
                  <input type="email" placeholder="Email" name="email" required />
                </div>
              </div>
              <div class="col-md-4">
                <div class="single-form">
                  <label>Website:</label>
                  <input type="text" placeholder="Website" name="website" />
                </div>
              </div>
              <div class="col-md-12">
                <div class="single-form">
                  <input type="submit" value="Submit" />
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- product tab end -->

<!-- Include jQuery library -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

<script>
    $(document).ready(function () {

       // Add a click event listener to the edit link
        $('.comment-edit-link').click(function (event) {

            // Get the parent comment block
            var currentReview = $(this).closest('.single-review');

            // Get the comment_id and comment_text from the current review
            var commentId = currentReview.data('comment-id');
            var commentText = currentReview.find('.review-bottom p').text();

            // Update the input and textarea values
            $('#edit_comment').val(commentId);
            $('#commentContent').val(commentText);
        });

        // Handle form submission
        $("#commentForm").submit(function (e) {
            e.preventDefault();

            // Serialize form data
            var formData = $(this).serialize();

            // Make AJAX request
            $.ajax({
                type: "POST",
                url: "submit_comment.php",
                data: formData,
                success: function (response) {
                    // Display response message
                    showNotification(response.success, response.message);
                    $("#commentForm").trigger("reset");;
                },
                error: function (x,y,z) {
                    // Handle error
                    console.log(z);
                }
            });
        });
    });
</script>
<?php
include 'footer.php';
?>