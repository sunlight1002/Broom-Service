import React, { useEffect, useState } from "react";
import Sidebar from "../../Layouts/Sidebar";
import logo from "../../../Assets/image/sample.svg";
import { Modal, Button } from 'react-bootstrap';

import Dropdown from "react-bootstrap/Dropdown";
import { Link, useParams } from "react-router-dom";
import Moment from "moment";
import { workerHours } from "../../../Utils/common.utils";
import { useTranslation } from "react-i18next";

export default function ViewOffer({ showModal, handleClose, offerId }) {
    const { t } = useTranslation();
    const [offer, setOffer] = useState([]);
    const [perhour, setPerHour] = useState(0);
    const param = useParams();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getOffer = () => {
        axios.get(`/api/admin/offers/${offerId}`, { headers }).then((res) => {
            let ar = [];
            ar.push(res.data.offer);
            setPerHour(res.data.offer.perhour);
            setOffer(ar);
        });
    };

    useEffect(() => {
        getOffer();
    }, []);

    return (
        <div id="container">
            <Modal
                size="lg"
                className="modal-container"
                dialogClassName="custom-modal-viewOffer"
                show={showModal} onHide={handleClose}>
                <Modal.Header closeButton
                    className="border-0"
                >
                    <Modal.Title>
                        <h1 className="page-title addEmployer border-0 mb-0 ml-2 navyblueColor">View Offer</h1>
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <div>
                        <div className="card" style={{ boxShadow: "none" }}>
                            {offer &&
                                offer.map((ofr, i) => {
                                    let cl = ofr.client;
                                    let services = ofr.services
                                        ? JSON.parse(ofr.services)
                                        : "";
                                    return (
                                        <div className="ViewOffer pt-0 pb-0" key={i}>
                                            <svg
                                                width="190"
                                                height="77"
                                                xmlns="http://www.w3.org/2000/svg"
                                                xmlnsXlink="http://www.w3.org/1999/xlink"
                                            >
                                                <image
                                                    xlinkHref={logo}
                                                    width="190"
                                                    height="77"
                                                ></image>
                                            </svg>
                                            <div className=" ViewOffer-box d-flex w-100" style={{ marginTop: "40px" }}>
                                                <div className="vbox1" style={{ border: "1px solid #E5EBF1", borderRadius: "5px", backgroundColor: "#FAFBFC", padding: "0px 18px 17px", marginRight: "20px" }}>
                                                    <h2>Broom Service</h2>
                                                    <p>
                                                        {t("worker_contract.company_address")}
                                                    </p>
                                                    <p>Israel</p>
                                                    <p>
                                                        {t("client.offer.view.phone")}:{" "}
                                                        <span>
                                                            +97235257060
                                                        </span>
                                                    </p>
                                                    <p>
                                                    {t("client.offer.view.email")}:{" "}
                                                        <span>
                                                            office@broomservice.co.il
                                                        </span>
                                                    </p>
                                                </div>
                                                <div className="vbox1" style={{ border: "1px solid #E5EBF1", borderRadius: "5px", backgroundColor: "#FAFBFC", padding: "0px 18px 17px", marginRight: "20px" }}>
                                                    <h2>{t("global.to")}</h2>
                                                    <p>
                                                        {cl.firstname +
                                                            " " +
                                                            cl.lastname}
                                                    </p>
                                                    {/* <p>{cl.street_n_no}</p>
                                                    <p>
                                                        {cl.city +
                                                            ", " +
                                                            cl.zipcode}
                                                    </p> */}
                                                    <p>
                                                    {t("client.offer.view.phone")}:{" "}
                                                        <span>{cl.phone}</span>
                                                    </p>
                                                    <p>
                                                    {t("client.offer.view.email")}:{" "}
                                                        <span>{cl.email}</span>
                                                    </p>
                                                </div>
                                                <div className="vbox1" style={{ border: "1px solid #E5EBF1", borderRadius: "5px", backgroundColor: "#FAFBFC", padding: "0px 18px 17px" }}>
                                                    <h2>{t("client.offer.view.ofr_price")}</h2>
                                                    <p>
                                                        <b>{t("client.offer.view.ofr_id")}: </b>
                                                        <span> {ofr.id}</span>
                                                    </p>
                                                    <p>
                                                        <b>{t("client.offer.view.date")}: </b>
                                                        <span>
                                                            {" "}
                                                            {Moment(
                                                                ofr.created_at
                                                            ).format(
                                                                "MMMM DD,Y"
                                                            )}
                                                        </span>
                                                    </p>
                                                    <div className="sent-status">
                                                        <p>{ofr.status}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="card card-dark" style={{ boxShadow: "none", backgroundColor: "#FFFFFF" }}>
                                                <div className="card-header border-0 card-black">
                                                    <h3 className="card-title mb-0 navyblueColor">
                                                    {t("client.offer.view.services")}
                                                    </h3>
                                                </div>
                                                <div className="card-body">
                                                    <div className="table-responsive">
                                                        <table className="table table-md navyblueColor">
                                                            <thead>
                                                                <tr>
                                                                    <th style={{ width: "30%" }}>{t("client.offer.view.address")}</th>
                                                                    <th style={{ width: "20%" }}>{t("client.offer.view.service")}</th>
                                                                    <th>{t("price_offer.type")}</th>
                                                                    <th className="text-right">{t("client.offer.view.frequency")}</th>
                                                                    <th className="text-right">{t("client.offer.view.job_hr")}</th>
                                                                    {ofr.type === "fixed" && (
                                                                        <th className="text-right">{t("admin.leads.AddLead.AddLeadClient.jobMenu.Price")}</th>
                                                                    )}
                                                                    {perhour !== 0 && (
                                                                        <th className="text-right">{t("client.offer.view.rate_ph")}</th>
                                                                    )}
                                                                    {ofr.type !== "fixed" && (
                                                                        <th className="text-right">{t("client.offer.view.total_amt")}</th>
                                                                    )}
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                {services && services.map((s, i) => (
                                                                    <tr key={i}>
                                                                        <td>
                                                                            {s.address ? (
                                                                                <a href={`https://maps.google.com?q=${s.address.geo_address}`} target="_blank" rel="noopener noreferrer">
                                                                                    {s.address.address_name}
                                                                                </a>
                                                                            ) : (
                                                                                "NA"
                                                                            )}
                                                                        </td>
                                                                        <td>{s.service === 10 ? s.other_title : s.name}</td>
                                                                        <td>{s.type}</td>
                                                                        <td className="text-right">{s.freq_name}</td>
                                                                        <td className="text-right">{workerHours(s, "hour(s)")}</td>
                                                                        {s.type !== "fixed" || perhour === 1 ? (
                                                                            <>
                                                                                <td className="text-right">
                                                                                    {s.rateperhour ? `${s.rateperhour} ILS` : "--"}
                                                                                </td>
                                                                                <td className="text-right">{`${s.totalamount} ILS`}</td>
                                                                            </>
                                                                        ) : (
                                                                            <td className="text-right">{`${s.fixed_price} ILS`}</td>
                                                                        )}
                                                                    </tr>
                                                                ))}
                                                            </tbody>
                                                        </table>
                                                    </div>

                                                    <div className="row">
                                                        <div className="col-sm-6"></div>
                                                        <div className="col-sm-6">
                                                            <div className="table-responsive">
                                                                <table className="table table-sm table-bordered ">
                                                                    <tfoot>
                                                                        <tr>
                                                                            <td
                                                                                width="65%"
                                                                                className="text-right"
                                                                                style={{ borderRight: "none", borderLeft: "none" }}
                                                                            >
                                                                                {t("client.offer.total")}
                                                                            </td>
                                                                            <td className="text-right"
                                                                                style={{ borderRight: "none", borderLeft: "none" }}
                                                                            >
                                                                                <span>
                                                                                    {
                                                                                        ofr.subtotal
                                                                                    }{" "}
                                                                                    ILS
                                                                                    +
                                                                                    VAT{" "}
                                                                                </span>
                                                                            </td>
                                                                        </tr>
                                                                    </tfoot>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                        </div>
                    </div>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={handleClose}>{t("global.close")}</Button>
                </Modal.Footer>
            </Modal>
        </div>
    );
}
