<?php

namespace App\Services;

use App\Contracts\HasPhotos;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Holds an uploaded image on the local disk between the moment a user picks
 * it on a create form and the moment the record they are creating exists.
 *
 * The create forms stay non-multipart and carry only an opaque token, which
 * means the image survives a validation bounce (a file input cannot be
 * repopulated from old input, a hidden token can).
 */
class TempImageStore
{
    /** Local storage directory used for temporary image copies. */
    public const TEMP_DIR = 'image_temp';

    /**
     * Previous directory name, still read so that tokens minted before this
     * was deployed continue to attach. Never written to.
     */
    public const LEGACY_TEMP_DIR = 'flyer_temp';

    /**
     * Request fields that may carry a token. The legacy name is still read so
     * that a create form rendered before this shipped still attaches.
     */
    public const TOKEN_FIELDS = ['image_temp_token', 'flyer_temp_token'];

    /** Extensions we are willing to hand back to the image pipeline. */
    public const SAFE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function __construct(private readonly ImageHandler $imageHandler) {}

    /**
     * Copy an upload into local temp storage and return its token.
     *
     * Passing an existing token overwrites that slot instead of creating a
     * second file, so selecting an image and then analysing it leaves one
     * temp file rather than two.
     */
    public function stash(UploadedFile $file, ?string $reuseToken = null): string
    {
        $token = null;

        if ($reuseToken !== null && $this->isWellFormed($reuseToken)) {
            $token = $reuseToken;
        }

        if ($token === null) {
            // Use the MIME-validated extension from Laravel (not the client
            // filename) so a user-supplied extension is never trusted.
            $extension = $file->extension() ?: 'jpg';
            $token = Str::uuid()->toString() . '.' . $extension;
        }

        Storage::disk('local')->putFileAs(self::TEMP_DIR, $file, $token);

        return $token;
    }

    /**
     * Absolute path for a token, or null when the token is malformed or the
     * file is gone. This is the only place the token format is enforced.
     */
    public function path(?string $token): ?string
    {
        if ($token === null || !$this->isWellFormed($token)) {
            return null;
        }

        foreach ([self::TEMP_DIR, self::LEGACY_TEMP_DIR] as $dir) {
            $path = storage_path('app/' . $dir . '/' . $token);

            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * The temp-image token carried by a create-form submission, if any.
     */
    public function tokenFromRequest(Request $request): ?string
    {
        foreach (self::TOKEN_FIELDS as $field) {
            $token = $request->input($field);

            if (is_string($token) && $token !== '') {
                return $token;
            }
        }

        return null;
    }

    /**
     * Attach the image referenced by a create-form submission to $model.
     */
    public function attachFromRequest(Request $request, HasPhotos $model, bool $primary = true): ?Photo
    {
        return $this->attachTo($this->tokenFromRequest($request), $model, $primary);
    }

    /**
     * Attach the stashed image to $model as a photo, then delete the temp file.
     *
     * Never throws: a photo failure must not fail the creation of the record
     * it belongs to. Returns the Photo on success, null otherwise.
     */
    public function attachTo(?string $token, HasPhotos $model, bool $primary = true): ?Photo
    {
        $tempPath = $this->path($token);

        if ($tempPath === null) {
            return null;
        }

        $photo = null;

        try {
            $mimeType = mime_content_type($tempPath);

            if ($mimeType === false) {
                Log::warning('TempImageStore: could not determine MIME type', ['token' => $token]);
                $mimeType = 'image/jpeg';
            }

            $uploadedFile = new UploadedFile(
                $tempPath,
                (string) $token,
                $mimeType,
                null,
                true // already in place, so no temp-file move is attempted
            );

            $photo = $this->imageHandler->makePhoto($uploadedFile);
            $photo->is_primary = $primary ? 1 : 0;
            $photo->save();

            $model->addPhoto($photo);
        } catch (\Throwable $e) {
            Log::warning('TempImageStore: failed to attach image', [
                'token' => $token,
                'model' => $model::class,
                'error' => $e->getMessage(),
            ]);

            $photo = null;
        } finally {
            $this->delete($tempPath);
        }

        return $photo;
    }

    /**
     * Delete temp files older than $hours. Returns the number removed (or,
     * in dry-run mode, the number that would have been removed).
     */
    public function prune(int $hours = 24, bool $dryRun = false): int
    {
        $cutoff = now()->subHours($hours)->getTimestamp();
        $removed = 0;

        foreach ([self::TEMP_DIR, self::LEGACY_TEMP_DIR] as $dir) {
            $path = storage_path('app/' . $dir);

            if (!is_dir($path)) {
                continue;
            }

            foreach ((array) glob($path . '/*') as $file) {
                if (!is_string($file) || !is_file($file)) {
                    continue;
                }

                $modified = @filemtime($file);

                if ($modified === false || $modified >= $cutoff) {
                    continue;
                }

                if ($dryRun || $this->delete($file)) {
                    $removed++;
                }
            }
        }

        return $removed;
    }

    /**
     * A token is "{uuid}.{ext}" and nothing else — this is what keeps a
     * crafted token from walking out of the temp directory.
     */
    private function isWellFormed(string $token): bool
    {
        if (!preg_match('/^[a-f0-9\-]{36}\.([a-z]{2,4})$/', $token, $matches)) {
            return false;
        }

        return in_array($matches[1], self::SAFE_EXTENSIONS, true);
    }

    private function delete(string $path): bool
    {
        if (!file_exists($path)) {
            return false;
        }

        if (!unlink($path)) {
            Log::warning('TempImageStore: failed to delete temp file', ['path' => $path]);

            return false;
        }

        return true;
    }
}
