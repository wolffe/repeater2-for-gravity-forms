<?php
/**
 * Plugin Name: Repeater2 for Gravity Forms
 * Plugin URI: https://getbutterfly.com/gravity-forms-repeater-plugin/
 * Description: A Gravity Forms add-on that allows specified groups of fields to be repeated by the user.
 * Version: 2.0.6
 * Author: Ciprian Popescu
 * Author URI: http://getbutterfly.com/
 * GitHub Plugin URI: wolffe/repeater2-for-gravity-forms
 * GitHub Branch: master
 * License: GNU General Public License v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

/** TODO
https://github.com/kodie/gravityforms-repeater/pull/99/commits/ee00d36ae62e790dba30bf00f5952c809b4c71c3
https://github.com/lukecav/awesome-gravity-forms
/**/

define('GF_REPEATER_VERSION', '2.0.6');
define('GF_REPEATER_PATH', basename(__DIR__) . '/' . basename(__FILE__));

if (class_exists('GFForms')) {
    GFForms::include_addon_framework();

    class GFRepeater extends GFAddOn {
        protected $_version = GF_REPEATER_VERSION;
        protected $_min_gravityforms_version = '2.5';
        protected $_slug = 'repeater2addon';
        protected $_path = GF_REPEATER_PATH;
        protected $_full_path = __FILE__;
        protected $_title = 'Gravity Forms Repeater Add-On';
        protected $_short_title = 'Repeater Add-On';

        public function scripts() {
            $scripts = [
				[
                    'handle'	=> 'gf_repeater2_js_admin',
                    'src'		=> $this->get_base_url() . '/js/gf-repeater2-admin.js',
                    'version'	=> $this->_version,
                    'deps'		=> ['jquery'],
                    'in_footer'	=> false,
                    'callback'	=> [$this, 'localize_scripts'],
                    'strings'	=> ['page' => rgget('page')],
                    'enqueue'	=> [
                        [
                            'admin_page' => ['form_editor', 'entry_view', 'entry_detail']
                        ]
                    ]
                ]
            ];

            return array_merge(parent::scripts(), $scripts);
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

        public function upgrade($previous_version) {
            if (version_compare($previous_version, '1.0.5') == -1) {
                $forms = GFAPI::get_forms(true);

                foreach ($forms as $form) {
                    $entries = GFAPI::get_entries($form['id']);
                    $fields = GFAPI::get_fields_by_type($form, 'repeater2');

                    foreach ($entries as $entry) {
                        foreach ($fields as $field) {
                            if (array_key_exists($field['id'], $entry)) {
                                $dataArray = GFFormsModel::unserialize($entry[$field['id']]);
                                $dataUpdated = false;

                                if (!is_array($dataArray)) { continue; }

                                foreach ($dataArray as $repeater2ChildId=>$repeater2Child) {
                                    foreach ($repeater2Child as $repeatedFieldId=>$repeatedField) {
                                        if (!is_array($repeatedField)) {
                                            if ($repeatedField !== '[gfRepeater-section]') {
                                                $dataUpdated = true;
                                                $dataArray[$repeater2ChildId][$repeatedFieldId] = Array($repeatedField);
                                            }
                                        } else if (reset($repeatedField) == '[gfRepeater-section]') {
                                            $dataUpdated = true;
                                            $dataArray[$repeater2ChildId][$repeatedFieldId] = reset($repeatedField);
                                        }
                                    }
                                }

                                if ($dataUpdated) {
                                    GFAPI::update_entry_field($entry['id'], $field['id'], maybe_serialize($dataArray));
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    new GFRepeater();

    require_once 'class-gf-field-repeater2.php';
    require_once 'class-gf-field-repeater2-end.php';
}
