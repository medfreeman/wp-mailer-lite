<?php
/**
 * Represents the view for the public-facing component of the plugin.
 *
 * This typically includes any information, if any, that is rendered to the
 * frontend of the theme when the plugin is activated.
 *
 * @package   Mailer_Lite
 * @author    Mehdi Lahlou <mehdi.lahlou@free.fr>
 * @license   GPL-2.0+
 * @link      http://www.mappingfestival.com
 * @copyright 2014 Mehdi Lahlou
 */
?>

<!-- This file is used to markup the public facing aspect of the plugin. -->
<div class="mailerlite_container">
	<form action="" method="POST">
		<div class="mailerlite_row">
			<label for="mailerlite_email_<?php echo $this->shortcode_increment; ?>"><?php _e('Email', $this->plugin_slug ); ?></label>
			<input type="email" required id="mailerlite_email_<?php echo $this->shortcode_increment; ?>" name="mailerlite_email" class="mailerlite_email_field<?php if ( array_key_exists('mailerlite_email', $highlights ) ) echo ' invalid'; ?>" size="40"  placeholder="<?php _e( 'Enter your e-mail address', $this->plugin_slug ); ?>" value="">
		</div>
		<div class="mailerlite_row">
			<button class="mailerlite_button mailerlite_subscribe_button" type="submit" name="mailerlite_action" value="subscribe" title="<?php _e('Subscribe', $this->plugin_slug ); ?>"><?php _e('Subscribe', $this->plugin_slug ); ?></button>
		</div>
		<div class="mailerlite_row">
			<button class="mailerlite_button mailerlite_unsubscribe_button" type="submit" name="mailerlite_action" value="unsubscribe" title="<?php _e('Unsubscribe', $this->plugin_slug ); ?>"><?php _e('Unsubscribe', $this->plugin_slug ); ?></button>
		</div>
		<input name="mailerlite_list_id" type="hidden" value="<?php echo $list_id; ?>">
		<input name="mailerlite_nonce" type="hidden" value="<?php echo $list_nonce; ?>">
		<div id="mailerlite_messages_<?php echo $this->shortcode_increment; ?>" class="mailerlite_row mailerlite_messages">
			<?php echo $this->get_messages_html( $list_id ); ?>
		</div>
	</form>
</div>
