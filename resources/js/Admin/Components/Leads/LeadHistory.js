import axios from "axios";
import React, { useEffect, useState } from "react";
import { useTranslation } from "react-i18next";
import { useLocation, useParams } from "react-router-dom";
import Contract from "./Contract";
import OfferedPrice from "./offers";
import ScheduledMeeting from "./schedules";

export default function LeadHistory({ client }) {
    const { t } = useTranslation();
    const [Contracts, setContracts] = useState([]);
    const [latestContract, setLatestContract] = useState([])
    const params = useParams();
    const [activeTab, setActiveTab] = useState("#tab-schedule");


    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getContract = () => {
        axios
            .post(`/api/admin/client-contracts`, { id: params.id }, { headers })
            .then((res) => {
                setContracts(res.data.contracts.data);
                setLatestContract(res.data.latest);
            });
    };
    useEffect(() => {
        getContract();
    }, []);

    const handleTabClick = (tab) => {
        setActiveTab(tab); 
    };

    return (
        <div className="ClientHistory">
            <ul className="nav nav-tabs" role="tablist">
                <li className="nav-item" role="presentation">
                    <a
                        id="schedule-meeting"
                        className={
                            `nav-link d-flex align-items-center ` +
                            (activeTab === "#tab-schedule" ? "active" : "")
                        }
                        href="#tab-schedule"
                        role="tab"
                        onClick={(e) => {
                            e.preventDefault(); // Prevent default link behavior
                            handleTabClick("#tab-schedule");
                        }}
                    >
                        {t("admin.schedule.scheduleMetting")}
                        <div className="form-group ml-3 mb-0 d-flex" style={{ padding: "10px", borderRadius: "7px", border: "1px solid #E5EBF1", backgroundColor: "#FAFBFC" }}>
                            <span
                                id="os"
                                className="dashStatus d-flex align-items-center mr-2"
                                style={{
                                    color: "#C83939",
                                    fontWeight: "500",
                                    cursor: "pointer",
                                    fontSize: "16px"
                                }}
                            >
                                <p className="mr-2" style={{ width: "7px", height: "7px", backgroundColor: "#C83939", borderRadius: "100px" }}></p>
                                {client.latest_offer
                                    ? client.latest_offer.status
                                    : t("admin.leads.leadDetails.NotSend")}
                            </span>
                            <label className="d-block mb-0">
                                {" "}
                                {t("admin.leads.leadDetails.PriceOffer")}
                            </label>
                        </div>
                    </a>
                </li>
                <li className="nav-item" role="presentation">
                    <a
                        id="offered-price"
                        className={
                            `nav-link d-flex align-items-center ` +
                            (activeTab === "#tab-offered" ? "active" : "")
                        }
                        href="#tab-offered"
                        role="tab"
                        onClick={(e) => {
                            e.preventDefault(); // Prevent default link behavior
                            handleTabClick("#tab-offered");
                        }}
                    >
                        {t("admin.schedule.offeredPrice")}
                        <div className="form-group ml-3 mb-0 d-flex" style={{ padding: "10px", borderRadius: "7px", border: "1px solid #E5EBF1", backgroundColor: "#FAFBFC" }}>
                            <span
                                id="os"
                                className="dashStatus d-flex align-items-center mr-2"
                                style={{
                                    color: "#C83939",
                                    fontWeight: "500",
                                    cursor: "pointer",
                                    fontSize: "16px"

                                }}
                            >
                                <p className="mr-2" style={{ width: "7px", height: "7px", backgroundColor: "#C83939", borderRadius: "100px" }}></p>

                                {client.latest_offer
                                    ? client.latest_offer.status
                                    : t("admin.leads.leadDetails.NotSend")}
                            </span>
                            <label className="d-block mb-0">
                                {" "}
                                {t("admin.leads.leadDetails.PriceOffer")}
                            </label>
                        </div>
                    </a>
                </li>
                <li className="nav-item" role="presentation">
                    <a
                        id="contract"
                        className={
                            `nav-link d-flex align-items-center ` +
                            (activeTab === "#tab-contract" ? "active" : "")
                        }
                        style={{ padding: "21px"}}
                        href="#tab-contract"
                        role="tab"
                        onClick={(e) => {
                            e.preventDefault(); // Prevent default link behavior
                            handleTabClick("#tab-contract");
                        }}
                    >
                        {t("admin.schedule.contract")}
                    </a>
                </li>
            </ul>

            <div className="tab-content border-0">
                {activeTab === "#tab-schedule" && (
                    <div id="tab-schedule" className="tab-panel">
                        <ScheduledMeeting />
                    </div>
                )}
                {activeTab === "#tab-offered" && (
                    <div id="tab-offered" className="tab-panel">
                        <OfferedPrice />
                    </div>
                )}
                {activeTab === "#tab-contract" && (
                    <div id="tab-contract" className="tab-panel">
                        <Contract
                            contracts={Contracts}
                            setContracts={setContracts}
                            fetchContract={getContract}
                        />
                    </div>
                )}
            </div>
        </div>
    );
}
