# AGENTS.md — Simple Contact Shortcode & Block

## Project Objective
Create a WordPress plugin named **Simple Contact Shortcode & Block** that exposes a simple contact form as:
- A shortcode: `[simple_contact]`
- A Gutenberg block: `simple-contact/form`

The form collects **Name** and **Email**, saves submissions into a custom database table, and sends a notification email to the site administrator. The plugin must strictly follow WordPress Coding Standards and security best practices. All code and documentation must be in **English**, and all user-facing strings must be **translatable** using the **text domain `simple-contact`**.

---

## Non-Negotiable Standards

1. **Coding Standards & PHPCS**
   - Must pass **PHPCS** with the official WordPress Coding Standards ruleset.
   - **Tabs for indentation**, **spaces for alignment**. No trailing whitespace.
   - Consistent array formatting, parameter alignment, and inline documentation.
   - No unused imports, dead code, or commented-out blocks left behind.

2. **Documentation Discipline**
   - Every PHP file: header PHPDoc describing purpose, package, since, and author.
   - Every class, method, and function: full docblocks (summary, `@param`, `@return`, `@since`, `@see` when relevant).
   - Inline comments for non-obvious logic and security-relevant branches.
   - All docs and comments in **English**.

3. **Security**
   - **Input sanitization**: use the correct sanitizers (`sanitize_text_field`, `sanitize_email`, `absint`, `sanitize_key`, `wp_unslash`, etc.).
   - **Output escaping**: use `esc_html`, `esc_attr`, `esc_url`, or `wp_kses` (with strict allowed HTML) right before rendering.
   - **Nonces**: all form submissions must include a nonce and be verified server-side with `check_admin_referer`.
   - **SQL safety**: use `$wpdb->prepare()` for all SQL with untrusted input.
   - **Capabilities**: any admin action or page must check appropriate capabilities (e.g., `manage_options` or a custom capability).
   - **No secrets in repo**: no API keys or secrets committed.
   - **No PII leaks**: never echo raw user input; log carefully (avoid sensitive data).

4. **Internationalization (i18n)**
   - All user-facing strings must be wrapped with translation functions: `__()`, `_e()`, `esc_html__()`, `esc_attr__()`, etc.
   - Text domain: **`simple-contact`**.
   - Provide `/languages/simple-contact.pot` and keep it updated.

5. **Pull Request (PR) Policy**
   - PRs must be **small, focused, and self-contained**.
   - If a change involves **very large files** (e.g., `.pot`, vendor bundles, generated assets), **split into multiple PRs** so PRs remain reviewable and pass size limits.
   - Every PR must:
     - Pass **PHPCS**.
     - Update **Documents.md** if it touches public surface (APIs, hooks, schema, UI strings).
     - Update **TODO.md** (maintained by Codex) to reflect what moved to “Completed” and any newly discovered tasks.
     - Include clear testing/verification notes.

---

## Repository Hygiene: .gitignore and .gitattributes

To ensure clean source control and clean release packages:

### 1) `.gitignore` (development junk and OS files)
Codex must create a `.gitignore` at repo root including (at minimum):

OS files

.DS_Store
.DS_Store?
Thumbs.db
ehthumbs.db
Icon?
._*

IDE/editor

.vscode/
.idea/
*.iml

Node / frontend builds

node_modules/
npm-debug.log*
yarn-error.log
dist/
build/

PHP / tooling

vendor/
composer.lock

Logs & temp

*.log
*.tmp
*.cache

Coverage / reports

coverage/
.clover
.phpunit.result.cache

If the project later adopts Composer or a build step, extend accordingly.

### 2) `.gitattributes` (control what goes into release packages)
Codex must create a `.gitattributes` at repo root to **exclude non-distribution files** from exported archives (GitHub “Download ZIP”, composer dist, etc.). Use `export-ignore` rules. At minimum:

/.gitattributes export-ignore
/.gitignore    export-ignore
/.github/      export-ignore
/.vscode/      export-ignore
/.idea/        export-ignore
/tests/        export-ignore
/docs/         export-ignore
/.editorconfig export-ignore
/phpcs.xml*    export-ignore
/phpmd.xml*    export-ignore
/phpunit.xml*  export-ignore
/CHANGELOG.md  export-ignore
/README.md     export-ignore
/CONTRIBUTING.md export-ignore
/package.json  export-ignore
/package-lock.json export-ignore
/yarn.lock     export-ignore
/webpack.*     export-ignore
/rollup.*      export-ignore
/vite.*        export-ignore
/node_modules/ export-ignore
/src/          export-ignore

Adjust the list as the toolchain evolves. The goal: **the release ZIP must only include what WordPress needs to run the plugin** (PHP in `includes/`, main bootstrap, assets built for runtime, languages). OS files like `.DS_Store` and Windows metadata must never appear in packages.

---

## File & Naming Conventions

### Directory Structure

/simple-contact/
├─ simple-contact.php              # Main plugin bootstrap (hooks, constants, loaders)
├─ uninstall.php                   # Cleanup on uninstall
├─ includes/
│  ├─ class-sc-database.php        # DB schema + CRUD
│  ├─ class-sc-form.php            # Form render + submission handler + shortcode
│  └─ class-sc-block.php           # Gutenberg block registration + asset logic
├─ assets/
│  ├─ css/style.css                # Frontend styles (scoped to plugin classes)
│  └─ js/block.js                  # Block editor script (registered via block.json)
├─ languages/
│  └─ simple-contact.pot           # Translation template (exported for translators)

### Class Names
- Prefix: `SC_`
- Examples: `SC_Database`, `SC_Form`, `SC_Block`

### Global Function Names
- Prefix: `sc_`
- Example: `sc_render_form()`

### Script & Style Handles
- Prefix: `sc-`
- Examples: `sc-frontend`, `sc-block-editor`, `sc-block-style`

### PHP File Headers
Each PHP file must start with a PHPDoc header block:
- Summary line (what this file does).
- `@package` plugin slug (e.g., `simple-contact`).
- `@since` version string.
- Optionally `@author`.

### Method & Function Docblocks
- One-line summary, a blank line, then detailed description if needed.
- `@param` for each parameter with type and description.
- `@return` with type and description.
- `@since` tag.

---

## Required Files: Responsibilities & Content

### 1) `simple-contact.php`
- **Plugin header**: Name, Description, Version, Author, License, Text Domain, Domain Path.
- **Constants**: `SC_PLUGIN_VERSION`, `SC_PLUGIN_FILE`, `SC_PLUGIN_DIR`, `SC_PLUGIN_URL`.
- **Load text domain** in `plugins_loaded`.
- **Includes**: require `includes/class-sc-database.php`, `includes/class-sc-form.php`, `includes/class-sc-block.php`.
- **Activation hook**: create DB table via `SC_Database`.
- **Deactivation hook**: no destructive action; keep data unless uninstall.
- **Bootstrap**: instantiate singletons or initialize hooks.

### 2) `uninstall.php`
- Verify `defined( 'WP_UNINSTALL_PLUGIN' )`.
- Drop custom table.
- Delete plugin options (if any).
- Leave no orphaned data created by the plugin (besides posts/users not owned by plugin).

### 3) `includes/class-sc-database.php`
- Singleton pattern (`SC_Database::instance()`).
- `create_table()` with `dbDelta()` for initial schema.
- Versioned migrations (if schema evolves) with an option `sc_db_version`.
- CRUD:
  - `insert_contact( array $data ): int|WP_Error`
  - `get_contacts( array $args = [] ): array`
- Strict sanitization at boundaries and `$wpdb->prepare()` everywhere.
- Store timestamps in **UTC**.

### 4) `includes/class-sc-form.php`
- Singleton pattern (`SC_Form::instance()`).
- Rendering method: `render_form( array $atts = [] ): string`
  - Attributes: `success_message` (string), `css_class` (string).
  - Output escaped and HTML well-formed.
- Submission handler: `handle_submission(): int|WP_Error`
  - Verify nonce.
  - Sanitize inputs.
  - Validate email with `is_email()`.
  - Insert via `SC_Database`.
  - Send email to admin (filterable).
- Shortcode registration: `[simple_contact]` mapping to render method.
- Hooks:
  - Actions: `sc_before_insert_contact`, `sc_after_insert_contact`
  - Filters: `sc_email_to`, `sc_email_subject`, `sc_email_headers`, `sc_success_message`

### 5) `includes/class-sc-block.php`
- Registers block via `block.json`.
- Ensures attributes mirror shortcode attributes:
  - `successMessage` (string)
  - `cssClass` (string)
- Registers/enqueues editor script (`assets/js/block.js`) and frontend style (`assets/css/style.css`) using versioned handles.
- Server-side render may call `SC_Form::render_form()` for parity with shortcode.

### 6) `assets/css/style.css`
- Minimal, theme-friendly CSS.
- Scope under a unique wrapper (e.g., `.sc-form`) to avoid conflicts.

### 7) `assets/js/block.js`
- Block registration (edit + save).
- Inspector controls for `successMessage` and `cssClass`.
- Editor preview lightweight and consistent.

### 8) `languages/simple-contact.pot`
- Exported from sources, includes all translatable strings.
- If `.pot` is very large, split PRs to keep reviewability (do not skip updates).

---

## Database Schema

Table: `{$wpdb->prefix}sc_contacts`

| Column      | Type                              | Notes                                          |
|-------------|-----------------------------------|------------------------------------------------|
| id          | BIGINT UNSIGNED AUTO_INCREMENT    | Primary key                                    |
| name        | VARCHAR(120)                      | Sanitized via `sanitize_text_field()`          |
| email       | VARCHAR(190)                      | Validate with `is_email()`                     |
| created_at  | DATETIME                          | Stored in UTC                                  |
| consent_ip  | VARBINARY(16) NULL                | Optional (IPv4/IPv6 stored as binary)          |
| user_agent  | VARCHAR(255) NULL                 | Optional                                       |

Indexes:
- `PRIMARY (id)`
- `INDEX (email)`
- `INDEX (created_at)`

Migrations:
- Implement `maybe_upgrade_schema()` with version option to handle future changes.

---

## Shortcode Specification

- **Tag**: `[simple_contact]`
- **Attributes**:
  - `success_message` (string, optional; default localized string)
  - `css_class` (string, optional)
- **Example**:

[simple_contact success_message=“Thank you, we will contact you soon.” css_class=“is-style-card”]

---

## Block Specification

- **Name**: `simple-contact/form`
- **Category**: `widgets`
- **Attributes**:
- `successMessage` (string)
- `cssClass` (string)
- Inspector controls must allow configuring both.
- Save attributes and ensure front end mirrors shortcode behavior.

---

## Actions & Filters (Public Surface)

**Actions**
- `sc_before_insert_contact( array $sanitized_data )`
- `sc_after_insert_contact( int $contact_id, array $data )`

**Filters**
- `sc_email_to( string $recipient, array $data )`
- `sc_email_subject( string $subject, array $data )`
- `sc_email_headers( array $headers, array $data )`
- `sc_success_message( string $message, array $data )`

Every time a new action/filter is added or changed, update **Documents.md** with signature, timing, and usage example.

---

## The Role of TODO.md (Maintained by Codex)

- `TODO.md` is **owned and maintained by Codex** from the first commit.
- Must include, at minimum, sections for **Pending**, **In Progress**, **Completed**.
- Codex decides task breakdown and order, moves items across states, and **adds new tasks proactively** when necessary.
- Human collaborators must **not** edit `TODO.md` directly; they request changes and Codex updates it.

---

## The Role of Documents.md (Maintained by Codex)

- `Documents.md` is a **living knowledge base** of what exists now:
- Quick navigation
- Architecture (modules and responsibilities)
- Data schema and migrations
- Public API (classes, methods, functions)
- Actions & Filters catalog
- Shortcode and Block usage
- Workflows (submission, uninstall)
- How-tos (extend/customize)
- Testing & QA
- Release notes (short); full details in `CHANGELOG.md`
- Glossary (prefixes, naming, terms)
- **Every PR** that changes public surface or behavior must update `Documents.md`. PRs lacking necessary docs updates must be rejected.

---

## Testing & QA

- Manual verification steps must be described in PRs (form renders, nonce verified, invalid email rejected, success path stores row and sends email, uninstall cleans).
- Where applicable, include basic automated tests or reproducible steps.
- Accessibility: form labels/ids, button text, error messages readable and localized.
- Performance: avoid unnecessary queries; enqueue only when needed.

---

## Release Packaging Policy

- The **distributed ZIP** (WordPress plugin package) must contain only runtime necessities:
- `simple-contact.php`, `uninstall.php`, `/includes/`, built `/assets/` for runtime, `/languages/`.
- **Exclude** development artifacts (tests, CI, editor configs, node_modules, src, docs) via `.gitattributes` `export-ignore`.
- OS junk files (e.g., `.DS_Store`, `Thumbs.db`) must never appear.
- If a build pipeline is introduced, ensure built assets are included and sources excluded as per policy.

---

## Final Operational Rules (for Codex)

- Always write **English** code comments and docs.
- Always wrap user strings with i18n functions using `simple-contact` text domain.
- Always sanitize input and escape output at the last responsible moment.
- Always pass **PHPCS** and respect tab/space conventions (tabs indent, spaces align).
- Keep **PRs small**; split large assets/changes across multiple PRs.
- Update **TODO.md** and **Documents.md** in lockstep with code changes.
- Maintain `.gitignore` and `.gitattributes` so that the repo stays clean and the release ZIP is minimal and compliant.

Failure to follow any of the rules in this AGENTS.md is grounds to request changes or reject the PR.