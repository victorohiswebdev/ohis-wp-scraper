jQuery(document).ready(function($) {
	const batchSize = 10;
	let itemsToProcess = [];
	let totalItems = 0;
	let processedItems = 0;

	const $form = $('#wphe-extraction-form');
	const $startBtn = $('#wphe-start-btn');
	const $progressContainer = $('#wphe-progress-container');
	const $progressBar = $('#wphe-progress-bar');
	const $progressText = $('#wphe-progress-text');
	const $console = $('#wphe-console');
	const $downloadContainer = $('#wphe-download-container');
	const $downloadBtn = $('#wphe-download-btn');

	function logToConsole(message, type = 'info') {
		const $li = $('<li>').text(message).addClass(type);
		$console.append($li);
		$console.parent().scrollTop($console.parent()[0].scrollHeight);
	}

	function updateProgress() {
		if (totalItems === 0) {
			$progressBar.css('width', '100%');
			$progressText.text('100% (0 / 0 items)');
			return;
		}
		const percentage = Math.round((processedItems / totalItems) * 100);
		$progressBar.css('width', percentage + '%');
		$progressText.text(percentage + '% (' + processedItems + ' / ' + totalItems + ' items)');
	}

	$form.on('submit', function(e) {
		e.preventDefault();

		// Reset UI
		$startBtn.prop('disabled', true);
		$progressContainer.show();
		$downloadContainer.hide();
		$console.empty();
		$progressBar.css('width', '0%');
		$progressText.text('0% (0 / 0 items)');
		processedItems = 0;

		logToConsole('Starting initialization...');

		// Gather options
		const options = {};
		$form.serializeArray().forEach(function(item) {
			options[item.name] = item.value;
		});

		// Init extraction
		$.ajax({
			url: wphe_ajax_obj.ajax_url,
			type: 'POST',
			data: {
				action: 'wphe_init_extraction',
				nonce: wphe_ajax_obj.nonce,
				options: options
			},
			success: function(response) {
				if (response.success) {
					logToConsole('Initialization successful. Found ' + response.data.total_items + ' items to process.', 'success');
					itemsToProcess = response.data.items || [];
					totalItems = response.data.total_items;

					if (totalItems > 0) {
						processNextBatch();
					} else {
						finalizeExtraction();
					}
				} else {
					logToConsole('Error during initialization: ' + (response.data.message || 'Unknown error'), 'error');
					$startBtn.prop('disabled', false);
				}
			},
			error: function() {
				logToConsole('AJAX error during initialization.', 'error');
				$startBtn.prop('disabled', false);
			}
		});
	});

	function processNextBatch() {
		if (itemsToProcess.length === 0) {
			finalizeExtraction();
			return;
		}

		// Get next chunk
		const batch = itemsToProcess.splice(0, batchSize);

		logToConsole('Processing batch of ' + batch.length + ' items...');

		$.ajax({
			url: wphe_ajax_obj.ajax_url,
			type: 'POST',
			data: {
				action: 'wphe_process_batch',
				nonce: wphe_ajax_obj.nonce,
				items: batch
			},
			success: function(response) {
				if (response.success) {
					processedItems += batch.length;
					updateProgress();
					logToConsole('Batch processed successfully.', 'success');

					// Process next batch
					processNextBatch();
				} else {
					logToConsole('Error processing batch: ' + (response.data.message || 'Unknown error'), 'error');
					$startBtn.prop('disabled', false);
				}
			},
			error: function() {
				logToConsole('AJAX error during batch processing.', 'error');
				$startBtn.prop('disabled', false);
			}
		});
	}

	function finalizeExtraction() {
		logToConsole('All items processed. Generating ZIP file...');
		updateProgress(); // Ensure it shows 100%

		$.ajax({
			url: wphe_ajax_obj.ajax_url,
			type: 'POST',
			data: {
				action: 'wphe_finalize_extraction',
				nonce: wphe_ajax_obj.nonce
			},
			success: function(response) {
				if (response.success) {
					logToConsole('Extraction complete! ZIP generated.', 'success');
					$downloadBtn.attr('href', response.data.zip_url);
					$downloadContainer.show();
				} else {
					logToConsole('Error generating ZIP: ' + (response.data.message || 'Unknown error'), 'error');
				}
				$startBtn.prop('disabled', false);
			},
			error: function() {
				logToConsole('AJAX error during ZIP generation.', 'error');
				$startBtn.prop('disabled', false);
			}
		});
	}
});
