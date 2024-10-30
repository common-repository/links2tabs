<?php


add_action ( 'admin_init', 'links2tabs_options_init' );
add_action ( 'admin_menu', 'links2tabs_options_add_page' );

function links2tabs_input_id_by_sid ( $input_sid ) {
  return 'links2tabs[' . $input_sid .  ']';
} // end of function links2tabs_input_id_by_sid ( $input_sid )

function links2tabs_options_init () {
  register_setting ( 'links2tabs_options_settings', 'links2tabs', 'links2tabs_options_validate' );
} // end of function links2tabs_options_init ()

function links2tabs_options_add_page () {
  add_options_page ( 'Links2Tabs', 'Links2Tabs', 'manage_options', 'links2tabs', 'links2tabs_options_do_page' );
} // end of function links2tabs_options_add_page ()

function links2tabs_options_validate ( $input ) {
  global $links2tabs_options;

  // always use the latest version (set it only here)
  $input ["version"] = LINKS2TABS_CURRENT_VERSION;

  // check formats
  $input ["one_bundle_caption"] = strip_tags ( $input ["one_bundle_caption"] );
  $input ["many_bundles_caption"] = strip_tags ( $input ["many_bundles_caption"] );
  $input ["default_reference_caption"] = strip_tags ( $input ["default_reference_caption"] );
  $input ["reference_caption_format"] = strip_tags ( $input ["reference_caption_format"] );
  $input ["title"] = strip_tags ( $input ["title"] );
  $input ["description"] = strip_tags ( $input ["description"] );
  $input ["toc"] = strip_tags ( $input ["toc"] );
  $input ["custom_api_base"] = str_replace ( array ( '"', "'" ), array ( '', "" ), strip_tags ( $input ["custom_api_base"] ) );

  // check filter priority
  $input [ "filter_priority" ] = ( int ) $input [ "filter_priority" ];
  if ( 0 >= $input ["filter_priority"] ) {
    $input ["filter_priority"] = $links2tabs_options [ "filter_priority" ];
  } // end of if ( is_int ( $input ["filter_priority"] ) )

  // convert the black list into array
  $exclude_url_keywords = explode ( " ", $input [ "exclude_url_keywords" ] );
  $input [ "exclude_url_keywords" ] = array ();
  foreach ( $exclude_url_keywords as $url_keyword ) {
    if ( $url_keyword = trim ( $url_keyword ) ) {
      // save only not empty ones
      $input ["exclude_url_keywords"] [] = $url_keyword;
    } // end of if ( $url_keyword = trim ( $url_keyword ) )
  } // end of foreach ( $exclude_url_keywords as $url_keyword )

  // rebuild the blog cache	
  if ( function_exists ( "name_ly_clear_blog_cache" ) ) {
    name_ly_clear_blog_cache ();
  } // end of if ( 0 >= $input ["filter_priority"] )

  return $input;
} // end of function links2tabs_options_validate ( $input )

function links2tabs_options_do_page () {
  global $links2tabs_options;
  global $links2tabs_options_default;
  global $links2tabs_api_default_bases;

  if ( ! current_user_can ( 'manage_options' ) ) {
    die ( __ ( 'Cheatin&#8217; uh?' ) );
  } // end of if ( ! current_user_can ( 'manage_options' ) )

  // handle the settings reset form
  if ( $_POST [ 'links2tabs_reset_settings_submit' ] ) {
    delete_option ( 'links2tabs' );
    echo '<div id="message" class="updated fade"><p><strong>' . __ ( 'Successfully reset Links2Tabs settings to their default values.', 'links2tabs'  ) . '</strong></p></div>' . NEW_LINE;
    // do not forget to reset already read values
    $links2tabs_options = $links2tabs_options_default;
  } // end of if ( $_POST [ 'links2tabs_reset_settings_submit' ] )

  echo '<div class="wrap">' . NEW_LINE;
  echo '<div id="icon-options-general" class="icon32"><br /></div>' . NEW_LINE;
  echo '  <h2>' . __ ( 'Links2Tabs Settings', 'links2tabs' ) . '</h2>' . NEW_LINE;

  if ( $_POST [ 'links2tabs_reset_settings_request' ] ) {
    echo '  <div id="poststuff" class="metabox-holder">' . NEW_LINE;
    echo '    <div class="stuffbox">' . NEW_LINE;
    echo '      <h3>' . __ ( 'Confirm Settings Reset', 'links2tabs' ) . '</h3>' . NEW_LINE;
    echo '      <div class="inside">' . NEW_LINE;
    echo '      <div style="color:red;"><p>' . __ ( 'Click on the button below to reset the settings to their original values:', 'links2tabs' ) . '</p></div>' . NEW_LINE;
    echo '      <form action="" name="name_ly_custom_background_reset_settings" method="post">' . NEW_LINE;
    echo '        <input type="submit" name="links2tabs_reset_settings_submit" value="' . __ ( 'Confirm settings reset', 'links2tabs' ) . '" class="button-primary" >' . NEW_LINE;
    echo '      </form>' . NEW_LINE;
    echo '      </div><!-- inside -->' . NEW_LINE;
    echo '    </div><!-- stuffbox -->' . NEW_LINE;
    echo '  </div><!-- poststuff -->' . NEW_LINE;
  } // end of if ( $_POST [ 'links2tabs_reset_settings_request' ] )

  echo '  <div id="poststuff" class="metabox-holder">' . NEW_LINE;
  echo '    <a name="menu_settings"></a>' . NEW_LINE;
  echo '    <div class="stuffbox">' . NEW_LINE;
  echo '      <h3>' . __ ( 'Menu Settings', 'links2tabs' ) . '</h3>' . NEW_LINE;
  echo '      <div class="inside">' . NEW_LINE;

  echo '      <form method="post" id="NamelyMySitesThemeSettingsForm" action="options.php" >' . NEW_LINE;
  settings_fields ( 'links2tabs_options_settings' );

  echo '        <br /><p>' . __ ( '<a href="http://links2tabs.com/" target="_blank"><i>Links2Tabs</i></a> instantly parses each page/post and bundles references into link(s) that will open all containing references in tabs with one single click.', 'links2tabs' ) . '</p>' . NEW_LINE;
  echo '        <p>' . __ ( 'It does everything by default, so no extra attention is required. If you would like to fine-tune it a bit, please use the settings below.', 'links2tabs' ) . '</p>' . NEW_LINE;
  echo '        <table class="form-table" style="width: auto">' . NEW_LINE;

  foreach ( $links2tabs_options_default as $sid => $value_default ) {

    $value = $links2tabs_options [ $sid ];

    switch ( $sid ) {

      // Parsing & Finish

      case "show_on_posts":
        echo '<tr>' . NEW_LINE;
        echo '  <th scope="row">' . NEW_LINE;
        echo '    ' . __ ( '', 'links2tabs' ) . NEW_LINE;
        echo '  </th>' . NEW_LINE;
        echo '  <td>' . NEW_LINE;
        echo '    <strong>' . __ ( 'Parsing & Final Finish', 'links2tabs' ) . '</strong>' . NEW_LINE;
        echo '  </td>' . NEW_LINE;
        echo '</tr>' . NEW_LINE;
        echo '<tr>' . NEW_LINE;
        echo '  <th scope="row">' . NEW_LINE;
        echo '    <label for="' . links2tabs_input_id_by_sid ( $sid ) . '"><strong>' . __ ( 'Show on posts:', 'links2tabs' ) . '</strong></label>' . NEW_LINE;
        echo '    <br /><br /><i>' . __ ( '', 'links2tabs' ) . '</i>' . NEW_LINE;
        echo '  </th>' . NEW_LINE;
        echo '  <td>' . NEW_LINE;
        echo '<select name="' . links2tabs_input_id_by_sid ( $sid ) . '" id="' . links2tabs_input_id_by_sid ( $sid ) . '" >' . NEW_LINE;
        echo '  <option ' . ( 'yes' == $value ? 'selected ' : '' ) . 'value="yes">' . __ (  'Yes', 'links2tabs' ) . '</option>' . NEW_LINE;
        echo '  <option ' . ( 'yes' != $value ? 'selected ' : '' ) . 'value="no">' . __ (  'No', 'links2tabs' ) . '</option>' . NEW_LINE;
        echo '</select><br />' . NEW_LINE;
        echo '  </td>' . NEW_LINE;
        echo '</tr>' . NEW_LINE;
      break; // end of case "show_on_posts"

      case "show_on_pages":
        echo '<tr>' . NEW_LINE;
        echo '  <th scope="row">' . NEW_LINE;
        echo '    <label for="' . links2tabs_input_id_by_sid ( $sid ) . '"><strong>' . __ ( 'Show on pages:', 'links2tabs' ) . '</strong></label>' . NEW_LINE;
        echo '    <br /><br /><i>' . __ ( '', 'links2tabs' ) . '</i>' . NEW_LINE;
        echo '  </th>' . NEW_LINE;
        echo '  <td>' . NEW_LINE;
        echo '<select name="' . links2tabs_input_id_by_sid ( $sid ) . '" id="' . links2tabs_input_id_by_sid ( $sid ) . '" >' . NEW_LINE;
        echo '  <option ' . ( 'yes' == $value ? 'selected ' : '' ) . 'value="yes">' . __ (  'Yes', 'links2tabs' ) . '</option>' . NEW_LINE;
        echo '  <option ' . ( 'yes' != $value ? 'selected ' : '' ) . 'value="no">' . __ (  'No', 'links2tabs' ) . '</option>' . NEW_LINE;
        echo '</select><br />' . NEW_LINE;
        echo '  </td>' . NEW_LINE;
        echo '</tr>' . NEW_LINE;
      break; // end of case "show_on_pages"

      case "show_on_home":
        echo '<tr>' . NEW_LINE;
        echo '  <th scope="row">' . NEW_LINE;
        echo '    <label for="' . links2tabs_input_id_by_sid ( $sid ) . '"><strong>' . __ ( 'Show on home page:', 'links2tabs' ) . '</strong></label>' . NEW_LINE;
        echo '    <br /><br /><i>' . __ ( '', 'links2tabs' ) . '</i>' . NEW_LINE;
        echo '  </th>' . NEW_LINE;
        echo '  <td>' . NEW_LINE;
        echo '<select name="' . links2tabs_input_id_by_sid ( $sid ) . '" id="' . links2tabs_input_id_by_sid ( $sid ) . '" >' . NEW_LINE;
        echo '  <option ' . ( 'yes' == $value ? 'selected ' : '' ) . 'value="yes">' . __ (  'Yes', 'links2tabs' ) . '</option>' . NEW_LINE;
        echo '  <option ' . ( 'yes' != $value ? 'selected ' : '' ) . 'value="no">' . __ (  'No', 'links2tabs' ) . '</option>' . NEW_LINE;
        echo '</select><br />' . NEW_LINE;
        echo '  </td>' . NEW_LINE;
        echo '</tr>' . NEW_LINE;
      break; // end of case "show_on_home"

      case "skip_double_links":
        echo '<tr>' . NEW_LINE;
        echo '  <th scope="row">' . NEW_LINE;
        echo '    <label for="' . links2tabs_input_id_by_sid ( $sid ) . '"><strong>' . __ ( 'Skip double references:', 'links2tabs' ) . '</strong></label>' . NEW_LINE;
        echo '    <br /><br /><i>' . __ ( 'N.B., if several double references with the same URLs are found, the title of the first one will be used in the bundle.', 'links2tabs' ) . '</i>' . NEW_LINE;
        echo '  </th>' . NEW_LINE;
        echo '  <td>' . NEW_LINE;
        echo '<select name="' . links2tabs_input_id_by_sid ( $sid ) . '" id="' . links2tabs_input_id_by_sid ( $sid ) . '" >' . NEW_LINE;
        echo '  <option ' . ( 'yes' == $value ? 'selected ' : '' ) . 'value="yes">' . __ (  'Yes', 'links2tabs' ) . '</option>' . NEW_LINE;
        echo '  <option ' . ( 'yes' != $value ? 'selected ' : '' ) . 'value="no">' . __ (  'No', 'links2tabs' ) . '</option>' . NEW_LINE;
        echo '</select><br />' . NEW_LINE;
        echo '  </td>' . NEW_LINE;
        echo '</tr>' . NEW_LINE;
      break; // end of case "skip_double_links"

      case "include_links_with_images":
        echo '<tr>' . NEW_LINE;
        echo '  <th scope="row">' . NEW_LINE;
        echo '    <label for="' . links2tabs_input_id_by_sid ( $sid ) . '"><strong>' . __ ( 'Include links with images:', 'links2tabs' ) . '</strong></label>' . NEW_LINE;
        echo '    <br /><br /><i>' . __ ( 'When set to <code>No</code>, references with images will be skipped.', 'links2tabs' ) . '</i>' . NEW_LINE;
        echo '  </th>' . NEW_LINE;
        echo '  <td>' . NEW_LINE;
        echo '<select name="' . links2tabs_input_id_by_sid ( $sid ) . '" id="' . links2tabs_input_id_by_sid ( $sid ) . '" >' . NEW_LINE;
        echo '  <option ' . ( 'yes' == $value ? 'selected ' : '' ) . 'value="yes">' . __ (  'Yes', 'links2tabs' ) . '</option>' . NEW_LINE;
        echo '  <option ' . ( 'yes' != $value ? 'selected ' : '' ) . 'value="no">' . __ (  'No', 'links2tabs' ) . '</option>' . NEW_LINE;
        echo '</select><br />' . NEW_LINE;
        echo '  </td>' . NEW_LINE;
        echo '</tr>' . NEW_LINE;
      break; // end of case "include_links_with_images"

      case "include_internal_links":
        echo '<tr>' . NEW_LINE;
        echo '  <th scope="row">' . NEW_LINE;
        echo '    <label for="' . links2tabs_input_id_by_sid ( $sid ) . '"><strong>' . __ ( 'Include internal links:', 'links2tabs' ) . '</strong></label>' . NEW_LINE;
        echo '    <br /><br /><i>' . __ ( 'Choose whether or not to bundle internal links (those refering to this site\'s domain).', 'links2tabs' ) . '</i>' . NEW_LINE;
        echo '  </th>' . NEW_LINE;
        echo '  <td>' . NEW_LINE;
        echo '<select name="' . links2tabs_input_id_by_sid ( $sid ) . '" id="' . links2tabs_input_id_by_sid ( $sid ) . '" >' . NEW_LINE;
        echo '  <option ' . ( 'yes' == $value ? 'selected ' : '' ) . 'value="yes">' . __ (  'Yes', 'links2tabs' ) . '</option>' . NEW_LINE;
        echo '  <option ' . ( 'yes' != $value ? 'selected ' : '' ) . 'value="no">' . __ (  'No', 'links2tabs' ) . '</option>' . NEW_LINE;
        echo '</select><br />' . NEW_LINE;
        echo '  </td>' . NEW_LINE;
        echo '</tr>' . NEW_LINE;
      break; // end of case "include_internal_links"

      case "add_reference_tags":
        echo '<tr>' . NEW_LINE;
        echo '  <th scope="row">' . NEW_LINE;
        echo '    <label for="' . links2tabs_input_id_by_sid ( $sid ) . '"><strong>' . __ ( 'Add reference tags:', 'links2tabs' ) . '</strong></label>' . NEW_LINE;
        echo '    <br /><br /><i>' . __ ( 'Should the plugin tag each recognised reference with its number?', 'links2tabs' ) . '</i>' . NEW_LINE;
        echo '  </th>' . NEW_LINE;
        echo '  <td>' . NEW_LINE;
        echo '<select name="' . links2tabs_input_id_by_sid ( $sid ) . '" id="' . links2tabs_input_id_by_sid ( $sid ) . '" >' . NEW_LINE;
        echo '  <option ' . ( 'yes' == $value ? 'selected ' : '' ) . 'value="yes">' . __ (  'Yes', 'links2tabs' ) . '</option>' . NEW_LINE;
        echo '  <option ' . ( 'yes' != $value ? 'selected ' : '' ) . 'value="no">' . __ (  'No', 'links2tabs' ) . '</option>' . NEW_LINE;
        echo '</select><br />' . NEW_LINE;
        echo '  </td>' . NEW_LINE;
        echo '</tr>' . NEW_LINE;
      break; // end of case "add_reference_tags"

      case "link_reference_tags":
        echo '<tr>' . NEW_LINE;
        echo '  <th scope="row">' . NEW_LINE;
        echo '    <label for="' . links2tabs_input_id_by_sid ( $sid ) . '"><strong>' . __ ( 'Link reference tags to the bundle:', 'links2tabs' ) . '</strong></label>' . NEW_LINE;
        echo '    <br /><br /><i>' . __ ( 'If "Add reference tags" above is <code>Yes</code>, should the plugin link each added tag to the result in the page/post bottom?', 'links2tabs' ) . '</i>' . NEW_LINE;
        echo '  </th>' . NEW_LINE;
        echo '  <td>' . NEW_LINE;
        echo '<select name="' . links2tabs_input_id_by_sid ( $sid ) . '" id="' . links2tabs_input_id_by_sid ( $sid ) . '" >' . NEW_LINE;
        echo '  <option ' . ( 'yes' == $value ? 'selected ' : '' ) . 'value="yes">' . __ (  'Yes', 'links2tabs' ) . '</option>' . NEW_LINE;
        echo '  <option ' . ( 'yes' != $value ? 'selected ' : '' ) . 'value="no">' . __ (  'No', 'links2tabs' ) . '</option>' . NEW_LINE;
        echo '</select><br />' . NEW_LINE;
        echo '  </td>' . NEW_LINE;
        echo '</tr>' . NEW_LINE;
      break; // end of case "link_reference_tags"

      case "links_per_bundle":
        echo '<tr>' . NEW_LINE;
        echo '  <th scope="row">' . NEW_LINE;
        echo '    <label for="' . links2tabs_input_id_by_sid ( $sid ) . '"><strong>' . __ ( 'Links per bundle:', 'links2tabs' ) . '</strong></label>' . NEW_LINE;
        echo '    <br /><br /><i>' . __ ( 'How many references to bundle in one link?', 'links2tabs' ) . '</i>' . NEW_LINE;
        echo '  </th>' . NEW_LINE;
        echo '  <td>' . NEW_LINE;
        echo '<select name="' . links2tabs_input_id_by_sid ( $sid ) . '" id="' . links2tabs_input_id_by_sid ( $sid ) . '" >' . NEW_LINE;
        for ( $index=1; $index<=NAME_LY_MAX_NUMBER_OF_TABS; $index++ ) {
          echo '  <option ' . ( $index == $value ? 'selected ' : '' ) . 'value="' . $index . '">' . $index . '</option>' . NEW_LINE;
        }
        echo '</select><br />' . NEW_LINE;
        echo '  </td>' . NEW_LINE;
        echo '</tr>' . NEW_LINE;
      break; // end of case "links_per_bundle"

      case "min_number_of_links":
        echo '<tr>' . NEW_LINE;
        echo '  <th scope="row">' . NEW_LINE;
        echo '    <label for="' . links2tabs_input_id_by_sid ( $sid ) . '"><strong>' . __ ( 'Minimum number of links:', 'links2tabs' ) . '</strong></label>' . NEW_LINE;
        echo '    <br /><br /><i>' . __ ( 'Set a threshold, so that if the number of references is less than specified, bundled links will not be shown.', 'links2tabs' ) . '</i>' . NEW_LINE;
        echo '  </th>' . NEW_LINE;
        echo '  <td>' . NEW_LINE;
        echo '<select name="' . links2tabs_input_id_by_sid ( $sid ) . '" id="' . links2tabs_input_id_by_sid ( $sid ) . '" >' . NEW_LINE;
        for ( $index=1; $index<=NAME_LY_MAX_NUMBER_OF_TABS; $index++ ) {
          echo '  <option ' . ( $index == $value ? 'selected ' : '' ) . 'value="' . $index . '">' . $index . '</option>' . NEW_LINE;
        }
        echo '</select><br />' . NEW_LINE;
        echo '  </td>' . NEW_LINE;
        echo '</tr>' . NEW_LINE;
      break; // end of case "min_number_of_links"

      case "one_bundle_caption":
        echo '<tr>' . NEW_LINE;
        echo '  <th scope="row">' . NEW_LINE;
        echo '    <label for="' . links2tabs_input_id_by_sid ( $sid ) . '"><strong>' . __ ( 'Text before one bundle:', 'links2tabs' ) . '</strong></label>' . NEW_LINE;
        echo '    <br /><br /><i>' . __ ( 'Text to appear before the link in case there is only one reference bundle.', 'links2tabs' ) . '</i>' . NEW_LINE;
        echo '  </th>' . NEW_LINE;
        echo '  <td>' . NEW_LINE;
        echo '<input name="' . links2tabs_input_id_by_sid ( $sid ) . '" id="' . links2tabs_input_id_by_sid ( $sid ) . '" value="' . esc_attr ( $value ) . '" size="80" />' . NEW_LINE;
        echo '  </td>' . NEW_LINE;
        echo '</tr>' . NEW_LINE;
      break; // end of case "one_bundle_caption"

      case "many_bundles_caption":
        echo '<tr>' . NEW_LINE;
        echo '  <th scope="row">' . NEW_LINE;
        echo '    <label for="' . links2tabs_input_id_by_sid ( $sid ) . '"><strong>' . __ ( 'Text before many bundles:', 'links2tabs' ) . '</strong></label>' . NEW_LINE;
        echo '    <br /><br /><i>' . __ ( 'Text to appear before the links in case there are several bundles.', 'links2tabs' ) . '</i>' . NEW_LINE;
        echo '  </th>' . NEW_LINE;
        echo '  <td>' . NEW_LINE;
        echo '<input name="' . links2tabs_input_id_by_sid ( $sid ) . '" id="' . links2tabs_input_id_by_sid ( $sid ) . '" value="' . esc_attr ( $value ) . '" size="80" />' . NEW_LINE;
        echo '  </td>' . NEW_LINE;
        echo '</tr>' . NEW_LINE;
      break; // end of case "many_bundles_caption"

      case "target":
        echo '<tr>' . NEW_LINE;
        echo '  <th scope="row">' . NEW_LINE;
        echo '    <label for="' . links2tabs_input_id_by_sid ( $sid ) . '"><strong>' . __ ( 'Link target:', 'links2tabs' ) . '</strong></label>' . NEW_LINE;
        echo '    <br /><br /><i>' . __ ( 'Where to open the bundled references?', 'links2tabs' ) . '</i>' . NEW_LINE;
        echo '  </th>' . NEW_LINE;
        echo '  <td>' . NEW_LINE;
        echo '<select name="' . links2tabs_input_id_by_sid ( $sid ) . '" id="' . links2tabs_input_id_by_sid ( $sid ) . '" >' . NEW_LINE;
        echo '  <option ' . ( '_blank' == $value ? 'selected ' : '' ) . 'value="_blank">' . __ (  'New Window', 'links2tabs' ) . '</option>' . NEW_LINE;
        echo '  <option ' . ( '_blank' != $value ? 'selected ' : '' ) . 'value="_same">' . __ (  'Same Window', 'links2tabs' ) . '</option>' . NEW_LINE;
        echo '</select><br />' . NEW_LINE;
        echo '  </td>' . NEW_LINE;
        echo '</tr>' . NEW_LINE;
      break; // end of case "target"

      case "visibility":
        echo '<tr>' . NEW_LINE;
        echo '  <th scope="row">' . NEW_LINE;
        echo '    <label for="' . links2tabs_input_id_by_sid ( $sid ) . '"><strong>' . __ ( 'Visibility:', 'links2tabs' ) . '</strong></label>' . NEW_LINE;
        echo '    <br /><br /><i>' . __ ( 'Who should be able to see the bundles?', 'links2tabs' ) . '</i>' . NEW_LINE;
        echo '  </th>' . NEW_LINE;
        echo '  <td>' . NEW_LINE;
        echo '<select name="' . links2tabs_input_id_by_sid ( $sid ) . '" id="' . links2tabs_input_id_by_sid ( $sid ) . '" >' . NEW_LINE;
        echo '  <option ' . ( '-99' == $value ? 'selected ' : '' ) . 'value="-99">' . __ (  'Public', 'links2tabs' ) . '</option>' . NEW_LINE;
        echo '  <option ' . ( '10' == $value ? 'selected ' : '' ) . 'value="10">' . __ (  'Private', 'links2tabs' ) . '</option>' . NEW_LINE;
        echo '  <option ' . ( '99' == $value ? 'selected ' : '' ) . 'value="99">' . __ (  'Hidden', 'links2tabs' ) . '</option>' . NEW_LINE;
        echo '</select><br />' . NEW_LINE;
        echo '<small>' . __ ( 'When set to <code>Public</code> - it will be visible to all visitors.<br />When set to <code>Private</code> - to this site\'s admins only.<br />When set to <code>Hidden</code> - this will disable the reference bundling completely.', 'links2tabs' ) . '</small><br />' . NEW_LINE;
        echo '  </td>' . NEW_LINE;
        echo '</tr>' . NEW_LINE;
      break; // end of case "visibility"

      // Name.ly/Frames appearance

      case "open_in_tabs":
        echo '<tr>' . NEW_LINE;
        echo '  <th scope="row">' . NEW_LINE;
        echo '    ' . __ ( '', 'links2tabs' ) . NEW_LINE;
        echo '  </th>' . NEW_LINE;
        echo '  <td>' . NEW_LINE;
        echo '    <strong>' . __ ( 'Bundled Tabs', 'links2tabs' ) . '</strong>' . NEW_LINE;
        echo '  </td>' . NEW_LINE;
        echo '</tr>' . NEW_LINE;
        echo '<tr>' . NEW_LINE;
        echo '  <th scope="row">' . NEW_LINE;
        echo '    <label for="' . links2tabs_input_id_by_sid ( $sid ) . '"><strong>' . __ ( 'Open references in tabs:', 'links2tabs' ) . '</strong></label>' . NEW_LINE;
        echo '    <br /><br /><i>' . __ ( 'Enables or disables automatic link opening in separate tabs. Please mind, that if ToC is set to <code>off</code> below, tabs will be enabled anyway.', 'links2tabs' ) . '</i>' . NEW_LINE;
        echo '  </th>' . NEW_LINE;
        echo '  <td>' . NEW_LINE;
        echo '<select name="' . links2tabs_input_id_by_sid ( $sid ) . '" id="' . links2tabs_input_id_by_sid ( $sid ) . '" >' . NEW_LINE;
        echo '  <option ' . ( 'yes' == $value ? 'selected ' : '' ) . 'value="yes">' . __ (  'Yes', 'links2tabs' ) . '</option>' . NEW_LINE;
        echo '  <option ' . ( 'yes' != $value ? 'selected ' : '' ) . 'value="no">' . __ (  'No', 'links2tabs' ) . '</option>' . NEW_LINE;
        echo '</select><br />' . NEW_LINE;
        echo '  </td>' . NEW_LINE;
        echo '</tr>' . NEW_LINE;
      break; // end of case "open_in_tabs"

      case "default_reference_caption":
        echo '<tr>' . NEW_LINE;
        echo '  <th scope="row">' . NEW_LINE;
        echo '    <label for="' . links2tabs_input_id_by_sid ( $sid ) . '"><strong>' . __ ( 'Default reference title:', 'links2tabs' ) . '</strong></label>' . NEW_LINE;
        echo '    <br /><br /><i>' . __ ( 'This caption will be used if no valid reference title is found.', 'links2tabs' ) . '</i>' . NEW_LINE;
        echo '  </th>' . NEW_LINE;
        echo '  <td>' . NEW_LINE;
        echo '<input name="' . links2tabs_input_id_by_sid ( $sid ) . '" id="' . links2tabs_input_id_by_sid ( $sid ) . '" value="' . esc_attr ( $value ) . '" size="80" />' . NEW_LINE;
        echo '  </td>' . NEW_LINE;
        echo '</tr>' . NEW_LINE;
      break; // end of case "default_reference_caption"

      case "reference_caption_format":
        echo '<tr>' . NEW_LINE;
        echo '  <th scope="row">' . NEW_LINE;
        echo '    <label for="' . links2tabs_input_id_by_sid ( $sid ) . '"><strong>' . __ ( 'Reference title format:', 'links2tabs' ) . '</strong></label>' . NEW_LINE;
        echo '    <br /><br /><i>' . __ ( 'How to format the reference titles into the tab captions. It is possible to use <code>%TITLE%</code> and <code>%REF_ID%</code>.', 'links2tabs' ) . ( LINKS2TABS_MAX_TAB_TITLE ? ' ' . str_replace ( '%LINKS2TABS_MAX_TAB_TITLE%', LINKS2TABS_MAX_TAB_TITLE, __ ( 'N.B. These titles will be cut off if longer than %LINKS2TABS_MAX_TAB_TITLE% characters.', 'links2tabs' ) ) : '' ) . '</i>' . NEW_LINE;
        echo '  </th>' . NEW_LINE;
        echo '  <td>' . NEW_LINE;
        echo '<input name="' . links2tabs_input_id_by_sid ( $sid ) . '" id="' . links2tabs_input_id_by_sid ( $sid ) . '" value="' . esc_attr ( $value ) . '" size="80" />' . NEW_LINE;
        echo '  </td>' . NEW_LINE;
        echo '</tr>' . NEW_LINE;
      break; // end of case "reference_caption_format"

      case "title":
        echo '<tr>' . NEW_LINE;
        echo '  <th scope="row">' . NEW_LINE;
        echo '    <label for="' . links2tabs_input_id_by_sid ( $sid ) . '"><strong>' . __ ( 'Bundle Title:', 'links2tabs' ) . '</strong></label>' . NEW_LINE;
        echo '    <br /><br /><i>' . __ ( 'Title of the bundle to appear on the ToC tab.', 'links2tabs' ) . '<a href="#shortcodes" style="text-decoration:none;"><sup> *</sup></a></i>' . NEW_LINE;
        echo '  </th>' . NEW_LINE;
        echo '  <td>' . NEW_LINE;
        echo '<input name="' . links2tabs_input_id_by_sid ( $sid ) . '" id="' . links2tabs_input_id_by_sid ( $sid ) . '" value="' . esc_attr ( $value ) . '" size="80" />' . NEW_LINE;
        echo '  </td>' . NEW_LINE;
        echo '</tr>' . NEW_LINE;
      break; // end of case "title"

      case "description":
        echo '<tr>' . NEW_LINE;
        echo '  <th scope="row">' . NEW_LINE;
        echo '    <label for="' . links2tabs_input_id_by_sid ( $sid ) . '"><strong>' . __ ( 'Bundle Description:', 'links2tabs' ) . '</strong></label>' . NEW_LINE;
        echo '    <br /><br /><i>' . __ ( 'Description of the bundle to appear on the ToC tab.', 'links2tabs' ) . '<a href="#shortcodes" style="text-decoration:none;"><sup> *</sup></a></i>' . NEW_LINE;
        echo '  </th>' . NEW_LINE;
        echo '  <td>' . NEW_LINE;
        echo '<input name="' . links2tabs_input_id_by_sid ( $sid ) . '" id="' . links2tabs_input_id_by_sid ( $sid ) . '" value="' . esc_attr ( $value ) . '" size="80" />' . NEW_LINE;
        echo '  </td>' . NEW_LINE;
        echo '</tr>' . NEW_LINE;
      break; // end of case "description"

      case "toc":
        echo '<tr>' . NEW_LINE;
        echo '  <th scope="row">' . NEW_LINE;
        echo '    <label for="' . links2tabs_input_id_by_sid ( $sid ) . '"><strong>' . __ ( 'Bundle Table of Contents:', 'links2tabs' ) . '</strong></label>' . NEW_LINE;
        echo '    <br /><br /><i>' . __ ( 'Caption of the ToC tab. Set to <code>off</code> to hide the ToC.', 'links2tabs' ) . '<a href="#shortcodes" style="text-decoration:none;"><sup> *</sup></a></i>' . NEW_LINE;
        echo '  </th>' . NEW_LINE;
        echo '  <td>' . NEW_LINE;
        echo '<input name="' . links2tabs_input_id_by_sid ( $sid ) . '" id="' . links2tabs_input_id_by_sid ( $sid ) . '" value="' . esc_attr ( $value ) . '" size="80" />' . NEW_LINE;
        echo '  </td>' . NEW_LINE;
        echo '</tr>' . NEW_LINE;
      break; // end of case "toc"

      // Advanced settings

      case "custom_api_base":
        echo '<tr>' . NEW_LINE;
        echo '  <th scope="row">' . NEW_LINE;
        echo '    ' . __ ( '', 'links2tabs' ) . NEW_LINE;
        echo '  </th>' . NEW_LINE;
        echo '  <td>' . NEW_LINE;
        echo '    <strong>' . __ ( 'Advance Settings', 'links2tabs' ) . '</strong>' . NEW_LINE;
        echo '  </td>' . NEW_LINE;
        echo '</tr>' . NEW_LINE;
        echo '<tr>' . NEW_LINE;
        echo '  <th scope="row">' . NEW_LINE;
        echo '    <label for="' . links2tabs_input_id_by_sid ( $sid ) . '"><strong>' . __ ( 'Custom  API base URL:', 'links2tabs' ) . '</strong></label>' . NEW_LINE;
        echo '    <br /><br /><i>' . __ ( 'So that the advanced users have extra playground.', 'links2tabs' ) . '</i>' . NEW_LINE;
        echo '  </th>' . NEW_LINE;
        echo '  <td>' . NEW_LINE;
        echo '<input name="' . links2tabs_input_id_by_sid ( $sid ) . '" id="' . links2tabs_input_id_by_sid ( $sid ) . '" value="' . esc_attr ( $value ) . '" size="80" /><br />' . NEW_LINE;
        echo '<small>' . __ ( 'You can choose a predefined API base', 'links2tabs' ) . '</small>' . NEW_LINE;
        echo '<select name="' . links2tabs_input_id_by_sid ( 'api_base' ) . '" id="' . links2tabs_input_id_by_sid ( 'api_base' ) . '" onchange="document.getElementById(\'' . links2tabs_input_id_by_sid ( $sid ) . '\').value=this.value;" >' . NEW_LINE;
        foreach ( $links2tabs_api_default_bases as $key => $api_default_base ) {
          echo '  <option ' . ( false !== stripos ( $value, $api_default_base ) ? 'selected ' : '' ) . 'value="' . $api_default_base . '">' . $key . '</option>' . NEW_LINE;
        } // end of foreach ( $links2tabs_api_default_bases as $key => $api_default_base )
        echo '</select>' . NEW_LINE;
        echo '<small>' . __ ( 'or provide your own.', 'links2tabs' ) . '</small><br />' . NEW_LINE;
        echo '<small>' . __ ( 'If you want to use your own custom base, you need to register and configure it first.', 'links2tabs' ) . '<br />' . NEW_LINE;
        echo __ ( 'You can even map it on your own domain name. More instructions on: <a href="http://name.ly/api/custom-api/" target="_blank">Custom API</a> help page.', 'links2tabs' ) . '</small>' . NEW_LINE;
        echo '  </td>' . NEW_LINE;
        echo '</tr>' . NEW_LINE;
      break; // end of case "custom_api_base"

      case "filter_priority":
        echo '<tr>' . NEW_LINE;
        echo '  <th scope="row">' . NEW_LINE;
        echo '    <label for="' . links2tabs_input_id_by_sid ( $sid ) . '"><strong>' . __ ( 'Plugin Priority:', 'links2tabs' ) . '</strong></label>' . NEW_LINE;
        echo '    <br /><br /><i>' . __ ( 'Advanced WordPress users only', 'links2tabs' ) . '</i>' . NEW_LINE;
        echo '  </th>' . NEW_LINE;
        echo '  <td>' . NEW_LINE;
        echo '<input name="' . links2tabs_input_id_by_sid ( $sid ) . '" id="' . links2tabs_input_id_by_sid ( $sid ) . '" value="' . esc_attr ( $value ) . '" size="80" /><br />' . NEW_LINE;
        echo '<small>' . __ ( 'Links2Tabs hooks on <code>the_content</code> filter (see more in <a href="http://codex.wordpress.org/Plugin_API/Filter_Reference/the_content" target="_blank">codex.wordpress.org</a>).<br />Wordpress default priority is 10.<br />By default, Links2Tabs hooks with low priority of 1000 to let other plugins run and parse the content first.<br />If you want to change the order, set plugin priority to any valid positive integer number.', 'links2tabs' ) . '</small><br />' . NEW_LINE;
        echo '  </td>' . NEW_LINE;
        echo '</tr>' . NEW_LINE;
      break; // end of case "toc"

      case "exclude_url_keywords":
        echo '<tr>' . NEW_LINE;
        echo '  <th scope="row">' . NEW_LINE;
        echo '    <label for="' . links2tabs_input_id_by_sid ( $sid ) . '"><strong>' . __ ( 'Exclude URL keywords:', 'links2tabs' ) . '</strong></label>' . NEW_LINE;
        echo '    <br /><br /><i>' . __ ( 'URLs containing the following keywords won\'t be included in the bundles. Separate each excluding keyword by space.', 'links2tabs' ) . '</i>' . NEW_LINE;
        echo '  </th>' . NEW_LINE;
        echo '  <td>' . NEW_LINE;
        echo '<input name="' . links2tabs_input_id_by_sid ( $sid ) . '" id="' . links2tabs_input_id_by_sid ( $sid ) . '" value="' . esc_attr ( implode ( " ", $value ) ) . '" size="80" /><br />' . NEW_LINE;
        echo '<small>' . __ ( 'E.g., adding <code>.zip</code> will exclude all ZIP archives, <code>Domain.com</code> - all links referring to domain.com.', 'links2tabs' ) . '</small><br />' . NEW_LINE;
        echo '<small>' . __ ( 'Click here to reset to default value of:', 'links2tabs' ) . ' <a onclick="document.getElementById(\'' . links2tabs_input_id_by_sid ( $sid ) . '\').value=\'call: mailto: skype: tel: .rar .tar.gz .zip\';">call: mailto: skype: tel: .rar .tar.gz .zip</a></small><br />' . NEW_LINE;
        echo '<small>' . __ ( 'N.B. These checks are case-insensitive.', 'links2tabs' ) . '</small><br />' . NEW_LINE;
        echo '<small>' . __ ( 'N.B. It is also possible to white list certain URLs, meaning no other domain names will be allowed.<br />This should be done via Custom API bases described above.', 'links2tabs' ) . '</small><br />' . NEW_LINE;
        echo '  </td>' . NEW_LINE;
        echo '</tr>' . NEW_LINE;
      break; // end of case "toc"

      default:
        echo '<!-- Unknown option:' . $sid . ' -->' . NEW_LINE;
      break; // end of default

    } // end of switch ( $sid )

  } // end of foreach ( $links2tabs_options as $sid => $value )

  echo '      </table><!-- form-table -->' . NEW_LINE;
  echo '      <p><input type="submit" class="button-primary" value="' . __ ( 'Save changes', 'links2tabs' ) . '" /></p>' . NEW_LINE;
  echo '      </form>' . NEW_LINE;
  echo '      <br /><hr size="1" /><br /><a name="shortcodes"></a>' . NEW_LINE;
  echo __ ( '<p><small><sup>*</sup> - It is possible to use the following short codes to insert corresponding credentials in Title, Description, and ToC fields above:</small></p><ul><li><small><code>"%BLOG_NAME%</code> - site title</small></li><li><small><code>"%BLOG_DESCRIPTION%</code> - site tagline</small></li><li><small><code>"%POST_TITLE%</code> - post title</small></li><li><small><code>"%REF_IDS%</code> - range of references included in the link bundle</small></li></ul>', 'links2tabs' ) . '<br />' . NEW_LINE;

  if ( ! $_POST [ 'links2tabs_reset_settings_request' ] && !  $_POST [ 'links2tabs_reset_settings_submit' ] ) {
    echo '      <br /><hr size="1" /><br />' . NEW_LINE;
    echo '      <form action="" name="name_ly_custom_background_reset_settings" method="post">' . NEW_LINE;
    echo '        <input type="submit" name="links2tabs_reset_settings_request" value="' . __ ( 'Reset to default settings', 'links2tabs' ) . '" class="button-primary" >' . NEW_LINE;
    echo '      </form>' . NEW_LINE;
  } // end of if ( ! $_POST [ 'links2tabs_reset_settings_request' ] && !  $_POST [ 'links2tabs_reset_settings_submit' ] )

  echo '      </div><!-- inside -->' . NEW_LINE;
  echo '    </div><!-- stuffbox -->' . NEW_LINE;
  echo '  </div><!-- poststuff -->' . NEW_LINE;
  echo '</div><!-- wrap -->' . NEW_LINE;

} // end of function links2tabs_options_do_page ()



?>