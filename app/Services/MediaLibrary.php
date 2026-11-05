<?php

namespace App\Services;

use App\Models\Media;
use App\Models\MediaUsage;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class MediaLibrary
{
    public function __construct(private ActivityLogger $activity) {}

    public function store(UploadedFile $file, array $alt, ?User $user, bool $allowSvg = false): Media
    {
        $mime = (string) $file->getMimeType();
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

        if ($allowSvg) {
            $allowed[] = 'image/svg+xml';
        }

        if (! in_array($mime, $allowed, true)) {
            throw new RuntimeException('Unsupported media type.');
        }

        if ($mime === 'image/svg+xml') {
            $svg = (string) file_get_contents($file->getRealPath());

            if (preg_match('/<script|on[a-z]+\s*=|javascript:|data:text\/html/i', $svg) === 1) {
                throw new RuntimeException('SVG rejected.');
            }

            $filename = Str::uuid()->toString().'.svg';
            $path = $file->storeAs('media', $filename, 'public');

            return $this->persist($path, $file->getClientOriginalName(), $mime, Storage::disk('public')->size($path), null, null, $alt, $user);
        }

        $optimized = $this->optimize($file);
        $filename = Str::uuid()->toString().'.'.$optimized['extension'];
        $path = 'media/'.$filename;
        Storage::disk('public')->put($path, $optimized['binary']);

        return $this->persist(
            $path,
            $file->getClientOriginalName(),
            $optimized['mime'],
            strlen($optimized['binary']),
            $optimized['width'],
            $optimized['height'],
            $alt,
            $user,
        );
    }

    public function replace(Media $media, UploadedFile $file, ?User $user): Media
    {
        $previous = $media->path;
        $stored = $this->store($file, $media->alt ?? ['ar' => '', 'en' => ''], $user, $media->mime === 'image/svg+xml');
        $media->fill($stored->only(['path', 'filename', 'mime', 'size', 'width', 'height']));
        $media->save();
        $stored->delete();
        Storage::disk('public')->delete($previous);
        $this->activity->log('media.replaced', 'Replaced media file', $media, ['filename' => $media->filename], $user);

        return $media->refresh();
    }

    public function delete(Media $media): void
    {
        if ($media->usages()->exists()) {
            throw new RuntimeException('in_use');
        }

        Storage::disk($media->disk)->delete($media->path);
        $media->delete();
    }

    /**
     * @param  array<int, int|null>  $mediaIds
     */
    public function syncUsage(Model $model, array $mediaIds, string $context): void
    {
        MediaUsage::query()
            ->where('usable_type', $model->getMorphClass())
            ->where('usable_id', $model->getKey())
            ->where('context', $context)
            ->delete();

        foreach (array_unique(array_filter($mediaIds)) as $id) {
            MediaUsage::query()->create([
                'media_id' => $id,
                'usable_type' => $model->getMorphClass(),
                'usable_id' => $model->getKey(),
                'context' => $context,
            ]);
        }
    }

    /**
     * @param  array{ar?: string, en?: string}  $alt
     */
    private function persist(string $path, string $filename, string $mime, int $size, ?int $width, ?int $height, array $alt, ?User $user): Media
    {
        $media = Media::query()->create([
            'disk' => 'public',
            'path' => $path,
            'filename' => $filename,
            'mime' => $mime,
            'size' => $size,
            'width' => $width,
            'height' => $height,
            'alt' => [
                'ar' => trim((string) ($alt['ar'] ?? '')),
                'en' => trim((string) ($alt['en'] ?? '')),
            ],
            'uploaded_by' => $user?->id,
        ]);

        $this->activity->log('media.uploaded', 'Uploaded media', $media, ['filename' => $filename], $user);

        return $media;
    }

    /**
     * @return array{binary: string, mime: string, extension: string, width: int, height: int}
     */
    private function optimize(UploadedFile $file): array
    {
        $contents = (string) file_get_contents($file->getRealPath());
        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            throw new RuntimeException('Invalid image.');
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $max = 2000;

        if ($width > $max || $height > $max) {
            $ratio = min($max / $width, $max / $height);
            $targetWidth = max(1, (int) round($width * $ratio));
            $targetHeight = max(1, (int) round($height * $ratio));
            $resized = imagecreatetruecolor($targetWidth, $targetHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
            $width = $targetWidth;
            $height = $targetHeight;
        }

        ob_start();

        if (function_exists('imagewebp')) {
            imagewebp($image, null, 82);
            $binary = (string) ob_get_clean();
            imagedestroy($image);

            return [
                'binary' => $binary,
                'mime' => 'image/webp',
                'extension' => 'webp',
                'width' => $width,
                'height' => $height,
            ];
        }

        imagejpeg($image, null, 84);
        $binary = (string) ob_get_clean();
        imagedestroy($image);

        return [
            'binary' => $binary,
            'mime' => 'image/jpeg',
            'extension' => 'jpg',
            'width' => $width,
            'height' => $height,
        ];
    }
}
