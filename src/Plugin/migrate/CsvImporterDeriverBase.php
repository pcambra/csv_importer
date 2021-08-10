<?php

namespace Drupal\csv_importer\Plugin\migrate;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for CSV Importer derivatives.
 */
abstract class CsvImporterDeriverBase extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The base plugin ID this derivative is for.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Excluded fields.
   */
  protected $excludedFields = [
    'type',
  ];

  /**
   * Constructs a new AssetDeriver.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   Bundle info service.
   */
  public function __construct($base_plugin_id, EntityTypeManagerInterface $entityTypeManager, EntityTypeBundleInfoInterface $bundle_info, EntityFieldManagerInterface $entity_field_manager) {
    $this->basePluginId = $base_plugin_id;
    $this->entityTypeManager = $entityTypeManager;
    $this->bundleInfo = $bundle_info;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $bundle_info = $this->bundleInfo->getBundleInfo($this->entityTypeId);
    foreach ($bundle_info as $bundle_name => $bundle) {
      $derivative = $this->getDerivativeValues($base_plugin_definition, $bundle_name);
      $this->derivatives["$this->entityTypeId:$bundle_name"] = $derivative;
    }

    return $this->derivatives;
  }

  /**
   * Get derivative values.
   *
   * @return array
   */
  protected function getDerivativeValues(array $base_plugin_definition, $bundle_name) {
    $base_plugin_definition['process']['type'] = [
      'plugin' => 'default_value',
      'default_value' => $bundle_name,
    ];
    $base_plugin_definition['destination'] = [
      'plugin' => "entity:$this->entityTypeId",
      'translation' => FALSE,
      'validate' => TRUE,
    ];

    $fields = $this->getFields($bundle_name);
    foreach ($fields as $field_name => $field) {
      $base_plugin_definition['column_names'][] = [
        $field_name => $field_name,
      ];
      $base_plugin_definition['process'][$field_name] = [
        'plugin' => 'get',
        'source' => $field_name,
      ];
    }

    return $base_plugin_definition;
  }

  /**
   * Gets the fields minus the excluded ones.
   *
   * @param string $bundle_name
   *  The bundle name.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *  Array of field definitions.
   */
  protected function getFields($bundle_name) {
    $fields = $this->entityFieldManager->getFieldDefinitions($this->entityTypeId, $bundle_name);
    return array_diff_key($fields, array_flip($this->excludedFields));
  }

}
