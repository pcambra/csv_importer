<?php

namespace Drupal\Tests\csv_importer\Kernel;

use Drupal\asset\Entity\Asset;

/**
 * Test update of assets using CSV import.
 *
 * @group csv_importer
 */
class UpdateTest extends CsvImporterTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['csv_importer_asset_update_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['csv_importer_asset_update_test']);
  }

  /**
   * Update CSV test.
   */
  public function testUpdateCsv() {
    Asset::create([
      'id' => '1',
      'name' => 'Valentina',
      'type' => 'cat',
      'age' => '1',
    ]);
    Asset::create([
      'id' => '2',
      'name' => 'Yuuna',
      'type' => 'cat',
      'age' => '1',
    ]);
    Asset::create([
      'id' => '3',
      'name' => 'Ada',
      'type' => 'cat',
      'age' => '0',
    ]);
    $this->runMigration();

    $assets = Asset::loadMultiple([1,2,3]);
    $this->assertCount(3, $assets);
    $this->assertEquals(2, $assets[1]->get('age')->value);
    $this->assertEquals(2, $assets[2]->get('age')->value);
    $this->assertEquals(1, $assets[3]->get('age')->value);
  }

}
