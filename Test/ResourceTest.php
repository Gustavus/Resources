<?php
/**
 * @package Resources
 * @subpackage Tests
 */

namespace Gustavus\Resources\Test;
use \Gustavus\Resources;

/**
 * @package Resources
 * @subpackage Tests
 */
class ResourcesTest extends \Gustavus\Test\Test
{
  /**
   * sets up the object for each test
   * @return void
   */
  public function setUp()
  {
  }

  /**
   * destructs the object after each test
   * @return void
   */
  public function tearDown()
  {
  }

  /**
   * @test
   */
  public function getResourceInfo()
  {
    $expected = ['path' => '/js/imageFill.js', 'version' => Resources\Config::IMAGE_FILL_JS_VERSION];
    $actual = $this->call('\Gustavus\Resources\Resource', 'getResourceInfo', array('imagefill'));
    $this->assertSame($expected, $actual);
  }

  /**
   * @test
   * @dataProvider renderResourceData
   */
  public function renderResource($expected, $resource, $minified = true)
  {
    $this->assertSame($expected, Resources\Resource::renderResource($resource, $minified));
  }

  /**
   * data provider for renderResource
   */
  public function renderResourceData()
  {
    return [
      ['/min/f=/js/imageFill.js&amp;' . Resources\Config::IMAGE_FILL_JS_VERSION, 'imageFill'],
      ['/min/f=/js/imageFill.js&amp;' . Resources\Config::IMAGE_FILL_JS_VERSION, 'imagefill'],
      ['/min/f=/js/tinymce-config.js&amp;' . Resources\Config::TINYMCE_CONFIG_VERSION, 'tinyMCEConfig'],
      // non minified
      ['/js/imageFill.js', 'imageFill', false],
      ['/js/imageFill.js', 'imagefill', false],
      ['/js/tinymce-config.js', 'tinyMCEConfig', false],
    ];
  }
}