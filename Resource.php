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
      case 'tinymceconfig' :
          return ['path' => '/js/tinymce-config.js', 'version' => Config::TINYMCE_CONFIG_VERSION];
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
      default :
          return false;
    }
  }

  /**
   * Renders out the link for the resource requested. If the resourse wasn't found, it will return an empty string
   *
   * @param  string|array  $resourceName Either a single resource name, an array of resource names or resource info, or an array of resource info
   * @param  boolean $minified Whether we should minify the code or not
   * @return string
   */
  public static function renderResource($resourceName, $minified = true)
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
    if ($minified) {
      return sprintf('%s%s?v=%s',
          Resource::MIN_PREFIX,
          $resource['path'],
          $resource['version']
      );
    } else {
      return sprintf('%s?v=%s',
          $resource['path'],
          $resource['version']
      );
    }
  }

  /**
   * Renders out a bunch of resources using the minifier and adds the versions of all the resources up so that if you increment one version, the concatenated file will be incremented accordingly
   *
   * @param  array  $resourceNames Array of resource names, or an array of resource info arrays
   * @return string
   */
  private static function renderResources(array $resourceNames)
  {
    $return = Resource::MIN_PREFIX;
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
    return $return;
  }
}