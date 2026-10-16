<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') — @endif Диспетчерийн үндэсний төв</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            /* Өнгөний систем */
            --navy: #0F2A33;        /* гол гарчиг (хар хөх-ногоон) */
            --card-title: #1A2B33;  /* картын гарчиг */
            --text: #1A2B33;
            --label: #1A2B33;
            --muted: #5F6B73;       /* тайлбар текст */
            --table-head: #2E4756;  /* хүснэгтийн толгой */
            --excel: #107C41;       /* амжилт, шийдвэрлэсэн */
            --card-bg: #FFFFFF;
            --card-radius: 16px;
            --card-border: #e5e9eb;
            --card-shadow: 0 1px 2px rgba(15, 42, 51, .04), 0 6px 18px rgba(15, 42, 51, .06);
            /* График: цэнхэр-ногоон (үндсэн), нил (харьцуулах), бараан цэнхэр-ногоон (шугам) */
            --chart-1: #1fa391;
            --chart-1-soft: #bfe9e2;
            --chart-2: #8b7cf0;
            --chart-3: #0f766e;
            --th-bg: #eef3f4;
            --border: #c9d6e6;
            --input-bg: #f3f6fb;
            --page-bg: #F3F5F6;     /* хуудасны дэвсгэр */
            --danger: #c0392b;
            --danger-bg: #fdecea;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; height: 100%; }
        body {
            font-family: 'Roboto', system-ui, -apple-system, 'Segoe UI', sans-serif;
            color: var(--text);
            background: var(--page-bg);
            -webkit-font-smoothing: antialiased;
        }
        a { color: var(--primary); }
        .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .brand-icon {
            width: 38px; height: 38px; border-radius: 6px;
            background: var(--primary); color: #fff;
            display: grid; place-items: center; flex-shrink: 0;
        }
        .brand-logo { height: 42px; width: auto; max-width: 64px; object-fit: contain; flex-shrink: 0; }
        .brand-name { font-weight: 800; color: var(--navy); font-size: 15px; line-height: 1.25; }
        .brand-sub { font-size: 12px; color: var(--muted); margin-top: 2px; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            height: 42px; padding: 0 20px; border: 0; border-radius: 8px;
            background: var(--primary); color: #fff; font: inherit; font-weight: 700; font-size: 14px;
            cursor: pointer; text-decoration: none; transition: background .15s;
        }
        .btn:hover { background: var(--primary-hover); }
        .btn:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(37, 99, 235, .3); }
        .btn-ghost { background: transparent; color: var(--primary); border: 1px solid var(--border); }
        .btn-ghost:hover { background: #eaf1fe; }
        @yield('styles')
    </style>
</head>
<body>
    @yield('content')
    @yield('scripts')
</body>
</html>
