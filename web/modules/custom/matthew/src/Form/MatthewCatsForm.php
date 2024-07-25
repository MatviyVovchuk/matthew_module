<?php

namespace Drupal\matthew\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\file\Entity\File;

/**
 * Provides a form to add a cat's name and email.
 *
 * This form allows users to input and submit their cat's name and their email.
 * The cat name must be between 2 and 32 characters long.
 * The email must match a specific pattern.
 * Upon submission, a message will be displayed confirming the input values are valid.
 */
class MatthewCatsForm extends FormBase {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'matthew_cats_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['cat_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your cat’s name:'),
      '#description' => $this->t('Minimum length is 2 characters and maximum length is 32 characters.'),
      '#required' => TRUE,
      '#maxlength' => 32,
      '#ajax' => [
        'callback' => '::validateCatName',
        'event' => 'keyup',
      ],
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Your email:'),
      '#description' => $this->t('The email can contain only Latin letters, underscores, or hyphens.'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::validateEmail',
        'event' => 'keyup',
      ],
    ];

    $form['image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload an image of your cat:'),
      '#description' => $this->t('Allowed formats: jpeg, jpg, png. Maximum file size: 2 MB.'),
      '#required' => TRUE,
      '#upload_validators' => [
        'file_validate_extensions' => ['jpeg jpg png'],
        'file_validate_size' => [2097152], // 2 MB in bytes.
      ],
      '#ajax' => [
        'callback' => '::validateImage',
        'event' => 'change',
      ],
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add cat'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::submitAjaxForm',
      ],
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
  public function validateCatName(array &$form, FormStateInterface $form_state): AjaxResponse {
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
    $valid = TRUE;

    if (trim($cat_name) === '') {
      $this->addValidationResponse($response, 'The name is required.', '#edit-cat-name', FALSE);
      $valid = FALSE;
    } elseif (mb_strlen($cat_name, 'UTF-8') < 2 || mb_strlen($cat_name, 'UTF-8') > 32) {
      $this->addValidationResponse($response, 'The name must be between 2 and 32 characters long.', '#edit-cat-name', FALSE);
    }

    if (trim($email) === '') {
      $this->addValidationResponse($response, 'The email is required.', '#edit-email', FALSE);
      $valid = FALSE;
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
        $valid = FALSE;
      } elseif ($file_size > 2097152) {
        $this->addValidationResponse($response, 'The file size exceeds 2 MB.', '#edit-image', FALSE);
        $valid = FALSE;
      } else {
        $this->addValidationResponse($response, 'The image is valid.', '#edit-image', TRUE);
      }
    }

    return $response;
  }

  /**
   * AJAX submit callback for the form.
   */
  public function submitAjaxForm(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();

    $this->validateForm($form, $form_state);

    if ($form_state->hasAnyErrors()) {
      return $response;
    }

    $cat_name = $form_state->getValue('cat_name');
    $email = $form_state->getValue('email');
    $image_fid = $form_state->getValue('image')[0];
    $file = File::load($image_fid);

    if ($file) {
      $file->setPermanent();
      $file->save();
    }

    $response->addCommand(new MessageCommand($this->t('Your cat %cat_name has been added with your email %email and the image is uploaded successfully.', [
      '%cat_name' => $cat_name,
      '%email' => $email,
    ]), NULL, ['type' => 'status']));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This function can be left empty as we are handling submission via AJAX.
  }

}
