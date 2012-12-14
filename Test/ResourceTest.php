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
   * @dataProvider renderResourcesData
   */
  public function renderResources($expected, $resources, $minified = true)
  {
    $this->assertSame($expected, Resources\Resource::renderResource($resources, $minified));
  }

  /**
   * data provider for renderResources
   */
  public function renderResourcesData()
  {
    return [
      ['/min/f=/js/imageFill.js&amp;' . (Resources\Config::IMAGE_FILL_JS_VERSION - 0), ['imageFill']],
      ['/min/f=/js/imageFill.js,/js/tinymce-config.js&amp;' . (Resources\Config::IMAGE_FILL_JS_VERSION + Resources\Config::TINYMCE_CONFIG_VERSION - 1), ['imagefill', 'tinyMCEConfig']],
    ];
  }
}