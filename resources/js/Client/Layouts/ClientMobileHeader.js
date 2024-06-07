import React, { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { useAlert } from "react-alert";
import { useTranslation } from "react-i18next";

import logo from "../../Assets/image/sample.svg";
import bars from "../../Assets/image/icons/bars.svg";

export default function ClientMobileHeader() {
    const alert = useAlert();
    const navigate = useNavigate();
    const [isSubmitting, setIsSubmitting] = useState(false);

    const { t } = useTranslation();

    const HandleLogout = (e) => {
        setIsSubmitting(true);
        fetch("/api/client/logout", {
            method: "POST",
            headers: {
                Accept: "application/json, text/plain, */*",
                "Content-Type": "application/json",
                Authorization: `Bearer ` + localStorage.getItem("client-token"),
            },
        })
            .then((res) => {
                if (res.status === 200) {
                    localStorage.removeItem("client-token");
                    localStorage.removeItem("client-name");
                    localStorage.removeItem("client-id");
                    navigate("/client/login");
                    alert.success("Logged Out Successfully");
                }
                setIsSubmitting(false);
            })
            .catch((e) => {
                setIsSubmitting(false);
            });
    };

    return (
        <div className="mobileNav hidden-xl">
            <nav className="navbar navbar-expand-lg navbar-dark fixed-top">
                <Link className="navbar-brand" to="/client/dashboard">
                    <svg
                        width="190"
                        height="77"
                        xmlns="http://www.w3.org/2000/svg"
                        xmlnsXlink="http://www.w3.org/1999/xlink"
                    >
                        <image xlinkHref={logo} width="190" height="77"></image>
                    </svg>
                </Link>
                <button
                    className="navbar-toggler"
                    type="button"
                    data-toggle="collapse"
                    data-target="#navbarCollapse"
                    aria-controls="navbarCollapse"
                    aria-expanded="false"
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
                <div className="collapse navbar-collapse" id="navbarCollapse">
                    <ul className="navbar-nav mr-auto">
                        <li className="nav-item">
                            <a href="/client/dashboard">
                                <i className="fa-solid fa-gauge"></i>
                                {t("client.sidebar.dashboard")}
                            </a>
                        </li>
                        <li className="nav-item">
                            <a href="/client/schedule">
                                <i className="fa-solid fa-video"></i>
                                {t("client.common.meetings")}
                            </a>
                        </li>
                        <li className="nav-item">
                            <a href="/client/offered-price">
                                <i className="fa-solid fa-tags"></i>
                                {t("client.common.offers")}
                            </a>
                        </li>
                        <li className="nav-item">
                            <a href="/client/contracts">
                                <i className="fa-solid fa-clipboard-list"></i>
                                {t("client.sidebar.contracts")}
                            </a>
                        </li>
                        <li className="nav-item">
                            <a href="/client/jobs">
                                <i className="fa-solid fa-briefcase"></i>
                                {t("client.common.services")}
                            </a>
                        </li>
                        <li className="nav-item">
                            <a href="/client/invoices">
                                <i className="fa-solid fa-briefcase"></i>
                                {t("client.common.payments")}
                            </a>
                        </li>
                        <li className="nav-item">
                            <a href="/client/settings">
                                <i className="fa-solid fa-gear"></i>{t("client.sidebar.settings")}
                            </a>
                        </li>
                    </ul>
                    <div className="sideLogout">
                        <div className="logoutBtn">
                            <button
                                type="button"
                                className="btn btn-danger"
                                onClick={HandleLogout}
                                disabled={isSubmitting}
                            >
                                Logout
                            </button>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    );
}
