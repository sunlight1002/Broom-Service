import React, { useState } from 'react';
import axios from 'axios';
import Sidebar from '../../Layouts/Sidebar';
import { useParams } from 'react-router-dom';

function Claim() {
    const [claimText, setClaimText] = useState('');
    const [message, setMessage] = useState('');

    const params = useParams();
    const { workerId, hid } = useParams(); 

    const adminId = localStorage.getItem("admin-id"); 

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ${localStorage.getItem("admin-token")}`,
    };

    const handleClaimChange = (event) => {
        setClaimText(event.target.value);
    };

    const handleClaimSubmit = async (event) => {
        event.preventDefault();
        
        try {
            const response = await axios.post('/api/admin/claims', {
                worker_id: workerId,
                admin_id: adminId,
                claim: claimText,
                hearing_invitation_id: hid,
            }, { headers });

            setMessage('Claim created successfully');
        } catch (error) {
            setMessage('Error creating claim');
        }
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <h1 className="page-title">Claim for Worker</h1>
                <div className="dashBox maxWidthControl p-4 sch-meet">
                    <div className="row mt-4">
                        <div className="col-sm-6">
                            <div className="flex space-x-6">
                                <div className="form-group">
                                    <label htmlFor="claim">Claim Description</label>
                                    <textarea
                                        id="claim"
                                        className="form-control"
                                        rows="5"
                                        value={claimText}
                                        onChange={handleClaimChange}
                                        placeholder="Enter claim details here..."
                                        required
                                    />
                                </div>
                                <button 
                                    type="submit" 
                                    className="navyblue btn mt-3"
                                    onClick={handleClaimSubmit}
                                >
                                    Submit Claim
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default Claim;
