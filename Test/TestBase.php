<?php
/**
 * @package Resources
 * @subpackage Tests
 */

namespace Gustavus\Resources\Test;

use \Gustavus\Resources\JSMin,
  Gustavus\Resources\CSSMin;

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
   * Backup of the minified css folder
   * @var string
   */
  private static $minifiedCSSFolderBackup;

  /**
   * Sets things up before the tests run in this class.
   */
  public static function setUpBeforeClass()
  {
    self::$minifiedFolderBackup = JSMin::$minifiedFolder;
    self::$minifiedCSSFolderBackup = CSSMin::$minifiedFolder;
    JSMin::$minifiedFolder = '/cis/lib/Gustavus/Resources/Test/files/min/';
    CSSMin::$minifiedFolder = '/cis/lib/Gustavus/Resources/Test/files/min/css/';
  }

  /**
   * Tears things down after the tests run in this class.
   *
   * @return void
   */
  public static function tearDownAfterClass()
  {
    JSMin::$minifiedFolder = self::$minifiedFolderBackup;
    CSSMin::$minifiedFolder = self::$minifiedCSSFolderBackup;
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
    if (!file_exists(JSMin::$minifiedFolder)) {
      mkdir(JSMin::$minifiedFolder);
    }
    if (!file_exists(CSSMin::$minifiedFolder)) {
      mkdir(CSSMin::$minifiedFolder);
    }

    $addDocRootToken = override_method('\Gustavus\Resources\Resource', 'addDocRootToPath', function($filePath) use(&$addDocRootToken) {
      if (strpos($filePath, '/cis/lib') !== false) {
        return $filePath;
      }
      return call_overridden_func($addDocRootToken, null, $filePath);
    });

    $this->overrideToken['addDocRootToPath'] = $addDocRootToken;
  }

  /**
   * destructs the object after each test
   * @return void
   */
  public function tearDown()
  {
    if (file_exists(JSMin::$minifiedFolder)) {
      self::removeFiles(JSMin::$minifiedFolder);
    }
    if (file_exists(CSSMin::$minifiedFolder)) {
      self::removeFiles(CSSMin::$minifiedFolder);
    }
    if (file_exists('/cis/lib/Gustavus/Resources/Test/files/staging/')) {
      self::removeFiles('/cis/lib/Gustavus/Resources/Test/files/staging/');
    }
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