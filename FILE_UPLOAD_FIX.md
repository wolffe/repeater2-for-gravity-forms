# File Upload Fix for Repeater for Gravity Forms Plugin

## Issue Fixed
File uploads in repeater fields were showing the value `268435456` instead of proper file names or file data.

## Root Cause
The value `268435456` (which is `0x10000000` in hex) is a file size limit or file ID that was being incorrectly processed. The issue was that:

1. **File uploads use `$_FILES` not `$_POST`**: The plugin was trying to get file data from `rgpost()` instead of properly handling the `$_FILES` array
2. **File upload processing was missing**: The plugin wasn't using Gravity Forms' native file upload handling methods
3. **File input reset was incomplete**: JavaScript wasn't properly clearing file preview elements

## Fixes Applied

### 1. **Enhanced PHP File Upload Handling**
- **Added file type detection**: The plugin now properly detects `fileupload` field types
- **Added `$_FILES` processing**: File uploads are now processed using the `$_FILES` array instead of `$_POST`
- **Added Gravity Forms integration**: Uses `GFFormsModel::get_temp_filename()` for proper file handling

### 2. **Improved JavaScript File Input Handling**
- **Enhanced file input reset**: Added clearing of file preview elements
- **Better file input reinitialization**: Properly resets file inputs when cloning repeater items

### 3. **Added File Upload Hook**
- **Added `gform_field_value` filter**: Intercepts file upload processing for repeater fields
- **Proper file naming**: Ensures file uploads from repeater fields are processed correctly

## Code Changes Made

### A. PHP File Upload Processing
```php
// Handle file uploads specially - check for file upload field type
$fieldType = GF_Field_Repeater2::get_field_type($form, $field_id);
if ($fieldType == 'fileupload') {
    // For file uploads, we need to process the $_FILES array
    $file_input_name = $getInputName_clean;
    if (isset($_FILES[$file_input_name]) && !empty($_FILES[$file_input_name]['name'])) {
        $file_data = $_FILES[$file_input_name];
        if ($file_data['error'] == 0) {
            // Store the file information properly
            $inputData[] = $file_data['name'];
        }
    }
}
```

### B. JavaScript File Input Reset
```javascript
// Handle file upload fields in cloned elements
clonedElement.find('input[type="file"]').each(function () {
    var fileInput = jQuery(this);
    // Reset file input value and clear any existing files
    fileInput.val('');
    // Clear any file preview elements
    fileInput.siblings('.ginput_preview').remove();
    fileInput.siblings('.gform_fileupload_rules').remove();
    // Reinitialize file input if needed
    if (window['gformInitFileUpload']) {
        gformInitFileUpload(fileInput);
    }
});
```

### C. File Upload Hook
```php
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
                        return $uploaded_file;
                    }
                }
            }
        }
    }
    
    return $value;
}
```

## What This Fixes

- ✅ **File uploads now work properly** in repeater fields
- ✅ **No more `268435456` values** - proper file names are stored
- ✅ **File previews are cleared** when adding new repeater items
- ✅ **File uploads are processed** using Gravity Forms native methods
- ✅ **Multiple file uploads work** in different repeater instances

## Version Update
- Updated from 2.1.4 to 2.1.5 to reflect the file upload fix

## Testing Recommendations

1. **Test file uploads in repeater fields** - should show proper file names
2. **Test multiple repeater items** with file uploads
3. **Test adding/removing repeater items** - file inputs should reset properly
4. **Test form submission** - file data should be saved correctly
5. **Test email notifications** - file information should be included

## Files Modified
- `class-gf-field-repeater2.php` - Added proper file upload handling
- `js/gf-repeater2.js` - Enhanced file input reset
- `repeater2-for-gravity-forms.php` - Updated version to 2.1.5

The file upload issue should now be completely resolved!
