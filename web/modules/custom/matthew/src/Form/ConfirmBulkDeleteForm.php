<?php

namespace Drupal\matthew\Form;

use Drupal;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\Core\Messenger\MessengerTrait;

/**
 * Class ConfirmBulkDeleteForm.
 *
 * Provides a form for confirming the bulk deletion of cat records.
 */
class ConfirmBulkDeleteForm extends ConfirmFormBase {

  use MessengerTrait;

  /**
   * The IDs of the cats to be deleted.
   *
   * @var array
   */
  protected array $ids;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'matthew_confirm_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): string {
    return $this->t('Are you sure you want to delete the selected cats?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('matthew.cats_list');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return $this->t('This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): string {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText(): string {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $ids = NULL): array {
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
    // Get the file URL generator service.
    $file_url_generator = Drupal::service('file_url_generator');
    // Get the date formatter service.
    $date_formatter = Drupal::service('date.formatter');

    // Iterate through the query results to build the table rows.
    foreach ($results as $record) {
      // Load the file entity for the cat image.
      $file = File::load($record->cats_image_id);
      // Generate the absolute URL for the cat image.
      $image_url = $file ? $file_url_generator->generateAbsoluteString($file->getFileUri()) : '';

      // Build the row data.
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

    // Add description and question to the form.
    $form['description'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->getDescription() . '</p>',
    ];
    $form['question'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->getQuestion() . '</p>',
    ];

    // Add action buttons.
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->getConfirmText(),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->getCancelText(),
      '#url' => $this->getCancelUrl(),
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   * @throws EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Get the database connection.
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
        $file?->delete();
      }
    }

    // Display a status message and redirect to the cats list.
    $this->messenger()->addStatus($this->t('@count cats and their associated images have been deleted.',
      ['@count' => count($this->ids)]
    ));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
