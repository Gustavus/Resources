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

  /**
   * Gets the path and version for a specific resource
   *
   * @param  string $resourceName
   * @return array|false Array of the path and the version number. False if the resource wasn't found.
   */
  private static function getResourceInfo($resourceName)
  {
    switch (strtolower($resourceName)) {
      case 'imagefill' :
          return ['path' => '/js/imageFill.js', 'version' => Config::IMAGE_FILL_JS_VERSION];
      case 'tinymce' :
          return ['path' => '/js/tinymce4.1/jquery.tinymce.min.js', 'version' => Config::TINYMCE_VERSION];
      case 'tinymceconfig' :
          return ['path' => '/js/Gustavus/TinyMCE.js', 'version' => Config::TINYMCE_CONFIG_VERSION];
      case 'qtip' :
          return ['path' => '/js/jquery/qTip2/dist/jquery.qtip.min.js', 'version' => Config::QTIP_VERSION];
      case 'qtip-css' :
          return ['path' => '/js/jquery/qTip2/dist/jquery.qtip.min.css', 'version' => Config::QTIP_VERSION];
      case 'helpbox' :
          return ['path' => '/js/plugins/helpbox/helpbox.js', 'version' => Config::HELPBOX_VERSION];
      case 'helpbox-css' :
          return ['path' => '/js/plugins/helpbox/helpbox.css', 'version' => Config::HELPBOX_VERSION];
      case 'crc32' :
          return ['path' => '/js/crc32.js', 'version' => Config::CRC32_VERSION];
      case 'socialpopover' :
          return ['path' => '/js/plugins/socialMedia/socialPopover.js', 'version' => Config::SOCIAL_POPOVER_VERSION];
      case 'socialpopover-css' :
          return ['path' => '/js/plugins/socialMedia/socialPopover.css', 'version' => Config::SOCIAL_POPOVER_VERSION];
      case 'select2' :
          return ['path' => '/js/jquery/select2/select2.js', 'version' => Config::SELECT2_VERSION];
      case 'select2-css' :
          return ['path' => '/js/jquery/select2/select2.css', 'version' => Config::SELECT2_VERSION];
      case 'bxslider':
          return ['path' => '/js/jquery/bxSlider/jquery.bxslider.js', 'version' => Config::BXSLIDER_VERSION];
      case 'bxslider-css':
          return ['path' => '/js/jquery/bxSlider/jquery.bxslider.css', 'version' => Config::BXSLIDER_VERSION];
      case 'urlutil':
          return ['path' => '/js/Gustavus/Utility/url.js', 'version' => Config::URL_UTILITY_VERSION];
      case 'dropdown':
          return ['path' => '/js/Gustavus/jquery/Dropdown.js', 'version' => Config::DROPDOWN_VERSION];
      case 'dropdown-css':
          return ['path' => '/js/Gustavus/jquery/Dropdown.css', 'version' => Config::DROPDOWN_VERSION];
      case 'isotope':
          return ['path' => '/js/jquery/isotope/dist/isotope.pkgd.min.js', 'version' => Config::ISOTOPE_VERSION];
      case 'imagesloaded':
          return ['path' => '/js/jquery/imagesloaded/imagesloaded.pkgd.min.js', 'version' => Config::IMAGESLOADED_VERSION];

      default :
          return false;
    }
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

      $crushResult = \CssCrush\CssCrush::{$crushMethod}($resource['path'], $cssCrushOptions);

      if ($crushMethod !== 'file') {
        return $crushResult;
      }
    }

    if (!isset($resource['version'])) {
      // make sure the resource has a version
      $resource['version'] = 1;
    }

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
          $crushedResources[] = self::getResourceInfo($resource);
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

    foreach ($resourceNames as $key => $resourceName) {
      if (is_array($resourceName) && array_key_exists('path', $resourceName)) {
        // we have all the info we need already
        $resource = $resourceName;
      } else {
        // try to find the resource internally
        $resource = Resource::getResourceInfo($resourceName);
      }
      if ($resource === false) {
        // resource not found. keep going
        continue;
      }

      if ($key === $lastKey) {
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