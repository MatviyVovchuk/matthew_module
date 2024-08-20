<?php

namespace Drupal\matthew\Form;

use Drupal;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;

/**
 * Class CatsListForm.
 *
 * Provides a list form with options to edit or delete cat records.
 */
class CatsListForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'cats_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Define the table headers.
    $header = [
      'cat_name' => ['data' => $this->t('Name'), 'field' => 'cat_name'],
      'user_email' => ['data' => $this->t('Email'), 'field' => 'user_email'],
      'created' => ['data' => $this->t('Date Added'), 'field' => 'created'],
      'image' => $this->t('Image'),
      'operations' => $this->t('Operations'),
    ];

    // Get the database connection.
    $connection = Database::getConnection();

    // Build the query to retrieve cat records.
    $query = $connection->select('matthew', 'm')
      ->fields('m', ['id', 'cat_name', 'user_email', 'created', 'cats_image_id'])
      ->orderBy('created', 'DESC');

    // Execute the query and fetch results.
    $results = $query->execute()->fetchAll();

    // Initialize rows array to store the cat records.
    $rows = [];
    // Get the file URL generator service.
    $file_url_generator = Drupal::service('file_url_generator');
    // Get the date formatter service.
    $date_formatter = Drupal::service('date.formatter');

    foreach ($results as $row) {
      // Load the file entity for the cat image.
      $file = File::load($row->cats_image_id);
      // Generate the absolute URL for the cat image.
      $image_url = $file ? $file_url_generator->generateAbsoluteString($file->getFileUri()) : '';

      // Build the row data.
      $rows[$row->id] = [
        'select' => [
          'data' => [
            '#type' => 'checkbox',
            '#attributes' => ['class' => ['select-cat']],
          ],
        ],
        'cat_name' => $row->cat_name,
        'user_email' => $row->user_email,
        'created' => $date_formatter->format($row->created, 'custom', 'd/m/Y H:i:s'),
        'image' => $image_url ? [
          'data' => [
            '#theme' => 'image',
            '#uri' => $image_url,
            '#width' => 100,
            '#height' => 100,
          ],
        ] : $this->t('No image'),
        'operations' => [
          'data' => [
            '#type' => 'operations',
            '#links' => [
              'edit' => [
                'title' => $this->t('Edit'),
                'url' => Url::fromRoute('matthew.edit_cat', ['id' => $row->id]),
              ],
              'delete' => [
                'title' => $this->t('Delete'),
                'url' => Url::fromRoute('matthew.delete_cat', ['id' => $row->id]),
              ],
            ],
          ],
        ],
      ];
    }

    // Define the tableselect element.
    $form['cats_table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $rows,
      '#empty' => $this->t('No cats found.'),
    ];

    // Add action buttons.
    $form['actions'] = [
      '#type' => 'actions',
      'delete_selected' => [
        '#type' => 'submit',
        '#value' => $this->t('Delete selected items'),
        '#attributes' => [
          'class' => ['button', 'button--action', 'button--danger', 'delete-button'],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Get the selected items.
    $selected = array_filter($form_state->getValue('cats_table'));

    // Check if any items are selected.
    if (empty($selected)) {
      $this->messenger()->addWarning($this->t('No items selected for deletion.'));
      return;
    }

    // Redirect to the bulk delete confirmation page with selected IDs.
    $form_state->setRedirect('matthew.delete_cat_bulk', [
      'ids' => implode(',', array_keys($selected))
    ]);
  }
}
