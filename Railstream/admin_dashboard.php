<?php 
include('db_config.php'); 
session_start(); 

if (!isset($_SESSION['username']) || $_SESSION['username'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Get stats
$total_trains = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM trains"))['c'];
$total_bookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT transaction_id) as c FROM bookings WHERE transaction_id IS NOT NULL AND transaction_id != ''"))['c'];
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE username != 'admin'"))['c'];
$total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(t.price) as rev FROM bookings b JOIN trains t ON b.train_id = t.id"))['rev'] ?? 0;
$confirmed_bookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings WHERE payment_method != ''"))['c'];
$unread_msgs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM contact_messages WHERE LOWER(status)='unread' OR status IS NULL OR status=''"))['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - RailStream</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --bg: #07090f;
            --surface: #0d1117;
            --card: #111827;
            --border: rgba(255,255,255,0.07);
            --accent-blue: #3b82f6;
            --accent-teal: #14b8a6;
            --accent-purple: #8b5cf6;
            --accent-amber: #f59e0b;
            --accent-rose: #f43f5e;
            --accent-green: #22c55e;
            --text: #f1f5f9;
            --muted: #64748b;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background: linear-gradient(rgba(0,0,0,0.72), rgba(0,0,0,0.72)), url('train.jpg'); background-size: cover; background-position: center; background-attachment: fixed; color: var(--text); font-family: 'DM Sans', sans-serif; min-height: 100vh; }

        /* Stars background */
        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image: 
                radial-gradient(1px 1px at 10% 15%, rgba(255,255,255,0.3) 0%, transparent 100%),
                radial-gradient(1px 1px at 30% 60%, rgba(255,255,255,0.2) 0%, transparent 100%),
                radial-gradient(1px 1px at 55% 25%, rgba(255,255,255,0.25) 0%, transparent 100%),
                radial-gradient(1px 1px at 75% 70%, rgba(255,255,255,0.2) 0%, transparent 100%),
                radial-gradient(1px 1px at 90% 40%, rgba(255,255,255,0.3) 0%, transparent 100%),
                radial-gradient(1px 1px at 45% 85%, rgba(255,255,255,0.15) 0%, transparent 100%),
                radial-gradient(1px 1px at 20% 90%, rgba(255,255,255,0.2) 0%, transparent 100%),
                radial-gradient(2px 2px at 65% 45%, rgba(255,255,255,0.1) 0%, transparent 100%);
            pointer-events: none; z-index: 0;
        }

        nav {
            position: sticky; top: 0; z-index: 100;
            background: rgba(13,17,23,0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 0 2rem;
            height: 64px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .nav-logo { display: flex; align-items: center; gap: 10px; }
        .nav-logo .icon-box {
            width: 36px; height: 36px; border-radius: 10px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
        }
        .nav-logo span { font-family: 'Syne', sans-serif; font-size: 1.2rem; font-weight: 700; }
        .nav-logo .badge {
            background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.3);
            color: var(--accent-blue); font-size: 0.65rem; font-weight: 600;
            padding: 2px 8px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.05em;
        }
        .nav-right { display: flex; align-items: center; gap: 16px; }
        .nav-right a {
            color: var(--muted); text-decoration: none; font-size: 0.85rem;
            transition: color 0.2s; display: flex; align-items: center; gap: 6px;
        }
        .nav-right a:hover { color: var(--text); }
        .btn-logout {
            background: rgba(244,63,94,0.1); border: 1px solid rgba(244,63,94,0.2);
            color: #f43f5e !important; padding: 6px 16px; border-radius: 8px;
            font-size: 0.8rem; font-weight: 600; transition: all 0.2s !important;
        }
        .btn-logout:hover { background: rgba(244,63,94,0.2) !important; color: #f43f5e !important; }

        .main { position: relative; z-index: 1; padding: 2.5rem 2rem; max-width: 1400px; margin: 0 auto; }

        .welcome-block { margin-bottom: 2.5rem; }
        .welcome-block h1 { font-family: 'Syne', sans-serif; font-size: clamp(2rem, 4vw, 3rem); font-weight: 800; margin-bottom: 6px; }
        .welcome-block h1 span { 
            background: linear-gradient(90deg, #3b82f6, #14b8a6);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .welcome-block p { color: var(--muted); font-size: 0.95rem; }

        /* Stat bar */
        .stat-bar {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2.5rem;
        }
        .stat-item {
            background: var(--card); border: 1px solid var(--border);
            border-radius: 14px; padding: 1.2rem 1.4rem;
            display: flex; align-items: center; gap: 14px;
        }
        .stat-icon {
            width: 44px; height: 44px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0;
        }
        .stat-item .val { font-family: 'Syne', sans-serif; font-size: 1.5rem; font-weight: 700; line-height: 1; }
        .stat-item .lbl { color: var(--muted); font-size: 0.75rem; margin-top: 3px; }

        /* Dashboard cards grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.2rem;
        }
        .dash-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 1.8rem;
            cursor: pointer;
            text-decoration: none;
            color: var(--text);
            display: block;
            transition: transform 0.25s, border-color 0.25s, box-shadow 0.25s;
            position: relative;
            overflow: hidden;
        }
        .dash-card::before {
            content: '';
            position: absolute; inset: 0;
            background: radial-gradient(ellipse at top left, var(--card-glow, transparent) 0%, transparent 60%);
            opacity: 0; transition: opacity 0.3s;
        }
        .dash-card:hover { transform: translateY(-4px); box-shadow: 0 20px 60px rgba(0,0,0,0.4); }
        .dash-card:hover::before { opacity: 1; }
        .dash-card:hover { border-color: var(--card-accent, #3b82f6); }

        .card-icon-wrap {
            width: 58px; height: 58px; border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; margin-bottom: 1.2rem;
            position: relative; z-index: 1;
        }
        .card-title { font-family: 'Syne', sans-serif; font-size: 1.15rem; font-weight: 700; margin-bottom: 8px; position: relative; z-index: 1; }
        .card-desc { color: var(--muted); font-size: 0.82rem; line-height: 1.6; margin-bottom: 1.2rem; position: relative; z-index: 1; }
        .card-cta {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 0.8rem; font-weight: 600; padding: 8px 16px;
            border-radius: 8px; border: none; cursor: pointer;
            transition: opacity 0.2s; position: relative; z-index: 1;
            text-decoration: none;
        }
        .card-cta:hover { opacity: 0.85; }

        /* Individual card themes */
        .card-blue  { --card-accent: #3b82f6; --card-glow: rgba(59,130,246,0.08); }
        .card-teal  { --card-accent: #14b8a6; --card-glow: rgba(20,184,166,0.08); }
        .card-purple{ --card-accent: #8b5cf6; --card-glow: rgba(139,92,246,0.08); }
        .card-amber { --card-accent: #f59e0b; --card-glow: rgba(245,158,11,0.08); }
        .card-rose  { --card-accent: #f43f5e; --card-glow: rgba(244,63,94,0.08); }
        .card-green { --card-accent: #22c55e; --card-glow: rgba(34,197,94,0.08); }

        .icon-blue   { background: rgba(59,130,246,0.15); color: #3b82f6; }
        .icon-teal   { background: rgba(20,184,166,0.15); color: #14b8a6; }
        .icon-purple { background: rgba(139,92,246,0.15); color: #8b5cf6; }
        .icon-amber  { background: rgba(245,158,11,0.15); color: #f59e0b; }
        .icon-rose   { background: rgba(244,63,94,0.15); color: #f43f5e; }
        .icon-green  { background: rgba(34,197,94,0.15); color: #22c55e; }

        .cta-blue   { background: rgba(59,130,246,0.15); color: #3b82f6; }
        .cta-teal   { background: rgba(20,184,166,0.15); color: #14b8a6; }
        .cta-purple { background: rgba(139,92,246,0.15); color: #8b5cf6; }
        .cta-amber  { background: rgba(245,158,11,0.15); color: #f59e0b; }
        .cta-rose   { background: rgba(244,63,94,0.15); color: #f43f5e; }
        .cta-green  { background: rgba(34,197,94,0.15); color: #22c55e; }

        @media (max-width: 768px) {
            .stat-bar { grid-template-columns: repeat(2, 1fr); }
            .cards-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 480px) {
            .cards-grid { grid-template-columns: 1fr; }
            .stat-bar { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
    <nav>
        <div class="nav-logo">
            <div class="icon-box"><i class="fa-solid fa-train-subway" style="color:#fff"></i></div>
            <span>RailStream</span>
            <span class="badge">Admin</span>
        </div>
        <div class="nav-right">
            <a href="index.php"><i class="fa-solid fa-globe"></i> View Site</a>
            <a href="logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </nav>

    <div class="main">
        <div class="welcome-block">
            <h1>Welcome <span>Admin</span></h1>
            <p>Manage trains, routes, schedules, users, bookings, reports, and complete transport operations from one premium dashboard.</p>
        </div>

        <!-- Stat bar -->
        <div class="stat-bar">
            <div class="stat-item">
                <div class="stat-icon" style="background:rgba(59,130,246,0.12);color:#3b82f6"><i class="fa-solid fa-train"></i></div>
                <div><div class="val"><?php echo $total_trains; ?></div><div class="lbl">Active Trains</div></div>
            </div>
            <div class="stat-item">
                <div class="stat-icon" style="background:rgba(245,158,11,0.12);color:#f59e0b"><i class="fa-solid fa-ticket"></i></div>
                <div><div class="val"><?php echo $total_bookings; ?></div><div class="lbl">Total Bookings</div></div>
            </div>
            <div class="stat-item">
                <div class="stat-icon" style="background:rgba(34,197,94,0.12);color:#22c55e"><i class="fa-solid fa-users"></i></div>
                <div><div class="val"><?php echo $total_users; ?></div><div class="lbl">Registered Users</div></div>
            </div>
            <div class="stat-item">
                <div class="stat-icon" style="background:rgba(139,92,246,0.12);color:#8b5cf6"><i class="fa-solid fa-indian-rupee-sign"></i></div>
                <div><div class="val">₹<?php echo number_format($total_revenue); ?></div><div class="lbl">Total Revenue</div></div>
            </div>
        </div>

        <!-- Dashboard cards -->
        <div class="cards-grid">

            <!-- Add Train -->
            <a href="admin_add_train.php" class="dash-card card-blue">
                <div class="card-icon-wrap icon-blue"><i class="fa-solid fa-circle-plus"></i></div>
                <div class="card-title">Add Train</div>
                <div class="card-desc">Add new trains with sleeper, semi-sleeper layouts, pricing, schedules, and travel information.</div>
                <span class="card-cta cta-blue">Add Train <i class="fa-solid fa-arrow-right"></i></span>
            </a>

            <!-- Manage Trains -->
            <a href="admin_manage_trains.php" class="dash-card card-teal">
                <div class="card-icon-wrap icon-teal"><i class="fa-solid fa-train-tram"></i></div>
                <div class="card-title">Manage Trains</div>
                <div class="card-desc">Edit trains, update timings, modify routes, and manage seat availability.</div>
                <span class="card-cta cta-teal">Manage <i class="fa-solid fa-arrow-right"></i></span>
            </a>

            <!-- Manage Routes -->
            <a href="admin_routes.php" class="dash-card card-purple">
                <div class="card-icon-wrap icon-purple"><i class="fa-solid fa-route"></i></div>
                <div class="card-title">Manage Routes</div>
                <div class="card-desc">Configure travel routes, destinations, pickup points, and journey paths.</div>
                <span class="card-cta cta-purple">Manage Routes <i class="fa-solid fa-arrow-right"></i></span>
            </a>

            <!-- View Bookings -->
            <a href="admin_bookings.php" class="dash-card card-amber">
                <div class="card-icon-wrap icon-amber"><i class="fa-solid fa-calendar-check"></i></div>
                <div class="card-title">View Bookings</div>
                <div class="card-desc">Monitor reservations, passenger bookings, seats, and confirmations.</div>
                <span class="card-cta cta-amber">View Bookings <i class="fa-solid fa-arrow-right"></i></span>
            </a>

            <!-- Reports -->
            <a href="admin_reports.php" class="dash-card card-rose">
                <div class="card-icon-wrap icon-rose"><i class="fa-solid fa-chart-line"></i></div>
                <div class="card-title">
                    Reports
                    <?php if($unread_msgs > 0): ?>
                    <span style="background:#f43f5e;color:#fff;font-size:0.65rem;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:8px;vertical-align:middle">
                        <?php echo $unread_msgs; ?> new
                    </span>
                    <?php endif; ?>
                </div>
                <div class="card-desc">Analyze revenues, booking statistics, and complete system insights.<?php if($unread_msgs > 0): ?> <span style="color:#f43f5e;font-weight:600"><?php echo $unread_msgs; ?> unread support message<?php echo $unread_msgs>1?'s':''; ?>.</span><?php endif; ?></div>
                <span class="card-cta cta-rose">View Reports <i class="fa-solid fa-arrow-right"></i></span>
            </a>

            <!-- Users -->
            <a href="admin_users.php" class="dash-card card-green">
                <div class="card-icon-wrap icon-green"><i class="fa-solid fa-users-gear"></i></div>
                <div class="card-title">Users</div>
                <div class="card-desc">Manage registrations, monitor activity, and update access controls.</div>
                <span class="card-cta cta-green">Manage Users <i class="fa-solid fa-arrow-right"></i></span>
            </a>

        </div>
    </div>
</body>
</html>
