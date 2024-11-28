import React, { useState, useEffect, useMemo } from "react";
import logo from "../Assets/image/sample.svg";
import star from "../Assets/image/icons/blue-star.png";
import footer from "../Assets/image/bg-bottom-footer.png";
import { useParams, useNavigate } from "react-router-dom";
import Moment from "moment";
import swal from "sweetalert";
import axios from "axios";
import { useTranslation } from "react-i18next";
import i18next from "i18next";
import Swal from "sweetalert2";
import { Base64 } from "js-base64";
import { frequencyDescription } from "../Utils/job.utils";
import FullPageLoader from "../Components/common/FullPageLoader";
import MiniLoader from "../Components/common/MiniLoader";

export default function PriceOffer() {
    const { t } = useTranslation();
    const param = useParams();
    const [offer, setOffer] = useState([]);
    const [services, setServices] = useState([]);
    const [client, setClient] = useState([]);
    const [allTemplates, setAllTemplates] = useState([]);
    const [status, setStatus] = useState("");
    const [subService, setSubService] = useState([])
    const [clientLng, setClientLng] = useState("")
    const [loading, setLoading] = useState(false)
    const [airbnb, setAirbnb] = useState({
        id: "",
        subServiceId: [],
    })

    // const clientLng = localStorage.getItem("client-lng")


    const getOffer = async () => {
        try {
            const res = await axios.post(`/api/client/get-offer/${Base64.decode(param.id)}`);
            const data = res.data.data;
            setClientLng(data.client?.lng);
            setOffer(data);
            setStatus(data.status);
            setClient(data.client);
            i18next.changeLanguage(data.client?.lng);
            if(data?.client?.lng) {
                document.querySelector("html").removeAttribute("dir");
                const rtlLink = document.querySelector('link[href*="rtl.css"]');
                if (rtlLink) {
                    rtlLink.remove();
                }
            }
            let _services = JSON.parse(data.services);

            setServices(_services);
            setAirbnb({
                id: _services[0].service,
                subServiceId: _services[0].subService
            });

            if (data.client.lng === "heb") {
                import("../Assets/css/rtl.css");
                document.querySelector("html").setAttribute("dir", "rtl");
            } else {
                document.querySelector("html").removeAttribute("dir");
            }

            let tm = [];
            _services.forEach(s => {
                tm.push(s.template);
            });

            tm = Array.from(new Set(tm)); // Remove duplicates
            setAllTemplates(tm);

        } catch (error) {
            console.error("Error fetching offer data:", error);
        }
    };


    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    useEffect(() => {
        if (airbnb.id) {
            handleGetSubServices(airbnb.id);
        }
    }, [airbnb.id, airbnb.subServiceId]);

    const handleGetSubServices = async (id) => {
        try {
            const res = await axios.get(`/api/admin/get-sub-services/${id}`, { headers });
            const allSubServices = res.data.subServices;

            const filteredSubServices = allSubServices.filter(sub => airbnb.subServiceId.includes(sub.id));

            setSubService(filteredSubServices);
        } catch (error) {
            console.log("Error fetching sub-services:", error);
        }
    }

    const handleOffer = (e, id) => {
        e.preventDefault();
        setLoading(true)
        let btn = document.querySelectorAll(".acpt");
        btn[0].setAttribute("disabled", true);
        btn[0].value = "Please Wait..";
        btn[1].setAttribute("disabled", true);
        btn[1].value = "Please Wait..";
        axios.post(`/api/client/accept-offer`, { id: id }).then((res) => {
            if (res.data.errors) {
                setLoading(false);
                for (let e in res.data.errors) {
                    alert.error(res.data.errors[e]);
                }
                btn[0].removeAttribute("disabled");
                btn[0].value = "Accept Offer";
                btn[1].removeAttribute("disabled");
                btn[1].value = "Accept Offer";
            } else {
                setLoading(false)
                setStatus("accepted");
                let msg = t("price_offer.messages.success");
                swal(msg, "", "success");
            }
        });
    };

    const RejectOffer = (id) => {
        Swal.fire({
            title: t("price_offer.messages.reject_title"),
            text: t("price_offer.messages.reject_text"),
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            cancelButtonText: t("price_offer.messages.cancel"),
            confirmButtonText: t("price_offer.messages.yes_reject"),
        }).then((result) => {
            setLoading(true);
            if (result.isConfirmed) {
                axios
                    .post(`/api/client/reject-offer`, { id: id })
                    .then((response) => {
                        setLoading(false)
                        Swal.fire(
                            t("price_offer.messages.reject"),
                            t("price_offer.messages.reject_msg"),
                            "success"
                        );
                        setStatus("declined");
                    })
                    .catch((e) => {
                        setLoading(false);
                        Swal.fire({
                            title: "Error!",
                            text: e.response.data.message,
                            icon: "error",
                        });
                    });
            }
        });
    };

    const workerHours = (_service) => {
        if (_service.type === "hourly") {
            return _service.workers.map((i) => i.jobHours).join(", ");
        }

        return "-";
    };

    useEffect(() => {
        getOffer();
        setTimeout(() => {
            document.querySelector(".parent").style.display = "block";
        }, 500);
    }, []);

    services.filter((s, i, a) => {
        //rg.includes(parseInt(s.service)) in IF commented
        if (i == 0 && s.service == "10" && a.length >= 2) {
            [a[0], a[a.length - 1]] = [a[a.length - 1], a[0]];
            return a;
        }
    });

    const showWorkerHours = useMemo(() => {
        return services.filter((i) => i.type !== "fixed").length > 0;
    }, [services]);

    return (
        <>
            <div className="container parent" style={{ display: "none" }}>
                <div className="send-offer sendOfferRtl">
                    <div className="mb-4 maxWidthControl dashBox">
                        <div className="row">
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
                            <div className="col-sm-6">
                                {status == "sent" ? (
                                    <div className="float-right mt-2 headBtns">
                                        <button
                                            type="button"
                                            className="btn btn-pink acpt"
                                            disabled={loading}
                                            onClick={(e) =>
                                                handleOffer(e, offer.id)
                                            }
                                        >
                                            {loading ? <MiniLoader/> : t("price_offer.button")}
                                        </button>
                                        <button
                                            type="button"
                                            className="ml-2 btn btn-danger rjct"
                                            onClick={(e) =>
                                                RejectOffer(offer.id)
                                            }
                                        >
                                            {t("price_offer.button_reject")}
                                        </button>
                                    </div>
                                ) : (
                                    <div className="float-right mt-2 headMsg">
                                        {status == "accepted" ? (
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
                        <div className="row">
                            <div className="col-sm-6">
                                <h1>
                                    {t("price_offer.title")}.{" "}
                                    <span style={{ color: "#16a6ef" }}>
                                        #{offer.id}
                                    </span>
                                </h1>
                            </div>
                            <div className="col-sm-6">
                                <p className="date">
                                    {t("price_offer.dateTxt")}:{" "}
                                    <span style={{ color: "#16a6ef" }}>
                                        {Moment(offer.created_at).format(
                                            "Y-MM-DD"
                                        )}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div className="grey-bd">
                            <p>
                                {t("price_offer.honour_of")}:{" "}
                                <span
                                    style={{
                                        color: "#3da7ef",
                                        fontWeight: "700",
                                    }}
                                >
                                    {client.firstname + " " + client.lastname}
                                </span>{" "}
                            </p>

                            {/* <p>
                                {t("price_offer.address_text")}:{" "}
                                <span>{client.geo_address}</span>
                            </p> */}
                        </div>
                        <div className="abt">
                            <h2>{t("price_offer.about_title")}</h2>
                            <p style={{ whiteSpace: "pre-wrap" }}>
                                {t("price_offer.about")}
                            </p>
                        </div>

                        <div className="we-have">
                            <h3>{t("price_offer.offer_title")}</h3>

                            {/*rg.includes(sid) && sid == 4
                                || rg.includes(sid) && sid == 5
                                || rg.includes(sid) && sid == 6
                                || rg.includes(sid) && sid == 7
                                && !rg.includes(sid) && sid == 10*/}

                            {allTemplates.includes("airbnb") && (
                                <div className="shift-20">
                                    <h4>
                                        &bull;{" "}
                                        {t("price_offer.airbnb.title")}
                                    </h4>
                                    <ul className="list-unstyled">
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.airbnb.subtitle"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.airbnb.air1"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.airbnb.air2"
                                            )}
                                        </li>
                                        {/* <li><img src={star} /> {t('price_offer.regular_services.rs1_p4')}</li> */}
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.airbnb.air3"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.airbnb.air4"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.airbnb.air5"
                                            )}
                                        </li>
                                    </ul>

                                    <h4 className="mt-4">
                                        &bull;{" "}
                                        {t("price_offer.regular_services.rs2")}
                                    </h4>
                                    <table border="1" style={{ width: "100%", textAlign: "center", borderCollapse: "collapse" }}>
                                        <thead>
                                            <tr>
                                                <th>{t("price_offer.airbnb.services.title")}</th>
                                                <th>{t("price_offer.airbnb.size_apt.title")}</th>
                                                <th>{t("price_offer.airbnb.price.title")}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {subService && subService.map((row, index) => (
                                                <tr key={index}>
                                                    <td>{clientLng == "en" ? row.name_en : row.name_heb}</td>
                                                    <td>{row.apartment_size}</td>
                                                    <td>{row.price}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}

                            {allTemplates.includes("regular") && (
                                <div className="shift-20">
                                    <h4>
                                        &bull;{" "}
                                        {t("price_offer.regular_services.rs1")}
                                    </h4>
                                    <ul className="list-unstyled">
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.regular_services.rs1_p1"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.regular_services.rs1_p2"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.regular_services.rs1_p3"
                                            )}
                                        </li>
                                        {/* <li><img src={star} /> {t('price_offer.regular_services.rs1_p4')}</li> */}
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.regular_services.rs1_p5"
                                            )}
                                        </li>
                                    </ul>

                                    <h4 className="mt-4">
                                        &bull;{" "}
                                        {t("price_offer.regular_services.rs2")}
                                    </h4>
                                    <img
                                        src={t(
                                            "price_offer.regular_services.rs2_img"
                                        )}
                                        className="img-fluid"
                                        alt="Room Services"
                                    />
                                </div>
                            )}


                            {/*(!rg.includes(sid) && sid == 4)
                                    && (!rg.includes(sid) && sid == 5)
                                    && (!rg.includes(sid) && sid == 6)
                                    && (!rg.includes(sid) && sid == 7)
                                    || (rg.includes(sid) && sid == 10)*/}

                            {services.map((s, i) => {
                                if (
                                    s.service == "10" &&
                                    allTemplates.includes("others") &&
                                    !allTemplates.includes("regular")
                                ) {
                                    return (




                                        <div className="shift-20" key={i}>
                                            <h4 className="mt-4">
                                                &bull; {s.other_title}
                                            </h4>

                                            <ul className="list-unstyled">
                                                <li>
                                                    <img src={star} />{" "}
                                                    {t(
                                                        "price_offer.regular_services.rs1_p1"
                                                    )}
                                                </li>
                                                <li>
                                                    <img src={star} />{" "}
                                                    {t(
                                                        "price_offer.regular_services.rs1_p2"
                                                    )}
                                                </li>
                                                <li>
                                                    <img src={star} />{" "}
                                                    {t(
                                                        "price_offer.regular_services.rs1_p3"
                                                    )}
                                                </li>
                                                {/* <li><img src={star} /> {t('price_offer.regular_services.rs1_p4')}</li> */}
                                                <li>
                                                    <img src={star} />{" "}
                                                    {t(
                                                        "price_offer.regular_services.rs1_p5"
                                                    )}
                                                </li>
                                            </ul>
                                        </div>
                                    );
                                }
                            })}

                            {allTemplates.includes("thorough_cleaning") && (
                                <div className="shift-20">
                                    <h4>
                                        &bull;{" "}
                                        {t(
                                            "price_offer.thorough_cleaning.premium"
                                        )}
                                    </h4>
                                    <ul className="list-unstyled">
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s1_1"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s1_2"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s1_3_ebasic"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s1_4"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s1_5_ebasic"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s1_6"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s1_7"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s1_8"
                                            )}{" "}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s1_9"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s1_10_estandard"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s1_11"
                                            )}{" "}
                                        </li>
                                    </ul>
                                    <h4 className="mt-4">
                                        &bull;{" "}
                                        {t(
                                            "price_offer.thorough_cleaning.standard"
                                        )}
                                    </h4>
                                    <ul className="list-unstyled">
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s1_1"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s1_2"
                                            )}{" "}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s1_3_ebasic"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s1_4"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s2_5r"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s1_6"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s1_7"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s2_8r"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s1_9"
                                            )}{" "}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s1_11"
                                            )}{" "}
                                        </li>
                                    </ul>
                                    <h4 className="mt-4">
                                        &bull;{" "}
                                        {t(
                                            "price_offer.thorough_cleaning.basic"
                                        )}
                                    </h4>
                                    <ul className="list-unstyled">
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s1_1"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s1_2"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s1_4"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s1_6"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s1_7"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s3_8r"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s1_9"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.thorough_cleaning.s1_11"
                                            )}
                                        </li>
                                    </ul>
                                </div>
                            )}

                            {allTemplates.includes("office_cleaning") && (
                                <div className="shift-20">
                                    {!allTemplates.includes("regular") ? (
                                        <>
                                            <h4>
                                                &bull;{" "}
                                                {t(
                                                    "price_offer.office_cleaning.oc1"
                                                )}
                                            </h4>
                                            <ul className="list-unstyled">
                                                <li>
                                                    <img src={star} />{" "}
                                                    {t(
                                                        "price_offer.office_cleaning.oc1_p1"
                                                    )}
                                                </li>
                                                <li>
                                                    <img src={star} />{" "}
                                                    {t(
                                                        "price_offer.office_cleaning.oc1_p2"
                                                    )}
                                                </li>
                                                <li>
                                                    <img src={star} />{" "}
                                                    {t(
                                                        "price_offer.office_cleaning.oc1_p3"
                                                    )}
                                                </li>
                                                <li>
                                                    <img src={star} />{" "}
                                                    {t(
                                                        "price_offer.office_cleaning.oc1_p4"
                                                    )}
                                                </li>
                                                <li>
                                                    <img src={star} />{" "}
                                                    {t(
                                                        "price_offer.office_cleaning.oc1_p5"
                                                    )}
                                                </li>
                                            </ul>
                                            <h4 className="mt-4">
                                                &bull;{" "}
                                                {t(
                                                    "price_offer.office_cleaning.oc2"
                                                )}
                                            </h4>
                                        </>
                                    ) : (
                                        <>
                                            <h4 className="mt-4">
                                                &bull;{" "}
                                                {t(
                                                    "price_offer.office_cleaning.oc2"
                                                )}
                                            </h4>
                                        </>
                                    )}
                                    <img
                                        src={t(
                                            "price_offer.office_cleaning.oc2_img"
                                        )}
                                        className="img-fluid"
                                        alt="Room Services"
                                    />
                                </div>
                            )}

                            {allTemplates.includes("after_renovation") && (
                                <div className="shift-20">
                                    <h4>
                                        &bull; {t("price_offer.renovation.rn1")}
                                    </h4>
                                    <ul className="list-unstyled">
                                        <li>
                                            <img src={star} />{" "}
                                            {t("price_offer.renovation.rn1_p1")}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t("price_offer.renovation.rn1_p2")}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t("price_offer.renovation.rn1_p3")}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t("price_offer.renovation.rn1_p4")}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t("price_offer.renovation.rn1_p5")}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t("price_offer.renovation.rn1_p6")}
                                        </li>
                                    </ul>
                                    <h4 className="mt-4">
                                        &bull; {t("price_offer.renovation.rn2")}
                                    </h4>
                                    <img
                                        src={t(
                                            "price_offer.renovation.rn2_img"
                                        )}
                                        className="img-fluid"
                                        alt="Room Services"
                                    />
                                </div>
                            )}

                            {allTemplates.includes("polish") && (
                                <div className="shift-20">
                                    <h4 className="mt-4">
                                        &bull;{" "}
                                        {t("price_offer.our_services.s1")}
                                    </h4>
                                    <ul className="list-unstyled">
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.our_services.s1_p1"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.our_services.s1_p2"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.our_services.s1_p3"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.our_services.s1_p4"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.our_services.s1_p5"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.our_services.s1_p6"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.our_services.s1_p7"
                                            )}
                                        </li>
                                        <li>
                                            <img src={star} />{" "}
                                            {t(
                                                "price_offer.our_services.s1_p8"
                                            )}
                                        </li>
                                    </ul>
                                </div>
                            )}


                            <div className="shift-20">
                                <h4 className="mt-4">
                                    &bull;{" "}
                                    {t("price_offer.window_any_height.title")}
                                </h4>
                                <ul className="list-unstyled">
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.window_any_height.p1")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.window_any_height.p2")}{" "}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.window_any_height.p3")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.window_any_height.p4")}
                                    </li>
                                </ul>
                            </div>

                            <div className="shift-20">
                                <h4 className="mt-4">
                                    &bull; {t("price_offer.laundary.title")}
                                </h4>
                                <ul className="list-unstyled">
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.laundary.p1")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.laundary.p2")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.laundary.p3")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.laundary.p4")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.laundary.p5")}
                                    </li>
                                </ul>
                            </div>

                            <div className="services shift-20">
                                <h3 className="card-title">
                                    {t("price_offer.service_title")}
                                </h3>
                                <div className="table-responsive">
                                    <table className="table table-sm table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>
                                                    {t(
                                                        "price_offer.address_text"
                                                    )}
                                                </th>
                                                <th>
                                                    {t(
                                                        "price_offer.service_txt"
                                                    )}
                                                </th>
                                                <th>{t("price_offer.type")}</th>
                                                <th>
                                                    {t(
                                                        "price_offer.freq_s_txt"
                                                    )}
                                                </th>
                                                {showWorkerHours && (
                                                    <th>
                                                        {t(
                                                            "price_offer.Quantity/Hours"
                                                        )}
                                                    </th>
                                                )}
                                                <th>
                                                    {t(
                                                        "price_offer.amount_txt"
                                                    )}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {services.map((s, i) => {
                                                console.log(s);

                                                return (
                                                    <tr key={i}>
                                                        <td>
                                                            {s.address &&
                                                                s.address
                                                                    .address_name
                                                                ? s.address
                                                                    .address_name
                                                                : "NA"}
                                                        </td>
                                                        <td>
                                                            {s.service == 10
                                                                ? s.other_title
                                                                : s.name}
                                                        </td>
                                                        <td>
                                                            {clientLng === 'heb' ? (
                                                                s.type === 'fixed' ? 'קָבוּעַ' :
                                                                    s.type === 'hourly' ? 'מדי שעה' :
                                                                        s.type === 'squaremeter' ? 'מטר מרובע' : s.type
                                                            ) : (
                                                                s.type // Fallback to default value if ClientLng is not 'heb'
                                                            )}
                                                        </td>
                                                        <td>
                                                            {s.freq_name}{" "}
                                                            {/* <p>
                                                                {frequencyDescription(
                                                                    s
                                                                )}
                                                            </p> */}
                                                        </td>
                                                        {s?.type == "squaremeter" ? (
                                                            <td>
                                                                {s.ratepersquaremeter}
                                                            </td>
                                                        ) : (

                                                            showWorkerHours && (<td>
                                                                {workerHours(s)}
                                                            </td>)

                                                        )}

                                                        {s?.type == "fixed" ? (
                                                            <td>
                                                                {s.workers
                                                                    .length *
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
                            </div>

                            <h3 className="mt-4">
                                <a
                                    href="https://www.broomservice.co.il"
                                    target="_blank"
                                >
                                    {t("price_offer.our_services.heading")}
                                </a>
                            </h3>
                            <div className="shift-20">
                                <h4 className="mt-4">
                                    &bull; {t("price_offer.our_services.s1")}
                                </h4>
                                <ul className="list-unstyled">
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s1_p1")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s1_p2")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s1_p3")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s1_p4")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s1_p5")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s1_p6")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s1_p7")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s1_p8")}
                                    </li>
                                </ul>
                                <h4 className="mt-4">
                                    &bull; {t("price_offer.our_services.s2")}
                                </h4>
                                <ul className="list-unstyled">
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s2_p1")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s2_p2")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s2_p3")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s2_p4")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s2_p5")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s2_p6")}
                                    </li>
                                </ul>
                                <h4 className="mt-4">
                                    &bull; {t("price_offer.our_services.s3")}
                                </h4>
                                <ul className="list-unstyled">
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s3_p1")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s2_p2")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s2_p3")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s2_p4")}
                                    </li>
                                </ul>
                                <h4 className="mt-4">
                                    &bull; {t("price_offer.our_services.s4")}
                                </h4>
                                <ul className="list-unstyled">
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s4_p1")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s4_p2")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s4_p3")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s4_p4")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s4_p5")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s4_p6")}
                                    </li>
                                </ul>
                                <h4 className="mt-4">
                                    &bull; {t("price_offer.our_services.s5")}
                                </h4>
                                <ul className="list-unstyled">
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s5_p1")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s5_p2")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s5_p3")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s5_p4")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s5_p5")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s5_p6")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s5_p7")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s5_p8")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s5_p9")}
                                    </li>
                                </ul>
                                {/* <h4 className="mt-4">
                                    &bull; {t("price_offer.our_services.s6")}
                                </h4>
                                <ul className="list-unstyled">
                                    <li>
                                        <img src={star} />{" "}
                                        {t("price_offer.our_services.s6_p1")}
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        <a
                                            href="https://bell-boy.com/"
                                            target="_blank"
                                        >
                                            {t(
                                                "price_offer.our_services.s6_p2"
                                            )}
                                        </a>{" "}
                                    </li>
                                </ul> */}
                            </div>
                        </div>
                        {status == "sent" && (
                            <>
                                <div className="mt-3 mb-3 text-center">
                                    <button
                                        type="button"
                                        className="btn btn-pink acpt"
                                        disabled={loading}
                                        onClick={(e) =>
                                            handleOffer(e, offer.id)
                                        }
                                    >
                                        {loading ? <MiniLoader/> : t("price_offer.button")}
                                    </button>
                                </div>
                            </>
                        )}

                        <footer className="mt-4">
                            <img
                                src={footer}
                                className="img-fluid"
                                alt="Footer"
                            />
                        </footer>
                    </div>
                </div>
                {loading && <FullPageLoader visible={loading} />}
            </div>
        </>
    );
}
