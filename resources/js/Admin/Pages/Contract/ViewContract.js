import axios from "axios";
import i18next from "i18next";
import moment from "moment";
import React, { useEffect, useMemo, useState } from "react";
import { useTranslation } from "react-i18next";
import { Link, useParams } from "react-router-dom";

import companySign from "../../../Assets/image/company-sign.png";
import logo from "../../../Assets/image/sample.svg";
import FullPageLoader from "../../../Components/common/FullPageLoader";

export default function ViewContract() {
    const [lng, setLng] = useState("en");
    const [offer, setOffer] = useState(null);
    const [services, setServices] = useState([]);
    const [client, setClient] = useState(null);
    const [contract, setContract] = useState(null);
    const [signature, setSignature] = useState(null);
    const [cardSignature, setCardSignature] = useState(null);
    const [Aaddress, setAaddress] = useState(null);
    const [status, setStatus] = useState("");
    const [sessionURL, setSessionURL] = useState("");
    const [addCardBtnDisabled, setAddCardBtnDisabled] = useState(false);
    const [checkingForCard, setCheckingForCard] = useState(false);
    const [clientCards, setClientCards] = useState([]);
    const [selectedClientCardID, setSelectedClientCardID] = useState(null);
    const [isCardAdded, setIsCardAdded] = useState(false);
    const [consentToAds, setConsentToAds] = useState(true);
    const [signDate, setSignDate] = useState(moment().format("DD/MM/YYYY"));
    const [loading, setLoading] = useState(false);

    const { t } = useTranslation();
    const params = useParams();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };


    const getContract = () => {
        axios
            .post(`/api/admin/get-contract/${params.id}`, {}, { headers })
            .then((res) => {
                const _contract = res.data.contract;
                setOffer(_contract.offer);
                setServices(JSON.parse(_contract.offer.services));
                setClient(_contract.client);
                setContract(_contract);
                setStatus(_contract.status);
                setConsentToAds(_contract.consent_to_ads ? true : false);

                setClientCards([_contract?.card == null ? '' : _contract?.card]);
                setSelectedClientCardID(_contract.card_id);
                if (_contract.status != "not-signed") {
                    setIsCardAdded(true);
                }
                if (_contract.signed_at) {
                    setSignDate(
                        moment(_contract.signed_at).format("DD/MM/YYYY")
                    );
                }
                i18next.changeLanguage(_contract.client.lng);

                if (_contract?.client?.lng == "heb") {
                    import("../../../Assets/css/rtl.css").then(() => {
                        document.querySelector("html").setAttribute("dir", "rtl");
                    });
                } else {
                    document.querySelector("html").removeAttribute("dir");
                    const rtlLink = document.querySelector('link[href*="rtl.css"]');
                    if (rtlLink) {
                        rtlLink.remove();
                    }
                }
            });
    };



    const handleVerify = (e) => {
        e.preventDefault();
        setLoading(true);
        axios
            .post(`/api/admin/verify-contract`, { id: params.id }, { headers })
            .then((res) => {
                setLoading(false);
                swal(res.data.message, "", "success");
                setTimeout(() => {
                    window.location.reload(true);
                }, 1000);
            })
            .catch((e) => {
                setLoading(false);
                Swal.fire({
                    title: "Error!",
                    text: e.response.data.message,
                    icon: "error",
                });
            });
    };

    useEffect(() => {
        getContract();
    }, []);

    const workerHours = (_service) => {
        if (_service.type === "hourly") {
            return _service.workers.map((i) => i.jobHours).join(", ");
        }

        return "-";
    };

    const clientName = useMemo(() => {
        return client ? `${client.firstname} ${client.lastname}` : "";
    }, [client]);

    const showWorkerHours = useMemo(() => {
        return services.filter((i) => i.type !== "fixed").length > 0;
    }, [services]);

    const selectedClientCard = useMemo(() => {
        return clientCards != null ? clientCards.find((i) => i.id == selectedClientCardID) : clientCards;
    }, [clientCards, selectedClientCardID]);


    return (
        <div className="container parent">
            <div className="send-offer client-contract sendOfferRtl">
                <div className="maxWidthControl dashBox mb-4">
                    <div className="row border-bottom pb-2">
                        <div className="col-sm-6">
                            <h4 className="m-0">
                                {t("client.contract-form.business_name_value")}
                            </h4>
                            <p className="m-0">
                                {t("client.contract-form.h_p")} 515184208
                            </p>
                            <p className="m-0">
                                {t("client.contract-form.address")}:{" "}
                                {t("client.contract-form.address_value")}
                            </p>
                            <p className="m-0">
                                {t("client.contract-form.phone")}: 03-5257060
                            </p>
                            <p className="m-0">
                                {t("client.contract-form.email")}:
                                Office@broomservice.co.il
                            </p>
                        </div>
                        <div className="col-sm-6">
                            <div className="float-right">
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
                            </div>
                        </div>
                    </div>
                    <div className="row mt-4">
                        <div className="col-md-8 d-flex">
                            <label htmlFor="">
                                {t("client.contract-form.name")}:
                            </label>
                            <span className="text-underline mx-3">
                                {clientName}
                            </span>
                            <div className="mx-4">
                                <label htmlFor="">
                                    {t("client.contract-form.hp_em_tz")}:
                                </label>
                            </div>
                            <span className="text-underline mx-3">
                                {client ? client.vat_number : ""}
                            </span>
                        </div>
                        {contract && (
                            <div className="col-md-4">
                                {contract.status == "un-verified" && (
                                    <div className="col-sm-6">
                                        <div className="mt-2 float-right">
                                            <button
                                                type="button"
                                                className="btn btn-sm btn-warning"
                                                onClick={handleVerify}
                                            >
                                                Verify
                                            </button>
                                        </div>
                                    </div>
                                )}

                                {contract.status == "verified" && (
                                    <React.Fragment>
                                        <div className="col-sm-6">
                                            <div className="mt-2 float-right">
                                                <button
                                                    type="button"
                                                    className="btn btn-sm btn-success"
                                                >
                                                    Verified
                                                </button>
                                            </div>
                                        </div>

                                        <div className="col-sm-6">
                                            <div className="mt-2 float-left">
                                                <Link
                                                    to={`/admin/create-job/${contract.id}`}
                                                    className="btn btn-sm btn-pink"
                                                >
                                                    Create Job
                                                </Link>
                                            </div>
                                        </div>
                                    </React.Fragment>
                                )}
                            </div>
                        )}
                        <div className="col-md-12">
                            <label htmlFor="">
                                {t("client.contract-form.address")}:
                            </label>
                            {services.map((s, i) => (
                                    <span key={i} className="text-underline mx-3">
                                         {s.address && s.address.address_name
                                                            ? s.address.address_name
                                                            : "NA"}
                                    </span>
                                ))
                            }
                        </div>
                        <div className="col-md-12 d-flex">
                            <label htmlFor="">
                                {t("client.contract-form.phone")}:
                            </label>
                            <span className="text-underline mx-3">
                                {client ? client.phone : ""}
                            </span>
                            <span className="text-underline mx-3"></span>
                        </div>
                        <div className="col-md-12">
                            <label htmlFor="">
                                {t("client.contract-form.email")}:
                            </label>
                            <span className="text-underline mx-3">
                                {client ? client.email : ""}
                            </span>
                        </div>
                    </div>
                    <div className="row mt-4">
                        <div className="col-md-12">
                            <p className="text-center">
                                {t(
                                    "client.contract-form.contractual_agreement"
                                )}
                            </p>
                            <p>1. {t("client.contract-form.ca1")}</p>

                            <div
                                className="text-justify"
                                style={{ textIndent: "50px" }}
                            >
                                <p>
                                    1.1.{" "}
                                    {t("client.contract-form.ca1_1", {
                                        name: clientName,
                                    })}
                                </p>
                                <p>1.2. {t("client.contract-form.ca1_2")}</p>
                                <p>1.3. {t("client.contract-form.ca1_3")}</p>
                            </div>
                        </div>
                    </div>
                    <div className="row mt-4">
                        <div className="col-md-12">
                            <p>2. {t("client.contract-form.ca2")}</p>
                        </div>
                        <div className="col-md-12">
                            <div className="table-responsive">
                                <table className="table table-sm table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>
                                                {t("price_offer.address_text")}
                                            </th>
                                            <th>
                                                {t("price_offer.service_txt")}
                                            </th>
                                            <th>{t("price_offer.type")}</th>
                                            <th>
                                                {t("price_offer.freq_s_txt")}
                                            </th>
                                            {showWorkerHours && (
                                                <th>
                                                    {t(
                                                        "price_offer.worker_hours"
                                                    )}
                                                </th>
                                            )}
                                            <th>
                                                {t("price_offer.amount_txt")}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {services.map((s, i) => {
                                            console.log(s.address.address_name);

                                            return (
                                                <tr key={i}>
                                                    <td>
                                                        {s.address && s.address.address_name
                                                            ? s.address.address_name
                                                            : "NA"}
                                                    </td>
                                                    <td>
                                                        {s.service == 10
                                                            ? s.other_title
                                                            : s.name}
                                                    </td>
                                                    <td>{s.type}</td>
                                                    <td>{s.freq_name} </td>
                                                    {showWorkerHours && (
                                                        <td>
                                                            {workerHours(s)}
                                                        </td>
                                                    )}
                                                    {s.type == "fixed" ? (
                                                        <td>
                                                            {s.workers.length *
                                                                s.fixed_price}{" "}
                                                            {t(
                                                                "global.currency"
                                                            )}
                                                        </td>
                                                    ) : (
                                                        <td>
                                                            {s.rateperhour}{" "}
                                                            {t(
                                                                "global.currency"
                                                            )}{" "}
                                                            {t(
                                                                "global.perhour"
                                                            )}{" "}
                                                        </td>
                                                    )}
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>

                            <div style={{ textIndent: "50px" }}>
                                {t("client.contract-form.the_services")}
                            </div>
                        </div>
                    </div>
                    <div className="row mt-4">
                        <div className="col-md-12">
                            <p>3. {t("client.contract-form.ca3")}</p>
                            <div
                                className="text-justify"
                                style={{ textIndent: "50px" }}
                            >
                                <p>3.1. {t("client.contract-form.ca3_1")}</p>
                                <p>3.2. {t("client.contract-form.ca3_2")}</p>
                                <p>3.3. {t("client.contract-form.ca3_3")}</p>

                                <div className="mt-2">
                                    <p>
                                        {t(
                                            "client.contract-form.cc_card_charge_auth"
                                        )}
                                    </p>
                                    <p>
                                        {t(
                                            "client.contract-form.cc_declaration"
                                        )}
                                    </p>
                                    <p>
                                        {t("client.contract-form.cc_details")}
                                    </p>
                                    <p>
                                        {t("client.contract-form.cc_card_type")}
                                        :{" "}
                                        {selectedClientCard
                                            ? selectedClientCard.card_type
                                            : ""}
                                    </p>
                                    <p>
                                        {t(
                                            "client.contract-form.cc_holder_name"
                                        )}
                                        :{" "}
                                        {selectedClientCard
                                            ? selectedClientCard.card_holder_name
                                            : ""}
                                    </p>
                                    <p>
                                        {t("client.contract-form.cc_id_number")}
                                        :{" "}
                                        {selectedClientCard
                                            ? selectedClientCard.card_holder_id
                                            : ""}
                                    </p>
                                    <p>
                                        {t("client.contract-form.cc_signature")}
                                        :
                                    </p>
                                    <div>
                                        {contract && contract.signed_at && (
                                            <>
                                                {contract.form_data && (
                                                    <img
                                                        src={
                                                            contract.form_data
                                                                .card_signature
                                                        }
                                                    />
                                                )}
                                            </>
                                        )}
                                    </div>
                                    <p>
                                        {t(
                                            "client.contract-form.add_cc_click_here"
                                        )}
                                    </p>
                                    <p>
                                        {t(
                                            "client.contract-form.add_cc_click_here1"
                                        )}
                                    </p>

                                    {clientCards.length > 0 && clientCards.map((_card, _index) => {
                                        return (
                                            // <div>{_card}</div>
                                            <div className="my-3" key={_index}>
                                                <label className="form-check-label ">
                                                    <input
                                                        type="checkbox"
                                                        className="form-check-input"
                                                        value={_card.id}
                                                        checked={
                                                            _card.id ==
                                                            selectedClientCardID
                                                        }
                                                        disabled={
                                                            contract &&
                                                            contract.status !=
                                                                "not-signed"
                                                        }
                                                    />
                                                    **** **** ****{" "}
                                                    {_card.card_number} -{" "}
                                                    {_card.valid} (
                                                    {_card.card_type})
                                                </label>
                                            </div>
                                        );
                                    })}

                                    <p>
                                        {t(
                                            "client.contract-form.cc_compensation"
                                        )}
                                    </p>
                                </div>

                                <p>3.4. {t("client.contract-form.ca3_4")}</p>

                                <label
                                    className="form-check-label"
                                    style={{ fontWeight: 500 }}
                                >
                                    <input
                                        type="checkbox"
                                        className="form-check-input"
                                        checked={consentToAds}
                                        disabled={
                                            contract &&
                                            contract.status != "not-signed"
                                        }
                                    />
                                    3.5.{" "}
                                    {t(
                                        "client.contract-form.direct_mail_declaration"
                                    )}
                                </label>
                                <p>
                                    <strong>
                                        {t(
                                            "client.contract-form.direct_mail_note"
                                        )}
                                    </strong>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div className="row mt-4">
                        <div className="col-md-12">
                            <p>4. {t("client.contract-form.ca4")}</p>
                            {offer && <p>{offer.comment}</p>}
                            <p>
                                {t("client.contract-form.date")}: {signDate}
                            </p>
                        </div>
                    </div>
                    <div className="row mt-4">
                        <div className="col-md-12">
                            <p className="text-center">
                                {t("client.contract-form.general_terms")}
                            </p>
                            <p>
                                1.{" "}
                                <strong>
                                    {t("client.contract-form.gt1_title")}
                                </strong>{" "}
                                {t("client.contract-form.gt1")}
                            </p>
                            <p>
                                2.{" "}
                                <strong>
                                    {t("client.contract-form.gt2_title")}
                                </strong>{" "}
                                {t("client.contract-form.gt2")}
                            </p>
                            <p>
                                3.{" "}
                                <strong>
                                    {t("client.contract-form.gt3_title")}
                                </strong>{" "}
                                {t("client.contract-form.gt3")}
                            </p>
                            <p>
                                4.{" "}
                                <strong>
                                    {t("client.contract-form.gt4_title")}
                                </strong>{" "}
                                {t("client.contract-form.gt4")}
                            </p>
                            <p>
                                5.{" "}
                                <strong>
                                    {t("client.contract-form.gt5_title")}
                                </strong>{" "}
                                {t("client.contract-form.gt5")}
                            </p>
                            <p>
                                6.{" "}
                                <strong>
                                    {t("client.contract-form.gt6_title")}
                                </strong>{" "}
                                {t("client.contract-form.gt6")}
                            </p>
                            <p>
                                7.{" "}
                                <strong>
                                    {t("client.contract-form.gt7_title")}
                                </strong>{" "}
                                {t("client.contract-form.gt7")}
                            </p>
                            <p>
                                8.{" "}
                                <strong>
                                    {t("client.contract-form.gt8_title")}
                                </strong>{" "}
                                {t("client.contract-form.gt8")}
                            </p>
                            <p>
                                9.{" "}
                                <strong>
                                    {t("client.contract-form.gt9_title")}
                                </strong>{" "}
                                {t("client.contract-form.gt9_1")}
                            </p>
                            <p>{t("client.contract-form.gt9_2")}</p>
                            <p>{t("client.contract-form.gt9_3")}</p>
                            <p>
                                10.{" "}
                                <strong>
                                    {t("client.contract-form.gt10_title")}
                                </strong>{" "}
                                {t("client.contract-form.gt10")}
                            </p>
                        </div>
                    </div>
                    <div className="shift-30">
                        {/* <div className="agg-list">
                            <div className="icons">
                                <img src={star} />
                            </div>
                            <div className="agg-text">
                                <p>{t("work-contract.tenant_txt_1")}</p>
                            </div>
                        </div> */}
                        <div className="row">
                            <div className="col-sm-6">
                                <h5 className="mt-2 mb-4">
                                    {t("work-contract.the_tenant_subtitle")}
                                </h5>
                                <h6>{t("work-contract.draw_signature")}</h6>
                                {contract && contract.signature != null && (
                                    <img src={contract.signature} />
                                )}
                            </div>
                            <div className="col-sm-6">
                                <div className="float-right">
                                    <h5 className="mt-2 mb-4">
                                        {t("work-contract.the_company")}
                                    </h5>
                                </div>
                                <div className="float-right">
                                    <img
                                        src={companySign}
                                        className="img-fluid"
                                        alt="Company"
                                    />
                                </div>
                            </div>
                        </div>

                        <div className="mb-4">&nbsp;</div>
                    </div>
                </div>
            </div>
            <FullPageLoader visible={loading} />
        </div>
    );
}
