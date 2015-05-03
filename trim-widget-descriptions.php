<?php
/*
Plugin Name: Trim Widget Descriptions
Plugin Group: WordPress Admin
Description: Limit the length of the description under each widget on the the <a href="widgets.php">widgets</a> page.
Author: Eric King
Version: 0.2.2
Author URI: http://webdeveric.com/
*/

define( 'TWD_PLUGIN', plugin_basename( __FILE__ ) );

if ( ! function_exists('ellipsis') ) {
	function ellipsis( $str, $max_len, $ellipsis="&hellip;" )
	{
		$str     = trim( html_entity_decode( $str ) );
		$str_len = strlen( $str );

		if ( $str_len <= $max_len ) {
			return htmlentities( $str );
		} else {
			$ellipsis_len = strlen( $ellipsis );
			if( $ellipsis == '&hellip;' || $ellipsis == '&#8230;' )
				$ellipsis_len = 2;
			$str = htmlentities( substr( $str, 0, $max_len - $ellipsis_len ) );
			return $str . $ellipsis;
		}
	}
}

class Trim_Widget_Descriptions
{
	const USER_META_KEY = 'twd_descriptions';

	private static $description_options = array(
		'normal' => 'No change (default)',
		'trim'	 => 'Make them shorter',
		'hide'   => 'Hide descriptions'
	);

	public static function options()
	{
		$user_id = get_current_user_id();

		if ( empty( $twd_descriptions ) )
			$twd_descriptions = 'normal';

		if ( isset( $_POST['twd_noncename'] ) ) {
			// Do a basic verification and check to see if the passed value is actually one of the values I was expecting.
			if( ! wp_verify_nonce( $_POST['twd_noncename'], TWD_PLUGIN ) || ! isset( self::$description_options[ $_POST['twd_descriptions'] ] ) ){
				self::redirect( admin_url('widgets.php?error=0') );
			}
			update_user_meta( $user_id, self::USER_META_KEY, $_POST['twd_descriptions'] );
			self::redirect( admin_url('widgets.php?message=0') );
		}
		
		$twd_descriptions = get_user_meta( $user_id, self::USER_META_KEY, true );
		
		?>

		<form method="post" action="">
			<fieldset>
				<?php wp_nonce_field( TWD_PLUGIN, 'twd_noncename' ); ?>
				<p class="description">
					<label for="twd_descriptions">Widget Descriptions:</label>
					<select name="twd_descriptions" id="twd_descriptions" class="wide">
					<?php
						foreach( self::$description_options as $value => $label ){
							printf('<option value="%s" %s>%s</option>', $value, selected($twd_descriptions, $value, false ), $label );
						}
					?>
					</select>
					<button type="submit" class="button-secondary">Save</button>
				</p>
			</fieldset>
		</form>

		<?php
	}

	public static function trim_descriptions()
	{
		global $wp_registered_widgets;

		$twd_descriptions = get_user_meta( get_current_user_id(), self::USER_META_KEY, true );

		if( $twd_descriptions == 'normal' || $twd_descriptions == '' )
			return;

		foreach ( $wp_registered_widgets as $id => $widget ) {
			if ( $twd_descriptions == 'hide' ) {
				$wp_registered_widgets[ $id ]['description'] = ' ';
				continue;
			}
			if ( ! isset( $widget['description'] ) )
				$widget['description'] = $widget['name'];
			$wp_registered_widgets[ $id ]['description'] = ellipsis( $widget['description'], 30 );
		}
	}

	public static function deactivate()
	{
		delete_metadata( 'user', 0, self::USER_META_KEY, '', true );
	}
	
	public static function redirect( $url )
	{
		if( ! headers_sent() ) {
			wp_redirect( $url );
		} else {
			printf('<script>window.location.replace("%1$s");</script><noscript><meta http-equiv="refresh" content="0;url=%1$s"></noscript>', $url );
		}
		exit;
	}

}

register_deactivation_hook( __FILE__, array('Trim_Widget_Descriptions', 'deactivate' ) );

add_action('widgets_admin_page', array('Trim_Widget_Descriptions', 'options') , 10, 0 );
add_action('widgets_admin_page', array('Trim_Widget_Descriptions', 'trim_descriptions'), 11, 0 );
