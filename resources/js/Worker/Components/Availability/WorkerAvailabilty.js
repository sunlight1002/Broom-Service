import React, { useState, useEffect, useTransition } from "react";
import { useAlert } from "react-alert";
import Moment from "moment";
import moment from "moment-timezone";
import { useTranslation } from "react-i18next";

export default function WorkerAvailabilty() {
    const [worker_aval, setWorkerAval] = useState({});
    const [errors, setErrors] = useState([]);
    const [interval, setTimeInterval] = useState([]);
    const [notAvailableDates, setNotAvailableDates] = useState([]);
    const [firstEditableDate, setFirstEditableDate] = useState(null);
    const alert = useAlert();
    const { t } = useTranslation();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };

    let handleChange = (event, w_date, slot) => {
        let newworker = { ...worker_aval };
        if (notAvailableDates.includes(w_date)) {
            alert.error("You Can't Select this Date");
            return false;
        }

        if (event.target.name.toString() === "true") {
            document
                .getElementById(event.target.id)
                .setAttribute("name", !event.target.checked);
            if (newworker[w_date] === undefined) {
                newworker[w_date] = [slot];
            } else {
                newworker[w_date] = [slot];
            }
        } else {
            document
                .getElementById(event.target.id)
                .setAttribute("name", !event.target.checked);
            let newarray = [];
            newworker[`${w_date}`].filter((e) => {
                if (e !== slot) {
                    newarray.push(e);
                }
            });
            newworker[w_date] = newarray;
        }
        setWorkerAval(newworker);
    };

    let handleSubmit = () => {
        axios
            .post(`/api/availabilities`, worker_aval, { headers })
            .then((response) => {
                if (response.data.errors) {
                    setErrors(response.data.errors);
                } else {
                    alert.success("Availabilty Updated Successfully");
                    getWorkerAvailabilty();
                }
            });
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
    const slot = [
        ["8am-16pm", "Full Day"],
        ["8am-12pm", "Morning"],
        ["12pm-16pm", "Afternoon"],
        ["16pm-20pm", "Evening"],
        ["20pm-24am", "Night"],
    ];

    const getWorkerAvailabilty = () => {
        axios.get(`/api/availabilities`, { headers }).then((response) => {
            if (response.data.availability) {
                setWorkerAval(response.data.availability);
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

    return (
        <div className="boxPanel">
            <ul className="nav nav-tabs" role="tablist">
                <li className="nav-item" role="presentation">
                    <a
                        id="current-week"
                        className="nav-link active"
                        data-toggle="tab"
                        href="#tab-current-week"
                        aria-selected="true"
                        role="tab"
                    >
                        {t("worker.schedule.c_week")}
                    </a>
                </li>
                <li className="nav-item" role="presentation">
                    <a
                        id="first-next-week"
                        className="nav-link"
                        data-toggle="tab"
                        href="#tab-first-next-week"
                        aria-selected="true"
                        role="tab"
                    >
                        {t("worker.schedule.n_week")}
                    </a>
                </li>
                <li className="nav-item" role="presentation">
                    <a
                        id="first-next-week"
                        className="nav-link"
                        data-toggle="tab"
                        href="#tab-first-next-next-week"
                        aria-selected="true"
                        role="tab"
                    >
                        Next Next Week
                    </a>
                </li>
            </ul>
            <div className="tab-content" style={{ background: "#fff" }}>
                <div
                    id="tab-current-week"
                    className="tab-pane active show"
                    role="tab-panel"
                    aria-labelledby="current-week"
                >
                    <div className="table-responsive">
                        <table className="timeslots table">
                            <thead>
                                <tr>
                                    {week.map((element, index) => (
                                        <th key={index}>
                                            {moment(element)
                                                .toString()
                                                .slice(0, 15)}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {slot.map((s, _sIndex) => (
                                    <tr key={_sIndex}>
                                        {week.map((w, _wIndex) => {
                                            const isDisabled =
                                                Moment(w).isBefore(
                                                    firstEditableDate
                                                );

                                            return (
                                                <td key={_wIndex}>
                                                    {!notAvailableDates.includes(
                                                        w
                                                    ) && (
                                                        <div className={w}>
                                                            <label>
                                                                <input
                                                                    type="checkbox"
                                                                    data-day="Sunday"
                                                                    className="btn-check"
                                                                    id={
                                                                        w +
                                                                        "-" +
                                                                        s["0"]
                                                                    }
                                                                    data-value={
                                                                        w
                                                                    }
                                                                    value={
                                                                        s["0"]
                                                                    }
                                                                    disabled={
                                                                        isDisabled
                                                                    }
                                                                    onChange={(
                                                                        e
                                                                    ) =>
                                                                        handleChange(
                                                                            e,
                                                                            w,
                                                                            s[
                                                                                "0"
                                                                            ]
                                                                        )
                                                                    }
                                                                    name={(worker_aval[
                                                                        `${w}`
                                                                    ] !==
                                                                    undefined
                                                                        ? !worker_aval[
                                                                              `${w}`
                                                                          ].includes(
                                                                              s[
                                                                                  "0"
                                                                              ]
                                                                          )
                                                                        : true
                                                                    ).toString()}
                                                                />
                                                                <span
                                                                    className={
                                                                        `forcustom` +
                                                                        (worker_aval[
                                                                            `${w}`
                                                                        ] &&
                                                                        worker_aval[
                                                                            `${w}`
                                                                        ].includes(
                                                                            s[
                                                                                "0"
                                                                            ]
                                                                        )
                                                                            ? ` checked_forcustom`
                                                                            : "") +
                                                                        (isDisabled
                                                                            ? ` not-allowed-date`
                                                                            : "")
                                                                    }
                                                                >
                                                                    {s["1"]}
                                                                </span>
                                                            </label>
                                                        </div>
                                                    )}
                                                </td>
                                            );
                                        })}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
                <div
                    id="tab-first-next-week"
                    className="tab-pane"
                    role="tab-panel"
                    aria-labelledby="first-next-week"
                >
                    <div className="table-responsive">
                        <table className="timeslots table">
                            <thead>
                                <tr>
                                    {nextweek.map((element, _nIndex) => (
                                        <th key={_nIndex}>
                                            {moment(element)
                                                .toString()
                                                .slice(0, 15)}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {slot.map((s, _sIndex) => (
                                    <tr key={_sIndex}>
                                        {nextweek.map((w, _nIndex) => {
                                            const isDisabled =
                                                Moment(w).isBefore(
                                                    firstEditableDate
                                                );

                                            return (
                                                <td key={_nIndex}>
                                                    {!notAvailableDates.includes(
                                                        w
                                                    ) && (
                                                        <div className={w}>
                                                            <label>
                                                                <input
                                                                    type="checkbox"
                                                                    data-day="Sunday"
                                                                    className="btn-check"
                                                                    id={
                                                                        w +
                                                                        "-" +
                                                                        s["0"]
                                                                    }
                                                                    data-value={
                                                                        w
                                                                    }
                                                                    value={
                                                                        s["0"]
                                                                    }
                                                                    disabled={
                                                                        isDisabled
                                                                    }
                                                                    onChange={(
                                                                        e
                                                                    ) =>
                                                                        handleChange(
                                                                            e,
                                                                            w,
                                                                            s[
                                                                                "0"
                                                                            ]
                                                                        )
                                                                    }
                                                                    name={(worker_aval[
                                                                        `${w}`
                                                                    ] !==
                                                                    undefined
                                                                        ? !worker_aval[
                                                                              `${w}`
                                                                          ].includes(
                                                                              s[
                                                                                  "0"
                                                                              ]
                                                                          )
                                                                        : true
                                                                    ).toString()}
                                                                />
                                                                <span
                                                                    className={
                                                                        `forcustom` +
                                                                        (worker_aval[
                                                                            `${w}`
                                                                        ] &&
                                                                        worker_aval[
                                                                            `${w}`
                                                                        ].includes(
                                                                            s[
                                                                                "0"
                                                                            ]
                                                                        )
                                                                            ? ` checked_forcustom`
                                                                            : "") +
                                                                        (isDisabled
                                                                            ? ` not-allowed-date`
                                                                            : "")
                                                                    }
                                                                >
                                                                    {s["1"]}
                                                                </span>
                                                            </label>
                                                        </div>
                                                    )}
                                                </td>
                                            );
                                        })}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
                <div
                    id="tab-first-next-next-week"
                    className="tab-pane"
                    role="tab-panel"
                    aria-labelledby="first-next-week"
                >
                    <div className="table-responsive">
                        <table className="timeslots table">
                            <thead>
                                <tr>
                                    {nextnextweek.map((element, _nnIndex) => (
                                        <th key={_nnIndex}>
                                            {moment(element)
                                                .toString()
                                                .slice(0, 15)}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {slot.map((s, _sIndex) => (
                                    <tr key={_sIndex}>
                                        {nextnextweek.map((w, _nnIndex) => {
                                            const isDisabled =
                                                Moment(w).isBefore(
                                                    firstEditableDate
                                                );

                                            return (
                                                <td key={_nnIndex}>
                                                    {!notAvailableDates.includes(
                                                        w
                                                    ) && (
                                                        <div className={w}>
                                                            <label>
                                                                <input
                                                                    type="checkbox"
                                                                    data-day="Sunday"
                                                                    className="btn-check"
                                                                    id={
                                                                        w +
                                                                        "-" +
                                                                        s["0"]
                                                                    }
                                                                    data-value={
                                                                        w
                                                                    }
                                                                    value={
                                                                        s["0"]
                                                                    }
                                                                    disabled={
                                                                        isDisabled
                                                                    }
                                                                    onChange={(
                                                                        e
                                                                    ) =>
                                                                        handleChange(
                                                                            e,
                                                                            w,
                                                                            s[
                                                                                "0"
                                                                            ]
                                                                        )
                                                                    }
                                                                    name={(worker_aval[
                                                                        `${w}`
                                                                    ] !==
                                                                    undefined
                                                                        ? !worker_aval[
                                                                              `${w}`
                                                                          ].includes(
                                                                              s[
                                                                                  "0"
                                                                              ]
                                                                          )
                                                                        : true
                                                                    ).toString()}
                                                                />
                                                                <span
                                                                    className={
                                                                        `forcustom` +
                                                                        (worker_aval[
                                                                            `${w}`
                                                                        ] &&
                                                                        worker_aval[
                                                                            `${w}`
                                                                        ].includes(
                                                                            s[
                                                                                "0"
                                                                            ]
                                                                        )
                                                                            ? ` checked_forcustom`
                                                                            : "") +
                                                                        (isDisabled
                                                                            ? ` not-allowed-date`
                                                                            : "")
                                                                    }
                                                                >
                                                                    {s["1"]}
                                                                </span>
                                                            </label>
                                                        </div>
                                                    )}
                                                </td>
                                            );
                                        })}
                                    </tr>
                                ))}
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
