import React, { useEffect, useState } from "react";
import Sidebar from "../../Layouts/Sidebar";
import LeadDetails from "../../Components/Leads/LeadDetails";
import LeadHistory from "../../Components/Leads/LeadHistory";
import axios from "axios";
import { useParams } from "react-router-dom";
import { Link } from "react-router-dom";
import { useTranslation } from "react-i18next";

export default function ViewLead() {
    const [lead, setLead] = useState(null);
    const [campaignName, setCampaignName] = useState("");

    const param = useParams();
    const { t } = useTranslation();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getLead = () => {
        axios
            .get(`/api/admin/leads/${param.id}/edit`, { headers })
            .then((res) => {
                setLead(res.data.lead);
                getCampaignName(res.data.lead.campaign_id);                
            });
    };

    const getCampaignName = async (campaignId) => {
        if (!campaignId) return;
        try {
            const response = await axios.get(`/api/admin/facebook-campaigns/${campaignId}`, { headers });
            setCampaignName(response.data.campaign_name);
        } catch (error) {
            console.error("Error fetching campaign name:", error);
        }
    };
    
    useEffect(() => {
        getLead();
    }, []);

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                {/* <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">
                                {t("admin.leads.viewLead.viewLead")}
                            </h1>
                        </div>
                       
                    </div>
                </div> */}
                {lead && (
                    <div className="view-applicant">
                        <LeadDetails lead={lead} campaignName={campaignName}/>
                        <div className="card mt-3" style={{boxShadow: "none"}}>
                            <div className="card-body">
                                <LeadHistory client={lead} />
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
