<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class KeepSessionAliveController extends Controller
{
    /**
     * Tell the server the staff member is still working, so the session is not
     * closed for inactivity. The middleware records the activity.
     */
    public function __invoke(): Response
    {
        return response()->noContent();
    }
}
