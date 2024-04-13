import React from "react";
import Contract from "./Contract";
import OfferedPrice from "./offers";
import ScheduledMeeting from "./schedules";
import { useTranslation } from "react-i18next";

export default function LeadHistory({ client }) {
    const { t } = useTranslation();
    return (
        <div className="ClientHistory">
            <ul className="nav nav-tabs" role="tablist">
                <li className="nav-item" role="presentation">
                    <a
                        id="schedule-meeting"
                        className="nav-link active"
                        data-toggle="tab"
                        href="#tab-schedule"
                        aria-selected="true"
                        role="tab"
                    >
                        {t("admin.leads.viewLead.ScheduledMeeting")}
                    </a>
                </li>
                <li className="nav-item" role="presentation">
                    <a
                        id="offers"
                        className="nav-link"
                        data-toggle="tab"
                        href="#tab-offered"
                        aria-selected="true"
                        role="tab"
                    >
                        {t("admin.leads.viewLead.Offers")}
                    </a>
                </li>
            </ul>
            <div className="tab-content">
                <div
                    id="tab-schedule"
                    className="tab-pane active show"
                    role="tab-panel"
                    aria-labelledby="schedule-meeting"
                >
                    <ScheduledMeeting />
                </div>
                <div
                    id="tab-offered"
                    className="tab-pane"
                    role="tab-panel"
                    aria-labelledby="offered-price"
                >
                    <OfferedPrice />
                </div>
            </div>
        </div>
    );
}
