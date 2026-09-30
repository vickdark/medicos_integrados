<?php

namespace App\Http\Controllers;

use App\Actions\Turns\BuildTurnBoard;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The waiting room screen. It is public so a TV can show it without signing in,
 * and it only exposes turn codes and doctor names.
 */
class TurnBoardController extends Controller
{
    /**
     * Show the waiting room screen.
     */
    public function show(BuildTurnBoard $board): Response
    {
        return Inertia::render('turns/Board', [
            'board' => $board->handle(),
        ]);
    }

    /**
     * The current state of the queue, polled by the screen.
     */
    public function data(BuildTurnBoard $board): JsonResponse
    {
        return response()->json($board->handle());
    }
}
