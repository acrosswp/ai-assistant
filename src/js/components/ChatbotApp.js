/**
 * WordPress dependencies
 */
import { useState, useEffect, useRef } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import ChatbotWindow from './ChatbotWindow';

/**
 * Main Chatbot App Component
 */
const ChatbotApp = () => {
	const [isVisible, setIsVisible] = useState(false);
	const [messages, setMessages] = useState([]);
	const [isLoading, setIsLoading] = useState(false);
	const [error, setError] = useState(null);

	const chatbotRef = useRef(null);
	const toggleButtonRef = useRef(null);

	// Load messages on mount
	useEffect(() => {
		loadMessages();
	}, []);

	// Focus management when visibility changes
	useEffect(() => {
		if (!isVisible && toggleButtonRef.current) {
			toggleButtonRef.current.focus();
		}
	}, [isVisible]);

	// ESC key handler
	useEffect(() => {
		const handleKeyDown = (event) => {
			if (event.key === 'Escape' && isVisible) {
				setIsVisible(false);
			}
		};

		if (isVisible) {
			document.addEventListener('keydown', handleKeyDown);
			return () => document.removeEventListener('keydown', handleKeyDown);
		}
	}, [isVisible]);

	/**
	 * Load messages from API
	 */
	const loadMessages = async () => {
		try {
			const response = await apiFetch({
				path: '/ai-assistant/v1/messages',
				method: 'GET',
			});
			setMessages(response || []);
		} catch (err) {
			console.error('Failed to load messages:', err);
			setError(__('Failed to load chat history.', 'ai-assistant'));
		}
	};

	/**
	 * Send message to chatbot
	 */
	const sendMessage = async (message) => {
		if (!message.trim()) return;

		setIsLoading(true);
		setError(null);

		// Add user message immediately
		const userMessage = {
			role: 'user',
			content: message,
			timestamp: Date.now(),
			type: 'regular',
		};

		setMessages(prev => [...prev, userMessage]);

		try {
			const response = await apiFetch({
				path: '/ai-assistant/v1/messages',
				method: 'POST',
				data: { message },
			});

			// Add AI response
			setMessages(prev => [...prev, response]);
		} catch (err) {
			console.error('Failed to send message:', err);

			// Add error message
			const errorMessage = {
				role: 'assistant',
				content: err.message || __('Sorry, I encountered an error. Please try again.', 'ai-assistant'),
				timestamp: Date.now(),
				type: 'error',
			};

			setMessages(prev => [...prev, errorMessage]);
		} finally {
			setIsLoading(false);
		}
	};

	/**
	 * Reset chat history
	 */
	const resetChat = async () => {
		try {
			await apiFetch({
				path: '/ai-assistant/v1/messages',
				method: 'DELETE',
			});
			setMessages([]);
			setError(null);
		} catch (err) {
			console.error('Failed to reset chat:', err);
			setError(__('Failed to reset chat history.', 'ai-assistant'));
		}
	};

	/**
	 * Toggle chatbot visibility
	 */
	const toggleVisibility = () => {
		setIsVisible(!isVisible);
	};

	return (
		<div className="ai-assistant-chatbot">
			{/* Toggle Button */}
			<Button
				ref={toggleButtonRef}
				className="ai-assistant-chatbot__toggle"
				variant="primary"
				onClick={toggleVisibility}
				aria-expanded={isVisible}
				aria-controls="ai-assistant-chatbot-window"
			>
				{__('Need Help?', 'ai-assistant')}
			</Button>

			{/* Chatbot Window */}
			{isVisible && (
				<div
					id="ai-assistant-chatbot-window"
					className="ai-assistant-chatbot__window"
					ref={chatbotRef}
				>
					<ChatbotWindow
						messages={messages}
						isLoading={isLoading}
						error={error}
						onSendMessage={sendMessage}
						onResetChat={resetChat}
						onClose={toggleVisibility}
					/>
				</div>
			)}
		</div>
	);
};

export default ChatbotApp;
