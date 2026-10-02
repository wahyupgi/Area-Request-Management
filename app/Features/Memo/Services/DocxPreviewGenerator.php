<?php

namespace App\Features\Memo\Services;

use App\Models\MemoAttachment;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

class DocxPreviewGenerator
{
    public function generate(MemoAttachment $attachment): ?string
    {
        $extension = strtolower(pathinfo($attachment->original_name ?: $attachment->file_path, PATHINFO_EXTENSION));
        if (! in_array($extension, ['doc', 'docx'], true)) {
            return null;
        }

        $disk = Storage::disk('public');
        $sourcePath = $disk->path($attachment->file_path);
        if (! is_file($sourcePath)) {
            return null;
        }

        $temporaryDirectory = storage_path('app/tmp/memo-docx-preview/'.Str::uuid());

        try {
            File::ensureDirectoryExists($temporaryDirectory.'/profile');
            $profilePath = str_replace('\\', '/', $temporaryDirectory.'/profile');
            if (preg_match('/^[A-Za-z]:\//', $profilePath)) {
                $profilePath = '/'.$profilePath;
            }

            $process = new Process([
                config('services.libreoffice.binary'),
                '--headless',
                '-env:UserInstallation=file://'.$profilePath,
                '--convert-to',
                'pdf',
                '--outdir',
                $temporaryDirectory,
                $sourcePath,
            ]);
            $process->setTimeout(120);
            $process->run();

            $convertedPath = $temporaryDirectory.DIRECTORY_SEPARATOR.pathinfo($sourcePath, PATHINFO_FILENAME).'.pdf';
            if (! $process->isSuccessful() || ! is_file($convertedPath)) {
                throw new \RuntimeException($process->getErrorOutput() ?: 'LibreOffice did not create a PDF preview.');
            }

            $previewPath = dirname($attachment->file_path).'/'.$attachment->id.'.preview.pdf';
            $pdfContents = file_get_contents($convertedPath);
            if ($pdfContents === false || ! $disk->put($previewPath, $pdfContents)) {
                throw new \RuntimeException('The generated PDF preview could not be stored.');
            }
            $attachment->forceFill(['preview_path' => $previewPath])->save();

            return $previewPath;
        } catch (Throwable $exception) {
            Log::warning('Memo DOCX preview conversion failed.', [
                'attachment_id' => $attachment->id,
                'error' => $exception->getMessage(),
            ]);

            return null;
        } finally {
            File::deleteDirectory($temporaryDirectory);
        }
    }
}
