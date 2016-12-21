<?php
if ( ! defined( 'ABSPATH' ) ) exit();

if ( ! current_user_can( 'manage_educator' ) ) {
	echo '<p>' . __( 'Access denied', 'educator' ) . '</p>';

	exit();
}

$members_table = new Edr_Admin_MembersTable();
$members_table->prepare_items();
?>

<div class="wrap">
	<h2>
		<?php _e( 'Educator Members', 'educator' ); ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=edr_admin_members&edr-action=edit-member' ) ); ?>" class="add-new-h2"><?php _e( 'Add New', 'educator' ); ?></a>
	</h2>

	<?php $members_table->display_member_filters(); ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=edr_admin_members' ) ); ?>">
		<?php $members_table->display(); ?>
	</form>
</div>
