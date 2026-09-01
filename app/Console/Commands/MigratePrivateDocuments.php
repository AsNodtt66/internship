<?php

namespace App\Console\Commands;

use App\Support\Documents\PrivateDocumentRegistry;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MigratePrivateDocuments extends Command
{
    protected $signature = 'documents:migrate-private
        {--keep-public : Keep the legacy public copy after a successful migration}';

    protected $description = 'Move database-referenced sensitive documents from the legacy public disk to private storage.';

    public function handle(): int
    {
        $source = Storage::disk('public');
        $target = Storage::disk(config('filesystems.private_documents_disk', 'documents'));
        $keepPublic = (bool) $this->option('keep-public');
        $migrated = 0;
        $alreadyPrivate = 0;
        $missing = 0;

        foreach (PrivateDocumentRegistry::modelFields() as $modelClass => $fields) {
            /** @var Model $model */
            $model = new $modelClass;

            $modelClass::query()
                ->select(array_merge([$model->getKeyName()], $fields))
                ->orderBy($model->getKeyName())
                ->chunkById(100, function ($records) use ($fields, $source, $target, $keepPublic, &$migrated, &$alreadyPrivate, &$missing): void {
                    foreach ($records as $record) {
                        foreach ($fields as $field) {
                            $path = $record->getAttribute($field);

                            if ($path === null || $path === '') {
                                continue;
                            }

                            if (! PrivateDocumentRegistry::isSafePath($path)) {
                                $this->warn('Lewati path tidak aman: '.$record::class.'#'.$record->getKey().' '.$field);
                                $missing++;

                                continue;
                            }

                            if ($target->exists($path)) {
                                $alreadyPrivate++;

                                if (! $keepPublic && $source->exists($path)) {
                                    $source->delete($path);
                                }

                                continue;
                            }

                            if (! $source->exists($path)) {
                                $this->warn('File tidak ditemukan: '.$record::class.'#'.$record->getKey().' '.$field.' => '.$path);
                                $missing++;

                                continue;
                            }

                            $stream = $source->readStream($path);

                            if (! is_resource($stream)) {
                                $this->warn("Gagal membaca: {$path}");
                                $missing++;

                                continue;
                            }

                            try {
                                $target->writeStream($path, $stream, ['visibility' => 'private']);
                            } finally {
                                fclose($stream);
                            }

                            if (! $keepPublic) {
                                $source->delete($path);
                            }

                            $migrated++;
                        }
                    }
                });
        }

        $this->newLine();
        $this->info("Migrated: {$migrated}; already private: {$alreadyPrivate}; missing/unsafe: {$missing}.");

        return $missing > 0 ? self::FAILURE : self::SUCCESS;
    }
}
