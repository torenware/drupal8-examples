<?php

/**
 * @file
 * Contains \Drupal\file_example\Controller\FileExampleController.
 */

namespace Drupal\file_example\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;

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
      '#markup' => $this->t('The file example module provides a form and code to demonstrate the Drupal 7 file api. Experiment with the form, and then look at the submit handlers in the code to understand the file api.'),
    );

    return $build;
  }
  
  /**
   * Session handler
   *
   * Parameters are variable, since we are using this to support a simulated file system built on sessions.
   *
   * @todo Figure out how routing works.  Symfony wants routes defined in advance, and won't just give
   *   you the path the way D7 did. So this is a research topic.  I think we'll need to keep it
   *   simple and use a query string (?path=), since the Drupal router really does NOT want to do this;
   *   see https://www.drupal.org/node/1827544.
   */
  public function accessSession() {
    //almost certainly wrong:
    return Response(NULL, 404);
  }

}
