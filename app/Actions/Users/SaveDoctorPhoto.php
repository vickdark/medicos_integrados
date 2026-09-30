<?php

namespace App\Actions\Users;

use App\Models\Doctor;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores or removes the photo a doctor shows on their profile and on the landing
 * page. Photos live on the private disk and are served through a controller.
 * Uploads are scaled down and re-encoded so they weigh far less than the original.
 */
class SaveDoctorPhoto
{
    public const DIRECTORY = 'doctor-photos';

    public const MAX_DIMENSION = 800;

    public const QUALITY = 82;

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
            'photo_path' => $photo ? $this->store($photo) : null,
        ])->save();
    }

    /**
     * Store the optimized photo, or the original when it cannot be processed.
     */
    private function store(UploadedFile $photo): string
    {
        $optimized = $this->optimize($photo);

        if ($optimized === null) {
            return $photo->store(self::DIRECTORY, 'local');
        }

        $path = self::DIRECTORY.'/'.Str::random(40).'.webp';

        Storage::disk('local')->put($path, $optimized);

        return $path;
    }

    /**
     * Scale the image down to fit MAX_DIMENSION, honoring the camera orientation,
     * and encode it as WebP. Returns null when the image cannot be handled.
     */
    private function optimize(UploadedFile $photo): ?string
    {
        if (! function_exists('imagewebp')) {
            return null;
        }

        $source = @imagecreatefromstring((string) file_get_contents($photo->getRealPath()));

        if (! $source instanceof GdImage) {
            return null;
        }

        $source = $this->orient($source, $photo);

        $width = imagesx($source);
        $height = imagesy($source);
        $ratio = min(1, self::MAX_DIMENSION / max($width, $height));

        if ($ratio < 1) {
            $source = imagescale($source, (int) round($width * $ratio), (int) round($height * $ratio)) ?: $source;
        }

        imagepalettetotruecolor($source);
        imagealphablending($source, false);
        imagesavealpha($source, true);

        ob_start();
        $encoded = imagewebp($source, null, self::QUALITY);
        $contents = ob_get_clean();

        return $encoded && is_string($contents) && $contents !== '' ? $contents : null;
    }

    /**
     * Rotate the image according to its EXIF orientation, as phones store portrait
     * photos sideways.
     */
    private function orient(GdImage $image, UploadedFile $photo): GdImage
    {
        if (! function_exists('exif_read_data') || ! in_array($photo->getMimeType(), ['image/jpeg', 'image/jpg'], true)) {
            return $image;
        }

        $orientation = @exif_read_data($photo->getRealPath())['Orientation'] ?? 1;

        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        return $angle === 0 ? $image : (imagerotate($image, $angle, 0) ?: $image);
    }
}
