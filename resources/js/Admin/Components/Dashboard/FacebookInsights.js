import axios from "axios";
import React, { useEffect, useState } from "react";
import { Button, Modal, Table } from "react-bootstrap";
import { useTranslation } from "react-i18next";
import Sidebar from "../../Layouts/Sidebar";
import { Card, Row, Col } from "react-bootstrap";
import FilterButtons from "../../../Components/common/FilterButton";

function FacebookInsights() {
    const { t, i18n } = useTranslation();
    const [insightsData, setInsightsData] = useState({
        insights: [],
        total_count: 0,
        total_spend: 0,
        total_per_lead: 0,
        total_per_client: 0
    });
    const [filter, setFilter] = useState("all");
    const [dateRange, setDateRange] = useState({
        start_date: "",
        end_date: "",
    });

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleGetInsights = async () => {
        try {
            const response = await axios.get(`/api/admin/facebook-campaigns`, {
                headers,
                params: {
                    start_date: dateRange.start_date,
                    end_date: dateRange.end_date,
                    filter,
                },
            });
            setInsightsData({
                insights: response.data.insights,
                total_count: response.data.clientCount,
                total_spend: response.data.totalSpend,
                total_per_lead: response.data.costPerLead,
                total_per_client: response.data.costPerClient
            });
        } catch (error) {
            console.error(error);
        }
    };


    useEffect(() => {
        handleGetInsights();
    }, [dateRange, filter]);

    const reset = () => {
        setDateRange({
            start_date: "",
            end_date: "",
        });
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content" className="facebook">
                <div className=" m-0">
                    <div className="titleBox customer-title d-flex justify-content-between flex-wrap">
                        <div className="d-flex justify-content-between align-items-center mb-4">
                            <h1 className="page-title navyblueColor">{t("admin.sidebar.fb_insights")}</h1>
                        </div>
                        <div className="d-flex align-items-center flex-wrap">
                            <div
                                className="mr-3"
                                style={{ fontWeight: "bold" }}
                            >
                                {t("global.date_range")}
                            </div>

                            <div className="d-flex align-items-center flex-wrap">
                                <input
                                    className="form-control my-1"
                                    type="date"
                                    placeholder="From date"
                                    name="from filter"
                                    style={{ width: "fit-content" }}
                                    value={dateRange.start_date}
                                    onChange={(e) => {
                                        setDateRange({
                                            start_date: e.target.value,
                                            end_date: dateRange.end_date,
                                        });
                                    }}
                                />
                                <div className="mx-2">{t("global.to")}</div>
                                <input
                                    className="form-control my-1"
                                    type="date"
                                    placeholder="To date"
                                    name="to filter"
                                    style={{ width: "fit-content" }}
                                    value={dateRange.end_date}
                                    onChange={(e) => {
                                        setDateRange({
                                            start_date: dateRange.start_date,
                                            end_date: e.target.value,
                                        });
                                    }}
                                />
                            </div>
                        </div>
                    </div>
                    {/* <div className="d-flex justify-content-between align-items-center mb-4">
                        <div className="d-flex align-items-center">
                            <div
                                className="mr-3"
                                style={{ fontWeight: "bold" }}
                            >
                                {t("global.date_range")}
                            </div>

                            <input
                                className="form-control"
                                type="date"
                                placeholder="From date"
                                name="from filter"
                                style={{ width: "fit-content" }}
                                value={dateRange.start_date}
                                onChange={(e) => {
                                    setDateRange({
                                        start_date: e.target.value,
                                        end_date: dateRange.end_date,
                                    });
                                }}
                            />
                            <div className="mx-2">{t("global.to")}</div>
                            <input
                                className="form-control"
                                type="date"
                                placeholder="To date"
                                name="to filter"
                                style={{ width: "fit-content" }}
                                value={dateRange.end_date}
                                onChange={(e) => {
                                    setDateRange({
                                        start_date: dateRange.start_date,
                                        end_date: e.target.value,
                                    });
                                }}
                            />
                            <div className="mx-2">
                                <Filter_Buttons
                                    text="All"
                                    className="px-3 mr-1"
                                    value="all"
                                    selectedFilter={filter}
                                    onClick={() => {
                                        setFilter("all");
                                        reset();
                                    }}
                                />
                                <Filter_Buttons
                                    text="Total Spend"
                                    className="px-3 mr-1"
                                    value="total_spend"
                                    selectedFilter={filter}
                                    onClick={() => {
                                        setFilter("total_spend");
                                        reset();
                                    }}
                                />
                                <Filter_Buttons
                                    text="Total Cost Per Lead"
                                    className="px-3 mr-1"
                                    value="cost_per_lead"
                                    selectedFilter={filter}
                                    onClick={() => {
                                        setFilter("cost_per_lead");
                                        reset();
                                    }}
                                />
                                <Filter_Buttons
                                    text="Total Cost Per Client"
                                    className="px-3 mr-1"
                                    value="cost_per_client"
                                    selectedFilter={filter}
                                    onClick={() => {
                                        setFilter("cost_per_client");
                                        reset();
                                    }}
                                />
                            </div>
                        </div>
                    </div> */}
                    <div className="adminDash">
                        <div className="row">
                            <div className="col-lg-4 col-sm-6  col-xs-6">
                                <div>
                                    <div className="dashBox">
                                        <div className="dashIcon">
                                            <i className="fa-solid fa-users font-50"></i>
                                        </div>
                                        <div className="dashText">
                                            <h3>{insightsData.total_count}</h3>
                                            <p> Total Leads Count</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="col-lg-4 col-sm-6  col-xs-6">
                                <div>
                                    <div className="dashBox">
                                        <div className="dashIcon">
                                            <i className="fa-solid fa-dollar-sign font-50"></i>
                                        </div>
                                        <div className="dashText">
                                            <h3>${insightsData.total_spend.toFixed(2)}</h3>
                                            <p> Total Spends </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="col-lg-4 col-sm-6  col-xs-6">
                                <div>
                                    <div className="dashBox">
                                        <div className="dashIcon">
                                            <i className="fa-solid fa-suitcase font-50"></i>
                                        </div>
                                        <div className="dashText">
                                            <h3>${insightsData.total_per_lead.toFixed(2)}</h3>
                                            <p> Total Cost Per Lead</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="col-lg-4 col-sm-6  col-xs-6">
                                <div>
                                    <div className="dashBox">
                                        <div className="dashIcon">
                                            <i className="fa-solid fa-suitcase font-50"></i>
                                        </div>
                                        <div className="dashText">
                                            <h3>${insightsData.total_per_client.toFixed(2)}</h3>
                                            <p> Total Cost Per Client</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <Row className="gy-4">
                        {insightsData?.insights.map((campaign) => (
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


const Filter_Buttons = ({ text, className, selectedFilter, onClick, value }) => (
    <button
        className={`btn border rounded ${className}`}
        style={
            selectedFilter === value
                ? { background: "white" }
                : {
                    background: "#2c3f51",
                    color: "white",
                }
        }
        onClick={() => {
            onClick?.();
        }}
    >
        {text}
    </button>
);


export default FacebookInsights;
