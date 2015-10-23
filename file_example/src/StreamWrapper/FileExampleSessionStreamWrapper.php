<?php
/**
 * @file
 * Provides a demonstration session:// streamwrapper.
 *
 * This example is nearly fully functional, but has no known
 * practical use. It's an example and demonstration only.
 */

namespace Drupal\file_example\StreamWrapper;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Routing\UrlGeneratorTrait;

/**
 * Example stream wrapper class to handle session:// streams.
 *
 * This is just an example, as it could have horrible results if much
 * information were placed in the $_SESSION variable. However, it does
 * demonstrate both the read and write implementation of a stream wrapper.
 *
 * A "stream" is an important Unix concept for the reading and writing of
 * files and other devices. Reading or writing a "stream" just means that you
 * open some device, file, internet site, or whatever, and you don't have to
 * know at all what it is. All the functions that deal with it are the same.
 * You can read/write more from/to the stream, seek a position in the stream,
 * or anything else without the code that does it even knowing what kind
 * of device it is talking to. This Unix idea is extended into PHP's
 * mindset.
 *
 * The idea of "stream wrapper" is that this can be extended indefinitely.
 * The classic example is HTTP: With PHP you can do a
 * file_get_contents("http://drupal.org/projects") as if it were a file,
 * because the scheme "http" is supported natively in PHP. So Drupal adds
 * the public:// and private:// schemes, and contrib modules can add any
 * scheme they want to. This example adds the session:// scheme, which allows
 * reading and writing the $_SESSION['file_example'] key as if it were a file.
 *
 * Drupal makes use of this concept to implement custom URI types like
 * "private://" and "public://".  To implement a stream wrapper, reading
 * the implementation of these stream wrappers is a very good way to get
 * started.
 *
 * To implement a stream wrapper in Drupal, you should do the following:
 *
 *  1. Create a class that implements the StreamWrapperInterface
 *     (Drupal\Core\StreamWrapper\StreamWrapperInterface).
 *
 *  2. Register the class with Drupal.  The best way to do this is to
 *     define a service in your MY_MODULE.services.yml file.  The
 *     service needs to be "tagged" with the scheme you want to implement,
 *     and, as so:
 *
 * @code
 *         tags:
 *           - { name: stream_wrapper, scheme: session }
 * @endcode
 *      See file_example.services.yml for an example.
 *
 *  3. (Optional) If you want to be able to access your files over the web,
 *     you need to add a route that handles, and implement hook_file_download().
 *     See file_example.routing.yml for an example of this, and file.module
 *     for the hook implementation.
 *
 * Note that because this implementation uses simple PHP arrays ($_SESSION)
 * it is limited to string values, so binary files will not work correctly.
 * Only text files can be used.
 *
 * @ingroup file_example
 */
class FileExampleSessionStreamWrapper implements StreamWrapperInterface {

  // We use this trait in order to get nice system-style links
  // for files stored via our stream wrapper.
  use UrlGeneratorTrait;

  /**
   * Instance URI (stream).
   *
   * These streams will be references as 'session://example_target'
   *
   * @var String
   */
  protected $uri;

  /**
   * The content of the stream.
   *
   * Since this trivial example just uses the $_SESSION variable, this is
   * simply a reference to the contents of the related part of
   * $_SESSION['file_example'].
   */
  protected $sessionContent;

  /**
   * Pointer to where we are in a directory read.
   */
  protected $directoryPointer;

  /**
   * List of keys in a given directory.
   */
  protected $directoryKeys;

  /**
   * The pointer to the next read or write within the session variable.
   */
  protected $streamPointer;

  /**
   * Returns the type of stream wrapper.
   *
   * @return int
   *   See StreamWrapperInterface for permissible values.
   */
  public static function getType() {
    return StreamWrapperInterface::NORMAL;
  }


  /**
   * Constructor method.
   */
  public function __construct() {
    $_SESSION['file_example']['.isadir.txt'] = TRUE;
  }

  /**
   * Returns the name of the stream wrapper for use in the UI.
   *
   * @return string
   *   The stream wrapper name.
   */
  public function getName() {
    return t('File Example Session files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Simulated file system using your session storage. Not for real use!');
  }


  /**
   * Implements setUri().
   */
  public function setUri($uri) {
    $this->uri = $uri;
  }

  /**
   * Implements getUri().
   */
  public function getUri() {
    return $this->uri;
  }

  /**
   * Implements getTarget().
   *
   * The "target" is the portion of the URI to the right of the scheme.
   * So in session://example/test.txt, the target is 'example/test.txt'.
   *
   * @todo Figure out what this is in the new API.
   */
  public function getTarget($uri = NULL) {
    if (!isset($uri)) {
      $uri = $this->uri;
    }

    list($scheme, $target) = explode('://', $uri, 2);

    // Remove erroneous leading or trailing, forward-slashes and backslashes.
    // In the session:// scheme, there is never a leading slash on the target.
    return trim($target, '\/');
  }

  /**
   * Implements getDirectoryPath().
   *
   * In this case there is no directory string, so return an empty string.
   */
  public function getDirectoryPath() {
    return '';
  }

  /**
   * Overrides getExternalUrl().
   *
   * We have set up a helper function and menu entry to provide access to this
   * key via HTTP; normally it would be accessible some other way.
   */
  public function getExternalUrl() {
    $path = str_replace('\\', '/', $this->getTarget());
    return $this->url('file_example.files.session', ['scheme' => 'session', 'file' => $path], ['absolute' => TRUE]);
  }

  /**
   * We have no concept of chmod, so just return TRUE.
   */
  public function chmod($mode) {
    return TRUE;
  }

  /**
   * Returns canonical, absolute path of the resource.
   *
   * Implementation placeholder. PHP's realpath() does not support stream
   * wrappers. We provide this as a default so that individual wrappers may
   * implement their own solutions.
   *
   * @return string
   *   Returns a string with absolute pathname on success (implemented
   *   by core wrappers), or FALSE on failure or if the registered
   *   wrapper does not provide an implementation.
   */
  public function realpath() {
    return 'session://' . $this->getLocalPath();
  }

  /**
   * Returns the local path.
   *
   * Here we aren't doing anything but stashing the "file" in a key in the
   * $_SESSION variable, so there's not much to do but to create a "path"
   * which is really just a key in the $_SESSION variable. So something
   * like 'session://one/two/three.txt' becomes
   * $_SESSION['file_example']['one']['two']['three.txt'] and the actual path
   * is "one/two/three.txt".
   *
   * @param string $uri
   *   Optional URI, supplied when doing a move or rename.
   */
  protected function getLocalPath($uri = NULL) {
    if (!isset($uri)) {
      $uri = $this->uri;
    }

    $path  = str_replace('session://', '', $uri);
    $path = trim($path, '/');
    return $path;
  }

  /**
   * Opens a stream, as for fopen(), file_get_contents(), file_put_contents().
   *
   * @param string $uri
   *   A string containing the URI to the file to open.
   * @param string $mode
   *   The file mode ("r", "wb" etc.).
   * @param int $options
   *   A bit mask of STREAM_USE_PATH and STREAM_REPORT_ERRORS.
   * @param string &$opened_path
   *   A string containing the path actually opened.
   *
   * @return bool
   *   Returns TRUE if file was opened successfully. (Always returns TRUE).
   *
   * @see http://php.net/manual/en/streamwrapper.stream-open.php
   */
  public function stream_open($uri, $mode, $options, &$opened_path) {
    $this->uri = $uri;
    // We make $session_content a reference to the appropriate key in the
    // $_SESSION variable. So if the local path were
    // /example/test.txt it $session_content would now be a
    // reference to $_SESSION['file_example']['example']['test.txt'].
    $this->sessionContent = &$this->uri_to_session_key($uri);

    // Reset the stream pointer since this is an open.
    $this->streamPointer = 0;
    return TRUE;
  }

  /**
   * Return a reference to the correct $_SESSION key.
   *
   * @param string $uri
   *   The uri: session://something.
   * @param bool $create
   *   If TRUE, create the key.
   *
   * @return array|bool
   *   A reference to the array at the end of the key-path, or
   *   FALSE if the path doesn't map to a key-path (and $create is FALSE).
   */
  protected function &uri_to_session_key($uri, $create = TRUE) {
    // Since our uri_to_session_key() method returns a reference, we
    // have to set up a failure flag variable.
    $fail = FALSE;
    $path = $this->getLocalPath($uri);
    $path_components = explode('/', $path);
    // Set up a reference to the root session:// 'directory.'.
    $var = &$_SESSION['file_example'];
    // Handle case of just session://.
    if (count($path_components) == 1 && $path_components[0] === '') {
      return $var;
    }
    // Walk through the path components and create keys in $_SESSION,
    // unless we're told not to create them.
    foreach ($path_components as $component) {
      if ($create || isset($var[$component])) {
        $var = &$var[$component];
      }
      else {
        // This path doesn't exist as keys, either because the
        // key doesn't exist, or because we're told not to create it.
        return $fail;
      }
    }
    return $var;
  }

  /**
   * Retrieve the underlying stream resource.
   *
   * This method is called in response to stream_select().
   *
   * @param int $cast_as
   *   Can be STREAM_CAST_FOR_SELECT when stream_select() is calling
   *   stream_cast() or STREAM_CAST_AS_STREAM when stream_cast() is called for
   *   other uses.
   *
   * @return resource|false
   *   The underlying stream resource or FALSE if stream_select() is not
   *   supported.
   *
   * @see stream_select()
   * @see http://php.net/manual/streamwrapper.stream-cast.php
   */
  public function stream_cast($cast_as) {
    return FALSE;
  }

  /**
   * Sets metadata on the stream.
   *
   * @param string $path
   *   A string containing the URI to the file to set metadata on.
   * @param int $option
   *   One of:
   *   - STREAM_META_TOUCH: The method was called in response to touch().
   *   - STREAM_META_OWNER_NAME: The method was called in response to chown()
   *     with string parameter.
   *   - STREAM_META_OWNER: The method was called in response to chown().
   *   - STREAM_META_GROUP_NAME: The method was called in response to chgrp().
   *   - STREAM_META_GROUP: The method was called in response to chgrp().
   *   - STREAM_META_ACCESS: The method was called in response to chmod().
   * @param mixed $value
   *   If option is:
   *   - STREAM_META_TOUCH: Array consisting of two arguments of the touch()
   *     function.
   *   - STREAM_META_OWNER_NAME or STREAM_META_GROUP_NAME: The name of the owner
   *     user/group as string.
   *   - STREAM_META_OWNER or STREAM_META_GROUP: The value of the owner
   *     user/group as integer.
   *   - STREAM_META_ACCESS: The argument of the chmod() as integer.
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure. If $option is not
   *   implemented, FALSE should be returned.
   *
   * @see http://www.php.net/manual/streamwrapper.stream-metadata.php
   */
  public function stream_metadata($path, $option, $value) {
    return FALSE;
  }


  /**
   * Change stream options.
   *
   * This method is called to set options on the stream.
   *
   * @param int $option
   *   One of:
   *   - STREAM_OPTION_BLOCKING: The method was called in response to
   *     stream_set_blocking().
   *   - STREAM_OPTION_READ_TIMEOUT: The method was called in response to
   *     stream_set_timeout().
   *   - STREAM_OPTION_WRITE_BUFFER: The method was called in response to
   *     stream_set_write_buffer().
   * @param int $arg1
   *   If option is:
   *   - STREAM_OPTION_BLOCKING: The requested blocking mode:
   *     - 1 means blocking.
   *     - 0 means not blocking.
   *   - STREAM_OPTION_READ_TIMEOUT: The timeout in seconds.
   *   - STREAM_OPTION_WRITE_BUFFER: The buffer mode, STREAM_BUFFER_NONE or
   *     STREAM_BUFFER_FULL.
   * @param int $arg2
   *   If option is:
   *   - STREAM_OPTION_BLOCKING: This option is not set.
   *   - STREAM_OPTION_READ_TIMEOUT: The timeout in microseconds.
   *   - STREAM_OPTION_WRITE_BUFFER: The requested buffer size.
   *
   * @return bool
   *   TRUE on success, FALSE otherwise. If $option is not implemented, FALSE
   *   should be returned.
   */
  public function stream_set_option($option, $arg1, $arg2) {
    return FALSE;
  }

  /**
   * Truncate stream.
   *
   * Will respond to truncation; e.g., through ftruncate().
   *
   * @param int $new_size
   *   The new size.
   *
   * @return bool
   *   TRUE on success, FALSE otherwise.
   *
   * @todo
   *   This one actually makes sense for the example.
   */
  public function stream_truncate($new_size) {
    return FALSE;
  }

  /**
   * Support for flock().
   *
   * The $_SESSION variable has no locking capability, so return TRUE.
   *
   * @param int $operation
   *   One of the following:
   *   - LOCK_SH to acquire a shared lock (reader).
   *   - LOCK_EX to acquire an exclusive lock (writer).
   *   - LOCK_UN to release a lock (shared or exclusive).
   *   - LOCK_NB if you don't want flock() to block while locking (not
   *     supported on Windows).
   *
   * @return bool
   *   Always returns TRUE at the present time. (no support)
   *
   * @see http://php.net/manual/en/streamwrapper.stream-lock.php
   */
  public function stream_lock($operation) {
    return TRUE;
  }

  /**
   * Support for fread(), file_get_contents() etc.
   *
   * @param int $count
   *   Maximum number of bytes to be read.
   *
   * @return string
   *   The string that was read, or FALSE in case of an error.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-read.php
   */
  public function stream_read($count) {
    if (is_string($this->sessionContent)) {
      $remaining_chars = strlen($this->sessionContent) - $this->streamPointer;
      $number_to_read = min($count, $remaining_chars);
      if ($remaining_chars > 0) {
        $buffer = substr($this->sessionContent, $this->streamPointer, $number_to_read);
        $this->streamPointer += $number_to_read;
        return $buffer;
      }
    }
    return FALSE;
  }

  /**
   * Support for fwrite(), file_put_contents() etc.
   *
   * @param string $data
   *   The string to be written.
   *
   * @return int
   *   The number of bytes written (integer).
   *
   * @see http://php.net/manual/en/streamwrapper.stream-write.php
   */
  public function stream_write($data) {
    // Sanitize the data in a simple way since we're putting it into the
    // session variable.
    $data = Html::escape($data);
    $this->sessionContent = substr_replace($this->sessionContent, $data, $this->streamPointer);
    $this->streamPointer += strlen($data);
    return strlen($data);
  }

  /**
   * Support for feof().
   *
   * @return bool
   *   TRUE if end-of-file has been reached.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-eof.php
   */
  public function stream_eof() {
    return FALSE;
  }

  /**
   * Support for fseek().
   *
   * @param int $offset
   *   The byte offset to got to.
   * @param int $whence
   *   SEEK_SET, SEEK_CUR, or SEEK_END.
   *
   * @return bool
   *   TRUE on success.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-seek.php
   */
  public function stream_seek($offset, $whence = SEEK_SET) {
    if (strlen($this->sessionContent) >= $offset) {
      $this->streamPointer = $offset;
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Support for fflush().
   *
   * @return bool
   *   TRUE if data was successfully stored (or there was no data to store).
   *   This always returns TRUE, as this example provides and needs no
   *   flush support.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-flush.php
   */
  public function stream_flush() {
    return TRUE;
  }

  /**
   * Support for ftell().
   *
   * @return int
   *   The current offset in bytes from the beginning of file.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-tell.php
   */
  public function stream_tell() {
    return $this->streamPointer;
  }

  /**
   * Support for fstat().
   *
   * @return array
   *   An array with file status, or FALSE in case of an error - see fstat()
   *   for a description of this array.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-stat.php
   */
  public function stream_stat() {
    return array(
      'size' => strlen($this->sessionContent),
    );
  }

  /**
   * Support for fclose().
   *
   * @return bool
   *   TRUE if stream was successfully closed.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-close.php
   */
  public function stream_close() {
    $this->streamPointer = 0;
    // Unassign the reference.
    unset($this->sessionContent);
    return TRUE;
  }

  /**
   * Support for unlink().
   *
   * @param string $uri
   *   A string containing the uri to the resource to delete.
   *
   * @return bool
   *   TRUE if resource was successfully deleted.
   *
   * @see http://php.net/manual/en/streamwrapper.unlink.php
   */
  public function unlink($uri) {
    $path = $this->getLocalPath($uri);
    $path_components = preg_split('/\//', $path);
    $fail = FALSE;
    $unset = '$_SESSION[\'file_example\']';
    foreach ($path_components as $component) {
      $unset .= '[\'' . $component . '\']';
    }
    // TODO: Is there a better way to delete from an array?
    // drupal_array_get_nested_value() doesn't work because it only returns
    // a reference; unsetting a reference only unsets the reference.
    eval("unset($unset);");
    return TRUE;
  }

  /**
   * Support for rename().
   *
   * @param string $from_uri
   *   The uri to the file to rename.
   * @param string $to_uri
   *   The new uri for file.
   *
   * @return bool
   *   TRUE if file was successfully renamed.
   *
   * @see http://php.net/manual/en/streamwrapper.rename.php
   */
  public function rename($from_uri, $to_uri) {
    $from_key = &$this->uri_to_session_key($from_uri);
    $to_key = &$this->uri_to_session_key($to_uri);
    if (is_dir($to_key) || is_file($to_key)) {
      return FALSE;
    }
    $to_key = $from_key;
    unset($from_key);
    return TRUE;
  }

  /**
   * Gets the name of the directory from a given path.
   *
   * @param string $uri
   *   A URI.
   *
   * @return string
   *   A string containing the directory name.
   *
   * @see drupal_dirname()
   */
  public function dirname($uri = NULL) {
    list($scheme, $target) = explode('://', $uri, 2);
    $target  = $this->getTarget($uri);
    if (strpos($target, '/')) {
      $dirname = preg_replace('@/[^/]*$@', '', $target);
    }
    else {
      $dirname = '';
    }
    return $scheme . '://' . $dirname;
  }

  /**
   * Support for mkdir().
   *
   * @param string $uri
   *   A string containing the URI to the directory to create.
   * @param int $mode
   *   Permission flags - see mkdir().
   * @param int $options
   *   A bit mask of STREAM_REPORT_ERRORS and STREAM_MKDIR_RECURSIVE.
   *
   * @return bool
   *   TRUE if directory was successfully created.
   *
   * @see http://php.net/manual/en/streamwrapper.mkdir.php
   */
  public function mkdir($uri, $mode, $options) {
    // If this already exists, then we can't mkdir.
    if (is_dir($uri) || is_file($uri)) {
      return FALSE;
    }

    // Create the key in $_SESSION;.
    $this->uri_to_session_key($uri, TRUE);

    // Place a magic file inside it to differentiate this from an empty file.
    $marker_uri = $uri . '/.isadir.txt';
    $this->uri_to_session_key($marker_uri, TRUE);
    return TRUE;
  }

  /**
   * Support for rmdir().
   *
   * @param string $uri
   *   A string containing the URI to the directory to delete.
   * @param int $options
   *   A bit mask of STREAM_REPORT_ERRORS.
   *
   * @return bool
   *   TRUE if directory was successfully removed.
   *
   * @see http://php.net/manual/en/streamwrapper.rmdir.php
   */
  public function rmdir($uri, $options) {
    $path = $this->getLocalPath($uri);
    $path_components = preg_split('/\//', $path);
    $fail = FALSE;
    $unset = '$_SESSION[\'file_example\']';
    foreach ($path_components as $component) {
      $unset .= '[\'' . $component . '\']';
    }
    // TODO: I really don't like this eval.
    debug($unset, 'array element to be unset');
    eval("unset($unset);");

    return TRUE;
  }

  /**
   * Support for stat().
   *
   * This important function goes back to the Unix way of doing things.
   * In this example almost the entire stat array is irrelevant, but the
   * mode is very important. It tells PHP whether we have a file or a
   * directory and what the permissions are. All that is packed up in a
   * bitmask. This is not normal PHP fodder.
   *
   * @param string $uri
   *   A string containing the URI to get information about.
   * @param int $flags
   *   A bit mask of STREAM_URL_STAT_LINK and STREAM_URL_STAT_QUIET.
   *
   * @return array|bool
   *   An array with file status, or FALSE in case of an error - see fstat()
   *   for a description of this array.
   *
   * @see http://php.net/manual/en/streamwrapper.url-stat.php
   */
  public function url_stat($uri, $flags) {
    // Get a reference to the $_SESSION key for this URI.
    $key = $this->uri_to_session_key($uri, FALSE);
    // Default to fail.
    $return = FALSE;
    $mode = 0;

    // We will call an array a directory and the root is always an array.
    if (is_array($key) && array_key_exists('.isadir.txt', $key)) {
      // S_IFDIR means it's a directory.
      $mode = 0040000;
    }
    elseif ($key !== FALSE) {
      // S_IFREG, means it's a file.
      $mode = 0100000;
    }

    if ($mode) {
      $size = 0;
      if ($mode == 0100000) {
        $size = strlen($key);
      }

      // There are no protections on this, so all writable.
      $mode |= 0777;
      $return = array(
        'dev' => 0,
        'ino' => 0,
        'mode' => $mode,
        'nlink' => 0,
        'uid' => 0,
        'gid' => 0,
        'rdev' => 0,
        'size' => $size,
        'atime' => 0,
        'mtime' => 0,
        'ctime' => 0,
        'blksize' => 0,
        'blocks' => 0,
      );
    }
    return $return;
  }

  /**
   * Support for opendir().
   *
   * @param string $uri
   *   A string containing the URI to the directory to open.
   * @param int $options
   *   Whether or not to enforce safe_mode (0x04).
   *
   * @return bool
   *   TRUE on success.
   *
   * @see http://php.net/manual/en/streamwrapper.dir-opendir.php
   */
  public function dir_opendir($uri, $options) {
    $var = &$this->uri_to_session_key($uri, FALSE);
    if ($var === FALSE || !array_key_exists('.isadir.txt', $var)) {
      return FALSE;
    }

    // We grab the list of key names, flip it so that .isadir.txt can easily
    // be removed, then flip it back so we can easily walk it as a list.
    $this->directoryKeys = array_flip(array_keys($var));
    unset($this->directoryKeys['.isadir.txt']);
    $this->directoryKeys = array_keys($this->directoryKeys);
    $this->directoryPointer = 0;
    return TRUE;
  }

  /**
   * Support for readdir().
   *
   * @return string|bool
   *   The next filename, or FALSE if there are no more files in the directory.
   *
   * @see http://php.net/manual/en/streamwrapper.dir-readdir.php
   */
  public function dir_readdir() {
    if ($this->directoryPointer < count($this->directoryKeys)) {
      $next = $this->directoryKeys[$this->directoryPointer];
      $this->directoryPointer++;
      return $next;
    }
    return FALSE;
  }

  /**
   * Support for rewinddir().
   *
   * @return bool
   *   TRUE on success.
   *
   * @see http://php.net/manual/en/streamwrapper.dir-rewinddir.php
   */
  public function dir_rewinddir() {
    $this->directoryPointer = 0;
  }

  /**
   * Support for closedir().
   *
   * @return bool
   *   TRUE on success.
   *
   * @see http://php.net/manual/en/streamwrapper.dir-closedir.php
   */
  public function dir_closedir() {
    $this->directoryPointer = 0;
    unset($this->directoryKeys);
    return TRUE;
  }

}
