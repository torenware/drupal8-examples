<?php

/**
 * Contains \Drupal\Tests\file_example\Unit\SessionWrapperTest.php
 *
 */

namespace Drupal\Tests\file_example\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\file_example\StreamWrapper\SessionWrapper;

class SessionWrapperTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    global $_SESSION;
    parent::setUp();
    // Note we do not save this.  The wrapper finds what
    // it needs; it's just a processing layer.
    if (!isset($_SESSION)) {
      $_SESSION = [];
    }
    $helper = new SessionWrapper();
    $helper->setUpStore();
  }

  /**
   *  Run our wrapper through the paces.
   */
  public function testWrapper() {
    // Check out root.
    $helper = new SessionWrapper();
    $root = $helper->getPath('');
    $this->assertTrue(is_array($root), "The root is an array");
    $this->assertTrue(empty($root), "The root is empty.");
    
    // add a top level file.
    $helper = new SessionWrapper();
    $helper->setPath('drupal.txt', "Stuff");
    $text = $helper->getPath('drupal.txt');
    $this->assertEquals($text, "Stuff", "File at base of hierarchy can be read.");

    // add a "directory"
    $helper = new SessionWrapper();
    $dir = [
      'file.txt' => 'More stuff',
    ];
    $helper->setPath('directory1', $dir);
    $fetched_dir = $helper->getPath('directory1');
    $this->assertEquals($fetched_dir['file.txt'], "More stuff", "File inside of directory can be read.");
    
    // Check file existance.
    $helper = new SessionWrapper();
    $this->assertTrue($helper->checkPath('drupal.txt'), "File at root still exists.");
    $this->assertFalse($helper->checkPath('file.txt'), "Non-existant file at root does not exist.");
    $this->assertTrue($helper->checkPath('directory1'), "Directory at root still exists.");
    $this->assertTrue($helper->checkPath('directory1/file.txt'), "File in directory at root still exists.");
    
    // Two deep.
    $helper = new SessionWrapper();
    $helper->setPath('directory1/directory2', []);
    $helper->setPath('directory1/directory2/junk.txt', "Store some junk");
    $text = $helper->getPath('directory1/directory2/junk.txt');
    $this->assertEquals($text, "Store some junk", "File inside of nested directory can be read.");
    
    // Clear references.
    $helper = new SessionWrapper();
    $before = $helper->checkPath('directory1/directory2/junk.txt');
    $this->assertTrue($before, "File 2 deep exists.");
    $helper->clearPath('directory1/directory2/junk.txt');
    $after = $helper->checkPath('directory1/directory2/junk.txt');
    $this->assertFalse($after, "File 2 deep should be gone.");
 
    // Clean up test.
    $helper = new SessionWrapper();
    $store = $helper->getPath('');
    $this->assertNotEmpty($store, "Before cleanup store is not empty.");
    $helper->cleanUpStore();
    $store = $helper->getPath('');
    $this->assertEmpty($store, "After cleanup store is empty.");
    
  }


}
