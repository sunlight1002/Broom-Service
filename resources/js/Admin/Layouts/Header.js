import React, { useState, useEffect } from "react";
import { useNavigate, Link } from "react-router-dom";
import User from "../../Assets/image/user.png";
import { useAlert } from "react-alert";
import MobileHeader from "./MobileHeader";
import Moment from "moment";
import axios from "axios";
import i18next from "i18next";
import { useTranslation } from "react-i18next";
import { LuBellRing } from "react-icons/lu";
import HeaderTimer from "./HeaderTimer";


export default function AdminHeader() {
    const alert = useAlert();
    const navigate = useNavigate();
    const [me, setMe] = useState(null);
    const [file, setFile] = useState("");
    const [notices, setNotices] = useState([]);
    const [count, setCount] = useState(0);
    const { t } = useTranslation()
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

    axios.interceptors.response.use(
        response => response,
        error => {
            if (error.response) {
                const url = error.config?.url;
                console.log("Axios Interceptor caught an error:", url);
                console.log("Status:", error.response.status);

                // List of URLs where a 401 should NOT trigger logout
                const skipLogoutUrls = ['/api/admin/notice', '/api/admin/seen'];

                const shouldSkip = skipLogoutUrls.some(skipUrl =>
                    url?.includes(skipUrl)
                );

                if (error.response.status === 401 && !shouldSkip) {
                    console.warn("401 Unauthorized - clearing admin localStorage");

                    localStorage.removeItem('admin-token');
                    localStorage.removeItem('admin-name');
                    localStorage.removeItem('admin-id');
                    localStorage.removeItem('admin-email');
                    localStorage.removeItem('admin-lng');
                    localStorage.removeItem('admin-role');
                    localStorage.removeItem('i18nextLng');

                    // Only redirect if not already on login
                    if (!window.location.pathname.includes('/admin/login')) {
                        window.location.href = '/admin/login';
                    }
                }
            }

            return Promise.reject(error);
        }
    );



    const getSetting = () => {
        axios.get("/api/admin/my-account", { headers }).then((response) => {
            setMe(response.data.account);
            response.data.account.avatar
                ? setFile(response.data.account.avatar)
                : setFile(User);
            i18next.changeLanguage(
                response.data.account.lng ? response.data.account.lng : "en"
            );

            if (response?.data?.account?.lng == "en") {
                document.querySelector("html").removeAttribute("dir");
                const rtlLink = document.querySelector('link[href*="rtl.css"]');
                if (rtlLink) {
                    rtlLink.remove();
                }
            } else {
                document.querySelector("html").setAttribute("dir", "rtl");
                import("../../Assets/css/rtl.css");
            }
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
                            <h1>{t("admin.welcome")}</h1>
                        </div>
                        <div className="col-sm-6">
                            <div className="float-right d-flex">
                                {
                                    (me && me.show_timer == 1) && (
                                        <div className="mx-3 d-flex justify-content-center align-items-center">
                                            <HeaderTimer />
                                        </div>
                                    )
                                }
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
                                        style={{ marginTop: "10px" }}
                                    >
                                        <i className="mt-1"><LuBellRing /></i>
                                    </button>
                                    <ul className="dropdown-menu adminIconDropdown">
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
                                            {t("admin.account")}
                                        </Link>
                                        {me && me.role !== "superadmin" && me.role !== "hr" && (
                                            <Link
                                                className="dropdown-item"
                                                to="/admin/my-availability"
                                            >
                                                {t("admin.availability")}
                                            </Link>
                                        )}
                                        <Link
                                            className="dropdown-item"
                                            onClick={HandleLogout}
                                        >
                                            {t("admin.logout")}
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
