import axios from "axios";
import React, { useEffect, useState } from "react";
import { Button, Modal, Table } from "react-bootstrap";
import { useTranslation } from "react-i18next";
import Sidebar from "../../Layouts/Sidebar";
import { Card, Row, Col } from "react-bootstrap";

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
            <div id="content" className="facebook">
                <div className=" m-0">
                    <div className="titleBox customer-title">
                        <div className="d-flex justify-content-between align-items-center mb-4">
                            <h1 className="page-title navyblueColor">{t("admin.sidebar.fb_insights")}</h1>
                        </div>
                    </div>

                    <Row className="gy-4">
                        {insightsData.map((campaign) => (
                            <Col md={6} lg={4} key={campaign.id} className="mb-2">
                                <Card className="shadow-sm h-100 mb-2">
                                    <Card.Body className="p-4">
                                        <Card.Title className="text-primary">{campaign.campaign_name}</Card.Title>
                                        <Card.Subtitle className="mb-3 text-muted">{t("Campaign ID")}: {campaign.campaign_id}</Card.Subtitle>
                                        <Card.Text>
                                            <strong>{t("Start Date")}:</strong> {new Date(campaign.date_start).toLocaleDateString()}<br />
                                            <strong>{t("End Date")}:</strong> {new Date(campaign.date_stop).toLocaleDateString()}<br />
                                            <strong>{t("Lead Count")}:</strong> {campaign.lead_count}<br />
                                            <strong>{t("Cost per Lead")}:</strong>
                                            {campaign.lead_count > 0 ? ` $${(campaign.spend / campaign.lead_count).toFixed(2)}` : t("admin.global.no_leads")}<br />
                                            <strong>{t("Client Count")}:</strong> {campaign.client_count}<br />
                                            <strong>{t("Cost per Client")}:</strong>
                                            {campaign.client_count > 0 ? ` $${(campaign.spend / campaign.client_count).toFixed(2)}` : t("admin.global.no_clients")}<br />
                                            <strong>{t("Reach")}:</strong> {campaign.reach}<br />
                                            <strong>{t("Spend")}:</strong> ${campaign.spend}<br />
                                            <strong>{t("CTR")}:</strong> {campaign.ctr}%<br />
                                            <strong>{t("CPC")}:</strong> {campaign.cpc}<br />
                                            <strong>{t("CPM")}:</strong> {campaign.cpm}<br />
                                            <strong>{t("CPP")}:</strong> {campaign.cpp}
                                        </Card.Text>
                                    </Card.Body>
                                </Card>
                            </Col>
                        ))}
                    </Row>
                </div>
            </div>
        </div>
    );
}

export default FacebookInsights;
