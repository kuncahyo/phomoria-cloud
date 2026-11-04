<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VoucherCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VoucherAdminController extends Controller
{
    public function index()
    {
        $vouchers = VoucherCode::query()->withCount([
            'redemptions as completed_count' => fn ($query) => $query->where('status', 'completed'),
            'redemptions as pending_count' => fn ($query) => $query->where('status', 'authorized'),
        ])->latest()->paginate(25);
        return view('admin.vouchers.index', compact('vouchers'));
    }

    public function store(Request $request)
    {
        $request->merge(['code' => Str::upper(trim((string) $request->input('code')))]);

        $data = $request->validate([
            'code' => ['required', 'string', 'min:4', 'max:100', 'regex:/^[A-Z0-9-]+$/', 'unique:voucher_codes,code'],
            'status' => ['required', 'in:active,secret'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ], [
            'code.regex' => 'Kode hanya boleh berisi huruf kapital, angka, dan tanda hubung.',
            'code.unique' => 'Kode voucher tersebut sudah digunakan.',
            'expires_at.after' => 'Waktu kedaluwarsa harus berada di masa mendatang.',
        ]);

        VoucherCode::create($data);

        return redirect()->route('admin.vouchers.index')->with('success', 'Voucher berhasil dibuat.');
    }
}
