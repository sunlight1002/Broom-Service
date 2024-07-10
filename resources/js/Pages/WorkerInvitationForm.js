import axios from "axios";
import React, { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import { useTranslation } from "react-i18next";
import i18next from "i18next";
import logo from "../Assets/image/sample.svg";
import { Link } from "react-router-dom";
import Skeleton from "react-loading-skeleton";
import "react-loading-skeleton/dist/skeleton.css";
import { useAlert } from "react-alert";
import {
    GoogleMap,
    LoadScript,
    InfoWindow,
    Marker,
    Autocomplete,
} from "@react-google-maps/api";
import Geocode from "react-geocode";
import { useNavigate } from "react-router-dom";

export default function WorkerInvitationForm() {
    const param = useParams();
    const alert = useAlert();
    const { t } = useTranslation();
    const [worker, setWorker] = useState({});
    const [isFetched, setIsFetched] = useState(false);
    const [errors, setErrors] = useState([]);
    const [libraries] = useState(["places", "geometry"]);
    const [latitude, setLatitude] = useState(-33.865143);
    const [longitude, setLongitude] = useState(151.2099);
    const [place, setPlace] = useState();
    const navigate = useNavigate();
    Geocode.setApiKey("AIzaSyBva3Ymax7XLY17ytw_rqRHggZmqegMBuM");

    // console.log(worker);
    const [formValues, setFormValues] = useState({
        first_name: "",
        last_name: "",
        phone: "",
        email: "",
        gender: "",
        role: "",
        payment_hour: "",
        worker_id: "",
        renewal_date: "",
        company_type: "",
        manpower_company_id: "",
    });

    const [countries, setCountries] = useState([]);
    const [country, setCountry] = useState("Israel");
    const [lng, setLng] = useState("en");
    const [address, setAddress] = useState("");
    const [isSubmitting, setIsSubmitting] = useState(false);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
    };

    const containerStyle = {
        width: "100%",
        height: "300px",
    };
    const center = {
        lat: latitude,
        lng: longitude,
    };

    const getWorker = () => {
        axios
            .get(`/api/worker-invitation/${param.id}`)
            .then((res) => {
                console.log(res);
                const { worker_invitation: workData, lng: workerLng } =
                    res.data;
                let workerLanguage = 'heb';
                switch (workData.lng) {
                    case 'eng':
                        workerLanguage = 'en';
                        break;

                    case 'rus':
                        workerLanguage = 'ru';
                        break;

                    default:
                        workerLanguage = 'heb';
                        break;
                }
                setLng(workerLanguage);
                setWorker(workData);
                if (workData) {
                    console.log(workData);
                    setFormValues((prev) => ({
                        ...prev,
                        first_name: workData.first_name,
                        last_name: workData.last_name,
                        email: workData.email,
                        phone: workData.phone,
                    }));
                }
                setIsFetched(true);
                i18next.changeLanguage(workerLanguage);
                if (workerLanguage == "heb") {
                    import("../Assets/css/rtl.css");
                    document.querySelector("html").setAttribute("dir", "rtl");
                } else document.querySelector("html").removeAttribute("dir");
            })
            .catch((err) => {
                if (err?.response?.data?.message) {
                    alert.error(err.response.data.message);
                }
            });
    };

    const getCountries = () => {
        axios.get(`/api/admin/countries`, { headers }).then((response) => {
            setCountries(response.data.countries);
        });
    };

    const handleUpdate = (e) => {
        e.preventDefault();
        setIsSubmitting(true);

        const data = {
            ...formValues,
            address: address,
            renewal_visa: formValues.renewal_date,
            lng: !lng ? "en" : lng,
            country: country,
            latitude: latitude,
            longitude: longitude,
        };
        axios
            .post(`/api/worker-invitation-update/${param.id}`, data, { headers })
            .then((response) => {
                if (response.data.errors) {
                    setErrors(response.data.errors);
                } else {
                    alert.success("Information has been updated successfully");
                    setTimeout(() => {
                        navigate('/' + response.data.url);
                    }, 1000);
                }
                setIsSubmitting(false);
            })
            .catch((e) => {
                setIsSubmitting(false);
            });
    };

    const handlePlaceChanged = () => {
        if (place) {
            // setCity(place.getPlace().vicinity);
            setAddress(place.getPlace().formatted_address);
            setLatitude(place.getPlace().geometry.location.lat());
            setLongitude(place.getPlace().geometry.location.lng());
        }
    };

    const onMarkerDragEnd = (e) => {
        const newLat = e.latLng.lat();
        const newLng = e.latLng.lng();
        setLatitude(e.latLng.lat());
        setLongitude(e.latLng.lng());
        const geocoder = new window.google.maps.Geocoder();
        const latlng = { lat: newLat, lng: newLng };

        geocoder.geocode({ location: latlng }, (results, status) => {
            if (status === "OK") {
                if (results[0]) {
                    setAddress(results[0].formatted_address);
                }
            }
        });
    };

    useEffect(() => {
        getWorker();
        getCountries();
    }, []);

    return (
        <div className="container">
            <div className="thankyou meet-status dashBox maxWidthControl p-4">
                <svg
                    width="190"
                    height="77"
                    xmlns="http://www.w3.org/2000/svg"
                    xmlnsXlink="http://www.w3.org/1999/xlink"
                >
                    <image xlinkHref={logo} width="190" height="77"></image>
                </svg>
                {!isFetched ? (
                    <Skeleton height={200} />
                ) : (
                    <>
                        <h1>{t("formTxt.workerInformation")}</h1>
                        <ul className="list-unstyled">
                            <li>
                                {t("formTxt.hii")},{" "}
                                <span>
                                    {worker.first_name} {worker.last_name}
                                </span>
                            </li>
                            <li>{t("formTxt.greetingTxt")}</li>
                            <li>{t("formTxt.join_portal")}</li>
                        </ul>
                    </>
                )}
                {isFetched && (
                    <div className="cta">
                        <div>
                            <form onSubmit={handleUpdate}>
                                <div className="row">
                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                {t(
                                                    "workerInviteForm.first_name"
                                                )}{" "}
                                                *
                                            </label>
                                            <input
                                                type="text"
                                                value={formValues.first_name}
                                                onChange={(e) => {
                                                    setFormValues({
                                                        ...formValues,
                                                        first_name:
                                                            e.target.value,
                                                    });
                                                }}
                                                className={`form-control ${errors.first_name ? 'is-invalid' : ''}`}
                                                placeholder={t(
                                                    "workerInviteForm.enter_first_name"
                                                )}
                                            />
                                            {errors.first_name ? (
                                                <small className="text-danger mb-1">
                                                    {errors.first_name}
                                                </small>
                                            ) : (
                                                ""
                                            )}
                                        </div>
                                    </div>
                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                {t(
                                                    "workerInviteForm.last_name"
                                                )}{" "}
                                                *
                                            </label>
                                            <input
                                                type="text"
                                                value={formValues.last_name}
                                                onChange={(e) => {
                                                    setFormValues({
                                                        ...formValues,
                                                        last_name:
                                                            e.target.value,
                                                    });
                                                }}
                                                className={`form-control ${errors.last_name ? 'is-invalid' : ''}`}
                                                placeholder={t(
                                                    "workerInviteForm.enter_last_name"
                                                )}
                                            />
                                                {errors.last_name ? (
                                                <small className="text-danger mb-1">
                                                    {errors.last_name}
                                                </small>
                                            ) : (
                                                ""
                                            )}
                                        </div>
                                    </div>
                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                {t("workerInviteForm.email")} *
                                            </label>
                                            <input
                                                type="email"
                                                value={formValues.email}
                                                onChange={(e) => {
                                                    setFormValues({
                                                        ...formValues,
                                                        email: e.target.value,
                                                    });
                                                }}
                                                className={`form-control ${errors.email ? 'is-invalid' : ''}`}

                                                placeholder={t(
                                                    "workerInviteForm.enter_email"
                                                )}
                                            />
                                            {errors.email ? (
                                                <small className="text-danger mb-1">
                                                    {errors.email}
                                                </small>
                                            ) : (
                                                ""
                                            )}
                                        </div>
                                    </div>
                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                {t("workerInviteForm.phone")} *
                                            </label>
                                            <input
                                                type="tel"
                                                value={formValues.phone}
                                                onChange={(e) => {
                                                    setFormValues({
                                                        ...formValues,
                                                        phone: e.target.value,
                                                    });
                                                }}
                                                className={`form-control ${errors.phone ? 'is-invalid' : ''}`}
                                                placeholder={t(
                                                    "workerInviteForm.enter_phone"
                                                )}
                                            />
                                            {errors.phone ? (
                                                <small className="text-danger mb-1">
                                                    {errors.phone}
                                                </small>
                                            ) : (
                                                ""
                                            )}
                                        </div>
                                    </div>
                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                {t("workerInviteForm.gender")} *
                                            </label>
                                        </div>
                                        <div className="form-check-inline">
                                            <label className="form-check-label">
                                                <input
                                                    type="radio"
                                                    className={`form-check-input ${errors.radio ? 'is-invalid' : ''}`}
                                                    value="male"
                                                    required
                                                    onChange={(e) => {
                                                        setFormValues({
                                                            ...formValues,
                                                            gender: e.target
                                                                .value,
                                                        });
                                                    }}
                                                    checked={
                                                        formValues.gender ===
                                                        "male"
                                                    }
                                                />
                                                {t("workerInviteForm.male")}

                                            </label>
                                        </div>

                                        <div className="form-check-inline">
                                            <label className="form-check-label">
                                                <input
                                                    type="radio"
                                                    className="form-check-input"
                                                    value="female"
                                                    required
                                                    onChange={(e) => {
                                                        setFormValues({
                                                            ...formValues,
                                                            gender: e.target
                                                                .value,
                                                        });
                                                    }}
                                                    checked={
                                                        formValues.gender ===
                                                        "female"
                                                    }
                                                />
                                                {t("workerInviteForm.female")}
                                            </label>
                                        </div>
                                        <div className="mb-3">

                                            {errors.gender ? (
                                                <small className="text-danger mb-1">
                                                    {errors.gender}
                                                </small>
                                            ) : (
                                                ""
                                            )}
                                        </div>
                                    </div>

                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                {t("workerInviteForm.language")}
                                            </label>

                                            <select
                                                className="form-control"
                                                value={lng}
                                                onChange={(e) =>
                                                    setLng(e.target.value)
                                                }
                                            >
                                                <option value="heb">
                                                    {t(
                                                        "workerInviteForm.hebrew"
                                                    )}
                                                </option>
                                                <option value="en">
                                                    {t(
                                                        "workerInviteForm.english"
                                                    )}
                                                </option>
                                                <option value="ru">
                                                    {t(
                                                        "workerInviteForm.russian"
                                                    )}
                                                </option>
                                                <option value="spa">
                                                    {t(
                                                        "workerInviteForm.spanish"
                                                    )}
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                {t("workerInviteForm.country")}{" "}
                                                *
                                            </label>

                                            <select
                                                className="form-control"
                                                value={country}
                                                required
                                                onChange={(e) =>
                                                    setCountry(e.target.value)
                                                }
                                            >
                                                {countries &&
                                                    countries.map(
                                                        (item, index) => (
                                                            <option
                                                                value={
                                                                    item.name
                                                                }
                                                                key={index}
                                                            >
                                                                {item.name}
                                                            </option>
                                                        )
                                                    )}
                                            </select>
                                        </div>
                                        <div className="mb-3">
                                            {errors.country ? (
                                                <small className="text-danger mb-1">
                                                    {errors.country}
                                                </small>
                                            ) : (
                                                ""
                                            )}
                                        </div>
                                    </div>


                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                {t("workerInviteForm.workerId")} *
                                            </label>
                                            <input
                                                type="number"
                                                value={formValues.worker_id}
                                                onChange={(e) => {
                                                    setFormValues({
                                                        ...formValues,
                                                        worker_id: e.target.value,
                                                    });
                                                }}
                                                className={`form-control ${errors.worker_id ? 'is-invalid' : ''}`}
                                                placeholder={t(
                                                    "workerInviteForm.workerId"
                                                )}
                                            />
                                            {errors.phone ? (
                                                <small className="text-danger mb-1">
                                                    {errors.worker_id}
                                                </small>
                                            ) : (
                                                ""
                                            )}
                                        </div>
                                    </div>



                                    <div className="col-sm-12">
                                        <div className="form-group">
                                            <label className="control-label">
                                                {t(
                                                    "workerInviteForm.enter_location"
                                                )}
                                            </label>
                                            <LoadScript
                                                googleMapsApiKey="AIzaSyBva3Ymax7XLY17ytw_rqRHggZmqegMBuM"
                                                libraries={libraries}
                                            >
                                                <GoogleMap
                                                    mapContainerStyle={
                                                        containerStyle
                                                    }
                                                    center={center}
                                                    zoom={15}
                                                >
                                                    <Marker
                                                        draggable={true}
                                                        onDragEnd={(e) =>
                                                            onMarkerDragEnd(e)
                                                        }
                                                        position={{
                                                            lat: latitude,
                                                            lng: longitude,
                                                        }}
                                                    />
                                                    {address ? (
                                                        <InfoWindow
                                                            onClose={(e) =>
                                                                onInfoWindowClose(
                                                                    e
                                                                )
                                                            }
                                                            position={{
                                                                lat:
                                                                    latitude +
                                                                    0.0018,
                                                                lng: longitude,
                                                            }}
                                                        >
                                                            <div>
                                                                <span
                                                                    style={{
                                                                        padding: 0,
                                                                        margin: 0,
                                                                    }}
                                                                >
                                                                    {address}
                                                                </span>
                                                            </div>
                                                        </InfoWindow>
                                                    ) : (
                                                        <></>
                                                    )}
                                                    <Marker />
                                                </GoogleMap>
                                                <Autocomplete
                                                    onLoad={(e) => setPlace(e)}
                                                    onPlaceChanged={
                                                        handlePlaceChanged
                                                    }
                                                >
                                                    <input
                                                        type="text"
                                                        placeholder={t(
                                                            "workerInviteForm.search_your_address"
                                                        )}
                                                        className="form-control mt-1"
                                                    />
                                                </Autocomplete>
                                            </LoadScript>
                                        </div>
                                    </div>
                                    <div className="col-sm-12">
                                        <div className="form-group">
                                            <label className="control-label">
                                                {t(
                                                    "workerInviteForm.full_address"
                                                )}{" "}
                                                <small className="text-pink mb-1">
                                                    &nbsp; ({t(
                                                        "workerInviteForm.auto_complete"
                                                    )})
                                                </small>
                                            </label>
                                            <input
                                                type="text"
                                                value={address}
                                                className={`form-control ${errors.address ? "is-invalid" : ""}`}
                                                placeholder="Full Address"
                                                readOnly
                                            />
                                            {errors.address ? (
                                                <small className="text-danger mb-1">
                                                    {errors.address}
                                                </small>
                                            ) : (
                                                ""
                                            )}
                                        </div>
                                    </div>
                                </div>
                                <div className="form-group text-center">
                                    <input
                                        type="submit"
                                        title={t("workerInviteForm.submit")}
                                        className="btn btn-danger"
                                        disabled={isSubmitting}
                                    />
                                </div>
                            </form>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
