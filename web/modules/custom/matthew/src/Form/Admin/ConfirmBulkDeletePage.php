<?php

namespace Drupal\matthew\Form\Admin;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Url;
use Drupal\matthew\Service\CatService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfirmBulkDeletePage.
 *
 * Provides a form for confirming the bulk deletion of cat records.
 */
class ConfirmBulkDeletePage extends ConfirmFormBase {

  use MessengerTrait;

  /**
   * The IDs of the cats to be deleted.
   *
   * @var array
   */
  protected array $ids;

  /**
   * The cat service instance.
   *
   * @var \Drupal\matthew\Service\CatService
   */
  protected $catService;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'confirm_delete_form_bulk';
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
    return new Url('matthew.admin_cats_page');
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
   * Constructs a new object.
   *
   * @param \Drupal\matthew\Service\CatService $catService
   *   The cat service.
   */
  public function __construct(CatService $catService,) {
    $this->catService = $catService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('matthew.cat_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $ids = NULL): array {
    // Store the IDs of the cats to be deleted.
    $this->ids = !empty($ids) ? explode(',', $ids) : [];

    // Retrieve cat records from the database.
    $results = $this->catService->getCatsByIds($this->ids);

    // Prepare table rows with cat records.
    $rows = [];

    foreach ($results as $record) {
      // Build the row data.
      $rows[] = [
        'id' => $record->id,
        'cat_name' => $record->cat_name,
        'user_email' => $record->user_email,
      ];
    }

    // Add a title and table to the form.
    $form['cats_to_delete_title'] = [
      '#type' => 'markup',
      '#markup' => '<h3>' . $this->t('The following cats will be deleted:') . '</h3>',
    ];

    $form['cats_info'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Cat ID'),
        $this->t('Name'),
        $this->t('Email'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('No cats selected for deletion.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    try {
      // Delete images and records from the matthew table.
      $this->catService->deleteCatsByIds($this->ids);

      // Display a status message and redirect to the cats list.
      $this->messenger()->addStatus($this->t('@count cats and their associated images have been deleted.',
        ['@count' => count($this->ids)]
      ));

      // Set redirect url.
      $form_state->setRedirectUrl($this->getCancelUrl());
    }
    catch (\Exception $e) {
      \Drupal::logger('matthew')->error('Failed to delete cats records. Error: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
  }

}
