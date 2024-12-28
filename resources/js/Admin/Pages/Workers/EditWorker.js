import React, { useEffect, useState, useRef, createRef } from "react";
import { useAlert } from "react-alert";
import { useParams, useNavigate } from "react-router-dom";
import Sidebar from "../../Layouts/Sidebar";
import PhoneInput from 'react-phone-input-2';
import 'react-phone-input-2/lib/style.css';

import {
    GoogleMap,
    LoadScript,
    InfoWindow,
    Marker,
    Autocomplete,
} from "@react-google-maps/api";
import Geocode from "react-geocode";
import { useTranslation } from "react-i18next";
import FullPageLoader from "../../../Components/common/FullPageLoader";

const animalArray = [
    {
        name: "Dog",
        key: "is_afraid_by_dog",
    },
    {
        name: "Cat",
        key: "is_afraid_by_cat",
    },
];

export default function EditWorker() {

    const { t } = useTranslation();
    const elementsRef = useRef(animalArray.map(() => createRef()));
    const [formValues, setFormValues] = useState({
        firstname: "",
        lastname: "",
        phone: "",
        email: "",
        gender: "",
        role: "",
        payment_hour: "",
        worker_id: Math.random().toString().concat("0".repeat(3)).substr(2, 5),
        renewal_date: "",
        company_type: "",
        manpower_company_id: "",
    });
    const [password, setPassword] = useState("");
    const [lng, setLng] = useState("");
    const [address, setAddress] = useState("");
    const [skill, setSkill] = useState([]);
    const [itemStatus, setItemStatus] = useState("");
    const [country, setCountry] = useState("Israel");

    const [countries, setCountries] = useState([]);
    const [avl_skill, setAvlSkill] = useState([]);
    const [manpowerCompanies, setManpowerCompanies] = useState([]);
    const [isSubmitting, setIsSubmitting] = useState(false);

    const [errors, setErrors] = useState([]);
    const params = useParams();
    const navigate = useNavigate();
    const alert = useAlert();
    const [libraries] = useState(["places", "geometry"]);
    const [latitude, setLatitude] = useState(-33.865143);
    const [longitude, setLongitude] = useState(151.2099);
    const [place, setPlace] = useState();
    const [payment, setPayment] = useState("");

    const [employmentType, setEmploymentType] = useState("");
    const [salary, setSalary] = useState("");

    const [loading, setLoading] = useState(false);
    const [bankDetails, setBankDetails] = useState({
        payment_type: "",
        full_name: "",
        bank_name: "",
        bank_no: null,
        branch_no: null,
        account_no: null
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

    const handleFormValuesChange = (name, value) => {
        setFormValues(prev => ({
            ...prev,
            [name]: value,
        }));
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setBankDetails((prevDetails) => ({
            ...prevDetails,
            [name]: value
        }));
    };

    const handlePlaceChanged = () => {
        if (place) {
            // setCity(place.getPlace().vicinity);
            setAddress(place.getPlace().formatted_address);
            setLatitude(place.getPlace().geometry.location.lat());
            setLongitude(place.getPlace().geometry.location.lng());
        }
    };

    const handleSkills = (e) => {
        const _value = e.target.value;
        const checked = e.target.checked;
        if (checked) {
            setSkill((_skill) => [..._skill, _value]);
        } else {
            setSkill((_skill) => _skill.filter((i) => i !== _value));
        }
    };

    const handleAllSkills = (e) => {
        const checked = e.target.checked;
        if (checked) {
            setSkill(avl_skill.map((i) => i.id.toString()));
        } else {
            setSkill([]);
        }
    };

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleUpdate = (e) => {
        e.preventDefault();
        setLoading(true);
        setIsSubmitting(true);

        const data = {
            ...formValues,
            phone: formValues.phone,
            address: address,
            renewal_visa: formValues.renewal_date,
            lng: !lng ? "en" : lng,
            password: password,
            skill: skill,
            status: !itemStatus ? 1 : parseInt(itemStatus),
            country: country,
            latitude: latitude,
            longitude: longitude,
            payment_type: payment,
            employment_type: employmentType,
            salary: employmentType === "fixed" ? salary : null,
            bank_name: bankDetails.bank_name,
            full_name: bankDetails.full_name,
            bank_number: bankDetails.bank_no,
            branch_number: bankDetails.branch_no,
            account_number: bankDetails.account_no,
        };
        elementsRef.current.map(
            (ref) => (data[ref.current.name] = ref.current.checked)
        );
        axios
            .put(`/api/admin/workers/${params.id}`, data, { headers })
            .then((response) => {
                if (response.data.errors) {
                    setErrors(response.data.errors);
                    setLoading(false);
                } else {
                    setLoading(false);
                    alert.success("Worker has been updated successfully");
                    setTimeout(() => {
                        navigate("/admin/workers");
                    }, 1000);
                }
                setIsSubmitting(false);
            })
            .catch((e) => {
                setLoading(false);
                setIsSubmitting(false);
            });
    };

    const getWorker = () => {
        axios
            .get(`/api/admin/workers/${params.id}/edit`, { headers })
            .then((response) => {
                const _worker = response.data.worker;
                const {
                    passcode,
                    skill,
                    address,
                    status,
                    lng,
                    country,
                    latitude,
                    longitude,
                    employment_type,
                    salary,
                    is_afraid_by_cat,
                    is_afraid_by_dog,
                } = response.data.worker;

                setFormValues({
                    ...formValues,
                    firstname: _worker.firstname,
                    lastname: _worker.lastname,
                    email: _worker.email,
                    phone: _worker.phone,
                    renewal_date: _worker.renewal_visa,
                    gender: _worker.gender,
                    role: _worker.role,
                    payment_hour: _worker.payment_per_hour,
                    worker_id: _worker.worker_id,
                    company_type: _worker.company_type,
                    manpower_company_id: _worker.manpower_company_id,
                });

                setBankDetails({
                    bank_name: _worker.bank_name,
                    account_no: _worker.account_number,
                    bank_no: _worker.bank_number,
                    branch_no: _worker.branch_number,
                    full_name: _worker.full_name
                })
                setPayment(_worker.payment_type)

                setEmploymentType(employment_type);
                setSalary(salary);
                setPassword(passcode);
                setSkill(skill ? JSON.parse(skill) : []);
                setAddress(address);
                setItemStatus(status);
                setLng(lng);
                setCountry(country);
                setLatitude(latitude);
                setLongitude(longitude);
                elementsRef.current.map(
                    (ref) =>
                    (ref.current.checked =
                        ref.current.name === animalArray[0].key
                            ? is_afraid_by_dog
                            : is_afraid_by_cat)
                );
            });
    };
    const getAvailableSkill = () => {
        axios
            .get(`/api/admin/services/create`, { headers })
            .then((response) => {
                setAvlSkill(response.data.services);
            });
    };
    const getCountries = () => {
        axios.get(`/api/admin/countries`, { headers }).then((response) => {
            setCountries(response.data.countries);
        });
    };
    const getManpowerCompanies = async () => {
        await axios
            .get("/api/admin/manpower-companies-list", {
                headers,
            })
            .then((response) => {
                if (response?.data?.companies?.length > 0) {
                    setManpowerCompanies(response.data.companies);
                } else {
                    setManpowerCompanies([]);
                }
            });
    };
    useEffect(() => {
        getWorker();
        getCountries();
        getAvailableSkill();
        getManpowerCompanies();
    }, []);
    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="edit-customer">
                    <h1 className="page-title editEmployer">Edit Worker</h1>
                    <div className="dashBox p-0 p-md-4" style={{ background: "inherit", border: "none" }}>
                        <form>
                            <div className="row">
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("worker.settings.f_name")} *
                                        </label>
                                        <input
                                            type="text"
                                            value={formValues.firstname}
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    firstname: e.target.value,
                                                });
                                            }}
                                            className="form-control"
                                            required
                                            placeholder={t("workerInviteForm.enter_first_name")}
                                        />
                                        {errors.firstname ? (
                                            <small className="text-danger mb-1">
                                                {errors.firstname}
                                            </small>
                                        ) : (
                                            ""
                                        )}
                                    </div>
                                </div>
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("worker.settings.l_name")}
                                        </label>
                                        <input
                                            type="text"
                                            value={formValues.lastname}
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    lastname: e.target.value,
                                                });
                                            }}
                                            className="form-control"
                                            placeholder={t("workerInviteForm.enter_last_name")}
                                        />
                                    </div>
                                </div>
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("worker.settings.email")}
                                        </label>
                                        <input
                                            type="tyoe"
                                            value={formValues.email}
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    email: e.target.value,
                                                });
                                            }}
                                            className="form-control"
                                            readOnly
                                            placeholder={t("worker.settings.email")}
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
                                        <label className="control-label">{t("worker.settings.phone")}</label>
                                        <PhoneInput
                                            country={'il'}
                                            value={formValues.phone}
                                            onChange={(phone, country) => {
                                                // Remove leading '0' after country code
                                                const dialCode = country.dialCode;
                                                let formattedPhone = phone;
                                                if (phone.startsWith(dialCode + '0')) {
                                                    formattedPhone = dialCode + phone.slice(dialCode.length + 1);
                                                }
                                                handleFormValuesChange('phone', formattedPhone)
                                            }}
                                            inputClass="form-control"
                                            inputProps={{
                                                name: 'phone',
                                                required: true,
                                                placeholder: t("worker.settings.phone"),
                                            }}
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
                                                        gender: e.target.value,
                                                    });
                                                }}
                                                checked={
                                                    formValues.gender === "male"
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
                                                        gender: e.target.value,
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
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("nonIsrailContract.role")}
                                        </label>
                                        <input
                                            type="text"
                                            value={formValues.role}
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    role: e.target.value,
                                                });
                                            }}
                                            className="form-control"
                                            placeholder={t("nonIsrailContract.role")}
                                        />
                                        {errors.role && (
                                            <small className="text-danger mb-1">
                                                {errors.role}
                                            </small>
                                        )}
                                    </div>
                                </div>
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("worker.settings.p_ph")} (ILS)
                                        </label>
                                        <input
                                            type="text"
                                            value={formValues.payment_hour}
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    payment_hour:
                                                        e.target.value,
                                                });
                                            }}
                                            className="form-control"
                                            placeholder={t("worker.settings.p_ph")}
                                        />
                                    </div>
                                </div>
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("worker.settings.w_id")}
                                        </label>
                                        <input
                                            type="text"
                                            value={formValues.worker_id}
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    worker_id: e.target.value,
                                                });
                                            }}
                                            className="form-control"
                                            placeholder={t("worker.settings.w_id")}
                                        />
                                        {errors.worker_id ? (
                                            <small className="text-danger mb-1">
                                                {errors.worker_id}
                                            </small>
                                        ) : (
                                            ""
                                        )}
                                    </div>
                                </div>
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("worker.settings.pass")} *
                                        </label>
                                        <input
                                            type="password"
                                            onChange={(e) =>
                                                setPassword(e.target.value)
                                            }
                                            className="form-control"
                                            required
                                            placeholder={t("worker.settings.pass")}
                                            autoComplete="new-password"
                                        />
                                    </div>
                                </div>
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("worker.settings.lng")}
                                        </label>

                                        <select
                                            className="form-control"
                                            value={lng}
                                            onChange={(e) =>
                                                setLng(e.target.value)
                                            }
                                        >
                                            <option value="heb">Hebrew</option>
                                            <option value="en">English</option>
                                            <option value="ru">Russian</option>
                                            <option value="spa">Spanish</option>
                                        </select>
                                    </div>
                                </div>
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("worker.settings.country")}
                                        </label>

                                        <select
                                            className="form-control"
                                            value={country}
                                            onChange={(e) =>
                                                setCountry(e.target.value)
                                            }
                                        >
                                            {countries &&
                                                countries.map((item, index) => (
                                                    <option
                                                        value={item.name}
                                                        key={index}
                                                    >
                                                        {item.name}
                                                    </option>
                                                ))}
                                        </select>
                                    </div>
                                </div>
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            Payment Method
                                        </label>

                                        <select
                                            className="form-control"
                                            value={payment}
                                            onChange={(e) =>
                                                setPayment(e.target.value)
                                            }
                                        >
                                            <option value="">--- please select ---</option>
                                            <option value="cheque">Cheque</option>
                                            <option value="money_transfer">Money Transfer</option>
                                        </select>
                                        {errors?.payment_type ? (
                                            <small className="text-danger mb-1">
                                                {errors?.payment_type}
                                            </small>
                                        ) : (
                                            ""
                                        )}
                                    </div>
                                </div>
                                {country != "Israel" && (
                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                {t("worker.settings.renewal_visa")}{" "}
                                            </label>
                                            <input
                                                type="date"
                                                value={formValues.renewal_date}
                                                onChange={(e) => {
                                                    setFormValues({
                                                        ...formValues,
                                                        renewal_date:
                                                            e.target.value,
                                                    });
                                                }}
                                                className="form-control"
                                                placeholder={t("worker.settings.renewal_visa")}
                                            />
                                        </div>
                                    </div>
                                )}

                                {
                                    payment === "money_transfer" && (

                                        <>
                                            <div className="col-sm-6">
                                                <div className="form-group">
                                                    <label className="control-label">
                                                        Full Name
                                                    </label>
                                                    <input
                                                        type="text"
                                                        value={bankDetails.full_name}
                                                        name="full_name"
                                                        onChange={handleChange}
                                                        className="form-control"
                                                        placeholder="Enter Full Name"
                                                    />
                                                    {errors?.full_name ? (
                                                        <small className="text-danger mb-1">
                                                            {errors?.full_name}
                                                        </small>
                                                    ) : (
                                                        ""
                                                    )}
                                                </div>
                                            </div>
                                            <div className="col-sm-6">
                                                <div className="form-group">
                                                    <label className="control-label">
                                                        Bank Name
                                                    </label>
                                                    <input
                                                        type="text"
                                                        value={bankDetails.bank_name}
                                                        name="bank_name"
                                                        onChange={handleChange}
                                                        className="form-control"
                                                        placeholder="Enter Bank Name"
                                                    />
                                                    {errors?.bank_name ? (
                                                        <small className="text-danger mb-1">
                                                            {errors?.bank_name}
                                                        </small>
                                                    ) : (
                                                        ""
                                                    )}
                                                </div>
                                            </div>
                                            <div className="col-sm-6">
                                                <div className="form-group">
                                                    <label className="control-label">
                                                        Bank Number
                                                    </label>
                                                    <input
                                                        type="text"
                                                        value={bankDetails.bank_no}
                                                        name="bank_no"
                                                        onChange={handleChange}
                                                        className="form-control"
                                                        placeholder="Enter Bank Number"
                                                    />
                                                    {errors?.bank_number ? (
                                                        <small className="text-danger mb-1">
                                                            {errors?.bank_number}
                                                        </small>
                                                    ) : (
                                                        ""
                                                    )}
                                                </div>
                                            </div>
                                            <div className="col-sm-6">
                                                <div className="form-group">
                                                    <label className="control-label">
                                                        Branch Number
                                                    </label>
                                                    <input
                                                        type="text"
                                                        value={bankDetails.branch_no}
                                                        name="branch_no"
                                                        onChange={handleChange}
                                                        className="form-control"
                                                        placeholder="Enter Branch Number"
                                                    />
                                                    {errors?.branch_number ? (
                                                        <small className="text-danger mb-1">
                                                            {errors?.branch_number}
                                                        </small>
                                                    ) : (
                                                        ""
                                                    )}
                                                </div>
                                            </div>
                                            <div className="col-sm-6">
                                                <div className="form-group">
                                                    <label className="control-label">
                                                        Acount Number
                                                    </label>
                                                    <input
                                                        type="text"
                                                        value={bankDetails.account_no}
                                                        name="account_no"
                                                        onChange={handleChange}
                                                        className="form-control"
                                                        placeholder="Enter Account Number"
                                                    />
                                                    {errors?.account_number ? (
                                                        <small className="text-danger mb-1">
                                                            {errors?.account_number}
                                                        </small>
                                                    ) : (
                                                        ""
                                                    )}
                                                </div>
                                            </div>
                                        </>
                                    )
                                }

                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("global.company")}
                                        </label>
                                    </div>
                                    <div className="form-check-inline">
                                        <label className="form-check-label">
                                            <input
                                                type="radio"
                                                className="form-check-input"
                                                value="my-company"
                                                onChange={(e) => {
                                                    setFormValues({
                                                        ...formValues,
                                                        company_type:
                                                            e.target.value,
                                                        manpower_company_id: "",
                                                    });
                                                }}
                                                checked={
                                                    formValues.company_type ===
                                                    "my-company"
                                                }
                                            />
                                            {t("admin.global.myCompany")}
                                        </label>
                                    </div>
                                    <div className="form-check-inline">
                                        <label className="form-check-label">
                                            <input
                                                type="radio"
                                                className="form-check-input"
                                                value="manpower"
                                                onChange={(e) => {
                                                    setFormValues({
                                                        ...formValues,
                                                        company_type:
                                                            e.target.value,
                                                    });
                                                }}
                                                checked={
                                                    formValues.company_type ===
                                                    "manpower"
                                                }
                                            />
                                            {t("admin.global.manpower")}
                                        </label>
                                    </div>
                                    <div className="form-check-inline">
                                        <label className="form-check-label">
                                            <input
                                                type="radio"
                                                className="form-check-input"
                                                value="freelancer"
                                                onChange={(e) => {
                                                    setFormValues({
                                                        ...formValues,
                                                        company_type:
                                                            e.target.value,
                                                    });
                                                }}
                                                checked={
                                                    formValues.company_type ===
                                                    "freelancer"
                                                }
                                            />
                                            {t("admin.global.freelancer")}
                                        </label>
                                    </div>
                                    <div>
                                        {errors.company_type ? (
                                            <small className="text-danger mb-1">
                                                {errors.company_type}
                                            </small>
                                        ) : (
                                            ""
                                        )}
                                    </div>
                                </div>
                                {formValues.company_type === "manpower" && (
                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                {t("admin.global.manpower")}
                                            </label>

                                            <select
                                                name="manpower-id"
                                                className="form-control"
                                                value={
                                                    formValues.manpower_company_id
                                                }
                                                onChange={(e) =>
                                                    setFormValues({
                                                        ...formValues,
                                                        manpower_company_id:
                                                            e.target.value,
                                                    })
                                                }
                                            >
                                                <option value="">
                                                    {t("admin.global.select_manpower")}
                                                </option>
                                                {manpowerCompanies.map(
                                                    (mpc, index) => (
                                                        <option
                                                            value={mpc.id}
                                                            key={mpc.id}
                                                        >
                                                            {mpc.name}
                                                        </option>
                                                    )
                                                )}
                                            </select>
                                        </div>
                                        <div>
                                            {errors.manpower_company_id ? (
                                                <small className="text-danger mb-1">
                                                    {errors.manpower_company_id}
                                                </small>
                                            ) : (
                                                ""
                                            )}
                                        </div>
                                    </div>
                                )}
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">{t("global.Type")}</label>
                                        <select
                                            className="form-control"
                                            value={employmentType}
                                            onChange={(e) => setEmploymentType(e.target.value)}
                                        >
                                            <option value="">{t("worker.settings.pleaseSelect")}</option>
                                            <option value="fixed">{t("worker.settings.fixed")}</option>
                                            <option value="hourly">{t("worker.settings.hourly")}</option>
                                        </select>
                                    </div>
                                </div>

                                {employmentType === "fixed" && (
                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">{t("worker.settings.salary")}</label>
                                            <input
                                                type="number"
                                                className="form-control"
                                                placeholder={t("worker.settings.salary")}
                                                value={salary}
                                                onChange={(e) => setSalary(e.target.value)}
                                            />
                                        </div>
                                    </div>
                                )}
                            </div>
                            <div className="form-group">
                                <label className="control-label">
                                    {t("admin.global.location")}
                                </label>
                                <LoadScript
                                    googleMapsApiKey="AIzaSyBU01s3r8ER0qJd1jG0NA8itmcNe-iSTYk"
                                    libraries={libraries}
                                >
                                    {/* <GoogleMap
                                            mapContainerStyle={containerStyle}
                                            center={center}
                                            zoom={15}
                                        >
                                            <Marker
                                                draggable={true}
                                                onDragEnd={(e) => onMarkerDragEnd(e)}
                                                position={{
                                                    lat: latitude,
                                                    lng: longitude,
                                                }}
                                            />
                                            {address ? (
                                                <InfoWindow
                                                    onClose={(e) => onInfoWindowClose(e)}
                                                    position={{
                                                        lat: latitude + 0.0018,
                                                        lng: longitude,
                                                    }}
                                                >
                                                    <div>
                                                        <span style={{ padding: 0, margin: 0 }}>
                                                            {address}
                                                        </span>
                                                    </div>
                                                </InfoWindow>
                                            ) : (
                                                <></>
                                            )}
                                            <Marker />
                                        </GoogleMap> */}
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
                                    {t("workerInviteForm.full_address")}{" "}
                                    <small className="text-pink mb-1">
                                        &nbsp; ({t("workerInviteForm.auto_complete")})
                                    </small>
                                </label>
                                <input
                                    type="text"
                                    value={address}
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
                            <div className="col-sm-12">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("worker.settings.skills")}
                                    </label>
                                </div>
                                <div className="form-check mb-3">
                                    <label className="form-check-label">
                                        <input
                                            type="checkbox"
                                            className="form-check-input"
                                            onChange={handleAllSkills}
                                        />
                                        <strong>{t("modal.select_all")}</strong>
                                    </label>
                                </div>

                                {avl_skill.map((item, index) => (
                                    <div className="form-check" key={index}>
                                        <label className="form-check-label">
                                            <input
                                                type="checkbox"
                                                className="form-check-input"
                                                name="skills"
                                                value={item.id}
                                                onChange={handleSkills}
                                                checked={skill.includes(
                                                    item.id.toString()
                                                )}
                                            />
                                            {item.name}
                                        </label>
                                    </div>
                                ))}
                            </div>
                            <div className="col-sm-12 mt-4">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("worker.settings.areYouAfraid")}
                                    </label>
                                </div>
                                {animalArray &&
                                    animalArray.map((item, index) => (
                                        <div
                                            className="form-check"
                                            key={item.key}
                                        >
                                            <label className="form-check-label">
                                                <input
                                                    ref={
                                                        elementsRef.current[
                                                        index
                                                        ]
                                                    }
                                                    type="checkbox"
                                                    className="form-check-input"
                                                    name={item.key}
                                                    value={item.key}
                                                />
                                                {item.name}
                                            </label>
                                        </div>
                                    ))}
                            </div>
                            <div className="form-group mt-4">
                                <label className="control-label">{t("worker.settings.status")}</label>
                                <select
                                    className="form-control"
                                    value={itemStatus}
                                    onChange={(e) =>
                                        setItemStatus(e.target.value)
                                    }
                                >
                                    <option value="1">{t("worker.settings.Enable")}</option>
                                    <option value="0">{t("worker.settings.Disable")}</option>
                                </select>
                                {errors.status ? (
                                    <small className="text-danger mb-1">
                                        {errors.status}
                                    </small>
                                ) : (
                                    ""
                                )}
                            </div>
                            <div className="form-group text-center">
                                <input
                                    type="submit"
                                    onClick={handleUpdate}
                                    className="btn navyblue"
                                    disabled={isSubmitting}
                                    value={t("workerInviteForm.submit")}
                                />
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            {loading && <FullPageLoader visible={loading} />}
        </div>
    );
}
