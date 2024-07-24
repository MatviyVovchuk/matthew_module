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
   * Edit a cat record.
   *
   * @param int $id
   *   The ID of the cat record.
   */
  public function editCat($id) {
    // For now just a message.
    return [
      '#markup' => $this->t('Edit cat record with ID @id', ['@id' => $id]),
    ];
  }

  /**
   * Redirect to the delete confirmation form.
   *
   * @param int $id
   *   The ID of the cat record.
   */
  public function deleteCat($id) {
    // Redirect to the confirmation form.
    $url = Url::fromRoute('matthew.delete_cat_confirm', ['id' => $id])->toString();
    return new RedirectResponse($url);
  }
}
