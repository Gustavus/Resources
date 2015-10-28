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
   * Location for all of our minified files
   */
  public static $minifiedFolder = '/cis/www/js/min/';

  /**
   * File to store our minify information in.
   *   This file contains an json_encoded associative array with keys of the basename and values of the modified time of the file when we created our minified file
   */
  private static $minifyInfoFile = '.gacmin';

  /**
   * Request parameters to pass to Google's closure compiler
   *
   * @var array
   */
  private static $minifyOptions = [
    'output_format'       => 'json',
    'language'            => 'ECMASCRIPT5',
    'compilation_level'   => 'SIMPLE_OPTIMIZATIONS',
    //'compilation_level' => 'WHITESPACE_ONLY',
    'output_info'         => 'compiled_code',
  ];

  /**
   * Minify params that are allowed to be customized
   *
   * @var array
   */
  private static $customizableOptions = [
    'language',
    'language_out',
    'compilation_level',
  ];

  /**
   * Actually performs the minification for the specified file
   *   Makes an API request to google's closure compiler.
   *
   * @param  string $filePath Path to the file to minify
   * @param  array  $minifyOptions Options to pass to Google's minifier
   * @return string|boolean String of the minified file. False if it failed to minify.
   */
  private static function performMinification($filePath, Array $minifyOptions)
  {
    $curl = new CURLRequest();

    $minifyOptions['js_code'] = file_get_contents($filePath);

    $curl->setOption(CURLOPT_POST, 1);
    $curl->setOption(CURLOPT_POSTFIELDS, http_build_query($minifyOptions));
    $curl->setOption(CURLOPT_RETURNTRANSFER, true);
    $curl->setOption(CURLOPT_HTTPHEADER, ['Content-type: application/x-www-form-urlencoded']);

    $result = $curl->execute('https://closure-compiler.appspot.com/compile');

    if ($curl->getLastErrorNumber() > 0) {
      trigger_error(curl_strerror($curl->getLastErrorNumber()), E_USER_NOTICE);
      $curl->close();
      return false;
    }
    $curl->close();

    $result = json_decode($result, true);
    if (!isset($result['compiledCode'])) {
      //https://developers.google.com/closure/compiler/docs/api-ref?hl=en#errors
      trigger_error('No compiled code was found in the result. Result: ' . print_r($result, true), E_USER_NOTICE);
      return false;
    }

    return $result['compiledCode'];
  }

  /**
   * Minifies a file and saves it with our minified extension.
   *
   * @param  string $filePath Path to the file to minify
   * @param  array  $options  Additional options to use when minifying the file
   * @return string Minified file path
   */
  public static function minifyFile($filePath, Array $options = [])
  {
    if (strpos($filePath, self::removeDocRootFromPath(self::$minifiedFolder)) !== false) {
      // this file appears to already be minified
      return $filePath;
    }
    // add our doc root onto the file path
    $filePath = self::addDocRootToPath($filePath);
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
    $minifiedBaseName = sprintf('%s-%s', md5($baseDir), $baseName);
    $minifiedFilePath = self::$minifiedFolder . $minifiedBaseName;

    // path to our info file
    $minifyInfoPath = self::$minifiedFolder . self::$minifyInfoFile;
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
    ];

    if ((file_exists($minifiedFilePath) && !is_writable($minifiedFilePath)) || !is_writable(self::$minifiedFolder)) {
      // our file is not writable.
      trigger_error(sprintf('The filepath: "%s" is not writable.', $minifiedFilePath), E_USER_NOTICE);
      return self::removeDocRootFromPath($filePath);
    }

    if ((file_exists($minifyInfoPath) && !is_writable($minifyInfoPath)) || !is_writable(self::$minifiedFolder)) {
      // our info file is not writable
      trigger_error(sprintf('Couldn\'t write to our minify info file: "%s"', $minifyInfoPath), E_USER_NOTICE);
      return self::removeDocRootFromPath($filePath);
    }

    // create our minified file
    $minifiedSource = self::performMinification($filePath, $minifyOptions);
    if (empty($minifiedSource) || !$minifiedSource) {
      return self::removeDocRootFromPath($filePath);
    }

    // Save our files and return the new minified file's path
    if (file_put_contents($minifiedFilePath, $minifiedSource)) {
      file_put_contents($minifyInfoPath, json_encode($minifyInfo));
      return self::removeDocRootFromPath($minifiedFilePath);
    } else {
      // This shouldn't happen, but just in case.
      trigger_error(sprintf('There weren\'t any bytes written to the file: "%s". Something happenend.', $minifiedFilePath), E_USER_NOTICE);
      return self::removeDocRootFromPath($filePath);
    }
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