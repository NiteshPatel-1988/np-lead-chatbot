# Lead Capture Chat

![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)
![Version](https://img.shields.io/badge/Version-2.4.4-orange)
![License](https://img.shields.io/badge/License-GPLv2-green)

A modern floating lead capture chat widget for WordPress. Collect visitor leads directly on your site and manage everything inside your WordPress dashboard - no SaaS, no monthly dependency, no data leaving your server.

---

## 🌐 Links

| | |
|---|---|
| 🌐 Live Demo & PRO Preview | https://leadcapturechat.netlify.app/ |
| 🎥 Watch the PRO Demo | https://www.youtube.com/watch?v=J-u0IBZRyaI |
| 🚀 Upgrade to PRO | https://checkout.freemius.com/plugin/29207/plan/48049/ |
| 🔌 WordPress.org Plugin Page | https://wordpress.org/plugins/np-lead-chatbot/ |

---

## Compatibility

- Tested with WordPress 7.1
- Compatible with WooCommerce
- Compatible with Elementor

## ✅ Free Features

- Floating animated lead capture widget
- Inline shortcode - `[npleadchat]`
- Leads dashboard with search, sorting, and bulk delete
- Basic settings for widget visibility and messages
- Basic admin email notifications
- Lead source page tracking
- Honeypot spam protection
- Configurable spam cooldown and duplicate submission protection
- CSV export
- Client-side validation
- REST API powered, works with page caching plugins
- GDPR-friendly local storage with suggested privacy policy text
- Locally bundled fonts (no Google Fonts request)
- Assets load only where the widget or shortcode is used
- Keyboard and screen reader friendly floating button
- Works with Elementor, Divi, Gutenberg, and WooCommerce

---

## 🔒 Security Features

### Input Validation
- ✅ Phone number format validation (digits, spaces, hyphens, +, parentheses, periods)
- ✅ Email format verification
- ✅ Textarea field sanitization
- ✅ Field length limits enforced by the REST route schema (name 191, phone 50, message 5,000 characters)
- ✅ Source page URL must belong to your own site

### REST API Security
- ✅ Public lead endpoint with a typed argument schema (non-string values are rejected)
- ✅ WordPress core verifies the REST nonce for logged-in users; visitors send none, so cached pages never break the form
- ✅ Proper error responses with HTTP status codes

### Email Security
- ✅ All email content properly sanitized
- ✅ User data escaped before email delivery
- ✅ Prevents content injection attacks

### Download Security
- ✅ MIME type sniffing prevention (`X-Content-Type-Options: nosniff`)
- ✅ Proper cache control headers
- ✅ Clickjacking protection (`X-Frame-Options: DENY`)
- ✅ Safe CSV export with injection prevention

### GDPR/Privacy Compliance
- ✅ Full privacy data export support (WordPress Tools > Privacy)
- ✅ Complete data erasure support (right to be forgotten)
- ✅ Integrated with WordPress privacy requests
- ✅ Suggested privacy policy text (Settings > Privacy > Policy Guide)
- ✅ GDPR-compliant lead management

### Additional Security
- ✅ Rate limiting per visitor IP and per email address
- ✅ Honeypot field for bot detection
- ✅ Duplicate submission prevention
- ✅ SQL injection prevention with prepared statements
- ✅ XSS prevention with output escaped at the point of printing
- ✅ Passes the WordPress.org Plugin Check tool

---

## 🚀 PRO Features

### Lead Capture
- Unlimited leads - no cap
- Custom Field Builder (Text, Email, Phone, Dropdown, Checkbox, Textarea)
- Drag-and-drop field reordering
- Live admin preview

### Email Automation
- Advanced admin notification templates
- Visitor auto-replies
- Template tag support
- Custom sender identity

### Spam Protection
- Google reCAPTCHA v3

### Appearance & Branding
- Full colour picker controls
- Widget position (left / right)
- Adjustable widget width
- Custom logo / profile image upload
- Editable welcome text and branding

### CRM Dashboard
- 5-stage lead pipeline management
- Inline status updates with AJAX
- Live KPI stats & conversion tracking
- Status filter tabs with lead counts
- Quick notes & full activity log
- Bulk status updates & CSV export
- Date range filters & last activity tracking
- Toast notifications & keyboard navigation

### Analytics Dashboard
- Interactive leads & conversion charts
- Pipeline funnel visualisation
- Top source pages tracking
- Live KPI summary dashboard
- AJAX-powered date range filtering
- Real-time analytics from your own database
- No external tracking or third-party services

> Plans start at $3/month · 14-day free trial · 30-day money-back guarantee

---

## Free vs PRO

| Feature | Free | PRO |
|---|:---:|:---:|
| Floating Chat Widget | ✅ | ✅ |
| Inline Shortcode | ✅ | ✅ |
| CSV Export | ✅ | ✅ |
| Basic Settings | ✅ | Full |
| Lead Source Page | ✅ | ✅ |
| Unlimited Leads | ❌ | ✅ |
| Custom Fields | ❌ | ✅ |
| Email Notifications | Basic | Advanced |
| Auto Replies | ❌ | ✅ |
| Honeypot Spam Protection | ✅ | ✅ |
| Spam Cooldown | ✅ | ✅ |
| Google reCAPTCHA v3 | ❌ | ✅ |
| Appearance Control | Basic | Full |
| Live Admin Preview | ❌ | ✅ |
| CRM Dashboard | ❌ | ✅ |
| Analytics Dashboard | ❌ | ✅ |
| Priority Support | ❌ | ✅ |

---

## Installation

**From WordPress.org (recommended)**

1. Go to **Plugins → Add New**
2. Search for **Lead Capture Chat**
3. Click **Install Now**, then **Activate**

**Manual**

```bash
# Download the ZIP from WordPress.org, then:
# WP Admin → Plugins → Add New → Upload Plugin → Activate
```

After activation the floating widget appears automatically. Visit **WP Admin → NP Lead Chatbot** to manage leads.

---

## Shortcode

```php
[npleadchat]
```

Embeds the lead form inline anywhere - pages, posts, or widget areas.

---

## 📸 Screenshots

### Floating chat widget

![Floating chat widget](assets/floating-chatwidget.png)

### Leads Dashboard

![Leads Dashboard](assets/leads-dashboard.png)

### PRO: Leads dashboard (CRM)

![PRO: Leads dashboard (CRM)](assets/leads-dashboard-crm.png)

### PRO: Status dropdown

![PRO: Status dropdown](assets/status-dropdown.png)

### PRO: Lead detail modal - Notes tab

![PRO: Lead detail modal - Notes tab](assets/notes-tab.png)

### PRO: Analytics dashboard

![PRO: Analytics dashboard](assets/analytics-dashboard.png)

### PRO: Settings + live preview

![PRO: Settings + live preview](assets/settings-livepreview.png)

### PRO: Custom Field Builder + Spam Protection + Email notifications

![PRO: Custom Field Builder + Spam Protection + Email notifications](assets/customfields-spamprotection-email.png)

---

## Changelog

### 2.4.4
- **Tested** with WordPress 7.1
- **Security** lead form fields now have length limits, and the source page URL must belong to your own site
- **Security** rate limiting now applies per visitor IP and per email separately, so changing one of them no longer bypasses the cooldown
- **Fixed** lead form no longer fails with a nonce error on cached pages
- **Fixed** frontend CSS/JS only load when the floating widget or shortcode is used
- **Fixed** floating chat button now opens with Enter/Space keys and announces its open state to screen readers
- **Improved** leads list uses database pagination, so large lead lists load faster
- **Improved** privacy policy suggestion text added under Settings > Privacy
- **Improved** uninstall now also cleans up on multisite and removes the dismissed-notice option
- **Removed** unused rate limit clearing function

### 2.4.3

- **Added** full GDPR compliance support - captured leads are now included in WordPress's built-in Tools → Export Personal Data and Erase Personal Data requests.
- **Fixed** admin stylesheet not loading on the Settings page
- **Fixed** "Upgrade to PRO" admin notice showing redundantly on the Upgrade to PRO page itself

### 2.4.1
- Changed demo video url

### 2.4.0 - Security & GDPR Enhancement
- **Added** comprehensive phone number format validation (digits, spaces, hyphens, +, parentheses, periods)
- **Added** full GDPR/Privacy compliance support with WordPress privacy export/erase hooks
- **Improved** REST API nonce header handling for better compatibility (supports both `x-wp-nonce` and `X-WP-Nonce`)
- **Improved** email content sanitization - all user data properly escaped before email delivery
- **Improved** CSV export with enhanced security headers 
- **Enhanced** REST API error handling with proper WP_Error responses and HTTP status codes
- **Added** rate limit management function for admin testing and debugging

### 2.3.4
- Added basic admin email notifications with recipient setting
- Added configurable spam cooldown and duplicate submission protection
- Improved frontend form accessibility by removing duplicate field IDs across shortcode and floating widget instances

### 2.3.3
- Added basic settings for floating widget visibility and messages
- Added lead source page tracking in the leads dashboard and CSV export
- Added lightweight honeypot spam protection

### 2.3.2
- Updated plugin tags and improved short description for better discoverability
- Fixed the floating widget close button styling issue
- Tested compatibility with WordPress 7.0

### 2.3.1 
- Add new screenshots of PRO Plugin
- Make some content changes in the admin Upgrade to PRO page
- Minor bug fixes and styling improvements

### 2.3.0
- **Added** in-admin **Upgrade to PRO** page (`WP Admin → NP Lead Chatbot → Upgrade to PRO 🚀`) with full feature list, Free vs PRO comparison table, and live preview / checkout links
- **Added** dismissible admin notice promoting PRO - permanently dismissible per-user via a nonce-protected link, no JavaScript dependency
- **Improved** all upsell code is fully i18n-ready, properly escaped, and follows WordPress coding standards
- **Improved** upsell is automatically disabled when the PRO version is active - no duplicate notices or menus
- Version bumped to **2.3.0**

### 2.2.3
- Improved upgrade CTAs
- Added comparison table
- Enhanced PRO promotion in readme

### 2.2.2
- Added demo video link
- Minor readme content changes

### 2.2.0
- Added Pro upsell section in plugin description
- New FAQ entries for Pro features

### 2.0.0
- Complete UI redesign - gradient header, DM Sans font, animated popup
- Locally bundled fonts (removed Google Fonts dependency)
- Added ARIA labels and accessibility improvements
- Added `[npleadchat]` inline shortcode

### 1.2.0
- Added CSV export

### 1.0.0
- Initial release

---

## Privacy

All lead data is stored inside your own WordPress database. The plugin makes no external API requests. No tracking, no SaaS dependency, no third-party storage.

---

## Contributing

Issues and pull requests are welcome via the [GitHub repository](https://github.com/NiteshPatel-1988/np-lead-chatbot).

### Building a release zip

Development-only files (`.gitattributes`, `README.md`) are marked `export-ignore`, so build the WordPress.org package from a committed state with:

```bash
git archive -o ../np-lead-chatbot.zip --prefix=np-lead-chatbot/ HEAD
```

---

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html
