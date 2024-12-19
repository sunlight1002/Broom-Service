import axios from "axios";
import React, { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import Moment from "moment";
import { useTranslation } from "react-i18next";
import i18next from "i18next";
import { Base64 } from "js-base64";
import logo from "../Assets/image/sample.svg";
import { Link } from "react-router-dom";
import Swal from 'sweetalert2';
import { useAlert } from "react-alert";

export default function MeetingSchedule() {
    const param = useParams();
    const { t } = useTranslation();
    const alert = useAlert();

    const [meeting, setMeeting] = useState([]);
    const [teamName, setTeamName] = useState("");
    const [address, setAddress] = useState(null);
    const [isSubmitted, setIsSubmitted] = useState(false);

    const getMeeting = () => {
        axios
            .post(`/api/client/meeting`, { id: Base64.decode(param.id) })
            .then((res) => {
                const { schedule } = res.data;
                const lng = schedule.client.lng;
                setMeeting(schedule);
                setTeamName(lng == "heb" ? schedule.team.heb_name : schedule.team?.name);
                setAddress(
                    schedule.property_address ? schedule.property_address : null
                );
                i18next.changeLanguage(lng);
                if (lng == "heb") {
                    import("../Assets/css/rtl.css");
                    document.querySelector("html").setAttribute("dir", "rtl");
                } else {
                    document.querySelector("html").removeAttribute("dir");
                    const rtlLink = document.querySelector('link[href*="rtl.css"]');
                    if (rtlLink) {
                        rtlLink.remove();
                    }
                }
            });
    };
    useEffect(() => {
        getMeeting();
        setTimeout(() => {
            document.querySelector(".meeting").style.display = "block";
        }, 1000);
    }, []);

    const dt = Moment(meeting.start_date).format("DD-MM-Y");

    const timeFormat = (intime) => {
        if (intime != undefined) {
            const [time, modifier] = intime.toString().split(" ");
            let [hours, minutes] = time.split(":");

            if (hours === "12") {
                hours = "00";
            }

            if (modifier === "PM") {
                hours = parseInt(hours, 10) + 12;
            }

            return `${hours}:${minutes}`;
        }
    };

    const handleRejectButton = () => {
        Swal.fire({
            title: t("common.delete.title"),
            text: t("common.delete.message"),
            icon: "warning",
            showCancelButton: true,
            showDenyButton: true, // Adds a second button
            confirmButtonColor: "#3085d6",
            denyButtonColor: "#d33",
            cancelButtonColor: "#aaa",
            confirmButtonText: t("swal.contact_me"), // Reschedule button
            denyButtonText: t("swal.not_interested"), // Not Interested button
            cancelButtonText: t("common.delete.cancel"), // Cancel button
        }).then(async (result) => {
            if (result.isConfirmed) {
                handleReject("contact_me");
            } else if (result.isDenied) {
                handleReject("not_interested");
            }
        });
    };

    const handleReject = async (type) => {
        if (!isSubmitted) {
            const data = {
                id: Base64.decode(param.id),
                type: type
            }
            const res = await axios.post(`/api/client/reject-meeting`, data);
            console.log(res);
            if (res.status == 200) {
                setIsSubmitted(true);
                alert.success(res?.data?.message)
            }
        }
    }

    return (
        <div className="container meeting" style={{ display: "none" }}>
            <div className="thankyou meet-status dashBox maxWidthControl p-4">
                <svg
                    width="190"
                    height="77"
                    xmlns="http://www.w3.org/2000/svg"
                    xmlnsXlink="http://www.w3.org/1999/xlink"
                >
                    <image xlinkHref={logo} width="190" height="77"></image>
                </svg>
                <h1>
                    {t("meet_stat.with")} {teamName}
                </h1>
                <ul className="list-unstyled">
                    <li>
                        {t("meet_stat.date")}: <span>{dt}</span>
                    </li>
                    <li>
                        {t("meet_stat.time")}:{" "}
                        <span>
                            {timeFormat(meeting.start_time)} {t("meet_stat.to")}{" "}
                            {timeFormat(meeting.end_time)}
                        </span>
                    </li>
                    {meeting.purpose ? (
                        <li>
                            {t("meet_stat.purpose")}{" "}
                            <span>
                                {meeting?.purpose == "Price offer"
                                    ? t("meet_stat.price_offer")
                                    : meeting?.purpose == "Quality check"
                                        ? t("meet_stat.quality_check") : meeting?.purpose}
                            </span>
                        </li>
                    ) : (
                        ""
                    )}
                    {meeting.meet_link ? (
                        <li>
                            {t("meet_stat.meet_link")}:{" "}
                            <span>
                                <Link target="_blank" to={meeting.meet_link}>
                                    {meeting.meet_link}
                                </Link>
                            </span>
                        </li>
                    ) : (
                        ""
                    )}
                    {address ? (
                        <li>
                            {t("meet_stat.address")}:{" "}
                            <span>
                                <Link
                                    target="_blank"
                                    to={`https://maps.google.com?q=${address.geo_address}`}
                                >
                                    {address.geo_address}
                                </Link>
                            </span>
                        </li>
                    ) : (
                        ""
                    )}
                </ul>
                <div className="cta">
                    <div id="content">
                        <div className="row">
                            <div className="col">
                                <Link
                                    target="_blank"
                                    className={`btn btn-success ${isSubmitted ? "disabled" : ""}`}
                                    to={isSubmitted ? "#" : `/thankyou/${param.id}/accept`}
                                    onClick={(e) => isSubmitted && e.preventDefault()}
                                >
                                    {t("front_meet.accept")}
                                </Link>
                            </div>
                            <div className="col d-flex align-items-end">
                                <button
                                    onClick={handleRejectButton}
                                    className="btn btn-danger"
                                    disabled={isSubmitted}
                                >
                                    {t("front_meet.reject")}
                                </button>
                            </div>
                            <div className="col">
                                <Link
                                    target="_blank"
                                    className={`btn btn-primary ${isSubmitted ? "disabled" : ""}`}
                                    to={isSubmitted ? "#" : `/meeting-status/${param.id}/reschedule`}
                                    onClick={(e) => isSubmitted && e.preventDefault()}
                                >
                                    {t("front_meet.reschedule")}
                                </Link>
                            </div>
                            {/* <div className="col">
                            <Link
                                target="_blank"
                                className={`btn btn-secondary ${isSubmitted ? "disabled" : ""}`}
                                to={isSubmitted ? "#" : `/meeting-files/${param.id}`}
                                onClick={(e) => isSubmitted && e.preventDefault()}
                            >
                                {t("front_meet.upload_job_description")}
                            </Link>
                        </div> */}
                        </div>

                    </div>
                </div>
            </div>
        </div>
    );
}
