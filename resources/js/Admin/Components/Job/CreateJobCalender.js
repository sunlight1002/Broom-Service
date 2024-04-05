import React, { useState, useEffect, useRef, useMemo } from "react";
import moment from "moment-timezone";
import { useNavigate, useParams } from "react-router-dom";
import { useAlert } from "react-alert";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import Swal from "sweetalert2";

import JobWorkerModal from "../Modals/JobWorkerModal";

export default function CreateJobCalender() {
    const params = useParams();
    const navigate = useNavigate();
    const alert = useAlert();
    const [interval, setTimeInterval] = useState([]);
    const [shiftFreezeTime, setShiftFreezeTime] = useState({});
    const [selectedService, setSelectedService] = useState(null);
    const [data, setData] = useState([]);
    const [isOpenWorker, setIsOpenWorker] = useState(false);
    const [services, setServices] = useState([]);
    const [contract, setContract] = useState(null);
    const [shiftFormValues, setShiftFormValues] = useState([]);
    const [tmpFormValues, setTmpFormValues] = useState({});
    const [editIndex, setEditIndex] = useState(-1);

    let isPrevWorker = useRef();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getJob = () => {
        axios
            .get(`/api/admin/contract/${params.id}`, { headers })
            .then((res) => {
                const _contract = res.data.contract;
                setContract(_contract);
                setServices(JSON.parse(_contract.offer.services));
            });
    };

    const clientName = useMemo(() => {
        if (contract) {
            return contract.client.firstname + " " + contract.client.lastname;
        } else {
            return "-";
        }
    }, [contract]);

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
                const { freeze_shift_start_time, freeze_shift_end_time } =
                    res.data.data;
                if (freeze_shift_start_time && freeze_shift_end_time) {
                    setShiftFreezeTime({
                        start: freeze_shift_start_time,
                        end: freeze_shift_end_time,
                    });
                }
            }
        });
    };
    useEffect(() => {
        getTime();
    }, []);

    const handleServices = (value) => {
        services.forEach((_s) => {
            if (_s.service != value) {
                $(".services-" + _s.service).css("display", "none");
            }
        });

        const _service = services.find((s) => s.service == value);

        setServices([_service]);
        setSelectedService(_service);
        $("#edit-work-time").modal("hide");
    };

    const handleSave = (indexKey, tmpWorkerData) => {
        let newFormValues = [...shiftFormValues];
        if (indexKey > -1) {
            newFormValues[indexKey] = tmpWorkerData;
        } else {
            newFormValues.push(tmpWorkerData);
        }
        setShiftFormValues(newFormValues);
    };

    const handleSubmit = () => {
        let formdata = {
            workers: shiftFormValues,
            service_id: selectedService.service,
            prevWorker: isPrevWorker.current.checked,
        };

        let viewbtn = document.querySelectorAll(".viewBtn");
        if (shiftFormValues.length > 0) {
            viewbtn[0].setAttribute("disabled", true);
            viewbtn[0].value = "please wait ...";

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
            alert.error("Please Select the Workers");
        }
    };

    const handleEditShift = (_shift, _index) => {
        setTmpFormValues(_shift);
        setEditIndex(_index);
        setIsOpenWorker(true);
    };

    const handleDeleteShift = (_index) => {
        let newFormValues = [...shiftFormValues];

        if (_index > -1) {
            newFormValues.splice(_index, 1);
            setShiftFormValues(newFormValues);
        }
    };

    const handleOpenWorkerModal = () => {
        setEditIndex(-1);
        setIsOpenWorker(true);
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
            {selectedService && (
                <>
                    <div className="mb-3">
                        <button
                            type="button"
                            className="btn btn-primary"
                            onClick={handleOpenWorkerModal}
                        >
                            Add worker
                        </button>
                    </div>

                    {isOpenWorker && (
                        <JobWorkerModal
                            setIsOpen={setIsOpenWorker}
                            isOpen={isOpenWorker}
                            service={selectedService}
                            handleSaveForm={handleSave}
                            tmpFormValues={tmpFormValues}
                            editIndex={editIndex}
                        />
                    )}
                </>
            )}

            <Table className="table table-bordered crt-jb">
                <Thead>
                    <Tr>
                        <Th>Worker</Th>
                        <Th>Date</Th>
                        <Th>Shifts</Th>
                        <Th></Th>
                    </Tr>
                </Thead>
                <Tbody>
                    {shiftFormValues.map((_shift, _index) => {
                        const _workerShifts = _shift.shifts
                            .map((t) => `${t.start}-${t.end}`)
                            .join(", ");

                        const dateInString = moment(_shift.date)
                            .toString()
                            .slice(0, 15);

                        return (
                            <Tr key={_index}>
                                <Td>{_shift.worker_name}</Td>
                                <Td>{dateInString}</Td>
                                <Td>{_workerShifts}</Td>
                                <Td>
                                    <button
                                        type="button"
                                        className="btn btn-icon btn-sm btn-info"
                                        onClick={() =>
                                            handleEditShift(_shift, _index)
                                        }
                                    >
                                        <i className="fa fa-edit"></i>
                                    </button>

                                    <button
                                        type="button"
                                        className="btn btn-icon btn-sm btn-danger"
                                        onClick={() =>
                                            handleDeleteShift(_index)
                                        }
                                    >
                                        <i className="fa fa-close"></i>
                                    </button>
                                </Td>
                            </Tr>
                        );
                    })}
                    {shiftFormValues.length == 0 && (
                        <Tr>
                            <Td colSpan="4" className="text-center">
                                No worker selected
                            </Td>
                        </Tr>
                    )}
                </Tbody>
            </Table>

            <div className="form-group text-center">
                <input
                    type="button"
                    value="View Job"
                    className="btn btn-pink viewBtn"
                    data-toggle="modal"
                    data-target="#exampleModal"
                    data-backdrop="static"
                    data-keyboard="false"
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
                                                <th scope="col">Services</th>
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
                                                <td>{clientName}</td>
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
                                                <td>
                                                    {services &&
                                                        services.map(
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
                                                    {services &&
                                                        services.map(
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
                                                    {services &&
                                                        services.map(
                                                            (item, index) => (
                                                                <p key={index}>
                                                                    {item
                                                                        ?.address
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
                                    {shiftFormValues.length > 0 && (
                                        <table className="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Worker</th>
                                                    <th scope="col">Date</th>
                                                    <th scope="col">Shifts</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {shiftFormValues.map(
                                                    (_shift, i) => {
                                                        const _workerShifts =
                                                            _shift.shifts
                                                                .map(
                                                                    (t) =>
                                                                        `${t.start}-${t.end}`
                                                                )
                                                                .join(", ");

                                                        const dateInString =
                                                            moment(_shift.date)
                                                                .toString()
                                                                .slice(0, 15);

                                                        return (
                                                            <tr key={i}>
                                                                <td>
                                                                    {
                                                                        _shift.worker_name
                                                                    }
                                                                </td>
                                                                <td>
                                                                    {
                                                                        dateInString
                                                                    }
                                                                </td>
                                                                <td>
                                                                    {
                                                                        _workerShifts
                                                                    }
                                                                </td>
                                                            </tr>
                                                        );
                                                    }
                                                )}
                                            </tbody>
                                        </table>
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
