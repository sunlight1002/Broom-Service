import axios from "axios";
import React, { useState, useEffect, useRef } from "react";
import { useAlert } from "react-alert";
import Moment from "moment";
import Swal from "sweetalert2";
import { useTranslation } from "react-i18next";
import { Base64 } from "js-base64";

const languageOptions = [
    { value: 'he', label: 'עִברִית' },
    { value: 'ru', label: 'Русский' },
    { value: 'en', label: 'English' },
    { value: 'es', label: 'Spanish' },
    { value: 'other', label: 'Other' },
];

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

export default function Comment({ jobId }) {
    let cmtFileRef = useRef(null);
    const [comment, setComment] = useState("");
    const [allComment, setAllComment] = useState([]);
    const [dropdownOpen, setDropdownOpen] = useState(false);
    const [targetLanguage, setTargetLanguage] = useState('en'); 
    const [additionalLanguage, setAdditionalLanguage] = useState(""); 
    const [showAdditionalLanguage, setShowAdditionalLanguage] = useState(false); 
    
    const alert = useAlert();
    const { t } = useTranslation();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "multipart/form-data",
        Authorization: `Bearer ${localStorage.getItem("client-token")}`,
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (comment === "") {
            window.alert(t("client.jobs.view.pleaseEnterCmt"));
            return;
        }
        const data = new FormData();
        data.append("comment", comment);
        data.append("name", localStorage.getItem("client-name"));
        if (cmtFileRef.current && cmtFileRef.current.files.length > 0) {
            for (let index = 0; index < cmtFileRef.current.files.length; index++) {
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
                    .delete(`/api/client/jobs/${jobId}/comments/${id}`, { headers })
                    .then(() => {
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

    const getComments = (language) => {
        axios
            .get(`/api/client/jobs/${jobId}/comments`, {
                headers,
                params: {
                    id: jobId,
                    target_language: language 
                },
            })
            .then((res) => {
                setAllComment(res.data.comments);
            })
            .catch((error) => {
                console.error("Error fetching comments: ", error);
            });
    };
    
    useEffect(() => {
        if (jobId) {
            getComments(targetLanguage); 
        }
    }, [jobId, targetLanguage]); 

    const handleToggle = () => {
        if (cmtFileRef.current) {
            cmtFileRef.current.value = "";
            cmtFileRef.current.type = "text";
            cmtFileRef.current.type = "file";
        }
    };

    const toggleDropdown = () => {
        setDropdownOpen(!dropdownOpen);
    };

    const handleLanguageChange = (language) => {
        setTargetLanguage(language); 
        setShowAdditionalLanguage(language === 'other');
        getComments(language); 
        setDropdownOpen(false); 
    };

    const handleAdditionalLanguageChange = (language) => {
        setAdditionalLanguage(language); 
        getComments(language);
        setShowAdditionalLanguage(false); 
    };

    return (
        <div
            className="tab-pane fade active show"
            id="customer-notes"
            role="tabpanel"
            aria-labelledby="customer-notes-tab"
        >
            {allComment.map((c, i) => (
                <div className="card card-widget widget-user-2" style={{ boxShadow: "none" }} key={i}>
                    <div className="card-comments cardforResponsive"></div>
                    <div className="card-comment p-3" style={{ backgroundColor: "rgba(0,0,0,.05)", borderRadius: "5px" }}>
                        <div className="row">
                            <div className="col-sm-10 col-10">
                                <p className="noteby p-1" style={{ fontSize: "16px" }}>
                                    {c.name} -
                                    <span className="noteDate" style={{ fontWeight: "600" }}>
                                        {" " + Moment(c.created_at).format("DD-MM-Y hh:mm a")}{" "}
                                        <br />
                                    </span>
                                </p>
                            </div>
                            <div className="col-sm-2 col-2">
                                <div className="float-right noteUser">
                                    {c.name === localStorage.getItem("client-name") && (
                                        <button
                                            className="ml-auto ml-md-2 btn bg-red"
                                            onClick={(e) => handleDelete(e, c.id)}
                                        >
                                            <i className="fa fa-trash"></i>
                                        </button>
                                    )}
                                </div>
                            </div>
                            <div className="col-sm-12">
                                <p className="rtl-comment">{c.comment}</p>
                                {c.attachments.map((cm, i) => (
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
                                ))}
                            </div>
                        </div>

                        <div className="d-flex justify-content-end">
                            <button
                                onClick={handleToggle}
                                type="button"
                                className="btn btn-primary mr-3"
                                data-toggle="modal"
                                data-target="#exampleModal"
                            >
                                {t("client.jobs.view.add_cmt")}
                            </button>

                            <div className="dropdown">
                                <button
                                    className="btn btn-default dropdown-toggle droptoggle navyblue text-white"
                                    type="button"
                                    onClick={toggleDropdown}
                                    aria-haspopup="true"
                                    aria-expanded={dropdownOpen}
                                >
                                    <i className="fa-solid fa-language"></i> Select Language
                                </button>
                                {dropdownOpen && (
                                    <div className="dropdown-menu show">
                                        {languageOptions.map((option) => (
                                            <button
                                                key={option.value}
                                                className="dropdown-item"
                                                onClick={() => handleLanguageChange(option.value)}
                                            >
                                                {option.label}
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </div>
                            
                            {showAdditionalLanguage && (
                                <select
                                    className="form-select me-2"
                                    value={additionalLanguage}
                                    onChange={(e) => handleAdditionalLanguageChange(e.target.value)}
                                >
                                    <option value="" disabled>Select Additional Language</option>
                                    {additionalLanguages.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                            )}
                        </div>
                    </div>
                </div>
            ))}

            <div className="modal fade" id="exampleModal" tabIndex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div className="modal-dialog modal-lg">
                    <div className="modal-content">
                        <div className="modal-header">
                            <h5 className="modal-title" id="exampleModalLabel">{t("client.jobs.view.add_cmt")}</h5>
                            <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div className="modal-body">
                            <form onSubmit={handleSubmit}>
                                <div className="form-group">
                                    <textarea
                                        rows="5"
                                        className="form-control"
                                        placeholder={t("client.jobs.view.enterCmt")}
                                        value={comment}
                                        onChange={(e) => setComment(e.target.value)}
                                        required
                                    />
                                </div>
                                <div className="form-group mt-2">
                                    <input
                                        ref={cmtFileRef}
                                        type="file"
                                        className="form-control"
                                        multiple
                                        onChange={handleToggle}
                                    />
                                </div>
                                <div className="modal-footer">
                                    <button type="button" className="btn btn-secondary" data-bs-dismiss="modal">{t("global.close")}</button>
                                    <button type="submit" className="btn btn-primary">{t("global.submit")}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div className="modal fade" id="exampleModalFile" tabIndex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div className="modal-dialog modal-lg">
                    <div className="modal-content">
                        <div className="modal-header">
                            <h5 className="modal-title" id="exampleModalLabel">{t("global.filePreview")}</h5>
                            <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div className="modal-body">
                            <img className="showFile" style={{ width: "100%" }} alt="Preview" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
