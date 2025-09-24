/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * LoadingIndicator Component
 */
const LoadingIndicator = () => {
	return (
		<div className="ai-assistant-loading">
			<div className="ai-assistant-loading__avatar">
				<span className="ai-assistant-loading__avatar-bot">🤖</span>
			</div>
			<div className="ai-assistant-loading__content">
				<div className="ai-assistant-loading__bubble">
					<div className="ai-assistant-loading__dots">
						<span></span>
						<span></span>
						<span></span>
					</div>
					<span className="ai-assistant-loading__text">
						{__('AI is thinking...', 'ai-assistant')}
					</span>
				</div>
			</div>
		</div>
	);
};

export default LoadingIndicator;
