import React, { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { useAlert } from "react-alert";
import { useNavigate } from "react-router-dom";
import axios from "axios";
import i18next from "i18next";
import { IoIosLogOut } from "react-icons/io";
import { HiOutlineSquares2X2 } from "react-icons/hi2";
import { RiVideoChatLine } from "react-icons/ri";
import { FaRegBookmark } from "react-icons/fa";
import { LiaFileContractSolid } from "react-icons/lia";
import { MdHomeRepairService } from "react-icons/md";
import { MdOutlinePayments } from "react-icons/md";
import { IoMdSettings } from "react-icons/io";

import logo from "../../Assets/image/sample.svg";
import { NavLink } from "react-router-dom";

export default function Sidebar() {
    const alert = useAlert();
    const navigate = useNavigate();
    const [role, setRole] = useState(null);
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

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
                navigate("/admin/login");
                alert.success("Logged Out Successfully");
            }
        });
    };

    useEffect(() => {
        i18next.changeLanguage("en");
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
                        </i>Dashboard
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink to="/admin/leads"
                    className="d-flex align-items-center"
                    >
                        <i className="fa-solid fa-poll-h font-28"></i>Leads
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink to="/admin/clients"
                    className="d-flex align-items-center"
                    >
                        <i className="fa-solid fa-user-tie font-20"></i>Clients
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink to="/admin/workers"
                    className="d-flex align-items-center"
                    >
                        <i className="fa-solid fa-users font-20"></i>Workers
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink to="/admin/schedule"
                    className="d-flex align-items-center"
                    >
                        <i className="fa-solid fa-video font-20"></i>Meetings
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink to="/admin/offered-price"
                    className="d-flex align-items-center"
                    >
                        <i className="fa-solid fa-tags font-20"></i>Offers
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink to="/admin/contracts"
                    className="d-flex align-items-center"
                    >
                        <i className="fa-solid fa-clipboard-list font-20"></i>Contracts
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink to="/admin/jobs"
                    className="d-flex align-items-center"
                    >
                        <i className="fa-solid fa-briefcase font-20"></i>Schedule
                    </NavLink>
                </li>
                <li className="list-group-item">
                    <NavLink to="/admin/chat"
                    className="d-flex align-items-center"
                    >
                        <i className="fa-solid fa-message font-20"></i>Whatsapp chat
                    </NavLink>
                </li>

                {/* <li className="list-group-item">
                    <div id="myFence" className="fence commonDropdown">
                        <div id="fencehead2">
                            <a
                                href="#"
                                className="text-left btn btn-header-link"
                                data-toggle="collapse"
                                data-target="#fence2121"
                                aria-expanded="true"
                                aria-controls="fence2121"
                            >
                                <i className="fa-solid fa-message"></i> Whatsapp
                                chat <i className="fa-solid fa-angle-down"></i>
                            </a>
                        </div>
                        <div
                            id="fence2121"
                            className="collapse"
                            aria-labelledby="fencehead2"
                            data-parent="#fence"
                        >
                            <div className="card-body">
                                <ul className="list-group">
                                    <li className="list-group-item">
                                        <Link to="/admin/chat">
                                            <i className="fa fa-angle-right"></i>{" "}
                                            Chat History{" "}
                                        </Link>
                                    </li>
                                    <li className="list-group-item">
                                        <Link to="/admin/responses">
                                            <i className="fa fa-angle-right"></i>{" "}
                                            Whatsapp Responses{" "}
                                        </Link>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </li> */}

                <li className="list-group-item">
                    <NavLink to="/admin/messenger">
                        <i className="fa-solid fa-comment font-20"></i>Messenger Chat
                    </NavLink>
                </li>

                <li className="list-group-item">
                    <NavLink to="/admin/payments">
                        <i className="fa-solid fa-cart-shopping font-20"></i>Payments
                    </NavLink>
                </li>

                {/* <li className="list-group-item">
                    <div id="myFence" className="fence commonDropdown">
                        <div id="fencehead2">
                            <a
                                href="#"
                                className="text-left btn btn-header-link"
                                data-toggle="collapse"
                                data-target="#fence21"
                                aria-expanded="true"
                                aria-controls="fence21"
                            >
                                <i className="fa-solid fa-file-invoice"></i>{" "}
                                Sales <i className="fa-solid fa-angle-down"></i>
                            </a>
                        </div>
                        <div
                            id="fence21"
                            className="collapse"
                            aria-labelledby="fencehead2"
                            data-parent="#fence"
                        >
                            <div className="card-body">
                                <ul className="list-group">
                                    <li className="list-group-item">
                                        <Link to="/admin/orders">
                                            <i className="fa fa-angle-right"></i>{" "}
                                            Orders{" "}
                                        </Link>
                                    </li>
                                    <li className="list-group-item">
                                        <Link to="/admin/invoices">
                                            <i className="fa fa-angle-right"></i>{" "}
                                            Invoices{" "}
                                        </Link>
                                    </li>
                                    <li className="list-group-item">
                                        <Link to="/admin/payments">
                                            <i className="fa fa-angle-right"></i>{" "}
                                            Payments{" "}
                                        </Link>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </li> */}

                {role !== "member" && (
                    <li className="list-group-item">
                        <NavLink to="/admin/income">
                            <i className="fa-solid fa-ils font-20"></i>Earnings
                        </NavLink>
                    </li>
                )}
                <li className="list-group-item">
                    <NavLink to="/admin/notifications">
                        <i className="fa-solid fa-bullhorn font-20"></i>Notifications
                    </NavLink>
                </li>

                <li className="list-group-item">
                    <div id="myFence" className="fence commonDropdown">
                        <div id="fencehead2">
                            <a
                                href="#"
                                className="text-left btn btn-header-link"
                                data-toggle="collapse"
                                data-target="#fence2"
                                aria-expanded="true"
                                aria-controls="fence2"
                            >
                                <i className="fa-solid fa-gear font-20"></i> Settings{" "}
                                <i className="fa-solid fa-angle-down"></i>
                            </a>
                        </div>
                        <div
                            id="fence2"
                            className="collapse"
                            aria-labelledby="fencehead2"
                            data-parent="#fence"
                        >
                            <div className="card-body">
                                <ul className="list-group">
                                    {role !== "member" && (
                                        <li className="list-group-item">
                                            <Link to="/admin/manage-team">
                                                <i className="fa fa-angle-right"></i>{" "}
                                                Manage team
                                            </Link>
                                        </li>
                                    )}
                                    <li className="list-group-item">
                                        <Link to="/admin/services">
                                            <i className="fa fa-angle-right"></i>{" "}
                                            Services
                                        </Link>
                                    </li>
                                    <li className="list-group-item">
                                        <Link to="/admin/manpower-companies">
                                            <i className="fa fa-angle-right"></i>{" "}
                                            Manpower Companies
                                        </Link>
                                    </li>
                                    <li className="list-group-item">
                                        <Link to="/admin/manage-time">
                                            <i className="fa fa-angle-right"></i>{" "}
                                            Manage Time
                                        </Link>
                                    </li>
                                    {/*<li className='list-group-item'>
                                        <Link to="/admin/languages"><i className="fa fa-angle-right"></i>Languages</Link>
                                    </li>*/}
                                    {/* <li className="list-group-item">
                                        <Link to="/admin/credentials">
                                            <i className="fa fa-angle-right"></i>{" "}
                                            Credentials{" "}
                                        </Link>
                                    </li> */}
                                    <li className="list-group-item">
                                        <Link to="/admin/settings">
                                            <i className="fa fa-angle-right"></i>{" "}
                                            My Account
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
                        Logout
                    </button>
                </div>
            </div>
        </div>
    );
}
