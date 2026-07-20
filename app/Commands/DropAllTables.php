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

        foreach ($tables as $table) {
            $db->query("DROP TABLE IF EXISTS \"$table\" CASCADE");
            CLI::write("- $table", 'red');
        }

        $db->enableForeignKeyChecks();

        CLI::write('All tables dropped successfully!', 'green');
    }
}
