<?php
session_start();
require 'db.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user parameter is set
if (!isset($_GET['user'])) {
    echo "User not specified.";
    exit();
}

$username = $_GET['user'];

// Fetch user details from the database
$stmt = $pdo->prepare("SELECT * FROM admin1 WHERE user = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found.";
    exit();
}

// Assign user_id from fetched user details
$user_id = $user['id'];

// Fetch user's images with location, uploaded_at fields
$stmt = $pdo->prepare("SELECT image_path, location, uploaded_at FROM uploads WHERE user_id = ? ORDER BY uploaded_at DESC");
$stmt->execute([$user_id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($username); ?>'s Gallery</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/itonatocuescano/CSS/fonts.css">
    <style>
        body {
            background: linear-gradient(to right, #B8860B, #000);
            color: white;
        }
        .calendar {
            background: white;
            padding: 10px;
            border-radius: 10px;
            text-align: center;
            position: absolute;
            top: 10px;
            right: 40px;
            width: 150px;
            color: #000;
        }
        .calendar input {
            font-size: 14px;
            padding: 3px;
            width: 100%;
        }
        .image-container {
            position: relative;
            display: inline-block;
            width: 100%;
            border-radius: 10px;
            border: 1px solid black;
            cursor: pointer;
        }
        .image-container img {
            width: 100%;
            height: auto;
            border-radius: 10px;
        }
        .image-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            font-size: 14px;
            padding: 8px;
            text-align: center;
            border-radius: 10px;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            justify-content: center;
            align-items: center;
        }
        .modal img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 10px;
        }
        .modal .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 30px;
            color: white;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            cursor: pointer;
        }
    </style>
</head>
<body class="flex">
    <!-- Sidebar Navigation -->
    <aside class="w-64 bg-stone-900 text-white min-h-screen p-6">
        <h2 class="text-2xl font-bold font-mono mb-9">Admin Dashboard</h2>
        <ul>
            <li class="mt-3"><a href="Admin_dashboard.php" class="block text-xl rounded p-2 hover:bg-yellow-700">Home</a></li>
            <li class="mt-6"><a href="/itonatocuescano/about/Company_mission.php" class="block text-xl rounded p-2 hover:bg-yellow-700">Our Mission</a></li>
            <li class="mt-6"><a href="/itonatocuescano/about/Company_vision.php" class="block text-xl rounded p-2 hover:bg-yellow-700">Our Vision</a></li>
            <li class="mt-6"><a href="/itonatocuescano/about/Company_goal.php" class="block text-xl rounded p-2 hover:bg-yellow-700">Our Goal</a></li>
            <li class="mt-6"><a href="logout.php" class="block text-xl rounded p-2 hover:bg-red-600">Logout</a></li>
        </ul>
    </aside>
    
    <!-- Main Content -->
    <main class="flex-1 p-8">
        <h1 class="text-2xl font-semibold mb-6"><?php echo htmlspecialchars($username); ?>'s Gallery</h1>

        <!-- Calendar -->
        <div class="calendar">
            <h2>Select a Date</h2>
            <input type="date" id="date-picker">
        </div>

        <!-- Gallery Section -->
        <div class="bg-white p-6 rounded shadow text-black">
            <h2 class="text-xl font-semibold mb-4">Uploaded Images</h2>

            <?php if (!empty($images)): ?>
                <div class="grid grid-cols-2 gap-10">
                    <?php foreach (['In', 'Out', 'Overtime', 'Undertime'] as $status): ?>
                        <div>
                            <h3 class="text-lg font-bold mb-3"><?php echo $status; ?></h3>
                            <div class="grid grid-cols-1 gap-4">
                                <?php foreach ($images as $image): ?>
                                    <?php if ($image['location'] === $status): ?>
                                        <div class="image-container" data-uploaded-at="<?php echo explode(' ', $image['uploaded_at'])[0]; ?>">
                                            <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                                                 alt="<?php echo $status; ?> Image"
                                                 onclick="openModal('<?php echo htmlspecialchars($image['image_path']); ?>')">
                                            <div class="image-overlay"><?php echo $image['uploaded_at']; ?></div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-600">No images uploaded by <?php echo htmlspecialchars($username); ?>.</p>
            <?php endif; ?>
        </div>
    </main>

    <!-- Image Modal -->
    <div class="modal" id="modal">
        <button class="close-btn" onclick="closeModal()">×</button>
        <img id="modal-image" src="" alt="Full Image">
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const datePicker = document.getElementById('date-picker');
            const today = new Date().toISOString().split('T')[0]; 
            datePicker.value = today; 

            // Filter images on date change
            datePicker.addEventListener('change', function() {
                const selectedDate = this.value;
                document.querySelectorAll('.image-container').forEach(image => {
                    const imageDate = image.getAttribute('data-uploaded-at');
                    image.style.display = (imageDate === selectedDate) ? 'block' : 'none';
                });
            });

            // Initialize the filter on page load
            datePicker.dispatchEvent(new Event('change'));
        });

        function openModal(imagePath) {
            document.getElementById('modal').style.display = 'flex';
            document.getElementById('modal-image').src = imagePath;
        }
        function closeModal() {
            document.getElementById('modal').style.display = 'none';
        }
    </script>
</body>
</html>
