import React, {useState, useEffect} from 'react'
import { useParams } from 'react-router-dom';
import axios from 'axios';
import Sidebar from '../../Layouts/Sidebar';
import { t } from 'i18next';

function FinalDecision() {
    const [messages, setMessages] = useState([]);
    const [error, setError] = useState('');
    const params = useParams();
    const workerId = params.id;

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };
  
    const handleGenerateDocument = async () => {
        if (!workerId) {
            setError('Worker ID is missing.');
            return;
        }
    
        try {
            
            const invitationResponse = await axios.get(
                `/api/admin/hearing-protocol/latest_invitation?worker_id=${workerId}`,
                { headers }
            );
    
            const hearingInvitationId = invitationResponse.data.hearing_invitation_id;    

            const response = await axios.post(
                '/api/admin/final-decision',
                {
                    worker_id: workerId,
                    hearing_invitation_id: hearingInvitationId,
                    manager_name: 'Alex',
                    manager_role: 'HR Manager',
                    team_id : localStorage.getItem("admin-id"),
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
            setError('');
        } catch (error) {
            console.error("Error generating document:", error);
            setError('Failed to generate protocol document.');
        }
    };  

    return (
        <div id="container">
            <Sidebar />
            <div id="content pl-0">
                <h1 className="page-title">{t("admin.hearing.finalDecision")}</h1>
                <div className="sch-meet">
                    <div className="row mt-4">
                        <div className="col-sm-6">
                        <button
                            type="button"
                            onClick={handleGenerateDocument}
                            className="navyblue text-white px-4 py-2 rounded mt-2"
                        >
                            {t("admin.hearing.generateAndSendFinalDecisionDocument")}
                        </button>
    
                            <div className="form-group mt-4">
                                {messages.map((msg, index) => (
                                    <div key={index} className={`flex ${msg.type === 'admin' ? 'justify-start' : msg.type === 'worker' ? 'justify-end' : 'justify-center'}`}>
                                        <div className={`p-3 rounded-lg max-w-xs ${msg.type === 'admin' ? 'bg-blue-100 text-left' : msg.type === 'worker' ? 'bg-green-100 text-right' : 'bg-gray-100 text-left'}`}>
                                            {msg.type === 'admin' ? (
                                                <span>{t("admin.hearing.documentGenerated")} {msg.content}</span>
                                            ) : ( ''
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
  
}

export default FinalDecision
