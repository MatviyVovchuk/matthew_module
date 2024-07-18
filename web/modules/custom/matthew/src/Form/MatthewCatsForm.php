<?php

namespace Drupal\matthew\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\MessageCommand;

/**
 * Provides a form to add a cat's name.
 *
 * This form allows users to input and submit their cat's name. The name
 * must be between 2 and 32 characters long. Upon submission, a message
 * will be displayed confirming the cat's name has been added.
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
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add cat'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::validateForm',
      ],
    ];

    return $form;
  }

  /**
   * AJAX callback to validate the cat name.
   */
  public function validateForm(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $cat_name = $form_state->getValue('cat_name');

    if (mb_strlen($cat_name, 'UTF-8') < 2 || mb_strlen($cat_name, 'UTF-8') > 32) {
      $response->addCommand(new MessageCommand($this->t('The name must be between 2 and 32 characters long.'), NULL, ['type' => 'error']));
    }
    else {
      $response->addCommand(new MessageCommand($this->t('Cat named @name added successfully!', ['@name' => $cat_name])));
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This function can be used to handle non-AJAX form submissions if needed.
  }

}
