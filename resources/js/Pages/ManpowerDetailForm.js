import { Base64 } from 'js-base64';
import React, { useEffect, useState } from 'react'
import { useTranslation } from "react-i18next";
import { useParams } from 'react-router-dom';
import axios from 'axios';

const ManpowerDetailForm = ({ setNextStep, values }) => {
    const { t } = useTranslation();
    const params = useParams();
    const workerId = Base64.decode(params.id);
    const [worker, setWorker] = useState({});
    const [countries, setCountries] = useState([]);
    const [errors, setErrors] = useState({});
    // const [country, setCountry] = useState("");
    const [formValues, setFormValues] = useState({
        worker_id: workerId,
        firstname: "",
        lastname: "",
        email: "",
        country: "",
        gender: "",
        renewal_visa: "",
        passportNumber: "",
        IDNumber: ""
    })



    const getCountries = () => {
        axios.get(`/api/admin/countries`).then((response) => {
            setCountries(response.data.countries);
        });
    };

    const getWorker = async () => {
        const response = await axios.post(`/api/worker-detail`, { worker_id: workerId });
        console.log(response.data);

        setFormValues({
            worker_id: response.data.worker.id,
            firstname: response.data.worker.firstname,
            lastname: response.data.worker.lastname,
            email: response.data.worker.email,
            country: response.data.worker.country,
            gender: response.data.worker.gender,
            renewal_visa: response.data.worker.renewal_visa,
            passportNumber: response.data.worker.passport_no ?? "",
            IDNumber: response.data.worker.id_number ?? "",
        })
        setWorker(response.data.worker);
    }

    const saveWorkerDetails = async (e) => {
        e.preventDefault();
        try {
            const response = await axios.post("/api/save-worker-detail", formValues);
            if(response.status === 200) {
                setNextStep(prev => prev + 1);
                window.location.reload();
            }
            console.log(response.data.worker);
        } catch (error) {
            if (error.response && error.response.data.errors) {
                setErrors(error.response.data.errors);
            } else {
                console.error("Something went wrong:", error);
            }
        }
    }

    useEffect(() => {
        getWorker()
        getCountries()
    }, [])


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

    const handleFileChange = (e, type) => {
        const data = new FormData();
        data.append("id", workerId);
        if (e.target.files.length > 0) {
            data.append(`${type}`, e.target.files[0]);
        }
        handleDocSubmit(data);
    };


    return (
        <div>
            <div>
                <div className="mb-4">
                    <p className="navyblueColor font-30 mt-4 font-w-500"> {t("global.declaration_form")}</p>
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
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    passportNumber: e.target.value,
                                                });
                                            }}
                                            className="form-control"
                                            placeholder={t("form101.passport_num")}
                                        />
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
                            {formValues.country != "Israel" && (
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
                            )}
                        </div>
                        <div className="row justify-content-center">
                            {formValues.country == "Israel" && (
                                <div className="col-sm">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("form101.id_num")}
                                        </label>
                                        <input
                                            type="passportNumber"
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    id_number: e.target.value,
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
                        <div className="row justify-content-center">
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
                                        {t("safeAndGear.Next")}

                                    </button>

                                </div>
                            </div>
                        </div>
                    </section >
                </form >
            </div >
        </div >
    )
}

export default ManpowerDetailForm