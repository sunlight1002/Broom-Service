import logo from "../../Assets/image/sample.svg";
import star from "../../Assets/image/icons/blue-star.png";
import footer from "../../Assets/image/bg-bottom-footer.png";
import Moment from "moment";
import { useTranslation } from "react-i18next";

export default function TemplateWindowCleaning() {
    const { t } = useTranslation();
    const data = [
        { col1: t("price_offer.airbnb.services.s2"), col2: t("price_offer.airbnb.size_apt.s2"), col3: t("price_offer.airbnb.price.p1") },
        { col1: t("price_offer.airbnb.services.s3"), col2: t("price_offer.airbnb.size_apt.s3"), col3: t("price_offer.airbnb.price.p2") },
        { col1: t("price_offer.airbnb.services.s1"), col2: t("price_offer.airbnb.size_apt.s1"), col3: t("price_offer.airbnb.price.p3") },
        { col1: t("price_offer.airbnb.services.s4"), col2: t("price_offer.airbnb.size_apt.s4"), col3: t("price_offer.airbnb.price.p4") },
        { col1: t("price_offer.airbnb.services.s5"), col2: t("price_offer.airbnb.size_apt.s5"), col3: t("price_offer.airbnb.price.p5") },
        { col1: t("price_offer.airbnb.services.s6"), col2: t("price_offer.airbnb.size_apt.s6"), col3: t("price_offer.airbnb.price.p6") },
        { col1: t("price_offer.airbnb.services.s7"), col2: t("price_offer.airbnb.size_apt.s7"), col3: t("price_offer.airbnb.price.p7") },
        { col1: t("price_offer.airbnb.services.s8"), col2: t("price_offer.airbnb.size_apt.s8"), col3: t("price_offer.airbnb.price.p8") },
        { col1: t("price_offer.airbnb.services.s9"), col2: t("price_offer.airbnb.size_apt.s9"), col3: t("price_offer.airbnb.price.p9") },
        { col1: t("price_offer.airbnb.services.s10"), col2: t("price_offer.airbnb.size_apt.s10"), col3: t("price_offer.airbnb.price.p10") },
        { col1: t("price_offer.airbnb.services.s11"), col2: t("price_offer.airbnb.size_apt.s11"), col3: t("price_offer.airbnb.price.p11") },
        { col1: t("price_offer.airbnb.services.s12"), col2: t("price_offer.airbnb.size_apt.s12"), col3: t("price_offer.airbnb.price.p12") },
    ];

    return (
        <>
            <div className="container">
                <div className="send-offer">
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
                        </div>
                        <div className="row">
                            <div className="col-sm-6">
                                <h1>
                                    Price Offer No.{" "}
                                    <span style={{ color: "#16a6ef" }}>
                                        #12
                                    </span>
                                </h1>
                            </div>
                            <div className="col-sm-6">
                                <p className="date">
                                    Date:{" "}
                                    <span style={{ color: "#16a6ef" }}>
                                        {Moment().format("Y-MM-DD")}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div className="grey-bd">
                            <p>
                                In Honor Of:{" "}
                                <span
                                    style={{
                                        color: "#3da7ef",
                                        fontWeight: "700",
                                    }}
                                >
                                    {"John Doe"}
                                </span>{" "}
                            </p>
                            <p>
                                Company Name: <span>Broom Service</span>{" "}
                            </p>
                            <p>
                                Address:{" "}
                                <span>
                                    Saurabh Vihar, Jaitpur, New Delhi, Delhi,
                                    India , 2nd , 12, New Delhi
                                </span>
                            </p>
                        </div>
                        <div className="abt">
                            <h2>{t("price_offer.about_title")}</h2>
                            <p style={{ whiteSpace: "pre-wrap" }}>
                                {t("price_offer.about")}
                            </p>
                        </div>
                        <div className="we-have">
                          <h3>{t("price_offer.offer_title")}</h3>
                        <div className="shift-20">
                                <h4 className="mt-4">
                                    1.AirBnb apartment cleaning and maintenance is us. You can also offer the service to tenants staying in your apartment on demand.And all in one centralized invoice at the end of the month.Join the success! All our customers are Super Hosts!
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
                            </div>
                            <div className="services">
                                <h3 className="card-title">Services - </h3>
                                <div className="table-responsive">
                                <table border="1" style={{ width: "100%", textAlign: "center", borderCollapse: "collapse" }}>
                                        <thead>
                                            <tr>
                                                <th>{t("price_offer.airbnb.services.title")}</th>
                                                <th>{t("price_offer.airbnb.size_apt.title")}</th>
                                                <th>{t("price_offer.airbnb.price.title")}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {data.map((row, index) => (
                                                <tr key={index}>
                                                    <td>{row.col1}</td>
                                                    <td>{row.col2}</td>
                                                    <td>{row.col3}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <h3 className="mt-4">
                                Our Services Here, And On Our Website:{" "}
                                <a
                                    href="https://www.broomservice.co.il"
                                    target="_blank"
                                >
                                    www.broomservice.co.il
                                </a>
                            </h3>
                            <div className="shift-20">
                                <h4 className="mt-4">
                                    1. Polishing & Renovating Floors & Surfaces:
                                </h4>
                                <ul className="list-unstyled">
                                    <li>
                                        <img src={star} /> Floor polishing
                                        services of all kinds
                                    </li>
                                    <li>
                                        <img src={star} /> Polishing and crystal
                                        polishing{" "}
                                    </li>
                                    <li>
                                        <img src={star} /> Renew and renovation
                                        of old and damaged tiles{" "}
                                    </li>
                                    <li>
                                        <img src={star} /> Riﬂe, opening slots,
                                        ﬁlling holes
                                    </li>
                                    <li>
                                        <img src={star} /> Remove stains{" "}
                                    </li>
                                    <li>
                                        <img src={star} /> Renovation of
                                        stairwells{" "}
                                    </li>
                                    <li>
                                        <img src={star} /> Fine polishing and
                                        lubrication and wood surfaces
                                    </li>
                                    <li>
                                        <img src={star} /> Renovation of wooden
                                        furniture, of all kinds{" "}
                                    </li>
                                </ul>
                                <h4 className="mt-4">
                                    2. Professional Cabinet Organization /
                                    Packing & Unpacking Services.
                                </h4>
                                <ul className="list-unstyled">
                                    <li>
                                        <img src={star} /> Creating maximum
                                        order and organization.
                                    </li>
                                    <li>
                                        <img src={star} /> Sorting your items in
                                        a professional way to maximize your
                                        storage space{" "}
                                    </li>
                                    <li>
                                        <img src={star} /> Re-storage using
                                        creative storage solutions that preserve
                                        order over time.{" "}
                                    </li>
                                    <li>
                                        <img src={star} /> Professional and
                                        agile arrangement, sorting clothes by
                                        seasons etc.
                                    </li>
                                    <li>
                                        <img src={star} /> Professional packing
                                        before moving
                                    </li>
                                    <li>
                                        <img src={star} /> Unpacking the
                                        contents and arranging cabinets after
                                        passage{" "}
                                    </li>
                                </ul>
                                <h4 className="mt-4">
                                    3. Home Accommodation Services:
                                </h4>
                                <ul className="list-unstyled">
                                    <li>
                                        <img src={star} /> Basic or deep
                                        cleaning before - after or while hosting
                                        at your home
                                    </li>
                                    <li>
                                        <img src={star} /> Waiters to serve your
                                        guests
                                    </li>
                                    <li>
                                        <img src={star} /> Chef for small and
                                        large events
                                    </li>
                                    <li>
                                        <img src={star} /> Help with cutting and
                                        preparing groceries
                                    </li>
                                </ul>
                                <h4 className="mt-4">
                                    4. One-time Cleaning Service Afer Renovation
                                    / Pre-occupation / Passover Cleaning And
                                    Holidays
                                </h4>
                                <ul className="list-unstyled">
                                    <li>
                                        <img src={star} /> One-time general
                                        cleaning services - moving to a new
                                        apartment? Planning a renovation?
                                        Passover in the doorway? we are here!
                                    </li>
                                    <li>
                                        <img src={star} /> Cleaning services at
                                        various levels tailored to you and your
                                        needs{" "}
                                    </li>
                                    <li>
                                        <img src={star} /> Service by legally
                                        insured professional workers{" "}
                                    </li>
                                    <li>
                                        <img src={star} /> Detergents and the
                                        most advanced equipment at our expense -
                                        without acids and dangerous{" "}
                                    </li>
                                    <li>
                                        <img src={star} /> A supervisor who will
                                        make sure that the work is to your
                                        satisfaction and up to our standards
                                    </li>
                                </ul>
                                <h4 className="mt-4">
                                    5. Full Moving Services From The Packaging
                                    Stage To The Coffee With The New Neighbors
                                </h4>
                                <ul className="list-unstyled">
                                    <li>
                                        <img src={star} /> Deep general cleaning
                                        services before moving
                                    </li>
                                    <li>
                                        <img src={star} /> Cleaning windows at
                                        any height (even in rappelling)
                                    </li>
                                    <li>
                                        <img src={star} /> Polishing and
                                        renovating all types of ﬂoors and wood
                                        (including renovation of parquet, deck
                                        and furniture renewal of all types)
                                    </li>
                                    <li>
                                        <img src={star} /> Packaging and sorting
                                        services from the old house (including
                                        crates and packaging products){" "}
                                    </li>
                                    <li>
                                        <img src={star} /> Freight services
                                        including dismantling assembly and
                                        warranty from the cutting plant
                                        (possibility of crane service at any
                                        height) Polishing and polishing
                                    </li>
                                    <li>
                                        <img src={star} /> Storage services for
                                        crates and furniture until the move
                                    </li>
                                    <li>
                                        <img src={star} /> Professional
                                        unpacking and arranging services in your
                                        home cabinets
                                    </li>
                                    <li>
                                        <img src={star} /> Handyman services:
                                        installations, hangings, electrical work
                                        of the highest and most professional
                                        level
                                    </li>
                                    <li>
                                        <img src={star} /> Pest control
                                        services: license by the Ministry of
                                        Health
                                    </li>
                                </ul>
                                <h4 className="mt-4">
                                    6. Short-term Service Asset Management
                                    Quietly:
                                </h4>
                                <ul className="list-unstyled">
                                    <li>
                                        <img src={star} /> we offer short term
                                        airbnb services through Bell boy app for
                                        more details and registration:
                                    </li>
                                    <li>
                                        <img src={star} />{" "}
                                        <a
                                            href="https://www.bell-boy.com/"
                                            target="_blank"
                                        >
                                            https://www.bell-boy.com/
                                        </a>{" "}
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <footer className="mt-4">
                            <img
                                src={footer}
                                className="img-fluid"
                                alt="Footer"
                            />
                        </footer>
                    </div>
                </div>
            </div>
        </>
    );
}
