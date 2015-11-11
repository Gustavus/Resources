<?php
/**
 * @package Resources
 * @subpackage Tests
 */

namespace Gustavus\Resources\Test;
use \Gustavus\Resources\JSMin;

/**
 * Test for JSMin class
 *
 * @package Resources
 * @subpackage Tests
 */
class JSMinTest extends TestBase
{
  /**
   * Location of the test js file
   *
   * @var string
   */
  private static $testFilePath = '/cis/lib/Gustavus/Resources/Test/files/test.js';

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
  private static $minifyInfoPath = '/cis/lib/Gustavus/Resources/Test/files/min/.gacmin';

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
    self::$testMinifiedPath = sprintf('%s%s-%s.js', JSMin::$minifiedFolder, 'test', md5('/cis/lib/Gustavus/Resources/Test/files/'));
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
  public function buildMinifyOptionsHash()
  {
    $options = ['test' => 'arst', 'arst' => 'test'];
    $result = $this->call('\Gustavus\Resources\JSMin', 'buildMinifyOptionsHash', [$options]);

    $this->assertSame('415183fa4e336475ff6f20d687e864dc', $result);
  }

  /**
   * @test
   */
  public function removeDocRootFromPath()
  {
    $_SERVER['DOCUMENT_ROOT'] = '/cis/www';
    $path = '/cis/www/resources/arst/';
    $this->assertSame('/resources/arst/', $this->call('\Gustavus\Resources\JSMin', 'removeDocRootFromPath', [$path]));
  }

  /**
   * @test
   */
  public function removeDocRootFromPathDocRootTrailingSlash()
  {
    $_SERVER['DOCUMENT_ROOT'] = '/cis/www/';
    $path = '/cis/www/resources/arst/';
    $this->assertSame('/resources/arst/', $this->call('\Gustavus\Resources\JSMin', 'removeDocRootFromPath', [$path]));
  }

  /**
   * @test
   */
  public function minifyFileAlreadyMinfied()
  {
    $result = JSMin::minifyFile(JSMin::$minifiedFolder . 'arst.js');
    $this->assertSame(JSMin::$minifiedFolder . 'arst.js', $result);
  }

  /**
   * @test
   */
  public function minifyFileNonExistent()
  {
    $result = JSMin::minifyFile('/arst/test/arst/test/arst.js');
    $this->assertSame('/arst/test/arst/test/arst.js', $result);
  }

  /**
   * @test
   */
  public function minifyFile()
  {
    @unlink(self::$minifyInfoPath);
    @unlink(self::$testMinifiedPath);
    $result = JSMin::minifyFile(self::$testFilePath);

    $this->assertNotSame(self::$testFilePath, $result);
    $this->assertSame(['minPath' => self::$testMinifiedPath, 'temporary' => true], $result);
    $this->assertTrue(file_exists($this->get('\Gustavus\Resources\JSMin', 'stagingDir') . basename(self::$testMinifiedPath)));
  }

  /**
   * @test
   */
  public function minifyFileNoTempFiles()
  {
    $this->set('\Gustavus\Resources\JSMin', 'saveTemporaryFile', false);
    @unlink(self::$minifyInfoPath);
    @unlink(self::$testMinifiedPath);
    $result = JSMin::minifyFile(self::$testFilePath);

    $this->assertNotSame(self::$testFilePath, $result);
    $this->assertSame(self::$testMinifiedPath, $result);
    $this->assertTrue(file_exists($this->get('\Gustavus\Resources\JSMin', 'stagingDir') . basename(self::$testMinifiedPath)));
    $this->set('\Gustavus\Resources\JSMin', 'saveTemporaryFile', true);
  }

  /**
   * @test
   * @dependsOn minifyFile
   */
  public function minifyFileAlreadyExists()
  {
    $this->minifyFile();
    unlink(self::$testMinifiedPath . JSMin::TEMPORARY_FLAG_EXT);
    $this->assertTrue(file_exists(self::$minifyInfoPath));
    $fileMTime = filemtime(self::$minifyInfoPath);

    $result = JSMin::minifyFile(self::$testFilePath);

    $this->assertNotSame(self::$testFilePath, $result);
    $this->assertSame(self::$testMinifiedPath, $result);
    $this->assertTrue(file_exists($this->get('\Gustavus\Resources\JSMin', 'stagingDir') . basename(self::$testMinifiedPath)));
  }

  /**
   * @test
   * @dependsOn minifyFile
   */
  public function minifyFileAlreadyExistsWithDifferentOptions()
  {
    $this->minifyFile();
    set_error_handler([$this, 'handleNotice'], E_USER_NOTICE);
    $this->assertTrue(file_exists(self::$minifyInfoPath));
    $fileMTime = filemtime(self::$minifyInfoPath);

    copy(self::$testFilePath, self::$testMinifiedPath);
    $result = JSMin::minifyFile(self::$testFilePath, ['compilation_level' => 'WHITESPACE_ONLY']);
    $this->assertTrue($this->errorTriggered);
    $this->assertContains('already been minified with different options', $this->errorString);

    $this->assertSame(self::$testFilePath, $result);
    restore_error_handler();
  }

  /**
   * @test
   */
  public function minifyFileWithTemporaryFile()
  {
    @unlink(self::$minifyInfoPath);
    @unlink(self::$testMinifiedPath);
    $result = JSMin::minifyFile(self::$testFilePath);
    $this->assertSame(['minPath' => self::$testMinifiedPath, 'temporary' => true], $result);
    $this->assertTrue(file_exists(self::$testMinifiedPath . JSMin::TEMPORARY_FLAG_EXT));
    $result = JSMin::minifyFile(self::$testFilePath);

    $this->assertSame(['minPath' => self::$testMinifiedPath, 'temporary' => true], $result);
    $this->assertTrue(file_exists($this->get('\Gustavus\Resources\JSMin', 'stagingDir') . basename(self::$testMinifiedPath)));
    // now remove the temp file and make sure we get a non-temp resource back
    unlink(self::$testMinifiedPath . JSMin::TEMPORARY_FLAG_EXT);
    $result = JSMin::minifyFile(self::$testFilePath);

    $this->assertSame(self::$testMinifiedPath, $result);
    $this->assertTrue(file_exists($this->get('\Gustavus\Resources\JSMin', 'stagingDir') . basename(self::$testMinifiedPath)));
  }

  /**
   * @test
   */
  public function bundle()
  {
    $resources = ['/cis/lib/Gustavus/Resources/Test/files/arst.js', '/cis/lib/Gustavus/Resources/Test/files/test.js'];
    $result = JSMin::bundle($resources, [1, 1]);

    $file = 'testBNDL-' . md5(implode(',', $resources)) . '.js';
    $this->assertContains($file, $result);
    $this->assertTrue(file_exists(JSMin::$minifiedFolder . $file));
    $this->assertNotContains('/**', file_get_contents(JSMin::$minifiedFolder . $file));
  }

  /**
   * @test
   */
  public function bundleNoMinify()
  {
    $resources = ['/cis/lib/Gustavus/Resources/Test/files/test.js', '/cis/lib/Gustavus/Resources/Test/files/arst.js'];
    $result = JSMin::bundle($resources, [1, 1], false);

    $file = 'arstBNDL-' . md5(implode(',', $resources)) . '.js';
    $this->assertContains($file, $result);
    $this->assertTrue(file_exists(JSMin::$minifiedFolder . $file));
    $this->assertContains('/**', file_get_contents(JSMin::$minifiedFolder . $file));
  }

  /**
   * @test
   */
  public function bundleWithExistingInfoFile()
  {
    file_put_contents(JSMin::$minifiedFolder . JSMin::$minifyInfoFile, json_encode([]));
    $resources = ['/cis/lib/Gustavus/Resources/Test/files/arst.js', '/cis/lib/Gustavus/Resources/Test/files/test.js'];
    $result = JSMin::bundle($resources, [1, 1]);

    $file = 'testBNDL-' . md5(implode(',', $resources)) . '.js';
    $this->assertContains($file, $result);
    $this->assertTrue(file_exists(JSMin::$minifiedFolder . $file));
  }

  /**
   * @test
   */
  public function bundleReRun()
  {
    $resources = ['/cis/lib/Gustavus/Resources/Test/files/arst.js', '/cis/lib/Gustavus/Resources/Test/files/test.js'];
    $result = JSMin::bundle($resources, $resources, [1, 1]);

    $file = 'testBNDL-' . md5(implode(',', $resources)) . '.js';
    $this->assertContains($file, $result);
    $minPath = JSMin::$minifiedFolder . $file;
    $this->assertTrue(file_exists($minPath));
    // change our file so we can verify that we didn't regenerate it.
    file_put_contents($minPath, file_get_contents($minPath) . 'arstAddition');

    $result = JSMin::bundle($resources, $resources, [1, 1]);

    $this->assertContains($file, $result);
    $this->assertTrue(file_exists($minPath));
    // make sure our file wasn't re-generated
    $this->assertContains('arstAddition', file_get_contents($minPath));
  }
}