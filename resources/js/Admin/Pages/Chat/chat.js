import axios from "axios";
import EmojiPicker from 'emoji-picker-react';
import React, { useEffect, useRef, useState } from "react";
import { useAlert } from "react-alert";
import { Button, Modal } from 'react-bootstrap';
import { useTranslation } from "react-i18next";
import { FaRegCircleUser } from "react-icons/fa6";
import { Link } from "react-router-dom";
import { SelectPicker } from "rsuite";
import MiniLoader from "../../../Components/common/MiniLoader";
import useWindowWidth from "../../../Hooks/useWindowWidth";
import Sidebar from "../../Layouts/Sidebar";
import './ChatFooter.css'; // Import the CSS

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
    const [replyMessage, setReplyMessage] = useState(null);
    const chatEndRef = useRef(null); // Reference to scroll to the end of the chat
    const [showChatContainer, setShowChatContainer] = useState(false);
    const [message, setMessage] = useState('');
    const [replyId, setReplyId] = useState(null)
    const [emoji, setEmoji] = useState(false)
    const [waMedia, setWaMedia] = useState(false)
    const [selectedFile, setSelectedFile] = useState({})
    const [allLeads, setAllLeads] = useState([]);
    const [leadId, setLeadId] = useState(null)
    const [number, setNumber] = useState(null)
    const [newChat, setNewChat] = useState(false)
    const [loading, setLoading] = useState(false)
    const [lead, setLead] = useState(false)
    const [client, setClient] = useState(false)
    const [media, setMedia] = useState('')
    const [image, setImage] = useState('')
    const [webhookResponses, setWebhookResponses] = useState([]);

    // const handleInputChange = (e) => {
    //     setMessage(e.target.value);
    // };

    const windowWidth = useWindowWidth();

    useEffect(() => {
        if (windowWidth < 1199) {
            setShowChatList(true)
        } else {
            setShowChatList(false)
        }
    }, [windowWidth])

    const getWebhook = () => {
        axios.get('/api/admin/webhook-responses', { headers })
            .then((response) => {
                setWebhookResponses(response?.data);
            })
            .catch((error) => {
                console.error("Error fetching webhook responses:", error);
            });
    }
    useEffect(() => {
        getWebhook()
    }, []);

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

    const getLeads = async () => {
        try {
            const res = await axios.get(`/api/admin/leads`, { headers })

            setAllLeads(res?.data?.data);

        } catch (error) {
            console.log(error)
        }
    }

    const getLead = () => {
        axios
            .get(`/api/admin/leads/${leadId}/edit`, { headers })
            .then((res) => {
                setNumber(res?.data?.lead?.phone);
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
            // setExpired(res.data.expired);
            setMessages(c);
        });
    };

    const addInChat = async () => {
        try {
            const res = await axios.post(`/api/admin/chat-message`, { number }, { headers });
            const r = res.data.data;
            setSelectNumber(r.number);
            setSelectedChat(r.number);
            getMessages(r.number);
            setShowChatList(false);
            handleClose();
            localStorage.setItem("number", r.number);
            // Safely handle the removal of the element with escaped class name
            const unreadElement = document.querySelector(escapedClassName);
            if (unreadElement) {
                unreadElement.remove();
            }
        } catch (error) {
            // alert.info("you are already in a chat")
        }
    };



    const sendMessage = () => {
        let msg = document.getElementById("message_typing").value;

        const messageToSend = replyId ? `Replying to: ${replyMessage == null ? '' : replyMessage}\n${msg}` : msg;

        const send = new FormData(); // Use FormData to handle file uploads
        send.append("number", selectNumber);
        send.append("message", messageToSend);
        if (replyId) {
            send.append("replyId", replyId);
        }
        if (selectedFile) {
            send.append("media", selectedFile); // Append the media file if it exists
        }
        setLoading(true);

        axios.post(`/api/admin/chat-reply`, send, {
            headers: {
                'Content-Type': 'multipart/form-data',
                ...headers
            }
        })
            .then((res) => {
                document.getElementById("message_typing").value = "";
                setSelectedFile({}) // Reset the file input
                setReplyMessage(null); // Reset reply after sending
                setMessage('');
                getData();
                setMedia('');
                setImage('');
                setReplyId(null);
                setLoading(false)
                getWebhook()
                setTimeout(() => {
                    scroller();
                }, 200);
            })
            .catch((error) => {
                setLoading(false)
            });
    };


    const handleInputChange = (e) => {
        setMessage(e.target.value);
    };

    const onEmojiClick = (event, emojiObject) => {
        if (event && event.emoji) {
            setMessage((prev) => prev + event.emoji); // Append the emoji to the existing message
        } else {
            console.error('Emoji object is invalid or does not contain an emoji:', emojiObject);
        }
    };

    const handleFileUpload = (type) => {
        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = type === 'Image' ? 'image/*' :
            type === 'Music' ? 'audio/*' :
                type === 'Contact' ? '.vcf' : // for vCard files
                    'video/*';

        fileInput.onchange = (event) => {
            const file = event.target.files[0];
            if (file) {
                setSelectedFile(file);
                setWaMedia(false);
                scrollToBottom()
                // Preview the file
                const reader = new FileReader();
                reader.onload = (e) => {
                    if (type === 'Image') {
                        setImage(e.target.result); // Set image preview
                    } else if (type === 'Video') {
                        setMedia(e.target.result); // Set video preview
                    }
                };
                reader.readAsDataURL(file); // Read the file as a data URL
            }
        };

        fileInput.click(); // Simulate a click to open the file selector
    };



    // const handleReply = (message) => {
    //     setReplyMessage(message); // Set the message to be replied to
    //     messageInputRef.current.value = `Replying to: ${message}`; // Set input value
    //     scrollToBottom(); // Scroll to bottom when replying
    // };

    const scrollToBottom = () => {
        chatEndRef.current?.scrollIntoView({ behavior: "smooth" });
    };

    useEffect(() => {
        scrollToBottom(); // Scroll to bottom when messages change
    }, [replyId, replyMessage]);


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
        axios.get(`/api/admin/chat-search?s=${s}&type=${lead ? 'lead' : 'client'}`, { headers }).then((res) => {
            const r = res.data.data;
    
            if (lead) {
                setAllLeads(res.data);
            } else {
                setClients(res.data.clients);
            }
            setData(r);
        });
    };
    

    useEffect(() => {
        getLead();
        addInChat()
    }, [number, leadId, selectNumber])

    useEffect(() => {
        getLeads()
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
                                                <div className=" mb-3 d-lg-block position-relative">
                                                    <input
                                                        type="text"
                                                        name="smsg"
                                                        className="form-control search-input"
                                                        onChange={(e) => search(e.target.value)}
                                                        placeholder="Search name or number"
                                                    />
                                                    <i className="fas fa-search search-icon"></i>
                                                </div>
                                                <div className="ClientHistory mb-1">
                                                    <ul className="nav nav-tabs" role="tablist">
                                                        <li className="nav-item" role="presentation">
                                                            <a
                                                                id="chat-details"
                                                                className="nav-link active navyblueColor"
                                                                data-toggle="tab"
                                                                href="#tab-chat-details"
                                                                aria-selected="true"
                                                                role="tab"
                                                                onClick={() => {
                                                                    setClient(true);
                                                                    setLead(false);
                                                                }}
                                                            >
                                                                Chats
                                                            </a>
                                                        </li>
                                                        <li className="nav-item" role="presentation">
                                                            <a
                                                                id="client-details"
                                                                className="nav-link navyblueColor"
                                                                data-toggle="tab"
                                                                href="#tab-client-details"
                                                                aria-selected="false"
                                                                role="tab"
                                                                onClick={() => {
                                                                    setClient(false);
                                                                    setLead(true);
                                                                }}
                                                            >
                                                                Clients
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>

                                                <div className="tab-content">
                                                    <div
                                                        id="tab-chat-details"
                                                        className="tab-pane fade active show"  // Corrected class for active tab
                                                        role="tabpanel"
                                                        aria-labelledby="chat-details"
                                                    >
                                                        {clientsCard}
                                                    </div>
                                                    <div
                                                        id="tab-client-details"
                                                        className="tab-pane fade"  // No 'active show' initially, only for inactive tab
                                                        role="tabpanel"
                                                        aria-labelledby="client-details"
                                                    >
                                                        {allLeads?.map((d, i) => (
                                                            <div
                                                                className={"card p-3 cardList"}
                                                                onClick={(e) => {
                                                                    getMessages(d.phone);
                                                                    setSelectNumber(d.phone);
                                                                    setSelectedChat(d.phone);  // Set the selected chat
                                                                    setShowChatList(false);
                                                                    handleClose();
                                                                    localStorage.setItem("number", d.phone);
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
                                                                        <h5
                                                                            className="mt-0 mb-2"
                                                                            style={{
                                                                                cursor: "pointer",
                                                                            }}
                                                                        >
                                                                            <Link
                                                                                to={`/admin/leads/view/${d.id}`}
                                                                            >
                                                                                {d.firstname + d.lastname}
                                                                            </Link>
                                                                        </h5>
                                                                        <h6
                                                                            className="mt-0 mb-1"
                                                                            style={{
                                                                                cursor: "pointer",
                                                                                display: "flex",
                                                                                alignItems: "center",
                                                                            }}
                                                                        >
                                                                            <i className="fas fa-phone mr-2"></i>
                                                                            {d.phone}
                                                                        </h6>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                                {
                                                    newChat && (
                                                        <div className="mb-3 d-lg-block position-relative">

                                                            <SelectPicker
                                                                data={allLeads}
                                                                value={leadId}
                                                                onChange={(value, event) => {
                                                                    setLeadId(value);
                                                                    search(event.target.value);
                                                                    setSelectNumber(value);
                                                                }}
                                                                size="lg"
                                                                required
                                                            />
                                                        </div>
                                                    )
                                                }
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
                                <div className="card-body p-0">

                                    <div className="row">
                                        <div
                                            className="card col-sm-4 card-body sidemsg mb-0 pt-0 d-none d-xl-flex"
                                            style={{
                                                borderRadius: "0",
                                                boxShadow: "none",
                                                borderRight: "1px solid #E5EBF1"
                                            }}
                                        >

                                            <div className="d-none mb-3 mt-3  d-lg-block position-relative">
                                                <input
                                                    type="text"
                                                    name="smsg"
                                                    className="form-control search-input"
                                                    onChange={(e) => search(e.target.value)}
                                                    placeholder="Search name or number"
                                                />
                                                <i className="fas fa-search search-icon"></i>
                                            </div>
                                            {/* <div className="d-flex justify-content-between align-items-center my-2 pl-3 pr-3">
                                                <span data-icon="chat" onClick={() => setNewChat(prev => !prev)} className=""
                                                ></span>
                                            </div> */}
                                            <div className="ClientHistory mb-1">
                                                <ul className="nav nav-tabs" role="tablist">
                                                    <li className="nav-item" role="presentation">
                                                        <a
                                                            id="chat-details"
                                                            className="nav-link active navyblueColor"
                                                            data-toggle="tab"
                                                            href="#tab-chat-details"
                                                            aria-selected="true"
                                                            role="tab"
                                                            onClick={() => {
                                                                setClient(true);
                                                                setLead(false);
                                                            }}
                                                        >
                                                            Chats
                                                        </a>
                                                    </li>
                                                    <li className="nav-item" role="presentation">
                                                        <a
                                                            id="client-details"
                                                            className="nav-link navyblueColor"
                                                            data-toggle="tab"
                                                            href="#tab-client-details"
                                                            aria-selected="false"
                                                            role="tab"
                                                            onClick={() => {
                                                                setClient(false);
                                                                setLead(true);
                                                            }}
                                                        >
                                                            Clients
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>

                                            <div className="tab-content">
                                                <div
                                                    id="tab-chat-details"
                                                    className="tab-pane fade active show"  // Corrected class for active tab
                                                    role="tabpanel"
                                                    aria-labelledby="chat-details"
                                                >
                                                    {clientsCard}
                                                </div>
                                                <div
                                                    id="tab-client-details"
                                                    className="tab-pane fade"  // No 'active show' initially, only for inactive tab
                                                    role="tabpanel"
                                                    aria-labelledby="client-details"
                                                >
                                                    {allLeads?.map((d, i) => (
                                                        <div
                                                            className={"card p-3 cardList"}
                                                            onClick={(e) => {
                                                                getMessages(d.phone);
                                                                setSelectNumber(d.phone);
                                                                setSelectedChat(d.phone);  // Set the selected chat
                                                                setShowChatList(false);
                                                                handleClose();
                                                                localStorage.setItem("number", d.phone);
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
                                                                    <h5
                                                                        className="mt-0 mb-2"
                                                                        style={{
                                                                            cursor: "pointer",
                                                                        }}
                                                                    >
                                                                        <Link
                                                                            to={`/admin/leads/view/${d.id}`}
                                                                        >
                                                                            {d.firstname + d.lastname}
                                                                        </Link>
                                                                    </h5>
                                                                    <h6
                                                                        className="mt-0 mb-1"
                                                                        style={{
                                                                            cursor: "pointer",
                                                                            display: "flex",
                                                                            alignItems: "center",
                                                                        }}
                                                                    >
                                                                        <i className="fas fa-phone mr-2"></i>
                                                                        {d.phone}
                                                                    </h6>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>

                                        </div>
                                        <div className="col-xl-8 col-12 p-0" style={{ backgroundColor: "white" }}>
                                            <div className="d-flex justify-content-between align-items-center my-2 pl-3 pr-3">
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
                                                <div data-simplebar="init" style={{ minHeight: "74vh" }}>
                                                    <div className="simplebar-wrapper" style={{ margin: "0px" }}>
                                                        <div
                                                            chat-content=""
                                                            id="ko"
                                                            style={{
                                                                overflowY: "scroll",
                                                                width: "auto",
                                                                height: "74vh",
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
                                                                                    <div className="conversation-text"
                                                                                        style={{
                                                                                            display: 'flex',
                                                                                            alignItems: "center",
                                                                                            justifyContent: m.flex != "C" && "end"
                                                                                        }}
                                                                                    >
                                                                                        <div className="ctext-wrap card" style={{
                                                                                            boxShadow: "none",
                                                                                            border: "1px solid #DDE2E8",
                                                                                            backgroundColor: m.flex === "C" ? "#E5EBF1" : "rgb(247 247 247 / 39%)" // Conditional background color
                                                                                        }}>
                                                                                            <div className="d-flex justify-content-between align-items-center">
                                                                                                <span className="pr-2">{m.flex == "C" ? chatName : "You"}</span>
                                                                                                <small>
                                                                                                    {new Date(m.created_at).toLocaleString("en-GB")}
                                                                                                </small>
                                                                                            </div>
                                                                                            <p>
                                                                                                {/* Check if the message starts with "Replying to:" */}
                                                                                                {m?.message != null && m?.message?.startsWith("Replying to:") && (
                                                                                                    <span className="replying-text">{m.message}</span>
                                                                                                )}

                                                                                                {/* Check for media based on wa_id */}
                                                                                                {m?.message != null && m?.message?.startsWith("Replying to:") && (
                                                                                                    <>
                                                                                                        {webhookResponses?.filter(response => response.id == m.wa_id) // Check for matching wa_id
                                                                                                            .map((response) => (
                                                                                                                <React.Fragment key={response.id}>
                                                                                                                    {response.video && (
                                                                                                                        <video width="300" height="220" controls>
                                                                                                                            <source src={`/storage/uploads/media/${response.video}`} type="video/mp4" />
                                                                                                                        </video>
                                                                                                                    )}
                                                                                                                    {response.image && (
                                                                                                                        <img src={`/storage/uploads/media/${response.image}`} alt="image" width="300" />
                                                                                                                    )}
                                                                                                                </React.Fragment>
                                                                                                            ))}
                                                                                                    </>
                                                                                                )}

                                                                                                {/* Display regular message if no media */}
                                                                                                {!m?.message?.startsWith("Replying to:") && (
                                                                                                    <>
                                                                                                        {m?.video && (
                                                                                                            <video width="300" height="220" controls>
                                                                                                                <source src={`/storage/uploads/media/${m.video}`} type="video/mp4" />
                                                                                                            </video>
                                                                                                        )}
                                                                                                        {m?.image && (
                                                                                                            <img src={`/storage/uploads/media/${m.image}`} alt="image" width="300" />
                                                                                                        )}
                                                                                                        <br />
                                                                                                        {m.message}
                                                                                                    </>
                                                                                                )}
                                                                                            </p>
                                                                                        </div>
                                                                                        <i
                                                                                            className="fa-solid fa-reply"
                                                                                            style={{ marginBottom: "50px", marginLeft: "5px", cursor: "pointer" }}
                                                                                            onClick={() => {
                                                                                                if (m?.video != null) {
                                                                                                    setMedia(m.video);
                                                                                                } else if (m?.image != null) {
                                                                                                    setImage(m?.image)
                                                                                                }
                                                                                                setReplyMessage(m.message);
                                                                                                setReplyId(m?.id)

                                                                                            }} // Set the message to reply to
                                                                                        ></i>
                                                                                    </div>
                                                                                </li>
                                                                            );
                                                                        }
                                                                    })}
                                                                    <div ref={chatEndRef} /> {/* Reference for scrolling */}
                                                                </ul>
                                                                {/* Reply Message Display */}
                                                                {/* Reply Message Display */}
                                                                <div className="reply-message">
                                                                    {(replyId || (selectedFile && selectedFile.name)) && (
                                                                        <div className="reply-container">
                                                                            <div className="reply-content pr-3">
                                                                                <span className="reply-label">
                                                                                    {selectedFile && selectedFile.name ? 'Selected file' : "Replying to:"}
                                                                                </span>
                                                                                <span className="reply-text">{replyMessage ?? ''}</span>

                                                                                <br />

                                                                                {/* Display selected media (if no file selected, show previously loaded media) */}
                                                                                {!selectedFile?.name && media && (
                                                                                    <div className="media-container">
                                                                                        <video className="reply-video" width="280" height="180" controls>
                                                                                            <source src={`/storage/uploads/media/${media}`} type="video/mp4" />
                                                                                        </video>
                                                                                    </div>
                                                                                )}

                                                                                {!selectedFile?.name && image && (
                                                                                    <div className="media-container">
                                                                                        <img
                                                                                            src={`/storage/uploads/media/${image}`}
                                                                                            alt="image"
                                                                                            className="reply-image"
                                                                                            width="280"
                                                                                        />
                                                                                    </div>
                                                                                )}

                                                                                {/* Media display: Video */}
                                                                                {selectedFile && selectedFile?.type?.startsWith("video") && (
                                                                                    <div className="media-container">
                                                                                        <video className="reply-video" width="280" height="180" controls>
                                                                                            <source src={URL.createObjectURL(selectedFile)} type={selectedFile.type} />
                                                                                        </video>
                                                                                    </div>
                                                                                )}

                                                                                {/* Media display: Image */}
                                                                                {selectedFile && selectedFile?.type?.startsWith("image") && (
                                                                                    <div className="media-container">
                                                                                        <img
                                                                                            src={URL.createObjectURL(selectedFile)}
                                                                                            alt="image"
                                                                                            className="reply-image"
                                                                                            width="280"
                                                                                        />
                                                                                    </div>
                                                                                )}
                                                                            </div>

                                                                            {/* Close button to dismiss the reply */}
                                                                            <button
                                                                                onClick={() => {
                                                                                    setReplyMessage(null);
                                                                                    setMedia('');
                                                                                    setImage('');
                                                                                    setReplyId(null);
                                                                                    setSelectedFile({}); // Clear selected file
                                                                                }}
                                                                                className="reply-close-btn ml-2"
                                                                            >
                                                                                <i className="fa-solid fa-xmark reply-close-icon"></i>
                                                                            </button>
                                                                        </div>
                                                                    )}
                                                                </div>



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
                                                    <>
                                                        {/* {
                                                            emoji && (
                                                                <EmojiPicker />
                                                            )
                                                        } */}
                                                        <div className="wa-input-bar">
                                                            {/* Attachments Button */}
                                                            <button className="wa-input-icon"
                                                                onClick={() => setWaMedia(true)}
                                                            >
                                                                <i className="fa fa-paperclip" aria-hidden="true"></i>
                                                            </button>



                                                            {/* Emoji Button */}
                                                            <button className="wa-input-icon"
                                                                onClick={() => setEmoji(prev => !prev)}
                                                            >
                                                                <i className="fa-regular fa-face-smile" aria-hidden="true"></i>
                                                            </button>

                                                            {/* Text Input */}
                                                            <input
                                                                type="text"
                                                                name="message"
                                                                id="message_typing"
                                                                className="wa-input-text"
                                                                chat-box=""
                                                                disabled={loading}
                                                                onKeyDown={(e) => e.key === "Enter" ? sendMessage() : ""}
                                                                placeholder="Type a message"
                                                                value={message}
                                                                onChange={(e) => handleInputChange(e)}
                                                            />

                                                            {/* Send Button */}
                                                            <button className="wa-input-icon wa-send-button"
                                                                onClick={(e) => sendMessage()}
                                                                disabled={selectedFile && selectedFile?.name ? false : message == ''}
                                                            >
                                                                {
                                                                    loading ? (
                                                                        <MiniLoader />
                                                                    ) : (
                                                                        message.trim() ? (
                                                                            <i className="fa fa-paper-plane" aria-hidden="true"></i>
                                                                        ) : (
                                                                            // <i className="fa fa-microphone" aria-hidden="true"></i>
                                                                            <i className="fa fa-paper-plane" aria-hidden="true"></i>
                                                                        )
                                                                    )
                                                                }
                                                            </button>
                                                        </div>


                                                        {/* Emoji Modal */}
                                                        <Modal
                                                            show={emoji} onHide={() => setEmoji(false)} centered>
                                                            <Modal.Header closeButton>
                                                                <Modal.Title>Select an Emoji</Modal.Title>
                                                            </Modal.Header>
                                                            <Modal.Body>
                                                                <div className="d-flex justify-content-center align-items-center">
                                                                    <EmojiPicker onEmojiClick={onEmojiClick} />
                                                                </div>
                                                            </Modal.Body>
                                                            <Modal.Footer>
                                                                <Button variant="secondary" onClick={() => setEmoji(false)}>
                                                                    Close
                                                                </Button>
                                                            </Modal.Footer>
                                                        </Modal>


                                                        <Modal show={waMedia} onHide={() => setWaMedia(false)} centered>
                                                            <Modal.Header closeButton>
                                                                <Modal.Title>Upload Options</Modal.Title>
                                                            </Modal.Header>
                                                            <Modal.Body>
                                                                <div className="d-flex flex-column">
                                                                    <Button variant="outline-primary" onClick={() => handleFileUpload('Image')}>
                                                                        📷 Upload Image
                                                                    </Button>
                                                                    <Button variant="outline-primary" onClick={() => handleFileUpload('Video')}>
                                                                        🎥 Upload Video
                                                                    </Button>
                                                                </div>
                                                            </Modal.Body>
                                                            <Modal.Footer>
                                                                <Button variant="secondary" onClick={() => setWaMedia(false)}>
                                                                    Close
                                                                </Button>
                                                            </Modal.Footer>
                                                        </Modal>
                                                    </>
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
