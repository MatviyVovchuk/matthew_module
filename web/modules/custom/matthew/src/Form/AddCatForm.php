<?php

namespace Drupal\matthew\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\matthew\Service\CatService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to add a cat's name and email.
 *
 * This form allows users to input and submit their cat's name and their email.
 * The cat name must be between 2 and 32 characters long.
 * The email must match a specific pattern.
 * Upon submission, a message will be displayed confirming the input values
 * are valid.
 */
class AddCatForm extends FormBase {

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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\matthew\Service\CatService $catService
   *   The cat service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(LoggerInterface $logger, CatService $catService, EntityTypeManagerInterface $entity_type_manager, FormBuilderInterface $form_builder) {
    $this->logger = $logger;
    $this->catService = $catService;
    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
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
  public function getFormId(): string {
    return 'add_cats_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Add form fields.
    $form['#id'] = $this->getFormId();

    $form['cat_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your cat’s name:'),
      '#description' => $this->t('Minimum length is 2 characters and maximum length is 32 characters.'),
      '#required' => TRUE,
      '#maxlength' => 32,
      '#ajax' => [
        'callback' => '::validateCatName',
        'event' => 'change',
      ],
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Your email:'),
      '#description' => $this->t('The email can contain only Latin letters, underscores, or hyphens.'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::validateEmail',
        'event' => 'change',
      ],
    ];

    $form['image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload an image of your cat:'),
      '#description' => $this->t('Allowed formats: jpeg, jpg, png. Maximum file size: 2 MB.'),
      '#required' => TRUE,
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
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   *   The AJAX response.
   * @param string $message
   *   The validation message.
   * @param string $selector
   *   The CSS selector.
   * @param bool $is_valid
   *   The validation status.
   */
  protected function addValidationResponse(
    AjaxResponse $response,
    string $message,
    string $selector,
    bool $is_valid,
  ): void {
    $response->addCommand(new MessageCommand($this->t('@message', ['@message' => $message]),
      NULL,
      ['type' => $is_valid ? 'status' : 'error']));
    $response->addCommand(new CssCommand($selector,
      ['border' => $is_valid ? '1px solid green' : '1px solid red']));
  }

  /**
   * AJAX callback to validate the cat name.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state interface.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function validateCatName(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $cat_name = $form_state->getValue('cat_name');

    if (empty($cat_name)) {
      $this->addValidationResponse($response, $this->t('The name is required.'),
        '#edit-cat-name',
        FALSE);
    }
    elseif (mb_strlen($cat_name, 'UTF-8') < 2 || mb_strlen($cat_name, 'UTF-8') > 32) {
      $this->addValidationResponse($response, $this->t('The name must be between 2 and 32 characters long.'),
        '#edit-cat-name',
        FALSE);
    }
    else {
      $this->addValidationResponse($response, $this->t('The name is valid.'),
        '#edit-cat-name',
        TRUE);
    }

    return $response;
  }

  /**
   * AJAX callback to validate the email.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state interface.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function validateEmail(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $email = $form_state->getValue('email');
    $email_pattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';

    if (empty($email)) {
      $this->addValidationResponse($response, $this->t('The email is required.'),
        '#edit-email',
        FALSE);
    }
    elseif (!preg_match($email_pattern, $email)) {
      $this->addValidationResponse($response, $this->t('The email is not valid.'),
        '#edit-email',
        FALSE);
    }
    else {
      $this->addValidationResponse($response, $this->t('The email is valid.'),
        '#edit-email',
        TRUE);
    }

    return $response;
  }

  /**
   * AJAX callback to validate the image.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state interface.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function validateImage(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $image_fid = $form_state->getValue('image')[0];

    if ($image_fid) {
      $file = $this->entityTypeManager->getStorage('file')->load($image_fid);

      if ($file) {
        $file_extension = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
        $allowed_extensions = ['jpeg', 'jpg', 'png'];
        $file_size = $file->getSize();

        if (!in_array($file_extension, $allowed_extensions)) {
          $this->addValidationResponse($response,
            $this->t('Invalid file type. Allowed formats: jpeg, jpg, png.'),
            '#edit-image',
            FALSE);
        }
        elseif ($file_size > 2097152) {
          $this->addValidationResponse($response,
            $this->t('The file size exceeds 2 MB.'),
            '#edit-image',
            FALSE);
        }
        else {
          $this->addValidationResponse($response,
            $this->t('The image is valid.'),
            '#edit-image',
            TRUE);
        }
      }
      else {
        $this->addValidationResponse($response,
          $this->t('The image is required.'),
          '#edit-image',
          FALSE);
      }
    }

    return $response;
  }

  /**
   * Validate the form before submission.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state interface.
   *
   * @return void
   *   Return nothing.
   */
  public function validateForm(array &$form, FormStateInterface $form_state):void {
    $this->validateCatName($form, $form_state);
    $this->validateEmail($form, $form_state);
    $this->validateImage($form, $form_state);
  }

  /**
   * AJAX form submission handler.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state interface.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function submitAjaxForm(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();

    $fields = [
      'cat_name' => $form_state->getValue('cat_name'),
      'user_email' => $form_state->getValue('email'),
      'cats_image_id' => $form_state->getValue('image')[0],
    ];

    try {
      // Save the data to the database.
      $this->catService->saveCat($fields);

      // Display success message.
      $response->addCommand(new MessageCommand(
        $this->t('Your cat %cat_name has been added with your email %email and the image is uploaded successfully.', [
          '%cat_name' => $fields['cat_name'],
          '%email' => $fields['email'],
        ]),
        NULL,
        ['type' => 'status']));

      // Reset form state and rebuild the form.
      $form_state->setRebuild();
      $form_state->setValues([]);
      $form_state->setUserInput([]);

      // Use the FormBuilder service to rebuild the form.
      $rebuilt_form = $this->formBuilder->rebuildForm($this->getFormId(), $form_state, $form);

      // Replace the old form with the new one.
      $response->addCommand(new ReplaceCommand('#' . $this->getFormId(), $rebuilt_form));
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to add cat record. Error: @message', [
        '@message' => $e->getMessage(),
      ]);
      $this->messenger()->addError($this->t('Failed to update the record. Please try again later.'));
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This function can be left empty as we are handling submission via AJAX.
  }

}
