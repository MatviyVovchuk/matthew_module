<?php

namespace Drupal\matthew\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;
use Drupal\Core\Link;
use Drupal\Core\Url;

class CatsViewForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cats_view_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Fetch the latest cat records from the database.
    $query = Database::getConnection()->select('matthew', 'm')
      ->fields('m', ['id', 'cat_name', 'user_email', 'cats_image_id', 'created'])
      ->orderBy('created', 'DESC')
      ->execute();

    $rows = [];
    $file_url_generator = \Drupal::service('file_url_generator');
    $date_formatter = \Drupal::service('date.formatter');

    foreach ($query as $record) {
      $file = File::load($record->cats_image_id);
      $image_url = $file ? $file_url_generator->generateAbsoluteString($file->getFileUri()) : '';

      $rows[] = [
        'id' => $record->id,
        'cat_name' => $record->cat_name,
        'user_email' => $record->user_email,
        'image' => $image_url ? [
          'data' => [
            '#theme' => 'image',
            '#uri' => $image_url,
            '#alt' => $this->t('Cat image'),
          ],
        ] : '',
        'created' => $date_formatter->format($record->created, 'custom', 'd/m/Y H:i:s'),
        'edit_url' => Url::fromRoute('matthew.edit_cat', ['id' => $record->id])->toString(),
        'delete_url' => Url::fromRoute('matthew.delete_cat', ['id' => $record->id])->toString(),
      ];
    }

    $is_admin = $this->currentUser()->hasPermission('administer site configuration');

    $form['cats_table'] = [
      '#theme' => 'cats-view',
      '#rows' => $rows,
      '#is_admin' => $is_admin,
      '#attached' => [
        'library' => [
          'matthew/styles',
        ],
      ],
      '#cache' => [
        'tags' => ['view'],
        'contexts' => ['user'],
        'max-age' => 0,
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No actions required on form submission.
  }
}
