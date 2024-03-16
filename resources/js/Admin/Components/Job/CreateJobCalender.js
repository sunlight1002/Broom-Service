import React, { useState, useEffect, useRef } from "react";
import moment from "moment-timezone";
import { useNavigate, useParams } from "react-router-dom";
import { useAlert } from "react-alert";
import Select from "react-select";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import { shiftOptions } from "../../../Utils/common.utils";
import { filterShiftOptions } from "../../../Utils/job.utils";

export default function CreateJobCalender() {
    const params = useParams();
    const navigate = useNavigate();
    const alert = useAlert();
    const [AllWorkers, setAllWorkers] = useState([]);
    const [interval, setTimeInterval] = useState([]);
    const [selected_service, setSelectedService] = useState(0);
    const [data, setData] = useState([]);
    const [c_time, setCTime] = useState(0);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };
    let isPrevWorker = useRef();
    const [services, setServices] = useState([]);
    const [clientname, setClientName] = useState("");
    const getJob = () => {
        axios
            .get(`/api/admin/contract/${params.id}`, { headers })
            .then((res) => {
                const r = res.data.contract;
                setClientName(r.client.firstname + " " + r.client.lastname);
                setServices(JSON.parse(r.offer.services));
            });
    };
    useEffect(() => {
        getJob();
    }, []);

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

    let service_id;
    let complete_time;
    const handleServices = (value) => {
        const filtered = services.filter((s) => {
            if (s.service == value) {
                service_id = value;
                complete_time = parseFloat(s.jobHours);
                return s;
            } else {
                $(".services-" + s.service).css("display", "none");
            }
        });
        setCTime(complete_time);
        setServices(filtered);
        setSelectedService(value);
        getWorkers();
        $("#edit-work-time").modal("hide");
    };

    const getWorkers = () => {
        axios
            .get(
                `/api/admin/all-workers?filter=true&service_id=${service_id}`,
                { headers }
            )
            .then((res) => {
                setAllWorkers(res.data.workers);
            });
    };

    const handleSubmit = () => {
        let formdata = {
            workers: data,
            service: services[0],
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

    const person = {
        "8am-16pm": "Full Day",
        "8am-12pm": "Morning",
        "12pm-16pm": "Afternoon",
        "16pm-20pm": "Evening",
        "20pm-24am": "Night",
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
                    className="tab-pane active show"
                    role="tab-panel"
                    aria-labelledby="current-job"
                >
                    <Table className="table table-bordered crt-jb">
                        <Thead>
                            <Tr>
                                <Th>Worker</Th>
                                {week.map((element, index) => (
                                    <Td key={index}>
                                        {moment(element)
                                            .toString()
                                            .slice(0, 15)}
                                    </Td>
                                ))}
                            </Tr>
                        </Thead>
                        <Tbody>
                            {AllWorkers.map((w, index) => {
                                let aval = w.aval ? w.aval : [];
                                let wjobs = w.wjobs ? w.wjobs : [];
                                return (
                                    <Tr key={index}>
                                        <Td>
                                            <span id={`worker-${w.id}`}>
                                                {w.firstname} {w.lastname}
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
                                                          shifts
                                                      )
                                                    : [];
                                            let list =
                                                shifts.length > 0
                                                    ? true
                                                    : false;

                                            return (
                                                <Td align="center" key={index}>
                                                    {list ? (
                                                        <span className="text-primary">
                                                            {"Partial Day"}
                                                        </span>
                                                    ) : (
                                                        <span className="text-success">
                                                            {
                                                                person[
                                                                    aval[
                                                                        element
                                                                    ]
                                                                ]
                                                            }
                                                        </span>
                                                    )}

                                                    {shifts.map((s, i) => {
                                                        return (
                                                            <div
                                                                className="text-danger"
                                                                key={i}
                                                            >
                                                                {s}
                                                            </div>
                                                        );
                                                    })}

                                                    {list &&
                                                        sav.map((s, i) => {
                                                            return (
                                                                <div
                                                                    className="text-success"
                                                                    key={i}
                                                                >
                                                                    {s.label}
                                                                </div>
                                                            );
                                                        })}

                                                    {aval[element] &&
                                                    aval[element] != "" ? (
                                                        <Select
                                                            isMulti
                                                            options={filterShiftOptions(
                                                                shiftOptions[
                                                                    aval[
                                                                        element
                                                                    ]
                                                                ],
                                                                shifts
                                                            )}
                                                            className="basic-multi-single"
                                                            isClearable={true}
                                                            classNamePrefix="select"
                                                            onChange={(e) =>
                                                                changeShift(
                                                                    w.id,
                                                                    element,
                                                                    e
                                                                )
                                                            }
                                                        />
                                                    ) : (
                                                        <div className="text-danger">
                                                            Not Available
                                                        </div>
                                                    )}
                                                </Td>
                                            );
                                        })}
                                    </Tr>
                                );
                            })}
                        </Tbody>
                    </Table>
                </div>
                <div
                    id="tab-current-job"
                    className="tab-pane"
                    role="tab-panel"
                    aria-labelledby="current-job"
                >
                    <Table className="table table-bordered crt-jb">
                        <Tbody>
                            <Tr>
                                <Td align="center">Worker</Td>
                                {nextweek.map((element, index) => (
                                    <Td align="center" key={index}>
                                        {moment(element)
                                            .toString()
                                            .slice(0, 15)}
                                    </Td>
                                ))}
                            </Tr>
                            {AllWorkers.map((w, index) => {
                                let aval = w.aval ? w.aval : [];
                                let wjobs = w.wjobs ? w.wjobs : [];
                                return (
                                    <Tr key={index}>
                                        <Td align="center">
                                            <span id={`worker-${w.id}`}>
                                                {w.firstname} {w.lastname}
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
                                                          shifts
                                                      )
                                                    : [];
                                            let list =
                                                shifts.length > 0
                                                    ? true
                                                    : false;

                                            return (
                                                <Td align="center" key={index}>
                                                    {list ? (
                                                        <span className="text-primary">
                                                            {"Partial Day"}
                                                        </span>
                                                    ) : (
                                                        <span className="text-success">
                                                            {
                                                                person[
                                                                    aval[
                                                                        element
                                                                    ]
                                                                ]
                                                            }
                                                        </span>
                                                    )}

                                                    {shifts.map((s, i) => {
                                                        return (
                                                            <div
                                                                className="text-danger"
                                                                key={i}
                                                            >
                                                                {s}
                                                            </div>
                                                        );
                                                    })}

                                                    {list &&
                                                        sav.map((s, i) => {
                                                            return (
                                                                <div
                                                                    className="text-success"
                                                                    key={i}
                                                                >
                                                                    {s.label}
                                                                </div>
                                                            );
                                                        })}

                                                    {aval[element] &&
                                                    aval[element] != "" ? (
                                                        <Select
                                                            isMulti
                                                            options={filterShiftOptions(
                                                                shiftOptions[
                                                                    aval[
                                                                        element
                                                                    ]
                                                                ],
                                                                shifts
                                                            )}
                                                            className="basic-multi-single"
                                                            isClearable={true}
                                                            classNamePrefix="select"
                                                            onChange={(e) =>
                                                                changeShift(
                                                                    w.id,
                                                                    element,
                                                                    e
                                                                )
                                                            }
                                                        />
                                                    ) : (
                                                        <div className="text-danger">
                                                            Not Available
                                                        </div>
                                                    )}
                                                </Td>
                                            );
                                        })}
                                    </Tr>
                                );
                            })}
                        </Tbody>
                    </Table>
                </div>
                <div
                    id="tab-current-next-job"
                    className="tab-pane"
                    role="tab-panel"
                    aria-labelledby="current-job"
                >
                    <Table className="table table-bordered crt-jb">
                        <Tbody>
                            <Tr>
                                <Td align="center">Worker</Td>
                                {nextnextweek.map((element, index) => (
                                    <Td align="center" key={index}>
                                        {moment(element)
                                            .toString()
                                            .slice(0, 15)}
                                    </Td>
                                ))}
                            </Tr>
                            {AllWorkers.map((w, index) => {
                                let aval = w.aval ? w.aval : [];
                                let wjobs = w.wjobs ? w.wjobs : [];
                                return (
                                    <Tr key={index}>
                                        <Td align="center">
                                            <span id={`worker-${w.id}`}>
                                                {w.firstname} {w.lastname}
                                            </span>
                                        </Td>
                                        {nextnextweek.map((element, index) => {
                                            let shifts = wjobs[element]
                                                ? wjobs[element].split(",")
                                                : [];
                                            let sav =
                                                shifts.length > 0
                                                    ? filterShiftOptions(
                                                          shiftOptions[
                                                              aval[element]
                                                          ],
                                                          shifts
                                                      )
                                                    : [];
                                            let list =
                                                shifts.length > 0
                                                    ? true
                                                    : false;

                                            return (
                                                <Td align="center" key={index}>
                                                    {list ? (
                                                        <span className="text-primary">
                                                            {"Partial Day"}
                                                        </span>
                                                    ) : (
                                                        <span className="text-success">
                                                            {
                                                                person[
                                                                    aval[
                                                                        element
                                                                    ]
                                                                ]
                                                            }
                                                        </span>
                                                    )}

                                                    {shifts.map((s, i) => {
                                                        return (
                                                            <div
                                                                className="text-danger"
                                                                key={i}
                                                            >
                                                                {s}
                                                            </div>
                                                        );
                                                    })}

                                                    {list &&
                                                        sav.map((s, i) => {
                                                            return (
                                                                <div
                                                                    className="text-success"
                                                                    key={i}
                                                                >
                                                                    {s.label}
                                                                </div>
                                                            );
                                                        })}

                                                    {aval[element] &&
                                                    aval[element] != "" ? (
                                                        <Select
                                                            isMulti
                                                            options={filterShiftOptions(
                                                                shiftOptions[
                                                                    aval[
                                                                        element
                                                                    ]
                                                                ],
                                                                shifts
                                                            )}
                                                            className="basic-multi-single"
                                                            isClearable={true}
                                                            classNamePrefix="select"
                                                            onChange={(e) =>
                                                                changeShift(
                                                                    w.id,
                                                                    element,
                                                                    e
                                                                )
                                                            }
                                                        />
                                                    ) : (
                                                        <div className="text-danger">
                                                            Not Available
                                                        </div>
                                                    )}
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
            <div className="form-group text-center">
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
                                                <th scope="col">Client Name</th>
                                                <th scope="col">Services</th>
                                                <th scope="col">Frequency</th>
                                                <th scope="col">
                                                    Complete Time
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>{clientname}</td>
                                                <td>
                                                    {" "}
                                                    {services &&
                                                        services.map(
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
                                                    {services &&
                                                        services.map(
                                                            (item, index) => (
                                                                <p key={index}>
                                                                    {
                                                                        item.freq_name
                                                                    }
                                                                </p>
                                                            )
                                                        )}
                                                </td>
                                                <td>
                                                    {services &&
                                                        services.map(
                                                            (item, index) => (
                                                                <p key={index}>
                                                                    {
                                                                        item.jobHours
                                                                    }{" "}
                                                                    hours
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
                                                    <th scope="col">
                                                        Worker Name
                                                    </th>
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
                                        value={selected_service}
                                        onChange={(e) =>
                                            handleServices(e.target.value)
                                        }
                                        className="form-control"
                                    >
                                        <option value="">
                                            --- Please Select Service ---
                                        </option>
                                        {services &&
                                            services.map((item, index) => {
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
