<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SqlImportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $sqlFile = database_path('../db_rentasuit_php.sql');
        
        if (!file_exists($sqlFile)) {
            echo "SQL file not found: $sqlFile\n";
            return;
        }

        echo "Reading SQL file...\n";
        $sql = file_get_contents($sqlFile);
        
        // Remove comments and clean up SQL
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        $sql = preg_replace('/SET.*?;/', '', $sql);
        $sql = preg_replace('/START TRANSACTION;/', '', $sql);
        $sql = preg_replace('/COMMIT;/', '', $sql);
        $sql = preg_replace('/ROLLBACK;/', '', $sql);
        
        // Split by semicolons
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        echo "Executing " . count($statements) . " SQL statements...\n";
        
        foreach ($statements as $statement) {
            if (empty($statement) || strtoupper(substr($statement, 0, 6)) === 'CREATE') {
                continue;
            }
            
            try {
                DB::statement($statement);
            } catch (\Exception $e) {
                echo "Error executing statement: " . substr($statement, 0, 100) . "...\n";
                echo "Error: " . $e->getMessage() . "\n";
            }
        }
        
        echo "SQL import completed.\n";
    }
}
