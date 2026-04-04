<div class="wrap wphe-wrap">
	<h1>WP Headless Content Extractor</h1>
	<p>Extract your WordPress content into a clean, headless-ready ZIP file containing JSON and Markdown files.</p>

	<div class="wphe-dashboard">
		<div class="wphe-settings-panel">
			<h2>Extraction Settings</h2>
			<form id="wphe-extraction-form">
				<div class="wphe-options-group">
					<h3>Standard Content</h3>
					<label><input type="checkbox" name="extract_pages" value="true" checked> Pages</label>
					<br>
					<label><input type="checkbox" name="extract_posts" value="true" checked> Posts</label>
				</div>

				<?php if ( ! empty( $post_types ) ) : ?>
					<div class="wphe-options-group">
						<h3>Custom Post Types</h3>
						<?php foreach ( $post_types as $cpt ) : ?>
							<label>
								<input type="checkbox" name="extract_cpt_<?php echo esc_attr( $cpt->name ); ?>" value="true" checked>
								<?php echo esc_html( $cpt->labels->name ); ?>
							</label>
							<br>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<div class="wphe-options-group">
					<h3>Global Data</h3>
					<label><input type="checkbox" name="extract_menus" value="true" checked> Menus</label>
					<br>
					<label><input type="checkbox" name="extract_media" value="true" checked> Media Library Data</label>
				</div>

				<button type="submit" id="wphe-start-btn" class="button button-primary button-hero">Start Extraction</button>
			</form>
		</div>

		<div class="wphe-progress-panel" id="wphe-progress-container" style="display: none;">
			<h2>Extraction Progress</h2>

			<div class="wphe-progress-bar-wrap">
				<div id="wphe-progress-bar" class="wphe-progress-bar"></div>
			</div>
			<div id="wphe-progress-text" class="wphe-progress-text">0% (0 / 0 items)</div>

			<div class="wphe-console-wrap">
				<ul id="wphe-console" class="wphe-console">
					<li>Initialization started...</li>
				</ul>
			</div>

			<div id="wphe-download-container" style="display: none; margin-top: 20px;">
				<a href="#" id="wphe-download-btn" class="button button-primary button-hero">Download ZIP File</a>
			</div>
		</div>
	</div>
</div>
