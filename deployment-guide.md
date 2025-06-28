# Easy Store Manager - Deployment Guide

## Prerequisites

### WordPress Requirements
- WordPress 5.0 or higher
- WooCommerce 4.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

### Server Requirements
- SSL certificate (HTTPS)
- Sufficient memory (minimum 256MB)
- Modern web server (Apache/Nginx)

## Installation Steps

### 1. File Deployment
```bash
# Upload files to your theme directory
/wp-content/themes/your-theme/
├── functions.php
├── store-management-app.html
├── store-management-app.js
└── assets/ (if any)
```

### 2. Database Setup
No additional database tables required. The plugin uses:
- WordPress users and roles
- WooCommerce products and orders
- WordPress transients for caching

### 3. Configuration

#### A. Update functions.php
Add the provided code to your theme's functions.php file or create a custom plugin.

#### B. Set Permissions
Ensure proper file permissions:
```bash
chmod 644 functions.php
chmod 644 store-management-app.html
chmod 644 store-management-app.js
```

#### C. Configure WooCommerce
- Enable REST API in WooCommerce settings
- Set appropriate stock management options
- Configure order statuses as needed

### 4. User Setup

#### Create Store Manager Users
```php
// Create a new user with store manager role
$user_id = wp_create_user('storemanager', 'secure_password', 'manager@store.com');
$user = new WP_User($user_id);
$user->set_role('store_manager_frontend');
```

#### Assign Existing Users
```php
// Assign role to existing user
$user = get_user_by('email', 'existing@user.com');
$user->add_role('store_manager_frontend');
```

## Security Configuration

### 1. SSL/HTTPS
Ensure your site uses HTTPS for all store management activities:
```php
// Force HTTPS for store management page
if (!is_ssl() && is_page('storemanagement')) {
    wp_redirect('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], 301);
    exit();
}
```

### 2. Rate Limiting
Configure rate limiting in wp-config.php:
```php
define('ESM_RATE_LIMIT_REQUESTS', 100); // requests per minute
define('ESM_RATE_LIMIT_WINDOW', 60); // seconds
```

### 3. Error Logging
Enable error logging for debugging:
```php
define('ESM_DEBUG_LOG', true);
define('ESM_LOG_API_ERRORS', true);
```

## Performance Optimization

### 1. Caching
```php
// Enable object caching for API responses
define('ESM_ENABLE_CACHING', true);
define('ESM_CACHE_DURATION', 300); // 5 minutes
```

### 2. Database Optimization
```sql
-- Add indexes for better performance
ALTER TABLE wp_posts ADD INDEX idx_post_type_status (post_type, post_status);
ALTER TABLE wp_postmeta ADD INDEX idx_meta_key_value (meta_key, meta_value(10));
```

### 3. CDN Configuration
Consider using a CDN for static assets:
```php
// CDN URL for assets
define('ESM_CDN_URL', 'https://cdn.yoursite.com');
```

## Testing Deployment

### 1. Functional Testing
- [ ] User role creation works
- [ ] Store management page loads
- [ ] API endpoints respond correctly
- [ ] Product CRUD operations work
- [ ] Order management functions
- [ ] Reports generate properly

### 2. Security Testing
- [ ] Unauthorized access blocked
- [ ] Input validation working
- [ ] Rate limiting active
- [ ] HTTPS enforced
- [ ] Error messages don't expose sensitive data

### 3. Performance Testing
- [ ] Page load times acceptable
- [ ] API response times under 2 seconds
- [ ] Database queries optimized
- [ ] Memory usage within limits

## Monitoring & Maintenance

### 1. Log Monitoring
Monitor these log files:
- WordPress debug log
- Server error logs
- API access logs
- Security event logs

### 2. Performance Monitoring
Track these metrics:
- API response times
- Database query performance
- Memory usage
- User session duration

### 3. Security Monitoring
Watch for:
- Failed login attempts
- Unusual API access patterns
- Rate limit violations
- Permission escalation attempts

## Troubleshooting

### Common Issues

#### 1. API Not Working
```php
// Check if WooCommerce is active
if (!class_exists('WooCommerce')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>Easy Store Manager requires WooCommerce to be active.</p></div>';
    });
}
```

#### 2. Permission Errors
```php
// Debug user capabilities
function debug_user_caps() {
    $user = wp_get_current_user();
    error_log('User capabilities: ' . print_r($user->allcaps, true));
}
```

#### 3. JavaScript Errors
- Check browser console for errors
- Verify Vue.js is loading correctly
- Ensure API endpoints are accessible

### Support Resources
- WordPress Codex
- WooCommerce Documentation
- Vue.js Documentation
- PHP Error Logs

## Backup & Recovery

### 1. Regular Backups
- Database backups (daily)
- File system backups (weekly)
- Configuration backups (before changes)

### 2. Recovery Procedures
- Test restore procedures regularly
- Document recovery steps
- Maintain offline backups

### 3. Version Control
- Use Git for code management
- Tag releases for easy rollback
- Document all changes