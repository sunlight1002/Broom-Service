import React, { useState, useEffect, useMemo, useRef } from "react";
import { useAlert } from "react-alert";
import { useParams } from "react-router-dom";
import Flatpickr from "react-flatpickr";
import "flatpickr/dist/flatpickr.css";
import Moment from "moment";
import moment from "moment-timezone";
import { useTranslation } from "react-i18next";

import WeekCard from "./Components/WeekCard";
import TimeSlot from "./Components/TimeSlot";
import { createHourlyTimeArray } from "../../../Utils/job.utils";


export default function WorkerAvailabilty({ days }) {

    const { t } = useTranslation();
    const tabList = [
        {
            key: "current-week",
            label: t("worker.jobs.current_week"),
        },
        {
            key: "first-next-week",
            label: t("global.nextweek"),
        },
        {
            key: "first-next-next-week",
            label: t("global.next")+t("global.to")+ t("global.nextweek"),
        },
    ];
    
    const [notAvailableDates, setNotAvailableDates] = useState([]);
    const [timeSlots, setTimeSlots] = useState([]);
    const [activeTab, setActiveTab] = useState(t("worker.jobs.current_week"));
    const [defaultTimeSlots, setDefaultTimeSlots] = useState([]);
    const [formValues, setFormValues] = useState({
        default_repeatancy: "forever",
        default_until_date: null,
        custom_start_date: null,
        custom_end_date: null,
    });
    const [customRange, setCustomRange] = useState([]);

    const params = useParams();
    const alert = useAlert();
    const flatpickrRef = useRef(null);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const weekDays = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

    const slots = useMemo(() => {
        return createHourlyTimeArray("08:00", "24:00");
    }, []);

    const calendarMinDate = useMemo(() => {
        return moment().startOf("week").add(3, "week").format("YYYY-MM-DD");
    }, []);

    const generateWeek = (startDate) => {
        let week = [];
        let today = moment().startOf("day"); // Get the current date at the start of the day
        days.forEach((d) => {
            let day = moment(startDate).add(d, "days");
            if (day.isSameOrAfter(today)) {
                // Check if the day is greater than or equal to today
                week.push(day.format("YYYY-MM-DD"));
            }
        });
        return week;
    };

    const sundayOfCurrentWeek = moment().startOf("week");

    let week = generateWeek(sundayOfCurrentWeek);
    let nextweek = generateWeek(sundayOfCurrentWeek.add(1, "weeks"));
    let nextnextweek = generateWeek(sundayOfCurrentWeek.add(1, "weeks"));

    const getWorkerAvailabilty = () => {
        axios
            .get(`/api/admin/worker_availability/${params.id}`, { headers })
            .then((response) => {
                if (response.data.data) {
                    const next2WeekLastDate = moment()
                        .endOf("week")
                        .add(2, "week");

                    let _timeslots = {};
                    let _custom_start_date = null;
                    let _custom_end_date = null;
                    for (var key in response.data.data.regular) {
                        _timeslots[key] = response.data.data.regular[key].map(
                            (i) => {
                                return {
                                    start_time: i.start_time.slice(0, -3),
                                    end_time: i.end_time.slice(0, -3),
                                };
                            }
                        );

                        if (next2WeekLastDate.isBefore(moment(key))) {
                            if (!_custom_start_date) {
                                _custom_start_date = key;
                            }

                            _custom_end_date = key;
                        }
                    }

                    setTimeSlots(_timeslots);

                    if (_custom_start_date && _custom_end_date) {
                        setFormValues({
                            ...formValues,
                            custom_start_date: _custom_start_date,
                            custom_end_date: _custom_end_date,
                        });

                        const startDate = Moment(_custom_start_date);
                        const endDate = Moment(_custom_end_date);

                        const diffInDays = endDate.diff(startDate, "days");

                        let _currentDate = startDate;
                        let _customRange = [];
                        for (let i = 0; i <= diffInDays; i++) {
                            _customRange.push(
                                _currentDate.format("YYYY-MM-DD")
                            );
                            _currentDate = _currentDate.add(1, "day");
                        }
                        setCustomRange(_customRange);
                    }

                    const _defaultAvailSlots = response.data.data.default;
                    let _defaultSlots = {};

                    const _defaultAvailSlotsKeys =
                        Object.keys(_defaultAvailSlots);
                    if (_defaultAvailSlotsKeys.length) {
                        setFormValues((values) => {
                            return {
                                ...values,
                                default_until_date:
                                    _defaultAvailSlots[
                                        _defaultAvailSlotsKeys[0]
                                    ][0].until_date,
                            };
                        });

                        for (const [key, value] of Object.entries(
                            _defaultAvailSlots
                        )) {
                            const _defaultWeekDaySlots = _defaultAvailSlots[
                                key
                            ].map((i) => {
                                return {
                                    start_time: i.start_time.slice(0, -3),
                                    end_time: i.end_time.slice(0, -3),
                                };
                            });

                            _defaultSlots[key] = _defaultWeekDaySlots;
                        }
                    }

                    setDefaultTimeSlots(_defaultSlots);
                }
            });
    };

    const getDates = () => {
        axios
            .post(
                `/api/admin/get-not-available-dates`,
                { id: parseInt(params.id) },
                { headers }
            )
            .then((res) => {
                setNotAvailableDates(res.data.dates.map((i) => i.date));
            });
    };

    const handleTab = (tabKey) => {
        setActiveTab(tabKey);
    };

    let handleSubmit = (e) => {
        e.preventDefault();
        if (!Object.values(timeSlots).length) {
            return false;
        }

        if (
            formValues.default_repeatancy == "until_date" &&
            !formValues.default_until_date
        ) {
            alert.error("Default until date not selected");
            return false;
        }

        axios
            .post(
                `/api/admin/update_availability/${params.id}`,
                {
                    time_slots: timeSlots,
                    default: {
                        time_slots: defaultTimeSlots,
                        until_date: formValues.default_until_date,
                    },
                },
                {
                    headers,
                }
            )
            .then((response) => {
                alert.success("Worker availabilty updated successfully");
                getWorkerAvailabilty();
            })
            .catch((err) => alert.error("Something went wrong!"));
    };

    const handleCustomDateSelect = (selectedDates, dateStr) => {
        if (selectedDates.length == 2) {
            const _dates = dateStr.split(" to ");

            setFormValues({
                ...formValues,
                custom_start_date: _dates[0],
                custom_end_date: _dates[1],
            });

            const startDate = Moment(selectedDates[0]);
            const endDate = Moment(selectedDates[1]);

            const diffInDays = endDate.diff(startDate, "days");

            let _currentDate = startDate;
            let _customRange = [];
            for (let i = 0; i <= diffInDays; i++) {
                _customRange.push(_currentDate.format("YYYY-MM-DD"));
                _currentDate = _currentDate.add(1, "day");
            }
            setCustomRange(_customRange);
        }
    };

    useEffect(() => {
        getWorkerAvailabilty();
        getDates();
    }, []);

    useEffect(() => {
        if (formValues.default_repeatancy == "forever") {
            setFormValues({
                ...formValues,
                default_until_date: null,
            });
        }
    }, [formValues.default_repeatancy]);

    return (
        <div className="boxPanel">
            <ul className="nav nav-tabs" role="tablist">
                {tabList.map((t) => {
                    return (
                        <li
                            className="nav-item"
                            role="presentation"
                            key={t.key}
                        >
                            <a
                                href="#"
                                className={
                                    "nav-link" +
                                    (activeTab == t.key ? " active" : "")
                                }
                                aria-selected="true"
                                role="tab"
                                onClick={() => handleTab(t.key)}
                            >
                                {t.label}
                            </a>
                        </li>
                    );
                })}
                <li className="nav-item" role="presentation">
                    <a
                        href="#"
                        className={
                            "nav-link" +
                            (activeTab == "custom" ? " active" : "")
                        }
                        aria-selected="true"
                        role="tab"
                        onClick={() => handleTab("custom")}
                    >
                        {t("worker.schedule.custom")}
                    </a>
                </li>
                <li className="nav-item" role="presentation">
                    <a
                        href="#"
                        className={
                            "nav-link" +
                            (activeTab == "default" ? " active" : "")
                        }
                        aria-selected="true"
                        role="tab"
                        onClick={() => handleTab("default")}
                    >
                         {t("worker.schedule.default")}
                    </a>
                </li>
            </ul>
            <div className="tab-content" style={{ background: "#fff" }}>
                {tabList
                    .filter((t) => activeTab == t.key)
                    .map((t) => {
                        const weekArr =
                            t.key === "current-week"
                                ? week
                                : t.key === "first-next-week"
                                ? nextweek
                                : nextnextweek;
                        return (
                            <WeekCard
                                key={t.key}
                                tabName={t.label}
                                week={weekArr}
                                slots={slots}
                                setTimeSlots={setTimeSlots}
                                timeSlots={timeSlots}
                                notAvailableDates={notAvailableDates}
                            />
                        );
                    })}

                <div
                    className={
                        "tab-pane " +
                        (activeTab == "custom" ? "active show" : "")
                    }
                    role="tab-panel"
                    aria-labelledby="Custom"
                >
                    <div className="offset-sm-4 col-sm-4">
                        <div className="form-group">
                            <label className="control-label">
                            {t("client.jobs.change.selectDate")}
                            </label>
                            <Flatpickr
                                name="date"
                                className="form-control"
                                onChange={(
                                    selectedDates,
                                    dateStr,
                                    instance
                                ) => {
                                    handleCustomDateSelect(
                                        selectedDates,
                                        dateStr
                                    );
                                }}
                                value={[
                                    formValues.custom_start_date,
                                    formValues.custom_end_date,
                                ]}
                                options={{
                                    disableMobile: true,
                                    minDate: calendarMinDate,
                                    mode: "range",
                                }}
                            />
                        </div>
                    </div>

                    <div className="table-responsive">
                        <table className="timeslots table">
                            <thead>
                                <tr>
                                    {customRange.map((element, index) => (
                                        <th key={index}>
                                            {moment(element)
                                                .toString()
                                                .slice(0, 15)}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    {customRange.map((w, _wIndex) => {
                                        return (
                                            <td key={_wIndex}>
                                                {!notAvailableDates.includes(
                                                    w
                                                ) && (
                                                    <TimeSlot
                                                        clsName={w}
                                                        slots={slots}
                                                        setTimeSlots={
                                                            setTimeSlots
                                                        }
                                                        timeSlots={timeSlots}
                                                    />
                                                )}
                                            </td>
                                        );
                                    })}
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div
                    className={
                        "tab-pane " +
                        (activeTab == "default" ? "active show" : "")
                    }
                    role="tab-panel"
                    aria-labelledby="Default"
                >
                    <div className="table-responsive">
                        <table className="timeslots table">
                            <thead>
                                <tr>
                                    {[...Array(7).keys()].map(
                                        (element, index) => (
                                            <th key={index}>
                                                {weekDays[element]}
                                            </th>
                                        )
                                    )}
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    {[...Array(7).keys()].map((w, _wIndex) => {
                                        return (
                                            <td key={_wIndex}>
                                                <TimeSlot
                                                    clsName={w}
                                                    slots={slots}
                                                    setTimeSlots={
                                                        setDefaultTimeSlots
                                                    }
                                                    timeSlots={defaultTimeSlots}
                                                    isDisabled={false}
                                                />
                                            </td>
                                        );
                                    })}
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div className="row">
                        <div className="offset-sm-4 col-sm-4">
                            <div className="form-group">
                                <label className="control-label">
                                {t("client.jobs.change.Repeatancy")}
                                </label>
                                <select
                                    className="form-control"
                                    value={formValues.default_repeatancy}
                                    onChange={(e) => {
                                        setFormValues({
                                            ...formValues,
                                            default_repeatancy: e.target.value,
                                        });
                                    }}
                                >
                                    <option value="">{t("global.select_default_option")}</option>
                                    <option value="forever">{t("client.jobs.change.Forever")}</option>
                                    <option value="until_date">
                                    {t("client.jobs.CancelModal.options.UntilDate")}
                                    </option>
                                </select>
                            </div>

                            {formValues.default_repeatancy == "until_date" && (
                                <div className="form-group">
                                    <label className="control-label">
                                    {t("client.jobs.CancelModal.options.UntilDate")}
                                    </label>
                                    <Flatpickr
                                        name="date"
                                        className="form-control"
                                        onChange={(
                                            selectedDates,
                                            dateStr,
                                            instance
                                        ) => {
                                            setFormValues({
                                                ...formValues,
                                                default_until_date: dateStr,
                                            });
                                        }}
                                        value={formValues.default_until_date}
                                        options={{
                                            disableMobile: true,
                                        }}
                                        ref={flatpickrRef}
                                    />
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
            <div className="text-center mt-3">
                <input
                    type="button"
                    value="Update availabilities"
                    className="btn navyblue"
                    onClick={handleSubmit}
                />
            </div>
        </div>
    );
}
