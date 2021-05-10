# Gravity Forms Repeater Add-On
A Gravity Forms add-on that allows specified groups of fields to be repeated by the user.
<https://getbutterfly.com/gravity-forms-repeater-plugin/>

### Supported Fields
* Address
* Checkboxes
* Date
* Drop Down
* Email
* Hidden
* HTML
* MultiSelect
* Name
* Number
* Paragraph Text
* Phone
* Radio
* Section
* Single Line Text
* Time
* Website

### Features
* Repeat groups of fields multiple times
* Use multiple repeaters on the same form
* Use shortcodes to display data to the user
* Use Javascript to manipulate the repeater
* Customize the add and remove button's HTML
* Use Gravity Forms pre-populate hooks and filters like normal
* Supports Conditional Logic!

### Issues
* Not all fields are currently supported.
* Ajax enabled forms are not yet supported. (Ajax will be automatically disabled on forms with repeaters)

### Shortcodes
You can place shortcodes inside of input labels, input descriptions, and HTML blocks!
* [gfRepeater-count] - Will output the current repeat number.
* [gfRepeater-buttons] - Will output both the '+' and '-' buttons.
* [gfRepeater-add] - Will output the '+' button.
* [gfRepeater-remove] - Will output the '-' button.

### CSS Classes
You can use these CSS classes in the in the "Custom CSS Class" setting to do different things.
* gf_repeater2_hide_add - Will hide the '+' button if placed in the repeater end css setting.
* gf_repeater2_hide_remove - Will hide the '-' button if placed in the repeater end css setting.

### Javascript
##### Functions
You can use Javascript to manipulate the repeater.
* formId is your form's Id number assigned by Gravity Forms.
* repeater2Id will depened on how many repeaters you have in your form. (The first repeater's ID is 1, second is 2, etc.)
* repeater2ChildId works the same way and depends on how many times the repeater has been repeated.
* These functions will not allow you to repeat more than the set max and unrepeat more than the set min.
#
```
gfRepeater_repeatRepeater(formId, repeater2Id);
  Repeats the repeater once.
```
```
gfRepeater_unrepeatRepeater(formId, repeater2Id, repeater2ChildId);
  Un-repeats the repeater once. repeater2ChildId is optional.
```
```
gfRepeater_repeatRepeaterTimes(formId, repeater2Id, timesX);
  Repeats the repeater a number of times.
```
```
gfRepeater_unrepeatRepeaterTimes(formId, repeater2Id, timesX);
  UnRepeats the repeater a number of times.
```
```
gfRepeater_setRepeater(formId, repeater2Id, timesX);
  Repeats or unrepeats the repeater to get it to whatever timesX is.
```
```
gfRepeater_select(formId, repeater2Id, repeatId, childId, inputId);
  Selects an element depending on the variables passed. All variables are optional besides formId. inputId also accepts '*' to select all inputs.
```

##### Triggers
These triggers are assigned to the form will be fired during different repeater related events with the repeater2Id and repeater2ChildId attached to them.
```
gform_repeater2_before_repeat - Fires right before a repeater is about to be repeated.
gform_repeater2_after_repeat - Fires right after a repeater has been repeated.
gform_repeater2_before_unrepeat - Fires right before a repeater is about to be un-repeated.
gform_repeater2_after_unrepeat - Fires right after a repeater has been un-repeated.
gform_repeater2_init_done - Fires after the repeater is done setting up. Also fires on the window when all repeaters are done setting up.
```

##### Information
You can access all repeater information using the object `gfRepeater_repeater2s`.
```
gfRepeater_repeater2s
[formId]
    [repeater2Id]
        ['data'] - Contains different data to keep track of.
            ['repeatCount'] - The number of times the repeater has been repeated.
            ['prevRepeatCount'] - If the form has already been submitted and failed validation, this will contain the repeatCount from before the from was submitted, otherwise it will be null.
            ['childrenCount'] - The number of children fields that get cloned everytime the repeater is repeated.
            ['paremCount'] - The highest field ID that has had their prepopulate paremeter set.
            ['tabIndex'] - The tabindex that was assigned to the repeater start field and will be assigned to all children.
            ['inputData'] - Contains an array with all of the input names in the repeater.
        ['settings'] - Contains the different settings that are set in the form editor.
            ['start'] - The number of times the repeater should be repeated when the form is loaded.
            ['min'] - The minimum number of times the repeater can be repeated.
            ['max'] - The maximum number of times the repeater can be repeated.
        ['controllers'] - Contains different elements that I like to call controllers.
            ['add'] - The add button.
            ['remove'] - The remove button.
            ['data'] - The hidden element that stores data to be passed to PHP.
            ['start'] - The start of the repeater.
            ['end'] - The end of the repeater.
        ['children'] - Contains the children fields that get cloned everytime the repeater is repeated.
            [childId]
                ['element'] - The field element.
                ['id'] - The field HTML id.
                ['idNum'] - The field ID number.
                ['inputCount'] - The number of inputs the child contains.
                ['inputMask'] - The field's input mask.
                ['required'] - True or False depeneding on if the field is required or not.
                ['type'] - The field type.
                ['inputs'] - Contains all of the inputs that the child contains.
                    [inputId]
                        ['element'] - The input element.
                        ['id'] - The input HTML id.
                        ['name'] - The input name.
                        ['defaultValue'] - The default value for the input.
                        ['prePopulate'] - Array containing prepopulate values.
```

##### Usage Examples
Repeat the repeater a number of times depending on the value of a drop down field:
```
jQuery('#gform_6 #input_6_7').change(function(){
    var attendees = jQuery(this).val();
    gfRepeater_setRepeater(6, 1, attendees);
});
```

Change the value of a field if the repeater is repeated or un-repeated:
```
jQuery('#gform_9').on('gform_repeater2_after_repeat gform_repeater2_after_unrepeat', function(event, repeater2Id, repeatId){
    if (repeater2Id == 1) {
        var repeatCount = gfRepeater_repeater2s[9][1]['data']['repeatCount'];
        var totalPrice = 27.47 * repeatCount;
        jQuery('#gform_9 #input_9_4').val('$'+totalPrice);
    }
});
```


### Prepopulate Fields
You can set Parameter Names to prepopulate repeated fields like usual with the added ability to specify which repeated set will be prepopulated. For example, let's say we have set the parameter name for one of our repeated fields to "parem":

`?param=hello+world` will result in that field being set to "hello world" regardless of how many times it has been repeated.

`?param3=hello+world` will result in that field being set to "hello world" only when repeated a third time.

You can use filters as well!

```
add_filter( 'gform_field_value_parem', 'your_function_name' );
```

```
add_filter( 'gform_field_value_parem3', 'your_function_name' );
```

Also, setting the prepopulate parameter on the repeater start field will override the `start` setting.


### Frequently Asked Questions
##### Can I use multiple repeaters in one form?
Yes!

##### Can I nest repeaters?
Nesting repeaters is not supported.

##### Can I change the `+` and `-` buttons to text links?
Yes! Just go to the form editor and change the `Add HTML` and `Remove HTML` settings to `<a>Your Link Text</a>` and they should appear as regular links on your form!

### Installation & Update

##### Method 1 (Automatic Updates)
It is possible to keep this version up to date automatically by using [Andy Fragen](https://github.com/afragen)'s [GitHub Updater](https://github.com/afragen/github-updater)!

1. Download & Install [GitHub Updater](https://github.com/afragen/github-updater).
2. Go to Settings -> GitHub Updater.
3. Enable Branch Switching.
4. Go to Plugins.

and that's it! You will receive the latest version of this plugin!

##### Method 2 (Manual)

1. Deactivate & Uninstall current version of Gravity Forms Repeater Add-On.
2. Download the [latest version](https://github.com/wolffe/repeater2-for-gravity-forms/releases).
3. Go to Plugins.
4. Select "Add New".
5. Select "Upload Plugin".
6. Select "Choose File" and find the zip file that you downloaded in step 2.
7. Select "Install Now".

and you're good to go!

### Requirements
* Wordpress 5.5 or later
* Gravity Forms 2.5 or later

### Installation
1. Upload the `repeater2-add-on-for-gravity-forms` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
