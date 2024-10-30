<?php defined('ABSPATH') or die; ?>
<div class="contentify-ai-ob-form <?php esc_attr_e( isset( $_GET['skip'] ) ? 'contentify-ai-skip' : '' ); ?>">
	<?php wp_nonce_field( CONTENTIFY_AI_NONCE_BN, CONTENTIFY_AI_NONCE_NAME ); ?>
	<div class="caiobf-step-rail">
		<div class="caiobf-step caiobf-step-1 caiobf-60">
			<label><?php esc_html_e( 'Add Your API Key Here', 'contentify-ai' ); ?></label>
			<input type="text" class="caiobf-api-key" name="caiobf_api_key" value="<?php esc_attr_e( get_option('contentify_api_key') ); ?>" placeholder="<?php esc_attr_e( 'API Key', 'contentify-ai' ); ?>">
			<a href="#" class="caiobf-btn"><?php esc_html_e( 'Next', 'contentify-ai' ); ?></a>
		</div>
		<?php if ( $this->is_yoast_installed() ) : ?>
			<div class="caiobf-step caiobf-step-2 caiobf-60">
				<label><?php esc_html_e( 'Optimize Your Existing Pages', 'contentify-ai' ); ?></label>
				<div class="caiobf-btn-flex">
					<a href="<?php esc_attr_e( admin_url() ); ?>" class="caiobf-btn caiobf-btn-black"><?php esc_html_e( 'No', 'contentify-ai' ); ?></a>
					<a href="#" class="caiobf-btn caiobf-btn-proceed"><?php esc_html_e( 'Yes', 'contentify-ai' ); ?></a>
				</div>
			</div>
			<div class="caiobf-step caiobf-step-3">
				<label><?php esc_html_e( 'Unoptimized Pages', 'contentify-ai' ); ?></label>
				<div class="caiobf-ajax-loader">
					<?php esc_html_e( 'Loading...', 'contentify-ai' ); ?>
				</div>
				<div class="caiobf-uo-pages">
					-
				</div>
				<div class="caiobf-btn-flex">
					<?php if ( ! isset( $_GET['skip'] ) ) : ?>
						<a href="#" class="caiobf-btn caiobf-btn-black"><?php esc_html_e( 'Back', 'contentify-ai' ); ?></a>
					<?php endif; ?>
					<a href="#" class="caiobf-btn caiobf-btn-optimize"><?php esc_html_e( 'Optimize', 'contentify-ai' ); ?></a>
				</div>
			</div>
			<div class="caiobf-step caiobf-step-4">
				<label><?php esc_html_e( 'Optimizing', 'contentify-ai' ); ?> (<span class="caiobf-optimized-percent">0</span>%)</label>
				<div class="caiobf-uo-optimized">
					-
				</div>
			</div>
		<?php endif; ?>
	</div>
</div>