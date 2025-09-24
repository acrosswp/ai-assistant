/**
 * WordPress dependencies
 */
import { useState, useRef, useEffect } from '@wordpress/element';
import { Button, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { close, reset } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import ChatMessage from './ChatMessage';
import LoadingIndicator from './LoadingIndicator';

/**
 * ChatbotWindow Component
 */
const ChatbotWindow = ({
	messages = [],
	isLoading = false,
	error = null,
	onSendMessage,
	onResetChat,
	onClose,
}) => {
	const [inputValue, setInputValue] = useState('');
	const messagesEndRef = useRef(null);
	const inputRef = useRef(null);

	// Auto-scroll to bottom when new messages arrive
	useEffect(() => {
		messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
	}, [messages, isLoading]);

	// Focus input when window opens
	useEffect(() => {
		inputRef.current?.focus();
	}, []);

	/**
	 * Handle form submission
	 */
	const handleSubmit = (e) => {
		e.preventDefault();
		if (!inputValue.trim() || isLoading) return;

		onSendMessage(inputValue);
		setInputValue('');
	};

	/**
	 * Handle key press in textarea
	 */
	const handleKeyPress = (e) => {
		if (e.key === 'Enter' && !e.shiftKey) {
			e.preventDefault();
			handleSubmit(e);
		}
	};

	/**
	 * Handle reset with confirmation
	 */
	const handleReset = () => {
		if (messages.length > 0) {
			const confirmed = window.confirm(
				__('Are you sure you want to clear the chat history?', 'ai-assistant')
			);
			if (confirmed) {
				onResetChat();
			}
		}
	};

	return (
		<div className="ai-assistant-chatbot-window">
			{/* Header */}
			<div className="ai-assistant-chatbot-window__header">
				<div className="ai-assistant-chatbot-window__title">
					<h3>{__('AI Assistant', 'ai-assistant')}</h3>
					<span className="ai-assistant-chatbot-window__subtitle">
						{__('How can I help you today?', 'ai-assistant')}
					</span>
				</div>
				<div className="ai-assistant-chatbot-window__actions">
					<Button
						icon={reset}
						size="small"
						variant="tertiary"
						onClick={handleReset}
						disabled={messages.length === 0}
						label={__('Reset chat', 'ai-assistant')}
					/>
					<Button
						icon={close}
						size="small"
						variant="tertiary"
						onClick={onClose}
						label={__('Close chatbot', 'ai-assistant')}
					/>
				</div>
			</div>

			{/* Messages Area */}
			<div className="ai-assistant-chatbot-window__messages">
				{error && (
					<div className="ai-assistant-chatbot-window__error">
						{error}
					</div>
				)}

				{messages.length === 0 && !isLoading && (
					<div className="ai-assistant-chatbot-window__welcome">
						<p>{__('Welcome! I\'m your AI assistant. How can I help you with your WordPress site today?', 'ai-assistant')}</p>
					</div>
				)}

				{messages.map((message, index) => (
					<ChatMessage
						key={index}
						message={message}
					/>
				))}

				{isLoading && <LoadingIndicator />}

				<div ref={messagesEndRef} />
			</div>

			{/* Input Area */}
			<form
				className="ai-assistant-chatbot-window__input-form"
				onSubmit={handleSubmit}
			>
				<div className="ai-assistant-chatbot-window__input-wrapper">
					<TextareaControl
						ref={inputRef}
						value={inputValue}
						onChange={setInputValue}
						onKeyDown={handleKeyPress}
						placeholder={__('Type your message here...', 'ai-assistant')}
						rows={2}
						disabled={isLoading}
						className="ai-assistant-chatbot-window__input"
					/>
					<Button
						type="submit"
						variant="primary"
						disabled={!inputValue.trim() || isLoading}
						className="ai-assistant-chatbot-window__send-button"
					>
						{__('Send', 'ai-assistant')}
					</Button>
				</div>
			</form>
		</div>
	);
};

export default ChatbotWindow;
