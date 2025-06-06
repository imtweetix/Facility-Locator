# Facility Locator Performance Optimization Guide

## Overview

This guide covers the performance optimizations implemented in the Facility Locator plugin and how to properly deploy them for production use.

## Performance Features Implemented

### 1. External Asset Management

- **CSS externalization**: All inline styles moved to external files
- **JavaScript externalization**: All inline scripts moved to external files
- **Minification support**: Build script for creating minified versions
- **Asset versioning**: Cache busting with version numbers

### 2. Multi-Layer Caching System

- **Object cache**: Fast in-memory caching (Redis/Memcached compatible)
- **Transient cache**: Database-backed persistent caching
- **Query result caching**: Database query results cached
- **Template caching**: Rendered templates cached when possible

### 3. Database Optimizations

- **Indexes added**: Proper indexing for fast queries
- **Query optimization**: Efficient SQL queries with proper joins
- **Lazy loading**: Taxonomy objects loaded only when needed
- **Batch operations**: Multiple operations combined for efficiency

### 4. Frontend Optimizations

- **Lazy script loading**: Scripts loaded only when needed
- **Image optimization**: Carousel images with lazy loading
- **Map clustering**: Marker clustering for better map performance
- **AJAX optimization**: Clean AJAX responses with proper termination

## Deployment Instructions

### Step 1: Minify Assets for Production

```bash
# Navigate to your plugin directory
cd /path/to/wp-content/plugins/facility-locator

# Run the minification script
php build/minify.php
```

This creates minified versions of all CSS and JS files:

- `admin/css/facility-locator-admin.min.css`
- `admin/js/facility-locator-admin.min.js`
- `admin/js/facility-locator-facility-form.min.js`
- `public/css/facility-locator-public.min.css`
- `public/css/facility-locator-frontend.min.css`
- `public/js/facility-locator-public.min.js`

### Step 2: Update Asset Loading (Optional Enhancement)

For automatic minified asset loading, update your enqueue functions to use the production config:

```php
// In admin/class-facility-locator-admin.php
public function enqueue_styles()
{
    wp_enqueue_style(
        $this->plugin_name, 
        facility_locator_get_asset_url('admin/css/facility-locator-admin.css'), 
        array(), 
        $this->version, 
        'all'
    );
}
```

### Step 3: Enable Production Mode

Add this to your `wp-config.php` for production:

```php
// Enable minified assets in production
define('FACILITY_LOCATOR_USE_MINIFIED', true);

// Enable object caching if available
define('WP_CACHE', true);
```

### Step 4: Configure Server-Level Caching

#### Apache (.htaccess)

```apache
# Enable GZIP compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE text/javascript
</IfModule>

# Set cache headers for static assets
<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType text/javascript "access plus 1 year"
</IfModule>
```

#### Nginx

```nginx
# GZIP compression
gzip on;
gzip_types text/css application/javascript text/javascript;

# Cache headers
location ~* \.(css|js)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

## Cache Management

### Automatic Cache Clearing

The plugin automatically clears relevant caches when:

- Facilities are added, updated, or deleted
- Taxonomies are modified
- Settings are changed
- WordPress core events occur (theme changes, etc.)

### Manual Cache Management

```php
// Clear all plugin caches
Facility_Locator_Cache_Manager::clear_all_caches();

// Clear specific cache groups
Facility_Locator_Cache_Manager::clear_facility_caches();
Facility_Locator_Cache_Manager::clear_taxonomy_caches();
Facility_Locator_Cache_Manager::clear_frontend_caches();

// Warm up caches
Facility_Locator_Cache_Manager::warm_up_caches();
```

### Cache Statistics

Monitor cache performance:

```php
$stats = Facility_Locator_Cache_Manager::get_cache_stats();
echo "Object cache available: " . ($stats['object_cache_available'] ? 'Yes' : 'No');
echo "Transients count: " . $stats['transients_count'];
```

## Performance Monitoring

### Key Metrics to Monitor

1. **Page Load Time**: Measure before/after optimization
2. **Database Query Count**: Should decrease with caching
3. **Memory Usage**: Monitor with caching enabled
4. **AJAX Response Time**: Should improve with optimizations

### Recommended Tools

- **Query Monitor**: WordPress plugin for database query analysis
- **GTmetrix**: Website speed testing
- **Google PageSpeed Insights**: Performance analysis
- **New Relic**: Application performance monitoring

## Database Optimization

### Automatic Optimizations

The cache manager automatically:

- Adds missing database indexes
- Optimizes table structure
- Cleans up expired transients

### Manual Database Optimization

```php
// Run database optimization
Facility_Locator_Cache_Manager::optimize_database();
```

## Memory Optimization

### Implemented Optimizations

1. **Lazy Loading**: Objects loaded only when needed
2. **Static Caching**: Reuse objects within same request
3. **Efficient Queries**: Minimize database hits
4. **Clean AJAX**: Proper output buffer management

### Memory Usage Tips

- Use object caching (Redis/Memcached) for large datasets
- Monitor memory usage with large numbers of facilities
- Consider pagination for admin lists with 100+ facilities

## CDN Integration

For optimal performance, consider using a CDN:

1. **Static Assets**: Serve CSS/JS files from CDN
2. **Images**: Use CDN for facility images
3. **Map Tiles**: Consider map tile caching

## Troubleshooting Performance Issues

### Common Issues

1. **Slow AJAX responses**
   - Check database indexes
   - Verify caching is working
   - Monitor query count

2. **High memory usage**
   - Enable object caching
   - Check for plugin conflicts
   - Monitor large dataset handling

3. **Slow map loading**
   - Verify Google Maps API key
   - Check marker clustering
   - Monitor JavaScript errors

### Debug Mode

Enable debug mode to monitor performance:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check logs for cache operations and performance metrics.

## Production Checklist

- [ ] Run minification script
- [ ] Test minified assets in staging
- [ ] Enable production mode
- [ ] Configure server-level caching
- [ ] Set up CDN (if applicable)
- [ ] Monitor performance metrics
- [ ] Schedule cache maintenance
- [ ] Test AJAX functionality
- [ ] Verify Google Maps integration
- [ ] Check mobile performance

## Maintenance

### Regular Tasks

1. **Weekly**: Review cache statistics
2. **Monthly**: Clear expired transients
3. **Quarterly**: Database optimization
4. **Updates**: Re-run minification after changes

### Automated Maintenance

The plugin includes automated daily maintenance:

- Clear expired transients
- Optimize database indexes
- Warm up critical caches

This runs automatically via WordPress cron.

## Advanced Optimizations

### Custom Object Cache

For high-traffic sites, consider:

- Redis with persistent connections
- Memcached with connection pooling
- Custom cache key strategies

### Database Partitioning

For very large datasets:

- Consider table partitioning by location
- Implement data archiving for old facilities
- Use read replicas for heavy queries

### API Optimization

- Implement API response caching
- Use ETag headers for conditional requests
- Consider GraphQL for flexible data fetching

## Support and Updates

When updating the plugin:

1. Clear all caches after updates
2. Re-run minification script
3. Test performance in staging
4. Monitor for any degradation

For performance-related issues, provide:

- Cache statistics
- Database query logs
- Server configuration details
- Traffic patterns and dataset size