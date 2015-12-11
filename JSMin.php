<?php
/**
 * @package Resources
 * @author  Billy Visto
 */
namespace Gustavus\Resources;

use RuntimeException;

/**
 * Class to minify javascript files using Google's closure compiler
 *
 * @package Resources
 * @author  Billy Visto
 */
class JSMin
{
  /**
   * Extension to put on our temporary file flag
   */
  const TEMPORARY_FLAG_EXT = '.tmpFlag';

  /**
   * Directory where the file watcher is running so we can execute our compiler.jar file
   */
  private static $stagingDir = '/cis/www-etc/lib/Gustavus/Resources/jsStaging/';

  /**
   * Flag to specify if we should save a temporary file or not
   *
   * @var boolean
   */
  private static $saveTemporaryFile = true;

  /**
   * Location for all of our minified files
   */
  public static $minifiedFolder = '/js/min/';

  /**
   * File to store our minify information in.
   *   This file contains an json_encoded associative array with keys of the basename and values of the modified time of the file when we created our minified file
   */
  public static $minifyInfoFile = '.gacmin';

  /**
   * Request parameters to pass to Google's closure compiler
   *
   * @var array
   */
  private static $minifyOptions = [
    'language_in'         => 'ECMASCRIPT5',
    'compilation_level'   => 'SIMPLE', // WHITESPACE_ONLY, SIMPLE, ADVANCED
  ];

  /**
   * Minify params that are allowed to be customized
   *
   * @var array
   */
  private static $customizableOptions = [
    'language_in',
    'language_out',
    'compilation_level',
  ];

  /**
   * Minifies a file and saves it with our minified extension.
   *
   * @param  string $filePath Path to the file to minify
   * @param  array  $options  Additional options to use when minifying the file
   * @return string|array Minified file path or array with results
   */
  public static function minifyFile($filePath, Array $options = [])
  {
    if (strpos($filePath, self::$minifiedFolder) !== false) {
      // this file appears to already be minified
      return $filePath;
    }
    // add our doc root onto the file path
    $filePath = Resource::addDocRootToPath($filePath);
    if (!file_exists($filePath)) {
      return self::removeDocRootFromPath($filePath);
    }
    // get our default options
    $minifyOptions = self::$minifyOptions;

    if (!empty($options)) {
      // add our customized options to our default set
      foreach ($options as $key => $value) {
        if (in_array($key, self::$customizableOptions)) {
          $minifyOptions[$key] = $value;
        }
      }
    }

    $baseDir  = dirname($filePath) . '/';
    $baseName = basename($filePath);
    $minifiedBaseName = sprintf('%s-%s.js', str_replace('.js', '', $baseName), md5($baseDir));
    $minifiedFilePath = Resource::addDocRootToPath(self::$minifiedFolder) . $minifiedBaseName;

    // path to our info file
    $minifyInfoPath = Resource::addDocRootToPath(self::$minifiedFolder) . self::$minifyInfoFile;
    // Note: We use an info file because just comparing timestamps might not be enough in some situations. ie. If we removed a minified file on Lisa, then copied a file from Bart, the copied file on Lisa could still have an mtime less than that of our new minified file.

    // build our options hash so we can determine if the file was minified with the same options
    $minifyOptionsHash = self::buildMinifyOptionsHash($minifyOptions);
    $fileMTime         = filemtime($filePath);
    if (file_exists($minifyInfoPath)) {
      $minifyInfo = json_decode(file_get_contents($minifyInfoPath), true);
    } else {
      $minifyInfo = [];
    }
    // Look at our info file to
    if (!empty($minifyInfo) && file_exists($minifiedFilePath) && filesize($minifiedFilePath) > 0) {
      // we need to see when we last minified the file.
      // make sure the correct information is in our info file
      if (isset($minifyInfo[$minifiedBaseName], $minifyInfo[$minifiedBaseName]['optionsHash'], $minifyInfo[$minifiedBaseName]['mTime'])) {
        $fileInfo = $minifyInfo[$minifiedBaseName];
        if (!empty($options) && $fileInfo['optionsHash'] !== $minifyOptionsHash) {
          // we have customized options that don't match the options the file was previously built with.
          // Note: A file generated with the default options will overwrite a file generated with custom options. This error will get triggered the next time the custom options are used.
          trigger_error(sprintf('It looks like the file: "%s" has already been minified with different options. Not minifying.', $filePath), E_USER_NOTICE);
          return self::removeDocRootFromPath($filePath);
        }
        if ($fileInfo['mTime'] === $fileMTime) {
          // the file we want to minify hasn't been modified since we last minified it
          if (file_exists($minifiedFilePath . self::TEMPORARY_FLAG_EXT)) {
            // our temporary flag exists
            return [
              'minPath'   => self::removeDocRootFromPath($minifiedFilePath),
              'temporary' => true,
            ];
          }
          return self::removeDocRootFromPath($minifiedFilePath);
        }
      }
    }
    // we need to save our modification time and options hash
    $minifyInfo[$minifiedBaseName] = [
      'optionsHash' => $minifyOptionsHash,
      'mTime'       => $fileMTime,
      'sourceFile'  => $filePath,
    ];

    if (self::$saveTemporaryFile) {
      // actually put a temporary file there to have something in place while waiting for the stagedFile to be ran.
      $temporaryFile = self::buildTemporaryFile($filePath);

      if ((file_exists($minifiedFilePath) && !is_writable($minifiedFilePath)) || !is_writable(Resource::addDocRootToPath(self::$minifiedFolder))) {
        // we can't write to our minified file
        trigger_error(sprintf('Couldn\'t write to the file: "%s"', $minifiedFilePath), E_USER_NOTICE);
        return self::removeDocRootFromPath($filePath);
      }
      if ((file_exists($minifiedFilePath . self::TEMPORARY_FLAG_EXT) && !is_writable($minifiedFilePath . self::TEMPORARY_FLAG_EXT)) || !is_writable(Resource::addDocRootToPath(self::$minifiedFolder))) {
        // we can't save our temporary flag
        trigger_error(sprintf('Couldn\'t save our temporary flag file: "%s"', $minifiedFilePath . self::TEMPORARY_FLAG_EXT), E_USER_NOTICE);
        return self::removeDocRootFromPath($filePath);
      }
      file_put_contents($minifiedFilePath, $temporaryFile);
      file_put_contents($minifiedFilePath . self::TEMPORARY_FLAG_EXT, 'temporary flag');
    }

    if (!self::stageFile($filePath, $minifiedFilePath, $minifyOptions)) {
      return self::removeDocRootFromPath($filePath);
    }

    if ((file_exists($minifyInfoPath) && !is_writable($minifyInfoPath)) || !is_writable(Resource::addDocRootToPath(self::$minifiedFolder))) {
      // our info file is not writable
      trigger_error(sprintf('Couldn\'t write to our minify info file: "%s"', $minifyInfoPath), E_USER_NOTICE);
      return self::removeDocRootFromPath($filePath);
    }

    file_put_contents($minifyInfoPath, json_encode($minifyInfo));
    if (self::$saveTemporaryFile) {
      return [
        'minPath'   => self::removeDocRootFromPath($minifiedFilePath),
        'temporary' => true,
      ];
    }
    return self::removeDocRootFromPath($minifiedFilePath);
  }

  /**
   * Bundles javascript resources together into one file.
   *
   * @param  array  $resourcePaths  Paths to all the resources we are bundling
   * @param  array  $resourceMTimes Modification times of all the files we are bundling
   * @param  boolean $minify        Whether we want these to be minified or just bundled
   * @return string Path to the bundled resource
   */
  public static function bundle(array $resourcePaths, array $resourceMTimes, $minify = true)
  {
    $bundleName = sprintf('%sBNDL-%s.js', str_replace('.js', '', basename(end($resourcePaths))), md5(implode(',', $resourcePaths)));
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
      filesize($absBundlePath) > 0 &&
      isset($minifyInfo[$bundleName], $minifyInfo[$bundleName]['mTimes']) &&
      $minifyInfo[$bundleName]['mTimes'] === json_encode($resourceMTimes)) {
      // our file hasn't changed.
      return $bundlePath;
    }

    if (!$minify) {
      // we need to manually build our bundle from the non-minified resources.
      $bundle = '';
      $joinSeparator = '';
      foreach ($resourcePaths as $resourcePath) {
        if (!file_exists($resourcePath)) {
          continue;
        }
        $bundle .= $joinSeparator . file_get_contents($resourcePath);
        $joinSeparator = ';';
      }
    } else {
      $bundle = self::buildTemporaryFile($resourcePaths);
      $absResourcePaths = array_map(function($item) {return Resource::addDocRootToPath($item);}, $resourcePaths);
      if (!self::stageFile($absResourcePaths, $absBundlePath, self::$minifyOptions)) {
        trigger_error(sprintf('Failed to stage the file: %s', $bundleName), E_USER_NOTICE);
      }
    }

    $minifyInfo[$bundleName] = [
      'mTimes'      => json_encode($resourceMTimes),
      'sourceFiles' => json_encode($resourcePaths),
    ];
    if ((file_exists($minifyInfoPath) && !is_writable($minifyInfoPath)) || !is_writable(Resource::addDocRootToPath(self::$minifiedFolder))) {
      // our info file is not writable
      trigger_error(sprintf('Couldn\'t write to our minify info file: "%s"', $minifyInfoPath), E_USER_NOTICE);
    } else {
      file_put_contents($minifyInfoPath, json_encode($minifyInfo));
    }

    file_put_contents($absBundlePath, $bundle);
    file_put_contents($absBundlePath . self::TEMPORARY_FLAG_EXT, 'temporary flag');

    return $bundlePath;
  }

  /**
   * Builds a temporary file using our old minifier
   *
   * @param  string $filePath Path to the file to build
   * @return string
   */
  private static function buildTemporaryFile($filePath)
  {
    if (is_array($filePath)) {
      $path = '';
      foreach ($filePath as $file) {
        $path .= ',' . self::removeDocRootFromPath($file);
      }
      $path = ltrim($path, ',');
    } else {
      $path = self::removeDocRootFromPath($filePath);
    }
    $_GET['f'] = $path;
    $min_serveOptions['quiet'] = true;
    $min_serveOptions['encodeOutput'] = false;
    $minifyResult = include '/cis/www/min/index.php';
    return $minifyResult['content'];
  }

  /**
   * Saves a file with the compiler command to be executed in our watched directory
   *
   * @param  string $sourceFilePath     Source file to compile
   * @param  string $destinationPath    Destination of the compiled file
   * @param  Array  $compilationOptions Options to pass to the compiler
   * @return void
   */
  private static function stageFile($sourceFilePath, $destinationPath, Array $compilationOptions)
  {
    if (!is_dir(self::$stagingDir)) {
      mkdir(self::$stagingDir, 0777, true);
    }
    if (is_array($sourceFilePath)) {
      $sourceFilePath = implode(' ', $sourceFilePath);
      $isBundle = true;
    } else {
      $isBundle = false;
    }
    $options = '';
    foreach ($compilationOptions as $option => $value) {
      $options .= sprintf(' --%s %s', $option, $value);
    }
    if ($isBundle || !self::reportWarningsForFile($sourceFilePath)) {
      // don't output warnings
      $options .= ' --warning_level QUIET';
    }
    $cmd = sprintf('java -jar /cis/lib/Gustavus/Resources/closure-compiler/compiler.jar --js_output_file %s%s %s', $destinationPath, $options, $sourceFilePath);

    file_put_contents(self::$stagingDir . basename($destinationPath), $cmd);

    return true;
  }

  /**
   * Checks whether we want to report warnings for the specified file.
   *
   * @param  string $filePath Absolute path to the file in question
   * @return boolean
   */
  private static function reportWarningsForFile($filePath)
  {
    if (preg_match(sprintf('`%s/+js/Gustavus`', rtrim($_SERVER['DOCUMENT_ROOT'], '/')), $filePath)) {
      // we want our Gustavus utiltiy bundle to throw warnings
      return true;
    } else if (preg_match(sprintf('`%s/+js/`', rtrim($_SERVER['DOCUMENT_ROOT'], '/')), $filePath)) {
      // we don't want our third party libraries to throw warnings
      return false;
    }
    // @todo add a blacklist if we need more to be excluded
    // everything else defaults to throwing warnings
    return true;
  }

  /**
   * Builds a hash from our request parameters
   *
   * @param  array $options Minification options
   * @return string
   */
  private static function buildMinifyOptionsHash($options)
  {
    return md5(json_encode($options));
  }

  /**
   * Removes the DOC_ROOT from the filepath
   *
   * @param  string $filePath Path of the file to remove the doc root for
   * @return string
   */
  private static function removeDocRootFromPath($filePath)
  {
    return str_replace('//', '/', '/' . str_replace($_SERVER['DOCUMENT_ROOT'], '', $filePath));
  }
}