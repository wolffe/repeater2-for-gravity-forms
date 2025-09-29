<?php
/**
 * Plugin Name: Repeater for Gravity Forms
 * Plugin URI: https://getbutterfly.com/gravity-forms-repeater-plugin/
 * Description: A Gravity Forms add-on that allows specified groups of fields to be repeated by the user.
 * Version: 2.1.6
 * Author: Ciprian Popescu
 * Author URI: http://getbutterfly.com/
 * GitHub Plugin URI: wolffe/repeater2-for-gravity-forms
 * GitHub Branch: master
 * License: GNU General Public License v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

define( 'GF_REPEATER_VERSION', '2.1.6' );
define( 'GF_REPEATER_PATH', basename( __DIR__ ) . '/' . basename( __FILE__ ) );

if ( class_exists( 'GFForms' ) ) {
    GFForms::include_addon_framework();

    class GFRepeater extends GFAddOn {
        protected $_version                  = GF_REPEATER_VERSION;
        protected $_min_gravityforms_version = '2.7';
        protected $_slug                     = 'repeater2addon';
        protected $_path                     = GF_REPEATER_PATH;
        protected $_full_path                = __FILE__;
        protected $_title                    = 'Gravity Forms Repeater Add-On';
        protected $_short_title              = 'Repeater Add-On';

        public function scripts() {
            $scripts = [
                [
                    'handle'    => 'gf_repeater2_js_admin',
                    'src'       => $this->get_base_url() . '/js/gf-repeater2-admin.js',
                    'version'   => $this->_version,
                    'deps'      => [ 'jquery' ],
                    'in_footer' => false,
                    'callback'  => [ $this, 'localize_scripts' ],
                    'strings'   => [ 'page' => rgget( 'page' ) ],
                    'enqueue'   => [
                        [
                            'admin_page' => [ 'form_editor', 'entry_view', 'entry_detail' ]
                        ],
                    ],
                ],
            ];

            return array_merge( parent::scripts(), $scripts );
        }

        public function init_admin() {
            parent::init_admin();

            GF_Field_Repeater2::init_admin();
            GF_Field_Repeater2_End::init_admin();
        }

        public function init_frontend() {
            parent::init_frontend();

            GF_Field_Repeater2::init_frontend();
        }
    }

    new GFRepeater();

    require_once 'class-gf-field-repeater2.php';
    require_once 'class-gf-field-repeater2-end.php';
}
