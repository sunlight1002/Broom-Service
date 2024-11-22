import FullCalendar from "@fullcalendar/react";
import timeGridPlugin from "@fullcalendar/timegrid";
import axios from "axios";
import { default as Moment, default as moment } from "moment";
import React, { useEffect, useMemo, useRef, useState } from "react";
import { useAlert } from "react-alert";
import { Button, Modal } from "react-bootstrap";
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";
import { useTranslation } from "react-i18next";
import { useNavigate, useParams } from "react-router-dom";
import Swal from "sweetalert2";
import Select from "react-select";

import FullPageLoader from "../../../Components/common/FullPageLoader";
import { createHalfHourlyTimeArray } from "../../../Utils/job.utils";
import Map from "../../Components/Map/map";
import Sidebar from "../../Layouts/Sidebar";

export default function ViewSchedule() {
    const [client, setClient] = useState([]);
    const [totalTeam, setTotalTeam] = useState([]);
    const [team, setTeam] = useState("");
    const [bstatus, setBstatus] = useState("");
    const [events, setEvents] = useState([]);
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
    const [selectedDate, setSelectedDate] = useState(null);
    const [selectedTime, setSelectedTime] = useState(null);
    const [isModalOpen, setModalStatus] = useState(false);
    const [place, setPlace] = useState();
    const [latitude, setLatitude] = useState(32.109333);
    const [longitude, setLongitude] = useState(34.855499);
    const [libraries] = useState(["places", "geometry"]);
    const [allWorkers, setAllWorkers] = useState([]);
    const [workers, setWorkers] = useState([]);

    const params = useParams();
    const alert = useAlert();
    const navigate = useNavigate();
    const { t } = useTranslation();
    const queryParams = new URLSearchParams(window.location.search);
    const sid = queryParams.get("sid");
    const urlParamAction = queryParams.get("action");

    let isAdd = useRef(true);
    let fullAddress = useRef();
    let floor = useRef();
    let Apt = useRef();
    let enterance = useRef();
    let zip = useRef();
    let parking = useRef();
    let addressId = useRef();
    let lat = useRef();
    let long = useRef();
    let city = useRef();
    let prefer_type = useRef();
    let is_dog_avail = useRef();
    let is_cat_avail = useRef();
    let client_id = useRef();
    let addressName = useRef();
    let key = useRef();
    let lobby = useRef();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

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
            client_id: params.id,
            team_id: team.length > 0 ? team : team == 0 ? "" : "",
            start_date: selectedDate
                ? Moment(selectedDate).format("YYYY-MM-DD")
                : null,
            start_time: selectedTime,
            meet_via: meetVia,
            meet_link: meetLink,
            purpose: purps,
            booking_status: st,
            address_id: address,
        };

        setIsLoading(true);

        if (sid) {
            await axios
                .put(`/api/admin/schedule/${sid}`, data, {
                    headers,
                })
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
                .post(`/api/admin/schedule`, data, {
                    headers,
                })
                .then((res) => {
                    setIsLoading(false);

                    if (res.data.errors) {
                        for (let e in res.data.errors) {
                            alert.error(res.data.errors[e]);
                        }
                    } else {
                        if (res.data.action == "redirect") {
                            window.location = res.data.url;
                        } else {
                            alert.success(res.data.message);
                            setTimeout(() => {
                                navigate("/admin/schedule");
                            }, 1000);
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

    const resetForm = () => {
        fullAddress.current && (fullAddress.current.value = "");
        addressName.current && (addressName.current.value = "");
        floor.current && (floor.current.value = "");
        Apt.current && (Apt.current.value = "");
        enterance.current && (enterance.current.value = "");
        zip.current && (zip.current.value = "");
        parking.current && (parking.current.value = "");
        key.current && (key.current.value = "");
        lobby.current && (lobby.current.value = "");
        prefer_type.current && (prefer_type.current.value = "default");
        is_cat_avail.current && (is_cat_avail.current.checked = false);
        is_dog_avail.current && (is_dog_avail.current.checked = false);
        client_id.current && (client_id.current.value = 0);
        lat.current && (lat.current.value = 32.109333);
        long.current && (long.current.value = 34.855499);
        setAddress("");
        setLatitude(32.109333);
        setLongitude(34.855499);
        setWorkers([]);
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
        axios
            .get(`/api/admin/clients/${params.id}`, { headers })
            .then((res) => {
                const { client } = res.data;
                setClient(client);
                setAddresses(
                    client.property_addresses ? client.property_addresses : []
                );
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
            .get(`/api/admin/schedule/${sid}`, { headers })
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
                setAddress(d.address_id);
                if (
                    d.purpose != "Price offer" &&
                    d.purpose != "Quality check"
                ) {
                    setPurposeText(d.purpose);
                }
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
                    return ai.indexOf(obj) == -1;
                });
                setInterval(hid);
            }
        });
    };

    useEffect(() => {
        getClient();
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
        if (meetVia == "off-site") {
            setSelectedDate("");
            setSelectedTime("");
        }
    }, [meetVia]);

    const getTeamAvailibality = async () => {
        if (team && team != "0" && team != "" && selectedDate) {
            const _date = Moment(selectedDate).format("Y-MM-DD");

            await axios
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

    useEffect(() => {
        getTeamAvailibality();
    }, [team, selectedDate]);

    useEffect(() => {
        if (team) {
            getTeamEvents(team);
        }
    }, [team]);

    const handlePurpose = (e) => {
        let pt = document.querySelector("#purpose_text");
        if (e.target.value == "Other") {
            pt.style.display = "block";
        } else {
            pt.style.display = "none";
        }
    };

    const onLoad = (autocomplete) => {
        setPlace(autocomplete);
    };
    const onPlaceChanged = () => {
        if (place) {
            const _place = place.getPlace();
            setAddress(_place.formatted_address);
            fullAddress.current.value = _place.formatted_address;
            addressName.current.value = _place.name;
            setLatitude(_place.geometry.location.lat());
            lat.current.value = _place.geometry.location.lat();
            setLongitude(_place.geometry.location.lng());
            long.current.value = _place.geometry.location.lng();
        }
    };

    useEffect(() => {
        if (place?.getPlace() && isModalOpen && isAdd.current) {
            const _place = place.getPlace();
            lat.current.value = _place.geometry.location.lat();
            long.current.value = _place.geometry.location.lng();
            city.current.value = _place.vicinity;
            const address_components = _place.address_components;
            $.each(address_components, function (index, component) {
                var types = component.types;
                $.each(types, function (index, type) {
                    if (type === "postal_code") {
                        zip.current.value = component.long_name;
                    }
                });
            });
        }
        if (!address && isModalOpen) {
            zip.current.value = "";
        }
    }, [place?.getPlace(), isModalOpen]);

    const handleAddress = (e) => {
        e.preventDefault();
        let addressVal = [...addresses];
        if (address === "" && fullAddress.current.value === "") {
            let newErrors = { ...errors };
            newErrors.address = "Please Select address";
            setErrors(newErrors);
            return false;
        } else if (addressName.current.value === "") {
            let newErrors = { ...errors };
            newErrors.address_name = "Please add address";
            setErrors(newErrors);
            return false;
        } else {
            const getWorkerId = [...workers].map((w) => w.value);
            const updatedData = {
                geo_address: fullAddress.current.value,
                address_name: addressName.current.value
                    ? addressName.current.value
                    : "",
                floor: floor.current.value,
                apt_no: Apt.current.value,
                entrence_code: enterance.current.value,
                zipcode: zip.current.value,
                parking: parking.current.value,
                longitude: long.current.value,
                latitude: lat.current.value,
                city: city.current.value,
                prefer_type: prefer_type.current.value,
                key: key.current.value,
                lobby: lobby.current.value,
                is_dog_avail: is_dog_avail.current.checked,
                is_cat_avail: is_cat_avail.current.checked,
                client_id: client_id.current.value,
                id: 0,
                not_allowed_worker_ids:
                    getWorkerId.length > 0 ? getWorkerId.toString() : null,
            };
            const adId = addressId.current?.value;
            if (isAdd.current) {
                if (!params.id) {
                    addressVal = [updatedData, ...addressVal];
                }
            } else {
                addressVal[addressId.current.value]["geo_address"] =
                    updatedData.geo_address;
                addressVal[addressId.current.value]["floor"] =
                    updatedData.floor;
                addressVal[addressId.current.value]["apt_no"] =
                    updatedData.apt_no;
                addressVal[addressId.current.value]["entrence_code"] =
                    updatedData.entrence_code;
                addressVal[addressId.current.value]["zipcode"] =
                    updatedData.zipcode;
                addressVal[addressId.current.value]["parking"] =
                    updatedData.parking;
                addressVal[addressId.current.value]["prefer_type"] =
                    updatedData.prefer_type;
                addressVal[addressId.current.value]["key"] =
                    updatedData.key;
                addressVal[addressId.current.value]["lobby"] =
                    updatedData.lobby;
                addressVal[addressId.current.value]["is_dog_avail"] =
                    updatedData.is_dog_avail;
                addressVal[addressId.current.value]["is_cat_avail"] =
                    updatedData.is_cat_avail;
                addressVal[addressId.current.value]["longitude"] =
                    updatedData.longitude;
                addressVal[addressId.current.value]["latitude"] =
                    updatedData.latitude;
                addressVal[addressId.current.value]["address_name"] =
                    updatedData.address_name ? updatedData.address_name : "";
                addressVal[addressId.current.value]["not_allowed_worker_ids"] =
                    updatedData.not_allowed_worker_ids
                        ? updatedData.not_allowed_worker_ids
                        : "";
                // console.log(updatedData.not_allowed_worker_ids);
            }
            if (params.id) {
                axios
                    .post(
                        `/api/admin/leads/save-property-address`,
                        {
                            data: isAdd.current
                                ? updatedData
                                : addressVal[addressId.current.value],
                        },
                        { headers }
                    )
                    .then((response) => {
                        if (isAdd.current) {
                            addressVal = [response.data.data, ...addressVal];
                        } else {
                            addressVal[adId] = response.data.data;
                        }
                        setAddresses(addressVal);
                        alert.success(
                            "Lead property address saved successfully!"
                        );
                    });
            } else {
                setAddresses(addressVal);
            }
        }
        resetForm();
        setModalStatus(false);
    };


    const formattedSelectedDate = useMemo(() => {
        if (selectedDate) {
            const _date = new Date(selectedDate);
            const dayName = _date.toLocaleDateString("en-US", {
                month: "long",
            });

            const monthName = _date.toLocaleDateString("en-US", {
                weekday: "long",
            });

            const date = _date.getDate();

            return `${dayName}, ${monthName}, ${date}`;
        }
        return "";
    }, [selectedDate]);

    const timeSlots = useMemo(() => {
        return startTimeOptions.map((i) =>
            moment(i, "kk:mm").format("hh:mm A")
        );
    }, [startTimeOptions]);

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
                                    +{client.phone}
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
                                    <option value="rescheduled">
                                        {t(
                                            "admin.schedule.options.meetingStatus.rescheduled"
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
                                    }}
                                >
                                    <option value="">
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
                    <div className="row">
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
                                        placeholder="Enter purpose please"
                                        className="form-control"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
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
                                <label>{t("admin.schedule.meetLink")}</label>
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
                                <label>{t("admin.schedule.Property")}</label>
                                <div className="d-flex">
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
                                    <button
                                        style={{ fontSize: "15px", color: "#2F4054", padding: "1px 9px", background: "#E5EBF1", borderRadius: "5px" }}
                                        className="d-flex ml-1 justify-content-center align-items-center"
                                        onClick={() => {
                                            setModalStatus(true);
                                            isAdd.current = true;
                                            resetForm();
                                        }}
                                    >
                                        {" "}
                                        <i className="fa fa-plus" ></i>{" "}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div className="mSchedule">
                        {meetVia == "on-site" && (
                            <>
                                <h4>
                                    {t("admin.schedule.meetingTimeAndDate")}
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
                                                        timeSlots.filter((t) => moment(t, "hh:mm A").isAfter(moment())).length > 0 ? (
                                                            timeSlots
                                                                .filter((t) => moment(t, "hh:mm A").isAfter(moment()))
                                                                .map((t, index) => (
                                                                    <li
                                                                        className={`py-2 px-3 border mb-2 text-center border-primary ${selectedTime === t ? "bg-primary text-white" : "text-primary"
                                                                            }`}
                                                                        key={index}
                                                                        onClick={() => handleTimeChange(t)}
                                                                    >
                                                                        {t}
                                                                    </li>
                                                                ))
                                                        ) : (
                                                            <li className="py-2 px-3 border mb-2 text-center border-secondary text-secondary bg-light">
                                                                {t("global.noTimeSlot")}
                                                                {t("global.available")}
                                                            </li>
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
            <Modal
                size="lg"
                className="modal-container"
                dialogClassName="custom-modal-dialog" // Apply your custom class here
                show={isModalOpen}
                onHide={() => {
                    isAdd.current = true;
                    resetForm();
                    setModalStatus(false);
                }}
            >
                <Modal.Header closeButton
                    className="border-0"
                    style={{ padding: "1rem 2rem" }}
                >
                    <Modal.Title>
                        <div className="navyblueColor">
                            {isAdd.current
                                ? t(
                                    "admin.leads.AddLead.addAddress.AddPropertyAddress"
                                )
                                : t(
                                    "admin.leads.AddLead.addAddress.EditPropertyAddress"
                                )}
                        </div>
                    </Modal.Title>
                </Modal.Header>

                <Modal.Body
                    className="border-0"
                    style={{ padding: "1rem 2rem" }}
                >
                    <div className="row">
                        <div className="w-100 mr-3 ml-3">
                            <Map
                                onLoad={onLoad}
                                onPlaceChanged={onPlaceChanged}
                                latitude={latitude}
                                longitude={longitude}
                                address={address}
                                setLatitude={setLatitude}
                                setLongitude={setLongitude}
                                libraries={libraries}
                                place={place}
                            />
                        </div>
                    </div>
                    <div className="row">
                        <div className="col-sm-12">
                            <div className="form-group">
                                <label className="control-label navyblueColor">
                                    {t(
                                        "admin.leads.AddLead.addAddress.FullAddress"
                                    )}
                                    <small className="text-pink mb-1">
                                        &nbsp; (
                                        {t(
                                            "admin.leads.AddLead.addAddress.autocomplete"
                                        )}
                                        )
                                    </small>
                                </label>
                                <input
                                    ref={fullAddress}
                                    type="text"
                                    className="form-control skyBorder"
                                    placeholder={t(
                                        "admin.leads.AddLead.addAddress.placeHolder.fullAddress"
                                    )}
                                // readOnly
                                />
                                {/* {errors.address ? (
                                    <small className="text-danger mb-1">
                                        {errors.address}
                                    </small>
                                ) : (
                                    ""
                                )} */}
                            </div>
                        </div>
                    </div>
                    <div className=" d-flex property-modal">
                        <div className="d-flex flex-column ">
                            <div className="form-group d-flex align-items-center">
                                <label className="control-label mb-0 navyblueColor" style={{ width: "15rem", fontWeight: "500", fontSize: "14px" }}>
                                    {t(
                                        "admin.leads.AddLead.addAddress.Name"
                                    )}
                                </label>
                                <input
                                    name="address_name"
                                    ref={addressName}
                                    type="text"
                                    className="form-control skyBorder"
                                    placeholder={t(
                                        "admin.leads.AddLead.addAddress.placeHolder.addressName"
                                    )}
                                />
                                {/* {errors.address_name ? (
                                    <small className="text-danger mb-1">
                                        {errors.address_name}
                                    </small>
                                ) : (
                                    ""
                                )} */}
                            </div>
                            <div className="form-group d-flex align-items-center">
                                <label className="control-label navyblueColor" style={{ width: "15rem", fontWeight: "500", fontSize: "14px" }}>
                                    {t(
                                        "admin.leads.AddLead.addAddress.Floor"
                                    )}
                                </label>
                                <input
                                    type="text"
                                    ref={floor}
                                    className="form-control skyBorder"
                                    placeholder={t(
                                        "admin.leads.AddLead.addAddress.placeHolder.floor"
                                    )}
                                />
                                {/* {errors.floor ? (
                                    <small className="text-danger mb-1">
                                        {errors.floor}
                                    </small>
                                ) : (
                                    ""
                                )} */}
                            </div>
                            <div className="form-group d-flex align-items-center">
                                <label className="control-label navyblueColor" style={{ width: "15rem", fontWeight: "500", fontSize: "14px" }}>
                                    {t(
                                        "admin.leads.AddLead.addAddress.AptNumberAndAptName"
                                    )}
                                </label>
                                <input
                                    type="text"
                                    ref={Apt}
                                    className="form-control skyBorder"
                                    placeholder={t(
                                        "admin.leads.AddLead.addAddress.placeHolder.AptNumberAndAptName"
                                    )}
                                />
                                {/* {errors.Apt ? (
                                    <small className="text-danger mb-1">
                                        {errors.Apt}
                                    </small>
                                ) : (
                                    ""
                                )} */}
                            </div>
                            <div className="form-group d-flex align-items-center">
                                <label className="control-label navyblueColor" style={{ width: "15rem", fontWeight: "500", fontSize: "14px" }}>
                                    {t(
                                        "admin.leads.AddLead.addAddress.EnteranceCode"
                                    )}
                                </label>
                                <input
                                    type="text"
                                    ref={enterance}
                                    className="form-control skyBorder"
                                    placeholder={t(
                                        "admin.leads.AddLead.addAddress.placeHolder.EnteranceCode"
                                    )}
                                />
                                {/* {errors.enterance ? (
                                    <small className="text-danger mb-1">
                                        {errors.enterance}
                                    </small>
                                ) : (
                                    ""
                                )} */}
                            </div>
                            <div className="form-group d-flex align-items-center">
                                <label className="control-label navyblueColor" style={{ width: "15rem", fontWeight: "500", fontSize: "14px" }}>
                                    {t(
                                        "admin.leads.AddLead.addAddress.ZipCode"
                                    )}
                                </label>
                                <input
                                    type="text"
                                    ref={zip}
                                    className="form-control skyBorder"
                                    placeholder={t(
                                        "admin.leads.AddLead.addAddress.placeHolder.ZipCode"
                                    )}
                                />
                                {/* {errors.zip && (
                                    <small className="text-danger mb-1">
                                        {errors.zip}
                                    </small>
                                )} */}
                            </div>
                            <div className="form-group d-flex align-items-center">
                                <label className="control-label" style={{ width: "15rem", fontWeight: "500", fontSize: "14px" }}>
                                    {t(
                                        "admin.leads.AddLead.addAddress.parking"
                                    )}
                                </label>
                                <input
                                    type="text"
                                    ref={parking}
                                    className="form-control skyBorder"
                                    placeholder={t(
                                        "admin.leads.AddLead.addAddress.placeHolder.parking"
                                    )}
                                />
                                {/* {errors.parking && (
                                    <small className="text-danger mb-1">
                                        {errors.parking}
                                    </small>
                                )} */}
                            </div>
                        </div>
                        <div className="d-flex flex-column ml-3">
                            <div className="form-group d-flex align-items-center">
                                <label className="control-label navyblueColor" style={{ width: "15rem", fontWeight: "500", fontSize: "14px" }}>
                                    {t(
                                        "admin.leads.AddLead.addAddress.Lobby"
                                    )}
                                </label>
                                <input
                                    type="text"
                                    ref={lobby}
                                    className="form-control skyBorder"
                                    placeholder={t(
                                        "admin.leads.AddLead.addAddress.placeHolder.Lobby"
                                    )}
                                />
                                {/* {errors.lobby ? (
                                    <small className="text-danger mb-1">
                                        {errors.lobby}
                                    </small>
                                ) : (
                                    ""
                                )} */}
                            </div>
                            <div className="form-group d-flex align-items-center">
                                <label className="control-label navyblueColor" style={{ width: "15rem", fontWeight: "500", fontSize: "14px" }}>
                                    {t(
                                        "admin.leads.AddLead.addAddress.Key"
                                    )}
                                </label>
                                <input
                                    type="text"
                                    ref={key}
                                    className="form-control skyBorder"
                                    placeholder={t(
                                        "admin.leads.AddLead.addAddress.placeHolder.Key"
                                    )}
                                />
                                {/* {errors.key ? (
                                    <small className="text-danger mb-1">
                                        {errors.key}
                                    </small>
                                ) : (
                                    ""
                                )} */}
                            </div>
                            <div className="form-group d-flex align-items-center">
                                <label className="control-label navyblueColor" style={{ width: "15rem", fontWeight: "500", fontSize: "14px" }}>
                                    {t(
                                        "admin.leads.AddLead.addAddress.PreferedType"
                                    )}
                                </label>
                                <select
                                    ref={prefer_type}
                                    className="form-control skyBorder"
                                    name="prefer_type"
                                    defaultValue="default"
                                >
                                    <option value="default">
                                        {t(
                                            "admin.leads.AddLead.addAddress.Options.PreferedType.Default"
                                        )}
                                    </option>
                                    <option value="female">
                                        {t(
                                            "admin.leads.AddLead.addAddress.Options.PreferedType.Female"
                                        )}
                                    </option>
                                    <option value="male">
                                        {" "}
                                        {t(
                                            "admin.leads.AddLead.addAddress.Options.PreferedType.Male"
                                        )}
                                    </option>
                                    <option value="both">
                                        {" "}
                                        {t(
                                            "admin.leads.AddLead.addAddress.Options.PreferedType.Both"
                                        )}
                                    </option>
                                </select>
                            </div>
                            <div className="form-group d-flex align-items-center">
                                <div className="form-check form-switch pl-0">
                                    <label
                                        className="form-check-label custom-checkbox navyblueColor"
                                        htmlFor="isDogAvail"
                                        style={{ fontWeight: "500", fontSize: "14px" }}
                                    >
                                        <input
                                            ref={is_dog_avail}
                                            className="form-check-input"
                                            type="checkbox"
                                            id="isDogAvail"
                                            name="is_dog_avail"

                                        />
                                        <span className="checkmark"></span>

                                        {t(
                                            "admin.leads.AddLead.addAddress.IsDOG"
                                        )}
                                    </label>
                                </div>
                            </div>
                            <div className="form-group d-flex align-items-center">
                                <div className="form-check form-switch pl-0 ">
                                    <label
                                        className="form-check-label custom-checkbox navyblueColor"
                                        htmlFor="isCatAvail"
                                        style={{ fontWeight: "500", fontSize: "14px" }}
                                    >
                                        <input
                                            ref={is_cat_avail}
                                            className="form-check-input  skyBorder"
                                            type="checkbox"
                                            id="isCatAvail"
                                            name="is_cat_avail"
                                        />
                                        <span className="checkmark"></span>

                                        {t(
                                            "admin.leads.AddLead.addAddress.IsCat"
                                        )}
                                    </label>
                                </div>
                            </div>
                            <div className="form-group d-flex align-items-center">
                                <label className="control-label navyblueColor" style={{ width: "15rem", fontWeight: "500", fontSize: "14px" }}>
                                    {t(
                                        "admin.leads.AddLead.addAddress.NotAllowedWorkers"
                                    )}
                                </label>
                                <Select
                                    value={workers}
                                    name="workers"
                                    isMulti
                                    options={allWorkers}
                                    className="basic-multi-single w-100 skyBorder"
                                    isClearable={true}
                                    placeholder={t(
                                        "admin.leads.AddLead.addAddress.Options.pleaseSelect"
                                    )}
                                    classNamePrefix="select"
                                    onChange={(newValue) =>
                                        setWorkers(newValue)
                                    }
                                />
                            </div>
                        </div>
                    </div>
                    <input
                        type="hidden"
                        ref={addressId}
                        name="addressId"
                    />
                    <input type="hidden" ref={lat} name="lat" />
                    <input type="hidden" ref={long} name="long" />
                    <input type="hidden" ref={city} name="city" />
                    <input
                        type="hidden"
                        ref={client_id}
                        name="client_id"
                        defaultValue={params.id ? params.id : 0}
                    />
                </Modal.Body>
                <Modal.Footer
                    className="border-0"
                    style={{ padding: "1rem 2rem" }}
                >
                    <div className="bg-transparent">
                        <Button
                            type="button"
                            className="navyblue"
                            onClick={() => {
                                isAdd.current = true;
                                resetForm();
                                setModalStatus(false);
                            }}
                        >
                            {t("admin.leads.AddLead.addAddress.Close")}
                        </Button>
                    </div>
                    <div>
                        <Button
                            type="button"
                            onClick={(e) => handleAddress(e)}
                            className="navyblue"
                        >
                            {t("admin.leads.AddLead.addAddress.Save")}
                        </Button>
                    </div>
                </Modal.Footer>
            </Modal>
            <FullPageLoader visible={isLoading} />
        </div>
    );
}
