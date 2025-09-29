# Tenancy Application Form Example

This document explains how to create a tenancy application form using the Repeater for Gravity Forms plugin.

## Form Setup

### 1. Create the Form Structure

1. **Primary Applicant Section** (outside repeater):
   - Name (First, Last)
   - Email
   - Phone
   - Current Address

2. **Repeater Field Setup**:
   - Add "Repeater" field
   - Set minimum: 1, maximum: 5 (or as needed)
   - Add "Repeater End" field

3. **Inside the Repeater** (for each additional applicant):
   - Name (First, Last)
   - Email
   - Phone
   - Relationship to Primary Applicant (dropdown)
   - Employment Status (dropdown)
   - Income (number field)
   - References (paragraph text)

### 2. Field Configuration

**Repeater Settings:**
- Start: 1
- Min: 1
- Max: 5
- Hide Label: No (or Yes if you prefer)

**Repeater End Settings:**
- Add HTML: "Add Another Applicant"
- Remove HTML: "Remove Applicant"
- Hide Buttons: No

### 3. Email Configuration

**Notification Settings:**
- Use merge tags like `{Repeater:1}` to include all repeater data
- The plugin will automatically format the data in a table format

### 4. Testing the Form

1. **Date Fields**: Should work properly with date pickers
2. **File Uploads**: Should work for document uploads
3. **Dropdowns**: Should maintain selections when adding/removing applicants
4. **Email Notifications**: Should include all applicant data

## Troubleshooting

### Common Issues Fixed:

1. **Date Fields Not Working**: Fixed with proper datepicker reinitialization
2. **File Uploads Not Working**: Fixed with file input handling
3. **Email Missing Data**: Fixed with improved merge tag handling
4. **WordPress 6.8 Compatibility**: Updated serialization methods

### Testing Checklist:

- [ ] Can add multiple applicants
- [ ] Can remove applicants (minimum 1)
- [ ] Date fields work in all instances
- [ ] File uploads work in all instances
- [ ] Dropdown selections are maintained
- [ ] Email includes all applicant data
- [ ] Form validation works correctly

## Support

If you encounter issues:
1. Check that Gravity Forms is version 2.7 or higher
2. Ensure WordPress is 6.8 compatible
3. Verify all required fields are marked as required
4. Test email notifications with sample data
