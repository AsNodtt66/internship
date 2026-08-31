<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::select('select 1');

            return response()
                ->json(['status' => 'ready'])
                ->header('Cache-Control', 'no-store');
        } catch (Throwable) {
            return response()
                ->json(['status' => 'unavailable'], 503)
                ->header('Cache-Control', 'no-store');
        }
    }
}
