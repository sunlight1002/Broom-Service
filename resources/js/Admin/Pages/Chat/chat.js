import React, { useState, useEffect } from "react";
import Sidebar from "../../Layouts/Sidebar";
import { useParams, useNavigate } from "react-router-dom";
import axios from "axios";
import { useAlert } from "react-alert";
import { Link } from "react-router-dom";
import moment from "moment";
import { template } from "lodash";
import CustomOffcanvas from "../../Components/shared/CustomOffcanvas";
import { useTranslation } from "react-i18next";
import { FaRegCircleUser } from "react-icons/fa6";
import useWindowWidth from "../../../Hooks/useWindowWidth";

export default function chat() {

    const { t } = useTranslation();
    const [data, setData] = useState(null);
    const [messages, setMessages] = useState(null);
    const [selectNumber, setSelectNumber] = useState(null);
    const [clients, setClients] = useState(null);
    const [expired, setExpired] = useState(0);
    const [isOpen, setIsOpen] = useState(false);
    const [chatName, setChatName] = useState("")
    const [isChatOpen, setIsChatOpen] = useState(false);
    const [selectedChat, setSelectedChat] = useState(null);
    const [showChatList, setShowChatList] = useState(true);
    const [showChatContainer, setShowChatContainer] = useState(false);

    const windowWidth = useWindowWidth();
    // console.log(windowWidth);

    useEffect(() => {
        if (windowWidth < 1199) {
            setShowChatList(true)
        } else {
            setShowChatList(false)
        }
    }, [windowWidth])



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
            setChatName(res?.data?.clientName)

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

    function escapeSelectorClass(className) {
        return className.replace(/([ :#.+,])/g, '\\$1');
    }

    const clientsCard = data
        ?.slice(0)
        .reverse()
        .sort((a, b) => b.unread - a.unread)
        .map((d, i) => {
            let cd = clients?.find(({ num }) => num == d.number);
            const escapedClassName = escapeSelectorClass(".cn_" + d.number);

            return (
                <div
                    className={"card p-3 cardList cl_" + d.number}
                    style={
                        d.unread > 0
                            ? {
                                background: "#e9dada",
                                boxShadow: "none",
                                marginBottom: "0",
                                borderRadius: "0",
                                borderBottom: "1px solid #E5EBF1"
                            }
                            : {
                                background: "#fff",
                                boxShadow: "none",
                                marginBottom: "0",
                                borderRadius: "0",
                                borderBottom: "1px solid #E5EBF1"
                            }
                    }
                    onClick={(e) => {
                        getMessages(d.number);
                        setSelectNumber(d.number);
                        setSelectedChat(d.number);  // Set the selected chat
                        setShowChatList(false);
                        handleClose();
                        localStorage.setItem("number", d.number);
                        setTimeout(() => {
                            scroller();
                        }, 200);

                        // Safely handle the removal of the element with escaped class name
                        const unreadElement = document.querySelector(escapedClassName);
                        if (unreadElement) {
                            unreadElement.remove();
                        }
                    }}
                    key={i}
                >
                    <div className="d-flex align-items-center">
                        <div className="user-icon2">
                            <FaRegCircleUser className="font-24" style={{ color: "#2F4054" }} />
                        </div>
                        <div className="ml-2">
                            {cd && (
                                <h5
                                    className="mt-0 mb-2"
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
                                        {cd.name}
                                    </Link>
                                </h5>
                            )}
                            <h6
                                className="mt-0 mb-1"
                                style={{
                                    cursor: "pointer",
                                    display: "flex",
                                    alignItems: "center",
                                }}
                            >
                                <i className="fas fa-phone mr-2"></i>
                                {d.number}
                                {d.unread > 0 && (
                                    <span
                                        className={"text-danger p-2 cn_" + d.number}
                                    >{`(${d.unread})`}</span>
                                )}
                            </h6>
                        </div>
                    </div>
                </div>
            );
        });

    const handleBack = () => {
        setShowChatList(true)
        setShowChatContainer(true);
        setSelectedChat(null);
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="view-applicant">
                    {
                        showChatList && (
                            <div className="mobileView">
                                <div className="card" style={{ boxShadow: "none" }}>
                                    <div className="card-body">
                                        <div className="row">
                                            <div className="col-xl-8 col-12" style={{ backgroundColor: "white" }}>
                                                {clientsCard}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )
                    }
                    {
                        !showChatList && (
                            <div className="card mb-0 " style={{ boxShadow: "none" }}>
                                <div className="card-body">

                                    <div className="row">
                                        <div
                                            className="card col-sm-4 card-body sidemsg mb-0 d-none d-xl-flex"
                                            style={{
                                                borderRadius: "0",
                                                boxShadow: "none",
                                                borderRight: "1px solid #E5EBF1"
                                            }}
                                        >
                                            <div className="d-none mb-3 d-lg-block position-relative">
                                                <input
                                                    type="text"
                                                    name="smsg"
                                                    className="form-control search-input"
                                                    onChange={(e) => search(e.target.value)}
                                                    placeholder="Search name or number"
                                                />
                                                <i className="fas fa-search search-icon"></i>
                                            </div>
                                            {clientsCard}
                                        </div>
                                        <div className="col-xl-8 col-12" style={{ backgroundColor: "white" }}>
                                            <div className="d-flex justify-content-between align-items-center my-2">
                                                <h4 className="header-title d-flex mr-2 align-items-center">
                                                    <button
                                                        className="btn navyblue mr-2 d-lg-none"
                                                        onClick={handleBack}
                                                        style={{ borderRadius: "50%" }}
                                                    >
                                                        <i className="fa-solid fa-arrow-left"></i>
                                                    </button>
                                                    <div className="user-icon2">
                                                        <FaRegCircleUser className="font-24" style={{ color: "#2F4054" }} />
                                                    </div>
                                                    {chatName}
                                                </h4>
                                                <button
                                                    type="button"
                                                    className="btn navyblue text-right float-right py-1 px-2"
                                                    onClick={(e) => handleDeleteConversation(e)}
                                                >
                                                    <i className="fa fa-trash"></i>
                                                </button>
                                            </div>
                                            <hr style={{ marginTop: "0" }} />
                                            {/* Chat messages */}
                                            <div className="chat-conversation">
                                                <div data-simplebar="init" style={{ minHeight: "72vh" }}>
                                                    <div className="simplebar-wrapper" style={{ margin: "0px" }}>
                                                        <div
                                                            chat-content=""
                                                            id="ko"
                                                            style={{
                                                                overflowY: "scroll",
                                                                width: "auto",
                                                                height: "72vh",
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
                                                                        fontFamily: "sans-serif",
                                                                    }}
                                                                >
                                                                    {messages?.map((m, i) => {
                                                                        if (m.message != "restart") {
                                                                            return (
                                                                                <li className={m.flex == "C" ? "clearfix " : "clearfix odd"} key={i}>
                                                                                    <div className="conversation-text">
                                                                                        <div className="ctext-wrap card" style={{
                                                                                            boxShadow: "none",
                                                                                            border: "1px solid #DDE2E8",
                                                                                            backgroundColor: m.flex === "C" ? "#E5EBF1" : "rgb(247 247 247 / 39%)" // Conditional background color
                                                                                        }}>
                                                                                            <div className="d-flex justify-content-between align-items-center">
                                                                                                <span>{m.flex == "C" ? chatName : "You"}</span>
                                                                                                <small>
                                                                                                    {new Date(m.created_at).toLocaleString("en-GB")}
                                                                                                </small>
                                                                                            </div>
                                                                                            <p>{m.message}</p>
                                                                                        </div>
                                                                                    </div>
                                                                                </li>
                                                                            );
                                                                        }
                                                                    })}
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div className="simplebar-track simplebar-horizontal" style={{ visibility: "hidden" }}>
                                                        <div className="simplebar-scrollbar" style={{ width: "0px", display: "none" }}></div>
                                                    </div>
                                                    <div className="simplebar-track simplebar-vertical" style={{ visibility: "visible" }}>
                                                        <div className="simplebar-scrollbar" style={{ transform: "translate3d(0px, 0px, 0px)", display: "block" }}></div>
                                                    </div>
                                                </div>

                                                {expired == 0 ? (
                                                    <div className="row m-3" style={{ marginTop: "2px", width: "90%" }}>
                                                        <div className="input-group">
                                                            <input type="hidden" name="support_id" value="1" />
                                                            <input
                                                                type="text"
                                                                name="message"
                                                                id="message_typing"
                                                                onKeyDown={(e) => e.key === "Enter" ? sendMessage() : ""}
                                                                chat-box=""
                                                                className="form-control"
                                                                placeholder="Enter your message..."
                                                                style={{ borderRadius: "5px" }}
                                                            />
                                                            <div className="input-group-prepend ml-2">
                                                                <button
                                                                    type="button"
                                                                    id="submitMessage"
                                                                    onClick={(e) => sendMessage()}
                                                                    style={{ borderRadius: "5px" }}
                                                                    className="btn chat-send btn-block navyblue waves-effect waves-light px-4"
                                                                >
                                                                    Send
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
                                                                {t("admin.global.restartChat")} <i className="fas fa-refresh"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )
                    }
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
                                {t("admin.global.template")}
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
                                                {t("admin.global.selectTemplate")}
                                            </option>
                                            <option value="leads">
                                                {" "}
                                                {t("admin.global.leads")}{" "}
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
                                {t("global.send")}
                            </button>
                            <button
                                type="button"
                                className="btn btn-secondary"
                                id="cbtn"
                                data-dismiss="modal"
                            >
                                {t("global.close")}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
