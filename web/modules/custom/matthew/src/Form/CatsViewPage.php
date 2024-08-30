<?php

namespace Drupal\matthew\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGenerator;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\matthew\Service\CatService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CatsViewPage.
 *
 * Provides a form to view a list of cats.
 */
class CatsViewPage extends FormBase {

  /**
   * The file URL generator service.
   *
   * @var \Drupal\Core\File\FileUrlGenerator
   */
  protected $fileUrlGenerator;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

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
   * Constructs a new CatsViewPage.
   *
   * @param \Drupal\matthew\Service\CatService $catService
   *   The cat service.
   * @param \Drupal\Core\File\FileUrlGenerator $file_url_generator
   *   The file URL generator service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(CatService $catService, FileUrlGenerator $file_url_generator, DateFormatterInterface $date_formatter, AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->catService = $catService;
    $this->fileUrlGenerator = $file_url_generator;
    $this->dateFormatter = $date_formatter;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('matthew.cat_service'),
      $container->get('file_url_generator'),
      $container->get('date.formatter'),
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'cats_view_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Build the query to retrieve cat records.
    $results = $this->catService->getCats();

    // Initialize rows array to store the cat records.
    $rows = [];

    // Iterate through the query results to build the table rows.
    foreach ($results as $record) {
      // Load the file entity for the cat image.
      $file = $this->entityTypeManager->getStorage('file')->load($record->cats_image_id);
      // Generate the absolute URL for the cat image.
      $image_url = $file ? $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri()) : '';

      // Build the row data.
      $rows[] = [
        'id' => $record->id,
        'cat_name' => $record->cat_name,
        'user_email' => $record->user_email,
        'image' => $image_url ? [
          'data' => [
            'uri' => $image_url,
            'alt' => $this->t('Cat image'),
          ],
        ] : '',
        'created' => $this->dateFormatter->format($record->created, 'custom', 'd/m/Y H:i:s'),
        'edit_url' => Url::fromRoute('matthew.edit_cat', ['id' => $record->id])->toString(),
        'delete_url' => Url::fromRoute('matthew.delete_cat', ['id' => $record->id])->toString(),
      ];
    }

    // Check if the current user has administrative permissions.
    $is_admin = $this->currentUser->hasPermission('administer site configuration');

    // Define the cats' element.
    $form['cats'] = [
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
