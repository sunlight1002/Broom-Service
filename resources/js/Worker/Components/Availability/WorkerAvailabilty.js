import React, { useState, useEffect, useTransition, useMemo } from "react";
import { useAlert } from "react-alert";
import Moment from "moment";
import moment from "moment-timezone";
import { useTranslation } from "react-i18next";

import WeekCard from "./Components/WeekCard";
import { createHourlyTimeArray } from "../../../Utils/job.utils";

const tabList = [
    {
        key: "current-week",
        label: "Current Week",
        isActive: true,
    },
    {
        key: "first-next-week",
        label: "Next Week",
        isActive: false,
    },
    {
        key: "first-next-next-week",
        label: "Next To Next Week",
        isActive: false,
    },
];

export default function WorkerAvailabilty() {
    const [interval, setTimeInterval] = useState([]);
    const [notAvailableDates, setNotAvailableDates] = useState([]);
    const [firstEditableDate, setFirstEditableDate] = useState(null);
    const [tab, setTab] = useState(tabList);
    const [timeSlots, setTimeSlots] = useState([]);
    const alert = useAlert();
    const { t } = useTranslation();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };

    const slots = useMemo(() => {
        return createHourlyTimeArray("08:00", "24:00");
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
                let _timeslots = {};
                for (var key in response.data.availability) {
                    _timeslots[key] = response.data.availability[key].map(
                        (i) => {
                            return {
                                start_time: i.start_time.slice(0, -3),
                                end_time: i.end_time.slice(0, -3),
                            };
                        }
                    );
                }

                setTimeSlots(_timeslots);
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
        const updatedTab = [...tabList].map((t) =>
            t.key === tabKey
                ? { ...t, ["isActive"]: true }
                : { ...t, ["isActive"]: false }
        );
        setTab(updatedTab);
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
                },
                { headers }
            )
            .then((res) => {
                alert.success(res.data.message);
            })
            .catch((err) => alert.error("Something went wrong!"));
    };

    return (
        <div className="boxPanel">
            <ul className="nav nav-tabs" role="tablist">
                {tab.map((t) => {
                    return (
                        <li
                            className="nav-item"
                            role="presentation"
                            key={t.key}
                        >
                            <a
                                href="#"
                                className={
                                    "nav-link" + (t.isActive ? " active" : "")
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
            </ul>
            <div className="tab-content" style={{ background: "#fff" }}>
                {tab
                    .filter((t) => t.isActive)
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
