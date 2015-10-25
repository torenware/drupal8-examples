<?php

/**
 * @file
 *   Contains \Drupal\Tests\file_example\MockSessionTrait
 */

namespace Drupal\Tests\file_example;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Prophecy\Prophecy\ProphecyInterface;
use Prophecy\Argument;

trait MockSessionTrait {

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
   * Create a mock session object.
   */
  protected function createSessionMock() {
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

}
