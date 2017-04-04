<?php
/**
 *
 * Settings page template
 *
 * @package PoweredCache
 * @subpackage PoweredCache/Settings
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="meta-box-sortables ui-sortable">

	<div class="postbox">
		<div class="inside">
			<table class="form-table">
				<tbody>
				<tr>
					<th scope="row"><label for="pc_rejected_user_agents"><?php _e( 'Rejected user agents', 'powered-cache' ); ?></label></th>
					<td>
						<textarea id="pc_rejected_user_agents" name="pc_settings[rejected_user_agents]" cols="50" rows="5"><?php echo pc_get_option( 'rejected_user_agents' ); ?></textarea><br>
						<span class="description"><?php _e( 'Never send cache pages for these user agents.', 'powered-cache' ); ?></span>
						(<a target="_blank" target="_blank" href="http://docs.poweredcache.com/article/11-advanced-caching-options#rejected-agents">?</a>)
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pc_rejected_cookies"><?php _e( 'Rejected cookies', 'powered-cache' ); ?></label></th>
					<td>
						<textarea id="pc_rejected_cookies" name="pc_settings[rejected_cookies]" cols="50" rows="5"><?php echo pc_get_option( 'rejected_cookies' ); ?></textarea><br>
						<span class="description"><?php _e( 'Never cache pages that use the specified cookies.', 'powered-cache' ); ?></span>
						(<a target="_blank" href="http://docs.poweredcache.com/article/11-advanced-caching-options#rejected-cookies">?</a>)
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pc_rejected_uri"><?php _e( 'Never cache the following pages', 'powered-cache' ); ?></label></th>
					<td>
						<textarea id="pc_rejected_uri" name="pc_settings[rejected_uri]" cols="50" rows="5"><?php echo pc_get_option( 'rejected_uri' ); ?></textarea><br>
						<span class="description"><?php _e( 'Ignore the specified pages / directories. Supports regex.', 'powered-cache' ); ?></span>
						(<a target="_blank" href="http://docs.poweredcache.com/article/11-advanced-caching-options#rejected-pages">?</a>)
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pc_accepted_query_strings"><?php _e( 'Accepted query strings', 'powered-cache' ); ?></label></th>
					<td>
						<textarea id="pc_accepted_query_strings" name="pc_settings[accepted_query_strings]" cols="50" rows="5"><?php echo pc_get_option( 'accepted_query_strings' ); ?></textarea><br>
						<span class="description"><?php _e( 'Enter GET parameters line by line.', 'powered-cache' ); ?></span>
						(<a target="_blank" href="http://docs.poweredcache.com/article/11-advanced-caching-options#allowed-query-strings">?</a>)
					</td>
				</tr>

				</tbody>
			</table>

		</div>
		<!-- .inside -->

	</div>
	<!-- .postbox -->

</div>