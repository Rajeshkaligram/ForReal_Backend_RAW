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
            if (empty($statement)) {
                continue;
            }
            
            // Skip CREATE, ALTER, DROP statements
            $upper = strtoupper(substr(ltrim($statement), 0, 10));
            if (strpos($upper, 'CREATE') !== false || strpos($upper, 'ALTER') !== false || strpos($upper, 'DROP') !== false) {
                continue;
            }
            
            // Process INSERT statements
            $upperFull = strtoupper(ltrim($statement));
            if (strpos($upperFull, 'INSERT INTO') === 0 || strpos($upperFull, 'INSERT IGNORE') === 0) {
                try {
                    DB::statement($statement);
                } catch (\Exception $e) {
                    // Try with INSERT IGNORE for duplicate key errors
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $insertPos = strpos($upperFull, 'INSERT INTO');
                        if ($insertPos !== false) {
                            $ignoreStatement = substr_replace($statement, 'INSERT IGNORE INTO', $insertPos, strlen('INSERT INTO'));
                            try {
                                DB::statement($ignoreStatement);
                            } catch (\Exception $e2) {
                                // Skip
                            }
                        }
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
