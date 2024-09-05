import React, { useRef, useState, useEffect , useMemo} from "react";
import Sidebar from "../../Layouts/Sidebar";
import logo from "../../../Assets/image/sample.svg";
import star from "../../../Assets/image/icons/blue-star.png";
import SignatureCanvas from "react-signature-canvas";
import companySign from "../../../Assets/image/company-sign.png";
import axios from "axios";
import { Link, useParams } from "react-router-dom";
import Swal from "sweetalert2";
import swal from "sweetalert";
import moment from "moment";
import { useTranslation } from "react-i18next";
import i18next from "i18next";
import { frequencyDescription } from "../../../Utils/job.utils";

export default function ContractEng() {
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
    const [clientCards, setClientCards] = useState({});
    const [selectedClientCardID, setSelectedClientCardID] = useState(null);
    const [isCardAdded, setIsCardAdded] = useState(false);
    const [consentToAds, setConsentToAds] = useState(true);
    const [signDate, setSignDate] = useState(moment().format("DD/MM/YYYY"));

    const param = useParams();
    const sigRef = useRef();
    const { t } = useTranslation();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getContract = () => {
        axios
            .post(`/api/admin/get-contract/${param.id}`, {}, { headers })
            .then((res) => {
                console.log(res);
                
                const _contract = res.data.contract;
                setOffer(_contract.offer);
                setServices(JSON.parse(_contract.offer.services));
                setClient(_contract.client);
                setContract(_contract);
                setStatus(_contract.status);
                setConsentToAds(_contract.consent_to_ads ? true : false);

                setClientCards(_contract?.card);
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

                if (_contract.client.lng == "heb") {
                    import("../../../Assets/css/rtl.css");
                    document.querySelector("html").setAttribute("dir", "rtl");
                } else {
                    document.querySelector("html").removeAttribute("dir");
                }
            });
    };

    const handleVerify = (e) => {
        e.preventDefault();
        axios
            .post(`/api/admin/verify-contract`, { id: param.id }, { headers })
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

    useEffect(() => {
        getContract();
    }, []);


    return (
        <>
            <div className="container">
            {/* <Sidebar /> */}
                <div className="send-offer client-contract">
                    <div className="maxWidthControl dashBox mb-4">
                        <div className="row mb-3">
                            <div className="col-sm-6">
                                <svg
                                    width="250"
                                    height="94"
                                    xmlns="http://www.w3.org/2000/svg"
                                    xmlnsXlink="http://www.w3.org/1999/xlink"
                                >
                                    <image
                                        xlinkHref={logo}
                                        width="250"
                                        height="94"
                                    ></image>
                                </svg>
                            </div>
                            {contract && (
                                <>
                                    {contract.status == "un-verified" && (
                                        <div className="col-sm-6">
                                            <div className="mt-2 float-right">
                                                <input
                                                    className="btn btn-warning"
                                                    onClick={handleVerify}
                                                    value="Verify"
                                                />
                                            </div>
                                        </div>
                                    )}
                                    {contract.status == "verified" && (
                                        <React.Fragment>
                                            <div className="col-sm-3">
                                                <div className="mt-2 float-right">
                                                    <input
                                                        className="btn btn-success"
                                                        value="Verified"
                                                    />
                                                </div>
                                            </div>

                                            <div className="col-sm-3">
                                                <div className="mt-2 float-left">
                                                    <Link
                                                        to={`/admin/create-job/${contract.id}`}
                                                        className="btn btn-pink"
                                                    >
                                                        Create Job
                                                    </Link>
                                                </div>
                                            </div>
                                        </React.Fragment>
                                    )}
                                </>
                            )}
                        </div>
                        <h4 className="inHead">
                            Broom Service L.M. Ltd Private Company no. 515184208
                            Exclusive Framework Agreement with Tenants/Clients
                        </h4>
                        <div className="signed">
                            <p>
                                Made and Signed in:{" "}
                                <span>{client && client?.city ? client.city : "NA"}</span>{" "}
                                on{" "}
                                <span>
                                    {moment(contract && contract?.created_at).format(
                                        "DD MMMM,Y"
                                    )}
                                </span>
                            </p>
                        </div>
                        <div className="between">
                            <p>Between:</p>
                            <p>
                                Broom Service L.M. Ltd Private Company no.
                                515184208
                            </p>
                        </div>
                        <div className="first">
                            <h2 className="mb-4">Of the First Party</h2>
                            <ul className="list-inline">
                                <li className="list-inline-item">
                                    Full Name:{" "}
                                    <span>
                                        {client && client.firstname +
                                            " " +
                                            client && client.lastname}
                                    </span>
                                </li>
                                {/* <li className="list-inline-item">
                                    City:{" "}
                                    <span>
                                        {client.city ? client.city : "NA"}
                                    </span>
                                </li> */}
                            </ul>
                            {/* <ul className="list-inline">
                                <li className="list-inline-item">
                                    Street and Number:{" "}
                                    <span>
                                        {client.geo_address
                                            ? client.geo_address
                                            : "NA"}
                                    </span>
                                </li> */}
                            {/* <li className='list-inline-item'>Floor: <span>{client.floor ? client.floor : 'NA'}</span></li>*/}
                            {/* </ul> */}
                            <ul className="list-inline">
                                {/*<li className='list-inline-item'>Apt Number: <span>{client.apt_no ? client.apt_no : 'NA'}</span></li>
                                <li className='list-inline-item'>Enterance Code: <span>{client.entrence_code ? client.entrence_code : 'NA'}</span></li>*/}
                            </ul>
                            <ul className="list-inline">
                                <li className="list-inline-item">
                                    Telephone:{" "}
                                    <span>
                                        {client && client.phone ? client.phone : "NA"}
                                    </span>
                                </li>
                                <li className="list-inline-item">
                                    Email:{" "}
                                    <span>
                                        { client && client.email ? client.email : "NA"}
                                    </span>
                                </li>
                            </ul>

                            <h2 className="mb-4">Of the Second Party</h2>
                            <div className="whereas">
                                <div className="info-list">
                                    <div className="icons">
                                        <h4>Whereas:</h4>
                                    </div>
                                    <div className="info-text">
                                        <p>
                                            Broom Service L.M. Private Company
                                            no. 515184208 (hereinafter: the
                                            Company) is a company that provides,
                                            inter alia, services of maintenance,
                                            supply, and cleaning fortenants in
                                            various facilities across the State
                                            of Israel.
                                        </p>
                                    </div>
                                </div>
                                <div className="info-list">
                                    <div className="icons">
                                        <h4>And whereas:</h4>
                                    </div>
                                    <div className="info-text">
                                        <p>
                                            The Tenant is interested in making
                                            an agreement with the Company in
                                            order to receive the services
                                            requested in this agreement, for the
                                            consideration specified in this
                                            agreement.
                                        </p>
                                    </div>
                                </div>
                                <div className="info-list">
                                    <div className="icons">
                                        <h4>And whereas:</h4>
                                    </div>
                                    <div className="info-text">
                                        <p>
                                            The Tenant is aware that in order to
                                            receive the service and/or work from
                                            the Company, he/she must sign this
                                            agreement and comply with all the
                                            terms and conditions of this
                                            agreement, with no exception, in
                                            connection with the service and/or
                                            work and/or the materials and/or the
                                            products the Tenant is interested in
                                            receiving from the Company.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <h2 className="text-center mb-4">
                            Therefore, the Parties hereby agree and declare as
                            follows:
                        </h2>
                        <div className="shift-30">
                            <h6>Introduction</h6>
                            <div className="agg-list">
                                <div className="icons">
                                    <img src={star} />
                                </div>
                                <div className="agg-text">
                                    <p>
                                        The introduction of this agreement is an
                                        integral part thereof and as binding as
                                        all its other provisions.
                                    </p>
                                </div>
                            </div>
                            <div className="agg-list">
                                <div className="icons">
                                    <img src={star} />
                                </div>
                                <div className="agg-text">
                                    <p>
                                        Any obligation of the Tenant under this
                                        agreement is an addition to any other
                                        obligation of the Tenant under other
                                        agreements and/or the quotation and/or
                                        any applicable law.
                                    </p>
                                </div>
                            </div>
                            <div className="agg-list">
                                <div className="icons">
                                    <img src={star} />
                                </div>
                                <div className="agg-text">
                                    <p>
                                        In any case of contrast between the
                                        provisions of this agreement and the
                                        provisions of any other agreement
                                        between the Tenant and the Company
                                        and/or the quotation the Tenant
                                        submitted to the Company, the provisions
                                        of this agreement shall prevail.
                                    </p>
                                </div>
                            </div>
                            <div className="agg-list">
                                <div className="icons">
                                    <img src={star} />
                                </div>
                                <div className="agg-text">
                                    <p>
                                        Headings of the sections contained in
                                        this agreement are for convenience only
                                        and shall not be interpreted to limit or
                                        otherwise affect the provisions of this
                                        agreement.
                                    </p>
                                </div>
                            </div>
                            <h6 className="text-center text-underline">
                                The service / work and/or products requested by
                                the Tenant, including their scope, location and
                                commercial terms
                            </h6>
                            <div className="service-table table-responsive">
                                <table className="table table-bordered">
                                    <tbody>
                                        <tr>
                                            <td style={{ width: "60%" }}>
                                                The service and/or work
                                                requested by the Tenant
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
                                                The location in which the
                                                service will be provided and/or
                                                work will be performed
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
                                                {contract && (
                                                    <>
                                                        {contract.additional_address && (
                                                            <>
                                                                {contract.status ==
                                                                    "not-signed" ? (
                                                                    <React.Fragment>
                                                                        <span>
                                                                            {contract
                                                                                .client
                                                                                .geo_address
                                                                                ? contract
                                                                                    .client
                                                                                    .geo_address +
                                                                                ", "
                                                                                : ""}
                                                                        </span>
                                                                        <span
                                                                            style={{
                                                                                fontWeight:
                                                                                    "600",
                                                                            }}
                                                                            className="d-block mt-2"
                                                                        >
                                                                            Other
                                                                            address
                                                                            if
                                                                            any?
                                                                        </span>{" "}
                                                                        <br />
                                                                        <input
                                                                            type="text"
                                                                            name="additional_address"
                                                                            onChange={(
                                                                                e
                                                                            ) =>
                                                                                setAaddress(
                                                                                    e
                                                                                        .target
                                                                                        .value
                                                                                )
                                                                            }
                                                                            placeholder="Any other address?"
                                                                            className="form-control"
                                                                        />
                                                                    </React.Fragment>
                                                                ) : (
                                                                    <React.Fragment>
                                                                        <span>
                                                                            {contract
                                                                                .client
                                                                                .geo_address
                                                                                ? contract
                                                                                    .client
                                                                                    .geo_address +
                                                                                ", "
                                                                                : ""}
                                                                        </span>
                                                                        <span
                                                                            style={{
                                                                                fontWeight:
                                                                                    "600",
                                                                            }}
                                                                            className="d-block mt-2"
                                                                        >
                                                                            Other
                                                                            address
                                                                            if
                                                                            any?
                                                                        </span>{" "}
                                                                        <br />
                                                                        <span className="form-control">
                                                                            {
                                                                                contract.additional_address
                                                                            }
                                                                        </span>
                                                                    </React.Fragment>
                                                                )}
                                                            </>
                                                        )}
                                                    </>
                                                )}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style={{ width: "60%" }}>
                                                Date on which the service
                                                delivery and/or work will begin,
                                                and the date on which the
                                                service delivery and/or work
                                                will end
                                            </td>
                                            <td>
                                                As agreed between the parties
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style={{ width: "60%" }}>
                                                Frequency of the service and/or
                                                work
                                            </td>
                                            <td>
                                                {services.map((s, i) => {
                                                    return (
                                                        <p key={i}>
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
                                                Consideration the Tenant will
                                                pay the Company, including the
                                                payment method and/or payment
                                                date
                                                <br />
                                                Prices does not include vat**
                                            </td>
                                            <td>
                                                {services.map((s, i) => {
                                                    return (
                                                        <p key={i}>
                                                            {s.totalamount +
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
                                                                (s.service !=
                                                                    "10"
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
                                            <td style={{ width: "60%" }}>
                                                Payment method:
                                            </td>
                                            <td>&nbsp;</td>
                                        </tr>


                                        {/* <tr>
                                            <td>
                                           
                                                
                                            </td>
                                        </tr> */}
                                        {/* <tr>
                                            <td>
                                                {t(
                                                    "client.contract-form.cc_holder_name"
                                                )}
                                                :{" "}
                                               
                                            </td>
                                            <td></td>
                                        </tr>

                                        <tr>
                                            <td>
                                                {t("client.contract-form.cc_id_number")}
                                                :{" "}
                                               
                                            </td>
                                            <td></td>
                                        </tr>  */}

                                        <tr>
                                            <td style={{ width: "60%" }}>
                                                I the undersigned hereby permit
                                                Broom Service L.M. Ltd to charge
                                                my credit card account (the
                                                details of which are listed
                                                below) for the services I will
                                                receive from the Company, in the
                                                amounts and on the dates
                                                specified in this agreement
                                                between Broom Service L.M. Ltd
                                                and me.
                                            </td>
                                            <td>&nbsp;</td>
                                        </tr>
                                        {clientCards && (
                                            <tr>
                                            <td style={{ width: "60%" }}>
                                                {t("credit-card.added-card")}
                                            </td>
                                            <td>
                                                **** **** **** {clientCards.card_number} - {clientCards.valid} (
                                                {clientCards.card_type})
                                            </td>
                                        </tr>
                                        )}
                                        {contract && (
                                            <>
                                                {contract.status ==
                                                    "not-signed" && (
                                                        <React.Fragment>
                                                            <tr>
                                                                <td>
                                                                    Signature on the
                                                                    Card
                                                                </td>
                                                                <td>
                                                                    {" "}
                                                                    <SignatureCanvas
                                                                        penColor="black"
                                                                        canvasProps={{
                                                                            width: 250,
                                                                            height: 100,
                                                                            className:
                                                                                "sigCanvas",
                                                                        }}
                                                                        ref={sigRef}
                                                                        // onEnd={
                                                                        //     handleSignatureEnd
                                                                        // }
                                                                    />
                                                                    &nbsp;
                                                                </td>
                                                            </tr>
                                                        </React.Fragment>
                                                    )}
                                            </>
                                        )}

                                        <tr>
                                            <td style={{ width: "60%" }}>
                                                Miscellaneous
                                            </td>
                                            <td>
                                                All the employees of the Company
                                                are employed in compliance with
                                                the law and the Company provides
                                                them with all the benefits to
                                                which they are entitled; the
                                                Client has no employee-employer
                                                relationship with the employees
                                                of the Company.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <h6 className="text-underline">
                                Obligations and Statements of the Tenant
                            </h6>
                            <div className="agg-list">
                                <div className="icons">
                                    <img src={star} />
                                </div>
                                <div className="agg-text">
                                    <p>
                                        By signing this agreement, the Tenant
                                        declares that he/she fully and
                                        voluntarily accepts all the terms and
                                        conditions specified in this agreement
                                        and may not have any claim and/or demand
                                        and/or complaint against the Company in
                                        connection with any promise and/or
                                        representation and/or correspondence
                                        and/or draft and/or presentation,
                                        whether done in writing or orally, prior
                                        to the signature of this agreement.
                                    </p>
                                </div>
                            </div>
                            <div className="agg-list">
                                <div className="icons">
                                    <img src={star} />
                                </div>
                                <div className="agg-text">
                                    <p>
                                        The right to use this document is
                                        personal and non-transferable. Note that
                                        it is prohibited to distribute and/or
                                        duplicate and/or copy and/or publish
                                        this document without prior express
                                        permission in writing from the
                                        management of the Company.
                                    </p>
                                </div>
                            </div>
                            <div className="agg-list">
                                <div className="icons">
                                    <img src={star} />
                                </div>
                                <div className="agg-text">
                                    <p>
                                        The Tenant states that he/she will fully
                                        cooperate with the Company for the
                                        performance of the work and/or service
                                        the Company should provide, as detailed
                                        above in this agreement.
                                    </p>
                                </div>
                            </div>
                            <div className="agg-list">
                                <div className="icons">
                                    <img src={star} />
                                </div>
                                <div className="agg-text">
                                    <p>
                                        The Tenant hereby undertakes not to
                                        directly or indirectly (through another
                                        company or person) hire the employees of
                                        the Company, even if they are no longer
                                        employees of the Company and/or after
                                        the termination of the agreement between
                                        the Parties.
                                    </p>
                                </div>
                            </div>
                            <div className="agg-list">
                                <div className="icons">
                                    <img src={star} />
                                </div>
                                <div className="agg-text">
                                    <p>
                                        All the orders placed by the Tenant in
                                        connection with any service and/or work
                                        he/she requests shall be governed by the
                                        order terms and conditions listed on the
                                        website of the Company; the website
                                        address is as follows:
                                        www.broomservice.co.il
                                    </p>
                                </div>
                            </div>
                            <div className="agg-list">
                                <div className="icons">
                                    <img src={star} />
                                </div>
                                <div className="agg-text">
                                    <p>
                                        This agreement is valid until the notice
                                        of a change or cancellation or freeze of
                                        any of the parties and in accordance
                                        with the company service cancellation
                                        procedures.
                                    </p>
                                </div>
                            </div>
                            <div className="agg-list">
                                <div className="icons">
                                    <img src={star} />
                                </div>
                                <div className="agg-text">
                                    <p>
                                        In case of cancellation of a work order
                                        after closing the weekly arrangement and
                                        up to 24 hours before the date, the
                                        tenant undertakes to pay the company 50
                                        percent of the cost of the visit.
                                    </p>
                                </div>
                            </div>
                            <div className="agg-list">
                                <div className="icons">
                                    <img src={star} />
                                </div>
                                <div className="agg-text">
                                    <p>
                                        In case of cancellation of a work order
                                        less than 24 hours before the date, the
                                        tenant undertakes to pay the company 100
                                        percent of the cost of the visit.
                                    </p>
                                </div>
                            </div>
                            <h6 className="text-underline">
                                Obligations and Statements of the Company
                            </h6>
                            <div className="agg-list">
                                <div className="icons">
                                    <img src={star} />
                                </div>
                                <div className="agg-text">
                                    <p>
                                        The Company agrees to perform the work
                                        and/or provide the service and/or
                                        products devotedly, in a reliable and
                                        timely manner and maintain a high
                                        standard of service, all pursuant to the
                                        dates determined by the Tenant, through
                                        suppliers and/or employees and/or
                                        sub-contractors working on its behalf.
                                        The Company hereby declares that it has
                                        the ability and skills to perform the
                                        work and/or provide the service and/or
                                        products specified in this agreement.
                                    </p>
                                </div>
                            </div>
                            <div className="agg-list">
                                <div className="icons">
                                    <img src={star} />
                                </div>
                                <div className="agg-text">
                                    <p>
                                        The company undertakes to pay its
                                        employees a salary according to law.
                                    </p>
                                </div>
                            </div>
                            <div className="agg-list">
                                <div className="icons">
                                    <img src={star} />
                                </div>
                                <div className="agg-text">
                                    <p>
                                        The Company undertakes to timely perform
                                        the work and/or provide the service,
                                        pursuant to the dates determined in this
                                        agreement and/or any dates determined by
                                        the Tenant in the orders he/she may
                                        place through the website of the
                                        Company. Reasonable delay in the
                                        performance of the work and/or the
                                        arrival of any employee of the Company
                                        to the Tenant&lsquos address, given the
                                        relevant circumstances, may not be
                                        deemed breach of this agreement on part
                                        of the Company.{" "}
                                    </p>
                                </div>
                            </div>
                            <div className="agg-list">
                                <div className="icons">
                                    <img src={star} />
                                </div>
                                <div className="agg-text">
                                    <p>
                                        The Company hereby states and notifies
                                        the Tenant that it may not be held
                                        responsible to any cancelation and/or
                                        postponement of the work and/or service
                                        and/or any delay in the performance of
                                        the work and/or service resulting from
                                        extraordinary circumstances it cannot
                                        control and/or such that derive from
                                        force majeure. For the purpose of this
                                        section, force majeure is defined as
                                        follows: wars, protests, emergencies,
                                        conscription (whether partial or full),
                                        including conscription of reserve duty
                                        forces, including conscription of
                                        employees and/or suppliers and/or
                                        contractors and/or any representatives
                                        of the Company and/or its suppliers,
                                        strikes, diseases and/or epidemics,
                                        mourning (including national days of
                                        mourning), natural disasters, inability
                                        to move on the roads, fire, state of
                                        preparedness to emergency, and any
                                        situation of any kind that the Company
                                        cannot control. In any of the
                                        aforementioned situations, the Tenant
                                        may not have any claim and/or demand
                                        and/or complaint against the Company
                                        and/or any of its representatives, and
                                        the Parties shall schedule the
                                        performance of the work and/or service
                                        on a later date that would be agreed
                                        upon between the Parties.{" "}
                                    </p>
                                </div>
                            </div>
                            <div className="agg-list">
                                <div className="icons">
                                    <img src={star} />
                                </div>
                                <div className="agg-text">
                                    <p>
                                        The Parties hereby agree that this
                                        agreement is a framework agreement and
                                        that the Tenant will submit to the
                                        Company, from time to time, written work
                                        orders that shall be deemed integral
                                        part of this agreement.{" "}
                                    </p>
                                </div>
                            </div>
                            <div className="agg-list">
                                <div className="icons">
                                    <img src={star} />
                                </div>
                                <div className="agg-text">
                                    <p>
                                        The company is not responsible for any
                                        direct or indirect damage, consequential
                                        or special, of any kind, that will be
                                        caused to the customer and / or any
                                        third party as a result of receiving
                                        service by the company and its employees
                                        or anyone on its behalf.{" "}
                                    </p>
                                </div>
                            </div>
                            <div className="agg-list">
                                <div className="icons">
                                    <img src={star} />
                                </div>
                                <div className="agg-text">
                                    <p>This agreement is valid for 1 year.</p>
                                </div>
                            </div>
                            <h6 className="text-underline">General</h6>
                            <div className="agg-list">
                                <div className="icons">
                                    <img src={star} />
                                </div>
                                <div className="agg-text">
                                    <p>
                                        This agreement summarizes and
                                        establishes any and all understandings
                                        between the Parties; upon signature, no
                                        other agreement that was made between
                                        the Parties will be in force, and no
                                        amendment and/or agreement and/or
                                        alteration and/or addition and/or
                                        reduction and/or extension and/or waiver
                                        in connection with anything related to
                                        this agreement may be in force, unless
                                        done in writing and signed by the
                                        Parties. In case any section, provision
                                        or obligation in this agreement is null
                                        or unenforceable, all the other
                                        provisions of this agreement shall
                                        remain in force. No failure or delay by
                                        either party in exercising any of its
                                        rights under this agreement may be
                                        deemed waiver of such rights.
                                    </p>
                                </div>
                            </div>
                            <div className="agg-list">
                                <div className="icons">
                                    <img src={star} />
                                </div>
                                <div className="agg-text">
                                    <p>
                                        By signing this agreement, the customer
                                        agrees to register his details in a
                                        database for the purpose of receiving
                                        direct mailing of everything.{" "}
                                    </p>
                                </div>
                            </div>
                            <div className="agg-list">
                                <div className="icons">
                                    <img src={star} />
                                </div>
                                <div className="agg-text">
                                    <p>
                                        Addresses of the Parties for the purpose
                                        of this agreement are as specified in
                                        the introduction of this agreement. Any
                                        notice sent by either party to the
                                        other, to the aforementioned addresses,
                                        shall be deemed effective within 72
                                        hours from its delivery to a post office
                                        branch in Israel for registered mail
                                        shipment and upon its delivery to the
                                        addressee, if it is delivered by hand,
                                        or upon receipt of transmission
                                        certificate, if it is sent by fax.
                                    </p>
                                </div>
                            </div>
                            <h6 className="text-center text-underline mt-3 mb-4">
                                In witness whereof the Parties have signed:
                            </h6>
                            <div className="row">
                                <div className="col-sm-6">
                                    <h5 className="mt-2 mb-4">The Tenant</h5>

                                    {contract && (
                                        <>
                                            {contract.status == "not-signed" ? (
                                                <React.Fragment>
                                                    <h6>
                                                        Draw Signature with
                                                        mouse or touch
                                                    </h6>
                                                    <SignatureCanvas
                                                        penColor="black"
                                                        canvasProps={{
                                                            width: 250,
                                                            height: 100,
                                                            className:
                                                                "sigCanvas",
                                                        }}
                                                        ref={sigRef}
                                                        // onEnd={
                                                        //     handleSignatureEnd
                                                        // }
                                                    />
                                                </React.Fragment>
                                            ) : (
                                                <img
                                                    src={
                                                        contract.signature
                                                            ? contract.signature
                                                            : "/images/NA.jpg"
                                                    }
                                                    className="img-fluid"
                                                    alt="Company"
                                                />
                                            )}
                                        </>
                                    )}
                                </div>
                                <div className="col-sm-6">
                                    <div className="float-right">
                                        <h5 className="mt-2 mb-4">
                                            The Company
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

                                {contract && (
                                    <>
                                        {contract.status == "un-verified" && (
                                            <div className="col-sm-6">
                                                <div className="mt-2 ">
                                                    <input
                                                        className="btn btn-warning"
                                                        onClick={handleVerify}
                                                        value="Verify"
                                                    />
                                                </div>
                                            </div>
                                        )}

                                        {contract.status == "verified" && (
                                            <div className="col-sm-6">
                                                <div className="mt-2 ">
                                                    <input
                                                        className="btn btn-success"
                                                        value="Verified"
                                                    />
                                                </div>
                                            </div>
                                        )}
                                    </>
                                )}
                            </div>

                            <div className="mb-4">&nbsp;</div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}





{/* <div className="mt-2">
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
                                </div> */}