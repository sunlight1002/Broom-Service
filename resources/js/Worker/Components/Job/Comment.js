import axios from "axios";
import React, { useEffect, useState } from "react";
import Moment from "moment";
import Swal from "sweetalert2";
import { useTranslation } from "react-i18next";
import SkipCommentModal from "./SkipCommentModal";
import { useAlert } from "react-alert";

export default function Comment({ allComment = [],skippedComments, setSkippedComments, handleGetSkippedComments, setAllComment, handleGetComments, setTargetLanguage, setJobId, setCommentId }) {
    const [commentLanguageMap, setCommentLanguageMap] = useState({});
    const [isOpen, setIsOpen] = useState(false)
    const [comment, setComment] = useState([])
    const alert = useAlert()
    const { t } = useTranslation();

    const [dropdownOpen, setDropdownOpen] = useState(Array(allComment && allComment?.length).fill(false));
    const languageOptions = [
        { value: 'he', label: 'עִברִית' },
        { value: 'ru', label: 'Русский' },
        { value: 'en', label: 'English' },
    ];

    // console.log(allComment);


    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "multipart/form-data",
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };

    // const handleGetSkippedComments = async () => {
    //     try {
    //         const response = await axios.get(`/api/job-comments/skipped-comments`, { headers });
    //         console.log(response);
    //         setSkippedComments(response?.data)

    //     } catch (error) {
    //         console.log(error);

    //     }
    // }
    // useEffect(() => {
    //     handleGetSkippedComments()
    // }, [])


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
                    .delete(`/api/job-comments/${id}`, { headers })
                    .then((response) => {
                        Swal.fire(
                            t("global.deleted"),
                            t("worker.jobs.commentDeleted"),
                            "success"
                        );
                        setTimeout(() => {
                            handleGetComments();
                        }, 1000);
                    });
            }
        });
    };

    const handleLanguageChange = async (language, index, comment) => {
        try {
            setJobId(comment.job_id);
            setCommentId(comment.id);
            setTargetLanguage(language);
            setDropdownOpen((prev) => {
                const newState = [...prev];
                newState[index] = false;
                return newState;
            });

            await handleGetComments();

            setCommentLanguageMap(prev => ({
                ...prev,
                [comment.id]: language
            }));

        } catch (error) {
            console.error("Error updating language:", error);

        }
    };



    const toggleDropdown = (index) => {
        setDropdownOpen((prev) => {
            const newState = [...prev];
            newState[index] = !newState[index];
            return newState;
        });
    };

    const handleSkipComment = (c) => {
        setIsOpen(true)
        setComment(c)
    }

    const handleMarkComplete = async (c) => {
        try {
            const formData = new FormData();
            formData.append('comment_id', c.id);

            const response = await axios.post(`/api/job-comments/mark-complete`, formData, { headers });

            if (response.data.success) {
                alert.success("Comment marked as complete!");
                const updatedComments = allComment.map(comment =>
                    comment.id === c.id ? { ...comment, status: 'complete' } : comment
                );
                setAllComment(updatedComments);
                location.reload();
            }
        } catch (error) {
            console.error("Error marking comment as complete", error);
            alert.error("Failed to mark comment as complete");
        }
    };




    return (
        <div
            className="tab-pane fade active show"
            id="customer-notes"
            role="tabpanel"
            aria-labelledby="customer-notes-tab"
        >
            {allComment.map((c, i) => {
                const skippedComment = skippedComments.find(sc => sc.comment_id === c.id);                
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
                                <div className="col-sm-8 col-10">
                                    <p className="noteby p-1" style={{ fontSize: "16px" }}>
                                        {c.name} -
                                        <span className="noteDate" style={{ fontWeight: "600" }}>
                                            {" " + Moment(c.created_at).format("DD-MM-Y h:sa")} <br />
                                        </span>
                                    </p>
                                </div>

                                <div className="col-sm-4 col-3 d-flex align-items-center justify-content-end">
                                    {/* Mark Complete Button */}
                                    {c.status !== 'complete' ? (
                                        <button type="button" className="btn btn-primary ml-1" onClick={() => handleMarkComplete(c)}>
                                            Mark as complete
                                        </button>
                                    ) : (
                                        <button type="button" className="btn btn-success ml-1" onClick={() => handleMarkComplete(c)}>
                                            Completed
                                        </button>
                                    )}
                                    {/* Skip Comment Button */}
                                    <button
                                        type="button"
                                        className="btn btn-danger ml-1"
                                        onClick={() => handleSkipComment(c)}
                                        disabled={skippedComment ? true : false}  // Disable if the comment is skipped
                                    >
                                        {skippedComment ? skippedComment.status : "Request To Manager"}
                                    </button>
                                    <div className="float-right noteUser ml-2">
                                        {/* Delete Button */}
                                        {c.name === localStorage.getItem("worker-name") ? (
                                            <button
                                                className="ml-2 btn bg-red"
                                                onClick={(e) => handleDelete(e, c.id)}
                                            >
                                                <i className="fa fa-trash"></i>
                                            </button>
                                        ) : (
                                            ""
                                        )}
                                        <div className="dropdown">
                                            {/* Language Dropdown */}
                                            <button
                                                className="btn btn-default dropdown-toggle droptoggle navyblue text-white"
                                                type="button"
                                                onClick={() => toggleDropdown(i)}
                                                aria-haspopup="true"
                                                aria-expanded={dropdownOpen[i]}
                                            >
                                                <i className="fa-solid fa-language"></i>
                                            </button>
                                            <div className="dropdown-menu" style={dropdownOpen[i] ? { display: "block", left: "-100px" } : { display: "none" }}>
                                                {languageOptions.map(option => (
                                                    <button
                                                        key={option.value}
                                                        className="dropdown-item"
                                                        onClick={() => handleLanguageChange(option.value, i, c)}
                                                    >
                                                        {option.label}
                                                    </button>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div className="col-sm-12">
                                    <p>{c.comment}</p>
                                    {/* Display Response Text */}
                                    {skippedComment &&  skippedComment.response_text && (
                                        <p className="response-text" style={{ color: 'green' }}>
                                            Team Response: {skippedComment.response_text}
                                        </p>
                                    )}
                                    {/* Attachments */}
                                    {c.attachments && c.attachments.length > 0 && c.attachments.map((cm, i) => {
                                        return (
                                            <span className="badge badge-warning text-dark" key={i}>
                                                <a
                                                    onClick={(e) => {
                                                        let show = document.querySelector(".showFile");
                                                        show.setAttribute("src", `/storage/uploads/attachments/${cm.file_name}`);
                                                        show.style.display = "block";
                                                    }}
                                                    data-toggle="modal"
                                                    data-target="#exampleModalFile"
                                                    style={{ cursor: "pointer" }}
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


            <SkipCommentModal
                isOpen={isOpen}
                setIsOpen={setIsOpen}
                comment={comment}
                handleGetSkippedComments={handleGetSkippedComments}
            />


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
