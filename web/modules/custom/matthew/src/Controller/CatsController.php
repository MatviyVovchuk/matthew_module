<?php

namespace Drupal\matthew\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the Cats page.
 */
class CatsController extends ControllerBase {

  /**
   * The form builder service.
   *
   * @var FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new object.
   *
   * @param FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): CatsController|static {
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
  public function content(): array {
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
  public function latestCatsPage(): array {
    // Build the cats view form.
    // Return the form.
    return $this->formBuilder->getForm('Drupal\matthew\Form\CatsViewForm');
  }

  /**
   * Returns a form for editing a cat record.
   *
   * @param int $id
   *   The ID of the cat record.
   */
  public function editCat(int $id): array {
    // Build the edit form.
    // Return the form.
    return $this->formBuilder->getForm('Drupal\matthew\Form\EditCatForm', $id);
  }

  /**
   * Returns a form for delete confirmation a cat record.
   *
   * @param int $id
   *   The ID of the cat record.
   */
  public function deleteCat(int $id): array {
    // Build the confirmation form.
    // Return the form.
    return $this->formBuilder->getForm('Drupal\matthew\Form\ConfirmDeleteCatForm', $id);
  }

  /**
   * Returns a form for delete bulk confirmation a cat record.
   *
   * @param array $ids
   *   The IDs of the cats record.
   */
  public function deleteBulkCats(array $ids): array {
    // Build the confirmation form.
    // Return the form.
    return $this->formBuilder->getForm('Drupal\matthew\Form\ConfirmBulkDeleteForm', $ids);
  }

  /**
   * Display a list of cats.
   *
   * @return array
   *   A render array.
   */
  public function list(): array {
    // Build the cats list form.
    // Return the form.
    return $this->formBuilder->getForm('Drupal\matthew\Form\CatsListForm');
  }
}
