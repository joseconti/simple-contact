# TODO

## Pending
- Implement automated email testing strategy.
- Create end-to-end tests for form submission workflow.

## In Progress
- _None_.

## Completed
- Implement transient-backed success payload so `sc_success_message` receives sanitized submission data.
- Normalize success payload data to expose human-readable IP addresses to filters.
- Scaffold plugin structure for Simple Contact Shortcode & Block.
- Implement contact form shortcode and Gutenberg block.
- Add database schema migration and uninstall cleanup.
- Document plugin architecture and public APIs.
- Normalize indentation and bring all PHP files into compliance with WordPress Coding Standards.
- Reformat Gutenberg block script to satisfy WordPress JavaScript coding standards.
- Replace direct DROP TABLE query with `maybe_drop_table()` during uninstall for safer cleanup.
- Prepare automated QA checklist for future releases.
- Prevent redundant schema migrations by tracking stored versions and ensuring the contact table exists before updating.
