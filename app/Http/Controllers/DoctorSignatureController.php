<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Doctor;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class DoctorSignatureController extends Controller
{
    /**
     * Serve the signature image to the doctor it belongs to and the administrator,
     * so they can check it before it is printed on documents.
     */
    public function __invoke(Request $request, Doctor $doctor): Response
    {
        $user = $request->user();

        abort_unless($user->hasRole(UserRole::Admin) || $user->doctor?->is($doctor), 403);
        abort_if($doctor->signature_path === null || ! Storage::disk('local')->exists($doctor->signature_path), 404);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        return $disk->response($doctor->signature_path, null, [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
