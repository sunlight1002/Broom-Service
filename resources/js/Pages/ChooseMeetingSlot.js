import axios from "axios";
import React, { useEffect, useState, useMemo } from "react";
import { useParams, useNavigate } from "react-router-dom";
import Moment from "moment";
import { useTranslation } from "react-i18next";
import i18next from "i18next";
import { Base64 } from "js-base64";
import Swal from "sweetalert2";
import { useAlert } from "react-alert";
import moment from "moment";

import { createHalfHourlyTimeArray } from "../Utils/job.utils";
import logo from "../Assets/image/sample.svg";

export default function ChooseMeetingSlot() {
    const { t } = useTranslation();
    const [meeting, setMeeting] = useState(null);
    const [isLoading, setIsLoading] = useState(false);
    const [availableSlots, setAvailableSlots] = useState([]);
    const [bookedSlots, setBookedSlots] = useState([]);
    const [teamName, setTeamName] = useState("");
    const param = useParams();
    const navigate = useNavigate();
    const [formValues, setFormValues] = useState({
        start_time: "",
        end_time: "",
    });

    const alert = useAlert();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
    };

    const timeOptions = useMemo(() => {
        return createHalfHourlyTimeArray("08:00", "24:00");
    }, []);

    const startTimeOptions = useMemo(() => {
        const _timeOptions = timeOptions.filter((_option) => {
            if (_option == "24:00") {
                return false;
            }

            const _startTime = moment(_option, "kk:mm");
            const isSlotAvailable = availableSlots.some((slot) => {
                const _slotStartTime = moment(slot.start, "kk:mm");
                const _slotEndTime = moment(slot.end, "kk:mm");

                return (
                    _slotStartTime.isSame(_startTime) ||
                    _startTime.isBetween(_slotStartTime, _slotEndTime)
                );
            });

            if (!isSlotAvailable) {
                return false;
            }

            return !bookedSlots.some((slot) => {
                const _slotStartTime = moment(slot.start_time, "kk:mm");
                const _slotEndTime = moment(slot.end_time, "kk:mm");

                return (
                    _startTime.isBetween(_slotStartTime, _slotEndTime) ||
                    _startTime.isSame(_slotStartTime)
                );
            });
        });

        return _timeOptions;
    }, [timeOptions, availableSlots, bookedSlots]);

    const endTimeOptions = useMemo(() => {
        // end time options depends on start time, just for UX.
        if (!formValues.start_time) {
            return [];
        }

        const startIndex = timeOptions.indexOf(formValues.start_time);

        const _timeOptions = timeOptions
            .slice(startIndex + 1)
            .filter((_option) => {
                if (_option == "08:00") {
                    return false;
                }

                const _startTime = moment(formValues.start_time, "kk:mm");
                const _endTime = moment(_option, "kk:mm");

                const isSlotAvailable = availableSlots.some((slot) => {
                    const _slotStartTime = moment(slot.start, "kk:mm");
                    const _slotEndTime = moment(slot.end, "kk:mm");

                    return (
                        (_slotEndTime.isSame(_endTime) ||
                            _endTime.isBetween(_slotStartTime, _slotEndTime)) &&
                        (_startTime.isSame(_slotStartTime) ||
                            _startTime.isAfter(_slotStartTime))
                    );
                });

                if (!isSlotAvailable) {
                    return false;
                }

                return !bookedSlots.some((slot) => {
                    const _slotStartTime = moment(slot.start_time, "kk:mm");
                    const _slotEndTime = moment(slot.end_time, "kk:mm");

                    return (
                        _endTime.isSame(_slotEndTime) ||
                        _endTime.isBetween(_slotStartTime, _slotEndTime) ||
                        (_startTime.isBefore(_slotStartTime) &&
                            _endTime.isAfter(_slotEndTime))
                    );
                });
            });

        return _timeOptions;
    }, [startTimeOptions, formValues.start_time]);

    const handleSaveSlot = () => {
        if (!formValues.start_time) {
            alert.error("The start time is missing");
            return false;
        }

        if (!formValues.end_time) {
            alert.error("The end time is missing");
            return false;
        }

        setIsLoading(true);
        const _meetingID = Base64.decode(param.id);

        axios
            .post(`/api/client/meetings/${_meetingID}/slot-save`, formValues)
            .then((response) => {
                Swal.fire({
                    title: "Scheduled!",
                    text: response.data.message,
                    icon: "success",
                });
                getMeeting();
                setIsLoading(false);
            })
            .catch((e) => {
                setIsLoading(false);
                Swal.fire({
                    title: "Error!",
                    text: e.response.data.message,
                    icon: "error",
                });
            });
    };

    const getMeeting = () => {
        axios
            .post(`/api/client/meeting`, { id: Base64.decode(param.id) })
            .then((res) => {
                setMeeting(res.data.schedule);
                setTeamName(res.data.schedule.team.name);
                setAvailableSlots(res.data.available_slots);
                setBookedSlots(res.data.booked_slots);
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
        if (meeting) {
            return Moment(meeting.start_date).format("DD-MM-Y");
        }

        return "-";
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

    const handleInputChange = (e) => {
        let newFormValues = { ...formValues };

        newFormValues[e.target.name] = e.target.value;

        setFormValues({ ...newFormValues });
    };

    return (
        <div className="container">
            <div className="thankyou meet-status dashBox maxWidthControl p-4">
                <svg
                    width="190"
                    height="77"
                    xmlns="http://www.w3.org/2000/svg"
                    xmlnsXlink="http://www.w3.org/1999/xlink"
                >
                    <image xlinkHref={logo} width="190" height="77"></image>
                </svg>
                {meeting && (
                    <>
                        <h1>
                            {t("meet_stat.with")} {teamName}
                        </h1>
                        <ul className="list-unstyled">
                            <li>
                                {t("meet_stat.client")}:{" "}
                                <span>
                                    {meeting.client.firstname}{" "}
                                    {meeting.client.lastname}
                                </span>
                            </li>
                            <li>
                                {t("meet_stat.address")}:{" "}
                                <span>
                                    {meeting.property_address.address_name}
                                </span>
                            </li>
                            <li>
                                {t("meet_stat.date")}: <span>{dt}</span>
                            </li>
                            {meeting.start_time && meeting.end_time && (
                                <li>
                                    {t("meet_stat.time")}:{" "}
                                    <span>
                                        {timeFormat(meeting.start_time)}{" "}
                                        {t("meet_stat.to")}{" "}
                                        {timeFormat(meeting.end_time)}
                                    </span>
                                </li>
                            )}
                        </ul>

                        {(!meeting.start_time || !meeting.end_time) && (
                            <div className="row">
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            Start Time
                                        </label>
                                        <select
                                            name="start_time"
                                            className="form-control"
                                            value={formValues.start_time}
                                            onChange={(e) => {
                                                handleInputChange(e);
                                            }}
                                        >
                                            <option value="">--Select--</option>
                                            {startTimeOptions.map((t, i) => {
                                                return (
                                                    <option value={t} key={i}>
                                                        {t}
                                                    </option>
                                                );
                                            })}
                                        </select>
                                    </div>
                                </div>

                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            End Time
                                        </label>
                                        <select
                                            name="end_time"
                                            className="form-control"
                                            value={formValues.end_time}
                                            onChange={(e) => {
                                                handleInputChange(e);
                                            }}
                                        >
                                            <option value="">--Select--</option>
                                            {endTimeOptions.map((t, i) => {
                                                return (
                                                    <option value={t} key={i}>
                                                        {t}
                                                    </option>
                                                );
                                            })}
                                        </select>
                                    </div>
                                </div>

                                <div className="col-sm-12">
                                    <button
                                        type="button"
                                        disabled={isLoading}
                                        className="btn btn-primary"
                                        onClick={() => handleSaveSlot()}
                                    >
                                        Save Slot
                                    </button>
                                </div>
                            </div>
                        )}
                    </>
                )}
            </div>
        </div>
    );
}
