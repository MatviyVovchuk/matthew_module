<?php

namespace Drupal\matthew\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;

/**
 * Form to confirm the deletion of a cat record.
 */
class ConfirmDeleteCatForm extends ConfirmFormBase {

  /**
   * The ID of the cat record.
   *
   * @var int
   */
  protected int $id;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    // Return the unique ID of the form.
    return 'delete_cat_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): string {
    // Return the confirmation question text.
    return $this->t('Are you sure you want to delete this cat record?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    // Return the URL to cancel and go back to the cat view page.
    return new Url('matthew.view');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): string {
    // Return the text for the confirmation button.
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText(): string {
    // Return the text for the cancel button.
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL): array {
    // Set the ID of the cat record to be deleted.
    $this->id = $id;
    // Call the parent buildForm method.
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   * @throws EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Load the record to get the image ID.
    $record = Database::getConnection()->select('matthew', 'm')
      ->fields('m', ['cats_image_id'])
      ->condition('id', $this->id)
      ->execute()
      ->fetchObject();

    if ($record && $record->cats_image_id) {
      // Load the file and delete it.
      $file = File::load($record->cats_image_id);
      $file?->delete();
    }

    // Delete the record from the database.
    Database::getConnection()->delete('matthew')
      ->condition('id', $this->id)
      ->execute();

    // Redirect to the latest cats page.
    $form_state->setRedirect('matthew.view');
  }

}
