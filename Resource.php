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
      default :
          return false;
    }
  }

  /**
   * Renders out the link for the resource requested. If the resourse wasn't found, it will return an empty string
   *
   * @param  string|array  $resourceName Either a single resource name, or an array of resource names
   * @param  boolean $minified Whether we should minify the code or not
   * @return string
   */
  public static function renderResource($resourceName, $minified = true)
  {
    if (is_array($resourceName)) {
      return Resource::renderResources($resourceName);
    }
    $resource = Resource::getResourceInfo($resourceName);
    if ($resource === false) {
      return '';
    }
    if ($minified) {
      return sprintf('%s%s&amp;%s',
          Resource::MIN_PREFIX,
          $resource['path'],
          $resource['version']
      );
    } else {
      return $resource['path'];
    }
  }

  /**
   * Renders out a bunch of resources using the minifier and adds the versions of all the resources up so that if you increment one version, the concatenated file will be incremented accordingly
   *
   * @param  array  $resourceNames Array of resource names
   * @return string
   */
  public static function renderResources(array $resourceNames)
  {
    $return = Resource::MIN_PREFIX;
    $version = 0;
    $lastKey = count($resourceNames) - 1;

    foreach ($resourceNames as $key => $resourceName) {
      $resource = Resource::getResourceInfo($resourceName);
      if ($key === $lastKey) {
        $return .= $resource['path'];
      } else {
        $return .= "{$resource['path']},";
      }
      $version += $resource['version'];
    }
    $return .= "&amp;{$version}";
    return $return;
  }
}