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
import { RiAccountCircleFill } from "react-icons/ri";

import wa from '../../../../../public/images/wa.jpg'

export default function chat() {

    const { t } = useTranslation();
    const [data, setData] = useState(null);
    const [messages, setMessages] = useState(null);
    const [groupedMessages, setGroupedMessages] = useState({});
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

            const grouped = groupMessagesByDate(c);
            setGroupedMessages(grouped); 
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

    const groupMessagesByDate = (messages) => {
        if (!messages) return {};
    
        return messages.reduce((grouped, message) => {
            const date = new Date(message.created_at).toLocaleDateString(); // Adjust date format as needed
            if (!grouped[date]) {
                grouped[date] = [];
            }
            grouped[date].push(message);
            return grouped;
        }, {});
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
                        <div
                            //  className="user-icon2"
                            className=""
                        >
                            {/* <FaRegCircleUser className="font-24" style={{ color: "#2F4054" }} /> */}
                            <svg viewBox="0 0 212 212" height="45" width="45" preserveAspectRatio="xMidYMid meet" class="xh8yej3 x5yr21d" version="1.1" x="0px" y="0px" enable-background="new 0 0 212 212"><title>default-user</title><path fill="#DFE5E7" class="background" d="M106.251,0.5C164.653,0.5,212,47.846,212,106.25S164.653,212,106.25,212C47.846,212,0.5,164.654,0.5,106.25 S47.846,0.5,106.251,0.5z"></path><g><path fill="#FFFFFF" class="primary" d="M173.561,171.615c-0.601-0.915-1.287-1.907-2.065-2.955c-0.777-1.049-1.645-2.155-2.608-3.299 c-0.964-1.144-2.024-2.326-3.184-3.527c-1.741-1.802-3.71-3.646-5.924-5.47c-2.952-2.431-6.339-4.824-10.204-7.026 c-1.877-1.07-3.873-2.092-5.98-3.055c-0.062-0.028-0.118-0.059-0.18-0.087c-9.792-4.44-22.106-7.529-37.416-7.529 s-27.624,3.089-37.416,7.529c-0.338,0.153-0.653,0.318-0.985,0.474c-1.431,0.674-2.806,1.376-4.128,2.101 c-0.716,0.393-1.417,0.792-2.101,1.197c-3.421,2.027-6.475,4.191-9.15,6.395c-2.213,1.823-4.182,3.668-5.924,5.47 c-1.161,1.201-2.22,2.384-3.184,3.527c-0.964,1.144-1.832,2.25-2.609,3.299c-0.778,1.049-1.464,2.04-2.065,2.955 c-0.557,0.848-1.033,1.622-1.447,2.324c-0.033,0.056-0.073,0.119-0.104,0.174c-0.435,0.744-0.79,1.392-1.07,1.926 c-0.559,1.068-0.818,1.678-0.818,1.678v0.398c18.285,17.927,43.322,28.985,70.945,28.985c27.678,0,52.761-11.103,71.055-29.095 v-0.289c0,0-0.619-1.45-1.992-3.778C174.594,173.238,174.117,172.463,173.561,171.615z"></path><path fill="#FFFFFF" class="primary" d="M106.002,125.5c2.645,0,5.212-0.253,7.68-0.737c1.234-0.242,2.443-0.542,3.624-0.896 c1.772-0.532,3.482-1.188,5.12-1.958c2.184-1.027,4.242-2.258,6.15-3.67c2.863-2.119,5.39-4.646,7.509-7.509 c0.706-0.954,1.367-1.945,1.98-2.971c0.919-1.539,1.729-3.155,2.422-4.84c0.462-1.123,0.872-2.277,1.226-3.458 c0.177-0.591,0.341-1.188,0.49-1.792c0.299-1.208,0.542-2.443,0.725-3.701c0.275-1.887,0.417-3.827,0.417-5.811 c0-1.984-0.142-3.925-0.417-5.811c-0.184-1.258-0.426-2.493-0.725-3.701c-0.15-0.604-0.313-1.202-0.49-1.793 c-0.354-1.181-0.764-2.335-1.226-3.458c-0.693-1.685-1.504-3.301-2.422-4.84c-0.613-1.026-1.274-2.017-1.98-2.971 c-2.119-2.863-4.646-5.39-7.509-7.509c-1.909-1.412-3.966-2.643-6.15-3.67c-1.638-0.77-3.348-1.426-5.12-1.958 c-1.181-0.355-2.39-0.655-3.624-0.896c-2.468-0.484-5.035-0.737-7.68-0.737c-21.162,0-37.345,16.183-37.345,37.345 C68.657,109.317,84.84,125.5,106.002,125.5z"></path></g></svg>

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

                                    <div className="row"
                                        style={{
                                            marginRight: "-29px",
                                            marginLeft: "-26px",
                                            borderTop: "1px solid #e0e0e0"
                                        }}
                                    >
                                        <div
                                            className="card col-sm-4 card-body sidemsg mb-0 p-0 d-none d-xl-flex"
                                            style={{
                                                borderRadius: "0",
                                                boxShadow: "none",
                                                borderRight: "1px solid #E5EBF1",
                                                // paddingRight: "0",
                                                // paddingBottom: "0"
                                            }}
                                        >

                                            <header className="header-container">
                                                <div className="profile-picture-container" >
                                                    <button
                                                        className="btn navyblue mr-2 d-lg-none"
                                                        onClick={handleBack}
                                                        style={{ borderRadius: "50%" }}
                                                    >
                                                        <i className="fa-solid fa-arrow-left"></i>
                                                    </button>
                                                    <div className="user-icon2"
                                                        style={{
                                                            background: "#dfe5e7"
                                                        }}
                                                    >
                                                        <RiAccountCircleFill className="font-24" style={{ color: "#2F4054" }} />
                                                    </div>
                                                </div>
                                                {/* <div className="contact-info-container" role="button">
                                                    <div className="contact-info">
                                                        <div className="contact-name"><span className="phone-number">{chatName}</span></div>
                                                    </div>
                                                    <div className="last-seen-container"><span title="last seen today at 6:15 PM" className="last-seen">+{selectNumber}</span></div>
                                                </div> */}
                                                <div className="header-buttons-container">
                                                    <div className="button-icons">
                                                        {/* <div className="icon-container">
                                                        <div title="Search">
                                                            <span data-icon="search-alt" className="search-icon"></span>
                                                        </div>
                                                        <span></span>
                                                    </div>
                                                    <div className="icon-container">
                                                        <div role="button" title="Attach">
                                                            <span data-icon="clip" className="attach-icon"></span>
                                                        </div>
                                                        <span></span>
                                                    </div>
                                                    <div className="icon-container">
                                                        <div title="Menu">
                                                            <span data-icon="menu" className="menu-icon"></span>
                                                        </div>
                                                        <span></span>
                                                    </div> */}
                                                    </div>
                                                </div>
                                            </header>

                                            <div className="d-none mb-3 mt-3 mx-3 d-lg-block position-relative">
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

                                            <div className="tab-content"
                                                style={{
                                                    height: "77.6vh",
                                                    overflowY: "auto",
                                                }}
                                            >
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
                                            <header className="header-container">
                                                <div className="profile-picture-container" >
                                                    <button
                                                        className="btn navyblue mr-2 d-lg-none"
                                                        onClick={handleBack}
                                                        style={{ borderRadius: "50%" }}
                                                    >
                                                        <i className="fa-solid fa-arrow-left"></i>
                                                    </button>
                                                    <div
                                                        // className="user-icon2"
                                                        className=""
                                                        // style={{
                                                        //     background: "#dfe5e7"
                                                        // }}
                                                    >
                                                        {/* <FaRegCircleUser className="font-24" style={{ color: "#2F4054" }} /> */}
                                                        <svg viewBox="0 0 212 212" height="45" width="45" preserveAspectRatio="xMidYMid meet" class="xh8yej3 x5yr21d" version="1.1" x="0px" y="0px" enable-background="new 0 0 212 212"><title>default-user</title><path fill="#DFE5E7" class="background" d="M106.251,0.5C164.653,0.5,212,47.846,212,106.25S164.653,212,106.25,212C47.846,212,0.5,164.654,0.5,106.25 S47.846,0.5,106.251,0.5z"></path><g><path fill="#FFFFFF" class="primary" d="M173.561,171.615c-0.601-0.915-1.287-1.907-2.065-2.955c-0.777-1.049-1.645-2.155-2.608-3.299 c-0.964-1.144-2.024-2.326-3.184-3.527c-1.741-1.802-3.71-3.646-5.924-5.47c-2.952-2.431-6.339-4.824-10.204-7.026 c-1.877-1.07-3.873-2.092-5.98-3.055c-0.062-0.028-0.118-0.059-0.18-0.087c-9.792-4.44-22.106-7.529-37.416-7.529 s-27.624,3.089-37.416,7.529c-0.338,0.153-0.653,0.318-0.985,0.474c-1.431,0.674-2.806,1.376-4.128,2.101 c-0.716,0.393-1.417,0.792-2.101,1.197c-3.421,2.027-6.475,4.191-9.15,6.395c-2.213,1.823-4.182,3.668-5.924,5.47 c-1.161,1.201-2.22,2.384-3.184,3.527c-0.964,1.144-1.832,2.25-2.609,3.299c-0.778,1.049-1.464,2.04-2.065,2.955 c-0.557,0.848-1.033,1.622-1.447,2.324c-0.033,0.056-0.073,0.119-0.104,0.174c-0.435,0.744-0.79,1.392-1.07,1.926 c-0.559,1.068-0.818,1.678-0.818,1.678v0.398c18.285,17.927,43.322,28.985,70.945,28.985c27.678,0,52.761-11.103,71.055-29.095 v-0.289c0,0-0.619-1.45-1.992-3.778C174.594,173.238,174.117,172.463,173.561,171.615z"></path><path fill="#FFFFFF" class="primary" d="M106.002,125.5c2.645,0,5.212-0.253,7.68-0.737c1.234-0.242,2.443-0.542,3.624-0.896 c1.772-0.532,3.482-1.188,5.12-1.958c2.184-1.027,4.242-2.258,6.15-3.67c2.863-2.119,5.39-4.646,7.509-7.509 c0.706-0.954,1.367-1.945,1.98-2.971c0.919-1.539,1.729-3.155,2.422-4.84c0.462-1.123,0.872-2.277,1.226-3.458 c0.177-0.591,0.341-1.188,0.49-1.792c0.299-1.208,0.542-2.443,0.725-3.701c0.275-1.887,0.417-3.827,0.417-5.811 c0-1.984-0.142-3.925-0.417-5.811c-0.184-1.258-0.426-2.493-0.725-3.701c-0.15-0.604-0.313-1.202-0.49-1.793 c-0.354-1.181-0.764-2.335-1.226-3.458c-0.693-1.685-1.504-3.301-2.422-4.84c-0.613-1.026-1.274-2.017-1.98-2.971 c-2.119-2.863-4.646-5.39-7.509-7.509c-1.909-1.412-3.966-2.643-6.15-3.67c-1.638-0.77-3.348-1.426-5.12-1.958 c-1.181-0.355-2.39-0.655-3.624-0.896c-2.468-0.484-5.035-0.737-7.68-0.737c-21.162,0-37.345,16.183-37.345,37.345 C68.657,109.317,84.84,125.5,106.002,125.5z"></path></g></svg>

                                                    </div>
                                                </div>
                                                <div className="contact-info-container" role="button">
                                                    <div className="contact-info">
                                                        <div className="contact-name"><span className="phone-number">{chatName}</span></div>
                                                    </div>
                                                    <div className="last-seen-container"><span title="last seen today at 6:15 PM" className="last-seen">+{selectNumber}</span></div>
                                                </div>
                                                <div className="header-buttons-container">
                                                    <div className="button-icons">
                                                        {/* <div className="icon-container">
                                                        <div title="Search">
                                                            <span data-icon="search-alt" className="search-icon"></span>
                                                        </div>
                                                        <span></span>
                                                    </div>
                                                    <div className="icon-container">
                                                        <div role="button" title="Attach">
                                                            <span data-icon="clip" className="attach-icon"></span>
                                                        </div>
                                                        <span></span>
                                                    </div>
                                                    <div className="icon-container">
                                                        <div title="Menu">
                                                            <span data-icon="menu" className="menu-icon"></span>
                                                        </div>
                                                        <span></span>
                                                    </div> */}
                                                        <div className="icon-container">
                                                            <button
                                                                type="button"
                                                                className="btn navyblue text-right float-right py-1 px-2"
                                                                onClick={(e) => handleDeleteConversation(e)}
                                                            >
                                                                <i className="fa fa-trash"></i>
                                                            </button>
                                                            <span></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </header>
                                            {/* <hr style={{ marginTop: "0" }} /> */}
                                            {/* Chat messages */}
                                            <div className="wa chat-conversation"
                                                style={{ borderRadius: "0" }}
                                            >
                                                {/* <img src={wa} /> */}
                                                <div data-simplebar="init" style={{ minHeight: "76vh" }}>
                                                    <div className="simplebar-wrapper" style={{ margin: "0px" }}>
                                                        <div
                                                            chat-content=""
                                                            id="ko"
                                                            style={{
                                                                overflowY: "scroll",
                                                                width: "auto",
                                                                height: "76vh",
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
                                                                    listStyleType: "none",
                                                                    padding: 0,
                                                                }}
                                                            >
                                                                {Object.keys(groupedMessages).map((date, idx) => (
                                                                    <div key={idx}>
                                                                        <div
                                                                            className="date-header"
                                                                            style={{
                                                                                textAlign: "center",
                                                                                margin: "10px 0",
                                                                                fontWeight: "bold",
                                                                                backgroundColor: "white",
                                                                                padding: "5px 5px",   
                                                                                borderRadius: "10px",    
                                                                                boxShadow: "0 1px 3px rgba(0, 0, 0, 0.1)",
                                                                                display: "inline-block", 
                                                                                width: "auto",
                                                                                marginLeft: "320px"
                                                                            }}
                                                                        >
                                                                            {date}
                                                                        </div>

                                                                        {groupedMessages[date].map((m, i) => {
                                                                            if (m.message !== "restart") {
                                                                                return (
                                                                                    <li className={m.flex === "C" ? "clearfix" : "clearfix odd"} key={i}>
                                                                                        <div
                                                                                            className="conversation-text"
                                                                                            style={{
                                                                                                display: 'flex',
                                                                                                alignItems: "center",
                                                                                                justifyContent: m.flex !== "C" && "end",
                                                                                            }}
                                                                                        >
                                                                                            <div
                                                                                                className={`message-bubble ${m.flex !== "C" ? "message-outgoing" : "message-incoming"}`}
                                                                                            >
                                                                                                <div className="message-content">
                                                                                                    <div
                                                                                                        className="text-content"
                                                                                                        style={{ display: "flex", flexDirection: "column" }}
                                                                                                    >
                                                                                                        {m?.message != null && m?.message?.startsWith("Replying to:") && (
                                                                                                            <span className="replying-text">{m.message}</span>
                                                                                                        )}

                                                                                                        {m?.message != null && m?.message?.startsWith("Replying to:") && (
                                                                                                            <>
                                                                                                                {webhookResponses
                                                                                                                    ?.filter((response) => response.id === m.wa_id)
                                                                                                                    .map((response) => (
                                                                                                                        <React.Fragment key={response.id}>
                                                                                                                            {response.video && (
                                                                                                                                <video width="300" height="220" controls>
                                                                                                                                    <source
                                                                                                                                        src={`/storage/uploads/media/${response.video}`}
                                                                                                                                        type="video/mp4"
                                                                                                                                    />
                                                                                                                                </video>
                                                                                                                            )}
                                                                                                                            {response.image && (
                                                                                                                                <img
                                                                                                                                    src={`/storage/uploads/media/${response.image}`}
                                                                                                                                    alt="image"
                                                                                                                                    width="300"
                                                                                                                                />
                                                                                                                            )}
                                                                                                                        </React.Fragment>
                                                                                                                    ))}
                                                                                                            </>
                                                                                                        )}

                                                                                                        {!m?.message?.startsWith("Replying to:") && (
                                                                                                            <>
                                                                                                                {m?.video && (
                                                                                                                    <video width="300" height="220" controls>
                                                                                                                        <source
                                                                                                                            src={`/storage/uploads/media/${m.video}`}
                                                                                                                            type="video/mp4"
                                                                                                                        />
                                                                                                                    </video>
                                                                                                                )}
                                                                                                                {m?.image && (
                                                                                                                    <img
                                                                                                                        src={`/storage/uploads/media/${m.image}`}
                                                                                                                        alt="image"
                                                                                                                        width="300"
                                                                                                                    />
                                                                                                                )}
                                                                                                                <br />
                                                                                                                {m.message}
                                                                                                            </>
                                                                                                        )}
                                                                                                    </div>
                                                                                                    <div className="message-info">
                                                                                                        <span className="message-time">
                                                                                                            {new Date(m.created_at).toLocaleTimeString("en-GB")}
                                                                                                        </span>
                                                                                                        <span className="message-status">
                                                                                                            <svg
                                                                                                                xmlns="http://www.w3.org/2000/svg"
                                                                                                                viewBox="0 0 16 15"
                                                                                                                width="16"
                                                                                                                height="15"
                                                                                                            >
                                                                                                                <path
                                                                                                                    fill="#727678"
                                                                                                                    d="M15.01 3.316l-.478-.372a.365.365 0 0 0-.51.063L8.666 9.879a.32.32 0 0 1-.484.033l-.358-.325a.319.319 0 0 0-.484.032l-.378.483a.418.418 0 0 0 .036.541l1.32 1.266c.143.14.361.125.484-.033l6.272-8.048a.366.366 0 0 0-.064-.512zm-4.1 0l-.478-.372a.365.365 0 0 0-.51.063L4.566 9.879a.32.32 0 0 1-.484.033L1.891 7.769a.366.366 0 0 0-.515.006l-.423.433a.364.364 0 0 0 .006.514l3.258 3.185c.143.14.361.125.484-.033l6.272-8.048a.365.365 0 0 0-.063-.51z"
                                                                                                                ></path>
                                                                                                            </svg>
                                                                                                        </span>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                            <i
                                                                                                className="fa-solid fa-reply"
                                                                                                style={{
                                                                                                    marginBottom: "50px",
                                                                                                    cursor: "pointer",
                                                                                                    background: "#e7e3e3",
                                                                                                    padding: "7px",
                                                                                                    borderRadius: "100%",
                                                                                                }}
                                                                                                onClick={() => {
                                                                                                    if (m?.video != null) {
                                                                                                        setMedia(m.video);
                                                                                                    } else if (m?.image != null) {
                                                                                                        setImage(m?.image);
                                                                                                    }
                                                                                                    setReplyMessage(m.message);
                                                                                                    setReplyId(m?.id);
                                                                                                }}
                                                                                            ></i>
                                                                                        </div>
                                                                                    </li>
                                                                                );
                                                                            }
                                                                            return null;
                                                                        })}
                                                                    </div>
                                                                ))}
                                                                <div ref={chatEndRef} />
                                                            </ul>

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
