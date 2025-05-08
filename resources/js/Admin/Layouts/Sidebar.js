import React, { useEffect, useState } from "react";
import { Link, useLocation } from "react-router-dom";
import { useAlert } from "react-alert";
import { useNavigate } from "react-router-dom";
import axios from "axios";
import i18next from "i18next";
import { useTranslation } from "react-i18next";
import { IoIosLogOut } from "react-icons/io";
import { HiOutlineSquares2X2 } from "react-icons/hi2";
import { CgInsights } from "react-icons/cg";
import { LuShuffle } from "react-icons/lu";

import logo from "../../Assets/image/sample.svg";
import { NavLink } from "react-router-dom";
import { GiReceiveMoney } from "react-icons/gi";

export default function Sidebar() {
    const location = useLocation();
    const alert = useAlert();
    const navigate = useNavigate();
    const [role, setRole] = useState(null);
    const { t } = useTranslation();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };
    const adminLng = localStorage.getItem("admin-lng");
    const [isDropdownOpen, setDropdownOpen] = useState(false); // Manage dropdown open state
    const [isClientDropdownOpen, setClientDropdownOpen] = useState(false); // Clients dropdown
    const [chatDropdown, setChatDropdown] = useState(false);

    // Toggle the dropdown
    const toggleDropdown = () => {
        setDropdownOpen(!isDropdownOpen);
    };

    const toggleClientDropdown = () => {
        navigate("/admin/clients");
        setClientDropdownOpen(!isClientDropdownOpen);
    };

    const toggleChatDropdown = () => {
        setChatDropdown(!chatDropdown);
    };

    const fullUrl = location.pathname + location.search;
    // Check if the current path matches any of the routes in the dropdown
    const isDropdownActive = ["/admin/manage-team", "/admin/services", "/admin/manpower-companies", "/admin/manage-time", "/admin/settings", "/admin/holidays", "/admin/templates"].includes(location.pathname);
    const isChatDropdownActive = [`/admin/chat/${process.env.MIX_TWILIO_WHATSAPP_NUMBER}`, `/admin/chat/${process.env.MIX_WORKER_LEAD_TWILIO_WHATSAPP_NUMBER}`].includes(location.pathname);
    const isClientDropdownActive = ["/admin/clients", "/admin/clients?type=pending%20client","/admin/clients?type=active%20client", "/admin/clients?type=freeze%20client", "/admin/clients?type=past"].includes(fullUrl);

    const getAdmin = () => {
        axios.get(`/api/admin/details`, { headers }).then((res) => {
            setRole(res.data.success.role);
        });
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
                localStorage.removeItem("admin-email");
                localStorage.removeItem("admin-lng");
                navigate("/admin/login");
                alert.success("Logged Out Successfully");
            }
        });
    };

    const routes = {
        manageTeam: "/admin/manage-team",
        services: "/admin/services",
        manpowerCompanies: "/admin/manpower-companies",
        insuranceCompanies: "/admin/insurance-companies",
        manageTime: "/admin/manage-time",
        accountSettings: "/admin/settings",
        holidays: "/admin/holidays",
        discount: "/admin/discount",
        waTemplates: "/admin/templates",
        payslipSettings: "/admin/payslip-settings",
        client_worker_chat: `/admin/chat/${process.env.MIX_TWILIO_WHATSAPP_NUMBER}`,
        worker_lead_chat: `/admin/chat/${process.env.MIX_WORKER_LEAD_TWILIO_WHATSAPP_NUMBER}`,
        // Client routes
        clients: "/admin/clients",
        pendingClient: "/admin/clients?type=pending%20client",
        activeClient: "/admin/clients?type=active%20client",
        freezeClient: "/admin/clients?type=freeze%20client",
        pastClient: "/admin/clients?type=past",
    };

    const isActive = (path) => location.pathname === path;
    const isActiveClient = (path) => fullUrl === path;

    // Check if any of the children are active to apply the active class to the parent
    const isParentActive = () => {
        return Object.values(routes).some((route) => isActive(route));
    };

    // console.log(lng);

    useEffect(() => {
        // i18next.changeLanguage(adminLng);
        getAdmin();
    }, []);


    return (
        <div id="column-left">
            <div className="sideLogo">
                <Link to="/admin/dashboard">
                    <svg
                        width="190"
                        height="77"
                        xmlns="http://www.w3.org/2000/svg"
                        xmlnsXlink="http://www.w3.org/1999/xlink"
                    >
                        <image xlinkHref={logo} width="190" height="77"></image>
                    </svg>
                </Link>
            </div>
            <ul className="list-group">

                <li className="list-group-item">
                    <NavLink to="/admin/dashboard"
                        className="d-flex align-items-center"
                    >
                        <i className="d-flex align-items-center">
                            <HiOutlineSquares2X2 className="font-28" />
                        </i>{t("admin.sidebar.dashboard")}
                    </NavLink>
                </li>
                {
                    role !== "hr" && (
                        <>
                            <li className="list-group-item">
                                <NavLink to="/admin/leads"
                                    className="d-flex align-items-center"
                                >
                                    <i className="fa-solid fa-poll-h font-28"></i>{t("admin.sidebar.leads")}
                                </NavLink>
                            </li>
                            <li className={`list-group-item ${isClientDropdownActive ? "active" : ""}`}>
                                <div className="fence commonDropdown">
                                    <div>
                                        <a
                                            href="/admin/clients"
                                            className={`text-left ${isClientDropdownActive ? "active text-white" : ""}`}
                                            data-toggle="collapse"
                                            onClick={toggleClientDropdown}
                                            aria-expanded={isClientDropdownOpen}
                                            aria-controls="clientDropdown"
                                            data-target="#clientDropdown"
                                        >
                                            <i className={`fa-solid fa-user-tie font-20 ${isClientDropdownOpen ? "text-white" : ""}`}></i> {t("admin.sidebar.clients")}{" "}
                                            <i className={`fa-solid fa-angle-down ${isClientDropdownOpen ? "text-white rotate-180" : ""}`}
                                                style={{ rotate: isClientDropdownOpen ? "180deg" : "" }}></i>
                                        </a>
                                    </div>
                                    <div
                                        id="clientDropdown"
                                        className={`collapse ${isClientDropdownOpen ? "show" : ""}`}
                                        aria-labelledby="clientDropdown"
                                        data-parent="#clientDropdown"
                                    >
                                        <div className="card-body">
                                            <ul className="list-group">
                                                <li className={`list-group-item ${isActiveClient(routes.pendingClient) ? "active" : ""}`}>
                                                    <Link to={routes.pendingClient} onClick={(e) => e.stopPropagation()} style={isActiveClient(routes.pendingClient) ? { color: "white" } : { color: "#757589" }}>
                                                        <i className="fa fa-angle-right"></i> {t("admin.sidebar.client.waiting")}
                                                    </Link>
                                                </li>
                                                <li className={`list-group-item ${isActiveClient(routes.activeClient) ? "active" : ""}`}>
                                                    <Link to={routes.activeClient} onClick={(e) => e.stopPropagation()} style={isActiveClient(routes.activeClient) ? { color: "white" } : { color: "#757589" }}>
                                                        <i className="fa fa-angle-right"></i> {t("admin.sidebar.client.active_client")}
                                                    </Link>
                                                </li>
                                                <li className={`list-group-item ${isActiveClient(routes.freezeClient) ? "active" : ""}`}>
                                                    <Link to={routes.freezeClient} onClick={(e) => e.stopPropagation()} style={isActiveClient(routes.freezeClient) ? { color: "white" } : { color: "#757589" }}>
                                                        <i className="fa fa-angle-right"></i> {t("admin.sidebar.client.freeze_client")}
                                                    </Link>
                                                </li>
                                                <li className={`list-group-item ${isActiveClient(routes.pastClient) ? "active" : ""}`}>
                                                    <Link to={routes.pastClient} onClick={(e) => e.stopPropagation()} style={isActiveClient(routes.pastClient) ? { color: "white" } : { color: "#757589" }}>
                                                        <i className="fa fa-angle-right"></i> {t("admin.sidebar.client.past_client")}
                                                    </Link>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </>
                    )
                }

                <li className="list-group-item">
                    <NavLink to="/admin/workers"
                        className="d-flex align-items-center"
                    >
                        <i className="fa-solid fa-users font-20"></i>{t("admin.sidebar.workers")}
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink to="/admin/workers-leaves"
                        className="d-flex align-items-center"
                    >
                        <i className="fa-solid fa-calendar-minus font-20"></i>{t("admin.sidebar.workerLeave")}
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink to="/admin/task"
                        className="d-flex align-items-center"
                    >
                        <i className="fa-solid fa-list-check"></i>{t("admin.sidebar.task_management")}
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink to="/admin/worker-leads"
                        className="d-flex align-items-center"
                    >
                        <i className="fa-solid fa-users font-20"></i>{t("admin.sidebar.worker_lead")}
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink to="/admin/workers-refund"
                        className="d-flex align-items-center"
                    >
                        <i className="fa-solid fa-undo-alt font-20"></i>{t("worker.worker_refund")}
                    </NavLink>
                </li>
                {/* <li className="list-group-item">
                    <NavLink to="/admin/workers-hearing"
                        className="d-flex align-items-center"
                    >
                        <i className="fa-solid fa-video font-20"></i>{t("admin.sidebar.workerHearing")}
                    </NavLink>
                </li> */}
                {
                    role !== "hr" && (
                        <>
                            <li className="list-group-item">
                                <NavLink to="/admin/schedule"
                                    className="d-flex align-items-center"
                                >
                                    <i className="fa-solid fa-video font-20"></i>{t("admin.sidebar.meetings")}
                                </NavLink>
                            </li>
                            <li className="list-group-item">
                                <NavLink to="/admin/offered-price"
                                    className="d-flex align-items-center"
                                >
                                    <i className="fa-solid fa-tags font-20"></i>{t("admin.sidebar.offers")}
                                </NavLink>
                            </li>
                            <li className="list-group-item">
                                <NavLink to="/admin/contracts"
                                    className="d-flex align-items-center"
                                >
                                    <i className="fa-solid fa-clipboard-list font-20"></i>{t("admin.sidebar.contracts")}
                                </NavLink>
                            </li>
                            <li className="list-group-item">
                                <NavLink to="/admin/jobs"
                                    className="d-flex align-items-center"
                                >
                                    <i className="fa-solid fa-briefcase font-20"></i>{t("admin.sidebar.schedule_meet")}
                                </NavLink>
                            </li>
                            <li className="list-group-item">
                                <NavLink to="/admin/conflicts"
                                    className="d-flex align-items-center"
                                >
                                    <LuShuffle className="font-20 mr-2" />{t("admin.sidebar.conflicts")}
                                </NavLink>
                            </li>
                            <li className="list-group-item">
                                <NavLink to="/admin/schedule-requests"
                                    className="d-flex align-items-center"
                                >
                                    <i className="fa-solid fa-hand font-20"></i>{t("admin.sidebar.pending_request")}
                                </NavLink>
                            </li>
                            <li className="list-group-item">
                                <NavLink to="/admin/expanses"
                                    className="d-flex align-items-center"
                                >
                                    <GiReceiveMoney className="font-20 mr-2" /> {t("global.expenses")}
                                </NavLink>
                            </li>
                            <li className="list-group-item">
                                <NavLink to="/admin/facebook-insights"
                                    className="d-flex align-items-center"
                                >
                                    <i className="fa-brands fa-facebook font-20 mr-0"></i><CgInsights className="font-20 mr-2" />{t("admin.sidebar.fb_insights")}
                                </NavLink>
                            </li>
                            {/* <li className="list-group-item">
                                <NavLink to="/admin/chat"
                                    className="d-flex align-items-center"
                                >
                                    <i className="fa-solid fa-message font-20"></i>{t("admin.sidebar.whatsapp")}
                                </NavLink>
                            </li> */}

                            <li className={`list-group-item ${isChatDropdownActive ? "active" : ""}`}>
                                <div className="fence commonDropdown">
                                    <div >
                                        <a
                                            href="#"
                                            className={`text-left ${isChatDropdownActive ? "active" : ""} `}
                                            data-toggle="collapse"
                                            onClick={toggleChatDropdown}
                                            aria-expanded={chatDropdown}
                                            data-target="#chat"
                                            aria-controls="chat"
                                        >
                                            <i className="fa-solid fa-message font-20"></i> {t("admin.sidebar.whatsapp")}{" "}
                                            <i className={`fa-solid fa-angle-down ${chatDropdown ? "text-white rotate-180" : ""}`}
                                                style={{
                                                    rotate: chatDropdown ? "180deg" : ""
                                                }}
                                            ></i>
                                        </a>
                                    </div>
                                    <div
                                        id="chat"
                                        className={`collapse ${isParentActive() ? "show" : ""}`}
                                        aria-labelledby="chat"
                                         data-parent="#chat"
                                    >
                                        <div className="card-body">
                                            <ul className="list-group">
                                                <li className={`list-group-item ${isActive(routes.client_worker_chat) ? "active" : ""}`}>
                                                    <Link to={routes.client_worker_chat} style={isActive(routes.client_worker_chat) ? { color: "white" } : { color: "#757589" }}>
                                                        <i className="fa fa-angle-right"></i>{" "}
                                                        Clients/Workers Chat
                                                    </Link>
                                                </li>
                                                <li className={`list-group-item ${isActive(routes.worker_lead_chat) ? "active" : ""}`}>
                                                    <Link to={routes.worker_lead_chat} style={isActive(routes.worker_lead_chat) ? { color: "white" } : { color: "#757589" }}>
                                                        <i className="fa fa-angle-right"></i>{" "}
                                                        Worker Leads Chat
                                                    </Link>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </li>



                            <li className="list-group-item">
                                <NavLink to="/admin/messenger">
                                    <i className="fa-solid fa-comment font-20"></i>{t("admin.sidebar.messenger")}
                                </NavLink>
                            </li>

                            <li className="list-group-item">
                                <NavLink to="/admin/custom-message">
                                    <i className="fa-solid fa-paper-plane font-20"></i>{t("admin.sidebar.custom_message")}
                                </NavLink>
                            </li>

                            <li className="list-group-item">
                                <NavLink to="/admin/payments">
                                    <i className="fa-solid fa-cart-shopping font-20"></i>{t("admin.sidebar.payments")}
                                </NavLink>
                            </li>
                        </>
                    )
                }

                {role !== "member" && role !== "hr" && (
                    <li className="list-group-item">
                        <NavLink to="/admin/income">
                            <i className="fa-solid fa-ils font-20"></i>{t("admin.sidebar.earnings")}
                        </NavLink>
                    </li>
                )}
                {
                    role !== "hr" && (
                        <li className="list-group-item">
                            <NavLink to="/admin/notifications">
                                <i className="fa-solid fa-bullhorn font-20"></i>{t("admin.sidebar.notification")}
                            </NavLink>
                        </li>
                    )
                }

                <li className={`list-group-item ${isDropdownActive ? "active" : ""}`}>
                    <div id="myFence" className="fence commonDropdown">
                        <div id="fencehead2">
                            <a
                                href="#"
                                className={`text-left ${isDropdownActive ? "active" : ""} `}
                                data-toggle="collapse"
                                onClick={toggleDropdown}
                                aria-expanded={isDropdownOpen}
                                data-target="#fence2"
                                aria-controls="fence2"
                            >
                                <i className="fa-solid fa-gear font-20"></i> {t("admin.sidebar.settings.title")}{" "}
                                <i className={`fa-solid fa-angle-down ${isDropdownOpen ? "text-white rotate-180" : ""}`}
                                    style={{
                                        rotate: isDropdownOpen ? "180deg" : ""
                                    }}
                                ></i>
                            </a>
                        </div>
                        <div
                            id="fence2"
                            className={`collapse ${isParentActive() ? "show" : ""}`}
                            aria-labelledby="fencehead2"
                            data-parent="#fence"
                        >
                            <div className="card-body">
                                <ul className="list-group">
                                    {role !== "member" && role !== "hr" && (
                                        <li className={`list-group-item ${isActive(routes.manageTeam) ? "active" : ""}`}>
                                            <Link to={routes.manageTeam} style={isActive(routes.manageTeam) ? { color: "white" } : { color: "#757589" }}>
                                                <i className="fa fa-angle-right"></i>{" "}
                                                {t("admin.sidebar.settings.manage_team")}
                                            </Link>
                                        </li>
                                    )}
                                    {
                                        role !== "hr" && (
                                            <>
                                                <li className={`list-group-item ${isActive(routes.services) ? "active" : ""}`}>
                                                    <Link to={routes.services} style={isActive(routes.services) ? { color: "white" } : { color: "#757589" }}>
                                                        <i className="fa fa-angle-right"></i>{" "}
                                                        {t("admin.sidebar.settings.services")}
                                                    </Link>
                                                </li>
                                                <li className={`list-group-item ${isActive(routes.manpowerCompanies) ? "active" : ""}`}>
                                                    <Link to={routes.manpowerCompanies} style={isActive(routes.manpowerCompanies) ? { color: "white" } : { color: "#757589" }}>
                                                        <i className="fa fa-angle-right"></i>{" "}
                                                        {t("admin.sidebar.settings.manpower")}
                                                    </Link>
                                                </li>
                                                <li className={`list-group-item ${isActive(routes.insuranceCompanies) ? "active" : ""}`}>
                                                    <Link to={routes.insuranceCompanies} style={isActive(routes.insuranceCompanies) ? { color: "white" } : { color: "#757589" }}>
                                                        <i className="fa fa-angle-right"></i>{" "}
                                                        {t("global.insurance_companies")}
                                                    </Link>
                                                </li>
                                                <li className={`list-group-item ${isActive(routes.manageTime) ? "active" : ""}`}>
                                                    <Link to={routes.manageTime} style={isActive(routes.manageTime) ? { color: "white" } : { color: "#757589" }}>
                                                        <i className="fa fa-angle-right"></i>{" "}
                                                        {t("admin.sidebar.settings.manageTime")}
                                                    </Link>
                                                </li>
                                                <li className={`list-group-item ${isActive(routes.holidays) ? "active" : ""}`}>
                                                    <Link to={routes.holidays} style={isActive(routes.holidays) ? { color: "white" } : { color: "#757589" }}>
                                                        <i className="fa fa-angle-right"></i>{" "}
                                                        {t("admin.sidebar.settings.holidays")}
                                                    </Link>
                                                </li>
                                                <li className={`list-group-item ${isActive(routes.discount) ? "active" : ""}`}>
                                                    <Link to={routes.discount} style={isActive(routes.discount) ? { color: "white" } : { color: "#757589" }}>
                                                        <i className="fa fa-angle-right"></i>{" "}
                                                        {t("admin.sidebar.settings.add_discount")}
                                                    </Link>
                                                </li>
                                                <li className={`list-group-item ${isActive(routes.waTemplates) ? "active text-white" : ""}`}>
                                                    <Link to={routes.waTemplates} style={isActive(routes.waTemplates) ? { color: "white" } : { color: "#757589" }}>
                                                        <i className="fa fa-angle-right"></i>{" "}
                                                        {t("admin.sidebar.templates.title")}
                                                    </Link>
                                                </li>
                                                <li className={`list-group-item ${isActive(routes.payslipSettings) ? "active" : ""}`}>
                                                    <Link to={routes.payslipSettings} style={isActive(routes.payslipSettings) ? { color: "white" } : { color: "#757589" }}>
                                                        <i className="fa fa-angle-right"></i>{" "}
                                                        {t("admin.sidebar.settings.payslip_settings")}
                                                    </Link>
                                                </li>
                                            </>
                                        )
                                    }
                                    <li className={`list-group-item ${isActive(routes.accountSettings) ? "active" : ""}`}>
                                        <Link to={routes.accountSettings} style={isActive(routes.accountSettings) ? { color: "white" } : { color: "#757589" }}>
                                            <i className="fa fa-angle-right"></i>{" "}
                                            {t("admin.sidebar.settings.account")}
                                        </Link>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
            <div className="sideLogout">
                <div className="logoutBtn mb-3">
                    <button className="btn d-flex justify-content-center align-items-center" onClick={HandleLogout}>
                        <IoIosLogOut className="mr-1 font-28" />
                        {t("admin.sidebar.logout")}
                    </button>
                </div>
            </div>
        </div>
    );
}
