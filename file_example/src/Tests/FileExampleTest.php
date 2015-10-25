<?php
/**
 * @file
 *   Contains Drupal\file_example\Tests\FileExampleTest.
 *
 * Tests for File Example.
 */

namespace Drupal\file_example\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for the File Example module.
 *
 * @ingroup file_example
 *
 * @group file_example
 * @group examples
 */
class FileExampleTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('file_example', 'file');

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $priviledgedUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp(array('file_example'));
    $permissions = [
      'use file example',
      'read private files',
      'read temporary files',
      'read session files',
    ];
    $this->priviledgedUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->priviledgedUser);
  }

  /**
   * t() no longer returns a string, but is used heavily in this test in contexts where it
   * is important that it really return a string, and not TranslatableMarkup.  We substitute
   * our own implementation that will hopefully be localizable, but will not have this problem.
   */
  protected function t($string, $args = [], $options = []) {
    return (string) t($string, $args, $options);
  }

  /**
   * Test the basic File Example UI.
   *
   * - Create a directory to work with
   * - Foreach scheme create and read files using each of the three methods.
   */
  public function testFileExampleBasic() {

    $expected_text = array(
      $this->t('Write managed file') => $this->t('Saved managed file'),
      $this->t('Write unmanaged file') => $this->t('Saved file as'),
      $this->t('Unmanaged using PHP') => $this->t('Saved file as'),
    );
    // For each of the three buttons == three write types.
    $buttons = array(
      $this->t('Write managed file'),
      $this->t('Write unmanaged file'),
      $this->t('Unmanaged using PHP'),
    );
    foreach ($buttons as $button) {
      // For each scheme supported by Drupal + the session:// wrapper.
      $schemes = array('public', 'private', 'temporary', 'session');
      foreach ($schemes as $scheme) {
        // Create a directory for use.
        $dirname = $scheme . '://' . $this->randomMachineName(10);

        // Directory does not yet exist; assert that.
        $edit = array(
          'directory_name' => $dirname,
        );
        $this->drupalPostForm('examples/file_example', $edit, $this->t('Check to see if directory exists'));
        $this->assertRaw(t('Directory %dirname does not exist', array('%dirname' => $dirname)), 'Verify that directory does not exist.');

        $this->drupalPostForm('examples/file_example', $edit, $this->t('Create directory'));
        $this->assertRaw(t('Directory %dirname is ready for use', array('%dirname' => $dirname)));

        $this->drupalPostForm('examples/file_example', $edit, $this->t('Check to see if directory exists'));
        $this->assertRaw(t('Directory %dirname exists', array('%dirname' => $dirname)), 'Verify that directory now does exist.');

        // Create a file in the directory we created.
        $content = $this->randomMachineName(30);
        $filename = $dirname . '/' . $this->randomMachineName(30) . '.txt';

        // Assert that the file we're about to create does not yet exist.
        $edit = array(
          'fileops_file' => $filename,
        );
        $this->drupalPostForm('examples/file_example', $edit, $this->t('Check to see if file exists'));
        $this->assertRaw(t('The file %filename does not exist', array('%filename' => $filename)), 'Verify that file does not yet exist.');

        debug(
          $this->t('Processing button=%button, scheme=%scheme, dir=%dirname, file=%filename',
            array(
              '%button' => $button,
              '%scheme' => $scheme,
              '%filename' => $filename,
              '%dirname' => $dirname,
            )
          )
        );
        $edit = array(
          'write_contents' => $content,
          'destination' => $filename,
        );
        $options = [];
        if (($scheme == 'session') and ($expected_text[$button] == 'Saved managed file')) {
          //$options['query'] = [];
          //$options['query']['XDEBUG_SESSION_START'] = 'PHPSTORM';
        }
        $this->drupalPostForm('examples/file_example', $edit, $button, $options);
        debug($expected_text[$button], "Button Text");
        $this->assertText($expected_text[$button]);

        // Capture the name of the output file, as it might have changed due
        // to file renaming.
        $element = $this->xpath('//span[@id="uri"]');
        $output_filename = (string) $element[0];
        debug($output_filename, 'Name of output file');

        // Click the link provided that is an easy way to get the data for
        // checking and make sure that the data we put in is what we get out.
        if (!in_array($scheme, array('private', 'temporary'))) {
          $this->clickLink(t('this URL'));
          // assertText give sketchy answers when the content is *exactly* the contents of the
          // buffer, so let's do something less fragile.
          // $this->assertText($content);
          $buffer = $this->getTextContent();
          $this->assertEqual($content, $buffer, "File contents matched.");
        }

        // Verify that the file exists.
        $edit = array(
          'fileops_file' => $filename,
        );
        $this->drupalPostForm('examples/file_example', $edit, $this->t('Check to see if file exists'));
        $this->assertRaw(t('The file %filename exists', array('%filename' => $filename)), 'Verify that file now exists.');

        // Now read the file that got written above and verify that we can use
        // the writing tools.
        $edit = array(
          'fileops_file' => $output_filename,
        );
        $this->drupalPostForm('examples/file_example', $edit, $this->t('Read the file and store it locally'));

        $this->assertText(t('The file was read and copied'));

        $edit = array(
          'fileops_file' => $filename,
        );

        $this->drupalPostForm('examples/file_example', $edit, $this->t('Delete file'));
        $this->assertText(t('Successfully deleted'));
        $this->drupalPostForm('examples/file_example', $edit, $this->t('Check to see if file exists'));
        $this->assertRaw(t('The file %filename does not exist', array('%filename' => $filename)), 'Verify file has been deleted.');

        $edit = array(
          'directory_name' => $dirname,
        );
        $this->drupalPostForm('examples/file_example', $edit, $this->t('Delete directory'));
        $this->drupalPostForm('examples/file_example', $edit, $this->t('Check to see if directory exists'));
        $this->assertRaw(t('Directory %dirname does not exist', array('%dirname' => $dirname)), 'Verify that directory does not exist after deletion.');
      }
    }
  }

}