@extends('layouts.admin')

@section('title', 'Voucher - Phomoria Cloud')

@section('styles')
<style>
.voucher-page{padding:42px 0 90px}.voucher-top{margin-bottom:25px}.voucher-eyebrow{color:#888890;font-size:11px;font-weight:700;letter-spacing:3px;text-transform:uppercase;margin-bottom:9px}.voucher-page h1{margin:0 0 8px;font-size:38px}.voucher-muted{margin:0;color:#9999a2;line-height:1.6}.voucher-card{margin:20px 0;padding:22px;border:1px solid rgba(255,255,255,.09);border-radius:15px;background:rgba(255,255,255,.025)}.voucher-grid{display:grid;grid-template-columns:1.4fr 1fr 1.2fr auto;align-items:end;gap:13px}.voucher-field label{display:block;margin-bottom:8px;color:#888890;font-size:10px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase}.voucher-field input,.voucher-field select{width:100%;min-height:44px;padding:0 11px;border:1px solid rgba(255,255,255,.12);border-radius:9px;background:#151518;color:#fff}.voucher-button{min-height:44px;padding:0 18px;border:0;border-radius:9px;background:#fff;color:#111;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap}.voucher-alert,.voucher-errors{margin:15px 0;padding:13px 16px;border-radius:10px}.voucher-alert{background:rgba(120,220,150,.09);color:#b9edc7}.voucher-errors{background:rgba(255,100,100,.09);color:#ffbaba}.voucher-table-wrap{overflow-x:auto;border:1px solid rgba(255,255,255,.09);border-radius:14px}.voucher-table{width:100%;border-collapse:collapse;min-width:720px}.voucher-table th,.voucher-table td{padding:14px 16px;border-bottom:1px solid rgba(255,255,255,.07);text-align:left;font-size:12px}.voucher-table th{color:#888890;font-size:10px;letter-spacing:1px;text-transform:uppercase}.voucher-table td{color:#ddd}.voucher-code{font-weight:800;letter-spacing:1px;color:#fff!important}.voucher-status{display:inline-block;padding:5px 9px;border-radius:20px;background:#24242a;color:#ccc;font-size:10px;font-weight:700}.voucher-status.active{background:#153522;color:#a9e9bd}.voucher-status.secret{background:#292344;color:#d0c0ff}.voucher-status.used{background:#3a2920;color:#ffc89f}.voucher-status.expired{background:#3b2020;color:#ffb0b0}.voucher-empty{padding:35px;text-align:center;color:#777}.voucher-pagination{padding:16px;color:#aaa}.voucher-hint{margin-top:12px;color:#777;font-size:11px;line-height:1.6}@media(max-width:850px){.voucher-grid{grid-template-columns:1fr 1fr}.voucher-page h1{font-size:30px}}@media(max-width:520px){.voucher-grid{grid-template-columns:1fr}.voucher-button{width:100%}}
</style>
@endsection

@section('content')
<div class="voucher-page">
    <div class="voucher-top">
        <div class="voucher-eyebrow">Cloud Admin</div>
        <h1>Voucher</h1>
        <p class="voucher-muted">Buat dan pantau voucher layanan photobooth. Voucher ACTIVE hanya dapat digunakan satu kali, sedangkan SECRET dapat digunakan berulang kali.</p>
    </div>

    @if(session('success'))<div class="voucher-alert">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="voucher-errors">{{ $errors->first() }}</div>@endif

    <form class="voucher-card" method="POST" action="{{ route('admin.vouchers.store') }}">
        @csrf
        <h2 style="margin:0 0 18px;font-size:17px">Buat Voucher</h2>
        <div class="voucher-grid">
            <div class="voucher-field">
                <label for="voucher-code">Kode Voucher</label>
                <input id="voucher-code" name="code" value="{{ old('code') }}" placeholder="CONTOH: PHOMORIA-2026" minlength="4" maxlength="100" required>
            </div>
            <div class="voucher-field">
                <label for="voucher-status">Jenis Voucher</label>
                <select id="voucher-status" name="status" required>
                    <option value="active" @selected(old('status', 'active') === 'active')>ACTIVE — sekali pakai</option>
                    <option value="secret" @selected(old('status') === 'secret')>SECRET — berulang</option>
                </select>
            </div>
            <div class="voucher-field">
                <label for="voucher-expiry">Kedaluwarsa (opsional)</label>
                <input id="voucher-expiry" type="datetime-local" name="expires_at" value="{{ old('expires_at') }}">
            </div>
            <button class="voucher-button" type="submit">Buat Voucher</button>
        </div>
        <p class="voucher-hint">Kode disimpan dalam huruf kapital. Gunakan huruf, angka, dan tanda hubung. Voucher ACTIVE berubah menjadi USED setelah seluruh file sesi foto berhasil diunggah. Voucher SECRET tetap dapat digunakan kembali.</p>
    </form>

    <div class="voucher-card" style="padding:0;overflow:hidden">
        <div style="padding:20px 20px 14px"><h2 style="margin:0;font-size:17px">Daftar Voucher</h2><p class="voucher-muted" style="font-size:12px;margin-top:5px">Menampilkan voucher terbaru terlebih dahulu.</p></div>
        <div class="voucher-table-wrap" style="border:0;border-radius:0">
            <table class="voucher-table">
                <thead><tr><th>Kode</th><th>Jenis / Status</th><th>Penggunaan</th><th>Kedaluwarsa</th><th>Dibuat</th></tr></thead>
                <tbody>
                @forelse($vouchers as $voucher)
                    @php($expired = $voucher->expires_at && $voucher->expires_at->isPast())
                    <tr>
                        <td class="voucher-code">{{ $voucher->code }}</td>
                        <td><span class="voucher-status {{ $expired ? 'expired' : $voucher->status }}">{{ $expired ? 'EXPIRED' : strtoupper($voucher->status) }}</span></td>
                        <td>{{ number_format($voucher->redemptions_count) }} kali</td>
                        <td>{{ $voucher->expires_at ? $voucher->expires_at->format('d/m/Y H:i') : 'Tidak ada batas' }}</td>
                        <td>{{ $voucher->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="voucher-empty">Belum ada voucher. Buat voucher pertama melalui formulir di atas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($vouchers->hasPages())<div class="voucher-pagination">{{ $vouchers->links() }}</div>@endif
    </div>
</div>
@endsection
