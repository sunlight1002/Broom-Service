import axios from "axios";
import React, { useState, useEffect, useRef, memo } from "react";
import { useParams, useNavigate } from "react-router-dom";
import { useAlert } from "react-alert";
import Moment from "moment";
import Swal from "sweetalert2";
import { useTranslation } from "react-i18next";

export default function Comment({ handleGetJob, jobStatus }) {
    let cmtFileRef = useRef(null);
    const [comment, setComment] = useState("");
    const [status, setStatus] = useState("");
    const [allComment, setAllComment] = useState([]);
    const [allCommentsChecked, setAllCommentsChecked] = useState(false);
    const param = useParams();
    const alert = useAlert();
    const { t } = useTranslation();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "multipart/form-data",
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (status === "" && jobStatus !== "completed") {
            window.alert("Please select status");
            return;
        } else if (comment == "") {
            window.alert("Please Enter Comment");
            return;
        }
        if (allComment.length > 0 && !allCommentsChecked) {
            window.alert("Please select all comments");
            return;
        }
        const data = new FormData();
        data.append("job_id", param.id);
        data.append("comment", comment);
        data.append("status", status);
        data.append("name", localStorage.getItem("worker-name"));
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
        axios.post(`/api/job-comments`, data, { headers }).then((res) => {
            if (res.data.error) {
                for (let e in res.data.error) {
                    window.alert(res.data.error[e]);
                }
            } else {
                document.querySelector(".closeb").click();
                if (status === "completed") {
                    alert.success("Job Mark as Completed.");
                    handleGetJob();
                } else {
                    alert.success(res.data.message);
                }
                getComments([]);
                setComment("");
                setStatus("");
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
                    .delete(`/api/job-comments/${id}`, { headers })
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
            .get(`/api/job-comments?id=${param.id}`, { headers })
            .then((res) => {
                setAllComment(res.data.comments);
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
            <div className="text-right pb-3 mt-3">
                <button
                    onClick={() => handleToggle()}
                    type="button"
                    className="btn btn-primary note-btn"
                    data-toggle="modal"
                    data-target="#exampleModal"
                >
                    {t("worker.jobs.view.req_re")}
                </button>
            </div>
            {allComment &&
                allComment.map((c, i) => {
                    return (
                        <div
                            className="card card-widget widget-user-2"
                            style={{ boxShadow: "none" }}
                            key={i}
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
                                                style={{ fontWeight: "600" }}
                                            >
                                                {" " +
                                                    Moment(c.created_at).format(
                                                        "DD-MM-Y h:sa"
                                                    )}{" "}
                                                <br />
                                            </span>
                                        </p>
                                    </div>
                                    <div className="col-sm-2 col-2">
                                        <div className="float-right noteUser">
                                            {c.name ==
                                            localStorage.getItem(
                                                "worker-name"
                                            ) ? (
                                                <button
                                                    className="ml-2 btn bg-red"
                                                    onClick={(e) =>
                                                        handleDelete(e, c.id)
                                                    }
                                                >
                                                    <i className="fa fa-trash"></i>
                                                </button>
                                            ) : (
                                                ""
                                            )}
                                            &nbsp;
                                        </div>
                                    </div>
                                    <div className="col-sm-12">
                                        <p>{c.comment}</p>
                                        {c.attachments &&
                                            c.attachments.length > 0 &&
                                            c.attachments.map((cm, i) => {
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
                                {t("worker.jobs.view.add_cancel_txt")}
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
                                        {allComment.length > 0 && (
                                            <AllCommentsWithCheckBox
                                                allComment={allComment}
                                                setAllCommentChecked={
                                                    setAllCommentsChecked
                                                }
                                            />
                                        )}
                                        <label className="control-label">
                                            {t(
                                                "worker.jobs.view.status_warning"
                                            )}
                                        </label>
                                        <select
                                            value={status}
                                            onChange={(e) =>
                                                setStatus(e.target.value)
                                            }
                                            className="form-control"
                                        >
                                            <option value="">
                                                --- Job Status ---
                                            </option>
                                            {jobStatus !== "completed" && (
                                                <option value="completed">
                                                    Completed
                                                </option>
                                            )}
                                            <option value="unscheduled">
                                                Unavailable
                                            </option>
                                            <option value="re-scheduled">
                                                Reschedule
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("worker.jobs.view.cmt")}
                                        </label>
                                        <textarea
                                            type="text"
                                            value={comment}
                                            onChange={(e) =>
                                                setComment(e.target.value)
                                            }
                                            className="form-control"
                                            required
                                            placeholder={t(
                                                "worker.jobs.view.cmt_box"
                                            )}
                                        ></textarea>
                                    </div>
                                </div>
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label
                                            htmlFor="cmtFiles"
                                            className="form-label"
                                        >
                                            {t("worker.jobs.view.file")}
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
                                {t("worker.jobs.view.close")}
                            </button>
                            <button
                                type="button"
                                onClick={handleSubmit}
                                className="btn btn-primary"
                            >
                                {t("worker.jobs.view.save_cmt")}
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

const AllCommentsWithCheckBox = memo(({ allComment, setAllCommentChecked }) => {
    const [modifiedComments, setModifiedComments] = useState([]);
    useEffect(() => {
        const addCheckProperty = allComment.map((c) => ({
            ...c,
            checked: false,
        }));
        setModifiedComments(addCheckProperty);
    }, [allComment]);
    useEffect(() => {
        setAllCommentChecked(modifiedComments.every((c) => c.checked));
    }, [modifiedComments, setAllCommentChecked]);
    return modifiedComments.map((c, i) => {
        return (
            <div
                className="card card-widget widget-user-2"
                style={{ boxShadow: "none" }}
                key={i}
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
                        <div className="col-sm-10 col-10 d-flex align-items-center">
                            <input
                                type="checkbox"
                                name="cb"
                                checked={c.checked}
                                onChange={(e) =>
                                    setModifiedComments((prev) => {
                                        const updatedComments = [...prev];
                                        updatedComments[i].checked =
                                            e.target.checked;
                                        return updatedComments;
                                    })
                                }
                                style={{ width: "15px", height: "15px" }}
                                className="form-control cb mr-2"
                            />
                            <p
                                className="noteby"
                                style={{
                                    fontSize: "16px",
                                }}
                            >
                                {c.name} -
                                <span
                                    className="noteDate"
                                    style={{ fontWeight: "600" }}
                                >
                                    {" " +
                                        Moment(c.created_at).format(
                                            "DD-MM-Y h:sa"
                                        )}{" "}
                                    <br />
                                </span>
                            </p>
                        </div>
                        <div className="col-sm-12">
                            <p>{c.comment}</p>
                            {c.attachments &&
                                c.attachments.length > 0 &&
                                c.attachments.map((cm, i) => {
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
    });
});
