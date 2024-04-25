import axios from "axios";
import React, { useState, useEffect, useRef } from "react";
import { useParams, useNavigate } from "react-router-dom";
import { useAlert } from "react-alert";
import Moment from "moment";
import Swal from "sweetalert2";
import { useTranslation } from "react-i18next";

export default function Comment() {
    let cmtFileRef = useRef(null);
    const [comment, setComment] = useState("");
    const [commentFor, setCommentFor] = useState("");
    const [comments, setComments] = useState([]);
    const [allWorkerComment, setAllWorkerComment] = useState([]);
    const param = useParams();
    const alert = useAlert();
    const { t } = useTranslation();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "multipart/form-data",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (commentFor == "") {
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
        data.append("comment_for", commentFor);
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
                setCommentFor("");
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
                setComments(res.data.comments);
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
                    {t("admin.schedule.jobs.comment.AddComment")}
                </button>
            </div>
            <div style={{ background: "#fff" }}>
                {comments.map((c, i) => {
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
                                                    Moment(c.created_at).format(
                                                        "DD-MM-Y hh:mm a"
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
                                                    handleDelete(e, c.id)
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
                                        {c.attachments.map((cm, i) => {
                                            return (
                                                <span
                                                    className="badge badge-warning text-dark"
                                                    key={i}
                                                >
                                                    <a
                                                        onClick={(e) => {
                                                            let show =
                                                                document.querySelector(
                                                                    ".showFile"
                                                                );

                                                            show.setAttribute(
                                                                "src",
                                                                `/storage/uploads/attachments/${cm.file}`
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
                                {t("admin.schedule.jobs.comment.AddComment")}
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
                                            {t(
                                                "admin.schedule.jobs.comment.CommentFor"
                                            )}
                                        </label>
                                        <select
                                            value={commentFor}
                                            onChange={(e) =>
                                                setCommentFor(e.target.value)
                                            }
                                            className="form-control"
                                        >
                                            <option value="">
                                                {t(
                                                    "admin.schedule.jobs.comment.PleaseChooseCommentFor"
                                                )}
                                            </option>
                                            <option value="client">
                                                {t(
                                                    "admin.schedule.jobs.comment.Client"
                                                )}
                                            </option>
                                            <option value="worker">
                                                {t(
                                                    "admin.schedule.jobs.comment.Worker"
                                                )}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t(
                                                "admin.schedule.jobs.comment.Comment"
                                            )}
                                        </label>
                                        <textarea
                                            type="text"
                                            value={comment}
                                            onChange={(e) => {
                                                setComment(e.target.value);
                                            }}
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
                                {t("admin.schedule.jobs.comment.Close")}
                            </button>
                            <button
                                type="button"
                                onClick={handleSubmit}
                                className="btn btn-primary"
                            >
                                {t("admin.schedule.jobs.comment.SaveComment")}
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
