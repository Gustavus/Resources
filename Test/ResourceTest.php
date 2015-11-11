<?php
/**
 * @package Resources
 * @subpackage Tests
 */

namespace Gustavus\Resources\Test;
use Gustavus\Resources,
  Gustavus\Resources\JSMin,
  Gustavus\Resources\CSSMin,
  Gustavus\Resources\Resource;

/**
 * @package Resources
 * @subpackage Tests
 */
class ResourceTest extends TestBase
{
  /**
   * sets up the object for each test
   * @return void
   */
  public function setUp()
  {
    $this->overrideToken['allowMinification'] = override_method('\Gustavus\Resources\Resource', 'allowMinification', function() {return true;});
    parent::setUp();
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
      [sprintf('https://static-beta2.gac.edu/cis/lib/Gustavus/Resources/Test/files/min/imageFill-%s.js?v=%s%s', md5('/cis/www/js/'), (Resources\Config::IMAGE_FILL_JS_VERSION - 0), Resource::TEMP_VERSION), ['imageFill']],
      ['https://static-beta2.gac.edu/js/imageFill.js?v=' . (Resources\Config::IMAGE_FILL_JS_VERSION - 0), 'imageFill', false],
      [sprintf('https://static-beta2.gac.edu/cis/lib/Gustavus/Resources/Test/files/min/TinyMCEBNDL-%s.js?v=%s', md5('/js/imageFill.js,/js/Gustavus/TinyMCE.js'), (Resources\Config::IMAGE_FILL_JS_VERSION + Resources\Config::TINYMCE_CONFIG_VERSION - 1)), ['imagefill', 'tinyMCEConfig']],
      ['https://static-beta2.gac.edu/js/formBuilder.js?v=1', ['path' => '/js/formBuilder.js', 'version' => 1]],
      ['https://static-beta2.gac.edu/js/formBuilder.js?v=1', ['path' => '/js/formBuilder.js']],
      [sprintf('https://static-beta2.gac.edu/cis/lib/Gustavus/Resources/Test/files/min/formBuilderBNDL-%s.js?v=2', md5('/js/arst.js,/js/formBuilder.js')), [['path' => '/js/arst.js', 'version' => 2], ['path' => '/js/formBuilder.js', 'version' => 1]]],
      [sprintf('https://static-beta2.gac.edu/cis/lib/Gustavus/Resources/Test/files/min/formBuilderBNDL-%s.js?v=2', md5('/js/arst.js,/js/formBuilder.js')), [['path' => '/js/arst.js', 'version' => 2], ['path' => '/js/formBuilder.js']]],
      ['/js/arst.js?v=2', ['path' => '/js/arst.js', 'version' => 2], true, false],
      [sprintf('/cis/lib/Gustavus/Resources/Test/files/min/formBuilderBNDL-%s.js?v=2', md5('/js/arst.js,/js/formBuilder.js')), [['path' => '/js/arst.js', 'version' => 2], ['path' => '/js/formBuilder.js']], true, false],

    ];
  }

  /**
   * @test
   */
  public function renderResourcesWithSubArrays()
  {
    $original = $this->get('\Gustavus\Resources\Resource', 'defaultResources');
    $this->set('\Gustavus\Resources\Resource', 'defaultResources', ['select2' => [['path' => '/js/jquery/select2/select2.css', 'version' => 1], ['path' => '/js/Gustavus/select2.custom.css', 'version' => 1]]]);
    $expectedName = sprintf('%sBNDL-%s.css', 'select2.custom', md5('/js/jquery/select2/select2.css,/js/Gustavus/select2.custom.css'));
    $expected = sprintf('https://static-beta2.gac.edu%s%s?v=1', CSSMin::$minifiedFolder, $expectedName);
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
    $expected = 'https://static-beta2.gac.edu/js/imageFill.js?v=' . (Resources\Config::IMAGE_FILL_JS_VERSION - 0);
    $this->assertSame($expected, Resources\Resource::renderResource('imageFill'));

    // not minifying, and we don't need to, won't go through the minifier
    $this->assertSame('https://static-beta2.gac.edu/js/imageFill.js?v=' . (Resources\Config::IMAGE_FILL_JS_VERSION - 0), Resources\Resource::renderResource('imageFill', false));

    $expected = sprintf('https://static-beta2.gac.edu/cis/lib/Gustavus/Resources/Test/files/min/formBuilderBNDL-%s.js?v=2', md5('/js/arst.js,/js/formBuilder.js'));
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
    $this->assertSame(sprintf('https://static-beta2.gac.edu%shelpbox-%s.css?v=1', CSSMin::$minifiedFolder, md5('/template/js/plugins/helpbox')), $actual);
  }

  /**
   * @test
   */
  public function renderCSSArray()
  {
    $resource = ['path' => '/template/js/plugins/helpbox/helpbox.css'];
    $options['doc_root'] = '/cis/www/';
    $actual = Resources\Resource::renderCSS([$resource], true, $options);
    $this->assertSame(sprintf('https://static-beta2.gac.edu%shelpbox-%s.css?v=1', CSSMin::$minifiedFolder, md5('/template/js/plugins/helpbox')), $actual);
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
    $this->assertSame(sprintf('https://static-beta2.gac.edu%shelpbox-%s.css?v=1', CSSMin::$minifiedFolder, md5('/template/js/plugins/helpbox')), $actual);
  }

  /**
   * @test
   */
  public function renderResourceCrush()
  {
    $resource = ['path' => '/template/js/plugins/helpbox/helpbox.css', 'crush' => true];
    $actual = Resources\Resource::renderResource([$resource], true);
    $this->assertSame(sprintf('https://static-beta2.gac.edu%shelpbox-%s.css?v=1', CSSMin::$minifiedFolder, md5('/template/js/plugins/helpbox')), $actual);
  }

  /**
   * @test
   */
  public function renderResourceCrushArrays()
  {
    $resource = [['path' => '/template/js/plugins/helpbox/helpbox.css'], ['path' => '/template/js/plugins/helpbox/helpbox.css']];
    $actual = Resources\Resource::renderResource($resource, true, true);
    $expectedCrushedPaths = sprintf('%s,%s',
        sprintf('%shelpbox-%s.css', CSSMin::$minifiedFolder, md5('/template/js/plugins/helpbox')),
        sprintf('%shelpbox-%s.css', CSSMin::$minifiedFolder, md5('/template/js/plugins/helpbox'))
    );
    $expected = sprintf(
        'https://static-beta2.gac.edu%shelpboxBNDL-%s.css?v=1',
        CSSMin::$minifiedFolder,
        md5($expectedCrushedPaths)
    );
    $this->assertSame($expected, $actual);
  }

  /**
   * @test
   */
  public function renderResourceCrushArraysCrushSettingOverride()
  {
    $resource = [['path' => '/template/js/plugins/helpbox/helpbox.css', 'crush' => true], ['path' => '/template/js/plugins/helpbox/helpbox.css']];
    $actual = Resources\Resource::renderResource($resource, true, false);
    $expectedCrushedPaths = sprintf('%s,/template/js/plugins/helpbox/helpbox.css',
        sprintf('%shelpbox-%s.css', CSSMin::$minifiedFolder, md5('/template/js/plugins/helpbox'))
    );
    $expected = sprintf(
        'https://static-beta2.gac.edu%shelpboxBNDL-%s.css?v=1',
        CSSMin::$minifiedFolder,
        md5($expectedCrushedPaths)
    );
    $this->assertSame($expected, $actual);
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
    $expectedCrushedPaths = sprintf('%s,%s',
        sprintf('%sselect2-%s.css', CSSMin::$minifiedFolder, md5('/js/jquery/select2')),
        sprintf('%sselect2.custom-%s.css', CSSMin::$minifiedFolder, md5('/js/Gustavus/css'))
    );
    $expected = sprintf('https://static-beta2.gac.edu%sselect2.customBNDL-%s.css?v=1',
        CSSMin::$minifiedFolder,
        md5($expectedCrushedPaths)
    );
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
    $expectedCrushedPaths = sprintf('%s,/js/Gustavus/css/select2.custom.css',
        sprintf('%sselect2-%s.css', CSSMin::$minifiedFolder, md5('/js/jquery/select2'))
    );
    $expected = sprintf('https://static-beta2.gac.edu%sselect2.customBNDL-%s.css?v=1',
        CSSMin::$minifiedFolder,
        md5($expectedCrushedPaths)
    );
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
    $this->assertSame('https://static-beta2.gac.edu/template/js/plugins/helpbox/helpbox.css?v=1', $actual);
  }

  /**
   * @test
   */
  public function renderCSSNoHost()
  {
    $resource = ['path' => '/template/js/plugins/helpbox/helpbox.css'];
    $options['doc_root'] = '/cis/www/';
    $actual = Resources\Resource::renderCSS($resource, true, $options, false);
    $this->assertSame(sprintf('%shelpbox-%s.css?v=1', CSSMin::$minifiedFolder, md5('/template/js/plugins/helpbox')), $actual);
  }

  /**
   * @test
   */
  public function renderCSSMultiple()
  {
    $resource = [['path' => '/template/js/plugins/helpbox/helpbox.css'], ['path' => '/template/js/plugins/helpbox/helpbox.css']];
    $options['doc_root'] = '/cis/www/';
    $actual = Resources\Resource::renderCSS($resource, true, $options);
    $expectedCrushedPaths = sprintf('%s,%s',
        sprintf('%shelpbox-%s.css', CSSMin::$minifiedFolder, md5('/template/js/plugins/helpbox')),
        sprintf('%shelpbox-%s.css', CSSMin::$minifiedFolder, md5('/template/js/plugins/helpbox'))
    );
    $expected = sprintf(
        'https://static-beta2.gac.edu%shelpboxBNDL-%s.css?v=1',
        CSSMin::$minifiedFolder,
        md5($expectedCrushedPaths)
    );
    $this->assertSame($expected, $actual);
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
          ['path' => '/js/Gustavus/css/select2.custom.css', 'version' => 1]
        ],
        'qtip-css'        => [
          'path' => '/js/jquery/qTip2/dist/jquery.qtip.min.css'
        ]
    ]);
    $expectedCrushedPaths = sprintf('%s,%s,%s,%s',
        sprintf('%sjquery.qtip.min-%s.css', CSSMin::$minifiedFolder, md5('/js/jquery/qTip2/dist')),
        sprintf('%sselect2-%s.css', CSSMin::$minifiedFolder, md5('/js/jquery/select2')),
        sprintf('%sselect2.custom-%s.css', CSSMin::$minifiedFolder, md5('/js/Gustavus/css')),
        sprintf('%shelpbox-%s.css', CSSMin::$minifiedFolder, md5('/template/js/plugins/helpbox'))
    );
    $expected = sprintf(
        'https://static-beta2.gac.edu%shelpboxBNDL-%s.css?v=1',
        CSSMin::$minifiedFolder,
        md5($expectedCrushedPaths)
    );

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
    $crushName = sprintf('%sselect2-%s.css', CSSMin::$minifiedFolder, md5('/js/jquery/select2'));
    $expectedName = sprintf('helpboxBNDL-%s.css', md5(sprintf('/js/jquery/qTip2/dist/jquery.qtip.min.css,%s,/js/Gustavus/select2.custom.css,/template/js/plugins/helpbox/helpbox.css', $crushName)));
    //$expected = 'https://static-beta2.gac.edu/min/f=/js/jquery/qTip2/dist/jquery.qtip.min.css,/js/jquery/select2/select2.crush.css,/js/Gustavus/select2.custom.css,/template/js/plugins/helpbox/helpbox.css?v=1';
    $expected = sprintf('https://static-beta2.gac.edu%s%s?v=1', CSSMin::$minifiedFolder, $expectedName);
    $options['doc_root'] = '/cis/www/';
    $actual = Resources\Resource::renderResource(['qtip-css', 'select2', ['path' => '/template/js/plugins/helpbox/helpbox.css']]);
    $this->assertSame($expected, $actual);
    $this->assertGreaterThanOrEqual(2, strpos($actual, '?'));
    $this->set('\Gustavus\Resources\Resource', 'defaultResources', $original);
  }

  /**
   * @test
   */
  public function addDocRootToPath()
  {
    unset($this->overrideToken);
    $_SERVER['DOCUMENT_ROOT'] = '/cis/www';
    $path = '/resources/arst/';
    $this->assertSame('/cis/www/resources/arst/', Resource::addDocRootToPath($path));
  }

  /**
   * @test
   */
  public function addDocRootToPathWithTrailingSlashInDocRoot()
  {
    unset($this->overrideToken);
    $_SERVER['DOCUMENT_ROOT'] = '/cis/www/';
    $path = '/resources/arst/';
    $this->assertSame('/cis/www/resources/arst/', Resource::addDocRootToPath($path));
  }
}