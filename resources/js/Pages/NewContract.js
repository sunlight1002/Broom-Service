import React, { useRef, useState, useEffect, useMemo } from "react";
import logo from "../Assets/image/sample.svg";
import star from "../Assets/image/icons/blue-star.png";
import SignatureCanvas from "react-signature-canvas";
import companySign from "../Assets/image/company-sign.png";
import axios from "axios";
import { useParams } from "react-router-dom";
import swal from "sweetalert";
import { useTranslation } from "react-i18next";
import i18next from "i18next";
import Swal from "sweetalert2";
import moment from "moment";
import FullPageLoader from "../Components/common/FullPageLoader";
import useWindowWidth from "../Hooks/useWindowWidth";
import { GrFormNextLink, GrFormPreviousLink } from "react-icons/gr";

export default function WorkContract() {
    const { t } = useTranslation();

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
    const [loading, setLoading] = useState(false)
    const windowWidth = useWindowWidth();
    const [mobileView, setMobileView] = useState(false);
    const params = useParams();
    const sigRef1 = useRef();
    const sigRef2 = useRef();
    const [isSubmitted, setIsSubmitted] = useState(false);

    useEffect(() => {
        if (windowWidth < 767) {
            setMobileView(true)
        } else {
            setMobileView(false)
        }
    }, [windowWidth])

    const handleAccept = (e) => {
        if (!selectedClientCardID) {
            swal(t("work-contract.messages.card_err"), "", "error");
            return false;
        }

        if (!signature) {
            swal(t("work-contract.messages.sign_err"), "", "error");
            return false;
        }
        if (!cardSignature) {
            swal(t("work-contract.messages.sign_card_err"), "", "error");
            return false;
        }

        // setLoading(true);

        const data = {
            unique_hash: params.id,
            offer_id: offer.id,
            client_id: offer.client.id,
            card_id: selectedClientCardID,
            additional_address: Aaddress,
            status: "un-verified",
            signature: signature,
            consent_to_ads: consentToAds ? 1 : 0,
            form_data: { card_signature: cardSignature },
        };
        setIsSubmitted(true);

        axios
            .post(`/api/client/new-accept-contract`, data)
            .then((res) => {
                setLoading(false);
                if (res.data.error) {
                    setIsSubmitted(false);
                    swal("", res.data.error, "error");
                } else {
                    setStatus("un-verified");
                    swal(t("work-contract.messages.success"), "", "success");
                    setTimeout(() => {
                        window.location.reload(true);
                    }, 2000);
                }
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

    const handleSignature1End = () => {
        setSignature(sigRef1.current.toDataURL());
    };

    const clearSignature1 = () => {
        sigRef1.current.clear();
        setSignature(null);
    };

    const handleSignature2End = () => {
        setCardSignature(sigRef2.current.toDataURL());
    };

    const clearSignature2 = () => {
        sigRef2.current.clear();
        setCardSignature(null);
    };

    const getOffer = () => {
        axios
            .post(`/api/client/contracts/${params.id}`)
            .then((res) => {
                if (res.data.offer) {
                    const _contract = res.data.contract;
                    setOffer(res.data.offer);
                    setServices(JSON.parse(res.data.offer.services));
                    setClient(res.data.offer.client);
                    setContract(_contract);
                    setIsSubmitted((_contract?.status == "un-verified" || contract?.status == "verified") ? true : false);
                    setStatus(_contract.status);
                    setConsentToAds(_contract.consent_to_ads ? true : false);

                    setClientCards(res.data.cards);
                    setSelectedClientCardID(_contract.card_id);
                    if (_contract.status != "not-signed") {
                        setIsCardAdded(true);
                    }
                    if (_contract.signed_at) {
                        setSignDate(
                            moment(_contract.signed_at).format("DD/MM/YYYY")
                        );
                    }
                    i18next.changeLanguage(res.data.offer.client.lng);

                    if (res.data.offer.client.lng == "heb") {
                        import("../Assets/css/rtl.css");
                        document
                            .querySelector("html")
                            .setAttribute("dir", "rtl");
                    } else {
                        document.querySelector("html").removeAttribute("dir");
                        const rtlLink = document.querySelector('link[href*="rtl.css"]');
                        if (rtlLink) {
                            rtlLink.remove();
                        }
                    }
                } else {
                    setOffer({});
                    setServices([]);
                    setClient(null);
                    setContract(null);
                }
            })
            .catch((e) => {
                Swal.fire({
                    title: "Error!",
                    text: e.response.data.message,
                    icon: "error",
                });
            });
    };

    const RejectContract = (e, id) => {
        e.preventDefault();
        Swal.fire({
            title: t("work-contract.messages.reject_title"),
            text: t("work-contract.messages.reject_text"),
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            cancelButtonText: t("work-contract.messages.cancel"),
            confirmButtonText: t("work-contract.messages.yes_reject"),
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .post(`/api/client/reject-contract`, { id: id })
                    .then((response) => {
                        Swal.fire(
                            t("work-contract.messages.reject"),
                            t("work-contract.messages.reject_msg"),
                            "success"
                        );
                        setTimeout(() => {
                            window.location.reload(true);
                        }, 2000);
                    });
                setStatus("declined");
            }
        });
    };

    const handleCard = () => {
        setAddCardBtnDisabled(true);

        axios
            .post(`/api/client/contracts/${params.id}/initialize-card`, {})
            .then((response) => {
                console.log(response);

                setCheckingForCard(true);

                setSessionURL(response.data.redirect_url);
                $("#exampleModal").modal("show");
                setAddCardBtnDisabled(false);
            })
            .catch((e) => {
                Swal.fire({
                    title: "Error!",
                    text: e.response.data.message,
                    icon: "error",
                });
            });
    };

    const handleCardSelect = (e) => {
        if (e.target.checked) {
            setSelectedClientCardID(e.target.value);
        } else {
            setSelectedClientCardID(null);
        }
    };

    useEffect(() => {
        let _intervalID;

        if (checkingForCard) {
            _intervalID = setInterval(() => {
                if (checkingForCard) {
                    axios
                        .post(
                            `/api/client/contracts/${params.id}/check-card`,
                            {}
                        )
                        .then((response) => {
                            if (response.data.card) {
                                setSelectedClientCardID(response.data.card.id);
                                setCheckingForCard(false);
                                setIsCardAdded(true);
                                getOffer();
                                clearInterval(_intervalID);
                            }
                        })
                        .catch((e) => {
                            setCheckingForCard(false);
                            clearInterval(_intervalID);
                            getOffer();
                            Swal.fire({
                                title: "Error!",
                                text: e.response.data.message,
                                icon: "error",
                            });
                        });
                }
            }, 2000);
        }

        return () => clearInterval(_intervalID);
    }, [checkingForCard]);

    useEffect(() => {
        getOffer();

        setTimeout(() => {
            document.querySelector(".parent").style.display = "block";
            var c = document.querySelectorAll("canvas");
            c.forEach((e, i) => {
                e.setAttribute("width", "300px");
                e.setAttribute("height", "115px");
            });
        }, 500);
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
        return clientCards.find((i) => i.id == selectedClientCardID);
    }, [clientCards, selectedClientCardID]);


    return (
        <div className="navyblueColor parent mb-5" style={{ display: "none" }}>
            <div className=" mt-4 mb-5 bg-transparent " style={{
                margin: mobileView ? "0 20px" : "auto",
                maxWidth: mobileView ? "unset" : "800px"
            }}>
                <div className="d-flex align-items-center justify-content-between flex-dir-co-1070">
                    <img
                        src={logo}
                        className="img-fluid broom-logo"
                        alt="Broom Services"
                        style={{ height: "100px" }}
                    />
                </div>

                <div className="mt-3">
                    <section className="d-flex align-items-center">
                        <p className="navyblueColor font-34 mt-4 font-w-500"> {t("client.contract-form.business_name_value")}</p>
                    </section>
                    <div className="d-flex align-items-center justify-content-between flex-wrap">
                        <section className="d-flex flex-column ">
                            <div className="d-flex align-items-center" style={{ gap: "30px" }}>
                                <p className="navyblueColor font-15">
                                    {t("client.contract-form.name")}:&nbsp;
                                    <b>{clientName}</b>
                                </p>
                                <p className="navyblueColor font-15 m-0">
                                    {t("client.contract-form.hp_em_tz")}:&nbsp;
                                    <b>{client ? client.vat_number : ""}</b>
                                </p>
                            </div>
                            <div className="d-flex align-items-center" style={{ gap: "30px" }}>
                                <p className="navyblueColor font-15">
                                    {t("client.contract-form.phone")}:&nbsp;
                                    <b>{client ? client.phone : ""}</b>
                                </p>
                                <p className="navyblueColor font-15 m-0">
                                    {t("client.contract-form.email")}:&nbsp;
                                    <b>{client ? client.email : ""}</b>
                                </p>
                            </div>
                        </section>
                        <section className="d-flex align-items-center" style={{ gap: "20px" }}>
                            {status == "not-signed" ? (
                                <div className="col-sm-12 mt-4 d-flex justify-content-end">
                                    <button
                                        type="button"
                                        className="btn btn-success"
                                        disabled={isSubmitted}
                                        onClick={handleAccept}
                                    >
                                        {t("work-contract.accept_contract")}
                                    </button>
                                    <button
                                        type="button"
                                        className="btn btn-danger ml-2"
                                        onClick={(e) =>
                                            RejectContract(e, contract.id)
                                        }
                                    >
                                        {t("work-contract.button_reject")}
                                    </button>
                                </div>
                            ) : (
                                <div className="col-sm-12 mt-4 d-flex justify-content-end">
                                    {status == "un-verified" ||
                                        status == "verified" ? (
                                        <h4 className="btn btn-success">
                                            {t("global.accepted")}
                                        </h4>
                                    ) : (
                                        <h4 className="btn btn-danger">
                                            {t("global.rejected")}
                                        </h4>
                                    )}
                                </div>
                            )}
                        </section>
                    </div>
                </div>
                <div className="mt-3">
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
                                                const serviceName = s.template == "others"
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
                                                        <td>{client?.lng == "heb" ? s.frequency_name_heb : s.frequency_name_en} </td>
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
                                            {contract && contract.signed_at ? (
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
                                            ) : (
                                                <>
                                                    <SignatureCanvas
                                                        penColor="black"
                                                        canvasProps={{
                                                            width: 250,
                                                            height: 100,
                                                            className: "sigCanvas mt-2",
                                                            style: {
                                                                border:
                                                                    "1px solid rgb(208 215 223)",
                                                                borderRadius: 5
                                                            }
                                                        }}
                                                        ref={sigRef2}
                                                        onEnd={handleSignature2End}
                                                    />
                                                    <button
                                                        className="btn navyblue m-3 mb-5 px-4 py-1"
                                                        onClick={clearSignature2}
                                                    >
                                                        {t("work-contract.btn_warning_txt")}
                                                    </button>
                                                </>
                                            )}
                                        </div>

                                        <p> {t("client.contract-form.add_cc_click_here1")} </p>

                                        {clientCards.map((_card, _index) => {
                                            return (
                                                <div className="my-3 d-flex" key={_index}>
                                                    <label className="custom-checkbox">
                                                        <input
                                                            type="checkbox"
                                                            className="mx-2"
                                                            id={_card.id}
                                                            value={_card.id}
                                                            onChange={
                                                                handleCardSelect
                                                            }
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
                                                    </label>
                                                    <label className="" htmlFor={_card.id}>
                                                        **** **** ****{" "}
                                                        {_card.card_number} -{" "}
                                                        {_card.valid} (
                                                        {_card.card_type})
                                                    </label>
                                                </div>
                                            );
                                        })}

                                        <p>{t("client.contract-form.add_cc_click_here")} </p>


                                        {/* {!isCardAdded && ( */}
                                        <button
                                            type="button"
                                            className="btn navyblue ac mb-3 mt-2"
                                            onClick={(e) => handleCard(e)}
                                            disabled={addCardBtnDisabled}
                                        >
                                            {t("client.contract-form.add_credit_card")}
                                        </button>
                                        {/* )} */}
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
                                style={{
                                    position: "unset",
                                    margin: 0,
                                }}
                                defaultChecked={consentToAds}
                                onChange={(e) => {
                                    setConsentToAds(
                                        e.target.checked ? true : false
                                    );
                                }}
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
                        {/*Iframe*/}
                        <div
                            className="modal fade"
                            id="exampleModal"
                            tabIndex="-1"
                            role="dialog"
                            aria-labelledby="exampleModalLabel"
                            aria-hidden="true"
                        >
                            <div
                                className="modal-dialog modal-dialog-centered modal-lg"
                                role="document"
                            >
                                <div className="modal-content">
                                    <div className="modal-header">
                                        <button
                                            type="button"
                                            className="btn btn-secondary"
                                            data-dismiss="modal"
                                            aria-label="Close"
                                        >
                                            {t("work-contract.back_btn")}
                                        </button>
                                    </div>
                                    <div className="modal-body">
                                        <div className="row">
                                            <div className="col-sm-12">
                                                <div className="form-group">
                                                    <iframe
                                                        src={sessionURL}
                                                        title="Pay Card Transaction"
                                                        width="100%"
                                                        height="800"
                                                    ></iframe>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
                                    {contract && contract.signature != null ? (
                                        <img src={contract.signature} />
                                    ) : (
                                        <>
                                            <SignatureCanvas
                                                penColor="black"
                                                canvasProps={{
                                                    width: 250,
                                                    height: 100,
                                                    className: "sigCanvas",
                                                    style: {
                                                        border:
                                                            "1px solid rgb(208 215 223)",
                                                        borderRadius: 5
                                                    }
                                                }}
                                                ref={sigRef1}
                                                onEnd={handleSignature1End}
                                            />
                                            <button
                                                className="btn navyblue px-4 py-1 mb-4 ml-2"
                                                onClick={clearSignature1}
                                            >
                                                {t("work-contract.btn_warning_txt")}
                                            </button>
                                        </>
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
                                {status == "not-signed" ? (
                                    <div className="col-sm-12 mt-4 d-flex justify-content-end">
                                        <button
                                            type="button"
                                            className="btn btn-success"
                                            disabled={isSubmitted}
                                            onClick={handleAccept}
                                        >
                                            {t("work-contract.accept_contract")}
                                        </button>
                                        <button
                                            type="button"
                                            className="btn btn-danger ml-2"
                                            onClick={(e) =>
                                                RejectContract(e, contract.id)
                                            }
                                        >
                                            {t("work-contract.button_reject")}
                                        </button>
                                    </div>
                                ) : (
                                    <div className="col-sm-12 mt-4 d-flex justify-content-end">
                                        {status == "un-verified" ||
                                            status == "verified" ? (
                                            <h4 className="btn btn-success">
                                                {t("global.accepted")}
                                            </h4>
                                        ) : (
                                            <h4 className="btn btn-danger">
                                                {t("global.rejected")}
                                            </h4>
                                        )}
                                    </div>
                                )}
                            </div>

                            <div className="mb-4">&nbsp;</div>
                        </div>
                    </section>
                </div>
            </div>
            {loading && <FullPageLoader visible={loading} />}
        </div>
    );
}
