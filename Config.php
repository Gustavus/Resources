<?php
/**
 * @package Resources
 * @author  Billy Visto
 */
namespace Gustavus\Resources;

/**
 * Config to keep track of resource versions
 *
 * @package Resources
 * @author  Billy Visto
 */
class Config
{
  /**
   * tinymce config version
   * @var integer
   */
  const TINYMCE_CONFIG_VERSION = 8;

  /**
   * tinymce version
   * @var integer
   */
  const TINYMCE_VERSION = 1;

  /**
   * imageFill version
   * @var integer
   */
  const IMAGE_FILL_JS_VERSION = 4;

  /**
   * qtip version
   * @var  integer
   */
  const QTIP_VERSION = 4;

  /**
   * helpbox version
   * @var  integer
   */
  const HELPBOX_VERSION = 4;

  /**
   * crc32 version
   * @var  integer
   */
  const CRC32_VERSION = 1;

  /**
   * socialpopover version
   * @var  integer
   */
  const SOCIAL_POPOVER_VERSION = 6;

  /**
   * Select2 version
   * @var  integer
   */
  const SELECT2_VERSION = 2;

  /**
   * bxSlider version
   * @var integer
   */
  const BXSLIDER_VERSION = 1;

  /**
   * url utility version
   * @var integer
   */
  const URL_UTILITY_VERSION = 6;

  /**
   * Gustavus.jQuery.Dropdown version
   * @var integer
   */
  const DROPDOWN_VERSION = 1;

  /**
   * Isotope version
   * @var integer
   */
  const ISOTOPE_VERSION = 1;

  /**
   * ImagesLoaded version
   * @var integer
   */
  const IMAGESLOADED_VERSION = 1;

  /**
   * Gustavus Player version
   * @var integer
   */
  const PLAYER_VERSION = 2;

  /**
   * FooTable CSS Version
   * @var integer
   */
  const FOOTABLE_CSS_VERSION = 1;

  /**
   * FooTable JS Version
   * @var integer
   */
  const FOOTABLE_JS_VERSION = 3;

  /**
   * Version of our custom select2 css overrides
   */
  const SELECT2_CUSTOM_CSS_VERSION = 1;

  /**
   * Version of crush's global variables.
   *   This number gets added onto any crushed resources versions
   *
   *   <strong>Note:</strong> Update this when modifying the globalCrushVariables variable
   *
   * @var  integer
   */
  const GLOBAL_CRUSH_VARIABLES_VERSION = 0;

  /**
   * Array of global variables accessible to cssCrush
   *
   * <strong>Note:</strong> Update the GLOBAL_CRUSH_VARIABLES_VERSION when updating this array.
   *
   * @var array
   */
  public static $globalCrushVariables = [
    // Device Breakpoints (Media Queries)
    'mobile'  => '(max-width: 767px)',
    'tablet'  => '(min-width: 768px) and (max-width: 1024px)',
    'desktop' => '(min-width: 1025px)',

    // New Design Colors
    'web-black'          => '111315',
    'web-background'     => 'EFEFEF',
    'web-light-gray'     => 'CFD4D8',
    'web-light-gray-90'  => 'D1D7DA',
    'web-light-gray-75'  => 'D7DBDE',
    'web-light-gray-alt' => '959DA0',
    'web-medium-gray'    => '373E42',
    'web-medium-gray-90' => '4A4F53',
    'web-dark-gray'      => '23272B',
    'brand-blue-30'      => 'A7C1D4',
    'brand-blue-50'      => '77A2C2',
    'brand-blue-75'      => '3C7CAC',
    'brand-blue-90'      => '17659E',
    'brand-gold-90'      => 'FDD217',
    'brand-gold-70'      => 'FFDE50',
    'brand-red-50'       => 'D79084',
    'brand-red-75'       => 'CB614F',
    'brand-red-90'       => 'C4442F',
    //'web-blue'         => '1C5681'
    'auxbox-blue'        => '164C78',
    'web-highlight'      => 'f8f8f8',


    // Gustavue Pallete
    'brand-gold'              => 'FFCF00',
    'brand-orange'            => 'D4891C',
    'brand-brown'             => '4B3900',
    'brand-red'               => 'BF311A',
    'brand-teal'              => '005958',
    'brand-green'             => '788E1E',
    'brand-blue'              => '005695',
    'brand-purple'            => '49182D',
    'brand-pale-yellow'       => 'FFECBC',
    'brand-pale-green'        => 'D4DB90',
    'brand-pale-blue'         => 'C1D4E3',
    'brand-warm-gray'         => 'EDE7DD',
    'brand-shadow-gray'       => 'DDDDDD',
    'brand-dark-shadow-gray'  => 'CCCCCC',

    // @todo search for uses of these
    // 'better-pale-yellow'      => 'FFFEFA',
    // 'better-pale-yellow-dark' => 'F9F7EE',
    // 'better-pale-blue'        => 'E2E8F8',

    // Social Colors
    'facebook-blue'  => '3B5998',
    'twitter-blue'   => '55ACEE',
    'instagram-blue' => '3F729b',
    'flickr-blue'    => '0063DC',
    'flickr-pink'    => 'FF0084',
    'youtube-red'    => 'E52D27',
    'rss-orange'     => 'FF6600',

    // Fonts
    //'serif' => '"Hoefler Text", Constantia, Palatino, "Palatino Linotype", "Book Antiqua", Georgia, serif',
    'sans-serif' => 'Arial, "Helvetica Neue", Helvetica, sans-serif',
    'monospace'  => '"Lucida Console", Monaco, monospace',

    // Speeds for Effects and Transitions
    'fx-fast-seconds'   => '.15s',
    'fx-fast-ms'        => '150',
    'fx-normal-seconds' => '.25s',
    'fx-normal-ms'      => '250',
    'fx-slow-seconds'   => '.5s',
    'fx-slow-ms'        => '500',

    // Sizes
    'banner-height' => '550',

    'utility-bar-height'         => '45',
    'mastmenu-bar-height'        => '100',
    'mastmenu-bar-height-tablet' => '94',

    'base-font-size'   => '15px',
    'base-line-height' => '1.5em',
  ];
}