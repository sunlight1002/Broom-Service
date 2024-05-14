import React, { useState, useEffect } from "react";
import { useParams, useNavigate } from "react-router-dom";
import { useAlert } from "react-alert";
import Swal from "sweetalert2";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import { CSVLink } from "react-csv";
import { useTranslation } from "react-i18next";

export default function WorkerTiming({ job }) {
    const [job_time, setJobTime] = useState([]);
    const [total_time, setTotalTime] = useState(0);
    const alert = useAlert();
    const params = useParams();
    const navigate = useNavigate();
    const { t } = useTranslation();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };
    const getTimes = () => {
        let data = {
            job_id: params.id,
        };
        axios.post(`/api/admin/get-job-time`, data, { headers }).then((res) => {
            let t = res.data;
            setJobTime(t.time);
            setTotalTime(parseInt(t.total));
        });
    };
    useEffect(() => {
        getTimes();
    }, []);
    const handleSubmit = (e) => {
        e.preventDefault();
        const start = document.querySelector("#timing_starts").value;
        const end = document.querySelector("#timing_ends").value;
        const data = {
            start_time: start.replace("T", " ") + ":00",
            end_time: end.replace("T", " ") + ":00",
            job_id: params.id,
            worker_id: job.worker_id,
            timeDiff:
                (new Date(end).getTime() - new Date(start).getTime()) / 1000,
        };
        if (data.start_time == ":00" || data.end_time == ":00") {
            window.alert("Please Select Start And End Time");
            return 0;
        }
        axios.post(`/api/admin/add-job-time`, data, { headers }).then((res) => {
            if (res.data.errors) {
                for (let e in res.data.errors) {
                    alert.error(res.data.errors[e]);
                }
            } else {
                alert.success("Worker Timing Added Successfully.");
                document.querySelector(".closeb").click();
                document.querySelector("#timing_starts").value = "";
                document.querySelector("#timing_ends").value = "";
                getTimes();
            }
        });
    };
    let time_difference = (start, end) => {
        const timeDiff =
            (new Date(end).getTime() - new Date(start).getTime()) / 1000;
        return calculateTime(timeDiff);
    };
    let calculateTime = (timeDiff) => {
        let hours = Math.floor(timeDiff / 3600);
        let minutes = Math.floor((timeDiff % 3600) / 60);
        let seconds = Math.floor(timeDiff % 60);
        hours = hours < 10 ? "0" + hours : hours;
        minutes = minutes < 10 ? "0" + minutes : minutes;
        seconds = seconds < 10 ? "0" + seconds : seconds;
        return `${hours}h:${minutes}m:${seconds}s`;
    };
    const handleDelete = (e, id) => {
        e.preventDefault();
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Delete Job Time",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/admin/delete-job-time/${id}`, { headers })
                    .then((response) => {
                        Swal.fire(
                            "Deleted!",
                            "Job Time has been deleted.",
                            "success"
                        );
                        setTimeout(() => {
                            getTimes();
                        }, 1000);
                    });
            }
        });
    };
    const handleEdit = (start, end, id) => {
        $("#timing_starts_id").val(id);
        $("#edit_timing_starts").val(start);
        $("#edit_timing_ends").val(end);
        $("#edit-work-time").modal("show");
    };
    const handleUpdate = (e) => {
        e.preventDefault();
        const id = document.querySelector("#timing_starts_id").value;
        const start = document.querySelector("#edit_timing_starts").value;
        const end = document.querySelector("#edit_timing_ends").value;
        const data = {
            id: id,
            start_time: start.replace("T", " ") + ":00",
            end_time: end.replace("T", " ") + ":00",
            timeDiff:
                (new Date(end).getTime() - new Date(start).getTime()) / 1000,
        };
        if (data.start_time == ":00" || data.end_time == ":00") {
            window.alert("Please Select Start And End Time");
            return 0;
        }
        axios
            .post(`/api/admin/update-job-time`, data, { headers })
            .then((res) => {
                if (res.data.errors) {
                    for (let e in res.data.errors) {
                        alert.error(res.data.errors[e]);
                    }
                } else {
                    alert.success("Worker Timing Updated Successfully.");
                    document.querySelector(".closee").click();
                    getTimes();
                }
            });
    };

    const header = [
        { label: "Worker Name", key: "worker_name" },
        { label: "Worker ID", key: "worker_id" },
        { label: "Start Time", key: "start_time" },
        { label: "End Time", key: "end_time" },
        { label: "Total Time", key: "hours" },
    ];

    const [Alldata, setAllData] = useState([]);
    const [filename, setFilename] = useState("");
    const handleReport = (e) => {
        e.preventDefault();
        axios
            .post(
                `/api/admin/jobs/${params.id}/worker/hours/export`,
                {},
                { headers }
            )
            .then((res) => {
                setFilename(
                    job.start_date +
                        " | " +
                        job.shifts +
                        " | " +
                        job.jobservice.name
                );
                setAllData(res.data.job_hours);
                document.querySelector("#csv").click();
            })
            .catch((e) => {
                alert.error(e.response.data.message);
            });
    };

    const csvReport = {
        data: Alldata,
        headers: header,
        filename: filename,
    };

    return (
        <>
            <div className="col-sm-12">
                <div className="row">
                    <div className="col-sm-6">
                        <h2 className="text-custom">
                            {t("admin.schedule.jobs.WorkTime")}
                        </h2>
                    </div>
                    <div className="col-sm-6">
                        <div className="inline-buttons">
                            <div className="App" style={{ display: "none" }}>
                                <CSVLink {...csvReport} id="csv">
                                    Export to CSV
                                    {t(
                                        "admin.schedule.jobs.workerTiming.ExportToCSV"
                                    )}
                                </CSVLink>
                            </div>
                            <div className="ml-2">
                                <button
                                    type="button"
                                    className="btn btn-success"
                                    onClick={(e) => handleReport(e)}
                                    style={{ height: "38px" }}
                                >
                                    {t(
                                        "admin.schedule.jobs.workerTiming.ExportReport"
                                    )}
                                </button>
                            </div>
                            <div className="ml-2">
                                <button
                                    type="button"
                                    className="btn btn-pink"
                                    data-toggle="modal"
                                    data-target="#add-work-time"
                                >
                                    {t(
                                        "admin.schedule.jobs.workerTiming.AddTiming"
                                    )}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                {job_time.length > 0 ? (
                    <Table
                        className="table table-bordered responsiveTable"
                        style={{ background: "#fff" }}
                    >
                        <Thead>
                            <Tr>
                                <Th scope="col">
                                    {t(
                                        "admin.schedule.jobs.workerTiming.StartTime"
                                    )}
                                </Th>
                                <Th scope="col">
                                    {t(
                                        "admin.schedule.jobs.workerTiming.EndTime"
                                    )}
                                </Th>
                                <Th scope="col">
                                    {t("admin.schedule.jobs.workerTiming.Time")}
                                </Th>
                                <Th scope="col">
                                    {t(
                                        "admin.schedule.jobs.workerTiming.Action"
                                    )}
                                </Th>
                            </Tr>
                        </Thead>
                        <Tbody>
                            {job_time &&
                                job_time.map((item, index) => {
                                    let w_t = item.end_time
                                        ? time_difference(
                                              item.start_time,
                                              item.end_time
                                          )
                                        : "";
                                    return (
                                        <Tr key={index}>
                                            <Td>{item.start_time}</Td>
                                            <Td>{item.end_time}</Td>
                                            <Td>{w_t}</Td>
                                            <Td>
                                                <div className="float-left">
                                                    <button
                                                        type="button"
                                                        className="ml-2 btn bg-success"
                                                        onClick={(e) =>
                                                            handleEdit(
                                                                item.start_time,
                                                                item.end_time,
                                                                item.id
                                                            )
                                                        }
                                                    >
                                                        <i className="fa fa-edit"></i>
                                                    </button>
                                                    &nbsp;
                                                    <button
                                                        className="ml-2 btn bg-red"
                                                        onClick={(e) =>
                                                            handleDelete(
                                                                e,
                                                                item.id
                                                            )
                                                        }
                                                    >
                                                        <i className="fa fa-trash"></i>
                                                    </button>
                                                    &nbsp;
                                                </div>
                                            </Td>
                                        </Tr>
                                    );
                                })}
                            <Tr>
                                <Td colSpan="2">
                                    {" "}
                                    {t(
                                        "admin.schedule.jobs.workerTiming.TotalTime"
                                    )}
                                </Td>
                                <Td>{calculateTime(total_time)}</Td>
                            </Tr>
                        </Tbody>
                    </Table>
                ) : (
                    <p className="text-center mt-5"></p>
                )}
            </div>

            <div
                className="modal fade"
                id="add-work-time"
                tabIndex="-1"
                role="dialog"
                aria-labelledby="exampleModalLabel"
                aria-hidden="true"
            >
                <div className="modal-dialog" role="document">
                    <div className="modal-content">
                        <div className="modal-header">
                            <h5 className="modal-title" id="exampleModalLabel">
                                {t(
                                    "admin.schedule.jobs.workerTiming.AddWorkerTiming"
                                )}
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
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label htmlFor="timing_starts">
                                            {t(
                                                "admin.schedule.jobs.workerTiming.TimingStartsAt"
                                            )}
                                        </label>
                                        <input
                                            type="datetime-local"
                                            className="form-control"
                                            id="timing_starts"
                                            name="timing_starts"
                                            placeholder="Enter Start Timing"
                                        />
                                    </div>
                                </div>
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label htmlFor="timing_starts">
                                            {t(
                                                "admin.schedule.jobs.workerTiming.TimingEndsAt"
                                            )}
                                        </label>
                                        <input
                                            type="datetime-local"
                                            className="form-control"
                                            id="timing_ends"
                                            name="timing_ends"
                                            placeholder="Enter End Timing"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="modal-footer">
                            <button
                                type="button"
                                className="btn btn-secondary closeb"
                                data-dismiss="modal"
                            >
                                {t("admin.schedule.jobs.workerTiming.Close")}
                            </button>
                            <button
                                type="button"
                                onClick={handleSubmit}
                                className="btn btn-primary"
                            >
                                {t(
                                    "admin.schedule.jobs.workerTiming.SaveTiming"
                                )}
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
                                {t(
                                    "admin.schedule.jobs.workerTiming.EditWorkerTiming"
                                )}
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
                            <input
                                type="hidden"
                                className="form-control"
                                id="timing_starts_id"
                            />
                            <div className="row">
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label htmlFor="timing_starts">
                                            {t(
                                                "admin.schedule.jobs.workerTiming.TimingStartsAt"
                                            )}
                                        </label>
                                        <input
                                            type="datetime-local"
                                            className="form-control"
                                            id="edit_timing_starts"
                                            name="timing_starts"
                                            placeholder="Enter Start Timing"
                                        />
                                    </div>
                                </div>
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label htmlFor="timing_starts">
                                            {t(
                                                "admin.schedule.jobs.workerTiming.TimingEndsAt"
                                            )}
                                        </label>
                                        <input
                                            type="datetime-local"
                                            className="form-control"
                                            id="edit_timing_ends"
                                            name="timing_ends"
                                            placeholder="Enter End Timing"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="modal-footer">
                            <button
                                type="button"
                                className="btn btn-secondary closee"
                                data-dismiss="modal"
                            >
                                {t("admin.schedule.jobs.workerTiming.Close")}
                            </button>
                            <button
                                type="button"
                                onClick={handleUpdate}
                                className="btn btn-primary"
                            >
                                {t(
                                    "admin.schedule.jobs.workerTiming.SaveTiming"
                                )}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
