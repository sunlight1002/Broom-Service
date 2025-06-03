import React, {useState, useEffect} from 'react'
import { useParams } from 'react-router-dom';
import axios from 'axios';
import Sidebar from '../../Layouts/Sidebar';
import { t } from 'i18next';
import { useAlert } from 'react-alert';

function FinalDecision() {
    const [messages, setMessages] = useState([]);
    const [decisionFile, setDecisionFile] = useState(null);
    const [error, setError] = useState('');
    const params = useParams();
    const workerId = params.id;
    const alert = useAlert();

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
            alert.success("Document Generated Successfully");
            setError('');
        } catch (error) {
            setError('Failed to generate protocol document.');
            alert.error("Failed to generate document");
        }
    };  

    useEffect(() => {
        if (!workerId) {
            alert.error('Worker ID not found in local storage.');
            return;
        }

        axios.get(`/api/admin/decision-document?worker_id=${workerId}`, { headers })
            .then(response => {
                setDecisionFile(response.data.file);
            })
            .catch(error => {
            });
    }, [workerId]);

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
                        
                        </div>
                    </div>
                </div>
                <div className="d-flex align-items-center mb-3 mt-2">
                    {decisionFile && (
                        <a 
                            href={decisionFile} 
                            target="_blank" 
                            rel="noopener noreferrer" 
                            className="btn px-3 mr-3"
                            style={{ textDecoration: "none", background: "#2F4054", color: "white"}}
                        >
                            {t("worker.hearing.protocol.viewDecisionDocument")}
                        </a>
                    )}
                </div>
            </div>
        </div>
    );
}

export default FinalDecision
