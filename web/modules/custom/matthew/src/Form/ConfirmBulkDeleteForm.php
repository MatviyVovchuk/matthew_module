<?php

namespace Drupal\matthew\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;

/**
 * Class ConfirmBulkDeleteForm.
 *
 * Provides a form for confirming the bulk deletion of cat records.
 */
class ConfirmBulkDeleteForm extends FormBase {

  /**
   * The IDs of the cats to be deleted.
   *
   * @var array
   */
  protected $ids;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'matthew_confirm_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $ids = NULL) {
    // Store the IDs of the cats to be deleted.
    $this->ids = !empty($ids) ? explode(',', $ids) : [];

    // Retrieve cat records from the database.
    $connection = Database::getConnection();
    $query = $connection->select('matthew', 'm')
      ->fields('m', ['id', 'cat_name', 'user_email', 'cats_image_id', 'created'])
      ->condition('id', $this->ids, 'IN')
      ->orderBy('created', 'DESC');
    $results = $query->execute()->fetchAll();

    // Prepare table rows with cat records.
    $rows = [];
    $file_url_generator = \Drupal::service('file_url_generator');
    $date_formatter = \Drupal::service('date.formatter');

    foreach ($results as $record) {
      $file = File::load($record->cats_image_id);
      $image_url = $file ? $file_url_generator->generateAbsoluteString($file->getFileUri()) : '';

      $rows[] = [
        'id' => $record->id,
        'cat_name' => $record->cat_name,
        'user_email' => $record->user_email,
        'created' => $date_formatter->format($record->created, 'custom', 'd/m/Y H:i:s'),
        'image' => $image_url ? [
          'data' => [
            '#theme' => 'image',
            '#uri' => $image_url,
            '#alt' => $this->t('Cat image'),
            '#width' => 50,
            '#height' => 50
          ],
        ] : $this->t('No image'),
      ];
    }

    // Add a title and table to the form.
    $form['cats_to_delete_title'] = [
      '#type' => 'markup',
      '#markup' => '<h3>' . $this->t('The following cats will be deleted:') . '</h3>',
    ];

    $form['cats_table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Cat Id'),
        $this->t('Name'),
        $this->t('Email'),
        $this->t('Date Added'),
        $this->t('Image'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('No cats selected for deletion.'),
    ];

    // Add action buttons.
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
      '#attributes' => ['class' => ['button', 'delete-button']], // Set the desired classes here
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('matthew.cats_list'),
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $connection = Database::getConnection();

    // Get image IDs before deleting records.
    $query = $connection->select('matthew', 'm')
      ->fields('m', ['cats_image_id'])
      ->condition('id', $this->ids, 'IN');
    $image_ids = $query->execute()->fetchCol();

    // Delete records from the matthew table.
    $connection->delete('matthew')
      ->condition('id', $this->ids, 'IN')
      ->execute();

    // Delete associated images.
    foreach ($image_ids as $fid) {
      if ($fid) {
        $file = File::load($fid);
        if ($file) {
          $file->delete();
        }
      }
    }

    // Display a status message and redirect to the cats list.
    $this->messenger()->addStatus($this->t('@count cats and their associated images have been deleted.', ['@count' => count($this->ids)]));
    $form_state->setRedirect('matthew.cats_list');
  }
}
