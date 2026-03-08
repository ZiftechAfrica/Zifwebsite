<?php
include 'config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

$settings_message = '';
$blog_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['settings_type'])) {
        $type = $_POST['settings_type'];
        if ($type === 'paystack') {
            $public = trim($_POST['paystack_public_key'] ?? '');
            $secret = trim($_POST['paystack_secret_key'] ?? '');
            $stmt = $conn->prepare("INSERT INTO payment_settings (gateway, public_key, secret_key) VALUES ('paystack', ?, ?) ON DUPLICATE KEY UPDATE public_key = VALUES(public_key), secret_key = VALUES(secret_key)");
            $stmt->bind_param("ss", $public, $secret);
            $stmt->execute();
            $stmt->close();
            $settings_message = 'Paystack settings updated.';
        } elseif ($type === 'paypal') {
            $clientId = trim($_POST['paypal_client_id'] ?? '');
            $secret = trim($_POST['paypal_secret'] ?? '');
            $other = json_encode([
                'client_id' => $clientId
            ]);
            $stmt = $conn->prepare("INSERT INTO payment_settings (gateway, public_key, secret_key, other_settings) VALUES ('paypal', ?, ?, ?) ON DUPLICATE KEY UPDATE public_key = VALUES(public_key), secret_key = VALUES(secret_key), other_settings = VALUES(other_settings)");
            $stmt->bind_param("sss", $clientId, $secret, $other);
            $stmt->execute();
            $stmt->close();
            $settings_message = 'PayPal settings updated.';
        }
    } elseif (isset($_POST['blog_action'])) {
        $action = $_POST['blog_action'];
        if ($action === 'save') {
            $id = isset($_POST['blog_id']) ? (int)$_POST['blog_id'] : 0;
            $title = trim($_POST['blog_title'] ?? '');
            $content = trim($_POST['blog_content'] ?? '');
            $videoUrl = trim($_POST['blog_video_url'] ?? '');
            $audioUrl = trim($_POST['blog_audio_url'] ?? '');
            $imagePath = null;

            if (!empty($_FILES['blog_image']['name'])) {
                $uploadDir = 'assets/uploads';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $safeName = preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($_FILES['blog_image']['name']));
                $fileName = time() . '_' . $safeName;
                $destPath = $uploadDir . '/' . $fileName;
                if (move_uploaded_file($_FILES['blog_image']['tmp_name'], $destPath)) {
                    $imagePath = $destPath;
                }
            }

            if ($id > 0) {
                $current = $conn->query("SELECT image_path FROM blog_posts WHERE id = " . $id)->fetch_assoc();
                if (!$imagePath && $current) {
                    $imagePath = $current['image_path'];
                }
                $stmt = $conn->prepare("UPDATE blog_posts SET title = ?, content = ?, image_path = ?, video_url = ?, audio_url = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $title, $content, $imagePath, $videoUrl, $audioUrl, $id);
                $stmt->execute();
                $stmt->close();
                $blog_message = 'Blog post updated.';
            } else {
                $stmt = $conn->prepare("INSERT INTO blog_posts (title, content, image_path, video_url, audio_url) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $title, $content, $imagePath, $videoUrl, $audioUrl);
                $stmt->execute();
                $stmt->close();
                $blog_message = 'Blog post created.';
            }
        } elseif ($action === 'delete') {
            $id = isset($_POST['blog_id']) ? (int)$_POST['blog_id'] : 0;
            if ($id > 0) {
                $stmt = $conn->prepare("DELETE FROM blog_posts WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
                $blog_message = 'Blog post deleted.';
            }
        }
    }
}

$messages_count = $conn->query("SELECT COUNT(*) as count FROM contact_messages")->fetch_assoc()['count'];
$jobs_count = $conn->query("SELECT COUNT(*) as count FROM job_applications")->fetch_assoc()['count'];
$ambassadors_count = $conn->query("SELECT COUNT(*) as count FROM ambassador_applications")->fetch_assoc()['count'];
$partners_count = $conn->query("SELECT COUNT(*) as count FROM partnership_applications")->fetch_assoc()['count'];
$donations_count = $conn->query("SELECT COUNT(*) as count FROM donations WHERE payment_status = 'completed'")->fetch_assoc()['count'];
$donations_total = $conn->query("SELECT SUM(amount) as total FROM donations WHERE payment_status = 'completed'")->fetch_assoc()['total'] ?? 0;
$visitors_count = $conn->query("SELECT COUNT(*) as count FROM visitor_logs")->fetch_assoc()['count'];

$messages = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
$jobs = $conn->query("SELECT * FROM job_applications ORDER BY created_at DESC");
$ambassadors = $conn->query("SELECT * FROM ambassador_applications ORDER BY created_at DESC");
$donations = $conn->query("SELECT * FROM donations ORDER BY created_at DESC");
$partners = $conn->query("SELECT * FROM partnership_applications ORDER BY created_at DESC");
$blog_posts = $conn->query("SELECT * FROM blog_posts ORDER BY created_at DESC");
$paystack_settings = $conn->query("SELECT public_key, secret_key FROM payment_settings WHERE gateway = 'paystack' LIMIT 1")->fetch_assoc() ?: ['public_key' => '', 'secret_key' => ''];
$paypal_settings = $conn->query("SELECT public_key, secret_key FROM payment_settings WHERE gateway = 'paypal' LIMIT 1")->fetch_assoc() ?: ['public_key' => '', 'secret_key' => ''];
$visitors = $conn->query("SELECT * FROM visitor_logs ORDER BY created_at DESC LIMIT 200");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | ZifTech Africa</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="light-mode">
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="assets/images/logo-dark.png" alt="Logo" class="admin-logo">
                <h2>Admin Panel</h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li class="active" data-target="dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</li>
                    <li data-target="messages"><i class="fas fa-envelope"></i> Messages (<?php echo $messages_count; ?>)</li>
                    <li data-target="jobs"><i class="fas fa-briefcase"></i> Job Apps (<?php echo $jobs_count; ?>)</li>
                    <li data-target="ambassadors"><i class="fas fa-user-graduate"></i> Ambassadors (<?php echo $ambassadors_count; ?>)</li>
                    <li data-target="partners"><i class="fas fa-handshake"></i> Partners (<?php echo $partners_count; ?>)</li>
                    <li data-target="donations"><i class="fas fa-hand-holding-usd"></i> Donations (<?php echo $donations_count; ?>)</li>
                    <li data-target="visitors"><i class="fas fa-chart-area"></i> Visitors (<?php echo $visitors_count; ?>)</li>
                    <li data-target="payments"><i class="fas fa-key"></i> Payment Settings</li>
                    <li data-target="blog"><i class="fas fa-blog"></i> Blog</li>
                    <li><a href="index.html" target="_blank"><i class="fas fa-external-link-alt"></i> View Site</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <h1 id="page-title">Dashboard Overview</h1>
                </div>
                <div class="header-right">
                    <button id="admin-theme-toggle" class="admin-theme-toggle" type="button">
                        <i class="fas fa-moon"></i>
                    </button>
                    <div class="admin-profile">
                        <span>Welcome, Admin</span>
                        <div class="profile-img">A</div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Stats -->
            <div id="dashboard-content" class="content-section active">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon message-icon"><i class="fas fa-envelope"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $messages_count; ?></h3>
                            <p>Total Messages</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon job-icon"><i class="fas fa-briefcase"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $jobs_count; ?></h3>
                            <p>Job Applications</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon ambassador-icon"><i class="fas fa-user-graduate"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $ambassadors_count; ?></h3>
                            <p>Ambassadors</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon donation-icon"><i class="fas fa-hand-holding-usd"></i></div>
                        <div class="stat-info">
                            <h3>$<?php echo number_format($donations_total, 2); ?></h3>
                            <p>Total Donations</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon partner-icon"><i class="fas fa-handshake"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $partners_count; ?></h3>
                            <p>Partners</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon visitor-icon"><i class="fas fa-chart-area"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $visitors_count; ?></h3>
                            <p>Total Visitors</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages Table -->
            <div id="messages-content" class="content-section">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Subject</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $messages->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['name']; ?></td>
                                    <td><?php echo $row['email']; ?></td>
                                    <td><?php echo $row['subject']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <button class="btn-view" onclick="viewMessage(<?php echo htmlspecialchars(json_encode($row)); ?>)">View</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Job Applications Table -->
            <div id="jobs-content" class="content-section">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Position</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $jobs->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['name']; ?></td>
                                    <td><?php echo $row['email']; ?></td>
                                    <td><?php echo $row['position']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <button class="btn-view" onclick="viewJob(<?php echo htmlspecialchars(json_encode($row)); ?>)">View</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Ambassador Applications Table -->
            <div id="ambassadors-content" class="content-section">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>University</th>
                                <th>Course</th>
                                <th>Year</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $ambassadors->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['name']; ?></td>
                                    <td><?php echo $row['email']; ?></td>
                                    <td><?php echo $row['university']; ?></td>
                                    <td><?php echo $row['course']; ?></td>
                                    <td><?php echo $row['year_of_study']; ?></td>
                                    <td>
                                        <button class="btn-view" onclick="viewAmbassador(<?php echo htmlspecialchars(json_encode($row)); ?>)">View</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Donations Table -->
            <div id="donations-content" class="content-section">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Donor Name</th>
                                <th>Email</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $donations->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['donor_name']; ?></td>
                                    <td><?php echo $row['donor_email']; ?></td>
                                    <td>$<?php echo number_format($row['amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($row['payment_status']); ?>">
                                            <?php echo ucfirst($row['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td><small><?php echo $row['transaction_reference']; ?></small></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Partners Table -->
            <div id="partners-content" class="content-section">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Organization</th>
                                <th>Type</th>
                                <th>Focus Area</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $partners->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['name']; ?></td>
                                    <td><?php echo $row['email']; ?></td>
                                    <td><?php echo $row['organization']; ?></td>
                                    <td><?php echo $row['partnership_type']; ?></td>
                                    <td><?php echo $row['focus_area']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <button class="btn-view" onclick="viewPartner(<?php echo htmlspecialchars(json_encode($row)); ?>)">View</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Visitors Table -->
            <div id="visitors-content" class="content-section">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>IP Address</th>
                                <th>Country</th>
                                <th>Region</th>
                                <th>City</th>
                                <th>Path</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $visitors->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['ip_address']; ?></td>
                                    <td><?php echo $row['country']; ?></td>
                                    <td><?php echo $row['region']; ?></td>
                                    <td><?php echo $row['city']; ?></td>
                                    <td><?php echo $row['path']; ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payment Settings -->
            <div id="payments-content" class="content-section">
                <div class="table-container">
                    <?php if ($settings_message): ?>
                        <p><strong><?php echo htmlspecialchars($settings_message); ?></strong></p>
                    <?php endif; ?>
                    <h2>Paystack Settings</h2>
                    <form method="POST" action="admin.php">
                        <input type="hidden" name="settings_type" value="paystack">
                        <div class="form-group">
                            <label for="paystack_public_key">Public Key</label>
                            <input type="text" id="paystack_public_key" name="paystack_public_key" value="<?php echo htmlspecialchars($paystack_settings['public_key']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="paystack_secret_key">Secret Key</label>
                            <input type="text" id="paystack_secret_key" name="paystack_secret_key" value="<?php echo htmlspecialchars($paystack_settings['secret_key']); ?>">
                        </div>
                        <button type="submit" class="btn-view">Save Paystack Settings</button>
                    </form>
                    <hr>
                    <h2>PayPal Settings</h2>
                    <form method="POST" action="admin.php">
                        <input type="hidden" name="settings_type" value="paypal">
                        <div class="form-group">
                            <label for="paypal_client_id">Client ID</label>
                            <input type="text" id="paypal_client_id" name="paypal_client_id" value="<?php echo htmlspecialchars($paypal_settings['public_key']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="paypal_secret">Secret</label>
                            <input type="text" id="paypal_secret" name="paypal_secret" value="<?php echo htmlspecialchars($paypal_settings['secret_key']); ?>">
                        </div>
                        <button type="submit" class="btn-view">Save PayPal Settings</button>
                    </form>
                </div>
            </div>

            <!-- Blog Management -->
            <div id="blog-content" class="content-section">
                <div class="table-container">
                    <?php if ($blog_message): ?>
                        <p><strong><?php echo htmlspecialchars($blog_message); ?></strong></p>
                    <?php endif; ?>
                    <h2>Blog Posts</h2>
                    <form method="POST" action="admin.php" enctype="multipart/form-data">
                        <input type="hidden" name="blog_action" value="save">
                        <input type="hidden" name="blog_id" id="blog_id">
                        <div class="form-group">
                            <label for="blog_title">Title</label>
                            <input type="text" id="blog_title" name="blog_title" required>
                        </div>
                        <div class="form-group">
                            <label for="blog_content">Content</label>
                            <textarea id="blog_content" name="blog_content" rows="6" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="blog_image">Image</label>
                            <input type="file" id="blog_image" name="blog_image" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label for="blog_video_url">Video URL (optional)</label>
                            <input type="text" id="blog_video_url" name="blog_video_url">
                        </div>
                        <div class="form-group">
                            <label for="blog_audio_url">Audio URL (optional)</label>
                            <input type="text" id="blog_audio_url" name="blog_audio_url">
                        </div>
                        <button type="submit" class="btn-view">Save Blog Post</button>
                    </form>
                    <hr>
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Created</th>
                                <th>Image</th>
                                <th>Video</th>
                                <th>Audio</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($post = $blog_posts->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $post['title']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($post['created_at'])); ?></td>
                                    <td><?php echo $post['image_path'] ? 'Yes' : 'No'; ?></td>
                                    <td><?php echo $post['video_url'] ? 'Yes' : 'No'; ?></td>
                                    <td><?php echo $post['audio_url'] ? 'Yes' : 'No'; ?></td>
                                    <td>
                                        <button class="btn-view" type="button" onclick='editPost(<?php echo json_encode($post, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>)'>Edit</button>
                                        <form method="POST" action="admin.php" style="display:inline;" onsubmit="return confirm('Delete this post?');">
                                            <input type="hidden" name="blog_action" value="delete">
                                            <input type="hidden" name="blog_id" value="<?php echo $post['id']; ?>">
                                            <button type="submit" class="btn-view" style="background-color:#dc3545;">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Details Modal -->
    <div id="details-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2 id="modal-title">Details</h2>
            <div id="modal-body"></div>
        </div>
    </div>

    <script src="js/admin.js"></script>
</body>

</html>