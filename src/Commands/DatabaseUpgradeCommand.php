<?php

namespace Laravel\VaporCli\Commands;

use Illuminate\Support\Arr;
use Laravel\VaporCli\Helpers;
use Symfony\Component\Console\Input\InputArgument;

class DatabaseUpgradeCommand extends Command
{
    /**
     * Existing database types.
     *
     * @var array
     */
    protected $databaseTypes = [
        'rds'                     => 'Fixed Size MySQL Instance 8.0 (Free Tier Eligible)',
        'rds-mysql-5.7'           => 'Fixed Size MySQL Instance 5.7 (Free Tier Eligible)',
        'aurora-serverless'       => 'Serverless MySQL Aurora Cluster',
        'rds-pgsql-11.10'         => 'Fixed Size PostgreSQL Instance 11.10',
        'rds-pgsql'               => 'Fixed Size PostgreSQL Instance 10.7',
        'aurora-serverless-pgsql' => 'Serverless PostgreSQL Aurora Cluster',
    ];

    /**
     * Existing database possible upgrades types.
     *
     * @var array
     */
    protected $possibleUpgrades = [
        'rds-mysql-5.7' => ['rds'],
        'rds-pgsql'     => ['rds-pgsql-11.10'],
    ];

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('database:upgrade')
            ->addArgument('from', InputArgument::REQUIRED, 'The name / ID of the existing database')
            ->addArgument('to', InputArgument::REQUIRED, 'The name of the new database')
            ->setDescription('Create a new database of the selected type containing the contents of an existing database.');
    }

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        Helpers::ensure_api_token_is_available();

        if (! is_numeric($databaseId = $this->argument('from'))) {
            $databaseId = $this->findIdByName($this->vapor->databases(), $databaseId);
        }

        if (is_null($databaseId)) {
            Helpers::abort('Unable to find a database with that name / ID.');
        }

        $databaseType = $this->determineDatabaseType($databaseId);

        if (is_null($databaseType)) {
            Helpers::danger('No possible upgrades were found for the given database');

            return 1;
        }

        if (! Helpers::confirm('Create a new database ['
            .$this->argument('to').'] that contains the contents of ['
            .$this->argument('from').'] and is of the type ('.$this->databaseTypes[$databaseType].')', false)) {
            Helpers::abort('Action cancelled.');
        }

        $this->vapor->upgradeDatabase(
            $databaseId,
            $this->argument('to'),
            $databaseType,
        );

        Helpers::info('Database upgrade initiated successfully.');
        Helpers::line();
        Helpers::line('Databases may take several minutes to finish provisioning.');
    }

    /**
     * Determine the database type.
     *
     * @param  int  $databaseId
     *
     * @return string|null
     */
    protected function determineDatabaseType($databaseId)
    {
        $databaseType = $this->vapor->database($databaseId)['type'];
        $possibleUpgrades = Arr::get($this->possibleUpgrades, $databaseType, []);

        if (! empty($possibleUpgrades)) {
            return $this->menu('Which type of database would you like to create?', collect($this->databaseTypes)
                ->filter(function ($label, $type) use ($possibleUpgrades) {
                    return in_array($type, $possibleUpgrades);
                })->all(),
            );
        }
    }
}
