import React, { useState, useEffect } from "react";
import { useNavigate, Link } from "react-router-dom";
import User from "../../Assets/image/user.png";
import { useAlert } from "react-alert";
import MobileHeader from "./MobileHeader";
import Moment from "moment";
import axios from "axios";
import i18next from "i18next";
import { LuBellRing } from "react-icons/lu";


export default function AdminHeader() {
    const alert = useAlert();
    const navigate = useNavigate();
    const [me, setMe] = useState(null);
    const [file, setFile] = useState("");
    const [notices, setNotices] = useState([]);
    const [count, setCount] = useState(0);
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };
    const HandleLogout = (e) => {
        fetch("/api/admin/logout", {
            method: "POST",
            headers: {
                Accept: "application/json, text/plain, */*",
                "Content-Type": "application/json",
                Authorization: `Bearer ` + localStorage.getItem("admin-token"),
            },
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

    const getSetting = () => {
        axios.get("/api/admin/my-account", { headers }).then((response) => {
            setMe(response.data.account);
            response.data.account.avatar
                ? setFile(response.data.account.avatar)
                : setFile(User);
            i18next.changeLanguage(
                response.data.account.lng ? response.data.account.lng : "en"
            );
        });
    };

    const headNotice = () => {
        axios
            .get("/api/admin/notice", {
                headers,
                params: {
                    head: 1,
                },
            })
            .then((res) => {
                setNotices(res.data.notice);
                setCount(res.data.count);
            });
    };
    const handleCount = (e) => {
        e.preventDefault();
        axios.post(`/api/admin/seen`, { all: 1 }, { headers }).then((res) => {
            setCount(0);
        });
    };
    const redirectNotice = (e) => {
        e.preventDefault();
        window.location.href = "/admin/notifications";
    };

    useEffect(() => {
        getSetting();
        headNotice();
        setInterval(() => {
            headNotice();
        }, 10000);
    }, []);

    return (
        <>
            <div className="AdminHeader hidden-xs">
                <div className="container-fluid">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1>Welcome Administrator</h1>
                        </div>
                        <div className="col-sm-6">
                            <div className="float-right d-flex">
                                <div className="dropdown notification-bell">
                                    {count != 0 && notices.length > 0 ? (
                                        <span className="counter">{count}</span>
                                    ) : (
                                        ""
                                    )}  

                                    <button
                                        onClick={(e) => handleCount(e)}
                                        type="button"
                                        className="btn btn-link dropdown-toggle"
                                        data-toggle="dropdown"
                                        style={{marginTop: "10px"}}
                                    >
                                        <i className="mt-1"><LuBellRing /></i>
                                    </button>
                                    <ul className="dropdown-menu">
                                        {notices.map((n, i) => {
                                            return (
                                                <li
                                                    key={i}
                                                    onClick={(e) =>
                                                        redirectNotice(e)
                                                    }
                                                    className="dropdown-item"
                                                >
                                                    <div
                                                        style={
                                                            n.seen == 0
                                                                ? {
                                                                      background:
                                                                          "#eee",
                                                                      padding:
                                                                          "2%",
                                                                      cursor: "pointer",
                                                                  }
                                                                : {
                                                                      padding:
                                                                          "2%",
                                                                      cursor: "pointer",
                                                                  }
                                                        }
                                                        className="agg-list"
                                                    >
                                                        <div className="icons">
                                                            <i className="fas fa-check-circle"></i>
                                                        </div>
                                                        <div className="agg-text">
                                                            <h6
                                                                dangerouslySetInnerHTML={{
                                                                    __html: n.data,
                                                                }}
                                                            />
                                                            <p>
                                                                {Moment(
                                                                    n.created_at
                                                                ).format(
                                                                    "DD MMM Y, HH:MM A"
                                                                )}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </li>
                                            );
                                        })}

                                        {/* View all notification button */}

                                        <li className="dropdown-item">
                                            <div className="allNotification">
                                                <a
                                                    style={{
                                                        color: "#fff",
                                                        display: "block",
                                                    }}
                                                    href="/admin/notifications"
                                                    className="btn btn-pink"
                                                >
                                                    See all
                                                </a>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                                <div className="userToggle dropdown show">
                                    <div
                                        className="dropdown-toggle"
                                        href="#!"
                                        role="button"
                                        id="dropdownMenuLink"
                                        data-toggle="dropdown"
                                        aria-haspopup="true"
                                        aria-expanded="false"
                                    >
                                        <img
                                            src={file}
                                            className="img-fluid"
                                            alt="Admin"
                                        />
                                    </div>
                                    <div
                                        className="dropdown-menu"
                                        aria-labelledby="dropdownMenuLink"
                                    >
                                        <Link
                                            className="dropdown-item"
                                            to="/admin/settings"
                                        >
                                            My Account
                                        </Link>
                                        {me && me.role !== "superadmin" && (
                                            <Link
                                                className="dropdown-item"
                                                to="/admin/my-availability"
                                            >
                                                My Availability
                                            </Link>
                                        )}
                                        <Link
                                            className="dropdown-item"
                                            onClick={HandleLogout}
                                        >
                                            Logout
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <MobileHeader />
        </>
    );
}
