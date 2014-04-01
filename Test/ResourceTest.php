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
      ['https://static-beta2.gac.edu/min/f=/js/imageFill.js?v=' . (Resources\Config::IMAGE_FILL_JS_VERSION - 0), ['imageFill']],
      ['https://static-beta2.gac.edu/js/imageFill.js?v=' . (Resources\Config::IMAGE_FILL_JS_VERSION - 0), 'imageFill', false],
      ['https://static-beta2.gac.edu/min/f=/js/imageFill.js,/js/tinymce-config.js?v=' . (Resources\Config::IMAGE_FILL_JS_VERSION + Resources\Config::TINYMCE_CONFIG_VERSION - 1), ['imagefill', 'tinyMCEConfig']],
      ['https://static-beta2.gac.edu/min/f=/js/formBuilder.js?v=1', ['path' => '/js/formBuilder.js', 'version' => 1]],
      ['https://static-beta2.gac.edu/min/f=/js/formBuilder.js?v=1', ['path' => '/js/formBuilder.js']],
      ['https://static-beta2.gac.edu/min/f=/js/arst.js,/js/formBuilder.js?v=2', [['path' => '/js/arst.js', 'version' => 2], ['path' => '/js/formBuilder.js', 'version' => 1]]],
      ['https://static-beta2.gac.edu/min/f=/js/arst.js,/js/formBuilder.js?v=2', [['path' => '/js/arst.js', 'version' => 2], ['path' => '/js/formBuilder.js']]],

    ];
  }

  /**
   * @test
   */
  public function renderCSS()
  {
    $resource = ['path' => '/js/plugins/helpbox/helpbox.css'];
    $options['doc_root'] = '/cis/www/';
    $actual = Resources\Resource::renderCSS($resource, true, $options);
    $this->assertTrue(strpos($actual, 'https://static-beta2.gac.edu/js/plugins/helpbox/helpbox.crush.css') !== false);
    $this->assertGreaterThanOrEqual(2, strpos($actual, 'crush'));
    $this->assertGreaterThanOrEqual(2, strpos($actual, '?'));
  }

  /**
   * @test
   */
  public function renderCSSMultiple()
  {
    $resource = [['path' => '/js/plugins/helpbox/helpbox.css'], ['path' => '/js/plugins/helpbox/helpbox.css']];
    $options['doc_root'] = '/cis/www/';
    $actual = Resources\Resource::renderCSS($resource, true, $options);
    $this->assertTrue(strpos($actual, 'https://static-beta2.gac.edu/min/f=/js/plugins/helpbox/helpbox.crush.css,/js/plugins/helpbox/helpbox.crush.css') !== false);
    $this->assertGreaterThanOrEqual(2, strpos($actual, 'crush'));
    $this->assertGreaterThanOrEqual(2, strpos($actual, '?'));
  }

  /**
   * @test
   */
  public function crushify()
  {
    $resource = ['path' => '/cis/lib/Gustavus/Resources/Test/files/test.css'];
    $options  = ['doc_root' => '/cis/lib/'];

    $actual = $this->call('Gustavus\Resources\Resource', 'crushify', [$resource, true, $options]);
    $this->assertContains('test.crush.css', $actual);
  }

  /**
   * @test
   */
  public function crushifyNoUrlify()
  {
    $resource = ['path' => '/cis/lib/Gustavus/Resources/Test/files/test.css'];
    $options  = ['doc_root' => '/cis/lib/'];

    $actual = $this->call('Gustavus\Resources\Resource', 'crushify', [$resource, true, $options, false]);
    $this->assertTrue(is_array($actual));
    $this->assertContains('test.crush.css', $actual['path']);
  }

  /**
   * @test
   */
  public function crushifyInline()
  {
    $resource = ['path' => '/cis/lib/Gustavus/Resources/Test/files/test.css'];
    $options  = ['doc_root' => '/cis/lib/', 'crushMethod' => 'inline'];

    $actual = $this->call('Gustavus\Resources\Resource', 'crushify', [$resource, true, $options]);
    $this->assertContains('<style', $actual);
    $this->assertContains('#testing', $actual);
  }
}