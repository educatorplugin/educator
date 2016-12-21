<?php
if ( ! defined( 'ABSPATH' ) ) exit();

$quiz_grades_table = new Edr_Admin_QuizGradesTable();
$quiz_grades_table->prepare_items();

$filters_input = $quiz_grades_table->get_filters_input();

$form_action = add_query_arg( array(
	'status' => $filters_input['status'],
), admin_url( 'admin.php?page=edr_admin_quiz_grades' ) );
?>
<div class="wrap">
	<h2><?php _e( 'Quiz Grades', 'educator' ); ?></h2>

	<?php $quiz_grades_table->display_quiz_grade_filters(); ?>

	<form method="post" action="<?php echo esc_url( $form_action ); ?>">
		<?php $quiz_grades_table->display(); ?>
	</form>
</div>
