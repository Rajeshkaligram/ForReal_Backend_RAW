<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sqlFile = base_path('db_rentasuit_php.sql');

        if (!file_exists($sqlFile)) {
            return;
        }

        $sql = file_get_contents($sqlFile);

        // Remove comments
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('!/\*.*?\*/!s', '', $sql);

        // Remove SET and transaction statements
        $sql = preg_replace('/SET\s+[^;]+;/i', '', $sql);
        $sql = str_replace('START TRANSACTION;', '', $sql);
        $sql = str_replace('COMMIT;', '', $sql);
        $sql = str_replace('ROLLBACK;', '', $sql);

        // Split by semicolons
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $statement) {
            if ($statement === '') {
                continue;
            }

            $trimmed = ltrim($statement);
            $upper = strtoupper(substr($trimmed, 0, 20));

            // Only INSERT statements
            if (strpos($upper, 'INSERT INTO') !== 0 && strpos($upper, 'INSERT IGNORE') !== 0) {
                continue;
            }

            try {
                DB::statement($statement);
            } catch (\Exception $e) {
                // If table doesn't exist, skip
                if (strpos($e->getMessage(), 'Base table or view not found') !== false) {
                    continue;
                }

                // If duplicate key, try INSERT IGNORE
                if (strpos($e->getMessage(), 'Duplicate entry') !== false && strpos(strtoupper($trimmed), 'INSERT IGNORE') !== 0) {
                    $ignoreStatement = preg_replace('/^INSERT\s+INTO/i', 'INSERT IGNORE INTO', $trimmed);
                    try {
                        DB::statement($ignoreStatement);
                    } catch (\Exception $e2) {
                        continue;
                    }
                }
            }
        }
    }

    public function down(): void
    {
        // No reverse
    }
};
