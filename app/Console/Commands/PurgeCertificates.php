<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Certificate;
use App\Models\CertificateItem;

class PurgeCertificates extends Command
{
    protected $signature = 'app:purge-certificates';

    protected $description = 'Elimina todos los registros de certificates y certificate_items';

    public function handle(): int
    {
        CertificateItem::query()->delete();
        Certificate::query()->delete();
        $this->info('Registros de certificates y certificate_items eliminados.');
        return self::SUCCESS;
    }
}

