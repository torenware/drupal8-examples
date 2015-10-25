<?php

/**
 * Contains \Drupal\Tests\file_example\Unit\SessionWrapperTest.php
 *
 */

namespace Drupal\Tests\file_example\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\file_example\StreamWrapper\SessionWrapper;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Prophecy\Prophecy\ProphecyInterface;
use Prophecy\Argument;


class SessionWrapperTest extends UnitTestCase {
  
  /**
   * @var array
   *
   * We'll use this to back our mock session.
   */
  protected $sessionStore;
  
  /**
   * @var RequestStack|ProphecyInterface
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    
    // Mock the session service.
    $this->sessionStore = [];
    $session = $this->prophesize(SessionInterface::class);
    $test = $this;
    
    $session
      ->get('file_example', [])
      ->will(function($args) use ($test) {
        return $test->getSessionStore();
      });

    $session
      ->set('file_example', Argument::any())
      ->will(function($args) use ($test) {
        $test->setSessionStore($args[1]);
      });

    $session
      ->remove('file_example')
      ->will(function($args) use ($test) {
        $test->resetSessionStore();
      });
      
    $request = $this->prophesize(Request::class);
    $request
      ->getSession()
      ->willReturn($session->reveal());
      
    $requestStack = $this->prophesize(RequestStack::class);
    $requestStack
      ->getCurrentRequest()
      ->willReturn($request->reveal());
      
    $this->requestStack = $requestStack->reveal();
    
    // Set up the example.
    $helper = new SessionWrapper($this->requestStack);
    $helper->setUpStore();
  }
  
  /**
   * Helper for mocks.
   */
  public function getSessionStore() {
    return $this->sessionStore;
  }
  
  /**
   * Helper for our mocks.
   */
  public function setSessionStore($data) {
    $this->sessionStore = $data;
  }

  /**
   * Helper for our mocks.
   */
  public function resetSessionStore() {
    $this->sessionStore = [];
  }

  /**
   *  Run our wrapper through the paces.
   */
  public function testWrapper() {
    // Check out root.
    $helper = new SessionWrapper($this->requestStack);
    $root = $helper->getPath('');
    $this->assertTrue(is_array($root), "The root is an array");
    $this->assertTrue(empty($root), "The root is empty.");
    
    // add a top level file.
    $helper = new SessionWrapper($this->requestStack);
    $helper->setPath('drupal.txt', "Stuff");
    $text = $helper->getPath('drupal.txt');
    $this->assertEquals($text, "Stuff", "File at base of hierarchy can be read.");

    // add a "directory"
    $helper = new SessionWrapper($this->requestStack);
    $dir = [
      'file.txt' => 'More stuff',
    ];
    $helper->setPath('directory1', $dir);
    $fetched_dir = $helper->getPath('directory1');
    $this->assertEquals($fetched_dir['file.txt'], "More stuff", "File inside of directory can be read.");
    
    // Check file existance.
    $helper = new SessionWrapper($this->requestStack);
    $this->assertTrue($helper->checkPath('drupal.txt'), "File at root still exists.");
    $this->assertFalse($helper->checkPath('file.txt'), "Non-existant file at root does not exist.");
    $this->assertTrue($helper->checkPath('directory1'), "Directory at root still exists.");
    $this->assertTrue($helper->checkPath('directory1/file.txt'), "File in directory at root still exists.");
    
    // Two deep.
    $helper = new SessionWrapper($this->requestStack);
    $helper->setPath('directory1/directory2', []);
    $helper->setPath('directory1/directory2/junk.txt', "Store some junk");
    $text = $helper->getPath('directory1/directory2/junk.txt');
    $this->assertEquals($text, "Store some junk", "File inside of nested directory can be read.");
    
    // Clear references.
    $helper = new SessionWrapper($this->requestStack);
    $before = $helper->checkPath('directory1/directory2/junk.txt');
    $this->assertTrue($before, "File 2 deep exists.");
    $helper->clearPath('directory1/directory2/junk.txt');
    $after = $helper->checkPath('directory1/directory2/junk.txt');
    $this->assertFalse($after, "File 2 deep should be gone.");
 
    // Clean up test.
    $helper = new SessionWrapper($this->requestStack);
    $store = $helper->getPath('');
    $this->assertNotEmpty($store, "Before cleanup store is not empty.");
    $helper->cleanUpStore();
    $store = $helper->getPath('');
    $this->assertEmpty($store, "After cleanup store is empty.");
    
  }


}
