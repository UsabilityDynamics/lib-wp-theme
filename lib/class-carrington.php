<?php
/**
 * Carrington Build.
 *
 * * udx:theme:carrington:styles
 *
 * @author team@UD
 * @version 0.2.4
 * @namespace UsabilityDynamics
 * @module Theme
 * @author potanin@UD
 */
namespace UsabilityDynamics\Theme {

  if( !class_exists( '\UsabilityDynamics\Theme\Carrington' ) ) {

    /**
     * Carrington Class
     *
     * @class Carrington
     * @author potanin@UD
     */
    class Carrington {

      /**
       * ID of instance, used for settings.
       *
       * Parses namespace, should be something like wpp:theme:festival
       *
       * @public
       * @property id
       * @var string
       */
      public $module_directories = null;

      /**
       * ID of instance, used for settings.
       *
       * Parses namespace, should be something like wpp:theme:festival
       *
       * @public
       * @property id
       * @var string
       */
      public $row_directories = null;

      /**
       * Carrington Builder Instance.
       *
       * @var null
       */
      public $_builder = null;

      public function __construct( $args = array() ) {

        $args = Utility::defaults( $args, array(
          'bootstrap'          => true,
          'templates'          => true,
          'debug'              => false,
          'landing'            => false,
          'module_directories' => array(),
          'row_directories'    => array(),
          'post_types'         => array()
        ) );

        $this->post_types = array_merge( array( 'post', 'page' ), (array) $args->post_types );

        $this->module_directories = array_merge( array( __DIR__ . '/modules' ), (array) $args->module_directories );
        $this->row_directories    = array_merge( array( __DIR__ . '/rows' ), (array) $args->row_directories );

        if( !is_file( $this->path = dirname( dirname( __DIR__ ) ) . '/lib-carrington/lib/carrington-build.php' ) ) {
          return false;
        }

        if( !defined( 'CFCT_BUILD_DEBUG_ERROR_LOG' ) ) {
          define( 'CFCT_BUILD_DEBUG_ERROR_LOG', $args->debug );
        }

        if( !defined( 'CFCT_BUILD_TAXONOMY_LANDING' ) ) {
          define( 'CFCT_BUILD_TAXONOMY_LANDING', $args->landing );
        }

        if( $args->templates ) {
          $this->templates();
        }

        if( $args->bootstrap ) {
          $this->bootstrap();
        }

        add_action( 'init', array( $this, 'init' ), 50 );

        add_filter( 'cfct-build-loc', array( &$this, 'cfct_build_loc' ) );
        add_filter( 'cfct-build-url', function ( $url ) {
          return str_replace( '\\', '/', $url );
        } );

        add_filter( 'cfct-build-enabled-post-types', array( $this, 'set_post_types' ) );

        add_filter( 'cfct-row-dirs', array( $this, 'set_row_directories' ) );
        add_filter( 'cfct-module-dirs', array( $this, 'set_module_directories' ) );

        add_filter( 'cfct-modules-included', function ( $dirs ) {
          // cfct_build_deregister_module( 'cfct_module_loop' );
          // cfct_build_deregister_module( 'cfct_module_pullquote' );
          // cfct_build_deregister_module( 'cfct_module_loop_subpages' );
          // cfct_build_deregister_module( 'cfct_module_html' );
          // cfct_build_deregister_module( 'cfct_module_hero' );
          // cfct_build_deregister_module( 'cfct_module_heading' );
          // cfct_build_deregister_module( 'cfct_module_divider' );
          // cfct_build_deregister_module( 'cfct_module_sidebar' );
          // cfct_build_deregister_module( 'cfct_module_carousel' );
          // cfct_build_deregister_module( 'cfct_module_plain_text' );
          // cfct_build_deregister_module( 'cfct_module_gallery' );
        } );

        add_action( 'cfct-rows-loaded', function ( $dirs ) {
          return $dirs;
        }, 100 );

        add_filter( 'cfct-build-display-class', function ( $current ) {
          global $post;

          return $current . ( get_post_meta( $post->ID, '_cfct_build_data', true ) ? ' build-enabled' : ' build-disabled' );
        } );

        add_filter( 'cfct-build-module-url-unknown', function ( $url, $module, $file_key ) {
          return trailingslashit( plugins_url( $file_key, $module ) );
        }, 10, 3 );

        add_filter( 'cfct-build-page-options', function () {
          global $post;

          $cfct_data = get_post_meta( $post->ID, '_cfct_build_data', true );

          $current_setting = !empty( $cfct_data[ 'template' ][ 'custom_class' ] ) ? $cfct_data[ 'template' ][ 'custom_class' ] : '';

          $options = array(
            '<li><a id="cfct-set-build-class" href="#cfct-set-build-class" current_setting="' . $current_setting . '">Set Build Class</a></li>',
            '<li><a id="cfct-copy-build-data" href="#cfct-copy-build">Copy Layout</a></li>',
            '<li><a id="cfct-paste-build-data" href="#cfct-paste-build">Paste Layout</a></li>'
          );

          return implode( '', $options );

        } );

        add_filter( 'cfct-build-module-class', function ( $class ) {
          return 'module';
        } );

        add_filter( 'cfct-block-template', array( $this, 'block_template' ), 10, 2 );

        add_action( 'cfct-widget-module-registered', array( get_class(), '_theme_admin_form' ), 10, 2 );

        add_filter( 'cfct-module-cfct-callout-admin-form', array( get_class(), '_theme_chooser' ), 10, 2 );
        add_filter( 'cfct-module-cfct-post-callout-module-admin-form', array( get_class(), '_theme_chooser' ), 10, 2 );
        add_filter( 'cfct-module-cfct-heading-admin-form', array( get_class(), '_theme_chooser' ), 10, 2 );
        add_filter( 'cfct-module-cfct-plain-text-admin-form', array( get_class(), '_theme_chooser' ), 10, 2 );
        add_filter( 'cfct-module-cfct-rich-text-admin-form', array( get_class(), '_theme_chooser' ), 10, 2 );
        add_filter( 'cfct-module-cfct-module-loop-admin-form', array( get_class(), '_theme_chooser' ), 10, 2 );
        add_filter( 'cfct-module-cfct-module-loop-subpages-admin-form', array( get_class(), '_theme_chooser' ), 10, 2 );

        add_filter( 'cfct-get-extras-modules-css-admin', array( get_class(), '_theme_chooser_css' ), 10, 1 );
        add_filter( 'cfct-get-extras-modules-js-admin', array( get_class(), '_theme_chooser_js' ), 10, 1 );

        include_once( $this->path );

        return $this;

      }

      /**
       * @param $types
       *
       * @return array
       */
      public function set_post_types( $types ) {
        return array_unique( array_merge( (array) $types, (array) $this->post_types ) );
      }

      /**
       * @param $base
       * @param $data
       *
       * @return mixed
       */
      public function module_display( $base, $data ) {
        return $base;
      }

      /**
       * Module Container Block
       *
       * @param $template
       * @param $builder
       *
       * @return string
       */
      public function block_template( $template, $builder ) {
        return '<div class="{class}"><section class="module-container">{modules}</section></div>';
      }

      public function init() {
        global $cfct_build;

        $this->_builder = $cfct_build;

        // Remove default Scripts and Styles.
        wp_deregister_script( 'cfct-build-js' );
        wp_deregister_style( 'cfct-build-css' );

        // Import. (note sure why this isn't called automatically).
        $this->_builder->import_included_rows();

        // Register Standard Modules.
        $this->registerModule( 'HTMLModule' );
        $this->registerModule( 'SidebarModule' );
        $this->registerModule( 'HeroModule' );
        $this->registerModule( 'ImageModule' );
        $this->registerModule( 'GalleryModule' );
        $this->registerModule( 'LoopModule' );
        $this->registerModule( 'CarouselModule' );
        $this->registerModule( 'CalloutModule' );
        $this->registerModule( 'EditorModule' );
        $this->registerModule( 'VideoModule' );

        // Register Rows.
        $this->registerRow( 'cfct_row_a' );
        $this->registerRow( 'cfct_row_ab' );
        $this->registerRow( 'cfct_row_ab_c' );
        $this->registerRow( 'cfct_row_a_bc' );
        $this->registerRow( 'cfct_row_abc' );
        $this->registerRow( 'RowFourColumns' );

      }

      /**
       * @return array
       */
      public function module_html( $ret, $data ) {
        return $this->post_types;
      }

      /**
       * Row Directories.
       *
       * @param $_dirs
       *
       * @return array|string
       */
      public function set_row_directories( $_dirs ) {
        return $this->row_directories;
      }

      /**
       * Module Directories.
       *
       * @param $_dirs
       *
       * @return array|string
       */
      public function set_module_directories( $_dirs ) {
        return $this->module_directories;
      }

      /**
       * Library Location.
       *
       * @param $location
       *
       * @return mixed
       */
      public function cfct_build_loc( $location ) {
        $location[ 'loc' ]  = 'theme';
        $location[ 'path' ] = dirname( $this->path );
        $location[ 'url' ]  = site_url( '/vendor/usabilitydynamics/lib-carrington/lib' );

        return $location;
      }

      /**
       * Enable Templates
       *
       */
      private function templates() {

        add_filter( 'cfct-build-enable-templates', function () {
          return true;
        } );

      }

      /**
       * Twitter Booststrap Classes.
       *
       */
      private function bootstrap() {

        add_filter( 'cfct-block-c6-12-classes', function ( $classes ) {
          return array_merge( array( 'column', 'col-md-4', 'col-sm-4', 'col-lg-4' ), $classes );
        } );

        add_filter( 'cfct-block-c6-34-classes', function ( $classes ) {
          return array_merge( array( 'column', 'col-md-4', 'col-sm-4', 'col-lg-4' ), $classes );
        } );

        add_filter( 'cfct-block-c6-56-classes', function ( $classes ) {
          return array_merge( array( 'column', 'col-md-4', 'col-sm-6', 'col-lg-4' ), $classes );
        } );

        add_filter( 'cfct-block-c6-123-classes', function ( $classes ) {
          return array_merge( array( 'column', 'col-md-6', 'col-sm-6', 'col-lg-6' ), $classes );
        } );

        add_filter( 'cfct-block-c6-456-classes', function ( $classes ) {
          return array_merge( array( 'column', 'col-md-6', 'col-sm-6', 'col-lg-6' ), $classes );
        } );

        add_filter( 'cfct-block-c4-12-classes', function ( $classes ) {
          return array_merge( array( 'column', 'col-md-6', 'col-sm-6', 'col-lg-6' ), $classes );
        } );

        add_filter( 'cfct-block-c4-34-classes', function ( $classes ) {
          return array_merge( array( 'column', 'col-md-6', 'col-sm-6', 'col-lg-6' ), $classes );
        } );

        add_filter( 'cfct-block-c6-1234-classes', function ( $classes ) {
          return array_merge( array( 'column', 'col-md-8', 'col-sm-12', 'col-lg-8' ), $classes );
        } );

        add_filter( 'cfct-block-c6-3456-classes', function ( $classes ) {
          return array_merge( array( 'column', 'col-md-8', 'col-sm-12', 'col-lg-8' ), $classes );
        } );

        add_filter( 'cfct-block-c6-123456-classes', function ( $classes ) {
          return array_merge( array( 'column', 'col-md-12', 'col-sm-12', 'col-lg-12' ), $classes );
        } );

        add_filter( 'cfct-block-c4-1234-classes', function ( $classes ) {
          return array_merge( array( 'column', 'col-md-12', 'col-sm-12', 'col-lg-12' ), $classes );
        } );

        add_filter( 'cfct-block-c8-12-classes', function ( $classes ) {

          return array_merge( array(
            'column',
            'col-md-12',
            'col-sm-12',
            'col-lg-12',
          ), $classes );

        } );

      }

      /**
       * Register Module
       *
       * @param $classname
       * @param $args
       *
       * @return bool
       */
      public function registerModule( $classname, $args = array() ) {
        global $cfct_build;

        _doing_it_wrong( 'register', __( 'Module registration called too early, before init.' ), 0 );

        if( func_num_args() > 1 && !is_array( $args ) ) {
          _deprecated_argument( __FUNCTION__, '1.0.2', 'Use of the <code>$id</code> parameter when registering a module has been deprecated. Pass only the module\'s classname when registering your module' );
          $args = array();
          list( $classname, $args ) = func_get_args();
        }

        if( $cfct_build instanceof \cfct_build_common ) {
          $cfct_build->template->register_type( 'module', $classname, $args );
          return true;
        } else {
          return false;
        }

      }

      /**
       * Register Row Calss
       *
       * @param       $classname
       * @param array $args
       *
       * @return bool
       */
      public function registerRow( $classname, $args = array() ) {
        global $cfct_build;

        _doing_it_wrong( 'register', __( 'Row registration called too early, before init.' ), 0 );

        if( func_num_args() > 1 && !is_array( $args ) ) {
          _deprecated_argument( __FUNCTION__, '1.0.2', 'Use of the <code>$id</code> parameter when registering a module has been deprecated. Pass only the module\'s classname when registering your module' );
          $args = array();
          list( $classname, $args ) = func_get_args();
        }

        if( $cfct_build instanceof \cfct_build_common ) {
          $cfct_build->template->register_type( 'row', $classname );

          return true;
        }

      }

      /**
       * Easy way of adding custom styles to Carrington Build Module style selector
       *
       * Type Options:
       *  - cfct_module_rich_text
       *  - cfct_module_callout
       *  - cfct_module_heading
       *  - cfct_module_loop_subpages
       *
       * @todo Add check to make sure image file exists
       *
       * @param bool   $class
       * @param string $image_path
       * @param string $type
       *
       * @return string HTML
       */
      public static function add_module_style( $class = false, $image_path = '', $type = 'general' ) {

        if( $image_path && $class ) {
          add_filter( 'udx:theme:carrington:styles', create_function( '$options, $type="' . $type . '", $image_path="' . $image_path . '", $class="' . $class . '" ', '  $options[$type][$class] = $image_path;  return $options; ' ) );
        }

      }

      /**
       * Style -> image mapping for style chooser
       *
       * @return array
       */
      public static function admin_theme_style_images( $type ) {

        $options[ 'general' ] = array();

        $options[ 'post_callout_module' ] = array();

        $options[ 'cfct_module_callout' ] = array();

        $options = apply_filters( 'udx:theme:carrington:styles', $options );

        //** Merge General Styles into Post Callout Module */
        $options[ 'post_callout_module' ] = array_merge( $options[ 'post_callout_module' ], $options[ 'general' ] );

        //** Merge Post Callout module (and thus General styles) into regular Callout */
        $options[ 'cfct_module_callout' ] = array_merge( $options[ 'cfct_module_callout' ], $options[ 'post_callout_module' ] );

        //** Return either a specific module style or general */
        $return = ( isset( $options[ $type ] ) ? $options[ $type ] : $options[ 'general' ] );

        return $return;

      }

      /**
       * Common function for adding style chooser
       *
       * @param string $form_html - HTML of module admin form
       * @param array  $data - form save data
       *
       * @return string HTML
       */
      public static function _theme_chooser( $form_html, $data ) {

        $type = $data[ 'module_type' ];

        $style_image_config = self::admin_theme_style_images( $type );

        $selected = null;

        if( !empty( $data[ 'cfct-custom-theme-style' ] ) && !empty( $style_image_config[ $data[ 'cfct-custom-theme-style' ] ] ) ) {
          $selected = $data[ 'cfct-custom-theme-style' ];
        }

        $onclick = 'onclick="cfct_set_theme_choice(this); return false;"';

        $form_html .= '
      <fieldset class="cfct-custom-theme-style">
        <div id="cfct-custom-theme-style-chooser" class="cfct-custom-theme-style-chooser cfct-image-select-b">
          <input type="hidden" id="cfct-custom-theme-style" class="cfct-custom-theme-style-input" name="cfct-custom-theme-style" value="' . ( !empty( $data[ 'cfct-custom-theme-style' ] ) ? esc_attr( $data[ 'cfct-custom-theme-style' ] ) : '' ) . '" />

          <label onclick="cfct_toggle_theme_chooser(this); return false;">Style</label>
          <div class="cfct-image-select-current-image cfct-image-select-items-list-item cfct-theme-style-chooser-current-image" onclick="cfct_toggle_theme_chooser(this); return false;">';

        if( !empty( $selected ) && !empty( $style_image_config[ $selected ] ) ) {
          $form_html .= '
            <div class="cfct-image-select-items-list-item">
              <div class="test1" style="background: #d2cfcf url(' . $style_image_config[ $selected ] . ') 0 0 no-repeat;"></div>
            </div>';

        } else {
          $form_html .= '
      <div class="cfct-image-select-items-list-item"><div style="background: #d2cfcf url(' . home_url( '/vendor/usabilitydynamics/lib-carrington/lib/img/none-icon.png' ) . ') 50% 50% no-repeat;"></div></div>';
        }

        $form_html .= '
      </div>

      <div class="clear"></div>

      <div id="cfct-theme-select-images-wrapper">
        <h4>' . __( 'Select a style...', 'favebusiness' ) . '</h4>
        <div class="cfct-image-select-items-list cfct-image-select-items-list-horizontal cfct-theme-select-items-list">
          <ul class="cfct-image-select-items">
            <li class="cfct-image-select-items-list-item ' . ( empty( $selected ) ? ' active' : '' ) . '" data-image-id="0" ' . $onclick . '>
              <div style="background: #d2cfcf url(' . home_url( '/vendor/usabilitydynamics/lib-carrington/lib/img/none-icon.png' ) . ') no-repeat 50% 50%;"></div>
            </li>';

        foreach( (array) $style_image_config as $style => $image ) {
          $form_html .= '<li class="cfct-image-select-items-list-item' . ( $selected == $style ? ' active' : '' ) . '" data-image-id="' . $style . '" ' . $onclick . '>
        <div class="test2" style="background: url(' . $image . ') 0 0 no-repeat;"></div>
        </li>';
        }

        $form_html .= '
                </ul>
              </div>
            </div>
          </div>
        </fieldset>
      ';

        return $form_html;
      }

      /**
       * Apply the custom theme style
       *
       * @param string $class_string - base module wrapper classes
       * @param array  $data - module save data
       *
       * @return string
       */
      public static function cfct_module_wrapper_classes( $class, $data ) {
        $type = $data[ 'module_type' ];

        $classes = explode( ' ', $class );

        if( $type == 'cfct_module_notice' ) {
          $classes[ ] = 'alert';
        }

        // see if we have a custom theme style to apply
        if( !empty( $data[ 'cfct-custom-theme-style' ] ) ) {
          $classes[ ] = esc_attr( $data[ 'cfct-custom-theme-style' ] );
        }

        $class = trim( implode( ' ', $classes ) );

        return $class;
      }

      /**
       * JS for Theme Chooser in individual Module Admin Screens
       *
       * @param string $js
       *
       * @return string
       */
      public static function _theme_chooser_js( $js ) {
        $js .= preg_replace( '/^(\t){2}/m', '', '

      cfct_set_theme_choice = function(clicked) {
        _this = $(clicked);
        _this.addClass("active").siblings().removeClass("active");
        _wrapper = _this.parents(".cfct-custom-theme-style-chooser");
        _val = _this.attr("data-image-id");
        _background_pos = (_val == "0" ? "50% 50%" : "0 0");

        $("input:hidden", _wrapper).val(_val);

        $(".cfct-image-select-current-image .cfct-image-select-items-list-item > div", _wrapper)
          .css({"background-image": _this.children(":first").css("backgroundImage"), "background-position": _background_pos});

        $("#cfct-theme-select-images-wrapper").slideToggle("fast");
        return false;
      };

      cfct_toggle_theme_chooser = function(clicked) {
        $("#cfct-theme-select-images-wrapper").slideToggle("fast");
        return false;
      }

    ' );

        return $js;
      }

      /**
       * CSS for Theme Chooser in individual Module Admin Screens
       *
       * @param string $css
       *
       * @return string
       */
      public static function _theme_chooser_css( $css ) {
        $css .= preg_replace( '/^(\t){2}/m', '', '
      /* Theme Chooser Additions */
      #cfct-custom-theme-style-chooser .cfct-image-select-current-image {
        display: block;
        height: 100px;
        width: auto;
      }
      #cfct-custom-theme-style-chooser .cfct-image-select-current-image p {
        text-align: left;
        font-size: 1em;
      }
      #cfct-custom-theme-style-chooser .cfct-image-select-current-image,
      #cfct-custom-theme-style-chooser .cfct-image-select-current-image>div {
        cursor: pointer;
      }
      #cfct-custom-theme-style-chooser .cfct-image-select-current-image .cfct-image-select-items-list-item,
      #cfct-custom-theme-style-chooser .cfct-image-select-current-image .cfct-image-select-items-list-item>div {
        height: 55px;
      }

      #cfct-custom-theme-style-chooser .cfct-theme-style-chooser-current-image {
        height: 75px;
      }
      #cfct-custom-theme-style-chooser label {
        float: left;
        display: block;
        width: 120px;
        margin-top: 25px;
      }
      #cfct-custom-theme-style-chooser #cfct-theme-select-images-wrapper {
        display: none;
      }
      .cfct-popup-content.cfct-popup-content-fullscreen fieldset.cfct-custom-theme-style {
        margin: 12px;
      }
      #cfct-theme-select-images-wrapper h4 {
        color: #666;
        font-weight: normal;
        margin: 0 0 5px;
      }
    ' );

        return $css;
      }

      /**
       * Register a filter for each widget module loaded
       *
       * @param string $widget_id - standard wordpress widget_id
       * @param string $module_id - id of module in build
       *
       * @return void
       */
      public static function _theme_admin_form( $widget_id, $module_id ) {
        add_filter( 'cfct-module-' . $module_id . '-admin-form', array( get_class(), '_theme_chooser' ), 10, 2 );
      }

    }

  }

}