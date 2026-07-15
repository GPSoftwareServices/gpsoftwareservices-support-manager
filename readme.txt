=== GPSoftwareServices Support Manager ===
Contributors: gpsoftwareservices
Tags: technical support, customer portal, asset management, service contracts, help desk
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 5.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage customers, devices, support requests, interventions, service packages and annual contracts directly in WordPress.

== Description ==

GPSoftwareServices Support Manager is a distinctive support-management workspace for IT service companies, maintenance providers and independent technicians.

Main features include:

* Customer and device management.
* Technical interventions and work history.
* Support requests and customer-facing comments.
* Assistance packages with limited or unlimited interventions.
* Annual service contracts and contract value reporting.
* Customer portal with devices, contracts, requests and intervention history.
* Deadlines, calendar, economic reports and CSV exports.
* Multi-company data separation.
* Locally generated device QR codes, without sending device URLs or tokens to third parties.

The plugin stores its data in dedicated WordPress database tables. Deactivating the plugin never removes data. Administrators may explicitly enable data removal from the plugin settings before uninstalling it.

== Privacy ==

GPSoftwareServices Support Manager does not track administrators, customers or site visitors. It does not send usage analytics, device URLs, QR tokens or customer data to GP Software Services or to third-party services.

Device QR codes are generated locally in the browser using a bundled MIT-licensed QR encoder. No remote QR service is contacted.

== External services ==

This plugin does not require or contact any external service during normal operation.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install the ZIP file from the WordPress Plugins screen.
2. Activate the plugin.
3. Open **GPSoftwareServices Support Manager** in the WordPress administration area.
4. Configure the company and create the first customer.
5. Add the `[gpsuma_area_cliente]` shortcode to a protected page when a customer portal is required.

When upgrading from an earlier GP Support Manager build, plugin-owned `gat_*` tables, settings, roles and customer portal shortcodes are migrated automatically to the distinctive `gpsuma_*` prefix.

== Frequently Asked Questions ==

= Are plugin data deleted when the plugin is deactivated? =

No. Deactivation does not remove any data.

= Can data be deleted during uninstall? =

Yes. An administrator must explicitly enable this option in the plugin settings before uninstalling the plugin.

= Are unlimited assistance contracts supported? =

Yes. Both assistance packages and annual contracts can use unlimited interventions.

= Can customers view their contracts? =

Yes. Contracts assigned to the authenticated customer are displayed in the customer portal.

= Does the plugin send device information to a QR service? =

No. QR codes are generated locally from the device portal URL. No device URL, token or customer information is sent to an external QR provider.

== Screenshots ==

1. Dashboard with operational and economic summaries.
2. Customer management and customer detail view.
3. Device and asset inventory.
4. Technical interventions and service history.
5. Assistance packages and annual contracts.
6. Customer portal with devices, contracts, tickets and intervention history.
7. Intervention calendar and deadlines.

== Changelog ==

= 5.2.1 =
* Renamed the plugin to GPSoftwareServices Support Manager and adopted the requested `gpsoftwareservices-support-manager` slug and text domain.
* Replaced short internal identifiers with the distinctive `gpsuma` prefix and added automatic migration from legacy `gat_*` data.
* Removed the external QR-code service and bundled a local MIT-licensed QR encoder.
* Moved inline JavaScript and CSS to registered and enqueued assets.
* Moved the top-level administration menu to a less prominent position.
* Updated privacy, external-service and installation documentation.

= 5.1.1 =
* Aligned the previous text domain with the initial WordPress.org slug.

= 5.1.0 =
* Added WordPress.org-ready icon and banner assets.
* Improved plugin metadata and product branding.

= 5.0.0 =
* Completed the centralized data-access and request-validation refactor.
* Improved customer portal, reporting, contracts and intervention management.

== Upgrade Notice ==

= 5.2.1 =
Required naming and compliance update for WordPress.org review. Existing legacy tables, settings, customer roles and portal shortcode content are migrated automatically.
== Development ==

The complete source code for this plugin is publicly available at:

https://github.com/GPSoftwareServices/gpsoftwareservices-support-manager

The human-readable JavaScript source is available under:

assets/src/admin-v2.source.js

No build step is required.