<?php
/**
 * @file
 *  Contains Drupal\file_example\Tests\FileExampleTest.
 * 
 * Tests for File Example.
 */

namespace Drupal\file_example\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for the File Example module.
 *
 * @ingroup file_example
 * @group file_example
 * @group examples
 */
class FileExampleTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('file_example');

  protected $priviledgedUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp(array('file_example'));
    $this->priviledgedUser = $this->drupalCreateUser(array('use file example'));
    $this->drupalLogin($this->priviledgedUser);
  }

  /**
   * Test the basic File Example UI.
   *
   * - Create a directory to work with
   * - Foreach scheme create and read files using each of the three methods.
   */
  public function testFileExampleBasic() {

    $expected_text = array(
      t('Write managed file') => t('Saved managed file'),
      t('Write unmanaged file') => t('Saved file as'),
      t('Unmanaged using PHP') => t('Saved file as'),
    );
    // For each of the three buttons == three write types.
    $buttons = array(
      t('Write managed file'),
      t('Write unmanaged file'),
      t('Unmanaged using PHP'),
    );
    foreach ($buttons as $button) {
      // For each scheme supported by Drupal + the session:// wrapper.
      //$schemes = array('public', 'private', 'temporary', 'session');
      $schemes = array('public', 'private', 'temporary');
      foreach ($schemes as $scheme) {
        // Create a directory for use.
        $dirname = $scheme . '://' . $this->randomMachineName(10);

        // Directory does not yet exist; assert that.
        $edit = array(
          'directory_name' => $dirname,
        );
        $this->drupalPost('examples/file_example/fileapi', $edit, t('Check to see if directory exists'));
        $this->assertRaw(t('Directory %dirname does not exist', array('%dirname' => $dirname)), 'Verify that directory does not exist.');

        $this->drupalPost('examples/file_example/fileapi', $edit, t('Create directory'));
        $this->assertRaw(t('Directory %dirname is ready for use', array('%dirname' => $dirname)));

        $this->drupalPost('examples/file_example/fileapi', $edit, t('Check to see if directory exists'));
        $this->assertRaw(t('Directory %dirname exists', array('%dirname' => $dirname)), 'Verify that directory now does exist.');

        // Create a file in the directory we created.
        $content = $this->randomString(30);
        $filename = $dirname . '/' . $this->randomMachineName(30) . '.txt';

        // Assert that the file we're about to create does not yet exist.
        $edit = array(
          'fileops_file' => $filename,
        );
        $this->drupalPost('examples/file_example/fileapi', $edit, t('Check to see if file exists'));
        $this->assertRaw(t('The file %filename does not exist', array('%filename' => $filename)), 'Verify that file does not yet exist.');

        debug(
          t('Processing button=%button, scheme=%scheme, dir=%dirname, file=%filename',
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
        $this->drupalPost('examples/file_example/fileapi', $edit, $button);
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
          $this->assertText($content);
        }

        // Verify that the file exists.
        $edit = array(
          'fileops_file' => $filename,
        );
        $this->drupalPost('examples/file_example/fileapi', $edit, t('Check to see if file exists'));
        $this->assertRaw(t('The file %filename exists', array('%filename' => $filename)), 'Verify that file now exists.');

        // Now read the file that got written above and verify that we can use
        // the writing tools.
        $edit = array(
          'fileops_file' => $output_filename,
        );
        $this->drupalPost('examples/file_example/fileapi', $edit, t('Read the file and store it locally'));

        $this->assertText(t('The file was read and copied'));

        $edit = array(
          'fileops_file' => $filename,
        );
        $this->drupalPost('examples/file_example/fileapi', $edit, t('Delete file'));
        $this->assertText(t('Successfully deleted'));
        $this->drupalPost('examples/file_example/fileapi', $edit, t('Check to see if file exists'));
        $this->assertRaw(t('The file %filename does not exist', array('%filename' => $filename)), 'Verify file has been deleted.');

        $edit = array(
          'directory_name' => $dirname,
        );
        $this->drupalPost('examples/file_example/fileapi', $edit, t('Delete directory'));
        $this->drupalPost('examples/file_example/fileapi', $edit, t('Check to see if directory exists'));
        $this->assertRaw(t('Directory %dirname does not exist', array('%dirname' => $dirname)), 'Verify that directory does not exist after deletion.');
      }
    }
  }
}
