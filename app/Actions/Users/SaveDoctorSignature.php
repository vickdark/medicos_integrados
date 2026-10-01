<?php

namespace App\Actions\Users;

use App\Models\Doctor;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores or removes the image of the doctor's handwritten signature, which is
 * printed on prescriptions and clinical documents. It lives on the private
 * disk and is scaled down and saved as PNG to keep a transparent background.
 */
class SaveDoctorSignature
{
    public const DIRECTORY = 'doctor-signatures';

    private const MAX_WIDTH = 600;

    private const MAX_HEIGHT = 240;

    /**
     * Replace the signature when a new file arrives, or remove it when asked to.
     */
    public function handle(Doctor $doctor, ?UploadedFile $signature, bool $remove = false): void
    {
        if ($signature === null && ! $remove) {
            return;
        }

        if ($doctor->signature_path) {
            Storage::disk('local')->delete($doctor->signature_path);
        }

        $doctor->forceFill([
            'signature_path' => $signature ? $this->store($signature) : null,
        ])->save();
    }

    /**
     * Store the scaled PNG, or the original file when it cannot be processed.
     */
    private function store(UploadedFile $signature): string
    {
        $png = $this->toPng($signature);

        if ($png === null) {
            return $signature->store(self::DIRECTORY, 'local');
        }

        $path = self::DIRECTORY.'/'.Str::random(40).'.png';
        Storage::disk('local')->put($path, $png);

        return $path;
    }

    private function toPng(UploadedFile $signature): ?string
    {
        $image = @imagecreatefromstring((string) file_get_contents($signature->getRealPath()));

        if (! $image instanceof GdImage) {
            return null;
        }

        $ratio = min(1, self::MAX_WIDTH / imagesx($image), self::MAX_HEIGHT / imagesy($image));

        if ($ratio < 1) {
            $image = imagescale($image, (int) round(imagesx($image) * $ratio), (int) round(imagesy($image) * $ratio)) ?: $image;
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        ob_start();
        $encoded = imagepng($image, null, 9);
        $contents = ob_get_clean();

        return $encoded && is_string($contents) && $contents !== '' ? $contents : null;
    }
}
