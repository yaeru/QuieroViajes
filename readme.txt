=== Quiero Viajes ===
Contributors: yaelduckwen
Tags: travel, taxi, corporate-trips, google-maps, custom-post-type
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 0.1.97
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Corporate trip and taxi management system integrated with Google Maps for WordPress.

== Description ==

**Quiero Viajes** is a tailored corporate trip management plugin for WordPress. Designed for taxi and private transit services (remis), it enables seamless route computation and fare estimation using the Google Maps Distance Matrix API. 

The plugin features dynamic price overrides based on corporate client agreements, allowing administrators to establish flat rates per kilometer either globally or personalized directly within the company profile.

### Key Features
* **Custom User Roles:** Built-in profiles for Companies (`empresa`) and Passengers (`pasajero`).
* **Google Maps Integration:** Automatic distance calculation, route mapping, and warning handling for API authentication issues.
* **Smart Corporate Pricing:** Sets a standard price per kilometer that can be overridden by specific corporate profiles.
* **Extra Expense Tracking:** Log supplementary trip costs (tolls, parking, waiting time) directly into the trip sheet.
* **Short trip surcharges:** Apply automated flat-rate fees for short distances.

== Installation ==

1. Upload the `quiero-viajes` folder to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the "Quiero Viajes" menu in your dashboard to configure your Google Maps API Key and general pricing settings.

== Usage ==

Once configured, managing trips is fully integrated into the WordPress Admin dashboard:

1. **Setup Profiles:** Create or update users under the **Empresa** or **Pasajero** role and assign custom kilometer rates if needed.
2. **Create a Trip:** Go to *Viajes > Añadir Nuevo*. 
3. **Log Route:** Enter the Origin and Destination addresses. The system will pull the distance using Google Maps and automatically apply either the general rate or the selected company's custom rate.
4. **Finalize:** Add extra expenses if applicable, assign a driver, and monitor the live breakdown in the sidebar summary box before saving.

== Frequently Asked Questions ==

= Does it require WooCommerce? =
No, this is a completely standalone management system that runs on standard WordPress custom post types and user metadata.

= Can it be extended? =
Yes. The plugin is built following clean object-oriented programming (OOP) principles, making it highly customizable for specialized logistics or billing features.

== Screenshots ==

1. Trip management dashboard with Google Places Autocomplete.
2. Live fare summary box including extra expenses and flat-rate overrides.

== Changelog ==

= 0.1.96 =
* Added dynamic passenger filtering linked to company selection.
* Improved seamless rate overrides using corporate profile metadata.

= 0.0.1 =
* Initial development version.

== Upgrade Notice ==

= 0.0.1 =
* First release of the plugin.