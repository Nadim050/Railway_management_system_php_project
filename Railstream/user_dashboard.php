<?php 
include('db_config.php'); 
session_start(); 

if(!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch all available trains (available every day)
$trains_res = mysqli_query($conn, "SELECT * FROM trains WHERE available_seats > 0 ORDER BY departure_time ASC");
$trains = [];
while($t = mysqli_fetch_assoc($trains_res)) { $trains[] = $t; }

// Fetch user's bookings grouped by transaction_id (one card per booking group)
$bookings_res = mysqli_query($conn, 
    "SELECT b.transaction_id,
            MIN(b.id) AS b_id,
            GROUP_CONCAT(b.passenger_name ORDER BY b.id SEPARATOR ', ') AS passenger_names,
            COUNT(b.id) AS num_passengers,
            MAX(b.berth_pref) AS berth_pref,
            MAX(b.payment_method) AS payment_method,
            MAX(b.booking_date) AS booking_date,
            t.train_name, t.origin, t.destination, t.departure_time, COALESCE(b.journey_date, t.travel_date) AS travel_date, t.price, t.stops,
            (t.price * COUNT(b.id)) AS total_amount
     FROM bookings b
     JOIN trains t ON b.train_id = t.id 
     WHERE b.user_id = '$user_id' AND b.train_id IS NOT NULL
     GROUP BY b.transaction_id, t.id
     ORDER BY b.id DESC"
);
$bookings = [];
while($b = mysqli_fetch_assoc($bookings_res)) { $bookings[] = $b; }

$total_bookings = array_sum(array_column($bookings, 'num_passengers'));
$total_spent    = array_sum(array_column($bookings, 'total_amount'));
$trains_available = count($trains);

// Fetch user email
$user_res = mysqli_query($conn, "SELECT email FROM users WHERE id='$user_id'");
$user_data = mysqli_fetch_assoc($user_res);
$user_email = $user_data['email'] ?? '';

// Handle support message
$msg_sent = false;
if(isset($_POST['send_msg'])){
    $cname  = mysqli_real_escape_string($conn, $_POST['c_name']);
    $cemail = mysqli_real_escape_string($conn, $_POST['c_email']);
    $cmsg   = mysqli_real_escape_string($conn, $_POST['c_message']);
    mysqli_query($conn, "INSERT INTO contact_messages (name, email, message) VALUES ('$cname','$cemail','$cmsg')");
    $msg_sent = true;
    header("Location: user_dashboard.php?support=sent#home");
    exit();
}
if(isset($_GET['support']) && $_GET['support'] === 'sent') {
    $msg_sent = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — RailStream</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{--bg:#07090f;--surface:#0d1117;--surface2:#111827;--border:rgba(255,255,255,0.08);--border-h:rgba(255,255,255,0.15);--ibg:rgba(255,255,255,0.05);--text:#f1f5f9;--muted:#64748b;--muted2:#94a3b8;--blue:#3b82f6;--blue-d:#2563eb;--bglow:rgba(59,130,246,0.15);--cyan:#06b6d4;--green:#22c55e;--amber:#f59e0b;--red:#f43f5e;--purple:#a855f7}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;min-height:100vh;overflow-x:hidden}
body::before{content:'';position:fixed;inset:0;z-index:0;background:radial-gradient(ellipse 80% 50% at 10% 0%,rgba(37,99,235,.12) 0%,transparent 60%),radial-gradient(ellipse 60% 40% at 90% 100%,rgba(6,182,212,.08) 0%,transparent 60%);pointer-events:none}
body::after{content:'';position:fixed;inset:0;z-index:0;background-image:radial-gradient(rgba(255,255,255,.05) 1px,transparent 1px);background-size:32px 32px;pointer-events:none}

/* NAV */
nav{position:sticky;top:0;z-index:100;background:rgba(7,9,15,.88);backdrop-filter:blur(24px);border-bottom:1px solid var(--border);padding:0 2rem;height:66px;display:flex;align-items:center;justify-content:space-between}
.nav-logo{display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--text);cursor:pointer}
.logo-icon{width:38px;height:38px;border-radius:11px;background:linear-gradient(135deg,var(--blue),#1d4ed8);display:flex;align-items:center;justify-content:center;font-size:15px;color:#fff;box-shadow:0 0 20px rgba(59,130,246,.4)}
.logo-txt{font-family:'Syne',sans-serif;font-size:1.15rem;font-weight:800;letter-spacing:-.02em}
.nav-right{display:flex;align-items:center;gap:10px}
.nbtn{color:var(--muted2);font-size:.83rem;display:flex;align-items:center;gap:6px;padding:7px 14px;border-radius:9px;border:1px solid var(--border);transition:all .2s;cursor:pointer;background:none;font-family:'DM Sans',sans-serif;text-decoration:none}
.nbtn:hover{color:var(--text);border-color:var(--border-h);background:rgba(255,255,255,.04)}
.nbtn.active{color:var(--blue);border-color:rgba(59,130,246,.3);background:var(--bglow)}
.nbtn.danger{color:var(--red);border-color:rgba(244,63,94,.2);background:rgba(244,63,94,.06)}
.nbtn.danger:hover{background:rgba(244,63,94,.12)}

/* VIEWS */
.view{display:none;position:relative;z-index:1}
.view.active{display:block}
.main{max-width:920px;margin:0 auto;padding:2.5rem 1.5rem 4rem}

/* GREETING */
.greet{margin-bottom:2.2rem}
.eyebrow{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--blue);margin-bottom:8px;display:flex;align-items:center;gap:8px}
.eyebrow::before{content:'';width:24px;height:1px;background:var(--blue)}
.greet h1{font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;line-height:1.2}
.greet h1 span{color:var(--cyan)}
.greet p{color:var(--muted2);font-size:.88rem;margin-top:6px}

/* STATS */
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:2rem}
.stat{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:1.1rem 1.25rem;display:flex;align-items:center;gap:14px;transition:border-color .2s}
.stat:hover{border-color:var(--border-h)}
.sico{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.sico.b{background:rgba(59,130,246,.12);color:var(--blue)}
.sico.g{background:rgba(34,197,94,.12);color:var(--green)}
.sico.a{background:rgba(245,158,11,.12);color:var(--amber)}
.slbl{font-size:.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px}
.sval{font-size:1.3rem;font-weight:700;font-family:'Syne',sans-serif}

/* CARDS */
.cards{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:2rem}
.dcard{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:1.6rem;cursor:pointer;transition:all .25s;position:relative;overflow:hidden;display:flex;flex-direction:column}
.dcard::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 80% 80% at 50% -20%,rgba(59,130,246,.06) 0%,transparent 70%);opacity:0;transition:opacity .25s}
.dcard:hover{border-color:rgba(59,130,246,.3);transform:translateY(-2px)}
.dcard:hover::before{opacity:1}
.cico{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:1rem}
.dcard h3{font-family:'Syne',sans-serif;font-size:.95rem;font-weight:700;margin-bottom:6px}
.dcard p{color:var(--muted);font-size:.78rem;line-height:1.5;flex:1}
.cact{margin-top:1.1rem;display:inline-flex;align-items:center;gap:6px;font-size:.78rem;font-weight:600;padding:7px 14px;border-radius:8px;transition:all .2s}
.cs .cico{background:rgba(59,130,246,.1);color:var(--blue)}
.cs .cact{background:rgba(59,130,246,.1);color:var(--blue)}
.cb .cico{background:rgba(34,197,94,.1);color:var(--green)}
.cb .cact{background:rgba(34,197,94,.1);color:var(--green)}
.cp .cico{background:rgba(168,85,247,.1);color:var(--purple)}
.cp .cact{background:rgba(168,85,247,.1);color:var(--purple)}

/* SECTION LABEL */
.slabel{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--blue);display:flex;align-items:center;gap:8px;margin-bottom:12px}

/* PANEL */
.panel{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:2rem;margin-bottom:1.5rem}

/* SEARCH */
.swrap{position:relative;margin-bottom:1.2rem}
.sinput{width:100%;background:var(--ibg);border:1px solid var(--border);border-radius:12px;padding:13px 16px 13px 44px;color:var(--text);font-size:.9rem;outline:none;transition:border-color .2s;font-family:'DM Sans',sans-serif}
.sinput:focus{border-color:var(--blue)}
.sinput::placeholder{color:var(--muted)}
.sico2{position:absolute;left:15px;top:50%;transform:translateY(-50%);color:var(--muted);pointer-events:none}
.tdrop{position:absolute;top:calc(100% + 6px);left:0;right:0;z-index:50;background:#0d1117;border:1px solid var(--border);border-radius:14px;max-height:280px;overflow-y:auto;display:none;box-shadow:0 24px 60px rgba(0,0,0,.6)}
.tdrop.open{display:block}
.topt{padding:12px 16px;cursor:pointer;transition:background .15s;border-bottom:1px solid rgba(255,255,255,.04);display:flex;justify-content:space-between;align-items:center}
.topt:last-child{border-bottom:none}
.topt:hover{background:rgba(59,130,246,.08)}
.tname{font-weight:600;font-size:.88rem}
.troute{color:var(--muted);font-size:.76rem;margin-top:3px}
.tprice{color:var(--green);font-weight:700;font-size:.88rem;flex-shrink:0}
.tbadge{display:inline-block;padding:2px 8px;border-radius:5px;font-size:.68rem;font-weight:600;margin-left:6px;background:rgba(245,158,11,.12);color:var(--amber)}
.stinfo{background:rgba(59,130,246,.07);border:1px solid rgba(59,130,246,.2);border-radius:12px;padding:14px 16px;margin-bottom:1.2rem;display:none}
.stinfo.visible{display:block}
.stname{font-weight:700;color:var(--blue);font-size:.95rem;margin-bottom:6px}
.chips{display:flex;flex-wrap:wrap;gap:8px}
.chip{display:flex;align-items:center;gap:5px;background:rgba(255,255,255,.05);border:1px solid var(--border);padding:4px 10px;border-radius:7px;font-size:.76rem;color:var(--muted2)}

/* FORM */
.fgrid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.field label{display:block;color:var(--muted);font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px}
.field input,.field select{width:100%;background:var(--ibg);border:1px solid var(--border);border-radius:10px;padding:12px 14px;color:var(--text);font-size:.88rem;outline:none;transition:border-color .2s;font-family:'DM Sans',sans-serif;appearance:none;-webkit-appearance:none}
.field select{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 13px center;padding-right:36px}
.field select option{background:#0d1117}
.field input:focus,.field select:focus{border-color:var(--blue)}
.field input::placeholder{color:var(--muted)}

/* BUTTONS */
.btn-pri{width:100%;margin-top:1.4rem;background:linear-gradient(135deg,var(--blue),var(--blue-d));color:#fff;border:none;border-radius:12px;padding:14px;font-size:.95rem;font-weight:700;cursor:pointer;transition:all .2s;font-family:'DM Sans',sans-serif;display:flex;align-items:center;justify-content:center;gap:10px;box-shadow:0 0 30px rgba(59,130,246,.25)}
.btn-pri:hover{opacity:.88;box-shadow:0 0 40px rgba(59,130,246,.35)}
.btn-pri:disabled{opacity:.36;cursor:not-allowed;box-shadow:none}
.btn-back{display:inline-flex;align-items:center;gap:8px;cursor:pointer;color:var(--muted2);font-size:.83rem;padding:7px 14px;border:1px solid var(--border);border-radius:9px;background:none;font-family:'DM Sans',sans-serif;transition:all .2s;margin-bottom:1.5rem;text-decoration:none}
.btn-back:hover{color:var(--text);border-color:var(--border-h)}

/* BOOKING ROWS */
.brow{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:1.25rem 1.5rem;margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;transition:border-color .2s}
.brow:hover{border-color:var(--border-h)}
.btrain{font-weight:700;color:var(--blue);font-size:.95rem;margin-bottom:4px;font-family:'Syne',sans-serif}
.broute{color:var(--muted2);font-size:.82rem}
.bmeta{color:var(--muted);font-size:.75rem;margin-top:5px}
.bprice{font-size:1.1rem;font-weight:700;color:var(--green);text-align:right}
.bpnr{font-size:.72rem;color:var(--muted);text-align:right;margin-top:4px}
.bstatus{display:inline-block;padding:3px 9px;border-radius:6px;font-size:.7rem;font-weight:600;background:rgba(34,197,94,.1);color:var(--green);margin-top:6px}
.empty{text-align:center;padding:3rem 0;color:var(--muted)}
.empty i{font-size:2.5rem;margin-bottom:12px;display:block;opacity:.35}

/* PROFILE */
.ph{display:flex;align-items:center;gap:1.2rem;margin-bottom:1.8rem;padding-bottom:1.5rem;border-bottom:1px solid var(--border)}
.avatar{width:64px;height:64px;border-radius:18px;background:linear-gradient(135deg,var(--purple),#7c3aed);display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:800;color:#fff;box-shadow:0 0 24px rgba(168,85,247,.35)}
.pname{font-family:'Syne',sans-serif;font-size:1.2rem;font-weight:800}
.pemail{color:var(--muted2);font-size:.83rem;margin-top:3px}
.pgrid{display:grid;grid-template-columns:1fr 1fr;gap:14px}

/* PAYMENT */
.psum{background:rgba(59,130,246,.06);border:1px solid rgba(59,130,246,.18);border-radius:14px;padding:1.2rem 1.4rem;margin-bottom:1.5rem}
.psrow{display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid rgba(255,255,255,.05);font-size:.85rem}
.psrow:last-child{border-bottom:none;font-weight:700;font-size:.95rem;color:var(--green)}
.psrow span:first-child{color:var(--muted2)}
.pmethods{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:1.5rem}
.pmethod{border:1px solid var(--border);border-radius:12px;padding:14px 16px;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:10px;font-size:.85rem;font-weight:600;background:var(--ibg);color:var(--text)}
.pmethod:hover{border-color:var(--border-h);background:rgba(255,255,255,.06)}
.pmethod.sel-upi{border-color:var(--purple);background:rgba(168,85,247,.1);color:var(--purple)}
.pmethod.sel-card{border-color:var(--blue);background:rgba(59,130,246,.1);color:var(--blue)}
.pmethod.sel-net{border-color:var(--cyan);background:rgba(6,182,212,.1);color:var(--cyan)}
.pmethod.sel-cash{border-color:var(--amber);background:rgba(245,158,11,.1);color:var(--amber)}

/* SUCCESS */
.sbox{text-align:center;padding:3rem 2rem}
.sring{width:88px;height:88px;border-radius:50%;background:rgba(34,197,94,.1);border:2px solid rgba(34,197,94,.3);display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;font-size:2rem;color:var(--green);animation:pop .5s cubic-bezier(.34,1.56,.64,1)}
@keyframes pop{from{transform:scale(.4);opacity:0}to{transform:scale(1);opacity:1}}
.sbox h2{font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:800;margin-bottom:8px}
.sbox p{color:var(--muted2);font-size:.88rem}
.tbox{background:var(--surface2);border:1px dashed rgba(255,255,255,.12);border-radius:16px;padding:1.4rem;margin:1.6rem 0;text-align:left}
.trow{display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid rgba(255,255,255,.04);font-size:.83rem}
.trow:last-child{border-bottom:none}
.trow span:first-child{color:var(--muted)}
.trow span:last-child{font-weight:600}
.pnr-big{font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;color:var(--cyan);letter-spacing:.04em}

/* HELP */
.help{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:1.8rem}
@media(max-width:700px){.search-top-grid{grid-template-columns:1fr!important}}
input[type=date].sinput::-webkit-calendar-picker-indicator{filter:invert(0.7);cursor:pointer}
.help h3{font-family:'Syne',sans-serif;font-size:.95rem;font-weight:700;color:var(--green);margin-bottom:1rem}
.hgrid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.hgrid input,.hgrid textarea{background:var(--ibg);border:1px solid var(--border);border-radius:10px;padding:12px 14px;color:var(--text);font-size:.85rem;outline:none;transition:border-color .2s;font-family:'DM Sans',sans-serif;width:100%}
.hgrid input:focus,.hgrid textarea:focus{border-color:var(--green)}
.hgrid input::placeholder,.hgrid textarea::placeholder{color:var(--muted)}
.hgrid .s2{grid-column:1/-1}
textarea{resize:none}
.btn-help{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);color:var(--green);border-radius:10px;padding:10px 22px;font-weight:700;font-size:.83rem;cursor:pointer;transition:all .2s;font-family:'DM Sans',sans-serif;margin-top:10px}
.btn-help:hover{background:rgba(34,197,94,.18)}

.divider{height:1px;background:var(--border);margin:1.8rem 0}
.alert-success{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);color:var(--green);border-radius:10px;padding:10px 16px;font-size:.84rem;margin-bottom:1rem}

@media(max-width:640px){.cards,.stats,.fgrid,.pmethods,.pgrid,.hgrid{grid-template-columns:1fr}}
</style>
</head>
<body>

<!-- NAV -->
<nav>
  <div class="nav-logo" onclick="goTo('home')">
    <div class="logo-icon"><i class="fa-solid fa-train-subway"></i></div>
    <span class="logo-txt">RailStream</span>
  </div>
  <div class="nav-right">
    <button class="nbtn" id="nav-search" onclick="goTo('search')"><i class="fa-solid fa-magnifying-glass"></i> Search Trains</button>
    <button class="nbtn" id="nav-bookings" onclick="goTo('bookings')"><i class="fa-solid fa-ticket"></i> My Bookings</button>
    <button class="nbtn" id="nav-track" onclick="goTo('track')"><i class="fa-solid fa-location-crosshairs"></i> Track Train</button>
    <a href="logout.php" class="nbtn danger"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
  </div>
</nav>

<!-- ═══════════════ HOME ═══════════════ -->
<div class="view active" id="view-home">
  <div class="main">

    <div class="greet">
      <div class="eyebrow">User Dashboard</div>
      <h1>Welcome, <span><?php echo htmlspecialchars($username); ?></span> 👋</h1>
      <p>Search trains, manage bookings, and access your complete rail account from one place.</p>
    </div>

    <?php if($msg_sent): ?>
    <div class="alert-success"><i class="fa-solid fa-circle-check" style="margin-right:6px"></i>Your message has been sent to admin successfully!</div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats">
      <div class="stat">
        <div class="sico b"><i class="fa-solid fa-ticket"></i></div>
        <div><div class="slbl">Total Bookings</div><div class="sval"><?php echo $total_bookings; ?></div></div>
      </div>
      <div class="stat">
        <div class="sico g"><i class="fa-solid fa-indian-rupee-sign"></i></div>
        <div><div class="slbl">Amount Spent</div><div class="sval">₹<?php echo number_format($total_spent); ?></div></div>
      </div>
      <div class="stat">
        <div class="sico a"><i class="fa-solid fa-train"></i></div>
        <div><div class="slbl">Trains Available</div><div class="sval"><?php echo $trains_available; ?></div></div>
      </div>
    </div>

    <!-- Action Cards -->
    <div class="cards">
      <div class="dcard cs" onclick="goTo('search')">
        <div class="cico"><i class="fa-solid fa-magnifying-glass-location"></i></div>
        <h3>Search Train</h3>
        <p>Find trains by origin, destination and travel date with live seat availability.</p>
        <div class="cact"><i class="fa-solid fa-arrow-right"></i> Search Now</div>
      </div>
      <div class="dcard cb" onclick="goTo('bookings')">
        <div class="cico"><i class="fa-solid fa-receipt"></i></div>
        <h3>Booking History</h3>
        <p>View all your previous and current bookings with complete journey details.</p>
        <div class="cact"><i class="fa-solid fa-arrow-right"></i> View Bookings</div>
      </div>
      <div class="dcard" onclick="goTo('track')" style="cursor:pointer;border-color:rgba(34,197,94,0.2)">
        <div class="cico" style="background:rgba(34,197,94,0.12);color:#22c55e"><i class="fa-solid fa-location-crosshairs"></i></div>
        <h3>Track My Train</h3>
        <p>Select your train and station to see real-time position along the route.</p>
        <div class="cact" style="color:#22c55e"><i class="fa-solid fa-arrow-right"></i> Track Now</div>
      </div>
      <div class="dcard cp" onclick="goTo('profile')">
        <div class="cico"><i class="fa-solid fa-user-circle"></i></div>
        <h3>My Profile</h3>
        <p>Manage your account details, contact information and travel preferences.</p>
        <div class="cact"><i class="fa-solid fa-arrow-right"></i> View Profile</div>
      </div>
    </div>

    <!-- Recent Bookings -->
    <div class="slabel"><i class="fa-solid fa-clock-rotate-left"></i> Recent Journeys</div>
    <?php if(count($bookings) > 0): 
      $recent = array_slice($bookings, 0, 2);
      foreach($recent as $b): ?>
      <div class="brow">
        <div>
          <div class="btrain"><?php echo htmlspecialchars($b['train_name']); ?></div>
          <div class="broute"><?php echo htmlspecialchars($b['origin']); ?> → <?php echo htmlspecialchars($b['destination']); ?></div>
          <div class="bmeta"><i class="fa-regular fa-calendar" style="margin-right:4px"></i><?php echo $b['travel_date']; ?> &nbsp;·&nbsp; <?php echo substr($b['departure_time'],0,5); ?> &nbsp;·&nbsp; <?php echo htmlspecialchars($b['berth_pref'] ?? 'N/A'); ?></div>
          <span class="bstatus"><i class="fa-solid fa-circle" style="font-size:.5rem;margin-right:4px"></i>Confirmed</span>
        </div>
        <div>
          <div class="bprice">₹<?php echo number_format($b['price']); ?></div>
          <div class="bpnr">PNR: #<?php echo $b['b_id'] + 1000; ?></div>
          <div style="margin-top:6px"><a href="print_ticket.php?id=<?php echo $b['b_id']; ?>" style="font-size:.75rem;color:var(--blue);text-decoration:none"><i class="fa-solid fa-print" style="margin-right:3px"></i>Print</a></div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php else: ?>
      <div class="empty"><i class="fa-solid fa-train"></i><p>No journeys yet. <span style="color:var(--blue);cursor:pointer" onclick="goTo('search')">Book your first train →</span></p></div>
    <?php endif; ?>

    <div class="divider"></div>

    <!-- Help -->
    <div class="help">
      <h3><i class="fa-solid fa-headset" style="margin-right:8px"></i>Need Help or Support?</h3>
      <?php if($msg_sent): ?>
      <div style="background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.3);color:#22c55e;border-radius:10px;padding:12px 16px;margin-bottom:1rem;font-size:.85rem;font-weight:600;display:flex;align-items:center;gap:8px">
        <i class="fa-solid fa-circle-check"></i> Your message has been sent to the admin successfully!
      </div>
      <?php endif; ?>
      <form action="user_dashboard.php" method="POST">
        <div class="hgrid">
          <div style="display:flex;flex-direction:column;gap:4px">
            <label style="font-size:.75rem;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.04em">Your Name</label>
            <input type="text" name="c_name" placeholder="Your name" required>
          </div>
          <div style="display:flex;flex-direction:column;gap:4px">
            <label style="font-size:.75rem;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.04em">Email Address</label>
            <input type="email" name="c_email" placeholder="Your email address" required>
          </div>
          <textarea name="c_message" class="s2" rows="3" placeholder="Describe your issue or query..." required></textarea>
        </div>
        <button type="submit" name="send_msg" class="btn-help"><i class="fa-solid fa-paper-plane" style="margin-right:6px"></i>Submit to Admin</button>
      </form>
    </div>
  </div>
</div>

<!-- ═══════════════ SEARCH & BOOK ═══════════════ -->
<div class="view" id="view-search">
  <div class="main">
    <button class="btn-back" onclick="goTo('home')"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</button>
    <div class="greet">
      <div class="eyebrow">Book Ticket</div>
      <h1>Search <span>Trains</span></h1>
      <p>Select a train, fill passenger details, then proceed to payment.</p>
    </div>

    <div class="panel">
      <form action="payment_gateway.php" method="POST" id="bookForm">
        <input type="hidden" name="train_id" id="selTrainId">
        <input type="hidden" name="journey_date" id="journeyDateHidden">

        <div class="slabel"><i class="fa-solid fa-train"></i> Select Train & Schedule</div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:10px" class="search-top-grid">
          <div class="field" style="margin:0">
            <label>Origin</label>
            <input type="text" class="sinput" id="originSearch" placeholder="Enter origin city..." autocomplete="off" style="width:100%;box-sizing:border-box">
          </div>
          <div class="field" style="margin:0">
            <label>Destination</label>
            <input type="text" class="sinput" id="destSearch" placeholder="Enter destination city..." autocomplete="off" style="width:100%;box-sizing:border-box">
          </div>
          <div class="field" style="margin:0">
            <label>Date of Journey</label>
            <input type="date" class="sinput" id="dateSearch" min="<?php echo date('Y-m-d'); ?>" style="width:100%;box-sizing:border-box;color:var(--text)" required>
          </div>
        </div>
        <div class="swrap" style="display:none">
          <i class="fa-solid fa-train sico2"></i>
          <input type="text" class="sinput" id="trainSearch" placeholder="Search by train name, origin or destination..." autocomplete="off">
          <div class="tdrop" id="trainDrop">
            <?php foreach($trains as $t): 
              $low = $t['available_seats'] <= 10;
            ?>
            <div class="topt"
              data-id="<?php echo $t['id']; ?>"
              data-name="<?php echo htmlspecialchars($t['train_name']); ?>"
              data-origin="<?php echo htmlspecialchars($t['origin']); ?>"
              data-dest="<?php echo htmlspecialchars($t['destination']); ?>"
              data-time="<?php echo substr($t['departure_time'],0,5); ?>"
              data-price="<?php echo $t['price']; ?>"
              data-seats="<?php echo $t['available_seats']; ?>">
              <div>
                <div class="tname">
                  <?php echo htmlspecialchars($t['train_name']); ?>
                  <?php if($low): ?><span class="tbadge">Only <?php echo $t['available_seats']; ?> left!</span><?php endif; ?>
                </div>
                <div class="troute"><?php echo htmlspecialchars($t['origin']); ?> → <?php echo htmlspecialchars($t['destination']); ?> &nbsp;·&nbsp; Departs <?php echo substr($t['departure_time'],0,5); ?> daily</div>
              </div>
              <div class="tprice">₹<?php echo number_format($t['price']); ?></div>
            </div>
            <?php endforeach; ?>
            <?php if(empty($trains)): ?>
            <div style="padding:1.5rem;text-align:center;color:var(--muted);font-size:.85rem"><i class="fa-solid fa-circle-exclamation" style="margin-right:6px"></i>No trains available right now.</div>
            <?php endif; ?>
          </div>
        </div>
        <div id="trainResults" style="margin-bottom:10px"></div>
        <div class="stinfo" id="stInfo">
          <div class="stname" id="stName"></div>
          <div class="chips" id="stChips"></div>
        </div>

        <div class="divider" style="margin:1.2rem 0"></div>
        <div class="slabel"><i class="fa-solid fa-users"></i> Number of Passengers</div>
        <div class="field" style="max-width:220px;margin-bottom:1.2rem">
          <label>How many passengers?</label>
          <select id="numPassengers" name="num_passengers" onchange="renderPassengerForms(this.value)">
            <option value="1">1 Passenger</option>
            <option value="2">2 Passengers</option>
            <option value="3">3 Passengers</option>
            <option value="4">4 Passengers</option>
            <option value="5">5 Passengers</option>
            <option value="6">6 Passengers</option>
          </select>
        </div>

        <div id="passengerForms"></div>

        <button type="submit" class="btn-pri" id="proceedBtn" disabled>
          <i class="fa-solid fa-lock"></i> Confirm & Proceed to Payment
        </button>
      </form>
    </div>
  </div>
</div>

<!-- ═══════════════ MY BOOKINGS ═══════════════ -->
<div class="view" id="view-bookings">
  <div class="main">
    <button class="btn-back" onclick="goTo('home')"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</button>
    <div class="greet">
      <div class="eyebrow">Journey History</div>
      <h1>My <span>Bookings</span></h1>
    </div>
    <?php if(count($bookings) > 0): foreach($bookings as $b): ?>
    <div class="brow">
      <div>
        <div class="btrain"><?php echo htmlspecialchars($b['train_name']); ?>
          <?php if($b['num_passengers'] > 1): ?>
            <span style="font-size:.72rem;background:rgba(59,130,246,.15);color:var(--blue);padding:2px 8px;border-radius:20px;margin-left:8px;font-weight:600"><?php echo $b['num_passengers']; ?> passengers</span>
          <?php endif; ?>
        </div>
        <div class="broute"><i class="fa-solid fa-route" style="margin-right:5px;font-size:.75rem"></i><?php echo htmlspecialchars($b['origin']); ?> → <?php echo htmlspecialchars($b['destination']); ?></div>
        <div class="bmeta">
          <i class="fa-regular fa-calendar" style="margin-right:4px"></i><?php echo $b['travel_date']; ?> &nbsp;·&nbsp;
          <i class="fa-regular fa-clock" style="margin-right:4px"></i><?php echo substr($b['departure_time'],0,5); ?> &nbsp;·&nbsp;
          <i class="fa-solid fa-users" style="margin-right:4px"></i><?php echo htmlspecialchars($b['passenger_names'] ?? 'N/A'); ?>
        </div>
        <span class="bstatus"><i class="fa-solid fa-circle" style="font-size:.5rem;margin-right:4px"></i>Confirmed</span>
      </div>
      <div>
        <div class="bprice">₹<?php echo number_format($b['total_amount']); ?></div>
        <div class="bpnr">TXN: <?php echo htmlspecialchars($b['transaction_id'] ?? ''); ?></div>
        <div class="bpnr"><?php echo htmlspecialchars($b['payment_method'] ?? ''); ?></div>
        <div style="margin-top:8px"><a href="receipt.php?txn=<?php echo urlencode($b['transaction_id']); ?>" style="font-size:.78rem;color:var(--blue);padding:5px 12px;border:1px solid rgba(59,130,246,.3);border-radius:7px;text-decoration:none;display:inline-flex;align-items:center;gap:5px"><i class="fa-solid fa-print"></i>View / Print</a></div>
      </div>
    </div>
    <?php endforeach; else: ?>
    <div class="empty"><i class="fa-solid fa-ticket-simple"></i><p>No bookings found.<br><span style="color:var(--blue);cursor:pointer" onclick="goTo('search')">Book your first train →</span></p></div>
    <?php endif; ?>
  </div>
</div>

<!-- ═══════════════ PROFILE ═══════════════ -->
<div class="view" id="view-profile">
  <div class="main">
    <button class="btn-back" onclick="goTo('home')"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</button>
    <div class="greet">
      <div class="eyebrow">Account</div>
      <h1>My <span>Profile</span></h1>
    </div>
    <div class="panel">
      <div class="ph">
        <div class="avatar"><?php echo strtoupper(substr($username,0,2)); ?></div>
        <div>
          <div class="pname"><?php echo htmlspecialchars($username); ?></div>
          <div class="pemail"><?php echo htmlspecialchars($user_email); ?></div>
          <div style="margin-top:8px"><span class="bstatus"><i class="fa-solid fa-circle" style="font-size:.5rem;margin-right:4px"></i>Active Account</span></div>
        </div>
      </div>
      <div class="slabel"><i class="fa-solid fa-pen-to-square"></i> Account Details</div>
      <div class="pgrid">
        <div class="field"><label>Username</label><input type="text" value="<?php echo htmlspecialchars($username); ?>" readonly></div>
        <div class="field"><label>Email Address</label><input type="email" value="<?php echo htmlspecialchars($user_email); ?>"></div>
        <div class="field"><label>Total Bookings</label><input type="text" value="<?php echo $total_bookings; ?>" readonly></div>
        <div class="field"><label>Total Spent</label><input type="text" value="₹<?php echo number_format($total_spent); ?>" readonly></div>
      </div>
      <div class="divider"></div>
      <div class="slabel"><i class="fa-solid fa-lock"></i> Change Password</div>
      <div class="pgrid">
        <div class="field"><label>Current Password</label><input type="password" placeholder="••••••••"></div>
        <div class="field"><label>New Password</label><input type="password" placeholder="••••••••"></div>
      </div>
      <button class="btn-pri" style="margin-top:1.4rem" onclick="alert('Contact admin to update your password.')">
        <i class="fa-solid fa-floppy-disk"></i> Save Changes
      </button>
    </div>
  </div>
</div>

<!-- ═══════════════ TRACK TRAIN ═══════════════ -->
<div class="view" id="view-track">
  <div class="main">
    <button class="btn-back" onclick="goTo('home')"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</button>
    <div class="greet">
      <div class="eyebrow">Live Tracking</div>
      <h1>Where is My <span>Train?</span></h1>
      <p>Select your train and check its current position between stations.</p>
    </div>

    <div class="panel">
      <!-- Step 1: Select Train -->
      <div class="slabel"><i class="fa-solid fa-train"></i> Select Train</div>
      <div class="field" style="margin-bottom:1rem">
        <select id="trackTrainSelect" onchange="onTrackTrainChange(this.value)" style="width:100%">
          <option value="">-- Choose a train --</option>
          <?php
          // Tracking shows ALL trains
          $all_trains_res = mysqli_query($conn, "SELECT * FROM trains ORDER BY departure_time ASC");
          while($t = mysqli_fetch_assoc($all_trains_res)):
          ?>
          <option value="<?php echo $t['id']; ?>"
            data-name="<?php echo htmlspecialchars($t['train_name']); ?>"
            data-origin="<?php echo htmlspecialchars($t['origin']); ?>"
            data-dest="<?php echo htmlspecialchars($t['destination']); ?>"
            data-dep="<?php echo $t['departure_time']; ?>"
            data-price="<?php echo $t['price']; ?>"
            data-stops="<?php echo htmlspecialchars($t['stops'] ?? '[]'); ?>">
            <?php echo htmlspecialchars($t['train_name']); ?> — <?php echo htmlspecialchars($t['origin']); ?> → <?php echo htmlspecialchars($t['destination']); ?>
          </option>
          <?php endwhile; ?>
        </select>
      </div>

      <!-- Step 2: Select intermediate station -->
      <div id="trackStationWrap" style="display:none">
        <div class="slabel"><i class="fa-solid fa-calendar-day"></i> Journey Date</div>
        <div class="field" style="margin-bottom:1.2rem;max-width:220px">
          <input type="date" id="trackJourneyDate" class="sinput" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" onchange="runTracking()" style="color:var(--text)">
        </div>
        <div class="slabel"><i class="fa-solid fa-map-pin"></i> Select Your Station</div>
        <p style="font-size:.82rem;color:var(--muted);margin-bottom:.8rem">Choose any station between origin and destination to check where the train is relative to it.</p>
        <div class="field" style="margin-bottom:1.2rem">
          <select id="trackStationSelect" onchange="runTracking()" style="width:100%">
            <option value="">-- Select a station --</option>
          </select>
        </div>
        <button class="btn-pri" onclick="runTracking()" style="margin-bottom:1.4rem">
          <i class="fa-solid fa-location-crosshairs"></i> Track Now
        </button>
      </div>

      <!-- Result panel -->
      <div id="trackResult" style="display:none">
        <div class="divider" style="margin:1rem 0"></div>
        <div id="trackStatusBadge" style="display:inline-flex;align-items:center;gap:8px;padding:8px 16px;border-radius:999px;font-size:.82rem;font-weight:700;margin-bottom:1.2rem"></div>
        <div id="trackJourneyLabel" style="font-size:.78rem;color:var(--muted);margin-bottom:1.4rem"></div>

        <!-- Route progress bar -->
        <div id="trackTimeline" style="position:relative;padding:0 12px;margin-bottom:1.8rem"></div>

        <!-- Info cards -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px" id="trackInfoCards"></div>
      </div>
    </div>
  </div>
</div>


<script>
// ── NAVIGATION ──
function goTo(v){
  document.querySelectorAll('.view').forEach(x=>x.classList.remove('active'));
  document.getElementById('view-'+v).classList.add('active');
  document.querySelectorAll('.nbtn[id^="nav-"]').forEach(b=>b.classList.remove('active'));
  if(v==='search') document.getElementById('nav-search').classList.add('active');
  if(v==='bookings') document.getElementById('nav-bookings').classList.add('active');
  if(v==='track') document.getElementById('nav-track').classList.add('active');
  window.scrollTo(0,0);
}

// ── TRAIN SEARCH WITH ORIGIN / DESTINATION ──
const originEl  = document.getElementById('originSearch');
const destEl    = document.getElementById('destSearch');
const dateEl    = document.getElementById('dateSearch');
const opts      = document.getElementById('trainDrop').querySelectorAll('.topt');
const stInfo    = document.getElementById('stInfo');
const selIdEl   = document.getElementById('selTrainId');
const proceedBtn= document.getElementById('proceedBtn');
const trainResultsEl = document.getElementById('trainResults');

function filterTrains() {
  const oq = originEl.value.toLowerCase().trim();
  const dq = destEl.value.toLowerCase().trim();
  const userDate = dateEl ? dateEl.value : '';
  let results = [];
  opts.forEach(o => {
    const oMatch = !oq || o.dataset.origin.toLowerCase().includes(oq);
    const dMatch = !dq || o.dataset.dest.toLowerCase().includes(dq);
    if (oMatch && dMatch) results.push(o);
  });
  if ((oq || dq) && results.length > 0) {
    trainResultsEl.innerHTML = results.map(o => `
      <div class="topt" style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;background:var(--ibg,rgba(255,255,255,0.05));border:1px solid var(--border);border-radius:10px;margin-bottom:8px;cursor:pointer"
        onclick="selectTrain('${o.dataset.id}','${o.dataset.name.replace(/'/g,"\\'")}','${o.dataset.origin}','${o.dataset.dest}','${o.dataset.time}','${o.dataset.price}','${o.dataset.seats}')">
        <div>
          <div class="tname">${o.dataset.name}</div>
          <div class="troute" style="font-size:.78rem;color:var(--muted)">${o.dataset.origin} → ${o.dataset.dest} &nbsp;·&nbsp; Departs ${o.dataset.time} daily</div>
        </div>
        <div class="tprice">₹${Number(o.dataset.price).toLocaleString('en-IN')}</div>
      </div>`).join('');
  } else if (oq || dq) {
    trainResultsEl.innerHTML = `<div style="padding:1rem;text-align:center;color:var(--muted);font-size:.85rem"><i class="fa-solid fa-circle-exclamation" style="margin-right:6px"></i>No trains found for this route.</div>`;
  } else {
    trainResultsEl.innerHTML = '';
  }
}

function selectTrain(id, name, origin, dest, time, price, seats) {
  const userDate = dateEl ? dateEl.value : '';
  if (!userDate) { alert('Please select a journey date first.'); dateEl.focus(); return; }
  selIdEl.value = id;
  // Store journey date to pass to payment
  document.getElementById('bookForm').dataset.journeyDate = userDate;
  document.getElementById('stName').innerHTML =
    `<i class="fa-solid fa-train" style="margin-right:6px;color:var(--blue)"></i>${name}`;
  document.getElementById('stChips').innerHTML = `
    <div class="chip"><i class="fa-solid fa-location-dot"></i>${origin}</div>
    <div class="chip"><i class="fa-solid fa-arrow-right"></i>${dest}</div>
    <div class="chip"><i class="fa-regular fa-calendar"></i>${userDate}</div>
    <div class="chip"><i class="fa-regular fa-clock"></i>${time}</div>
    <div class="chip"><i class="fa-solid fa-chair"></i>${seats} seats</div>
    <div class="chip" style="color:var(--green)"><i class="fa-solid fa-indian-rupee-sign"></i>${Number(price).toLocaleString('en-IN')}</div>`;
  stInfo.classList.add('visible');
  trainResultsEl.innerHTML = '';
  checkProceed();
}

originEl.addEventListener('input', filterTrains);
destEl.addEventListener('input', filterTrains);
if(dateEl) dateEl.addEventListener('change', filterTrains);

function makePassengerBlock(i){
  const wrap = document.createElement('div');
  wrap.innerHTML = `
    <div class="slabel" style="margin-top:${i>1?'1.4rem':'0'}"><i class="fa-solid fa-user"></i> Passenger ${i}</div>
    <div class="fgrid">
      <div class="field"><label>Full Name</label><input type="text" name="p_name[]" class="pfield" placeholder="Enter full name" required></div>
      <div class="field"><label>Age</label><input type="number" name="p_age[]" class="pfield" placeholder="Enter age" min="1" max="120" required></div>
      <div class="field"><label>Gender</label>
        <select name="p_gender[]" class="pfield" required>
          <option value="" disabled selected>Select Gender</option>
          <option>Male</option><option>Female</option><option>Other</option>
        </select>
      </div>
      <div class="field"><label>Berth Preference</label>
        <select name="p_berth[]" class="pfield" required>
          <option value="" disabled selected>Select Berth</option>
          <option>Lower Berth</option><option>Middle Berth</option><option>Upper Berth</option><option>Side Lower</option>
        </select>
      </div>
    </div>`;
  wrap.querySelectorAll('.pfield').forEach(el=>{
    el.addEventListener('input', checkProceed);
    el.addEventListener('change', checkProceed);
  });
  return wrap;
}

function renderPassengerForms(n){
  const container = document.getElementById('passengerForms');
  container.innerHTML = '';
  for(let i=1;i<=parseInt(n);i++){
    container.appendChild(makePassengerBlock(i));
  }
  checkProceed();
}

function checkProceed(){
  if(!selIdEl.value){ proceedBtn.disabled=true; return; }
  const fields = document.querySelectorAll('#passengerForms .pfield');
  if(!fields.length){ proceedBtn.disabled=true; return; }
  const allFilled = Array.from(fields).every(f=>f.value.trim()!=='');
  proceedBtn.disabled = !allFilled;
}

// Init with 1 passenger on page load
renderPassengerForms(1);

// Guard: validate all fields before submit
document.getElementById('bookForm').addEventListener('submit', e=>{
  if(!selIdEl.value){
    e.preventDefault();
    alert('Please select a train first.');
    return;
  }
  const userDate = dateEl ? dateEl.value : '';
  if(!userDate){
    e.preventDefault();
    alert('Please select a journey date.');
    dateEl.focus();
    return;
  }
  document.getElementById('journeyDateHidden').value = userDate;
  const fields = document.querySelectorAll('#passengerForms .pfield');
  const allFilled = Array.from(fields).every(f=>f.value.trim()!=='');
  if(!allFilled){
    e.preventDefault();
    alert('Please fill in all passenger details.');
    return;
  }
});

// Auto-open correct view if URL hash present
const hash = window.location.hash.replace('#','');
if(['search','bookings','profile','track'].includes(hash)) goTo(hash);

// ── WHERE IS MY TRAIN ──
// Real intermediate stations for every route in the DB
const STATION_MAP = {
  // Hospet → Bengaluru / Bangalore (Hampi Express & SINDHANUR EXPRESS)
  'hospet-bengaluru': [
    'Hospet Jn', 'Ginigera', 'Bellary Jn', 'Adoni', 'Guntakal Jn',
    'Dharmavaram Jn', 'Hindupur', 'Bangarpet', 'Tumkur', 'Yeshwanthpur', 'Bengaluru City'
  ],
  'hospet-bangalore': [
    'Hospet Jn', 'Ginigera', 'Bellary Jn', 'Adoni', 'Guntakal Jn',
    'Dharmavaram Jn', 'Hindupur', 'Bangarpet', 'Tumkur', 'Yeshwanthpur', 'Bangalore City'
  ],

  // Mumbai → Delhi (Express 101)
  'mumbai-delhi': [
    'Mumbai Central', 'Borivali', 'Surat', 'Baroda (Vadodara)', 'Ratlam Jn',
    'Kota Jn', 'Sawai Madhopur', 'Bharatpur', 'Mathura Jn',
    'Agra Cantt', 'Hazrat Nizamuddin', 'New Delhi'
  ],

  // Mumbai → Pune (Transit Plus)
  'mumbai-pune': [
    'Mumbai CSMT', 'Dadar', 'Thane', 'Kalyan Jn', 'Karjat',
    'Khopoli', 'Lonavala', 'Talegaon', 'Dehu Road', 'Shivajinagar', 'Pune Jn'
  ],

  // Bangalore → Chennai (RailStream Bullet)
  'bangalore-chennai': [
    'Bangalore City Jn', 'Krishnarajapuram', 'Whitefield', 'Bangarapet',
    'Jolarpettai Jn', 'Ambur', 'Vaniyambadi', 'Katpadi Jn',
    'Walajah Road', 'Arakkonam Jn', 'Chennai Central'
  ],
  'bengaluru-chennai': [
    'Bengaluru City Jn', 'Krishnarajapuram', 'Whitefield', 'Bangarapet',
    'Jolarpettai Jn', 'Ambur', 'Vaniyambadi', 'Katpadi Jn',
    'Walajah Road', 'Arakkonam Jn', 'Chennai Central'
  ],
};

// Real approx journey durations in minutes for each route
const JOURNEY_DURATION = {
  'hospet-bengaluru':   480,  // 8 hrs
  'hospet-bangalore':   480,
  'mumbai-delhi':       960,  // 16 hrs
  'mumbai-pune':        180,  // 3 hrs
  'bangalore-chennai':  300,  // 5 hrs
  'bengaluru-chennai':  300,
};

function getRouteKey(origin, dest) {
  return (origin + '-' + dest).toLowerCase().replace(/\s+/g, '');
}

function getStations(origin, dest) {
  const key = getRouteKey(origin, dest);
  return STATION_MAP[key] || [origin, 'Junction A', 'Midpoint Junction', 'Junction B', dest];
}

function getJourneyMinutes(origin, dest) {
  const key = getRouteKey(origin, dest);
  return JOURNEY_DURATION[key] || 300;
}

function onTrackTrainChange(trainId) {
  const sel = document.getElementById('trackTrainSelect');
  const opt = sel.options[sel.selectedIndex];
  document.getElementById('trackResult').style.display = 'none';
  if (!trainId) {
    document.getElementById('trackStationWrap').style.display = 'none';
    return;
  }

  const origin   = opt.dataset.origin;
  const dest     = opt.dataset.dest;
  // Use stops saved by admin if available, else fall back to STATION_MAP
  let stations;
  try {
    const savedStops = JSON.parse(opt.dataset.stops || '[]');
    if (savedStops && savedStops.length > 0) {
      // Ensure origin and dest are included at start/end
      stations = [origin, ...savedStops.filter(s => s !== origin && s !== dest), dest];
    } else {
      stations = getStations(origin, dest);
    }
  } catch(e) {
    stations = getStations(origin, dest);
  }

  const stSel = document.getElementById('trackStationSelect');
  stSel.innerHTML = '<option value="">-- Select a station --</option>';
  stations.forEach((s, i) => {
    const o = document.createElement('option');
    o.value = i;
    o.textContent = (i === 0 ? '🟢 ' : i === stations.length - 1 ? '🔴 ' : '📍 ') + s;
    stSel.appendChild(o);
  });
  document.getElementById('trackStationWrap').style.display = 'block';
}

function runTracking() {
  const sel   = document.getElementById('trackTrainSelect');
  const opt   = sel.options[sel.selectedIndex];
  const stSel = document.getElementById('trackStationSelect');
  const stIdx = parseInt(stSel.value);
  if (!opt || !opt.value || isNaN(stIdx)) return;

  const origin      = opt.dataset.origin;
  const dest        = opt.dataset.dest;
  const depTime     = opt.dataset.dep;    // HH:MM:SS from PHP
  const travelDate  = document.getElementById('trackJourneyDate').value || new Date().toISOString().slice(0,10);
  const trainName   = opt.dataset.name;
  let stations;
  try {
    const savedStops2 = JSON.parse(opt.dataset.stops || '[]');
    if (savedStops2 && savedStops2.length > 0) {
      stations = [origin, ...savedStops2.filter(s => s !== origin && s !== dest), dest];
    } else {
      stations = getStations(origin, dest);
    }
  } catch(e) { stations = getStations(origin, dest); }
  const totalSeg    = stations.length - 1;
  const totalMin    = getJourneyMinutes(origin, dest);
  const minPerSeg   = totalMin / totalSeg;

  // Build departure datetime using travel_date + departure_time
  const [dh, dm] = depTime.split(':').map(Number);
  const [dy, dmo, dd] = travelDate.split('-').map(Number);
  const dep = new Date(dy, dmo - 1, dd, dh, dm, 0, 0);
  const arr = new Date(dep.getTime() + totalMin * 60000);
  const now = new Date();

  // Minutes elapsed since departure (can be negative = not yet departed)
  const elapsedMin = (now - dep) / 60000;

  // Clamp to [0, totalMin]
  const progressMin = Math.max(0, Math.min(totalMin, elapsedMin));
  // Current position in segment units (0.0 to totalSeg)
  const currentSeg = progressMin / minPerSeg;
  // Which station dot is "current" (the one the train just left or is at)
  const currentDotIdx = Math.min(Math.floor(currentSeg), totalSeg);

  const selectedStation = stations[stIdx];

  // Minutes the train spends at/around each station index
  const minsAtSelectedStation = stIdx * minPerSeg;
  const minsToStation = minsAtSelectedStation - elapsedMin;

  // Status logic
  let statusText, statusColor, statusIcon, statusBg;
  if (elapsedMin < 0) {
    // Train hasn't departed yet
    statusText = `Departs in ${Math.ceil(-elapsedMin)} min`;
    statusColor = '#a78bfa'; statusIcon = 'fa-clock'; statusBg = 'rgba(167,139,250,0.12)';
  } else if (currentSeg >= totalSeg) {
    statusText = `Arrived at ${dest}`;
    statusColor = '#22c55e'; statusIcon = 'fa-circle-check'; statusBg = 'rgba(34,197,94,0.12)';
  } else if (minsToStation > 5) {
    statusText = `Approaching ${selectedStation}`;
    statusColor = '#f59e0b'; statusIcon = 'fa-train'; statusBg = 'rgba(245,158,11,0.12)';
  } else if (minsToStation >= -10) {
    statusText = `At / Near ${selectedStation}`;
    statusColor = '#22c55e'; statusIcon = 'fa-circle-dot'; statusBg = 'rgba(34,197,94,0.12)';
  } else {
    statusText = `Departed ${selectedStation}`;
    statusColor = '#64748b'; statusIcon = 'fa-train-tram'; statusBg = 'rgba(100,116,139,0.12)';
  }

  // ETA at selected station
  let etaText;
  if (elapsedMin < 0) {
    // Departs in future — ETA = dep + minsAtSelectedStation
    const etaDate = new Date(dep.getTime() + minsAtSelectedStation * 60000);
    etaText = etaDate.getHours().toString().padStart(2,'0') + ':' + etaDate.getMinutes().toString().padStart(2,'0');
  } else if (minsToStation <= 0) {
    etaText = 'Already passed';
  } else {
    const etaDate = new Date(now.getTime() + minsToStation * 60000);
    etaText = '~' + etaDate.getHours().toString().padStart(2,'0') + ':' + etaDate.getMinutes().toString().padStart(2,'0');
  }

  const arrStr = arr.getHours().toString().padStart(2,'0') + ':' + arr.getMinutes().toString().padStart(2,'0');
  const progress = Math.max(0, Math.min(100, Math.round((progressMin / totalMin) * 100)));

  // ─── RENDER ───
  document.getElementById('trackResult').style.display = 'block';

  const badge = document.getElementById('trackStatusBadge');
  badge.innerHTML = `<i class="fa-solid ${statusIcon}" style="color:${statusColor}"></i><span style="color:${statusColor}">${statusText}</span>`;
  badge.style.background = statusBg;
  badge.style.border = `1px solid ${statusColor}55`;

  document.getElementById('trackJourneyLabel').innerHTML =
    `<i class="fa-solid fa-train" style="margin-right:5px;color:var(--blue)"></i>
     <strong>${trainName}</strong> &nbsp;·&nbsp; ${origin} → ${dest}
     &nbsp;·&nbsp; Departs <strong>${dh.toString().padStart(2,'0')}:${dm.toString().padStart(2,'0')}</strong>
     on <strong>${travelDate}</strong>`;

  // ─── TIMELINE ───
  let tlHtml = `<div style="overflow-x:auto;padding-bottom:12px">
    <div style="display:flex;align-items:flex-start;min-width:${stations.length * 80}px;padding:8px 0">`;

  stations.forEach((s, i) => {
    const passed   = currentSeg > i + 0.05;
    const isCurr   = i === currentDotIdx && currentSeg < totalSeg;
    const isArrive = currentSeg >= totalSeg && i === totalSeg;
    const isSel    = i === stIdx;

    const dotBg     = (passed || isArrive) ? '#3b82f6' : (isCurr ? '#3b82f6' : 'var(--border,#334155)');
    const ringColor = isSel ? '#f59e0b' : (isCurr || isArrive ? '#3b82f6' : 'transparent');
    const labelClr  = isSel ? '#f59e0b' : (passed || isCurr || isArrive ? 'var(--text,#f1f5f9)' : 'var(--muted,#64748b)');
    const dotSize   = isCurr || isArrive || isSel ? 20 : 12;

    // Line before this dot
    const lineLeft = i > 0 ? (currentSeg >= i ? '#3b82f6' : 'var(--border,#334155)') : null;

    tlHtml += `<div style="display:flex;flex-direction:column;align-items:center;flex:1;min-width:72px">
      <div style="display:flex;align-items:center;width:100%;height:28px">`;

    if (i > 0) {
      tlHtml += `<div style="flex:1;height:3px;background:${lineLeft};border-radius:2px;transition:background .4s"></div>`;
    }

    tlHtml += `<div style="
      width:${dotSize}px;height:${dotSize}px;border-radius:50%;
      background:${dotBg};
      border:3px solid ${ringColor};
      flex-shrink:0;
      transition:all .3s;
      box-shadow:${isCurr||isArrive ? '0 0 0 5px rgba(59,130,246,0.2)' : isSel ? '0 0 0 5px rgba(245,158,11,0.2)' : 'none'};
      position:relative;z-index:1
    "></div>`;

    if (i < stations.length - 1) {
      const lineRight = currentSeg > i + 0.5 ? '#3b82f6' : 'var(--border,#334155)';
      tlHtml += `<div style="flex:1;height:3px;background:${lineRight};border-radius:2px;transition:background .4s"></div>`;
    }

    tlHtml += `</div>`;

    // Label
    tlHtml += `<div style="font-size:.62rem;color:${labelClr};font-weight:${isSel?700:500};text-align:center;margin-top:6px;line-height:1.3;padding:0 2px;word-break:break-word">
      ${s}${isSel ? ' ★' : ''}
    </div>`;
    if (isCurr) tlHtml += `<div style="font-size:.58rem;color:#3b82f6;font-weight:800;margin-top:3px;letter-spacing:.03em">▲ HERE</div>`;
    if (isArrive) tlHtml += `<div style="font-size:.58rem;color:#22c55e;font-weight:800;margin-top:3px">✓ ARRIVED</div>`;

    tlHtml += `</div>`;
  });

  tlHtml += `</div></div>`;

  // Progress bar
  tlHtml += `<div style="margin-top:8px">
    <div style="display:flex;justify-content:space-between;font-size:.72rem;color:var(--muted);margin-bottom:4px">
      <span>${origin}</span><span style="color:var(--blue);font-weight:700">${progress}% complete</span><span>${dest}</span>
    </div>
    <div style="background:var(--border,#334155);border-radius:999px;height:6px;overflow:hidden">
      <div style="width:${progress}%;background:linear-gradient(90deg,#3b82f6,#60a5fa);height:100%;border-radius:999px;transition:width .6s ease"></div>
    </div>
  </div>`;

  document.getElementById('trackTimeline').innerHTML = tlHtml;

  // ─── INFO CARDS ───
  document.getElementById('trackInfoCards').innerHTML = `
    <div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:10px;padding:12px 14px">
      <div style="color:#3b82f6;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">ETA at ${selectedStation}</div>
      <div style="font-size:1.05rem;font-weight:700">${etaText}</div>
    </div>
    <div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:10px;padding:12px 14px">
      <div style="color:#3b82f6;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Arrives at ${dest}</div>
      <div style="font-size:1.05rem;font-weight:700">${arrStr}</div>
    </div>
    <div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:10px;padding:12px 14px">
      <div style="color:#3b82f6;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Current Station</div>
      <div style="font-size:.9rem;font-weight:600">${stations[currentDotIdx]}</div>
    </div>
    <div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:10px;padding:12px 14px">
      <div style="color:#3b82f6;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Journey Duration</div>
      <div style="font-size:.9rem;font-weight:600">${Math.floor(totalMin/60)}h ${totalMin%60>0?totalMin%60+'m':''}</div>
    </div>`;
}

// Auto-refresh every 60s if tracking is visible
setInterval(() => {
  if (document.getElementById('trackResult').style.display !== 'none') runTracking();
}, 60000);


</script>
</body>
</html>
