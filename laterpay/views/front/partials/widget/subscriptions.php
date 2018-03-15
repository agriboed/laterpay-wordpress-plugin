<?php
if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file
	exit;
}
?>
<?php foreach ( $_['subscriptions'] as $subscription ) : ?>
	<?php echo $subscription['content']; ?>
<?php endforeach;?>
