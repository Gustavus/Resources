<?php
/**
 * @package Resources
 * @author  Billy Visto
 */
namespace Gustavus\Resources;

use RuntimeException;

/**
 * Class to minify css files using CssCrush
 *
 * @package Resources
 * @author  Billy Visto
 */
class CSSMin
{
  /**
   * Location for all of our minified files
   */
  public static $minifiedFolder = '/css/min/';

  /**
   * File to store our minify information in.
   *   This file contains an json_encoded associative array with keys of the basename and values of the modified time of the file when we created our minified file
   */
  public static $minifyInfoFile = '.gacmin';

  /**
   * CssCrushifies a single resource
   *
   * @param  array    $resource   array of resource config with keys of path and version
   * @param  boolean  $minified   whether or not to minify the resource
   * @param  array|boolean  $additionalOpts Additional options to pass to css crush.
   *   Key of 'crushMethod' is used internally to determine how to crush the file. File option returns the filename, inline returns html containing all of the styles. Defaults to file
   *   More options can be found in <a href="http://the-echoplex.net/csscrush/#api--options">crush's documentation</a>.
   * @param  boolean  $urlify     whether or not to return the url version or the array version of this resource
   * @param  boolean $includeHost Whether to include the host in the returned url
   * @return string|array String if $urlify is true, otherwise an array.
   */
  public static function crushify($resource, $minified, $additionalOpts, $urlify = true, $includeHost = true)
  {
    $path = $resource['path'];
    $dir = dirname($path);
    $basename = basename($path);
    $minFileName = sprintf('%s-%s.css', str_replace('.css', '', $basename), md5($dir));

    if (!\Config::isBlog()) {
      // we don't want to crush files as the blog's httpd user due to permission issues, and doc_root issues.
      if (!Resource::allowMinification()) {
        // we don't want to minify anything
        $minified = false;
      }

      if (isset($additionalOpts['crushMethod']) && in_array($additionalOpts['crushMethod'], ['file', 'inline'])) {
        $crushMethod = $additionalOpts['crushMethod'];
      } else {
        $crushMethod = 'file';
      }

      $cssCrushOptions = ['minify' => $minified, 'versioning' => false, 'doc_root' => $_SERVER['DOCUMENT_ROOT']];

      if (is_array($additionalOpts)) {
        $cssCrushOptions = array_merge($cssCrushOptions, $additionalOpts);
      }
      if (isset($cssCrushOptions['vars'])) {
        $cssCrushOptions['vars'] = array_merge($cssCrushOptions['vars'], Config::$globalCrushVariables);
      } else {
        $cssCrushOptions['vars'] = Config::$globalCrushVariables;
      }

      $cssCrushOptions['output_dir'] = Resource::addDocRootToPath(self::$minifiedFolder);
      $cssCrushOptions['output_file'] = $minFileName;

      $crushResult = \CssCrush\CssCrush::{$crushMethod}($resource['path'], $cssCrushOptions);

      if ($crushMethod !== 'file') {
        return $crushResult;
      }
    }
    // change our resource to point to its minified path
    $resource['path'] = self::$minifiedFolder . $minFileName;

    if (!isset($resource['version'])) {
      // make sure the resource has a version
      $resource['version'] = 1;
    }
    // add the version of our global variables so the browser doesn't use a version with old variables
    $resource['version'] += Config::GLOBAL_CRUSH_VARIABLES_VERSION;

    if ($urlify) {
      return sprintf('%s%s?v=%s',
          ($includeHost ? Resource::determineHost() : ''),
          $resource['path'],
          $resource['version']
      );
    } else {
      // we don't want this resource urlified.
      return $resource;
    }
  }

  /**
   * Bundles css resources into one file
   *
   * @param  array  $resourcePaths    Paths to the resources to include in the bundle
   * @param  array  $srcResourcePaths Paths to the source files of the included resources
   * @param  array  $resourceMTimes   Modification times of all the resource sources we are bundling
   * @param  boolean $minify          Whether to minify the bundle or not (Removes whitespace and comments between files)
   *
   * @return string Path to the bundled file
   */
  public static function bundle(array $resourcePaths, array $srcResourcePaths, array $resourceMTimes, $minify = true)
  {
    $bundleName = sprintf('%sBNDL-%s.css',
        str_replace('.css', '', basename(end($srcResourcePaths))),
        md5(implode(',', $resourcePaths))
    );
    $bundlePath = sprintf('%s%s', self::$minifiedFolder, $bundleName);
    $absBundlePath = Resource::addDocRootToPath($bundlePath);

    if ((file_exists($absBundlePath) && !is_writable($absBundlePath)) || !is_writable(Resource::addDocRootToPath(self::$minifiedFolder))) {
      // we can't write to our minified file
      throw new RuntimeException(sprintf('Couldn\'t write to the file: "%s"', $absBundlePath));
    }

    $minifyInfoPath = Resource::addDocRootToPath(self::$minifiedFolder) . self::$minifyInfoFile;
    if (file_exists($minifyInfoPath)) {
      $minifyInfo = json_decode(file_get_contents($minifyInfoPath), true);
    } else {
      $minifyInfo = [];
    }

    if (!empty($minifyInfo) &&
      file_exists($absBundlePath) &&
      isset($minifyInfo[$bundleName], $minifyInfo[$bundleName]['mTimes']) &&
      $minifyInfo[$bundleName]['mTimes'] === json_encode($resourceMTimes)) {
      // our file hasn't changed.
      return $bundlePath;
    }

    // build our bundle
    $bundle = '';
    foreach ($resourcePaths as $resourcePath) {
      if (!file_exists(Resource::addDocRootToPath($resourcePath))) {
        continue;
      }
      if ($minify) {
        // remove any comments and spaces from the beginning of the file
        $bundle .= preg_replace('`^\/\*.+?\*\/\s`s', '', file_get_contents(Resource::addDocRootToPath($resourcePath)));
      } else {
        $bundle .= file_get_contents(Resource::addDocRootToPath($resourcePath));
      }
    }

    $minifyInfo[$bundleName] = [
      'mTimes'      => json_encode($resourceMTimes),
      'sourceFiles' => json_encode($srcResourcePaths),
    ];

    if ((file_exists($minifyInfoPath) && !is_writable($minifyInfoPath)) || !is_writable(Resource::addDocRootToPath(self::$minifiedFolder))) {
      // our info file is not writable
      trigger_error(sprintf('Couldn\'t write to our minify info file: "%s"', $minifyInfoPath), E_USER_NOTICE);
    } else {
      file_put_contents($minifyInfoPath, json_encode($minifyInfo));
    }

    file_put_contents($absBundlePath, $bundle);
    return $bundlePath;
  }
}