import React, { useState, useEffect } from "react";
import { useNavigate, Link } from "react-router-dom";
import User from "../../Assets/image/user.png";
import { useAlert } from "react-alert";
import axios from "axios";
import ClientMobileHeader from "./ClientMobileHeader";
import { useTranslation } from "react-i18next";
import i18next from "i18next";

export default function ClientHeader() {
    const { t } = useTranslation();
    const alert = useAlert();
    const navigate = useNavigate();
    const [avatar, setAvatar] = useState("");
    const HandleLogout = (e) => {
        fetch("/api/client/logout", {
            method: "POST",
            headers: {
                Accept: "application/json, text/plain, */*",
                "Content-Type": "application/json",
                Authorization: `Bearer ` + localStorage.getItem("client-token"),
            },
        }).then((res) => {
            if (res.status === 200) {
                localStorage.removeItem("client-token");
                localStorage.removeItem("client-name");
                localStorage.removeItem("client-id");
                navigate("/client/login");
                alert.success(t("global.Logout"));
            }
        });
    };
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("client-token"),
    };
    const getAvatar = () => {
        axios.get("/api/client/my-account", { headers }).then((res) => {
            setAvatar(res.data.account.avatar);
            i18next.changeLanguage(res.data.account.lng);
            if(res?.data?.account?.lng == "en") {
                document.querySelector("html").removeAttribute("dir");
                    const rtlLink = document.querySelector('link[href*="rtl.css"]');
                    if (rtlLink) {
                        rtlLink.remove();
                    }
            }
        });
    };
    useEffect(() => {
        getAvatar();
    }, []);

    return (
        <>
            <div className="AdminHeader hidden-xs">
                <div className="container-fluid">
                    <div className="d-flex justify-content-end">
                        <div className="">
                            <div className="float-right" style={{marginTop: "20px", marginBottom: "20px"}}>
                                <div className="dropdown show">
                                    <div
                                        className="dropdown-toggle"
                                        href="#!"
                                        role="button"
                                        id="dropdownMenuLink"
                                        data-toggle="dropdown"
                                        aria-haspopup="true"
                                        aria-expanded="false"
                                    >
                                        <span>
                                            {localStorage.getItem("client-name")}
                                        </span>
                                    </div>
                                    <div
                                        className="dropdown-menu dropdown-menu-right"
                                        aria-labelledby="dropdownMenuLink"
                                    >
                                        <Link
                                            className="dropdown-item"
                                            to="/client/settings"
                                        >
                                            {t("client.my_account")}
                                        </Link>
                                        <Link
                                            className="dropdown-item"
                                            onClick={HandleLogout}
                                        >
                                            {t("client.logout")}
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <ClientMobileHeader />
        </>
    );
}
