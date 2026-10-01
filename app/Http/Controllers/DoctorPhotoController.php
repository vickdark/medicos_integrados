<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Doctor;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class DoctorPhotoController extends Controller
{
    /**
     * Serve the doctor's photo. It is public only while the administrator shows the
     * doctors on the landing page and the account is active; signed-in users can
     * always see it inside the system.
     */
    public function __invoke(Request $request, Doctor $doctor): Response
    {
        abort_if($doctor->photo_path === null || ! Storage::disk('local')->exists($doctor->photo_path), 404);

        $isPubliclyShown = AppSetting::showDoctorsOnLanding() && $doctor->user->is_active;

        abort_unless($isPubliclyShown || $request->user() !== null, 404);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        return $disk->response($doctor->photo_path, null, [
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
