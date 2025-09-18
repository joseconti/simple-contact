<?php
/**
 * PHPUnit bootstrap file for Simple Contact plugin tests.
 *
 * @package SimpleContact\Tests
 * @since 1.0.0
 */

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

require_once dirname( __DIR__, 2 ) . '/includes/class-simple-contact-notification.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-simple-contact-form.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-simple-contact-form-handler.php';

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
        define( 'MINUTE_IN_SECONDS', 60 );
}
