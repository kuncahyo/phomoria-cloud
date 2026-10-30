<?php

namespace App\\Http\\Controllers\\System;

use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;

class PaymentProtectionController
{
    public function suspend(Request $request): JsonResponse
    {
        if ($unauthorized = $this->authorizeOperator($request)) {
            return $unauthorized;
        }

        $markerPath = $this->markerPath();
        $contents = implode(PHP_EOL, [
            'PHOMORIA SERVICE SUSPENSION',
            'Status: suspended',
            'Activated at: '.now()->toIso8601String(),
            'This marker file can be deleted by an authorized operator to restore service.',
            '',
        ]);

        if (file_put_contents($markerPath, $contents, LOCK_EX) === false) {
            return response()->json([
                'message' => 'Unable to create the suspension marker. Check the configured marker directory permissions.',
            ], 500);
        }

        return response()->json([
            'message' => 'Phomoria has been suspended.',
            'status' => 'suspended',
        ]);
    }

    public function resume(Request $request): JsonResponse
    {
        if ($unauthorized = $this->authorizeOperator($request)) {
            return $unauthorized;
        }

        $markerPath = $this->markerPath();

        if (is_file($markerPath) && ! unlink($markerPath)) {
            return response()->json([
                'message' => 'Unable to remove the suspension marker. Delete the configured marker file manually.',
            ], 500);
        }

        return response()->json([
            'message' => 'Phomoria has been restored.',
            'status' => 'active',
        ]);
    }

    private function markerPath(): string
    {
        return (string) config('services.payment_protection.marker_path');
    }

    private function authorizeOperator(Request $request): ?JsonResponse
    {
        $expectedToken = (string) config('services.payment_protection.token');
        $providedToken = (string) $request->bearerToken();

        if (
            $expectedToken === ''
            || $providedToken === ''
            || ! hash_equals($expectedToken, $providedToken)
        ) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        return null;
    }
}
