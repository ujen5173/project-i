<?php
session_start();
require_once '../db/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'host') {
    header("Location: /stayhaven/login.php");
    exit();
}

$error = '';
$success = '';
$upload_dir = '../uploads/listings/';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $image_url = null;
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_type = $_FILES['image']['type'];
        $file_size = $_FILES['image']['size'];
        
        if (in_array($file_type, ['image/jpeg', 'image/png', 'image/jpg'])) {
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $unique_filename = uniqid('listing_') . '.' . $file_extension;
            $upload_path = $upload_dir . $unique_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_url = '/uploads/listings/' . $unique_filename;
            }
        }
    }
    
    $sql = "INSERT INTO listings (host_id, title, description, room_type, max_guests, price, location, image_url) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssidss", 
        $_SESSION['user_id'],
        $_POST['title'],
        $_POST['description'],
        $_POST['room_type'],
        $_POST['max_guests'],
        $_POST['price'],
        $_POST['location'],
        $image_url
    );
    
    if ($stmt->execute()) {
        $listingId = $stmt->insert_id;
        
        if (!empty($_POST['amenities'])) {
            $amenityStmt = $conn->prepare("INSERT INTO listing_amenities (listing_id, amenity_id) VALUES (?, ?)");
            foreach (array_filter(explode(',', $_POST['amenities'])) as $amenityId) {
                if (is_numeric($amenityId)) {
                    $amenityStmt->bind_param("ii", $listingId, $amenityId);
                    $amenityStmt->execute();
                }
            }
        }
        
        $success = "Listing added successfully!";
    } else {
        $error = "Error creating listing";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add New Listing - StayHaven</title>
  <script src="https://cdn.tailwindcss.com"></script>

  <link rel="stylesheet" href="../css/styles.css">
  <link rel="stylesheet" href="./dashboard.css">

</head>

<body class="bg-gray-50">
  <div class="flex h-screen">
    <aside class="w-64 bg-white border-r border-gray-200 px-4 py-6">
      <div class="flex items-center mb-8">
        <a href="/stayhaven/index.php">

          <h1 class="text-2xl font-bold text-red-600">StayHaven</h1>
        </a>
      </div>
      <nav>
        <ul class="space-y-2">
          <li>
            <a href="index.php" class="sidebar-link flex  items-center px-4 py-3 text-gray-700 rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" style="margin-right: 10px;" width="18" height="20"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" class="lucide lucide-layout-dashboard">
                <rect width="7" height="9" x="3" y="3" rx="1" />
                <rect width="7" height="5" x="14" y="3" rx="1" />
                <rect width="7" height="9" x="14" y="12" rx="1" />
                <rect width="7" height="5" x="3" y="16" rx="1" />
              </svg>
              Dashboard
            </a>
          </li>
          <li>
            <a href="add_listing.php" class="sidebar-link active flex items-center px-4 py-3 text-gray-700 rounded-lg">
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Add New Listing
            </a>
          </li>
          <li>
            <a href="#" class="sidebar-link flex items-center px-4 py-3 text-gray-700 rounded-lg">
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
              Bookings
            </a>
          </li>
          <li>
            <a href="logout.php" class="sidebar-link flex items-center px-4 py-3 text-gray-700 rounded-lg">
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
              Logout
            </a>
          </li>
        </ul>
      </nav>
    </aside>

    <main class="flex-1 overflow-y-auto">
      <div class="bg-white border-b border-gray-200 px-8 py-4">
        <div class="flex justify-end items-center">
          <div class="flex items-center">
            <span class="text-gray-700 mr-4"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <img
              src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_name']); ?>&background=ef4444&color=fff"
              alt="Profile" class="w-8 h-8 rounded-full">
          </div>
        </div>
      </div>

      <div class="container p-6">
        <h1 class="page-title">Add New Listing</h1>

        <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="form-container">
          <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-grid">
              <div style="display: flex; gap: 10px;">
                <div class="form-group form-group-full" style="flex:1 ">
                  <label class="form-label">Title</label>
                  <input type="text" name="title" required class="form-input">
                </div>

                <div class="form-group" style="flex:1 ">
                  <label class="form-label">Room Type</label>
                  <select name="room_type" style="height: 40px;" required class=" form-input">
                    <option value="">Select type</option>
                    <option value="Entire place">Entire place</option>
                    <option value="Private room">Private room</option>
                    <option value="Shared room">Shared room</option>
                  </select>
                </div>
              </div>

              <div class="form-group form-group-full">
                <label class="form-label">Description</label>
                <textarea name="description" required class="form-input"></textarea>
              </div>



              <div class="form-group">
                <label class="form-label">Maximum Guests</label>
                <input type="number" name="max_guests" required min="1" class="form-input">
              </div>
              <div style="display: flex; gap: 10px;">

                <div class="form-group" style="flex:1 ">
                  <label class="form-label">Price per Night ($)</label>
                  <input type="number" name="price" required min="0" step="0.01" class="form-input">
                </div>

                <div class="form-group" style="flex:1 ">
                  <label class="form-label">Location</label>
                  <input type="text" name="location" required class="form-input">
                </div>
              </div>

              <div class="form-group form-group-full">
                <label class="form-label">Upload Images</label>
                <input type="file" name="image" multiple accept="image/*" class="form-input"
                  onchange="previewImages(event)">
                <div id="imagePreview" class="image-preview-container"></div>
              </div>

              <div class="form-group form-group-full">
                <label class="form-label">Amenities</label>
                <input type="text" name="amenities" class="form-input"
                  placeholder="WiFi, TV, Kitchen (comma-separated)">
              </div>

              <div class="form-group form-group-full">
                <button type="submit" class="btn ">Add Listing</button>
              </div>
            </div>
          </form>
        </div>
      </div>
  </div>
  </main>

  <script>
  // Simple image preview function
  function previewImages(event) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';

    const files = event.target.files;
    for (let i = 0; i < files.length; i++) {
      const file = files[i];
      if (file.type.startsWith('image/')) {
        const img = document.createElement('img');
        img.classList.add('preview-image');
        img.file = file;
        preview.appendChild(img);

        const reader = new FileReader();
        reader.onload = (function(aImg) {
          return function(e) {
            aImg.src = e.target.result;
          };
        })(img);
        reader.readAsDataURL(file);
      }
    }
  }
  </script>
</body>

</html>