<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackup;
use App\Services\MailOutbox;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class StoreMaintenance extends Command
{
    protected $signature = 'isablue:maintenance {--backup : Crear respaldo aunque ya exista uno reciente}';

    protected $description = 'Procesa avisos pendientes y crea el respaldo diario sin workers permanentes.';

    public function handle(): int
    {
        $lock = Cache::lock('store-maintenance', 300);
        if (! $lock->get()) {
            return 0;
        }
        try {
            $n = MailOutbox::process(10);
            $this->info('Correos enviados: '.$n);
            if ($this->option('backup') || DatabaseBackup::due()) {
                $this->info('Respaldo verificado: '.DatabaseBackup::create());
            }

            return 0;
        } finally {
            $lock->release();
        }
    }
}
