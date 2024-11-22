import axios from "axios";
import React, { useEffect, useMemo, useState } from "react";
import { useParams } from "react-router-dom";
import moment from "moment";
import { useTranslation } from "react-i18next";
import i18next from "i18next";
import { Base64 } from "js-base64";
import Swal from "sweetalert2";

import logo from "../Assets/image/sample.svg";
import CustomCalendar from "./Form101/inputElements/CustomCalendar";

export default function MeetingStatus() {
    const { t } = useTranslation();
    const [meeting, setMeeting] = useState(null);
    const [teamName, setTeamName] = useState("");
    const param = useParams();

    const getMeeting = () => {
        axios
            .post(`/api/client/meeting`, { id: Base64.decode(param.id) })
            .then((res) => {
                setMeeting(res.data.schedule);
                setTeamName(res.data.schedule.team.name);
                const lng = res.data.schedule.client.lng;
                i18next.changeLanguage(lng);
                if (lng == "heb") {
                    import("../Assets/css/rtl.css");
                    document.querySelector("html").setAttribute("dir", "rtl");
                } else document.querySelector("html").removeAttribute("dir");
            });
    };

    useEffect(() => {
        getMeeting();
    }, []);

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
            {meeting && (
                <div className="thankyou meet-status dashBox p-0 p-md-4">
                    <svg
                        width="190"
                        className="pl-2 mb-2"
                        height="77"
                        xmlns="http://www.w3.org/2000/svg"
                        xmlnsXlink="http://www.w3.org/1999/xlink"
                    >
                        <image xlinkHref={logo} width="190" height="77"></image>
                    </svg>
                    <h1 className="pl-2">
                        {t("meet_stat.with")} {teamName}
                    </h1>
                    <ul className="list-unstyled pl-2">
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
                        <li>
                            {t("meet_stat.address")}:{" "}
                            <span>{meeting.property_address.address_name}</span>
                        </li>
                    </ul>

                    <CustomCalendar meeting={meeting} start_time={meeting.start_time} meetingDate={dt}/>
                </div>
            )}
        </div>
    );
}
