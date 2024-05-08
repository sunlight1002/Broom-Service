import React from "react";
import { useLocation } from "react-router-dom";

import Contract from "./Contract";
import Jobs from "./Jobs";
import OfferedPrice from "./OfferedPrice";
import ScheduledMeeting from "./ScheduledMeeting";
import CardDetails from "./CardDetails";
import Order from "./Order";
import Invoice from "./Invoice";
import Payment from "./Payment";
import Comments from "../common/Comments";

export default function ClientHistory({
    contracts,
    setContracts,
    latestContract,
    client,
    fetchContract,
}) {
    const { hash } = useLocation();

    return (
        <div className="ClientHistory">
            <ul className="nav nav-tabs" role="tablist">
                <li className="nav-item" role="presentation">
                    <a
                        id="schedule-meeting"
                        className={
                            `nav-link ` +
                            (!hash || hash === "#tab-schedule" ? "active" : "")
                        }
                        data-toggle="tab"
                        href="#tab-schedule"
                        aria-selected={
                            !hash || hash === "#tab-schedule" ? "true" : "false"
                        }
                        role="tab"
                    >
                        Scheduled Meeting
                    </a>
                </li>
                <li className="nav-item" role="presentation">
                    <a
                        id="offered-price"
                        className={
                            `nav-link ` +
                            (hash === "#tab-offered" ? "active" : "")
                        }
                        data-toggle="tab"
                        href="#tab-offered"
                        aria-selected={
                            hash === "#tab-offered" ? "true" : "false"
                        }
                        role="tab"
                    >
                        Offered Price
                    </a>
                </li>
                <li className="nav-item" role="presentation">
                    <a
                        id="contract"
                        className={
                            `nav-link ` +
                            (hash === "#tab-contract" ? "active" : "")
                        }
                        data-toggle="tab"
                        href="#tab-contract"
                        aria-selected={
                            hash === "#tab-contract" ? "true" : "false"
                        }
                        role="tab"
                    >
                        Contracts
                    </a>
                </li>
                <li className="nav-item" role="presentation">
                    <a
                        id="jobs-tab"
                        className={
                            `nav-link ` + (hash === "#tab-jobs" ? "active" : "")
                        }
                        data-toggle="tab"
                        href="#tab-jobs"
                        aria-selected={hash === "#tab-jobs" ? "true" : "false"}
                        role="tab"
                    >
                        Jobs
                    </a>
                </li>

                {/* <li className="nav-item" role="presentation">
                    <a
                        id="order-tab"
                        className={
                            `nav-link ` +
                            (hash === "#tab-order" ? "active" : "")
                        }
                        data-toggle="tab"
                        href="#tab-order"
                        onClick={(e) => {
                            $(".order_drop").show();
                        }}
                        aria-selected={hash === "#tab-order" ? "true" : "false"}
                        role="tab"
                    >
                        Orders{" "}
                    </a>
                </li> */}

                <li className="nav-item" role="presentation">
                    <a
                        id="invoice-tab"
                        className={
                            `nav-link ` +
                            (hash === "#tab-invoice" ? "active" : "")
                        }
                        data-toggle="tab"
                        href="#tab-invoice"
                        aria-selected={
                            hash === "#tab-invoice" ? "true" : "false"
                        }
                        role="tab"
                    >
                        Invoice
                    </a>
                </li>
                {/* <li className="nav-item" role="presentation">
                    <a
                        id="payment-tab"
                        className={
                            `nav-link ` +
                            (hash === "#tab-payment" ? "active" : "")
                        }
                        data-toggle="tab"
                        href="#tab-payment"
                        aria-selected={
                            hash === "#tab-payment" ? "true" : "false"
                        }
                        role="tab"
                    >
                        Payment
                    </a>
                </li> */}
                <li className="nav-item" role="presentation">
                    <a
                        id="creditCard-tab"
                        className={
                            `nav-link ` +
                            (hash === "#tab-creditCard" ? "active" : "")
                        }
                        data-toggle="tab"
                        href="#tab-creditCard"
                        aria-selected={
                            hash === "#tab-creditCard" ? "true" : "false"
                        }
                        role="tab"
                    >
                        Cards
                    </a>
                </li>
                <li className="nav-item" role="presentation">
                    <a
                        id="comments-tab"
                        className={
                            `nav-link ` +
                            (hash === "#tab-comments" ? "active" : "")
                        }
                        data-toggle="tab"
                        href="#tab-comments"
                        aria-selected={
                            hash === "#tab-comments" ? "true" : "false"
                        }
                        role="tab"
                    >
                        Comments
                    </a>
                </li>
            </ul>
            <div className="tab-content">
                <div
                    id="tab-schedule"
                    className={
                        `tab-pane ` +
                        (!hash || hash === "#tab-schedule" ? "active show" : "")
                    }
                    role="tab-panel"
                    aria-labelledby="schedule-meeting"
                >
                    <ScheduledMeeting />
                </div>
                <div
                    id="tab-offered"
                    className={
                        `tab-pane ` +
                        (hash === "#tab-offered" ? "active show" : "")
                    }
                    role="tab-panel"
                    aria-labelledby="offered-price"
                >
                    <OfferedPrice />
                </div>
                <div
                    id="tab-contract"
                    className={
                        `tab-pane ` +
                        (hash === "#tab-contract" ? "active show" : "")
                    }
                    role="tab-panel"
                    aria-labelledby="rejected-tab"
                >
                    <Contract
                        contracts={contracts}
                        setContracts={setContracts}
                        fetchContract={fetchContract}
                    />
                </div>
                <div
                    id="tab-jobs"
                    className={
                        `tab-pane ` +
                        (hash === "#tab-jobs" ? "active show" : "")
                    }
                    role="tab-panel"
                    aria-labelledby="rejected-tab"
                >
                    <Jobs contracts={contracts} client={client} />
                </div>
                {/* <div
                    id="tab-order"
                    className={
                        `tab-pane ` +
                        (hash === "#tab-order" ? "active show" : "")
                    }
                    role="tab-panel"
                    aria-labelledby="order-tab"
                >
                    <Order />
                </div> */}
                <div
                    id="tab-invoice"
                    className={
                        `tab-pane ` +
                        (hash === "#tab-invoice" ? "active show" : "")
                    }
                    role="tab-panel"
                    aria-labelledby="invoice-tab"
                >
                    <Invoice />
                </div>
                {/* <div
                    id="tab-payment"
                    className={
                        `tab-pane ` +
                        (hash === "#tab-payment" ? "active show" : "")
                    }
                    role="tab-panel"
                    aria-labelledby="payment-tab"
                >
                    <Payment />
                </div> */}
                <div
                    id="tab-creditCard"
                    className={
                        `tab-pane ` +
                        (hash === "#tab-creditCard" ? "active show" : "")
                    }
                    role="tab-panel"
                    aria-labelledby="creditCard-tab"
                >
                    <CardDetails
                        latestContract={latestContract}
                        client={client}
                    />
                </div>
                <div
                    id="tab-comments"
                    className={
                        `tab-pane ` +
                        (hash === "#tab-comments" ? "active show" : "")
                    }
                    role="tab-panel"
                    aria-labelledby="comments-tab"
                >
                    <Comments relationID={client.id} routeType="clients" />
                </div>
            </div>
        </div>
    );
}
