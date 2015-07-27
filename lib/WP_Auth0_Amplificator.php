<?php

class WP_Auth0_Amplificator {

	public static function init() {
		add_action( 'wp_ajax_auth0_amplificator', array( __CLASS__, 'share' ) );
	}

	public static function share() {
		if ( ! isset( $_POST['provider'] ) ) {
			wp_die();
		}

		$provider = $_POST['provider'];

		switch ( $provider ) {
			case 'facebook': self::_share_facebook(); break;
			case 'twitter': self::_share_twitter(); break;
		}

		wp_die();
	}

	protected static function _share_facebook() {
		$user_profiles = WP_Auth0_DBManager::get_current_user_profiles();

		foreach ($user_profiles as $user_profile) {
			foreach ($user_profile->identities as $identity) {
				if ($identity->provider == 'facebook') {

					$options = WP_Auth0_Options::Instance();
					$message = urlencode($options->get('social_facebook_message'));

					$url = "https://graph.facebook.com/{$identity->user_id}/feed?message={$message}&access_token={$identity->access_token}";
					$response = wp_remote_post( $url );

					return;
				}
			}
		}

	}

	protected static function _share_twitter() {

		require_once WPA0_PLUGIN_DIR . 'lib/twitter-api-php/TwitterAPIExchange.php';
		$user_profiles = WP_Auth0_DBManager::get_current_user_profiles();

		foreach ($user_profiles as $user_profile) {
			foreach ($user_profile->identities as $identity) {
				if ($identity->provider == 'twitter') {

					$options = WP_Auth0_Options::Instance();
					$message = urlencode($options->get('social_twitter_message'));

					$settings = array(
					    'consumer_key' => $options->get('social_twitter_key'),
					    'consumer_secret' => $options->get('social_twitter_secret'),
					    'oauth_access_token' => $identity->access_token,
					    'oauth_access_token_secret' => $identity->access_token_secret
					);

					$twitter = new TwitterAPIExchange($settings);
					$twitter->buildOauth('https://api.twitter.com/1.1/statuses/update.json', 'POST')
					    ->setPostfields(array('status' => $message))
					    ->performRequest();

					return;
				}
			}
		}

	}

}
