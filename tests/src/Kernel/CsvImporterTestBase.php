<?php

namespace Drupal\Tests\csv_importer\Kernel;

use Drupal\Tests\migrate\Kernel\MigrateTestBase;

/**
 * Creates abstract base class for CSV importer tests.
 */
abstract class CsvImporterTestBase extends MigrateTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // Base modules.
    'system',
    'field',
    'user',
    // Migrate depencencies.
    'migrate',
    'migrate_source_csv',
    // Farm requirements.
    'asset',
    'farm_field',
    'farm_location',
    'farm_log',
    // Contrib modules needed by the farm modules installed.
    'geofield',
    'state_machine',
    // Import modules for the test.
    'csv_importer',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('asset');
    $this->installConfig(['csv_importer']);
  }

  /**
   * Runs the bundle migration.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function runMigration() {
    /** @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migrationManager */
    $migrationManager = $this->container->get('plugin.manager.migration');
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $migrationManager->createInstance('csv_importer_asset:asset:cat');
    $this->executeMigration($migration);
  }

}
