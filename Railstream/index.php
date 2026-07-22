<?php 
include('db_config.php'); 
session_start(); 

$is_logged_in = isset($_SESSION['username']);
$is_admin = $is_logged_in && $_SESSION['username'] == 'admin';
$is_user = $is_logged_in && $_SESSION['username'] != 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RailStream — Future of Transit</title>
    <link href="https://fonts.googleapis.com/css2?family=Clash+Display:wght@400;500;600;700&family=Cabinet+Grotesk:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --ink: #03050a;
            --surface: #080c14;
            --glass: rgba(255,255,255,0.04);
            --glass-border: rgba(255,255,255,0.09);
            --blue: #2563eb;
            --blue-glow: #3b82f6;
            --cyan: #06b6d4;
            --gold: #f59e0b;
            --text: #f0f4ff;
            --muted: #64748b;
            --muted-light: #94a3b8;
        }

        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        html { scroll-behavior: smooth; }

        body {
            background: var(--ink);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            overflow-x: hidden;
            min-height: 100vh;
        }

        /* ── Canvas background ── */
        #bg-canvas {
            position: fixed; inset: 0; z-index: 0;
            pointer-events: none;
        }

        /* ── Grain overlay ── */
        body::after {
            content: '';
            position: fixed; inset: 0; z-index: 1;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
            opacity: 0.45;
            pointer-events: none;
        }

        /* ── Layout Wrapper ── */
        .wrapper { position: relative; z-index: 2; }

        /* ── NAV ── */
        nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 3rem; height: 68px;
            background: rgba(3,5,10,0.7);
            backdrop-filter: blur(24px);
            border-bottom: 1px solid var(--glass-border);
            transition: background 0.3s;
        }

        .nav-logo {
            display: flex; align-items: center; gap: 12px;
            text-decoration: none; color: var(--text);
        }
        .nav-logo .logo-icon {
            width: 38px; height: 38px; border-radius: 11px;
            background: linear-gradient(135deg, var(--blue), #1d4ed8);
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; color: #fff;
            box-shadow: 0 0 20px rgba(37,99,235,0.5);
        }
        .nav-logo .logo-text {
            font-family: 'Syne', sans-serif;
            font-size: 1.2rem; font-weight: 800;
            letter-spacing: -0.02em;
        }

        .nav-links { display: flex; align-items: center; gap: 8px; }
        .nav-links a {
            color: var(--muted-light); text-decoration: none;
            font-size: 0.85rem; font-weight: 500;
            padding: 8px 16px; border-radius: 9px;
            transition: all 0.2s;
        }
        .nav-links a:hover { color: var(--text); background: var(--glass); }

        .btn-nav-primary {
            background: linear-gradient(135deg, var(--blue), #1d4ed8) !important;
            color: #fff !important; font-weight: 700 !important;
            padding: 8px 22px !important;
            box-shadow: 0 0 20px rgba(37,99,235,0.3);
        }
        .btn-nav-primary:hover { box-shadow: 0 0 30px rgba(37,99,235,0.5) !important; transform: translateY(-1px); }

        .btn-nav-admin {
            background: rgba(245,158,11,0.12) !important;
            border: 1px solid rgba(245,158,11,0.3) !important;
            color: var(--gold) !important; font-weight: 700 !important;
        }
        .btn-nav-admin:hover { background: rgba(245,158,11,0.2) !important; }

        .btn-nav-logout {
            background: rgba(244,63,94,0.1) !important;
            border: 1px solid rgba(244,63,94,0.25) !important;
            color: #f43f5e !important; font-weight: 600 !important;
        }
        .btn-nav-logout:hover { background: rgba(244,63,94,0.2) !important; }

        /* ── HERO ── */
        .hero {
            min-height: 100vh;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            padding: 120px 2rem 80px;
            text-align: center;
            position: relative;
        }

        .hero-eyebrow {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(37,99,235,0.1); border: 1px solid rgba(59,130,246,0.25);
            color: var(--blue-glow); padding: 6px 18px; border-radius: 50px;
            font-size: 0.78rem; font-weight: 700; letter-spacing: 0.1em;
            text-transform: uppercase; margin-bottom: 2rem;
            animation: fadeUp 0.8s ease forwards; opacity: 0;
        }
        .hero-eyebrow .dot { width:6px;height:6px;border-radius:50%;background:var(--blue-glow);animation:pulse 1.5s infinite; }
        @keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.4;transform:scale(0.8)} }

        .hero-title {
            font-family: 'Syne', sans-serif;
            font-size: clamp(3.5rem, 9vw, 8rem);
            font-weight: 800;
            line-height: 0.92;
            letter-spacing: -0.04em;
            margin-bottom: 1.5rem;
            animation: fadeUp 0.8s 0.1s ease forwards; opacity: 0;
        }
        .hero-title .line2 {
            display: block;
            background: linear-gradient(90deg, var(--blue-glow), var(--cyan), var(--blue-glow));
            background-size: 200%;
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            animation: shimmer 4s linear infinite, fadeUp 0.8s 0.1s ease forwards;
        }
        @keyframes shimmer { 0%{background-position:0%} 100%{background-position:200%} }

        .hero-sub {
            font-size: clamp(1rem, 2vw, 1.2rem);
            color: var(--muted-light); max-width: 500px;
            line-height: 1.7; margin-bottom: 3.5rem;
            animation: fadeUp 0.8s 0.2s ease forwards; opacity: 0;
        }

        @keyframes fadeUp {
            from { opacity:0; transform:translateY(28px); }
            to   { opacity:1; transform:translateY(0); }
        }

        /* ── DASHBOARD CARDS ── */
        .dash-cards {
            display: flex; gap: 24px; justify-content: center; flex-wrap: wrap;
            animation: fadeUp 0.8s 0.35s ease forwards; opacity: 0;
        }

        .dash-card {
            position: relative; overflow: hidden;
            width: 300px; padding: 2.5rem 2rem;
            border-radius: 24px; text-decoration: none;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(20px);
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            transform-style: preserve-3d;
            cursor: pointer;
        }
        .dash-card::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(circle at 50% 0%, rgba(59,130,246,0.15), transparent 70%);
            opacity: 0; transition: opacity 0.4s;
            border-radius: inherit;
        }
        .dash-card:hover { transform: translateY(-12px) scale(1.02); border-color: rgba(59,130,246,0.4); }
        .dash-card:hover::before { opacity: 1; }

        .dash-card.admin-card::before {
            background: radial-gradient(circle at 50% 0%, rgba(245,158,11,0.2), transparent 70%);
        }
        .dash-card.admin-card:hover { border-color: rgba(245,158,11,0.4); }

        /* Card glow ring */
        .dash-card::after {
            content: ''; position: absolute;
            inset: -1px; border-radius: inherit; z-index: -1;
            background: linear-gradient(135deg, rgba(59,130,246,0.3), transparent, rgba(6,182,212,0.2));
            opacity: 0; transition: opacity 0.4s;
        }
        .dash-card:hover::after { opacity: 1; }
        .dash-card.admin-card::after {
            background: linear-gradient(135deg, rgba(245,158,11,0.3), transparent, rgba(245,158,11,0.1));
        }

        .card-icon-wrap {
            width: 64px; height: 64px; border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            font-size: 26px; margin: 0 auto 1.5rem;
            position: relative;
        }
        .card-icon-wrap.blue {
            background: linear-gradient(135deg, rgba(37,99,235,0.3), rgba(6,182,212,0.15));
            border: 1px solid rgba(59,130,246,0.3);
            color: var(--blue-glow);
            box-shadow: 0 0 30px rgba(37,99,235,0.25);
        }
        .card-icon-wrap.gold {
            background: linear-gradient(135deg, rgba(245,158,11,0.25), rgba(251,191,36,0.1));
            border: 1px solid rgba(245,158,11,0.3);
            color: var(--gold);
            box-shadow: 0 0 30px rgba(245,158,11,0.2);
        }

        .dash-card h3 {
            font-family: 'Syne', sans-serif;
            font-size: 1.3rem; font-weight: 700;
            color: var(--text); margin-bottom: 0.5rem;
            text-align: center;
        }
        .dash-card p {
            color: var(--muted-light); font-size: 0.85rem;
            line-height: 1.6; text-align: center;
        }
        .card-arrow {
            position: absolute; bottom: 1.5rem; right: 1.5rem;
            width: 32px; height: 32px; border-radius: 50%;
            background: var(--glass); border: 1px solid var(--glass-border);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.75rem; color: var(--muted);
            transition: all 0.3s;
        }
        .dash-card:hover .card-arrow {
            background: var(--blue); border-color: var(--blue);
            color: #fff; transform: rotate(-45deg);
        }
        .dash-card.admin-card:hover .card-arrow {
            background: var(--gold); border-color: var(--gold);
        }

        /* ── STATS ROW ── */
        .stats-row {
            display: flex; gap: 40px; justify-content: center; flex-wrap: wrap;
            margin-top: 5rem; padding: 2rem 0;
            border-top: 1px solid var(--glass-border);
            animation: fadeUp 0.8s 0.5s ease forwards; opacity: 0;
        }
        .stat-item { text-align: center; }
        .stat-num {
            font-family: 'Syne', sans-serif;
            font-size: 2rem; font-weight: 800;
            background: linear-gradient(135deg, #fff, rgba(255,255,255,0.6));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .stat-lbl { color: var(--muted); font-size: 0.78rem; margin-top: 2px; }

        /* ── FEATURES SECTION ── */
        .features {
            padding: 7rem 2rem;
            max-width: 1100px; margin: 0 auto;
        }
        .section-label {
            text-align: center;
            font-size: 0.75rem; font-weight: 700; letter-spacing: 0.12em;
            text-transform: uppercase; color: var(--blue-glow); margin-bottom: 1rem;
        }
        .section-title {
            font-family: 'Syne', sans-serif;
            font-size: clamp(2rem, 4vw, 3rem); font-weight: 800;
            text-align: center; margin-bottom: 4rem; letter-spacing: -0.03em;
        }
        .features-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;
        }
        .feat-card {
            background: var(--glass); border: 1px solid var(--glass-border);
            border-radius: 18px; padding: 2rem;
            transition: all 0.3s; position: relative; overflow: hidden;
        }
        .feat-card:hover { border-color: rgba(59,130,246,0.3); transform: translateY(-4px); }
        .feat-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, transparent, var(--blue-glow), transparent);
            opacity: 0; transition: opacity 0.3s;
        }
        .feat-card:hover::before { opacity: 1; }
        .feat-icon {
            width: 44px; height: 44px; border-radius: 12px;
            background: rgba(37,99,235,0.12); border: 1px solid rgba(59,130,246,0.2);
            display: flex; align-items: center; justify-content: center;
            color: var(--blue-glow); font-size: 18px; margin-bottom: 1.2rem;
        }
        .feat-card h4 {
            font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .feat-card p { color: var(--muted-light); font-size: 0.83rem; line-height: 1.7; }

        /* ── FOOTER ── */
        .site-footer {
            background: rgba(3,5,10,0.95);
            border-top: 1px solid var(--glass-border);
            padding: 4rem 3rem 2rem;
            position: relative; z-index: 2;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 2.5rem;
            max-width: 1200px; margin: 0 auto 3rem;
        }
        .footer-brand .logo-wrap {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 1rem;
        }
        .footer-brand .logo-icon {
            width: 40px; height: 40px; border-radius: 11px;
            background: linear-gradient(135deg, var(--blue), #1d4ed8);
            display: flex; align-items: center; justify-content: center;
            font-size: 17px; color: #fff;
            box-shadow: 0 0 18px rgba(37,99,235,0.4);
        }
        .footer-brand .logo-text {
            font-family: 'Syne', sans-serif;
            font-size: 1.15rem; font-weight: 800;
        }
        .footer-brand p {
            color: var(--muted); font-size: 0.83rem; line-height: 1.7;
            max-width: 280px; margin-bottom: 1.5rem;
        }
        .footer-socials { display: flex; gap: 10px; }
        .social-btn {
            width: 36px; height: 36px; border-radius: 9px;
            background: var(--glass); border: 1px solid var(--glass-border);
            display: flex; align-items: center; justify-content: center;
            color: var(--muted); font-size: 14px;
            text-decoration: none; transition: all 0.2s;
        }
        .social-btn:hover { background: rgba(37,99,235,0.15); border-color: rgba(59,130,246,0.4); color: var(--blue-glow); }

        .footer-col h5 {
            font-family: 'Syne', sans-serif;
            font-size: 0.78rem; font-weight: 700;
            letter-spacing: 0.1em; text-transform: uppercase;
            color: var(--muted-light); margin-bottom: 1.2rem;
        }
        .footer-col ul { list-style: none; }
        .footer-col ul li { margin-bottom: 0.6rem; }
        .footer-col ul li a {
            color: var(--muted); font-size: 0.84rem;
            text-decoration: none; transition: color 0.2s;
        }
        .footer-col ul li a:hover { color: var(--text); }

        .footer-bottom {
            max-width: 1200px; margin: 0 auto;
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 12px;
            padding-top: 2rem;
            border-top: 1px solid var(--glass-border);
        }
        .footer-bottom p { color: var(--muted); font-size: 0.78rem; }
        .footer-bottom p span { color: var(--blue-glow); }
        .footer-badges { display: flex; gap: 8px; flex-wrap: wrap; }
        .badge {
            background: var(--glass); border: 1px solid var(--glass-border);
            border-radius: 6px; padding: 4px 10px;
            font-size: 0.7rem; color: var(--muted); font-weight: 500;
        }

        @media(max-width: 900px) {
            .footer-grid { grid-template-columns: 1fr 1fr; }
            .footer-brand { grid-column: 1/-1; }
        }
        @media(max-width: 500px) {
            .footer-grid { grid-template-columns: 1fr; }
            .site-footer { padding: 3rem 1.5rem 1.5rem; }
            .footer-bottom { flex-direction: column; align-items: flex-start; }
        }

        /* ── DIVIDER LINE ── */
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--glass-border), transparent);
            margin: 0 4rem;
        }

        /* ── 3D Floating elements ── */
        .float-orb {
            position: absolute; border-radius: 50%; filter: blur(80px);
            pointer-events: none; z-index: 0;
        }
        .orb-1 { width:400px;height:400px; background:rgba(37,99,235,0.15); top:-100px; left:-100px; animation: orbFloat 8s ease-in-out infinite; }
        .orb-2 { width:300px;height:300px; background:rgba(6,182,212,0.1); bottom:100px; right:-50px; animation: orbFloat 10s ease-in-out infinite reverse; }
        .orb-3 { width:250px;height:250px; background:rgba(245,158,11,0.08); top:40%; left:60%; animation: orbFloat 12s ease-in-out infinite 2s; }
        @keyframes orbFloat {
            0%,100% { transform:translateY(0) scale(1); }
            50% { transform:translateY(-40px) scale(1.05); }
        }

        /* ── TRAIN LINE ── */
        .train-line {
            position: absolute; bottom: 120px; left: 0; right: 0;
            height: 2px; overflow: hidden; opacity: 0.2;
        }
        .train-line::before {
            content: ''; position: absolute; top: 0;
            width: 200px; height: 100%;
            background: linear-gradient(90deg, transparent, var(--blue-glow), transparent);
            animation: trainMove 4s linear infinite;
        }
        @keyframes trainMove { from{left:-200px} to{left:100%} }

        @media(max-width:768px) {
            nav { padding: 0 1.2rem; }
            .dash-cards { gap: 16px; }
            .dash-card { width: 100%; max-width: 340px; }
            .features-grid { grid-template-columns: 1fr; }
            .input-row { grid-template-columns: 1fr; }
            .stats-row { gap: 24px; }
        }

        /* Scroll reveal */
        .reveal { opacity: 0; transform: translateY(30px); transition: all 0.7s ease; }
        .reveal.visible { opacity: 1; transform: translateY(0); }
    </style>
</head>
<body>
<canvas id="bg-canvas"></canvas>

<div class="wrapper">
<!-- NAV -->
<nav>
    <a href="index.php" class="nav-logo">
        <div class="logo-icon"><i class="fa-solid fa-train-subway"></i></div>
        <span class="logo-text">RailStream</span>
    </a>
    <div class="nav-links">
        <a href="#features">Features</a>
        <a href="#footer-about">About</a>
        <?php if($is_admin): ?>
            <a href="admin_dashboard.php" class="btn-nav-admin"><i class="fa-solid fa-user-shield" style="margin-right:6px"></i>Admin Panel</a>
            <a href="logout.php" class="btn-nav-logout"><i class="fa-solid fa-right-from-bracket" style="margin-right:6px"></i>Logout</a>
        <?php elseif($is_user): ?>
            <a href="user_dashboard.php" class="btn-nav-primary"><i class="fa-solid fa-ticket" style="margin-right:6px"></i>My Dashboard</a>
            <a href="logout.php" class="btn-nav-logout"><i class="fa-solid fa-right-from-bracket" style="margin-right:6px"></i>Logout</a>
        <?php else: ?>
            <a href="login.php" class="btn-nav-primary"><i class="fa-solid fa-right-to-bracket" style="margin-right:6px"></i>Login</a>
        <?php endif; ?>
    </div>
</nav>

<!-- HERO -->
<section class="hero">
    <!-- Floating orbs -->
    <div class="float-orb orb-1"></div>
    <div class="float-orb orb-2"></div>
    <div class="float-orb orb-3"></div>
    <div class="train-line"></div>

    <div class="hero-eyebrow">
        <span class="dot"></span>
        India's Smartest Railway Platform
    </div>

    <h1 class="hero-title">
        The Future of<br>
        <span class="line2">Rail Transit</span>
    </h1>

    <p class="hero-sub">
        Seamlessly book, manage and track rail journeys.<br>
        Built for passengers. Powered for operators.
    </p>

    <!-- Role-aware cards -->
    <div class="dash-cards">
        <?php if($is_admin): ?>
            <!-- Admin only sees Admin Dashboard -->
            <a href="admin_dashboard.php" class="dash-card admin-card">
                <div class="card-icon-wrap gold">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <h3>Admin Dashboard</h3>
                <p>Manage trains, schedules, bookings, users and platform analytics.</p>
                <div class="card-arrow"><i class="fa-solid fa-arrow-right"></i></div>
            </a>
        <?php elseif($is_user): ?>
            <!-- User only sees User Dashboard -->
            <a href="user_dashboard.php" class="dash-card">
                <div class="card-icon-wrap blue">
                    <i class="fa-solid fa-ticket"></i>
                </div>
                <h3>My Dashboard</h3>
                <p>Search trains, book tickets and manage your passenger journeys.</p>
                <div class="card-arrow"><i class="fa-solid fa-arrow-right"></i></div>
            </a>
        <?php else: ?>
            <!-- Guest sees both cards -->
            <a href="user_dashboard.php" class="dash-card">
                <div class="card-icon-wrap blue">
                    <i class="fa-solid fa-user"></i>
                </div>
                <h3>Passenger Portal</h3>
                <p>Search trains, book tickets and manage your passenger journeys.</p>
                <div class="card-arrow"><i class="fa-solid fa-arrow-right"></i></div>
            </a>
            <a href="admin_dashboard.php" class="dash-card admin-card">
                <div class="card-icon-wrap gold">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <h3>Operator Console</h3>
                <p>Manage trains, schedules, bookings, users and platform analytics.</p>
                <div class="card-arrow"><i class="fa-solid fa-arrow-right"></i></div>
            </a>
        <?php endif; ?>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-item">
            <div class="stat-num">99.9%</div>
            <div class="stat-lbl">System Uptime</div>
        </div>
        <div class="stat-item">
            <div class="stat-num">24/7</div>
            <div class="stat-lbl">Active Service</div>
        </div>
        <div class="stat-item">
            <div class="stat-num">500+</div>
            <div class="stat-lbl">Routes Covered</div>
        </div>
        <div class="stat-item">
            <div class="stat-num">4.9★</div>
            <div class="stat-lbl">Satisfaction</div>
        </div>
    </div>
</section>

<div class="divider"></div>

<!-- FEATURES -->
<section class="features" id="features">
    <div class="section-label reveal">Platform Capabilities</div>
    <h2 class="section-title reveal">Everything Rail, in One Place</h2>
    <div class="features-grid">
        <div class="feat-card reveal">
            <div class="feat-icon"><i class="fa-solid fa-bolt"></i></div>
            <h4>Instant Booking</h4>
            <p>Reserve seats in seconds with a streamlined booking flow and real-time seat availability.</p>
        </div>
        <div class="feat-card reveal">
            <div class="feat-icon"><i class="fa-solid fa-chart-line"></i></div>
            <h4>Live Analytics</h4>
            <p>Admins get real-time performance dashboards, revenue tracking, and booking intelligence.</p>
        </div>
        <div class="feat-card reveal">
            <div class="feat-icon"><i class="fa-solid fa-shield-halved"></i></div>
            <h4>Secure Payments</h4>
            <p>UPI, PhonePe, GPay — every transaction encrypted and confirmed with a printed receipt.</p>
        </div>
        <div class="feat-card reveal">
            <div class="feat-icon"><i class="fa-solid fa-route"></i></div>
            <h4>Route Management</h4>
            <p>Operators can configure multi-city routes, dynamic pricing, and multi-class scheduling.</p>
        </div>
        <div class="feat-card reveal">
            <div class="feat-icon"><i class="fa-solid fa-ticket"></i></div>
            <h4>Digital Tickets</h4>
            <p>Passengers get printable tickets with QR codes, PNR numbers, and full journey details.</p>
        </div>
        <div class="feat-card reveal">
            <div class="feat-icon"><i class="fa-solid fa-headset"></i></div>
            <h4>24/7 Support</h4>
            <p>Integrated support contact form that routes queries directly to railway administrators.</p>
        </div>
    </div>
</section>

<div class="divider"></div>

<!-- SITE FOOTER -->
<footer class="site-footer" id="footer-about">
    <div class="footer-grid">
        <!-- Brand -->
        <div class="footer-brand">
            <div class="logo-wrap">
                <div class="logo-icon"><i class="fa-solid fa-train-subway"></i></div>
                <span class="logo-text">RailStream</span>
            </div>
            <p>India's smartest railway management platform. Seamlessly book, manage and track rail journeys — built for passengers, powered for operators.</p>
            <div class="footer-socials">
                <a href="#" class="social-btn"><i class="fa-brands fa-twitter"></i></a>
                <a href="#" class="social-btn"><i class="fa-brands fa-linkedin-in"></i></a>
                <a href="#" class="social-btn"><i class="fa-brands fa-instagram"></i></a>
                <a href="#" class="social-btn"><i class="fa-brands fa-youtube"></i></a>
            </div>
        </div>
        <!-- Platform -->
        <div class="footer-col">
            <h5>Platform</h5>
            <ul>
                <li><a href="login.php">Book Tickets</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="login.php">My Bookings</a></li>
                <li><a href="login.php">E-Tickets</a></li>
            </ul>
        </div>
        <!-- Company -->
        <div class="footer-col">
            <h5>Company</h5>
            <ul>
                <li><a href="#">About Us</a></li>
                <li><a href="#">Careers</a></li>
                <li><a href="#">Press</a></li>
                <li><a href="#">Blog</a></li>
            </ul>
        </div>
        <!-- Support -->
        <div class="footer-col">
            <h5>Support</h5>
            <ul>
                <li><a href="#">Help Centre</a></li>
                <li><a href="#">Privacy Policy</a></li>
                <li><a href="#">Terms of Service</a></li>
                <li><a href="#">Refund Policy</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; 2026 <span>RailStream</span> — Railway Management System. All rights reserved.</p>
        <div class="footer-badges">
            <span class="badge"><i class="fa-solid fa-shield-halved" style="margin-right:4px;color:#22c55e"></i>SSL Secured</span>
            <span class="badge"><i class="fa-solid fa-lock" style="margin-right:4px;color:#3b82f6"></i>PCI Compliant</span>
            <span class="badge"><i class="fa-solid fa-circle" style="margin-right:4px;color:#22c55e;font-size:7px"></i>99.9% Uptime</span>
        </div>
    </div>
</footer>
</div><!-- end wrapper -->

<script>
/* ── Particle Canvas ── */
const canvas = document.getElementById('bg-canvas');
const ctx = canvas.getContext('2d');
let particles = [];

function resize() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
}
resize();
window.addEventListener('resize', resize);

class Particle {
    constructor() { this.reset(); }
    reset() {
        this.x = Math.random() * canvas.width;
        this.y = Math.random() * canvas.height;
        this.size = Math.random() * 1.2 + 0.3;
        this.speedX = (Math.random() - 0.5) * 0.3;
        this.speedY = (Math.random() - 0.5) * 0.3;
        this.opacity = Math.random() * 0.5 + 0.1;
    }
    update() {
        this.x += this.speedX; this.y += this.speedY;
        if (this.x < 0 || this.x > canvas.width || this.y < 0 || this.y > canvas.height) this.reset();
    }
    draw() {
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(148,163,184,${this.opacity})`;
        ctx.fill();
    }
}

for (let i = 0; i < 120; i++) particles.push(new Particle());

function animate() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    particles.forEach(p => { p.update(); p.draw(); });
    requestAnimationFrame(animate);
}
animate();

/* ── 3D Card Tilt ── */
document.querySelectorAll('.dash-card').forEach(card => {
    card.addEventListener('mousemove', e => {
        const rect = card.getBoundingClientRect();
        const cx = rect.left + rect.width / 2;
        const cy = rect.top + rect.height / 2;
        const dx = (e.clientX - cx) / (rect.width / 2);
        const dy = (e.clientY - cy) / (rect.height / 2);
        card.style.transform = `translateY(-12px) scale(1.02) rotateX(${-dy*6}deg) rotateY(${dx*6}deg)`;
    });
    card.addEventListener('mouseleave', () => {
        card.style.transform = '';
    });
});

/* ── Scroll reveal ── */
const revealEls = document.querySelectorAll('.reveal');
const observer = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); } });
}, { threshold: 0.15 });
revealEls.forEach(el => observer.observe(el));
</script>
</body>
</html>
