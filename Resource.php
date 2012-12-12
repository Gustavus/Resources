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
   * @param  string  $resourceName
   * @param  boolean $version
   * @return string
   */
  public static function renderResource($resourceName, $version = true)
  {
    $resource = self::getResourceInfo($resourceName);
    if ($resource === false) {
      return '';
    }
    if ($version) {
      return sprintf('%s%s&amp;%s',
          self::MIN_PREFIX,
          $resource['path'],
          $resource['version']
      );
    } else {
      return $resource['path'];
    }
  }
}