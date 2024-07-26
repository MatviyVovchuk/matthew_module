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
    // Get the form builder service.
    $form_builder = \Drupal::service('form_builder');

    // Build the cats view form.
    $form = $form_builder->getForm('\Drupal\matthew\Form\CatsViewForm');

    // Return the form.
    return $form;
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
    $form = $form_builder->getForm('\Drupal\matthew\Form\ConfirmDeleteCatForm', $id);

    // Return the form.
    return $form;
  }

  /**
   * Returns a form for delete bulk confirmation a cat record.
   *
   * @param array $ids
   *   The IDs of the cats record.
   */
  public function deleteBulkCats($ids) {
    // Get the form builder service.
    $form_builder = \Drupal::service('form_builder');

    // Build the confirmation form.
    $form = $form_builder->getForm('\Drupal\matthew\Form\ConfirmBulkDeleteForm', $ids);

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
    // Get the form builder service.
    $form_builder = \Drupal::service('form_builder');

    // Build the cats list form.
    $form = $form_builder->getForm('\Drupal\matthew\Form\CatsListForm');

    // Return the form.
    return $form;
  }
}
