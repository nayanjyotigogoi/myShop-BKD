<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MyShop Super Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        :root {
            --bg-main: #0f172a;
            --bg-card: #0b1120;
            --bg-elevated: #020617;
            --border-subtle: rgba(148, 163, 184, 0.18);
            --accent: #38bdf8;
            --accent-soft: rgba(56, 189, 248, 0.14);
            --accent-strong: #0ea5e9;
            --danger: #f97373;
            --text-main: #e5e7eb;
            --text-muted: #9ca3af;
            --text-strong: #f9fafb;
            --radius-lg: 16px;
            --radius-full: 999px;
            --shadow-soft: 0 18px 40px rgba(15, 23, 42, 0.75);
            --transition-fast: 150ms ease-out;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "SF Pro Text",
                "Segoe UI", sans-serif;
            background: radial-gradient(circle at top, #1e293b 0, #020617 55%, #020617 100%);
            color: var(--text-main);
            min-height: 100vh;
        }

        body {
            display: flex;
            flex-direction: column;
        }

        .app-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Nav */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 30;
            backdrop-filter: blur(18px);
            background: radial-gradient(circle at top left,
                rgba(56, 189, 248, 0.12),
                rgba(15, 23, 42, 0.95)
            );
            border-bottom: 1px solid rgba(148, 163, 184, 0.22);
        }

        .navbar-inner {
            max-width: 1120px;
            margin: 0 auto;
            padding: 0.75rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .brand-icon {
            width: 32px;
            height: 32px;
            border-radius: 12px;
            background: radial-gradient(circle at 20% 0,
                #facc15,
                #22c55e 32%,
                #38bdf8 70%,
                #0f172a 100%
            );
            box-shadow:
                0 10px 30px rgba(56, 189, 248, 0.45),
                inset 0 0 0 1px rgba(15, 23, 42, 0.7);
        }

        .brand-text-main {
            font-weight: 600;
            letter-spacing: 0.03em;
            color: var(--text-strong);
            font-size: 0.95rem;
        }

        .brand-text-sub {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.16em;
        }

        .pill {
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            font-size: 0.7rem;
            color: var(--text-muted);
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .pill-dot {
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: #22c55e;
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.25);
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .nav-user {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .nav-user span {
            color: var(--text-main);
            font-weight: 500;
        }

        .btn-outline {
            border-radius: var(--radius-full);
            border: 1px solid rgba(148, 163, 184, 0.5);
            background: rgba(15, 23, 42, 0.6);
            color: var(--text-main);
            font-size: 0.8rem;
            padding: 0.3rem 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            cursor: pointer;
            transition: background var(--transition-fast), border-color var(--transition-fast),
                transform var(--transition-fast), box-shadow var(--transition-fast);
        }

        .btn-outline:hover {
            background: rgba(15, 23, 42, 0.95);
            border-color: rgba(148, 163, 184, 0.9);
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.72);
            transform: translateY(-1px);
        }

        .btn-outline:active {
            transform: translateY(0);
            box-shadow: none;
        }

        .btn-icon {
            width: 16px;
            height: 16px;
            border-radius: 999px;
            background: radial-gradient(circle at 30% 0,
                #38bdf8,
                #0ea5e9 70%,
                #0369a1 100%
            );
        }

        /* Page content */
        .page-main {
            flex: 1;
            padding: 1.75rem 1.25rem 2.5rem;
        }

        .page-inner {
            max-width: 1120px;
            margin: 0 auto;
        }

        /* Shell card for admin area */
        .shell-card {
            background: radial-gradient(circle at top left,
                rgba(56, 189, 248, 0.18),
                rgba(15, 23, 42, 0.98)
            );
            border-radius: 22px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            box-shadow: var(--shadow-soft);
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            animation: shell-enter 260ms ease-out;
        }

        .shell-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top right,
                rgba(250, 204, 21, 0.08),
                transparent 55%
            );
            opacity: 0.85;
            pointer-events: none;
        }

        .shell-content {
            position: relative;
            z-index: 1;
        }

        /* Flash message */
        .flash {
            margin-bottom: 1rem;
            padding: 0.7rem 0.9rem;
            border-radius: 999px;
            font-size: 0.78rem;
            color: #bbf7d0;
            background: linear-gradient(
                90deg,
                rgba(22, 163, 74, 0.16),
                rgba(22, 163, 74, 0.55)
            );
            border: 1px solid rgba(74, 222, 128, 0.5);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }

        .flash-text {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .flash-pill {
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: #22c55e;
        }

        .flash-close {
            border: none;
            background: transparent;
            color: #dcfce7;
            font-size: 0.9rem;
            cursor: pointer;
            padding: 0 0.3rem;
            line-height: 1;
        }

        .flash-hidden {
            opacity: 0;
            transform: translateY(-4px);
            pointer-events: none;
            transition: opacity 180ms ease-out, transform 180ms ease-out;
        }

        /* Content area that holds @yield('content') */
        .content-surface {
            background: rgba(2, 6, 23, 0.96);
            border-radius: 18px;
            border: 1px solid var(--border-subtle);
            padding: 1.2rem 1.1rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.88);
        }

        @media (min-width: 768px) {
            .shell-card {
                padding: 1.75rem 1.85rem;
            }
            .content-surface {
                padding: 1.5rem 1.6rem;
            }
        }

        /* Form & table base styles so admin pages look nice without Tailwind */
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        select {
            width: 100%;
            padding: 0.45rem 0.7rem;
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.5);
            background: rgba(15, 23, 42, 0.85);
            color: var(--text-main);
            font-size: 0.85rem;
            outline: none;
            transition: border-color var(--transition-fast),
                        box-shadow var(--transition-fast),
                        background var(--transition-fast),
                        transform var(--transition-fast);
        }

        input:focus,
        select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 1px rgba(56, 189, 248, 0.7);
            background: rgba(15, 23, 42, 0.98);
            transform: translateY(-0.5px);
        }

        label {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
            display: inline-block;
        }

        button,
        .btn-primary {
            font-family: inherit;
        }

        .btn-primary {
            border-radius: var(--radius-full);
            border: none;
            background: linear-gradient(135deg, var(--accent), var(--accent-strong));
            color: #0b1120;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 0.5rem 1.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
            cursor: pointer;
            box-shadow: 0 12px 25px rgba(56, 189, 248, 0.45);
            transition: transform var(--transition-fast), box-shadow var(--transition-fast),
                        filter 120ms ease-out;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 30px rgba(56, 189, 248, 0.65);
            filter: brightness(1.03);
        }

        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 6px 14px rgba(56, 189, 248, 0.35);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
            color: var(--text-main);
        }

        th,
        td {
            padding: 0.65rem 0.75rem;
            border-bottom: 1px solid rgba(30, 64, 175, 0.45);
        }

        th {
            text-align: left;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            color: var(--text-muted);
            background: rgba(15, 23, 42, 0.96);
        }

        tr:nth-child(even) td {
            background: rgba(15, 23, 42, 0.7);
        }

        tr:hover td {
            background: rgba(15, 23, 42, 0.92);
        }

        .tag {
            display: inline-flex;
            align-items: center;
            padding: 0.15rem 0.55rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.5);
            font-size: 0.7rem;
            gap: 0.25rem;
        }

        .tag-dot {
            width: 6px;
            height: 6px;
            border-radius: 999px;
        }

        .tag-dot-super {
            background: #f97316;
        }

        .tag-dot-user {
            background: #38bdf8;
        }

        .link-muted {
            color: var(--accent);
            font-size: 0.8rem;
            text-decoration: none;
            border-bottom: 1px dashed rgba(56, 189, 248, 0.4);
        }

        .link-muted:hover {
            border-bottom-style: solid;
        }

        /* Animations */
        @keyframes shell-enter {
            from {
                opacity: 0;
                transform: translateY(12px) scale(0.99);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .fade-out {
            opacity: 0;
            transform: translateY(-4px);
            transition: opacity 180ms ease-out, transform 180ms ease-out;
        }

        /* Small responsive tweaks */
        @media (max-width: 640px) {
            .navbar-inner {
                padding-inline: 1rem;
            }

            .brand-text-sub {
                display: none;
            }

            .page-main {
                padding-inline: 1rem;
            }

            .shell-card {
                border-radius: 18px;
            }
        }
    </style>
</head>
<body>
<div class="app-shell">
    <header class="navbar">
        <div class="navbar-inner">
            <div class="brand">
                <div class="brand-icon"></div>
                <div>
                    <div class="brand-text-main">MyShop Super Admin</div>
                    <div class="brand-text-sub">Control Panel</div>
                </div>
            </div>

            @auth
                <div class="nav-right">
                    <div class="pill">
                        <span class="pill-dot"></span>
                        <span>Signed in</span>
                    </div>
                    <div class="nav-user">
                        Hello, <span>{{ auth()->user()->name }}</span>
                    </div>
                    <form action="{{ route('admin.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-outline">
                            <span class="btn-icon"></span>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            @endauth
        </div>
    </header>

    <main class="page-main">
        <div class="page-inner">
            <div class="shell-card">
                <div class="shell-content">
                    @if (session('status'))
                        <div class="flash" id="flash-message">
                            <div class="flash-text">
                                <span class="flash-pill"></span>
                                <span>{{ session('status') }}</span>
                            </div>
                            <button type="button" class="flash-close" aria-label="Close">&times;</button>
                        </div>
                    @endif

                    <div class="content-surface" id="admin-content">
                        @yield('content')
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    (function () {
        document.addEventListener("DOMContentLoaded", function () {
            const flash = document.getElementById("flash-message");
            const content = document.getElementById("admin-content");

            // Soft entrance
            if (content) {
                content.style.opacity = "0";
                content.style.transform = "translateY(6px)";
                requestAnimationFrame(() => {
                    content.style.transition = "opacity 220ms ease-out, transform 220ms ease-out";
                    content.style.opacity = "1";
                    content.style.transform = "translateY(0)";
                });
            }

            // Flash handling
            if (flash) {
                const closeBtn = flash.querySelector(".flash-close");
                const hideFlash = () => {
                    flash.classList.add("fade-out");
                    setTimeout(() => {
                        flash.style.display = "none";
                    }, 200);
                };

                if (closeBtn) {
                    closeBtn.addEventListener("click", hideFlash);
                }

                // Auto hide after 4s
                setTimeout(hideFlash, 4000);
            }
        });
    })();
</script>
</body>
</html>
