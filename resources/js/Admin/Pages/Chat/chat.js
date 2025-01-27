import axios from "axios";
import EmojiPicker from 'emoji-picker-react';
import React, { useEffect, useRef, useState } from "react";
import { useAlert } from "react-alert";
import { Button, Modal } from 'react-bootstrap';
import { useTranslation } from "react-i18next";
import { FaRegCircleUser } from "react-icons/fa6";
import { RiAccountCircleFill } from "react-icons/ri";
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
    const [groupedMessages, setGroupedMessages] = useState({});
    const [selectNumber, setSelectNumber] = useState(null);
    const [clients, setClients] = useState(null);
    const [expired, setExpired] = useState(0);
    const [isOpen, setIsOpen] = useState(false);
    const [chatName, setChatName] = useState("")
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
    const [waSvg, setWaSvg] = useState(false);

    const windowWidth = useWindowWidth();

    const adminLng = localStorage.getItem("admin-lng")

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

    const getAllChats = async () => {
        try {
            const response = await axios.get('/api/admin/get-all-chats', { headers });
            console.log(response.data, "response.data");

        } catch (error) {
            console.error(error);
        }
    }

    const getChatById = async (chatId) => {
        try {
            const response = await axios.get(`/api/admin/get-chat/${chatId}`, { headers });
            console.log(response.data, "response.data");
        } catch (error) {
            console.error(error);
        }
    };

    const getConversationsByNumber = async (chatId) => {
        try {
            const response = await axios.get(`/api/admin/get-conversations/${chatId}`, { headers });
            console.log(response.data, "conversations");
        } catch (error) {
            console.error(error);
        }
    };

    // const deleteMessage = async (messageId) => {
    //     console.log(messageId, "messageId");

    //     try {
    //         const response = await axios.delete(`/api/admin/delete-message/${messageId}`, { headers });
    //         console.log(response.data, "response.data");
    //     } catch (error) {
    //         console.error(error);
    //     }
    // };


    useEffect(() => {
        getWebhook()
    }, []);


    useEffect(() => {
        if (!selectNumber && showChatList) {
            const interval = setInterval(() => {
                const wa = document.getElementById('waChat');
                if (wa) {
                    setWaSvg(true)
                    clearInterval(interval);
                } else {
                    setWaSvg(false)
                    clearInterval(interval);
                }
            }, 100);
        } else {
            setWaSvg(false)
        }
    }, [selectNumber]);

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

        send.forEach((value, key) => {
            console.log(`${key}:`, value);
        });

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
        const objDiv = document.getElementById("ko");
        if (objDiv) {
            objDiv.scrollTop = objDiv.scrollHeight;
        }
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

    const parsedData = (data) => {
        if (!data) {
            // console.warn("Data is null or undefined");
            return 'No data provided';
        }

        try {
            let jsonData = typeof data === 'string' ? JSON.parse(data) : data;
            // Check if the parsed data is still a string (stringified JSON inside JSON)
            if (typeof jsonData === 'string') {
                jsonData = JSON.parse(jsonData);
            }
            // Safely extract the message ID
            const messageId = jsonData?.messages?.[0]?.id || 'Message ID not found';
            // console.log(messageId, "Extracted Message ID");
            return messageId;
        } catch (error) {
            return 'Invalid data format';
        }
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
                            <svg viewBox="0 0 212 212" height="45" width="45" preserveAspectRatio="xMidYMid meet" className="xh8yej3 x5yr21d" version="1.1" x="0px" y="0px" enableBackground="new 0 0 212 212"><title>default-user</title><path fill="#DFE5E7" className="background" d="M106.251,0.5C164.653,0.5,212,47.846,212,106.25S164.653,212,106.25,212C47.846,212,0.5,164.654,0.5,106.25 S47.846,0.5,106.251,0.5z"></path><g><path fill="#FFFFFF" className="primary" d="M173.561,171.615c-0.601-0.915-1.287-1.907-2.065-2.955c-0.777-1.049-1.645-2.155-2.608-3.299 c-0.964-1.144-2.024-2.326-3.184-3.527c-1.741-1.802-3.71-3.646-5.924-5.47c-2.952-2.431-6.339-4.824-10.204-7.026 c-1.877-1.07-3.873-2.092-5.98-3.055c-0.062-0.028-0.118-0.059-0.18-0.087c-9.792-4.44-22.106-7.529-37.416-7.529 s-27.624,3.089-37.416,7.529c-0.338,0.153-0.653,0.318-0.985,0.474c-1.431,0.674-2.806,1.376-4.128,2.101 c-0.716,0.393-1.417,0.792-2.101,1.197c-3.421,2.027-6.475,4.191-9.15,6.395c-2.213,1.823-4.182,3.668-5.924,5.47 c-1.161,1.201-2.22,2.384-3.184,3.527c-0.964,1.144-1.832,2.25-2.609,3.299c-0.778,1.049-1.464,2.04-2.065,2.955 c-0.557,0.848-1.033,1.622-1.447,2.324c-0.033,0.056-0.073,0.119-0.104,0.174c-0.435,0.744-0.79,1.392-1.07,1.926 c-0.559,1.068-0.818,1.678-0.818,1.678v0.398c18.285,17.927,43.322,28.985,70.945,28.985c27.678,0,52.761-11.103,71.055-29.095 v-0.289c0,0-0.619-1.45-1.992-3.778C174.594,173.238,174.117,172.463,173.561,171.615z"></path><path fill="#FFFFFF" className="primary" d="M106.002,125.5c2.645,0,5.212-0.253,7.68-0.737c1.234-0.242,2.443-0.542,3.624-0.896 c1.772-0.532,3.482-1.188,5.12-1.958c2.184-1.027,4.242-2.258,6.15-3.67c2.863-2.119,5.39-4.646,7.509-7.509 c0.706-0.954,1.367-1.945,1.98-2.971c0.919-1.539,1.729-3.155,2.422-4.84c0.462-1.123,0.872-2.277,1.226-3.458 c0.177-0.591,0.341-1.188,0.49-1.792c0.299-1.208,0.542-2.443,0.725-3.701c0.275-1.887,0.417-3.827,0.417-5.811 c0-1.984-0.142-3.925-0.417-5.811c-0.184-1.258-0.426-2.493-0.725-3.701c-0.15-0.604-0.313-1.202-0.49-1.793 c-0.354-1.181-0.764-2.335-1.226-3.458c-0.693-1.685-1.504-3.301-2.422-4.84c-0.613-1.026-1.274-2.017-1.98-2.971 c-2.119-2.863-4.646-5.39-7.509-7.509c-1.909-1.412-3.966-2.643-6.15-3.67c-1.638-0.77-3.348-1.426-5.12-1.958 c-1.181-0.355-2.39-0.655-3.624-0.896c-2.468-0.484-5.035-0.737-7.68-0.737c-21.162,0-37.345,16.183-37.345,37.345 C68.657,109.317,84.84,125.5,106.002,125.5z"></path></g></svg>

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
                                                                {t("admin.global.chats")}
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
                                                                {t("admin.global.clients")}

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
                                                                    setWaSvg(false);
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
                                                        className="btn navyblue mx-2 d-lg-none"
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
                                                            {t("admin.global.chats")}

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
                                                            {t("admin.global.clients")}

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
                                                                setWaSvg(false);
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

                                        <div id="waChat" className="col-xl-8 col-12 p-0" style={{
                                            backgroundColor: waSvg ? "#f5f5f5" : "white"
                                        }}>
                                            {
                                                waSvg ? (
                                                    <>
                                                        <div
                                                            className="d-flex justify-content-center align-items-center flex-column"
                                                            style={{
                                                                position: 'absolute',
                                                                top: '50%',
                                                                left: '50%',
                                                                transform: 'translate(-50%, -50%)',
                                                                height: '90%',
                                                            }}
                                                        >
                                                            <svg viewBox="0 0 303 172" width="360" preserveAspectRatio="xMidYMid meet" className="" fill="none"><title>intro-md-beta-logo-light</title><path fillRule="evenodd" clipRule="evenodd" d="M229.565 160.229C262.212 149.245 286.931 118.241 283.39 73.4194C278.009 5.31929 212.365 -11.5738 171.472 8.48673C115.998 35.6999 108.972 40.1612 69.2388 40.1612C39.645 40.1612 9.51318 54.4147 5.7467 92.952C3.0166 120.885 13.9985 145.267 54.6373 157.716C128.599 180.373 198.017 170.844 229.565 160.229Z" fill="#DAF7F3"></path><path fillRule="evenodd" clipRule="evenodd" d="M131.589 68.9422C131.593 68.9422 131.596 68.9422 131.599 68.9422C137.86 68.9422 142.935 63.6787 142.935 57.1859C142.935 50.6931 137.86 45.4297 131.599 45.4297C126.518 45.4297 122.218 48.8958 120.777 53.6723C120.022 53.4096 119.213 53.2672 118.373 53.2672C114.199 53.2672 110.815 56.7762 110.815 61.1047C110.815 65.4332 114.199 68.9422 118.373 68.9422C118.377 68.9422 118.381 68.9422 118.386 68.9422H131.589Z" fill="white"></path><path fillRule="evenodd" clipRule="evenodd" d="M105.682 128.716C109.186 128.716 112.026 125.908 112.026 122.446C112.026 118.983 109.186 116.176 105.682 116.176C104.526 116.176 103.442 116.481 102.509 117.015L102.509 116.959C102.509 110.467 97.1831 105.203 90.6129 105.203C85.3224 105.203 80.8385 108.616 79.2925 113.335C78.6052 113.143 77.88 113.041 77.1304 113.041C72.7503 113.041 69.1995 116.55 69.1995 120.878C69.1995 125.207 72.7503 128.716 77.1304 128.716C77.1341 128.716 77.1379 128.716 77.1416 128.716H105.682L105.682 128.716Z" fill="white"></path><rect x="0.445307" y="0.549558" width="50.5797" height="100.068" rx="7.5" transform="matrix(0.994522 0.104528 -0.103907 0.994587 10.5547 41.6171)" fill="#42CBA5" stroke="#316474"></rect><rect x="0.445307" y="0.549558" width="50.4027" height="99.7216" rx="7.5" transform="matrix(0.994522 0.104528 -0.103907 0.994587 10.9258 37.9564)" fill="white" stroke="#316474"></rect><path d="M57.1609 51.7354L48.5917 133.759C48.2761 136.78 45.5713 138.972 42.5503 138.654L9.58089 135.189C6.55997 134.871 4.36688 132.165 4.68251 129.144L13.2517 47.1204C13.5674 44.0992 16.2722 41.9075 19.2931 42.2251L24.5519 42.7778L47.0037 45.1376L52.2625 45.6903C55.2835 46.0078 57.4765 48.7143 57.1609 51.7354Z" fill="#EEFEFA" stroke="#316474"></path><path d="M26.2009 102.937C27.0633 103.019 27.9323 103.119 28.8023 103.21C29.0402 101.032 29.2706 98.8437 29.4916 96.6638L26.8817 96.39C26.6438 98.5681 26.4049 100.755 26.2009 102.937ZM23.4704 93.3294L25.7392 91.4955L27.5774 93.7603L28.7118 92.8434L26.8736 90.5775L29.1434 88.7438L28.2248 87.6114L25.955 89.4452L24.1179 87.1806L22.9824 88.0974L24.8207 90.3621L22.5508 92.197L23.4704 93.3294ZM22.6545 98.6148C22.5261 99.9153 22.3893 101.215 22.244 102.514C23.1206 102.623 23.9924 102.697 24.8699 102.798C25.0164 101.488 25.1451 100.184 25.2831 98.8734C24.4047 98.7813 23.5298 98.6551 22.6545 98.6148ZM39.502 89.7779C38.9965 94.579 38.4833 99.3707 37.9862 104.174C38.8656 104.257 39.7337 104.366 40.614 104.441C41.1101 99.6473 41.6138 94.8633 42.1271 90.0705C41.2625 89.9282 40.3796 89.8786 39.502 89.7779ZM35.2378 92.4459C34.8492 96.2179 34.4351 99.9873 34.0551 103.76C34.925 103.851 35.7959 103.934 36.6564 104.033C37.1028 100.121 37.482 96.1922 37.9113 92.2783C37.0562 92.1284 36.18 92.0966 35.3221 91.9722C35.2812 92.1276 35.253 92.286 35.2378 92.4459ZM31.1061 94.1821C31.0635 94.341 31.0456 94.511 31.0286 94.6726C30.7324 97.5678 30.4115 100.452 30.1238 103.348L32.7336 103.622C32.8582 102.602 32.9479 101.587 33.0639 100.567C33.2611 98.5305 33.5188 96.4921 33.6905 94.4522C32.8281 94.3712 31.9666 94.2811 31.1061 94.1821Z" fill="#316474"></path><path d="M17.892 48.4889C17.7988 49.3842 18.4576 50.1945 19.3597 50.2923C20.2665 50.3906 21.0855 49.7332 21.1792 48.8333C21.2724 47.938 20.6136 47.1277 19.7115 47.0299C18.8047 46.9316 17.9857 47.5889 17.892 48.4889Z" fill="white" stroke="#316474"></path><path d="M231.807 136.678L197.944 139.04C197.65 139.06 197.404 139.02 197.249 138.96C197.208 138.945 197.179 138.93 197.16 138.918L196.456 128.876C196.474 128.862 196.5 128.843 196.538 128.822C196.683 128.741 196.921 128.668 197.215 128.647L231.078 126.285C231.372 126.265 231.618 126.305 231.773 126.365C231.814 126.381 231.842 126.395 231.861 126.407L232.566 136.449C232.548 136.463 232.522 136.482 232.484 136.503C232.339 136.584 232.101 136.658 231.807 136.678Z" fill="white" stroke="#316474"></path><path d="M283.734 125.679L144.864 135.363C141.994 135.563 139.493 133.4 139.293 130.54L133.059 41.6349C132.858 38.7751 135.031 36.2858 137.903 36.0856L276.773 26.4008C279.647 26.2005 282.144 28.364 282.345 31.2238L288.578 120.129C288.779 122.989 286.607 125.478 283.734 125.679Z" fill="white"></path><path d="M144.864 135.363C141.994 135.563 139.493 133.4 139.293 130.54L133.059 41.6349C132.858 38.7751 135.031 36.2858 137.903 36.0856L276.773 26.4008C279.647 26.2004 282.144 28.364 282.345 31.2238L288.578 120.129C288.779 122.989 286.607 125.478 283.734 125.679" stroke="#316474"></path><path d="M278.565 121.405L148.68 130.463C146.256 130.632 144.174 128.861 144.012 126.55L138.343 45.695C138.181 43.3846 139.994 41.3414 142.419 41.1723L272.304 32.1142C274.731 31.945 276.81 33.7166 276.972 36.0271L282.641 116.882C282.803 119.193 280.992 121.236 278.565 121.405Z" fill="#EEFEFA" stroke="#316474"></path><path d="M230.198 129.97L298.691 125.193L299.111 131.189C299.166 131.97 299.013 132.667 298.748 133.161C298.478 133.661 298.137 133.887 297.825 133.909L132.794 145.418C132.482 145.44 132.113 145.263 131.777 144.805C131.445 144.353 131.196 143.684 131.141 142.903L130.721 136.907L199.215 132.131C199.476 132.921 199.867 133.614 200.357 134.129C200.929 134.729 201.665 135.115 202.482 135.058L227.371 133.322C228.188 133.265 228.862 132.782 229.345 132.108C229.758 131.531 230.05 130.79 230.198 129.97Z" fill="#42CBA5" stroke="#316474"></path><path d="M230.367 129.051L300.275 124.175L300.533 127.851C300.591 128.681 299.964 129.403 299.13 129.461L130.858 141.196C130.025 141.254 129.303 140.627 129.245 139.797L128.987 136.121L198.896 131.245C199.485 132.391 200.709 133.147 202.084 133.051L227.462 131.281C228.836 131.185 229.943 130.268 230.367 129.051Z" fill="white" stroke="#316474"></path><ellipse rx="15.9969" ry="15.9971" transform="matrix(0.997577 -0.0695704 0.0699429 0.997551 210.659 83.553)" fill="#42CBA5" stroke="#316474"></ellipse><path d="M208.184 87.1094L204.777 84.3593C204.777 84.359 204.776 84.3587 204.776 84.3583C203.957 83.6906 202.744 83.8012 202.061 84.6073C201.374 85.4191 201.486 86.6265 202.31 87.2997L202.312 87.3011L207.389 91.4116C207.389 91.4119 207.389 91.4121 207.389 91.4124C208.278 92.1372 209.611 91.9373 210.242 90.9795L218.283 78.77C218.868 77.8813 218.608 76.6968 217.71 76.127C216.817 75.5606 215.624 75.8109 215.043 76.6939L208.184 87.1094Z" fill="white" stroke="#316474"></path></svg>
                                                            <div className="mt-3 text-center" style={{ color: "#41525d", fontSize: "32px", fontWeight: 300, fontFamily: "system-ui" }}>
                                                                <p>{t("admin.global.wa_chat")}</p>
                                                                <p className="font-15">{t("admin.global.s_and_r_message")}.</p>
                                                            </div>

                                                        </div>

                                                    </>
                                                ) : (
                                                    <>
                                                        <header className="header-container">
                                                            <div className="profile-picture-container" >
                                                                <button
                                                                    className="btn navyblue mx-2 d-lg-none"
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
                                                                    <svg viewBox="0 0 212 212" height="45" width="45" preserveAspectRatio="xMidYMid meet" className="xh8yej3 x5yr21d" version="1.1" x="0px" y="0px" enableBackground="new 0 0 212 212"><title>default-user</title><path fill="#DFE5E7" className="background" d="M106.251,0.5C164.653,0.5,212,47.846,212,106.25S164.653,212,106.25,212C47.846,212,0.5,164.654,0.5,106.25 S47.846,0.5,106.251,0.5z"></path><g><path fill="#FFFFFF" className="primary" d="M173.561,171.615c-0.601-0.915-1.287-1.907-2.065-2.955c-0.777-1.049-1.645-2.155-2.608-3.299 c-0.964-1.144-2.024-2.326-3.184-3.527c-1.741-1.802-3.71-3.646-5.924-5.47c-2.952-2.431-6.339-4.824-10.204-7.026 c-1.877-1.07-3.873-2.092-5.98-3.055c-0.062-0.028-0.118-0.059-0.18-0.087c-9.792-4.44-22.106-7.529-37.416-7.529 s-27.624,3.089-37.416,7.529c-0.338,0.153-0.653,0.318-0.985,0.474c-1.431,0.674-2.806,1.376-4.128,2.101 c-0.716,0.393-1.417,0.792-2.101,1.197c-3.421,2.027-6.475,4.191-9.15,6.395c-2.213,1.823-4.182,3.668-5.924,5.47 c-1.161,1.201-2.22,2.384-3.184,3.527c-0.964,1.144-1.832,2.25-2.609,3.299c-0.778,1.049-1.464,2.04-2.065,2.955 c-0.557,0.848-1.033,1.622-1.447,2.324c-0.033,0.056-0.073,0.119-0.104,0.174c-0.435,0.744-0.79,1.392-1.07,1.926 c-0.559,1.068-0.818,1.678-0.818,1.678v0.398c18.285,17.927,43.322,28.985,70.945,28.985c27.678,0,52.761-11.103,71.055-29.095 v-0.289c0,0-0.619-1.45-1.992-3.778C174.594,173.238,174.117,172.463,173.561,171.615z"></path><path fill="#FFFFFF" className="primary" d="M106.002,125.5c2.645,0,5.212-0.253,7.68-0.737c1.234-0.242,2.443-0.542,3.624-0.896 c1.772-0.532,3.482-1.188,5.12-1.958c2.184-1.027,4.242-2.258,6.15-3.67c2.863-2.119,5.39-4.646,7.509-7.509 c0.706-0.954,1.367-1.945,1.98-2.971c0.919-1.539,1.729-3.155,2.422-4.84c0.462-1.123,0.872-2.277,1.226-3.458 c0.177-0.591,0.341-1.188,0.49-1.792c0.299-1.208,0.542-2.443,0.725-3.701c0.275-1.887,0.417-3.827,0.417-5.811 c0-1.984-0.142-3.925-0.417-5.811c-0.184-1.258-0.426-2.493-0.725-3.701c-0.15-0.604-0.313-1.202-0.49-1.793 c-0.354-1.181-0.764-2.335-1.226-3.458c-0.693-1.685-1.504-3.301-2.422-4.84c-0.613-1.026-1.274-2.017-1.98-2.971 c-2.119-2.863-4.646-5.39-7.509-7.509c-1.909-1.412-3.966-2.643-6.15-3.67c-1.638-0.77-3.348-1.426-5.12-1.958 c-1.181-0.355-2.39-0.655-3.624-0.896c-2.468-0.484-5.035-0.737-7.68-0.737c-21.162,0-37.345,16.183-37.345,37.345 C68.657,109.317,84.84,125.5,106.002,125.5z"></path></g></svg>

                                                                </div>
                                                            </div>
                                                            <div className="contact-info-container" role="button">
                                                                <div className="contact-info">
                                                                    <div className="contact-name"><span className="phone-number">{chatName}</span></div>
                                                                </div>
                                                                <div className="last-seen-container"><span title="last seen today at 6:15 PM" className="last-seen">+{selectNumber}</span></div>
                                                            </div>
                                                            <div className="header-buttons-container">
                                                                <div className="button-icons mx-3">
                                                                    <div className="icon-container">
                                                                        <button
                                                                            type="button"
                                                                            className="btn navyblue text-right float-right py-1 px-2 "
                                                                            onClick={(e) => handleDeleteConversation(e)}
                                                                        >
                                                                            <i className="fa fa-trash"></i>
                                                                        </button>
                                                                        <span></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </header>
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
                                                                                        <div className="text-center">
                                                                                            <span
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
                                                                                                    // marginLeft: "320px"
                                                                                                }}
                                                                                            >
                                                                                                {date}
                                                                                            </span>
                                                                                        </div>

                                                                                        {groupedMessages[date].map((m, i) => {
                                                                                               const chatId = parsedData(m.data);

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
                                                                                                                            <span className="replying-text" >{m.message}</span>
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
                                                                                                                    setReplyId(chatId??m.id);
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
                                                                            placeholder={t("admin.global.chatPlaceholder")}
                                                                            value={message}
                                                                            onChange={(e) => handleInputChange(e)}
                                                                        />

                                                                        {/* Send Button */}
                                                                        <button className="wa-input-icon wa-send-button mx-2"
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
                                                                            <Modal.Title>{t("admin.global.select_emoji")}</Modal.Title>
                                                                        </Modal.Header>
                                                                        <Modal.Body>
                                                                            <div className="d-flex justify-content-center align-items-center">
                                                                                <EmojiPicker onEmojiClick={onEmojiClick} />
                                                                            </div>
                                                                        </Modal.Body>
                                                                        <Modal.Footer>
                                                                            <Button variant="secondary" onClick={() => setEmoji(false)}>
                                                                                {t("global.close")}
                                                                            </Button>
                                                                        </Modal.Footer>
                                                                    </Modal>
                                                                    <Modal show={waMedia} onHide={() => setWaMedia(false)} centered>
                                                                        <Modal.Header closeButton>
                                                                            <Modal.Title>{t("admin.global.upload_options")}</Modal.Title>
                                                                        </Modal.Header>
                                                                        <Modal.Body>
                                                                            <div className="d-flex flex-column">
                                                                                <Button variant="outline-primary" onClick={() => handleFileUpload('Image')}>
                                                                                    📷 {t("admin.global.upload_image")}
                                                                                </Button>
                                                                                <Button variant="outline-primary" onClick={() => handleFileUpload('Video')}>
                                                                                    🎥 {t("admin.global.upload_video")}
                                                                                </Button>
                                                                            </div>
                                                                        </Modal.Body>
                                                                        <Modal.Footer>
                                                                            <Button variant="secondary" onClick={() => setWaMedia(false)}>
                                                                                {t("global.close")}
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
                                                    </>
                                                )
                                            }
                                        </div>
                                    </div>
                                </div>
                            </div >
                        )
                    }
                </div >
            </div >


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
        </div >
    );
}
