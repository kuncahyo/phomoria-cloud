<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Phomoria Cloud')</title>
<style>
*{box-sizing:border-box}html,body{min-height:100%}body{margin:0;font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#0b0b0d;color:#fff}a{text-decoration:none}.admin-sidebar{position:fixed;inset:0 auto 0 0;width:245px;padding:28px 18px;border-right:1px solid rgba(255,255,255,.08);background:#101012}.sidebar-brand{display:block;margin:0 12px 35px;color:#fff;font-size:21px;font-weight:800;letter-spacing:3px}.sidebar-section{margin:0 12px 10px;color:#66666e;font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase}.sidebar-link{display:flex;align-items:center;min-height:44px;margin:4px 0;padding:0 12px;border-radius:10px;color:#98989f;font-size:13px}.sidebar-link:hover{background:rgba(255,255,255,.07);color:#fff}.sidebar-user{position:absolute;left:18px;right:18px;bottom:20px;padding:14px 12px;border-top:1px solid rgba(255,255,255,.08)}.sidebar-user-name{margin-bottom:10px;color:#d0d0d5;font-size:12px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.logout-button{width:100%;min-height:38px;border:1px solid rgba(255,255,255,.12);border-radius:9px;background:transparent;color:#9999a2;cursor:pointer}.admin-main{min-height:100vh;margin-left:245px}.admin-content{width:min(1180px,calc(100% - 48px));margin:0 auto}@yield('styles')
</style>
</head>
<body>
<aside class="admin-sidebar">
<a href="{{ route('admin') }}" class="sidebar-brand">PHOMORIA</a>
<div class="sidebar-section">Workspace</div>
<a href="{{ route('admin') }}" class="sidebar-link">Dashboard</a>
<a href="{{ route('admin.frames.index') }}" class="sidebar-link">Frames</a>
<a href="{{ route('admin.frames.select') }}" class="sidebar-link">Pilih Frame</a>
<a href="{{ route('admin.frames.create') }}" class="sidebar-link">Upload Frame</a>
<div class="sidebar-user">
<div class="sidebar-user-name">{{ auth()->user()->name }}</div>
<form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="logout-button">Logout</button></form>
</div>
</aside>
<div class="admin-main"><main class="admin-content">@yield('content')</main></div>
</body>
</html>