# Simple Contact Shortcode & Block Documentation

## Quick Navigation
- [Architecture](#architecture)
- [Data Schema](#data-schema)
- [Public APIs](#public-apis)
- [Actions & Filters](#actions--filters)
- [Shortcode Usage](#shortcode-usage)
- [Block Usage](#block-usage)
- [Submission Workflow](#submission-workflow)
- [Uninstall Behavior](#uninstall-behavior)
- [Testing & QA](#testing--qa)

## Architecture
The plugin is organized into modular classes under the `includes/` directory:
- `class-simple-contact-plugin.php`: Boots the plugin, registers hooks, and loads translations.
- `class-simple-contact-installer.php`: Handles database migrations via the activation hook and provides `maybe_upgrade_schema()`.
- `class-simple-contact-form.php`: Renders the contact form markup shared by the shortcode and block.
- `class-simple-contact-form-handler.php`: Processes submissions, sanitizes input, persists records, and dispatches notification emails.
- `class-simple-contact-shortcode.php`: Registers the `[simple_contact]` shortcode and delegates rendering to the shared form renderer.
- `class-simple-contact-block.php`: Registers the Gutenberg block `simple-contact/form` with server-side rendering using the shared form renderer.

Assets live under `assets/` and currently include a vanilla JavaScript block implementation in `assets/js/block.js`. Translation templates are kept in `languages/simple-contact.pot`.

## Data Schema
The plugin creates a custom table named `{prefix}sc_contacts` with the following columns:
| Column     | Type                 | Notes |
|------------|----------------------|-------|
| `id`       | `BIGINT UNSIGNED`    | Primary key. |
| `name`     | `VARCHAR(120)`       | Sanitized with `sanitize_text_field()`. |
| `email`    | `VARCHAR(190)`       | Validated via `is_email()`. |
| `created_at` | `DATETIME`         | Stored in UTC. |
| `consent_ip` | `VARBINARY(16)`    | IP address stored as packed binary when available. |
| `user_agent` | `VARCHAR(255)`     | Optional user agent string. |

Indexes: `PRIMARY (id)`, `INDEX (email)`, `INDEX (created_at)`.

Schema updates are managed by `Simple_Contact_Installer::maybe_upgrade_schema()` which records the schema version in the option `simple_contact_schema_version`.

## Public APIs
- Shortcode `[simple_contact]` with attributes:
  - `success_message` (string, optional) – Overrides the default localized success message.
  - `css_class` (string, optional) – Additional CSS classes appended to the wrapping `<div>`.
- Block `simple-contact/form` attributes:
  - `successMessage` (string) – Mirrors the shortcode `success_message` attribute.
  - `cssClass` (string) – Mirrors the shortcode `css_class` attribute.

## Actions & Filters
- `sc_before_insert_contact( array $sanitized_data )`: Fired before the submission data is persisted.
- `sc_after_insert_contact( int $contact_id, array $data )`: Fired after a new contact row is saved.
- `sc_email_to( string $recipient, array $data )`: Filters the notification recipient email address.
- `sc_email_subject( string $subject, array $data )`: Filters the notification subject line.
- `sc_email_headers( array $headers, array $data )`: Filters the notification email headers.
- `sc_success_message( string $message, array $data )`: Filters the success message shown to the user. The `$data` array mirrors the sanitized submission (name, email, created_at, consent_ip, user_agent) and includes the `insert_id` when available via the post-redirect token. The `consent_ip` entry is provided as a human-readable IPv4 or IPv6 string when available.

## Shortcode Usage
```
[simple_contact]
```
Optional parameters:
```
[simple_contact success_message="Thank you!" css_class="is-style-card"]
```

## Block Usage
Insert the **Simple Contact Form** block (`simple-contact/form`) from the Widgets category. Configure the success message and extra CSS classes via the block inspector.

## Submission Workflow
1. The form renders with fields for name and email plus a nonce and hidden redirect URL.
2. On submission, WordPress routes the request through the `admin_post` endpoints.
3. `Simple_Contact_Form_Handler` validates nonce, sanitizes input, and confirms email validity.
4. Valid submissions are inserted into `{prefix}sc_contacts` with the visitor IP and user agent when present.
5. Notification emails are sent to the site administrator. Filters allow customization of recipient, subject, and headers.
6. Users are redirected back to the originating page with `sc_status` and, on failure, `sc_error` query parameters. Successful submissions also append an `sc_token` that references a short-lived transient containing the sanitized payload.
7. The front-end renderer consumes the token, deletes the transient, and passes the payload (name, email, created_at, consent_ip, user_agent, insert_id) into the `sc_success_message` filter before escaping the message for display.

## Uninstall Behavior
Deleting the plugin triggers `uninstall.php`, which loads the installer class, drops the custom table via `maybe_drop_table()`, and removes the stored schema version option.

## Testing & QA
- Run `vendor/bin/phpcs --standard=WordPress --ignore=vendor .` to ensure coding standard compliance.
- Manually verify form submission happy and error paths on the front end.
- Confirm uninstall removes the database table and related options.
