# Building a Better Repeater for Gravity Forms

I ship a lot of Gravity Forms projects for clients who expect flexible, repeatable field groups. For years that meant shipping my own repeater because Gravity Forms lacked one. When the official Repeater field finally arrived—in beta and still incomplete—I already had battle-tested code in production, and an inbox full of edge cases. This article documents how I evolved Repeater for Gravity Forms, why it still matters even with the official beta, and how you can integrate both approaches in real-world builds.

## Why I Didn’t Wait for the Official Repeater

The official API is promising, but you still need to glue it together manually. You inject the repeater at runtime, define every nested field yourself, and then clean it back out before Gravity Forms persists the form. My Tenancy Application test harness demonstrates the ceremony required.

```12:29:official-repeater-implementation.php
add_filter( 'gform_form_post_get_meta_149', 'add_tenancy_repeater_field' );
function add_tenancy_repeater_field( $form ) {
    $applicant_name = GF_Fields::create( array(
        'type'       => 'name',
        'id'         => 1001,
        'formId'     => $form['id'],
        'label'      => 'Applicant Name',
        'pageNumber' => 1,
        'isRequired' => true,
    ) );
    // ... existing code ...
```

It works, but it is fragile. You must manage IDs, watch for serialization conflicts, and add cleanup filters so the builder doesn’t save your temporary fields. For client projects where editors expect to drag-and-drop fields visually, that workflow is a non-starter. I needed something I could configure inside the form editor, export with the form, and rely on in paid production sites.

## Full Official Repeater Example, Annotated

To understand exactly what the beta API demands, here is the full reference implementation with heavy comments. It shows how to inject the repeater after Gravity Forms loads the form metadata, how to strip it back out before the builder saves changes, and how to surface a custom merge tag for notifications.

```1:160:official-repeater-implementation.php
<?php
/**
 * Official Gravity Forms Repeater Implementation
 * For Tenancy Application Form
 * 
 * This replaces the third-party repeater plugin with the official Gravity Forms Repeater field
 */

// Add this implementation to your theme's functions.php or drop it into a small utility plugin.
// It demonstrates how to wire up the beta Gravity Forms repeater field entirely in PHP.

// Adjust your form ID - replace 149 with your actual form ID.
// We run this after Gravity Forms loads the form meta so the repeater field exists only at runtime.
add_filter( 'gform_form_post_get_meta_149', 'add_tenancy_repeater_field' );
function add_tenancy_repeater_field( $form ) {

    // Create fields for each applicant
    // Each sub-field needs a unique ID that will not clash with existing fields.
    // I like to reserve a block (1000+) for programmatically injected fields.
    $applicant_name = GF_Fields::create( array(
        'type'       => 'name',
        'id'         => 1001,
        'formId'     => $form['id'],
        'label'      => 'Applicant Name',
        'pageNumber' => 1,
        'isRequired' => true,
    ) );

    $applicant_email = GF_Fields::create( array(
        'type'       => 'email',
        'id'         => 1002,
        'formId'     => $form['id'],
        'label'      => 'Email Address',
        'pageNumber' => 1,
        'isRequired' => true,
    ) );

    $applicant_phone = GF_Fields::create( array(
        'type'       => 'phone',
        'id'         => 1003,
        'formId'     => $form['id'],
        'label'      => 'Phone Number',
        'pageNumber' => 1,
        'isRequired' => true,
    ) );

    $relationship = GF_Fields::create( array(
        'type'       => 'select',
        'id'         => 1004,
        'formId'     => $form['id'],
        'label'      => 'Relationship to Primary Applicant',
        'pageNumber' => 1,
        'isRequired' => true,
        'choices'    => array(
            array( 'text' => 'Spouse/Partner', 'value' => 'spouse' ),
            array( 'text' => 'Family Member', 'value' => 'family' ),
            array( 'text' => 'Roommate', 'value' => 'roommate' ),
            array( 'text' => 'Other', 'value' => 'other' ),
        ),
    ) );

    $employment_status = GF_Fields::create( array(
        'type'       => 'select',
        'id'         => 1005,
        'formId'     => $form['id'],
        'label'      => 'Employment Status',
        'pageNumber' => 1,
        'isRequired' => true,
        'choices'    => array(
            array( 'text' => 'Employed Full-time', 'value' => 'fulltime' ),
            array( 'text' => 'Employed Part-time', 'value' => 'parttime' ),
            array( 'text' => 'Self-employed', 'value' => 'selfemployed' ),
            array( 'text' => 'Student', 'value' => 'student' ),
            array( 'text' => 'Unemployed', 'value' => 'unemployed' ),
            array( 'text' => 'Retired', 'value' => 'retired' ),
        ),
    ) );

    $income = GF_Fields::create( array(
        'type'       => 'number',
        'id'         => 1006,
        'formId'     => $form['id'],
        'label'      => 'Annual Income ($)',
        'pageNumber' => 1,
        'isRequired' => true,
    ) );

    $references = GF_Fields::create( array(
        'type'       => 'textarea',
        'id'         => 1007,
        'formId'     => $form['id'],
        'label'      => 'References (Name, Phone, Relationship)',
        'pageNumber' => 1,
        'isRequired' => false,
    ) );

    // Create the repeater field for additional applicants
    $applicants_repeater = GF_Fields::create( array(
        'type'             => 'repeater',
        'id'               => 1000,
        'formId'           => $form['id'],
        'label'            => 'Additional Applicants',
        'description'      => 'Add other applicants who will be living in the property',
        'addButtonText'    => 'Add Another Applicant',
        'removeButtonText' => 'Remove Applicant',
        'maxItems'         => 5, // Maximum 5 additional applicants
        'pageNumber'       => 1,
        'fields'           => array( 
            $applicant_name, 
            $applicant_email, 
            $applicant_phone, 
            $relationship, 
            $employment_status, 
            $income, 
            $references 
        ),
    ) );

    // Add the repeater field to the form
    // The repeater is appended to the end of the form array. Change this to insert elsewhere.
    $form['fields'][] = $applicants_repeater;

    return $form;
}

// Remove the repeater field before the form is saved to prevent database issues.
// Without this, the temporary field would be persisted to the database and duplicate entries
// would accumulate every time the form is edited.
add_filter( 'gform_form_update_meta_149', 'remove_tenancy_repeater_field', 10, 3 );
function remove_tenancy_repeater_field( $form_meta, $form_id, $meta_name ) {
    if ( $meta_name == 'display_meta' ) {
        // Remove the Repeater field: ID 1000
        $form_meta['fields'] = wp_list_filter( $form_meta['fields'], array( 'id' => 1000 ), 'NOT' );
    }
    return $form_meta;
}

/**
 * Custom merge tag for displaying repeater data in emails
 */
add_filter( 'gform_replace_merge_tags', 'custom_repeater_merge_tag', 10, 7 );
function custom_repeater_merge_tag( $text, $form, $entry, $url_encode, $esc_html, $nl2br, $format ) {
    
    if ( strpos( $text, '{applicants_data}' ) !== false ) {
        // This string builder captures HTML output for the custom merge tag.
        $applicants_data = '';
        
        // Get the repeater field data
        $repeater_field_id = 1000; // Adjust based on your field ID
        $repeater_data = rgar( $entry, $repeater_field_id );
        
        if ( ! empty( $repeater_data ) ) {
            // The official repeater stores data as JSON. You can decode and iterate for cleaner output.
            // For clarity here, I dump the raw payload into a table so you can inspect the structure.
            $applicants_data = '<h3>Additional Applicants:</h3>';
            $applicants_data .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">';
            $applicants_data .= '<tr><th>Name</th><th>Email</th><th>Phone</th><th>Relationship</th><th>Employment</th><th>Income</th><th>References</th></tr>';
            
            // Parse the repeater data (this will depend on how GF stores it)
            // The beta API may shift; inspect $repeater_data and shape the table to match.
            $applicants_data .= '<tr><td colspan="7">Repeater data: ' . $repeater_data . '</td></tr>';
            $applicants_data .= '</table>';
        }
        
        $text = str_replace( '{applicants_data}', $applicants_data, $text );
    }
    
    return $text;
}
```

## What Repeater for Gravity Forms Does Differently

Repeater for Gravity Forms registers two custom field types (`repeater2` and `repeater2-end`) and lets Gravity Forms handle the rest. Editors add start and end markers, configure start/min/max counts, and drop whatever child fields they need inside the pair. Under the hood, the add-on framework boots both the PHP field classes and the JavaScript that keeps everything in sync.

```21:62:repeater2-for-gravity-forms.php
class GFRepeater extends GFAddOn {
    protected $_version                  = GF_REPEATER_VERSION;
    protected $_min_gravityforms_version = '2.7';
    protected $_slug                     = 'repeater2addon';
    // ... existing code ...
    public function init_frontend() {
        parent::init_frontend();

        GF_Field_Repeater2::init_frontend();
    }
}
```

The front-end bootstrap touches every lifecycle step that Gravity Forms exposes. It disables AJAX automatically (the official field still requires this), queues the repeater scripts and styles only when needed, and ensures submissions capture every repeated value—including files and conditional fields.

```21:28:class-gf-field-repeater2.php
public static function init_frontend() {
    add_action( 'gform_form_args', array( 'GF_Field_Repeater2', 'gform_disable_ajax' ) );
    add_action( 'gform_enqueue_scripts', array( 'GF_Field_Repeater2', 'gform_enqueue_scripts' ), 10, 2 );
    add_filter( 'gform_pre_render', array( 'GF_Field_Repeater2', 'gform_unhide_children_validation' ) );
    add_filter( 'gform_pre_validation', array( 'GF_Field_Repeater2', 'gform_bypass_children_validation' ) );
    add_filter( 'gform_notification', array( 'GF_Field_Repeater2', 'gform_ensure_repeater_data_in_notification' ), 10, 3 );
    add_filter( 'gform_field_value', array( 'GF_Field_Repeater2', 'gform_handle_repeater_file_uploads' ), 10, 3 );
}
```

Inside the editor, Repeater for Gravity Forms adds custom settings panels for start/min/max counts, hide-label toggles, and customizable add/remove button HTML. Because the field lives alongside core fields, form exports include the repeater data natively. There is no extra migration step when you move between environments or ship a form bundle to a client.

## Versioned Fixes and Lessons Learned

Building a repeater field is more than cloning DOM nodes. The last few releases were driven by real support tickets and production logs, and every fix made the plugin more resilient.

### JavaScript Stability

Early adopters hit JavaScript errors whenever a child field was missing metadata. I hardened the setup routine to validate every object before accessing it, bootstrapping sane defaults and logging parse errors instead of crashing the form. This refactor removed the dreaded “Cannot read properties of undefined” error and made the repeater recover gracefully when editors drag unsupported fields into the group (`FIXES_APPLIED.md`).

### File Uploads That Actually Work

File uploads inside repeaters were the hardest issue to solve. Gravity Forms posts file metadata through `$_FILES`, not `$_POST`, so repeated uploads were coming through as raw size integers (`268435456`). Repeater for Gravity Forms now detects file upload fields, processes the `$_FILES` array, and stores clickable URLs for notifications and entry views.

```645:669:class-gf-field-repeater2.php
public static function gform_handle_repeater_file_uploads( $value, $field, $form ) {
    if ( $field->type == 'fileupload' && ! empty( $_FILES ) ) {
        // ... existing code ...
        foreach ( $_FILES as $file_key => $file_data ) {
            if ( strpos( $file_key, $input_name ) === 0 && ! empty( $file_data['name'] ) ) {
                // ... existing code ...
                $file_url = $upload_dir['baseurl'] . '/gravity_forms/' . $form['id'] . '/' . $file_data['name'];
                return '<a href="' . esc_url($file_url) . '" target="_blank">' . esc_html($file_data['name']) . '</a>';
            }
        }
    }

    return $value;
}
```

On the front-end, the JavaScript runner resets file inputs when it clones a repeater row, clears previews, and reinitializes Gravity Forms’ own upload handler (`FILE_UPLOAD_FIX.md`). The result: editors can add multiple file uploads across repeated rows and get valid links in notifications, entry views, and exports.

### Time Fields in Repeated Rows

Gravity Forms initializes time pickers once, so cloned rows were shipping raw hour and minute arrays. Repeater for Gravity Forms now detects time fields, reinitializes `gformInitTimepicker`, and formats values as `HH:MM` both in entries and emails (`TIME_FIELD_FIX.md`). No more `12
12` glitches.

### Better Entry Displays and Emails

Repeater for Gravity Forms serializes entry data as JSON to stay compatible with WordPress 6.8+ (`FIXES_APPLIED.md`). Entry detail views convert repeated groups into readable tables, email notifications include entire repeater payloads automatically, and merge tags now work even when conditional logic hides certain repetitions. `DISPLAY_FIXES.md` documents the tweaks that turned a blob of IDs into usable client communication.

## Tenancy Application Form: A Real-World Stress Test

I built a full tenancy application form to hammer the edge cases. The structure looks like this (`TENANCY_FORM_EXAMPLE.md`):

- A primary applicant section outside the repeater
- A repeater that captures 1–5 additional occupants
- Nested fields for employment status, income, references, and uploads
- Conditional logic to show or hide follow-up questions

Testing that flow uncovered everything from datepicker reinitialization to multi-field validation. Because editors configure the repeater right in the form builder, they can change copy, tweak field requirements, and export the form like any other Gravity Forms project. No PHP edits required.

## Bridging to the Official API

Even though Repeater for Gravity Forms is production-ready, I still experiment with the official field so I’m ready when it graduates from beta. The implementation file in the repo shows how to inject the official repeater today: add your fields, append the repeater, and strip it back out before save. You can also wire merge tags manually for notifications.

Where it helps right now:

- Rapid prototyping: I can spin up a beta form quickly, compare responses with Repeater for Gravity Forms, and spot gaps in Gravity Forms’ implementation.
- Future-proofing: Once the official field reaches parity, I can offer a migration path by reading stored Repeater for Gravity Forms entries (JSON) and mapping them to official repeater structures.
- Client messaging: I can explain the difference between a supported beta feature and a maintained add-on, and recommend the stable path for mission-critical forms.

## Implementation Notes for Your Projects

If you plan to ship repeaters today, here are the practices I follow:

- **Clamp the counts**: Always set sensible min and max values in the repeater settings so validation rules stay predictable.
- **Audit child fields**: Avoid unsupported field types until you’ve tested them in staged environments. Repeater for Gravity Forms covers the common ones—address, date, time, uploads, conditional sections—but I still smoke-test anything exotic from third-party add-ons.
- **Disable AJAX intentionally**: Repeater for Gravity Forms does this for you, but if you switch to the official field remember it still needs non-AJAX submissions for now.
- **Keep notifications simple**: Use the repeater merge tags that Gravity Forms provides. Repeater for Gravity Forms formats the payload for you, but I still log representative entries to confirm email templates match client expectations.
- **Version everything**: Each fix in this plugin bumped the version in `repeater2-for-gravity-forms.php`. Keep changelogs so you can explain upgrades to maintainers and customers.

## What’s Next

Repeater for Gravity Forms will keep shipping until the official Gravity Forms repeater is stable, documented, and feature-complete. When that happens, I want my users to have a clear upgrade path. Until then, this add-on lets me deliver repeatable field groups that feel native to the form editor, survive exports, and respect every edge case I’ve met in client work.

If you are a WordPress developer building for agencies or larger sites, you can drop Repeater for Gravity Forms into your stack today, test the official beta in parallel, and decide which one fits each project. I’ll keep sharing what I learn on getbutterfly.com as Gravity Forms evolves—and I’ll keep shipping fixes the moment production sites surface them.
