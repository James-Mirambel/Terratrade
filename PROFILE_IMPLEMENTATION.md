# Profile Page Implementation

## Overview
Complete user profile page for TerraTrade with full database integration, split into modular components for maintainability.

## File Structure

### Main Files
```
profile.php                              # Main profile page (entry point)
```

### Includes (Modular Components)
```
includes/
├── profile-header.php                   # Site header with navigation
├── profile-hero.php                     # Profile avatar and user info header
├── profile-stats.php                    # Quick stats cards
└── profile-tabs/
    ├── overview.php                     # Overview tab content
    ├── personal.php                     # Personal info form
    ├── kyc.php                          # KYC verification section
    ├── security.php                     # Password & sessions
    ├── activity.php                     # Activity timeline
    └── preferences.php                  # User preferences
```

### Assets
```
css/profile.css                          # Profile page styles
js/profile.js                            # Profile page JavaScript
```

### API Endpoints
```
api/profile/
├── update-personal.php                  # Update name, email, phone
├── change-password.php                  # Change password
├── upload-avatar.php                    # Upload profile photo
├── upload-kyc.php                       # Upload KYC documents
├── update-preferences.php               # Save user preferences
└── terminate-session.php                # End active sessions
```

## Features Implemented

### 1. Profile Header
- **Large avatar display** with upload functionality
- **User badges**: Role, Status, KYC verification
- **Member since** and **last login** timestamps
- **Change photo** button with instant preview

### 2. Quick Statistics (6 Cards)
- Active Listings
- Offers Made
- Offers Received
- Favorites
- Active Contracts
- Completed Transactions

### 3. Tabbed Interface (6 Tabs)

#### Tab 1: Overview
- Account summary (email, phone, role, status, KYC)
- Verification badges
- KYC warning (if not verified)
- Recent activity list (last 10 actions)

#### Tab 2: Personal Information
- Edit full name
- Change email (with verification notice)
- Update phone number
- Save/Cancel buttons

#### Tab 3: KYC Verification
- **Status card** with color-coded messages
- **Document upload form**:
  - Document type selector (National ID, Driver's License, Passport, TIN, Business Permit, Other)
  - File upload (JPG, PNG, PDF - max 5MB)
- **Submitted documents list**:
  - Document type and status badges
  - Upload and verification dates
  - Rejection reasons (if rejected)
  - View document links

#### Tab 4: Security
- **Change password form**:
  - Current password
  - New password (min 8 chars)
  - Confirm password
- **Active sessions list**:
  - Device type (Desktop/Mobile)
  - IP address
  - Last activity timestamp
  - Terminate button (except current session)

#### Tab 5: Activity
- **Timeline view** of recent actions
- Shows: Action type, table affected, IP address, timestamp
- Visual timeline with markers

#### Tab 6: Preferences
- **Notification preferences**:
  - Email for new offers
  - Email for offer updates
  - Email for messages
  - Email for auction updates
  - Marketing emails
- **Display preferences**:
  - Area unit (sqm/hectares/both)
  - Currency (PHP/USD)
- **Privacy settings**:
  - Show email to verified users
  - Show phone to verified users
  - Allow messages from non-verified users

## Database Integration

### Tables Used
1. **users** - Main user data
2. **properties** - For listing counts
3. **offers** - For offer statistics
4. **user_favorites** - For favorites count
5. **contracts** - For contract statistics
6. **kyc_documents** - For KYC document management
7. **audit_logs** - For activity tracking
8. **user_sessions** - For active sessions

### Queries Executed
- User profile data retrieval
- Statistics aggregation (6 COUNT queries)
- KYC documents list
- Recent activity (last 10 records)
- Active sessions list
- Update operations for all forms

## API Endpoints Details

### 1. Update Personal Info
**Endpoint**: `POST /api/profile/update-personal.php`
**Input**: `{ full_name, email, phone, csrf_token }`
**Validation**:
- Email format validation
- Email uniqueness check
- CSRF token verification
**Output**: Success/error message

### 2. Change Password
**Endpoint**: `POST /api/profile/change-password.php`
**Input**: `{ current_password, new_password, confirm_password, csrf_token }`
**Validation**:
- Current password verification
- New password length (min 8 chars)
- Password match confirmation
**Output**: Success/error message

### 3. Upload Avatar
**Endpoint**: `POST /api/profile/upload-avatar.php`
**Input**: `FormData { avatar (file), csrf_token }`
**Validation**:
- File type (JPG, PNG, GIF, WebP)
- File size (max 5MB)
- MIME type verification
**Process**:
- Generate unique filename
- Save to `uploads/avatars/`
- Delete old avatar
- Update database
**Output**: `{ success, avatar_url }`

### 4. Upload KYC Document
**Endpoint**: `POST /api/profile/upload-kyc.php`
**Input**: `FormData { document (file), document_type, csrf_token }`
**Validation**:
- Document type validation
- File type (JPG, PNG, PDF)
- File size (max 5MB)
**Process**:
- Save to `uploads/kyc/`
- Insert into kyc_documents table
- Update user KYC status to 'pending'
- Notify admins
**Output**: Success/error message

### 5. Update Preferences
**Endpoint**: `POST /api/profile/update-preferences.php`
**Input**: `{ notification prefs, display prefs, privacy prefs, csrf_token }`
**Process**:
- Convert to JSON structure
- Insert or update user_preferences table
**Output**: Success/error message

### 6. Terminate Session
**Endpoint**: `POST /api/profile/terminate-session.php`
**Input**: `{ session_id, csrf_token }`
**Validation**:
- Session ownership verification
- Prevent terminating current session
**Process**:
- Delete from user_sessions table
- Log audit entry
**Output**: Success/error message

## JavaScript Functionality

### Core Functions
- `initializeProfile()` - Initialize all components
- `setupTabSwitching()` - Handle tab navigation
- `setupFormHandlers()` - Bind form submissions
- `setupAvatarUpload()` - Handle avatar changes
- `setupUserMenu()` - Dropdown menu toggle
- `setupLogout()` - Logout functionality

### Form Handlers
- `handlePersonalInfoUpdate()` - Update personal info
- `handlePasswordChange()` - Change password with validation
- `handleKYCUpload()` - Upload KYC documents
- `handlePreferencesUpdate()` - Save preferences
- `handleAvatarUpload()` - Upload and preview avatar

### Utility Functions
- `switchTab(tabName)` - Programmatic tab switching
- `terminateSession(sessionId)` - End session
- `resetForm(formId)` - Reset form fields
- `showAlert(type, message)` - Display notifications

## CSS Styling

### Components Styled
- Profile header with avatar
- Stats grid (responsive)
- Tab navigation
- Tab content panels
- Forms and inputs
- Activity timeline
- KYC status cards
- Document cards
- Session list
- Badges and status indicators
- Alerts and notifications

### Responsive Design
- Mobile-friendly layout
- Stacked stats on small screens
- Collapsible navigation
- Touch-friendly buttons

## Security Features

1. **CSRF Protection** - All forms include CSRF tokens
2. **Authentication** - Login required for all pages/APIs
3. **Authorization** - Users can only modify their own data
4. **Input Sanitization** - All inputs sanitized before storage
5. **File Validation** - Type and size checks on uploads
6. **Session Verification** - Session ownership checks
7. **Audit Logging** - All actions logged with IP/timestamp

## Usage

### Access Profile Page
1. User must be logged in
2. Navigate to: `http://localhost/Terratrade/profile.php`
3. Or click "Profile" in user dropdown menu

### Update Information
1. Navigate to desired tab
2. Fill in form fields
3. Click "Save" button
4. Success/error message displayed
5. Page reloads on success (where applicable)

### Upload Documents
1. Go to "KYC Verification" tab
2. Select document type
3. Choose file (JPG/PNG/PDF, max 5MB)
4. Click "Upload Document"
5. Document appears in submitted list with "Pending" status

### Manage Sessions
1. Go to "Security" tab
2. View all active sessions
3. Click "Terminate" on any session (except current)
4. Session immediately ended

## Integration Points

### With Existing System
- Uses existing `Auth` class for authentication
- Uses existing database helper functions
- Uses existing `logAudit()` for activity tracking
- Uses existing `sendNotification()` for alerts
- Follows existing CSRF token pattern
- Uses existing file upload structure

### Navigation Links
- Added to `index.php` user dropdown menu
- Accessible from all pages via header
- Returns to home via "Home" button

## Testing Checklist

- [ ] Profile page loads correctly
- [ ] All 6 tabs display properly
- [ ] Statistics show correct counts
- [ ] Personal info form updates database
- [ ] Password change validates and updates
- [ ] Avatar upload works and displays
- [ ] KYC document upload succeeds
- [ ] Preferences save correctly
- [ ] Session termination works
- [ ] Activity log displays
- [ ] All badges show correct status
- [ ] Responsive design on mobile
- [ ] CSRF protection active
- [ ] Error messages display
- [ ] Success messages display

## Future Enhancements

1. **Email verification** - Send verification emails on email change
2. **Phone verification** - SMS verification for phone numbers
3. **Two-factor authentication** - Add 2FA support
4. **Profile visibility** - Public profile pages
5. **Social links** - Add social media profiles
6. **Profile completion** - Progress indicator
7. **Export data** - GDPR compliance data export
8. **Account deletion** - Self-service account deletion
9. **Notification center** - In-app notification panel
10. **Activity filters** - Filter activity by type/date

## Notes

- All file paths are relative to TerraTrade root directory
- Database queries use prepared statements for security
- File uploads stored in `uploads/` subdirectories
- Session data updated on profile changes
- Audit logs created for all modifications
- Admin notifications sent on KYC submissions
