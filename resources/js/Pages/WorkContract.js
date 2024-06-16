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
import { useNavigate } from "react-router-dom";

export default function WorkContract() {
    const { t } = useTranslation();
    const navigate = useNavigate();

    const [offer, setOffer] = useState(null);
    const [services, setServices] = useState([]);
    const [client, setClient] = useState(null);
    const [contract, setContract] = useState(null);
    const [signature, setSignature] = useState(null);
    const [Aaddress, setAaddress] = useState(null);
    const [status, setStatus] = useState("");
    const [sessionURL, setSessionURL] = useState("");
    const [addCardBtnDisabled, setAddCardBtnDisabled] = useState(false);
    const [checkingForCard, setCheckingForCard] = useState(false);
    const [clientCards, setClientCards] = useState([]);
    const [selectedClientCardID, setSelectedClientCardID] = useState(null);
    const [isCardAdded, setIsCardAdded] = useState(false);
    const [consentToAds, setConsentToAds] = useState(true);

    const params = useParams();
    const sigRef = useRef();
    const consentToAdsRef = useRef();

    const handleAccept = (e) => {
        if (!selectedClientCardID) {
            swal(t("work-contract.messages.card_err"), "", "error");
            return false;
        }

        if (!signature) {
            swal(t("work-contract.messages.sign_err"), "", "error");
            return false;
        }

        const data = {
            unique_hash: params.id,
            offer_id: offer.id,
            client_id: offer.client.id,
            card_id: selectedClientCardID,
            additional_address: Aaddress,
            status: "un-verified",
            signature: signature,
            consent_to_ads: consentToAdsRef.current.checked ? 1 : 0,
        };

        axios
            .post(`/api/client/accept-contract`, data)
            .then((res) => {
                if (res.data.error) {
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
                Swal.fire({
                    title: "Error!",
                    text: e.response.data.message,
                    icon: "error",
                });
            });
    };

    const handleSignatureEnd = () => {
        setSignature(sigRef.current.toDataURL());
    };

    const clearSignature = () => {
        sigRef.current.clear();
        setSignature(null);
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
                    setStatus(_contract.status);
                    setConsentToAds(_contract.consent_to_ads ? true : false);

                    setClientCards(res.data.cards);
                    setSelectedClientCardID(_contract.card_id);
                    if (_contract.status != "not-signed") {
                        setIsCardAdded(true);
                    }
                    i18next.changeLanguage(res.data.offer.client.lng);

                    if (res.data.offer.client.lng == "heb") {
                        import("../Assets/css/rtl.css");
                        document
                            .querySelector("html")
                            .setAttribute("dir", "rtl");
                    } else {
                        document.querySelector("html").removeAttribute("dir");
                    }

                    if (res.data.offer.client.lng == "heb") {
                        document
                            .querySelector("html")
                            .setAttribute("dir", "rtl");
                    }
                } else {
                    setOffer({});
                    setServices([]);
                    setClient([]);
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
                setCheckingForCard(true);

                setSessionURL(response.data.redirect_url);
                $("#exampleModal").modal("show");
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
                                setClientCards([response.data.card]);
                                setSelectedClientCardID(response.data.card.id);
                                setCheckingForCard(false);
                                setIsCardAdded(true);
                                clearInterval(_intervalID);
                            }
                        })
                        .catch((e) => {
                            setCheckingForCard(false);
                            clearInterval(_intervalID);

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

    return (
        <div className="container parent" style={{ display: "none" }}>
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
                        <div className="col-md-12 d-flex">
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
                            <span className="text-underline mx-3"></span>
                        </div>
                        <div className="col-md-12">
                            <label htmlFor="">
                                {t("client.contract-form.address")}:
                            </label>
                            <span className="text-underline mx-3"></span>
                        </div>
                        <div className="col-md-12 d-flex">
                            <label htmlFor="">
                                {t("client.contract-form.phone")}:
                            </label>
                            <span className="text-underline mx-3">
                                {client ? client.phone : ""}
                            </span>
                            <div className="mx-4">
                                <label htmlFor="">
                                    {t("client.contract-form.fax")}:
                                </label>
                            </div>
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
                                            return (
                                                <tr key={i}>
                                                    <td>
                                                        {s.address &&
                                                        s.address.address_name
                                                            ? s.address
                                                                  .address_name
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
                                        :
                                    </p>
                                    <p>
                                        {t(
                                            "client.contract-form.cc_holder_name"
                                        )}
                                        :
                                    </p>
                                    <p>
                                        {t("client.contract-form.cc_id_number")}
                                        :
                                    </p>
                                    <p>
                                        {t("client.contract-form.cc_signature")}
                                        :
                                    </p>
                                    <p>
                                        {t(
                                            "client.contract-form.add_cc_click_here"
                                        )}
                                    </p>

                                    {clientCards.map((_card, _index) => {
                                        return (
                                            <div className="my-3" key={_index}>
                                                <label className="form-check-label ">
                                                    <input
                                                        type="checkbox"
                                                        className="form-check-input"
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
                                                    **** **** ****{" "}
                                                    {_card.card_number} -{" "}
                                                    {_card.valid} (
                                                    {_card.card_type})
                                                </label>
                                            </div>
                                        );
                                    })}

                                    {!isCardAdded && (
                                        <button
                                            type="button"
                                            className="btn btn-success ac mx-5"
                                            onClick={(e) => handleCard(e)}
                                            disabled={addCardBtnDisabled}
                                        >
                                            {t(
                                                "client.contract-form.add_credit_card"
                                            )}
                                        </button>
                                    )}
                                    <p>
                                        {t(
                                            "client.contract-form.cc_compensation"
                                        )}
                                    </p>
                                </div>

                                <p>3.4. {t("client.contract-form.ca3_4")}</p>
                                <p>
                                    3.5.
                                    <label className="form-check-label">
                                        <input
                                            type="checkbox"
                                            className="form-check-input"
                                            checked={consentToAds}
                                            onChange={(e) => {
                                                setConsentToAds(
                                                    e.target.checked
                                                        ? true
                                                        : false
                                                );
                                            }}
                                            disabled={
                                                contract &&
                                                contract.status != "not-signed"
                                            }
                                            ref={consentToAdsRef}
                                        />
                                        {t("client.contract-form.ca3_5")}
                                    </label>
                                </p>

                                <p>
                                    {t(
                                        "client.contract-form.direct_mail_declaration"
                                    )}
                                </p>
                                <p>
                                    {t("client.contract-form.direct_mail_note")}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div className="row mt-4">
                        <div className="col-md-12">
                            <p>4. {t("client.contract-form.ca4")}</p>
                            {contract && <p>{contract.comment}</p>}
                            <p>{t("client.contract-form.date")}: </p>
                        </div>
                    </div>
                    <div className="row mt-4">
                        <div className="col-md-12">
                            <p className="text-center">
                                {t("client.contract-form.general_terms")}
                            </p>
                            <p>1. {t("client.contract-form.gt1")}</p>
                            <p>2. {t("client.contract-form.gt2")}</p>
                            <p>3. {t("client.contract-form.gt3")}</p>
                            <p>4. {t("client.contract-form.gt4")}</p>
                            <p>5. {t("client.contract-form.gt5")}</p>
                            <p>6. {t("client.contract-form.gt6")}</p>
                            <p>7. {t("client.contract-form.gt7")}</p>
                            <p>8. {t("client.contract-form.gt8")}</p>
                            <p>9. {t("client.contract-form.gt9_1")}</p>
                            <p>{t("client.contract-form.gt9_2")}</p>
                            <p>{t("client.contract-form.gt9_3")}</p>
                            <p>10. {t("client.contract-form.gt10")}</p>
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
                                            }}
                                            ref={sigRef}
                                            onEnd={handleSignatureEnd}
                                        />
                                        <button
                                            className="btn btn-warning"
                                            onClick={clearSignature}
                                        >
                                            {t("work-contract.btn_warning_txt")}
                                        </button>
                                    </>
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
                            {status == "not-signed" ? (
                                <div className="col-sm-12 mt-2 float-right">
                                    <button
                                        type="button"
                                        className="btn btn-pink"
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
                                <div className="col-sm-12 mt-2 float-right">
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
                    </div>
                </div>
            </div>
        </div>
    );
}
