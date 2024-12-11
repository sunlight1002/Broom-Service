import axios from "axios";
import React, { useEffect, useState } from "react";
import { Button, Modal, Table } from "react-bootstrap";
import { useTranslation } from "react-i18next";
import Sidebar from "../../Layouts/Sidebar";

function FacebookInsights() {
    const { t, i18n } = useTranslation();
    const [insightsData, setInsightsData] = useState([]);
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleGetInsights = async () => {
        try {
            const response = await axios.get(`/api/admin/facebook-campaigns`, { headers });
            console.log(response.data);
            setInsightsData(response.data); // Set data to state
        } catch (error) {
            console.error(error);
        }
    };

    useEffect(() => {
        handleGetInsights();
    }, []);

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="d-flex justify-content-between">
                        <div className="">
                            <h1 className="page-title">{t("admin.sidebar.fb_insights")}</h1>
                        </div>
                    </div>
                </div>
                <div className="dashBox pt-4 pb-4" style={{ backgroundColor: "inherit", border: "none" }}>
                    <Table responsive>
                        <thead>
                            <tr>
                                <th>{t("Campaign ID")}</th>
                                <th>{t("Campaign Name")}</th>
                                <th>{t("Start Date")}</th>
                                <th>{t("End Date")}</th>
                                <th>{t("Lead Count")}</th>
                                <th>{t("Client Count")}</th>
                                <th>{t("Reach")}</th>
                                <th>{t("Spend")}</th>
                                <th>{t("CTR")}</th>
                                <th>{t("CPC")}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {insightsData.map((campaign) => (
                                <tr key={campaign.id}>
                                    <td>{campaign.campaign_id}</td>
                                    <td>{campaign.campaign_name}</td>
                                    <td>{new Date(campaign.date_start).toLocaleDateString()}</td>
                                    <td>{new Date(campaign.date_stop).toLocaleDateString()}</td>
                                    <td>{campaign.lead_count}</td>
                                    <td>{campaign.client_count}</td>
                                    <td>{campaign.reach}</td>
                                    <td>${campaign.spend}</td>
                                    <td>{campaign.ctr}%</td>
                                    <td>${campaign.cpc}</td>
                                </tr>
                            ))}
                        </tbody>
                    </Table>
                </div>
            </div>
        </div>
    );
}

export default FacebookInsights;
