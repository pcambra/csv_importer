<?php

namespace Drupal\Tests\csv_importer\Kernel;

use Drupal\asset\Entity\Asset;

/**
 * Test import of CSV.
 *
 * @group csv_importer
 */
class ImportTest extends CsvImporterTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['csv_importer_asset_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['csv_importer_asset_test']);
  }

  /**
   * Import CSV test.
   */
  public function testImportCsv() {
    $this->runMigration();
    $assets = Asset::loadMultiple([1,2,3]);
    $this->assertCount(3, $assets);
    $this->assertEquals('Valentina', $assets[1]->label());
    $this->assertEquals('Yuuna', $assets[2]->label());
    $this->assertEquals('Ada', $assets[3]->label());
    $this->assertEquals(1, $assets[1]->get('age')->value);
    $this->assertEquals(1, $assets[2]->get('age')->value);
    $this->assertEquals(0, $assets[3]->get('age')->value);
  }

}
