<?php

namespace Drupal\matthew\Form;

use Drupal;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a form for editing a cat record.
 */
class EditCatForm extends MatthewCatsForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'edit_cat_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Add form fields.
    $form = parent::buildForm($form, $form_state);

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    // Fetch the cat ID from the route parameters
    $route_match = Drupal::routeMatch();
    $route_parameters = $route_match->getParameters();
    $id = $route_parameters->get('id');

    // Check if $id is available
    if (!empty($id)) {
      // Load the cat record from the database.
      $record = Database::getConnection()->select('matthew', 'm')
        ->fields('m')
        ->condition('id', $id)
        ->execute()
        ->fetchAssoc();

      // Set default values for form elements
      if ($record) {
        $form['cat_name']['#default_value'] = $record['cat_name'];
        $form['email']['#default_value'] = $record['user_email'];

        // If there is a cats_image_id, load the file entity and set it as the default value
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
   * @throws EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();

    $this->validateForm($form, $form_state);
    if (count($form_state->getErrors()) > 0) {
      foreach ($form_state->getErrors() as $error) {
        $response->addCommand(new MessageCommand($error, NULL, ['type' => 'error']));
      }
      return $response;
    }

    // Fetch the cat ID from the route parameters
    $route_match = Drupal::routeMatch();
    $route_parameters = $route_match->getParameters();
    $id = $route_parameters->get('id');

    // Get the old file ID from the database.
    $old_file_id = Database::getConnection()->select('matthew', 'm')
      ->fields('m', ['cats_image_id'])
      ->condition('id', $id)
      ->execute()
      ->fetchField();

    // Save the cat record to the database.
    $fields = [
      'cat_name' => $form_state->getValue('cat_name'),
      'user_email' => $form_state->getValue('email'),
    ];

    // Handle the file upload.
    if ($file_id = $form_state->getValue('image')[0]) {
      // Load the new file.
      $file = File::load($file_id);
      $file->setPermanent();
      $file->save();
      $fields['cats_image_id'] = $file_id;

      // Delete the old file if it exists and is different from the new file.
      if ($old_file_id && $old_file_id != $file_id) {
        $old_file = File::load($old_file_id);
        $old_file?->delete();
      }
    }

    // Update the cat record in the database.
    Database::getConnection()->update('matthew')
      ->fields($fields)
      ->condition('id', $id)
      ->execute();

    // Redirect to the latest cats page.
    $url = Url::fromRoute('matthew.view')->toString();
    $response = new RedirectResponse($url);
    $response->send();
    return new AjaxResponse();
  }

}
