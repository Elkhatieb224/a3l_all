<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifySubcategoryRenamesCommand extends Command
{
    protected $signature = 'subcategories:verify-renames
                            {report : Path to rename report CSV}
                            {--fix : Apply missing updates for rows with entity_id}';

    protected $description = 'Verify that report entity_id rows have name_ar equal to the new column in the database';

    public function handle(): int
    {
        $path = $this->argument('report');

        if (! is_readable($path)) {
            $this->error("Report not readable: {$path}");

            return self::FAILURE;
        }

        $rows = $this->readReport($path);
        $checked = 0;
        $ok = 0;
        $missing = [];
        $wrong = [];

        foreach ($rows as $row) {
            $entityId = trim((string) ($row['entity_id'] ?? ''));
            $expectedNew = trim((string) ($row['new'] ?? ''));

            if ($entityId === '' || $expectedNew === '') {
                continue;
            }

            $checked++;
            $entityType = trim((string) ($row['entity_type'] ?? 'subcategory'));
            $table = $entityType === 'category' ? 'categories' : 'subcategories';
            $actual = DB::table($table)->where('id', (int) $entityId)->value('name_ar');

            if ($actual === null) {
                $missing[] = $row;
                continue;
            }

            if ((string) $actual === $expectedNew) {
                $ok++;
                continue;
            }

            $wrong[] = array_merge($row, [
                'actual_name_ar' => (string) $actual,
            ]);

            if ($this->option('fix')) {
                DB::table($table)->where('id', (int) $entityId)->update([
                    'name_ar' => $expectedNew,
                    'updated_at' => now(),
                ]);
            }
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Rows with entity_id', $checked],
                ['Correct in DB', $ok],
                ['Mismatch', count($wrong)],
                ['Missing entity', count($missing)],
            ]
        );

        if ($wrong !== []) {
            $this->newLine();
            $this->warn('Mismatches:');
            $this->table(
                ['line', 'entity_id', 'expected', 'actual', 'path'],
                array_map(fn (array $row) => [
                    $row['line'] ?? '',
                    $row['entity_id'] ?? '',
                    $row['new'] ?? '',
                    $row['actual_name_ar'] ?? '',
                    mb_substr((string) ($row['path'] ?? ''), 0, 60),
                ], array_slice($wrong, 0, 50))
            );

            if (count($wrong) > 50) {
                $this->line('... and '.(count($wrong) - 50).' more.');
            }
        }

        if ($this->option('fix') && $wrong !== []) {
            $this->info('Applied fixes for '.count($wrong).' rows.');
        }

        return ($wrong === [] && $missing === []) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return list<array<string, string>>
     */
    private function readReport(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'rb');
        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);

            return [];
        }

        while (($line = fgetcsv($handle)) !== false) {
            if ($line === false || count($line) < count($header)) {
                continue;
            }

            $rows[] = array_combine($header, array_slice($line, 0, count($header)));
        }

        fclose($handle);

        return $rows;
    }
}
