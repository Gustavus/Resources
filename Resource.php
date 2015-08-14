<?php
/**
 * @package Resources
 * @author  Billy Visto
 */
namespace Gustavus\Resources;
use Gustavus\Resources\Config;

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
      'path' => '/js/jquery/bxSlider/jquery.bxslider.js', 'version' => Config::BXSLIDER_VERSION
    ],
    'bxslider-css'    => [
      'path' => '/js/jquery/bxSlider/jquery.bxslider.css', 'version' => Config::BXSLIDER_VERSION
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
    // 'datatables'   => ['path' => '/js/jquery/DataTables/media/js/jquery.dataTables.js', 'version' => 1],
    // 'datatables-responsive' => ['path' => '/js/jquery/DataTables/plugins/Responsive/js/dataTables.responsive.js', 'version' => 1],
    'footable'        => [
      'path' => '/js/jquery/FooTable/js/footable.js', 'version' => 1
    ],
    'footable-sort'   => [
      'path' => '/js/jquery/FooTable/js/footable.sort.js', 'version' => 1
    ],
    'footable-filter' => [
      'path' => '/js/jquery/FooTable/dist/footable.filter.min.js', 'version' => 1
    ],
    'footable-stripe' => [
      'path' => '/js/jquery/FooTable/js/footable.striping.js', 'version' => 1
    ],
    'footable-trimmer'         => [
      'path' => '/js/jquery/FooTable/gac/footable.trimmer.js', 'version' => 1
    ],
    'footable-css'    => [
      'path' => '/js/jquery/FooTable/gac/footable.gac.css', 'version' => 1
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
   *
   * @param  string|array  $resourceName Either a single resource name, an array of resource names or resource info, or an array of resource info
   * @param  boolean $minified Whether we should minify the code or not
   * @param  boolean|array $cssCrush Whether we should pass this through cssCrush or not. Could be an array of options to pass to cssCrush.
   * @return string
   */
  public static function renderResource($resourceName, $minified = true, $cssCrush = false)
  {
    if (is_array($resourceName)) {
      if (array_key_exists('path', $resourceName)) {
        // single resource with info
        $resource = $resourceName;
      } else {
        return Resource::renderResources($resourceName);
      }
    } else {
      $resource = Resource::getResourceInfo($resourceName);

      if (is_array($resource) && !array_key_exists('path', $resource)) {
        // we have multiple resources in this default config
        return Resource::renderResources($resource);
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

    if ($cssCrush !== false && substr($resource['path'], -4) === '.css') {
      // css file. Let's pass this through css crush and return the crushed file
      return self::crushify($resource, $minified, $cssCrush);
    }

    if ($minified || strpos($resource['path'], ',') !== false) {
      return sprintf('%s%s%s?v=%s%s',
          self::determineHost(),
          Resource::MIN_PREFIX,
          $resource['path'],
          $resource['version'],
          (!self::allowMinification() ? '&m=false' : '')
      );
    } else {
      return sprintf('%s%s?v=%s',
          self::determineHost(),
          $resource['path'],
          $resource['version']
      );
    }
  }

  /**
   * CssCrushifies a single resource
   *
   * @param  array    $resource   array of resource config with keys of path and version
   * @param  boolean  $minified   whether or not to minify the resource
   * @param  array|boolean  $additionalOpts Additional options to pass to css crush.
   *   Key of 'crushMethod' is used internally to determine how to crush the file. File option returns the filename, inline returns html containing all of the styles. Defaults to file
   *   More options can be found in <a href="http://the-echoplex.net/csscrush/#api--options">crush's documentation</a>.
   * @param  boolean  $urlify     whether or not to return the url version or the array version of this resource
   * @return string|array String if $urlify is true, otherwise an array.
   */
  private static function crushify($resource, $minified, $additionalOpts, $urlify = true)
  {
    if (!\Config::isBlog()) {
      // we don't want to crush files as the blog's httpd user due to permission issues, and doc_root issues.
      if (!self::allowMinification()) {
        // we don't want to minify anything
        $minified = false;
      }

      if (isset($additionalOpts['crushMethod']) && in_array($additionalOpts['crushMethod'], ['file', 'inline'])) {
        $crushMethod = $additionalOpts['crushMethod'];
      } else {
        $crushMethod = 'file';
      }

      $cssCrushOptions = ['minify' => $minified, 'versioning' => false, 'doc_root' => '/cis/www'];

      if (is_array($additionalOpts)) {
        $cssCrushOptions = array_merge($cssCrushOptions, $additionalOpts);
      }
      if (isset($cssCrushOptions['vars'])) {
        $cssCrushOptions['vars'] = array_merge($cssCrushOptions['vars'], Config::$globalCrushVariables);
      } else {
        $cssCrushOptions['vars'] = Config::$globalCrushVariables;
      }

      $crushResult = \CssCrush\CssCrush::{$crushMethod}($resource['path'], $cssCrushOptions);

      if ($crushMethod !== 'file') {
        return $crushResult;
      }
    }

    if (!isset($resource['version'])) {
      // make sure the resource has a version
      $resource['version'] = 1;
    }
    // add the version of our global variables so the browser doesn't use a version with old variables
    $resource['version'] += Config::GLOBAL_CRUSH_VARIABLES_VERSION;

    if ($urlify) {
      return sprintf('%s%s?v=%s',
          self::determineHost(),
          str_replace('.css', '.crush.css', $resource['path']),
          $resource['version']
      );
    } else {
      // we don't want this resource urlified.
      $resource['path'] = str_replace('.css', '.crush.css', $resource['path']);
      return $resource;
    }
  }

  /**
   * Renders out a crushed css resource.
   * This requires that the location of the css file is writeable by httpd
   *
   * @param  string|array  $resourceName Either a single resource name, an array of resource names or resource info, or an array of resource info
   * @param  boolean $minified Whether we should minify the code or not
   * @param  array|boolean $cssCrushOptions Options to pass onto cssCrush
   * @return string
   */
  public static function renderCSS($resourceName, $minified = true, $cssCrushOptions = true)
  {
    if (is_array($resourceName) && !array_key_exists('path', $resourceName)) {
      // working with multiple css resources
      $crushedResources = [];
      foreach ($resourceName as $resource) {
        if (!is_array($resource)) {
          $defaultResources = self::getResourceInfo($resource);
          if (!is_array($defaultResources) || array_key_exists('path', $defaultResources)) {
            $defaultResources = [$defaultResources];
          }

          foreach ($defaultResources as $defaultResource) {
            if (isset($defaultResource['crush']) && $defaultResource['crush']) {
              $defaultResource = self::crushify($defaultResource, $minified, $cssCrushOptions, false);
            }
            $crushedResources[] = $defaultResource;
          }
        } else {
          $crushedResources[] = self::crushify($resource, $minified, $cssCrushOptions, false);
        }
      }
      return self::renderResources($crushedResources);
    }
    return Resource::renderResource($resourceName, $minified, $cssCrushOptions);
  }


  /**
   * Renders out a bunch of resources using the minifier and adds the versions of all the resources up so that if you increment one version, the concatenated file will be incremented accordingly
   *
   * @param  array  $resourceNames Array of resource names, or an array of resource info arrays
   * @return string
   */
  private static function renderResources(array $resourceNames)
  {
    $return  = self::determineHost() . Resource::MIN_PREFIX;
    $version = 0;
    $lastKey = count($resourceNames) - 1;

    for ($i = 0; $i <= $lastKey; ++$i) {
      $resourceName = $resourceNames[$i];
      if (is_array($resourceName) && array_key_exists('path', $resourceName)) {
        // we have all the info we need already
        $resource = $resourceName;
      } else {
        // try to find the resource internally
        $resource = Resource::getResourceInfo($resourceName);
        if (is_array($resource) && !array_key_exists('path', $resource)) {
          // we got multiple resources for this resourceName
          if (count($resource) === 1) {
            $resource = $resource[0];
          } else {
            $firstResource = $resource[0];
            // add all the resources to our array to make sure they get included
            $resourceNames = array_merge(array_splice($resourceNames, 0, $i - 1), $resource, array_splice($resourceNames, $i + 1));
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

      if ($i === $lastKey) {
        $return .= $resource['path'];
      } else {
        $return .= "{$resource['path']},";
      }
      if (!isset($resource['version'])) {
        // make sure the resource has a version
        $resource['version'] = 1;
      }
      $version += $resource['version'];
    }
    // subract the number of files -1 from the version
    // If we have 5 files of version 1, we want the version to be 1.
    // If one file increments to version 2, we want it to be version 2.
    // If two files increment to verison 2, it will be version 3.
    $return .= '?v=' . ($version - $lastKey);
    if (!self::allowMinification()) {
      $return .= '&m=false';
    }
    return $return;
  }

  /**
   * Determines what host to use based on the current server
   *
   * @return string
   */
  private static function determineHost()
  {
    if (\Config::isBeta()) {
      if (\Config::isAlpha()) {
        return 'https://homer.gac.edu';
      }
      return 'https://static-beta2.gac.edu';
    } else {
      return 'https://static2.gac.edu';
    }
  }

  /**
   * Whether or not we want to minify resources
   *
   * @return boolean
   */
  private static function allowMinification()
  {
    return !\Config::isBeta();
  }
}