import React, { useRef, useState, useEffect } from "react";
import Sidebar from "../../Layouts/ClientSidebar";
import logo from "../../../Assets/image/sample.svg";
import star from "../../../Assets/image/icons/blue-star.png";
import SignatureCanvas from "react-signature-canvas";
import companySign from "../../../Assets/image/company-sign.png";
import axios from "axios";
import { useParams } from "react-router-dom";
import swal from "sweetalert";
import Moment from "moment";
import { useTranslation } from "react-i18next";
import Swal from "sweetalert2";
import { Base64 } from "js-base64";
import { frequencyDescription } from "../../../Utils/job.utils";

export default function ViewContract() {
    const [offer, setOffer] = useState();
    const [contract, setContract] = useState([]);
    const [client, setClient] = useState([]);
    const [services, setServices] = useState([]);
    const param = useParams();
    const sigRef = useRef();
    const { t } = useTranslation();
    const [signature, setSignature] = useState(null);
    const [Aaddress, setAaddress] = useState(null);
    const [clientCard, setClientCard] = useState(null);
    const [status, setStatus] = useState("")

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("client-token"),
    };

    const handleAccept = (e) => {
        if (!signature) {
            swal(t("work-contract.pleaseSign"), "", "error");
            return false;
        }

        const data = {
            unique_hash: param.hash,
            offer_id: offer.id,
            client_id: offer.client.id,
            additional_address: Aaddress,
            status: "un-verified",
            signature: signature,
        };

        axios
            .post(`/api/client/accept-contract`, data)
            .then((res) => {
                swal(res.data.message, "", "success");
                setTimeout(() => {
                    window.location.reload(true);
                }, 1000);
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
        axios.post(`/api/client/contracts/${param.hash}`).then((res) => {
            console.log(res);
            
            setOffer(res.data.offer);
            setClient(res.data.offer.client);
            setServices(JSON.parse(res.data.offer.services));
            setClientCard(res.data.card);
        });
    };

    const getContract = () => {
        axios
            .post(
                `/api/client/get-contract/${Base64.decode(param.id)}`,
                {},
                { headers }
            )
            .then((res) => {
                console.log(res,"get");
                
                setStatus(res.data?.contract?.status)
                setContract(res.data.contract);
            });
    };

    useEffect(() => {
        getOffer();
        getContract();
    }, []);

    return (
        <div className="container parent">
            {/* <Sidebar /> */}
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
                            <h4 className="btn btn-success float-right">
                                {t("global.accepted")}
                            </h4>
                        </div>
                    </div>
                    <h4 className="inHead" style={{ whiteSpace: "pre-wrap" }}>
                        {t("work-contract.inHead")}
                    </h4>
                    <div className="signed">
                        <p>
                            {t("work-contract.signed")}{" "}
                            <span>{client.city ? client.city : "NA"}</span> on{" "}
                            <span>
                                {contract &&
                                    Moment(contract.created_at).format(
                                        "DD MMMM,Y"
                                    )}
                            </span>
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
                                    {/* <li className="list-inline-item">
                                                {t("work-contract.city")}{" "}
                                                <span>{offer.client.city}</span>
                                            </li> */}
                                </ul>
                                {/* <ul className="list-inline">
                                            <li className="list-inline-item ml-2">
                                                {t(
                                                    "work-contract.street_and_number"
                                                )}{" "}
                                                <span>{offer.client.geo_address}</span>
                                            </li> */}
                                {/* <li className='list-inline-item'>{t('work-contract.floor')} <span>{offer.client.floor}</span></li>*/}
                                {/* </ul> */}
                                <ul className="list-inline">
                                    {/* <li className='list-inline-item ml-2'>{t('work-contract.apt_number')} <span>{offer.client.apt_no}</span></li>
                                        <li className='list-inline-item'>{t('work-contract.enterance_code')} <span>{offer.client.entrence_code}</span></li>*/}
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
                                                        {s.address
                                                            ? s.address
                                                                  .address_name
                                                                ? s.address
                                                                      .address_name
                                                                : ""
                                                            : ""}
                                                    </p>
                                                );
                                            })}
                                            <br />{" "}
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
                                    {clientCard && (
                                        <tr>
                                            <td style={{ width: "60%" }}>
                                                {t("credit-card.added-card")}
                                            </td>
                                            <td>
                                                **** **** ****{" "}
                                                {clientCard.card_number} -{" "}
                                                {clientCard.valid} (
                                                {clientCard.card_type})
                                            </td>
                                        </tr>
                                    )}
                                    <tr>
                                        <td colSpan="2">
                                            {t(
                                                "work-contract.hereby_permit_txt"
                                            )}
                                        </td>
                                        {/* <td>&nbsp;</td> */}
                                    </tr>
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
                            {status == "not-signed" ? (
                                <div className=" col-sm-12 mt-2 float-right">
                                    <input
                                        className="btn btn-pink"
                                        onClick={handleAccept}
                                        value={t(
                                            "work-contract.accept_contract"
                                        )}
                                    />
                                </div>
                            ) : (
                                ""
                            )}
                        </div>

                        <div className="mb-4">&nbsp;</div>
                    </div>
                </div>
            </div>
        </div>
    );
}
