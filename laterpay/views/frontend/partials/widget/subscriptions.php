<?php
if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file
	exit;
}
?>
<?php foreach ( $laterpay_sub['subscriptions'] as $subscription ) : ?>
	<?php echo $this->renderSubscription( $subscription ); ?>
<?php endforeach;?>
