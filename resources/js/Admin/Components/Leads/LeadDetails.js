import React, { useMemo } from "react";
import { Link } from "react-router-dom";
import { useParams } from "react-router-dom";
import Moment from "moment";
import Notes from "./Notes";
import Files from "../Clients/Files";
import { useTranslation } from "react-i18next";
import { Tooltip } from "react-tooltip";
import LeadActivityList from "../../Pages/LeadActivity/ViewLeadActivity";

export default function LeadDetails({ lead }) {
    
    const { t } = useTranslation();
    const generatedOn = useMemo(() => {
        return (
            Moment(lead.created_at).format("DD/MM/Y") +
            " " +
            Moment(lead.created_at).format("dddd")
        );
    }, [lead.created_at]);

    const param = useParams();    

    return (
        <>
            <div className="client-view">
                <div className="d-flex align-items-center justify-content-between">
                    <h1 className="navyblueColor">
                        <span>#{lead.id}</span>{" "}
                        {lead.firstname + " " + lead.lastname}
                    </h1>
                    <div className=" p-4 d-flex align-items-center client-view-div1">
                        <div className="">
                            {/* <label className="d-block">
                                {t(
                                    "admin.leads.leadDetails.convertToClient"
                                )}
                            </label> */}
                            <Link
                                to={`/admin/add-lead-client/${param.id}`}
                                // to={`/admin/leads/${param.id}/edit`}
                                className="btn navyblue no-hover addButton mr-2"
                            >
                                <i className="btn-icon fas fa-plus-circle"></i>
                                {t(
                                    "admin.leads.leadDetails.convertToClient"
                                )}
                            </Link>
                        </div>
                        <div className="">
                            <div className="search-data">
                                <Link
                                    // to={`/admin/add-lead-client/${param.id}`}
                                    to={`/admin/leads/${param.id}/edit`}
                                    className="btn navyblue no-hover addButton"
                                >
                                    <i className="btn-icon fas fa-pencil"></i>
                                    {t("admin.global.Edit")}
                                </Link>
                            </div>
                        </div>
                    </div>

                </div>
                <div className="row d-inline">
                    <div className="">
                        <div className="ClientHistory  pl-4 pr-4">
                            <ul className="nav nav-tabs" role="tablist">
                                <li className="nav-item" role="presentation">
                                    <a
                                        id="client-details"
                                        className="nav-link active navyblueColor"
                                        data-toggle="tab"
                                        href="#tab-client-details"
                                        aria-selected="true"
                                        role="tab"
                                    >
                                        {t("admin.leads.leadDetails.LeadInfo")}
                                    </a>
                                </li>
                                <li className="nav-item" role="presentation">
                                    <a
                                        id="note-details"
                                        className="nav-link navyblueColor"
                                        data-toggle="tab"
                                        href="#tab-note-details"
                                        aria-selected="false"
                                        role="tab"
                                    >
                                        {t("admin.leads.leadDetails.Comments")}
                                    </a>
                                </li>
                                <li className="nav-item" role="presentation">
                                    <a
                                        id="files-tab"
                                        className="nav-link navyblueColor"
                                        data-toggle="tab"
                                        href="#tab-files"
                                        aria-selected="false"
                                        role="tab"
                                    >
                                        {t("admin.leads.leadDetails.Files")}
                                    </a>
                                </li>
                                <li className="nav-item" role="presentation">
                                    <a
                                        id="intrest-details"
                                        className="nav-link navyblueColor"
                                        data-toggle="tab"
                                        href="#tab-intrest"
                                        aria-selected="false"
                                        role="tab"
                                    >
                                        {t(
                                            "admin.leads.leadDetails.IntrestedIn"
                                        )}
                                    </a>
                                </li>
                                <li className="nav-item" role="presentation">
                                    <a
                                        id="activities-details"
                                        className="nav-link navyblueColor"
                                        data-toggle="tab"
                                        href="#tab-activities"
                                        aria-selected="false"
                                        role="tab"
                                    >
                                        {t(
                                            "admin.leads.leadDetails.Activities"
                                        )}
                                    </a>
                                </li>
                                {/* <li className="nav-item" role="presentation"><a id="contact-details" className="nav-link" data-toggle="tab" href="#tab-contact" aria-selected="false" role="tab">First Contacted</a></li> */}
                            </ul>
                            <div className="tab-content border-0">
                                <div
                                    id="tab-client-details"
                                    className="tab-pane active show"
                                    role="tab-panel"
                                    aria-labelledby="client-details"
                                >
                                    <h5 className="navyblueColor">{t("admin.leads.leadDetails.LeadInfo")}</h5>

                                    <div className="row mt-3">
                                        <div className="col-xl-4">
                                            <div className="form-group navyblueColor">
                                                <label>
                                                    {t("admin.global.Email")}
                                                </label>
                                                <p>{lead.email}</p>
                                            </div>
                                        </div>
                                        <div className="col-xl-6">
                                            <div className="form-group navyblueColor">
                                                <label>
                                                    {" "}
                                                    {t("admin.global.Phone")}
                                                </label>
                                                <p>
                                                    <a
                                                        href={`tel:+${lead.phone}`}
                                                    >
                                                        +{lead.phone}
                                                    </a>
                                                </p>
                                            </div>
                                        </div>
                                        {lead.lead_status && (
                                            <div className="col-xl-4">
                                                <div className="form-group navyblueColor">
                                                    <label>
                                                        {t(
                                                            "admin.global.Status"
                                                        )}
                                                    </label>

                                                    {lead.latest_log &&
                                                        lead.latest_log[0] ? (
                                                        <p
                                                            data-tooltip-id="status-tooltip"
                                                            data-tooltip-content={`Reason : ${lead
                                                                .latest_log[0]
                                                                .reason
                                                                } on ${Moment(
                                                                    lead
                                                                        .latest_log[0]
                                                                        .created_at
                                                                ).format(
                                                                    "DD/MM/Y"
                                                                )}`}
                                                        >
                                                            {
                                                                lead.lead_status
                                                                    .lead_status
                                                            }
                                                        </p>
                                                    ) : (
                                                        <p>
                                                            {
                                                                lead.lead_status
                                                                    .lead_status
                                                            }
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                        )}
                                        <div className="col-xl-6">
                                            <div className="form-group navyblueColor">
                                                <label>
                                                    {" "}
                                                    {t(
                                                        "admin.leads.leadDetails.GeneratedOn"
                                                    )}
                                                </label>
                                                <p>{generatedOn}</p>
                                            </div>
                                        </div>
                                        <div className="col-sm-12">
                                            <div className="form-group navyblueColor">
                                                <label>
                                                    {" "}
                                                    {t(
                                                        "admin.leads.leadDetails.Meta"
                                                    )}
                                                </label>
                                                <p>{lead.meta}</p>
                                            </div>
                                        </div>

                                        {/* <div className="col-sm-12">
                                            <div className="form-group navyblueColor">
                                                <p>
                                                    <Link
                                                        className="btn navyblue"
                                                        to={`/admin/leads/${param.id}/edit`}
                                                    >
                                                        {t(
                                                            "admin.leads.leadDetails.EditLead"
                                                        )}
                                                    </Link>
                                                </p>
                                            </div>
                                        </div> */}
                                    </div>
                                </div>
                                <div
                                    id="tab-note-details"
                                    className="tab-pane"
                                    role="tab-panel"
                                    aria-labelledby="card-details"
                                >
                                    <div className="form-group">
                                        <Notes />
                                    </div>
                                </div>

                                <div
                                    id="tab-files"
                                    className="tab-pane"
                                    role="tab-panel"
                                    aria-labelledby="rejected-tab"
                                >
                                    <Files />
                                </div>

                                <div
                                    id="tab-intrest"
                                    className="tab-pane"
                                    role="tab-panel"
                                    aria-labelledby="card-details"
                                >

                                    {lead.reply ? (
                                        <div className="form-group">
                                            <div className="col-sm-6">
                                                <div className="form-group">
                                                    <label>
                                                        {" "}
                                                        {t(
                                                            "admin.leads.leadDetails.Option"
                                                        )}
                                                    </label>

                                                    <p>
                                                        {lead.reply
                                                            ? lead.reply.message
                                                                .length < 2
                                                                ? lead.reply
                                                                    .message
                                                                : "Chat"
                                                            : ""}
                                                    </p>
                                                </div>
                                            </div>
                                            <div className="col-sm-12">
                                                <div className="form-group">
                                                    <label>
                                                        {" "}
                                                        {t(
                                                            "admin.leads.leadDetails.Message"
                                                        )}
                                                    </label>
                                                    <p>{lead.reply?.msg}</p>
                                                </div>
                                            </div>
                                        </div>
                                    ) : (
                                        <p className="text-center form-control">
                                            {t(
                                                "admin.leads.leadDetails.dataNotAvailable"
                                            )}
                                        </p>
                                    )}
                                </div>

                                <div
                                    id="tab-activities"
                                    className="tab-pane"
                                    role="tab-panel"
                                    aria-labelledby="card-details"
                                >
                                    <div className="form-group">
                                        <LeadActivityList />
                                    </div>
                                </div>

                                {/* <div id="tab-contact" className="tab-pane" role="tab-panel" aria-labelledby="card-details">
                                   
                                    { lead.reply ? (<div className='form-group'>
                                    <div className='col-sm-6'>
                                            <div className='form-group'>
                                                <label>Option</label>

                                                <p>{  lead.reply? ( (lead.reply.message.length < 2) ? lead.reply.message : 'Chat' ) : '' }</p>
                                            </div>
                                        </div>
                                        <div className='col-sm-12'>
                                            <div className='form-group'>
                                                <label>Message</label>
                                                <p>{lead.reply?.msg}</p>
                                            </div>
                                        </div>

                                    </div>)
                                    :(
                                        <p className='text-center form-control'>Data not availabe.</p>
                                    )
                                    
                                }
                                    
                                </div> */}
                            </div>
                        </div>
                    </div>
                    <div className="col-lg-4 col-12 mt-3 mt-lg-0">

                        <div className="buttonBlocks mt-3 p-4 d-none">
                            <Link
                                to={`/admin/create-client-job/${param.id}`}
                                id="bookBtn"
                                style={{ display: "none" }}
                            >
                                <i className="fas fa-hand-point-right"></i>
                                {t("admin.leads.leadDetails.BookClient")}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
            <Tooltip id="status-tooltip" />
        </>
    );
}
