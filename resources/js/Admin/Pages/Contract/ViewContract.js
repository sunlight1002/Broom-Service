import axios from "axios";
import i18next from "i18next";
import moment from "moment";
import React, { useEffect, useMemo, useState } from "react";
import { useTranslation } from "react-i18next";
import { useParams } from "react-router-dom";

import companySign from "../../../Assets/image/company-sign.png";
import logo from "../../../Assets/image/sample.svg";
import FullPageLoader from "../../../Components/common/FullPageLoader";
import useWindowWidth from "../../../Hooks/useWindowWidth";

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
    const windowWidth = useWindowWidth();
    const [mobileView, setMobileView] = useState(false);
    const [nextStep, setNextStep] = useState(1);

    const { t } = useTranslation();
    const params = useParams();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    useEffect(() => {
        if (windowWidth < 767) {
            setMobileView(true)
        } else {
            setMobileView(false)
        }
    }, [windowWidth])


    const getContract = () => {
        axios
            .post(`/api/admin/get-contract/${params.id}`, {}, { headers })
            .then((res) => {
                console.log(res.data);

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
                setLng(_contract.client.lng);

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


    const handleNextPrev = (e) => {
        window.scrollTo(0, 0);
        if (e.target.name === "prev") {
            setNextStep(prev => prev - 1);
        } else {
            setNextStep(prev => prev + 1);
        }

    }


    return (
        <div className="parent"
            style={{
                margin: mobileView ? "0 10px" : "auto",
                maxWidth: "800px"
            }}
        >
            <div className="send-offer client-contract sendOfferRtl">
                <div className="navyblueColor mb-5">
                    <div className=" mt-4 mb-5 bg-transparent " style={{
                        margin: mobileView ? "0 20px" : "0"
                    }}>
                        {
                            !mobileView && (
                                <div className="d-flex align-items-center justify-content-between flex-dir-co-1070">
                                    <img
                                        src={logo}
                                        className="img-fluid broom-logo"
                                        alt="Broom Services"
                                        style={{ height: "100px" }}
                                    />
                                </div>
                            )
                        }
                        <div>
                            <section className="d-flex align-items-center">
                                <p className="navyblueColor font-34 mt-4 font-w-500"> {t("client.contract-form.business_name_value")}</p>
                            </section>
                            <section className="d-flex flex-column ">
                                <div className="d-flex align-items-center flex-wrap" style={{
                                    gap: mobileView ? "0" : "30px"
                                }}>                                    <p className={`navyblueColor font-15 ${mobileView ? "mr-3" : ""}`}>
                                        {t("client.contract-form.name")}:&nbsp;
                                        <b>{clientName}</b>
                                    </p>
                                    <p className="navyblueColor font-15 m-0">
                                        {t("client.contract-form.hp_em_tz")}:&nbsp;
                                        <b>{client ? client.vat_number : ""}</b>
                                    </p>
                                </div>
                                <div className={`d-flex justify-content-between ${mobileView ? "flex-column" : "align-items-center"} `}>
                                    <div className="d-flex align-items-center flex-wrap" style={{
                                        gap: mobileView ? "0" : "30px"
                                    }}>
                                        <p className={`navyblueColor font-15 ${mobileView ? "mr-3" : ""}`}>
                                            {t("client.contract-form.phone")}:&nbsp;
                                            <b>{client ? client.phone : ""}</b>
                                        </p>
                                        <p className="navyblueColor font-15 m-0">
                                            {t("client.contract-form.email")}:&nbsp;
                                            <b>{client ? client.email : ""}</b>
                                        </p>
                                    </div>
                                    {contract && (
                                        <div className="d-flex align-items-center">
                                            {contract.status == "un-verified" && (
                                                <div className="col-sm-6">
                                                    <div className="mt-2 float-right">
                                                        <button
                                                            type="button"
                                                            className="btn px-3 py-2 navyblue"
                                                            onClick={handleVerify}
                                                        >
                                                            {t("common.verify")}
                                                        </button>
                                                    </div>
                                                </div>
                                            )}

                                            {contract.status == "verified" && (
                                                <React.Fragment>
                                                    <div className="mt-2 mx-2">
                                                        <button
                                                            type="button"
                                                            className="btn px-3 py-2 navyblue"
                                                        >
                                                            {t("common.verified")}

                                                        </button>
                                                    </div>

                                                    <div className="mt-2 mx-2">
                                                        <a
                                                            href={`/admin/create-job/${contract.id}`}
                                                            className="btn px-3 py-2 navyblue no-hover"
                                                        >
                                                            {t("common.create_job")}

                                                        </a>
                                                    </div>
                                                </React.Fragment>
                                            )}
                                        </div>
                                    )}
                                </div>
                            </section>
                        </div>
                    </div>
                    <div className={`row mt-4 ${!mobileView && "m-4"}`}>
                        <div className=" mt-3">
                            <section className="col-xl">
                                <div className="abt">
                                    <h5 className="mb-2">{t("client.contract-form.contractual_agreement")}</h5>
                                </div>
                                <p className="mt-3" style={{ whiteSpace: "pre-wrap" }}><strong>1.</strong> {t("client.contract-form.ca1")}</p>
                                <div className="text-justify m-2" >
                                    <p>
                                        <b className="font-w-500">1.1.</b>{" "}
                                        {t("client.contract-form.ca1_1", { name: clientName, })}
                                    </p>
                                    <p><b className="font-w-500">1.2.</b> {t("client.contract-form.ca1_2")}</p>
                                    <p><b className="font-w-500">1.3.</b> {t("client.contract-form.ca1_3")}</p>
                                </div>
                                <div className="row mt-4">
                                    <div className="col-md-12">
                                        <p><strong>2.</strong> {t("client.contract-form.ca2")}</p>
                                    </div>
                                    <div className="col-md-12 mt-2" id="priceOfferTable">
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
                                                                {t("price_offer.worker_hours")}
                                                            </th>
                                                        )}
                                                        <th>
                                                            {t("price_offer.amount_txt")}
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {services.map((s, i) => {

                                                        const serviceName = s.template === "others"
                                                            ? s.other_title
                                                            : client?.lng === 'heb'
                                                                ? s.service_name_heb
                                                                : s.service_name_en;

                                                        const subServiceName = client?.lng === 'heb'
                                                            ? s.sub_services?.subServices?.name_heb
                                                            : s.sub_services?.subServices?.name_en;

                                                        return (
                                                            <tr key={i}>
                                                                {
                                                                    s.template === "airbnb" ? (
                                                                        <td>{s.sub_services?.address_name}</td>
                                                                    ) : (
                                                                        <td>
                                                                            {s.address &&
                                                                                s.address
                                                                                    .address_name
                                                                                ? s.address
                                                                                    .address_name
                                                                                : "NA"}
                                                                        </td>
                                                                    )
                                                                }
                                                                <td>
                                                                    {s.template === "airbnb"
                                                                        ? `${serviceName} - ${subServiceName}`
                                                                        : serviceName}
                                                                </td>
                                                                <td>{s.type == "fixed" ? t("admin.leads.AddLead.Options.Type.Fixed") : s.type == "hourly" ? t("admin.leads.AddLead.Options.Type.Hourly") : t("admin.leads.AddLead.Options.Type.Squaremeter")}</td>
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
                                        <div >
                                            {t("client.contract-form.the_services")}
                                        </div>
                                    </div>
                                </div>
                                <div className="row mt-4">
                                    <div className="col-md-12" style={{ lineHeight: "21px" }}>
                                        <p><strong>3.</strong> {t("client.contract-form.ca3")}</p>
                                        <div className="text-justify mt-3 mb-2 ml-2">
                                            <p><b className="font-w-500">3.1.</b> {t("client.contract-form.ca3_1")}</p>
                                            <p><b className="font-w-500">3.2.</b> {t("client.contract-form.ca3_2")}</p>
                                            <p><b className="font-w-500">3.3.</b> {t("client.contract-form.ca3_3")}</p>

                                            <div className="mt-2">
                                                <p>
                                                    {t("client.contract-form.cc_card_charge_auth")}
                                                </p>
                                                <p>
                                                    {t("client.contract-form.cc_declaration")}
                                                </p>
                                                <p className="font-w-500">
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
                                                    {t("client.contract-form.cc_holder_name")}
                                                    :{" "}
                                                    {selectedClientCard
                                                        ? selectedClientCard.card_holder_name
                                                        : ""}
                                                </p>
                                                {/* <p>
                                                    {t("client.contract-form.cc_id_number")}
                                                    :{" "}
                                                    {selectedClientCard
                                                        ? selectedClientCard.card_holder_id
                                                        : ""}
                                                </p> */}
                                                <p> {t("client.contract-form.cc_signature")} :</p>
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

                                                <p>{t("client.contract-form.add_cc_click_here")} </p>
                                                <p> {t("client.contract-form.add_cc_click_here1")} </p>

                                                {clientCards.map((_card, _index) => {
                                                    return (
                                                        <div className="my-3" key={_index}>
                                                            <label className="custom-checkbox" style={{ fontSize: "14px" }}>
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
                                                                <span className="checkmark"></span>
                                                                **** **** ****{" "}
                                                                {_card.card_number} -{" "}
                                                                {_card.valid} (
                                                                {_card.card_type})
                                                            </label>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                            <section className="col px-3">
                                <p className="mb-3">
                                    {t("client.contract-form.cc_compensation")}
                                </p>
                                <p><b className="font-w-500">3.4.</b> {t("client.contract-form.ca3_4")}</p>

                                <label
                                    className="form-check-label mx-2 mt-2 custom-checkbox navyblueColor"
                                    style={{ fontSize: "14px" }}
                                >
                                    <input
                                        type="checkbox"
                                        className="form-check-input"
                                        defaultChecked={consentToAds}
                                        disabled={
                                            contract &&
                                            contract.status != "not-signed"
                                        }
                                    />
                                    <span className="checkmark"></span>

                                    <b className="font-w-500">3.5.</b>{" "}
                                    {t(
                                        "client.contract-form.direct_mail_declaration"
                                    )}
                                </label>
                                <p>
                                    <b className="font-w-500">
                                        {t(
                                            "client.contract-form.direct_mail_note"
                                        )}
                                    </b>
                                </p>
                                <div className="row mt-4">
                                    <div className="col-md-12">
                                        <p><strong>4.</strong>  <b className="font-w-500">{t("client.contract-form.ca4")}</b></p>
                                        {offer && <b className="font-w-500">{offer.comment}</b>}
                                        <p>
                                            {t("client.contract-form.date")}: <b className="font-w-500">{signDate}</b>
                                        </p>
                                    </div>
                                </div>
                                <div className="row mt-4">
                                    <div className="col-md-12" style={{ lineHeight: "21px" }}>
                                        <h5 className="text-left mb-3">
                                            {t("client.contract-form.general_terms")}
                                        </h5>
                                        <p>
                                            <strong>1.</strong>{" "}
                                            <b className="font-w-500">
                                                {t("client.contract-form.gt1_title")}
                                            </b>{" "}
                                            {t("client.contract-form.gt1")}
                                        </p>
                                        <p>
                                            <strong>2.</strong>{" "}
                                            <b className="font-w-500">
                                                {t("client.contract-form.gt2_title")}
                                            </b>{" "}
                                            {t("client.contract-form.gt2")}
                                        </p>
                                        <p>
                                            <strong>3.</strong>{" "}
                                            <b className="font-w-500">
                                                {t("client.contract-form.gt3_title")}
                                            </b>{" "}
                                            {t("client.contract-form.gt3")}
                                        </p>
                                        <p>
                                            <strong>4.</strong>{" "}
                                            <b className="font-w-500">
                                                {t("client.contract-form.gt4_title")}
                                            </b>{" "}
                                            {t("client.contract-form.gt4")}
                                        </p>
                                        <p>
                                            <strong>5.</strong>{" "}
                                            <b className="font-w-500">
                                                {t("client.contract-form.gt5_title")}
                                            </b>{" "}
                                            {t("client.contract-form.gt5")}
                                        </p>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <div className=" mt-3">
                            <section className="col-xl">
                                <div className="row mt-4">
                                    <div className="col-md-12" style={{ lineHeight: "21px" }}>
                                        <p>
                                            <strong>6.</strong>{" "}
                                            <b className="font-w-500">
                                                {t("client.contract-form.gt6_title")}
                                            </b>{" "}
                                            {t("client.contract-form.gt6")}
                                        </p>
                                        <p>
                                            <strong>7.</strong>{" "}
                                            <b className="font-w-500">
                                                {t("client.contract-form.gt7_title")}
                                            </b>{" "}
                                            {t("client.contract-form.gt7")}
                                        </p>
                                        <p>
                                            <strong>8.</strong>{" "}
                                            <b className="font-w-500">
                                                {t("client.contract-form.gt8_title")}
                                            </b>{" "}
                                            {t("client.contract-form.gt8")}
                                        </p>
                                        <p>
                                            <strong>9.</strong>{" "}
                                            <b className="font-w-500">
                                                {t("client.contract-form.gt9_title")}
                                            </b>{" "}
                                            {t("client.contract-form.gt9_1")}
                                        </p>
                                        <p>{t("client.contract-form.gt9_2")}</p>
                                        <p>{t("client.contract-form.gt9_3")}</p>
                                        <p>
                                            <strong>10.</strong>{" "}
                                            <b className="font-w-500">
                                                {t("client.contract-form.gt10_title")}
                                            </b>{" "}
                                            {t("client.contract-form.gt10")}
                                        </p>
                                    </div>
                                </div>
                            </section>
                            <section className="col px-3">
                                <div className="shift-30">
                                    <div className="row">
                                        <div className="col-sm-6">
                                            <h5 className="mt-2 mb-4">
                                                {t("work-contract.the_tenant_subtitle")}
                                            </h5>
                                            <h6 className="mb-2">{t("work-contract.draw_signature")}</h6>
                                            {contract && contract.signature != null && (
                                                <img src={contract.signature} />
                                            )}
                                        </div>
                                        <div className="col-sm-6">
                                            <div className="text-right">
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
                            </section>
                        </div>
                    </div>

                    {loading && <FullPageLoader visible={loading} />}
                </div>
            </div>
        </div>
    );
}
