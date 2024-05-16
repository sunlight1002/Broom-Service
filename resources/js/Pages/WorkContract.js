import React, { useRef, useState, useEffect } from "react";
import logo from "../Assets/image/sample.svg";
import star from "../Assets/image/icons/blue-star.png";
import SignatureCanvas from "react-signature-canvas";
import companySign from "../Assets/image/company-sign.png";
import axios from "axios";
import { useParams } from "react-router-dom";
import swal from "sweetalert";
import Moment from "moment";
import { useTranslation } from "react-i18next";
import i18next from "i18next";
import Swal from "sweetalert2";
import { useNavigate } from "react-router-dom";
import { frequencyDescription } from "../Utils/job.utils";

export default function WorkContract() {
    const { t } = useTranslation();
    const navigate = useNavigate();
    const [offer, setOffer] = useState(null);
    const [services, setServices] = useState([]);
    const [client, setClient] = useState([]);
    const [contract, setContract] = useState(null);
    const param = useParams();
    const sigRef = useRef();
    const [signature, setSignature] = useState(null);
    const [Aaddress, setAaddress] = useState(null);
    const [status, setStatus] = useState("");
    const [sessionURL, setSessionURL] = useState("");
    const [addCardBtnDisabled, setAddCardBtnDisabled] = useState(false);
    const [checkingForCard, setCheckingForCard] = useState(false);
    const [clientCards, setClientCards] = useState([]);
    const [selectedClientCardID, setSelectedClientCardID] = useState(null);
    const [isCardAdded, setIsCardAdded] = useState(false);

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
            unique_hash: param.id,
            offer_id: offer.id,
            client_id: offer.client.id,
            card_id: selectedClientCardID,
            additional_address: Aaddress,
            status: "un-verified",
            signature: signature,
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
            .post(`/api/client/contracts/${param.id}`)
            .then((res) => {
                if (res.data.offer) {
                    const _contract = res.data.contract;
                    setOffer(res.data.offer);
                    setServices(JSON.parse(res.data.offer.services));
                    setClient(res.data.offer.client);
                    setContract(_contract);
                    setStatus(_contract.status);

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
            .post(`/api/client/contracts/${param.id}/initialize-card`, {})
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
                            `/api/client/contracts/${param.id}/check-card`,
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

    return (
        <div className="container parent" style={{ display: "none" }}>
            <div className="send-offer client-contract sendOfferRtl">
                <div className="maxWidthControl dashBox mb-4">
                    <div className="row">
                        <div className="col-sm-6">
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
                        <div className="col-sm-6">
                            {status == "not-signed" ? (
                                <div className="mt-2 float-right">
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
                                <div className="mt-2 float-right headMsg">
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
                    </div>
                    <h4 className="inHead" style={{ whiteSpace: "pre-wrap" }}>
                        {t("work-contract.inHead")}
                    </h4>
                    <div className="signed">
                        <p>
                            {t("work-contract.signed")}{" "}
                            <span>{client.city ? client.city : "NA"}</span> on{" "}
                            {contract && (
                                <span>
                                    {Moment(contract.created_at).format(
                                        "DD MMMM,Y"
                                    )}
                                </span>
                            )}
                        </p>
                    </div>
                    <div className="between">
                        <p>{t("work-contract.between")}</p>
                        <p>{t("work-contract.broom_service")}</p>
                    </div>
                    <div className="first">
                        <h2 className="mb-4">
                            {t("work-contract.first_party_title")}
                        </h2>
                        <p style={{ textAlign: "center" }}>
                            {t("work-contract.and")}
                        </p>
                        {offer && offer.client && (
                            <React.Fragment>
                                <ul className="list-inline">
                                    <li className="list-inline-item ml-2">
                                        {t("work-contract.full_name")}{" "}
                                        <span>
                                            {offer.client.firstname +
                                                " " +
                                                offer.client.lastname}
                                        </span>
                                    </li>
                                </ul>
                                <ul className="list-inline">
                                    <li className="list-inline-item ml-2">
                                        {t("work-contract.telephone")}{" "}
                                        <span>{offer.client.phone}</span>
                                    </li>
                                    <li className="list-inline-item">
                                        {t("work-contract.email")}{" "}
                                        <span>{offer.client.email}</span>
                                    </li>
                                </ul>
                            </React.Fragment>
                        )}
                        <h2 className="mb-4">
                            {t("work-contract.second_party_title")}
                        </h2>
                        <div className="whereas">
                            <div className="info-list">
                                <div className="icons">
                                    <h4>{t("work-contract.whereas")}</h4>
                                </div>
                                <div className="info-text">
                                    <p>
                                        {t("work-contract.whereas_info_text")}
                                    </p>
                                </div>
                            </div>
                            <div className="info-list">
                                <div className="icons">
                                    <h4>{t("work-contract.and_whereas")}</h4>
                                </div>
                                <div className="info-text">
                                    <p>
                                        {t(
                                            "work-contract.and_whereas_info_text"
                                        )}
                                    </p>
                                </div>
                            </div>
                            <div className="info-list">
                                <div className="icons">
                                    <h4>{t("work-contract.and_whereas_2")}</h4>
                                </div>
                                <div className="info-text">
                                    <p>
                                        {t(
                                            "work-contract.and_whereas_2_info_text"
                                        )}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <h2 className="text-center mb-4">
                        {t("work-contract.parties_hereby_title")}
                    </h2>
                    <div className="shift-30">
                        <h6>{t("work-contract.intro_subtitle")}</h6>
                        <div className="agg-list">
                            <div className="icons">
                                <img src={star} />
                            </div>
                            <div className="agg-text">
                                <p>{t("work-contract.intro_txt_1")}</p>
                            </div>
                        </div>
                        <div className="agg-list">
                            <div className="icons">
                                <img src={star} />
                            </div>
                            <div className="agg-text">
                                <p>{t("work-contract.intro_txt_2")}</p>
                            </div>
                        </div>
                        <div className="agg-list">
                            <div className="icons">
                                <img src={star} />
                            </div>
                            <div className="agg-text">
                                <p>{t("work-contract.intro_txt_3")}</p>
                            </div>
                        </div>
                        <div className="agg-list">
                            <div className="icons">
                                <img src={star} />
                            </div>
                            <div className="agg-text">
                                <p>{t("work-contract.intro_txt_4")}</p>
                            </div>
                        </div>
                        <h6 className="text-center text-underline">
                            {t("work-contract.service_subtitle")}
                        </h6>
                        <div className="service-table table-responsive">
                            <table className="table table-bordered">
                                <tbody>
                                    <tr>
                                        <td style={{ width: "60%" }}>
                                            {t("work-contract.the_service_txt")}
                                        </td>
                                        <td>
                                            {services.map((s, i) => {
                                                return (
                                                    <p key={i}>
                                                        {s.service != "10"
                                                            ? s.name
                                                            : s.other_title}
                                                    </p>
                                                );
                                            })}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style={{ width: "60%" }}>
                                            {t("work-contract.location_txt")}
                                        </td>
                                        <td>
                                            {services.map((s, i) => {
                                                return (
                                                    <p key={i}>
                                                        {s.address.address_name}
                                                    </p>
                                                );
                                            })}
                                            <span
                                                style={{ fontWeight: "600" }}
                                                className="d-block mt-2"
                                            >
                                                {t(
                                                    "work-contract.other_address_txt"
                                                )}
                                            </span>{" "}
                                            <br />
                                            {contract &&
                                            contract.additional_address !=
                                                null ? (
                                                <input
                                                    type="text"
                                                    value={
                                                        contract.additional_address
                                                    }
                                                    readOnly
                                                    className="form-control"
                                                />
                                            ) : (
                                                <input
                                                    type="text"
                                                    name="additional_address"
                                                    onChange={(e) =>
                                                        setAaddress(
                                                            e.target.value
                                                        )
                                                    }
                                                    placeholder={t(
                                                        "work-contract.placeholder_address"
                                                    )}
                                                    className="form-control"
                                                />
                                            )}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style={{ width: "60%" }}>
                                            {t(
                                                "work-contract.service_delivery_txt"
                                            )}
                                        </td>
                                        <td>
                                            {t("work-contract.as_agreed_txt")}{" "}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style={{ width: "60%" }}>
                                            {t("work-contract.frequency_txt")}
                                        </td>
                                        <td>
                                            {services.map((s, i) => {
                                                return (
                                                    <p key={i}>
                                                        {" "}
                                                        {s.freq_name};{" "}
                                                        {/* {frequencyDescription(
                                                            s
                                                        )} */}
                                                    </p>
                                                );
                                            })}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style={{ width: "60%" }}>
                                            {t(
                                                "work-contract.consideration_txt"
                                            )}
                                        </td>
                                        <td>
                                            {services.map((s, i) => {
                                                return (
                                                    <p key={i}>
                                                        {s.totalamount +
                                                            " " +
                                                            t(
                                                                "work-contract.ils"
                                                            ) +
                                                            " + " +
                                                            t(
                                                                "work-contract.vat"
                                                            ) +
                                                            " " +
                                                            t(
                                                                "work-contract.for"
                                                            ) +
                                                            " " +
                                                            (s.service != "10"
                                                                ? s.name
                                                                : s.other_title) +
                                                            ", " +
                                                            s.freq_name}
                                                    </p>
                                                );
                                            })}
                                        </td>
                                    </tr>
                                    <tr>
                                        {/* <td style={{width: "60%"}}>{t('work-contract.payment_method')}</td> */}
                                        <td colSpan="2">
                                            {t("work-contract.payment_method")}
                                        </td>
                                        {/* <td>&nbsp;</td> */}
                                    </tr>
                                    <tr>
                                        <td colSpan="2">
                                            {t(
                                                "work-contract.hereby_permit_txt"
                                            )}
                                        </td>
                                        {/* <td>&nbsp;</td> */}
                                    </tr>

                                    {clientCards.map((_card, _index) => {
                                        return (
                                            <tr key={_index}>
                                                <td colSpan={2}>
                                                    <div className="form-check">
                                                        <label className="form-check-label">
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
                                                            {_card.card_number}{" "}
                                                            - {_card.valid} (
                                                            {_card.card_type})
                                                        </label>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}

                                    {!isCardAdded && (
                                        <tr>
                                            <td style={{ width: "60%" }}>
                                                {t(
                                                    "work-contract.add_card_txt"
                                                )}
                                            </td>
                                            <td>
                                                <button
                                                    type="button"
                                                    className="btn btn-success ac"
                                                    onClick={(e) =>
                                                        handleCard(e)
                                                    }
                                                    disabled={
                                                        addCardBtnDisabled
                                                    }
                                                >
                                                    {t(
                                                        "work-contract.add_card_btn"
                                                    )}
                                                </button>
                                            </td>
                                        </tr>
                                    )}

                                    <tr>
                                        <td style={{ width: "60%" }}>
                                            {t(
                                                "work-contract.miscellaneous_txt"
                                            )}
                                        </td>
                                        <td>
                                            {t("work-contract.employees_txt")}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <h6 className="text-underline">
                            {t("work-contract.tenant_subtitle")}
                        </h6>
                        <div className="agg-list">
                            <div className="icons">
                                <img src={star} />
                            </div>
                            <div className="agg-text">
                                <p>{t("work-contract.tenant_txt_1")}</p>
                            </div>
                        </div>
                        <div className="agg-list">
                            <div className="icons">
                                <img src={star} />
                            </div>
                            <div className="agg-text">
                                <p>{t("work-contract.tenant_txt_2")}</p>
                            </div>
                        </div>
                        <div className="agg-list">
                            <div className="icons">
                                <img src={star} />
                            </div>
                            <div className="agg-text">
                                <p>{t("work-contract.tenant_txt_3")}</p>
                            </div>
                        </div>
                        <div className="agg-list">
                            <div className="icons">
                                <img src={star} />
                            </div>
                            <div className="agg-text">
                                <p>{t("work-contract.tenant_txt_4")}</p>
                            </div>
                        </div>
                        <div className="agg-list">
                            <div className="icons">
                                <img src={star} />
                            </div>
                            <div className="agg-text">
                                <p style={{ whiteSpace: "pre-wrap" }}>
                                    {t("work-contract.tenant_txt_5")}
                                </p>
                            </div>
                        </div>
                        <div className="agg-list">
                            <div className="icons">
                                <img src={star} />
                            </div>
                            <div className="agg-text">
                                <p style={{ backgroundColor: "yellow" }}>
                                    {t("work-contract.tenant_txt_6")}
                                </p>
                            </div>
                        </div>
                        <div className="agg-list">
                            <div className="icons">
                                <img src={star} />
                            </div>
                            <div className="agg-text">
                                <p style={{ backgroundColor: "yellow" }}>
                                    {t("work-contract.tenant_txt_7")}
                                </p>
                            </div>
                        </div>
                        <div className="agg-list">
                            <div className="icons">
                                <img src={star} />
                            </div>
                            <div className="agg-text">
                                <p style={{ backgroundColor: "yellow" }}>
                                    {t("work-contract.tenant_txt_8")}
                                </p>
                            </div>
                        </div>
                        <h6 className="text-underline">
                            {t("work-contract.company_subtitle")}
                        </h6>
                        <div className="agg-list">
                            <div className="icons">
                                <img src={star} />
                            </div>
                            <div className="agg-text">
                                <p style={{ whiteSpace: "pre-wrap" }}>
                                    {t("work-contract.company_txt_1")}
                                </p>
                            </div>
                        </div>
                        <div className="agg-list">
                            <div className="icons">
                                <img src={star} />
                            </div>
                            <div className="agg-text">
                                <p>{t("work-contract.company_txt_2")}</p>
                            </div>
                        </div>
                        <div className="agg-list">
                            <div className="icons">
                                <img src={star} />
                            </div>
                            <div className="agg-text">
                                <p>{t("work-contract.company_txt_3")} </p>
                            </div>
                        </div>
                        <div className="agg-list">
                            <div className="icons">
                                <img src={star} />
                            </div>
                            <div className="agg-text">
                                <p>{t("work-contract.company_txt_4")} </p>
                            </div>
                        </div>
                        <div className="agg-list">
                            <div className="icons">
                                <img src={star} />
                            </div>
                            <div className="agg-text">
                                <p>{t("work-contract.company_txt_5")} </p>
                            </div>
                        </div>
                        <div className="agg-list">
                            <div className="icons">
                                <img src={star} />
                            </div>
                            <div className="agg-text">
                                <p>{t("work-contract.company_txt_6")} </p>
                            </div>
                        </div>
                        <div className="agg-list">
                            <div className="icons">
                                <img src={star} />
                            </div>
                            <div className="agg-text">
                                <p>{t("work-contract.company_txt_7")}</p>
                            </div>
                        </div>
                        <h6 className="text-underline">
                            {t("work-contract.general_subtitle")}
                        </h6>
                        <div className="agg-list">
                            <div className="icons">
                                <img src={star} />
                            </div>
                            <div className="agg-text">
                                <p>{t("work-contract.general_txt_1")}</p>
                            </div>
                        </div>
                        <div className="agg-list">
                            <div className="icons">
                                <img src={star} />
                            </div>
                            <div className="agg-text">
                                <p>{t("work-contract.general_txt_2")} </p>
                            </div>
                        </div>
                        <div className="agg-list">
                            <div className="icons">
                                <img src={star} />
                            </div>
                            <div className="agg-text">
                                <p>{t("work-contract.general_txt_3")}</p>
                            </div>
                        </div>
                        <h6 className="text-center text-underline mt-3 mb-4">
                            {t("work-contract.signed_title")}
                        </h6>
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
                            {status == "not-signed" && (
                                <div className=" col-sm-12 mt-2 float-right">
                                    <button
                                        type="button"
                                        className="btn btn-pink"
                                        onClick={handleAccept}
                                    >
                                        {t("work-contract.accept_contract")}
                                    </button>
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
