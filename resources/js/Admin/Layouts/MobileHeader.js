import React, { useState, useEffect } from "react";
import logo from "../../Assets/image/sample.svg";
import bars from "../../Assets/image/icons/bars.svg";
import { useAlert } from "react-alert";
import { useNavigate } from "react-router-dom";
import axios from "axios";
import "./mobile.css"


export default function MobileHeader() {
    const alert = useAlert();
    const navigate = useNavigate();
    const [role, setRole] = useState();
    const [isSidebarOpen, setIsSidebarOpen] = useState(false); // New state for sidebar visibility
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleClick = (event, path) => {
        event.preventDefault();
        navigate(path);  // Programmatically navigate to the given path
        setIsSidebarOpen(false); // Close the sidebar after navigation
    };

    const HandleLogout = (e) => {
        fetch("/api/admin/logout", {
            method: "POST",
            headers,
        }).then((res) => {
            if (res.status === 200) {
                localStorage.removeItem("admin-token");
                localStorage.removeItem("admin-name");
                localStorage.removeItem("admin-id");
                navigate("/admin/login");
                alert.success("Logged Out Successfully");
            }
        });
    };

    const getAdmin = () => {
        axios.get(`/api/admin/details`, { headers }).then((res) => {
            setRole(res.data.success.role);
        });
    };

    useEffect(() => {
        getAdmin();
    }, []);

    return (
        <div className="mobileNav hidden-xl">
            <nav className="navbar navbar-expand-lg navbar-dark fixed-top">
                <a className="navbar-brand" href="/admin/dashboard">
                    <svg
                        width="190"
                        height="77"
                        xmlns="http://www.w3.org/2000/svg"
                        xmlnsXlink="http://www.w3.org/1999/xlink"
                    >
                        <image xlinkHref={logo} width="190" height="77"></image>
                    </svg>
                </a>
                <button
                    className="navbar-toggler"
                    type="button"
                    onClick={() => setIsSidebarOpen(!isSidebarOpen)} // Toggle sidebar visibility
                    aria-controls="navbarCollapse"
                    aria-expanded={isSidebarOpen} // Control aria-expanded attribute
                    aria-label="Toggle navigation"
                >
                    <span className="icon-bar">
                        <svg
                            width="30"
                            height="30"
                            xmlns="http://www.w3.org/2000/svg"
                            xmlnsXlink="http://www.w3.org/1999/xlink"
                        >
                            <image xlinkHref={bars} height="30" width="30" />
                        </svg>
                    </span>
                </button>
                <div className={`navbar-collapse ${isSidebarOpen ? 'show' : ''}`} id="navbarCollapse">
                    <ul className="navbar-nav mr-auto">
                        <li className="nav-item">
                            <a href="/admin/dashboard" onClick={(e) => handleClick(e, "/admin/dashboard")}>
                                <i className="fa-solid fa-gauge"></i>Dashboard
                            </a>
                        </li>
                        <li className="nav-item">
                            <a href="/admin/leads" onClick={(e) => handleClick(e, "/admin/leads")}>
                                <i className="fa-solid fa-poll-h"></i>Leads
                            </a>
                        </li>
                        {/* Client Dropdown */}
                        <li className="nav-item">
                            <div id="clientDropdown" className="fence commonDropdown">
                                <div id="clientHead">
                                <a
                                        href="#"
                                        className="text-left btn btn-header-link"
                                        data-toggle="collapse"
                                        data-target="#clientDropdownMenu"
                                        aria-expanded="true"
                                        aria-controls="clientDropdownMenu"
                                    >
                                        <i className="fa-solid fa-user-tie"></i> Clients{" "}
                                        <i className="fa-solid fa-angle-down"></i>
                                    </a>
                                </div>
                                <div
                                    id="clientDropdownMenu"
                                    className={`collapse `}
                                    aria-labelledby="clientHead"
                                    data-parent="#clientDropdown"
                                >
                                    <div className="card-body">
                                        <ul className="list-group">
                                            <li className="list-group-item">
                                                <a
                                                    href="/admin/clients"
                                                    onClick={(e) => handleClick(e, "/admin/clients")}
                                                >
                                                    <i className="fa fa-angle-right"></i> All Clients
                                                </a>
                                            </li>
                                            <li className="list-group-item">
                                                <a
                                                    href="/admin/clients?type=pending%20client"
                                                    onClick={(e) =>
                                                        handleClick(e, "/admin/clients?type=pending%20client")
                                                    }
                                                >
                                                    <i className="fa fa-angle-right"></i> Waiting
                                                </a>
                                            </li>
                                            <li className="list-group-item">
                                                <a
                                                    href="/admin/clients?type=active%20client"
                                                    onClick={(e) =>
                                                        handleClick(e, "/admin/clients?type=active%20client")
                                                    }
                                                >
                                                    <i className="fa fa-angle-right"></i> Active Clients
                                                </a>
                                            </li>
                                            <li className="list-group-item">
                                                <a
                                                    href="/admin/clients?type=freeze%20client"
                                                    onClick={(e) =>
                                                        handleClick(e, "/admin/clients?type=freeze%20client")
                                                    }
                                                >
                                                    <i className="fa fa-angle-right"></i> Freeze Clients
                                                </a>
                                            </li>
                                            <li className="list-group-item">
                                                <a
                                                    href="/admin/clients?type=past"
                                                    onClick={(e) => handleClick(e, "/admin/clients?type=past")}
                                                >
                                                    <i className="fa fa-angle-right"></i> Past Clients
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li className="nav-item">
                            <a href="/admin/workers" onClick={(e) => handleClick(e, "/admin/workers")}>
                                <i className="fa-solid fa-users"></i>Workers
                            </a>
                        </li>
                        <li className="nav-item">
                            <a href="/admin/schedule" onClick={(e) => handleClick(e, "/admin/schedule")}>
                                <i className="fa-solid fa-video"></i>Meetings
                            </a>
                        </li>
                        <li className="nav-item">
                            <a href="/admin/offered-price" onClick={(e) => handleClick(e, "/admin/offered-price")}>
                                <i className="fa-solid fa-tags"></i>Offers
                            </a>
                        </li>
                        <li className="nav-item">
                            <a href="/admin/contracts" onClick={(e) => handleClick(e, "/admin/contracts")}>
                                <i className="fa-solid fa-clipboard-list"></i>
                                Contracts
                            </a>
                        </li>
                        <li className="nav-item">
                            <a href="/admin/jobs" onClick={(e) => handleClick(e, "/admin/jobs")}>
                                <i className="fa-solid fa-briefcase"></i>
                                Schedule
                            </a>
                        </li>

                        <li className="nav-item">
                            <a href="/admin/chat" onClick={(e) => handleClick(e, "/admin/chat")}>
                                <i className="fa-solid fa-message"></i>
                                Whatsapp chat
                            </a>
                        </li>

                        {/* <li className="nav-item">
                            <div
                                id="myFencewh"
                                className="fence commonDropdown"
                            >
                                <div id="fencehead2wh">
                                    <a
                                        href="#"
                                        className="text-left btn btn-header-link"
                                        data-toggle="collapse"
                                        data-target="#fence212"
                                        aria-expanded="true"
                                        aria-controls="fence212"
                                    >
                                        <i className="fa-solid fa-message"></i>{" "}
                                        Whatsapp chat{" "}
                                        <i className="fa-solid fa-angle-down"></i>
                                    </a>
                                </div>
                                <div
                                    id="fence212"
                                    className="collapse"
                                    aria-labelledby="fencehead2wh"
                                    data-parent="#fence"
                                >
                                    <div className="card-body">
                                        <ul className="list-group">
                                            <li className="list-group-item">
                                                <a href="/admin/chat">
                                                    <i className="fa fa-angle-right"></i>{" "}
                                                    Chat History{" "}
                                                </a>
                                            </li>
                                            <li className="list-group-item">
                                                <a href="/admin/responses">
                                                    <i className="fa fa-angle-right"></i>{" "}
                                                    Whatsapp Responses{" "}
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </li> */}

                        {/* <li className="nav-item">
                            <div
                                id="myFencePay"
                                className="fence commonDropdown"
                            >
                                <div id="fenceheadpay">
                                    <a
                                        href="#"
                                        className="text-left btn btn-header-link"
                                        data-toggle="collapse"
                                        data-target="#fencepay"
                                        aria-expanded="true"
                                        aria-controls="fencepay"
                                    >
                                        <i className="fas fa-file-invoice"></i>{" "}
                                        Sales{" "}
                                        <i className="fa-solid fa-angle-down"></i>
                                    </a>
                                </div>
                                <div
                                    id="fencepay"
                                    className="collapse"
                                    aria-labelledby="fenceheadpay"
                                    data-parent="#fencepay"
                                >
                                    <div className="card-body">
                                        <ul className="list-group">
                                            <li className="list-group-item">
                                                <a href="/admin/orders">
                                                    <i className="fa fa-angle-right"></i>{" "}
                                                    Orders{" "}
                                                </a>
                                            </li>
                                            <li className="list-group-item">
                                                <a href="/admin/invoices">
                                                    <i className="fa fa-angle-right"></i>{" "}
                                                    Invoices{" "}
                                                </a>
                                            </li>
                                            <li className="list-group-item">
                                                <a href="/admin/payments">
                                                    <i className="fa fa-angle-right"></i>{" "}
                                                    Payments{" "}
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </li> */}
                        <li className="nav-item">
                            <a href="/admin/payments" onClick={(e) => handleClick(e, "/admin/payments")}>
                                <i className="fa-solid fa-cart-shopping"></i>
                                Payments
                            </a>
                        </li>

                        {role !== "member" && (
                            <li className="nav-item">
                                <a href="/admin/income" onClick={(e) => handleClick(e, "/admin/income")}>
                                    <i className="fa-solid fa-ils"></i>Earnings
                                </a>
                            </li>
                        )}
                        <li className="nav-item">
                            <a href="/admin/notifications" onClick={(e) => handleClick(e, "/admin/notifications")}>
                                <i className="fa-solid fa-bullhorn"></i>
                                Notifications
                            </a>
                        </li>
                    <li className="nav-item">
                        <div id="fence" className="fence commonDropdown">
                            <div id="fencehead1">
                                <a
                                    href="#"
                                    className="text-left btn btn-header-link"
                                    data-toggle="collapse"
                                    data-target="#fence1"
                                    aria-expanded="true"
                                    aria-controls="fence1"
                                >
                                    <i className="fa-solid fa-gear"></i>{" "}
                                    Settings{" "}
                                    <i className="fa-solid fa-angle-down"></i>
                                </a>
                            </div>
                            <div
                                id="fence1"
                                className="collapse"
                                aria-labelledby="fencehead1"
                                data-parent="#fence"
                            >
                                <div className="card-body">
                                    <ul className="list-group">
                                        {role !== "member" && (
                                            <li className="list-group-item">
                                                <a href="/admin/manage-team">
                                                    <i className="fa fa-angle-right"></i>{" "}
                                                    Manage team
                                                </a>
                                            </li>
                                        )}
                                        <li className="list-group-item">
                                            <a href="/admin/services">
                                                <i className="fa fa-angle-right"></i>{" "}
                                                Services
                                            </a>
                                        </li>
                                        <li className="list-group-item">
                                            <a href="/admin/manage-time">
                                                <i className="fa fa-angle-right"></i>{" "}
                                                Manage Time
                                            </a>
                                        </li>
                                        <li className="list-group-item">
                                            <a href="/admin/settings">
                                                <i className="fa fa-angle-right"></i>{" "}
                                                My Account
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
                <div className="sideLogout">
                    <div className="logoutBtn">
                        <button
                            className="btn btn-danger"
                            onClick={HandleLogout}
                        >
                            Logout
                        </button>
                    </div>
                </div>
        </div>
            </nav >
        </div >
    );
}
