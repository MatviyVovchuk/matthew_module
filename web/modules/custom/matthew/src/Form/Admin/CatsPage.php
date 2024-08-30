<?php

namespace Drupal\matthew\Form\Admin;

use Drupal\Core\Datetime\DateFormatterInterface;
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
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

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
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(CatService $catService, DateFormatterInterface $date_formatter) {
    $this->catService = $catService;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('matthew.cat_service'),
      $container->get('date.formatter')
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
      // Generate render array for the cat image using the new method.
      $image_render_array = $this->catService->getImageFileRenderArray($record->cats_image_id, 'matthew_admin_cats');

      // Build the row data.
      $rows[$record->id] = [
        'cat_name' => $record->cat_name,
        'user_email' => $record->user_email,
        'created' => $this->dateFormatter->format($record->created, 'matthew_date_format'),
        'image' => !empty($image_render_array) ? ['data' => $image_render_array] : $this->t('No image'),
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
