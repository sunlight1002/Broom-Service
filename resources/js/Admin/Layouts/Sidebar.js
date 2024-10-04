import React, { useEffect, useState } from "react";
import { Link, useLocation } from "react-router-dom";
import { useAlert } from "react-alert";
import { useNavigate } from "react-router-dom";
import axios from "axios";
import i18next from "i18next";
import { useTranslation } from "react-i18next";
import { IoIosLogOut } from "react-icons/io";
import { HiOutlineSquares2X2 } from "react-icons/hi2";

import logo from "../../Assets/image/sample.svg";
import { NavLink } from "react-router-dom";

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

    // Toggle the dropdown
    const toggleDropdown = () => {
        setDropdownOpen(!isDropdownOpen);
    };

    const [isDropdownOpen, setDropdownOpen] = useState(false); // Manage dropdown open state

    // Check if the current path matches any of the routes in the dropdown
    const isDropdownActive = ["/admin/manage-team", "/admin/services", "/admin/manpower-companies", "/admin/manage-time", "/admin/settings","/admin/holidays","/admin/templates"].includes(location.pathname);

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
        manageTime: "/admin/manage-time",
        accountSettings: "/admin/settings",
        holidays:"/admin/holidays",
        waTemplates: "/admin/templates",
    };

    const isActive = (path) => location.pathname === path;

    // Check if any of the children are active to apply the active class to the parent
    const isParentActive = () => {
        return Object.values(routes).some((route) => isActive(route));
    };

    // console.log(lng);

    useEffect(() => {
        i18next.changeLanguage(adminLng);
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
                <li className="list-group-item">
                    <NavLink to="/admin/leads"
                        className="d-flex align-items-center"
                    >
                        <i className="fa-solid fa-poll-h font-28"></i>{t("admin.sidebar.leads")}
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink to="/admin/clients"
                        className="d-flex align-items-center"
                    >
                        <i className="fa-solid fa-user-tie font-20"></i>{t("admin.sidebar.clients")}
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink to="/admin/workers"
                        className="d-flex align-items-center"
                    >
                        <i className="fa-solid fa-users font-20"></i>{t("admin.sidebar.workers")}
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink to="/admin/worker-leads"
                        className="d-flex align-items-center"
                    >
                        <i className="fa-solid fa-users font-20"></i>Worker Lead
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink to="/admin/workers-leaves"
                        className="d-flex align-items-center"
                    >
                        <i className="fa-solid fa-calendar-minus font-20"></i>{t("worker.worker_leave")}
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink to="/admin/workers-refund"
                        className="d-flex align-items-center"
                    >
                        <i className="fa-solid fa-undo-alt font-20"></i>{t("worker.worker_refund")}
                    </NavLink>
                </li>
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
                    <NavLink to="/admin/chat"
                        className="d-flex align-items-center"
                    >
                        <i className="fa-solid fa-message font-20"></i>{t("admin.sidebar.whatsapp")}
                    </NavLink>
                </li>

                <li className="list-group-item">
                    <NavLink to="/admin/messenger">
                        <i className="fa-solid fa-comment font-20"></i>{t("admin.sidebar.messenger")}
                    </NavLink>
                </li>

                <li className="list-group-item">
                    <NavLink to="/admin/payments">
                        <i className="fa-solid fa-cart-shopping font-20"></i>{t("admin.sidebar.payments")}
                    </NavLink>
                </li>

                {role !== "member" && (
                    <li className="list-group-item">
                        <NavLink to="/admin/income">
                            <i className="fa-solid fa-ils font-20"></i>{t("admin.sidebar.earnings")}
                        </NavLink>
                    </li>
                )}
                <li className="list-group-item">
                    <NavLink to="/admin/notifications">
                        <i className="fa-solid fa-bullhorn font-20"></i>{t("admin.sidebar.notification")}
                    </NavLink>
                </li>

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
                                <i className={`fa-solid fa-angle-down ${isDropdownOpen ? "rotate-180" : ""}`}></i>
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
                                    {role !== "member" && (
                                        <li className={`list-group-item ${isActive(routes.manageTeam) ? "active" : ""}`}>
                                            <Link to={routes.manageTeam} style={isActive(routes.manageTeam)?{color: "white"}:{color: "#757589"}}>
                                                <i className="fa fa-angle-right"></i>{" "}
                                                {t("admin.sidebar.settings.manage_team")}
                                            </Link>
                                        </li>
                                    )}
                                    <li className={`list-group-item ${isActive(routes.services) ? "active" : ""}`}>
                                        <Link to={routes.services} style={isActive(routes.services)?{color: "white"}:{color: "#757589"}}>
                                            <i className="fa fa-angle-right"></i>{" "}
                                            {t("admin.sidebar.settings.services")}
                                        </Link>
                                    </li>
                                    <li className={`list-group-item ${isActive(routes.manpowerCompanies) ? "active" : ""}`}>
                                        <Link to={routes.manpowerCompanies} style={isActive(routes.manpowerCompanies)?{color: "white"}:{color: "#757589"}}>
                                            <i className="fa fa-angle-right"></i>{" "}
                                            {t("admin.sidebar.settings.manpower")}
                                        </Link>
                                    </li>
                                    <li className={`list-group-item ${isActive(routes.manageTime) ? "active" : ""}`}>
                                        <Link to={routes.manageTime} style={isActive(routes.manageTime)?{color: "white"}:{color: "#757589"}}>
                                            <i className="fa fa-angle-right"></i>{" "}
                                            {t("admin.sidebar.settings.manageTime")}
                                        </Link>
                                    </li>
                                    <li className={`list-group-item ${isActive(routes.accountSettings) ? "active" : ""}`}>
                                        <Link to={routes.accountSettings} style={isActive(routes.accountSettings)?{color: "white"}:{color: "#757589"}}>
                                            <i className="fa fa-angle-right"></i>{" "}
                                            {t("admin.sidebar.settings.account")}
                                        </Link>
                                    </li>
                                    <li className={`list-group-item ${isActive(routes.holidays) ? "active" : ""}`}>
                                        <Link to={routes.holidays} style={isActive(routes.holidays)?{color: "white"}:{color: "#757589"}}>
                                            <i className="fa fa-angle-right"></i>{" "}
                                            {t("admin.sidebar.settings.holidays")}
                                        </Link>
                                    </li>
                                    <li className={`list-group-item ${isActive(routes.waTemplates) ? "active text-white" : ""}`}>
                                        <Link to={routes.waTemplates} style={isActive(routes.waTemplates)?{color: "white"}:{color: "#757589"}}>
                                            <i className="fa fa-angle-right"></i>{" "}
                                            Templates
                                        </Link>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
            <div className="sideLogout">
                <div className="logoutBtn">
                    <button className="btn d-flex justify-content-center align-items-center" onClick={HandleLogout}>
                        <IoIosLogOut className="mr-1 font-28" />
                        {t("admin.sidebar.logout")}
                    </button>
                </div>
            </div>
        </div>
    );
}






// import React, { useEffect, useState } from "react";
// import { Link , useLocation} from "react-router-dom";
// import { useAlert } from "react-alert";
// import { useNavigate } from "react-router-dom";
// import axios from "axios";
// import i18next from "i18next";
// import { useTranslation } from "react-i18next";
// import { IoIosLogOut } from "react-icons/io";
// import { HiOutlineSquares2X2 } from "react-icons/hi2";
// import { RiVideoChatLine } from "react-icons/ri";
// import { FaRegBookmark } from "react-icons/fa";
// import { LiaFileContractSolid } from "react-icons/lia";
// import { MdHomeRepairService } from "react-icons/md";
// import { MdOutlinePayments } from "react-icons/md";
// import { IoMdSettings } from "react-icons/io";

// import logo from "../../Assets/image/sample.svg";
// import { NavLink } from "react-router-dom";

// export default function Sidebar() {
//     const location = useLocation();
//     const alert = useAlert();
//     const navigate = useNavigate();
//     const [role, setRole] = useState(null);
//     const { t } = useTranslation();
//     const headers = {
//         Accept: "application/json, text/plain, */*",
//         "Content-Type": "application/json",
//         Authorization: `Bearer ` + localStorage.getItem("admin-token"),
//     };
//     const adminLng = localStorage.getItem("admin-lng");


//     const getAdmin = () => {
//         axios.get(`/api/admin/details`, { headers }).then((res) => {
//             setRole(res.data.success.role);
//         });
//     };

//     const HandleLogout = (e) => {
//         fetch("/api/admin/logout", {
//             method: "POST",
//             headers,
//         }).then((res) => {
//             if (res.status === 200) {
//                 localStorage.removeItem("admin-token");
//                 localStorage.removeItem("admin-name");
//                 localStorage.removeItem("admin-id");
//                 localStorage.removeItem("admin-email");
//                 localStorage.removeItem("admin-lng");
//                 navigate("/admin/login");
//                 alert.success("Logged Out Successfully");
//             }
//         });
//     };

//     const routes = {
//         manageTeam: "/admin/manage-team",
//         services: "/admin/services",
//         manpowerCompanies: "/admin/manpower-companies",
//         manageTime: "/admin/manage-time",
//         accountSettings: "/admin/settings",
//     };

//     const isActive = (path) => location.pathname === path;

//     // Check if any of the children are active to apply the active class to the parent
//     const isParentActive = () => {
//         return Object.values(routes).some((route) => isActive(route));
//     };

//     // console.log(lng);

//     useEffect(() => {
//         i18next.changeLanguage(adminLng);
//         getAdmin();
//     }, []);

//     return (
//         <div id="column-left">
//             <div className="sideLogo">
//                 <Link to="/admin/dashboard">
//                     <svg
//                         width="190"
//                         height="77"
//                         xmlns="http://www.w3.org/2000/svg"
//                         xmlnsXlink="http://www.w3.org/1999/xlink"
//                     >
//                         <image xlinkHref={logo} width="190" height="77"></image>
//                     </svg>
//                 </Link>
//             </div>
//             <ul className="list-group">
//                 <li className="list-group-item">
//                     <NavLink to="/admin/dashboard"
//                     className="d-flex align-items-center"
//                     >
//                     <i className="d-flex align-items-center">
//                             <HiOutlineSquares2X2 className="font-28" />
//                         </i>{t("admin.sidebar.dashboard")}
//                     </NavLink>
//                 </li>
//                 <li className="list-group-item">
//                     <NavLink to="/admin/leads"
//                     className="d-flex align-items-center"
//                     >
//                         <i className="fa-solid fa-poll-h font-28"></i>{t("admin.sidebar.leads")}
//                     </NavLink>
//                 </li>
//                 <li className="list-group-item">
//                     <NavLink to="/admin/clients"
//                     className="d-flex align-items-center"
//                     >
//                         <i className="fa-solid fa-user-tie font-20"></i>{t("admin.sidebar.clients")}
//                     </NavLink>
//                 </li>
//                 <li className="list-group-item">
//                     <NavLink to="/admin/workers"
//                     className="d-flex align-items-center"
//                     >
//                         <i className="fa-solid fa-users font-20"></i>{t("admin.sidebar.workers")}
//                     </NavLink>
//                 </li>
//                 <li className="list-group-item">
//                     <NavLink to="/admin/schedule"
//                     className="d-flex align-items-center"
//                     >
//                         <i className="fa-solid fa-video font-20"></i>{t("admin.sidebar.meetings")}
//                     </NavLink>
//                 </li>
//                 <li className="list-group-item">
//                     <NavLink to="/admin/offered-price"
//                     className="d-flex align-items-center"
//                     >
//                         <i className="fa-solid fa-tags font-20"></i>{t("admin.sidebar.offers")}
//                     </NavLink>
//                 </li>
//                 <li className="list-group-item">
//                     <NavLink to="/admin/contracts"
//                     className="d-flex align-items-center"
//                     >
//                         <i className="fa-solid fa-clipboard-list font-20"></i>{t("admin.sidebar.contracts")}
//                     </NavLink>
//                 </li>
//                 <li className="list-group-item">
//                     <NavLink to="/admin/jobs"
//                     className="d-flex align-items-center"
//                     >
//                         <i className="fa-solid fa-briefcase font-20"></i>{t("admin.sidebar.schedule_meet")}
//                     </NavLink>
//                 </li>
//                 <li className="list-group-item">
//                     <NavLink to="/admin/chat"
//                     className="d-flex align-items-center"
//                     >
//                         <i className="fa-solid fa-message font-20"></i>{t("admin.sidebar.whatsapp")}
//                     </NavLink>
//                 </li>

//                 {/* <li className="list-group-item">
//                     <div id="myFence" className="fence commonDropdown">
//                         <div id="fencehead2">
//                             <a
//                                 href="#"
//                                 className="text-left btn btn-header-link"
//                                 data-toggle="collapse"
//                                 data-target="#fence2121"
//                                 aria-expanded="true"
//                                 aria-controls="fence2121"
//                             >
//                                 <i className="fa-solid fa-message"></i> Whatsapp
//                                 chat <i className="fa-solid fa-angle-down"></i>
//                             </a>
//                         </div>
//                         <div
//                             id="fence2121"
//                             className="collapse"
//                             aria-labelledby="fencehead2"
//                             data-parent="#fence"
//                         >
//                             <div className="card-body">
//                                 <ul className="list-group">
//                                     <li className="list-group-item">
//                                         <Link to="/admin/chat">
//                                             <i className="fa fa-angle-right"></i>{" "}
//                                             Chat History{" "}
//                                         </Link>
//                                     </li>
//                                     <li className="list-group-item">
//                                         <Link to="/admin/responses">
//                                             <i className="fa fa-angle-right"></i>{" "}
//                                             Whatsapp Responses{" "}
//                                         </Link>
//                                     </li>
//                                 </ul>
//                             </div>
//                         </div>
//                     </div>
//                 </li> */}

//                 <li className="list-group-item">
//                     <NavLink to="/admin/messenger">
//                         <i className="fa-solid fa-comment font-20"></i>{t("admin.sidebar.messenger")}
//                     </NavLink>
//                 </li>

//                 <li className="list-group-item">
//                     <NavLink to="/admin/payments">
//                         <i className="fa-solid fa-cart-shopping font-20"></i>{t("admin.sidebar.payments")}
//                     </NavLink>
//                 </li>

//                 {/* <li className="list-group-item">
//                     <div id="myFence" className="fence commonDropdown">
//                         <div id="fencehead2">
//                             <a
//                                 href="#"
//                                 className="text-left btn btn-header-link"
//                                 data-toggle="collapse"
//                                 data-target="#fence21"
//                                 aria-expanded="true"
//                                 aria-controls="fence21"
//                             >
//                                 <i className="fa-solid fa-file-invoice"></i>{" "}
//                                 Sales <i className="fa-solid fa-angle-down"></i>
//                             </a>
//                         </div>
//                         <div
//                             id="fence21"
//                             className="collapse"
//                             aria-labelledby="fencehead2"
//                             data-parent="#fence"
//                         >
//                             <div className="card-body">
//                                 <ul className="list-group">
//                                     <li className="list-group-item">
//                                         <Link to="/admin/orders">
//                                             <i className="fa fa-angle-right"></i>{" "}
//                                             Orders{" "}
//                                         </Link>
//                                     </li>
//                                     <li className="list-group-item">
//                                         <Link to="/admin/invoices">
//                                             <i className="fa fa-angle-right"></i>{" "}
//                                             Invoices{" "}
//                                         </Link>
//                                     </li>
//                                     <li className="list-group-item">
//                                         <Link to="/admin/payments">
//                                             <i className="fa fa-angle-right"></i>{" "}
//                                             Payments{" "}
//                                         </Link>
//                                     </li>
//                                 </ul>
//                             </div>
//                         </div>
//                     </div>
//                 </li> */}

//                 {role !== "member" && (
//                     <li className="list-group-item">
//                         <NavLink to="/admin/income">
//                             <i className="fa-solid fa-ils font-20"></i>{t("admin.sidebar.earnings")}
//                         </NavLink>
//                     </li>
//                 )}
//                 <li className="list-group-item">
//                     <NavLink to="/admin/notifications">
//                         <i className="fa-solid fa-bullhorn font-20"></i>{t("admin.sidebar.notification")}
//                     </NavLink>
//                 </li>

//                 <li className="list-group-item">
//                     <div id="myFence" className="fence commonDropdown">
//                         <div id="fencehead2">
//                             <a
//                                 href="#"
//                                 className="text-left btn btn-header-link"
//                                 data-toggle="collapse"
//                                 data-target="#fence2"
//                                 aria-expanded="true"
//                                 aria-controls="fence2"
//                             >
//                                 <i className="fa-solid fa-gear font-20"></i> {t("admin.sidebar.settings.title")}{" "}
//                                 <i className="fa-solid fa-angle-down"></i>
//                             </a>
//                         </div>
//                         <div
//                             id="fence2"
//                             className="collapse"
//                             aria-labelledby="fencehead2"
//                             data-parent="#fence"
//                         >
//                             <div className="card-body">
//                                 <ul className="list-group">
//                                     {role !== "member" && (
//                                         <li className="list-group-item">
//                                             <Link to="/admin/manage-team">
//                                                 <i className="fa fa-angle-right"></i>{" "}
//                                                 {t("admin.sidebar.settings.manage_team")}
//                                             </Link>
//                                         </li>
//                                     )}
//                                     <li className="list-group-item">
//                                         <Link to="/admin/services">
//                                             <i className="fa fa-angle-right"></i>{" "}
//                                             {t("admin.sidebar.settings.services")}
//                                         </Link>
//                                     </li>
//                                     <li className="list-group-item">
//                                         <Link to="/admin/manpower-companies">
//                                             <i className="fa fa-angle-right"></i>{" "}
//                                             {t("admin.sidebar.settings.manpower")}
//                                         </Link>
//                                     </li>
//                                     <li className="list-group-item">
//                                         <Link to="/admin/manage-time">
//                                             <i className="fa fa-angle-right"></i>{" "}
//                                             {t("admin.sidebar.settings.manageTime")}
//                                         </Link>
//                                     </li>
//                                     {/*<li className='list-group-item'>
//                                         <Link to="/admin/languages"><i className="fa fa-angle-right"></i>Languages</Link>
//                                     </li>*/}
//                                     {/* <li className="list-group-item">
//                                         <Link to="/admin/credentials">
//                                             <i className="fa fa-angle-right"></i>{" "}
//                                             Credentials{" "}
//                                         </Link>
//                                     </li> */}
//                                     <li className="list-group-item">
//                                         <Link to="/admin/settings">
//                                             <i className="fa fa-angle-right"></i>{" "}
//                                             {t("admin.sidebar.settings.account")}
//                                         </Link>
//                                     </li>
//                                 </ul>
//                             </div>
//                         </div>
//                     </div>
//                 </li>
//             </ul>
//             <div className="sideLogout">
//                 <div className="logoutBtn">
//                     <button className="btn d-flex justify-content-center align-items-center" onClick={HandleLogout}>
//                     <IoIosLogOut className="mr-1 font-28" />
//                     {t("admin.sidebar.logout")}
//                     </button>
//                 </div>
//             </div>
//         </div>
//     );
// }
