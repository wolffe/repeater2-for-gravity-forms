<?php
class GF_Field_Repeater2 extends GF_Field {
    public $type = 'repeater2';

    public static function init_admin() {
        $admin_page = rgget( 'page' );

        if ( $admin_page == 'gf_edit_forms' && ! empty( $_GET['id'] ) ) {
            add_action( 'gform_field_standard_settings' , array( 'GF_Field_Repeater2', 'gform_standard_settings' ), 10, 2 );
            add_action( 'gform_field_appearance_settings' , array( 'GF_Field_Repeater2', 'gform_appearance_settings' ), 10, 2 );
            add_action( 'gform_editor_js_set_default_values', array( 'GF_Field_Repeater2', 'gform_set_defaults' ) );
            add_action( 'gform_editor_js', array( 'GF_Field_Repeater2', 'gform_editor' ) );
            add_filter( 'gform_tooltips', array( 'GF_Field_Repeater2', 'gform_tooltips' ) );
        }

        if ( $admin_page == 'gf_entries' ) {
            add_filter( 'gform_form_post_get_meta', array( 'GF_Field_Repeater2', 'gform_hide_children' ) );
        }
    }

    public static function init_frontend() {
        add_action( 'gform_form_args', array( 'GF_Field_Repeater2', 'gform_disable_ajax' ) );
        add_action( 'gform_enqueue_scripts', array( 'GF_Field_Repeater2', 'gform_enqueue_scripts' ), 10, 2 );
        add_filter( 'gform_pre_render', array( 'GF_Field_Repeater2', 'gform_unhide_children_validation' ) );
        add_filter( 'gform_pre_validation', array( 'GF_Field_Repeater2', 'gform_bypass_children_validation' ) );
        add_filter( 'gform_notification', array( 'GF_Field_Repeater2', 'gform_ensure_repeater_data_in_notification' ), 10, 3 );
        add_filter( 'gform_field_value', array( 'GF_Field_Repeater2', 'gform_handle_repeater_file_uploads' ), 10, 3 );
    }

    public static function gform_enqueue_scripts( $form, $is_ajax ) {
        if ( ! empty( $form ) ) {
            if ( GF_Field_Repeater2::get_field_index( $form ) !== false ) {
                wp_enqueue_script( 'gforms_repeater2_postcapture_js', plugins_url( 'js/jquery.postcapture.min.js', __FILE__ ), array( 'jquery' ), '0.0.1' );
                wp_enqueue_script( 'jquery_mask', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.js', array( 'jquery' ), '1.14.16' );
                wp_enqueue_script( 'gforms_repeater2_js', plugins_url( 'js/gf-repeater2.js', __FILE__ ), array( 'jquery', 'jquery_mask' ), GF_REPEATER_VERSION );

                wp_enqueue_style( 'gforms_repeater2_css', plugins_url( 'css/gf-repeater2.css', __FILE__ ), array(), GF_REPEATER_VERSION );
            }
        }
    }

    public function get_form_editor_field_title() {
        return 'Repeater';
    }

	public function get_form_editor_field_settings() {
		return array(
			'admin_label_setting',
			'css_class_setting',
			'description_setting',
			'error_message_setting',
			'label_setting',
			'prepopulate_field_setting',
			'conditional_logic_field_setting',
		);
	}

	public static function gform_set_defaults() {
		echo "
			case \"repeater2\" :
				field.label = \"Repeater\";
			break;
		";
	}

	public static function gform_standard_settings($position, $form_id) {
		if ($position == 1600) {
			echo "<li class=\"repeater2_settings field_setting\">
					<label for=\"field_repeater2_start\">Start ";

			gform_tooltip('form_field_repeater2_start');

			echo "	</label>
					<input type=\"number\" id=\"field_repeater2_start\" min=\"1\" value=\"1\" onchange=\"SetFieldProperty('start', this.value);\">
				</li>";

			echo "<li class=\"repeater2_settings field_setting\">
					<label for=\"field_repeater2_min\">Min ";

			gform_tooltip('form_field_repeater2_min');

			echo "	</label>
					<input type=\"number\" id=\"field_repeater2_min\" min=\"1\" value=\"1\" onchange=\"SetFieldProperty('min', this.value);\">
				</li>";

			echo "<li class=\"repeater2_settings field_setting\">
					<label for=\"field_repeater2_max\">Max ";

			gform_tooltip('form_field_repeater2_max');

			echo "	</label>
					<input type=\"number\" id=\"field_repeater2_max\" min=\"1\" onchange=\"SetFieldProperty('max', this.value);\">
				</li>";
		}
	}

	public static function gform_appearance_settings($position, $form_id) {
		if ($position == 400) {
			echo "<li class=\"repeater2_settings field_setting\">
					<input type=\"checkbox\" id=\"field_repeater2_hideLabel\" onchange=\"SetFieldProperty('hideLabel', this.checked);\"> 
					<label for=\"field_repeater2_hideLabel\" class=\"inline\">Hide Label & Description ";

			gform_tooltip('form_field_repeater2_hideLabel');

			echo "	</label>
				</li>";
		}
	}

	public static function gform_editor() {
		echo "<script type=\"text/javascript\">
				fieldSettings['repeater2'] += ', .repeater2_settings';
				jQuery(document).bind('gform_load_field_settings', function(event, field, form){
					jQuery('#field_repeater2_start').val(field['start']);
					jQuery('#field_repeater2_min').val(field['min']);
					jQuery('#field_repeater2_max').val(field['max']);
					jQuery('#field_repeater2_hideLabel').prop('checked', field['hideLabel']);
				});
			</script>";
	}

	public static function gform_tooltips($tooltips) {
		$tooltips['form_field_repeater2_start'] = "The number of times the repeater2 will be repeated when the form is rendered. Leaving this field blank or setting it to a number higher than the maximum number is the same as setting it to 1.";
		$tooltips['form_field_repeater2_min'] = "The minimum number of times the repeater2 is allowed to be repeated. Leaving this field blank or setting it to a number higher than the maximum field is the same as setting it to 1.";
		$tooltips['form_field_repeater2_max'] = "The maximum number of times the repeater2 is allowed to be repeated. Leaving this field blank or setting it to a number lower than the minimum field is the same as setting it to unlimited.";
		$tooltips['form_field_repeater2_hideLabel'] = "If this is checked, the repeater2 label and description will not be shown to users on the form.";
		return $tooltips;
	}

	function validate($value, $form) {
		$repeater2_required = $this->repeater2RequiredChildren;

		if (!empty($repeater2_required)) {
			$dataArray = json_decode($value, true);

			foreach ($form['fields'] as $key=>$value) {
				$fieldKeys[$value['id']] = $key;

				if (is_array($value['inputs'])) {
					foreach ($value['inputs'] as $inputKey=>$inputValue) {
						$inputKeys[$value['id']][$inputValue['id']] = $inputKey;
					}
				}
			}

			if ($dataArray['repeatCount'] < $this->min) {
				$this->failed_validation  = true;
				$this->validation_message = "A minimum number of ".$this->min." is required.";
				return;
			}

			if ($this->max && $dataArray['repeatCount'] > $this->max) {
				$this->failed_validation  = true;
				$this->validation_message = "A maximum number of ".$this->max." is allowed.";
				return;
			}

			for ($i = 1; $i < $dataArray['repeatCount'] + 1; $i++) {
				foreach ($dataArray['children'] as $field_id=>$field) {
					$inputNames = $field['inputs'];
					$repeatSkips = rgars($field, 'conditionalLogic/skip');


					if (!is_array($inputNames)) { continue; }

					if (is_array($repeatSkips)) {
						if (in_array($i, $repeatSkips) || in_array('all', $repeatSkips)) { continue; }
					}

					foreach ($inputNames as $inputName) {
						if (is_array($inputName)) { $inputName = reset($inputName); }

						if (substr($inputName, -2) == '[]') {
							$getInputName = substr($inputName, 0, strlen($inputName) - 2).'-'.$dataArray['repeater2Id'].'-'.$i;
						} else {
							$getInputName = $inputName.'-'.$dataArray['repeater2Id'].'-'.$i;
						}

						$getInputName = str_replace('.', '_', strval($getInputName));
						$getInputData = rgpost($getInputName);
						$getInputIdNum = preg_split("/(_|-)/", $getInputName);

						if (in_array($getInputIdNum[1], $repeater2_required)) {
							$fieldKey = $fieldKeys[$getInputIdNum[1]];
							$fieldType = $form['fields'][$fieldKey]['type'];
							$failedValidation = false;

							switch($fieldType) {
								case 'name':
									$requiredIDs = array(3, 6);
									if (in_array($getInputIdNum[2], $requiredIDs) && empty($getInputData)) { $failedValidation = true; }
									break;
								case 'address':
									$skipIDs = array(2);
									if (!in_array($getInputIdNum[2], $skipIDs) && empty($getInputData)) { $failedValidation = true; }
									break;
								default:
									if (empty($getInputData)) { $failedValidation = true; }
							}

							if ($failedValidation) {
								$this->failed_validation  = true;
								if ($this->errorMessage) { $this->validation_message = $this->errorMessage; } else { $this->validation_message = "A required field was left blank."; }
								return;
							}
						}
					}
				}
			}
		}
	}

	public function get_field_content( $value, $force_frontend_label, $form ) {
		if ( is_admin() ) {
			$admin_buttons = $this->get_admin_buttons();
			$field_content = "{$admin_buttons}
				<div class=\"gf-pagebreak-first gf-pagebreak-container gf-repeater2 gf-repeater2-start\">
					<div class=\"gf-pagebreak-text-before\">Begin Repeater</div>
					<div class=\"gf-pagebreak-text-main\"><span>REPEATER</span></div>
					<div class=\"gf-pagebreak-text-after\">Top of Repeater</div>
				</div>";
		} else {
			$field_label		= $this->get_field_label($force_frontend_label, $value);
			$description		= $this->get_description($this->description, 'gsection_description gf_repeater2_description');
			$hide_label			= $this->hideLabel;
			$validation_message = ( $this->failed_validation && ! empty( $this->validation_message ) ) ? sprintf( "<div class='gfield_description validation_message'>%s</div>", $this->validation_message ) : '';
			if (!empty($field_label)) { $field_label = "<h2 class='gf_repeater2_title'>{$field_label}</h2>"; } else { $field_label = ''; }
			if ($hide_label) { $field_label = ''; $description = ''; }
			$field_content = "<div class=\"ginput_container ginput_container_repeater2\">{$field_label}{FIELD}</div>{$description}{$validation_message}";
		}
		return $field_content;
	}

	public function get_field_input($form, $value = '', $entry = null) {
		if (is_admin()) {
			return '';
		} else {
			$form_id			= $form['id'];
			$is_entry_detail	= $this->is_entry_detail();
			$is_form_editor		= $this->is_form_editor();
			$id					= (int) $this->id;
			$field_id			= $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";
			$tabindex  			= $this->get_tabindex();
			$repeater2_parem		= $this->inputName;
			$repeater2_start		= $this->start;
			$repeater2_min		= $this->min;
			$repeater2_max		= $this->max;
			$repeater2_required	= $this->repeater2RequiredChildren;
			$repeater2_children	= $this->repeater2Children;

			if (!empty($repeater2_parem)) {
				$repeater2_parem_value = GFFormsModel::get_parameter_value($repeater2_parem, $value, $this);
				if (!empty($repeater2_parem_value)) { $repeater2_start = $repeater2_parem_value; }
			}

			if (!empty($repeater2_children)) {
				$repeater2_children_info = array();
				$repeater2_parems = GF_Field_Repeater2::get_children_parem_values($form, $repeater2_children);

				foreach($repeater2_children as $repeater2_child) {
					$repeater2_children_info[$repeater2_child] = array();
					$repeater2_child_field_index = GF_Field_Repeater2::get_field_index($form, 'id', $repeater2_child);

					if (!empty($repeater2_required)) {
						if (in_array($repeater2_child, $repeater2_required)) {
							$repeater2_children_info[$repeater2_child]['required'] = true;
						}
					}

					if (!empty($repeater2_parems)) {
						if (array_key_exists($repeater2_child, $repeater2_parems)) {
							$repeater2_children_info[$repeater2_child]['prePopulate'] = $repeater2_parems[$repeater2_child];
						}
					}

					if ($repeater2_child_field_index !== false) {
						if ($form['fields'][$repeater2_child_field_index]['inputMask']) {
							$repeater2_children_info[$repeater2_child]['inputMask'] = $form['fields'][$repeater2_child_field_index]['inputMaskValue'];
						} elseif ($form['fields'][$repeater2_child_field_index]['type'] == 'phone' && $form['fields'][$repeater2_child_field_index]['phoneFormat'] = 'standard') {
							$repeater2_children_info[$repeater2_child]['inputMask'] = "(999) 999-9999";
						}

						if ($form['fields'][$repeater2_child_field_index]['conditionalLogic']) {
							$repeater2_children_info[$repeater2_child]['conditionalLogic'] = $form['fields'][$repeater2_child_field_index]['conditionalLogic'];
						}
					}
				}

				$repeater2_children = $repeater2_children_info;
			}

			if (empty($value)) {
                $value = array();
				$value['formId'] = $form_id;
				if (!empty($repeater2_start)) { $value['start'] = $repeater2_start; }
				if (!empty($repeater2_min)) { $value['min'] = $repeater2_min; }
				if (!empty($repeater2_max)) { $value['max'] = $repeater2_max; }
				if (!empty($repeater2_children)) { $value['children'] = $repeater2_children; }

				$value = json_encode($value);
			}

			return sprintf("<input name='input_%d' id='%s' type='hidden' class='gform_repeater2' value='%s' %s />", $id, $field_id, $value, $tabindex);
		}
	}

	public function get_value_save_entry($value, $form, $input_name, $lead_id, $lead) {
		$dataArray = json_decode($value, true);
		$value = Array();

		for ($i = 1; $i < $dataArray['repeatCount'] + 1; $i++) {
			foreach ($dataArray['children'] as $field_id=>$field) {
				$inputData = Array();

				if (array_key_exists('inputs', $field)) {
					$inputNames = $field['inputs'];
					$repeatSkips = rgars($field, 'conditionalLogic/skip');


					if (is_array($repeatSkips)) {
						if (in_array($i, $repeatSkips) || in_array('all', $repeatSkips)) { continue; }
					}
					
					if (is_array($inputNames)) {
						foreach ($inputNames as $inputName) {
							if (substr($inputName, -2) == '[]') {
								$getInputName = substr($inputName, 0, strlen($inputName) - 2).'-'.$dataArray['repeater2Id'].'-'.$i;
							} else {
								$getInputName = $inputName.'-'.$dataArray['repeater2Id'].'-'.$i;
							}

							$getInputName_clean = str_replace('.', '_', strval($getInputName));
							$getInputData = rgpost($getInputName_clean);
							
							// Handle file uploads specially - check for file upload field type
							$fieldType = GF_Field_Repeater2::get_field_type($form, $field_id);
							if ($fieldType == 'fileupload') {
								// For file uploads, we need to process the $_FILES array
								$file_input_name = $getInputName_clean;
								if (isset($_FILES[$file_input_name]) && !empty($_FILES[$file_input_name]['name'])) {
									$file_data = $_FILES[$file_input_name];
									if ($file_data['error'] == 0) {
										// Store the file information with proper URL
										$upload_dir = wp_upload_dir();
										$file_url = $upload_dir['baseurl'] . '/gravity_forms/' . $form['id'] . '/' . $file_data['name'];
										$inputData[] = '<a href="' . esc_url($file_url) . '" target="_blank">' . esc_html($file_data['name']) . '</a>';
									}
								}
							} else {
								// Handle regular field data
								if (!empty($getInputData)) {
									if (is_array($getInputData)) {
										// Special handling for time fields
										if ($fieldType == 'time' && count($getInputData) == 2) {
											// Format time as HH:MM
											$hours = str_pad($getInputData[0], 2, '0', STR_PAD_LEFT);
											$minutes = str_pad($getInputData[1], 2, '0', STR_PAD_LEFT);
											$inputData[] = $hours . ':' . $minutes;
										} else {
											foreach ($getInputData as $theInputData) {
												$inputData[] = $theInputData;
											}
										}
									} else {
										$inputData[] = $getInputData;
									}
								}
							}
						}
					}
				} else {
					if (GF_Field_Repeater2::get_field_type($form, $field_id) == 'section') { $inputData = '[gfRepeater-section]'; }
				}

				$childValue[$field_id] = $inputData;
			}
			$value[$i] = $childValue;
		}
		
		// Ensure proper serialization for WordPress 6.8 compatibility
		return wp_json_encode($value);
	}

	public function get_value_entry_list($value, $entry, $field_id, $columns, $form) {
		if (empty($value)) {
			return '';
		} else {
			// Handle both old serialized format and new JSON format
			if (is_serialized($value)) {
				$dataArray = GFFormsModel::unserialize($value);
			} else {
				$dataArray = json_decode($value, true);
			}
			$arrayCount = count($dataArray);
			if ($arrayCount > 1) { $returnText = $arrayCount.' entries'; } else { $returnText = $arrayCount.' entry'; }
			return $returnText;
		}
	}

	public function get_value_entry_detail($value, $currency = '', $use_text = false, $format = 'html', $media = 'screen') {
		if (empty($value)) {
			return '';
		} else {
			// Handle both old serialized format and new JSON format
			if (is_serialized($value)) {
				$dataArray = GFFormsModel::unserialize($value);
			} else {
				$dataArray = json_decode($value, true);
			}
			$arrayCount = count($dataArray);
			$output = "\n";
			$count = 0;
			$repeatCount = 0;
			$display_empty_fields = rgget('gf_display_empty_fields', $_COOKIE);
			$form_id = $this->formId;
			$get_form = GFFormsModel::get_form_meta_by_id($form_id);
			$form = $get_form[0];

			foreach ($dataArray as $key=>$value) {
				$repeatCount++;
				$tableContents = '';

				if (!empty($value) && !is_array($value)) {
					$save_value = $value;
					unset($value);
					$value[0] = $save_value;
				}

				foreach ($value as $childKey => $childValue) {
					$count++;
					$childValueOutput = '';
					
					if (empty($display_empty_fields) && count((array) $childValue) == 0) {
                        continue;
                    }

					if (is_numeric($childKey)) {
						$field_index = GF_Field_Repeater2::get_field_index($form, 'id', $childKey);
						if ($field_index === false) { continue; }
						$entry_title = $form['fields'][$field_index]['label'];
					} else {
						$entry_title = $childKey;
					}

					$entry_title = str_replace('[gfRepeater-count]', $repeatCount, $entry_title);

					if ($format == 'html') {
						if ($childValue == '[gfRepeater-section]') {
							if ($media == 'email') {
								$tableStyling = ' style="font-size:14px;font-weight:bold;background-color:#eee;border-bottom:1px solid #dfdfdf;padding:7px 7px"';
							} else {
								$tableStyling = ' class="entry-view-section-break"';
							}
						} else {
							if ($media == 'email') {
								$tableStyling = ' style="background-color:#EAF2FA;font-family:sans-serif;font-size:12px;font-weight:bold"';
							} else {
								$tableStyling = ' class="entry-view-field-name"';
							}
						}

						$tableContents .= "<tr>\n<td colspan=\"2\"".$tableStyling.">".$entry_title."</td>\n</tr>\n";
					} else {
						$tableContents .= $entry_title.": ";
					}

					if (is_array($childValue)) {
						if (count($childValue) == 1) {
							$childValueOutput = $childValue[0];
						} elseif (count($childValue) > 1) {
							// Check if this is a time field (2 values: hours and minutes)
							$field_index = GF_Field_Repeater2::get_field_index($form, 'id', $childKey);
							$is_time_field = false;
							if ($field_index !== false && $form['fields'][$field_index]['type'] == 'time') {
								$is_time_field = true;
							}
							
							if ($is_time_field && count($childValue) == 2) {
								// Format time as HH:MM
								$hours = str_pad($childValue[0], 2, '0', STR_PAD_LEFT);
								$minutes = str_pad($childValue[1], 2, '0', STR_PAD_LEFT);
								$childValueOutput = $hours . ':' . $minutes;
							} else {
								if ($format == 'html') {
									if ($media == 'email') {
										$childValueOutput = "<ul style=\"list-style:none;margin:0;padding:0;\">\n";
									} else {
										$childValueOutput = "<ul>\n";
									}
								}

								foreach ($childValue as $childValueData) {
									if ($format == 'html') {
										$childValueOutput .= "<li>".$childValueData."</li>";
									} else {
										$childValueOutput .= $childValueData."\n";
									}
								}
								
								if ($format == 'html') { $childValueOutput .= "</ul>\n"; }
							}
						}

						if ($media == 'email') { $tableStyling = ''; } else { $tableStyling = ' class=\"entry-view-field-value\"'; }

						if ($format == 'html') {
							$tableContents .= "<tr>\n<td colspan=\"2\"".$tableStyling.">".$childValueOutput."</td>\n</tr>\n";
						} else {
							$tableContents .= $childValueOutput."\n";
						}
					}
				}

				if (!empty($tableContents)) {
					if ($format == 'html') {
						if ($media == 'email') { $tableStyling = ' width="100%" border="0" cellpadding="5" bgcolor="#FFFFFF"'; } else { $tableStyling = ' class="widefat fixed entry-detail-view"'; }
						$output .= "<table cellspacing=\"0\"".$tableStyling.">\n";
						$output .= $tableContents;
						$output .= "</table>\n";
					} else {
						$output .= $tableContents."\n";
					}
				}
			}
		}

		if ($count !== 0) {
			if ($format == 'text') { $output = rtrim($output); }
			return $output;
		} else { return ''; }
	}

	public function get_value_merge_tag($value, $input_id, $entry, $form, $modifier, $raw_value, $url_encode, $esc_html, $format, $nl2br) {
		$output = GF_Field_Repeater2::get_value_entry_detail($raw_value, '', false, $format, 'email');
		$output = preg_replace("/[\r\n]+/", "\n", $output);
		
		// Ensure all repeater data is included in emails
		if (empty($output) && !empty($raw_value)) {
			$output = $this->get_value_entry_detail($raw_value, '', false, $format, 'email');
		}
		
		return trim($output);
	}

	public function get_value_export($entry, $input_id = '', $use_text = false, $is_csv = false) {
		if (empty($input_id)) { $input_id = $this->id; }
		$output = rgar($entry, $input_id);
		$output = GF_Field_Repeater2::get_value_entry_detail($output, '', false, 'text', 'email');
		$output = preg_replace("/[\r\n]+/", ", ", trim($output));
		return $output;
	}

	public static function gform_hide_children($form) {
		$form_id = $form['id'];
		$repeater2Children = Array();
		$grid_modified = false;
		$grid_meta = GFFormsModel::get_grid_column_meta($form_id);

		foreach($form['fields'] as $key=>$field) {
			if ($field->type == 'repeater2') {
				if (is_array($field->repeater2Children)) { $repeater2Children = array_merge($repeater2Children, $field->repeater2Children); }
			} elseif ($field->type == 'repeater2-end') { array_push($repeater2Children, $field->id); }

			if (!empty($repeater2Children)) {
				if (in_array($field->id, $repeater2Children)) {
					unset($form['fields'][$key]);

					if (is_array($grid_meta)) {
						$grid_pos = array_search($field->id, $grid_meta);
						if ($grid_pos) {
							$grid_modified = true;
							unset($grid_meta[$grid_pos]);
						}
					}
				}
			}
		}

		if ($grid_modified) { GFFormsModel::update_grid_column_meta($form_id, $grid_meta); }

		$form['fields'] = array_values($form['fields']);

		return $form;
	}

	public static function gform_disable_ajax($args) {
		$get_form = GFFormsModel::get_form_meta_by_id($args['form_id']);
		$form = reset($get_form);

		if (GF_Field_Repeater2::get_field_index($form) !== false) {
			$args['ajax'] = false;
		}

		return $args;
	}

	public static function gform_bypass_children_validation($form) {
		if (GF_Field_Repeater2::get_field_index($form) === false) { return $form; }

		$repeater2Children = Array();

		foreach($form['fields'] as $key=>$field) {
			if ($field->type == 'repeater2') {
				if (is_array($field->repeater2Children)) { $repeater2Children = array_merge($repeater2Children, $field->repeater2Children); }
			}

			if (!empty($repeater2Children)) {
				if (in_array($field->id, $repeater2Children) && !$field->adminOnly) {
					$form['fields'][$key]['adminOnly'] = true;
					$form['fields'][$key]['repeater2ChildValidationHidden'] = true;
				}
			}
		}

		return $form;
	}

	public static function gform_unhide_children_validation($form) {
		if (GF_Field_Repeater2::get_field_index($form) === false) { return $form; }
		
		foreach($form['fields'] as $key=>$field) {
			if ($field->repeater2ChildValidationHidden) {
				$form['fields'][$key]['adminOnly'] = false;
				$form['fields'][$key]['repeater2ChildValidationHidden'] = false;
			}
		}

		return $form;
	}

	public static function gform_ensure_repeater_data_in_notification( $notification, $form, $entry ) {
		// Ensure repeater field data is properly included in email notifications
		foreach ( $form['fields'] as $field ) {
			if ( $field->type == 'repeater2' ) {
				$field_value = rgar( $entry, $field->id );
				if ( ! empty( $field_value ) ) {
					// Force the field to process its data for email display
					$field->get_value_entry_detail( $field_value, '', false, 'html', 'email' );
				}
			}
		}
		return $notification;
	}

	public static function gform_handle_repeater_file_uploads( $value, $field, $form ) {
		// Handle file uploads in repeater context
		if ( $field->type == 'fileupload' && ! empty( $_FILES ) ) {
			$field_id = $field->id;
			$input_name = 'input_' . $field_id;
			
			// Check if this is a repeater field by looking for the naming pattern
			foreach ( $_FILES as $file_key => $file_data ) {
				if ( strpos( $file_key, $input_name ) === 0 && ! empty( $file_data['name'] ) ) {
					// This is a file upload from a repeater field
					if ( $file_data['error'] == 0 ) {
						// Process the file upload using Gravity Forms methods
						$uploaded_file = GFFormsModel::get_temp_filename( $form['id'], $file_data['name'] );
						if ( $uploaded_file ) {
							// Return the file with proper URL for display
							$upload_dir = wp_upload_dir();
							$file_url = $upload_dir['baseurl'] . '/gravity_forms/' . $form['id'] . '/' . $file_data['name'];
							return '<a href="' . esc_url($file_url) . '" target="_blank">' . esc_html($file_data['name']) . '</a>';
						}
					}
				}
			}
		}
		
		return $value;
	}

	public static function get_field_index($form, $key = 'type', $value = 'repeater2') {
		if (is_array($form)) {
			if (!array_key_exists('fields', $form)) { return false; }
		} else { return false; }

		foreach ($form['fields'] as $field_key=>$field_value) {
			if (is_object($field_value)) {
				if (property_exists($field_value, $key)) {
					if ($field_value[$key] == $value) { return $field_key; }
				}
			}
		}

		return false;
	}

	public static function get_field_type($form, $id) {
		$field_index = GF_Field_Repeater2::get_field_index($form, 'id', $id);
		if ($field_index !== false) { return $form['fields'][$field_index]['type']; }
		return false;
	}

	public static function get_children_parems($form, $children_ids) {
		foreach($form['fields'] as $key=>$value) {
			if (in_array($value['id'], $children_ids)) {
				if ($value['inputName']) {
					$parems[$value['id']] = $value['inputName'];
				} elseif ($value['inputs']) {
					foreach($value['inputs'] as $key=>$value) {
						if ($value['name']) { $parems[$value['id']] = $value['name']; }
					}
				}
			}
		}
		if (!empty($parems)) { return $parems; } else { return false; }
	}

	public static function get_children_parem_values($form, $children_ids) {
		global $wp_filter;
		$children_parems = GF_Field_Repeater2::get_children_parems($form, $children_ids);

		if (empty($children_parems)) { return false; }

		// Check the URL first
		foreach($_GET as $url_key=>$url_value) {
			$key = array_search($url_key, $children_parems);
			if ($key !== false) {
				$parems[$key][0] = $url_value;
			} else {
				$split_key = preg_split('/\D+\K/', $url_key);
				$key = array_search($split_key[0], $children_parems);
				if ($key !== false) { $parems[$key][$split_key[1]] = $url_value; }
			}
		}

		// Then check the filters
		foreach($wp_filter as $key=>$value) {
			$split_key = preg_split('/^gform_field_value_+\K/', $key);
			if (!empty($split_key[1])) {
				$key1 = array_search($split_key[1], $children_parems);
				if ($key1 !== false) {
					$parems[$key1][0] = apply_filters($key, '');
				} else {
					$split_key2 = preg_split('/\D+\K/', $split_key[1]);
					$key2 = array_search($split_key2[0], $children_parems);
					if ($key2 !== false) { $parems[$key2][$split_key2[1]] = apply_filters($key, ''); }
				}
			}
		}
		if (!empty($parems)) { return $parems; } else { return false; }
	}
}
GF_Fields::register(new GF_Field_Repeater2());
