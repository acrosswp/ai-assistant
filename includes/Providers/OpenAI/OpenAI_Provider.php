<?php
/**
 * Custom OpenAI Provider that fixes empty tool_calls array issue
 *
 * @since 0.0.1
 * @package ai-assistant
 */

namespace Ai_Assistant\Providers\OpenAI;

use Ai_Assistant\Providers\OpenAI\OpenAI_Text_Generation_Model;
use WordPress\AiClient\ProviderImplementations\OpenAi\OpenAiProvider;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

defined( 'ABSPATH' ) || exit;

/**
 * Custom OpenAI Provider that uses the fixed text generation model.
 *
 * @since 0.0.1
 */
class OpenAI_Provider extends OpenAiProvider {

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.0.1
	 *
	 * @param ModelMetadata    $model_metadata The model metadata.
	 * @param ProviderMetadata $provider_metadata The provider metadata.
	 * @return OpenAI_Text_Generation_Model The model instance.
	 */
	protected function createTextGenerationModel(
		ModelMetadata $model_metadata,
		ProviderMetadata $provider_metadata
	) {
		return new OpenAI_Text_Generation_Model( $model_metadata, $provider_metadata );
	}
}
