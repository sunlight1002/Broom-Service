import React, { useState, useEffect } from "react";
import logo from "../../Assets/image/sample.svg";
import bars from "../../Assets/image/icons/bars.svg";
import { useAlert } from "react-alert";
import { useNavigate } from "react-router-dom";
import axios from "axios";
import "./mobile.css"
import { useTranslation } from "react-i18next";
import { LuShuffle } from "react-icons/lu";


export default function MobileHeader() {
    const { t } = useTranslation();
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
                                <i className="fa-solid fa-gauge"></i>{t("admin.sidebar.dashboard")}
                            </a>
                        </li>
                        {
                            role !== "hr" && (
                                <>
                                    <li className="nav-item">
                                        <a href="/admin/leads" onClick={(e) => handleClick(e, "/admin/leads")}>
                                            <i className="fa-solid fa-poll-h"></i>{t("admin.sidebar.leads")}
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
                                                    <i className="fa-solid fa-user-tie"></i> {t("admin.sidebar.clients")}{" "}
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
                                                                <i className="fa fa-angle-right"></i> {t("admin.sidebar.client.waiting")}
                                                            </a>
                                                        </li>
                                                        <li className="list-group-item">
                                                            <a
                                                                href="/admin/clients?type=active%20client"
                                                                onClick={(e) =>
                                                                    handleClick(e, "/admin/clients?type=active%20client")
                                                                }
                                                            >
                                                                <i className="fa fa-angle-right"></i> {t("admin.sidebar.client.active_client")}
                                                            </a>
                                                        </li>
                                                        <li className="list-group-item">
                                                            <a
                                                                href="/admin/clients?type=freeze%20client"
                                                                onClick={(e) =>
                                                                    handleClick(e, "/admin/clients?type=freeze%20client")
                                                                }
                                                            >
                                                                <i className="fa fa-angle-right"></i> {t("admin.sidebar.client.freeze_client")}
                                                            </a>
                                                        </li>
                                                        <li className="list-group-item">
                                                            <a
                                                                href="/admin/clients?type=past"
                                                                onClick={(e) => handleClick(e, "/admin/clients?type=past")}
                                                            >
                                                                <i className="fa fa-angle-right"></i> {t("admin.sidebar.client.past_client")}
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                </>
                            )
                        }
                        <li className="nav-item">
                            <a href="/admin/workers" onClick={(e) => handleClick(e, "/admin/workers")}>
                                <i className="fa-solid fa-users"></i>{t("admin.sidebar.workers")}
                            </a>
                        </li>
                        <li className="nav-item">
                            <a href="/admin/workers-leaves" onClick={(e) => handleClick(e, "/admin/workers-leaves")}>
                                <i className="fa-solid fa-calendar-minus"></i>{t("admin.sidebar.workerLeave")}
                            </a>
                        </li>
                        <li className="nav-item">
                            <a href="/admin/worker-leads" onClick={(e) => handleClick(e, "/admin/worker-leads")}>
                                <i className="fa-solid fa-users"></i>{t("admin.sidebar.worker_lead")}
                            </a>
                        </li>
                        <li className="nav-item">
                            <a href="/admin/workers-refund" onClick={(e) => handleClick(e, "/admin/workers-refund")}>
                                <i className="fa-solid fa-undo-alt"></i>{t("worker.worker_refund")}
                            </a>
                        </li>
                        <li className="nav-item">
                            <a href="/admin/task" onClick={(e) => handleClick(e, "/admin/task")}>
                                <i className="fa-solid fa-list-check"></i>{t("admin.sidebar.task_management")}
                            </a>
                        </li>

                        {
                            role !== "hr" && (
                                <>
                                    <li className="nav-item">
                                        <a href="/admin/schedule" onClick={(e) => handleClick(e, "/admin/schedule")}>
                                            <i className="fa-solid fa-video"></i>{t("admin.sidebar.meetings")}
                                        </a>
                                    </li>
                                    <li className="nav-item">
                                        <a href="/admin/offered-price" onClick={(e) => handleClick(e, "/admin/offered-price")}>
                                            <i className="fa-solid fa-tags"></i>{t("admin.sidebar.offers")}
                                        </a>
                                    </li>
                                    <li className="nav-item">
                                        <a href="/admin/contracts" onClick={(e) => handleClick(e, "/admin/contracts")}>
                                            <i className="fa-solid fa-clipboard-list"></i>
                                            {t("admin.sidebar.contracts")}
                                        </a>
                                    </li>
                                    <li className="nav-item">
                                        <a href="/admin/jobs" onClick={(e) => handleClick(e, "/admin/jobs")}>
                                            <i className="fa-solid fa-briefcase"></i>
                                            {t("admin.sidebar.schedule_meet")}
                                        </a>
                                    </li>
                                    <li className="nav-item">
                                        <a href="/admin/conflicts" onClick={(e) => handleClick(e, "/admin/conflicts")}>
                                            <i class="fa-solid fa-shuffle"></i>
                                            {t("admin.sidebar.conflicts")}
                                        </a>
                                    </li>
                                    <li className="nav-item">
                                        <a href="/admin/schedule-requests" onClick={(e) => handleClick(e, "/admin/schedule-requests")}>
                                            <i className="fa-solid fa-hand"></i>
                                            {t("admin.sidebar.pending_request")}
                                        </a>
                                    </li>

                                    <li className="nav-item">
                                        <a href="/admin/facebook-insights" onClick={(e) => handleClick(e, "/admin/facebook-insights")}>
                                            <i className="fa-brands fa-facebook"></i>
                                            {t("admin.sidebar.fb_insights")}
                                        </a>
                                    </li>
                                    <li className="nav-item">
                                        <a href="/admin/custom-message" onClick={(e) => handleClick(e, "/admin/custom-message")}>
                                            <i className="fa-solid fa-paper-plane"></i>
                                            {t("admin.sidebar.custom_message")}
                                        </a>
                                    </li>
                                    <li className="nav-item">
                                        <a href="/admin/chat" onClick={(e) => handleClick(e, "/admin/chat")}>
                                            <i className="fa-solid fa-message"></i>
                                            {t("admin.sidebar.whatsapp")}
                                        </a>
                                    </li>
                                    <li className="nav-item">
                                        <a href="/admin/messenger" onClick={(e) => handleClick(e, "/admin/messenger")}>
                                            <i className="fa-solid fa-comment"></i>
                                            {t("admin.sidebar.messenger")}
                                        </a>
                                    </li>

                                    <li className="nav-item">
                                        <a href="/admin/payments" onClick={(e) => handleClick(e, "/admin/payments")}>
                                            <i className="fa-solid fa-cart-shopping"></i>
                                            {t("admin.sidebar.payments")}
                                        </a>
                                    </li>
                                </>
                            )
                        }

                        {role !== "member" && role !== "hr" && (
                            <li className="nav-item">
                                <a href="/admin/income" onClick={(e) => handleClick(e, "/admin/income")}>
                                    <i className="fa-solid fa-ils"></i>{t("admin.sidebar.earnings")}
                                </a>
                            </li>
                        )}
                        {
                            role !== "hr" && (
                                <li className="nav-item">
                                    <a href="/admin/notifications" onClick={(e) => handleClick(e, "/admin/notifications")}>
                                        <i className="fa-solid fa-bullhorn"></i>
                                        {t("admin.sidebar.notification")}
                                    </a>
                                </li>
                            )
                        }
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
                                        {t("admin.sidebar.settings.title")}{" "}
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
                                            {role !== "member" && role !== "hr" && (
                                                <li className="list-group-item">
                                                    <a href="/admin/manage-team">
                                                        <i className="fa fa-angle-right"></i>{" "}
                                                        {t("admin.sidebar.settings.manage_team")}
                                                    </a>
                                                </li>
                                            )}
                                            {
                                                role !== "hr" && (
                                                    <>
                                                        <li className="list-group-item">
                                                            <a href="/admin/services">
                                                                <i className="fa fa-angle-right"></i>{" "}
                                                                {t("admin.sidebar.settings.services")}
                                                            </a>
                                                        </li>
                                                        <li className="list-group-item">
                                                            <a href="/admin/manpower-companies">
                                                                <i className="fa fa-angle-right"></i>{" "}
                                                                {t("admin.sidebar.settings.manpower")}
                                                            </a>
                                                        </li>
                                                        <li className="list-group-item">
                                                            <a href="/admin/insurance-companies">
                                                                <i className="fa fa-angle-right"></i>{" "}
                                                                {t("global.insurance_companies")}
                                                            </a>
                                                        </li>
                                                        <li className="list-group-item">
                                                            <a href="/admin/manage-time">
                                                                <i className="fa fa-angle-right"></i>{" "}
                                                                {t("admin.sidebar.settings.manageTime")}
                                                            </a>
                                                        </li>
                                                        <li className="list-group-item">
                                                            <a href="/admin/holidays">
                                                                <i className="fa fa-angle-right"></i>{" "}
                                                                {t("admin.sidebar.settings.holidays")}
                                                            </a>
                                                        </li>
                                                        <li className="list-group-item">
                                                            <a href="/admin/discount">
                                                                <i className="fa fa-angle-right"></i>{" "}
                                                                {t("admin.sidebar.settings.add_discount")}                                                </a>
                                                        </li>
                                                        <li className="list-group-item">
                                                            <a href="/admin/templates">
                                                                <i className="fa fa-angle-right"></i>{" "}
                                                                {t("admin.sidebar.templates.title")}
                                                            </a>
                                                        </li>
                                                        <li className="list-group-item">
                                                            <a href="/admin/payslip-settings">
                                                                <i className="fa fa-angle-right"></i>{" "}
                                                                {t("admin.sidebar.settings.payslip_settings")}
                                                            </a>
                                                        </li>
                                                    </>
                                                )
                                            }
                                            <li className="list-group-item">
                                                <a href="/admin/settings">
                                                    <i className="fa fa-angle-right"></i>{" "}
                                                    {t("admin.sidebar.settings.account")}
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
                                {t("admin.sidebar.logout")}
                            </button>
                        </div>
                    </div>
                </div>
            </nav >
        </div >
    );
}
