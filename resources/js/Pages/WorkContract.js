// import React, { useRef, useState, useEffect, useMemo } from "react";
// import logo from "../Assets/image/sample.svg";
// import star from "../Assets/image/icons/blue-star.png";
// import SignatureCanvas from "react-signature-canvas";
// import companySign from "../Assets/image/company-sign.png";
// import axios from "axios";
// import { useParams } from "react-router-dom";
// import swal from "sweetalert";
// import { useTranslation } from "react-i18next";
// import i18next from "i18next";
// import Swal from "sweetalert2";
// import moment from "moment";

// export default function WorkContract() {
//     const { t } = useTranslation();

//     const [offer, setOffer] = useState(null);
//     const [services, setServices] = useState([]);
//     const [client, setClient] = useState(null);
//     const [contract, setContract] = useState(null);
//     const [signature, setSignature] = useState(null);
//     const [cardSignature, setCardSignature] = useState(null);
//     const [Aaddress, setAaddress] = useState(null);
//     const [status, setStatus] = useState("");
//     const [sessionURL, setSessionURL] = useState("");
//     const [addCardBtnDisabled, setAddCardBtnDisabled] = useState(false);
//     const [checkingForCard, setCheckingForCard] = useState(false);
//     const [clientCards, setClientCards] = useState([]);
//     const [selectedClientCardID, setSelectedClientCardID] = useState(null);
//     const [isCardAdded, setIsCardAdded] = useState(false);
//     const [consentToAds, setConsentToAds] = useState(true);
//     const [signDate, setSignDate] = useState(moment().format("DD/MM/YYYY"));

//     const params = useParams();
//     const sigRef1 = useRef();
//     const sigRef2 = useRef();
//     const consentToAdsRef = useRef();

//     const handleAccept = (e) => {
//         if (!selectedClientCardID) {
//             swal(t("work-contract.messages.card_err"), "", "error");
//             return false;
//         }

//         if (!signature) {
//             swal(t("work-contract.messages.sign_err"), "", "error");
//             return false;
//         }

//         const data = {
//             unique_hash: params.id,
//             offer_id: offer.id,
//             client_id: offer.client.id,
//             card_id: selectedClientCardID,
//             additional_address: Aaddress,
//             status: "un-verified",
//             signature: signature,
//             consent_to_ads: consentToAdsRef.current.checked ? 1 : 0,
//             form_data: { card_signature: cardSignature },
//         };

//         axios
//             .post(`/api/client/accept-contract`, data)
//             .then((res) => {
//                 if (res.data.error) {
//                     swal("", res.data.error, "error");
//                 } else {
//                     setStatus("un-verified");
//                     swal(t("work-contract.messages.success"), "", "success");
//                     setTimeout(() => {
//                         window.location.reload(true);
//                     }, 2000);
//                 }
//             })
//             .catch((e) => {
//                 Swal.fire({
//                     title: "Error!",
//                     text: e.response.data.message,
//                     icon: "error",
//                 });
//             });
//     };

//     const handleSignature1End = () => {
//         setSignature(sigRef1.current.toDataURL());
//     };

//     const clearSignature1 = () => {
//         sigRef1.current.clear();
//         setSignature(null);
//     };

//     const handleSignature2End = () => {
//         setCardSignature(sigRef2.current.toDataURL());
//     };

//     const clearSignature2 = () => {
//         sigRef2.current.clear();
//         setCardSignature(null);
//     };

//     const getOffer = () => {
//         axios
//             .post(`/api/client/contracts/${params.id}`)
//             .then((res) => {
//                 if (res.data.offer) {
//                     const _contract = res.data.contract;
//                     setOffer(res.data.offer);
//                     setServices(JSON.parse(res.data.offer.services));
//                     setClient(res.data.offer.client);
//                     setContract(_contract);
//                     setStatus(_contract.status);
//                     setConsentToAds(_contract.consent_to_ads ? true : false);

//                     setClientCards(res.data.cards);
//                     setSelectedClientCardID(_contract.card_id);
//                     if (_contract.status != "not-signed") {
//                         setIsCardAdded(true);
//                     }
//                     if (_contract.signed_at) {
//                         setSignDate(
//                             moment(_contract.signed_at).format("DD/MM/YYYY")
//                         );
//                     }
//                     i18next.changeLanguage(res.data.offer.client.lng);

//                     if (res.data.offer.client.lng == "heb") {
//                         import("../Assets/css/rtl.css");
//                         document
//                             .querySelector("html")
//                             .setAttribute("dir", "rtl");
//                     } else {
//                         document.querySelector("html").removeAttribute("dir");
//                     }
//                 } else {
//                     setOffer({});
//                     setServices([]);
//                     setClient(null);
//                     setContract(null);
//                 }
//             })
//             .catch((e) => {
//                 Swal.fire({
//                     title: "Error!",
//                     text: e.response.data.message,
//                     icon: "error",
//                 });
//             });
//     };

//     const RejectContract = (e, id) => {
//         e.preventDefault();
//         Swal.fire({
//             title: t("work-contract.messages.reject_title"),
//             text: t("work-contract.messages.reject_text"),
//             icon: "warning",
//             showCancelButton: true,
//             confirmButtonColor: "#3085d6",
//             cancelButtonColor: "#d33",
//             cancelButtonText: t("work-contract.messages.cancel"),
//             confirmButtonText: t("work-contract.messages.yes_reject"),
//         }).then((result) => {
//             if (result.isConfirmed) {
//                 axios
//                     .post(`/api/client/reject-contract`, { id: id })
//                     .then((response) => {
//                         Swal.fire(
//                             t("work-contract.messages.reject"),
//                             t("work-contract.messages.reject_msg"),
//                             "success"
//                         );
//                         setTimeout(() => {
//                             window.location.reload(true);
//                         }, 2000);
//                     });
//                 setStatus("declined");
//             }
//         });
//     };

//     const handleCard = () => {
//         setAddCardBtnDisabled(true);

//         axios
//             .post(`/api/client/contracts/${params.id}/initialize-card`, {})
//             .then((response) => {
//                 setCheckingForCard(true);

//                 setSessionURL(response.data.redirect_url);
//                 $("#exampleModal").modal("show");
//             })
//             .catch((e) => {
//                 Swal.fire({
//                     title: "Error!",
//                     text: e.response.data.message,
//                     icon: "error",
//                 });
//             });
//     };

//     const handleCardSelect = (e) => {
//         if (e.target.checked) {
//             setSelectedClientCardID(e.target.value);
//         } else {
//             setSelectedClientCardID(null);
//         }
//     };

//     useEffect(() => {
//         let _intervalID;

//         if (checkingForCard) {
//             _intervalID = setInterval(() => {
//                 if (checkingForCard) {
//                     axios
//                         .post(
//                             `/api/client/contracts/${params.id}/check-card`,
//                             {}
//                         )
//                         .then((response) => {
//                             if (response.data.card) {
//                                 setClientCards([response.data.card]);
//                                 setSelectedClientCardID(response.data.card.id);
//                                 setCheckingForCard(false);
//                                 setIsCardAdded(true);
//                                 clearInterval(_intervalID);
//                             }
//                         })
//                         .catch((e) => {
//                             setCheckingForCard(false);
//                             clearInterval(_intervalID);

//                             Swal.fire({
//                                 title: "Error!",
//                                 text: e.response.data.message,
//                                 icon: "error",
//                             });
//                         });
//                 }
//             }, 2000);
//         }

//         return () => clearInterval(_intervalID);
//     }, [checkingForCard]);

//     useEffect(() => {
//         getOffer();

//         setTimeout(() => {
//             document.querySelector(".parent").style.display = "block";
//             var c = document.querySelectorAll("canvas");
//             c.forEach((e, i) => {
//                 e.setAttribute("width", "300px");
//                 e.setAttribute("height", "115px");
//             });
//         }, 500);
//     }, []);

//     const workerHours = (_service) => {
//         if (_service.type === "hourly") {
//             return _service.workers.map((i) => i.jobHours).join(", ");
//         }

//         return "-";
//     };

//     const clientName = useMemo(() => {
//         return client ? `${client.firstname} ${client.lastname}` : "";
//     }, [client]);

//     const showWorkerHours = useMemo(() => {
//         return services.filter((i) => i.type !== "fixed").length > 0;
//     }, [services]);

//     const selectedClientCard = useMemo(() => {
//         return clientCards.find((i) => i.id == selectedClientCardID);
//     }, [clientCards, selectedClientCardID]);

//     return (
//         <div className="container parent" style={{ display: "none" }}>
//             <div className="send-offer client-contract sendOfferRtl">
//                 <div className="maxWidthControl dashBox mb-4">
//                     <div className="row border-bottom pb-2">
//                         <div className="col-sm-6">
//                             <h4 className="m-0">
//                                 {t("client.contract-form.business_name_value")}
//                             </h4>
//                             <p className="m-0">
//                                 {t("client.contract-form.h_p")} 515184208
//                             </p>
//                             <p className="m-0">
//                                 {t("client.contract-form.address")}:{" "}
//                                 {t("client.contract-form.address_value")}
//                             </p>
//                             <p className="m-0">
//                                 {t("client.contract-form.phone")}: 03-5257060
//                             </p>
//                             <p className="m-0">
//                                 {t("client.contract-form.email")}:
//                                 Office@broomservice.co.il
//                             </p>
//                         </div>
//                         <div className="col-sm-6">
//                             <div className="float-right">
//                                 <svg
//                                     width="190"
//                                     height="77"
//                                     xmlns="http://www.w3.org/2000/svg"
//                                     xmlnsXlink="http://www.w3.org/1999/xlink"
//                                 >
//                                     <image
//                                         xlinkHref={logo}
//                                         width="190"
//                                         height="77"
//                                     ></image>
//                                 </svg>
//                             </div>
//                         </div>
//                     </div>
//                     <div className="row mt-4">
//                         <div className="col-md-12 d-flex">
//                             <label htmlFor="">
//                                 {t("client.contract-form.name")}:
//                             </label>
//                             <span className="text-underline mx-3">
//                                 {clientName}
//                             </span>
//                             <div className="mx-4">
//                                 <label htmlFor="">
//                                     {t("client.contract-form.hp_em_tz")}:
//                                 </label>
//                             </div>
//                             <span className="text-underline mx-3">
//                                 {client ? client.vat_number : ""}
//                             </span>
//                         </div>
//                         <div className="col-md-12">
//                             <label htmlFor="">
//                                 {t("client.contract-form.address")}:
//                             </label>
//                             <span className="text-underline mx-3"></span>
//                         </div>
//                         <div className="col-md-12 d-flex">
//                             <label htmlFor="">
//                                 {t("client.contract-form.phone")}:
//                             </label>
//                             <span className="text-underline mx-3">
//                                 {client ? client.phone : ""}
//                             </span>
//                             <div className="mx-4">
//                                 <label htmlFor="">
//                                     {t("client.contract-form.fax")}:
//                                 </label>
//                             </div>
//                             <span className="text-underline mx-3"></span>
//                         </div>
//                         <div className="col-md-12">
//                             <label htmlFor="">
//                                 {t("client.contract-form.email")}:
//                             </label>
//                             <span className="text-underline mx-3">
//                                 {client ? client.email : ""}
//                             </span>
//                         </div>
//                     </div>
//                     <div className="row mt-4">
//                         <div className="col-md-12">
//                             <p className="text-center">
//                                 {t(
//                                     "client.contract-form.contractual_agreement"
//                                 )}
//                             </p>
//                             <p>1. {t("client.contract-form.ca1")}</p>

//                             <div
//                                 className="text-justify"
//                                 style={{ textIndent: "50px" }}
//                             >
//                                 <p>
//                                     1.1.{" "}
//                                     {t("client.contract-form.ca1_1", {
//                                         name: clientName,
//                                     })}
//                                 </p>
//                                 <p>1.2. {t("client.contract-form.ca1_2")}</p>
//                                 <p>1.3. {t("client.contract-form.ca1_3")}</p>
//                             </div>
//                         </div>
//                     </div>
//                     <div className="row mt-4">
//                         <div className="col-md-12">
//                             <p>2. {t("client.contract-form.ca2")}</p>
//                         </div>
//                         <div className="col-md-12">
//                             <div className="table-responsive">
//                                 <table className="table table-sm table-bordered table-striped">
//                                     <thead>
//                                         <tr>
//                                             <th>
//                                                 {t("price_offer.address_text")}
//                                             </th>
//                                             <th>
//                                                 {t("price_offer.service_txt")}
//                                             </th>
//                                             <th>{t("price_offer.type")}</th>
//                                             <th>
//                                                 {t("price_offer.freq_s_txt")}
//                                             </th>
//                                             {showWorkerHours && (
//                                                 <th>
//                                                     {t(
//                                                         "price_offer.worker_hours"
//                                                     )}
//                                                 </th>
//                                             )}
//                                             <th>
//                                                 {t("price_offer.amount_txt")}
//                                             </th>
//                                         </tr>
//                                     </thead>
//                                     <tbody>
//                                         {services.map((s, i) => {
//                                             return (
//                                                 <tr key={i}>
//                                                     <td>
//                                                         {s.address &&
//                                                         s.address.address_name
//                                                             ? s.address
//                                                                   .address_name
//                                                             : "NA"}
//                                                     </td>
//                                                     <td>
//                                                         {s.service == 10
//                                                             ? s.other_title
//                                                             : s.name}
//                                                     </td>
//                                                     <td>{s.type}</td>
//                                                     <td>{s.freq_name} </td>
//                                                     {showWorkerHours && (
//                                                         <td>
//                                                             {workerHours(s)}
//                                                         </td>
//                                                     )}
//                                                     {s.type == "fixed" ? (
//                                                         <td>
//                                                             {s.workers.length *
//                                                                 s.fixed_price}{" "}
//                                                             {t(
//                                                                 "global.currency"
//                                                             )}
//                                                         </td>
//                                                     ) : (
//                                                         <td>
//                                                             {s.rateperhour}{" "}
//                                                             {t(
//                                                                 "global.currency"
//                                                             )}{" "}
//                                                             {t(
//                                                                 "global.perhour"
//                                                             )}{" "}
//                                                         </td>
//                                                     )}
//                                                 </tr>
//                                             );
//                                         })}
//                                     </tbody>
//                                 </table>
//                             </div>

//                             <div style={{ textIndent: "50px" }}>
//                                 {t("client.contract-form.the_services")}
//                             </div>
//                         </div>
//                     </div>
//                     <div className="row mt-4">
//                         <div className="col-md-12">
//                             <p>3. {t("client.contract-form.ca3")}</p>
//                             <div
//                                 className="text-justify"
//                                 style={{ textIndent: "50px" }}
//                             >
//                                 <p>3.1. {t("client.contract-form.ca3_1")}</p>
//                                 <p>3.2. {t("client.contract-form.ca3_2")}</p>
//                                 <p>3.3. {t("client.contract-form.ca3_3")}</p>

//                                 <div className="mt-2">
//                                     <p>
//                                         {t(
//                                             "client.contract-form.cc_card_charge_auth"
//                                         )}
//                                     </p>
//                                     <p>
//                                         {t(
//                                             "client.contract-form.cc_declaration"
//                                         )}
//                                     </p>
//                                     <p>
//                                         {t("client.contract-form.cc_details")}
//                                     </p>
//                                     <p>
//                                         {t("client.contract-form.cc_card_type")}
//                                         :{" "}
//                                         {selectedClientCard
//                                             ? selectedClientCard.card_type
//                                             : ""}
//                                     </p>
//                                     <p>
//                                         {t(
//                                             "client.contract-form.cc_holder_name"
//                                         )}
//                                         :{" "}
//                                         {selectedClientCard
//                                             ? selectedClientCard.card_holder_name
//                                             : ""}
//                                     </p>
//                                     <p>
//                                         {t("client.contract-form.cc_id_number")}
//                                         :{" "}
//                                         {selectedClientCard
//                                             ? selectedClientCard.card_holder_id
//                                             : ""}
//                                     </p>
//                                     <p>
//                                         {t("client.contract-form.cc_signature")}
//                                         :
//                                     </p>
//                                     <div>
//                                         {contract && contract.signed_at ? (
//                                             <>
//                                                 {contract.form_data && (
//                                                     <img
//                                                         src={
//                                                             contract.form_data
//                                                                 .card_signature
//                                                         }
//                                                     />
//                                                 )}
//                                             </>
//                                         ) : (
//                                             <>
//                                                 <SignatureCanvas
//                                                     penColor="black"
//                                                     canvasProps={{
//                                                         width: 250,
//                                                         height: 100,
//                                                         className: "sigCanvas",
//                                                     }}
//                                                     ref={sigRef2}
//                                                     onEnd={handleSignature2End}
//                                                 />
//                                                 <button
//                                                     className="btn btn-warning m-3 mb-5"
//                                                     onClick={clearSignature2}
//                                                 >
//                                                     {t(
//                                                         "work-contract.btn_warning_txt"
//                                                     )}
//                                                 </button>
//                                             </>
//                                         )}
//                                     </div>
//                                     <p>
//                                         {t(
//                                             "client.contract-form.add_cc_click_here"
//                                         )}
//                                     </p>

//                                     {clientCards.map((_card, _index) => {
//                                         return (
//                                             <div className="my-3" key={_index}>
//                                                 <label className="form-check-label ">
//                                                     <input
//                                                         type="checkbox"
//                                                         className="form-check-input"
//                                                         value={_card.id}
//                                                         onChange={
//                                                             handleCardSelect
//                                                         }
//                                                         checked={
//                                                             _card.id ==
//                                                             selectedClientCardID
//                                                         }
//                                                         disabled={
//                                                             contract &&
//                                                             contract.status !=
//                                                                 "not-signed"
//                                                         }
//                                                     />
//                                                     **** **** ****{" "}
//                                                     {_card.card_number} -{" "}
//                                                     {_card.valid} (
//                                                     {_card.card_type})
//                                                 </label>
//                                             </div>
//                                         );
//                                     })}

//                                     {!isCardAdded && (
//                                         <button
//                                             type="button"
//                                             className="btn btn-success ac mx-5"
//                                             onClick={(e) => handleCard(e)}
//                                             disabled={addCardBtnDisabled}
//                                         >
//                                             {t(
//                                                 "client.contract-form.add_credit_card"
//                                             )}
//                                         </button>
//                                     )}
//                                     <p>
//                                         {t(
//                                             "client.contract-form.cc_compensation"
//                                         )}
//                                     </p>
//                                 </div>

//                                 <p>3.4. {t("client.contract-form.ca3_4")}</p>

//                                 <label
//                                     className="form-check-label"
//                                     style={{ fontWeight: 500 }}
//                                 >
//                                     <input
//                                         type="checkbox"
//                                         className="form-check-input"
//                                         checked={consentToAds}
//                                         onChange={(e) => {
//                                             setConsentToAds(
//                                                 e.target.checked ? true : false
//                                             );
//                                         }}
//                                         disabled={
//                                             contract &&
//                                             contract.status != "not-signed"
//                                         }
//                                         ref={consentToAdsRef}
//                                     />
//                                     3.5.{" "}
//                                     {t(
//                                         "client.contract-form.direct_mail_declaration"
//                                     )}
//                                 </label>
//                                 <p>
//                                     <strong>
//                                         {t(
//                                             "client.contract-form.direct_mail_note"
//                                         )}
//                                     </strong>
//                                 </p>
//                             </div>
//                         </div>
//                     </div>
//                     <div className="row mt-4">
//                         <div className="col-md-12">
//                             <p>4. {t("client.contract-form.ca4")}</p>
//                             {offer && <p>{offer.comment}</p>}
//                             <p>
//                                 {t("client.contract-form.date")}: {signDate}
//                             </p>
//                         </div>
//                     </div>
//                     <div className="row mt-4">
//                         <div className="col-md-12">
//                             <p className="text-center">
//                                 {t("client.contract-form.general_terms")}
//                             </p>
//                             <p>
//                                 1.{" "}
//                                 <strong>
//                                     {t("client.contract-form.gt1_title")}
//                                 </strong>{" "}
//                                 {t("client.contract-form.gt1")}
//                             </p>
//                             <p>
//                                 2.{" "}
//                                 <strong>
//                                     {t("client.contract-form.gt2_title")}
//                                 </strong>{" "}
//                                 {t("client.contract-form.gt2")}
//                             </p>
//                             <p>
//                                 3.{" "}
//                                 <strong>
//                                     {t("client.contract-form.gt3_title")}
//                                 </strong>{" "}
//                                 {t("client.contract-form.gt3")}
//                             </p>
//                             <p>
//                                 4.{" "}
//                                 <strong>
//                                     {t("client.contract-form.gt4_title")}
//                                 </strong>{" "}
//                                 {t("client.contract-form.gt4")}
//                             </p>
//                             <p>
//                                 5.{" "}
//                                 <strong>
//                                     {t("client.contract-form.gt5_title")}
//                                 </strong>{" "}
//                                 {t("client.contract-form.gt5")}
//                             </p>
//                             <p>
//                                 6.{" "}
//                                 <strong>
//                                     {t("client.contract-form.gt6_title")}
//                                 </strong>{" "}
//                                 {t("client.contract-form.gt6")}
//                             </p>
//                             <p>
//                                 7.{" "}
//                                 <strong>
//                                     {t("client.contract-form.gt7_title")}
//                                 </strong>{" "}
//                                 {t("client.contract-form.gt7")}
//                             </p>
//                             <p>
//                                 8.{" "}
//                                 <strong>
//                                     {t("client.contract-form.gt8_title")}
//                                 </strong>{" "}
//                                 {t("client.contract-form.gt8")}
//                             </p>
//                             <p>
//                                 9.{" "}
//                                 <strong>
//                                     {t("client.contract-form.gt9_title")}
//                                 </strong>{" "}
//                                 {t("client.contract-form.gt9_1")}
//                             </p>
//                             <p>{t("client.contract-form.gt9_2")}</p>
//                             <p>{t("client.contract-form.gt9_3")}</p>
//                             <p>
//                                 10.{" "}
//                                 <strong>
//                                     {t("client.contract-form.gt10_title")}
//                                 </strong>{" "}
//                                 {t("client.contract-form.gt10")}
//                             </p>
//                         </div>
//                     </div>
//                     <div className="shift-30">
//                         {/* <div className="agg-list">
//                             <div className="icons">
//                                 <img src={star} />
//                             </div>
//                             <div className="agg-text">
//                                 <p>{t("work-contract.tenant_txt_1")}</p>
//                             </div>
//                         </div> */}
//                         <div className="row">
//                             <div className="col-sm-6">
//                                 <h5 className="mt-2 mb-4">
//                                     {t("work-contract.the_tenant_subtitle")}
//                                 </h5>
//                                 <h6>{t("work-contract.draw_signature")}</h6>
//                                 {contract && contract.signature != null ? (
//                                     <img src={contract.signature} />
//                                 ) : (
//                                     <>
//                                         <SignatureCanvas
//                                             penColor="black"
//                                             canvasProps={{
//                                                 width: 250,
//                                                 height: 100,
//                                                 className: "sigCanvas",
//                                             }}
//                                             ref={sigRef1}
//                                             onEnd={handleSignature1End}
//                                         />
//                                         <button
//                                             className="btn btn-warning"
//                                             onClick={clearSignature1}
//                                         >
//                                             {t("work-contract.btn_warning_txt")}
//                                         </button>
//                                     </>
//                                 )}
//                             </div>
//                             <div className="col-sm-6">
//                                 <div className="float-right">
//                                     <h5 className="mt-2 mb-4">
//                                         {t("work-contract.the_company")}
//                                     </h5>
//                                 </div>
//                                 <div className="float-right">
//                                     <img
//                                         src={companySign}
//                                         className="img-fluid"
//                                         alt="Company"
//                                     />
//                                 </div>
//                             </div>
//                             {status == "not-signed" ? (
//                                 <div className="col-sm-12 mt-2 float-right">
//                                     <button
//                                         type="button"
//                                         className="btn btn-pink"
//                                         onClick={handleAccept}
//                                     >
//                                         {t("work-contract.accept_contract")}
//                                     </button>
//                                     <button
//                                         type="button"
//                                         className="btn btn-danger ml-2"
//                                         onClick={(e) =>
//                                             RejectContract(e, contract.id)
//                                         }
//                                     >
//                                         {t("work-contract.button_reject")}
//                                     </button>
//                                 </div>
//                             ) : (
//                                 <div className="col-sm-12 mt-2 float-right">
//                                     {status == "un-verified" ||
//                                     status == "verified" ? (
//                                         <h4 className="btn btn-success">
//                                             {t("global.accepted")}
//                                         </h4>
//                                     ) : (
//                                         <h4 className="btn btn-danger">
//                                             {t("global.rejected")}
//                                         </h4>
//                                     )}
//                                 </div>
//                             )}
//                         </div>

//                         <div className="mb-4">&nbsp;</div>

//                         {/*Iframe*/}
//                         <div
//                             className="modal fade"
//                             id="exampleModal"
//                             tabIndex="-1"
//                             role="dialog"
//                             aria-labelledby="exampleModalLabel"
//                             aria-hidden="true"
//                         >
//                             <div
//                                 className="modal-dialog modal-dialog-centered modal-lg"
//                                 role="document"
//                             >
//                                 <div className="modal-content">
//                                     <div className="modal-header">
//                                         <button
//                                             type="button"
//                                             className="btn btn-secondary"
//                                             data-dismiss="modal"
//                                             aria-label="Close"
//                                         >
//                                             {t("work-contract.back_btn")}
//                                         </button>
//                                     </div>
//                                     <div className="modal-body">
//                                         <div className="row">
//                                             <div className="col-sm-12">
//                                                 <div className="form-group">
//                                                     <iframe
//                                                         src={sessionURL}
//                                                         title="Pay Card Transaction"
//                                                         width="100%"
//                                                         height="800"
//                                                     ></iframe>
//                                                 </div>
//                                             </div>
//                                         </div>
//                                     </div>
//                                 </div>
//                             </div>
//                         </div>
//                     </div>
//                 </div>
//             </div>
//         </div>
//     );
// }



import React, { useRef, useState, useEffect } from 'react'
import logo from "../Assets/image/sample.svg";
import star from "../Assets/image/icons/blue-star.png";
import SignatureCanvas from 'react-signature-canvas'
import companySign from "../Assets/image/company-sign.png";
import axios from 'axios';
import { useParams } from 'react-router-dom';
import swal from 'sweetalert';
import Moment from 'moment';
import { useTranslation } from "react-i18next";
import i18next from 'i18next';
import { useNavigate } from 'react-router-dom';


export default function WorkContract() {

    const { t } = useTranslation();
    const navigate = useNavigate();
    const [offer, setoffer] = useState([]);
    const [services, setServices] = useState([]);
    const [client, setClient] = useState([]);
    const [contract, setContract] = useState([]);
    const param = useParams();
    const sigRef = useRef();
    const sigRef2 = useRef();
    const [signature, setSignature] = useState(null);
    const [signature2, setSignature2] = useState(null);
    const [Aaddress, setAaddress] = useState(null);
    const [ctype, setCtype] = useState("");
    const [cname, setCname] = useState("");
    const [cvv, setCvv] = useState("");
    const [status, setStatus] = useState('');
    const [card, setCard] = useState('');
    const [exy, setExy] = useState('0');
    const [exm, setExm] = useState('0');
    const [submit, setSubmit] = useState(false);
    const [oc, setOc] = useState("");
    const [gurl, setGurl] = useState('');
    const [sesid, setSesid] = useState(null);
    const [csdata, setCsdata] = useState(null);
    const [formatValid, setFormatvalid] = useState();
    const [selectedClientCardID, setSelectedClientCardID] = useState(null);
    // const consentToAdsRef = useRef();

    const handleAccept = (e) => {
        // if (!selectedClientCardID) {
        //     swal(t("work-contract.messages.card_err"), "", "error");
        //     return false;
        // }

        if (!signature) {
            swal(t("work-contract.messages.sign_err"), "", "error");
            return false;
        }

        const data = {
            unique_hash: param.id,
            offer_id: offer.id,
            client_id: offer.client.id,
            // card_id: selectedClientCardID,
            additional_address: Aaddress,
            status: "un-verified",
            signature: signature,
            // consent_to_ads: consentToAdsRef.current.checked ? 1 : 0,
            form_data: { card_signature: signature },
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
    }
    const clearSignature = () => {
        sigRef.current.clear();
        setSignature(null);
    }

    const handleSignatureEnd2 = () => {
        setSignature2(sigRef2.current.toDataURL());
    }
    const clearSignature2 = () => {
        sigRef2.current.clear();
        setSignature2(null);
    }
    useEffect(() => {
        console.log(signature);
    }, [signature]);

    const getOffer = () => {
        axios
            .post(`/api/client/contracts/${param.id}`)
            .then((res) => {

                if (res.data.offer) {
                    // console.log(res.data.offer.client,"res");

                    setoffer(res.data.offer);
                    setServices(JSON.parse(res.data.offer.services));
                    setClient(res.data.offer.client);
                    setContract(res.data.contract);
                    setStatus(res.data.contract.status);
                    setOc(res.data.old_contract);

                    if (res.data.contract.add_card == 0 || res.data.old_contract == true) { setSubmit(true); }

                    if (res.data.card != null) {

                        setCsdata(res.data.card);
                        let s = (res.data.card.valid).split('-');
                        let fs = (s[1] + " / " + s[0].substring(2, 4))
                        console.log(fs);
                        setFormatvalid(fs);
                    }
                    i18next.changeLanguage(res.data.offer.client.lng);

                    if (res.data.offer.client.lng == 'heb') {
                        import('../Assets/css/rtl.css')
                        document.querySelector('html').setAttribute('dir', 'rtl')
                    }
                    else
                        document.querySelector('html').removeAttribute('dir');

                    if (res.data.offer.client.lng == 'heb') {
                        document.querySelector('html').setAttribute('dir', 'rtl');
                    }
                } else {
                    setoffer([]);
                    setServices([]);
                    setClient([]);
                    setContract([]);
                };
            })
    }

    const RejectContract = (e, id) => {
        e.preventDefault();
        Swal.fire({
            title: t('work-contract.messages.reject_title'),
            text: t('work-contract.messages.reject_text'),
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            cancelButtonText: t('work-contract.messages.cancel'),
            confirmButtonText: t('work-contract.messages.yes_reject'),
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .post(`/api/client/reject-contract`, { id: id })
                    .then((response) => {
                        Swal.fire(
                            t('work-contract.messages.reject'),
                            t('work-contract.messages.reject_msg'),
                            "success"
                        );

                    });
                setStatus('declined');
            }
        });
    }

    // const handleCard = (e) => {

    //     axios
    //         .get(`/generate-payment/${client.id}`)
    //         .then((res) => {
    //             setGurl(res.data.url);
    //             setSesid(res.data.session_id);
    //             $("#exampleModal2").modal('show');
    //         });

    // }

    useEffect(() => {
        getOffer();
        let dateDropdown = document.getElementById('date-dropdown');

        let currentYear = new Date().getFullYear();
        let earliestYear = 2080;
        while (currentYear <= earliestYear) {
            let dateOption = document.createElement('option');
            dateOption.text = currentYear;
            dateOption.value = currentYear;
            dateDropdown.appendChild(dateOption);
            currentYear += 1;
        }
        setTimeout(() => {
            document.querySelector('.parent').style.display = 'block';
            var c = document.querySelectorAll("canvas");
            c.forEach((e, i) => {
                e.setAttribute('width', '300px');
                e.setAttribute('height', '115px');
            })

        }, 500);



    }, []);

    // let address;
    // if (offer && offer.client) {
    //     address = (offer.client.geo_address) ? (offer.client.geo_address) + ", " : '';
    // }
    // console.log(address);

    return (

        <div className='container parent' style={{ display: "none" }}>
            <div className='send-offer client-contract sendOfferRtl'>
                <div className='maxWidthControl dashBox mb-4'>
                    <div className='row'>
                        <div className='col-sm-6'>
                            <svg width="190" height="77" xmlns="http://www.w3.org/2000/svg" xmlnsXlink="http://www.w3.org/1999/xlink">
                                <image xlinkHref={logo} width="190" height="77"></image>
                            </svg>
                        </div>
                        <div className='col-sm-6'>
                            {

                                (status == 'not-signed') ?
                                    <div className='mt-2 float-right'>
                                        <input className='btn btn-pink' onClick={handleAccept} value={t('work-contract.accept_contract')} />
                                        <input className='btn btn-danger mt-2' onClick={(e) => RejectContract(e, contract.id)} value={t('work-contract.button_reject')} />
                                    </div>
                                    :
                                    <div className='mt-2 float-right headMsg'>
                                        {
                                            (status == 'un-verified' || status == 'verified') ?
                                                <h4 className='btn btn-success'>{t('global.accepted')}</h4>
                                                :
                                                <h4 className='btn btn-danger'>{t('global.rejected')}</h4>
                                        }
                                    </div>
                            }
                        </div>
                    </div>
                    <h4 className='inHead' style={{ whiteSpace: 'pre-wrap' }}>{t('work-contract.inHead')}</h4>
                    <div className='signed'>
                        <p>{t('work-contract.signed')} <span>{client.city ? client.city : 'NA'}</span> on <span>{Moment(contract.created_at).format('DD MMMM,Y')}</span></p>
                    </div>
                    <div className='between'>
                        <p>{t('work-contract.between')}</p>
                        <p>{t('work-contract.broom_service')}</p>

                    </div>
                    <div className='first'>
                        <h2 className='mb-4'>{t('work-contract.first_party_title')}</h2>
                        <p style={{ textAlign: 'center' }}>{t('work-contract.and')}</p>
                        {offer && offer.client && (
                            <>
                                <ul className='list-inline'>
                                    <li className='list-inline-item ml-2'>{t('work-contract.full_name')} <span>{offer.client.firstname + " " + offer.client.lastname}</span></li>
                                    <li className='list-inline-item'>{t('work-contract.city')} <span>{offer.client.city}</span></li>
                                </ul>
                                <ul className='list-inline'>
                                    <li className='list-inline-item ml-2'>{t('work-contract.street_and_number')} <span>{offer.client.geo_address}</span></li>
                                </ul>
                                <ul className='list-inline'>

                                </ul>
                                <ul className='list-inline'>
                                    <li className='list-inline-item ml-2'>{t('work-contract.telephone')} <span>{offer.client.phone}</span></li>
                                    <li className='list-inline-item'>{t('work-contract.email')} <span>{offer.client.email}</span></li>
                                </ul>

                            </>
                        )

                        }
                        <h2 className='mb-4'>{t('work-contract.second_party_title')}</h2>
                        <div className='whereas'>
                            <div className='info-list'>
                                <div className='icons'>
                                    <h4>{t('work-contract.whereas')}</h4>
                                </div>
                                <div className='info-text'>
                                    <p>{t('work-contract.whereas_info_text')}</p>
                                </div>
                            </div>
                            <div className='info-list'>
                                <div className='icons'>
                                    <h4>{t('work-contract.and_whereas')}</h4>
                                </div>
                                <div className='info-text'>
                                    <p>{t('work-contract.and_whereas_info_text')}</p>
                                </div>
                            </div>
                            <div className='info-list'>
                                <div className='icons'>
                                    <h4>{t('work-contract.and_whereas_2')}</h4>
                                </div>
                                <div className='info-text'>
                                    <p>{t('work-contract.and_whereas_2_info_text')}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <h2 className='text-center mb-4'>{t('work-contract.parties_hereby_title')}</h2>
                    <div className='shift-30'>
                        <h6>{t('work-contract.intro_subtitle')}</h6>
                        <div className='agg-list'>
                            <div className='icons'><img src={star} /></div>
                            <div className='agg-text'>
                                <p>{t('work-contract.intro_txt_1')}</p>
                            </div>
                        </div>
                        <div className='agg-list'>
                            <div className='icons'><img src={star} /></div>
                            <div className='agg-text'>
                                <p>{t('work-contract.intro_txt_2')}</p>
                            </div>
                        </div>
                        <div className='agg-list'>
                            <div className='icons'><img src={star} /></div>
                            <div className='agg-text'>
                                <p>{t('work-contract.intro_txt_3')}</p>
                            </div>
                        </div>
                        <div className='agg-list'>
                            <div className='icons'><img src={star} /></div>
                            <div className='agg-text'>
                                <p>{t('work-contract.intro_txt_4')}</p>
                            </div>
                        </div>
                        <h6 className='text-center text-underline'>{t('work-contract.service_subtitle')}</h6>
                        <div className='service-table table-responsive'>
                            <table className='table table-bordered'>
                                <tr>
                                    <td style={{ width: "60%" }}>{t('work-contract.the_service_txt')}</td>
                                    <td>
                                        {services && services.map((s, i) => {

                                            return <p>{((s.service != '10') ? s.name : s.other_title)}</p>
                                        })}
                                    </td>
                                </tr>
                                <tr>
                                    <td style={{ width: "60%" }}>{t('work-contract.location_txt')}</td>
                                    <td>

                                        <br /> <span style={{ fontWeight: "600" }} className='d-block mt-2'>{t('work-contract.other_address_txt')}</span> <br />
                                        {contract && contract.additional_address != null ?
                                            <input type='text' value={contract.additional_address} readOnly className='form-control' />
                                            :
                                            <input type='text' name="additional_address" onChange={(e) => setAaddress(e.target.value)} placeholder={t('work-contract.placeholder_address')} className='form-control' />
                                        }
                                    </td>
                                </tr>
                                <tr>
                                    <td style={{ width: "60%" }}>{t('work-contract.service_delivery_txt')}</td>
                                    <td>{t('work-contract.as_agreed_txt')} </td>
                                </tr>
                                <tr>
                                    <td style={{ width: "60%" }}>{t('work-contract.frequency_txt')}</td>
                                    <td>

                                        {services && services.map((s, i) => {
                                            return (
                                                <p> {s.freq_name}</p>
                                            )
                                        })}

                                    </td>
                                </tr>
                                <tr>
                                    <td style={{ width: "60%" }}>{t('work-contract.consideration_txt')}</td>
                                    <td>
                                        {services && services.map((s, i) => {

                                            return <p>{s.totalamount + t('work-contract.ils') + " + " + t('work-contract.vat') + " " + t('work-contract.for') + " " + ((s.service != '10') ? s.name : s.other_title) + ", " + s.freq_name}</p>
                                        })}
                                    </td>
                                </tr>
                                <tr>
                                    {/* <td style={{width: "60%"}}>{t('work-contract.payment_method')}</td> */}
                                    <td colSpan="2">{t('work-contract.payment_method')}</td>
                                    {/* <td>&nbsp;</td> */}
                                </tr>
                                <tr>
                                    <td colSpan="2">{t('work-contract.hereby_permit_txt')}</td>
                                    {/* <td>&nbsp;</td> */}
                                </tr>


                                {/*<tr>
                                    <td style={{ width: "60%" }}>{t('work-contract.card_number')}</td>
                                    <td>
                                    { contract && contract.name_on_card != null ?
                                      <input type="text" value={contract.name_on_card} className="form-control" readOnly/>
                                      :
                                    <input type='text' name="name_on_card" onChange={(e) => setCname(e.target.value)} className='form-control' placeholder={t('work-contract.card_number')} />
                                    }
                                    </td>
                                </tr>

                                <tr>
                                    <td style={{ width: "60%" }}>{t('work-contract.card_expiry')}</td>
                                    <td>
                                    { contract && contract.name_on_card != null ?
                                      <input type="text" value={contract.name_on_card} className="form-control" readOnly/>
                                      :
                                    <input type='text' name="name_on_card" onChange={(e) => setCname(e.target.value)} className='form-control' placeholder={t('work-contract.card_expiry')} />
                                    }
                                    </td>
                                </tr>*/}

                                {/* {
                                    oc != false &&
                                    <tr>
                                        <td><label className="control-label">
                                            {t('work-contract.card_type')}
                                        </label></td>
                                        <td>
                                            <select className='form-control' onChange={(e) => setCtype(e.target.value)}>
                                                <option> {t('work-contract.please_select')}</option>
                                                <option value='Visa' selected={contract.card_type == 'Visa'}>Visa</option>
                                                <option value='Master Card' selected={contract.card_type == 'Master Card'}>Master Card</option>
                                                <option value='American Express' selected={contract.card_type == 'American Express'}>American Express</option>
                                            </select>
                                        </td>
                                    </tr>
                                }

                                {(oc == true || csdata == null) && <tr>
                                    <td style={{ width: "60%" }}>{t('work-contract.card_name')}</td>
                                    <td>
                                        {contract && contract.name_on_card != null ?
                                            <input type="text" value={contract.name_on_card} className="form-control" readOnly />
                                            :
                                            <input type='text' name="name_on_card" onChange={(e) => setCname(e.target.value)} className='form-control' placeholder={t('work-contract.card_name')} />
                                        }
                                    </td>
                                </tr>} */}

                                {oc == false && csdata &&
                                    <>
                                        {/* <tr>
                                            <td style={{ width: "60%" }}>{t('work-contract.card.four_digits')}</td>
                                            <td>
                                                <span className='form-control'>{csdata.card_number}</span>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td style={{ width: "60%" }}>{t('work-contract.card.valid')}</td>
                                            <td>
                                                <span className='form-control'>{formatValid}</span>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td style={{ width: "60%" }}>{t('work-contract.card.type')}</td>
                                            <td>
                                                <span className='form-control'>{csdata.card_type}</span>
                                            </td>
                                        </tr> */}

                                        <tr>
                                            <td style={{ width: "60%" }}>{t('work-contract.card.holder')}</td>
                                            <td>
                                                <span className='form-control'>{csdata.card_holder}</span>
                                            </td>
                                        </tr>
                                    </>
                                }



                                {/* {
                                    oc != false &&
                                    <tr>
                                        <td> <label className="control-label">
                                            {t('work-contract.card_cvv')}
                                        </label></td>
                                        <td>

                                            {contract && contract.name_on_card != null ?
                                                <input type="text" value={contract.cvv} className="form-control" readOnly />
                                                :
                                                <input type='text' name="cvv" onChange={(e) => setCvv(e.target.value)} onKeyUp={(e) => { if (e.target.value.length >= 3) e.target.value = e.target.value.slice(0, 3); }} className='form-control' placeholder={t('work-contract.card_cvv')} />
                                            }


                                        </td>
                                    </tr>
                                } */}

                                <tr>
                                    <td style={{ width: "60%" }}>{t('work-contract.signature')}</td>
                                    <td>
                                        {contract && contract.card_sign != null ?
                                            <img src={contract.card_sign} />
                                            :
                                            <>
                                                <SignatureCanvas
                                                    penColor="black"
                                                    canvasProps={{ className: 'sigCanvas', width: 300, height: 115 }}
                                                    ref={sigRef2}
                                                    onEnd={handleSignatureEnd2}
                                                />
                                                <button className='btn btn-warning' onClick={clearSignature2}>{t('work-contract.btn_warning_txt')}</button>
                                            </>
                                        }

                                    </td>
                                </tr>


                                {oc == false && contract.add_card == 1 && <tr>
                                    <td style={{ width: "60%" }}>{t('work-contract.add_card_txt')}</td>
                                    <td>
                                        {
                                            (status == 'not-signed' && submit == false) && <button className='btn btn-success ac' onClick={e => handleCard(e)}>{t('work-contract.add_card_btn')}</button>
                                        }
                                        {
                                            (status != 'not-signed') && <span className='text text-success font-weight-bold' > Verified </span>
                                        }
                                    </td>
                                </tr>
                                }



                                <tr>
                                    <td style={{ width: "60%" }}>{t('work-contract.miscellaneous_txt')}</td>
                                    <td>{t('work-contract.employees_txt')}</td>
                                </tr>
                            </table>
                        </div>
                        <h6 className='text-underline'>{t('work-contract.tenant_subtitle')}</h6>
                        <div className='agg-list'>
                            <div className='icons'><img src={star} /></div>
                            <div className='agg-text'>
                                <p>{t('work-contract.tenant_txt_1')}</p>
                            </div>
                        </div>
                        <div className='agg-list'>
                            <div className='icons'><img src={star} /></div>
                            <div className='agg-text'>
                                <p>{t('work-contract.tenant_txt_2')}</p>
                            </div>
                        </div>
                        <div className='agg-list'>
                            <div className='icons'><img src={star} /></div>
                            <div className='agg-text'>
                                <p>{t('work-contract.tenant_txt_3')}</p>
                            </div>
                        </div>
                        <div className='agg-list'>
                            <div className='icons'><img src={star} /></div>
                            <div className='agg-text'>
                                <p>{t('work-contract.tenant_txt_4')}</p>
                            </div>
                        </div>
                        <div className='agg-list'>
                            <div className='icons'><img src={star} /></div>
                            <div className='agg-text'>
                                <p style={{ whiteSpace: 'pre-wrap' }}>{t('work-contract.tenant_txt_5')}</p>
                            </div>
                        </div>
                        <div className='agg-list'>
                            <div className='icons'><img src={star} /></div>
                            <div className='agg-text'>
                                <p style={{ backgroundColor: 'yellow' }}>{t('work-contract.tenant_txt_6')}</p>
                            </div>
                        </div>
                        <div className='agg-list'>
                            <div className='icons'><img src={star} /></div>
                            <div className='agg-text'>
                                <p style={{ backgroundColor: 'yellow' }}>{t('work-contract.tenant_txt_7')}</p>
                            </div>
                        </div>
                        <div className='agg-list'>
                            <div className='icons'><img src={star} /></div>
                            <div className='agg-text'>
                                <p style={{ backgroundColor: 'yellow' }}>{t('work-contract.tenant_txt_8')}</p>
                            </div>
                        </div>
                        <h6 className='text-underline'>{t('work-contract.company_subtitle')}</h6>
                        <div className='agg-list'>
                            <div className='icons'><img src={star} /></div>
                            <div className='agg-text'>
                                <p style={{ whiteSpace: 'pre-wrap' }}>{t('work-contract.company_txt_1')}</p>
                            </div>
                        </div>
                        <div className='agg-list'>
                            <div className='icons'><img src={star} /></div>
                            <div className='agg-text'>
                                <p>{t('work-contract.company_txt_2')}</p>
                            </div>
                        </div>
                        <div className='agg-list'>
                            <div className='icons'><img src={star} /></div>
                            <div className='agg-text'>
                                <p>{t('work-contract.company_txt_3')} </p>
                            </div>
                        </div>
                        <div className='agg-list'>
                            <div className='icons'><img src={star} /></div>
                            <div className='agg-text'>
                                <p>{t('work-contract.company_txt_4')} </p>
                            </div>
                        </div>
                        <div className='agg-list'>
                            <div className='icons'><img src={star} /></div>
                            <div className='agg-text'>
                                <p>{t('work-contract.company_txt_5')} </p>
                            </div>
                        </div>
                        <div className='agg-list'>
                            <div className='icons'><img src={star} /></div>
                            <div className='agg-text'>
                                <p>{t('work-contract.company_txt_6')} </p>
                            </div>
                        </div>
                        <div className='agg-list'>
                            <div className='icons'><img src={star} /></div>
                            <div className='agg-text'>
                                <p>{t('work-contract.company_txt_7')}</p>
                            </div>
                        </div>
                        <h6 className='text-underline'>{t('work-contract.general_subtitle')}</h6>
                        <div className='agg-list'>
                            <div className='icons'><img src={star} /></div>
                            <div className='agg-text'>
                                <p>{t('work-contract.general_txt_1')}</p>
                            </div>
                        </div>
                        <div className='agg-list'>
                            <div className='icons'><img src={star} /></div>
                            <div className='agg-text'>
                                <p>{t('work-contract.general_txt_2')} </p>
                            </div>
                        </div>
                        <div className='agg-list'>
                            <div className='icons'><img src={star} /></div>
                            <div className='agg-text'>
                                <p>{t('work-contract.general_txt_3')}</p>
                            </div>
                        </div>
                        <h6 className='text-center text-underline mt-3 mb-4'>{t('work-contract.signed_title')}</h6>
                        <div className='row'>
                            <div className='col-sm-6'>
                                <h5 className='mt-2 mb-4'>{t('work-contract.the_tenant_subtitle')}</h5>
                                <h6>{t('work-contract.draw_signature')}</h6>
                                {contract && contract.signature != null ?
                                    <img src={contract.signature} />
                                    :
                                    <>
                                        <SignatureCanvas
                                            penColor="black"
                                            canvasProps={{ className: 'sigCanvas' }}
                                            ref={sigRef}
                                            onEnd={handleSignatureEnd}
                                        />
                                        <button className='btn btn-warning' onClick={clearSignature}>{t('work-contract.btn_warning_txt')}</button>
                                    </>
                                }
                            </div>
                            <div className='col-sm-6'>
                                <div className='float-right'>
                                    <h5 className='mt-2 mb-4'>{t('work-contract.the_company')}</h5>
                                </div>
                                <div className='float-right'>
                                    <img src={companySign} className='img-fluid' alt='Company' />
                                </div>
                            </div>
                            {

                                (status == 'not-signed') ?
                                    <div className=' col-sm-12 mt-2 float-right'>
                                        <input className='btn btn-pink' onClick={handleAccept} value={t('work-contract.accept_contract')} />
                                    </div>
                                    : ''
                            }

                        </div>

                        <div className='mb-4'>&nbsp;</div>
                        <div className="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                            <div className="modal-dialog" role="document">
                                <div className="modal-content">
                                    <div className="modal-header">
                                        <h5 className="modal-title" id="exampleModalLabel">Add credit card</h5>
                                        <button type="button" className="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div className="modal-body">

                                        <div className="row">


                                            <div className="col-sm-12">
                                                <div className="form-group">
                                                    <label className="control-label">
                                                        {t('work-contract.card_type')}
                                                    </label>
                                                    <select className='form-control' onChange={(e) => setCtype(e.target.value)}>
                                                        <option> {t('work-contract.please_select')}</option>
                                                        <option value='Visa'>Visa</option>
                                                        <option value='Master Card'>Master Card</option>
                                                        <option value='American Express'>American Express</option>
                                                    </select>


                                                </div>
                                            </div>

                                            <div className="col-sm-12">
                                                <div className="form-group">
                                                    <label className="control-label">
                                                        {t('work-contract.card_number')}
                                                    </label>
                                                    <input
                                                        type="number"
                                                        onChange={(e) => {
                                                            e.target.value = e.target.value.slice(0, 16)
                                                            setCard(e.target.value);

                                                        }
                                                        }

                                                        className="form-control"
                                                        required
                                                        placeholder={t('work-contract.card_number')}
                                                    />


                                                </div>
                                            </div>

                                            <div className="col-sm-12">
                                                <div className="form-group">
                                                    <label className="control-label">
                                                        {t('work-contract.card_ex_year')}
                                                    </label>
                                                    <select id='date-dropdown' className='form-control' onChange={e => setExy(e.target.value)}>
                                                        <option value="0">{t('work-contract.select_year')}</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div className="col-sm-12">
                                                <div className="form-group">
                                                    <label className="control-label">
                                                        {t('work-contract.card_ex_month')}
                                                    </label>
                                                    <select className='form-control' onChange={e => setExm(e.target.value)}>
                                                        <option value="0">{t('work-contract.select_month')}</option>
                                                        <option value="01" >01</option>
                                                        <option value="02" >02</option>
                                                        <option value="03" >03</option>
                                                        <option value="04" >04</option>
                                                        <option value="05" >05</option>
                                                        <option value="06" >06</option>
                                                        <option value="07" >07</option>
                                                        <option value="08" >08</option>
                                                        <option value="09" >09</option>
                                                        <option value="10" >10</option>
                                                        <option value="11" >11</option>
                                                        <option value="12" >12</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div className="col-sm-12">
                                                <div className="form-group">
                                                    <label className="control-label">
                                                        {t('work-contract.card_cvv')}
                                                    </label>
                                                    <input type='text' name="cvv" onChange={(e) => setCvv(e.target.value)} onKeyUp={(e) => { if (e.target.value.length >= 3) e.target.value = e.target.value.slice(0, 3); }} className='form-control' placeholder={t('work-contract.card_cvv')} />
                                                </div>
                                            </div>

                                        </div>


                                    </div>
                                    <div className="modal-footer">
                                        <button type="button" className="btn btn-secondary closeb" data-dismiss="modal">{t('client.jobs.view.close')}</button>
                                        <button type="button" onClick={e => handleCard(e)} className="btn btn-primary msbtn">{t('work-contract.model_submit')}</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/*Iframe*/}
                        <div className="modal fade" id="exampleModal2" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
                            <div className="modal-dialog modal-dialog-centered modal-lg" role="document">
                                <div className="modal-content" >
                                    <div className="modal-header">
                                        <button type="button" className="btn btn-secondary" data-dismiss="modal" aria-label="Close">
                                            {t('work-contract.back_btn')}
                                        </button>
                                    </div>
                                    <div className="modal-body">

                                        <div className="row">


                                            <div className="col-sm-12">
                                                <div className="form-group">
                                                    <iframe src={gurl} title="Pay Card Transaction" width="100%" height="800"></iframe>
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

    )
}
