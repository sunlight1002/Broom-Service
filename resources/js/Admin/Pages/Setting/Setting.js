import React from "react";
import Acc from "../../Components/Settings/Acc";
import ChangePass from "../../Components/Settings/ChangePass";
import Sidebar from "../../Layouts/Sidebar";
import { useTranslation } from "react-i18next";

export default function Setting() {
    const { t } = useTranslation();
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
                    </div>
                </div>
            </div>
        </div>
    );
}
