import axios from "axios";
import React, { useEffect, useMemo, useState } from "react";
import { useParams } from "react-router-dom";
import moment from "moment";
import { useTranslation } from "react-i18next";
import i18next from "i18next";
import { Base64 } from "js-base64";
import Swal from "sweetalert2";

import logo from "../Assets/image/sample.svg";
import HearingCustomCalendar from "./Form101/inputElements/HearingCustomCalendar";

function ThankYouHearingSchedule() {
    const [status, setStatus] = useState([]);
    const [actionStatus, setActionStatus] = useState(""); 
    const [rescheduleMode, setRescheduleMode] = useState(false);
    const [newDate, setNewDate] = useState(null);
    const param = useParams();
    const { t } = useTranslation();

    const [meeting, setMeeting] = useState(null);
    const [teamName, setTeamName] = useState("");

    const getMeeting = () => {
        axios
            .post(`/api/admin/hearing`, { id: Base64.decode(param.id) })
            .then((res) => {
                const stat = res.data.schedule.booking_status;
                setMeeting(res.data.schedule);
                setTeamName(res.data.team_name);
                // const lng = res.data.schedule.client.lng;
                setStatus(stat);
                // i18next.changeLanguage(lng);
                // if (lng === "heb") {
                //     import("../Assets/css/rtl.css");
                //     document.querySelector("html").setAttribute("dir", "rtl");
                // } else {
                //     document.querySelector("html").removeAttribute("dir");
                // }
            });
    };

    useEffect(() => {
        getMeeting();
    }, []);

    // Function to handle meeting update actions
    const updateMeeting = (action) => {
        let responseUrl;
        if (action === "accept") {
            responseUrl = "/api/admin/accept-hearing";
        } else if (action === "reject") {
            responseUrl = "/api/admin/reject-hearing";
        }else if (action === "reschedule") {
            responseUrl = "/api/admin/reschedule-hearing";
        }

        const postData = {
            id: Base64.decode(param.id),
            ...(action === "reschedule" && { start_date: newDate }),
        };

        axios
            .post(responseUrl, postData)
            .then(() => {
                Swal.fire({
                    title: "Success!",
                    text: `Meeting ${action}ed successfully`,
                    icon: "success",
                });
                setActionStatus(action);
            })
            .catch((e) => {
                Swal.fire({
                    title: "Error!",
                    text: e.response?.data?.message || "Something went wrong!",
                    icon: "error",
                });
            });
    };

    const dt = useMemo(() => {
        if (!meeting) {
            return "-";
        }

        return moment(meeting.start_date).format("DD-MM-Y");
    }, [meeting]);

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
        <div className="container">
            <div className="thankyou dashBox maxWidthControl p-4">
                <svg width="190" height="77" xmlns="http://www.w3.org/2000/svg" xmlnsXlink="http://www.w3.org/1999/xlink">
                    <image xlinkHref={logo} width="190" height="77"></image>
                </svg>

                {actionStatus === "accept" ? (
                    <div>
                        <h3>{t("This meeting is already accepted.")}</h3>
                        <p>{t("Please write us an email if you have any queries.")}</p>
                        <a className="btn btn-pink" href="mailto:office@broomservice.co.il">
                            Write Email
                        </a>
                    </div>
                )  : actionStatus === "reject" ? (
                    <div>
                        <h3>{t("res_txt")}</h3>
                        <p>May I know the reason for rejection?</p>
                        <p>
                            If you have any query/suggestions or would like to reschedule the meeting, please write us on email. We will get back to you shortly.
                        </p>
                        <a className="btn btn-pink" href="mailto:office@broomservice.co.il">
                            Write Email
                        </a>
                    </div>
                ) : actionStatus === "reschedule" ? (
                    <div>
                        <h3>{t("Your meeting has been rescheduled.")}</h3>
                        <p>{t("Please write us an email if you have any questions or further requests.")}</p>
                        <a className="btn btn-pink" href="mailto:office@broomservice.co.il">
                            Write Email
                        </a>
                    </div>
                ) : rescheduleMode ? (
                    <div>
                       <h1>
                        {t("meet_stat.with")} {teamName}
                    </h1>
                    <ul className="list-unstyled">
                        {meeting.start_date && (
                            <>
                                <li>
                                    {t("meet_stat.date")}: <span>{dt}</span>
                                </li>
                                <li>
                                    {t("meet_stat.time")}:{" "}
                                    <span>
                                        {timeFormat(meeting.start_time)}{" "}
                                        {t("meet_stat.to")}{" "}
                                        {timeFormat(meeting.end_time)}
                                    </span>
                                </li>
                            </>
                        )}
                    </ul>

                    <HearingCustomCalendar meeting={meeting} />
                    </div>
                ) : (
                    <div>
                        {status === "pending" ? (
                            <div>
                                <h3>{t("res_txt")}</h3>
                                <div className="button-group">
                                    <button className="btn btn-success" onClick={() => updateMeeting("accept")}>
                                        Accept
                                    </button>
                                    <button className="btn btn-danger ml-2" onClick={() => updateMeeting("reject")}>
                                        Reject
                                    </button>
                                    <button className="btn btn-warning ml-2" onClick={() => setRescheduleMode(true)}>
                                        Reschedule
                                    </button>
                                </div>
                            </div>
                        ) : status === "confirmed" ? (
                            <div>
                                <p>{t("meet_stat.accepted_text")}</p>
                                <p className="mb-3">{t("meet_stat.write_email_text")}</p>
                                <a className="btn btn-pink" href="mailto:office@broomservice.co.il">
                                    {t("meet_reject.btn_txt")}
                                </a>
                            </div>
                        ) : (
                            <div>
                                <p className="mb-3">{t("meet_reject.txt")}</p>
                                <a className="btn btn-pink" href="mailto:office@broomservice.co.il">
                                    {t("meet_reject.btn_txt")}
                                </a>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}

export default ThankYouHearingSchedule;
