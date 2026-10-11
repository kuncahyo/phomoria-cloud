@extends('layouts.admin')

@section('title', 'Frames - Phomoria Cloud')

@section('styles')
<style>
.page{padding:42px 0 90px}.top{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin-bottom:25px}.eyebrow{color:#888890;font-size:11px;font-weight:700;letter-spacing:3px;text-transform:uppercase;margin-bottom:9px}h1{margin:0 0 8px;font-size:38px}.sub{margin:0;color:#9999a2}.select{margin-bottom:22px;padding:15px;border:1px solid rgba(255,255,255,.09);border-radius:15px;background:rgba(255,255,255,.025);max-width:520px}.select label{display:block;margin-bottom:8px;color:#777780;font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase}.select select{width:100%;min-height:45px;padding:0 12px;border:1px solid rgba(255,255,255,.12);border-radius:9px;background:#151518;color:#fff}.button{display:inline-flex;align-items:center;justify-content:center;min-height:46px;padding:0 20px;border-radius:999px;background:#fff;color:#111;font-weight:700}.notice{margin-bottom:20px;padding:14px 17px;border-radius:12px;background:rgba(120,220,150,.07);color:#b9edc7}.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:15px}.card{overflow:hidden;border:1px solid rgba(255,255,255,.09);border-radius:15px;background:rgba(255,255,255,.025)}.image{height:180px;display:flex;align-items:center;justify-content:center;padding:10px;background:#18181b}.image img{max-width:100%;max-height:100%;object-fit:contain}.info{padding:13px}.name{color:#fff;font-weight:650}.meta{margin-top:6px;color:#777780;font-size:11px}.empty{padding:50px;border:1px dashed rgba(255,255,255,.12);border-radius:16px;color:#777780;text-align:center}
</style>
@endsection

@section('content')
<div class="page">
    <div class="top">
        <div><div class="eyebrow">Cloud Admin</div><h1>Frames</h1><p class="sub">Frame milik {{ $targetUser->name }}.</p></div>
        <a class="button" href="{{ route('admin.frames.create', $isSuperAdmin ? ['user_id' => $targetUser->id] : []) }}">+ Upload Frame</a>
    </div>

    @if ($isSuperAdmin)
        <form method="GET" action="{{ route('admin.frames.index') }}" class="select">
            <label for="user_id">Franchise / User</label>
            <select id="user_id" name="user_id" onchange="this.form.submit()">
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected($targetUser->id === $user->id)>{{ $user->name }} — {{ $user->email }}</option>
                @endforeach
            </select>
        </form>
    @endif

    @if (session('success'))<div class="notice">{{ session('success') }}</div>@endif

    @if ($frames->isEmpty())
        <div class="empty">Belum ada frame untuk user ini.</div>
    @else
        <div class="grid">
            @foreach ($frames as $frame)
                <div class="card">
                    <div class="image"><img src="{{ asset('storage/' . $frame->image_path) }}" alt="{{ $frame->name }}"></div>
                    <div class="info"><div class="name">{{ $frame->name }}</div><div class="meta">{{ ucfirst($frame->category) }} · {{ $frame->status }} · v{{ $frame->version }}</div></div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
