<?php

namespace Drupal\matthew\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;
use Drupal\Core\Link;
use Drupal\Core\Url;

class CatsListForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cats_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
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

    // Build the query.
    $query = $connection->select('matthew', 'm')
      ->fields('m', ['id', 'cat_name', 'user_email', 'created', 'cats_image_id'])
      ->orderBy('created', 'DESC');

    // Execute the query.
    $results = $query->execute()->fetchAll();

    $rows = [];
    $file_url_generator = \Drupal::service('file_url_generator');
    $date_formatter = \Drupal::service('date.formatter');

    foreach ($results as $row) {
      $image_url = '';
      if ($row->cats_image_id) {
        $file = File::load($row->cats_image_id);
        if ($file) {
          $image_url = $file ? $file_url_generator->generateAbsoluteString($file->getFileUri()) : '';
        }
      }

      $edit_link = Link::fromTextAndUrl($this->t('Edit'), Url::fromRoute('matthew.edit_cat', ['id' => $row->id]))->toString();
      $delete_link = Link::fromTextAndUrl($this->t('Delete'), Url::fromRoute('matthew.delete_cat', ['id' => $row->id]))->toString();

      $rows[$row->id] = [
        'cat_name' => $row->cat_name,
        'user_email' => $row->user_email,
        'created' => $date_formatter->format($row->created, 'custom', 'd/m/Y H:i:s'),
        'image' => $image_url ? ['data' => ['#theme' => 'image', '#uri' => $image_url, '#width' => 100, '#height' => 100]] : $this->t('No image'),
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

    $form['cats_table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $rows,
      '#empty' => $this->t('No cats found.'),
    ];

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
    public function submitForm(array &$form, FormStateInterface $form_state) {
      $selected = array_filter($form_state->getValue('cats_table'));
      if (empty($selected)) {
        $this->messenger()->addWarning($this->t('No items selected for deletion.'));
        return;
      }

      $form_state->setRedirect('matthew.delete_cat_bulk', ['ids' => implode(',', array_keys($selected))]);
    }
  }
