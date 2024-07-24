<?php

namespace Drupal\matthew\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;

/**
 * Form to confirm the deletion of a cat record.
 */
class DeleteCatForm extends ConfirmFormBase {

  /**
   * The ID of the cat record.
   *
   * @var int
   */
  protected $id;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'delete_cat_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this cat record?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('matthew.view');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $this->id = $id;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Load the record to get the image ID.
    $record = Database::getConnection()->select('matthew', 'm')
      ->fields('m', ['cats_image_id'])
      ->condition('id', $this->id)
      ->execute()
      ->fetchObject();

    if ($record && $record->cats_image_id) {
      // Load the file and delete it.
      $file = File::load($record->cats_image_id);
      if ($file) {
        $file->delete();
      }
    }

    // Delete the record from the database.
    Database::getConnection()->delete('matthew')
      ->condition('id', $this->id)
      ->execute();

    // Redirect to the latest cats page.
    $form_state->setRedirect('matthew.view');
  }
}
