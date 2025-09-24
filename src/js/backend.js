/**
 * WordPress dependencies
 */
import { render } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';

/**
 * Internal dependencies
 */
import ChatbotApp from './components/ChatbotApp';

/**
 * Initialize the chatbot when DOM is ready
 */
domReady(() => {
	// Check if we should render the chatbot
	if (window.aiAssistantChatbot && window.aiAssistantChatbot.enabled) {
		// Create container if it doesn't exist
		let container = document.getElementById('ai-assistant-chatbot-root');
		if (!container) {
			container = document.createElement('div');
			container.id = 'ai-assistant-chatbot-root';
			document.body.appendChild(container);
		}

		// Render the chatbot
		render(<ChatbotApp />, container);
	}
});
