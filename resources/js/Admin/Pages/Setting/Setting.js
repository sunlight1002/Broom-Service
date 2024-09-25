import React, { useEffect, useState } from "react";
import Acc from "../../Components/Settings/Acc";
import ChangePass from "../../Components/Settings/ChangePass";
import Integration from "../../Components/Settings/Integration";
import Sidebar from "../../Layouts/Sidebar";
import { useTranslation } from "react-i18next";
import BankDetails from "../../Components/Settings/BankDetails";

export default function Setting() {
    const { t } = useTranslation();
    const [role, setRole] = useState("")

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getSetting = async () => {

        try {
            const response = await axios.get("/api/admin/my-account", { headers });
            const data = response.data.account;
            setRole(data.role)
        } catch (error) {
            console.error("Error fetching settings:", error);
        }
    };

    useEffect(() => {
        getSetting();
    }, []);

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="settings-page">
                    <h1 className="page-title revTitle">{t("admin.sidebar.settings.title")}</h1>
                    <ul className="nav nav-tabs mb-2" role="tablist">
                        <li className="nav-item" role="presentation">
                            <a
                                id="account-tab"
                                className="nav-link active"
                                data-toggle="tab"
                                href="#tab-account"
                                aria-selected="false"
                                role="tab"
                            >
                                {t("admin.sidebar.settings.account")}
                            </a>
                        </li>
                        <li className="nav-item" role="presentation">
                            <a
                                id="password-tab"
                                className="nav-link"
                                data-toggle="tab"
                                href="#tab-password"
                                aria-selected="false"
                                role="tab"
                            >
                                {t("client.settings.change_pass")}
                            </a>
                        </li>
                        {role && role === "superadmin" && (
                        <li className="nav-item" role="presentation">
                            <a
                                id="integration-tab"
                                className="nav-link"
                                data-toggle="tab"
                                href="#tab-integration"
                                aria-selected="false"
                                role="tab"
                            >
                                {t("client.settings.integration")}
                            </a>
                        </li>
                        )}
                      {
                        role !== "superadmin" && (
                            <li className="nav-item" role="presentation">
                            <a
                                id="bank-tab"
                                className="nav-link"
                                data-toggle="tab"
                                href="#tab-bank"
                                aria-selected="false"
                                role="tab"
                            >
                                Bank details
                            </a>
                        </li>
                        )
                      }
                    </ul>
                    <div className="tab-content">
                        <div
                            id="tab-account"
                            className="tab-pane active show"
                            role="tab-panel"
                            aria-labelledby="account-tab"
                        >
                            <Acc />
                        </div>
                        <div
                            id="tab-password"
                            className="tab-pane"
                            role="tab-panel"
                            aria-labelledby="password-tab"
                        >
                            <ChangePass />
                        </div>
                        <div
                            id="tab-integration"
                            className="tab-pane"
                            role="tab-panel"
                            aria-labelledby="integration-tab"
                        >
                            <Integration />
                        </div>
                        {
                            role !== "superadmin" && (
                                <div
                                    id="tab-bank"
                                    className="tab-pane"
                                    role="tab-panel"
                                    aria-labelledby="tab-bank"
                                >
                                    <BankDetails />
                                </div>
                            )
                        }
                    </div>
                </div>
            </div>
        </div>
    );
}
