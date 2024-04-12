import React, { useMemo } from "react";
import { Link } from "react-router-dom";
import { useParams } from "react-router-dom";
import Moment from "moment";
import Notes from "./Notes";
import Files from "../Clients/Files";
import { useTranslation } from "react-i18next";

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
                <h1>
                    <span>#{lead.id}</span>{" "}
                    {lead.firstname + " " + lead.lastname}
                </h1>
                <div className="row">
                    <div className="col-sm-8">
                        <div className="ClientHistory dashBox p-4 min-414">
                            <ul className="nav nav-tabs" role="tablist">
                                <li className="nav-item" role="presentation">
                                    <a
                                        id="client-details"
                                        className="nav-link active"
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
                                        className="nav-link"
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
                                        className="nav-link"
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
                                        className="nav-link"
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
                                {/* <li className="nav-item" role="presentation"><a id="contact-details" className="nav-link" data-toggle="tab" href="#tab-contact" aria-selected="false" role="tab">First Contacted</a></li> */}
                            </ul>
                            <div className="tab-content">
                                <div
                                    id="tab-client-details"
                                    className="tab-pane active show"
                                    role="tab-panel"
                                    aria-labelledby="client-details"
                                >
                                    <div className="row">
                                        <div className="col-sm-6">
                                            <div className="form-group">
                                                <label>
                                                    {t("admin.global.Email")}
                                                </label>
                                                <p>{lead.email}</p>
                                            </div>
                                        </div>
                                        <div className="col-sm-6">
                                            <div className="form-group">
                                                <label>
                                                    {" "}
                                                    {t("admin.global.Phone")}
                                                </label>
                                                <p>
                                                    <a
                                                        href={`tel:${lead.phone}`}
                                                    >
                                                        {lead.phone}
                                                    </a>
                                                </p>
                                            </div>
                                        </div>
                                        {lead.lead_status && (
                                            <div className="col-sm-6">
                                                <div className="form-group">
                                                    <label>
                                                        {t(
                                                            "admin.global.Status"
                                                        )}
                                                    </label>
                                                    <p>
                                                        {
                                                            lead.lead_status
                                                                .lead_status
                                                        }
                                                    </p>
                                                </div>
                                            </div>
                                        )}
                                        <div className="col-sm-6">
                                            <div className="form-group">
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
                                            <div className="form-group">
                                                <label>
                                                    {" "}
                                                    {t(
                                                        "admin.leads.leadDetails.Meta"
                                                    )}
                                                </label>
                                                <p>{lead.meta}</p>
                                            </div>
                                        </div>

                                        <div className="col-sm-12">
                                            <div className="form-group">
                                                <p>
                                                    <Link
                                                        className="btn btn-success"
                                                        to={`/admin/edit-lead/${param.id}`}
                                                    >
                                                        {t(
                                                            "admin.leads.leadDetails.EditLead"
                                                        )}
                                                    </Link>
                                                </p>
                                            </div>
                                        </div>
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
                    <div className="col-sm-4">
                        <div className="dashBox p-4">
                            <div className="form-group">
                                <label className="d-block">
                                    {t(
                                        "admin.leads.leadDetails.convertToClient"
                                    )}
                                </label>
                                <Link
                                    to={`/admin/add-lead-client/${param.id}`}
                                    className="btn btn-pink addButton"
                                >
                                    <i className="btn-icon fas fa-plus-circle"></i>
                                    {t("admin.leads.leadDetails.Convert")}
                                </Link>
                            </div>
                        </div>

                        <div className="dashBox p-4 mt-3">
                            <div className="form-group">
                                <label className="d-block">
                                    {t("admin.leads.leadDetails.MeetingStatus")}
                                </label>
                                <span
                                    id="ms"
                                    className="dashStatus"
                                    style={{
                                        background: "#7e7e56",
                                        cursor: "pointer",
                                    }}
                                >
                                    {lead.latest_meeting
                                        ? lead.latest_meeting.booking_status
                                        : t("admin.leads.leadDetails.NotSend")}
                                </span>
                            </div>

                            <div className="form-group">
                                <label className="d-block">
                                    {" "}
                                    {t("admin.leads.leadDetails.PriceOffer")}
                                </label>
                                <span
                                    id="os"
                                    className="dashStatus"
                                    style={{
                                        background: "#7e7e56",
                                        cursor: "pointer",
                                    }}
                                >
                                    {lead.latest_offer
                                        ? lead.latest_offer.status
                                        : t("admin.leads.leadDetails.NotSend")}
                                </span>
                            </div>
                        </div>

                        <div className="buttonBlocks dashBox mt-3 p-4">
                            <Link to={`/admin/view-schedule/${param.id}`}>
                                <i className="fas fa-hand-point-right"></i>

                                {lead.meetings?.length == 0
                                    ? t(
                                          "admin.leads.leadDetails.ScheduleMeeting"
                                      )
                                    : t(
                                          "admin.leads.leadDetails.ReScheduleMeeting"
                                      )}
                            </Link>
                            <Link to={`/admin/offers/create?c=${param.id}`}>
                                <i className="fas fa-hand-point-right"></i>
                                {lead.offers?.length == 0
                                    ? t("admin.leads.leadDetails.SendOffer")
                                    : t("admin.leads.leadDetails.ReSendOffer")}
                            </Link>
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
        </>
    );
}
