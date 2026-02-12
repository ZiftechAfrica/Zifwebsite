<?php
include 'config.php';

// Check if logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

// Fetch stats
$messages_count = $conn->query("SELECT COUNT(*) as count FROM contact_messages")->fetch_assoc()['count'];
$jobs_count = $conn->query("SELECT COUNT(*) as count FROM job_applications")->fetch_assoc()['count'];
$ambassadors_count = $conn->query("SELECT COUNT(*) as count FROM ambassador_applications")->fetch_assoc()['count'];
$donations_count = $conn->query("SELECT COUNT(*) as count FROM donations WHERE payment_status = 'completed'")->fetch_assoc()['count'];
$donations_total = $conn->query("SELECT SUM(amount) as total FROM donations WHERE payment_status = 'completed'")->fetch_assoc()['total'] ?? 0;

// Fetch messages
$messages = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");

// Fetch job applications
$jobs = $conn->query("SELECT * FROM job_applications ORDER BY created_at DESC");

// Fetch ambassador applications
$ambassadors = $conn->query("SELECT * FROM ambassador_applications ORDER BY created_at DESC");

// Fetch donations
$donations = $conn->query("SELECT * FROM donations ORDER BY created_at DESC");
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
                    <li data-target="donations"><i class="fas fa-hand-holding-usd"></i> Donations (<?php echo $donations_count; ?>)</li>
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
                            <?php while($row = $messages->fetch_assoc()): ?>
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
                            <?php while($row = $jobs->fetch_assoc()): ?>
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
                            <?php while($row = $ambassadors->fetch_assoc()): ?>
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
                            <?php while($row = $donations->fetch_assoc()): ?>
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
