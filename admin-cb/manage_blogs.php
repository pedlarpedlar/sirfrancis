<?php
// Start or resume the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    $redirect_url = "manage_blogs";
    header("Location: admin_login?redirect=" . urlencode($redirect_url)); // Redirect to the login page
    exit(); // Stop further execution
}

// Fetch admin_id from the session
$admin_id = $_SESSION['admin_id'];

// Include header and page menus
include 'header.php';
include 'dbh.inc.php';

?>

<title>Create a Blog</title>

<?php
include 'page_menues.php';
?>


    <div class="container mt-5">
        <h2 class="mb-4">Manage Blogs</h2>

        <!-- Table to Display Blogs -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Display Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
                <tbody>
                    <!-- Fetch blogs data from the database and loop through them -->
                    <?php
                    // Query to retrieve blogs
                    $sql = "SELECT id, title, author, display_date FROM blogs ORDER BY id DESC";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                <td>{$row['id']}</td>
                                <td>{$row['title']}</td>
                                <td>{$row['author']}</td>
                                <td>{$row['display_date']}</td>
                                <td>
                                    <a href='create_blog?id={$row['id']}' class='btn btn-primary btn-sm mr-2'><i class='fas fa-edit'></i> Edit</a>
                                    <a href='create_blog?id={$row['id']}&copy=true' class='btn btn-primary btn-sm copy-blog' data-id='{$row['id']}'><i class='fas fa-copy'></i> Copy</a>
                                    <a href='https://www.candybird.co.za/blog?id={$row['id']}' class='btn btn-primary btn-sm view-blog' target='_blank'><i class='fas fa-eye'></i> View</a>
                                    <a href='#' class='btn btn-danger btn-sm delete-blog' data-id='{$row['id']}' data-toggle='modal' data-target='#confirmDeleteModal'><i class='fas fa-trash'></i> Delete</a>
                                </td>
                            </tr>";

                        }
                    } else {
                        echo "<tr><td colspan='5'>No blogs found. Click <a href='create_blog'>here</a> to create one.</td></tr>";
                    }

                    $conn->close();
                    ?>
                </tbody>
        </table>
    </div>
<!-- Modal for Delete Confirmation -->
<div class="modal" id="confirmDeleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this blog?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>
<!-- Include jQuery library -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

<script>
    $(document).ready(function () {
        // Handle click event on Delete button
        $('.delete-blog').click(function () {
            // Get the blog ID from the data-id attribute
            var blogId = $(this).data('id');

            // Update the confirmation modal's Delete button href attribute
            $('#confirmDeleteBtn').attr('data-id', blogId);

            // Show the confirmation modal
            $('#confirmDeleteModal').modal('show');
        });

        // Handle click event on the Delete button inside the modal
        $('#confirmDeleteBtn').click(function () {
            // Get the blog ID from the modal's Delete button data-id attribute
            var blogId = $(this).attr('data-id');

            // Make an Ajax request to delete the blog
            $.ajax({
                url: 'delete_blog.inc.php?id=' + blogId, // Adjust the URL to your server-side script
                method: 'GET',
                success: function (response) {
                    // Handle success (e.g., reload the page)
                    location.reload();
                },
                error: function (error) {
                    // Handle error
                    console.error('Delete request failed:', error);
                }
            });

            // Hide the confirmation modal
            $('#confirmDeleteModal').modal('hide');
        });
    });
</script>

<?php
include '../footer.php';
?>
