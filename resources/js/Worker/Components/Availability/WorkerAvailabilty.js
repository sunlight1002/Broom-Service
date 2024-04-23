import React, { useState, useEffect, useMemo, useRef } from "react";
import { useAlert } from "react-alert";
import { useTranslation } from "react-i18next";
import Flatpickr from "react-flatpickr";
import "flatpickr/dist/flatpickr.css";
import Moment from "moment";
import moment from "moment-timezone";

import WeekCard from "./Components/WeekCard";
import TimeSlot from "./Components/TimeSlot";
import { createHourlyTimeArray } from "../../../Utils/job.utils";

const tabList = [
    {
        key: "current-week",
        label: "Current Week",
    },
    {
        key: "first-next-week",
        label: "Next Week",
    },
    {
        key: "first-next-next-week",
        label: "Next To Next Week",
    },
];

export default function WorkerAvailabilty() {
    const [interval, setTimeInterval] = useState([]);
    const [notAvailableDates, setNotAvailableDates] = useState([]);
    const [firstEditableDate, setFirstEditableDate] = useState(null);
    const [timeSlots, setTimeSlots] = useState([]);
    const [activeTab, setActiveTab] = useState("current-week");
    const [defaultTimeSlots, setDefaultTimeSlots] = useState([]);
    const [formValues, setFormValues] = useState({
        default_until_date: null,
        custom_start_date: null,
        custom_end_date: null,
    });
    const [customRange, setCustomRange] = useState([]);

    const alert = useAlert();
    const { t } = useTranslation();
    const flatpickrRef = useRef(null);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };

    const slots = useMemo(() => {
        return createHourlyTimeArray("08:00", "24:00");
    }, []);

    const calendarMinDate = useMemo(() => {
        return moment().startOf("week").add(3, "week").format("YYYY-MM-DD");
    }, []);

    let curr = new Date();
    let week = [];
    let nextweek = [];
    let nextnextweek = [];
    for (let i = 0; i < 7; i++) {
        let first = curr.getDate() - curr.getDay() + i;
        if (first >= curr.getDate()) {
            if (!interval.includes(i)) {
                let day = new Date(curr.setDate(first))
                    .toISOString()
                    .slice(0, 10);
                week.push(day);
            }
        }
    }

    for (let i = 0; i < 7; i++) {
        if (!interval.includes(i)) {
            var today = new Date();
            var first = today.getDate() - today.getDay() + 7 + i;
            var firstday = new Date(today.setDate(first))
                .toISOString()
                .slice(0, 10);
            nextweek.push(firstday);
        }
    }
    for (let i = 0; i < 7; i++) {
        if (!interval.includes(i)) {
            var today = new Date();
            var first = today.getDate() - today.getDay() + 14 + i;
            var firstday = new Date(today.setDate(first))
                .toISOString()
                .slice(0, 10);
            nextnextweek.push(firstday);
        }
    }

    const getWorkerAvailabilty = () => {
        axios.get(`/api/availabilities`, { headers }).then((response) => {
            if (response.data.availability) {
                const next2WeekLastDate = moment().endOf("week").add(2, "week");

                let _timeslots = {};
                let _custom_start_date = null;
                let _custom_end_date = null;
                for (var key in response.data.availability.regular) {
                    _timeslots[key] = response.data.availability.regular[
                        key
                    ].map((i) => {
                        return {
                            start_time: i.start_time.slice(0, -3),
                            end_time: i.end_time.slice(0, -3),
                        };
                    });

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
                        _customRange.push(_currentDate.format("YYYY-MM-DD"));
                        _currentDate = _currentDate.add(1, "day");
                    }
                    setCustomRange(_customRange);
                }

                const _defaultAvailSlots = response.data.availability.default;
                let _defaultSlots = [];

                if (_defaultAvailSlots.length) {
                    setFormValues((values) => {
                        return {
                            ...values,
                            default_until_date:
                                _defaultAvailSlots[0].until_date,
                        };
                    });

                    _defaultSlots["default"] = _defaultAvailSlots.map((i) => {
                        return {
                            start_time: i.start_time.slice(0, -3),
                            end_time: i.end_time.slice(0, -3),
                        };
                    });
                }

                setDefaultTimeSlots(_defaultSlots);
            }
        });
    };

    const getTime = () => {
        axios.get(`/api/get-time`, { headers }).then((res) => {
            if (res.data.data) {
                let ar = JSON.parse(res.data.data.days);
                let ai = [];
                ar && ar.map((a, i) => ai.push(parseInt(a)));
                var hid = [0, 1, 2, 3, 4, 5, 6].filter(function (obj) {
                    return ai.indexOf(obj) == -1;
                });
                setTimeInterval(hid);
            }
        });
    };

    const getDates = () => {
        axios.get(`/api/not-available-dates`, { headers }).then((res) => {
            setNotAvailableDates(res.data.dates.map((i) => i.date));
        });
    };

    useEffect(() => {
        const isMondayPassed = Moment().day() > 1;

        if (isMondayPassed) {
            setFirstEditableDate(Moment().add(2, "w").startOf("w"));
        } else {
            setFirstEditableDate(Moment().add(1, "w").startOf("w"));
        }

        getWorkerAvailabilty();
        getTime();
        getDates();
    }, []);

    const handleTab = (tabKey) => {
        setActiveTab(tabKey);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (!Object.values(timeSlots).length) {
            return false;
        }

        axios
            .post(
                `/api/availabilities`,
                {
                    time_slots: timeSlots,
                    default: {
                        time_slots: defaultTimeSlots.default,
                        until_date: formValues.default_until_date,
                    },
                },
                { headers }
            )
            .then((res) => {
                alert.success(res.data.message);
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
                        Custom
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
                        Default
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
                                firstEditableDate={firstEditableDate}
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
                                Select Date Range
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
                            <tbody>
                                <tr>
                                    <td>
                                        <div className="offset-sm-4 col-sm-4">
                                            <div className="form-group">
                                                <label className="control-label">
                                                    Until Date
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
                                                            default_until_date:
                                                                dateStr,
                                                        });
                                                    }}
                                                    value={
                                                        formValues.default_until_date
                                                    }
                                                    options={{
                                                        disableMobile: true,
                                                    }}
                                                    ref={flatpickrRef}
                                                />
                                            </div>

                                            <TimeSlot
                                                clsName="default"
                                                slots={slots}
                                                setTimeSlots={
                                                    setDefaultTimeSlots
                                                }
                                                timeSlots={defaultTimeSlots}
                                                isDisabled={false}
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div className="text-center mt-3">
                <input
                    type="button"
                    value={t("worker.schedule.update")}
                    className="btn btn-primary"
                    onClick={handleSubmit}
                />
            </div>
        </div>
    );
}
