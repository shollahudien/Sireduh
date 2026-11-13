<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sedang dalam Perbaikan &mdash; SIRESTEDUH</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
               font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; background: #f0fdf4; color: #14532d;
               text-align: center; padding: 1.5rem; }
        .card { max-width: 560px; }
        .icon { font-size: 5rem; animation: spin 2.4s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        h1 { font-size: 1.6rem; margin: 1.2rem 0 .5rem; }
        p { color: #4b5563; line-height: 1.6; }
        .badge { display: inline-block; margin-top: 1.2rem; background: #dcfce7; color: #15803d;
                 font-size: .75rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
                 border-radius: 999px; padding: .4rem 1.1rem; }
        .footer { margin-top: 2rem; font-size: .75rem; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">🛠️</div>
        <h1>SIRESTEDUH Sedang dalam Perbaikan</h1>
        <p>{{ $message }}</p>
        <span class="badge">Maintenance Mode</span>
        <a href="/login" class="footer" style="display:inline-block;margin-top:1.5rem;font-size:.75rem;color:#6b7280;text-decoration:underline;">Masuk sebagai admin →</a>
        <div class="footer">&copy; {{ date('Y') }} BUMDes Wisata Teduh Glamping — SIRESTEDUH</div>
    </div>
</body>
</html>
