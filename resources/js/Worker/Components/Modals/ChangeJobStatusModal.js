import { useEffect, useState, useMemo, useRef, memo } from "react";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";
import Moment from "moment";
import Swal from "sweetalert2";
import { useTranslation } from "react-i18next";

export default function ChangeJobStatusModal({
    setIsOpen,
    isOpen,
    jobId,
    allComment = [],
    jobStatus,
    onSuccess,
    handleGetComments,
    setTargetLanguage,
    setJobId,
    setCommentId,
    skippedComments
}) {
    const alert = useAlert();
    const [isLoading, setIsLoading] = useState(false);
    const [status, setStatus] = useState("");
    const [allCommentsChecked, setAllCommentsChecked] = useState([]);
    const [comment, setComment] = useState("");

    let cmtFileRef = useRef(null);

    const { t } = useTranslation();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        setIsLoading(true);
        const data = new FormData();
        data.append("job_id", jobId);
        data.append("comment", comment);
        data.append("status", "completed");
        data.append("name", localStorage.getItem("worker-name"));
        if (cmtFileRef.current && cmtFileRef.current.files.length > 0) {
            for (let index = 0; index < cmtFileRef.current.files.length; index++) {
                const element = cmtFileRef.current.files[index];
                data.append("files[]", element);
            }
        }
        axios
            .post(`/api/job-comments`, data, { headers })
            .then((res) => {
                if (res.data.error) {
                    res.data.error.forEach((err) => window.alert(err));
                } else {
                    alert.success(t("worker.jobs.view.jobMarkCompleted"));
                    onSuccess();
                    setComment("");
                }
                setIsLoading(false);
            })
            .catch((e) => {
                setIsLoading(false);
            });
    };



    return (
        <Modal
            size="md"
            className="modal-container"
            show={isOpen}
            onHide={() => {
                setIsOpen(false);
            }}
            backdrop="static"
        >
            <Modal.Header closeButton>
                <Modal.Title>{t("worker.jobs.view.CompleteJob")}</Modal.Title>
            </Modal.Header>

            <Modal.Body>
                <div className="row">
                    <div className="col-sm-12">
                        <div className="form-group">
                            {allComment.length > 0 && (
                                <AllCommentsWithCheckBox
                                    allComment={allComment}
                                    setAllCommentChecked={setAllCommentsChecked}
                                    handleGetComments={handleGetComments}
                                    setTargetLanguage={setTargetLanguage}
                                    setJobId={setJobId}
                                    setCommentId={setCommentId}
                                />
                            )}
                            {/* <label className="control-label">
                                {t("worker.jobs.view.status_warning")}
                            </label>
                            <select
                                value={status}
                                onChange={(e) => setStatus(e.target.value)}
                                className="form-control"
                            >
                                <option value="">--- Job Status ---</option>
                                {jobStatus !== "completed" && (
                                    <option value="completed">Completed</option>
                                )}
                                <option value="unscheduled">Unavailable</option>
                            </select> */}
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
                                onChange={(e) => setComment(e.target.value)}
                                className="form-control"
                                required
                                placeholder={t("worker.jobs.view.cmt_box")}
                            ></textarea>
                        </div>
                    </div>
                    <div className="col-sm-12">
                        <div className="form-group">
                            <label htmlFor="cmtFiles" className="form-label">
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
            </Modal.Body>

            <Modal.Footer>
                <Button
                    type="button"
                    className="btn btn-secondary"
                    onClick={() => {
                        setIsOpen(false);
                    }}
                >
                    {t("worker.jobs.view.close")}
                </Button>
                <Button
                    type="button"
                    disabled={isLoading}
                    onClick={handleSubmit}
                    className="btn btn-primary"
                >
                    {t("worker.jobs.view.save_cmt")}
                </Button>
            </Modal.Footer>
        </Modal>
    );
}

const AllCommentsWithCheckBox = memo(({ allComment, setAllCommentChecked, setJobId, handleGetComments, setCommentId, setTargetLanguage }) => {
    const [modifiedComments, setModifiedComments] = useState([]);

    const [checkedCommentIds, setCheckedCommentIds] = useState([]); // Track the checked comment IDs
    const [dropdownOpen, setDropdownOpen] = useState(Array(allComment?.length).fill(false));

    const languageOptions = [
        { value: 'he', label: 'עִברִית' },
        { value: 'ru', label: 'Русский' },
        { value: 'en', label: 'English' },
    ];

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

    useEffect(() => {
        const addCheckProperty = allComment.map((c) => ({
            ...c,
            checked: false,
        }));
        setModifiedComments(addCheckProperty);
    }, [allComment]);

    // Update the checked state of all comments when modifiedComments change
    useEffect(() => {
        setAllCommentChecked(checkedCommentIds);
    }, [checkedCommentIds, setAllCommentChecked]);

    const handleCheckboxChange = (checked, comment, index) => {
        // Update the modified comments
        setModifiedComments((prev) => {
            const updatedComments = [...prev];
            updatedComments[index].checked = checked;
            return updatedComments;
        });

        // Update the checkedCommentIds array
        if (checked) {
            setCheckedCommentIds((prev) => [...prev, comment.id]);
        } else {
            setCheckedCommentIds((prev) => prev.filter(id => id !== comment.id));
        }
    };

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
                                checked={c.status === "complete" ? true : false}
                                onChange={(e) =>
                                    handleCheckboxChange(e.currentTarget.checked, c, i)
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
                        <div className="dropdown">
                            <button
                                className="btn btn-default dropdown-toggle droptoggle"
                                type="button"
                                onClick={() => toggleDropdown(i)}
                                aria-haspopup="true"
                                aria-expanded={dropdownOpen[i]}
                                style={{ backgroundColor: "#f7f3f3" }}
                            >
                                <i className="fa-solid fa-language"></i>
                            </button>
                            <div className="dropdown-menu"
                                style={dropdownOpen[i] ? { display: "block", left: "-100px" } : { display: "none" }}
                            >
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
    });
});


