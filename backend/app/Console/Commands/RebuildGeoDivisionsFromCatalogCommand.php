<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

class RebuildGeoDivisionsFromCatalogCommand extends Command
{
    protected $signature = 'geo:rebuild-from-catalog
                            {--build-sy : Regenerate config/ad_regions_sy.php from public/geo/syria-geojson (Python)}
                            {--build-tr : Regenerate config/ad_regions_tr.php from turkey-geo-api JSON (Python)}';

    protected $description = 'Truncate and refill geo_divisions from config/ad_regions_*.php so cascade APIs match the catalog';

    public function handle(): int
    {
        if ($this->option('build-tr')) {
            $py = database_path('scripts/build_ad_regions_tr_from_turkey_geo_api.py');
            if (! is_file($py)) {
                $this->error('Missing '.$py);

                return self::FAILURE;
            }

            $result = Process::path(base_path())->run(['python3', $py]);
            $out = trim($result->output().$result->errorOutput());
            if ($out !== '') {
                $this->line($out);
            }
            if (! $result->successful()) {
                $this->error('Turkey catalog build failed (exit '.$result->exitCode().').');

                return self::FAILURE;
            }
        }

        if ($this->option('build-sy')) {
            $py = database_path('scripts/build_ad_regions_sy_from_geojson.py');
            if (! is_file($py)) {
                $this->error('Missing '.$py);

                return self::FAILURE;
            }

            $result = Process::path(base_path())->run(['python3', $py]);
            $out = trim($result->output().$result->errorOutput());
            if ($out !== '') {
                $this->line($out);
            }
            if (! $result->successful()) {
                $this->error('Python build failed (exit '.$result->exitCode().').');

                return self::FAILURE;
            }
        }

        $this->info('Running GeoDivisionsSeeder…');
        Artisan::call('db:seed', ['--class' => 'GeoDivisionsSeeder', '--force' => true]);
        $this->output->write(Artisan::output());

        Artisan::call('config:clear');
        $this->output->write(Artisan::output());

        $this->info('Geo divisions are in sync with config. Cascade selects will list all centers per governorate.');

        return self::SUCCESS;
    }
}
