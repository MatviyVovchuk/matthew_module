<?php

namespace Drupal\matthew\Form\Admin;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\matthew\Service\CatService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to confirm the deletion of a cat record.
 */
class ConfirmDeleteCatPage extends ConfirmFormBase {

  /**
   * The ID of the cat record.
   *
   * @var int
   */
  protected int $id;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;


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
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(CatService $catService, LoggerInterface $logger) {
    $this->catService = $catService;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('matthew.cat_service'),
      $container->get('logger.channel.default')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'confirm_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): string {
    return $this->t('Are you sure you want to delete this cat record?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    // Return the URL to cancel and go back to the cat view page.
    return new Url('matthew.user_cats_view');
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
    // Return the text for the confirmation button.
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText(): string {
    // Return the text for the cancel button.
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL): array {
    // Set the ID of the cat record to be deleted.
    $this->id = $id;

    // Retrieve the cat record from the database.
    $record = $this->catService->getCatById($id);

    // Add a title to the form.
    $form['cats_to_delete_title'] = [
      '#type' => 'markup',
      '#markup' => '<h3>' . $this->t('The following cat will be deleted:') . '</h3>',
    ];

    // Display the cat ID, name, and email.
    $form['cat_info'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Cat ID'),
        $this->t('Name'),
        $this->t('Email'),
      ],
      '#rows' => [
        [
          'id' => $record->id,
          'cat_name' => $record->cat_name,
          'user_email' => $record->user_email,
        ],
      ],
      '#empty' => $this->t('No cat found for deletion.'),
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
      // Delete the image and record from the database.
      $this->catService->deleteCatById($this->id);

      // Display a status message and redirect to the cats list.
      $this->messenger()->addStatus($this->t('Cat and their associated image have been deleted.'));

      // Set redirect url.
      $form_state->setRedirectUrl($this->getCancelUrl());
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to delete cat record with ID @id. Error: @message', [
        '@id' => $this->id,
        '@message' => $e->getMessage(),
      ]);
      $this->messenger()->addError($this->t('Failed to delete cat the record. Please try again later.'));
    }
  }

}
