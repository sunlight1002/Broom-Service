import React, { useState } from 'react';
import './ChatFooter.css'; // Import the CSS

const ChatFooter = ({
    sendMessage
}) => {
    const [message, setMessage] = useState('');

    const handleInputChange = (e) => {
        setMessage(e.target.value);
    };

    return (
        <div className="input-bar">
            {/* Attachments Button */}
            <button className="input-icon">
                <i className="fa fa-paperclip" aria-hidden="true"></i>
            </button>

            {/* Emoji Button */}
            <button className="input-icon">
                <i className="fa fa-smile-o" aria-hidden="true"></i>
            </button>

            {/* Text Input */}
            <input
                type="text"
                name="message"
                className="input-text"
                chat-box=""
                onKeyDown={(e) => e.key === "Enter" ? sendMessage() : ""}
                placeholder="Type a message"
                value={message}
                onChange={handleInputChange}
            />

            {/* Send Button */}
            <button className="input-icon send-button"
             onClick={(e) => sendMessage()}
             >
                {message.trim() ? (
                    <i className="fa fa-paper-plane" aria-hidden="true"></i>
                ) : (
                    <i className="fa fa-microphone" aria-hidden="true"></i>
                )}
            </button>
        </div>
    );
};

export default ChatFooter;
