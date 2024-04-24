import React, { useState, useEffect, useCallback, useMemo } from "react";
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";
import FullCalendar from "@fullcalendar/react";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import interactionPlugin from "@fullcalendar/interaction";
import { useParams, useNavigate } from "react-router-dom";
import axios from "axios";
import Moment from "moment";
import Swal from "sweetalert2";
import { useAlert } from "react-alert";
import { useTranslation } from "react-i18next";
import moment from "moment";

import Sidebar from "../../Layouts/Sidebar";
import { createHalfHourlyTimeArray } from "../../../Utils/job.utils";

export default function ViewSchedule() {
    const [startDate, setStartDate] = useState(new Date());
    const [client, setClient] = useState([]);
    const [totalTeam, setTotalTeam] = useState([]);
    const [team, setTeam] = useState("");
    const [bstatus, setBstatus] = useState("");
    const [startTime, setStartTime] = useState("");
    const [endTime, setEndTime] = useState("");
    const [events, setEvents] = useState([]);
    const [lang, setLang] = useState("");
    const [meetVia, setMeetVia] = useState("on-site");
    const [meetLink, setMeetLink] = useState("");
    const [startSlot, setStartSlot] = useState([]);
    const [endSlot, setEndSlot] = useState([]);
    const [interval, setInterval] = useState([]);
    const [purpose, setPurpose] = useState("Price offer");
    const [purposeText, setPurposeText] = useState("");
    const [addresses, setAddresses] = useState([]);
    const [address, setAddress] = useState("");
    const [availableSlots, setAvailableSlots] = useState([]);
    const [bookedSlots, setBookedSlots] = useState([]);
    const [schedule, setSchedule] = useState(null);
    const [isLoading, setIsLoading] = useState(false);

    const param = useParams();
    const alert = useAlert();
    const navigate = useNavigate();
    const { t } = useTranslation();
    const queryParams = new URLSearchParams(window.location.search);
    const sid = queryParams.get("sid");
    const urlParamAction = queryParams.get("action");

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const sendMeeting = () => {
        let purps = "";
        if (purpose == null) {
            purps = "Price offer";
        } else if (purpose == "Other") {
            purps = purposeText;
        } else {
            purps = purpose;
        }

        let st = document.querySelector("#status").value;
        const data = {
            client_id: param.id,
            team_id: team.length > 0 ? team : team == 0 ? "" : "",
            start_date: startDate,
            start_time: startTime,
            end_time: endTime,
            meet_via: meetVia,
            meet_link: meetLink,
            purpose: purps,
            booking_status: st,
            address_id: address,
        };

        setIsLoading(true);

        axios
            .post(`/api/admin/schedule`, data, { headers })
            .then((res) => {
                if (res.data.errors) {
                    for (let e in res.data.errors) {
                        alert.error(res.data.errors[e]);
                    }
                    setIsLoading(false);
                } else {
                    if (res.data.action == "redirect") {
                        window.location = res.data.url;
                    } else {
                        alert.success(res.data.message);
                        createAndSendMeeting(res.data.data.id);
                    }
                }
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

    const createAndSendMeeting = (_scheduleID) => {
        setIsLoading(true);

        axios
            .post(
                `/api/admin/schedule/${_scheduleID}/create-event`,
                {},
                {
                    headers,
                }
            )
            .then((res) => {
                setIsLoading(false);

                if (res.data.errors) {
                    for (let e in res.data.errors) {
                        alert.error(res.data.errors[e]);
                    }
                } else {
                    alert.success(res.data.message);
                    setTimeout(() => {
                        navigate("/admin/schedule");
                    }, 1000);
                }
            })
            .catch((error) => {
                setIsLoading(false);
                if (error.response.data.error.message) {
                    Swal.fire({
                        title: "Error!",
                        text: error.response.data.error.message,
                        icon: "error",
                    });
                }
            });
    };

    const getClient = () => {
        axios.get(`/api/admin/clients/${param.id}`, { headers }).then((res) => {
            const { client } = res.data;
            setClient(client);
            setAddresses(
                client.property_addresses ? client.property_addresses : []
            );
        });
    };
    const getTeam = () => {
        axios.get(`/api/admin/teams`, { headers }).then((res) => {
            let team = res.data.team.data
                ? res.data.team.data.filter((e) => {
                      return e.name != "superadmin";
                  })
                : [];
            setTotalTeam(team);
        });
    };

    const getSchedule = () => {
        axios.get(`/api/admin/schedule/${sid}`, { headers }).then((res) => {
            const d = res.data.schedule;
            setSchedule(d);
            setTeam(d.team_id ? d.team_id.toString() : "0");
            setBstatus(d.booking_status);
            setStartDate(Moment(d.start_date).toDate());

            if (d.start_time) {
                setStartTime(moment(d.start_time, "hh:mm A").format("kk:mm"));
            } else {
                setStartTime("");
            }
            if (d.end_time) {
                setEndTime(moment(d.end_time, "hh:mm A").format("kk:mm"));
            } else {
                setEndTime("");
            }
            setMeetVia(d.meet_via);
            setMeetLink(d.meet_link);
            setPurpose(d.purpose);
            setAddress(d.address_id);
            if (d.purpose != "Price offer" && d.purpose != "Quality check") {
                setPurposeText(d.purpose);
            }
        });
    };

    const getEvents = (tm) => {
        axios
            .post(`/api/admin/schedule-events`, { tid: tm }, { headers })
            .then((res) => {
                setEvents(res.data.events);
            });
    };

    const getTime = () => {
        axios.get(`/api/admin/get-time`, { headers }).then((res) => {
            if (res.data.data) {
                setStartSlot(res.data.data.start_time);
                setEndSlot(res.data.data.end_time);
                let ar = JSON.parse(res.data.data.days);
                let ai = [];
                ar && ar.map((a, i) => ai.push(parseInt(a)));
                var hid = [0, 1, 2, 3, 4, 5, 6].filter(function (obj) {
                    return ai.indexOf(obj) == -1;
                });
                setInterval(hid);
            }
        });
    };

    useEffect(() => {
        getClient();
        getTime();
        getTeam();
        if (sid != "" && sid != null) {
            setTimeout(() => {
                getSchedule();

                if (urlParamAction === "create-calendar-event") {
                    createAndSendMeeting(sid);
                }
            }, 500);
            setTimeout(() => {
                const tm = document.querySelector("#team").value;
                getEvents(tm);
            }, 1000);
        }
    }, []);

    useEffect(() => {
        if (meetVia == "off-site") {
            setStartDate("");
            setStartTime("");
            setEndTime("");
        }
    }, [meetVia]);

    const handleUpdate = (e) => {
        if (sid != "" && sid != null) {
            let data = {};

            if (e.target == undefined) {
                data.name = "start_date";
                data.value = e;
            } else if (e.target.value == "Other") {
                data.name = e.target.name;
                data.value = document.querySelector("#purpose_text").value;
            } else {
                data.name =
                    e.target.name == "purpose_text" ? "purpose" : e.target.name;
                data.value = e.target.value;
            }

            axios
                .put(`/api/admin/schedule/${sid}`, data, { headers })
                .then((res) => {
                    alert.success(res.data.message);
                    if (res.data.change == "date") {
                        setTimeout(() => {
                            window.location.reload(true);
                        }, 2000);
                    }
                })
                .catch((e) => {
                    Swal.fire({
                        title: "Error!",
                        text: e.response.data.message,
                        icon: "error",
                    });
                });
        }
    };

    const getTeamAvailibality = () => {
        if ((team && team != "0" && team != "") && startDate) {
            const _date = Moment(startDate).format("Y-MM-DD");

            axios
                .get(`/api/admin/teams/availability/${team}/date/${_date}`, {
                    headers,
                })
                .then((response) => {
                    setAvailableSlots(
                        response.data.available_slots.map((i) => {
                            return {
                                start_time: i.start_time.slice(0, -3),
                                end_time: i.end_time.slice(0, -3),
                            };
                        })
                    );
                    setBookedSlots(response.data.booked_slots);
                })
                .catch((e) => {
                    setAvailableSlots([]);
                    setBookedSlots([]);

                    Swal.fire({
                        title: "Error!",
                        text: e.response.data.message,
                        icon: "error",
                    });
                });
        } else {
            setAvailableSlots([]);
            setBookedSlots([]);
        }
    };

    const handleTeamChange = (_id) => {
        getEvents(_id);
    };

    const timeOptions = useMemo(() => {
        return createHalfHourlyTimeArray("08:00", "24:00");
    }, []);

    const startTimeOptions = useMemo(() => {
        const _timeOptions = timeOptions.filter((_option) => {
            if (_option == "24:00") {
                return false;
            }

            if (schedule && schedule.start_time) {
                const _st = moment(schedule.start_time, "hh:mm A").format(
                    "kk:mm"
                );
                if (_st == _option) {
                    return true;
                }
            }

            const _startTime = moment(_option, "kk:mm");
            const isSlotAvailable = availableSlots.some((slot) => {
                const _slotStartTime = moment(slot.start_time, "kk:mm");
                const _slotEndTime = moment(slot.end_time, "kk:mm");

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

    useEffect(() => {
        getTeamAvailibality();
    }, [team, startDate]);

    useEffect(() => {
        const startIndex = timeOptions.indexOf(startTime);

        if (startIndex != -1) {
            const _timeOptions = timeOptions.slice(startIndex + 1);

            const _endTime = _timeOptions[0];
            setEndTime(_endTime);
        }
    }, [startTime]);

    const handlePurpose = (e) => {
        let pt = document.querySelector("#purpose_text");
        if (e.target.value == "Other") {
            pt.style.display = "block";
        } else {
            pt.style.display = "none";
        }
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <h1 className="page-title">
                    {t("admin.schedule.scheduleMetting")}
                </h1>
                <div className="dashBox maxWidthControl p-4 sch-meet">
                    <div className="row">
                        <div className="col-sm-8">
                            <h1>
                                {client.firstname +
                                    " " +
                                    (client.lastname ? client.lastname : "")}
                            </h1>
                            <ul className="list-unstyled">
                                <li>
                                    <i className="fas fa-mobile"></i>{" "}
                                    {client.phone}
                                </li>
                                <li>
                                    <i className="fas fa-envelope"></i>{" "}
                                    {client.email}
                                </li>
                            </ul>
                        </div>
                        <div className="col-sm-4">
                            <div className="form-group float-right xs-float-none">
                                <label>
                                    {t("admin.schedule.scheduleMetting")}
                                </label>
                                <p>
                                    {Moment(client.created_at).format(
                                        "DD/MM/Y"
                                    )}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div className="row mt-4">
                        <div className="col-sm-6">
                            <div className="form-group">
                                <label className="control-label">
                                    {t("admin.schedule.meetingStatus")}
                                </label>
                                <select
                                    className="form-control"
                                    id="status"
                                    name="booking_status"
                                    value={bstatus}
                                    onChange={(e) => {
                                        setBstatus(e.target.value);
                                        handleUpdate(e);
                                    }}
                                >
                                    <option value="pending">
                                        {t(
                                            "admin.schedule.options.meetingStatus.Pending"
                                        )}
                                    </option>
                                    <option value="confirmed">
                                        {t(
                                            "admin.schedule.options.meetingStatus.Confirmed"
                                        )}
                                    </option>
                                    <option value="declined">
                                        {t(
                                            "admin.schedule.options.meetingStatus.Declined"
                                        )}
                                    </option>
                                    <option value="completed">
                                        {t(
                                            "admin.schedule.options.meetingStatus.Completed"
                                        )}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div className="col-sm-6">
                            <div className="form-group">
                                <label className="control-label">
                                    {t("admin.schedule.meetingAttender")}
                                </label>
                                <select
                                    className="form-control"
                                    name="team_id"
                                    id="team"
                                    value={team}
                                    onChange={(e) => {
                                        setTeam(e.target.value);
                                        handleUpdate(e);
                                        handleTeamChange(e.target.value);
                                    }}
                                >
                                    <option value="0">
                                        {t(
                                            "admin.schedule.options.pleaseSelect"
                                        )}
                                    </option>
                                    {totalTeam &&
                                        totalTeam.map((t, i) => {
                                            return (
                                                <option value={t.id} key={i}>
                                                    {" "}
                                                    {t.name}{" "}
                                                </option>
                                            );
                                        })}
                                </select>
                            </div>
                        </div>
                    </div>
                    <div className="row mt-4">
                        <div className="col-sm-6">
                            <div className="form-group">
                                <label className="control-label">
                                    {t("admin.schedule.meetingPurpose")}
                                </label>
                                <select
                                    className="form-control"
                                    name="purpose"
                                    id="purpose"
                                    value={purpose}
                                    onChange={(e) => {
                                        setPurpose(e.target.value);
                                        handlePurpose(e);
                                        handleUpdate(e);
                                    }}
                                >
                                    <option value="Price offer">
                                        {t(
                                            "admin.schedule.options.meetingPurpose.priceOffer"
                                        )}
                                    </option>
                                    <option value="Quality check">
                                        {t(
                                            "admin.schedule.options.meetingPurpose.qualityCheck"
                                        )}
                                    </option>
                                    <option value="Other">
                                        {t(
                                            "admin.schedule.options.meetingPurpose.other"
                                        )}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div className="col-sm-6">
                            <div className="form-group">
                                <div className="form-group">
                                    <label>&nbsp;</label>
                                    <input
                                        type="text"
                                        name="purpose_text"
                                        id="purpose_text"
                                        value={purposeText}
                                        style={
                                            purpose != "Quality check" &&
                                            purpose != "Price offer" &&
                                            purpose != ""
                                                ? { display: "block" }
                                                : { display: "none" }
                                        }
                                        onChange={(e) => {
                                            setPurposeText(e.target.value);
                                        }}
                                        onBlur={(e) => handleUpdate(e)}
                                        placeholder="Enter purpose please"
                                        className="form-control"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div className="mSchedule">
                        <h4>{t("admin.schedule.meetingTimeAndDate")}</h4>
                        {meetVia == "on-site" && (
                            <div className="row">
                                <div className="col-sm-4">
                                    <div className="form-group">
                                        <label>
                                            {" "}
                                            {t("admin.schedule.date")}
                                        </label>
                                        <DatePicker
                                            dateFormat="dd/MM/Y"
                                            selected={startDate}
                                            id="dateSel"
                                            onChange={(date) => {
                                                setStartDate(date);
                                                handleUpdate(date);
                                            }}
                                        />
                                    </div>
                                </div>
                                <div className="col-sm-4">
                                    <div className="form-group">
                                        <label>Start Time</label>
                                        <select
                                            name="start_time"
                                            id="start_time"
                                            value={startTime}
                                            onChange={(e) => {
                                                setStartTime(e.target.value);
                                                handleUpdate(e);
                                            }}
                                            className="form-control"
                                        >
                                            <option value="">
                                                --- Choose start time ---
                                            </option>
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
                                <div className="col-sm-4">
                                    <div className="form-group">
                                        <label>End Time</label>
                                        <input
                                            name="end_time"
                                            id="end_time"
                                            value={endTime}
                                            className="form-control"
                                            readOnly
                                        />
                                    </div>
                                </div>
                            </div>
                        )}

                        <div className="row">
                            <div className="col-sm-4">
                                <div className="form-group">
                                    <label>{t("admin.schedule.meetVia")}</label>
                                    <select
                                        name="meet_via"
                                        id="meet_via"
                                        value={meetVia}
                                        onChange={(e) => {
                                            setMeetVia(e.target.value);
                                            handleUpdate(e);
                                        }}
                                        className="form-control"
                                    >
                                        <option value="on-site">
                                            {t(
                                                "admin.schedule.options.meetVia.onSite"
                                            )}
                                        </option>
                                        <option value="off-site">
                                            {t(
                                                "admin.schedule.options.meetVia.offSite"
                                            )}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div className="col-sm-4">
                                <div className="form-group">
                                    <label>
                                        {t("admin.schedule.meetLink")}
                                    </label>
                                    <input
                                        type="text"
                                        id="meet_link"
                                        name="meet_link"
                                        value={meetLink}
                                        onChange={(e) => {
                                            setMeetLink(e.target.value);
                                            handleUpdate(e);
                                        }}
                                        className="form-control"
                                        placeholder="Insert Meeting Link"
                                    />
                                </div>
                            </div>
                        </div>
                        <div className="row">
                            <div className="col-sm-4">
                                <div className="form-group">
                                    <label>
                                        {t("admin.schedule.Property")}
                                    </label>
                                    <select
                                        name="address_id"
                                        id="address_id"
                                        value={address}
                                        onChange={(e) => {
                                            setAddress(e.target.value);
                                            handleUpdate(e);
                                        }}
                                        className="form-control"
                                    >
                                        <option value="">
                                            {t(
                                                "admin.schedule.options.pleaseSelect"
                                            )}
                                        </option>
                                        {addresses.map((address, i) => (
                                            <option
                                                value={address.id}
                                                key={address.id}
                                            >
                                                {address.address_name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div className="text-center mt-3">
                            <button
                                className="btn btn-pink sendBtn"
                                onClick={sendMeeting}
                                disabled={isLoading}
                            >
                                {t("admin.schedule.btnSend")}
                            </button>
                        </div>

                        <div className="worker-avail1">
                            <h4 className="text-center">
                                {t("admin.schedule.workerAvailability")}
                            </h4>
                            <FullCalendar
                                initialView="timeGridWeek"
                                allDaySlot={false}
                                slotMinTime={startSlot}
                                slotMaxTime={endSlot}
                                hiddenDays={interval}
                                selectable={true}
                                height={"auto"}
                                slotEventOverlap={false}
                                plugins={[timeGridPlugin]}
                                events={events}
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
