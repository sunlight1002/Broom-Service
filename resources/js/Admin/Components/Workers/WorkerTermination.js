import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import HearingInvitationForm from './HearingInvitationForm';
// import HearingProtocol from './HearingProtocol';
// import DecisionDocument from './DecisionDocument';

export default function WorkerTermination({ worker, getWorkerDetails }) {
    const { t } = useTranslation();
    const [activeTab, setActiveTab] = useState('invitationForHearing'); // State for active tab

    const handleTabChange = (tab) => {
        setActiveTab(tab); // Update the active tab
    };

    return (
        <div className="WorkerTermination">
            <ul className="nav nav-tabs" role="tablist">
                <li className="nav-item" role="presentation">
                    <a
                        className={`nav-link ${activeTab === 'invitationForHearing' ? 'active' : ''}`}
                        onClick={() => handleTabChange('invitationForHearing')}
                        role="tab"
                    >
                        {t("worker.settings.invitation_for_hearing")}
                    </a>
                </li>
                {/* <li className="nav-item" role="presentation">
                    <a
                        className={`nav-link ${activeTab === 'hearingProtocol' ? 'active' : ''}`}
                        onClick={() => handleTabChange('hearingProtocol')}
                        role="tab"
                    >
                        {t("worker.settings.invitation_for_hearing")}
                    </a>
                </li>
                <li className="nav-item" role="presentation">
                    <a
                        className={`nav-link ${activeTab === 'decisionDocument' ? 'active' : ''}`}
                        onClick={() => handleTabChange('decisionDocument')}
                        role="tab"
                    >
                        {t("worker.settings.invitation_for_hearing")}
                    </a>
                </li> */}
            </ul>

            <div className="tab-content" style={{ background: "#fff" }}>
                {activeTab === 'invitationForHearing' && (
                    <div className="tab-pane active show" role="tabpanel">
                        <HearingInvitationForm worker={worker} getWorkerDetails={getWorkerDetails} />
                    </div>
                )}
                {activeTab === 'hearingProtocol' && (
                    <div className="tab-pane" role="tabpanel">
                        <HearingProtocol worker={worker} />
                    </div>
                )}
                {/* {activeTab === 'decisionDocument' && (
                    <div className="tab-pane" role="tabpanel">
                        <DecisionDocument worker={worker} />
                    </div>
                )} */}
            </div>
        </div>
    );
}
