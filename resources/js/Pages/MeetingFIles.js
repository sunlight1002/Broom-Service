import axios from "axios";
import React, { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import Moment from "moment";
import { useTranslation } from "react-i18next";
import i18next from "i18next";
import { Base64 } from "js-base64";
import logo from "../Assets/image/sample.svg";
import { useAlert } from "react-alert";
import { Link } from "react-router-dom";
import Swal from "sweetalert2";
import useToggle from "../Hooks/useToggle";
import FullPageLoader from "../Components/common/FullPageLoader";

export default function MeetingFiles() {
    const param = useParams();
    const { t } = useTranslation();
    const alert = useAlert();

    const [meeting, setMeeting] = useState([]);
    const [teamName, setTeamName] = useState("");
    const [note, setNote] = useState("");
    const [file, setFile] = useState([]);
    const [AllFiles, setAllFiles] = useState([]);
    const [loading, setLoading] = useState("Loading...");
    const [address, setAddress] = useState(null);
    const [toggle, toggleValue] = useToggle(false);
    const [filetype, setFiletype] = useState("image")
    const [loader, setLoader] = useState(false);
    const meetId = Base64.decode(param.id);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
    };

    const getMeeting = () => {
        setLoader(true);
        axios
            .post(`/api/client/meeting`, { id: Base64.decode(param.id) })
            .then((res) => {
                setLoader(false);
                const { schedule } = res.data;
                setMeeting(schedule);
                setTeamName(schedule.team?.name);
                setAddress(
                    schedule.property_address ? schedule.property_address : null
                );
                const lng = schedule.client.lng;
                i18next.changeLanguage(lng);
                if (lng == "heb") {
                    import("../Assets/css/rtl.css");
                    document.querySelector("html").setAttribute("dir", "rtl");
                } else {
                    document.querySelector("html").removeAttribute("dir");
                    const rtlLink = document.querySelector('link[href*="rtl.css"]');
                    if (rtlLink) {
                        rtlLink.remove();
                    }
                }
            });
    };
    const handleFileChange = (e) => {
        if (e.target.files.length > 0) {
            let file = e.target.files[0];
            const sanitizedFileName = file.name.replace(/\s+/g, "_");
            const type = file.type.split("/")[1];
            const allow = filetype === "image" ? ["jpeg", "png", "jpg"] : ["mp4", "webm"];

            if (allow.includes(type)) {
                const sanitizedFile = new File([file], sanitizedFileName, { type: file.type });
                setFile(sanitizedFile);
            } else {
                setFile([]);
                window.alert("This file is not allowed");
                e.target.value = ""; // Reset the file input
            }
        }
    };


    const handleFile = (e) => {
        e.preventDefault();
        setLoader(true);
        if (file.length == 0) {
            window.alert("Please add file");
            return;
        }
        toggleValue(true);
        const type = document.querySelector('select[name="filetype"]').value;
        const fd = new FormData();
        fd.append("user_id", meeting.client_id);
        fd.append("note", note);
        fd.append("meeting", meetId);
        fd.append("file", file);
        fd.append("type", type);
        fd.append("role", "client");

        const videoHeader = {
            Accept: "application/octet-stream, text/plain, */*",
            "Content-Type": "application/octet-stream",
        };
        axios
            .post(`/api/client/add-file`, fd, {
                headers: type == "image" ? headers : videoHeader,
            })
            .then((res) => {
                setLoader(false);
                if (res.data.error) {
                    for (let e in res.data.error) {
                        window.alert(res.data.error[e]);
                    }
                } else {
                    document.querySelector(".closeb").click();
                    alert.success(res.data.message);
                    setTimeout(() => {
                        getFiles();
                    }, 1000);
                    setNote("");
                    setFile([]);
                    document.querySelector('input[type="file"]').value = "";
                }
                toggleValue(false);

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
            confirmButtonText: "Yes, Delete File",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .post(`/api/client/delete-file/`, { id: id }, { headers })
                    .then((response) => {
                        Swal.fire(
                            "Deleted!",
                            "File has been deleted.",
                            "success"
                        );
                        setTimeout(() => {
                            getFiles();
                        }, 1000);
                    });
            }
        });
    };
    const getFiles = () => {
        setLoader(true);
        if (meeting.client_id) {
            axios
                .post(
                    `/api/client/get-files`,
                    { id: meeting.client_id, meet_id: meetId },
                    { headers }
                )
                .then((res) => {
                    setLoader(false);
                    setAllFiles(res.data.files);
                    if (!res.data.files.length) {
                        setLoading(t("meet_stat.no_file"));
                    }
                });
        } else {
            setAllFiles([]);
            setLoading(t("meet_stat.no_file"));
        }
    };
    useEffect(() => {
        getMeeting();
        setTimeout(() => {
            document.querySelector(".meeting").style.display = "block";
        }, 1000);
    }, []);

    useEffect(() => {
        getFiles();
    }, [meeting.client_id]);

    const dt = Moment(meeting.start_date).format("DD-MM-Y");

    const timeFormat = (intime) => {
        if (intime != undefined) {
            const [time, modifier] = intime.toString().split(" ");
            let [hours, minutes] = time.split(":");

            if (hours === "12") {
                hours = "00";
            }

            if (modifier === "PM") {
                hours = parseInt(hours, 10) + 12;
            }

            return `${hours}:${minutes}`;
        }
    };
    return (
        <div className="container meeting" style={{ display: "none" }}>
            <div className="thankyou meet-status dashBox maxWidthControl p-4">
                <svg
                    width="190"
                    height="77"
                    xmlns="http://www.w3.org/2000/svg"
                    xmlnsXlink="http://www.w3.org/1999/xlink"
                >
                    <image xlinkHref={logo} width="190" height="77"></image>
                </svg>
                <h1>
                    {t("meet_stat.with")} {teamName}
                </h1>
                <ul className="list-unstyled">
                    <li>
                        {t("meet_stat.date")}: <span>{dt}</span>
                    </li>
                    <li>
                        {t("meet_stat.time")}:{" "}
                        <span>
                            {timeFormat(meeting.start_time)} {t("meet_stat.to")}{" "}
                            {timeFormat(meeting.end_time)}
                        </span>
                    </li>
                    {address ? (
                        <li>
                            {t("meet_stat.address")}:{" "}
                            <span>
                                <Link
                                    target="_blank"
                                    to={`https://maps.google.com?q=${address.geo_address}`}
                                >
                                    {address.geo_address}
                                </Link>
                            </span>
                        </li>
                    ) : (
                        ""
                    )}
                </ul>
                <div className="cta">
                    <div id="content">
                        <div className="titleBox customer-title">
                            <div className="row">
                                <div className="col-sm-6">
                                    <h1 className="page-title">
                                        {t("client.meeting.cfiles.title")}
                                    </h1>
                                </div>
                                <div className="col-sm-6">
                                    <div className="search-data">
                                        <Link
                                            className="btn btn-pink addButton"
                                            data-toggle="modal"
                                            data-target="#exampleModal"
                                        >
                                            <i className="btn-icon fas fa-plus-circle"></i>
                                            {t("client.meeting.cfiles.button")}
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="card">
                            <div className="card-body">
                                <div className="boxPanel">
                                    <div className="table-responsive">
                                        {AllFiles.length > 0 ? (
                                            <table className="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">
                                                            {t(
                                                                "client.meeting.cfiles.upload_date"
                                                            )}
                                                        </th>
                                                        <th scope="col">
                                                            {t(
                                                                "client.meeting.cfiles.note"
                                                            )}
                                                        </th>
                                                        <th scope="col">
                                                            {t(
                                                                "client.meeting.cfiles.action"
                                                            )}
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {AllFiles &&
                                                        AllFiles.map(
                                                            (item, index) => {
                                                                return (
                                                                    <tr
                                                                        key={
                                                                            index
                                                                        }
                                                                    >
                                                                        <td>
                                                                            {Moment(
                                                                                item.created_at
                                                                            ).format(
                                                                                "DD/MM/Y"
                                                                            )}
                                                                        </td>

                                                                        <td>
                                                                            {item.note
                                                                                ? item.note
                                                                                : "NA"}
                                                                        </td>

                                                                        <td>
                                                                            <a
                                                                                onClick={(
                                                                                    e
                                                                                ) => {
                                                                                    let show =
                                                                                        document.querySelector(
                                                                                            ".showFile"
                                                                                        );
                                                                                    let showvideo =
                                                                                        document.querySelector(
                                                                                            ".showvideo"
                                                                                        );
                                                                                    if (
                                                                                        item.type ==
                                                                                        "image"
                                                                                    ) {
                                                                                        show.setAttribute(
                                                                                            "src",
                                                                                            item.path
                                                                                        );
                                                                                        show.style.display =
                                                                                            "block";
                                                                                        showvideo.style.display =
                                                                                            "none";
                                                                                    } else {
                                                                                        showvideo.setAttribute(
                                                                                            "src",
                                                                                            item.path
                                                                                        );
                                                                                        showvideo.style.display =
                                                                                            "block";
                                                                                        show.style.display =
                                                                                            "none";
                                                                                    }
                                                                                }}
                                                                                data-toggle="modal"
                                                                                data-target="#exampleModalFile"
                                                                                className="btn bg-yellow"
                                                                            >
                                                                                <i className="fa fa-eye"></i>
                                                                            </a>
                                                                            <button
                                                                                className="ml-2 btn bg-red"
                                                                                onClick={(
                                                                                    e
                                                                                ) =>
                                                                                    handleDelete(
                                                                                        e,
                                                                                        item.id
                                                                                    )
                                                                                }
                                                                            >
                                                                                <i className="fa fa-trash"></i>
                                                                            </button>
                                                                        </td>
                                                                    </tr>
                                                                );
                                                            }
                                                        )}
                                                </tbody>
                                            </table>
                                        ) : (
                                            <p className="text-center mt-5">
                                                {t("meet_stat.no_file")}
                                            </p>
                                        )}
                                    </div>

                                    <div
                                        className="modal fade"
                                        id="exampleModal"
                                        tabIndex="-1"
                                        role="dialog"
                                        aria-labelledby="exampleModalLabel"
                                        aria-hidden="true"
                                    >
                                        <div
                                            className="modal-dialog"
                                            role="document"
                                        >
                                            <div className="modal-content">
                                                <div className="modal-header">
                                                    <h5
                                                        className="modal-title"
                                                        id="exampleModalLabel"
                                                    >
                                                        {t(
                                                            "client.meeting.cfiles.add_file"
                                                        )}
                                                    </h5>
                                                    <button
                                                        type="button"
                                                        className="close"
                                                        data-dismiss="modal"
                                                        aria-label="Close"
                                                    >
                                                        <span aria-hidden="true">
                                                            &times;
                                                        </span>
                                                    </button>
                                                </div>
                                                <div className="modal-body">
                                                    <div className="row">
                                                        <div className="col-sm-12">
                                                            <div className="form-group">
                                                                <label className="control-label">
                                                                    {t(
                                                                        "client.meeting.cfiles.note_label"
                                                                    )}
                                                                </label>
                                                                <textarea
                                                                    type="text"
                                                                    value={note}
                                                                    onChange={(
                                                                        e
                                                                    ) =>
                                                                        setNote(
                                                                            e
                                                                                .target
                                                                                .value
                                                                        )
                                                                    }
                                                                    className="form-control"
                                                                    required
                                                                    placeholder={t(
                                                                        "client.meeting.cfiles.note_box"
                                                                    )}
                                                                ></textarea>
                                                            </div>
                                                        </div>
                                                        <div className="col-sm-12">
                                                            <div className="form-group">
                                                                <label className="control-label">
                                                                    {t(
                                                                        "client.meeting.cfiles.type"
                                                                    )}
                                                                </label>
                                                                <select
                                                                    name="filetype"
                                                                    className="form-control"
                                                                    value={filetype}
                                                                    onChange={(e) => setFiletype(e.target.value)}
                                                                >
                                                                    <option value="image">
                                                                        {t(
                                                                            "client.meeting.cfiles.type_img"
                                                                        )}
                                                                    </option>
                                                                    <option value="video">
                                                                        {t(
                                                                            "client.meeting.cfiles.type_video"
                                                                        )}
                                                                    </option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div className="col-sm-12">
                                                            <div className="form-group">
                                                                <label className="control-label">
                                                                    {t("client.meeting.cfiles.file")} *
                                                                </label>
                                                                <input
                                                                    accept={filetype === "image" ? "image/*" : "video/*"}
                                                                    type="file"
                                                                    name="file"
                                                                    onChange={(e) => handleFileChange(e)}
                                                                    className="form-control"
                                                                    required
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
                                                        {t(
                                                            "client.meeting.cfiles.close"
                                                        )}
                                                    </button>
                                                    <button
                                                        disabled={toggle}
                                                        type="button"
                                                        onClick={handleFile}
                                                        className="btn btn-primary"
                                                    >
                                                        {t(
                                                            "client.meeting.cfiles.save"
                                                        )}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
                            <div
                                className="modal-content"
                                style={{ width: "130%" }}
                            >
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
                                                <video
                                                    className="form-control showvideo"
                                                    controls
                                                >
                                                    <source
                                                        src=""
                                                        type="video/mp4"
                                                    />
                                                </video>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            { loader && <FullPageLoader visible={loader} />}
        </div>
    );
}
