<?php

namespace Drupal\csv_importer;

use Drupal\Core\Messenger\MessengerTrait;
use Drupal\migrate\MigrateMessageInterface;

/**
 * A stub migrate message.
 */
class StubMigrationMessage implements MigrateMessageInterface {
  use MessengerTrait;

  /**
   * Output a message from the migration.
   *
   * @param string $message
   *   The message to display.
   * @param string $type
   *   The type of message to display.
   *
   * @see drupal_set_message()
   */
  public function display($message, $type = 'status') {
    $this->messenger()->addMessage($message, $type);
  }

}
