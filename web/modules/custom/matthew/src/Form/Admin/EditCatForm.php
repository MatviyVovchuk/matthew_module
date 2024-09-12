<?php

namespace Drupal\matthew\Form\Admin;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\matthew\Form\AddCatForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for editing a cat record.
 */
class EditCatForm extends AddCatForm {

  /**
   * The ID of the cat record.
   *
   * @var int
   */
  protected int $id;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'edit_cat_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): EditCatForm|AddCatForm|static {
    return new static(
      $container->get('logger.channel.default'),
      $container->get('matthew.cat_service'),
      $container->get('entity_type.manager'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL): array {
    // Set the ID of the cat record to be edited.
    $this->id = $id;

    // Add form fields.
    $form = parent::buildForm($form, $form_state);

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    // Check if $id is available.
    if (!empty($this->id)) {
      // Load the cat record from the database.
      $record = Database::getConnection()->select('matthew', 'm')
        ->fields('m')
        ->condition('id', $this->id)
        ->execute()
        ->fetchAssoc();

      // Set default values for form elements.
      if ($record) {
        $form['cat_name']['#default_value'] = $record['cat_name'];
        $form['email']['#default_value'] = $record['user_email'];

        // If there is a cats_image_id,
        // load the file entity and set it as the default value.
        if (!empty($record['cats_image_id'])) {
          $file = File::load($record['cats_image_id']);
          if ($file) {
            $form['image']['#default_value'] = [$file->id()];
          }
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $fields = [
      'cat_name' => $form_state->getValue('cat_name'),
      'user_email' => $form_state->getValue('email'),
      'cats_image_id' => $form_state->getValue('image')[0],
    ];

    try {
      // Update cat record.
      $this->catService->updateCatById($this->id, $fields);

      // Display a status message and redirect to the cats list.
      $this->messenger()->addStatus($this->t('Cat information updated successfully.'));

      // Redirect to the cats page.
      $form_state->setRedirect('matthew.user_cats_view')->disableRedirect(FALSE)->setRebuild(FALSE);
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to update cat record with ID @id. Error: @message', [
        '@id' => $this->id,
        '@message' => $e->getMessage(),
      ]);
      $this->messenger()->addError($this->t('Failed to update the record. Please try again later.'));
    }
  }

}
