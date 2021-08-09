<?php

namespace Drupal\csv_importer\Plugin\migrate;

/**
 * Asset deriver class.
 */
class AssetDeriver extends CsvImporterDeriverBase {

  /**
   * The entity type id for the deriver,
   *
   * @var string
   */
  protected $entityTypeId = 'asset';

}
