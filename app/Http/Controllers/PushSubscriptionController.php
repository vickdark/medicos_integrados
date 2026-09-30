<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyPushSubscriptionRequest;
use App\Http\Requests\StorePushSubscriptionRequest;
use Illuminate\Http\Response;

class PushSubscriptionController extends Controller
{
    /**
     * Save the browser subscription of the signed-in user so it can receive push notifications.
     */
    public function store(StorePushSubscriptionRequest $request): Response
    {
        $request->user()->updatePushSubscription(
            $request->validated('endpoint'),
            $request->validated('keys.p256dh'),
            $request->validated('keys.auth'),
            $request->validated('contentEncoding'),
        );

        return response()->noContent();
    }

    /**
     * Remove the browser subscription of the signed-in user.
     */
    public function destroy(DestroyPushSubscriptionRequest $request): Response
    {
        $request->user()->deletePushSubscription($request->validated('endpoint'));

        return response()->noContent();
    }
}
