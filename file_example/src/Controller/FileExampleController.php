<?php

/**
 * @file
 * Contains \Drupal\file_example\Controller\FileExampleController.
 */

namespace Drupal\file_example\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for file example routes.
 */
class FileExampleController extends ControllerBase {

  /**
   * A simple controller method to explain what the file example is about.
   */
  public function description() {

    // Put the link into the content.
    $build = array(
      '#markup' => $this->t('The file example module provides a form and code to demonstrate the Drupal 8 file api. Experiment with the form, and then look at the submit handlers in the code to understand the file api.'),
    );

    return $build;
  }

}
