import React, { useState, useEffect } from 'react';
import axios from 'axios';
import Sidebar from '../../Layouts/Sidebar';
import { useParams } from 'react-router-dom';

const HearingProtocol = () => {
    const [file, setFile] = useState(null);
    const [responses, setResponses] = useState('');
    const [messages, setMessages] = useState([]);
    const [error, setError] = useState('');
    const params = useParams();
    const workerId = params.id;

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ${localStorage.getItem("admin-token")}`,
    };

    // Fetch comments on component mount
    useEffect(() => {
        if (workerId) {
            axios.get(`/api/admin/hearing-protocol/comments?worker_id=${workerId}`, { headers })
                .then(response => {
                    if (response.data && response.data.comments) {
                        // Populate the messages with comments
                        setMessages(response.data.comments.map(comment => ({
                            type: 'comment',
                            content: comment,
                        })));
                    }
                })
                .catch(error => {
                    console.error('Error fetching comments:', error);
                    setError('Failed to load comments.');
                });
        }
    }, [workerId]);

    const handleAdminSubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData();

        if (file) {
            if (file.type !== 'application/pdf') {
                setError('Please upload a valid PDF file.');
                return;
            }
            formData.append('file', file);
        } else {
            setError('No file selected. Please select a file to upload.');
            return;
        }

        if (workerId) {
            formData.append('worker_id', workerId);
        } else {
            setError('Worker ID is missing.');
            return;
        }

        try {
            await axios.post('/api/admin/hearing-protocol', formData, { headers });
            setMessages((prev) => [...prev, { type: 'admin', content: file.name }]);
            setFile(null);
            setError('');
        } catch (error) {
            console.error("Error submitting document:", error);
            setError('Failed to submit document. Please try again.');
        }
    };

    const handleWorkerSubmit = (e) => {
        e.preventDefault();
        if (responses.trim()) {
            setMessages((prev) => [...prev, { type: 'worker', content: responses }]);
            setResponses('');
        } else {
            setError('Response cannot be empty.');
        }
    };

    return (
    <div id="container">
        <Sidebar />
        <div id="content">
            <h1 className="page-title">Hearing Protocol</h1>
            <div className="dashBox maxWidthControl p-4 sch-meet">
                <div className="row mt-4">
                    <div className="col-sm-6">
                        <div className="flex space-x-6">
                            <div className="w-1/2 border-r pr-4">
                                <p className="text-lg font-semibold mb-2">Upload Document</p>
                                <form onSubmit={handleAdminSubmit} className="space-y-4">
                                    <input
                                        type="file"
                                        className="border rounded w-full p-2"
                                        onChange={(e) => setFile(e.target.files[0])}
                                        required
                                    />
                                    <button type="submit" className="navyblue text-white px-4 py-2 rounded mt-2">
                                        Send Document
                                    </button>
                                </form>
                                {error && <p className="mt-4 text-red-500">{error}</p>}
                            </div>
                        </div>

                        <div className="form-group mt-4">
                            {messages.map((msg, index) => (
                                <div key={index} className={`flex ${msg.type === 'admin' ? 'justify-start' : msg.type === 'worker' ? 'justify-end' : 'justify-center'}`}>
                                    <div className={`p-3 rounded-lg max-w-xs ${msg.type === 'admin' ? 'bg-blue-100 text-left' : msg.type === 'worker' ? 'bg-green-100 text-right' : 'bg-gray-100 text-left'}`}>
                                        {msg.type === 'admin' ? (
                                            <span>Document Uploaded: {msg.content}</span>
                                        ) : msg.type === 'worker' ? (
                                            <span>Worker response: {msg.content}</span>
                                        ) : (
                                            <span>Comment: {msg.content}</span>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    );
};

export default HearingProtocol;
