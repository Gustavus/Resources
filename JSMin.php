<?php
/**
 * @package Resources
 * @author  Billy Visto
 */
namespace Gustavus\Resources;

use Gustavus\Utility\CURLRequest;

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
   * Location for all of our minified files
   */
  public static $minifiedWebFolder = '/js/min/';

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
    $filePath = self::addDocRootToPath($filePath);
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
    $minifiedFilePath = self::addDocRootToPath(self::$minifiedFolder) . $minifiedBaseName;

    // path to our info file
    $minifyInfoPath = self::addDocRootToPath(self::$minifiedFolder) . self::$minifyInfoFile;
    // Note: We use an info file because just comparing timestamps might not be enough in some situations. ie. If we removed a minified file on Lisa, then copied a file from Bart, the copied file on Lisa could still have an mtime less than that of our new minified file.

    // build our options hash so we can determine if the file was minified with the same options
    $minifyOptionsHash = self::buildMinifyOptionsHash($minifyOptions);
    $fileMTime         = filemtime($filePath);
    // Look at our info file to
    if (file_exists($minifyInfoPath) && file_exists($minifiedFilePath)) {
      // we need to see when we last minified the file.
      $minifyInfo = json_decode(file_get_contents($minifyInfoPath), true);
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

    if (!isset($minifyInfo) || !is_array($minifyInfo)) {
      $minifyInfo = [];
    }
    // we need to save our modification time and options hash
    $minifyInfo[$minifiedBaseName] = [
      'optionsHash' => $minifyOptionsHash,
      'mTime'       => $fileMTime,
      'sourceFile'  => $filePath,
    ];

    if (self::$saveTemporaryFile) {
      // actually put a temporary file there to have something in place while waiting for the stagedFile to be ran.
      $_GET['f'] = self::removeDocRootFromPath($filePath);
      $min_serveOptions['quiet'] = true;
      $min_serveOptions['encodeOutput'] = false;
      $minifyResult = include '/cis/www/min/index.php';
      $temporaryFile = $minifyResult['content'];

      if ((file_exists($minifiedFilePath) && !is_writable($minifiedFilePath)) || !is_writable(self::addDocRootToPath(self::$minifiedFolder))) {
        // we can't write to our minified file
        trigger_error(sprintf('Couldn\'t write to the file: "%s"', $minifiedFilePath), E_USER_NOTICE);
        return self::removeDocRootFromPath($filePath);
      }
      if ((file_exists($minifiedFilePath . self::TEMPORARY_FLAG_EXT) && !is_writable($minifiedFilePath . self::TEMPORARY_FLAG_EXT)) || !is_writable(self::addDocRootToPath(self::$minifiedFolder))) {
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

    if ((file_exists($minifyInfoPath) && !is_writable($minifyInfoPath)) || !is_writable(self::addDocRootToPath(self::$minifiedFolder))) {
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
    $options = '';
    foreach ($compilationOptions as $option => $value) {
      $options .= sprintf(' --%s %s', $option, $value);
    }
    $cmd = sprintf('java -jar /cis/lib/Gustavus/Resources/closure-compiler/compiler.jar --js %s --js_output_file %s%s', $sourceFilePath, $destinationPath, $options);

    file_put_contents(self::$stagingDir . basename($destinationPath), $cmd);

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

  /**
   * Adds the DOC_ROOT to the filepath
   *
   * @param string $filePath Path of the file to add the doc root to
   * @return string
   */
  private static function addDocRootToPath($filePath)
  {
    return str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'] . $filePath);
  }
}