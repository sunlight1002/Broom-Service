import React, { useState, useEffect } from "react";
import Icount from "../../Components/Settings/Icount";
import Zcredit from "../../Components/Settings/Zcredit";
import Sidebar from "../../Layouts/Sidebar";

export default function Credentials() {
    const [settings, setSettings] = useState("");

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getSettings = () => {
        axios.get(`/api/admin/settings`, { headers }).then((res) => {
            let r = res.data;
            setSettings(r);
        });
    };

    const refreshSettings = () => {
        getSettings();
    };

    useEffect(() => {
        getSettings();
    }, []);

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="settings-page">
                    <h1 className="page-title revTitle">Credentials</h1>
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
                                ICount
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
                                ZCredit
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
                            <Icount
                                settings={settings}
                                refreshSettings={refreshSettings}
                            />
                        </div>
                        <div
                            id="tab-password"
                            className="tab-pane"
                            role="tab-panel"
                            aria-labelledby="password-tab"
                        >
                            <Zcredit
                                settings={settings}
                                refreshSettings={refreshSettings}
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
