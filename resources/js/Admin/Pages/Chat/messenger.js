import React, { useState, useEffect } from "react";
import Sidebar from "../../Layouts/Sidebar";
import { useParams, useNavigate } from "react-router-dom";
import axios from "axios";
import { useAlert } from "react-alert";
import { Link } from "react-router-dom";
import moment from "moment";
import { template } from "lodash";
import { useTranslation } from "react-i18next";
import { FaRegCircleUser } from "react-icons/fa6";
import { FaUsers, FaTimes } from "react-icons/fa"; // Make sure to import the icons


export default function Messenger() {
    const { t } = useTranslation();
    const [data, setData] = useState(null);
    const [messages, setMessages] = useState(null);
    const [selectNumber, setSelectNumber] = useState(null);
    const [pageId, setPageId] = useState("");
    const [showSide, setShowSide] = useState(false);
    const [activeTab, setActiveTab] = useState('Messenger');

    const tabs = ['Messenger', 'Instagram', 'Comments'];

    const alert = useAlert();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getData = () => {
        axios
            .get(`/api/admin/messenger-participants`, { headers })
            .then((res) => {
                const r = res.data.data.data;
                setPageId(res.data.page_id);
                setData(r);
            });
    };

    const getMessages = (no) => {
        axios
            .get(`/api/admin/messenger-message/${no}`, { headers })
            .then((res) => {
                const c = res.data.chat.messages.data;

                let cl = localStorage.getItem("chatLenM");
                if (cl > c.length) {
                    scroller();
                }

                localStorage.setItem("chatLenM", c.length);
                setMessages(c);
            });
    };

    const sendMessage = () => {
        let msg = document.getElementById("message_typing").value;
        let pid = localStorage.getItem("participant_id");
        if (msg == "") {
            alert.error("Please type message");
            return;
        }
        if (pid == null || pid == undefined) {
            alert.error("Please select sender");
            return;
        }
        const send = {
            pid: pid,
            message: msg,
        };
        axios
            .post(`/api/admin/messenger-reply`, send, { headers })
            .then((res) => {
                document.getElementById("message_typing").value = "";
                getMessages(localStorage.getItem("t_id"));
                setTimeout(() => {
                    scroller();
                }, 200);
            });
    };

    const callApi = () => {
        const interval = setInterval(() => {
            //getMessages(localStorage.getItem('number'));
        }, 2000);
        return () => clearInterval(interval);
    };

    const scroller = () => {
        var objDiv = document?.getElementById("ko");
        if (objDiv) {
            objDiv.scrollTop = objDiv.scrollHeight;
        }
    };

    const search = (s) => {
        let users = document.querySelectorAll(".uname");

        users.forEach((u, i) => {
            if (u.innerText.toLowerCase().includes(s.toLowerCase())) {
                u.style.display = "unset";
            } else {
                u.style.display = "none";
            }
        });
    };

    useEffect(() => {
        getData();
        callApi();
        localStorage.removeItem("participant_id");
    }, []);

    return (
        <div
            id="container"
            style={{
                height: "90vh",
                overflow: "hidden",
            }}
        >
            <Sidebar />
            <div id="content">
                <div className="view-applicant">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title mb-0">{t("admin.global.messengerChat")}</h1>
                        </div>
                        {/* Toggle Button for Mobile
                        <div className="d-flex align-items-center shadow rounded">
                            <div className=" my-2 mb-0 px-2 d-block d-sm-none">
                                {tabs.map((tab) => (
                                    <button
                                        key={tab}
                                        className={`btn m-1 ${activeTab === tab ? 'navyblue' : 'btn-outline-secondary'}`}
                                        onClick={() => setActiveTab(tab)}
                                    >
                                        {tab}
                                    </button>
                                ))}
                            </div>
                        </div> */}
                        <div className="col-sm-6 text-right page-title mb-0 pt-2">
                            <input
                                type="text"
                                name="smsg"
                                className="form-control"
                                onChange={(e) => search(e.target.value)}
                                placeholder="search name"
                                style={{ float: "right", width: "55%" }}
                            />
                        </div>
                    </div>

                    <div className="card">
                        <div className="card-body " style={{maxHeight: "82vh"}}>
                            <div className="container-fluid">
                                {/* Toggle Button for Mobile */}
                                <div className="d-flex align-items-center shadow rounded justify-content-between justify-content-sm-start">
                                    <h4 className="header-title mx-3">{t("admin.global.replies")}</h4>
                                    {/* Tab Buttons */}
                                    {/* <div className=" my-3 px-2 d-none d-sm-block">
                                        {tabs.map((tab) => (
                                            <button
                                                key={tab}
                                                className={`btn mx-1 ${activeTab === tab ? 'navyblue' : 'btn-outline-secondary'}`}
                                                onClick={() => setActiveTab(tab)}
                                            >
                                                {tab}
                                            </button>
                                        ))}
                                    </div> */}
                                    <div className="d-flex justify-content-between align-items-center">
                                        <div className="d-block d-sm-none text-end mt-2">
                                            <button
                                                className="btn btn-info d-flex align-items-center gap-2"
                                                onClick={() => setShowSide(!showSide)}
                                            >
                                                {showSide ? (
                                                    <>
                                                        <FaTimes className="font-20" />
                                                    </>
                                                ) : (
                                                    <>
                                                        <FaUsers className="font-20" />
                                                    </>
                                                )}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div className="row">
                                    {/* Chat Section */}
                                    <div className="col-sm-9">
                                        <hr className="m-0" />
                                        <div className="chat-conversation">
                                            <div style={{ height: "65vh", overflowY: "auto" }}>
                                                <ul className="conversation-list" style={{ fontFamily: "sans-serif" }}>
                                                    {messages?.map((m, i) => (
                                                        <li className={m.from.id !== pageId ? "clearfix" : "clearfix odd"} key={i}>
                                                            <div className="chat-avatar">
                                                                <img src="/images/chat.png" alt="chatIcon" />
                                                            </div>
                                                            <div className="conversation-text">
                                                                <div className="ctext-wrap card">
                                                                    <p>{m.message}</p>
                                                                    <small>{new Date(m.created_time).toLocaleString("en-GB")}</small>
                                                                </div>
                                                            </div>
                                                        </li>
                                                    ))}
                                                </ul>
                                            </div>

                                            {/* Input */}
                                            <div className="row m-3">
                                                <div className="input-group">
                                                    <input type="hidden" name="support_id" value="1" />
                                                    <input
                                                        type="text"
                                                        name="message"
                                                        id="message_typing"
                                                        onKeyDown={(e) => e.key === "Enter" && sendMessage()}
                                                        className="form-control"
                                                        placeholder="Type..."
                                                    />
                                                    <div className="input-group-prepend">
                                                        <button
                                                            type="button"
                                                            id="submitMessage"
                                                            onClick={sendMessage}
                                                            className="btn chat-send btn-block"
                                                            style={{ background: "#00a4f3", color: "white" }}
                                                        >
                                                            <i className="fas fa-paper-plane"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Side List (Users) */}
                                    <div
                                        className={`card col-sm-3 card-body sidemsgList ${showSide ? "d-block" : "d-none"} d-sm-block pt-0`}
                                        style={{
                                            backgroundColor: "rgb(241 241 241 / 62%)",
                                            borderRadius: "3%",
                                            overflowY: "auto",
                                            height: "73vh",
                                            maxHeight: "70vh",
                                        }}
                                    >
                                        {data?.map((d, i) => (
                                            <div
                                                className="mb-3 card p-3 mt-3 uname"
                                                style={{ color: "black", cursor: "pointer" }}
                                                onClick={() => {
                                                    getMessages(d.id);
                                                    setSelectNumber(d.id);
                                                    localStorage.setItem("t_id", d.id);
                                                    localStorage.setItem("participant_id", d.participants.data[0].id);
                                                    setTimeout(() => {
                                                        scroller();
                                                    }, 200);
                                                    setShowSide(false); // Hide on mobile after selecting
                                                }}
                                                key={i}
                                            >
                                                <div className="d-flex align-items-center">
                                                    <div className="user-icon2">
                                                        <FaRegCircleUser className="font-24" style={{ color: "#2F4054" }} />
                                                    </div>
                                                    <div className="ml-2">
                                                        <h5 className="mt-0 mb-2">{d.participants.data[0].name}</h5>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
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
