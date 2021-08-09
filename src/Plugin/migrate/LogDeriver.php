<?php

namespace Drupal\csv_importer\Plugin\migrate;

/**
 * Log deriver class.
 */
class LogDeriver extends CsvImporterDeriverBase {

  /**
   * The entity type id for the deriver,
   *
   * @var string
   */
  protected $entityTypeId = 'log';

}
