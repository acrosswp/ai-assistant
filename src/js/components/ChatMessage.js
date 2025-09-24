/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * ChatMessage Component
 */
const ChatMessage = ({ message }) => {
	const { role, content, type = 'regular', timestamp } = message;

	const isUser = role === 'user';
	const isError = type === 'error';

	// Format timestamp
	const formatTime = (timestamp) => {
		if (!timestamp) return '';
		const date = new Date(timestamp);
		return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
	};

	return (
		<div className={`ai-assistant-chat-message ai-assistant-chat-message--${role} ${isError ? 'ai-assistant-chat-message--error' : ''}`}>
			<div className="ai-assistant-chat-message__avatar">
				{isUser ? (
					<span className="ai-assistant-chat-message__avatar-user">👤</span>
				) : (
					<span className="ai-assistant-chat-message__avatar-bot">🤖</span>
				)}
			</div>

			<div className="ai-assistant-chat-message__content">
				<div className="ai-assistant-chat-message__bubble">
					<div className="ai-assistant-chat-message__text">
						{content}
					</div>
					{timestamp && (
						<div className="ai-assistant-chat-message__timestamp">
							{formatTime(timestamp)}
						</div>
					)}
				</div>
			</div>
		</div>
	);
};

export default ChatMessage;
