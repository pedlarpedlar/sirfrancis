<?php
// Start or resume the session
session_start();

// Check if the admin_id is set in the session
if (!isset($_SESSION['admin_id'])) {
    // Redirect or handle the case where admin_id is not set
    header("Location: admin_login"); // Redirect to login page, for example
    exit();
}

// Fetch admin_id from the session
$admin_id = $_SESSION['admin_id'];
$initialContent = "";

include 'header.php';
?>

<title>Create a Blog</title>

<style>
    .image-preview {
        max-width: 100px;
        max-height: 100px;
        margin-top: 10px;
    }
</style>


<?php
include 'page_menues.php';
?>

<div class="container mt-5">
  <div class="p-4">
    <h2 class="mb-3">Create a Blog</h2>
    <a href="manage_blogs">Manage Blogs</a>

    <form class="mt-3" action="create_blog.inc.php" method="post" enctype="multipart/form-data">

      <input type="hidden" id="blog_id" name="blog_id" value="">

      <input type="hidden" id="author_id" name="author_id" value="<?=$admin_id?>" required>

      <div class="mb-3">
        <label for="title" class="form-label">Title:</label>
        <input type="text" id="title" name="title" class="form-control" >
      </div>

      <div class="mb-3">
        <label for="tags" class="form-label">Tags (comma separated):</label>
        <input type="text" id="tags" name="tags" class="form-control">
      </div>

      <div class="mb-3">
        <label for="author" class="form-label">Author:</label>
        <input type="text" id="author" name="author" class="form-control" value="Sir Francis" >
      </div>

       <div class="mb-3">
        <label for="image" class="form-label">Blog Image:</label>
        <input type="file" id="image" name="image" class="form-control">
        <small class="form-text text-muted">Upload a high-quality 1290x900 px image to be used as the blog's background image. Leave it blank if not needed.</small>
      </div>

      <div class="mb-3">
        <label for="content" class="form-label">Content:</label>
        <!-- <textarea id="content" name="content" class="form-control" ></textarea> -->
        <textarea id="content" name="content"><?= htmlspecialchars($initialContent) ?></textarea>
      </div>

      <div class="mb-3">
        <label for="display_date" class="form-label">Display Date:</label>
        <input type="date" id="display_date" name="display_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" >
      </div>

        <button type="submit" class="btn btn-primary" id="submit_blog" style="display: none;">Submit</button>
        <button type="submit" class="btn btn-primary" id="update_blog" style="display: none;">Update</button>
        <button type="submit" class="btn btn-primary" id="copy_blog" style="display: none;">Submit</button>

    </form>
  </div>
</div>

<?php
include '../footer.php';
?>

<!-- Include jQuery library -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script src="https://cdn.tiny.cloud/1/krc3t31hewwxmxp9ymcfecueza73p98zly4l51k8zm5ngjy8/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>


<script>
$(document).ready(function() {
    var blogId = <?php echo isset($_GET['id']) ? $_GET['id'] : 0; ?>;

    var urlParams = new URLSearchParams(window.location.search);
    var copyBlog = urlParams.has('copy');

    // Show the appropriate button based on the operation
    if (blogId > 0 && !copyBlog) {
        // Editing an existing blog
        $('#update_blog').show();
        $('#copy_blog').hide();
    } else if (copyBlog) {
        // Copying a blog
        $('#copy_blog').show();
        $('#update_blog').hide();
    } else {
        // Creating a new blog
        $('#submit_blog').show();
    }


    // Fetch blog details using Ajax
    if (blogId > 0) {
        $.ajax({
            url: 'fetch_blog_details.php', // Replace with your actual PHP script to fetch blog details
            type: 'GET',
            data: { id: blogId },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    // Populate form fields with fetched data
                    if (copyBlog) {
                        // Clear the ID to create a new entry if it's a copy
                        $('#blog_id').val('');
                    } else {
                        // Set the ID if it's not a copy (for editing)
                        $('#blog_id').val(response.data.id);
                    }

                    $('#title').val(response.data.title);
                    $('#tags').val(response.data.tags);
                    $('#author').val(response.data.author);
                    $('#content').val(response.data.content);

                    // Assuming 'image' is an <img> tag displaying the image
                    // If 'image' is an <input type="file">, you might not set its value due to security reasons
                    // $('#image').attr('src', response.data.image); // Update the 'src' attribute of the image tag

                    // Assuming 'display_date' is an input field of type date
                    $('#display_date').val(response.data.display_date);
                } else {
                    console.error('Error fetching blog details');
                }
            },
            error: function () {
                console.error('Ajax request failed');
            }
        });
    }

});
</script>


<script>
document.addEventListener("DOMContentLoaded", function() {
    tinymce.init({
      selector: '#content',
      height: 300, // Adjust the desired height of the editor
      plugins: [
          'advlist autolink lists link image charmap print preview anchor',
          'searchreplace visualblocks code fullscreen',
          'insertdatetime media table paste code help wordcount'
      ],
      toolbar: 'undo redo | formatselect | ' +
          'bold italic backcolor | alignleft aligncenter ' +
          'alignright alignjustify | bullist numlist outdent indent | ' +
          'removeformat | help',
      menubar: 'file edit view insert format tools table help'
    });
});
</script>