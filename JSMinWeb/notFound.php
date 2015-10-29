<?php
use Gustavus\Resources\JSMin,
  Gustavus\Utility\PageUtil;
// File wasn't found if we are here.
if (!isset($_GET['file'])) {
  return PageUtil::renderPageNotFound();
}
// Let's see if we can render the original
if (file_exists(__DIR__ . '/' . JSMin::$minifyInfoFile)) {
  $minifyInfo = json_decode(file_get_contents(__DIR__ . '/' . JSMin::$minifyInfoFile), true);
  if (isset($minifyInfo[$_GET['file']])) {
    // run the source file through our old minifier
    $_GET['f'] = str_replace('//', '/', '/' . str_replace($_SERVER['DOCUMENT_ROOT'], '', $minifyInfo[$_GET['file']]['sourceFile']));
    include '/cis/www/min/index.php';
    exit;
  }
}

return PageUtil::renderPageNotFound();