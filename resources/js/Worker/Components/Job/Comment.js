import axios from "axios";
import React, { useEffect, useState } from "react";
import Moment from "moment";
import Swal from "sweetalert2";
import { useTranslation } from "react-i18next";
import SkipCommentModal from "./SkipCommentModal";
import { useAlert } from "react-alert";


export default function Comment({
    allComment = [],
    skippedComments,
    setSkippedComments,
    handleGetSkippedComments,
    setAllComment,
    handleGetComments,
    setTargetLanguage,
    setJobId,
    setCommentId,
    job_status
}) {
    const [commentLanguageMap, setCommentLanguageMap] = useState({});
    const [isOpen, setIsOpen] = useState(false)
    const [comment, setComment] = useState([])
    const [additionalLanguagesVisible, setAdditionalLanguagesVisible] = useState(false); // New state
    const alert = useAlert()
    const { t } = useTranslation();

    const [dropdownOpen, setDropdownOpen] = useState(Array(allComment && allComment?.length).fill(false));
    const languageOptions = [
        { value: 'he', label: 'עִברִית' },
        { value: 'ru', label: 'Русский' },
        { value: 'en', label: 'English' },
        { value: 'es', label: 'Spanish' },
        { value: 'other', label: 'Other' },
    ];

    // List of additional languages
    const additionalLanguages = [
        { value: 'am', label: 'Amharic' },
        { value: 'ar', label: 'Arabic' },
        { value: 'eu', label: 'Basque' },
        { value: 'bn', label: 'Bengali' },
        { value: 'en-GB', label: 'English (UK)' },
        { value: 'pt-BR', label: 'Portuguese (Brazil)' },
        { value: 'bg', label: 'Bulgarian' },
        { value: 'ca', label: 'Catalan' },
        { value: 'chr', label: 'Cherokee' },
        { value: 'hr', label: 'Croatian' },
        { value: 'cs', label: 'Czech' },
        { value: 'da', label: 'Danish' },
        { value: 'nl', label: 'Dutch' },
        { value: 'en', label: 'English (US)' },
        { value: 'et', label: 'Estonian' },
        { value: 'fil', label: 'Filipino' },
        { value: 'fi', label: 'Finnish' },
        { value: 'fr', label: 'French' },
        { value: 'de', label: 'German' },
        { value: 'el', label: 'Greek' },
        { value: 'gu', label: 'Gujarati' },
        { value: 'iw', label: 'Hebrew' },
        { value: 'hi', label: 'Hindi' },
        { value: 'hu', label: 'Hungarian' },
        { value: 'is', label: 'Icelandic' },
        { value: 'id', label: 'Indonesian' },
        { value: 'it', label: 'Italian' },
        { value: 'ja', label: 'Japanese' },
        { value: 'kn', label: 'Kannada' },
        { value: 'ko', label: 'Korean' },
        { value: 'lv', label: 'Latvian' },
        { value: 'lt', label: 'Lithuanian' },
        { value: 'ms', label: 'Malay' },
        { value: 'ml', label: 'Malayalam' },
        { value: 'mr', label: 'Marathi' },
        { value: 'no', label: 'Norwegian' },
        { value: 'pl', label: 'Polish' },
        { value: 'pt-PT', label: 'Portuguese (Portugal)' },
        { value: 'ro', label: 'Romanian' },
        { value: 'ru', label: 'Russian' },
        { value: 'sr', label: 'Serbian' },
        { value: 'zh-CN', label: 'Chinese (PRC)' },
        { value: 'sk', label: 'Slovak' },
        { value: 'sl', label: 'Slovenian' },
        { value: 'es', label: 'Spanish' },
        { value: 'sw', label: 'Swahili' },
        { value: 'sv', label: 'Swedish' },
        { value: 'ta', label: 'Tamil' },
        { value: 'te', label: 'Telugu' },
        { value: 'th', label: 'Thai' },
        { value: 'zh-TW', label: 'Chinese (Taiwan)' },
        { value: 'tr', label: 'Turkish' },
        { value: 'ur', label: 'Urdu' },
        { value: 'uk', label: 'Ukrainian' },
        { value: 'vi', label: 'Vietnamese' },
        { value: 'cy', label: 'Welsh' },
    ];

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "multipart/form-data",
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
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

            // Reset additional languages visibility when a language other than "Other" is selected
            if (language !== 'other') {
                setAdditionalLanguagesVisible(false);
            }

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
                                    {/* Check if commenter type is Admin or Client */}
                                    {(c.commenter_type === "App\\Models\\Client" || c.commenter_type === "App\\Models\\Admin") && (
                                        <>
                                            {c.status !== "approved" && (
                                                c.status !== 'complete' ? (
                                                    <button type="button" disabled={job_status === "completed"} className="btn btn-primary ml-1" onClick={() => handleMarkComplete(c)}>
                                                        Mark as complete
                                                    </button>
                                                ) : (
                                                    <button type="button" disabled={job_status === "completed"} className="btn btn-success ml-1" onClick={() => handleMarkComplete(c)}>
                                                        Completed
                                                    </button>
                                                )
                                            )}

                                            <button
                                                type="button"
                                                className="btn btn-danger ml-1"
                                                onClick={() => handleSkipComment(c)}
                                                disabled={skippedComment ? true : false}
                                            >
                                                {skippedComment ? skippedComment.status : "Request To Manager"}
                                            </button>
                                        </>
                                    )}

                                    <div className="float-right noteUser ml-2">
                                        {/* Delete Button */}
                                        {c.name === localStorage.getItem("worker-name") ? (
                                            <button
                                                className="ml-2 btn bg-red"
                                                onClick={(e) => handleDelete(e, c.id)}
                                            >
                                                <i className="fa fa-trash"></i>
                                            </button>
                                        ) : null}
                                         <div className="col-sm-4">
                                            <div className="dropdown">
                                                {/* Language Dropdown Button */}
                                                <button
                                                    className="btn btn-default dropdown-toggle droptoggle navyblue text-white"
                                                    type="button"
                                                    onClick={() => toggleDropdown(i)}
                                                    aria-haspopup="true"
                                                    aria-expanded={dropdownOpen[i]}
                                                >
                                                    <i className="fa-solid fa-language"></i> Select Language
                                                </button>
                                                <div className="dropdown-menu" style={dropdownOpen[i] ? { display: "block", left: "-100px" } : { display: "none" }}>
                                                    {languageOptions.map(option => (
                                                        <button
                                                            key={option.value}
                                                            className="dropdown-item"
                                                            onClick={() => {
                                                                const language = option.value;
                                                                // Check if 'other' option is selected to show additional languages
                                                                if (language === 'other') {
                                                                    setAdditionalLanguagesVisible(true);
                                                                } else {
                                                                    setAdditionalLanguagesVisible(false);
                                                                }
                                                                handleLanguageChange(language, i, c);
                                                            }}
                                                        >
                                                            {option.label}
                                                        </button>
                                                    ))}
                                                </div>
                                            </div>

                                            {/* Additional Languages Dropdown (Visible if 'other' is selected) */}
                                            {additionalLanguagesVisible && (
                                                <div className="additional-languages mt-3">
                                                    <select onChange={(e) => handleLanguageChange(e.target.value, i, c)}>
                                                        {additionalLanguages.map((lang) => (
                                                            <option key={lang.value} value={lang.value}>
                                                                {lang.label}
                                                            </option>
                                                        ))}
                                                    </select>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                                <div className="col-sm-12">
                                    <p>{c.comment}</p>
                                    {/* Display Response Text */}
                                    {skippedComment && skippedComment.response_text && (
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
