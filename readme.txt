== Customization ==

= Template Overriding =

This plugin includes a template system that allows theme developers to override the default templates in their theme. To override a template:

1. Create a `facility-locator` folder in your theme's directory
2. Copy the template file from the plugin's `templates` directory to your theme's `facility-locator` directory
3. Modify the template as needed

For example, to override the main facility locator display:
- Copy `templates/public/facility-locator.php` from the plugin
- Paste it to `your-theme/facility-locator/public/facility-locator.php`
- Edit the template to match your theme's design

Available templates:
- `public/facility-locator.php` - Main frontend display
- `admin/facilities-list.php` - Admin facilities list

= CSS Customization =

You can add custom CSS to your theme to modify the appearance of the facility locator. The main elements use the following classes:

* `.facility-locator-container` - Main container
* `.facility-locator-cta-button` - CTA button
* `.facility-locator-popup` - Popup container
* `.facility-locator-map` - Map container
* `.facility-locator-list` - Facility list container

=== Facility Locator ===
Contributors: yourname
Tags: map, facilities, locator, google maps, directory
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin that displays facilities on a map based on user preferences through a multi-step form.

== Description ==

Facility Locator is a powerful WordPress plugin that allows you to create an interactive facility finder with a multi-step form. It's perfect for businesses with multiple locations, healthcare providers, retail chains, or any organization that needs to help users find their nearest facility.

= Key Features =

* **Interactive Google Maps integration** - Display all your facilities on a responsive map
* **Multi-step filtering form** - Customize a step-by-step form to help users find the most relevant facilities
* **Skip option** - Users can bypass the form to see all facilities at once
* **Customizable filters** - Categories and attributes can be added to facilities for detailed filtering
* **Facility management** - Add, edit, and delete facilities through an easy-to-use admin interface
* **Responsive design** - Works on all device sizes

= Use Cases =

* Healthcare providers can help patients find the nearest clinic
* Retail chains can direct customers to the nearest store
* Service providers can showcase locations with specific features
* Organizations can display branch locations along with their business hours

= Shortcode Usage =

Simply add the `[facility_locator]` shortcode to any page or post to display the facility locator.

== Installation ==

1. Upload the `facility-locator` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Facility Locator > Settings to add your Google Maps API key
4. Add facilities through the Facility Locator > Add New page
5. Place the shortcode `[facility_locator]` on any page or post

== Frequently Asked Questions ==

= Do I need a Google Maps API key? =

Yes, you need a Google Maps API key with the Maps JavaScript API and Places API enabled. You can get one from the [Google Cloud Console](https://console.cloud.google.com/).

= Can I customize the form steps? =

Yes, you can fully customize the form steps, including adding, removing, or modifying steps, fields, and options.

= How do I add facilities? =

Go to Facility Locator > Add New in your WordPress admin. Fill in the details, set the location on the map, and add categories and attributes as needed.

= Can users get directions to a facility? =

Yes, each facility listing includes a "Get Directions" link that opens Google Maps with directions from the user's location to the facility.

= Is the plugin mobile-friendly? =

Yes, the plugin is fully responsive and works well on mobile devices, tablets, and desktops.

== Screenshots ==

1. Frontend view of the facility locator with map and listings
2. Multi-step form popup
3. Admin interface for managing facilities
4. Settings page for customization

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release.
