import React, { useEffect, useState , useRef, createRef} from "react";
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
import Swal from "sweetalert2";
import { useTranslation } from "react-i18next";
import FullPageLoader from "../../../Components/common/FullPageLoader";
import axios from "axios";

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

export default function WorkerLeadView({ mode }) {
    const { t } = useTranslation();
    const elementsRef = useRef(animalArray.map(() => createRef()));
    const params = useParams();
    const alert = useAlert();
    const navigate = useNavigate();
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState({}); // State to store validation errors
    const [formValues, setFormValues] = useState({
        firstname: "",
        lastname: "",
        phone: "",
        email: "",
        gender: "",
        role: "cleaner",
        country: "Israel",
        // payment_hour: "",
        // worker_id: Math.random().toString().concat("0".repeat(3)).substr(2, 5),
        renewal_visa: "",
        company_type: "",
        manpower_company_id: "",
        // employment_type: "",
        // salary: "",
        lng: "heb",
        status: "pending",
        experience_in_house_cleaning: false,
        you_have_valid_work_visa: false,
        send_bot_message: false,
    });
    // const [password, setPassword] = useState("");
    const [address, setAddress] = useState("");
    // const [skill, setSkill] = useState([]);
    // const [avl_skill, setAvlSkill] = useState([]);
    const [countries, setCountries] = useState([]);
    const [manpowerCompanies, setManpowerCompanies] = useState([]);
    // const [payment, setPayment] = useState("")
    // const [bankDetails, setBankDetails] = useState({
    //     full_name: "",
    //     bank_name: "",
    //     bank_no: null,
    //     branch_no: null,
    //     account_no: null
    // })
    // const [selectedType, setSelectedType] = useState("");
    const [salary, setSalary] = useState("");
    const [libraries] = useState(["places", "geometry"]);
    const [latitude, setLatitude] = useState(-33.865143);
    const [longitude, setLongitude] = useState(151.2099);
    const [place, setPlace] = useState();

    Geocode.setApiKey("AIzaSyBU01s3r8ER0qJd1jG0NA8itmcNe-iSTYk");
    const containerStyle = {
        width: "100%",
        height: "300px",
    };
    const center = {
        lat: latitude,
        lng: longitude,
    };

    const statusArr = {
        pending: "pending",
        rejected: "rejected",
        irrelevant: "irrelevant",
        unanswered: "unanswered",
        hiring: "hiring",
        "will-think": "will-think",
        "not-hired": "not-hired",
    };

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleGetWorkerLead = async () => {
        try {
            const response = await axios.get(
                `/api/admin/worker-leads/${params.id}/edit`,
                { headers }
            );
            const res = response?.data;
            setFormValues({
                firstname: res?.firstname,
                lastname: res?.lastname,
                role: res?.role,
                email: res?.email,
                phone: res?.phone,
                status: res?.status,
                // payment_hour: res?.payment_hour,
                gender: res?.gender,
                renewal_visa: res?.renewal_visa,
                company_type: res?.company_type,
                manpower_company_id: res?.manpower_company_id,
                // employment_type: res?.employment_type,
                // salary: res?.salary,
                lng: res?.lng,  
                // worker_id: res?.worker_id,
                // send_bot_message: res?.send_bot_message,
                // hourly_rate: res?.hourly_rate,
                experience_in_house_cleaning:
                    res?.experience_in_house_cleaning == 1 ? "true" : "false",
                you_have_valid_work_visa:
                    res?.you_have_valid_work_visa == 1 ? "true" : "false",
            });
            setAddress(res?.address);
            setLatitude(res?.latitude);
            setLongitude(res?.longitude);
            elementsRef.current.map(
                (ref) =>
                (ref.current.checked =
                    ref.current.name === animalArray[0].key
                        ? res?.is_afraid_by_dog
                        : res?.is_afraid_by_cat
                    )
            );
        } catch (error) {
            console.log(error);
        }
    };

    // const handleBankDetails = (e) => {
    //     const { name, value } = e.target;
    //     setBankDetails(prev => ({ ...prev, [name]: value }));
    // }

    const handlePlaceChanged = () => {
        if (place) {
            // setCity(place.getPlace().vicinity);
            setAddress(place.getPlace().formatted_address);
            setLatitude(place.getPlace().geometry.location.lat());
            setLongitude(place.getPlace().geometry.location.lng());
        }
    };

    // const handleSkills = (e) => {
    //     const _value = e.target.value;
    //     const checked = e.target.checked;
    //     if (checked) {
    //         setSkill((_skill) => [..._skill, _value]);
    //     } else {
    //         setSkill((_skill) => _skill.filter((i) => i !== _value));
    //     }
    // };

    // const handleAllSkills = (e) => {
    //     const checked = e.target.checked;
    //     if (checked) {
    //         setSkill(avl_skill.map((i) => i.id.toString()));
    //     } else {
    //         setSkill([]);
    //     }
    // };

    const handleUpdate = async (e) => {
        e.preventDefault();

        const data = {
            ...formValues,
            phone: formValues.phone,
            address: address,
            renewal_visa: formValues.renewal_visa,
            lng: !formValues.lng ? "en" : formValues.lng,
            // password: password,
            // skill: skill,
            status: formValues.status,
            country: formValues.country,
            latitude: latitude,
            longitude: longitude,
            // payment_type: payment,
            // employment_type: selectedType,
            // salary: selectedType === "fixed" ? salary : null,
            // bank_name: bankDetails.bank_name,
            // full_name: bankDetails.full_name,
            // bank_number: bankDetails.bank_no,
            // branch_number: bankDetails.branch_no,
            // account_number: bankDetails.account_no,
        };

        elementsRef.current.map(
            (ref) => (data[ref.current.name] = ref.current.checked)
        );

        console.log(data);
        

        try {
            await axios.put(
                `/api/admin/worker-leads/${params.id}`,
                data,
                { headers }
            );
            alert.success("Worker lead updated successfully");
            handleGetWorkerLead();
        } catch (error) {
            console.log(error);
            alert.error("Error updating worker lead");
        }
    };

    const handleAdd = async (e) => {
        e.preventDefault();
        const data = {
            ...formValues,
            phone: formValues.phone,
            address: address,
            renewal_visa: formValues.renewal_visa,
            lng: !formValues.lng ? "en" : formValues.lng,
            // password: password,
            // skill: skill,
            status: formValues.status,
            country: formValues.country,
            latitude: latitude,
            longitude: longitude,
            // payment_type: payment,
            // employment_type: selectedType,
            // salary: selectedType === "fixed" ? salary : null,
            // bank_name: bankDetails.bank_name,
            // full_name: bankDetails.full_name,
            // bank_number: bankDetails.bank_no,
            // branch_number: bankDetails.branch_no,
            // account_number: bankDetails.account_no,
        };

        elementsRef.current.map(
            (ref) => (data[ref.current.name] = ref.current.checked)
        );

        try {
            await axios.post(`/api/admin/worker-leads/add`, data, {
                headers,
            });
            alert.success("Worker lead added successfully");
            navigate("/admin/worker-leads");
        } catch (error) {
            if (error.response && error.response.data.errors) {
                setErrors(error.response.data.errors);
            } else {
                console.error("Something went wrong:", error);
            }
        }
    };

    // const getAvailableSkill = () => {
    //     axios
    //         .get(`/api/admin/services/create`, { headers })
    //         .then((response) => {
    //             setAvlSkill(response.data.services);
    //         });
    // };
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
        // getAvailableSkill();
        getCountries();
        getManpowerCompanies();
        handleGetWorkerLead();

    }, []);

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="edit-customer">
                    <h1 className="page-title editEmployer">
                        {mode === "edit"
                            ? `Edit Worker Lead #${params.id}`
                            : mode === "add"
                                ? "Add Worker Lead"
                                : `View Worker Lead #${params.id}`}
                    </h1>
                    <div
                        className="dashBox p-4"
                        style={{ background: "inherit", border: "none" }}
                    >
                        <form
                            onSubmit={
                                mode === "edit"
                                    ? handleUpdate
                                    : mode === "add"
                                        ? handleAdd
                                        : (e) => e.preventDefault()
                            }
                        >
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
                                            readOnly={
                                                mode !== "edit" &&
                                                mode !== "add"
                                            }
                                            placeholder={t("admin.global.Name")}
                                        />
                                        {errors.firstname && (
                                            <small className="text-danger mb-1">
                                                {errors.firstname}
                                            </small>
                                        )}
                                    </div>
                                </div>
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("worker.settings.l_name")} *
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
                                            readOnly={
                                                mode !== "edit" &&
                                                mode !== "add"
                                            }
                                            placeholder={t("admin.global.Name")}
                                        />
                                        {errors.lastname && (
                                            <small className="text-danger mb-1">
                                                {errors.lastname}
                                            </small>
                                        )}
                                    </div>
                                </div>
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("admin.global.Email")}
                                        </label>
                                        <input
                                            type="text"
                                            value={formValues.email}
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    email: e.target.value,
                                                });
                                            }}
                                            className="form-control"
                                            readOnly={
                                                mode !== "edit" &&
                                                mode !== "add"
                                            }
                                            placeholder={t(
                                                "admin.global.Email"
                                            )}
                                        />
                                        {errors.email && (
                                            <small className="text-danger mb-1">
                                                {errors.email}
                                            </small>
                                        )}
                                    </div>
                                </div>
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("admin.global.Phone")}
                                        </label>
                                        <PhoneInput
                                            country={"il"}
                                            value={formValues.phone}
                                            onChange={(phone, country) => {
                                                // Remove leading '0' after country code
                                                const dialCode =
                                                    country.dialCode;
                                                let formattedPhone = phone;
                                                if (
                                                    phone.startsWith(
                                                        dialCode + "0"
                                                    )
                                                ) {
                                                    formattedPhone =
                                                        dialCode +
                                                        phone.slice(
                                                            dialCode.length + 1
                                                        );
                                                }
                                                setFormValues({
                                                    ...formValues,
                                                    phone: formattedPhone,
                                                });
                                            }}
                                            inputClass="form-control"
                                            placeholder={t("admin.leads.phone")} // Move placeholder out of inputProps
                                            name="phone" // Move name out of inputProps
                                            required={true} // Move required out of inputProps
                                            readOnly={
                                                mode !== "edit" &&
                                                mode !== "add"
                                            } // Set readOnly directly
                                        />
                                        {errors.phone && (
                                            <small className="text-danger mb-1">
                                                {errors.phone}
                                            </small>
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
                                    <div>
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
                                            {t("nonIsrailContract.role")}
                                        </label>
                                        <select
                                            className="form-control"
                                            value={formValues.role}
                                            onChange={(e) =>
                                                setFormValues({
                                                    ...formValues,
                                                    role: e.target.value,
                                                })
                                            }
                                        >
                                            <option value="cleaner">
                                                Cleaner
                                            </option>
                                            <option value="general_worker">
                                                General worker
                                            </option>
                                        </select>
                                        {errors.role && (
                                            <small className="text-danger mb-1">
                                                {errors.role}
                                            </small>
                                        )}
                                    </div>
                                </div>
                                {/* <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("worker.settings.p_ph")}(ILS)
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
                                </div> */}
                                {/* <div className="col-sm-6">
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
                                                    worker_id:
                                                        e.target.value,
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
                                </div> */}
                                {/* <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("worker.settings.pass")} *
                                        </label>
                                        <input
                                            type="password"
                                            value={password}
                                            onChange={(e) =>
                                                setPassword(e.target.value)
                                            }
                                            className="form-control"
                                            required
                                            placeholder={t("worker.settings.pass")}
                                            autoComplete="new-password"
                                        />
                                    </div>
                                    {errors.password ? (
                                        <small className="text-danger mb-1">
                                            {errors.password}
                                        </small>
                                    ) : (
                                        ""
                                    )}
                                </div> */}
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("worker.settings.lng")}
                                        </label>

                                        <select
                                            className="form-control"
                                            value={formValues.lng}
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    lng: e.target.value
                                                })
                                            }}
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
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("worker.settings.country")}
                                        </label>

                                        <select
                                            className="form-control"
                                            value={formValues.country}
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    country:
                                                        e.target.value,
                                                });
                                            }}
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
                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                {t("worker.settings.renewal_visa")}
                                            </label>
                                            <input
                                                type="date"
                                                value={formValues.renewal_visa}
                                                onChange={(e) => {
                                                    setFormValues({
                                                        ...formValues,
                                                        renewal_visa:
                                                            e.target.value,
                                                    });
                                                }}
                                                className="form-control"
                                                placeholder={t("worker.settings.renewal_visa")}
                                            />
                                        </div>
                                    </div>
                                )}
                                {/* <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("worker.settings.paymentMethod")}
                                        </label>

                                        <select
                                            className="form-control"
                                            value={payment}
                                            onChange={(e) =>
                                                setPayment(e.target.value)
                                            }
                                        >
                                            <option value="">{t("worker.settings.pleaseSelect")}</option>
                                            <option value="cheque">{t("worker.settings.cheque")}</option>
                                            <option value="money_transfer">{t("worker.settings.moneyTrasnfer")}</option>
                                        </select>
                                    </div>
                                    {errors.payment_type ? (
                                        <small className="text-danger mb-1">
                                            {errors.payment_type}
                                        </small>
                                    ) : (
                                        ""
                                    )}
                                </div> */}



                                {/* {
                                    payment === "money_transfer" && (

                                        <>
                                            <div className="col-sm-6">
                                                <div className="form-group">
                                                    <label className="control-label">
                                                        {t("worker.settings.fullName")}
                                                    </label>
                                                    <input
                                                        type="text"
                                                        value={bankDetails.full_name}
                                                        name="full_name"
                                                        onChange={handleBankDetails}
                                                        className="form-control"
                                                        placeholder={t("worker.settings.enterFullname")}
                                                    />
                                                    {errors.full_name ? (
                                                        <small className="text-danger mb-1">
                                                            {errors.full_name}
                                                        </small>
                                                    ) : (
                                                        ""
                                                    )}
                                                </div>
                                            </div>
                                            <div className="col-sm-6">
                                                <div className="form-group">
                                                    <label className="control-label">
                                                        {t("worker.settings.bankName")}
                                                    </label>
                                                    <input
                                                        type="text"
                                                        value={bankDetails.bank_name}
                                                        name="bank_name"
                                                        onChange={handleBankDetails}
                                                        className="form-control"
                                                        placeholder={t("worker.settings.enterBankname")}
                                                    />
                                                    {errors.bank_name ? (
                                                        <small className="text-danger mb-1">
                                                            {errors.bank_name}
                                                        </small>
                                                    ) : (
                                                        ""
                                                    )}
                                                </div>
                                            </div>
                                            <div className="col-sm-6">
                                                <div className="form-group">
                                                    <label className="control-label">
                                                        {t("worker.settings.bankNumber")}
                                                    </label>
                                                    <input
                                                        type="text"
                                                        value={bankDetails.bank_no}
                                                        name="bank_no"
                                                        onChange={handleBankDetails}
                                                        className="form-control"
                                                        placeholder={t("worker.settings.enterBanknumber")}
                                                    />
                                                    {errors.bank_number ? (
                                                        <small className="text-danger mb-1">
                                                            {errors.bank_number}
                                                        </small>
                                                    ) : (
                                                        ""
                                                    )}
                                                </div>
                                            </div>
                                            <div className="col-sm-6">
                                                <div className="form-group">
                                                    <label className="control-label">
                                                        {t("worker.settings.branchNumber")}
                                                    </label>
                                                    <input
                                                        type="text"
                                                        value={bankDetails.branch_no}
                                                        name="branch_no"
                                                        onChange={handleBankDetails}
                                                        className="form-control"
                                                        placeholder={t("worker.settings.enterBranchnumber")}
                                                    />
                                                    {errors.branch_number ? (
                                                        <small className="text-danger mb-1">
                                                            {errors.branch_number}
                                                        </small>
                                                    ) : (
                                                        ""
                                                    )}
                                                </div>
                                            </div>
                                            <div className="col-sm-6">
                                                <div className="form-group">
                                                    <label className="control-label">
                                                        {t("worker.settings.accountNumber")}
                                                    </label>
                                                    <input
                                                        type="text"
                                                        value={bankDetails.account_no}
                                                        name="account_no"
                                                        onChange={handleBankDetails}
                                                        className="form-control"
                                                        placeholder={t("worker.settings.enterAccountnumber")}
                                                    />
                                                    {errors.account_number ? (
                                                        <small className="text-danger mb-1">
                                                            {errors.account_number}
                                                        </small>
                                                    ) : (
                                                        ""
                                                    )}
                                                </div>
                                            </div>
                                        </>
                                    )
                                } */}
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
                                                        manpower_company_id:
                                                            "",
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
                                                    {
                                                        errors.manpower_company_id
                                                    }
                                                </small>
                                            ) : (
                                                ""
                                            )}
                                        </div>
                                    </div>
                                )}
                                {/* <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">{t("global.Type")}</label>
                                        <select
                                            className="form-control"
                                            value={selectedType}
                                            onChange={(e) => setSelectedType(e.target.value)}
                                        >
                                            <option value="">{t("worker.settings.pleaseSelect")}</option>
                                            <option value="fixed">{t("worker.settings.fixed")}</option>
                                            <option value="hourly">{t("worker.settings.hourly")}</option>
                                        </select>
                                    </div>
                                </div> */}

                                {/* {selectedType === "fixed" && (
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
                                )} */}
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("admin.leads.areas")}
                                        </label>
                                        <select
                                            className="form-control"
                                            value={
                                                formValues.experience_in_house_cleaning
                                            }
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    experience_in_house_cleaning:
                                                        e.target.value ===
                                                            "true"
                                                            ? true
                                                            : false, // Ensure boolean conversion
                                                });
                                            }}
                                        >
                                            <option value="true">Yes</option>
                                            <option value="false">No</option>
                                        </select>
                                    </div>
                                </div>
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t(
                                                "admin.leads.you_have_valid_work_visa"
                                            )}
                                        </label>
                                        <select
                                            className="form-control"
                                            value={
                                                formValues.you_have_valid_work_visa
                                            }
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    you_have_valid_work_visa:
                                                        e.target.value ===
                                                            "true"
                                                            ? true
                                                            : false, // Ensure boolean conversion
                                                });
                                            }}
                                        >
                                            <option value="true">Yes</option>
                                            <option value="false">No</option>
                                        </select>
                                    </div>
                                </div>
                                {mode == "add" && (
                                    <div className="col-sm-6">
                                        <div className="form-group d-flex align-items-center">
                                            <label
                                                htmlFor="waBot"
                                                className="control-label navyblueColor"
                                                style={{ width: "10rem" }}
                                            >
                                                {t(
                                                    "admin.leads.AddLead.SendWPBotMessage"
                                                )}
                                            </label>
                                            <input
                                                type="checkbox"
                                                id="waBot"
                                                value={
                                                    formValues.send_bot_message
                                                }
                                                onChange={(e) => {
                                                    setFormValues({
                                                        ...formValues,
                                                        send_bot_message:
                                                            e.target.checked,
                                                    });
                                                }}
                                            />
                                        </div>
                                    </div>
                                )}
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">
                                            Status
                                        </label>

                                        <select
                                            name="status"
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    status: e.target.value,
                                                });
                                            }}
                                            value={formValues.status}
                                            className="form-control mb-3"
                                        >
                                            {Object.keys(statusArr).map((s) => (
                                                <option key={s} value={s}>
                                                    {statusArr[s]}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.status && (
                                            <small className="text-danger mb-1">
                                                {errors.status}
                                            </small>
                                        )}
                                    </div>
                                </div>
                            </div>
                            <div className="form-group">
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
                                        {address ? (
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
                            {/* <div className="col-sm-12">
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
                            </div> */}
                            <div className="col-sm-12 mt-4">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("worker.settings.areYouAfraid")}
                                    </label>
                                </div>
                                {animalArray.map((item, index) => (
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
                            {(mode === "edit" || mode === "add") && (
                                <div className="form-group text-center">
                                    <button
                                        type="submit"
                                        className="btn px-3 text-center navyblue"
                                    >
                                        {" "}
                                        {mode === "add" ? (
                                            <span>
                                                {t("workerInviteForm.add")}{" "}
                                                <i className="btn-icon fas fa-plus-circle"></i>
                                            </span>
                                        ) : (
                                            t("workerInviteForm.update")
                                        )}
                                    </button>
                                </div>
                            )}
                        </form>
                    </div>
                </div>
            </div>
            {loading && <FullPageLoader visible={loading} />}
        </div>
    );
}
