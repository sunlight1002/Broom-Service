import axios from "axios";
import React, { useEffect, useState } from "react";
import { useTranslation } from "react-i18next";
import { Link, useParams } from "react-router-dom";
import Contract from "./Contract";
import OfferedPrice from "./offers";
import ScheduledMeeting from "./schedules";

export default function LeadHistory({ client }) {
    const { t } = useTranslation();
    const [Contracts, setContracts] = useState([]);
    const [latestContract, setLatestContract] = useState([])
    const params = useParams();

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
    return (
        <div className="ClientHistory">
            <div className="nav-item d-flex justify-content-between client-div1" role="presentation">
                <div className="d-flex align-items-center client-div1-div1">

                    <h5
                        id="schedule-meeting"
                        className="navyblueColor"
                    >
                        {t("admin.leads.viewLead.ScheduledMeeting")}
                    </h5>

                    <div className="form-group ml-3 mb-0 d-flex" style={{ padding: "10px", borderRadius: "7px", border: "1px solid #E5EBF1", backgroundColor: "#FAFBFC" }}>
                        <span
                            id="ms"
                            className="dashStatus d-flex align-items-center mr-2"
                            style={{
                                color: "#C83939",
                                fontWeight: "500",
                                cursor: "pointer",
                                fontSize: "16px"
                            }}
                        >
                            <p className="mr-2" style={{ width: "7px", height: "7px", backgroundColor: "#C83939", borderRadius: "100px" }}></p>
                            {client.latest_meeting
                                ? client.latest_meeting.booking_status
                                : t("admin.leads.leadDetails.NotSend")}
                        </span>
                        <label className="d-block mb-0">
                            {t("admin.leads.leadDetails.MeetingStatus")}
                        </label>
                    </div>
                </div>

                <Link to={`/admin/schedule/view/${client.id}`}
                    className="text-white navyblue text-center"
                    style={{ padding: "10px", borderRadius: "5px" }}
                >
                    <i className="fas fa-hand-point-right"></i>

                    {client.meetings?.length == 0
                        ? t(
                            "admin.leads.leadDetails.ScheduleMeeting"
                        )
                        : t(
                            "admin.leads.leadDetails.ReScheduleMeeting"
                        )}
                </Link>
            </div>

            <div className="tab-content border-0">
                <div
                    id="tab-schedule"
                    className="tab-panel"
                >
                    <ScheduledMeeting />
                </div>
            </div>
            <div className="nav-item d-flex justify-content-between mt-5 client-div1" role="presentation">
                <div className="d-flex align-items-center client-div1-div1">
                    <h5
                        id="offers"
                        className="nav-link navyblueColor"
                    >
                        {t("admin.leads.viewLead.Offers")}
                    </h5>
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
                </div>

                <Link to={`/admin/offers/create?c=${client.id}`}
                    className="text-white  navyblue text-center"
                    style={{ padding: "10px", borderRadius: "5px" }}
                >
                    <i className="fas fa-hand-point-right"></i>
                    {client.offers?.length == 0
                        ? t("admin.leads.leadDetails.SendOffer")
                        : t("admin.leads.leadDetails.ReSendOffer")}
                </Link>
            </div>
            <div className="tab-content border-0">
                <div
                    id="tab-offered"
                    className=""
                >
                    <OfferedPrice />
                </div>
            </div>
            <h5
                id="offers"
                className="nav-link navyblueColor"
            >
                Contracts
            </h5>
            <div className="tab-content border-0">
                <div
                    id="tab-contract"
                    className=""
                >
                    <Contract
                        contracts={Contracts}
                        setContracts={setContracts}
                        fetchContract={getContract}
                    />
                </div>
            </div>
        </div>
    );
}
