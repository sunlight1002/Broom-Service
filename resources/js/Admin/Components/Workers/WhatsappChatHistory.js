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
import '../../Pages/Chat/ChatFooter.css'; // Import the CSS

const WhatsappChatHistory = ({
    workerId,
    worker
}) => {
    const { t } = useTranslation();
    const [data, setData] = useState([]);
    const [messages, setMessages] = useState(null);
    const [groupedMessages, setGroupedMessages] = useState({});
    const [selectNumber, setSelectNumber] = useState(null);
    const [clients, setClients] = useState([]);
    const [expired, setExpired] = useState({
        expired: 0,
        offical: 0,
        isExist: 0
    });
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
    const [loadingChats, setLoadingChats] = useState(false);
    const [page, setPage] = useState(1); // Page number to fetch
    const [hasMore, setHasMore] = useState(true); // To track if more records exist
    const [tabs, setTabs] = useState([]);
    const [activeTab, setActiveTab] = useState(null);


    const fromNumber = process.env.MIX_TWILIO_WHATSAPP_NUMBER;
    console.log(fromNumber, "fromNumber");


    const windowWidth = useWindowWidth();

    useEffect(() => {
        if (windowWidth < 1199) {
            setShowChatList(true)
        } else {
            setShowChatList(false)
        }
    }, [windowWidth])


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

    const alert = useAlert();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    // const getData = () => {
    //     if (loadingChats || !hasMore) return; // Prevent multiple requests

    //     setLoadingChats(true);
    //     axios.get(`/api/admin/personal-chat?page=${page}&from=${fromNumber}`, { headers })
    //         .then((res) => {
    //             const newData = res.data.data;
    //             const newClients = res.data.clients;

    //             // Update data and clients
    //             setClients(prevClients => [...prevClients, ...newClients]);
    //             setData(prevData => [...prevData, ...newData]);

    //             // Update page number
    //             // setPage(prevPage => prevPage + 1);

    //             // If no more data to load, set hasMore to false
    //             if (newData?.length === 0) {
    //                 setHasMore(false);
    //             }
    //         })
    //         .catch((error) => {
    //             console.error("Error loading data:", error);
    //         })
    //         .finally(() => {
    //             setLoadingChats(false);
    //         });
    // };


    const getMessages = (no) => {
        axios.get(`/api/admin/chat-message/${worker?.phone}?from=${fromNumber}`, { headers }).then((res) => {
            // console.log(res.data, "res.data");

            const c = res.data.chat;
            let cl = localStorage.getItem("chatLen");
            // if (cl > c.length) {
            //     scroller();
            // }
            setChatName(res?.data?.clientName)

            localStorage.setItem("chatLen", c.length);
            setExpired({
                expired: res.data.expired,
                offical: res.data.offical,
                isExist: res.data.isExist
            });
            setMessages(c);

            const grouped = groupMessagesByDate(c);
            setGroupedMessages(grouped);
        });
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
        send.append("number", worker?.phone);
        send.append("message", messageToSend);
        send.append("from", fromNumber);
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
            getMessages(fromNumber);
        }, 8000);
        return () => clearInterval(interval);
    };

    const scroller = () => {
        const objDiv = document.getElementById("ko");
        if (objDiv) {
            objDiv.scrollTop = objDiv.scrollHeight;
        }
    };


    const handleScroll = (e) => {
        const bottom = e.target.scrollHeight === e.target.scrollTop + e.target.clientHeight;
        if (bottom) {
            console.log("Loading more data...");
            setPage(prevPage => prevPage + 1);
            // getData(); // Load more data when reaching the bottom
        }
    };


    // Add the scroll event listener to the container
    useEffect(() => {
        const scrollContainer = document.getElementById('scrollContainer');
        if (scrollContainer) {
            scrollContainer.addEventListener('scroll', handleScroll);
        }
        return () => {
            if (scrollContainer) {
                scrollContainer.removeEventListener('scroll', handleScroll);
            }
        };
    }, [data, loadingChats, hasMore]);


    useEffect(() => {
        callApi();
        getMessages(fromNumber);
    }, []);

    // useEffect(() => {
    //     setPage(1); // optional: reset pagination if tab changed
    //     setData([]);
    //     setClients([]);
    //     setHasMore(true);
    //     getData();
    // }, []);

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
                        // setTimeout(() => {
                        //     getData();
                        // }, 1000);
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



    return (
        <div>
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
                                                                                // <span className="replying-text" >{m.message}</span>
                                                                                <pre className="replying-text" style={{ whiteSpace: 'pre-wrap', fontFamily: 'inherit' }}>
                                                                                    {m.message}
                                                                                </pre>
                                                                            )}

                                                                            {m?.message != null && m?.message?.startsWith("Replying to:") && (
                                                                                <>
                                                                                    {groupedMessages[date]
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
                                                                                    <pre style={{ whiteSpace: 'pre-wrap', fontFamily: 'inherit' }}>
                                                                                        {m.message}
                                                                                    </pre>                                                                                                                            </>
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
                                                                        setReplyId(chatId ?? m.id);
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


                <div className="wa-input-bar">
                    {/* Attachments Button */}
                    {
                        expired.offical === 0 && (
                            <button className="wa-input-icon"
                                onClick={() => setWaMedia(true)}
                            >
                                <i className="fa fa-paperclip" aria-hidden="true"></i>
                            </button>
                        )
                    }

                    {/* Emoji Button */}
                    <button className="wa-input-icon"
                        onClick={() => setEmoji(prev => !prev)}
                    >
                        <i className="fa-regular fa-face-smile" aria-hidden="true"></i>
                    </button>

                    {/* Text Input */}
                    <textarea
                        name="message"
                        id="message_typing"
                        className="wa-input-text"
                        style={{ resize: 'none' }}
                        disabled={loading}
                        onKeyDown={(e) => {
                            if (!(
                                (expired.isExist === 0 && expired.offical === 1) ||
                                (expired.expired === 1 && expired.offical === 1)
                            )) {
                                e.key === "Enter" && !e.shiftKey ? (e.preventDefault(), sendMessage()) : null;
                            }
                        }}
                        placeholder={t("admin.global.chatPlaceholder")}
                        value={message}
                        onChange={handleInputChange}
                    />


                    {/* Send Button */}
                    {
                        !(
                            (expired.isExist === 0 && expired.offical === 1) ||
                            (expired.expired === 1 && expired.offical === 1)
                        ) && (
                            <button
                                className="wa-input-icon wa-send-button mx-2"
                                onClick={(e) => sendMessage()}
                                disabled={selectedFile && selectedFile?.name ? false : message == ''}
                            >
                                {
                                    loading ? (
                                        <MiniLoader />
                                    ) : (
                                        <i className="fa fa-paper-plane" aria-hidden="true"></i>
                                    )
                                }
                            </button>
                        )
                    }


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

            </div>
        </div>
    )
}

export default WhatsappChatHistory
