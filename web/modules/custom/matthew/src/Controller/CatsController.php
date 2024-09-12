<?php

namespace Drupal\matthew\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\matthew\Service\CatService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the Cats.
 */
class CatsController extends ControllerBase {

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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\matthew\Service\CatService $catService
   *   The cat service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   */
  public function __construct(FormBuilderInterface $form_builder, CatService $catService, DateFormatterInterface $date_formatter, EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user) {
    $this->formBuilder = $form_builder;
    $this->catService = $catService;
    $this->dateFormatter = $date_formatter;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): CatsController|static {
    return new static(
      $container->get('form_builder'),
      $container->get('matthew.cat_service'),
      $container->get('date.formatter'),
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * Returns the add form for the Cat.
   *
   * @return array
   *   Render array for the Cat add form.
   */
  public function add(): array {
    $form = $this->formBuilder->getForm('Drupal\matthew\Form\AddCatForm');

    return [
      '#theme' => 'cats',
      '#content' => [
        'title' => $this->t('Hello! You can add here a photo of your cat.'),
        'form' => $form,
      ],
    ];
  }

  /**
   * Returns the content for the Cats page.
   *
   * @return array
   *   Render array for the Cats page content.
   */
  public function userCatsView(): array {
    // Get the list of cat records.
    $results = $this->catService->getCats();

    // Process each record: format image, date, and URLs.
    foreach ($results as $record) {
      $image_render_array = $this->catService->getImageFileRenderArray($record->cats_image_id, 'matthew_cats');
      $record->image = !empty($image_render_array) ? $image_render_array : '';
      $record->created = $this->dateFormatter->format($record->created, 'matthew_date_format');
      $record->edit_url = Url::fromRoute('matthew.edit_cat', ['id' => $record->id])->toString();
      $record->delete_url = Url::fromRoute('matthew.delete_cat', ['id' => $record->id])->toString();
    }

    // Check user permissions for admin-specific actions.
    $is_admin = $this->currentUser->hasPermission('administer site configuration');

    // Build the render array for the cats table with caching settings.
    $form['cats'] = [
      '#theme' => 'cats-view',
      '#rows' => $results,
      '#is_admin' => $is_admin,
      '#attached' => ['library' => ['matthew/styles']],
      '#cache' => [
        'tags' => ['view'],
        'contexts' => ['user'],
        'max-age' => 0,
      ],
    ];

    return $form;
  }

}
