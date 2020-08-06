<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Handles fetching of the site's plan from WordPress.com and caching the value locally.
 *
 * Not to be confused with the `Jetpack_Plans` class (in `_inc/lib/plans.php`), which
 * fetches general information about all available plans from WordPress.com, side-effect free.
 *
 * @package Jetpack
 */

use Automattic\Jetpack\Connection\Client;

/**
 * Provides methods methods for fetching the plan from WordPress.com.
 */
class Jetpack_Plan {
	/**
	 * A cache variable to hold the active plan for the current request.
	 *
	 * @var array
	 */
	private static $active_plan_cache;

	const PLAN_OPTION = 'jetpack_active_plan';

	const PLAN_DATA = array(
		'free'     => array(
			'plans'    => array(
				'jetpack_free',
			),
			'supports' => array(
				'opentable',
				'calendly',
				'send-a-message',
				'social-previews',
			),
		),
		'personal' => array(
			'plans'    => array(
				'jetpack_personal',
				'jetpack_personal_monthly',
				'personal-bundle',
				'personal-bundle-monthly',
				'personal-bundle-2y',
			),
			'supports' => array(
				'akismet',
				'recurring-payments',
			),
		),
		'premium'  => array(
			'plans'    => array(
				'jetpack_premium',
				'jetpack_premium_monthly',
				'value_bundle',
				'value_bundle-monthly',
				'value_bundle-2y',
			),
			'supports' => array(
				'donations',
				'simple-payments',
				'vaultpress',
				'videopress',
			),
		),
		'business' => array(
			'plans'    => array(
				'jetpack_business',
				'jetpack_business_monthly',
				'business-bundle',
				'business-bundle-monthly',
				'business-bundle-2y',
				'ecommerce-bundle',
				'ecommerce-bundle-monthly',
				'ecommerce-bundle-2y',
				'vip',
			),
			'supports' => array(),
		),
	);

	/**
	 * Given a response to the `/sites/%d` endpoint, will parse the response and attempt to set the
	 * plan from the response.
	 *
	 * @param array $response The response from `/sites/%d`.
	 * @return bool Was the plan successfully updated?
	 */
	public static function update_from_sites_response( $response ) {
		// Bail if there was an error or malformed response.
		if ( is_wp_error( $response ) || ! is_array( $response ) || ! isset( $response['body'] ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		if ( is_wp_error( $body ) ) {
			return false;
		}

		// Decode the results.
		$results = json_decode( $body, true );

		// Bail if there were no results or plan details returned.
		if ( ! is_array( $results ) || ! isset( $results['plan'] ) ) {
			return false;
		}

		// Store the new plan in an option and return true if updated.
		$result = update_option( self::PLAN_OPTION, $results['plan'], true );
		if ( ! $result ) {
			// If we got to this point, then we know we need to update. So, assume there is an issue
			// with caching. To fix that issue, we can delete the current option and then update.
			delete_option( self::PLAN_OPTION );
			$result = update_option( self::PLAN_OPTION, $results['plan'], true );
		}

		if ( $result ) {
			// Reset the cache since we've just updated the plan.
			self::$active_plan_cache = null;
		}

		return $result;
	}

	/**
	 * Make an API call to WordPress.com for plan status
	 *
	 * @uses Jetpack_Options::get_option()
	 * @uses Client::wpcom_json_api_request_as_blog()
	 * @uses update_option()
	 *
	 * @access public
	 * @static
	 *
	 * @return bool True if plan is updated, false if no update
	 */
	public static function refresh_from_wpcom() {
		// Make the API request.
		$request  = sprintf( '/sites/%d', Jetpack_Options::get_option( 'id' ) );
		$response = Client::wpcom_json_api_request_as_blog( $request, '1.1' );

		return self::update_from_sites_response( $response );
	}

	/**
	 * Get the plan that this Jetpack site is currently using.
	 *
	 * @uses get_option()
	 *
	 * @access public
	 * @static
	 *
	 * @return array Active Jetpack plan details
	 */
	public static function get() {
		// this can be expensive to compute so we cache for the duration of a request.
		if ( is_array( self::$active_plan_cache ) && ! empty( self::$active_plan_cache ) ) {
			return self::$active_plan_cache;
		}

		$plan = get_option( self::PLAN_OPTION, array() );

		// Set the default options.
		$plan = wp_parse_args(
			$plan,
			array(
				'product_slug' => 'jetpack_free',
				'class'        => 'free',
				'features'     => array(
					'active' => array(),
				),
			)
		);

		list( $plan['class'], $supports ) = self::get_class_and_features( $plan['product_slug'] );

		// get available features.
		foreach ( Jetpack::get_available_modules() as $module_slug ) {
			$module = Jetpack::get_module( $module_slug );
			if ( ! isset( $module ) || ! is_array( $module ) ) {
				continue;
			}
			if ( in_array( 'free', $module['plan_classes'], true ) || in_array( $plan['class'], $module['plan_classes'], true ) ) {
				$supports[] = $module_slug;
			}
		}

		$plan['supports'] = $supports;

		self::$active_plan_cache = $plan;

		return $plan;
	}

	/**
	 * Get the class of plan and a list of features it supports
	 *
	 * @param string $plan_slug The plan that we're interested in.
	 * @return array Two item array, the plan class and the an array of features.
	 */
	private static function get_class_and_features( $plan_slug ) {
		$features = array();
		foreach ( self::PLAN_DATA as $class => $details ) {
			$features = array_merge( $features, $details['supports'] );
			if ( in_array( $plan_slug, $details['plans'], true ) ) {
				return array( $class, $features );
			}
		}
		return array( 'free', self::PLAN_DATA['free']['supports'] );
	}

	/**
	 * Gets the minimum plan slug that supports the given feature
	 *
	 * @param string $feature The name of the feature.
	 * @return string|bool The slug for the minimum plan that supports.
	 *  the feature or false if not found
	 */
	public static function get_minimum_plan_for_feature( $feature ) {
		foreach ( self::PLAN_DATA as $class => $details ) {
			if ( in_array( $feature, $details['supports'], true ) ) {
				return $details['plans'][0];
			}
		}
		return false;
	}

	/**
	 * Determine whether the active plan supports a particular feature
	 *
	 * @uses Jetpack_Plan::get()
	 *
	 * @access public
	 * @static
	 *
	 * @param string $feature The module or feature to check.
	 *
	 * @return bool True if plan supports feature, false if not
	 */
	public static function supports( $feature ) {
		// Search product bypasses plan feature check.
		if ( 'search' === $feature && (bool) get_option( 'has_jetpack_search_product' ) ) {
			return true;
		}

		$plan = self::get();

		// Manually mapping WordPress.com features to Jetpack module slugs.
		foreach ( $plan['features']['active'] as $wpcom_feature ) {
			switch ( $wpcom_feature ) {
				case 'wordads-jetpack':
					// WordAds are supported for this site.
					if ( 'wordads' === $feature ) {
						return true;
					}
					break;
			}
		}

		if (
			in_array( $feature, $plan['supports'], true )
			|| in_array( $feature, $plan['features']['active'], true )
		) {
			return true;
		}

		return false;
	}
}
