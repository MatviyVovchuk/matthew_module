<?php

namespace Drupal\matthew\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MatthewCatsForm.
 */
class MatthewCatsForm extends FormBase {

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
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $cat_name = $form_state->getValue('cat_name');
    if (strlen($cat_name) < 2) {
      $form_state->setErrorByName('cat_name', $this->t('The cat name must be at least 2 characters long.'));
    }
    if (strlen($cat_name) > 32) {
      $form_state->setErrorByName('cat_name', $this->t('The cat name must be at most 32 characters long.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // For now just display a message.
    \Drupal::messenger()->addMessage($this->t('Cat named @name added successfully!', ['@name' => $form_state->getValue('cat_name')]));
  }
}
