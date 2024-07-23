import React, { useState, useEffect } from "react";
import Sidebar from "../../Layouts/Sidebar";
import { useParams, useNavigate } from "react-router-dom";
import axios from "axios";
import { useAlert } from "react-alert";
import { Link } from "react-router-dom";
import moment from "moment";
import { template } from "lodash";
import CustomOffcanvas from "../../Components/shared/CustomOffcanvas";

export default function chat() {
    const [data, setData] = useState(null);
    const [messages, setMessages] = useState(null);
    const [selectNumber, setSelectNumber] = useState(null);
    const [clients, setClients] = useState(null);
    const [expired, setExpired] = useState(0);
    const [isOpen, setIsOpen] = useState(false);

    const handleClose = () => {
        setIsOpen(false);
    };
    const handleOpen = () => {
        setIsOpen(true);
    };
    const alert = useAlert();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getData = () => {
        axios.get(`/api/admin/chats`, { headers }).then((res) => {
            const r = res.data.data;
            setClients(res.data.clients);
            setData(r);
        });
    };

    const getMessages = (no) => {
        axios.get(`/api/admin/chat-message/${no}`, { headers }).then((res) => {
            const c = res.data.chat;
            let cl = localStorage.getItem("chatLen");
            if (cl > c.length) {
                scroller();
            }

            localStorage.setItem("chatLen", c.length);
            setExpired(res.data.expired);
            setMessages(c);
        });
    };

    const sendMessage = () => {
        let msg = document.getElementById("message_typing").value;
        if (msg == "") {
            alert.error("Please type message");
            return;
        }
        if (selectNumber == null) {
            alert.error("Please open chat of number");
            return;
        }
        const send = {
            number: selectNumber,
            message: msg,
            expired: expired,
        };
        axios.post(`/api/admin/chat-reply`, send, { headers }).then((res) => {
            document.getElementById("message_typing").value = "";
            getData();
            setTimeout(() => {
                scroller();
            }, 200);
        });
    };

    const callApi = () => {
        const interval = setInterval(() => {
            getMessages(localStorage.getItem("number"));
        }, 2000);
        return () => clearInterval(interval);
    };

    const scroller = () => {
        var objDiv = document.getElementById("ko");
        objDiv.scrollTop = objDiv.scrollHeight;
    };

    const restartChat = () => {
        let template = document.getElementById("template").value;
        let number = localStorage.getItem("number");
        if (template == "") {
            window.alert("Please select template");
            return;
        }

        const data = {
            template: template,
            number: number,
        };
        axios.post(`/api/admin/chat-restart`, data, { headers }).then((res) => {
            $("#cbtn").click();
            setExpired(0);
            getMessages(number);
            setTimeout(() => {
                scroller();
            }, 200);
        });
    };

    const search = (s) => {
        axios.get(`/api/admin/chat-search?s=${s}`, { headers }).then((res) => {
            const r = res.data.data;
            setClients(res.data.clients);
            setData(r);
        });
    };

    useEffect(() => {
        getData();
        if (localStorage.getItem("number")) {
            callApi();
        }
    }, []);

    const handleDeleteConversation = (e) => {
        e.preventDefault();
        if (selectNumber == null) {
            alert.error("Please open chat of number");
            return;
        }
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Delete conversation",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .post(
                        `/api/admin/delete-conversation/`,
                        { number: selectNumber },
                        { headers }
                    )
                    .then((response) => {
                        localStorage.removeItem("number");
                        Swal.fire("Deleted!", response.data.msg, "success");
                        setTimeout(() => {
                            getData();
                        }, 1000);
                    })
                    .catch((err) => {
                        Swal.fire("Error!", err.response.data.msg, "error");
                    });
            }
        });
    };

    const clientsCard = data
        ?.slice(0)
        .reverse()
        .sort((a, b) => b.unread - a.unread)
        .map((d, i) => {
            let cd = clients?.find(({ num }) => num == d.number);

            return (
                <div
                    className={"mb-3 card p-3 mt-3 cl_" + d.number}
                    style={
                        d.unread > 0
                            ? {
                                  background: "#e9dada",
                              }
                            : {}
                    }
                    onClick={(e) => {
                        getMessages(d.number);
                        setSelectNumber(d.number);
                        handleClose();
                        localStorage.setItem("number", d.number);
                        setTimeout(() => {
                            scroller();
                        }, 200);
                        document.querySelector(
                            ".cl_" + d.number
                        ).style.background = "#fff";
                        document.querySelector(".cn_" + d.number).remove();
                    }}
                    key={i}
                >
                    {cd && (
                        <h5
                            className="mt-0 mb-1"
                            style={{
                                cursor: "pointer",
                            }}
                        >
                            <Link
                                to={
                                    cd.client == 1
                                        ? `/admin/clients/view/${cd.id}`
                                        : `/admin/leads/view/${cd.id}`
                                }
                            >
                                <i className="fas fa-user"></i>
                                {cd.name}
                            </Link>
                        </h5>
                    )}
                    <h6
                        className="mt-0 mb-1"
                        style={{
                            cursor: "pointer",
                        }}
                    >
                        <i className="fas fa-phone"></i>
                        {d.number}
                        {d.unread > 0 && (
                            <span
                                className={"text-danger p-2 cn_" + d.number}
                            >{`(${d.unread})`}</span>
                        )}
                    </h6>
                </div>
            );
        });
    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="view-applicant">
                    <div className="row">
                        <div className="col-lg-6 col-12">
                            <h1 className="page-title">Chat History</h1>
                        </div>
                        <div className="col-sm-6 d-none d-lg-block text-right page-title">
                            <input
                                type="text"
                                name="smsg"
                                className="form-control"
                                onChange={(e) => search(e.target.value)}
                                placeholder="search name or number"
                                style={{ float: "right", width: "55%" }}
                            />
                        </div>
                    </div>
                    <div>
                        <input
                            type="text"
                            name="smsg"
                            className="form-control d-lg-none"
                            onChange={(e) => search(e.target.value)}
                            placeholder="search name or number"
                            style={{ float: "right", width: "55%" }}
                        />
                        <button
                            className="btn btn-danger d-flex d-xl-none mb-3"
                            onClick={handleOpen}
                        >
                            Clients
                        </button>
                    </div>
                    <CustomOffcanvas isOpen={isOpen} handleClose={handleClose}>
                        {clientsCard}
                    </CustomOffcanvas>
                    <div className="card">
                        <div className="card-body">
                            <div className="row">
                                <div className="col-xl-8  col-12">
                                    <h4 className="header-title mb-3">
                                        Replies
                                        <button
                                            type="button"
                                            className="btn btn-danger text-right float-right"
                                            onClick={(e) =>
                                                handleDeleteConversation(e)
                                            }
                                        >
                                            <i className="fa fa-trash"></i>
                                        </button>
                                    </h4>
                                    <hr />
                                    <div className="chat-conversation">
                                        <div
                                            data-simplebar="init"
                                            style={{ height: "600px" }}
                                        >
                                            <div
                                                className="simplebar-wrapper"
                                                style={{ margin: "0px" }}
                                            >
                                                <div
                                                    chat-content=""
                                                    id="ko"
                                                    style={{
                                                        overflowY: "scroll",
                                                        width: "auto",
                                                        height: "580px",
                                                    }}
                                                >
                                                    <div
                                                        className="simplebar-content"
                                                        style={{
                                                            padding: "0px",
                                                        }}
                                                    >
                                                        <ul
                                                            className="conversation-list"
                                                            style={{
                                                                fontFamily:
                                                                    "sans-serif",
                                                            }}
                                                        >
                                                            {messages?.map(
                                                                (m, i) => {
                                                                    if (
                                                                        m.message !=
                                                                        "restart"
                                                                    ) {
                                                                        return (
                                                                            <li
                                                                                className={
                                                                                    m.flex ==
                                                                                    "C"
                                                                                        ? "clearfix "
                                                                                        : "clearfix odd"
                                                                                }
                                                                                key={
                                                                                    i
                                                                                }
                                                                            >
                                                                                <div className="chat-avatar">
                                                                                    <img
                                                                                        src="/images/chat.png"
                                                                                        alt="chatIcon"
                                                                                    />
                                                                                </div>
                                                                                <div className="conversation-text">
                                                                                    <div className="ctext-wrap card">
                                                                                        <p>
                                                                                            {
                                                                                                m.message
                                                                                            }
                                                                                        </p>
                                                                                        <br />
                                                                                        <small>
                                                                                            {new Date(
                                                                                                m.created_at
                                                                                            ).toLocaleString(
                                                                                                "en-GB"
                                                                                            )}
                                                                                        </small>
                                                                                    </div>
                                                                                </div>
                                                                            </li>
                                                                        );
                                                                    }
                                                                }
                                                            )}
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div
                                                className="simplebar-track simplebar-horizontal"
                                                style={{ visibility: "hidden" }}
                                            >
                                                <div
                                                    className="simplebar-scrollbar"
                                                    style={{
                                                        width: "0px",
                                                        display: "none",
                                                    }}
                                                ></div>
                                            </div>
                                            <div
                                                className="simplebar-track simplebar-vertical"
                                                style={{
                                                    visibility: "visible",
                                                }}
                                            >
                                                <div
                                                    className="simplebar-scrollbar"
                                                    style={{
                                                        height: "348px",
                                                        transform:
                                                            "translate3d(0px, 0px, 0px)",
                                                        display: "block",
                                                    }}
                                                ></div>
                                            </div>
                                        </div>

                                        {expired == 0 ? (
                                            <div
                                                className="row m-3"
                                                style={{
                                                    marginTop: "2px",
                                                    width: "90%",
                                                }}
                                            >
                                                <div className="input-group">
                                                    <input
                                                        type="hidden"
                                                        name="support_id"
                                                        value="1"
                                                    />
                                                    <input
                                                        type="text"
                                                        name="message"
                                                        id="message_typing"
                                                        onKeyDown={(e) =>
                                                            e.key === "Enter"
                                                                ? sendMessage()
                                                                : ""
                                                        }
                                                        chat-box=""
                                                        className="form-control"
                                                        placeholder="Type..."
                                                    />
                                                    <div className="input-group-prepend">
                                                        <button
                                                            type="button"
                                                            id="submitMessage"
                                                            onClick={(e) =>
                                                                sendMessage()
                                                            }
                                                            className="btn chat-send btn-block waves-effect waves-light"
                                                            style={{
                                                                background:
                                                                    "#00a4f39e!important",
                                                                color: "black",
                                                            }}
                                                        >
                                                            <i className="fas fa-sharp fa-light fa-paper-plane"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        ) : (
                                            <div className="input-group">
                                                <div className="text-center">
                                                    <button
                                                        type="button"
                                                        className="btn btn-info text-white"
                                                        data-toggle="modal"
                                                        data-target="#exampleModalTemplate"
                                                    >
                                                        Restart Chat{" "}
                                                        <i className="fas fa-refresh"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                <div
                                    className="card col-sm-4 card-body sidemsg d-none d-xl-flex"
                                    style={{
                                        backgroundColor: "#00a4f39e!important",
                                        borderRadius: "3%",
                                    }}
                                >
                                    {clientsCard}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                className="modal fade"
                id="exampleModalTemplate"
                tabIndex="-1"
                role="dialog"
                aria-labelledby="exampleModalLabel"
                aria-hidden="true"
            >
                <div className="modal-dialog" role="document">
                    <div className="modal-content" style={{ width: "130%" }}>
                        <div className="modal-header">
                            <h5 className="modal-title" id="exampleModalLabel">
                                Template
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
                                        <select
                                            className="form-control"
                                            name="template"
                                            id="template"
                                        >
                                            <option value="">
                                                --- Select template ---
                                            </option>
                                            <option value="leads">
                                                {" "}
                                                leads{" "}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="modal-footer">
                            <button
                                type="button"
                                className="btn btn-primary"
                                onClick={(e) => restartChat()}
                            >
                                Send
                            </button>
                            <button
                                type="button"
                                className="btn btn-secondary"
                                id="cbtn"
                                data-dismiss="modal"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
