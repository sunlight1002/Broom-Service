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
import useWindowWidth from "../Hooks/useWindowWidth";
import { FaStar } from "react-icons/fa";

export default function PriceOffer() {
    const { t } = useTranslation();
    const param = useParams();
    const [offer, setOffer] = useState([]);
    const [services, setServices] = useState([]);
    const [client, setClient] = useState([]);
    const [allTemplates, setAllTemplates] = useState([]);
    const [status, setStatus] = useState("");
    const [subService, setSubService] = useState([])
    const [clientLng, setClientLng] = useState("he")
    const [loading, setLoading] = useState(false)
    const [mobileView, setMobileView] = useState(false);
    const windowWidth = useWindowWidth();
    const [airbnb, setAirbnb] = useState({
        id: "",
        subServiceIds: [],
    })

    const id = Base64.decode(param.id);

    // const clientLng = localStorage.getItem("client-lng")

    useEffect(() => {
        if (windowWidth < 767) {
            setMobileView(true)
        } else {
            setMobileView(false)
        }
    }, [windowWidth])

    let sectionCounter = 0;

    // Function to get the next number in sequence
    const getNextNumber = () => {
        sectionCounter++;
        return sectionCounter;
    };

    const getOffer = async () => {
        try {
            const res = await axios.post(`/api/client/get-offer/${Base64.decode(param.id)}`);
            const data = res.data.data;

            setClientLng(data.client?.lng);
            setOffer(data);
            setStatus(data.status);
            setClient(data.client);
            i18next.changeLanguage(data.client?.lng);
            if (data?.client?.lng) {
                document.querySelector("html").removeAttribute("dir");
                const rtlLink = document.querySelector('link[href*="rtl.css"]');
                if (rtlLink) {
                    rtlLink.remove();
                }
            }
            let _services = JSON.parse(data.services);

            setServices(_services);
            const airbnbServices = _services.find(service => service.template === "airbnb");

            const airbnbSubServiceIds = _services
                .map(service => service.sub_services?.id) // Map to sub_services IDs
                .filter(id => id); // Filter out undefined or null IDs
            // Set the AirBnb state with all the IDs
            if (airbnbServices) {
                setAirbnb({
                    id: airbnbServices.service,// Collect all main service IDs
                    subServiceIds: airbnbSubServiceIds, // Collect all sub_service IDs
                });
            }



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
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };

    useEffect(() => {
        if (airbnb.id) {
            handleGetSubServices(airbnb.id);
        }
    }, [airbnb.id, airbnb.subServiceIds]);

    const handleGetSubServices = async (id) => {
        try {
            const res = await axios.get(`/api/get-sub-services/${id}`);

            const allSubServices = res.data?.subServices || [];

            // Ensure subServiceIds is an array and convert to string for comparison
            const subServiceIds = airbnb?.subServiceIds || [];

            // Filter sub-services where the id matches any in subServiceIds array
            const filteredSubServices = allSubServices.filter(sub =>
                subServiceIds.includes(sub.id.toString())
            );


            setSubService(filteredSubServices);
        } catch (error) {
            console.log("Error fetching sub-services:", error);
        }
    };



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
        if (i == 0 && s.template == "others" && a.length >= 2) {
            [a[0], a[a.length - 1]] = [a[a.length - 1], a[0]];
            return a;
        }
    });

    const showWorkerHours = useMemo(() => {
        return services.filter((i) => i.type !== "fixed").length > 0;
    }, [services]);

    const TableRow = ({ colspan, content, stars }) => (
        <tr>
            <td colSpan={colspan}>{content}</td>
            {stars.map((star, index) => (
                <td style={{ color: "#1F78BD" }} key={index}>{<FaStar />}</td>
            ))}
            {new Array(11 - stars.length - colspan).fill(null).map((_, index) => (
                <td key={index + stars.length}></td>
            ))}
        </tr>
    );

    return (
        <div className="navyblueColor parent">
            <div className="mt-4 mb-5 bg-transparent " style={{
                margin: mobileView ? "0 10px" : "auto",
                maxWidth: "800px"
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
                    <section className="d-flex align-items-center" style={{ gap: "20px" }}>
                        <p className="mt-4 navyblueColor font-34 font-w-500">{t("price_offer.price_offno")} #{id}</p>
                        {status == "sent" ? (
                            <div className="mt-3 headBtns">
                                <button
                                    type="button"
                                    className="m-1 btn btn-success acpt"
                                    disabled={loading}
                                    style={{ lineHeight: "1.3" }}
                                    onClick={(e) =>
                                        handleOffer(e, offer.id)
                                    }
                                >
                                    {loading ? <MiniLoader /> : t("price_offer.button")}
                                </button>
                                <button
                                    type="button"
                                    className="m-1 btn btn-danger rjct"
                                    style={{ lineHeight: "1.3" }}
                                    onClick={(e) =>
                                        RejectOffer(offer.id)
                                    }
                                >
                                    {t("price_offer.button_reject")}
                                </button>
                            </div>
                        ) : (
                            <div className="mt-3 headMsg">
                                {status == "accepted" ? (
                                    <h4 className="px-3 btn btn-success" style={{ lineHeight: "1.3" }}>
                                        {t("global.accepted")}
                                    </h4>
                                ) : (
                                    <h4 className="px-3 btn btn-danger" style={{ lineHeight: "1.3" }}>
                                        {t("global.rejected")}
                                    </h4>
                                )}
                            </div>
                        )}
                    </section>
                    <section className="d-flex align-items-center" style={{ gap: "20px" }}>
                        <p className="navyblueColor font-15">{t("price_offer.dateTxt")}:{" "}
                            <span >
                                {Moment(offer.created_at).format(
                                    "Y-MM-DD"
                                )}
                            </span></p>
                        <p className="m-0 navyblueColor font-15">{t("price_offer.honour_of")}:{" "}
                            <span>
                                {client.firstname + " " + client.lastname}
                            </span>{" "}</p>
                    </section>
                </div>
                <div className="mt-3">
                    <section className="col-xl">
                        <div className="abt">
                            <h5 className="mb-2">{t("price_offer.about_title")}</h5>
                            <p style={{ whiteSpace: "pre-wrap" }}>{t("price_offer.about")}</p>
                        </div>
                        <div className="mt-2 mt-3 we-have">
                            <h4>{t("price_offer.offer_title")}</h4>
                        </div>

                        {/* Airbnb Services */}
                        {allTemplates.includes("airbnb") && (
                            <div className="mt-3" style={{ lineHeight: "2.3" }}>
                                <h5>{getNextNumber()}. {t("price_offer.airbnb.title")}</h5>
                                <ul className="mt-2 ">
                                    <li>{t("price_offer.airbnb.subtitle")}</li>
                                    <li>{t("price_offer.airbnb.air1")}</li>
                                    <li>{t("price_offer.airbnb.air2")}</li>
                                    <li>{t("price_offer.airbnb.air3")}</li>
                                    <li>{t("price_offer.airbnb.air4")}</li>
                                    <li>{t("price_offer.airbnb.air5")}</li>
                                </ul>
                                <h5 className="mt-4 mb-2">{getNextNumber()}. {t("price_offer.regular_services.rs2")}</h5>
                                <div className="text-center table-responsive">
                                    <table className="table table-sm table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>{t("price_offer.airbnb.services.title")}</th>
                                                <th>{t("price_offer.airbnb.size_apt.title")}</th>
                                                <th>{t("price_offer.airbnb.price.title")}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {subService &&
                                                subService.map((row, index) => (
                                                    <tr key={index}>
                                                        <td>{clientLng === "en" ? row.name_en : row.name_heb}</td>
                                                        <td>{row.apartment_size}</td>
                                                        <td>{row.price}</td>
                                                    </tr>
                                                ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}

                        {/* Regular Services */}
                        {allTemplates.includes("regular") && (
                            <div className="mt-3" id="priceOfferTable" style={{ lineHeight: "34px" }}>
                                <h5>{getNextNumber()}. {t("price_offer.regular_services.rs1")}</h5>
                                <ul>
                                    <li>{t("price_offer.regular_services.rs1_p1")}</li>
                                    <li>{t("price_offer.regular_services.rs1_p2")}</li>
                                    <li>{t("price_offer.regular_services.rs1_p3")}</li>
                                    <li>{t("price_offer.regular_services.rs1_p5")}</li>
                                </ul>
                                <h4 className="mt-4">
                                    {getNextNumber()}. {" "}
                                    {t("price_offer.regular_services.rs2")}
                                </h4>
                                <div className="text-center table-responsive">
                                    <table className="table table-sm table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>{t("price_offer.our_packages.th1")}</th>
                                                <th>{t("price_offer.our_packages.th2")}</th>
                                                <th>{t("price_offer.our_packages.th3")}</th>
                                                <th>{t("price_offer.our_packages.th4")}</th>
                                                <th>{t("price_offer.our_packages.th5")}</th>
                                                <th>{t("price_offer.our_packages.th6")}</th>
                                                <th>{t("price_offer.our_packages.th7")}</th>
                                                <th>{t("price_offer.our_packages.th8")}</th>
                                                <th>{t("price_offer.our_packages.th9")}</th>
                                                <th>{t("price_offer.our_packages.th10")}</th>
                                                <th>{t("price_offer.our_packages.th11")}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <TableRow
                                                colspan={2}
                                                content={t("price_offer.our_packages.tr1.tc1")}
                                                stars={new Array(5).fill("⭐")}
                                            />
                                            <TableRow
                                                colspan={2}
                                                content={t("price_offer.our_packages.tr2")}
                                                stars={new Array(6).fill("⭐")}
                                            />
                                            <TableRow
                                                colspan={2}
                                                content={t("price_offer.our_packages.tr3")}
                                                stars={new Array(7).fill("⭐")}
                                            />
                                            <TableRow
                                                colspan={2}
                                                content={t("price_offer.our_packages.tr4")}
                                                stars={new Array(8).fill("⭐")}
                                            />
                                            <TableRow
                                                colspan={2}
                                                content={t("price_offer.our_packages.tr5")}
                                                stars={new Array(9).fill("⭐")}
                                            />
                                            <tr>
                                                <td colSpan={2}>{t("price_offer.our_packages.tr6.tc1")}</td>
                                                <td colSpan="9" dangerouslySetInnerHTML={{ __html: t("price_offer.our_packages.tr6.tc2") }} />
                                            </tr>
                                            <tr>
                                                <td colSpan={2}>{t("price_offer.our_packages.tr7.tc1")}</td>
                                                <td colSpan="9" dangerouslySetInnerHTML={{ __html: t("price_offer.our_packages.tr7.tc2") }} />
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}

                        {/* Other Services */}
                        {services.map((service, index) => {
                            if (
                                service.template === "others" &&
                                allTemplates.includes("others") &&
                                !allTemplates.includes("regular")
                            ) {
                                return (
                                    <div className="mt-3" key={index} style={{ lineHeight: "2.3" }}>
                                        <h5>{getNextNumber()}. {service.other_title}</h5>
                                        <ul>
                                            <li>{t("price_offer.regular_services.rs1_p1")}</li>
                                            <li>{t("price_offer.regular_services.rs1_p2")}</li>
                                            <li>{t("price_offer.regular_services.rs1_p3")}</li>
                                            <li>{t("price_offer.regular_services.rs1_p5")}</li>
                                        </ul>
                                    </div>
                                );
                            }
                            return null;
                        })}

                        {/* Thorough Cleaning */}
                        {allTemplates.includes("thorough_cleaning") && (
                            <div className="mt-3" style={{ lineHeight: "2.3" }}>
                                <h5>
                                    {getNextNumber()}. {" "}
                                    {t(
                                        "price_offer.thorough_cleaning.premium"
                                    )}
                                </h5>
                                <ul className="">
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s1_1"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s1_2"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s1_3_ebasic"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s1_4"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s1_5_ebasic"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s1_6"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s1_7"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s1_8"
                                        )}{" "}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s1_9"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s1_10_estandard"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s1_11"
                                        )}{" "}
                                    </li>
                                </ul>
                                <h5 className="mt-3">
                                    {getNextNumber()}. {" "}
                                    {t(
                                        "price_offer.thorough_cleaning.standard"
                                    )}
                                </h5>
                                <ul className="">
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s1_1"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s1_2"
                                        )}{" "}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s1_3_ebasic"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s1_4"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s2_5r"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s1_6"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s1_7"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s2_8r"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s1_9"
                                        )}{" "}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s1_11"
                                        )}{" "}
                                    </li>
                                </ul>
                                <h5 className="mt-3">
                                    {getNextNumber()}. {" "}
                                    {t(
                                        "price_offer.thorough_cleaning.basic"
                                    )}
                                </h5>
                                <ul className="">
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s1_1"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s1_2"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s1_4"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s1_6"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s1_7"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s3_8r"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s1_9"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.thorough_cleaning.s1_11"
                                        )}
                                    </li>
                                </ul>
                            </div>
                        )}

                        {/* Office Cleaning */}
                        {allTemplates.includes("office_cleaning") && (
                            <div className="mt-3" style={{ lineHeight: "2.3" }}>
                                {!allTemplates.includes("regular") ? (
                                    <>
                                        <h5>
                                            {getNextNumber()}. {" "}
                                            {t(
                                                "price_offer.office_cleaning.oc1"
                                            )}
                                        </h5>
                                        <ul className="">
                                            <li>
                                                {t(
                                                    "price_offer.office_cleaning.oc1_p1"
                                                )}
                                            </li>
                                            <li>
                                                {t(
                                                    "price_offer.office_cleaning.oc1_p2"
                                                )}
                                            </li>
                                            <li>
                                                {t(
                                                    "price_offer.office_cleaning.oc1_p3"
                                                )}
                                            </li>
                                            <li>
                                                {t(
                                                    "price_offer.office_cleaning.oc1_p4"
                                                )}
                                            </li>
                                            <li>
                                                {t(
                                                    "price_offer.office_cleaning.oc1_p5"
                                                )}
                                            </li>
                                        </ul>
                                        <h5 className="mt-3">
                                            {getNextNumber()}. {" "}
                                            {t(
                                                "price_offer.office_cleaning.oc2"
                                            )}
                                        </h5>
                                    </>
                                ) : (
                                    <>
                                        <h5 className="mt-3">
                                            {getNextNumber()}. {" "}
                                            {t(
                                                "price_offer.office_cleaning.oc2"
                                            )}
                                        </h5>
                                    </>
                                )}
                                <div className="mt-3 rtldiv">
                                    <img
                                        src={t(
                                            "price_offer.office_cleaning.oc2_img"
                                        )}
                                        className={`img-fluid mt-2 ${mobileView ? "mx-0" : "mx-3"}`}
                                        alt="Room Services"
                                    />
                                </div>
                            </div>
                        )}

                        {/* After Renovation */}
                        {allTemplates.includes("after_renovation") && (
                            <div className="mt-3" style={{ lineHeight: "2.3" }}>
                                <h5>
                                    {getNextNumber()}. {t("price_offer.renovation.rn1")}
                                </h5>
                                <ul className="">
                                    <li>
                                        {t("price_offer.renovation.rn1_p1")}
                                    </li>
                                    <li>
                                        {t("price_offer.renovation.rn1_p2")}
                                    </li>
                                    <li>
                                        {t("price_offer.renovation.rn1_p3")}
                                    </li>
                                    <li>
                                        {t("price_offer.renovation.rn1_p4")}
                                    </li>
                                    <li>
                                        {t("price_offer.renovation.rn1_p5")}
                                    </li>
                                    <li>
                                        {t("price_offer.renovation.rn1_p6")}
                                    </li>
                                </ul>
                                <h5 className="mt-3">
                                    {getNextNumber()}. {t("price_offer.renovation.rn2")}
                                </h5>
                                <div className="mt-3 rtldiv">
                                    <img
                                        src={t(
                                            "price_offer.renovation.rn2_img"
                                        )}
                                        className="m-2 img-fluid"
                                        alt="Room Services"
                                    />
                                </div>
                            </div>
                        )}


                        {allTemplates.includes("polish") && (
                            <div className="mt-3" style={{ lineHeight: "2.3" }}>
                                <h5 className="mt-3">
                                    {getNextNumber()}. {" "}
                                    {t("price_offer.our_services.s1")}
                                </h5>
                                <ul className="">
                                    <li>
                                        {t(
                                            "price_offer.our_services.s1_p1"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.our_services.s1_p2"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.our_services.s1_p3"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.our_services.s1_p4"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.our_services.s1_p5"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.our_services.s1_p6"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.our_services.s1_p7"
                                        )}
                                    </li>
                                    <li>
                                        {t(
                                            "price_offer.our_services.s1_p8"
                                        )}
                                    </li>
                                </ul>
                            </div>
                        )}

                        {/* Window Any Height */}
                        <div className="mt-3" style={{ lineHeight: "2.3" }}>
                            <h5>{getNextNumber()}. {t("price_offer.window_any_height.title")}</h5>
                            <ul className="">
                                <li>{t("price_offer.window_any_height.p1")}</li>
                                <li>{t("price_offer.window_any_height.p2")}</li>
                                <li>{t("price_offer.window_any_height.p3")}</li>
                                <li>{t("price_offer.window_any_height.p4")}</li>
                            </ul>
                        </div>

                        {/* Laundry
                        <div className="mt-3" style={{ lineHeight: "2.3" }}>
                            <h5>{getNextNumber()}. {t("price_offer.laundary.title")}</h5>
                            <ul>
                                <li>{t("price_offer.laundary.p1")}</li>
                                <li>{t("price_offer.laundary.p2")}</li>
                                <li>{t("price_offer.laundary.p3")}</li>
                                <li>{t("price_offer.laundary.p4")}</li>
                                <li>{t("price_offer.laundary.p5")}</li>
                            </ul>
                        </div> */}
                    </section>
                    <section className="px-3 col">
                        <div className="shift-20" id="priceOfferTable">
                            <h5 className="card-title">
                                {t("price_offer.service_title")}
                            </h5>
                            <div className="text-center table-responsive">
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
                                        
                                            const serviceName = s.template === "others"
                                                ? s.other_title
                                                : clientLng === 'heb'
                                                    ? s.service_name_heb
                                                    : s.service_name_en;

                                            const subServiceName = clientLng === 'heb'
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
                                                    <td>
                                                        {clientLng === 'heb' ? (
                                                            s.type === 'fixed' ? "לביקור" :
                                                                s.type === 'hourly' ? 'לשעה' :
                                                                    s.type === 'squaremeter' ? 'מ\"ר' : s.type
                                                        ) : (
                                                            s.type === 'fixed' ? "Fixed" :
                                                                s.type === 'hourly' ? 'Hourly' :
                                                                    s.type === 'squaremeter' ? 'Sqm' : s.type
                                                        )}

                                                    </td>
                                                    <td>
                                                        {clientLng === 'heb' ? s.frequency_name_heb : s.frequency_name_en}{" "}
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
                                                            {s.fixed_price}{" "}
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
                            {
                                services && services[0]?.comments && (
                                    <div className="row">
                                        <div className="col-12">
                                            <ul>
                                                <li>
                                                    {t("global.comment")}: {services[0]?.comments}
                                                </li>
                                            </ul>
                                            {/* <p className="mt-4">
                                        {t("global.comment")}: {services[0]?.comments}
                                    </p> */}
                                        </div>
                                    </div>
                                )
                            }
                        </div>

                        <h4 className="mt-4">
                            <a
                                href="https://www.broomservice.co.il"
                                target="_blank"
                                className="navyblueColor"
                            >
                                {t("price_offer.our_services.heading")}
                            </a>
                        </h4>
                        <div className="navyblueColor" style={{ lineHeight: "2.3" }}>
                            <h4 className="mt-4">
                                1. {t("price_offer.our_services.s1")}
                            </h4>
                            <ul className="">
                                <li>
                                    {t("price_offer.our_services.s1_p1")}
                                </li>
                                <li>
                                    {t("price_offer.our_services.s1_p2")}
                                </li>
                                <li>
                                    {t("price_offer.our_services.s1_p3")}
                                </li>
                                <li>
                                    {t("price_offer.our_services.s1_p4")}
                                </li>
                                <li>
                                    {t("price_offer.our_services.s1_p5")}
                                </li>
                                <li>
                                    {t("price_offer.our_services.s1_p6")}
                                </li>
                                <li>
                                    {t("price_offer.our_services.s1_p7")}
                                </li>
                                <li>
                                    {t("price_offer.our_services.s1_p8")}
                                </li>
                            </ul>
                            <h4 className="mt-4">
                                2. {t("price_offer.our_services.s2")}
                            </h4>
                            <ul className="">
                                <li>
                                    {t("price_offer.our_services.s2_p1")}
                                </li>
                                <li>
                                    {t("price_offer.our_services.s2_p2")}
                                </li>
                                <li>
                                    {t("price_offer.our_services.s2_p3")}
                                </li>
                                <li>
                                    {t("price_offer.our_services.s2_p4")}
                                </li>
                                <li>
                                    {t("price_offer.our_services.s2_p5")}
                                </li>
                                <li>
                                    {t("price_offer.our_services.s2_p6")}
                                </li>
                            </ul>
                            <h4 className="mt-4">
                                3. {t("price_offer.our_services.s3")}
                            </h4>
                            <ul className="">
                                <li>
                                    {t("price_offer.our_services.s3_p1")}
                                </li>
                                <li>
                                    {t("price_offer.our_services.s2_p2")}
                                </li>
                                <li>
                                    {t("price_offer.our_services.s2_p3")}
                                </li>
                                <li>
                                    {t("price_offer.our_services.s2_p4")}
                                </li>
                            </ul>
                            <h4 className="mt-4">
                                4. {t("price_offer.our_services.s4")}
                            </h4>
                            <ul className="">
                                <li>
                                    {t("price_offer.our_services.s4_p1")}
                                </li>
                                <li>
                                    {t("price_offer.our_services.s4_p2")}
                                </li>
                                <li>
                                    {t("price_offer.our_services.s4_p3")}
                                </li>
                                <li>
                                    {t("price_offer.our_services.s4_p4")}
                                </li>
                                <li>
                                    {t("price_offer.our_services.s4_p5")}
                                </li>
                                <li>
                                    {t("price_offer.our_services.s4_p6")}
                                </li>
                            </ul>
                            <h4 className="mt-4">
                                5. {t("price_offer.our_services.s5")}
                            </h4>
                            <ul className="">
                                <li>
                                    {t("price_offer.our_services.s5_p1")}
                                </li>
                                <li>
                                    {t("price_offer.our_services.s5_p2")}
                                </li>
                                <li>
                                    {t("price_offer.our_services.s5_p3")}
                                </li>
                                <li>
                                    {t("price_offer.our_services.s5_p4")}
                                </li>
                                <li>
                                    {t("price_offer.our_services.s5_p5")}
                                </li>
                                <li>
                                    {t("price_offer.our_services.s5_p6")}
                                </li>
                                <li>
                                    {t("price_offer.our_services.s5_p7")}
                                </li>
                                <li>
                                    {t("price_offer.our_services.s5_p8")}
                                </li>
                                <li>
                                    {t("price_offer.our_services.s5_p9")}
                                </li>
                            </ul>
                        </div>
                        {status == "sent" ? (
                            <div className="mt-3 headBtns d-flex justify-content-end">
                                <button
                                    type="button"
                                    className="btn btn-success acpt"
                                    disabled={loading}
                                    style={{ lineHeight: "1.3" }}
                                    onClick={(e) =>
                                        handleOffer(e, offer.id)
                                    }
                                >
                                    {loading ? <MiniLoader /> : t("price_offer.button")}
                                </button>
                                <button
                                    type="button"
                                    className="mx-2 btn btn-danger rjct"
                                    style={{ lineHeight: "1.3" }}
                                    onClick={(e) =>
                                        RejectOffer(offer.id)
                                    }
                                >
                                    {t("price_offer.button_reject")}
                                </button>
                            </div>
                        ) : (
                            <div className="mt-3 headMsg d-flex justify-content-end">
                                {status == "accepted" ? (
                                    <h4 className="px-3 btn btn-success" style={{ lineHeight: "1.3" }}>
                                        {t("global.accepted")}
                                    </h4>
                                ) : (
                                    <h4 className="px-3 btn btn-danger" style={{ lineHeight: "1.3" }}>
                                        {t("global.rejected")}
                                    </h4>
                                )}
                            </div>
                        )}
                    </section>
                </div>
            </div >
            {loading && <FullPageLoader visible={loading} />
            }
        </div >
    );
}
