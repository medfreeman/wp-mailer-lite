<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   Mailer_Lite
 * @author    Mehdi Lahlou <mehdi.lahlou@free.fr>
 * @license   GPL-2.0+
 * @link      http://www.mappingfestival.com
 * @copyright 2014 Mehdi Lahlou
 */
?>
<div class="wrap">
	<?php AdminPageFramework::getOption( $this->class_name, $this->options_prefix . 'api_key' ); ?>
	<?php AdminPageFramework::getOption( $this->class_name, $this->options_prefix . 'load_default_styles' ); ?>
	<?php AdminPageFramework::getOption( $this->class_name, $this->options_prefix . 'loading_body_class' ); ?>
	
	<?php 
	if ( isset( $this->options['api_key'] ) &&  $this->options['api_key'] ) :
		$lists = $this->api->get_lists($this->options['api_key']);
		if ( isset( $lists['error'] ) ) :
			if ( $lists['error']['code'] == 401 ) :
			?>
				<p><?php echo __('Error : Incorrect API Key', $this->plugin_slug); ?></p>
			<?php
			else :
			?>
				<p><?php echo __('Error :', $this->plugin_slug); ?> <?php echo $lists['error']['code']; ?> / <?php echo $lists['error']['message']; ?></p>
			<?php
			endif;
		else :
		?>
		<table class="form-table">
			<tr id="fieldrow-mailerlite_lists" valign="top" class="admin-page-framework-fieldrow">
				<th><label for="mailerlite_lists"><a id="mailerlite_lists"></a><span title="Available lists.">Lists</span></label></th>
				<td>
					<fieldset id="fieldset-mailerlite_lists" class="admin-page-framework-fieldset">
						<div id="fields-mailerlite_lists" class="admin-page-framework-fields">
							<div id="field-mailerlite_lists" class="admin-page-framework-field admin-page-framework-field-select" data-type="text">
								<div class="admin-page-framework-input-label-container">
									<label for="mailerlite_lists__0">
										<select id="mailerlite_lists__0">
										<?php foreach( $lists['Results'] as $list ) : ?>
											<option value="<?php echo $list['id']; ?>"><?php echo $list['name']; ?></option>	
										<?php endforeach; ?>
										</select>
										<div class="repeatable-field-buttons"></div>
									</label>
								</div>
							</div>
						</div>
						<p class="admin-page-framework-fields-description">
							<span class="description">Available lists.</span>
						</p>
					</fieldset>
				</td>
			</tr>
		</table>
		<table class="form-table">
			<tr id="fieldrow-mailerlite_shortcode" valign="top" class="admin-page-framework-fieldrow">
				<th><label for="mailerlite_shortcode"><a id="mailerlite_shortcode"></a><span title=""></span></label></th>
				<td>
					<fieldset id="fieldset-mailerlite_shortcode" class="admin-page-framework-fieldset">
						<div id="fields-mailerlite_shortcode" class="admin-page-framework-fields">
							<div id="field-mailerlite_shortcode" class="admin-page-framework-field admin-page-framework-field-text" data-type="text">
								<div class="admin-page-framework-input-label-container">
									<label for="mailerlite_shortcode__0">
										<input id="mailerlite_shortcode__0" value="" type="text" size="30" maxlength="400" disabled="disabled">
										<div class="repeatable-field-buttons"></div>
									</label>
								</div>
							</div>
						</div>
						<p class="admin-page-framework-fields-description">
							<span class="description">Shortcode to insert into post / page.</span>
						</p>
					</fieldset>
				</td>
			</tr>
		</table>
		<?php
		endif;
	endif;
	?>
	
	<?php submit_button(); ?>

</div>
