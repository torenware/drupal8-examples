<?php

/**
 * @file
 * Contains \Drupal\file_example\Form\EmailExampleGetFormPage.
 */

namespace Drupal\file_example\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileInterface;
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
   *
   * @todo Remove direct manipulation of the session.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (empty($_SESSION['file_example_default_file'])) {
      $_SESSION['file_example_default_file'] = 'session://drupal.txt';
    }
    $default_file = $_SESSION['file_example_default_file'];
    if (empty($_SESSION['file_example_default_directory'])) {
      $_SESSION['file_example_default_directory'] = 'session://directory1';
    }
    $default_directory = $_SESSION['file_example_default_directory'];

    $form['write_file'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Write to a file'),
    );
    $form['write_file']['write_contents'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Enter something you would like to write to a file') . ' ' . date('m'),
      '#default_value' => $this->t('Put some text here or just use this text'),
    );

    $form['write_file']['destination'] = array(
      '#type' => 'textfield',
      '#default_value' => $default_file,
      '#title' => $this->t('Optional: Enter the streamwrapper saying where it should be written'),
      '#description' => $this->t('This may be public://some_dir/test_file.txt or private://another_dir/some_file.txt, for example. If you include a directory, it must already exist. The default is "public://". Since this example supports session://, you can also use something like session://somefile.txt.'),
    );

    $form['write_file']['managed_submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Write managed file'),
      '#submit' => array('::handleManagedFile'),
    );
    $form['write_file']['unmanaged_submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Write unmanaged file'),
      '#submit' => array('::handleUnmanagedFile'),
    );
    $form['write_file']['unmanaged_php'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Unmanaged using PHP'),
      '#submit' => array('::handleUnmanagedPHP'),
    );

    $form['fileops'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Read from a file'),
    );
    $form['fileops']['fileops_file'] = array(
      '#type' => 'textfield',
      '#default_value' => $default_file,
      '#title' => $this->t('Enter the URI of a file'),
      '#description' => $this->t('This must be a stream-type description like public://some_file.txt or http://drupal.org or private://another_file.txt or (for this example) session://yet_another_file.txt.'),
    );
    $form['fileops']['read_submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Read the file and store it locally'),
      '#submit' => array('::handleFileRead'),
    );
    $form['fileops']['delete_submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Delete file'),
      '#submit' => array('::handleFileDelete'),
    );
    $form['fileops']['check_submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Check to see if file exists'),
      '#submit' => array('file_example_file_check_exists_submit'),
    );

    $form['directory'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Create or prepare a directory'),
    );

    $form['directory']['directory_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Directory to create/prepare/delete'),
      '#default_value' => $default_directory,
      '#description' => $this->t('This is a directory as in public://some/directory or private://another/dir.'),
    );
    $form['directory']['create_directory'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Create directory'),
      '#submit' => array('::handleDirectoryCreate'),
    );
    $form['directory']['delete_directory'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Delete directory'),
      '#submit' => array('::handleDirectoryDelete'),
    );
    $form['directory']['check_directory'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Check to see if directory exists'),
      '#submit' => array('::handleDirectoryExists'),
    );

    $form['debug'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Debugging'),
    );
    $form['debug']['show_raw_session'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Show raw $_SESSION contents'),
      '#submit' => array('::handleShowSession'),
    );

    return $form;
  }

/**
 * Submit handler to write a managed file.
 *
 * The key functions used here are:
 * - file_save_data(), which takes a buffer and saves it to a named file and
 *   also creates a tracking record in the database and returns a file object.
 *   In this function we use FILE_EXISTS_RENAME (the default) as the argument,
 *   which means that if there's an existing file, create a new non-colliding
 *   filename and use it.
 * - file_create_url(), which converts a URI in the form public://junk.txt or
 *   private://something/test.txt into a URL like
 *   http://example.com/sites/default/files/junk.txt.
 */
  public function handleManagedFile(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    $data = $form_values['write_contents'];
    $uri = !empty($form_values['destination']) ? $form_values['destination'] : NULL;
  
    // Managed operations work with a file object.
    $file_object = file_save_data($data, $uri, FILE_EXISTS_RENAME);
    if (!empty($file_object)) {
      $url = file_create_url($file_object->getFileUri());
      $_SESSION['file_example_default_file'] = $file_object->getFileUri();
      drupal_set_message(
       $this->t('Saved managed file: %file to destination %destination (accessible via !url, actual uri=<span id="uri">@uri</span>)',
          array(
            '%file' => print_r($file_object, TRUE),
            '%destination' => $uri, '@uri' => $file_object->getFileUri(),
            '!url' => $this->l(t('this URL'), $url),
          )
        )
      );
    }
    else {
      drupal_set_message(t('Failed to save the managed file'), 'error');
    }

  }

/**
 * Submit handler to write an unmanaged file.
 *
 * The key functions used here are:
 * - file_unmanaged_save_data(), which takes a buffer and saves it to a named
 *   file, but does not create any kind of tracking record in the database.
 *   This example uses FILE_EXISTS_REPLACE for the third argument, meaning
 *   that if there's an existing file at this location, it should be replaced.
 * - file_create_url(), which converts a URI in the form public://junk.txt or
 *   private://something/test.txt into a URL like
 *   http://example.com/sites/default/files/junk.txt.
 */
  public function handleUnmanagedFile(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    $data = $form_values['write_contents'];
    $destination = !empty($form_values['destination']) ? $form_values['destination'] : NULL;
  
    // With the unmanaged file we just get a filename back.
    $filename = file_unmanaged_save_data($data, $destination, FILE_EXISTS_REPLACE);
    if ($filename) {
      $url = file_create_url($filename);
      $_SESSION['file_example_default_file'] = $filename;
      drupal_set_message(
       $this->t('Saved file as %filename (accessible via !url, uri=<span id="uri">@uri</span>)',
          array(
            '%filename' => $filename,
            '@uri' => $filename,
            '!url' => $this->l(t('this URL'), $url),
          )
        )
      );
    }
    else {
      drupal_set_message(t('Failed to save the file'), 'error');
    }
  }


/**
 * Submit handler to write an unmanaged file using plain PHP functions.
 *
 * The key functions used here are:
 * - file_unmanaged_save_data(), which takes a buffer and saves it to a named
 *   file, but does not create any kind of tracking record in the database.
 * - file_create_url(), which converts a URI in the form public://junk.txt or
 *   private://something/test.txt into a URL like
 *   http://example.com/sites/default/files/junk.txt.
 * - drupal_tempnam() generates a temporary filename for use.
 */
  public function handleUnmanagedPHP(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    $data = $form_values['write_contents'];
    $destination = !empty($form_values['destination']) ? $form_values['destination'] : NULL;
  
    if (empty($destination)) {
      // If no destination has been provided, use a generated name.
      $destination = drupal_tempnam('public://', 'file');
    }
  
    // With all traditional PHP functions we can use the stream wrapper notation
    // for a file as well.
    $fp = fopen($destination, 'w');
  
    // To demonstrate the fact that everything is based on streams, we'll do
    // multiple 5-character writes to put this to the file. We could easily
    // (and far more conveniently) write it in a single statement with
    // fwrite($fp, $data).
    $length = strlen($data);
    $write_size = 5;
    for ($i = 0; $i < $length; $i += $write_size) {
      $result = fwrite($fp, substr($data, $i, $write_size));
      if ($result === FALSE) {
        drupal_set_message(t('Failed writing to the file %file', array('%file' => $destination)), 'error');
        fclose($fp);
        return;
      }
    }
    $url = file_create_url($destination);
    $_SESSION['file_example_default_file'] = $destination;
    drupal_set_message(
     $this->t('Saved file as %filename (accessible via !url, uri=<span id="uri">@uri</span>)',
        array(
          '%filename' => $destination,
          '@uri' => $destination,
          '!url' => $this->l(t('this URL'), $url),
        )
      )
    );

  }


/**
 * Submit handler for reading a stream wrapper.
 *
 * Drupal now has full support for PHP's stream wrappers, which means that
 * instead of the traditional use of all the file functions
 * ($fp = fopen("/tmp/some_file.txt");) far more sophisticated and generalized
 * (and extensible) things can be opened as if they were files. Drupal itself
 * provides the public:// and private:// schemes for handling public and
 * private files. PHP provides file:// (the default) and http://, so that a
 * URL can be read or written (as in a POST) as if it were a file. In addition,
 * new schemes can be provided for custom applications, as will be demonstrated
 * below.
 *
 * Here we take the stream wrapper provided in the form. We grab the
 * contents with file_get_contents(). Notice that's it's as simple as that:
 * file_get_contents("http://example.com") or
 * file_get_contents("public://somefile.txt") just works. Although it's
 * not necessary, we use file_unmanaged_save_data() to save this file locally
 * and then find a local URL for it by using file_create_url().
 */
  public function handleFileRead(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    $uri = $form_values['fileops_file'];
  
    if (!is_file($uri)) {
      drupal_set_message(t('The file %uri does not exist', array('%uri' => $uri)), 'error');
      return;
    }
  
    // Make a working filename to save this by stripping off the (possible)
    // file portion of the streamwrapper. If it's an evil file extension,
    // file_munge_filename() will neuter it.
    $filename = file_munge_filename(preg_replace('@^.*/@', '', $uri), '', TRUE);
    $buffer = file_get_contents($uri);
  
    if ($buffer) {
      $sourcename = file_unmanaged_save_data($buffer, 'public://' . $filename);
      if ($sourcename) {
        $url = file_create_url($sourcename);
        $_SESSION['file_example_default_file'] = $sourcename;
        drupal_set_message(
         $this->t('The file was read and copied to %filename which is accessible at !url',
            array(
              '%filename' => $sourcename,
              '!url' => $this->l($url, $url),
            )
          )
        );
      }
      else {
        drupal_set_message(t('Failed to save the file'));
      }
    }
    else {
      // We failed to get the contents of the requested file.
      drupal_set_message(t('Failed to retrieve the file %file', array('%file' => $uri)));
    }

  }


  /**
   * Submit handler to delete a file.
   */
  public function handleFileDelete(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    $uri = $form_values['fileops_file'];
  
    // Since we don't know if the file is managed or not, look in the database
    // to see. Normally, code would be working with either managed or unmanaged
    // files, so this is not a typical situation.
    $file_object = self::getManagedFile($uri);
  
    // If a managed file, use file_delete().
    if (!empty($file_object)) {
      $result = file_delete($file_object);
      if ($result !== TRUE) {
        drupal_set_message(t('Failed deleting managed file %uri. Result was %result',
          array(
            '%uri' => $uri,
            '%result' => print_r($result, TRUE),
          )
        ), 'error');
      }
      else {
        drupal_set_message(t('Successfully deleted managed file %uri', array('%uri' => $uri)));
        $_SESSION['file_example_default_file'] = $uri;
      }
    }
    // Else use file_unmanaged_delete().
    else {
      $result = file_unmanaged_delete($uri);
      if ($result !== TRUE) {
        drupal_set_message(t('Failed deleting unmanaged file %uri', array('%uri' => $uri, 'error')));
      }
      else {
        drupal_set_message(t('Successfully deleted unmanaged file %uri', array('%uri' => $uri)));
        $_SESSION['file_example_default_file'] = $uri;
      }
    }

  }

  /**
   * Submit handler to check existence of a file.
   */
  public function handleFileExists(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    $uri = $form_values['fileops_file'];
    if (is_file($uri)) {
      drupal_set_message(t('The file %uri exists.', array('%uri' => $uri)));
    }
    else {
      drupal_set_message(t('The file %uri does not exist.', array('%uri' => $uri)));
    }
  }

  /**
   * Submit handler for directory creation.
   *
   * Here we create a directory and set proper permissions on it using
   * file_prepare_directory().
   */
  public function handleDirectoryCreate(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    $directory = $form_values['directory_name'];
  
    // The options passed to file_prepare_directory are a bitmask, so we can
    // specify either FILE_MODIFY_PERMISSIONS (set permissions on the directory),
    // FILE_CREATE_DIRECTORY, or both together:
    // FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY.
    // FILE_MODIFY_PERMISSIONS will set the permissions of the directory by
    // by default to 0755, or to the value of the variable 'file_chmod_directory'.
    if (!file_prepare_directory($directory, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY)) {
      drupal_set_message(t('Failed to create %directory.', array('%directory' => $directory)), 'error');
    }
    else {
      $result = is_dir($directory);
      drupal_set_message(t('Directory %directory is ready for use.', array('%directory' => $directory)));
      $_SESSION['file_example_default_directory'] = $directory;
    }
  }

  /**
   * Submit handler for directory deletion.
   *
   * @see file_unmanaged_delete_recursive()
   */
  public function handleDirectoryDelete(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    $directory = $form_values['directory_name'];
  
    $result = file_unmanaged_delete_recursive($directory);
    if (!$result) {
      drupal_set_message(t('Failed to delete %directory.', array('%directory' => $directory)), 'error');
    }
    else {
      drupal_set_message(t('Recursively deleted directory %directory.', array('%directory' => $directory)));
      $_SESSION['file_example_default_directory'] = $directory;
    }
  }

  /**
   * Submit handler to test directory existence.
   *
   * This actually just checks to see if the directory is writable
   *
   * @param array $form
   *   FormAPI form.
   * @param array $form_state
   *   FormAPI form state.
   */
  public function handleDirectoryExists(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    $directory = $form_values['directory_name'];
    $result = is_dir($directory);
    if (!$result) {
      drupal_set_message(t('Directory %directory does not exist.', array('%directory' => $directory)));
    }
    else {
      drupal_set_message(t('Directory %directory exists.', array('%directory' => $directory)));
    }
  }

  /**
   * Utility submit function to show the contents of $_SESSION.
   */
  public function handleShowSession(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    // If the devel module is installed, use it's nicer message format.
    if (\Drupal::moduleHandler()->moduleExists('devel')) {
      dsm($_SESSION['file_example'],$this->t('Entire $_SESSION["file_example"]'));
    }
    else {
      drupal_set_message('<pre>' . print_r($_SESSION['file_example'], TRUE) . '</pre>');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //we don't use this, but the interface requires us to implement it.
  }
  
  /**
  * Utility function to check for and return a managed file.
  *
  * In this demonstration code we don't necessarily know if a file is managed
  * or not, so often need to check to do the correct behavior. Normal code
  * would not have to do this, as it would be working with either managed or
  * unmanaged files.
  *
  * @param string $uri
  *   The URI of the file, like public://test.txt.
  
  * @return FileInterface|bool
  *
  * @todo This should still work. An entity query could be used instead. May be other alternatives.
  */
  private static function getManagedFile($uri) {
    $fid = db_query('SELECT fid FROM {file_managed} WHERE uri = :uri', array(':uri' => $uri))->fetchField();
    if (!empty($fid)) {
      $file_object = file_load($fid);
      return $file_object;
    }
    return FALSE;
  }
 
}
