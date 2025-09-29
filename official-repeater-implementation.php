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
