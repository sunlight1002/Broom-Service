import { Base64 } from 'js-base64';
import React, { useEffect, useState } from 'react'
import { useTranslation } from "react-i18next";
import { useParams } from 'react-router-dom';
import axios from 'axios';
import { useAlert } from 'react-alert';
import { GrFormNextLink } from 'react-icons/gr';
import {
    GoogleMap,
    LoadScript,
    InfoWindow,
    Marker,
    Autocomplete,
} from "@react-google-maps/api";
import Geocode from "react-geocode";

const ManpowerDetailForm = ({ setNextStep, values, type }) => {
    const { t } = useTranslation();
    const params = useParams();
    const workerId = Base64.decode(params.id);
    const [worker, setWorker] = useState({});
    const [countries, setCountries] = useState([]);
    const [errors, setErrors] = useState({});
    const alert = useAlert();
    const [libraries] = useState(["places", "geometry"]);
    const [latitude, setLatitude] = useState(-33.865143);
    const [longitude, setLongitude] = useState(151.2099);
    const [place, setPlace] = useState();
    // const [country, setCountry] = useState("");
    const [formValues, setFormValues] = useState({
        worker_id: workerId,
        firstname: "",
        lastname: "",
        email: "",
        lng: "",
        country: "",
        gender: "",
        renewal_visa: "",
        passportNumber: "",
        IDNumber: "",
        address: "",
        is_afraid_by_dog: false,
        is_afraid_by_cat: false,
        latitude: latitude,
        longitude: longitude,
        type: type == "lead" ? "lead" : "worker"
    })

    Geocode.setApiKey("AIzaSyBU01s3r8ER0qJd1jG0NA8itmcNe-iSTYk");
    const containerStyle = {
        width: "100%",
        height: "300px",
    };
    const center = {
        lat: latitude,
        lng: longitude,
    };

    const getCountries = () => {
        axios.get(`/api/admin/countries`).then((response) => {
            setCountries(response.data.countries);
        });
    };
    const getWorkerLead = () => {
        axios
            .get(`/api/worker-lead-detail/${workerId}`)
            .then((res) => {
                const _worker = res.data;
                setFormValues({
                    worker_id: _worker.id,
                    firstname: _worker.firstname,
                    lastname: _worker.lastname,
                    email: _worker.email,
                    lng: _worker.lng,
                    country: _worker.country,
                    gender: _worker.gender,
                    renewal_visa: _worker.renewal_visa,
                    passportNumber: _worker.passport ?? "",
                    IDNumber: _worker.id_number ?? "",
                    address: _worker.address ?? "",
                    is_afraid_by_dog: _worker.is_afraid_by_dog == 1 ? true : false,
                    is_afraid_by_cat: _worker.is_afraid_by_cat == 1 ? true : false,
                    latitude: _worker.latitude,
                    longitude: _worker.longitude,
                    type: "lead"
                })
                setWorker(_worker);
            })
            .catch((err) => {
                if (err?.response?.data?.message) {
                    alert.error(err.response.data.message);
                }
            });
    };

    const getWorker = async () => {
        const response = await axios.post(`/api/worker-detail`, { worker_id: workerId });
        setFormValues({
            worker_id: response.data.worker.id,
            firstname: response.data.worker.firstname,
            lastname: response.data.worker.lastname,
            email: response.data.worker.email,
            lng: response.data.worker.lng,
            country: response.data.worker.country,
            gender: response.data.worker.gender,
            renewal_visa: response.data.worker.renewal_visa,
            passportNumber: response.data.worker.passport ?? "",
            IDNumber: response.data.worker.id_number ?? "",
            address: response.data.worker.address ?? "",
            is_afraid_by_dog: response.data.worker.is_afraid_by_dog == 1 ? true : false,
            is_afraid_by_cat: response.data.worker.is_afraid_by_cat == 1 ? true : false,
            latitude: response.data.worker.latitude,
            longitude: response.data.worker.longitude,
            type: "worker"
        })
        setWorker(response.data.worker);
    }

    const saveWorkerDetails = async (e) => {
        e.preventDefault();
        try {
            const response = await axios.post("/api/save-worker-detail", formValues);
            if (response.status === 200) {
                setNextStep(prev => prev + 1);
                window.location.reload(true);
            }
        } catch (error) {
            if (error.response && error.response.data.errors) {
                setErrors(error.response.data.errors);
            } else {
                console.error("Something went wrong:", error);
            }
        }
    }

    useEffect(() => {
        if (type == "lead") {
            getWorkerLead();
        } else {
            getWorker()
        }
        getCountries()
    }, [])


    const handlePlaceChanged = () => {
        if (place) {
            // console.log(place.getPlace());
            
            // setCity(place.getPlace().vicinity);
            setFormValues({
                ...formValues,
                address: place.getPlace().formatted_address
            });
            setLatitude(place.getPlace().geometry.location.lat());
            setLongitude(place.getPlace().geometry.location.lng());
        }
    };

    const handleDocSubmit = (data) => {
        axios
            .post(`/api/document/save`, data)
            .then((res) => {
                if (res.data.errors) {
                    console.log(res.data.errors);

                } else {
                    console.log(res.data.message);
                }
            })
            .catch((err) => {
                console.log(err);
            });
    };

    const handleFileChange = (e, typ) => {
        const data = new FormData();
        data.append("id", workerId);
        data.append("type", type == "lead" ? "lead" : "worker");
        if (e.target.files.length > 0) {
            const file = e.target.files[0];
            const fileSizeInMB = file.size / (1024 * 1024); // Convert file size to MB
            if (fileSizeInMB > 10) {
                alert.error(t("form101.step1.imageSize")); // Show an error message
                return;
            }
            data.append(`${typ}`, file);
        }
        handleDocSubmit(data);
    };


    return (
        <div>
            <div className="mb-4">
                <p className="navyblueColor font-30 mt-4 font-w-500"> {t("client.jobs.view.worker_details")}</p>
            </div>
            <form className="row">
                <section className="col-xl">
                    <div className="row justify-content-center">
                        <div className="col-sm">
                            <div className="form-group">
                                <label className="control-label">
                                    {t("insurance.fN")}
                                </label>
                                <input
                                    type="text"
                                    name={"firstname"}
                                    id="firstname"
                                    className="form-control"
                                    value={formValues.firstname}
                                    onChange={(e) => setFormValues({ ...formValues, firstname: e.target.value })}
                                />
                                <span className="text-danger">
                                    {errors.firstname && errors.firstname}
                                </span>
                            </div>
                        </div>
                        <div className="col-sm">
                            <div className="form-group">
                                <label className="control-label">
                                    {t("insurance.LN")}
                                </label>
                                <input
                                    type="text"
                                    name={"canLastName"}
                                    id="canLastName"
                                    className="form-control"
                                    value={formValues.lastname}
                                    onChange={(e) => setFormValues({ ...formValues, lastname: e.target.value })}
                                />
                                <span className="text-danger">
                                    {errors.lastname && errors.lastname}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div className="row justify-content-center">
                        <div className="col-sm">
                            <div className="form-group">
                                <label className="control-label">
                                    {t("worker.settings.country")}
                                </label>

                                <select
                                    className="form-control"
                                    value={formValues.country}
                                    onChange={(e) =>
                                        setFormValues({
                                            ...formValues,
                                            country: e.target.value,
                                        })
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
                                <span className="text-danger">
                                        {errors.country && errors.country}
                                    </span>
                            </div>
                        </div>
                        {formValues.country != "Israel" && (
                            <div className="col-sm">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("worker.settings.renewal_visa")}
                                    </label>
                                    <input
                                        type="date"
                                        name={"renewal_visa"}
                                        value={formValues.renewal_visa}
                                        onChange={(e) => {
                                            setFormValues({
                                                ...formValues,
                                                renewal_visa: e.target.value,
                                            });
                                        }}
                                        className="form-control"
                                        placeholder={t("worker.settings.renewal_visa")}
                                    />
                                    <span className="text-danger">
                                        {errors.renewal_visa && errors.renewal_visa}
                                    </span>
                                </div>
                            </div>
                        )}

                    </div>

                    <div className="row justify-content-center">
                        {formValues.country != "Israel" && (
                            <div className="col-sm">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("form101.passport_num")}
                                    </label>
                                    <input
                                        type="passportNumber"
                                        value={formValues.passportNumber}
                                        onChange={(e) => {
                                            setFormValues({
                                                ...formValues,
                                                passportNumber: e.target.value,
                                            });
                                        }}
                                        className="form-control"
                                        placeholder={t("form101.passport_num")}
                                    />
                                     <span className="text-danger">
                                        {errors.passport && errors.passport}
                                    </span>
                                </div>
                            </div>
                        )}
                        {formValues.country != "Israel" && (
                            <div className="col-sm-6">
                                <label htmlFor="employeepassportCopy">
                                    {t("form101.passport_photo")}
                                </label>
                                <br />
                                <div className="input_container">
                                    <input
                                        className="w-100"
                                        type="file"
                                        name="employeepassportCopy"
                                        id="employeepassportCopy"
                                        accept="image/*"
                                        onChange={(e) => {
                                            handleFileChange(e, "passport");
                                        }}
                                    />
                                </div>
                            </div>
                        )}
                        {/* {formValues.country != "Israel" && (
                                <div className="col-sm-6">
                                    <label htmlFor="employeeResidencePermit"
                                        style={{ marginBottom: "0", width: "100%" }}
                                    >
                                        {t("form101.PhotoCopyResident")}
                                    </label>
                                    <div className="input_container" style={{ height: "42px" }}>
                                        <input
                                            type="file"
                                            name="employeeResidencePermit"
                                            id="employeeResidencePermit"
                                            className="form-control man p-0 border-0"
                                            style={{ fontSize: "unset", backgroundColor: "unset", }}
                                            accept="image/*"
                                            onChange={(e) => {
                                                handleFileChange(e, "visa");
                                            }}
                                        />
                                    </div>
                                </div>
                            )} */}
                    </div>
                    <div className="row justify-content-center">
                        {formValues.country == "Israel" && (
                            <div className="col-sm">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("form101.id_num")}
                                    </label>
                                    <input
                                        type="text"
                                        value={formValues.IDNumber}
                                        onChange={(e) => {
                                            setFormValues({
                                                ...formValues,
                                                IDNumber: e.target.value,
                                            });
                                        }}
                                        className="form-control"
                                        placeholder={t("form101.id_num")}
                                    />
                                </div>
                            </div>
                        )}
                        {formValues.country == "Israel" && (
                            <div className="col-sm mb-2">
                                <label htmlFor="employeeIdCardCopy">
                                    {t("form101.id_photocopy")}
                                </label>
                                <br />
                                <div className="input_container">
                                    <input
                                        className="w-100"
                                        type="file"
                                        name="employeeIdCardCopy"
                                        id="employeeIdCardCopy"
                                        accept="image/*"
                                        onChange={(e) => {
                                            handleFileChange(e, "id_card");
                                        }}
                                    />
                                </div>
                            </div>
                        )}
                    </div>
                    <div className="row justify-content-center mt-2">
                        <div className="col-sm">
                            <div className="form-group">
                                <label className="control-label">
                                    {t("worker.settings.email")}
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
                                    className="form-control"
                                    placeholder={t("worker.settings.email")}
                                />
                            </div>
                        </div>
                        {formValues.country != "Israel" && (
                            <div className="col-sm">
                                <label htmlFor="employeeResidencePermit"
                                    style={{ marginBottom: "0", width: "100%" }}
                                >
                                    {t("form101.PhotoCopyResident")}
                                </label>
                                <div className="input_container" style={{ height: "42px" }}>
                                    <input
                                        type="file"
                                        name="employeeResidencePermit"
                                        id="employeeResidencePermit"
                                        className="form-control man p-0 border-0"
                                        style={{ fontSize: "unset", backgroundColor: "unset", }}
                                        accept="image/*"
                                        onChange={(e) => {
                                            handleFileChange(e, "visa");
                                        }}
                                    />
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="row justify-content-center mt-2">
                        <div className="col-sm">
                            <div className="form-group">
                                <label className="control-label">
                                    {t("worker.settings.lng")}
                                </label>

                                <select
                                    className="form-control"
                                    value={formValues.lng}
                                    onChange={(e) =>
                                        setFormValues({
                                            ...formValues,
                                            lng: e.target.value,
                                        })
                                    }
                                >
                                    <option value="en">
                                        English
                                    </option>
                                    <option value="heb">
                                        Hebrew
                                    </option>
                                    <option value="ru">
                                        Russian
                                    </option>
                                    <option value="spa">
                                        Spanish
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div className="col-sm">
                            <div className="form-group mb-0">
                                <label className="control-label">
                                    {t("worker.settings.areYouAfraid")}
                                </label>
                            </div>
                            <div className="form-check d-flex align-items-center">
                                <label htmlFor='is_afraid_by_dog' className="form-check-label mx-2">
                                    Dog
                                </label>
                                <input
                                    type="checkbox"
                                    className=""
                                    name="is_afraid_by_dog"
                                    checked={formValues.is_afraid_by_dog}
                                    onChange={(e) => {
                                        setFormValues({
                                            ...formValues,
                                            is_afraid_by_dog: e.target
                                                .checked,
                                        });
                                    }}
                                />
                            </div>
                            <div className="form-check d-flex align-items-center">
                                <label htmlFor='is_afraid_by_cat' className="form-check-label mx-2">
                                    Cat
                                </label>
                                <input
                                    type="checkbox"
                                    className="t"
                                    name="is_afraid_by_cat"
                                    checked={formValues.is_afraid_by_cat}
                                    onChange={(e) => {
                                        setFormValues({
                                            ...formValues,
                                            is_afraid_by_cat: e.target
                                                .checked,
                                        });
                                    }}
                                />
                            </div>
                        </div>
                    </div>

                    <div className="">
                        <div className="form-group mb-0">
                            <label className="control-label">
                                {t("worker.settings.gender")}
                            </label>
                        </div>
                        <div className="form-check-inline">
                            <label className="form-check-label">
                                <input
                                    type="radio"
                                    className="form-check-input"
                                    value="male"
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
                                {t("worker.settings.male")}
                            </label>
                        </div>
                        <div className="form-check-inline">
                            <label className="form-check-label">
                                <input
                                    type="radio"
                                    className="form-check-input"
                                    value="female"
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
                                {t("worker.settings.female")}
                            </label>
                        </div>
                    </div>

                    <div className="form-group mt-4">
                        <label className="control-label">
                            {t("admin.global.location")}
                        </label>
                        <LoadScript
                            googleMapsApiKey="AIzaSyBU01s3r8ER0qJd1jG0NA8itmcNe-iSTYk"
                            libraries={libraries}
                        >
                            <GoogleMap
                                mapContainerStyle={containerStyle}
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
                                {formValues.address ? (
                                    <InfoWindow
                                        onClose={(e) =>
                                            onInfoWindowClose(e)
                                        }
                                        position={{
                                            lat: latitude + 0.0018,
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
                                                {formValues.address}
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
                                onPlaceChanged={handlePlaceChanged}
                            >
                                <input
                                    type="text"
                                    placeholder={t("workerInviteForm.search_your_address")}
                                    className="form-control mt-1"
                                />
                            </Autocomplete>
                        </LoadScript>
                    </div>
                    <div className="form-group">
                        <label className="control-label">
                            {t("workerInviteForm.full_address")}
                            <small className="text-pink mb-1">
                                &nbsp; ({t("workerInviteForm.auto_complete")})
                            </small>
                        </label>
                        <input
                            type="text"
                            value={formValues.address}
                            className="form-control"
                            placeholder={t("workerInviteForm.enter_your_address")}
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


                    {/* Buttons */}
                    <div div >
                        <div className="row justify-content-center mt-4">
                            <div className="col d-flex justify-content-end">
                                <button
                                    type="button"
                                    onClick={(e) => {
                                        saveWorkerDetails(e);
                                    }}
                                    className="btn navyblue"
                                >
                                    {/* {!isSubmitted ? t("safeAndGear.Accept") : <> Next <GrFormNextLink /></>} */}
                                    {t("safeAndGear.Next")} <GrFormNextLink />

                                </button>

                            </div>
                        </div>
                    </div>
                </section >
            </form >
        </div >
    )
}

export default ManpowerDetailForm