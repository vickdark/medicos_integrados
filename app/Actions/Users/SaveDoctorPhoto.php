<?php

namespace App\Actions\Users;

use App\Models\Doctor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Stores or removes the photo a doctor shows on their profile and on the landing
 * page. Photos live on the private disk and are served through a controller.
 */
class SaveDoctorPhoto
{
    public const DIRECTORY = 'doctor-photos';

    /**
     * Replace the photo when a new file arrives, or remove it when asked to.
     */
    public function handle(Doctor $doctor, ?UploadedFile $photo, bool $remove = false): void
    {
        if ($photo === null && ! $remove) {
            return;
        }

        if ($doctor->photo_path) {
            Storage::disk('local')->delete($doctor->photo_path);
        }

        $doctor->forceFill([
            'photo_path' => $photo?->store(self::DIRECTORY, 'local'),
        ])->save();
    }
}
