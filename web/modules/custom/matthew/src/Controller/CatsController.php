<?php

namespace Drupal\matthew\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for the Cats page.
 */
class CatsController extends ControllerBase {

  /**
   * Returns the content for the Cats page.
   *
   * @return array
   *   Render array for the Cats page content.
   */
  public function content() {
    $content = [];

    $content['title'] = 'Matthew cats';
    $content['content'] = 'Hello! You can add here a photo of your cat.';
    return [
      '#theme' => 'cats',
      '#content' => $content,
    ];
  }
}
