// ProfileDetails.js

import React, { useEffect, useState } from "react";
import { Link, Navigate } from "react-router-dom";
import { useParams } from "react-router-dom";
import Moment from "moment";
import { useNavigate } from "react-router-dom";
import axios from "axios";
import Notes from "./Notes";
import Files from "./Files";
import PropertyAddressTable from "../common/PropertyAddressTable";
import { Tooltip } from "react-tooltip";
import { useTranslation } from "react-i18next";

export default function ProfileDetails({
    client,
    offerStatus,
    scheduleStatus,
    latestContract,
}) {
    const navigate = useNavigate();
    const { t } = useTranslation();
    const firstname = client.firstname;
    const lastname = client.lastname;
    const email = client.email;
    const phone = client.phone
        ? client.phone.toString().split(",").join(" | ")
        : "";
    const city = client.city;
    const streetNumber = client.street_n_no;
    const floor = client.floor;
    const Apt = client.apt_no;
    const enterance = client.entrence_code;
    const lang = client.lng == "heb" ? "Hebrew" : "English";

    let geo_address = client.geo_address ? client.geo_address : "NA";
    let cords =
        client.latitude && client.longitude
            ? client.latitude + "," + client.longitude
            : "";

    const zip = client.zipcode;
    const passcode = client.passcode;
    const joined =
        Moment(client.created_at).format("DD/MM/Y") +
        " " +
        Moment(client.created_at).format("dddd");

    const param = useParams();

    let scolor = "",
        ocolor = "";
    if (scheduleStatus == "pending" || scheduleStatus == "Not Sent") {
        scolor = "#7e7e56";
    }
    if (scheduleStatus == "confirmed") {
        scolor = "green";
    }
    if (scheduleStatus == "completed") {
        scolor = "lightblue";
    }
    if (scheduleStatus == "declined") {
        scolor = "red";
    }

    if (offerStatus == "sent" || offerStatus == "Not Sent") {
        ocolor = "#7e7e56";
    }
    if (offerStatus == "accepted") {
        ocolor = "green";
    }
    if (offerStatus == "declined") {
        ocolor = "red";
    }

    let cstatus = "";
    if (client.status == "0") {
        cstatus = "Lead";
    }
    if (client.status == "1") {
        cstatus = "Potential Customer";
    }
    if (client.status == "2") {
        cstatus = "Customer";
    }

    // const handleTab = (e) => {
    //     e.preventDefault();
    //     let id = e.target.getAttribute("id");
    //     if (id == "ms") document.querySelector("#schedule-meeting").click();
    //     if (id == "os") document.querySelector("#offered-price").click();
    //     if (id == "cs") document.querySelector("#contract").click();
    // };

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const [pass, setPass] = useState(null);
    const [passVal, setPassVal] = useState(null);

    const viewPass = () => {
        if (!passVal) {
            window.alert("Please enter your password");
            return;
        }
        axios
            .post(`/api/admin/viewpass`, { pass: passVal }, { headers })
            .then((res) => {
                if (res.data.response == false) {
                    window.alert("Wrong password!");
                } else {
                    setPass(passcode);
                    document.querySelector(".closePs").click();
                }
            });
    };

    useEffect(() => {
        setTimeout(() => {
            if (
                client.latest_contract != 0 &&
                client.latest_contract != undefined
            ) {
                let bookBtn = document.querySelector("#bookBtn");
                bookBtn.style.display = "block";
            }
        }, 200);
    }, [client]);

    return (
        <>
            <div className="client-view">
                <div className="d-flex align-items-center justify-content-between"
                style={{padding: "30px 0 10px"}}
                >
                    <h1 className="navyblueColor w-100">
                        <span>#{client.id}</span>{" "}
                        {client.firstname + " " + client.lastname}
                    </h1>
                        <div className="form-group w-100 mb-0 d-flex justify-content-end flex-wrap">
                        <Link to={`/admin/schedule/view/${param.id}`}
                            style={{borderRadius: "5px", padding: "10px"}}
                            className="navyblue no-hover pl-2 pr-2 mr-2 mt-2 align-content-center"
                            >
                                <i className="fas fa-hand-point-right mr-2"></i>

                                {scheduleStatus == "Not Sent" ||
                                    scheduleStatus == "sent"
                                    ?  t("admin.schedule.scheduleMetting")
                                    : t("admin.schedule.reSchedule")}
                            </Link>
                            <Link to={`/admin/offers/create?c=${param.id}`}
                            style={{borderRadius: "5px", padding: "10px"}}
                            className="navyblue no-hover pl-2 pr-2 mr-2 mt-2 align-content-center"
                            >
                                <i className="fas fa-hand-point-right mr-2"></i>
                                {offerStatus == "Not Sent" ||
                                    offerStatus == "sent"
                                    ? t("admin.schedule.sendOffer")
                                    : ("Re-" + t("admin.schedule.sendOffer"))}
                            </Link>
                            <Link
                                to={`/admin/create-client-job/${param.id}`}
                                id="bookBtn"
                                style={{ display: "none" , borderRadius: "5px", padding: "10px"}}
                                className="navyblue no-hover pl-2 pr-2 mr-2 mt-2 align-content-center"
                                >
                                <i className="fas fa-hand-point-right mr-2"></i>{t("admin.schedule.bookClient")}
                            </Link>
                                <Link
                                    className="btn navyblue no-hover mt-2 "
                                    to={`/admin/clients/${param.id}/edit`}
                                >
                                    {t("admin.global.Edit")}
                                </Link>
                        </div>
                </div>
                <div className="row d-inline">
                    <div className="">
                        <div className="ClientHistory  pl-0 pr-0 pl-md-4 pr-md-4">
                            <ul className="nav nav-tabs" role="tablist">
                                <li className="nav-item" role="presentation">
                                    <a
                                        id="client-details"
                                        className="nav-link active navyblueColor"
                                        data-toggle="tab"
                                        href="#tab-client-details"
                                        aria-selected="true"
                                        role="tab"
                                    >
                                        {t("admin.client.client_info")}
                                    </a>
                                </li>
                                <li className="nav-item" role="presentation">
                                    <a
                                        id="note-details"
                                        className="nav-link navyblueColor"
                                        data-toggle="tab"
                                        href="#tab-note-details"
                                        aria-selected="false"
                                        role="tab"
                                    >
                                         {t("admin.client.notes")}
                                    </a>
                                </li>
                                <li className="nav-item" role="presentation">
                                    <a
                                        id="files-tab"
                                        className="nav-link navyblueColor"
                                        data-toggle="tab"
                                        href="#tab-files"
                                        aria-selected="false"
                                        role="tab"
                                    >
                                        {t("admin.client.Files")}
                                    </a>
                                </li>
                                <li className="nav-item" role="presentation">
                                    <a
                                        id="property-address-tab"
                                        className="nav-link navyblueColor"
                                        data-toggle="tab"
                                        href="#tab-property-address"
                                        aria-selected="false"
                                        role="tab"
                                    >
                                        {t("admin.client.property_address")}
                                    </a>
                                </li>
                            </ul>
                            <div className="tab-content border-0 px-0 pt-2">
                                <div
                                    id="tab-client-details"
                                    className="tab-pane active show"
                                    role="tab-panel"
                                    aria-labelledby="client-details"
                                >
                                    <h5 className="navyblueColor">{t("admin.client.client_info")}</h5>

                                    <div className="row mt-3">
                                        <div className="col-sm-4">
                                            <div className="form-group navyblueColor">
                                                <label>{t("admin.leads.AddLead.Color")}</label>
                                                <span
                                                    style={{
                                                        background: client.color
                                                            ? client.color
                                                            : "#000",
                                                        height: "24px",
                                                        width: "34px",
                                                        display: "block",
                                                        borderRadius: "4px",
                                                        border: "1px solid #e6e8eb",
                                                    }}
                                                >
                                                    &nbsp;
                                                </span>
                                            </div>
                                        </div>
                                        <div className="col-sm-4  ">
                                            <div className="form-group navyblueColor">
                                                <label>{t("admin.client.Options.Email")}</label>
                                                <p className="word-break">
                                                    {email}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="col-sm-4">
                                            <div className="form-group navyblueColor">
                                                <label>{t("admin.client.Options.Phone")}</label>
                                                <p>
                                                    <a href={`tel:+${phone}`}>
                                                        +{phone}
                                                    </a>
                                                </p>
                                            </div>
                                        </div>
                                        <div className="col-sm-4">
                                            <div className="form-group navyblueColor">
                                                <label>{t("admin.client.language")}</label>
                                                <p>{lang}</p>
                                            </div>
                                        </div>
                                        {/* <div className="col-sm-4">
                                            <div className="form-group">
                                                <label>Enterance code</label>
                                                <p>{enterance}</p>
                                            </div>
                                        </div> */}
                                        <div className="col-sm-4">
                                            <div className="form-group navyblueColor">
                                                <label>{t("admin.client.Login_details")}</label>
                                                <p className="word-break">
                                                    <span>{t("admin.client.Options.Email")}:</span> {email}
                                                </p>
                                                <p>
                                                    <span>{t("admin.client.Options.Password")}:</span>
                                                    {pass == null ? (
                                                        <span
                                                            style={{
                                                                cursor: "pointer",
                                                            }}
                                                            data-toggle="modal"
                                                            data-target="#exampleModalPass"
                                                        >
                                                            ******** &#128274;
                                                        </span>
                                                    ) : (
                                                        <span>{pass}</span>
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="col-sm-4">
                                            <div className="form-group navyblueColor">
                                                <label>{t("admin.client.Joined_on")}</label>
                                                <p>{joined}</p>
                                            </div>
                                        </div>
                                        {/* <div className="col-sm-4">
                                            <div className="form-group">
                                                <label>Google address</label>
                                                <p>
                                                    <a
                                                        href={`https://maps.google.com?q=${cords}`}
                                                        target="_blank"
                                                    >
                                                        {geo_address}
                                                    </a>
                                                </p>
                                            </div>
                                        </div> */}
                                        {/* <div className="col-sm-4">
                                            <div className="form-group">
                                                <label>Floor</label>
                                                <p>{floor}</p>
                                            </div>
                                        </div> */}
                                        {/* <div className="col-sm-4">
                                            <div className="form-group">
                                                <label>
                                                    Apt number or Apt name
                                                </label>
                                                <p>{Apt}</p>
                                            </div>
                                        </div> */}
                                        <div className="col-sm-4">
                                            <div className="form-group navyblueColor">
                                                <label>{t("admin.client.Options.Status")}</label>
                                                {client.latest_log &&
                                                    client.latest_log[0] ? (
                                                    <p
                                                        data-tooltip-id="status-tooltip"
                                                        data-tooltip-content={`Reason : ${client.latest_log[0]
                                                            .reason
                                                            } on ${Moment(
                                                                client.latest_log[0]
                                                                    .created_at
                                                            ).format("DD/MM/Y")}`}
                                                    >
                                                        {cstatus}
                                                    </p>
                                                ) : (
                                                    <p>{cstatus}</p>
                                                )}
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <div
                                    id="tab-note-details"
                                    className="tab-pane"
                                    role="tab-panel"
                                    aria-labelledby="card-details"
                                >
                                    <div className="form-group">
                                        <Notes />
                                    </div>
                                </div>
                                <div
                                    id="tab-files"
                                    className="tab-pane"
                                    role="tab-panel"
                                    aria-labelledby="rejected-tab"
                                >
                                    <Files />
                                </div>
                                <div
                                    id="tab-property-address"
                                    className="tab-pane"
                                    role="tab-panel"
                                    aria-labelledby="rejected-tab"
                                >
                                    <PropertyAddressTable clientId={param.id} />
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div
                    className="modal fade"
                    id="exampleModalPass"
                    tabIndex="-1"
                    role="dialog"
                    aria-labelledby="exampleModalPass"
                    aria-hidden="true"
                >
                    <div className="modal-dialog" role="document">
                        <div className="modal-content">
                            <div className="modal-header">
                                <button
                                    type="button"
                                    className="close"
                                    data-dismiss="modal"
                                    aria-label="Close"
                                >
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div className="modal-body">
                                <div className="row">
                                    <div className="col-sm-12">
                                        <div className="form-group">
                                            <label className="control-label">
                                            {t("admin.client.Enter_password")}
                                            </label>
                                            <input
                                                type="password"
                                                onChange={(e) =>
                                                    setPassVal(e.target.value)
                                                }
                                                className="form-control"
                                                required
                                                placeholder="Enter your password"
                                                autoComplete="new-password"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="modal-footer">
                                <button
                                    type="button"
                                    className="btn btn-secondary closePs"
                                    data-dismiss="modal"
                                >
                                    {t("admin.client.Close")}
                                </button>
                                <button
                                    type="button"
                                    onClick={viewPass}
                                    className="btn btn-primary"
                                >
                                    {t("admin.client.Submit")}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <Tooltip id="status-tooltip" />
        </>
    );
}
