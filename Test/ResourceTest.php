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
   * Token for overriding methods
   * @var mixed
   */
  private $overrideToken;

  /**
   * sets up the object for each test
   * @return void
   */
  public function setUp()
  {
    $this->overrideToken = override_method('\Gustavus\Resources\Resource', 'allowMinification', function() {return true;});
  }

  /**
   * destructs the object after each test
   * @return void
   */
  public function tearDown()
  {
    unset($this->overrideToken);
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
  public function renderResource($expected, $resources, $minified = true, $includeHost = true)
  {
    $this->assertSame($expected, Resources\Resource::renderResource($resources, $minified, false, $includeHost));
  }

  /**
   * data provider for renderResource
   */
  public function renderResourceData()
  {
    return [
      ['https://static-beta2.gac.edu/min/f=/js/imageFill.js?v=' . (Resources\Config::IMAGE_FILL_JS_VERSION - 0), ['imageFill']],
      ['https://static-beta2.gac.edu/js/imageFill.js?v=' . (Resources\Config::IMAGE_FILL_JS_VERSION - 0), 'imageFill', false],
      ['https://static-beta2.gac.edu/min/f=/js/imageFill.js,/js/Gustavus/TinyMCE.js?v=' . (Resources\Config::IMAGE_FILL_JS_VERSION + Resources\Config::TINYMCE_CONFIG_VERSION - 1), ['imagefill', 'tinyMCEConfig']],
      ['https://static-beta2.gac.edu/min/f=/js/formBuilder.js?v=1', ['path' => '/js/formBuilder.js', 'version' => 1]],
      ['https://static-beta2.gac.edu/min/f=/js/formBuilder.js?v=1', ['path' => '/js/formBuilder.js']],
      ['https://static-beta2.gac.edu/min/f=/js/arst.js,/js/formBuilder.js?v=2', [['path' => '/js/arst.js', 'version' => 2], ['path' => '/js/formBuilder.js', 'version' => 1]]],
      ['https://static-beta2.gac.edu/min/f=/js/arst.js,/js/formBuilder.js?v=2', [['path' => '/js/arst.js', 'version' => 2], ['path' => '/js/formBuilder.js']]],
      ['/min/f=/js/arst.js?v=2', ['path' => '/js/arst.js', 'version' => 2], true, false],
      ['/min/f=/js/arst.js,/js/formBuilder.js?v=2', [['path' => '/js/arst.js', 'version' => 2], ['path' => '/js/formBuilder.js']], true, false],

    ];
  }

  /**
   * @test
   */
  public function renderResourcesWithSubArrays()
  {
    $original = $this->get('\Gustavus\Resources\Resource', 'defaultResources');
    $this->set('\Gustavus\Resources\Resource', 'defaultResources', ['select2' => [['path' => '/js/jquery/select2/select2.css', 'version' => 1], ['path' => '/js/Gustavus/select2.custom.css', 'version' => 1]]]);
    $expected = 'https://static-beta2.gac.edu/min/f=/js/jquery/select2/select2.css,/js/Gustavus/select2.custom.css?v=1';
    $this->assertSame($expected, Resources\Resource::renderResource(['select2']));
    $this->set('\Gustavus\Resources\Resource', 'defaultResources', $original);
  }

  /**
   * @test
   */
  public function renderResourceBeta()
  {
    // test that our beta resources aren't minified
    $this->tearDown();
    $expected = 'https://static-beta2.gac.edu/min/f=/js/imageFill.js?v=' . (Resources\Config::IMAGE_FILL_JS_VERSION - 0) . '&m=false';
    $this->assertSame($expected, Resources\Resource::renderResource('imageFill'));

    // not minifying, and we don't need to, won't go through the minifier
    $this->assertSame('https://static-beta2.gac.edu/js/imageFill.js?v=' . (Resources\Config::IMAGE_FILL_JS_VERSION - 0), Resources\Resource::renderResource('imageFill', false));

    $expected = 'https://static-beta2.gac.edu/min/f=/js/arst.js,/js/formBuilder.js?v=2&m=false';
    $this->assertSame($expected, Resources\Resource::renderResource([['path' => '/js/arst.js', 'version' => 2], ['path' => '/js/formBuilder.js']]));

  }

  /**
   * @test
   */
  public function renderCSS()
  {
    $resource = ['path' => '/template/js/plugins/helpbox/helpbox.css'];
    $options['doc_root'] = '/cis/www/';
    $actual = Resources\Resource::renderCSS($resource, true, $options);
    $this->assertSame('https://static-beta2.gac.edu/template/js/plugins/helpbox/helpbox.crush.css?v=1', $actual);
  }

  /**
   * @test
   */
  public function renderCSSArray()
  {
    $resource = ['path' => '/template/js/plugins/helpbox/helpbox.css'];
    $options['doc_root'] = '/cis/www/';
    $actual = Resources\Resource::renderCSS([$resource], true, $options);
    $this->assertSame('https://static-beta2.gac.edu/template/js/plugins/helpbox/helpbox.crush.css?v=1', $actual);
  }

  /**
   * @test
   */
  public function renderCSSArrayDontCrush()
  {
    $resource = ['path' => '/template/js/plugins/helpbox/helpbox.css'];
    $actual = Resources\Resource::renderCSS([$resource], true, false);
    $this->assertSame('https://static-beta2.gac.edu/template/js/plugins/helpbox/helpbox.css?v=1', $actual);
  }

  /**
   * @test
   */
  public function renderCSSArrayDontCrushOverride()
  {
    $resource = ['path' => '/template/js/plugins/helpbox/helpbox.css', 'crush' => true];
    $actual = Resources\Resource::renderCSS([$resource], true, false);
    $this->assertSame('https://static-beta2.gac.edu/template/js/plugins/helpbox/helpbox.crush.css?v=1', $actual);
  }

  /**
   * @test
   */
  public function renderResourceCrush()
  {
    $resource = ['path' => '/template/js/plugins/helpbox/helpbox.css', 'crush' => true];
    $actual = Resources\Resource::renderResource([$resource], true);
    $this->assertSame('https://static-beta2.gac.edu/min/f=/template/js/plugins/helpbox/helpbox.crush.css?v=1', $actual);
  }

  /**
   * @test
   */
  public function renderResourceCrushArrays()
  {
    $resource = [['path' => '/template/js/plugins/helpbox/helpbox.css'], ['path' => '/template/js/plugins/helpbox/helpbox.css']];
    $actual = Resources\Resource::renderResource($resource, true, true);
    $this->assertSame('https://static-beta2.gac.edu/min/f=/template/js/plugins/helpbox/helpbox.crush.css,/template/js/plugins/helpbox/helpbox.crush.css?v=1', $actual);
  }

  /**
   * @test
   */
  public function renderResourceCrushArraysCrushSettingOverride()
  {
    $resource = [['path' => '/template/js/plugins/helpbox/helpbox.css', 'crush' => true], ['path' => '/template/js/plugins/helpbox/helpbox.css']];
    $actual = Resources\Resource::renderResource($resource, true, false);
    $this->assertSame('https://static-beta2.gac.edu/min/f=/template/js/plugins/helpbox/helpbox.crush.css,/template/js/plugins/helpbox/helpbox.css?v=1', $actual);
  }

  /**
   * @test
   */
  public function renderResourceCrushDefaultArrayResource()
  {
    $original = $this->get('\Gustavus\Resources\Resource', 'defaultResources');
    $this->set('\Gustavus\Resources\Resource', 'defaultResources', [
        'select2' => [
          ['path' => '/js/jquery/select2/select2.css', 'version' => 1, 'crush' => true],
          ['path' => '/js/Gustavus/css/select2.custom.css', 'version' => 1]
        ],
        'qtip-css'        => [
          'path' => '/js/jquery/qTip2/dist/jquery.qtip.min.css'
        ]
    ]);
    $expected = 'https://static-beta2.gac.edu/min/f=/js/jquery/select2/select2.crush.css,/js/Gustavus/css/select2.custom.crush.css?v=1';
    $options['doc_root'] = '/cis/www/';
    $actual = Resources\Resource::renderResource('select2', true, true);
    $this->assertSame($expected, $actual);
    $this->assertGreaterThanOrEqual(2, strpos($actual, '?'));
    $this->set('\Gustavus\Resources\Resource', 'defaultResources', $original);
  }

  /**
   * @test
   */
  public function renderResourceDontCrushDefaultArrayResourceCrushOverride()
  {
    $original = $this->get('\Gustavus\Resources\Resource', 'defaultResources');
    $this->set('\Gustavus\Resources\Resource', 'defaultResources', [
        'select2' => [
          ['path' => '/js/jquery/select2/select2.css', 'version' => 1, 'crush' => true],
          ['path' => '/js/Gustavus/css/select2.custom.css', 'version' => 1]
        ],
        'qtip-css'        => [
          'path' => '/js/jquery/qTip2/dist/jquery.qtip.min.css'
        ]
    ]);
    $expected = 'https://static-beta2.gac.edu/min/f=/js/jquery/select2/select2.crush.css,/js/Gustavus/css/select2.custom.css?v=1';
    $options['doc_root'] = '/cis/www/';
    $actual = Resources\Resource::renderResource('select2', true, false);
    $this->assertSame($expected, $actual);
    $this->assertGreaterThanOrEqual(2, strpos($actual, '?'));
    $this->set('\Gustavus\Resources\Resource', 'defaultResources', $original);
  }


  /**
   * @test
   */
  public function renderResourceCrushDontCrush()
  {
    $resource = ['path' => '/template/js/plugins/helpbox/helpbox.css'];
    $actual = Resources\Resource::renderResource([$resource], true);
    $this->assertSame('https://static-beta2.gac.edu/min/f=/template/js/plugins/helpbox/helpbox.css?v=1', $actual);
  }

  /**
   * @test
   */
  public function renderCSSNoHost()
  {
    $resource = ['path' => '/template/js/plugins/helpbox/helpbox.css'];
    $options['doc_root'] = '/cis/www/';
    $actual = Resources\Resource::renderCSS($resource, true, $options, false);
    $this->assertSame('/template/js/plugins/helpbox/helpbox.crush.css?v=1', $actual);
    $this->assertGreaterThanOrEqual(2, strpos($actual, 'crush'));
    $this->assertGreaterThanOrEqual(2, strpos($actual, '?'));
  }

  /**
   * @test
   */
  public function renderCSSMultiple()
  {
    $resource = [['path' => '/template/js/plugins/helpbox/helpbox.css'], ['path' => '/template/js/plugins/helpbox/helpbox.css']];
    $options['doc_root'] = '/cis/www/';
    $actual = Resources\Resource::renderCSS($resource, true, $options);
    $this->assertTrue(strpos($actual, 'https://static-beta2.gac.edu/min/f=/template/js/plugins/helpbox/helpbox.crush.css,/template/js/plugins/helpbox/helpbox.crush.css') !== false);
    $this->assertGreaterThanOrEqual(2, strpos($actual, 'crush'));
    $this->assertGreaterThanOrEqual(2, strpos($actual, '?'));
  }

  /**
   * @test
   */
  public function renderCSSSubArrays()
  {

    $original = $this->get('\Gustavus\Resources\Resource', 'defaultResources');
    $this->set('\Gustavus\Resources\Resource', 'defaultResources', [
        'select2' => [
          ['path' => '/js/jquery/select2/select2.css', 'version' => 1, 'crush' => true],
          ['path' => '/js/Gustavus/select2.custom.css', 'version' => 1]
        ],
        'qtip-css'        => [
          'path' => '/js/jquery/qTip2/dist/jquery.qtip.min.css'
        ]
    ]);
    $expected = 'https://static-beta2.gac.edu/min/f=/js/jquery/qTip2/dist/jquery.qtip.min.css,/js/jquery/select2/select2.crush.css,/js/Gustavus/select2.custom.css,/template/js/plugins/helpbox/helpbox.crush.css?v=1';
    $options['doc_root'] = '/cis/www/';
    $actual = Resources\Resource::renderCSS(['qtip-css', 'select2', ['path' => '/template/js/plugins/helpbox/helpbox.css']], true, $options);
    $this->assertSame($expected, $actual);
    $this->assertGreaterThanOrEqual(2, strpos($actual, '?'));
    $this->set('\Gustavus\Resources\Resource', 'defaultResources', $original);
  }

  /**
   * @test
   */
  public function renderResourcesCSSSubArrays()
  {

    $original = $this->get('\Gustavus\Resources\Resource', 'defaultResources');
    $this->set('\Gustavus\Resources\Resource', 'defaultResources', [
        'select2' => [
          ['path' => '/js/jquery/select2/select2.css', 'version' => 1, 'crush' => true],
          ['path' => '/js/Gustavus/select2.custom.css', 'version' => 1]
        ],
        'qtip-css'        => [
          'path' => '/js/jquery/qTip2/dist/jquery.qtip.min.css'
        ]
    ]);
    $expected = 'https://static-beta2.gac.edu/min/f=/js/jquery/qTip2/dist/jquery.qtip.min.css,/js/jquery/select2/select2.crush.css,/js/Gustavus/select2.custom.css,/template/js/plugins/helpbox/helpbox.css?v=1';
    $options['doc_root'] = '/cis/www/';
    $actual = Resources\Resource::renderResource(['qtip-css', 'select2', ['path' => '/template/js/plugins/helpbox/helpbox.css']]);
    $this->assertSame($expected, $actual);
    $this->assertGreaterThanOrEqual(2, strpos($actual, '?'));
    $this->set('\Gustavus\Resources\Resource', 'defaultResources', $original);
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