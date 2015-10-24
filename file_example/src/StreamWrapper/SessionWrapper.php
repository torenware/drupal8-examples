<?php

/**
 *
 * Contains \Drupal\file_example\StreamWrapper\SessionWrapper.
 *
 * Drupal 8 deprecates direct access to the $_SESSION magic variable.  To avoid
 * directly munging $_SESSION, this class abstracts access to the session
 * so we can use the approved APIs for D8.
 *
 */

namespace Drupal\file_example\StreamWrapper;

class SessionWrapper {

  /**
   * Keep the top-level "file system" area in one place.
   */
  const SESSION_BASE_ATTRIBUTE = 'file_example';

  /**
   * @var string
   *
   * This is the current location in our store.
   */
  protected $storePath;

  /**
   *  Construct our helper object.
   */
  public function __construct() {
    $this->storePath = '';
  }

  /**
   * Get whatever's in the store.
   *
   * @return array
   */
  protected function getStore() {
    $store = [];
    if (isset($_SESSION[static::SESSION_BASE_ATTRIBUTE])) {
      $store = $_SESSION[static::SESSION_BASE_ATTRIBUTE];
    }
    return $store;
  }

  /**
   *  Since we cannot deal with references to the session, write the whole
   *  store back.
   *
   * @param array $store.
   */
  protected function setStore($store) {
    $_SESSION[static::SESSION_BASE_ATTRIBUTE] = $store;
  }

  /**
   *  Turn a path into the arrays we use internally.
   *
   * @param string $path
   *   Path into the store.
   * @param boolean $is_dir
   *   Path will be used as a container.  Otherwise, just a scalar value.
   *
   *
   * @return array|bool
   *   Return an array containing the "bottom" and "tip" of a directory
   *   hierarchy.  You will want to save the 'bottom' array, but you may
   *   need to manipulate an object at the very tip of the hierarchy
   *   as defined in the path. The tip will be a string if we are scalar
   *   and an array otherwise.  Since we don't want to create new
   *   sub arrays as a side effect, we return FALSE the intervening path
   *   does not exist.
   */
  public function processPath($path, $is_dir = FALSE) {
    // We need to create a reference into the store for the point
    // the of the path, so get a copy of the store.
    $store = $this->getStore();

    $hierarchy = explode('/', $path);
    if (empty($hierarchy) or empty($hierarchy[0])) {
      return ['store' => &$store, 'tip' => &$store];
    }
    $bottom =& $store;
    $tip = array_pop($hierarchy);

    foreach ($hierarchy as $dir) {
      if (!isset($bottom[$dir])) {
        // If the path does not exist, DO NOT create it.
        // That is handled by the stream wrapper code.
        return FALSE;
      }
      $new_tip =& $bottom[$dir];
      $bottom =& $new_tip;
    }
    // If the hierarchy was empty, just point to the object.
    $new_tip =& $bottom[$tip];
    $bottom =& $new_tip;
    return ['store' => &$store, 'tip' => &$bottom];
  }

  /**
   *  The equivalent to dirname() and basename() for a path
   *
   * @param string $path
   *
   * @return array
   *   .
   */
  protected function getParentPath($path) {
    $dirs = explode('/', $path);
    $tip = array_pop($dirs);
    $parent = implode('/', $dirs);
    return ['dirname' => $parent, 'basename' => $tip];
  }

  /**
   * Clear a path into our store.
   *
   * @param string $path
   */
  public function clearPath($path) {
    $store = $this->getStore();
    if ($this->checkPath($path)) {
      $path_info = $this->getParentPath($path);
      $store_info = $this->processPath($path_info['dirname']);
      if ($store_info === FALSE) {
        // The path was not found, nothing to do.
        return;

      }
      // We want to clear the key at the tip, so...
      unset($store_info['tip'][$path_info['basename']]);
      // Write back to the store.
      $this->setStore($store_info['store']);
    }

  }

  /**
   *  Get a path.
   *
   * @param string $path
   *
   * @return mixed
   *   Return the stored value at this "node" of the store.
   */
  public function getPath($path) {
    $path_info = $this->getParentPath($path);
    $store_info = $this->processPath(($path_info['dirname']));
    if ($store_info === FALSE) {
      return NULL;
    }
    if ($store_info['store'] === $store_info['tip']) {
      //we are at the top of the hierarchy; return the store itself.
      if (empty($path_info['basename'])) {
        return $store_info['store'];
      }
      return $store_info['store'][$path_info['basename']];
    }
    
    if (!isset($store_info['tip'][$path_info['basename']])) {
      return NULL;
    }
    return $store_info['tip'][$path_info['basename']];
  }

  /**
   * Set a path.
   *
   * @param string $path
   *   Path into the store.
   * @param string|array $value
   *   Set a value.
   */
   public function setPath($path, $value) {
     $path_info = $this->getParentPath($path);
     $store_info = $this->processPath(($path_info['dirname']));
     if ($store_info !== FALSE) {
       $store_info['tip'][$path_info['basename']] = $value;
     }
     $this->setStore($store_info['store']);
   }

  /**
   * Does path exist?
   *
   * @param string $path
   *   Path into the store.
   */
  public function checkPath($path) {
    $path_info = $this->getParentPath($path);
    $store_info = $this->processPath($path_info['dirname']);
    if (empty($store_info)) {
      // containing directory did not exist.
      return FALSE;
    }
    return isset($store_info['tip'][$path_info['basename']]);
  }

  /**
   * Set up the store for use.
   */
  public static function setUpStore() {
    //nothing to do with $_SESSION version.
  }


  /**
   *  Zero out the store.
   */
  public static function cleanUpStore() {
    unset($_SESSION[static::SESSION_BASE_ATTRIBUTE]);
  }

}
