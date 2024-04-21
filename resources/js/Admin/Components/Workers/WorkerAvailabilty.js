import React, { useState, useEffect, useMemo } from "react";
import { useAlert } from "react-alert";
import { useParams } from "react-router-dom";
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

export default function WorkerAvailabilty({ interval }) {
    const [notAvailableDates, setNotAvailableDates] = useState([]);
    const [tab, setTab] = useState(tabList);
    const [timeSlots, setTimeSlots] = useState([]);

    const params = useParams();
    const alert = useAlert();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
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
        axios
            .get(`/api/admin/worker_availability/${params.id}`, { headers })
            .then((response) => {
                if (response.data.data) {
                    let _timeslots = {};
                    for (var key in response.data.data) {
                        _timeslots[key] = response.data.data[key].map((i) => {
                            return {
                                start_time: i.start_time.slice(0, -3),
                                end_time: i.end_time.slice(0, -3),
                            };
                        });
                    }

                    setTimeSlots(_timeslots);
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
        const updatedTab = [...tabList].map((t) =>
            t.key === tabKey
                ? { ...t, ["isActive"]: true }
                : { ...t, ["isActive"]: false }
        );
        setTab(updatedTab);
    };

    let handleSubmit = (e) => {
        e.preventDefault();
        if (!Object.values(timeSlots).length) {
            return false;
        }

        axios
            .post(
                `/api/admin/update_availability/${params.id}`,
                {
                    time_slots: timeSlots,
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

    useEffect(() => {
        getWorkerAvailabilty();
        getDates();
    }, []);

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
                            />
                        );
                    })}
            </div>
            <div className="text-center mt-3">
                <input
                    type="button"
                    value="Update availabilities"
                    className="btn btn-pink"
                    onClick={handleSubmit}
                />
            </div>
        </div>
    );
}
