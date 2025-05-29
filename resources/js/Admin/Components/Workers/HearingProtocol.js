import React, { useState, useEffect } from 'react';                                                 
import axios from 'axios';                                                      
import Sidebar from '../../Layouts/Sidebar';
import { useParams } from 'react-router-dom';
import { useTranslation } from "react-i18next";
import { useAlert } from "react-alert";

const HearingProtocol = () => {
    const [messages, setMessages] = useState([]);
    const [error, setError] = useState('');
    const params = useParams();
    const workerId = params.id;
    const { t } = useTranslation();
    const alert = useAlert();

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
                    if (response.data && response.data.comment) {
                        setMessages(prev => [
                            ...prev,
                            {
                                type: 'comment',
                                content: response.data.comment,
                            }
                        ]);
                    }
                })
                .catch(error => {
                    console.error('Error fetching comments:', error);
                    setError('Failed to load comments.');
                });
        }
    }, [workerId]);
    

    const handleGenerateDocument = async () => {
        if (!workerId) {
            setError('Worker ID is missing.');
            return;
        }
    
        try {
            
            const invitationResponse = await axios.get(
                `/api/admin/hearing-protocol/latest-invitation?worker_id=${workerId}`,
                { headers }
            );
    
            const hearingInvitationId = invitationResponse.data.hearing_invitation_id;    

            const response = await axios.post(
                '/api/admin/hearing-protocol',
                {
                    worker_id: workerId,
                    hearing_invitation_id: hearingInvitationId,
                    pdf_name: 'Protocol_' + workerId,
                },
                { headers }
            );
    
            const filePath = response.data.path;
    
            setMessages((prev) => [
                ...prev,
                {
                    type: 'admin',
                    content: `${filePath}`,
                },
            ]);
            alert.success("Document Generated Successfully");
            setError('');
        } catch (error) {
            setError('Failed to generate protocol document.');
            alert.error("Failed to generate document");
        }
    };

    return (
    <div id="container">
        <Sidebar />
        <div id="content pl-0">
            <h1 className="page-title">{t("admin.hearing.protocol.hearingprotocol")}</h1>
            <div className="sch-meet">
                <div className="row mt-4">
                    <div className="col-sm-6">

                        <button
                            type="button"
                            onClick={handleGenerateDocument}
                            className="navyblue text-white px-4 py-2 rounded mt-2"
                        >
                            {t("admin.hearing.protocol.generateProtocolDocument")}
                        </button>

                        <div className="form-group mt-4">
                            {messages.map((msg, index) => (
                                <div key={index} className={`flex ${msg.type === 'admin' ? 'justify-start' : msg.type === 'worker' ? 'justify-end' : 'justify-center'}`}>
                                    <div className={`p-3 rounded-lg max-w-xs ${msg.type === 'admin' ? 'bg-blue-100 text-left' : msg.type === 'worker' ? 'bg-green-100 text-right' : 'bg-gray-100 text-left'}`}>
                                        {msg.type === 'admin' ? (
                                            <span> {t("admin.hearing.protocol.documentGenerated")} {msg.content}</span>
                                        ) : msg.type === 'worker' ? (
                                            <span>{t("admin.hearing.protocol.workerResponse")} {msg.content}</span>
                                        ) : (
                                            <span>{t("admin.hearing.protocol.comment")} {msg.content}</span>
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
