import React, { useEffect, useState } from 'react';
import Sidebar from "../../Layouts/WorkerSidebar";
import axios from 'axios';
import { useParams } from 'react-router-dom';

function Protocol() {
    const [protocolFile, setProtocolFile] = useState(null);
    const [error, setError] = useState(null);
    const params = useParams();
    const id = params.id;

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "multipart/form-data", // Important for file upload
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };


    useEffect(() => {
        axios.get(`/api/admin/protocol/${id}`,
            { headers }
            ).then(response => {
                setProtocolFile(response.data.file);
            })
            .catch(error => {
                setError('Failed to load protocol document.');
                console.error(error);
            });
    }, []);

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h5 className="page-title">Protocol Document</h5>
                        </div>
                    </div>
                </div>
                <div className="card" style={{ boxShadow: "none" }}>
                    <div className="card-body">
                        <div className="boxPanel">
                            {error ? (
                                <p>{error}</p>
                            ) : protocolFile ? (
                                <iframe 
                                    src={`/${protocolFile}`} 
                                    title="Protocol Document" 
                                    width="100%" 
                                    height="600px">
                                </iframe>
                            ) : (
                                <p>Loading...</p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default Protocol;
