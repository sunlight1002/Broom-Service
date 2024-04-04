import { useState, useEffect } from "react";
import { useTranslation } from "react-i18next";
import { useAlert } from "react-alert";
import moment from "moment-timezone";
import { useParams, useNavigate, Link } from "react-router-dom";
import WeekCard from "./Components/WeekCard";

const slots = [
    "08:00 AM",
    "08:30 AM",
    "09:00 AM",
    "09:30 AM",
    "10:00 AM",
    "10:30 AM",
    "11:00 AM",
    "11:30 AM",
    "12:00 PM",
    "12:30 PM",
    "01:00 PM",
    "01:30 PM",
    "02:00 PM",
    "02:30 PM",
    "03:00 PM",
    "03:30 PM",
    "04:00 PM",
    "04:30 PM",
    "05:00 PM",
    "05:30 PM",
    "06:00 PM",
    "06:30 PM",
    "07:00 PM",
    "07:30 PM",
    "08:00 PM",
    "08:30 PM",
    "09:00 PM",
    "09:30 PM",
    "10:00 PM",
    "10:30 PM",
    "11:00 PM",
    "11:30 PM",
    "12:00 AM",
];

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

const AvailabilityForm = () => {
    const [interval, setTimeInterval] = useState([]);
    const [tab, setTab] = useState(tabList);
    const [timeSlots, setTimeSlots] = useState([]);
    const param = useParams();
    const alert = useAlert();
    const { t } = useTranslation();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };
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
    const getTime = () => {
        axios.get(`/api/admin/get-time`, { headers }).then((res) => {
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
    const getTeamAvailabilityTime = () => {
        axios
            .get(`/api/admin/teams/availability/${param.id}`, { headers })
            .then((res) => {
                if (res.data && res.data.data && res.data.data.time_slots) {
                    setTimeSlots(JSON.parse(res.data.data.time_slots));
                }
            })
            .catch((err) => alert.error("Something went wrong!"));
    };
    useEffect(() => {
        getTime();
        getTeamAvailabilityTime();
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
        const timeArr = JSON.stringify(timeSlots);
        const teamId = param.id;
        let data = {
            time_slots: timeArr,
            teamId: teamId,
        };
        axios
            .post(`/api/admin/teams/update-availability`, data, { headers })
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
                            />
                        );
                    })}
            </div>
            <div className="text-center mt-3">
                <input
                    type="button"
                    value={"Update Availability"}
                    className="btn btn-primary"
                    onClick={handleSubmit}
                />
            </div>
        </div>
    );
};

export default AvailabilityForm;
