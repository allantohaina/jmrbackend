<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DropAllTables extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:dropall';
    protected $description = 'Drop all tables in the database';

    public function run(array $params)
    {
        $db = \Config\Database::connect();

        $tables = $db->listTables();

        if (empty($tables)) {
            CLI::write('No tables to drop.', 'yellow');
            return;
        }

        CLI::write('Dropping tables:', 'yellow');
        $db->disableForeignKeyChecks();

        $driver = strtolower($db->DBDriver);

        foreach ($tables as $table) {
            $ident = $db->escapeIdentifiers($table);

            if ($driver === 'postgre') {
                $db->query("DROP TABLE IF EXISTS $ident CASCADE");
            } elseif ($driver === 'oci8') {
                $db->query("DROP TABLE $ident CASCADE CONSTRAINTS");
            } else {
                $db->query("DROP TABLE IF EXISTS $ident");
            }

            CLI::write("- $table", 'red');
        }

        $db->enableForeignKeyChecks();

        CLI::write('All tables dropped successfully!', 'green');
    }
}
