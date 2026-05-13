<?php
// start session to read login state
session_start();
// include common components
include 'db.php';          // database access
include 'header.php';      // page header with navigation
include 'sidebar.php';     // side menu

// If no user logged in, send them to login page
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

// grab some values from session for display
$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$is_admin = isset($_SESSION['is_admin']) ? (int)$_SESSION['is_admin'] : 0;

// run simple SQL counts for various statistics displayed on dashboard
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_posts = $conn->query("SELECT COUNT(*) as count FROM posts")->fetch_assoc()['count'];
$total_reviews = $conn->query("SELECT COUNT(*) as count FROM reviews")->fetch_assoc()['count'];
$total_messages = $conn->query("SELECT COUNT(*) as count FROM messages")->fetch_assoc()['count'];
// Only count notifications if the table exists (migration may not have been run yet)
$notificationsCount = 0;
$tableCheck = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $notificationsCount = (int)$conn->query("SELECT COUNT(*) as count FROM notifications WHERE user_id={$user_id}")->fetch_assoc()['count'];
}
$total_notifications = $notificationsCount;

// precompute non‑admin user's personal stats to avoid repeated queries
$my_posts = $conn->query("SELECT COUNT(*) as count FROM posts WHERE user_id=$user_id")->fetch_assoc()['count'];
$my_reviews = $conn->query("SELECT COUNT(*) as count FROM reviews WHERE user_id=$user_id")->fetch_assoc()['count'];
$my_messages = $conn->query("SELECT COUNT(*) as count FROM messages WHERE sender_id=$user_id OR receiver_id=$user_id")->fetch_assoc()['count'];
?>

<div class="main">
    <div class="welcome">
        <h2">Welcome, <?php echo htmlspecialchars($full_name); ?> - <?php echo $is_admin ? 'Admin' : ($role ? ucfirst($role) : 'User'); ?></h2>
    </div>

    <!-- Tab Navigation -->
    <div class="dashboard-tabs">
        <div class="tab-navigation">
            <button class="tab-button active" onclick="switchTab(event, 'statsTab')">
                <span class="tab-icon">📊</span> Statistics
            </button>
            <button class="tab-button" onclick="switchTab(event, 'overviewTab')">
                <span class="tab-icon">🌍</span> Platform Overview
            </button>
            <?php if(!$is_admin): ?>
                <button class="tab-button" onclick="switchTab(event, 'activityTab')">
                    <span class="tab-icon">📈</span> My Activity
                </button>
            <?php endif; ?>
        </div>

        <!-- Tab 1: Statistics -->
        <div id="statsTab" class="tab-content active">
            <div class="tab-content-header">
                <h3>Dashboard Statistics</h3>
                <p class="tab-subtitle">Key metrics and insights</p>
            </div>
            
            <!-- Stats Section -->
            <div class="cards dashboard">
                <?php if($is_admin): ?>
                    <div class="card dashboard-stat">
                        <div class="stat-icon">👥</div>
                        <div class="stat-content">
                            <h2>Total Users</h2>
                            <p class="stat-number"><?php echo $total_users; ?></p>
                        </div>
                    </div>
                    <div class="card dashboard-stat">
                        <div class="stat-icon">💡</div>
                        <div class="stat-content">
                            <h2>Total Projects</h2>
                            <p class="stat-number"><?php echo $total_posts; ?></p>
                        </div>
                    </div>
                    <div class="card dashboard-stat">
                        <div class="stat-icon">⭐</div>
                        <div class="stat-content">
                            <h2>Total Reviews</h2>
                            <p class="stat-number"><?php echo $total_reviews; ?></p>
                        </div>
                    </div>
                    <div class="card dashboard-stat">
                        <div class="stat-icon">💬</div>
                        <div class="stat-content">
                            <h2>Total Messages</h2>
                            <p class="stat-number"><?php echo $total_messages; ?></p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card dashboard-stat">
                        <div class="stat-icon">💡</div>
                        <div class="stat-content">
                            <h2>My Projects</h2>
                            <p class="stat-number"><?php echo $my_posts; ?></p>
                        </div>
                    </div>
                    <div class="card dashboard-stat">
                        <div class="stat-icon">⭐</div>
                        <div class="stat-content">
                            <h2>My Reviews</h2>
                            <p class="stat-number"><?php echo $my_reviews; ?></p>
                        </div>
                    </div>
                    <div class="card dashboard-stat">
                        <div class="stat-icon">💬</div>
                        <div class="stat-content">
                            <h2>Messages</h2>
                            <p class="stat-number"><?php echo $my_messages; ?></p>
                        </div>
                    </div>
                    <div class="card dashboard-stat">
                        <div class="stat-icon">🔔</div>
                        <div class="stat-content">
                            <h2>Notifications</h2>
                            <p class="stat-number"><?php echo $total_notifications; ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab 2: Platform Overview -->
        <div id="overviewTab" class="tab-content">
            <div class="tab-content-header">
                <h3>Empowering African Entrepreneurship</h3>
                <p class="tab-subtitle">Connecting Mentors, Investors, and Entrepreneurs Across the Continent</p>
            </div>
            
            <div class="gallery-container">
                <!-- Entrepreneurship Card -->
                <div class="gallery-card" onclick="openModal('innovationModal')">
                    <div class="gallery-image-wrapper">
                        <img src="Photos/Innovator.jpg" alt="Entrepreneurship and Innovation" class="gallery-img">
                        <div class="gallery-overlay">
                            <span class="view-more">Click to Explore</span>
                        </div>
                    </div>
                    <div class="gallery-content">
                        <h3>Innovation & Entrepreneurship</h3>
                        <p class="gallery-quote">"The best time to plant a tree was 20 years ago. The second best time is now." - African proverb</p>
                    </div>
                </div>

                <!-- Investment Card -->
                <div class="gallery-card" onclick="openModal('investmentModal')">
                    <div class="gallery-image-wrapper">
                        <img src="Photos/Investor%20meeting.webp" alt="Investment Opportunities" class="gallery-img">
                        <div class="gallery-overlay">
                            <span class="view-more">Click to Explore</span>
                        </div>
                    </div>
                    <div class="gallery-content">
                        <h3>Smart Investment</h3>
                        <p class="gallery-quote">"Africa is on the cusp of a business revolution." - World Economic Forum</p>
                    </div>
                </div>

                <!-- Collaboration Card -->
                <div class="gallery-card" onclick="openModal('collaborationModal')">
                    <div class="gallery-image-wrapper">
                        <img src="Photos/business%20meeting.jpg" alt="Business Collaboration" class="gallery-img">
                        <div class="gallery-overlay">
                            <span class="view-more">Click to Explore</span>
                        </div>
                    </div>
                    <div class="gallery-content">
                        <h3>Strategic Collaboration</h3>
                        <p class="gallery-quote">"Ubuntu - 'I am because we are.' Together we build stronger futures."</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 3: My Activity (Only for non-admins) -->
        <?php if(!$is_admin): ?>
        <div id="activityTab" class="tab-content">
            <div class="tab-content-header">
                <h3>Your Activity Overview</h3>
                <p class="tab-subtitle">Track your contributions to the platform</p>
            </div>

            <div class="activity-grid">
                <div class="activity-card">
                    <div class="activity-icon">💡</div>
                    <h4>Project Submissions</h4>
                    <p class="activity-stat"><?php echo $my_posts; ?></p>
                    <p class="activity-text">Projects you've shared with the community</p>
                    <a href="post_idea.php" class="activity-link">View All →</a>
                </div>

                <div class="activity-card">
                    <div class="activity-icon">⭐</div>
                    <h4>Reviews Given</h4>
                    <p class="activity-stat"><?php echo $my_reviews; ?></p>
                    <p class="activity-text">Feedback you've provided to others</p>
                    <a href="reviews.php" class="activity-link">View All →</a>
                </div>

                <div class="activity-card">
                    <div class="activity-icon">💬</div>
                    <h4>Conversations</h4>
                    <p class="activity-stat"><?php echo $my_messages; ?></p>
                    <p class="activity-text">Messages exchanged with community members</p>
                    <a href="messages.php" class="activity-link">View All →</a>
                </div>

                <div class="activity-card">
                    <div class="activity-icon">👤</div>
                    <h4>Profile</h4>
                    <p class="activity-stat">Complete</p>
                    <p class="activity-text">Your professional profile information</p>
                    <a href="profile.php" class="activity-link">Edit Profile →</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>


    <!-- Modals -->
    <div id="innovationModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('innovationModal')">&times;</span>
            <h2>Innovation & Entrepreneurship in African Startups</h2>
            <p class="modal-detailed-text">
                Africa is home to the world's most innovative and entrepreneurial minds. Our platform is dedicated to nurturing this spirit of innovation by providing African entrepreneurs with the tools, resources, and network they need to transform their ideas into thriving businesses. From tech startups to agricultural innovations, African entrepreneurs are solving real-world problems and creating sustainable solutions that impact millions across the continent. We believe that with the right support system and mentorship, African startups can compete on the global stage and lead the next generation of technological and social innovation.
            </p>
            <p class="modal-detailed-text">
                Our mission is to create an ecosystem where young innovators can pitch their ideas, connect with experienced mentors, and access market insights. We celebrate the boldness of African entrepreneurs who dream big and the resilience they demonstrate in bringing those dreams to life. By fostering a community of innovation, we're not just building companies—we're building Africa's future.
            </p>
        </div>
    </div>

    <div id="investmentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('investmentModal')">&times;</span>
            <h2>Investment & Growth Opportunities</h2>
            <p class="modal-detailed-text">
                Africa's startup ecosystem is attracting unprecedented investment interest from global venture capitalists and impact investors. The continent's young, dynamic population, growing digital infrastructure, and abundance of natural resources create a powerful foundation for business growth. Our platform bridges the gap between promising African startups and investors seeking high-impact, high-growth opportunities in emerging markets.
            </p>
            <p class="modal-detailed-text">
                We facilitate meaningful connections between entrepreneurs and investors, providing transparent, detailed information about business opportunities, growth metrics, and financial projections. Whether you're looking to invest in fintech, agricultural technology, renewable energy, or e-commerce solutions, you'll find compelling opportunities on our platform. African startups are not just attracting investment—they're generating exceptional returns while creating jobs and driving economic transformation across the continent. Join us in shaping Africa's investment landscape and supporting the next generation of continental champions.
            </p>
        </div>
    </div>

    <div id="collaborationModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('collaborationModal')">&times;</span>
            <h2>Strategic Collaboration & Partnerships</h2>
            <p class="modal-detailed-text">
                The power of African entrepreneurship is amplified through collaboration and strategic partnerships. Our platform is designed to foster meaningful connections between entrepreneurs, investors, corporate partners, government agencies, and development organizations. We believe that the most impactful solutions emerge when diverse stakeholders come together with a shared vision of continental growth and prosperity.
            </p>
            <p class="modal-detailed-text">
                Through strategic partnerships, startups gain access to capital, technical expertise, market networks, and distribution channels that accelerate their growth trajectory. Investors and corporate partners benefit from deal-flow diversification and the opportunity to support transformative businesses that create social and economic value. Our collaboration model emphasizes transparency, mutual respect, and long-term value creation. Together, we're building an African startup ecosystem that is resilient, inclusive, and capable of competing in the global digital economy. We invite you to participate in this exciting journey and be part of Africa's entrepreneurial revolution.
            </p>
        </div>
    </div>

    <script>
        // Tab switching function
        function switchTab(event, tabName) {
            event.preventDefault();
            
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            const tabButtons = document.querySelectorAll('.tab-button');
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked button
            event.target.closest('.tab-button').classList.add('active');
        }

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = "block";
            document.body.style.overflow = "hidden";
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
            document.body.style.overflow = "auto";
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = "none";
                document.body.style.overflow = "auto";
            }
        }
    </script>

    <style>
        /* Dashboard Tab Styling */
        .dashboard-tabs {
            margin-top: 30px;
        }

        .tab-navigation {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 0;
            flex-wrap: wrap;
        }

        .tab-button {
            padding: 12px 24px;
            border: none;
            background-color: transparent;
            color: #6b7280;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab-button:hover {
            color: #203a43;
            background-color: #f3f4f6;
            border-radius: 6px 6px 0 0;
        }

        .tab-button.active {
            color: #203a43;
            border-bottom-color: #2c5364;
            background-color: transparent;
        }

        .tab-icon {
            font-size: 16px;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .tab-content-header {
            margin-bottom: 30px;
        }

        .tab-content-header h3 {
            font-size: 24px;
            color: #203a43;
            margin: 0 0 8px 0;
            font-weight: 700;
        }

        .tab-subtitle {
            color: #6b7280;
            font-size: 14px;
            margin: 0;
        }

        /* Enhanced Stats Cards */
        .dashboard-stat {
            display: flex;
            align-items: center;
            gap: 20px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .dashboard-stat:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(32, 58, 67, 0.1);
            border-color: #2c5364;
        }

        .stat-icon {
            font-size: 32px;
            min-width: 50px;
            text-align: center;
        }

        .stat-content {
            flex: 1;
        }

        .stat-content h2 {
            margin: 0;
            font-size: 14px;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-number {
            margin: 8px 0 0 0;
            font-size: 28px;
            color: #203a43;
            font-weight: 700;
        }

        /* Activity Grid */
        .activity-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-top: 20px;
        }

        .activity-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #f3f4f6 100%);
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 28px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .activity-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 30px rgba(32, 58, 67, 0.15);
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-color: #2c5364;
        }

        .activity-icon {
            font-size: 40px;
            margin-bottom: 16px;
        }

        .activity-card h4 {
            margin: 0 0 12px 0;
            color: #203a43;
            font-size: 16px;
            font-weight: 700;
        }

        .activity-stat {
            font-size: 28px;
            color: #2c5364;
            font-weight: 700;
            margin: 0 0 8px 0;
        }

        .activity-text {
            font-size: 13px;
            color: #6b7280;
            margin: 0 0 16px 0;
            line-height: 1.5;
        }

        .activity-link {
            display: inline-block;
            color: #2c5364;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s ease;
            padding: 8px 12px;
            border-radius: 6px;
            background-color: rgba(44, 83, 100, 0.1);
        }

        .activity-link:hover {
            background-color: #2c5364;
            color: white;
        }

        /* Gallery Container */
        .gallery-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 28px;
            margin-top: 20px;
        }

        .gallery-card {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            background: white;
        }

        .gallery-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 35px rgba(44, 83, 100, 0.2);
        }

        .gallery-image-wrapper {
            position: relative;
            overflow: hidden;
            height: 220px;
        }

        .gallery-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .gallery-card:hover .gallery-img {
            transform: scale(1.05);
        }

        .gallery-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(32, 58, 67, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .gallery-card:hover .gallery-overlay {
            opacity: 1;
        }

        .view-more {
            color: white;
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .gallery-content {
            padding: 20px;
        }

        .gallery-content h3 {
            margin: 0 0 12px 0;
            color: #203a43;
            font-size: 16px;
            font-weight: 700;
        }

        .gallery-quote {
            margin: 0;
            color: #6b7280;
            font-size: 13px;
            font-style: italic;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .tab-navigation {
                gap: 0;
            }

            .tab-button {
                padding: 10px 16px;
                font-size: 12px;
                gap: 6px;
            }

            .tab-icon {
                display: none;
            }

            .dashboard-stat {
                padding: 16px;
                gap: 12px;
            }

            .stat-number {
                font-size: 22px;
            }

            .activity-grid {
                grid-template-columns: 1fr;
            }

            .gallery-container {
                grid-template-columns: 1fr;
            }
        }
    </style>

</div>

</body>
</html>
