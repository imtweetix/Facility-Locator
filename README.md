# Facility Locator - WordPress Plugin

A modern, secure WordPress plugin for creating interactive facility finders with advanced filtering, image galleries, and Google Maps integration.

## 🚀 Features

- **Interactive Google Maps** with AdvancedMarkerElement support
- **Multi-step filtering** with customizable form builder
- **Image galleries** with up to 5 images per facility
- **Mobile-first responsive design**
- **Performance optimized** with multi-layer caching
- **Security hardened** with comprehensive validation
- **Modern development** with ES6+ JavaScript and SCSS

## 📋 Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.2 or higher
- **MySQL**: 5.6 or higher
- **Node.js**: 16+ (for development)
- **Google Maps API Key** (required for maps functionality)

## 🛠 Development Setup

### Installation

1. **Clone or download** the plugin to your WordPress plugins directory
2. **Install Node.js dependencies**:
   ```bash
   npm install
   ```
3. **Activate the plugin** in WordPress admin

### Development Workflow

1. **Start development mode** with file watching:
   ```bash
   npm run dev
   ```

2. **Build for production**:
   ```bash
   npm run build
   ```

3. **Create distribution package**:
   ```bash
   npm run dist
   ```

4. **Create WordPress.org ready ZIP**:
   ```bash
   npm run release:zip
   ```

### File Structure

```
facility-locator/
├── src/                          # Development source files
│   ├── admin/
│   │   ├── css/main.scss        # Admin styles (SCSS)
│   │   └── js/
│   │       ├── main.js          # Admin JavaScript entry
│   │       └── modules/         # Modular components
│   ├── public/
│   │   ├── css/main.scss        # Public styles (SCSS)
│   │   └── js/main.js           # Public JavaScript entry
│   └── shared/                  # Shared components
├── dist/                        # Built assets (auto-generated)
├── includes/                    # PHP classes and logic
├── admin/                       # Admin interface (PHP)
├── public/                      # Public interface (PHP)
├── templates/                   # Template files
├── build/                       # Build tools and scripts
├── tests/                       # Unit and integration tests
└── release/                     # Release packages
```

## 🔧 Build System

### Available Scripts

| Command | Description |
|---------|-------------|
| `npm run dev` | Development build with file watching |
| `npm run build` | Production build with minification |
| `npm run dist` | Create distribution with build + release prep |
| `npm run release:zip` | Create WordPress.org ready ZIP |
| `npm run lint:js` | Lint JavaScript files |
| `npm run lint:css` | Lint SCSS/CSS files |
| `npm run lint:php` | Lint PHP files (requires phpcs) |
| `npm run test` | Run JavaScript tests |

### Build Features

- **Webpack** for asset bundling and optimization
- **Babel** for modern JavaScript transpilation
- **SCSS** compilation with autoprefixer
- **Asset minification** for production
- **Source maps** for development
- **Code splitting** for optimized loading

## 🔒 Security Features

### Implemented Protections

- **SQL Injection Prevention**: All database queries use prepared statements
- **CSRF Protection**: Nonce verification on all forms
- **Input Validation**: Comprehensive sanitization and validation
- **Output Escaping**: All dynamic content properly escaped
- **File Upload Security**: Strict validation of uploaded files
- **Rate Limiting**: Protection against API abuse
- **Access Control**: Proper capability checks

### Security Best Practices

1. **Never edit files in `dist/` directly** - always work in `src/`
2. **Run security linting** before releases: `npm run lint:php`
3. **Update dependencies regularly** to patch vulnerabilities
4. **Use HTTPS** for production environments
5. **Restrict file permissions** appropriately

## 🚦 Performance Optimization

### Caching System

- **Multi-layer caching**: Memory > Object Cache > Transients
- **Intelligent expiration**: Adaptive cache durations
- **Cache warming**: Automated cache population
- **Cache invalidation**: Smart cache clearing on updates

### Database Optimization

- **Proper indexing**: Optimized database indexes
- **JSON queries**: Efficient taxonomy filtering
- **Query optimization**: Minimized database calls
- **Connection pooling**: Efficient database connections

### Asset Optimization

- **Minification**: CSS/JS compression for production
- **Code splitting**: Optimized loading strategies
- **Lazy loading**: Images and components load on demand
- **CDN ready**: Compatible with content delivery networks

## 🧪 Testing

### PHP Testing
```bash
# Install PHP testing dependencies
composer install

# Run PHP unit tests
vendor/bin/phpunit

# Run PHP code standards check
vendor/bin/phpcs --standard=WordPress
```

### JavaScript Testing
```bash
# Run JavaScript tests
npm run test

# Run tests with coverage
npm run test:coverage

# Run tests in watch mode
npm run test:watch
```

## 📦 Release Process

### Creating a Release

1. **Update version numbers** in all relevant files:
   - `facility-locator.php` (header comment and constant)
   - `package.json`
   - `readme.txt`

2. **Run full build and tests**:
   ```bash
   npm run build
   npm run test
   npm run lint:js
   npm run lint:css
   ```

3. **Create release package**:
   ```bash
   npm run dist
   npm run release:zip
   ```

4. **Test the release package** in a clean WordPress installation

5. **Tag the release** in version control:
   ```bash
   git tag v1.1.0
   git push origin v1.1.0
   ```

### WordPress.org Deployment

The `npm run release:zip` command creates a WordPress.org ready package that:

- Removes all development files
- Includes only production assets
- Updates plugin name (removes "DEV" suffix)
- Validates file structure
- Creates optimized ZIP archive

## 🔧 Configuration

### Google Maps Setup

1. **Get API Key** from [Google Cloud Console](https://console.cloud.google.com/)
2. **Enable APIs**:
   - Maps JavaScript API
   - Places API
   - Geocoding API (optional)
3. **Add API key** in WordPress admin: **Facility Locator > Settings**

### Plugin Settings

Configure the plugin through **Facility Locator > Settings**:

- **Google Maps API Key**: Required for map functionality
- **Map Settings**: Default zoom, center coordinates
- **CTA Button**: Custom text and colors
- **Performance**: Cache settings and optimization options

## 🐛 Troubleshooting

### Common Issues

**Maps not loading:**
- Verify Google Maps API key is valid
- Check browser console for JavaScript errors
- Ensure required APIs are enabled in Google Cloud Console

**Build errors:**
- Clear node_modules: `rm -rf node_modules && npm install`
- Check Node.js version (requires 16+)
- Verify all dependencies are installed

**Performance issues:**
- Enable object caching (Redis/Memcached)
- Check database indexes are created
- Verify cache is working: **Facility Locator > Settings > Cache Status**

### Debug Mode

Enable WordPress debug mode for development:
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check debug logs in `/wp-content/debug.log`

## 📚 API Reference

### PHP Hooks

**Actions:**
- `facility_locator_facility_added` - Fired when facility is created
- `facility_locator_facility_updated` - Fired when facility is updated
- `facility_locator_facility_deleted` - Fired when facility is deleted

**Filters:**
- `facility_locator_facility_data` - Modify facility data before save
- `facility_locator_query_args` - Modify facility query arguments
- `facility_locator_cache_duration` - Modify cache expiration time

### JavaScript Events

**Custom Events:**
- `facilityLocator:facilitiesLoaded` - Fired when facilities are loaded
- `facilityLocator:mapReady` - Fired when map is initialized
- `facilityLocator:filterChanged` - Fired when filters are updated

## 🤝 Contributing

### Development Guidelines

1. **Follow WordPress Coding Standards**
2. **Write tests** for new functionality
3. **Update documentation** for changes
4. **Use semantic versioning** for releases
5. **Test thoroughly** before submitting

### Code Style

- **PHP**: WordPress Coding Standards
- **JavaScript**: ESLint configuration provided
- **CSS/SCSS**: Stylelint configuration provided

## 📄 License

This plugin is licensed under the GPL v2 or later.

## 🆘 Support

For support and bug reports:
1. Check this documentation first
2. Search existing issues on GitHub
3. Create a new issue with detailed information
4. Include WordPress version, PHP version, and error logs

---

**Version**: 1.1.0
**Last Updated**: December 19, 2024
**Compatibility**: WordPress 5.0+ | PHP 7.2+