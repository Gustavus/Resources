<?php
/**
 * @package Resources
 * @subpackage Tests
 */

namespace Gustavus\Resources\Test;
use \Gustavus\Resources\JSMin;

/**
 * Base test class for testing resources
 *
 * @package Resources
 * @subpackage Tests
 */
class TestBase extends \Gustavus\Test\Test
{
  /**
   * Token for overriding methods
   * @var mixed
   */
  protected $overrideToken = [];

  /**
   * Location of our minify info file
   *
   * @var string
   */
  private static $minifyInfoPath = '/cis/lib/Gustavus/Resources/Test/files/min/.gacmin';

  /**
   * Backup of the minified folder
   * @var string
   */
  private static $minifiedFolderBackup;

  /**
   * Sets things up before the tests run in this class.
   */
  public static function setUpBeforeClass()
  {
    self::$minifiedFolderBackup = JSMin::$minifiedFolder;
    JSMin::$minifiedFolder = '/cis/lib/Gustavus/Resources/Test/files/min/';
    if (!file_exists(JSMin::$minifiedFolder)) {
      mkdir(JSMin::$minifiedFolder);
    }
  }

  /**
   * Tears things down after the tests run in this class.
   *
   * @return void
   */
  public static function tearDownAfterClass()
  {
    JSMin::$minifiedFolder = self::$minifiedFolderBackup;
    self::removeFiles('/cis/lib/Gustavus/Resources/Test/files/min/');
    self::removeFiles('/cis/lib/Gustavus/Resources/Test/files/staging/');
  }

  /**
   * sets up the object for each test
   * @return void
   */
  public function setUp()
  {
    $this->set('\Gustavus\Resources\JSMin', 'stagingDir', '/cis/lib/Gustavus/Resources/Test/files/staging/');
    if (!file_exists('/cis/lib/Gustavus/Resources/Test/files/staging/')) {
      mkdir('/cis/lib/Gustavus/Resources/Test/files/staging/');
    }
    $this->overrideToken['addDocRootToPath'] = override_method('\Gustavus\Resources\JSMin', 'addDocRootToPath', function($filePath) {return $filePath;});
  }

  /**
   * destructs the object after each test
   * @return void
   */
  public function tearDown()
  {
    $this->overrideToken = [];
  }

  /**
   * Recursively removes files
   *
   * @param  string $dir Directory to remove files from
   * @return void
   */
  protected static function removeFiles($dir)
  {
    $files = scandir($dir);
    foreach ($files as $file) {
      if ($file === '.' || $file === '..') {
        continue;
      }
      $file = $dir . '/' . $file;
      if (is_dir($file)) {
        self::removeFiles($file);
        rmdir($file);
        continue;
      }
      unlink($file);
    }
  }
}