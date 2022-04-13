var gfRepeater_debug = false;
var gfRepeater_repeater2s = {};
var gfRepeater_submitted = false;
var gfRepeater_repeater2s_is_set = false;

/*
	gfRepeater_getRepeaters()
		Collects all repeater2 info and stores it inside of the global array "gfRepeater_repeater2s". - First phase of setup.
*/
function gfRepeater_getRepeaters() {
	var repeater2Data = jQuery('.gform_wrapper').each(function(){
		var repeater2s = {};
		var formId = this.id.split('_')[2];
		var form = jQuery(this).children('form').first();
		var repeater2Id = 0;

		var repeater2Found = 0;
		var repeater2ChildCount = 0;
		var repeater2ParemCount = 0;
		var parentSection = null;
		var repeater2Info = {};
		var repeater2Children = {};
		var repeater2ChildrenInputData = {};
		var capturedData = {};
		var dataElement;
		var startElement;

		// Remove ajax action from form because ajax enabled forms are not yet supported.
		if (jQuery(form).attr('action') == '/ajax-test') { jQuery(form).removeAttr('action'); }

		jQuery(this).find('.gfield').each(function(){
			if (repeater2Found == 0) {
				if (jQuery(this).has('.ginput_container_repeater2').length) {
					// Repeater Start

					repeater2Id += 1;

					if (gfRepeater_debug) { console.log('Form #'+formId+' - Repeater #'+repeater2Id+' - Start: '+jQuery(this).attr('id')); }

					startElement = jQuery(this);
					dataElement = startElement.find('.gform_repeater2');

					repeater2Info = jQuery(dataElement).val();
					if (repeater2Info) { repeater2Info = JSON.parse(repeater2Info); }

					if (jQuery.captures()) {
						capturedData = jQuery.captures(dataElement.attr('name'));
						if (capturedData) {
							capturedData = JSON.parse(capturedData);
							if (repeater2Id == 1 && capturedData['formId'] == formId) {
								gfRepeater_submitted = true;
							}
						}
					}

					if (repeater2Id == 1) {
						jQuery(form).capture();
					}

					repeater2Found = 1;
				}
			} else {
				if (jQuery(this).has('.ginput_container_repeater2').length) { return false; }
				if (jQuery(this).has('.ginput_container_repeater2-end').length) {
					// Repeater End

					if (gfRepeater_debug) {
						console.log('Form #'+formId+' - Repeater #'+repeater2Id+' - End: '+jQuery(this).attr('id'));
						console.log('Form #'+formId+' - Repeater #'+repeater2Id+' - Children Found: '+(repeater2ChildCount));
					}

					var repeater2Controllers = {};
					var endElement = jQuery(this);
					var addElement = endElement.find('.gf_repeater2_add');
					var removeElement = endElement.find('.gf_repeater2_remove');
					var addFunction = 'gfRepeater_repeatRepeater('+formId+','+repeater2Id+');';
					var removeFunction = 'gfRepeater_unrepeatRepeater('+formId+','+repeater2Id+');';

					jQuery(addElement).attr({onclick:addFunction,onkeypress:addFunction});
					jQuery(removeElement).attr({onclick:removeFunction,onkeypress:removeFunction});

					repeater2Controllers = {
						add:addElement,
						remove:removeElement,
						data:dataElement,
						start:startElement,
						end:endElement
					};

					var repeater2Settings = {};
					var repeater2Start = Number(repeater2Info['start']);
					var repeater2Min = Number(repeater2Info['min']);
					var repeater2Max = Number(repeater2Info['max']);
					if (!repeater2Start || (repeater2Max && repeater2Start > repeater2Max)) { repeater2Start = 1; }
					if (!repeater2Min || (repeater2Max && repeater2Min > repeater2Max)) { repeater2Min = 1; }
					if (!repeater2Max || (repeater2Min && repeater2Max && repeater2Min > repeater2Max)) { repeater2Max = null; }

					repeater2Settings = {
						start:repeater2Start,
						min:repeater2Min,
						max:repeater2Max
					};

					var repeater2data = {};
					var repeater2TabIndex = Number(dataElement.attr('tabindex'));
					var prevRepeatCount = null;
					if (gfRepeater_submitted && capturedData) { prevRepeatCount = capturedData['repeatCount']; }

					repeater2data = {
						repeatCount:1,
						prevRepeatCount:prevRepeatCount,
						childrenCount:repeater2ChildCount,
						paremCount:repeater2ParemCount,
						tabIndex:repeater2TabIndex,
						inputData:repeater2ChildrenInputData
					};

					repeater2s[repeater2Id] = {
						data:repeater2data,
						settings:repeater2Settings,
						controllers:repeater2Controllers,
						children:repeater2Children
					};

					// Set back to defaults for the next repeater2
					repeater2Found = 0;
					repeater2ChildCount = 0;
					repeater2ParemCount = 0;
					parentSection = null;
					repeater2Children = {};
					repeater2ChildrenInputData = {};
					repeater2ChildrenPrePopulate = {};
					repeater2RequiredChildren = null;
				} else {
					// Repeater Child

					repeater2ChildCount +=1;
					var childElement = jQuery(this);
					var childLabel = jQuery(this).children('.gfield_label').text();
					var childId = jQuery(this).attr('id');
					var childIdNum = childId.split('_')[2];
					var childInputs = {};
					var childInputNames = [];
					var childInputCount = 0;
					var childRequired = false;
					var childInfo = repeater2Info['children'][childIdNum];
					var childParentSection = parentSection;
					var childType;
					var inputMask;
					var conditionalLogic;

                    if (childInfo === undefined) {
                        return;
                    }

					if (jQuery(this).has('.ginput_container').length) {
						var childContainerClasses = jQuery(this).find('.ginput_container').attr('class').split(/\s+/);
						var searchFor = 'ginput_container_';

						jQuery.each(childContainerClasses, function(key, value){
							if (value.slice(0, searchFor.length) == searchFor) {
								childType = value.slice(searchFor.length, value.length);
							}
						});
					} else if (jQuery(this).hasClass('gform_hidden')) {
						childType = 'hidden';
					} else if (jQuery(this).hasClass('gsection')) {
						childType = 'section';
					}

					if (childType == 'section') {
						parentSection = repeater2ChildCount;
						childParentSection = null;
					}

					if (childInfo['required']) { childRequired = true; }
					if (childInfo['inputMask']) { inputMask = childInfo['inputMask']; }
					if (childInfo['conditionalLogic']) {
						conditionalLogic = childInfo['conditionalLogic'];
						conditionalLogic['skip'] = [];
					}

					if (gfRepeater_debug) { console.log('Form #'+formId+' - Repeater #'+repeater2Id+' - Child #'+repeater2ChildCount+' - Found: '+childId); }

					jQuery(this).find(':input').each(function(){
						childInputCount += 1;
						var inputElement = jQuery(this);
						var inputId = jQuery(this).attr('id');
						var inputName = jQuery(this).attr('name');
						var inputName2;
						var inputDefaultValue = gfRepeater_getInputValue(inputElement);
						var inputPrePopulate = {};

						if (inputName) {
							if (jQuery.inArray(inputName, childInputNames) == -1) { childInputNames.push(inputName); }
							if (inputName.slice(-2) == '[]') { inputName2 = inputName.slice(0, inputName.length - 2); } else { inputName2 = inputName; }

							if (childInfo['prePopulate']) {
								if (childType == 'checkbox' || childType == 'radio') {
									inputPrePopulate = childInfo['prePopulate'];
								} else if (childInfo['prePopulate'][inputName2.split('_')[1]]) {
									inputPrePopulate = childInfo['prePopulate'][inputName2.split('_')[1]];
								}

								if (inputPrePopulate) {
									jQuery.each(inputPrePopulate, function(key, value){
										if (key > repeater2ParemCount) { repeater2ParemCount = Number(key); }
									});
								}
							}
						};

						childInputs[childInputCount] = {
							element:inputElement,
							id:inputId,
							name:inputName,
							defaultValue:inputDefaultValue,
							prePopulate:inputPrePopulate
						};

						if (gfRepeater_debug) { console.log('Form #'+formId+' - Repeater #'+repeater2Id+' - Child #'+repeater2ChildCount+' - Input Found: '+inputId); }
					});

					repeater2Children[repeater2ChildCount] = {
						element:childElement,
						id:childId,
						idNum:childIdNum,
						inputs:childInputs,
						inputCount:childInputCount,
						required:childRequired,
						type:childType,
						inputMask:inputMask,
						conditionalLogic:conditionalLogic,
						parentSection:childParentSection
					};

					repeater2ChildrenInputData[childIdNum] = childInputNames;
				}
			}
		});

		if (gfRepeater_debug) { console.log('Form #'+formId+' - Repeaters Found: '+(repeater2Id)); }
		if (repeater2Found !== 0) { return false; }

		if (repeater2s) {
			gfRepeater_repeater2s[formId] = repeater2s;
			return true;
		}
	});

	if (repeater2Data) { return true; } else { return false; }
}

/*
	gfRepeater_setRepeaterChildAttrs(formId, repeater2Id, repeater2ChildElement, repeatId)
		Adds the repeater2 ID number and Count number to the end of repeater2 child ID and name.

		formId					The form Id.
		repeater2Id				The repeater2 ID.
		repeater2ChildElement	The child element to run the function for.
		repeatId (Optional)		The repeatId to assign the child to. If a number is not specified, one will be automatically assigned. A 1 is required the first time this function is used during the setup process.
*/
function gfRepeater_setRepeaterChildAttrs(formId, repeater2Id, repeater2ChildElement, repeatId) {
	var repeater2 = gfRepeater_repeater2s[formId][repeater2Id];
	if (!repeatId) { var repeatId = repeater2['data']['repeatCount'] + 1; }
	var childId = jQuery(repeater2ChildElement).attr('id').split('-')[0];
	var childKey = gfRepeater_getIndex(repeater2['children'], 'id', childId);
	var checkValidation = jQuery('#gform_wrapper_' + formId).hasClass('gform_validation_error');

	if (childKey) {
		var failedValidation = false;
		var child = repeater2['children'][childKey];
		var childRequired = child['required'];
		var childType = child['type'];
		var inputCount = child['inputCount'];
		var inputMask = child['inputMask'];
		var tabindex = repeater2['data']['tabIndex'];

		var newRootId = childId+'-'+repeater2Id+'-'+repeatId;
		jQuery(repeater2ChildElement)
			.attr('id', newRootId)
			.attr('data-repeater2-parentId', repeater2Id)
			.attr('data-repeater2-repeatId', repeatId)
			.attr('data-repeater2-childId', childKey)
			.addClass('gf_repeater2_child_field');

		gfRepeater_replaceShortcodes(repeater2ChildElement);
		gfRepeater_doShortcode(repeater2ChildElement, 'count', repeatId);
		gfRepeater_doShortcode(repeater2ChildElement, 'buttons', repeater2['controllers']['add'].parent().clone());
		gfRepeater_doShortcode(repeater2ChildElement, 'add', repeater2['controllers']['add'].clone());
		gfRepeater_doShortcode(repeater2ChildElement, 'remove', repeater2['controllers']['remove'].clone());

		var removeFunction = 'gfRepeater_unrepeatRepeater('+formId+','+repeater2Id+','+repeatId+');';
		jQuery(repeater2ChildElement)
			.find('.gf_repeater2_remove')
			.attr({onclick:removeFunction,onkeypress:removeFunction})
			.show();

		jQuery(repeater2ChildElement)
			.find('.gf_repeater2_add')
			.show();

		jQuery.each(repeater2['children'][childKey]['inputs'], function(key, value){
			var inputId = this['id'];
			var inputName = this['name'];
			var prePopulate = '';

			if (childType == 'radio') {
				var inputElement = gfRepeater_findElementByNameOrId(repeater2ChildElement, null, inputId);
			} else {
				var inputElement = gfRepeater_findElementByNameOrId(repeater2ChildElement, inputName, inputId);
			}

			inputElement.attr('data-repeater2-inputId', key);

			if (inputId) {
				var newInputId = inputId+'-'+repeater2Id+'-'+repeatId;
				jQuery(inputElement).attr('id', newInputId);
				jQuery(repeater2ChildElement).find("label[for^='"+inputId+"']").attr('for', newInputId);
			}

			if (inputName) {
				if (inputName.slice(-2) == '[]') {
					var newInputName = inputName.slice(0, inputName.length - 2)+'-'+repeater2Id+'-'+repeatId+'[]';
				} else {
					var newInputName = inputName+'-'+repeater2Id+'-'+repeatId;
				}

				jQuery(inputElement)
					.attr('name', newInputName)
					.attr('tabindex', tabindex);
			}

            // Maybe include https://www.geeksforgeeks.org/jquery-mask-plugin/
			if (inputMask) { jQuery(inputElement).mask(inputMask); }

			if (this['prePopulate'][repeatId]) {
				prePopulate = this['prePopulate'][repeatId];
			} else if (this['prePopulate'][0]) {
				prePopulate = this['prePopulate'][0];
			}

			if (prePopulate) {
				if (childType == 'checkbox' || childType == 'radio') {
					prePopulateValues = prePopulate.split(',');
					if (jQuery.inArray(key, prePopulateValues) !== -1) {
						prePopulate = true;
					} else {
						prePopulate = false;
					}
				}

				gfRepeater_setInputValue(inputElement, prePopulate);
			}

			if (window['gformInitDatepicker'] && childType == 'date' && inputCount == 2 && key == 1) {
				jQuery(inputElement)
					.removeClass('hasDatepicker')
					.datepicker('destroy')
					.siblings('.ui-datepicker-trigger').remove();
			}

			if (gfRepeater_submitted && checkValidation) {
				if (newInputName) {
					var savedValue = jQuery.captures(newInputName);
					if (savedValue) {
						gfRepeater_setInputValue(inputElement, savedValue);
					}
				}

				if (childRequired) {
					if (newInputName) {
						var splitName = newInputName.replace('.', '_').split(/(_|-)/);
						if (childType == 'name' && jQuery.inArray(splitName[4], ['3', '6']) == -1) { return true; }
						if (childType == 'address' && jQuery.inArray(splitName[4], ['2']) !== -1) { return true; }
					}

					var inputValue = gfRepeater_getInputValue(inputElement);
					if (!inputValue && repeatId <= repeater2['data']['prevRepeatCount']) {
						failedValidation = true;
					}
				}
			}
		});

		if (childRequired) {
			var childLabel = repeater2ChildElement.children('.gfield_label');

			repeater2ChildElement.addClass('gfield_contains_required');

			if (!childLabel.has('.gfield_required').length) {
				childLabel.append("<span class=\"gfield_required\">*</span>");
			}

			if (gfRepeater_submitted && checkValidation) {
				if (failedValidation) {
					repeater2ChildElement.addClass('gfield_error');
					if (!repeater2ChildElement.has('.validation_message').length) {
						repeater2ChildElement.append("<div class=\"gfield_description validation_message\">This field is required.</div>");
					}
				} else {
					repeater2ChildElement
						.removeClass('gfield_error')
						.find('.validation_message').remove();
				}
			}
		}
	}
}

/*
	gfRepeater_resetRepeaterChildrenAttrs(formId, repeater2Id)
		Resets all repeatId's so that they are chronological.
*/
function gfRepeater_resetRepeaterChildrenAttrs(formId, repeater2Id) {
	var repeater2Children = gfRepeater_select(formId, repeater2Id);
	var x = 0;

	jQuery(repeater2Children).each(function(){
		if (jQuery(this).attr('data-repeater2-childid') == 1) {
			x += 1;
		}

		if (jQuery(this).attr('data-repeater2-repeatid') !== x) {
			gfRepeater_setRepeaterChildAttrs(formId, repeater2Id, jQuery(this), x);
		}
	});
}

/*
	gfRepeater_conditionalLogic_set(formId, repeater2Id, repeater2ChildId, repeatId)
		Runs 'gfRepeater_conditionalLogic_do' and assigns change event for all fields involed with the repeater2ChildElement's conditional logic.
*/
function gfRepeater_conditionalLogic_set(formId, repeater2Id, repeater2ChildId, repeatId) {
	gfRepeater_conditionalLogic_do(formId, repeater2Id, repeater2ChildId, repeatId);

	var repeater2 = gfRepeater_repeater2s[formId][repeater2Id];
	var repeater2Child = repeater2['children'][repeater2ChildId]
	var conditionalLogic = repeater2Child['conditionalLogic'];

	jQuery.each(conditionalLogic['rules'], function(key, value){
		var fieldId = value['fieldId'];
		var childId = gfRepeater_getIndex(repeater2['children'], 'idNum', fieldId);

		if (childId !== false) {
			var inputs = gfRepeater_select(formId, repeater2Id, repeatId, childId, '*');
		} else {
			var inputs = jQuery('#field_' + formId + '_' + fieldId + ' :input');
			repeatId = null;
		}

		jQuery.each(inputs, function(key, input){
			jQuery(this).bind('propertychange change click keyup input paste', function(){
				gfRepeater_conditionalLogic_do(formId, repeater2Id, repeater2ChildId, repeatId);
			});
		});
	});
}

/*
	gfRepeater_conditionalLogic_setAll(formId, repeater2Id, repeatId)
		Sets conditionalLogic for all children inside of a repeatId.
*/
function gfRepeater_conditionalLogic_setAll(formId, repeater2Id, repeatId) {
	var repeater2 = gfRepeater_repeater2s[formId][repeater2Id];
	jQuery.each(repeater2['children'], function(key, value){
		if (this.conditionalLogic) {
			gfRepeater_conditionalLogic_set(formId, repeater2Id, key, repeatId);
		}
	});
}

/*
	gfRepeater_conditionalLogic_do(formId, repeater2Id, repeater2ChildId, repeatId)
		Hides or Shows repeater2ChildElement depending on conditional logic.
*/
function gfRepeater_conditionalLogic_do(formId, repeater2Id, repeater2ChildId, repeatId) {
	var repeater2 = gfRepeater_repeater2s[formId][repeater2Id];
	var repeater2Child = repeater2['children'][repeater2ChildId]
	var conditionalLogic = repeater2Child['conditionalLogic'];
	var effectedIds = [repeater2ChildId];
	var conditions = [];
	var conditionsPassed = false;
	var hideField = false;

	jQuery.each(conditionalLogic['rules'], function(key, value){
		var condition = false;
		var fieldId = value['fieldId'];
		var childId = gfRepeater_getIndex(repeater2['children'], 'idNum', fieldId);

		if (childId !== false) {
			var child = repeater2['children'][childId];
			var childElement = gfRepeater_select(formId, repeater2Id, repeatId, childId);

			if (child['type'] == 'checkbox' || child['type'] == 'radio') {
				var inputValue = gfRepeater_getChoiceValue(childElement);
				var multiInput = true;
			} else {
				var inputElement = gfRepeater_select(formId, repeater2Id, repeatId, childId, 1);
				var inputValue = gfRepeater_getInputValue(inputElement);
				var multiInput = false;
			}
		} else {
			var fieldElement = jQuery('#field_' + formId + '_' + fieldId);
			var firstInput = fieldElement.find(':input').first();

			if (firstInput.is(':checkbox, :radio')) {
				var inputValue = gfRepeater_getChoiceValue(fieldElement);
				var multiInput = true;
			} else {
				var inputValue = gfRepeater_getInputValue(firstInput);
				var multiInput = false;
			}
		}

		if (multiInput) {
			if (jQuery.inArray(value['value'], inputValue) !== -1) { inputValue = value['value']; } else { inputValue = false; }
		}

		condition = gf_matches_operation(inputValue, value['value'], value['operator']);
		conditions.push(condition);
	});

	if (conditionalLogic['logicType'] == 'all') {
		if (jQuery.inArray(false, conditions) == -1) { conditionsPassed = true; }
	} else {
		if (jQuery.inArray(true, conditions) !== -1) { conditionsPassed = true; }
	}

	if ((conditionsPassed && conditionalLogic['actionType'] !== 'show') || (!conditionsPassed && conditionalLogic['actionType'] == 'show')) {
		hideField = true;
	}

	if (repeater2Child['type'] == 'section') {
		var sectionChildren = gfRepeater_getIndex(repeater2['children'], 'parentSection', repeater2ChildId, true);
		if (sectionChildren !== false) { effectedIds = effectedIds.concat(sectionChildren); }
	}

	jQuery.each(effectedIds, function(key, value){
		var effectedChild = repeater2['children'][value];
		var effectedLogic = effectedChild['conditionalLogic'];
		var effectedElement = gfRepeater_select(formId, repeater2Id, repeatId, value);
		var skipId = repeatId;

		if (skipId == null) { skipId = 'all'; }

		if (effectedElement.length) {
			if (hideField) {
				effectedElement.hide();

				if (effectedLogic) {
					if (jQuery.inArray(skipId, effectedLogic['skip']) == -1) {
						effectedLogic['skip'].push(skipId);
					}
				}
			} else {
				effectedElement.show();

				if (effectedLogic) {
					if (jQuery.inArray(skipId, effectedLogic['skip']) !== -1) {
						var skipIndex = effectedLogic['skip'].indexOf(skipId);
						effectedLogic['skip'].splice(skipIndex, 1);
					}
				}
			}
		}
	});

	gfRepeater_updateDataElement(formId, repeater2Id);
}

/*
	gfRepeater_doShortcode(element, shortcode, value)
		Finds the 'shortcode' inside of 'element' and replaces it's contents with 'value'.

		element			The element to search inside.
		shortcode		The shortcode to search for.
		value			The value to put inside the shortcode.
*/
function gfRepeater_doShortcode(element, shortcode, value) {
	element.find('.gfRepeater-shortcode-'+shortcode).each(function(){
		jQuery(this).html(value);
	});
}

/*
	gfRepeater_replaceShortcodes(element)
		Replaces any repeater2 shortcodes with spans for those shortcodes.

		element			The element to search and replace.
*/
function gfRepeater_replaceShortcodes(element) {
	var shortcodes = ['count', 'buttons', 'add', 'remove'];

	jQuery.each(shortcodes, function(key, shortcode){
		var html = element.html();
		element.html(html.replace('[gfRepeater-'+shortcode+']', '<span class=\"gfRepeater-shortcode-'+shortcode+'\"></span>'));
	});
}

/*
	gfRepeater_repeatRepeater(formId, repeater2Id)
		Repeats the repeater2 once.

		formId				The form Id.
		repeater2Id			The repeater2 ID number to repeat.
*/
function gfRepeater_repeatRepeater(formId, repeater2Id) {
	var repeater2 = gfRepeater_repeater2s[formId][repeater2Id];
	var repeatId = repeater2['data']['repeatCount'] + 1;
	if (repeater2['settings']['max'] && repeater2['data']['repeatCount'] >= repeater2['settings']['max']) { return; }

	jQuery(repeater2['controllers']['start'])
		.parents('form')
		.trigger('gform_repeater2_before_repeat', [repeater2Id, repeatId]);

	var lastElement = gfRepeater_select(formId, repeater2Id).last();

	jQuery.each(repeater2['children'], function(key, value){
		var clonedElement = jQuery(this.element).clone();

		gfRepeater_resetInputs(formId, repeater2Id, key, clonedElement);
		gfRepeater_setRepeaterChildAttrs(formId, repeater2Id, clonedElement);

		clonedElement.insertAfter(lastElement);

		lastElement = clonedElement;
	});

	gfRepeater_conditionalLogic_setAll(formId, repeater2Id, repeatId);

	repeater2['data']['repeatCount'] += 1;
	gfRepeater_updateDataElement(formId, repeater2Id);
	gfRepeater_updateRepeaterControls(formId, repeater2Id);

	if (window['gformInitDatepicker']) { gformInitDatepicker(); }

	jQuery(repeater2['controllers']['start'])
		.parents('form')
		.trigger('gform_repeater2_after_repeat', [repeater2Id, repeatId]);

	if (gfRepeater_debug) { console.log('Form #'+formId+' - Repeater #'+repeater2Id+' - repeated'); }
}

/*
	gfRepeater_unrepeatRepeater(formId, repeater2Id, repeatId)
		Un-repeats the repeater2 once.

		formId						The form Id.
		repeater2Id					The repeater2 ID number to unrepeat.
		repeatId (Optional)			The repeat ID number to unrepeat. If an ID number is not specified, the last one will be chosen.
*/
function gfRepeater_unrepeatRepeater(formId, repeater2Id, repeatId) {
	var repeater2 = gfRepeater_repeater2s[formId][repeater2Id];
	if (repeater2['data']['repeatCount'] <= repeater2['settings']['min']) { return; }
	if (!repeatId) { var repeatId = repeater2['data']['repeatCount']; }

	jQuery(repeater2['controllers']['start'])
		.parents('form')
		.trigger('gform_repeater2_before_unrepeat', [repeater2Id, repeatId]);

	jQuery.each(repeater2['children'], function(childId, value){
		gfRepeater_select(formId, repeater2Id, repeatId, childId).remove();
	});

	repeater2['data']['repeatCount'] -= 1;
	gfRepeater_updateDataElement(formId, repeater2Id);
	gfRepeater_updateRepeaterControls(formId, repeater2Id);

	if (repeatId !== repeater2['data']['repeatCount'] + 1) {
		gfRepeater_resetRepeaterChildrenAttrs(formId, repeater2Id);
	}

	jQuery(repeater2['controllers']['start'])
		.parents('form')
		.trigger('gform_repeater2_after_unrepeat', [repeater2Id, repeatId]);

	if (gfRepeater_debug) { console.log('Form #'+formId+' - Repeater #'+repeater2Id+' - Repeat #'+repeatId+' - unrepeated'); }
}

/*
	gfRepeater_repeatRepeaterTimes(formId, repeater2Id, timesX)
		Repeats the repeater2 a multiple number of times depeneding on the 'timesX' variable.

		formId				The form Id.
		repeater2Id			The repeater2 ID number to repeat.
		timesX (Optional)	The number of times to repeat the repeater2. Default is 1.
*/
function gfRepeater_repeatRepeaterTimes(formId, repeater2Id, timesX) {
	if (!timesX) { var timesX = 1; }
	for (i = 0; i < timesX; i++) {
		gfRepeater_repeatRepeater(formId, repeater2Id);
	}
}

/*
	gfRepeater_unrepeatRepeaterTimes(formId, repeater2Id, timesX)
		UnRepeats the repeater2 a multiple number of times depeneding on the 'timesX' variable.

		formId				The form Id.
		repeater2Id			The repeater2 ID number to unrepeat.
		timesX (Optional)	The number of times to unrepeat the repeater2. Default is 1.
*/
function gfRepeater_unrepeatRepeaterTimes(formId, repeater2Id, timesX) {
	if (!timesX) { var timesX = 1; }
	for (i = 0; i < timesX; i++) {
		gfRepeater_unrepeatRepeater(formId, repeater2Id);
	}
}

/*
	gfRepeater_setRepeater(formId, repeater2Id, timesX)
		Repeats or unrepeats the repeater2 to set it to timesX.

		formId			The form Id.
		repeater2Id		The repeater2 ID number to repeat or unrepeat.
		timesX			The number to set the repeater2 to.
*/
function gfRepeater_setRepeater(formId, repeater2Id, timesX) {
	var repeater2 = gfRepeater_repeater2s[formId][repeater2Id];
	var currentRepeatCount = repeater2['data']['repeatCount'];

	if (timesX == currentRepeatCount) {
		return;
	} else if (timesX > currentRepeatCount) {
		var timesY = timesX - currentRepeatCount;
		gfRepeater_repeatRepeaterTimes(formId, repeater2Id, timesY);
	} else if (timesX < currentRepeatCount) {
		var timesY = currentRepeatCount - timesX;
		gfRepeater_unrepeatRepeaterTimes(formId, repeater2Id, timesY);
	}
}

/*
	gfRepeater_updateRepeaterControls(formId, repeater2Id)
		Updates the add and remove buttons for the repeater2. If the minimum repeat number has been reached, the remove button is hidden. If the maximum number has been reached, the add button is hidden.

		formId			The form Id.
		repeater2Id		The repeater2 ID number to update the controls for.
*/
function gfRepeater_updateRepeaterControls(formId, repeater2Id) {
	var repeater2 = gfRepeater_repeater2s[formId][repeater2Id];

	if (repeater2['settings']['max']) {
		if (repeater2['data']['repeatCount'] >= repeater2['settings']['max']) {
			jQuery(repeater2['controllers']['add']).hide();
		} else {
			jQuery(repeater2['controllers']['add']).show();
		}
	}

	if (repeater2['data']['repeatCount'] <= repeater2['settings']['min']) {
		jQuery(repeater2['controllers']['remove']).hide();
	} else {
		jQuery(repeater2['controllers']['remove']).show();
	}
}

/*
	gfRepeater_resetInputs(formId, repeater2Id, childId, repeater2ChildElement)
		Resets all input elements inside of a repeater2 child.

		formId					The form Id.
		repeater2Id				The repeater2 ID.
		childId					The repeater2 child ID number.
		repeater2ChildElement	The repeater2 child element.
*/
function gfRepeater_resetInputs(formId, repeater2Id, childId, repeater2ChildElement) {
	var repeater2 = gfRepeater_repeater2s[formId][repeater2Id];
	jQuery.each(repeater2['children'][childId]['inputs'], function(key, value){
		var inputId = this['id'];
		var inputName = this['name'];
		var inputDefaultValue = this['defaultValue'];
		var inputElement = gfRepeater_findElementByNameOrId(repeater2ChildElement, inputName, inputId);

		if (inputElement) {
			gfRepeater_setInputValue(inputElement, inputDefaultValue);
		}
	});
}

/*
	gfRepeater_select(formId, repeater2Id, repeatId, childId, inputId)
		Selects an element depending on the variables passed.

		formId						The form Id.
		repeater2Id (Optional)		The repeater2 Id.
		repeatId (Optional)			The repeat Id.
		childId (Optional)			The child Id.
		inputId (Optional)			The input Id. Also accepts '*' to select all inputs.
*/
function gfRepeater_select(formId, repeater2Id, repeatId, childId, inputId) {
	var selector = 'div#gform_wrapper_'+formId+'>form#gform_'+formId;
	if (repeater2Id || repeatId || childId || inputId) { selector += '>.gform_body .gform_fields>.gfield.gf_repeater2_child_field'; }
	if (repeater2Id) { selector += '[data-repeater2-parentid='+repeater2Id+']'; }
	if (repeatId) { selector += '[data-repeater2-repeatid='+repeatId+']'; }
	if (childId) { selector += '[data-repeater2-childid='+childId+']'; }
	if (inputId) {
		if (inputId == '*') {
			selector += ' [data-repeater2-inputid]';
		} else {
			selector += ' [data-repeater2-inputid='+inputId+']';
		}
	}
	return jQuery(selector);
}

/*
	gfRepeater_findElementByNameOrId(searchElement, elementName, elementId)
		Searches for an an element inside of another element by ID or Name. If both an ID and a Name are supplied it will first try the Name and then the ID.

		searchElement			Element to search inside.
		inputName (Optional)	A element name to search for.
		inputId (Optional)		A element ID to search for.
*/
function gfRepeater_findElementByNameOrId(searchElement, elementName, elementId) {
	if (elementName) { var foundElement = jQuery(searchElement).find("[name^='"+elementName+"']"); }
	if (!foundElement && elementId) { var foundElement = jQuery(searchElement).find("[id^='"+elementId+"']"); }
	if (foundElement) { return foundElement; } else { return false; }
}

/*
	gfRepeater_getIndex
		Searches 'object' where 'key' equals 'value'.
		Returns first result if multiple is false.
		Returns array with all key results if multiple is true.
		Returns false if nothing was found.

		object		Object or array to search through.
		key			Key to search for.
		value		Value to search for.
		multiple	Set to true to return all results in an array.
*/
function gfRepeater_getIndex(object, key, value, multiple) {
	var keys = [];

	jQuery.each(object, function(fieldKey, fieldValue){
		if (fieldValue[key] == value) {
			keys.push(fieldKey);
			if (!multiple) { return false; }
		}
	});

	if (keys.length) {
		if (multiple) {
			return keys;
		} else { return keys[0]; }
	} else { return false; }
}

/*
	gfRepeater_getChoiceValue(fieldElement)
		Searches 'fieldElement' for checkboxes and radios. Returns an array with the labels of all the values that are 'checked'.

		fieldElement	The element to search in.
*/
function gfRepeater_getChoiceValue(fieldElement) {
	var value = [];
	jQuery(fieldElement).find(':checkbox, :radio').each(function(){
		if (jQuery(this).prop('checked') == true) {
			var id = this.id;
			var label = jQuery(this).siblings('label').first().text();
			value.push(label);
		}
	});
	return value;
}

/*
	gfRepeater_getInputValue(inputElement)
		Gets the value of an input.

		inputElement	The input element.
*/
function gfRepeater_getInputValue(inputElement) {
	if (inputElement.is(':checkbox, :radio')) {
		if (inputElement.prop('checked') == true) { return true; } else { return false; }
	} else {
		return inputElement.val();
	}
}

/*
	gfRepeater_setInputValue(inputElement, value)
		Sets the value of an input.

		inputElement	The input element.
		inputValue		The value to set to the input.
*/
function gfRepeater_setInputValue(inputElement, inputValue) {
	if (inputElement.is(':checkbox, :radio')) {
		if (inputValue == 'on' || inputElement.prop('value') === inputValue) { inputElement.prop('checked', true) } else { inputElement.prop('checked', false) }
	} else {
		inputElement.val(inputValue);
	}
}

/*
	gfRepeater_updateDataElement(formId, repeater2Id)
		Updates the data element for the repater. The data element stores information that is passed to PHP for processing.

		formId			The form Id.
		repeater2Id		The repeater2 ID number to update the data element for.
*/
function gfRepeater_updateDataElement(formId, repeater2Id) {
	var repeater2 = gfRepeater_repeater2s[formId][repeater2Id];
	var dataElement = jQuery(repeater2['controllers']['data']);

	var dataArray = jQuery(dataElement).val();
	if (dataArray) { dataArray = JSON.parse(dataArray); }

	dataArray['repeater2Id'] = repeater2Id;
	dataArray['repeatCount'] = repeater2['data']['repeatCount'];

	jQuery.each(dataArray['children'], function(key, value){
		if (Array.isArray(this)) { dataArray['children'][key] = {}; }
		var inputData = repeater2['data']['inputData'][key];
		if (inputData && inputData.length) {
			dataArray['children'][key]['inputs'] = inputData;
		}
		var fieldIndex = gfRepeater_getIndex(repeater2['children'], 'idNum', key);
        // TODO: Temporarily comment this line out
		//dataArray['children'][key]['conditionalLogic'] = repeater2['children'][fieldIndex]['conditionalLogic'];
	});

	dataArray = JSON.stringify(dataArray);
	jQuery(dataElement).val(dataArray);
}

/*
	gfRepeater_start()
		Runs the gfRepeater_setRepeaterChildAttrs function for the first set of repeater2 children and then repeats the repeater2 a number of times depending on the repeater2 setting. - Second phase of setup.
*/
function gfRepeater_start() {
	jQuery.each(gfRepeater_repeater2s, function(key, repeater2){
		var formId = key;
		var form = gfRepeater_select(formId);

		jQuery.each(repeater2, function(key, value){
			var repeater2Id = key;
			var repeater2 = gfRepeater_repeater2s[formId][repeater2Id];
			var repeatCount = repeater2['settings']['start'];
			var paremCount = repeater2['data']['paremCount'];

			if (repeater2['controllers']['data'].attr('data-required')) { repeater2['controllers']['start'].addClass('gfield_contains_required'); }

			jQuery.each(repeater2['children'], function(key, value){
				gfRepeater_setRepeaterChildAttrs(formId, repeater2Id, jQuery(repeater2['children'][key]['element']), 1);
				if (this.conditionalLogic) { gfRepeater_conditionalLogic_set(formId, repeater2Id, key, 1); }
			});

			if (gfRepeater_submitted) {
				repeatCount = repeater2['data']['prevRepeatCount'];
			} else if (paremCount > repeatCount) {
				repeatCount = paremCount;
			}

			gfRepeater_setRepeater(formId, repeater2Id, repeatCount);
			gfRepeater_updateRepeaterControls(formId, repeater2Id);
			gfRepeater_updateDataElement(formId, repeater2Id);
		});

		jQuery(form).trigger('gform_repeater2_init_done');
	});

	if (window['gformInitDatepicker']) { gformInitDatepicker(); }
}

// Initiation after gravity forms has rendered.
// This will fire each time a form is rendered, but we only need it the first time.
jQuery(document).bind('gform_post_render', function() {
	if(!gfRepeater_repeater2s_is_set) {
		if (gfRepeater_getRepeaters()) {
			gfRepeater_start();
			jQuery(window).trigger('gform_repeater2_init_done');
		} else {
			console.log('There was an error with one of your repeater2s. This is usually caused by forgetting to include a repeater2-end field or by trying to nest repeater2s.');
		}
		gfRepeater_repeater2s_is_set = true;
	}
});

// Debug shortcuts
if (gfRepeater_debug) {
	jQuery(window).keydown(function(event){
		// Up Arrow - Prints the contents of gfRepeater_repeater2s into the console.
		if (event.which == 38) { console.log(gfRepeater_repeater2s); }

		// Down Arrow - Prints the captured form values into the console.
		if (event.which == 40) { console.log(jQuery.captures()); }
	});
}
