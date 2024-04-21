import React, { useState, useEffect, useRef } from "react";
import moment from "moment-timezone";
import { useNavigate, useParams } from "react-router-dom";
import { useAlert } from "react-alert";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import Swal from "sweetalert2";
import Flatpickr from "react-flatpickr";
import "flatpickr/dist/flatpickr.css";

import { filterShiftOptions } from "../../../Utils/job.utils";

export default function ChangeWorkerCalender({ job }) {
    const params = useParams();
    const navigate = useNavigate();
    const alert = useAlert();
    const [workerData, setWorkerData] = useState([]);
    const [AllWorkers, setAllWorkers] = useState([]);
    const [interval, setTimeInterval] = useState([]);
    const [data, setData] = useState([]);
    const [formValues, setFormValues] = useState({
        repeatancy: "one_time",
        until_date: null,
    });
    const [minUntilDate, setMinUntilDate] = useState(null);

    const flatpickrRef = useRef(null);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

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

    const getWorkers = () => {
        axios
            .get(`/api/admin/all-workers`, {
                headers,
                params: {
                    filter: true,
                    service_id: job.jobservice.service_id,
                    has_cat: job.property_address.is_cat_avail,
                    has_dog: job.property_address.is_dog_avail,
                    prefer_type: job.property_address.prefer_type,
                    ignore_worker_ids: job.worker_id,
                },
            })
            .then((res) => {
                setAllWorkers(res.data.workers);
            });
    };

    useEffect(() => {
        getTime();
        getWorkers();
    }, []);

    useEffect(() => {
        setMinUntilDate(
            moment().startOf("day").add(1, "day").format("YYYY-MM-DD")
        );
    }, []);

    const handleSubmit = () => {
        if (!formValues.repeatancy) {
            alert.error("The Repeatancy is missing");
            return false;
        }

        if (formValues.repeatancy == "until_date" && !formValues.until_date) {
            alert.error("The Until Date is missing");
            return false;
        }

        let formdata = {
            worker: data[0],
            repeatancy: formValues.repeatancy,
            until_date: formValues.until_date,
        };
        let viewbtn = document.querySelectorAll(".viewBtn");
        if (data.length > 0) {
            viewbtn[0].setAttribute("disabled", true);
            viewbtn[0].value = "please wait ...";

            axios
                .post(`/api/admin/jobs/${params.id}/change-worker`, formdata, {
                    headers,
                })
                .then((res) => {
                    alert.success(res.data.message);
                    setTimeout(() => {
                        navigate("/admin/jobs");
                    }, 1000);
                })
                .catch((e) => {
                    Swal.fire({
                        title: "Error!",
                        text: e.response.data.message,
                        icon: "error",
                    });
                });
        } else {
            viewbtn[0].removeAttribute("disabled");
            viewbtn[0].value = "View Job";
            alert.error("Please Select the Workers");
        }
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

    const changeShift = (w_id, date, e) => {
        if (
            data.length === 0 ||
            (data.length > 0 &&
                data[0].worker_id == w_id &&
                data[0].date == date)
        ) {
            setWorkerData([...workerData, { ...e, w_id, date }]);

            e = [
                ...workerData.filter((d) => d.date == date && d.w_id == w_id),
                { ...e, w_id, date },
            ];

            const w_n = $("#worker-" + w_id).html();

            const filtered = data.filter((d) => {
                return !(d.date == date && d.worker_id == w_id);
            });

            const shifts = e.map((v) => `${v.start}-${v.end}`).join(",");

            var newdata;
            if (shifts != "") {
                newdata = [
                    ...filtered,
                    {
                        worker_id: w_id,
                        worker_name: w_n,
                        date: date,
                        shifts: shifts,
                    },
                ];
            } else {
                newdata = [...filtered];
            }
            setData(newdata);
        } else {
            Swal.fire({
                title: "Error!",
                text: "You can't select multiple workers and multiple date",
                icon: "error",
            });
        }
    };

    const removeShift = (w_id, date, e) => {
        const filtered = data.find((d) => {
            return d.date == date && d.worker_id == w_id;
        });

        if (filtered) {
            const _shifts = filtered.shifts.split(",") ?? [];

            const _index = _shifts.indexOf(`${e.start}-${e.end}`);
            if (_index !== -1) {
                _shifts.splice(_index, 1);
                const tmpworker = [...workerData];

                const indexWorker = tmpworker.findIndex((item) => {
                    return (
                        item.date === date &&
                        item.w_id === w_id &&
                        item.start === e.start &&
                        item.end === e.end
                    );
                });

                if (indexWorker !== -1) {
                    tmpworker.splice(indexWorker, 1);
                    setWorkerData(tmpworker);
                }

                if (_shifts.length > 0) {
                    setData((oldData) =>
                        oldData.map((item) => {
                            if (item.date === date && item.worker_id === w_id) {
                                return { ...item, shifts: _shifts.join(",") };
                            } else {
                                return item;
                            }
                        })
                    );
                } else {
                    setData((oldData) =>
                        oldData.filter(
                            (item) =>
                                item.date !== date && item.worker_id !== w_id
                        )
                    );
                }
            }
        }
    };

    const hasActive = (w_id, date, e) => {
        const filtered = data.find((d) => {
            return d.date == date && d.worker_id == w_id;
        });

        if (filtered) {
            const _shifts = filtered.shifts.split(",") ?? [];

            return _shifts.includes(`${e.start}-${e.end}`);
        }

        return false;
    };

    return (
        <>
            <ul className="nav nav-tabs" role="tablist">
                <li className="nav-item" role="presentation">
                    <a
                        id="worker-availability"
                        className="nav-link active"
                        data-toggle="tab"
                        href="#tab-worker-availability"
                        aria-selected="true"
                        role="tab"
                    >
                        Current Week
                    </a>
                </li>
                <li className="nav-item" role="presentation">
                    <a
                        id="current-job"
                        className="nav-link"
                        data-toggle="tab"
                        href="#tab-current-job"
                        aria-selected="true"
                        role="tab"
                    >
                        Next Week
                    </a>
                </li>
                <li className="nav-item" role="presentation">
                    <a
                        id="current-next-job"
                        className="nav-link"
                        data-toggle="tab"
                        href="#tab-current-next-job"
                        aria-selected="true"
                        role="tab"
                    >
                        Next Next Week
                    </a>
                </li>
            </ul>
            <div className="tab-content" style={{ background: "#fff" }}>
                <div
                    id="tab-worker-availability"
                    className="tab-pane active show  table-responsive"
                    role="tab-panel"
                    aria-labelledby="current-job"
                >
                    <div className="crt-jb-table-scrollable">
                        <Table className="table table-bordered crt-jb-wrap">
                            <Thead>
                                <Tr>
                                    <Th>Worker</Th>
                                    {week.map((element, index) => (
                                        <Th key={index}>
                                            {moment(element)
                                                .toString()
                                                .slice(0, 15)}
                                        </Th>
                                    ))}
                                </Tr>
                            </Thead>
                            <Tbody>
                                {AllWorkers.map((w, index) => {
                                    let aval = w.aval ? w.aval : [];
                                    let wjobs = w.wjobs ? w.wjobs : [];
                                    let fullname =
                                        w.firstname + " " + w.lastname;

                                    const shiftFreezeTime = {
                                        start: w.freeze_shift_start_time,
                                        end: w.freeze_shift_end_time,
                                    };

                                    const notAvailableDates =
                                        w.not_available_dates;

                                    return (
                                        <Tr key={index}>
                                            <Td>
                                                <span
                                                    id={`worker-${w.id}`}
                                                    className="d-flex align-items-center justify-content-center"
                                                >
                                                    {fullname}
                                                </span>
                                            </Td>
                                            {week.map((element, index) => {
                                                let shifts = wjobs[element]
                                                    ? wjobs[element].split(",")
                                                    : [];
                                                let sav =
                                                    shifts.length > 0
                                                        ? filterShiftOptions(
                                                              aval[element],
                                                              shifts,
                                                              shiftFreezeTime
                                                          )
                                                        : [];

                                                let list =
                                                    shifts.length > 0
                                                        ? true
                                                        : false;
                                                const isDateAvailable =
                                                    !notAvailableDates.includes(
                                                        element
                                                    );

                                                return (
                                                    <Td key={index}>
                                                        <div>
                                                            {shifts.map(
                                                                (s, i) => {
                                                                    return (
                                                                        <div
                                                                            className="text-success p-2 bg-light border-bottom"
                                                                            key={
                                                                                i
                                                                            }
                                                                        >
                                                                            {s}
                                                                        </div>
                                                                    );
                                                                }
                                                            )}

                                                            {isDateAvailable &&
                                                            aval[element] &&
                                                            aval[element] !=
                                                                "" ? (
                                                                filterShiftOptions(
                                                                    aval[
                                                                        element
                                                                    ],
                                                                    shifts,
                                                                    shiftFreezeTime
                                                                ).map(
                                                                    (
                                                                        shift,
                                                                        _sIdx
                                                                    ) => {
                                                                        const isActive =
                                                                            hasActive(
                                                                                w.id,
                                                                                element,
                                                                                shift
                                                                            );

                                                                        return (
                                                                            <div
                                                                                className={`d-flex justify-content-between p-2 border-bottom align-items-center  ${
                                                                                    isActive
                                                                                        ? "bg-primary"
                                                                                        : ""
                                                                                }`}
                                                                                onClick={() => {
                                                                                    isActive
                                                                                        ? removeShift(
                                                                                              w.id,
                                                                                              element,
                                                                                              shift
                                                                                          )
                                                                                        : changeShift(
                                                                                              w.id,
                                                                                              element,
                                                                                              shift
                                                                                          );
                                                                                }}
                                                                                key={
                                                                                    _sIdx
                                                                                }
                                                                            >
                                                                                <div>
                                                                                    {
                                                                                        shift.start
                                                                                    }{" "}
                                                                                    -{" "}
                                                                                    {
                                                                                        shift.end
                                                                                    }
                                                                                </div>
                                                                                {isActive ? (
                                                                                    <i className="fa-solid fa-minus"></i>
                                                                                ) : (
                                                                                    <i className="fa-solid fa-plus"></i>
                                                                                )}
                                                                            </div>
                                                                        );
                                                                    }
                                                                )
                                                            ) : (
                                                                <div
                                                                    className={`text-danger text-right pr-5 pr-md-0 text-md-center`}
                                                                >
                                                                    Not
                                                                    Available
                                                                </div>
                                                            )}
                                                        </div>
                                                    </Td>
                                                );
                                            })}
                                        </Tr>
                                    );
                                })}
                            </Tbody>
                        </Table>
                    </div>
                </div>
                <div
                    id="tab-current-job"
                    className="tab-pane"
                    role="tab-panel"
                    aria-labelledby="current-job"
                >
                    <div className="crt-jb-table-scrollable">
                        <Table className="table table-bordered crt-jb-wrap">
                            <Thead>
                                <Tr>
                                    <Th>Worker</Th>
                                    {nextweek.map((element, index) => (
                                        <Th key={index}>
                                            {moment(element)
                                                .toString()
                                                .slice(0, 15)}
                                        </Th>
                                    ))}
                                </Tr>
                            </Thead>
                            <Tbody>
                                {AllWorkers.map((w, index) => {
                                    let aval = w.aval ? w.aval : [];
                                    let wjobs = w.wjobs ? w.wjobs : [];
                                    let fullname =
                                        w.firstname + " " + w.lastname;

                                    const shiftFreezeTime = {
                                        start: w.freeze_shift_start_time,
                                        end: w.freeze_shift_end_time,
                                    };

                                    const notAvailableDates =
                                        w.not_available_dates;

                                    return (
                                        <Tr key={index}>
                                            <Td>
                                                <span
                                                    id={`worker-${w.id}`}
                                                    className="d-flex align-items-center justify-content-center"
                                                >
                                                    {fullname}
                                                </span>
                                            </Td>
                                            {nextweek.map((element, index) => {
                                                let shifts = wjobs[element]
                                                    ? wjobs[element].split(",")
                                                    : [];
                                                let sav =
                                                    shifts.length > 0
                                                        ? filterShiftOptions(
                                                              aval[element],
                                                              shifts,
                                                              shiftFreezeTime
                                                          )
                                                        : [];

                                                let list =
                                                    shifts.length > 0
                                                        ? true
                                                        : false;

                                                const isDateAvailable =
                                                    !notAvailableDates.includes(
                                                        element
                                                    );

                                                return (
                                                    <Td key={index}>
                                                        <div>
                                                            {shifts.map(
                                                                (s, i) => {
                                                                    return (
                                                                        <div
                                                                            className="text-success p-2 bg-light border-bottom"
                                                                            key={
                                                                                i
                                                                            }
                                                                        >
                                                                            {s}
                                                                        </div>
                                                                    );
                                                                }
                                                            )}

                                                            {isDateAvailable &&
                                                            aval[element] &&
                                                            aval[element] !=
                                                                "" ? (
                                                                filterShiftOptions(
                                                                    aval[
                                                                        element
                                                                    ],
                                                                    shifts,
                                                                    shiftFreezeTime
                                                                ).map(
                                                                    (
                                                                        shift,
                                                                        _sIdx
                                                                    ) => {
                                                                        const isActive =
                                                                            hasActive(
                                                                                w.id,
                                                                                element,
                                                                                shift
                                                                            );

                                                                        return (
                                                                            <div
                                                                                className={`d-flex justify-content-between p-2 border-bottom align-items-center ${
                                                                                    isActive
                                                                                        ? "bg-primary"
                                                                                        : ""
                                                                                }`}
                                                                                key={
                                                                                    _sIdx
                                                                                }
                                                                                onClick={() => {
                                                                                    isActive
                                                                                        ? removeShift(
                                                                                              w.id,
                                                                                              element,
                                                                                              shift
                                                                                          )
                                                                                        : changeShift(
                                                                                              w.id,
                                                                                              element,
                                                                                              shift
                                                                                          );
                                                                                }}
                                                                            >
                                                                                <div>
                                                                                    {
                                                                                        shift.start
                                                                                    }{" "}
                                                                                    -{" "}
                                                                                    {
                                                                                        shift.end
                                                                                    }
                                                                                </div>
                                                                                {isActive ? (
                                                                                    <i className="fa-solid fa-minus"></i>
                                                                                ) : (
                                                                                    <i className="fa-solid fa-plus"></i>
                                                                                )}
                                                                            </div>
                                                                        );
                                                                    }
                                                                )
                                                            ) : (
                                                                <div
                                                                    className={`text-danger text-right pr-5 pr-md-0 text-md-center`}
                                                                >
                                                                    Not
                                                                    Available
                                                                </div>
                                                            )}
                                                        </div>
                                                    </Td>
                                                );
                                            })}
                                        </Tr>
                                    );
                                })}
                            </Tbody>
                        </Table>
                    </div>
                </div>
                <div
                    id="tab-current-next-job"
                    className="tab-pane"
                    role="tab-panel"
                    aria-labelledby="current-job"
                >
                    <div className="crt-jb-table-scrollable">
                        <Table className="table table-bordered crt-jb-wrap">
                            <Thead>
                                <Tr>
                                    <Th>Worker</Th>
                                    {nextnextweek.map((element, index) => (
                                        <Th key={index}>
                                            {moment(element)
                                                .toString()
                                                .slice(0, 15)}
                                        </Th>
                                    ))}
                                </Tr>
                            </Thead>
                            <Tbody>
                                {AllWorkers.map((w, index) => {
                                    let aval = w.aval ? w.aval : [];
                                    let wjobs = w.wjobs ? w.wjobs : [];
                                    let fullname =
                                        w.firstname + " " + w.lastname;

                                    const shiftFreezeTime = {
                                        start: w.freeze_shift_start_time,
                                        end: w.freeze_shift_end_time,
                                    };

                                    const notAvailableDates =
                                        w.not_available_dates;

                                    return (
                                        <Tr key={index}>
                                            <Td>
                                                <span
                                                    id={`worker-${w.id}`}
                                                    className="d-flex align-items-center justify-content-center"
                                                >
                                                    {fullname}
                                                </span>
                                            </Td>
                                            {nextnextweek.map(
                                                (element, index) => {
                                                    let shifts = wjobs[element]
                                                        ? wjobs[element].split(
                                                              ","
                                                          )
                                                        : [];
                                                    let sav =
                                                        shifts.length > 0
                                                            ? filterShiftOptions(
                                                                  aval[element],
                                                                  shifts,
                                                                  shiftFreezeTime
                                                              )
                                                            : [];

                                                    let list =
                                                        shifts.length > 0
                                                            ? true
                                                            : false;

                                                    const isDateAvailable =
                                                        !notAvailableDates.includes(
                                                            element
                                                        );

                                                    return (
                                                        <Td key={index}>
                                                            <div>
                                                                {shifts.map(
                                                                    (s, i) => {
                                                                        return (
                                                                            <div
                                                                                className="text-success p-2 bg-light border-bottom"
                                                                                key={
                                                                                    i
                                                                                }
                                                                            >
                                                                                {
                                                                                    s
                                                                                }
                                                                            </div>
                                                                        );
                                                                    }
                                                                )}

                                                                {isDateAvailable &&
                                                                aval[element] &&
                                                                aval[element] !=
                                                                    "" ? (
                                                                    filterShiftOptions(
                                                                        aval[
                                                                            element
                                                                        ],
                                                                        shifts,
                                                                        shiftFreezeTime
                                                                    ).map(
                                                                        (
                                                                            shift,
                                                                            _sIdx
                                                                        ) => {
                                                                            const isActive =
                                                                                hasActive(
                                                                                    w.id,
                                                                                    element,
                                                                                    shift
                                                                                );

                                                                            return (
                                                                                <div
                                                                                    className={`d-flex justify-content-between p-2 border-bottom align-items-center ${
                                                                                        isActive
                                                                                            ? "bg-primary"
                                                                                            : ""
                                                                                    }`}
                                                                                    key={
                                                                                        _sIdx
                                                                                    }
                                                                                    onClick={() => {
                                                                                        isActive
                                                                                            ? removeShift(
                                                                                                  w.id,
                                                                                                  element,
                                                                                                  shift
                                                                                              )
                                                                                            : changeShift(
                                                                                                  w.id,
                                                                                                  element,
                                                                                                  shift
                                                                                              );
                                                                                    }}
                                                                                >
                                                                                    <div>
                                                                                        {
                                                                                            shift.start
                                                                                        }{" "}
                                                                                        -{" "}
                                                                                        {
                                                                                            shift.end
                                                                                        }
                                                                                    </div>
                                                                                    {isActive ? (
                                                                                        <i className="fa-solid fa-minus"></i>
                                                                                    ) : (
                                                                                        <i className="fa-solid fa-plus"></i>
                                                                                    )}
                                                                                </div>
                                                                            );
                                                                        }
                                                                    )
                                                                ) : (
                                                                    <div
                                                                        className={`text-danger text-right pr-5 pr-md-0 text-md-center`}
                                                                    >
                                                                        Not
                                                                        Available
                                                                    </div>
                                                                )}
                                                            </div>
                                                        </Td>
                                                    );
                                                }
                                            )}
                                        </Tr>
                                    );
                                })}
                            </Tbody>
                        </Table>
                    </div>
                </div>
            </div>
            <div className="form-group text-center mt-3">
                <input
                    type="button"
                    value="View Job"
                    className="btn btn-pink viewBtn"
                    data-toggle="modal"
                    data-target="#exampleModal"
                />
            </div>
            <div
                className="modal fade"
                id="exampleModal"
                tabIndex="-1"
                role="dialog"
                aria-labelledby="exampleModalLabel"
                aria-hidden="true"
            >
                <div className="modal-dialog modal-lg" role="document">
                    <div className="modal-content">
                        <div className="modal-header">
                            <h5 className="modal-title" id="exampleModalLabel">
                                View Job
                            </h5>
                            <button
                                type="button"
                                className="close"
                                data-dismiss="modal"
                                aria-label="Close"
                            >
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div className="modal-body">
                            <div className="row">
                                <div className="table-responsive">
                                    <table className="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th scope="col">Client</th>
                                                <th scope="col">Service</th>
                                                <th scope="col">Frequency</th>
                                                <th scope="col">
                                                    Time to Complete
                                                </th>
                                                <th scope="col">Property</th>
                                                <th scope="col">
                                                    Gender preference
                                                </th>
                                                <th scope="col">Pet animals</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    {`${job.client.firstname} ${job.client.lastname}`}
                                                </td>
                                                <td>
                                                    {" "}
                                                    <p>{job.jobservice.name}</p>
                                                </td>
                                                <td>
                                                    <p>
                                                        {
                                                            job.jobservice
                                                                .freq_name
                                                        }
                                                    </p>
                                                </td>
                                                <td>
                                                    <p>
                                                        {
                                                            job.jobservice
                                                                .jobHours
                                                        }{" "}
                                                        hours
                                                    </p>
                                                </td>
                                                <td>
                                                    <p>
                                                        {
                                                            job.property_address
                                                                .address_name
                                                        }
                                                    </p>
                                                </td>
                                                <td
                                                    style={{
                                                        textTransform:
                                                            "capitalize",
                                                    }}
                                                >
                                                    <p>
                                                        {
                                                            job.property_address
                                                                .prefer_type
                                                        }
                                                    </p>
                                                </td>
                                                <td>
                                                    <p>
                                                        {job.property_address
                                                            .is_cat_avail
                                                            ? "Cat ,"
                                                            : job
                                                                  .property_address
                                                                  .is_dog_avail
                                                            ? "Dog"
                                                            : !job
                                                                  .property_address
                                                                  .is_cat_avail &&
                                                              !job
                                                                  .property_address
                                                                  .is_dog_avail
                                                            ? "NA"
                                                            : ""}
                                                    </p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div className="table-responsive">
                                    {data.length > 0 && (
                                        <table className="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Worker</th>
                                                    <th scope="col">Data</th>
                                                    <th scope="col">Shifts</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {data &&
                                                    data.map((d, i) => (
                                                        <tr key={i}>
                                                            <td>
                                                                {d.worker_name}
                                                            </td>
                                                            <td>{d.date}</td>
                                                            <td>{d.shifts}</td>
                                                        </tr>
                                                    ))}
                                            </tbody>
                                        </table>
                                    )}
                                </div>
                            </div>

                            <div className="row">
                                <div className="offset-sm-4 col-sm-4">
                                    <div className="form-group">
                                        <label className="control-label">
                                            Repeatancy
                                        </label>

                                        <select
                                            name="repeatancy"
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    repeatancy: e.target.value,
                                                });
                                            }}
                                            value={formValues.repeatancy}
                                            className="form-control mb-3"
                                        >
                                            <option value="one_time">
                                                One Time ( for single job )
                                            </option>
                                            <option value="until_date">
                                                Until Date
                                            </option>
                                            <option value="forever">
                                                Forever
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                {formValues.repeatancy == "until_date" && (
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
                                                        until_date: dateStr,
                                                    });
                                                }}
                                                options={{
                                                    disableMobile: true,
                                                    minDate: minUntilDate,
                                                }}
                                                value={formValues.until_date}
                                                ref={flatpickrRef}
                                            />
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                        <div className="modal-footer">
                            <button
                                type="button"
                                className="btn btn-secondary closeb"
                                data-dismiss="modal"
                            >
                                Close
                            </button>
                            <button
                                type="button"
                                onClick={handleSubmit}
                                className="btn btn-primary"
                                data-dismiss="modal"
                            >
                                Save and Send
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
