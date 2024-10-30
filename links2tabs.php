<?php
/**
 * @package Links2Tabs
 */
/*
Plugin Name: Links2Tabs
Plugin URI: http://links2tabs.com/plugins/wordpress/
Description: In addition to link bundling services <a href="http://brief.ly/" target="_blank" ><em>Brief.ly</em></a>, <a href="http://links2.me/" target="_blank" ><em>Links2.Me</em></a>, <a href="http://many.at/" target="_blank" ><em>Many.at</em></a>, Links2Tabs plugin automatically generates the list of references at the bottom of each post and/or page and bundles them into handy links that open everything with one click. If you also want your visitors to open all recent items from your RSS feed with one click, consider installing Feed2Tabs plugin.
Version: 0.0.6
Author: Name.ly
Author URI: http://name.ly/
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/



if ( ! defined ( 'NEW_LINE' ) ) {
  define ( 'NEW_LINE', "\n" );     
} // end of if ( ! defined ( 'NEW_LINE' ) )

if ( ! defined ( 'NAME_LY_MAX_NUMBER_OF_TABS' ) ) {
  define ( 'NAME_LY_MAX_NUMBER_OF_TABS', 36 );     
} // end of if ( ! defined ( 'NAME_LY_MAX_NUMBER_OF_TABS' ) )



define ( 'LINKS2TABS_MAX_TAB_TITLE', 100 ); // set to 0 for no limit

define ( 'LINKS2TABS_SHORTENING_SHUFFIX', '...' );

define ( 'LINKS2TABS_MAX_LINKS_TO_PROCESS', 1000 ); // remove this parameter from explode below for no limit

define ( 'LINKS2TABS_CURRENT_VERSION', '0.0.1' );

define ( 'LINKS2TABS_DEFAULT_FILTER_PRIORITY', 1000 );



global $links2tabs_api_default_bases;
$links2tabs_api_default_bases = array (
  "many.at" => "http://many.at/links2tabs/",
  "brief.ly" => "http://brief.ly/links2tabs/",
  "links2.me" => "http://links2.me/links2tabs/",
  "links2tabs.com" => "http://wp.links2tabs.com/",
);

global $links2tabs_api_default_base;
$links2tabs_api_default_base = "http://many.at/links2tabs/";
// set "random" default base
global $blog_id;
if ( ! function_exists ( "get_blog_details" ) ) {
  if ( file_exists ( ABSPATH . 'wp-includes/ms-blogs.php' ) ) {
    include_once ( ABSPATH . 'wp-includes/ms-blogs.php' );
  } // end of if ( file_exists ( ABSPATH . 'wp-includes/ms-blogs.php' ) )
} // end of if ( ! function_exists ( "get_blog_details" ) )
if ( function_exists ( "get_blog_details" ) ) {
  $blog_details = get_blog_details ( $blog_id, true);
  $blog_registration_time = strtotime ( $blog_details->registered );
  $number_of_bases = count ( $links2tabs_api_default_bases );
} // end of if ( function_exists ( "get_blog_details" ) )
if ( $number_of_bases ) {
  $default_bases = array_values ( $links2tabs_api_default_bases );
  $links2tabs_api_default_base = $default_bases [ $blog_registration_time % $number_of_bases ];
} // end of if ( $number_of_bases )



global $links2tabs_options_default;
$links2tabs_options_default = array (
  "version" => LINKS2TABS_CURRENT_VERSION,
  // Parsing & Finish
  "show_on_posts" => "yes",
  "show_on_pages" => "yes",
  //"show_on_home" => "yes",
  "skip_double_links" => "yes",
  "include_links_with_images" => "yes",
  "include_internal_links" => "yes",
  "add_reference_tags" => "no",
  "link_reference_tags" => "yes",
  "links_per_bundle" => 10,
  "min_number_of_links" => 3,
  "one_bundle_caption" => __ ( 'Open all references in tabs:', 'links2tabs' ),
  "many_bundles_caption" => __ ( 'Open bundled references in tabs:', 'links2tabs' ),
  "target" => "_blank",
  "visibility" => "-99", // "-99" - Public, "10" - Private, "99" - Hidden
  // Name.ly/Frames appearance
  "open_in_tabs" => "yes",
  "default_reference_caption" => __ ( 'Reference', 'links2tabs' ),
  "reference_caption_format" => '[%REF_ID%] %TITLE%',
  "title" => __ ( '%BLOG_NAME% - %BLOG_DESCRIPTION%', 'links2tabs' ),
  "description" => __ ( 'References %REF_IDS% for %POST_TITLE%', 'links2tabs' ),
  "toc" => __ ( 'ToC', 'links2tabs' ),
  // Advanced settings
  "custom_api_base" => $links2tabs_api_default_base,
  "filter_priority" => LINKS2TABS_DEFAULT_FILTER_PRIORITY,
  "exclude_url_keywords" => array ( 'call:', 'mailto:', 'skype:', 'tel:', '.rar', '.tar.gz', '.zip', ),
);
global $links2tabs_options;
$links2tabs_options = get_option ( 'links2tabs' );
if ( is_array ( $links2tabs_options ) ) {
  $links2tabs_options = wp_parse_args ( (array) get_option ( 'links2tabs' ), $links2tabs_options_default );
} else {
  $links2tabs_options = $links2tabs_options_default;
} // end of if ( is_array ( $links2tabs_options ) )



if ( is_admin () ) {
  // enable settings page
  include_once ( "links2tabs_settings_page.php" );
} else {
  // added this check not to double include the filter when "Front-end Editor" plugin is used
  add_filter ( 'the_content', 'links2tabs_the_content_filter', $links2tabs_options [ "filter_priority" ], 1 ); // set very low priority, so we are able to catch other links inserted by shortcodes and other plugins
} // end of if ( is_admin () )



function links2tabs_check_url ( $url ) {
  $result = false;
  $url = trim ( $url );
  if ( $url ) {
    // let's check for the common protocols/prototypes first
    if ( preg_match ( "/^(https?|ftp|news|nntp|telnet|mailto|irc|ssh|sftp|webcal|rtsp|skype)\:(.*)?$/i", $url ) ) {
      $result = $url;
    } else {
      if ( "/" == $url [0] ) {
        $result = ( isset ( $_SERVER['HTTPS'] ) && ( 'on' == $_SERVER['HTTPS'] ) ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $url;
      } else {
        $baseuriend = stripos ( $_SERVER['REQUEST_URI'], "?" );
        if ( false !== $baseuriend ) {
          $baseuri = substr ( $_SERVER['REQUEST_URI'], 0, $baseuriend );
        } else {
          $baseuri = $_SERVER['REQUEST_URI'];
        } // end of if ( false !== $baseuriend )
        if ( "/" != substr ( $baseuri, -1 ) ) {
          $baseuri .= "/";
        } // end of if ( "/" != substr ( $baseuri, -1 ) )
        $result = ( isset ( $_SERVER['HTTPS'] ) && ( 'on' == $_SERVER['HTTPS'] ) ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $baseuri . $url;
      } // end of if ( "/" == $url [0] )
    } // end of if ( preg_match ( "/^(https?|...)\:(.*)?$/i", $url ) )
  } else {
    $result = false;
  } // end of if ( $url )
  return $result;
} // end of function links2tabs_check_url ( $url )



function links2tabs_parse_blog_shortcodes ( $string ) {
  global $post;

  $string = str_replace ( '%BLOG_NAME%', get_option ( 'blogname' ), $string );
  $string = str_replace ( '%BLOG_DESCRIPTION%', get_option ( 'blogdescription' ), $string );
  $string = str_replace ( '%POST_TITLE%', ( $post ? $post->post_title : '' ), $string );

  return $string;  
} // end of function links2tabs_parse_blog_shortcodes ( $string )



function links2tabs_the_content_filter ( $content ) {
  global $current_site;
  global $links2tabs_options;

  if ( defined ( 'SKIPLINKS2TABS' ) && SKIPLINKS2TABS ) {
    // do nothing on specific requests
    return $content;
  } // end of if ( defined ( 'SKIPLINKS2TABS' ) )

  if ( is_home () && ( "yes" == $links2tabs_options [ "show_on_home" ] ) ) {
    // proceed further
  } elseif ( is_single () && ( "yes" == $links2tabs_options [ "show_on_posts" ] ) ) {
    // proceed further
  } elseif ( is_page () && ( "yes" == $links2tabs_options [ "show_on_pages" ] ) ) {
    // proceed further
  } else {
    // do nothing on other pages
    return $content;
  } // end of if ( is_single () && ( "yes" == $links2tabs_options [ "show_on_posts" ] ) )

  if ( "-99" == $links2tabs_options [ "visibility" ] ) {
    $visible = true;
  } elseif ( "10" == $links2tabs_options [ "visibility" ] ) {
    $visible = current_user_can ( "manage_options" );
  } else {
    $visible = false;
  } // end of if ( "-99" == $links2tabs_options [ "visibility" ] )

  if ( ! $visible ) {
    // Links2Tabs are not visible to this user
    return $content;
  } // end of if ( ! $visible )

  // init
  $bundles = array ();
  $bundlecaptions = array ();
  $foundlinks = array ();
  $number_of_bundles = 1;
  $current_link_in_current_bundle = 1;
  $bundleindexbase = 0;
  $content_modified = false;

  // black list flag
  $check_black_list = is_array ( $links2tabs_options [ "exclude_url_keywords" ] );
  if ( $check_white_list ) {
    $check_black_list = ( count ( $links2tabs_options [ "exclude_url_keywords" ] ) > 0 );
  } // end of if ( $check_white_list )

  // look for the links
  $rawlinks = explode ( "</a>", str_replace ( '</A>', '</a>', $content ), LINKS2TABS_MAX_LINKS_TO_PROCESS + 1 );
  $rawlinksn = count ( $rawlinks );
  if ( $rawlinksn < 2 ) {
    // do nothing when no links are found
    return $content;
  } // if ( $rawlinksn < 2 )

  // process the raw data
  foreach ( $rawlinks as $key => $rawlink ) {

    // skip the last element
    if ( $key + 1 == $rawlinksn ) {
      continue;
    } // end of if ( $key + 1 == $rawlinksn )

    // append the ending
    $rawlinks [ $key ] .= '</a>';

    // find the beginning
    $astart = stripos ( $rawlink, "<a" );
    if ( false === $astart ) {
      continue;
    } // end of if ( false === $astart )
    
    // look for the url's href
    $hrefstart = stripos ( $rawlink, "href=", $astart+2 );
    if ( false === $hrefstart ) {
      continue;
    } // end of if ( false !== $hrefstart )

    $urlneedle = $rawlink [$hrefstart+5];
    if ( ( '"' != $urlneedle ) && ( "'" != $urlneedle ) ) {
      continue;
    } // end of if ( ( '"' != $urlneedle ) && ( "'" != $urlneedle ) )

    $hrefend = stripos ( $rawlink, $urlneedle, $hrefstart+6 );
    if ( false === $hrefend ) {
      continue;
    } // end of if ( false !== $hrefend )

    $url_original = substr ( $rawlink, $hrefstart + 6, $hrefend - $hrefstart - 6 );

    $url = links2tabs_check_url ( $url_original );
    // let other plugins modify urls in bundles and cancel the bundling of some urls as well
    $url = apply_filters ( "links2tabs_url_to_bundle", $url, $url_original );
    // check that $url is okay
    if ( ! $url ) {
      continue;
    } // end of if ( ! $url )

    // check for internal links
    if ( "yes" != $links2tabs_options [ "include_internal_links" ] ) {
      if ( ( 0 === stripos ( $url, 'http://' . $current_site->domain ) ) || ( 0 === stripos ( $url, 'https://' . $current_site->domain ) ) ) {
        continue;
      } // end of if ( ( 0 === stripos ( $url, 'http://' . $current_site->domain ) ) || ( 0 === stripos ( $url, 'https://' . $current_site->domain ) ) )
    } // end of if ( "yes" == $links2tabs_options [ "include_internal_links" ] )

    // let other plugins modify URLs in the content
    $url_modified = apply_filters ( "links2tabs_url_in_the_content", $url_original, $url );
    if ( $url_modified != $url_original ) {
      $pre_url = substr ( $rawlink, 0, $hrefstart + 6 ); // this can also be broken into $pre_a_start and $post_a_start // $pre_url = $pre_a_start . '<a' . $post_a_start
      $post_url = substr ( $rawlink, $hrefstart + 6 + $hrefend - $hrefstart - 6 );
      $rawlinks [ $key ] = $pre_url . $url_modified . $post_url . '</a>';
      $content_modified = true;
    } // end of if ( $url_modified != $url_original )

    // check black list
    if ( $check_black_list ) {
      foreach ( $links2tabs_options [ "exclude_url_keywords" ] as $url_keyword ) {
        if ( false !== stripos ( $url, $url_keyword ) ) {
          continue 2;
        } // end of if ( false !== stripos ( $url, $url_keyword ) )
      } // end of foreach ( $links2tabs_options [ "exclude_url_keywords" ] as $url_keyword )
    } // end of if ( $check_black_list )

    // default caption
    $caption = $links2tabs_options [ "default_reference_caption" ];
    // Look for a meaningful caption
    $captionstart = stripos ( $rawlink, ">", $hrefend );
    if ( false !== $captionstart ) {
      // find title if any, we might need it later on
      $a_title = false;
      $a_part_1 = trim ( substr ( $rawlink, 0, $captionstart ) );
      $titlestart = stripos ( $a_part_1, "title=" );
      if ( false !== $titlestart ) {
        $titleneedle = $a_part_1 [$titlestart+6];
        if ( ( '"' != $titleneedle ) && ( "'" != $titleneedle ) ) {
          // no valid title specified, just go on
        } else {
          $titleend = stripos ( $a_part_1, $titleneedle, $titlestart+7 );
          if ( false !== $titleend ) {
            $a_title = trim ( substr ( $a_part_1, $titlestart+7, $titleend - $titlestart - 7 ) );
            if ( $a_title ) {
              $caption = $a_title; // change the default value to this one
            } else {
              // an empty title found, go on, look deeper
            } // end of if ( $a_title )
          } else {
            // no title specified, just go on
          } // end of if ( false !== $titleend )
        } // end of if ( ( '"' != $titleneedle ) && ( "'" != $titleneedle ) )
      } else {
        // no end specified, just go on
      } // if ( false !== $titlestart )

      // continue looking a valid title
      $rawcaption = trim ( substr ( $rawlink, $captionstart+1 ) );
      $clean_caption = trim ( strip_tags ( $rawcaption ) );
      if ( $clean_caption ) {
        $caption = $clean_caption;
      } elseif ( $rawcaption ) {
        // try to look into a first image
        $imgstart = stripos ( $rawcaption, "<img" );
        if ( false !== $imgstart ) {
          if ( "yes" != $links2tabs_options [ "include_links_with_images" ] ) {
            // have found an image in the reference and were asked skip it
            continue;
          } // end of if ( "yes" != $links2tabs_options [ "include_links_with_images" ] )
          $imgend = stripos ( $rawcaption, ">", $imgstart+5 );
          if ( false !== $imgend ) {
            $img = trim ( substr ( $rawcaption, $imgstart+7, $imgend - $imgstart - 7 ) );
            $altstart = stripos ( $img, "alt=", $imgstart+5 );
            if ( false !== $altstart ) {
              $altneedle = $img [$altstart+4];
              if ( ( '"' != $altneedle ) && ( "'" != $altneedle ) ) {
                // no meaningful caption found, just continue with the default one
              } else {
                $altend = stripos ( $img, $altneedle, $altstart+5 );
                if ( false !== $altend ) {
                  $alt = trim ( substr ( $img, $altstart+5, $altend - $altstart - 5 ) );
                  if ( $alt ) {
                    $caption = $alt;
                  } else {
                    // empty alt found, just continue with the default one
                  } // end of if ( $alt )
                } else {
                  // no meaningful caption found, just continue with the default one
                } // end of if ( false !== $altend )
              } // end of if ( ( '"' != $altneedle ) && ( "'" != $altneedle ) )
            } else {
              // no alt found, just continue with the default one
            } // end of if ( false !== $altstart )
          } else {
            // no embedded image found, just continue with the default one
          } // end of if ( false !== $imgend )
        } else {
          // no meaningful caption found, just continue with the default one
        } // end of if ( false !== $imgstart )
      } else {
        // no meaningful caption found, just continue with the default one
      } // end of if ( $clean_caption )
    } // end of if ( false !== $captionstart )

    // we have found a URL and its Caption

    // check for double links, if requested
    if ( "yes" == $links2tabs_options [ "skip_double_links" ] ) {
      $link_key = array_search ( $url, $foundlinks );
      if ( false === $link_key ) {
        // a new unique reference found, remember it and proceed further
        $foundlinks [] = $url;
      } else {
        if ( "yes" == $links2tabs_options [ "add_reference_tags" ] ) {
          $rawlinks [ $key ] .= ' [' . ( "yes" == $links2tabs_options [ "link_reference_tags" ] ? '<a href="#links2tabs">' . ( $link_key + 1 ) . '</a>' : ( $link_key + 1 ) ) . ']';
          $content_modified = true;
        } // end of if ( "yes" == $links2tabs_options [ "add_reference_tags" ] )
        continue;
      } // end of if ( false === $link_key )
    } else {
      $foundlinks [] = $url;
    } // end of if ( "yes" == $links2tabs_options [ "skip_double_links" ] )

    // limit the tab caption length
    if ( LINKS2TABS_MAX_TAB_TITLE ) {
      $caption_short = substr ( $caption, 0, LINKS2TABS_MAX_TAB_TITLE );
      if ( strlen ( $caption_short ) < strlen ( $caption ) ) {
        $caption = $caption_short . LINKS2TABS_SHORTENING_SHUFFIX;
      } // end of if ( strlen ( $caption_short ) < strlen ( $caption ) )
    } // end of if ( LINKS2TABS_MAX_TAB_TITLE )
    // apply the format
    if ( trim ( $links2tabs_options [ "reference_caption_format" ] ) ) {
      $caption = str_replace ( '%TITLE%', $caption, str_replace ( '%REF_ID%', ( ( $number_of_bundles - 1 ) * $links2tabs_options [ "links_per_bundle" ] + $current_link_in_current_bundle ), $links2tabs_options [ "reference_caption_format" ] ) );
    } // end of if ( $links2tabs_options [ "reference_caption_format" ] )
    // add new reference to the current bundle
    $bundles [ $number_of_bundles ] .= '&url' . $current_link_in_current_bundle . '=' . urlencode ( $url ) . '&caption' . $current_link_in_current_bundle . '=' . urlencode ( $caption );
    // update the bundle's caption
    $bundlecaptions [ $number_of_bundles ] = ( $bundleindexbase + 1 ) . ( $current_link_in_current_bundle > 1 ? ' - ' . ( $bundleindexbase + $current_link_in_current_bundle ) : '' );
    // add ids
    if ( "yes" == $links2tabs_options [ "add_reference_tags" ] ) {
      $rawlinks [ $key ] .= ' [' . ( "yes" == $links2tabs_options [ "link_reference_tags" ] ? '<a href="#links2tabs">' . ( $bundleindexbase + $current_link_in_current_bundle ) . '</a>' : ( $bundleindexbase + $current_link_in_current_bundle ) ) . ']';
      $content_modified = true;
    } // end of if ( "yes" == $links2tabs_options [ "add_reference_tags" ] )
    // increase the counters
    $current_link_in_current_bundle++;
    if ( $current_link_in_current_bundle >  $links2tabs_options [ "links_per_bundle" ] ) {
      // start a new bundle
      $current_link_in_current_bundle = 1;
      $number_of_bundles++;
      $bundleindexbase += $links2tabs_options [ "links_per_bundle" ];
    } // end of if ( $current_link_in_current_bundle >  $links2tabs_options [ "links_per_bundle" ] )

  } // end of foreach ( $rawlinks as $rawlink )

  if ( $content_modified ) {
    $content = implode ( '', $rawlinks );
  } // end of if ( $content_modified )

  if ( $links2tabs_options [ "min_number_of_links" ] >= $bundleindexbase + $current_link_in_current_bundle ) {
    // too few links to show the bundles
    return $content;
  } // end of if ( $links2tabs_options [ "min_number_of_links" ] >= $bundleindexbase + $current_link_in_current_bundle )

  // function get_site_url was not present in old versions of WP prior to 3.0.0
  if ( function_exists ( "get_site_url" ) ) {
    $site_url = get_site_url ();
  } elseif ( function_exists ( "site_url" ) ) {
    $site_url = site_url ();
  } else {
    $site_url = get_option( 'siteurl' );
  } // end of if ( function_exists ( "get_site_url" ) )

  // add the bundled refereces
  if ( 1 == count ( $bundles ) ) {
    $toc = urlencode ( str_replace ( '%REF_IDS%', $bundlecaptions [ 1 ], links2tabs_parse_blog_shortcodes ( $links2tabs_options [ "toc" ] ) ) );
    $title = urlencode ( str_replace ( '%REF_IDS%', $bundlecaptions [ 1 ], links2tabs_parse_blog_shortcodes ( $links2tabs_options [ "title" ] ) ) );
    $description = urlencode ( str_replace ( '%REF_IDS%', $bundlecaptions [ 1 ], links2tabs_parse_blog_shortcodes ( $links2tabs_options [ "description" ] ) ) );
    $bundledlink = $links2tabs_options ["custom_api_base" ] . '?toc=' . $toc . ( "yes" != $links2tabs_options [ "open_in_tabs" ] ? '&menu=toconly' : '' ) . '&title=' . $title . '&description=' . $description . $bundles [1];
    // let other plugin to modify final links (e.g., cache and / or shorten them)
    $bundledlink = apply_filters ( "links2tabs_bundledlink", $bundledlink );
    $content .= '<a name="links2tabs"></a><div id="links2tabs" class="links2tabs"><p>' . ( $links2tabs_options [ "one_bundle_caption" ] ? $links2tabs_options [ "one_bundle_caption" ] . ' ' : '' ) . ' [<a href="' . $bundledlink . '"' . ( "_blank" == $links2tabs_options [ "target" ] ? ' target="_blank"' : '' ) . '>' . $bundlecaptions [ 1 ] . '</a>' . ']' . ( current_user_can ( "manage_options" ) ? ' [<a href="' . $site_url . '/wp-admin/options-general.php?page=links2tabs">' . __ ( 'Configure', 'links2tabs' ) . '</a>]' : '' ) . '</p></div>';
  } elseif ( 1 < count ( $bundles ) ) {
    $toc_basis = links2tabs_parse_blog_shortcodes ( $links2tabs_options [ "toc" ] ) ;
    $title_basis = links2tabs_parse_blog_shortcodes ( $links2tabs_options [ "title" ] ) ;
    $description_basis = links2tabs_parse_blog_shortcodes ( $links2tabs_options [ "description" ] );
    $content .= '<a name="links2tabs"></a><div id="links2tabs" class="links2tabs">' . ( $links2tabs_options [ "many_bundles_caption" ] ? '<p>' . $links2tabs_options [ "many_bundles_caption" ] . '</p>' : '' ) . '<ul>';
    foreach ( $bundles as $key => $bundle ) {
      $toc = urlencode ( str_replace ( '%REF_IDS%', $bundlecaptions [ $key ], $toc_basis ) );
      $title = urlencode ( str_replace ( '%REF_IDS%', $bundlecaptions [ $key ], $title_basis ) );
      $description = urlencode ( str_replace ( '%REF_IDS%', $bundlecaptions [ $key ], $description_basis ) );
      $bundledlink = $links2tabs_options ["custom_api_base" ] . '?toc=' . $toc . ( "yes" != $links2tabs_options [ "open_in_tabs" ] ? '&menu=toconly' : '' ) . '&title=' . $title . '&description=' . $description . $bundle;
      $bundledlink = apply_filters ( "links2tabs_bundledlink", $bundledlink );
      $content .= '<li>[<a href="' . $bundledlink . '"' . ( "_blank" == $links2tabs_options [ "target" ] ? ' target="_blank"' : '' ) . '>' . $bundlecaptions [ $key ] . '</a>' . ']</li>';
    } // end of foreach ( $bundles as $key => $bundle )
    $content .= '</ul>';
    if ( current_user_can ( "manage_options" ) ) {
      $content .= '<p>' . '[<a href="' . $site_url . '/wp-admin/options-general.php?page=links2tabs">' . __ ( 'Configure', 'links2tabs' ) . '</a>]' . '</p>';
    } // end of if ( current_user_can ( "manage_options" ) )
    $content .= '</div>';
  } else {
    // no references, nothing to add
  } // end of if ( 1 == count ( $bundles ) )

  return $content;
} // end of function links2tabs_the_content_filter ( $content )



// filter hooks templates
// add_filter ( "links2tabs_url_to_bundle", "links2tabs_url_to_bundle_filter", 10, 2 );
// add_filter ( "links2tabs_url_in_the_content", "links2tabs_url_in_the_content_filter", 10, 2 );
// add_filter ( "links2tabs_bundledlink", "links2tabs_bundledlink_filter", 10, 1 );
// function links2tabs_url_to_bundle_filter ( $url_bundled, $url_original ) { return $url_bundled; }
// function links2tabs_url_in_the_content_filter ( $url_original, $url ) { return $url_original; }
// function links2tabs_bundledlink_filter ( $bundledlink ) { return $bundledlink; }



add_shortcode ( 'SkipLinks2Tabs', 'links2tabs_shortcode_skip' );

function links2tabs_shortcode_skip ( $atts ) {
  if ( ! defined ( 'SKIPLINKS2TABS' ) ) {
    define ( 'SKIPLINKS2TABS', true );
  } // end of if ( ! defined ( 'SKIPLINKS2TABS' ) )
  return "";
} // end of function links2tabs_shortcode_skip



?>