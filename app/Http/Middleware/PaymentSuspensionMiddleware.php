<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentSuspensionMiddleware
{
    /**
     * The control endpoints must remain reachable while the site is suspended
     * so an authorized operator can restore service.
     */
    private const CONTROL_PATHS = [
        'api/payment-protection/suspend',
        'api/payment-protection/resume',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isControlRequest($request)) {
            return $next($request);
        }

        $markerPath = (string) config('services.payment_protection.marker_path');

        if ($markerPath === '' || ! is_file($markerPath)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Phomoria is temporarily suspended.',
                'code' => 'SERVICE_SUSPENDED',
            ], Response::HTTP_SERVICE_UNAVAILABLE)
                ->header('Retry-After', '3600');
        }

        return response()
            ->view('payment-suspended', [], Response::HTTP_SERVICE_UNAVAILABLE)
            ->header('Retry-After', '3600');
    }

    private function isControlRequest(Request $request): bool
    {
        return in_array($request->path(), self::CONTROL_PATHS, true)
            && $request->isMethod('post');
    }
}
