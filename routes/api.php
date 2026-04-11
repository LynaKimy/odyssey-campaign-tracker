<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/deploy', function (Request $request) {
    $secret = config('app.deploy_secret');
    $script = config('app.deploy_script');
    $logs = config('app.deploy_log');

    $signature = $request->header('X-Hub-Signature-256');
    if (!$signature) {
        abort(403);
    }

    $payload = $request->getContent();

    if (!hash_equals(
        'sha256=' . hash_hmac('sha256', $payload, $secret),
        $signature
    )) {
        abort(403);
    }
    $ref = $request->input('ref', '');
    if (!str_starts_with($ref, 'refs/tags/')) {
        return response()->json(['message' => 'Not a tag, skipping']);
    }

    dispatch(function () use ($script, $logs) {
        exec("{$script} >> {$logs} 2>&1");
    })->afterResponse();

    return response()->json(['message' => 'Deploy triggered']);
});
