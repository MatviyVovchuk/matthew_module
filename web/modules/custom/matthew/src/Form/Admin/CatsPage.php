<?php

namespace Drupal\matthew\Form\Admin;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\matthew\Service\CatService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list form with options to edit or delete cat records.
 */
class CatsPage extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The file URL generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The cat service instance.
   *
   * @var \Drupal\matthew\Service\CatService
   */
  protected $catService;

  /**
   * Constructs a new object.
   *
   * @param \Drupal\matthew\Service\CatService $catService
   *   The cat service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(CatService $catService, Connection $database, FileUrlGeneratorInterface $file_url_generator, DateFormatterInterface $date_formatter, EntityTypeManagerInterface $entity_type_manager) {
    $this->catService = $catService;
    $this->database = $database;
    $this->fileUrlGenerator = $file_url_generator;
    $this->dateFormatter = $date_formatter;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('matthew.cat_service'),
      $container->get('database'),
      $container->get('file_url_generator'),
      $container->get('date.formatter'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'admin_cats_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Define the table headers.
    $header = [
      'cat_name' => [
        'data' => $this->t('Name'),
        'field' => 'cat_name',
      ],
      'user_email' => [
        'data' => $this->t('Email'),
        'field' => 'user_email',
      ],
      'created' => [
        'data' => $this->t('Date Added'),
        'field' => 'created',
      ],
      'image' => $this->t('Image'),
      'operations' => $this->t('Operations'),
    ];

    // Build the query to retrieve cat records.
    $results = $this->catService->getCats();

    // Initialize rows array to store the cat records.
    $rows = [];

    foreach ($results as $record) {
      // Load the file entity for the cat image.
      $file = $this->entityTypeManager->getStorage('file')->load($record->cats_image_id);
      // Generate the absolute URL for the cat image.
      $image_url = $file ? $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri()) : '';

      // Build the row data.
      $rows[$record->id] = [
        'select' => [
          'data' => [
            '#type' => 'checkbox',
            '#attributes' => [
              'class' => [
                'select-cat',
              ],
            ],
          ],
        ],
        'cat_name' => $record->cat_name,
        'user_email' => $record->user_email,
        'created' => $this->dateFormatter->format($record->created, 'custom', 'd/m/Y H:i:s'),
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
                'url' => Url::fromRoute('matthew.edit_cat', ['id' => $record->id]),
              ],
              'delete' => [
                'title' => $this->t('Delete'),
                'url' => Url::fromRoute('matthew.delete_cat', ['id' => $record->id]),
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
      'ids' => implode(',', array_keys($selected)),
    ]);
  }

}
