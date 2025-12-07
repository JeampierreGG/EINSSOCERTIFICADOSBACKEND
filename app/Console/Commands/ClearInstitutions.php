<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Institution;
use App\Models\Certificate;
use App\Models\CertificateItem;

class ClearInstitutions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-institutions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina todas las instituciones y certificados asociados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        CertificateItem::query()->delete();
        Certificate::query()->delete();
        Institution::query()->delete();
        $this->info('Instituciones y certificados eliminados.');
    }
}
