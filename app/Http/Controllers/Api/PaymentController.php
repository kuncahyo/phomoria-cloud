<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentAccount;
use App\Models\PaymentTransaction;
use App\Services\DokuSnapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'device_uuid' => ['required','string'],
            'client_session_id' => ['required','uuid'],
        ]);
        $device = $request->user()->devices()->where('device_uuid', $data['device_uuid'])->first();
        if (!$device) return response()->json(['success'=>false,'message'=>'Device tidak ditemukan'],404);
        if ($device->status !== 'ACTIVE') return response()->json(['success'=>false,'message'=>'Device tidak aktif'],422);
        if (!$device->session_price || $device->session_price < 1) return response()->json(['success'=>false,'message'=>'Harga sesi perangkat belum dikonfigurasi'],422);

        $account = PaymentAccount::query()->where('user_id',$request->user()->id)->where('provider','doku')->where('is_active',true)->first();
        if (!$account) return response()->json(['success'=>false,'message'=>'Akun pembayaran belum dikonfigurasi'],422);

        $existing = PaymentTransaction::query()->where('user_id',$request->user()->id)->where('device_id',$device->id)->where('client_session_id',$data['client_session_id'])->latest()->first();
        if ($existing && in_array($existing->status,['PENDING','PAID'],true)) return $this->response($existing);

        $reference = 'PHO-' . strtoupper(Str::random(20));
        try {
            $provider = app(DokuSnapService::class, ['account'=>$account]);
            $result = $provider->generate($reference,(int)$device->session_price);
            $transaction = DB::transaction(function () use ($request,$device,$account,$data,$reference,$result) {
                $tx = PaymentTransaction::create([
                    'user_id'=>$request->user()->id,'device_id'=>$device->id,'payment_account_id'=>$account->id,
                    'client_session_id'=>$data['client_session_id'],'merchant_reference'=>$reference,
                    'provider_reference'=>$result['referenceNo'] ?? $reference,'amount'=>(int)$device->session_price,
                    'currency'=>'IDR','description'=>'Phomoria Photo Session','status'=>'PENDING',
                    'qr_content'=>$result['qrContent'] ?? null,'expires_at'=>now()->addMinutes(15),'provider_response'=>$result,
                ]);
                $tx->events()->create(['source'=>'doku_generate','status'=>'PENDING','metadata'=>$result]);
                return $tx;
            });
            return $this->response($transaction,201);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['success'=>false,'message'=>'Gagal membuat pembayaran','error'=>app()->environment('local')?$e->getMessage():null],502);
        }
    }

    public function show(Request $request, string $publicId)
    {
        $tx = PaymentTransaction::query()->where('public_id',$publicId)->where('user_id',$request->user()->id)->firstOrFail();
        if ($tx->status === 'PENDING' && $tx->expires_at?->isPast()) $tx->update(['status'=>'EXPIRED']);
        return $this->response($tx);
    }

    public function cancel(Request $request, string $publicId)
    {
        $tx = PaymentTransaction::query()->where('public_id',$publicId)->where('user_id',$request->user()->id)->firstOrFail();
        if ($tx->status === 'PAID') return response()->json(['success'=>false,'message'=>'Transaksi yang sudah PAID tidak dapat dibatalkan'],409);
        if ($tx->status !== 'PENDING') return $this->response($tx);
        try {
            $result = app(DokuSnapService::class,['account'=>$tx->paymentAccount])->expire($tx->provider_reference ?: $tx->merchant_reference);
            DB::transaction(function() use($tx,$result){
                $locked=PaymentTransaction::whereKey($tx->id)->lockForUpdate()->first();
                if($locked->status==='PENDING'){
                    $locked->status='CANCELLED'; $locked->cancelled_at=now(); $locked->provider_response=$result; $locked->save();
                    $locked->events()->create(['source'=>'doku_expire','status'=>'CANCELLED','metadata'=>$result]);
                }
            });
            return $this->response($tx->fresh());
        } catch(Throwable $e){ report($e); return response()->json(['success'=>false,'message'=>'Gagal membatalkan pembayaran'],502); }
    }

    private function response(PaymentTransaction $tx, int $status=200)
    {
        return response()->json(['success'=>true,'data'=>[
            'transaction_id'=>$tx->public_id,'merchant_reference'=>$tx->merchant_reference,'amount'=>$tx->amount,
            'currency'=>$tx->currency,'status'=>$tx->status,'qr_content'=>$tx->qr_content,
            'expires_at'=>optional($tx->expires_at)->toIso8601String(),'paid_at'=>optional($tx->paid_at)->toIso8601String(),
        ]],$status);
    }
}
