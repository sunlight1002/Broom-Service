import { useState, useEffect, useMemo } from "react";
import axios from "axios";
import { useParams, useNavigate } from "react-router-dom";
import { useAlert } from "react-alert";
import Moment from "moment";
import Swal from "sweetalert2";
import { createHourlyTimeArray } from "../../../Utils/job.utils";
import { useTranslation } from "react-i18next";

export default function WorkerNotAvailability() {
    const [date, setDate] = useState("");
    const [AllDates, setAllDates] = useState([]);
    const param = useParams();
    const alert = useAlert();
    const [startTime, setStartTime] = useState("");
    const [endTime, setEndTime] = useState("");
    const { t } = useTranslation();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };

    const slots = useMemo(() => {
        return createHourlyTimeArray("08:00", "24:00");
    }, []);

    const handleDate = (e) => {
        e.preventDefault();

        if ((startTime && !endTime) || (!startTime && endTime)) {
            alert.error(
                "Please select both Start Time and End Time, or leave both empty."
            );
            return;
        }

        if (startTime && endTime) {
            const startTimeMinutes =
                parseInt(startTime.split(":")[0]) * 60 +
                parseInt(startTime.split(":")[1]);
            const endTimeMinutes =
                parseInt(endTime.split(":")[0]) * 60 +
                parseInt(endTime.split(":")[1]);

            if (endTimeMinutes <= startTimeMinutes) {
                alert.error("End Time must be greater than Start Time.");
                return;
            }
        }

        const data = {
            date: date,
            start_time: startTime,
            end_time: endTime,
            worker_id: parseInt(param.id),
            status: 1,
        };

        axios.post(`/api/not-available-date`, data, { headers }).then((res) => {
            if (res.data.errors) {
                for (let e in res.data.errors) {
                    alert.error(res.data.errors[e]);
                }
            } else {
                setDate("");
                setStartTime("");
                setEndTime("");
                document.querySelector(".closeb1").click();
                alert.success(res.data.message);
                getDates();
            }
        });
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
            confirmButtonText: "Yes, Delete Date",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .post(
                        `/api/delete-not-available-date`,
                        { id: id },
                        { headers }
                    )
                    .then((response) => {
                        Swal.fire(
                            "Deleted!",
                            "Date has been deleted.",
                            "success"
                        );
                        setTimeout(() => {
                            getDates();
                        }, 1000);
                    });
            }
        });
    };

    const getDates = () => {
        axios.get(`/api/not-available-dates`, { headers }).then((res) => {
            setAllDates(res.data.dates);
        });
    };
    useEffect(() => {
        getDates();
    }, []);

    return (
        <div
            className="tab-pane fade active show"
            id="customer-notes"
            role="tabpanel"
            aria-labelledby="customer-notes-tab"
        >
            {AllDates &&
                AllDates.map((n, i) => {
                    return (
                        <div
                            key={i}
                            className="card card-widget widget-user-2"
                            style={{ boxShadow: "none" }}
                        >
                            <div className="card-comments cardforResponsive"></div>
                            <div
                                className="card-comment p-3"
                                style={{
                                    backgroundColor: "rgba(0,0,0,.05)",
                                    borderRadius: "5px",
                                }}
                            >
                                <div className="row">
                                    <div className="col-sm-4 col-4">
                                        <p
                                            style={{
                                                fontSize: "16px",
                                                fontWeight: "600",
                                            }}
                                        >
                                            {n.date ? n.date : "NA"}
                                        </p>
                                    </div>
                                    <div className="col-sm-3 col-3">
                                        <p
                                            style={{
                                                fontSize: "16px",
                                                fontWeight: "600",
                                            }}
                                        >
                                            {n.start_time ? n.start_time : "NA"}
                                        </p>
                                    </div>
                                    <div className="col-sm-3 col-3">
                                        <p
                                            style={{
                                                fontSize: "16px",
                                                fontWeight: "600",
                                            }}
                                        >
                                            {n.end_time ? n.end_time : "NA"}
                                        </p>
                                    </div>
                                    <div className="col-sm-2 col-2">
                                        <div className="float-right noteUser">
                                            <button
                                                className="ml-2 btn bg-red"
                                                onClick={(e) =>
                                                    handleDelete(e, n.id)
                                                }
                                            >
                                                <i className="fa fa-trash"></i>
                                            </button>
                                            &nbsp;
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    );
                })}

            <div
                className="modal fade"
                id="exampleModalNote"
                tabIndex="-1"
                role="dialog"
                aria-labelledby="exampleModalNote"
                aria-hidden="true"
            >
                <div className="modal-dialog" role="document">
                    <div className="modal-content">
                        <div className="modal-header">
                            <h5 className="modal-title" id="exampleModalNote">
                                {t("worker.schedule.add_date")}
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
                                        <label className="control-label">
                                            {t("worker.schedule.date")}
                                        </label>
                                        <input
                                            type="date"
                                            value={date}
                                            onChange={(e) =>
                                                setDate(e.target.value)
                                            }
                                            className="form-control"
                                            required
                                            placeholder="Enter Date"
                                        />
                                    </div>
                                </div>
                                <div className="col-sm-12 row">
                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                {t(
                                                    "worker.schedule.start_time"
                                                )}
                                            </label>
                                            <select
                                                name="startTime"
                                                className="form-control"
                                                onChange={(e) =>
                                                    setStartTime(e.target.value)
                                                }
                                            >
                                                <option value="">
                                                    {"Start Time"}
                                                </option>
                                                {slots.map((t, i) => {
                                                    return (
                                                        <option
                                                            value={t}
                                                            key={i}
                                                        >
                                                            {" "}
                                                            {t}{" "}
                                                        </option>
                                                    );
                                                })}
                                            </select>
                                        </div>
                                    </div>
                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                {t("worker.schedule.end_time")}
                                            </label>
                                            <select
                                                name="endTime"
                                                className="form-control"
                                                onChange={(e) =>
                                                    setEndTime(e.target.value)
                                                }
                                            >
                                                <option value="">
                                                    {"End Time"}
                                                </option>
                                                {slots.map((t, i) => {
                                                    return (
                                                        <option
                                                            value={t}
                                                            key={i}
                                                        >
                                                            {" "}
                                                            {t}{" "}
                                                        </option>
                                                    );
                                                })}
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="modal-footer">
                            <button
                                type="button"
                                className="btn btn-secondary closeb1"
                                data-dismiss="modal"
                            >
                                {t("worker.schedule.close")}
                            </button>
                            <button
                                type="button"
                                onClick={handleDate}
                                className="btn btn-primary"
                            >
                                {t("worker.schedule.save_date")}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
