<?php
/**
 * @package Resources
 * @subpackage Tests
 */

namespace Gustavus\Resources\Test;
use \Gustavus\Resources\CSSMin;

/**
 * Test for CSSMin class
 *
 * @package Resources
 * @subpackage Tests
 */
class CSSMinTest extends TestBase
{
  /**
   * Location of the test js file
   *
   * @var string
   */
  private static $testFilePath = '/cis/lib/Gustavus/Resources/Test/files/test.css';

  /**
   * Location of our minified file
   *
   * @var string
   */
  private static $testMinifiedPath;

  /**
   * Location of our minify info file
   *
   * @var string
   */
  private static $minifyInfoPath = '/cis/lib/Gustavus/Resources/Test/files/min/css/.gacmin';

  /**
   * Flag to specify if an error was triggered or not
   *
   * @var boolean
   */
  private $errorTriggered = false;

  /**
   * Error string of the triggered error
   *
   * @var string
   */
  private $errorString;

  /**
   * Sets things up before the tests run in this class.
   */
  public static function setUpBeforeClass()
  {
    parent::setUpBeforeClass();
    self::$testMinifiedPath = sprintf('%s%s-%s.css', CSSMin::$minifiedFolder, 'test', md5('/cis/lib/Gustavus/Resources/Test/files'));
  }

  /**
   * destructs the object after each test
   * @return void
   */
  public function tearDown()
  {
    $this->errorTriggered = false;
    $this->errorString = null;
    parent::tearDown();
  }

  /**
   * Handles notices so we can test that user notices get triggered
   *
   * @param  integer $errno  Error number
   * @param  string $errstr Error string
   * @return void
   */
  public function handleNotice($errno, $errstr)
  {
    $this->errorTriggered = true;
    $this->errorString = $errstr;
    $this->assertSame(E_USER_NOTICE, $errno);
  }

  /**
   * @test
   */
  public function crushify()
  {
    $resource = ['path' => '/cis/lib/Gustavus/Resources/Test/files/test.css'];
    $options  = ['doc_root' => '/cis/lib/'];

    $actual = CSSMin::crushify($resource, true, $options);
    $this->assertContains(self::$testMinifiedPath, $actual);
  }

  /**
   * @test
   */
  public function crushifyNoUrlify()
  {
    $resource = ['path' => '/cis/lib/Gustavus/Resources/Test/files/test.css'];
    $options  = ['doc_root' => '/cis/lib/'];

    $actual = CSSMin::crushify($resource, true, $options, false);
    $this->assertTrue(is_array($actual));
    $this->assertContains(self::$testMinifiedPath, $actual['path']);
  }

  /**
   * @test
   */
  public function crushifyInline()
  {
    $resource = ['path' => '/cis/lib/Gustavus/Resources/Test/files/test.css'];
    $options  = ['doc_root' => '/cis/lib/', 'crushMethod' => 'inline'];

    $actual = CSSMin::crushify($resource, true, $options);
    $this->assertContains('<style', $actual);
    $this->assertContains('#testing', $actual);
  }

  /**
   * @test
   */
  public function bundle()
  {
    $resources = ['/cis/lib/Gustavus/Resources/Test/files/test.css', '/cis/lib/Gustavus/Resources/Test/files/test.css'];
    $result = CSSMin::bundle($resources, $resources, [1, 1]);

    $file = 'testBNDL-' . md5(implode(',', $resources)) . '.css';
    $this->assertContains($file, $result);
    $this->assertTrue(file_exists(CSSMin::$minifiedFolder . $file));
  }

  /**
   * @test
   */
  public function bundleNoMinify()
  {
    $resources = ['/cis/lib/Gustavus/Resources/Test/files/test.css', '/cis/lib/Gustavus/Resources/Test/files/test.css'];
    $result = CSSMin::bundle($resources, $resources, [1, 1], false);

    $file = 'testBNDL-' . md5(implode(',', $resources)) . '.css';
    $this->assertContains($file, $result);
    $this->assertTrue(file_exists(CSSMin::$minifiedFolder . $file));
    $this->assertContains('/**', file_get_contents(CSSMin::$minifiedFolder . $file));
  }

  /**
   * @test
   */
  public function bundleWithExistingInfoFile()
  {
    file_put_contents(CSSMin::$minifiedFolder . CSSMin::$minifyInfoFile, json_encode([]));
    $resources = ['/cis/lib/Gustavus/Resources/Test/files/test.css', '/cis/lib/Gustavus/Resources/Test/files/test.css'];
    $result = CSSMin::bundle($resources, $resources, [1, 1]);

    $file = 'testBNDL-' . md5(implode(',', $resources)) . '.css';
    $this->assertContains($file, $result);
    $this->assertTrue(file_exists(CSSMin::$minifiedFolder . $file));
  }

  /**
   * @test
   */
  public function bundleReRun()
  {
    $resources = ['/cis/lib/Gustavus/Resources/Test/files/test.css', '/cis/lib/Gustavus/Resources/Test/files/test.css'];
    $result = CSSMin::bundle($resources, $resources, [1, 1]);

    $file = 'testBNDL-' . md5(implode(',', $resources)) . '.css';
    $this->assertContains($file, $result);
    $minPath = CSSMin::$minifiedFolder . $file;
    $this->assertTrue(file_exists($minPath));
    // change our file so we can verify that we didn't regenerate it.
    file_put_contents($minPath, file_get_contents($minPath) . 'arstAddition');

    $result = CSSMin::bundle($resources, $resources, [1, 1]);

    $this->assertContains($file, $result);
    $this->assertTrue(file_exists($minPath));
    // make sure our file wasn't re-generated
    $this->assertContains('arstAddition', file_get_contents($minPath));
  }
}