import axios from "axios";
import React, { useEffect, useState } from "react";
import { useParams, useNavigate } from "react-router-dom";
import Moment from "moment";
import i18next, { use } from "i18next";
import { Base64 } from "js-base64";
import logo from "../Assets/image/sample.svg";
import { useAlert } from "react-alert";
import { Link } from "react-router-dom";
import Swal from "sweetalert2";
import useToggle from "../Hooks/useToggle";
import { useTranslation } from "react-i18next";

export const RequestToChangeScheduled = () => {
    const [text, setText] = useState("")
    const params = useParams()
    const alert = useAlert();
    const { t } = useTranslation();
    const navigate = useNavigate();
    const query = new URLSearchParams(location.search);
    const type = query.get("type");
    console.log(type);


    // const ClientLng = localStorage.getItem("client-lng")
    // console.log(ClientLng);

    useEffect(() => {
        if (type == "worker") {
            if (!localStorage.getItem("worker-token")) return navigate("/worker/login");
        } else {
            if (!localStorage.getItem("client-token")) return navigate("/client/login");
        }

    })
    let headers;

    if (type == "worker") {

        headers = {
            Accept: "application/json, text/plain, */*",
            "Content-Type": "application/json",
            Authorization: `Bearer ` + localStorage.getItem("worker-token"),
        };
    } else {

        headers = {
            Accept: "application/json, text/plain, /",
            "Content-Type": "application/json",
            Authorization: `Bearer ` + localStorage.getItem("client-token"),
        };
    }

    const handleSend = async () => {
        let url = type == "worker" ? `/api/jobs/request-to-change` : `/api/client/jobs/request-to-change`;
        try {
            const res = await axios.post(`${url}`, {
                "text": text,
                "client_id": Base64.decode(params.id),
                "type": type
            }, { headers });
            alert.success(res?.data?.message)
            setText("")
        } catch (error) {
            console.log(error);

        }
    }
    return (
        <div className="container meeting" style={{ display: "block" }}>
            <div className="thankyou meet-status dashBox maxWidthControl p-4">
                <svg
                    width="190"
                    height="77"
                    xmlns="http://www.w3.org/2000/svg"
                    xmlnsXlink="http://www.w3.org/1999/xlink"
                >
                    <image xlinkHref={logo} width="190" height="77"></image>
                </svg>
                <div className="cta">
                    <div id="content">
                        <div className="titleBox customer-title">
                            <div className="row">
                                <div className="col-sm-6">
                                    <h1 className="page-title">
                                        {t("client.other.change_or_request")}
                                    </h1>
                                </div>

                            </div>
                        </div>
                        {/* {job && (
                            <div className="comment-details mb-3">
                                <p>Details</p>
                                <p>Client: {job?.client?.firstname} {job?.client?.lastname}</p>
                                <p>Worker: {job?.worker?.firstname} {job?.client?.lastname}</p>
                                <p>Property Address: {job?.property_address?.geo_address}</p>
                            </div>
                        )} */}
                        <div className="card">
                            <div className="card-body d-flex justify-content-around align-items-center flex-wrap">
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("client.other.message")}
                                        </label>
                                        <textarea
                                            type="text"
                                            value={text}
                                            onChange={(e) =>
                                                setText(e.target.value)
                                            }
                                            className="form-control"
                                            required
                                            placeholder="Enter here..."
                                        ></textarea>
                                    </div>
                                </div>
                                <div className="ml-2">
                                    <button
                                        className="btn btn-pink addButton mt-2"
                                        style={{ textTransform: "none", width: "9rem" }}
                                        type="button"
                                        onClick={handleSend}
                                    >
                                        Send
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    )
}