import React, { useRef, useState, useEffect , useMemo} from 'react'
import Sidebar from '../../Layouts/ClientSidebar';
import logo from "../../../Assets/image/sample.svg";
import star from "../../../Assets/image/icons/blue-star.png";
import SignatureCanvas from 'react-signature-canvas'
import companySign from "../../../Assets/image/company-sign.png";
import axios from 'axios';
import { useParams } from 'react-router-dom';
import swal from 'sweetalert';
import moment from 'moment';
import { useTranslation } from 'react-i18next';
import { Base64 } from 'js-base64';
import i18next from "i18next";


export default function WorkContract() {

    const [offer, setoffer] = useState([]);
    const [contract, setContract] = useState([]);
    const [client, setClient] = useState([]);
    const [services, setServices] = useState([]);
    const param = useParams();
    const sigRef = useRef();
    const sigRef2 = useRef();
    const { t } = useTranslation();
    const [signature, setSignature] = useState(null);
    const [signature2, setSignature2] = useState(null);
    const [Aaddress, setAaddress] = useState(null);
    const [ctype, setCtype] = useState("");
    const [cname, setCname] = useState("");
    const [cvv, setCvv] = useState("");
    const [status, setStatus] = useState("");
    const [sessionURL, setSessionURL] = useState("");
    const [addCardBtnDisabled, setAddCardBtnDisabled] = useState(false);
    const [checkingForCard, setCheckingForCard] = useState(false);
    const [clientCards, setClientCards] = useState([]);
    const [selectedClientCardID, setSelectedClientCardID] = useState(null);
    const [isCardAdded, setIsCardAdded] = useState(false);
    const [consentToAds, setConsentToAds] = useState(true);
    const [signDate, setSignDate] = useState(moment().format("DD/MM/YYYY"));

    const consentToAdsRef = useRef();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("client-token"),
    };

    const handleAccept = (e) => {

        // if (!ctype) { swal('Please select card type', '', 'error'); return false; }
        // if (!cname) { swal('Please enter card holder name', '', 'error'); return false; }
        // if (!cvv) { swal('Please select card cvv', '', 'error'); return false; }
        // if (!signature) { swal('Please sign the contract', '', 'error'); return false; }
        // if (!signature2) { swal('Please enter signature on the card', '', 'error'); return false; }
        // if (cvv.length < 3) { swal('Invalid cvv', '', 'error'); return false; }

        const data = {
            unique_hash: param.hash,
            offer_id: offer.id,
            client_id: offer.client.id,
            card_id: selectedClientCardID,
            additional_address: Aaddress,
            status: "un-verified",
            signature: signature,
            consent_to_ads: consentToAdsRef?.current?.checked ? 1 : 0,
            form_data: { card_signature: signature2 },
        };
        console.log(data);


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


    const getOffer = () => {
        axios
            .post(`/api/client/contracts/${param.hash}`)
            .then((res) => {
                if (res.data.offer) {
                    const _contract = res.data.contract;
                    setoffer(res.data.offer);

                    setServices(JSON.parse(res.data.offer.services));
                    setClient(res.data.offer.client);
                    setContract(_contract);
                    setStatus(_contract.status);
                    setConsentToAds(_contract.consent_to_ads ? true : false);

                    setClientCards(res.data.cards);
                    setSelectedClientCardID(_contract.card_id != null ? _contract.card_id : '');
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
                        import("../../../Assets/css/rtl.css");
                        document
                            .querySelector("html")
                            .setAttribute("dir", "rtl");
                    } else {
                        document.querySelector("html").removeAttribute("dir");
                    }
                } else {
                    setoffer({});
                    setServices([]);
                    setClient(null);
                    setContract(null);
                }
            })
            .catch((e) => {
                console.log(e);

                Swal.fire({
                    title: "Error!",
                    text: e.response?.data?.message,
                    icon: "error",
                });
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
                setStatus(res.data?.contract?.status)
                setContract(res.data.contract);
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
            .post(`/api/client/contracts/${param.hash}/initialize-card`, {})
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
        console.log(e.target.value);
        
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
        return clientCards.find((i) => i.id === parseInt(selectedClientCardID));
    }, [clientCards, selectedClientCardID]);

    return (

        <div className='container parent' >
            <Sidebar />
            <div className='send-offer client-contract sendOfferRtl'>
                <div className='maxWidthControl dashBox mb-4'>
                    <div className='row'>
                        <div className='col-sm-6'>
                            <svg width="190" height="77" xmlns="http://www.w3.org/2000/svg" xmlnsXlink="http://www.w3.org/1999/xlink">
                                <image xlinkHref={logo} width="190" height="77"></image>
                            </svg>
                        </div>
                        <div className='col-sm-6'>
                            <h4 className='btn btn-success float-right'>{t('global.accepted')}</h4>
                        </div>
                    </div>
                    <h4 className='inHead' style={{ whiteSpace: 'pre-wrap' }}>{t('work-contract.inHead')}</h4>
                    <div className='signed'>
                        <p>{t('work-contract.signed')} <span>{client.city ? client.city : 'NA'}</span> on <span>{moment(contract.created_at).format('DD MMMM,Y')}</span></p>
                    </div>
                    <div className='between'>
                        <p>{t('work-contract.between')}</p>
                        <p>{t('work-contract.broom_service')}</p>

                    </div>
                    <div className='first'>
                        <h2 className='mb-4'>{t('work-contract.first_party_title')}</h2>
                        <p style={{ textAlign: 'center' }}>{t('work-contract.and')}</p>
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

                                            return <p key={i}>{((s.service != '10') ? s.name : s.other_title)}</p>
                                        })}
                                    </td>
                                </tr>
                                <tr>
                                    <td style={{ width: "60%" }}>{t('work-contract.location_txt')}</td>
                                    <td>
                                        {/* {offer && offer?.map((ofr, i) => {
                                            let address = (ofr.client.geo_address) ? (ofr.client.geo_address) + ", " : '';
                                            return address;
                                        })} */}

                                        <br /> <span style={{ fontWeight: "600" }} className='d-block mt-2'>{t('work-contract.other_address_txt')}</span> <br />
                                        {contract && contract.additional_address != null ?
                                            <input type='text' value={contract.additional_address} readOnly className='form-control' style={{ border: "2px solid #ccc" }} />
                                            :
                                            <input type='text' name="additional_address" onChange={(e) => setAaddress(e.target.value)} placeholder={t('work-contract.placeholder_address')} className='form-control' style={{ border: "2px solid #ccc" }} />
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
                                                <p key={i}> {s.freq_name}</p>
                                            )
                                        })}

                                    </td>
                                </tr>
                                <tr>
                                    <td style={{ width: "60%" }}>{t('work-contract.consideration_txt')}</td>
                                    <td>
                                        {services && services.map((s, i) => {

                                            return <p key={i}>{s.totalamount + t('work-contract.ils') + " + " + t('work-contract.vat') + " " + t('work-contract.for') + " " + ((s.service != '10') ? s.name : s.other_title) + ", " + s.freq_name}</p>
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
                                <tr>
                                    <td style={{ width: "60%" }}>
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
                                    {clientCards.map((_card, _index) => {
                                        return (
                                            <div className="my-3 ml-3" key={_index}>
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
                                                    ** ** **{" "}
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
                                    </td>
                                    {/* <td>
                                        {contract && contract.card_type != null ?
                                            <input type="text" value={contract.card_type} className="form-control" style={{ border: "2px solid #ccc" }} readOnly />
                                            :
                                            <select className='form-control' style={{ border: "2px solid #ccc" }} onChange={(e) => setCtype(e.target.value)}>
                                                <option>Please Select</option>
                                                <option value='Visa'>Visa</option>
                                                <option value='Master Card'>Master Card</option>
                                                <option value='American Express'>American Express</option>
                                            </select>
                                        }
                                    </td> */}
                                </tr>
                                {/* <tr>
                                    <td style={{ width: "60%" }}>{t('work-contract.card_name')}</td>
                                    <td>
                                        {contract && contract.name_on_card != null ?
                                            <input type="text" value={contract.name_on_card} className="form-control" readOnly />
                                            :
                                            <input type='text' name="name_on_card" onChange={(e) => setCname(e.target.value)} className='form-control' style={{ border: "2px solid #ccc" }} placeholder={t('work-contract.card_name')} />
                                        }
                                    </td>
                                </tr> */}

                                {/* <tr>
                                    <td style={{ width: "60%" }}>{t('work-contract.card_cvv')}</td>
                                    <td>
                                        {contract && contract.cvv != null ?
                                            <input type="text" value={contract.cvv} className="form-control" style={{ border: "2px solid #ccc" }} readOnly />
                                            :
                                            <input type='text' name="cvv" onChange={(e) => setCvv(e.target.value)} onKeyUp={(e) => { if (e.target.value.length >= 3) e.target.value = e.target.value.slice(0, 3); }} className='form-control' style={{ border: "2px solid #ccc" }} placeholder={t('work-contract.card_cvv')} />
                                        }
                                    </td>
                                </tr> */}

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

                        <div className='mb-4'>&nbsp;</div>

                        
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

    )
}
