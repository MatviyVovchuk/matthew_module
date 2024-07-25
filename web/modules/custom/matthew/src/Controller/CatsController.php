<?php

namespace Drupal\matthew\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Link;

/**
 * Controller for the Cats page.
 */
class CatsController extends ControllerBase {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * Returns the content for the Cats page.
   *
   * @return array
   *   Render array for the Cats page content.
   */
  public function content() {
    $form = $this->formBuilder->getForm('Drupal\matthew\Form\MatthewCatsForm');

    return [
      '#theme' => 'cats',
      '#content' => [
        'title' => $this->t('Hello! You can add here a photo of your cat.'),
        'form' => $form,
      ],
    ];
  }

  /**
   * Returns a page with the latest cat records.
   */
  public function latestCatsPage() {
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

    return [
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
  }

  /**
   * Returns a form for editing a cat record.
   *
   * @param int $id
   *   The ID of the cat record.
   */
  public function editCat($id) {
    // Get the form builder service.
    $form_builder = \Drupal::service('form_builder');

    // Build the edit form.
    $form = $form_builder->getForm('\Drupal\matthew\Form\EditCatForm', $id);

    // Return the form.
    return $form;
  }

  /**
   * Returns a form for delete confirmation a cat record.
   *
   * @param int $id
   *   The ID of the cat record.
   */
  public function deleteCat($id) {
    // Get the form builder service.
    $form_builder = \Drupal::service('form_builder');

    // Build the confirmation form.
    $form = $form_builder->getForm('\Drupal\matthew\Form\DeleteCatForm', $id);

    // Return the form.
    return $form;
  }

  /**
   * Display a list of cats.
   *
   * @return array
   *   A render array.
   */
  public function list() {
    // Define the table headers.
    $header = [
      ['data' => $this->t('Name'), 'field' => 'cat_name'],
      ['data' => $this->t('Email'), 'field' => 'user_email'],
      ['data' => $this->t('Date Added'), 'field' => 'created'],
      ['data' => $this->t('Image')],
      ['data' => $this->t('Operations')],
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

      $rows[] = [
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

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No cats found.'),
    ];
  }
}
