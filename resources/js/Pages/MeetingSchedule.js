import axios from "axios";
import React, { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import Moment from "moment";
import { useTranslation } from "react-i18next";
import i18next from "i18next";
import { Base64 } from "js-base64";
import logo from "../Assets/image/sample.svg";
import { Link } from "react-router-dom";

export default function MeetingSchedule() {
    const param = useParams();
    const { t } = useTranslation();

    const [meeting, setMeeting] = useState([]);
    const [teamName, setTeamName] = useState("");
    const [address, setAddress] = useState([]);

    const getMeeting = () => {
        axios
            .post(`/api/client/meeting`, { id: Base64.decode(param.id) })
            .then((res) => {
                const { schedule } = res.data;
                setMeeting(schedule);
                setTeamName(schedule.team?.name);
                setAddress(
                    schedule.property_address ? schedule.property_address : []
                );
                const lng = schedule.client.lng;
                i18next.changeLanguage(lng);
                if (lng == "heb") {
                    import("../Assets/css/rtl.css");
                    document.querySelector("html").setAttribute("dir", "rtl");
                } else document.querySelector("html").removeAttribute("dir");
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
                    {meeting.team ? (
                        <li>
                            {t("meet_stat.team_name")}:{" "}
                            <span>
                                {meeting.client.lng === "en"
                                    ? meeting.team.name
                                    : meeting.team.heb_name}
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
                    {address.address_name ? (
                        <li>
                            {t("meet_stat.address")}:{" "}
                            <span>
                                <Link
                                    target="_blank"
                                    to={`https://maps.google.com?q=${
                                        address.latitude +
                                        "," +
                                        address.longitude
                                    }`}
                                >
                                    {address.address_name}
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
                                    className="btn btn-success"
                                    to={`/thankyou/${param.id}/accept`}
                                >
                                    {t("front_meet.accept")}
                                </Link>
                            </div>
                            <div className="col">
                                <Link
                                    target="_blank"
                                    className="btn btn-danger"
                                    to={`/thankyou/${param.id}/reject`}
                                >
                                    {t("front_meet.reject")}
                                </Link>
                            </div>
                            <div className="col">
                                <Link
                                    target="_blank"
                                    className="btn btn-primary"
                                    to={`/meeting-status/${param.id}/reschedule`}
                                >
                                    {t("front_meet.reschedule")}
                                </Link>
                            </div>
                            {/* <div className="col">
                                <Link
                                    target="_blank"
                                    className="btn btn-secondary"
                                    to={`/meeting-files/${param.id}`}
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
