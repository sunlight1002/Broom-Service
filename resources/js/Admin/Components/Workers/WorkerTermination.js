import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import WorkersHearing from './WorkersHearing';
import HearingProtocol from './HearingProtocol';
import DisplayClaims from './DisplayClaims';
import FinalDecision from './FinalDecision';

export default function WorkerTermination({ worker, getWorkerDetails }) {
    const { t } = useTranslation();
    const [activeTab, setActiveTab] = useState('invitationForHearing');

    const handleTabChange = (tab) => {
        setActiveTab(tab);
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
                <li className="nav-item" role="presentation">
                    <a
                        className={`nav-link ${activeTab === 'showClaims' ? 'active' : ''}`}
                        onClick={() => handleTabChange('showClaims')}
                        role="tab"
                    >
                        {t("admin.hearing.showClaims")}
                    </a>
                </li>
                <li className="nav-item" role="presentation">
                    <a
                        className={`nav-link ${activeTab === 'hearingProtocol' ? 'active' : ''}`}
                        onClick={() => handleTabChange('hearingProtocol')}
                        role="tab"
                    >
                         {t("admin.hearing.protocol.hearingprotocol")}
                    </a>
                </li>
                <li className="nav-item" role="presentation">
                    <a
                        className={`nav-link ${activeTab === 'finalDecision' ? 'active' : ''}`}
                        onClick={() => handleTabChange('finalDecision')}
                        role="tab"
                    >
                        {t("admin.hearing.finalDecision")}
                    </a>
                </li>
            </ul>

            <div className="tab-content" style={{ background: "#fff" }}>
                {activeTab === 'invitationForHearing' && (
                    <div className="tab-pane active show" role="tabpanel">
                        <WorkersHearing worker={worker} getWorkerDetails={getWorkerDetails}/>
                    </div>
                )}
                {activeTab === 'showClaims' && (
                    <div className="tab-pane active show" role="tabpanel">
                        <DisplayClaims worker={worker} getWorkerDetails={getWorkerDetails}/>
                    </div>
                )}
                {activeTab === 'hearingProtocol' && (
                    <div className="tab-pane active show" role="tabpanel">
                        <HearingProtocol worker={worker} getWorkerDetails={getWorkerDetails}/>
                    </div>
                )}
                {activeTab === 'finalDecision' && (
                    <div className="tab-pane active show" role="tabpanel">
                        <FinalDecision worker={worker} getWorkerDetails={getWorkerDetails}/>
                    </div>
                )}
            </div>
        </div>
    );
}
