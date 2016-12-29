<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap">
	<h2><?php _e( 'Educator Settings', 'educator' ); ?></h2>

	<?php
		settings_errors( 'general' );
		self::settings_tabs( 'taxes' );
		echo '<form action="options.php" method="post">';
		settings_fields( 'edr_taxes_settings' );
		do_settings_sections( 'edr_taxes_page' );

		$countries = Edr_Countries::get_instance()->get_countries();
		?>
		<h2><?php _e( 'Tax Classes &amp; Rates', 'educator' ); ?></h2>
		<div id="edr-tax-classes-container"></div>

		<!-- TEMPLATE: TaxClassView -->
		<script id="edr-tax-class" type="text/html">
		<td><%- description %></td>
		<td>
			<button class="button edit-tax-class"><?php _e( 'Edit', 'educator' ); ?></button>
			<button class="button edit-rates"><?php _e( 'Rates', 'educator' ); ?></button>
			<button class="button delete-tax-class"><?php _e( 'Delete', 'educator' ); ?></button>
		</td>
		</script>

		<!-- TEMPLATE: TaxClassesView -->
		<script id="edr-tax-classes" type="text/html">
		<table class="edr-table">
			<thead>
				<tr>
					<th><?php _e( 'Tax Class', 'educator' ); ?></th>
					<th><?php _e( 'Options', 'educator' ); ?></th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
		<p class="actions">
			<button class="button add-new-class"><?php _e( 'Add New', 'educator' ); ?></button>
		</p>
		</script>

		<!-- TEMPLATE: EditTaxClassView -->
		<script id="edr-edit-tax-class" type="text/html">
		<h4 class="title-add-new"><?php _e( 'Add New Tax Rate', 'educator' ); ?></h4>
		<h4 class="title-edit"><?php _e( 'Edit Tax Rate', 'educator' ); ?></h4>
		<p>
			<label><?php _e( 'Short Name', 'educator' ); ?></label>
			<input type="text" class="short-name" value="<%- name %>">
		</p>
		<p>
			<label><?php _e( 'Description', 'educator' ); ?></label>
			<input type="text" class="description" value="<%- description %>">
		</p>
		<p>
			<button class="button button-primary save-tax-class"><?php _e( 'Save', 'educator' ); ?></button>
			<button class="button cancel"><?php _e( 'Cancel', 'educator' ); ?></button>
		</p>
		</script>

		<!-- TEMPLATE: view tax rate -->
		<script id="edr-tax-rate" type="text/html">
		<td class="handle"><div class="edr-handle-y dashicons dashicons-sort"></div></td>
		<td class="country"><%- country_name %></td>
		<td class="state"><%- state_name %></td>
		<td class="name"><%- name %></td>
		<td class="rate"><%- rate %></td>
		<td class="priority"><%- priority %></td>
		<td class="options">
			<a class="edit-rate edr-action-btn" href="#"><span class="dashicons dashicons-edit"></span></a>
			<a class="delete-rate edr-action-btn" href="#"><span class="dashicons dashicons-trash"></span></a>
		</td>
		</script>

		<!-- TEMPLATE: edit tax rate -->
		<script id="edr-tax-rate-edit" type="text/html">
		<td class="handle"><div class="edr-handle-y dashicons dashicons-sort"></div></td>
		<td class="country">
			<select class="country">
				<option value=""></option>
				<?php
					foreach ( $countries as $code => $country ) {
						echo '<option value="' . esc_attr( $code ) . '">' . esc_html( $country ) . '</option>';
					}
				?>
			</select>
		</td>
		<td class="state"></td>
		<td class="name"><input type="text" value="<%- name %>"></td>
		<td class="rate"><input type="number" value="<%- rate %>"></td>
		<td class="priority"><input type="number" value="<%- priority %>"></td>
		<td class="options">
			<a class="save-rate edr-action-btn" href="#"><span class="dashicons dashicons-yes"></span></a>
			<a class="delete-rate edr-action-btn" href="#"><span class="dashicons dashicons-trash"></span></a>
		</td>
		</script>

		<!-- TEMPLATE: TaxRatesView -->
		<script id="edr-tax-rates" type="text/html">
		<table class="edr-table edr-tax-rates-table">
			<thead>
				<tr>
					<th></th>
					<th><?php _e( 'Country', 'educator' ); ?></th>
					<th><?php _e( 'State', 'educator' ); ?></th>
					<th><?php _e( 'Name', 'educator' ); ?></th>
					<th><?php _e( 'Rate (%)', 'educator' ); ?></th>
					<th><?php _e( 'Priority', 'educator' ); ?></th>
					<th><?php _e( 'Options', 'educator' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr class="loading">
					<td colspan="7">
						<?php _e( 'Loading', 'educator' ); ?>
					</td>
				</tr>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="7">
						<button class="button button-primary add-new-rate"><?php _e( 'Add New', 'educator' ); ?></button>
						<button class="button save-order" disabled="disabled"><?php _e( 'Save Order', 'educator' ); ?></button>
						<button class="button cancel"><?php _e( 'Close', 'educator' ); ?></button>
					</td>
				</tr>
			</tfoot>
		</table>
		</script>

		<script>
		var edrTaxAppNonce = <?php echo json_encode( wp_create_nonce( 'edr_tax_rates' ) ); ?>;
		var edrGetStatesNonce = <?php echo json_encode( wp_create_nonce( 'edr_get_states' ) ); ?>;
		var edrTaxClasses = <?php
			$json = '[';
			$classes = Edr_TaxManager::get_instance()->get_tax_classes();
			$i = 0;

			foreach ( $classes as $name => $description ) {
				if ( $i > 0 ) {
					$json .= ',';
				}

				$json .= '{name:' . json_encode( $name ) . ',description:' . json_encode( $description ) . '}';
				++$i;
			}

			$json .= ']';

			echo $json;
		?>;
		var edrTaxAppErrors = {
			name: <?php echo json_encode( __( 'The name is invalid.', 'educator' ) ); ?>,
			nameNotUnique: <?php echo json_encode( __( 'Tax class with this name exists.', 'educator' ) ); ?>,
			description: <?php echo json_encode( __( 'Description cannot be empty.', 'educator' ) ); ?>,
			ratesNotSaved: <?php echo json_encode( __( 'Rates could not be saved.', 'educator' ) ); ?>
		};
		</script>
		<?php
		submit_button();
		echo '</form>';
	?>
</div>
