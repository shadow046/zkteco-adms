<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle ?? 'shadow046/zkteco-adms' }}</title>
    <style>
        :root {
            --bg: #07111f;
            --bg-2: #0b1730;
            --panel: rgba(20, 24, 35, 0.88);
            --line: rgba(255,255,255,0.08);
            --text: #eef2ff;
            --muted: #97a3b6;
            --blue: #4f7cff;
            --teal: #2dd4bf;
            --green: #22c55e;
            --red: #f87171;
            --yellow: #fbbf24;
            --shadow: 0 24px 60px rgba(0, 0, 0, 0.35);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(79, 124, 255, 0.20), transparent 18%),
                radial-gradient(circle at top right, rgba(45, 212, 191, 0.14), transparent 16%),
                linear-gradient(180deg, #08111d 0%, var(--bg) 36%, var(--bg-2) 100%);
            min-height: 100vh;
        }
        .shell { max-width: 1180px; margin: 0 auto; padding: 40px 18px; }
        .frame {
            overflow: hidden;
            border-radius: 22px;
            background: linear-gradient(180deg, rgba(18, 20, 31, 0.97), rgba(14, 17, 26, 0.98));
            border: 1px solid var(--line);
            box-shadow: var(--shadow);
        }
        .window-chrome {
            display: flex; align-items: center; justify-content: space-between; gap: 16px;
            padding: 14px 16px; border-bottom: 1px solid var(--line);
            background: linear-gradient(180deg, rgba(28, 22, 39, 0.94), rgba(23, 19, 31, 0.96));
        }
        .chrome-dots { display: flex; gap: 8px; }
        .chrome-dots span { width: 12px; height: 12px; border-radius: 50%; display:block; background: rgba(167, 139, 250, 0.50); }
        .address-pill {
            min-width: 280px; display: inline-flex; align-items: center; justify-content: center; gap: 10px;
            padding: 8px 16px; border-radius: 10px; color: #c9d4ea; background: rgba(14, 17, 29, 0.72);
            border: 1px solid rgba(167, 139, 250, 0.16); font-size: 0.9rem;
        }
        .lock-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--teal); box-shadow: 0 0 12px rgba(45, 212, 191, 0.45); }
        .app-nav {
            display: grid; grid-template-columns: auto 1fr; gap: 18px; align-items: center;
            padding: 14px 18px; border-bottom: 1px solid var(--line); background: rgba(24, 24, 24, 0.74);
        }
        .brand { display:inline-flex; align-items:center; gap:8px; font-weight:700; color:#f5f7ff; font-size:0.95rem; }
        .brand-mark { width:14px; height:14px; background:linear-gradient(135deg,#fff,#9fb5ff); clip-path: polygon(0 60%, 52% 0, 100% 0, 48% 100%, 0 100%, 24% 60%); }
        .nav-links { display:flex; gap:16px; flex-wrap:wrap; }
        .nav-links a { color: var(--muted); font-size: .84rem; text-decoration:none; }
        .nav-links a.active { color: var(--blue); }
        .content { padding: 22px 18px; background: linear-gradient(180deg, rgba(26,26,26,.88), rgba(17,19,28,.98)); }
        .header { display:flex; justify-content:space-between; gap:18px; align-items:end; margin-bottom:22px; flex-wrap:wrap; }
        .eyebrow { color: var(--blue); font-size: .74rem; text-transform:uppercase; letter-spacing:.12em; margin-bottom: 8px; }
        h1 { margin:0; font-size:2rem; letter-spacing:-.03em; }
        .meta { color: var(--muted); margin-top:10px; line-height:1.6; max-width: 760px; }
        .flash {
            margin-bottom: 16px; padding: 14px 16px; border-radius: 16px; border: 1px solid rgba(45,212,191,.24);
            background: rgba(19,78,74,.22); color: #ccfbf1;
        }
        .flash.error { border-color: rgba(248,113,113,.24); background: rgba(127,29,29,.22); color: #fecaca; }
        .cards { display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap:12px; margin-bottom:18px; }
        .card { padding: 16px; border-radius: 16px; background: var(--panel); border: 1px solid var(--line); }
        .card-label { color: var(--muted); font-size: .78rem; text-transform: uppercase; letter-spacing: .08em; }
        .card-value { font-size: 2rem; font-weight: 700; margin-top: 10px; }
        .grid { display:grid; grid-template-columns: 360px minmax(0,1fr); gap:18px; }
        .panel { border-radius: 18px; background: var(--panel); border: 1px solid var(--line); padding: 18px; }
        .panel h2 { margin: 0 0 8px; font-size: 1.1rem; }
        .stack { display:grid; gap:12px; }
        label { display:grid; gap:8px; color: var(--muted); font-size:.92rem; }
        input, select, textarea {
            width:100%; border-radius:14px; border:1px solid rgba(255,255,255,.08); background: rgba(20,24,35,.95);
            color: var(--text); padding: 12px 14px; font: inherit;
        }
        button, .button {
            display:inline-flex; align-items:center; justify-content:center; border:none; text-decoration:none; cursor:pointer;
            min-height: 44px; padding: 0 18px; border-radius: 999px; font-weight: 700; color: white;
            background: linear-gradient(135deg, var(--blue), #5b8dff);
        }
        .table-panel { border-radius:18px; background: var(--panel); border:1px solid var(--line); overflow:hidden; }
        .table-head { display:flex; justify-content:space-between; gap:12px; align-items:center; padding:16px 18px; border-bottom:1px solid var(--line); }
        .count { color: var(--muted); font-size: .92rem; }
        .table-wrap { overflow:auto; }
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        th, td { padding: 13px 12px; text-align:left; border-bottom:1px solid rgba(255,255,255,.08); }
        th { color: #c9d1e3; font-size: .8rem; text-transform: uppercase; letter-spacing: .08em; }
        td { color: var(--text); }
        .pagination { padding: 16px 18px; color: var(--muted); }
        .pagination nav > div:first-child { display:none; }
        .pagination nav span, .pagination nav a {
            display:inline-flex; align-items:center; justify-content:center; min-width:36px; height:36px;
            border-radius:10px; border:1px solid rgba(148,163,184,.18); margin-left:8px; text-decoration:none;
            color: var(--text); background: rgba(15,23,42,.7);
        }
        .badge {
            display:inline-flex; align-items:center; border-radius:999px; padding:4px 10px;
            font-size:.8rem; background: rgba(45,212,191,.12); color:#bff5ec; border:1px solid rgba(45,212,191,.18);
        }
        @media (max-width: 960px) {
            .shell { padding: 12px 8px 16px; }
            .address-pill { min-width: 0; width: 100%; }
            .app-nav, .grid, .cards { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="frame">
            <div class="window-chrome">
                <div class="chrome-dots"><span></span><span></span><span></span></div>
                <div class="address-pill"><span class="lock-dot"></span><span>{{ url('/'.$uiPrefix) }}</span></div>
                <div style="width:58px;"></div>
            </div>
            <div class="app-nav">
                <div class="brand"><span class="brand-mark"></span><span>shadow046/zkteco-adms</span></div>
                <div class="nav-links">
                    <a class="{{ ($activeNav ?? '') === 'dashboard' ? 'active' : '' }}" href="{{ url('/'.$uiPrefix.'/dashboard') }}">Dashboard</a>
                    <a class="{{ ($activeNav ?? '') === 'attendance' ? 'active' : '' }}" href="{{ url('/'.$uiPrefix.'/attendance') }}">Attendance</a>
                    <a class="{{ ($activeNav ?? '') === 'daily-logs' ? 'active' : '' }}" href="{{ url('/'.$uiPrefix.'/daily-logs') }}">Daily Logs</a>
                    <a class="{{ ($activeNav ?? '') === 'sequence-audit' ? 'active' : '' }}" href="{{ url('/'.$uiPrefix.'/sequence-audit') }}">Sequence Audit</a>
                </div>
            </div>
            <div class="content">
                @if ($errors->any())
                    <div class="flash error">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif
                @if (session('status'))
                    <div class="flash">
                        <div>{{ session('status') }}</div>
                        @if (session('command_text'))
                            <div style="margin-top:6px; color:#99f6e4;">{{ session('command_text') }}</div>
                        @endif
                    </div>
                @endif
                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
