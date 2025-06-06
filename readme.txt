=== Facility Locator ===
Contributors: guardianrecovery
Tags: map, facilities, locator, google maps, directory, healthcare, recovery, treatment centers
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A powerful WordPress plugin for creating interactive facility finders with advanced filtering, image galleries, and Google Maps integration.

== Description ==

Facility Locator is a comprehensive WordPress plugin designed for organizations with multiple locations who need to help users find their nearest facility. Perfect for healthcare providers, treatment centers, retail chains, or any business with multiple locations.

= Key Features =

**🗺️ Advanced Google Maps Integration**
* Interactive Google Maps with AdvancedMarkerElement support
* Custom map pins with image support
* Marker clustering for better performance
* Mobile-optimized map interface
* Info windows with facility details

**🔍 Powerful Multi-Step Filtering**
* Customizable multi-step form builder
* Six built-in taxonomy types: Levels of Care, Features, Therapies, Environment, Location, Insurance Providers
* Skip option to browse all facilities
* Real-time filtering with instant results
* Mobile-friendly filter drawer

**🖼️ Image Gallery Support**
* Up to 5 images per facility
* Responsive image carousel with navigation
* Drag-and-drop image reordering in admin
* Lazy loading for optimal performance
* Fallback support for facilities without images

**⚡ Performance Optimized**
* Multi-layer caching system (Object cache, Transients, Database optimization)
* Asset minification support for production
* Lazy loading and efficient queries
* Cache warming and automated maintenance
* Database indexing for fast searches

**📱 Mobile-First Design**
* Fully responsive interface
* Touch-friendly controls
* Mobile filter drawer
* Optimized for all screen sizes
* Progressive web app ready

**🎨 Customizable Interface**
* Template override system for theme developers
* Customizable CTA button colors and text
* Modern, clean design aesthetic
* CSS custom properties support
* Accessibility compliant (WCAG 2.1)

**🛠️ Developer Friendly**
* Template override system
* Extensive hook system
* REST API compatible
* Production build tools included
* Comprehensive documentation

= Use Cases =

* **Healthcare Providers** - Help patients find the nearest clinic or treatment center
* **Recovery Centers** - Connect individuals with appropriate treatment facilities
* **Retail Chains** - Direct customers to the nearest store location
* **Service Providers** - Showcase locations with specific features and capabilities
* **Educational Institutions** - Display campus locations and facilities
* **Government Agencies** - Show office locations and services offered

= Taxonomy System =

The plugin includes six built-in taxonomy types that can be customized for any industry:

* **Levels of Care** - Treatment levels, service tiers, or facility types
* **Features** - Amenities, services, or special offerings
* **Therapies** - Treatment methods, specializations, or programs
* **Environment** - Setting types, accommodations, or facility characteristics
* **Location** - Geographic regions, coverage areas, or service zones
* **Insurance Providers** - Accepted insurance plans or payment methods

= Shortcode Usage =

Display the facility locator anywhere using the simple shortcode:

`[facility_locator]`

You can also specify a custom ID:

`[facility_locator id="my-custom-locator"]`

= Performance Features =

* **Caching System** - Multi-layer caching with Redis/Memcached support
* **Asset Optimization** - Minified CSS/JS files for production
* **Database Optimization** - Proper indexing and efficient queries
* **Lazy Loading** - Images and map components load on demand
* **CDN Ready** - Compatible with content delivery networks

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Navigate to Plugins > Add New
3. Search for "Facility Locator"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin ZIP file
2. Upload the `facility-locator` folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress

= Configuration =

1. Go to **Facility Locator > Settings** and add your Google Maps API key
2. Configure your CTA button text and colors
3. Set up your form steps in **Facility Locator > Form Configuration**
4. Add your taxonomies (Levels of Care, Features, etc.)
5. Add your first facility in **Facility Locator > Add New**
6. Place the shortcode `[facility_locator]` on any page or post

== Frequently Asked Questions ==

= Do I need a Google Maps API key? =

Yes, you need a Google Maps API key with the following APIs enabled:
* Maps JavaScript API
* Places API
* Geocoding API (optional, for address validation)

You can get a free API key from the [Google Cloud Console](https://console.cloud.google.com/).

= Can I customize the form steps? =

Absolutely! Go to **Facility Locator > Form Configuration** to create custom multi-step forms. You can:
* Add unlimited steps
* Create columns within steps
* Use radio buttons, checkboxes, or dropdowns
* Populate options from taxonomies or create manual options
* Drag and drop to reorder steps and options

= How do I add custom map pins? =

You can set custom map pins in two ways:
1. **Default Pin** - Set a default pin image in Settings that applies to all facilities
2. **Custom Pin** - Upload a unique pin image for each individual facility

Supported formats: PNG, JPG, GIF. Recommended size: 32x40 pixels.

= Can I override the templates? =

Yes! The plugin includes a complete template override system:

1. Create a `facility-locator` folder in your active theme
2. Copy template files from the plugin's `templates` directory
3. Modify the templates to match your design

Available templates:
* `public/public-template.php` - Main frontend display
* `admin/facilities-list.php` - Admin facilities list
* Custom templates can be added as needed

= Is the plugin mobile-friendly? =

The plugin is built with a mobile-first approach and includes:
* Responsive design that works on all devices
* Touch-friendly controls and navigation
* Mobile-specific filter drawer
* Optimized map interactions for mobile
* Fast loading on slower connections

= Can I import existing facility data? =

While the plugin doesn't include a built-in import tool, developers can use the plugin's API to import data programmatically. The database structure is well-documented for custom import scripts.

= How do I optimize performance? =

The plugin includes several performance features:

1. **Enable Caching** - The plugin automatically uses WordPress object caching if available
2. **Use Minified Assets** - Run the build script: `php build/minify.php`
3. **Configure CDN** - Serve assets from a content delivery network
4. **Optimize Images** - Use compressed images for facilities and map pins
5. **Database Maintenance** - The plugin includes automated cache maintenance

= Can I use custom fields? =

The plugin includes extensive taxonomy support, but for additional custom fields, you can:
* Use the description field for rich content
* Extend the plugin with custom hooks
* Create custom templates with additional data

= Does it work with page builders? =

Yes! The plugin works with all major page builders:
* Elementor (use the shortcode widget)
* Gutenberg (use the shortcode block)
* Beaver Builder (use the text/HTML module)
* Divi (use the code module)
* Visual Composer (use the raw HTML element)

= How do I get support? =

For support, please:
1. Check this FAQ section
2. Review the plugin documentation
3. Contact the plugin developers through the support channels

== Screenshots ==

1. **Frontend Interface** - Modern, responsive facility locator with map and sidebar
2. **Multi-Step Form** - Customizable filtering form with skip option
3. **Mobile View** - Optimized mobile interface with filter drawer
4. **Facility Cards** - Rich facility cards with image galleries and details
5. **Admin Dashboard** - Comprehensive facility management interface
6. **Form Builder** - Drag-and-drop form configuration tool
7. **Taxonomy Management** - Easy management of categories and attributes
8. **Settings Panel** - Google Maps integration and customization options
9. **Image Gallery** - Multiple images per facility with carousel display
10. **Custom Map Pins** - Support for custom map markers and branding

== Changelog ==

= 1.0.0 =
**Major Release - Complete Rewrite**

**New Features:**
* Advanced Google Maps integration with AdvancedMarkerElement
* Image gallery support (up to 5 images per facility)
* Six built-in taxonomy types with unlimited extensibility
* Multi-step form builder with drag-and-drop interface
* Mobile-first responsive design
* Template override system for theme developers
* Performance optimization with multi-layer caching
* Production build system with asset minification
* Enhanced admin interface with improved UX

**Performance Improvements:**
* Multi-layer caching system (Object cache + Transients)
* Database optimization with proper indexing
* Asset minification and optimization
* Lazy loading for images and map components
* Efficient AJAX responses with clean termination
* Automated cache maintenance and warming

**Technical Enhancements:**
* Modern JavaScript with ES6+ features
* Improved error handling and debugging
* Better mobile performance and touch interactions
* Enhanced accessibility (WCAG 2.1 compliant)
* RESTful AJAX architecture
* Comprehensive hook system for developers

**Admin Improvements:**
* Redesigned admin interface
* Drag-and-drop form builder
* Image gallery management
* Taxonomy system with usage tracking
* Real-time form preview
* Enhanced facility editing experience

**Frontend Enhancements:**
* Google Maps style interface design
* Smooth animations and transitions
* Advanced filtering with real-time results
* Mobile filter drawer
* Facility detail modals
* Image carousel with navigation

= 0.x.x =
* Previous versions (legacy)

== Upgrade Notice ==

= 1.0.0 =
Major update with new features and performance improvements. Please backup your site before updating. The plugin includes automatic database migration but custom modifications may need adjustment.

**New Features in 1.0.0:**
* Image gallery support
* Enhanced taxonomy system
* Mobile-optimized interface
* Performance caching system
* Form builder improvements

**Breaking Changes:**
* Template structure updated (automatic migration included)
* New database schema (automatic migration included)
* Updated JavaScript architecture (may affect custom modifications)

**Recommended Actions After Update:**
1. Clear any existing caches
2. Test form functionality
3. Verify Google Maps integration
4. Check mobile responsiveness
5. Review customizations if any

== Technical Requirements ==

**Minimum Requirements:**
* WordPress 5.0 or higher
* PHP 7.2 or higher
* MySQL 5.6 or higher
* Modern web browser with JavaScript enabled

**Recommended:**
* WordPress 6.0+
* PHP 8.0+
* MySQL 8.0+
* Redis or Memcached for object caching
* CDN for asset delivery

**Google Maps Setup:**
* Google Cloud Console account
* Maps JavaScript API enabled
* Places API enabled
* Geocoding API enabled (optional)
* Valid billing account (Google provides free tier)

**For Developers:**
* Node.js 14+ (for build tools)
* Composer (for PHP dependencies)
* Git (for version control)

== Performance Optimization ==

For optimal performance, consider:

1. **Object Caching** - Install Redis or Memcached
2. **CDN** - Use a content delivery network
3. **Image Optimization** - Compress facility images
4. **Asset Minification** - Run the included build script
5. **Database Optimization** - Regular maintenance included
6. **Monitoring** - Use performance monitoring tools

See the included performance documentation for detailed optimization guide.

== Developer Information ==

**Hooks Available:**
* `facility_locator_facility_saved`
* `facility_locator_facility_deleted`
* `facility_locator_taxonomy_saved`
* `facility_locator_taxonomy_deleted`
* `facility_locator_settings_updated`

**Template Locations:**
* `templates/public/public-template.php`
* `templates/admin/facilities-list.php`

**Cache Groups:**
* `facility_locator_facilities`
* `facility_locator_taxonomies`
* `facility_locator_frontend`

**API Endpoints:**
* `wp-ajax-get_facilities`
* `wp-ajax-nopriv-get_facilities`

For complete developer documentation, see the included docs folder.