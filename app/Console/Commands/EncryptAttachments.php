<?php

namespace App\Console\Commands;

use App\Models\ConsultationAttachment;
use Illuminate\Console\Command;

class EncryptAttachments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attachments:encrypt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cifra en disco los archivos adjuntos clínicos que se guardaron sin cifrar';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $encrypted = 0;
        $missing = 0;

        ConsultationAttachment::query()
            ->where('is_encrypted', false)
            ->lazyById(100)
            ->each(function (ConsultationAttachment $attachment) use (&$encrypted, &$missing): void {
                if ($attachment->encryptStoredFile()) {
                    $encrypted++;
                } else {
                    $missing++;
                    $this->warn("No se encontró el archivo del adjunto #{$attachment->id} ({$attachment->path}).");
                }
            });

        $this->info("Adjuntos cifrados: {$encrypted}. Sin archivo en disco: {$missing}.");

        return self::SUCCESS;
    }
}
