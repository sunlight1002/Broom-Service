import axios from "axios";
import React, { useState, useEffect, useRef } from "react";
import { useParams, useNavigate } from "react-router-dom";
import { useAlert } from "react-alert";
import Moment from "moment";
import Swal from "sweetalert2";

export default function Comment() {
    let cmtFileRef = useRef(null);
    const [comment, setComment] = useState("");
    const [role, setRole] = useState("");
    const [allClientComment, setAllClientComment] = useState([]);
    const [allWorkerComment, setAllWorkerComment] = useState([]);
    const param = useParams();
    const alert = useAlert();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "multipart/form-data",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (role == "") {
            window.alert("Please Select Comment For.");
            return;
        }
        if (comment == "") {
            window.alert("Please Enter Comment");
            return;
        }
        const data = new FormData();
        data.append("job_id", param.id);
        data.append("comment", comment);
        data.append("role", role);
        data.append("name", localStorage.getItem("admin-name"));
        if (cmtFileRef.current && cmtFileRef.current.files.length > 0) {
            for (
                let index = 0;
                index < cmtFileRef.current.files.length;
                index++
            ) {
                const element = cmtFileRef.current.files[index];
                data.append("files[]", element);
            }
        }

        axios.post(`/api/admin/job-comments`, data, { headers }).then((res) => {
            if (res.data.error) {
                for (let e in res.data.error) {
                    window.alert(res.data.error[e]);
                }
            } else {
                document.querySelector("#exampleModal .closeb").click();
                alert.success(res.data.message);
                getComments();
                setComment("");
                setRole("");
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
            confirmButtonText: "Yes, Delete Comment",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/admin/job-comments/${id}`, { headers })
                    .then((response) => {
                        Swal.fire(
                            "Deleted!",
                            "Comment has been deleted.",
                            "success"
                        );
                        setTimeout(() => {
                            getComments();
                        }, 1000);
                    });
            }
        });
    };

    const getComments = () => {
        axios
            .get(`/api/admin/job-comments?id=${param.id}`, { headers })
            .then((res) => {
                setAllClientComment(res.data.client_comments);
                setAllWorkerComment(res.data.worker_comments);
            });
    };

    useEffect(() => {
        getComments();
    }, []);

    const handleToggle = () => {
        if (cmtFileRef.current) {
            cmtFileRef.current.value = "";
            cmtFileRef.current.type = "text";
            cmtFileRef.current.type = "file";
        }
    };

    return (
        <div
            className="tab-pane fade active show"
            id="customer-notes"
            role="tabpanel"
            aria-labelledby="customer-notes-tab"
        >
            <div className="text-right pb-3">
                <button
                    onClick={() => handleToggle()}
                    type="button"
                    className="btn btn-pink"
                    data-toggle="modal"
                    data-target="#exampleModal"
                >
                    Add Comment
                </button>
            </div>
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
                        Client Comment
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
                        Worker Comment
                    </a>
                </li>
            </ul>
            <div className="tab-content" style={{ background: "#fff" }}>
                <div
                    id="tab-worker-availability"
                    className="tab-pane active show"
                    role="tab-panel"
                    aria-labelledby="current-job"
                >
                    {allClientComment &&
                        allClientComment.map((c, i) => {
                            return (
                                <div
                                    className="card card-widget widget-user-2"
                                    style={{ boxShadow: "none" }}
                                    key={i}
                                >
                                    <div className="card-comments cardforResponsive"></div>
                                    <div className="card-comment p-3">
                                        <div className="row">
                                            <div className="col-sm-10 col-10">
                                                <p
                                                    className="noteby p-1"
                                                    style={{
                                                        fontSize: "16px",
                                                    }}
                                                >
                                                    {c.name} -
                                                    <span
                                                        className="noteDate"
                                                        style={{
                                                            fontWeight: "600",
                                                        }}
                                                    >
                                                        {"" +
                                                            Moment(
                                                                c.created_at
                                                            ).format(
                                                                "DD-MM-Y h:sa"
                                                            )}{" "}
                                                        <br />
                                                    </span>
                                                </p>
                                            </div>
                                            <div className="col-sm-2 col-2">
                                                <div className="float-right noteUser">
                                                    <button
                                                        className="ml-2 btn bg-red"
                                                        onClick={(e) =>
                                                            handleDelete(
                                                                e,
                                                                c.id
                                                            )
                                                        }
                                                    >
                                                        <i className="fa fa-trash"></i>
                                                    </button>
                                                    &nbsp;
                                                </div>
                                            </div>
                                            <div className="col-sm-12">
                                                {c.comment}
                                                <br />
                                                {c.comments &&
                                                    c.comments.length > 0 &&
                                                    c.comments.map((cm, i) => {
                                                        return (
                                                            <span
                                                                className="badge badge-warning text-dark"
                                                                key={i}
                                                            >
                                                                <a
                                                                    onClick={(
                                                                        e
                                                                    ) => {
                                                                        let show =
                                                                            document.querySelector(
                                                                                ".showFile"
                                                                            );

                                                                        show.setAttribute(
                                                                            "src",
                                                                            `storage/uploads/comments/${cm.file}`
                                                                        );
                                                                        show.style.display =
                                                                            "block";
                                                                    }}
                                                                    data-toggle="modal"
                                                                    data-target="#exampleModalFile"
                                                                    style={{
                                                                        cursor: "pointer",
                                                                    }}
                                                                >
                                                                    {cm.file}
                                                                </a>
                                                            </span>
                                                        );
                                                    })}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                </div>
                <div
                    id="tab-current-job"
                    className="tab-pane"
                    role="tab-panel"
                    aria-labelledby="current-job"
                >
                    {allWorkerComment &&
                        allWorkerComment.map((w, i) => {
                            return (
                                <div
                                    className="card card-widget widget-user-2"
                                    style={{ boxShadow: "none" }}
                                    key={i}
                                >
                                    <div className="card-comments cardforResponsive"></div>
                                    <div className="card-comment p-3">
                                        <div className="row">
                                            <div className="col-sm-10 col-10">
                                                <p
                                                    className="noteby p-1"
                                                    style={{
                                                        fontSize: "16px",
                                                    }}
                                                >
                                                    {w.name} -
                                                    <span
                                                        className="noteDate"
                                                        style={{
                                                            fontWeight: "600",
                                                        }}
                                                    >
                                                        {" " +
                                                            Moment(
                                                                w.created_at
                                                            ).format(
                                                                "DD-MM-Y h:sa"
                                                            )}{" "}
                                                        <br />
                                                    </span>
                                                </p>
                                            </div>
                                            <div className="col-sm-2 col-2">
                                                <div className="float-right noteUser">
                                                    <button
                                                        className="ml-2 btn bg-red"
                                                        onClick={(e) =>
                                                            handleDelete(
                                                                e,
                                                                w.id
                                                            )
                                                        }
                                                    >
                                                        <i className="fa fa-trash"></i>
                                                    </button>
                                                    &nbsp;
                                                </div>
                                            </div>
                                            <div className="col-sm-12">
                                                {w.comment}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                </div>
            </div>

            <div
                className="modal fade"
                id="exampleModal"
                tabIndex="-1"
                role="dialog"
                aria-labelledby="exampleModalLabel"
                aria-hidden="true"
            >
                <div className="modal-dialog" role="document">
                    <div className="modal-content">
                        <div className="modal-header">
                            <h5 className="modal-title" id="exampleModalLabel">
                                Add Comment
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
                                            Comment For
                                        </label>
                                        <select
                                            value={role}
                                            onChange={(e) =>
                                                setRole(e.target.value)
                                            }
                                            className="form-control"
                                        >
                                            <option value="">
                                                --- Please Choose Comment For
                                                ---
                                            </option>
                                            <option value="client">
                                                Client
                                            </option>
                                            <option value="worker">
                                                Worker
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">
                                            Comment
                                        </label>
                                        <textarea
                                            type="text"
                                            value={comment}
                                            onChange={(e) =>
                                                setComment(e.target.value)
                                            }
                                            className="form-control"
                                            required
                                            placeholder="Enter Comment"
                                        ></textarea>
                                    </div>
                                </div>
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label
                                            htmlFor="cmtFiles"
                                            className="form-label"
                                        >
                                            Upload files
                                        </label>
                                        <input
                                            ref={cmtFileRef}
                                            className="form-control"
                                            type="file"
                                            id="cmtFiles"
                                            multiple
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
                                Close
                            </button>
                            <button
                                type="button"
                                onClick={handleSubmit}
                                className="btn btn-primary"
                            >
                                Save Comment
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div
                className="modal fade"
                id="exampleModalFile"
                tabIndex="-1"
                role="dialog"
                aria-labelledby="exampleModalLabel"
                aria-hidden="true"
            >
                <div className="modal-dialog" role="document">
                    <div className="modal-content" style={{ width: "130%" }}>
                        <div className="modal-header">
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
                                        <img
                                            src=""
                                            className="showFile form-control"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
