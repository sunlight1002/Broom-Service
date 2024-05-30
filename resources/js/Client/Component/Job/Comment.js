import axios from "axios";
import React, { useState, useEffect, useRef } from "react";
import { useParams, useNavigate } from "react-router-dom";
import { useAlert } from "react-alert";
import Moment from "moment";
import Swal from "sweetalert2";
import { useTranslation } from "react-i18next";
import { Base64 } from "js-base64";

export default function Comment() {
    let cmtFileRef = useRef(null);
    const [comment, setComment] = useState("");
    const [allComment, setAllComment] = useState([]);
    const params = useParams();
    const alert = useAlert();
    const { t } = useTranslation();
    const jobId = Base64.decode(params.id);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "multipart/form-data",
        Authorization: `Bearer ` + localStorage.getItem("client-token"),
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (comment == "") {
            window.alert(t("client.jobs.view.pleaseEnterCmt"));
            return;
        }
        const data = new FormData();
        data.append("comment", comment);
        data.append("name", localStorage.getItem("client-name"));
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

        axios
            .post(`/api/client/jobs/${jobId}/comments`, data, { headers })
            .then((res) => {
                if (res.data.error) {
                    for (let e in res.data.error) {
                        window.alert(res.data.error[e]);
                    }
                } else {
                    document.querySelector(".closeb").click();
                    alert.success(res.data.message);
                    getComments();
                    setComment("");
                }
            });
    };

    const handleDelete = (e, id) => {
        e.preventDefault();
        Swal.fire({
            title: t("global.areYouSure"),
            text: t("global.notAbleToRevert"),
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: t("global.yesDelete"),
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/client/jobs/${jobId}/comments/${id}`, {
                        headers,
                    })
                    .then((response) => {
                        Swal.fire(
                            t("global.deleted"),
                            t("client.jobs.view.commentDeleted"),
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
            .get(`/api/client/jobs/${jobId}/comments`, { headers })
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
            <div className="text-right pb-3">
                <button
                    onClick={() => handleToggle()}
                    type="button"
                    className="btn btn-primary"
                    data-toggle="modal"
                    data-target="#exampleModal"
                >
                    {t("client.jobs.view.add_cmt")}
                </button>
            </div>
            {allComment.map((c, i) => {
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
                                                    "DD-MM-Y hh:mm a"
                                                )}{" "}
                                            <br />
                                        </span>
                                    </p>
                                </div>
                                <div className="col-sm-2 col-2">
                                    <div className="float-right noteUser">
                                        {c.name ==
                                        localStorage.getItem("client-name") ? (
                                            <button
                                                className="ml-auto ml-md-2 btn bg-red"
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
                                    <p className="rtl-comment">{c.comment}</p>
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
                                                            `/storage/uploads/attachments/${cm.file_name}`
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
                                                    {cm.original_name}
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
                                {t("client.jobs.view.add_cmt_box")}
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
                                            {t("client.jobs.view.cmt")}
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
                                                "client.jobs.view.enter_cmt"
                                            )}
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                            <div className="row">
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label
                                            htmlFor="cmtFiles"
                                            className="form-label"
                                        >
                                            {t("client.jobs.view.file")}
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
                                {t("client.jobs.view.close")}
                            </button>
                            <button
                                type="button"
                                onClick={handleSubmit}
                                className="btn btn-primary"
                            >
                                {t("client.jobs.view.save_cmt")}
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
