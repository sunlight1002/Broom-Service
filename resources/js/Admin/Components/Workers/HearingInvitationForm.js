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
import { useLocation } from "react-router-dom";

import Sidebar from "../../Layouts/Sidebar";
import { createHalfHourlyTimeArray } from "../../../Utils/job.utils";
import FullPageLoader from "../../../Components/common/FullPageLoader";

const HearingInvitation = () => {
    const [workerState, setWorker] = useState([]);
    const [totalTeam, setTotalTeam] = useState([]);
    const [team, setTeam] = useState("");
    const [bstatus, setBstatus] = useState("");
    const [events, setEvents] = useState([]);
    const [meetVia, setMeetVia] = useState("on-site");
    const [meetLink, setMeetLink] = useState("");
    const [startSlot, setStartSlot] = useState([]);
    const [endSlot, setEndSlot] = useState([]);
    const [interval, setInterval] = useState([]);
    const [purpose, setPurpose] = useState("Hearing Invitation");
    const [purposeText, setPurposeText] = useState("");
    const [addresses, setAddresses] = useState([]);
    const [address, setAddress] = useState("");
    const [availableSlots, setAvailableSlots] = useState([]);
    const [bookedSlots, setBookedSlots] = useState([]);
    const [schedule, setSchedule] = useState(null);
    const [isLoading, setIsLoading] = useState(false);
    const [selectedDate, setSelectedDate] = useState(null);
    const [selectedTime, setSelectedTime] = useState(null);
    const [formattedSelectedDate, setFormattedSelectedDate] = useState('');

    const location = useLocation();
    const { worker, getWorkerDetails } = location.state || {};

    const params = useParams();
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

    const handleDateChange = (_date) => {
        setSelectedDate(_date);
    };

    const handleTimeChange = (_time) => {
        setSelectedTime(_time);
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

    const timeSlots = useMemo(() => {
        return startTimeOptions.map((i) =>
            moment(i, "kk:mm").format("hh:mm A")
        );
    }, [startTimeOptions]);

    const sendMeeting = async () => {
        if (meetVia === "on-site") {
            if (!selectedDate) {
                alert.error("Date not selected");
                return false;
            }

            if (!selectedTime) {
                alert.error("Time not selected");
                return false;
            }
        }

        let purps = purposeText || "Hearing Invitation";

        let st = document.querySelector("#status").value;
        const data = {
            user_id: params.id,
            team_id: team.length > 0 ? team : team == 0 ? "" : "",
            start_date: selectedDate ? Moment(selectedDate).format("YYYY-MM-DD") : null,
            start_time: selectedTime,
            meet_via: meetVia,
            meet_link: meetLink,
            purpose: purps,
            booking_status: st,
        };

        setIsLoading(true);

        if (sid) {
            await axios
                .put(`/api/admin/hearing-invitations/${sid}`, data, { headers })
                .then((res) => {
                    setIsLoading(false);

                    if (res.data.errors) {
                        for (let e in res.data.errors) {
                            alert.error(res.data.errors[e]);
                        }
                    } else {
                        alert.success(res.data.message);
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
        } else {
            await axios
                .post(`/api/admin/hearing-invitations/create`, data, { headers })
                .then((res) => {
                    setIsLoading(false);

                    if (res.data.errors) {
                        for (let e in res.data.errors) {
                            alert.error(res.data.errors[e]);
                        }
                    } else {
                        const workerId = worker.id; 
                        if (res.data.action === "redirect") {
                            window.location = navigate(`/admin/workers/view/${workerId}`);
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
        }
    };

    const createAndSendMeeting = (_scheduleID) => {
        setIsLoading(true);

        axios
            .post(`/api/admin/hearing-invitations/${_scheduleID}/create-event`, {}, { headers })
            .then((res) => {
                setIsLoading(false);

                if (res.data.errors) {
                    for (let e in res.data.errors) {
                        alert.error(res.data.errors[e]);
                    }
                } else {
                    const workerId = worker.id; 
                    setTimeout(() => {
                        navigate(`/admin/workers/view/${workerId}`);
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

    const getWorker = () => {
        axios
            .get(`/api/admin/workers/${params.id}`, { headers })
            .then((res) => {
                const { worker } = res.data;
                setWorker(worker);
            });
    };

    const getTeams = () => {
        axios.get(`/api/admin/teams/all`, { headers }).then((res) => {
            setTotalTeam(res.data.data);
        });
    };

    const getSchedule = () => {
        setIsLoading(true);

        axios
            .get(`/api/admin/hearing-invitations/${sid}`, { headers }) // Updated endpoint for getting schedule details
            .then((res) => {
                setIsLoading(false);
                const d = res.data.schedule;
                setSchedule(d);
                setTeam(d.team_id ? d.team_id.toString() : "");
                setBstatus(d.booking_status);
                if (d.start_date) {
                    setSelectedDate(Moment(d.start_date).toDate());
                } else {
                    setSelectedDate(null);
                }

                if (d.start_time) {
                    setSelectedTime(d.start_time);
                } else {
                    setSelectedTime("");
                }
                setMeetVia(d.meet_via);
                setMeetLink(d.meet_link ?? "");
                setPurpose(d.purpose);
            })
            .catch((e) => {
                setIsLoading(false);
            });
    };

    const getTeamEvents = async (_teamID) => {
        await axios
            .get(`/api/admin/teams/${_teamID}/schedule-events`, { headers })
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
                    return ai.indexOf(obj) === -1;
                });
                setInterval(hid);
            }
        });
    };

    useEffect(() => {
        getWorker();
        getTime();
        getTeams();
        if (sid != null) {
            setTimeout(() => {
                getSchedule();

                if (urlParamAction === "create-calendar-event") {
                    createAndSendMeeting(sid);
                }
            }, 500);
        }
    }, []);

    useEffect(() => {
        if (meetVia === "off-site") {
            setSelectedDate("");
            setSelectedTime("");
        }
    }, [meetVia]);

    const getTeamAvailability = async () => {
        if (team && team !== "0" && team !== "" && selectedDate) {
            const _date = Moment(selectedDate).format("YYYY-MM-DD");

            await axios
                .get(`/api/admin/teams/availability/${team}/date/${_date}`, { headers })
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
                });
        }
    };

    useEffect(() => {
        if (team) {
            getTeamEvents(team);
            getTeamAvailability();
        }
    }, [team, selectedDate]);   

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="col-sm-8 mt-4">
                    <h4>
                        {t("worker.settings.invitation_for_hearing")}
                    </h4>
                    <hr/>
                </div>
                <div className="dashBox maxWidthControl p-4 sch-meet">
                    <div className="row">
                    </div>
                    <div className="row mt-4">
                        <div className="col-sm-6">
                            <div className="form-group">
                                <label className="control-label">
                                    {t("admin.hearing.hearingStatus")}
                                </label>
                                <select
                                    className="form-control"
                                    id="status"
                                    name="booking_status"
                                    value={bstatus}
                                    onChange={(e) => {
                                        setBstatus(e.target.value);
                                    }}
                                >
                                    <option value="pending">
                                        {t("admin.hearing.options.hearingStatus.Pending")}
                                    </option>
                                    <option value="confirmed">
                                        {t("admin.hearing.options.hearingStatus.Confirmed")}
                                    </option>
                                    <option value="declined">
                                        {t("admin.hearing.options.hearingStatus.Declined")}
                                    </option>
                                    <option value="completed">
                                        {t("admin.hearing.options.hearingStatus.Completed")}
                                    </option>
                                    <option value="rescheduled">
                                        {t("admin.hearing.options.hearingStatus.Rescheduled")}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div className="col-sm-6">
                            <div className="form-group">
                                <label className="control-label">
                                    {t("admin.hearing.hearingAttender")}
                                </label>
                                <select
                                    className="form-control"
                                    name="team_id"
                                    id="team"
                                    value={team}
                                    onChange={(e) => {
                                        setTeam(e.target.value);
                                    }}
                                >
                                    <option value="">
                                        {t("admin.hearing.options.pleaseSelect")}
                                    </option>
                                    {totalTeam &&
                                        totalTeam.map((t, i) => (
                                            <option value={t.id} key={i}>
                                                {t.name}
                                            </option>
                                        ))}
                                </select>
                            </div>
                        </div>
                    </div>
                    <div className="row">
                        <div className="col-sm-6">
                            <div className="form-group">
                                <label className="control-label">
                                    {t("admin.hearing.hearingPurpose")}
                                </label>
                                <input
                                        type="text"
                                        name="purpose_text"
                                        id="purpose_text"
                                        value={purposeText}
                                        onChange={(e) => {
                                            setPurposeText(e.target.value);
                                        }}
                                        placeholder="Enter purpose please"
                                        className="form-control"
                                    />
                            </div>
                        </div>
                    </div>
                    <div className="row">
                        <div className="col-sm-4">
                            <div className="form-group">
                                <label>{t("admin.hearing.meetVia")}</label>
                                <select
                                    name="meet_via"
                                    id="meet_via"
                                    value={meetVia}
                                    onChange={(e) => {
                                        setMeetVia(e.target.value);
                                    }}
                                    className="form-control"
                                >
                                    <option value="on-site">
                                        {t("admin.hearing.options.meetVia.onSite")}
                                    </option>
                                    <option value="off-site">
                                        {t("admin.hearing.options.meetVia.offSite")}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div className="col-sm-4">
                            <div className="form-group">
                                <label>{t("admin.hearing.meetLink")}</label>
                                <input
                                    type="text"
                                    id="meet_link"
                                    name="meet_link"
                                    value={meetLink}
                                    onChange={(e) => {
                                        setMeetLink(e.target.value);
                                    }}
                                    className="form-control"
                                    placeholder="Insert Meeting Link"
                                />
                            </div>
                        </div>
                        <div className="col-sm-4">
                            <div className="form-group">
                                <label>{t("admin.hearing.Property")}</label>
                                <select
                                    name="address_id"
                                    id="address_id"
                                    value={address}
                                    onChange={(e) => {
                                        setAddress(e.target.value);
                                    }}
                                    className="form-control"
                                >
                                    <option value="">
                                        {t("admin.hearing.options.pleaseSelect")}
                                    </option>
                                    {addresses &&
                                        addresses.map((addr, i) => (
                                            <option value={addr.id} key={i}>
                                                {addr.name}
                                            </option>
                                        ))}
                                </select>
                            </div>
                        </div>
                    </div>
    
                    <div className="mSchedule">
                        {meetVia == "on-site" && (
                            <>
                                <h4>
                                    {t("admin.hearing.hearingTimeAndDate")}
                                </h4>

                                <div className="mx-auto mt-5 custom-calendar">
                                    <div className="border">
                                        <h5 className="mt-3 ml-3">
                                            {t("global.selectDateAndTimeRange")}
                                        </h5>
                                        <div
                                            className="d-flex gap-3 p-3 flex-wrap justify-content-center"
                                            style={{ overflowX: "auto" }}
                                        >
                                            <div>
                                                <DatePicker
                                                    selected={selectedDate}
                                                    onChange={(date) =>
                                                        handleDateChange(date)
                                                    }
                                                    autoFocus
                                                    shouldCloseOnSelect={false}
                                                    inline
                                                    minDate={new Date()}
                                                />
                                            </div>
                                            <div className="mt-1 ">
                                                <h6 className="time-slot-date">
                                                    {formattedSelectedDate}
                                                </h6>
                                                <ul className="list-unstyled mt-4 timeslot">
                                                    {timeSlots.length > 0 ? (
                                                        timeSlots.map(
                                                            (t, index) => {
                                                                return (
                                                                    <li
                                                                        className={`py-2 px-3 border  mb-2  text-center border-primary  ${
                                                                            selectedTime ===
                                                                            t
                                                                                ? "bg-primary text-white"
                                                                                : "text-primary"
                                                                        }`}
                                                                        key={
                                                                            index
                                                                        }
                                                                        onClick={() => {
                                                                            handleTimeChange(
                                                                                t
                                                                            );
                                                                        }}
                                                                    >
                                                                        {t}
                                                                    </li>
                                                                );
                                                            }
                                                        )
                                                    ) : (
                                                        <li className="py-2 px-3 border mb-2 text-center border-secondary text-secondary bg-light">
                                                            {t("global.noTimeSlot")}
                                                            {t("global.available")}
                                                        </li>
                                                    )}
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </>
                        )}

                        <div className="text-center mt-3">
                            <button
                                className="btn navyblue sendBtn"
                                onClick={sendMeeting}
                                disabled={isLoading}
                            >
                                {t("admin.hearing.btnSend")}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <FullPageLoader visible={isLoading} />
        </div>
    );
    

};

export default HearingInvitation;
