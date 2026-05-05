/* global kimiModelData, kimiModelStrings */
( function () {
	var select   = document.getElementById( 'ai_provider_kimi_default_model' );
	var panel    = document.getElementById( 'kimi-model-details' );
	var models   = window.kimiModelData || {};
	var strings  = window.kimiModelStrings || {};

	function formatContext( length ) {
		if ( length >= 1000 ) {
			return ( length / 1000 ) + 'K';
		}
		return String( length );
	}

	function formatPrice( value ) {
		if ( value === null || value === undefined ) {
			return '—';
		}
		return '$' + value.toFixed( 2 );
	}

	function escapeHtml( text ) {
		var div = document.createElement( 'div' );
		div.textContent = text;
		return div.innerHTML;
	}

	function render( modelId ) {
		var data = models[ modelId ];
		var capabilities, html;

		if ( ! data || ! panel ) {
			if ( panel ) {
				panel.innerHTML = '';
			}
			return;
		}

		capabilities = [];
		if ( data.supports_image_in ) {
			capabilities.push( 'Vision' );
		}
		if ( data.supports_video_in ) {
			capabilities.push( 'Video' );
		}
		if ( data.supports_reasoning ) {
			capabilities.push( 'Reasoning' );
		}

		html = '<div class="kimi-model-detail-section">';
		html += '<p class="kimi-model-detail-description">' + escapeHtml( data.description ) + '</p>';
		html += '</div>';

		html += '<div class="kimi-model-detail-section">';
		html += '<span class="kimi-model-detail-label">' + escapeHtml( strings.contextLength ) + '</span> ';
		html += escapeHtml( formatContext( data.context_length ) ) + ' tokens';
		html += '</div>';

		if ( capabilities.length > 0 ) {
			html += '<div class="kimi-model-detail-section">';
			html += '<span class="kimi-model-detail-label">' + escapeHtml( strings.capabilities ) + '</span> ';
			html += escapeHtml( capabilities.join( ', ' ) );
			html += '</div>';
		}

		if ( data.pricing ) {
			html += '<div class="kimi-model-detail-section">';
			html += '<span class="kimi-model-detail-label">' + escapeHtml( strings.pricing ) + '</span> ';
			html += escapeHtml( strings.in ) + ' ' + escapeHtml( formatPrice( data.pricing.input ) );
			html += ' / ' + escapeHtml( strings.out ) + ' ' + escapeHtml( formatPrice( data.pricing.output ) );
			if ( data.pricing.cache_hit !== null && data.pricing.cache_hit !== undefined ) {
				html += ' / ' + escapeHtml( strings.cache ) + ' ' + escapeHtml( formatPrice( data.pricing.cache_hit ) );
			}
			html += '</div>';
		}

		panel.innerHTML = html;
	}

	if ( select && panel ) {
		render( select.value );
		select.addEventListener( 'change', function () {
			render( this.value );
		} );
	}
} )();
