<?php

/**
 * @file
 * Contains \Drupal\file_example\Form\EmailExampleGetFormPage.
 */

namespace Drupal\file_example\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * File test form class.
 *
 * @ingroup file_example
 */
class FileExampleReadWriteForm extends FormBase {

  /**
   * Constructs a new FileExampleReadWriteForm page.
   *
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    //return new static($container->get('plugin.manager.mail'));
    return new static();
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormID() {
    return 'file_example_readwrite';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['intro'] = array(
      '#markup' => t('This will be the File Example Read/Write form'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();

  }
}
