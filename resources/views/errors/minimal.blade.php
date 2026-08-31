<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title') — {{ config('app.name') }}</title>
    <style>
        :root { font-family: Inter, ui-sans-serif, system-ui, sans-serif; color-scheme: light; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f8fafc; color: #0f172a; }
        main { width: min(92vw, 620px); padding: 32px; background: white; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 12px 32px rgba(15,23,42,.08); }
        .code { font-size: .875rem; font-weight: 700; color: #15803d; letter-spacing: .08em; }
        h1 { margin: .5rem 0; font-size: clamp(1.75rem, 4vw, 2.5rem); }
        p { margin: 0; color: #475569; line-height: 1.65; }
        a { display: inline-block; margin-top: 1.5rem; color: #166534; font-weight: 700; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<main>
    <div class="code">@yield('code')</div>
    <h1>@yield('heading')</h1>
    <p>@yield('message')</p>
    <a href="{{ url('/') }}">Kembali ke halaman utama</a>
</main>
</body>
</html>
