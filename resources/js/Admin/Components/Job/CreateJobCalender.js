import React, { useState, useEffect, useRef } from "react";
import moment from "moment-timezone";
import { useNavigate, useParams } from "react-router-dom";
import { useAlert } from "react-alert";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import Swal from "sweetalert2";

import { shiftOptions } from "../../../Utils/common.utils";
import { filterShiftOptions } from "../../../Utils/job.utils";

export default function CreateJobCalender({
    services: clientServices,
    client,
}) {
    const params = useParams();
    const navigate = useNavigate();
    const alert = useAlert();
    const [workerData, setWorkerData] = useState([]);
    const [AllWorkers, setAllWorkers] = useState([]);
    const [interval, setTimeInterval] = useState([]);
    const [selectedService, setSelectedService] = useState(0);
    const [data, setData] = useState([]);
    const [c_time, setCTime] = useState(0);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };
    let isPrevWorker = useRef();
    const [services, setServices] = useState(clientServices);

    useEffect(() => {
        setServices(clientServices);
    }, [clientServices]);

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
    useEffect(() => {
        getTime();
    }, []);

    const handleServices = (value) => {
        services.forEach((_s) => {
            if (_s.service != value) {
                $(".services-" + _s.service + "-" + _s.contract_id).css(
                    "display",
                    "none"
                );
            }
        });

        const _service = services.find((_s, _index) => _s.service == value);

        setCTime(parseFloat(_service.jobHours));
        setServices([_service]);
        setSelectedService(_service);
        getWorkers(_service);
        $("#edit-work-time").modal("hide");
    };

    const getWorkers = (_service) => {
        axios
            .get(`/api/admin/all-workers`, {
                headers,
                params: {
                    filter: true,
                    service_id: _service.service,
                    has_cat: _service.address.is_cat_avail,
                    has_dog: _service.address.is_dog_avail,
                    prefer_type: _service.address.prefer_type,
                    ignore_worker_ids: _service.address.not_allowed_worker_ids,
                },
            })
            .then((res) => {
                setAllWorkers(res.data.workers);
            });
    };

    const handleSubmit = () => {
        let formdata = {
            workers: data,
            service_id: selectedService.service,
            contract_id: selectedService.contract_id,
            prevWorker: isPrevWorker.current.checked,
        };
        let viewbtn = document.querySelectorAll(".viewBtn");
        if (data.length > 0) {
            viewbtn[0].setAttribute("disabled", true);
            viewbtn[0].value = "please wait ...";

            viewbtn[1].setAttribute("disabled", true);
            viewbtn[1].value = "please wait ...";

            axios
                .post(`/api/admin/create-job/${params.id}`, formdata, {
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
            viewbtn[1].removeAttribute("disabled");
            viewbtn[1].value = "View Job";
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
        setWorkerData([...workerData, { ...e, w_id, date }]);
        e = [
            ...workerData.filter((d) => d.date == date && d.w_id == w_id),
            { ...e, w_id, date },
        ];
        let w_n = $("#worker-" + w_id).html();
        let filtered = data.filter((d) => {
            if (d.date == date && d.worker_id == w_id) {
                return false;
            } else {
                return d;
            }
        });
        let shifts = "";
        let value = false;
        e.map((v) => {
            if (v.label == "fullday-8am-16pm") {
                value = true;
            }
            if (shifts == "") {
                shifts = v.label;
            } else {
                if (value && [0, 1, 2, 3, 4, 5, 6].includes(v.value)) {
                    Swal.fire(
                        "Warning!",
                        "Worker already assigned to full Day.",
                        "success"
                    );
                } else {
                    shifts = shifts + "," + v.label;
                }
            }
        });

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
    };
    const removeShift = (w_id, date, e) => {
        let filtered = data.find((d) => {
            if (d.date == date && d.worker_id == w_id) {
                return d;
            } else {
                return false;
            }
        });
        if (filtered) {
            const shift = filtered.shifts.split(",") ?? [];
            var index = shift.indexOf(e.label);
            if (index > -1) {
                shift.splice(index, 1);
                const tmpworker = [...workerData];
                var indexWorker = tmpworker.findIndex(
                    (item) =>
                        item.date === date &&
                        item.w_id === w_id &&
                        item.value === e.value &&
                        item.label === e.label
                );
                if (indexWorker > -1) {
                    tmpworker.splice(indexWorker, 1);
                    setWorkerData(tmpworker);
                }
                setData(
                    data.map((item) =>
                        item.date === date && item.worker_id === w_id
                            ? { ...item, shifts: shift.join(",") }
                            : item
                    )
                );
            }
        }
    };
    const hasActive = (w_id, date, e) => {
        let filtered = data.find((d) => {
            if (d.date == date && d.worker_id == w_id) {
                return d;
            } else {
                return false;
            }
        });
        if (filtered) {
            const shift = filtered.shifts.split(",") ?? [];
            var index = shift.indexOf(e.label);
            if (index > -1) {
                return true;
            }
        }
        return false;
    };

    return (
        <>
            <ul className="nav nav-tabs mb-2" role="tablist">
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
            <div className="form-group text-right pr-2">
                <input
                    type="button"
                    value="View Job"
                    className="btn btn-pink viewBtn"
                    data-toggle="modal"
                    data-target="#exampleModal"
                />
            </div>
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
                                                              shiftOptions[
                                                                  aval[element]
                                                              ],
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
                                                            {/* {list &&
                                                            sav.map((s, i) => {
                                                                return (
                                                                    <div
                                                                        className="text-success p-0"
                                                                        key={i}
                                                                    >
                                                                        {
                                                                            s.label
                                                                        }
                                                                    </div>
                                                                );
                                                            })} */}

                                                            {isDateAvailable &&
                                                            aval[element] &&
                                                            aval[element] !=
                                                                "" ? (
                                                                filterShiftOptions(
                                                                    shiftOptions[
                                                                        aval[
                                                                            element
                                                                        ]
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
                                                                                        shift.label
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
                                                              shiftOptions[
                                                                  aval[element]
                                                              ],
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
                                                            {/* {list &&
                                                            sav.map((s, i) => {
                                                                return (
                                                                    <div
                                                                        className="text-success p-0"
                                                                        key={i}
                                                                    >
                                                                        {
                                                                            s.label
                                                                        }
                                                                    </div>
                                                                );
                                                            })} */}

                                                            {isDateAvailable &&
                                                            aval[element] &&
                                                            aval[element] !=
                                                                "" ? (
                                                                filterShiftOptions(
                                                                    shiftOptions[
                                                                        aval[
                                                                            element
                                                                        ]
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
                                                                                        shift.label
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
                                                                  shiftOptions[
                                                                      aval[
                                                                          element
                                                                      ]
                                                                  ],
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
                                                                        shiftOptions[
                                                                            aval[
                                                                                element
                                                                            ]
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
                                                                                            shift.label
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
                                                    `${client.firstname} $
                                                    {client.lastname}`
                                                </td>
                                                <td>
                                                    {" "}
                                                    {services.map(
                                                        (item, index) => {
                                                            if (
                                                                item.service ==
                                                                "10"
                                                            )
                                                                return (
                                                                    <p
                                                                        key={
                                                                            index
                                                                        }
                                                                    >
                                                                        {
                                                                            item.other_title
                                                                        }
                                                                    </p>
                                                                );
                                                            else
                                                                return (
                                                                    <p
                                                                        key={
                                                                            index
                                                                        }
                                                                    >
                                                                        {
                                                                            item.name
                                                                        }
                                                                    </p>
                                                                );
                                                        }
                                                    )}
                                                </td>
                                                <td>
                                                    {services.map(
                                                        (item, index) => (
                                                            <p key={index}>
                                                                {item.freq_name}
                                                            </p>
                                                        )
                                                    )}
                                                </td>
                                                <td>
                                                    {services.map(
                                                        (item, index) => (
                                                            <p key={index}>
                                                                {item.jobHours}{" "}
                                                                hours
                                                            </p>
                                                        )
                                                    )}
                                                </td>
                                                <td>
                                                    {services.map(
                                                        (item, index) => (
                                                            <p key={index}>
                                                                {
                                                                    item
                                                                        ?.address
                                                                        ?.address_name
                                                                }
                                                            </p>
                                                        )
                                                    )}
                                                </td>
                                                <td
                                                    style={{
                                                        textTransform:
                                                            "capitalize",
                                                    }}
                                                >
                                                    {services.map(
                                                        (item, index) => (
                                                            <p key={index}>
                                                                {
                                                                    item
                                                                        ?.address
                                                                        ?.prefer_type
                                                                }
                                                            </p>
                                                        )
                                                    )}
                                                </td>
                                                <td>
                                                    {services.map(
                                                        (item, index) => (
                                                            <p key={index}>
                                                                {item?.address
                                                                    ?.is_cat_avail
                                                                    ? "Cat ,"
                                                                    : item
                                                                          ?.address
                                                                          ?.is_dog_avail
                                                                    ? "Dog"
                                                                    : !item
                                                                          ?.address
                                                                          ?.is_cat_avail &&
                                                                      !item
                                                                          ?.address
                                                                          ?.is_dog_avail
                                                                    ? "NA"
                                                                    : ""}
                                                            </p>
                                                        )
                                                    )}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div className="table-responsive">
                                    {data.length > 0 ? (
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
                                    ) : (
                                        ""
                                    )}
                                </div>
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
            <div
                className="modal fade"
                id="edit-work-time"
                tabIndex="-1"
                role="dialog"
                aria-labelledby="exampleModalLabel"
                aria-hidden="true"
            >
                <div className="modal-dialog" role="document">
                    <div className="modal-content">
                        <div className="modal-header">
                            <h5 className="modal-title" id="exampleModalLabel">
                                Select Service
                            </h5>
                        </div>
                        <div className="modal-body">
                            <div className="row">
                                <div className="col-sm-12 mb-4">
                                    <div className="form-check">
                                        <label className="form-check-label">
                                            <input
                                                ref={isPrevWorker}
                                                type="checkbox"
                                                className="form-check-input"
                                                name={"is_keep_prev_worker"}
                                            />
                                            Keep previous worker
                                        </label>
                                    </div>
                                </div>
                                <div className="col-sm-12">
                                    <label className="control-label">
                                        Services
                                    </label>
                                    <select
                                        onChange={(e) =>
                                            handleServices(e.target.value)
                                        }
                                        className="form-control"
                                    >
                                        <option value="">
                                            --- Please Select Service ---
                                        </option>
                                        {services.map((item, index) => {
                                            return (
                                                <option
                                                    value={item.service}
                                                    key={index}
                                                >
                                                    {item.service != "10"
                                                        ? item.name
                                                        : item.other_title}
                                                </option>
                                            );
                                        })}
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
