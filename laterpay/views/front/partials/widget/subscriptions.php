<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div>
<?php foreach ( $_['subscriptions'] as $subscription ) : ?>
	<?php echo $subscription['content']; ?>
<?php endforeach;?>
</div>