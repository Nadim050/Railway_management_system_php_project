<?php
// Shared admin head styles - include inside <head> tag
?>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    :root {
        --bg: #07090f; --surface: #0d1117; --card: #111827;
        --border: rgba(255,255,255,0.07);
        --accent-blue: #3b82f6; --text: #f1f5f9; --muted: #64748b;
    }
    * { margin:0; padding:0; box-sizing:border-box; }
    body {
        background: linear-gradient(rgba(0,0,0,0.72), rgba(0,0,0,0.72)), url('train.jpg');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        color: var(--text); font-family: 'DM Sans', sans-serif; min-height:100vh;
    }
    body::before {
        content: '';
        position: fixed; inset: 0;
        background-image: radial-gradient(1px 1px at 10% 15%, rgba(255,255,255,0.15) 0%, transparent 100%),
            radial-gradient(1px 1px at 55% 25%, rgba(255,255,255,0.12) 0%, transparent 100%),
            radial-gradient(1px 1px at 75% 70%, rgba(255,255,255,0.1) 0%, transparent 100%),
            radial-gradient(1px 1px at 90% 40%, rgba(255,255,255,0.15) 0%, transparent 100%);
        pointer-events: none; z-index: 0;
    }
    .admin-nav {
        position: sticky; top: 0; z-index: 100;
        background: rgba(13,17,23,0.9); backdrop-filter: blur(20px);
        border-bottom: 1px solid var(--border);
        padding: 0 2rem; height: 60px;
        display: flex; align-items: center; justify-content: space-between;
    }
    .nav-left { display: flex; align-items: center; gap: 20px; }
    .nav-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; color: var(--text); }
    .nav-logo .icon-box {
        width: 32px; height: 32px; border-radius: 9px;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        display: flex; align-items: center; justify-content: center; font-size: 14px;
    }
    .nav-logo span { font-family: 'Syne', sans-serif; font-size: 1.05rem; font-weight: 700; }
    .back-link {
        display: flex; align-items: center; gap: 6px;
        color: var(--muted); text-decoration: none; font-size: 0.82rem;
        padding: 5px 12px; border-radius: 7px;
        border: 1px solid var(--border); transition: all 0.2s;
    }
    .back-link:hover { color: var(--text); border-color: rgba(255,255,255,0.15); }
    .nav-right { display: flex; align-items: center; gap: 12px; }
    .nav-right a { color: var(--muted); text-decoration: none; font-size: 0.82rem; transition: color 0.2s; display:flex; align-items:center; gap:5px; }
    .nav-right a:hover { color: var(--text); }
    .btn-logout {
        background: rgba(244,63,94,0.1); border: 1px solid rgba(244,63,94,0.2);
        color: #f43f5e !important; padding: 5px 14px; border-radius: 7px; font-weight: 600;
    }
    .btn-logout:hover { background: rgba(244,63,94,0.2) !important; }
    .page-main { position: relative; z-index: 1; padding: 2rem 2rem 3rem; max-width: 1300px; margin: 0 auto; }
    .page-header { margin-bottom: 2rem; }
    .page-header h1 { font-family: 'Syne', sans-serif; font-size: 2rem; font-weight: 800; margin-bottom: 4px; }
    .page-header p { color: var(--muted); font-size: 0.88rem; }
    .card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 1.5rem; }
    .input-field {
        width: 100%; background: rgba(255,255,255,0.04); border: 1px solid var(--border);
        border-radius: 10px; padding: 12px 14px; color: var(--text); font-size: 0.88rem;
        outline: none; transition: border-color 0.2s; font-family: 'DM Sans', sans-serif;
    }
    .input-field:focus { border-color: #3b82f6; }
    .input-field::placeholder { color: var(--muted); }
    .btn-primary {
        background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff;
        border: none; border-radius: 10px; padding: 12px 24px;
        font-weight: 600; font-size: 0.88rem; cursor: pointer; transition: opacity 0.2s;
        font-family: 'DM Sans', sans-serif; display: inline-flex; align-items: center; gap: 8px;
        text-decoration: none;
    }
    .btn-primary:hover { opacity: 0.88; }
    .btn-danger {
        background: rgba(244,63,94,0.1); border: 1px solid rgba(244,63,94,0.2);
        color: #f43f5e; border-radius: 8px; padding: 6px 14px;
        font-size: 0.78rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
        font-family: 'DM Sans', sans-serif; text-decoration: none; display: inline-block;
    }
    .btn-danger:hover { background: rgba(244,63,94,0.2); }
    table { width: 100%; border-collapse: collapse; }
    thead th { color: var(--muted); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; padding: 0 12px 12px; text-align: left; border-bottom: 1px solid var(--border); }
    tbody tr { border-bottom: 1px solid rgba(255,255,255,0.03); transition: background 0.15s; }
    tbody tr:hover { background: rgba(255,255,255,0.02); }
    tbody td { padding: 14px 12px; font-size: 0.85rem; }
    .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; }
    .badge-green { background: rgba(34,197,94,0.12); color: #22c55e; }
    .badge-blue  { background: rgba(59,130,246,0.12); color: #3b82f6; }
    .badge-amber { background: rgba(245,158,11,0.12); color: #f59e0b; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
    label { display: block; color: var(--muted); font-size: 0.75rem; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.04em; }
    @media(max-width:768px) { .grid-2,.grid-3 { grid-template-columns: 1fr; } }
</style>
