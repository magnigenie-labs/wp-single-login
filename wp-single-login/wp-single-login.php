<?php
/*
Plugin name: WP Single Login
Plugin URI: http://magnigenie.com/wp-single-login/
Description: This plugin will automatically logout the already logged in user when a user with the same login details tries to login from different browser or different computer. This plugin needs zero configuration to run. Just install it if you want single login functionality on your site.
Version: 1.0
Author: Nirmal Ram
Author URI: http://magnigenie.com/about-me/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/
if( !class_exists( 'wp_single_login' ) ) {
  	class wp_single_login {
		private $session_id; 
	
		function __construct() {
			if ( ! session_id() )
			    session_start();
	
			$this->session_id = session_id();
	
			add_action( 'init', array( $this, 'wpsl_init' ) );
			add_action( 'wp_login', array( $this, 'wpsl_login' ), 10, 2 );
      add_filter('heartbeat_received', array( $this, 'wpsl_heartbeat_received' ), 10, 2);
			add_filter('heartbeat_nopriv_received', array( $this, 'wpsl_heartbeat_received' ), 10, 2);
			add_filter( 'login_message', array( $this, 'wpsl_loggedout_msg' ), 10 );
		}
	
		function wpsl_init() {
			if( ! is_user_logged_in() )
				return;
      //enqueue the Heartbeat API
      wp_enqueue_script('heartbeat');
      wp_enqueue_script('jquery');
      
      //load our Javascript in the footer
      add_action("wp_footer", array( $this, 'wpsl_scripts' ) );
			$user_sess_id = get_user_meta( get_current_user_id(), '_wpsl_hash', true );
			
			if( $user_sess_id != $this->session_id ) {
				wp_logout(); 
				wp_redirect( site_url( 'wp-login.php?wpsl=loggedout' ) );
				exit;
			}
		}
		function wpsl_login( $user_login, $user ) {
			update_user_meta( $user->ID, '_wpsl_hash', $this->session_id );
			return;
		}
		function wpsl_loggedout_msg() {
				if ( isset($_GET['wpsl']) && $_GET['wpsl'] == 'loggedout' ) {
						$msg = __( "Your session has been terminated as you are logged in from another browser." ) ;
						$message = '<p class="message">'.$msg.'</p><br />';
						return $message;
				}
		}
function wpsl_heartbeat_received($response, $data) {
  $user_sess_id = get_user_meta( get_current_user_id(), '_wpsl_hash', true );
	if( $data['user_hash'] && $data['user_hash'] != $user_sess_id ){
		$response['wpsl_response'] = 1;
    wp_logout();
	}
  else
    $response['wpsl_response'] = 0;
    
	return $response;
}
    
function wpsl_scripts() { ?>
<script>
  jQuery(document).ready(function() {
		wp.heartbeat.interval( 'fast' );
		//hook into heartbeat-send: and send the current session id to the server
		jQuery(document).on('heartbeat-send', function(e, data) {
			data['user_hash'] = '<?php echo $this->session_id; ?>';	//need some data to kick off AJAX call
		});
		
		//hook into heartbeat-tick: client looks for a 'server' var in the data array and logs it to console
		jQuery(document).on( 'heartbeat-tick', function( e, data ) {			
			if( data['wpsl_response'] ){
        alert( '<?php _e('Your session has been terminated as you are logged in from another browser.'); ?>' );
				window.location.href='<?php echo site_url( 'wp-login.php?wpsl=loggedout' ); ?> ';
			}
		});
	});		
</script>
<?php
}
	}
	new wp_single_login();
}