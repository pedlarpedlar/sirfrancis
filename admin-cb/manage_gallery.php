<?php
include '../session_logins.php';

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    $redirect_url = "manage_gallery";
    header("Location: admin_login?redirect=" . urlencode($redirect_url)); // Redirect to the login page
    exit(); // Stop further execution
}

// Ensure the uploads directory exists
$uploadDir = '../uploads/products/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}


// Handle image upload
// if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {

//     // $successMessage = "We Are Here";
//     // echo json_encode(['status' => 'success', 'message' => $successMessage]);
//     // exit();


//     $timestamp = time();
//     foreach ($_FILES['images']['name'] as $key => $name) {
//         $extension = pathinfo($name, PATHINFO_EXTENSION);
//         $newFileName = $timestamp . '_' . $key . '.' . $extension;
//         $targetFile = $uploadDir . $newFileName;
//         if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
//             if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $targetFile)) {
//                 // File uploaded successfully
//                 $successMessage = "File uploaded successfully";
//                 // header('Content-Type: application/json');
//                 // echo json_encode(['status' => 'success', 'message' => $successMessage]);
//                 // exit();
//             } else {
//                 // Handle move_uploaded_file failure
//                 $errorMessage = "Failed to move uploaded file: $name";
//                 // header('Content-Type: application/json');
//                 // echo json_encode(['status' => 'error', 'message' => $errorMessage]);
//                 // exit();
//             }
//         } else {
//             // Handle upload error
//             $uploadError = $_FILES['images']['error'][$key];
//             $errorMessage = "Upload error ($uploadError) occurred for file: $name";
//             // header('Content-Type: application/json');
//             // echo json_encode(['status' => 'error', 'message' => $errorMessage]);
//             // exit();
//         }
//     }
// }


// Get list of images
$images = glob($uploadDir . '*.*');
// Sort images in reverse order to show the latest images first
rsort($images);
$imagesPerPage = 12;
$totalImages = count($images);
$totalPages = ceil($totalImages / $imagesPerPage);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$startIndex = ($page - 1) * $imagesPerPage;
$imagesToDisplay = array_slice($images, $startIndex, $imagesPerPage);

// Get the full URL of the site
$siteUrl = 'https://www.candybird.co.za/uploads/products/';


include 'header.php';
?>

<title>Manage Gallery</title>

    <style>
        .gallery img {
            width: 100%;
            height: auto;
        }
    </style>

    <style>
        #progressBarContainer {
            width: 200pxx;
            background-color: #f3f3f3;
            border: 1px solid #ccc;
            margin-top: 10px;
        }
        #progressBar {
            height: 20px;
            background-color: #4caf50;
            width: 0;
            text-align: center;
            color: white;
        }
    </style>


<?php

include 'page_menues.php';

?>

<div class="container">
    <h1 class="mt-5">Manage Image Gallery</h1>
    
    <!-- Upload Form -->
    <form id="uploadForm" enctype="multipart/form-data" class="mb-4">
        <div class="form-group">
            <label for="images">Select Images to Upload:</label>
            <input type="file" name="images[]" id="images" class="form-control" multiple required>
            <div id="progressBarContainer">
                <div id="progressBar">0%</div>
            </div>
            <button type="button" onclick="uploadFiles()" class="btn btn-primary">Upload Images</button>
            <div id="progress"></div>
        </div>
    </form>

    <script>
        async function uploadFiles() {
            const form = document.getElementById('uploadForm');
            const formData = new FormData(form);
            const files = formData.getAll('images[]');
            const progressDiv = document.getElementById('progress');
            const progressBar = document.getElementById('progressBar');
            progressDiv.innerHTML = `<p> Uploading... </p>`;

            let uploadedFiles = 0;

            for (const file of files) {
                const data = new FormData();
                data.append('image', file);

                try {
                    const response = await fetch('upload_gallery_images.php', {
                        method: 'POST',
                        body: data
                    });

                    const result = await response.json();
                    if (response.ok) {
                        progressDiv.innerHTML += `<p>${file.name} uploaded successfully!</p>`;
                    } else {
                        progressDiv.innerHTML += `<p>Failed to upload ${file.name}: ${result.message}</p>`;
                    }
                } catch (error) {
                    progressDiv.innerHTML += `<p>Error uploading ${file.name}: ${error.message}</p>`;
                }

                // Update the progress bar
                uploadedFiles++;
                const progressPercentage = Math.round((uploadedFiles / files.length) * 100);
                progressBar.style.width = `${progressPercentage}%`;
                progressBar.innerText = `${progressPercentage}%`;
            }
        }
    </script>

    <!-- Gallery -->
    <div class="row gallery">
        <?php foreach ($imagesToDisplay as $image): 
            $imageUrl = $siteUrl . basename($image);
            ?>
            <div class="col-md-3 mb-4">
                <img src="<?php echo $image; ?>" class="img-thumbnail copy-btn" data-url="<?php echo $imageUrl; ?>">
                <div class="image-info mt-2">
                    <p>Name: <?php echo basename($image); ?> <span class="btn btn-light delete-btn" data-url="<?php echo $imageUrl; ?>"><i class="fas fa-trash"></i></span></p>
                    <p>URL: 
                        <span class="btn btn-light copy-btn" data-url="<?php echo $imageUrl; ?>"><i class="fas fa-clone"></i></span>
                        <input type="text" class="form-control" value="<?php echo $imageUrl; ?>" readonly>
                    </p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <!-- Pagination -->
    <nav>
        <ul class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

<!-- Include jQuery library -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

<script>
$(document).ready(function () {

    // Delete button click event
    $('.delete-btn').on('click', function() {
        var imageUrl = $(this).data('url');
        var imageWrapper = $(this);

        // Confirm deletion
        if (confirm("Are you sure you want to delete this image?")) {
            // AJAX request to delete image
            $.ajax({
                url: 'delete_image.php',
                method: 'POST',
                data: { imageUrl: imageUrl },
                success: function(response) {
                    // Handle success response
                    // Optionally remove the image container from DOM if needed
                    imageWrapper.closest('.col-md-3').remove();
                    showNotification(true, 'Image deleted from gallery!');

                },
                error: function(error) {
                    // Handle error response
                    // alert('Error deleting image.');
                }
            });
        }
    });

    $('body').on('click', '.copy-btn', function() {
        var url = $(this).data('url');
        var tempInput = $("<input>");
        $("body").append(tempInput);
        tempInput.val(url).select();
        document.execCommand("copy");
        tempInput.remove();
        showNotification(true, 'URL copied to clipboard!');
    });

});



</script>

<?php
include '../footer.php';
?>