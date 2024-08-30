<?php

namespace Drupal\matthew\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the Cats.
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

}
