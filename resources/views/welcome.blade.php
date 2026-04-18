<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Sher ERP') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        :root {
            --sher-green: #0e5f41;
            --sher-green-deep: #0a4731;
            --sher-gold: #d9a423;
            --sher-sand: #f8f4e8;
            --sher-ink: #11261d;
            --sher-white: #ffffff;
            --sher-shadow: 0 18px 45px rgba(14, 95, 65, 0.16);
            --sher-radius: 20px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Montserrat', sans-serif;
            color: var(--sher-ink);
            background:
                radial-gradient(circle at 10% 10%, rgba(217, 164, 35, 0.24) 0%, rgba(217, 164, 35, 0) 40%),
                radial-gradient(circle at 90% 80%, rgba(14, 95, 65, 0.2) 0%, rgba(14, 95, 65, 0) 45%),
                linear-gradient(160deg, #fefdf8 0%, #f6f0dc 50%, #f7f4ea 100%);
            padding: 32px 18px;
        }

        .shell {
            width: min(1080px, 100%);
            margin: 0 auto;
            background: linear-gradient(165deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 244, 232, 0.96) 100%);
            border: 1px solid rgba(14, 95, 65, 0.14);
            border-radius: 28px;
            box-shadow: var(--sher-shadow);
            overflow: hidden;
        }

        .topbar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 12px;
            padding: 18px 22px;
            background: rgba(255, 255, 255, 0.8);
            border-bottom: 1px solid rgba(14, 95, 65, 0.09);
        }

        .topbar a {
            text-decoration: none;
            color: var(--sher-green-deep);
            font-size: 14px;
            font-weight: 600;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid rgba(14, 95, 65, 0.2);
            transition: all 0.2s ease;
        }

        .topbar a:hover {
            background: var(--sher-green);
            color: var(--sher-white);
            border-color: var(--sher-green);
        }

        .topbar .primary-link {
            background: var(--sher-green);
            color: var(--sher-white);
            border-color: var(--sher-green);
        }

        .hero {
            padding: 28px;
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 22px;
        }

        .left-panel {
            background: var(--sher-white);
            border: 1px solid rgba(14, 95, 65, 0.12);
            border-radius: var(--sher-radius);
            padding: 26px;
        }

        .right-panel {
            border-radius: var(--sher-radius);
            background: linear-gradient(180deg, var(--sher-green) 0%, var(--sher-green-deep) 100%);
            padding: 24px;
            color: #eaf7f0;
            position: relative;
            overflow: hidden;
        }

        .right-panel::after {
            content: '';
            position: absolute;
            right: -50px;
            bottom: -60px;
            width: 210px;
            height: 210px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(217, 164, 35, 0.6) 0%, rgba(217, 164, 35, 0) 70%);
        }

        .logo-wrap {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 20px;
        }

        .logo-wrap img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            background: var(--sher-white);
            padding: 8px;
            border-radius: 14px;
            border: 1px solid rgba(14, 95, 65, 0.12);
        }

        .brand-title {
            margin: 0;
            font-size: 26px;
            line-height: 1.1;
            color: var(--sher-green-deep);
        }

        .brand-subtitle {
            margin: 6px 0 0;
            color: #3f5a4d;
            font-weight: 500;
        }

        .tag {
            display: inline-block;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #6b5722;
            background: #f8e8bd;
            border: 1px solid rgba(217, 164, 35, 0.35);
            border-radius: 999px;
            padding: 7px 12px;
            margin-bottom: 14px;
        }

        .content-copy {
            margin: 0;
            color: #325445;
            line-height: 1.65;
            font-size: 15px;
        }

        .quick-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }

        .quick-links a {
            text-decoration: none;
            color: var(--sher-green);
            border: 1px solid rgba(14, 95, 65, 0.22);
            padding: 10px 14px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            transition: 0.2s ease;
        }

        .quick-links a:hover {
            background: var(--sher-green);
            color: var(--sher-white);
            border-color: var(--sher-green);
        }

        .metric-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-top: 22px;
            position: relative;
            z-index: 1;
        }

        .metric {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 14px;
            padding: 14px;
        }

        .metric strong {
            display: block;
            font-size: 20px;
            color: #fff;
            margin-bottom: 4px;
        }

        .metric span {
            font-size: 12px;
            opacity: 0.9;
        }

        .footer {
            padding: 0 28px 24px;
            color: #557263;
            font-size: 13px;
        }

        @media (max-width: 900px) {
            .hero {
                grid-template-columns: 1fr;
                padding: 18px;
            }

            .brand-title {
                font-size: 22px;
            }

            .topbar {
                justify-content: center;
                flex-wrap: wrap;
            }

            .logo-wrap img {
                width: 68px;
                height: 68px;
            }
        }
    </style>
</head>
<body>
    <div class="shell">
        @if (Route::has('login'))
            <nav class="topbar">
                @auth
                    <a href="{{ url('/dashboard') }}" class="primary-link">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="primary-link">Log In</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}">Register</a>
                    @endif
                @endauth
            </nav>
        @endif

        <section class="hero">
            <article class="left-panel">
                <span class="tag">Sher Theme Active</span>
                <div class="logo-wrap">
                    <img src="{{ asset('SHER EAST AFRICA LOGO.pdf.png') }}" alt="Sher East Africa logo">
                    <div>
                        <h1 class="brand-title">Sher East Africa ERP</h1>
                        <p class="brand-subtitle">Operational clarity for parks, safaris, transport, and finance.</p>
                    </div>
                </div>

                <p class="content-copy">
                    The application has been styled with the Sher brand look and feel, replacing the dark default starter view.
                    You can continue to the system dashboard or access accounts from the top navigation.
                </p>

                <div class="quick-links">
                    <a href="{{ url('/dashboard') }}">Open Dashboard</a>
                    <a href="{{ route('login') }}">Go to Login</a>
                    <a href="https://laravel.com/docs" target="_blank" rel="noreferrer">Laravel Docs</a>
                </div>
            </article>

            <aside class="right-panel">
                <h2 style="margin:0 0 8px;font-size:22px;">Sher Operations Snapshot</h2>
                <p style="margin:0 0 12px;line-height:1.6;font-size:14px;max-width:38ch;position:relative;z-index:1;">
                    A clean and high-contrast interface focused on everyday workflows: client management, quotations,
                    job cards, invoices, and safari allocation.
                </p>

                <div class="metric-grid">
                    <div class="metric">
                        <strong>Clients</strong>
                        <span>Lead to booking flow</span>
                    </div>
                    <div class="metric">
                        <strong>Quotations</strong>
                        <span>Fast proposal generation</span>
                    </div>
                    <div class="metric">
                        <strong>Invoices</strong>
                        <span>Billing and payment tracking</span>
                    </div>
                    <div class="metric">
                        <strong>Job Cards</strong>
                        <span>Safari and transport operations</span>
                    </div>
                </div>
            </aside>
        </section>

        <div class="footer">
            Version {{ app()->version() }} | Theme: Sher East Africa
        </div>
    </div>
</body>
</html>
