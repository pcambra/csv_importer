<?php

namespace Drupal\csv_importer\Form;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;
use Drupal\migrate_source_ui\StubMigrationMessage;
use Drupal\migrate_source_ui\MigrateBatchExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 *
 */
class ImportForm extends FormBase {

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $pluginManagerMigration;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The migration definitions.
   *
   * @var array
   */
  protected $definitions;

  /**
   * Config object for migrate_source_ui.settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * MigrateSourceUiForm constructor.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManager $plugin_manager_migration
   *   The migration plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(MigrationPluginManager $plugin_manager_migration, EntityFieldManagerInterface $entity_field_manager) {
    $this->pluginManagerMigration = $plugin_manager_migration;
    $this->entityFieldManager = $entity_field_manager;
    $this->definitions = $this->pluginManagerMigration->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.migration'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'csv_importer_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = '', $bundle = '') {
    try {
      //@TODO throw exception if it's not a log or asset.

      $field_options = [];
      /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $fields */
      $fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
      foreach ($fields as $field_name => $field) {
        $field_options[$field_name] = $field_name;
      }
      $form['info'] = [
        '#type' => 'details',
        '#title' => $this->t('Import details'),
        '#open' => TRUE,
      ];
      $form['info']['fields'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Fields that can be used for %entity_type %bundle import, are: @fields',[
          '%entity_type' => $entity_type_id,
          '%bundle' => $bundle,
          '@fields' => implode(', ', $field_options),
        ]),
      ];
      $form_state->setTemporaryValue('entity_type_id', $entity_type_id);
      $form_state->setTemporaryValue('bundle', $bundle);

      $form['source_file'] = [
        '#type' => 'file',
        '#title' => $this->t('Upload the source file'),
      ];
      $form['update_existing_records'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Update existing records'),
        '#default_value' => 0,
      ];
      $form['import'] = [
        '#type' => 'submit',
        '#value' => $this->t('Import'),
      ];
    }
    catch (\Exception $e) {
      watchdog_exception('csv_importer', $e);
      throw new NotFoundHttpException();
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $migration_id = $this->getMigrationId($form_state);
    $definition = $this->pluginManagerMigration->getDefinition($migration_id);
    $migrationInstance = $this->pluginManagerMigration->createStubMigration($definition);
    if ($migrationInstance->getSourcePlugin() instanceof CSV) {
      $extension = 'csv';
    }
    else {
      $form_state->setErrorByName('source_file', $this->t('Only CSV files are supported.'));
    }
    $validators = ['file_validate_extensions' => [$extension]];
    $file = file_save_upload('source_file', $validators, FALSE, 0, FileSystemInterface::EXISTS_REPLACE);

    if (isset($file)) {
      // File upload was attempted.
      if ($file) {
        $form_state->setValue('file_path', $file->getFileUri());
      }
      // File upload failed.
      else {
        $form_state->setErrorByName('source_file', $this->t('The file could not be uploaded.'));
      }
    }
    else {
      $form_state->setErrorByName('source_file', $this->t('You have to upload a source file.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $migration_id = $this->getMigrationId($form_state);
    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->pluginManagerMigration->createInstance($migration_id);

    // Reset status.
    $status = $migration->getStatus();
    if ($status !== MigrationInterface::STATUS_IDLE) {
      $migration->setStatus(MigrationInterface::STATUS_IDLE);
      $this->messenger()->addWarning($this->t('Migration @id reset to Idle', ['@id' => $migration_id]));
    }

    $options = [
      'file_path' => $form_state->getValue('file_path'),
    ];
    // Force updates or not.
    if ($form_state->getValue('update_existing_records')) {
      $options['update'] = TRUE;
    }

    $executable = new MigrateBatchExecutable($migration, new StubMigrationMessage(), $options);
    $executable->batchImport();
  }

  /**
   * Returns the migration id based on the form state values.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *  Form state.
   *
   * @return string
   *  The migration id.
   */
  protected function getMigrationId(FormStateInterface $form_state) {
    $entity_type_id = $form_state->getTemporaryValue('entity_type_id');
    $bundle = $form_state->getTemporaryValue('bundle');
    return "csv_importer_$entity_type_id:$entity_type_id:$bundle";
  }

}
