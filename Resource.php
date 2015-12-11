<?php
/**
 * @package Resources
 * @author  Billy Visto
 */
namespace Gustavus\Resources;
use Gustavus\Resources\Config,
  Gustavus\Resources\JSMin,
  Gustavus\Utility\Debug,
  Gustavus\Extensibility\Filters,
  Gustavus\Template\Request as TemplateRequest,
  DomainException;

/**
 * Class to render out resources that we need.
 *
 * @package Resources
 * @author  Billy Visto
 */
class Resource
{
  /**
   * Minifier prefix
   */
  const MIN_PREFIX = '/min/f=';

  /**
   * Extension to use for crushed css files
   */
  const CRUSH_EXTENSION = '.crush.css';

  /**
   * String to append to versions to signify a temporary version
   */
  const TEMP_VERSION = 'tmp';

  /**
   * Whether we are debugging or not
   *
   * @var boolean
   */
  public static $debug = false;

  /**
   * Default configurations for resources
   *
   * @var array
   */
  private static $defaultResources = [
    'imagefill'       => [
      'path' => '/js/imageFill.js', 'version' => Config::IMAGE_FILL_JS_VERSION
    ],
    'tinymce'         => [
      'path' => '/js/tinymce4.2.2/jquery.tinymce.min.js', 'version' => Config::TINYMCE_VERSION
    ],
    'tinymceconfig'   => [
      'path' => '/js/Gustavus/TinyMCE.js', 'version' => Config::TINYMCE_CONFIG_VERSION
    ],
    'qtip'            => [
      'path' => '/js/jquery/qTip2/dist/jquery.qtip.min.js', 'version' => Config::QTIP_VERSION
    ],
    'qtip-css'        => [
      'path' => '/js/jquery/qTip2/dist/jquery.qtip.min.css', 'version' => Config::QTIP_VERSION
    ],
    'helpbox'         => [
      'path' => '/template/js/plugins/helpbox/helpbox.js', 'version' => Config::HELPBOX_VERSION
    ],
    'helpbox-css'     => [
      'path' => '/template/js/plugins/helpbox/helpbox.css', 'version' => Config::HELPBOX_VERSION
    ],
    'crc32'           => [
      'path' => '/js/crc32.js', 'version' => Config::CRC32_VERSION
    ],
    'socialpopover'   => [
      'path' => '/template/js/plugins/socialPopover/socialPopover.js', 'version' => Config::SOCIAL_POPOVER_VERSION
    ],
    'socialpopover-css'        => [
      'path' => '/template/js/plugins/socialPopover/socialPopover.css', 'version' => Config::SOCIAL_POPOVER_VERSION
    ],
    'select2'         => [
      'path' => '/js/jquery/select2/select2.js', 'version' => Config::SELECT2_VERSION
    ],
    'select2-css'     => [
      [
        'path' => '/js/jquery/select2/select2.css',
        'version' => Config::SELECT2_VERSION,
      ],
      [
        'path' => '/js/Gustavus/css/select2.custom.css',
        'version' => Config::SELECT2_CUSTOM_CSS_VERSION,
        'crush' => true,
      ],
    ],
    'bxslider'        => [
      'path' => '/js/jquery/bxSlider/dist/jquery.bxslider.js', 'version' => Config::BXSLIDER_VERSION
    ],
    'bxslider-css'    => [
      ['path' => '/js/jquery/bxSlider/dist/jquery.bxslider.css', 'version' => Config::BXSLIDER_VERSION],
      ['path' => '/js/Gustavus/css/bxslider.custom.css', 'version' => Config::BXSLIDER_CUSTOM_CSS_VERSION],
    ],
    'urlutil'         => [
      'path' => '/js/Gustavus/Utility/url.js', 'version' => Config::URL_UTILITY_VERSION
    ],
    'dropdown'        => [
      'path' => '/js/Gustavus/jquery/Dropdown.js', 'version' => Config::DROPDOWN_VERSION
    ],
    'dropdown-css'    => [
      'path' => '/js/Gustavus/jquery/Dropdown.css', 'version' => Config::DROPDOWN_VERSION
    ],
    'isotope'         => [
      'path' => '/js/jquery/isotope/dist/isotope.pkgd.min.js', 'version' => Config::ISOTOPE_VERSION
    ],
    // ImagesLoaded is primarily used with Isotope to relayout when images finish loading.
    'imagesloaded'    => [
      'path' => '/js/jquery/imagesloaded/imagesloaded.pkgd.min.js', 'version' => Config::IMAGESLOADED_VERSION
    ],
    'player'          => [
      'path' => '/js/Gustavus/Player.js', 'version' => Config::PLAYER_VERSION
    ],
    'footable'        => [
      ['path' => '/js/jquery/FooTable/js/footable.js', 'version' => Config::FOOTABLE_JS_VERSION],
      ['path' => '/js/jquery/FooTable/js/footable.sort.js', 'version' => Config::FOOTABLE_JS_VERSION],
      ['path' => '/js/jquery/FooTable/dist/footable.filter.min.js', 'version' => Config::FOOTABLE_JS_VERSION],
      ['path' => '/js/jquery/FooTable/js/footable.striping.js', 'version' => Config::FOOTABLE_JS_VERSION],
      ['path' => '/js/jquery/FooTable/gac/footable.trimmer.js', 'version' => Config::FOOTABLE_JS_VERSION],
    ],
    'footable-css'    => [
      'path' => '/js/jquery/FooTable/gac/footable.gac.css', 'version' => Config::FOOTABLE_CSS_VERSION
    ],
  ];

  /**
   * Gets the path and version for a specific resource
   *
   * @param  string $resourceName
   * @return array|false Array of the path and the version number. False if the resource wasn't found.
   */
  private static function getResourceInfo($resourceName)
  {
    $resourceName = strtolower($resourceName);
    if (isset(self::$defaultResources[$resourceName])) {
      return self::$defaultResources[$resourceName];
    }
    return false;
  }

  /**
   * Renders out the link for the resource requested. If the resourse wasn't found, it will return an empty string
   *   Resource options:
   *   <ul>
   *     <li>path: Path to the resource</li>
   *     <li>version: Version of the resource</li>
   *     <li>crush: Whether to crush a css resource or not</li>
   *     <li>jsMinOptions: Options to pass to our js minifier</li>
   *   </ul>
   *
   * @param  string|array  $resourceName Either a single resource name, an array of resource names or resource info, or an array of resource info
   * @param  boolean $minified Whether we should minify the code or not
   * @param  boolean|array $cssCrush Whether we should pass this through cssCrush or not. Could be an array of options to pass to cssCrush.
   * @param  boolean $includeHost Whether to include the host in the returned url
   * @return string
   */
  public static function renderResource($resourceName, $minified = true, $cssCrush = true, $includeHost = true)
  {
    if (is_array($resourceName) && !array_key_exists('path', $resourceName) && count($resourceName) === 1) {
      // we have an array of arrays, but it only contains one nested array.
      $resourceName = current($resourceName);
    }
    if (is_array($resourceName)) {
      if (array_key_exists('path', $resourceName)) {
        // single resource with info
        $resource = $resourceName;
      } else {
        return self::renderResources($resourceName, $cssCrush, $includeHost);
      }
    } else {
      $resource = self::getResourceInfo($resourceName);

      if (is_array($resource) && !array_key_exists('path', $resource)) {
        // we have multiple resources in this default config
        return self::renderResources($resource, $cssCrush, $includeHost);
      }
    }

    if ($resource === false) {
      // resource not found.
      return '';
    }

    if (!isset($resource['version'])) {
      // make sure the resource has a version
      $resource['version'] = 1;
    }

    if (($cssCrush !== false && !isset($resource['crush']) || (isset($resource['crush']) && $resource['crush'])) && substr($resource['path'], -4) === '.css') {
      // css file. Let's pass this through css crush and return the crushed file
      unset($resource['crush']);
      return CSSMin::crushify($resource, $minified, $cssCrush, true, $includeHost);
    }

    if (self::$debug) {
      $debugArray = [];
    }
    if ($minified && self::allowMinification() && substr($resource['path'], -3) === '.js') {
      $opts = isset($resource['jsMinOptions']) ? $resource['jsMinOptions']: [];
      if (self::$debug) {
        $origPath = $resource['path'];
      }
      $minResult = JSMin::minifyFile($resource['path'], $opts);
      if (is_array($minResult)) {
        $resource['path'] = $minResult['minPath'];
        if (isset($minResult['temporary']) && $minResult['temporary']) {
          $resource['version'] = $resource['version'] . self::TEMP_VERSION;
        }
      } else {
        $resource['path'] = $minResult;
      }
      if (self::$debug) {
        $debugArray[$resource['path']] = $origPath;
      }
    }

    if (self::$debug && !empty($debugArray)) {
      Filters::add('body', function($content) use($debugArray) {
        return sprintf('<pre>%s</pre>', Debug::dump($debugArray, true)) . $content;
      });
    }

    return sprintf('%s%s?v=%s',
        ($includeHost ? self::determineHost() : ''),
        $resource['path'],
        $resource['version']
    );
  }

  /**
   * Renders out a bunch of resources using the minifier and adds the versions of all the resources up so that if you increment one version, the concatenated file will be incremented accordingly
   *
   * @param  array  $resourceNames Array of resource names, or an array of resource info arrays
   * @param  boolean|array $cssCrush Whether we should pass this through cssCrush or not. Could be an array of options to pass to cssCrush.
   * @param  boolean $includeHost Whether to include the host in the returned url
   * @return string
   */
  private static function renderResources(array $resourceNames, $cssCrush = true, $includeHost = true)
  {
    if ($includeHost) {
      $return  = self::determineHost();
    } else {
      $return = '';
    }
    $version = 0;
    $temporaryVersion = false;
    $lastKey = count($resourceNames) - 1;

    if (self::$debug) {
      $debugArray = [];
    }

    // flag for what type of resource we are rendering. Allows us to not mix css and js.
    $resourceType = '';
    // Storage for building our bundle information. Note, all three variables will have the same index for the same file.
    // paths to all the files we are rendering
    $resourcePaths    = [];
    $srcResourcePaths = [];
    // mTimes of the files we are rendering
    $resourceMTimes   = [];

    for ($i = 0; $i <= $lastKey; ++$i) {
      $resourceName = $resourceNames[$i];
      if (is_array($resourceName) && array_key_exists('path', $resourceName)) {
        // we have all the info we need already
        $resource = $resourceName;
      } else {
        // try to find the resource internally
        $resource = self::getResourceInfo($resourceName);
        if (is_array($resource) && !array_key_exists('path', $resource)) {
          // we got multiple resources for this resourceName
          if (count($resource) === 1) {
            $resource = $resource[0];
          } else {
            $firstResource = $resource[0];
            // add all the resources to our array to make sure they get included
            $resourceNames = array_merge(array_slice($resourceNames, 0, $i), $resource, array_slice($resourceNames, $i + 1));
            // we added resources, so we need to increment the lastKey variable
            $lastKey += count($resource) - 1;
            $resource = $firstResource;
          }
        }
      }
      if ($resource === false) {
        // resource not found. keep going
        continue;
      }
      // make sure we aren't cross rendering resource types
      if (substr($resource['path'], -4) === '.css') {
        // css file
        if (empty($resourceType)) {
          $resourceType = 'css';
        } else if ($resourceType !== 'css') {
          throw new DomainException('A css resource was attempted to be rendered with non css.');
        }
      } else if (substr($resource['path'], -3) === '.js') {
        // js file
        if (empty($resourceType)) {
          $resourceType = 'js';
        } else if ($resourceType !== 'js') {
          throw new DomainException('A js resource was attempted to be rendered with non js.');
        }
      }

      // this needs to be set before going through crush if it is a css resource
      $srcResourcePaths[] = $resource['path'];
      if ($resourceType === 'css') {
        // we first need to gather all of our resource files check modification times and determine if we need to rebuild or not.
        $renderedResource = '';
        if (($cssCrush !== false && !isset($resource['crush'])) || (isset($resource['crush']) && $resource['crush'])) {
          unset($resource['crush']);
          $resource = CSSMin::crushify($resource, true, $cssCrush, false, false);
        }

      }
      // now store our updated path and mtime
      if (file_exists(self::addDocRootToPath($resource['path']))) {
        $resourceMTimes[]   = filemtime(self::addDocRootToPath($resource['path']));
      } else {
        $resourceMTimes[] = 0;
      }
      $resourcePaths[]    = $resource['path'];

      if (!isset($resource['version'])) {
        // make sure the resource has a version
        $resource['version'] = 1;
      }
      $version += $resource['version'];
    }

    // we have all of our bundle information built. Now actually build the bundles.
    if ($resourceType === 'css') {
      $return .= CSSMin::bundle($resourcePaths, $srcResourcePaths, $resourceMTimes, self::allowMinification());
    } else if ($resourceType === 'js') {
      $return .= JSMin::bundle($resourcePaths, $resourceMTimes, self::allowMinification());
    } else {
      // we should never get here, but just in case.
      throw new DomainException(sprintf('The resource type of %s is not supported.', $resourceType));
    }
    if (self::$debug) {
      $debugArray[basename($return)] = [
        'srcPaths'      => $srcResourcePaths,
        'resourcePaths' => $resourcePaths,
      ];
    }

    // subract the number of files -1 from the version
    // If we have 5 files of version 1, we want the version to be 1.
    // If one file increments to version 2, we want it to be version 2.
    // If two files increment to verison 2, it will be version 3.
    $return .= '?v=' . ($version - $lastKey);
    if ($temporaryVersion) {
      $return .= self::TEMP_VERSION;
    }
    if (self::$debug && !empty($debugArray)) {
      Filters::add('body', function($content) use($debugArray) {
        return sprintf('<pre>%s</pre>', Debug::dump($debugArray, true)) . $content;
      });
    }
    return $return;
  }

  /**
   * Renders out a crushed css resource.
   * This requires that the location of the css file is writeable by httpd
   *
   * @param  string|array  $resourceName Either a single resource name, an array of resource names or resource info, or an array of resource info
   * @param  boolean $minified Whether we should minify the code or not
   * @param  array|boolean $cssCrushOptions Options to pass onto cssCrush
   * @param  boolean $includeHost Whether to include the host in the returned url
   * @return string
   */
  public static function renderCSS($resourceName, $minified = true, $cssCrushOptions = true, $includeHost = true)
  {
    return self::renderResource($resourceName, $minified, $cssCrushOptions, $includeHost);
  }

  /**
   * Determines what host to use based on the current server
   *
   * @return string
   */
  public static function determineHost()
  {
    if (\Config::isBeta()) {
      if (\Config::isAlpha()) {
        return 'https://homer.gac.edu';
      }
      return 'https://static-beta2.gac.edu';
    } else if (\Config::isBlog()) {
      return 'https://blog.gustavus.edu';
    } else {
      return 'https://static2.gac.edu';
    }
  }

  /**
   * Adds the DOC_ROOT to the filepath
   *
   * @param string $filePath Path of the file to add the doc root to
   * @return string
   */
  public static function addDocRootToPath($filePath)
  {
    return str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'] . $filePath);
  }

  /**
   * Whether or not we want to minify resources
   *
   * @return boolean
   */
  public static function allowMinification()
  {
    return !\Config::isBeta();
  }
}

if (TemplateRequest::useDebug()) {
  Resource::$debug = true;
}