<?php
namespace Drupal\matthew\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a form for editing a cat record.
 */
class EditCatForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edit_cat_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    // Load the cat record from the database.
    $record = Database::getConnection()->select('matthew', 'm')
      ->fields('m', ['id', 'cat_name', 'user_email', 'cats_image_id', 'created'])
      ->condition('id', $id)
      ->execute()
      ->fetchObject();

    // Add form fields.
    $form['id'] = [
      '#type' => 'hidden',
      '#value' => $record->id,
    ];
    $form['cat_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cat Name'),
      '#description' => $this->t('Minimum length is 2 characters and maximum length is 32 characters.'),
      '#default_value' => $record->cat_name,
      '#required' => TRUE,
      '#maxlength' => 32,
      '#ajax' => [
        'callback' => '::validateCatName',
        'event' => 'change',
      ],
    ];
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('User Email'),
      '#description' => $this->t('The email can contain only Latin letters, underscores, or hyphens.'),
      '#default_value' => $record->user_email,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::validateEmail',
        'event' => 'change',
      ],
    ];
    $form['image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Cat Image'),
      '#description' => $this->t('Allowed formats: jpeg, jpg, png. Maximum file size: 2 MB.'),
      '#default_value' => $record->cats_image_id ? [$record->cats_image_id] : NULL,
      '#required' => FALSE,
      '#upload_location' => 'public://cats',
      '#upload_validators' => [
        'file_validate_extensions' => ['jpeg jpg png'],
        'file_validate_size' => [2097152],
      ],
      '#ajax' => [
        'callback' => '::validateImage',
        'event' => 'change',
      ],
    ];
    $form['created'] = [
      '#type' => 'item',
      '#title' => $this->t('Date Added'),
      '#markup' => date('Y-m-d H:i:s', $record->created),
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Validates the input and adds AJAX commands to the response.
   */
  protected function addValidationResponse(AjaxResponse $response, string $message, string $selector, bool $is_valid): void {
    $response->addCommand(new MessageCommand($this->t($message), NULL, ['type' => $is_valid ? 'status' : 'error']));
    $response->addCommand(new CssCommand($selector, ['border' => $is_valid ? '1px solid green' : '1px solid red']));
  }

  /**
   * AJAX callback to validate the cat name.
   */
  public function validateCatName(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $cat_name = $form_state->getValue('cat_name');

    if (empty($cat_name)) {
      $this->addValidationResponse($response, 'The name is required.', '#edit-cat-name', FALSE);
    } elseif (mb_strlen($cat_name, 'UTF-8') < 2 || mb_strlen($cat_name, 'UTF-8') > 32) {
      $this->addValidationResponse($response, 'The name must be between 2 and 32 characters long.', '#edit-cat-name', FALSE);
    } else {
      $this->addValidationResponse($response, 'The name is valid.', '#edit-cat-name', TRUE);
    }

    return $response;
  }

  /**
   * AJAX callback to validate the email.
   */
  public function validateEmail(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $email = $form_state->getValue('email');
    $email_pattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';

    if (empty($email)) {
      $this->addValidationResponse($response, 'The email is required.', '#edit-email', FALSE);
    } elseif (!preg_match($email_pattern, $email)) {
      $this->addValidationResponse($response, 'The email is not valid.', '#edit-email', FALSE);
    } else {
      $this->addValidationResponse($response, 'The email is valid.', '#edit-email', TRUE);
    }

    return $response;
  }

  /**
   * AJAX callback to validate the image.
   */
  public function validateImage(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $image_fid = $form_state->getValue('image')[0];

    if ($image_fid) {
      $file = File::load($image_fid);
      $file_extension = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
      $allowed_extensions = ['jpeg', 'jpg', 'png'];
      $file_size = $file->getSize();

      if (!in_array($file_extension, $allowed_extensions)) {
        $this->addValidationResponse($response, 'Invalid file type. Allowed formats: jpeg, jpg, png.', '#edit-image', FALSE);
      } elseif ($file_size > 2097152) {
        $this->addValidationResponse($response, 'The file size exceeds 2 MB.', '#edit-image', FALSE);
      } else {
        $this->addValidationResponse($response, 'The image is valid.', '#edit-image', TRUE);
      }
    } else {
      $this->addValidationResponse($response, 'The image is required.', '#edit-image', FALSE);
    }

    return $response;
  }

  /**
   * AJAX callback to validate the form before submission.
   */
  public function validateForm(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();

    $cat_name = $form_state->getValue('cat_name');
    $email = $form_state->getValue('email');
    $image_fid = $form_state->getValue('image')[0];
    $email_pattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';

    if (trim($cat_name) === '') {
      $this->addValidationResponse($response, 'The name is required.', '#edit-cat-name', FALSE);
    } elseif (mb_strlen($cat_name, 'UTF-8') < 2 || mb_strlen($cat_name, 'UTF-8') > 32) {
      $this->addValidationResponse($response, 'The name must be between 2 and 32 characters long.', '#edit-cat-name', FALSE);
    }

    if (trim($email) === '') {
      $this->addValidationResponse($response, 'The email is required.', '#edit-email', FALSE);
    } elseif (!preg_match($email_pattern, $email)) {
      $this->addValidationResponse($response, 'The email is not valid.', '#edit-email', FALSE);
    }

    if (empty($image_fid)) {
      $this->addValidationResponse($response, 'The image is required.', '#edit-image', FALSE);
    } else {
      $file = File::load($image_fid);
      $file_extension = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
      $allowed_extensions = ['jpeg', 'jpg', 'png'];
      $file_size = $file->getSize();

      if (!in_array($file_extension, $allowed_extensions)) {
        $this->addValidationResponse($response, 'Invalid file type. Allowed formats: jpeg, jpg, png.', '#edit-image', FALSE);
      } elseif ($file_size > 2097152) {
        $this->addValidationResponse($response, 'The file size exceeds 2 MB.', '#edit-image', FALSE);
      }
    }

    return $response;
  }

  /**
   * Checks if the form has any errors.
   */
  protected function hasAnyErrors(FormStateInterface $form_state): bool {
    return count($form_state->getErrors()) > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();

    $this->validateForm($form, $form_state);
    if ($this->hasAnyErrors($form_state)) {
      foreach ($form_state->getErrors() as $error) {
        $response->addCommand(new MessageCommand($error, NULL, ['type' => 'error']));
      }
      return $response;
    }

    // Get the old file ID from the database.
    $old_file_id = Database::getConnection()->select('matthew', 'm')
      ->fields('m', ['cats_image_id'])
      ->condition('id', $form_state->getValue('id'))
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
        if ($old_file) {
          $old_file->delete();
        }
      }
    }

    // Update the database.
    Database::getConnection()->update('matthew')
      ->fields($fields)
      ->condition('id', $form_state->getValue('id'))
      ->execute();

    // Redirect to the latest cats page.
    $url = Url::fromRoute('matthew.view')->toString();
    $response = new RedirectResponse($url);
    $response->send();
  }
}
