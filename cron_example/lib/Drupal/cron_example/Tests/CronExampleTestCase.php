<?php

/**
 * @file
 * Test case for testing the cron example module.
 */

/**
 * @addtogroup cron_example
 * @{
 */

namespace Drupal\cron_example\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * cron_example test class
 */
class CronExampleTestCase extends WebTestBase {

  public static $modules = array('cron_example');
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Cron example functionality',
      'description' => 'Test the functionality of the Cron Example.',
      'group' => 'Examples',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Create user. Search content permission granted for the search block to
    // be shown.
    $this->webUser = $this->drupalCreateUser(array('administer site configuration'));
    $this->drupalLogin($this->webUser);
  }

  /**
   * Create an example node, test block through admin and user interfaces.
   */
  public function testCronExampleBasic() {
    // Pretend that cron has never been run (even though simpletest seems to
    // run it once...)
    variable_set('cron_example_next_execution', 0);
    $this->drupalGet('examples/cron_example');

    // Initial run should cause cron_example_cron() to fire.
    $post = array();
    $this->drupalPostForm('examples/cron_example', $post, t('Run cron now'));
    $this->assertText(t('cron_example executed at'));

    // Forcing should also cause cron_example_cron() to fire.
    $post['cron_reset'] = TRUE;
    $this->drupalPostForm(NULL, $post, t('Run cron now'));
    $this->assertText(t('cron_example executed at'));

    // But if followed immediately and not forced, it should not fire.
    $post['cron_reset'] = FALSE;
    $this->drupalPostForm(NULL, $post, t('Run cron now'));
    $this->assertNoText(t('cron_example executed at'));

    $this->assertText(t('There are currently 0 items in queue 1 and 0 items in queue 2'));
    $post = array(
      'num_items' => 5,
      'queue' => 'cron_example_queue_1',
    );
    $this->drupalPostForm(NULL, $post, t('Add jobs to queue'));
    $this->assertText('There are currently 5 items in queue 1 and 0 items in queue 2');
    $post = array(
      'num_items' => 100,
      'queue' => 'cron_example_queue_2',
    );
    $this->drupalPostForm(NULL, $post, t('Add jobs to queue'));
    $this->assertText('There are currently 5 items in queue 1 and 100 items in queue 2');

    $post = array();
    $this->drupalPostForm('examples/cron_example', $post, t('Run cron now'));
    $this->assertPattern('/Queue 1 worker processed item with sequence 5 /');
    $this->assertPattern('/Queue 2 worker processed item with sequence 100 /');
  }

}

/**
 * @} End of "addtogroup cron_example".
 */
