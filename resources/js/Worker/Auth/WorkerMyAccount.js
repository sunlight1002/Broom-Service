import React, { createRef, useEffect, useRef, useState } from "react";
import { useAlert } from "react-alert";
import { useTranslation } from "react-i18next";
import FullPageLoader from "../../Components/common/FullPageLoader";
import PhoneInput from 'react-phone-input-2';
import 'react-phone-input-2/lib/style.css';
import styled from 'styled-components';

const StyledPhoneInput = styled(PhoneInput)`
.form-control {
    width: 100%;
    max-width: 100%;
}
`;

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
export default function WorkerMyAccount() {
    const elementsRef = useRef(animalArray.map(() => createRef()));
    const [firstname, setFirstName] = useState("");
    const [lastname, setLastName] = useState("");
    const [phone, setPhone] = useState("");
    const [email, setEmail] = useState("");
    const [renewal_date, setRenewalDate] = useState("");
    const [gender, setGender] = useState("male");
    const [worker_id, setWorkerId] = useState(
        Math.random().toString().concat("0".repeat(3)).substr(2, 5)
    );
    const [password, setPassword] = useState("");
    const [lng, setLng] = useState("");
    const [address, setAddress] = useState("");
    const [country, setCountry] = useState("Israel");
    const [loading, setLoading] = useState(false)

    const [countries, setCountries] = useState([]);
    const [twostepverification, setTwostepverification] = useState(false);
    const [bankDetails, setBankDetails] = useState({
        payment_type: "",
        full_name: "",
        bank_name: "",
        bank_no: null,
        branch_no: null,
        account_no: null
    })

    const [errors, setErrors] = useState([]);
    const alert = useAlert();
    const { t } = useTranslation();

    const handleChange = (e) => {
        const { name, value } = e.target;
        setBankDetails((prevDetails) => ({
            ...prevDetails,
            [name]: value
        }));
    };


    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };

    const handleUpdate = (e) => {
        setLoading(true)
        location.reload();
        const data = {
            firstname: firstname,
            lastname: lastname,
            phone: phone,
            address: address,
            renewal_visa: renewal_date,
            gender: gender,
            // payment_hour: payment_hour,
            lng: lng != 0 ? lng : "heb",
            worker_id: worker_id,
            password: password,
            // status: itemStatus,
            country: country,
            email: email,
            twostepverification: twostepverification,
            payment_type: bankDetails.payment_type,
            bank_name: bankDetails.bank_name,
            full_name: bankDetails.full_name,
            bank_number: bankDetails.bank_no,
            branch_number: bankDetails.branch_no,
            account_number: bankDetails.account_no,
        };

        elementsRef.current.map(
            (ref) => (data[ref.current.name] = ref.current.checked)
        );
        axios.post(`/api/profile`, data, { headers }).then((response) => {
            if (response.data.errors) {
                setLoading(false)
                setErrors(response.data.errors);
            } else {
                setLoading(false)
                setErrors([])
                localStorage.setItem("worker-lng", lng)
                alert.success(t("worker.settings.profileUpdated"));
            }
        });
    };

    const getWorker = () => {
        axios.get(`/api/details`, { headers }).then((response) => {
            let w = response.data.success;

            setFirstName(w.firstname);
            setLastName(w.lastname);
            setEmail(w.email);
            setPhone(w.phone);
            setRenewalDate(w.renewal_visa);
            setGender(w.gender);
            setWorkerId(w.worker_id);
            setPassword(w.passcode);
            setAddress(w.address);
            setLng(w.lng);
            setCountry(w.country);
            setBankDetails({
                payment_type: w.payment_type,
                full_name: w.full_name,
                bank_name: w.bank_name,
                account_no: w.account_number,
                branch_no: w.branch_number,
                bank_no: w.bank_number
            })
            setTwostepverification(w.two_factor_enabled === 1);
            elementsRef.current.map(
                (ref) =>
                (ref.current.checked =
                    ref.current.name === animalArray[0].key
                        ? w.is_afraid_by_dog
                        : w.is_afraid_by_cat)
            );
        });
    };

    const getCountries = () => {
        axios.get(`/api/admin/countries`, { headers }).then((response) => {
            setCountries(response.data.countries);
        });
    };

    useEffect(() => {
        getWorker();
        getCountries();
    }, []);

    return (
        <>
            <div className="edit-customer worker-account">
                <div className="dashBox p-0 p-md-4">
                    <form>
                        <div className="row">
                            <div className="col-sm-6">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("worker.settings.f_name")} *
                                    </label>
                                    <input
                                        readOnly
                                        type="text"
                                        value={firstname}
                                        onChange={(e) =>
                                            setFirstName(e.target.value)
                                        }
                                        className="form-control"
                                        required
                                        placeholder={t(
                                            "worker.settings.f_name"
                                        )}
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
                                        readOnly
                                        type="text"
                                        value={lastname}
                                        onChange={(e) =>
                                            setLastName(e.target.value)
                                        }
                                        className="form-control"
                                        placeholder={t(
                                            "worker.settings.l_name"
                                        )}
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
                                        value={email}
                                        onChange={(e) =>
                                            setEmail(e.target.value)
                                        }
                                        readOnly
                                        className="form-control"
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
                                    <label className="control-label">
                                        {t("worker.settings.phone")}
                                    </label>
                                    <StyledPhoneInput
                                        country={'il'}
                                        value={phone}
                                        onChange={(phone) => {
                                            setPhone(phone);
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
                                <div className="form-group gender-group">
                                    <label className="control-label d-block">
                                        {t("worker.settings.gender")}
                                    </label>
                                    <div className="form-check-inline">
                                        <label className="form-check-label">
                                            <input
                                                readOnly
                                                type="radio"
                                                className="form-check-input"
                                                value="male"
                                                style={{ height: "unset" }}
                                                onChange={(e) =>
                                                    setGender(e.target.value)
                                                }
                                                checked={gender === "male"}
                                                disabled
                                            />
                                            {t("worker.settings.male")}
                                        </label>
                                    </div>
                                    <div className="form-check-inline">
                                        <label className="form-check-label">
                                            <input
                                                readOnly
                                                type="radio"
                                                className="form-check-input"
                                                value="female"
                                                style={{ height: "unset" }}
                                                onChange={(e) =>
                                                    setGender(e.target.value)
                                                }
                                                checked={gender === "female"}
                                                disabled
                                            />
                                            {t("worker.settings.female")}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div className="col-sm-6">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("worker.settings.w_id")}
                                    </label>
                                    <input
                                        readOnly
                                        type="text"
                                        value={worker_id}
                                        onChange={(e) =>
                                            setWorkerId(e.target.value)
                                        }
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
                                        type="text"
                                        value={password}
                                        onChange={(e) =>
                                            setPassword(e.target.value)
                                        }
                                        className="form-control"
                                        required
                                        placeholder={t("worker.settings.pass")}
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
                                        onChange={(e) => setLng(e.target.value)}
                                    >
                                        <option value="">
                                            {t("worker.settings.selectLang")}
                                        </option>
                                        <option value="heb">
                                            {t("worker.settings.Hebrew")}
                                        </option>
                                        <option value="en">
                                            {t("worker.settings.English")}
                                        </option>
                                        <option value="ru">
                                            Russian
                                        </option>
                                        {/* <option value="spa">
                                            Spanish
                                        </option> */}
                                    </select>
                                </div>
                            </div>
                            <div className="col-sm-6">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("worker.settings.country")}
                                    </label>

                                    <select
                                        disabled
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
                            {country != "Israel" && (
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("worker.settings.renewal_visa")}{" "}
                                        </label>
                                        <input
                                            type="date"
                                            value={renewal_date}
                                            onChange={(e) =>
                                                setRenewalDate(e.target.value)
                                            }
                                            className="form-control"
                                            placeholder={t(
                                                "worker.settings.email"
                                            )}
                                        />
                                    </div>
                                </div>
                            )}

                            <div className="col-sm-6">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("worker.settings.paymentMethod")}
                                    </label>

                                    <select
                                        className="form-control"
                                        name="payment_type"
                                        value={bankDetails.payment_type}
                                        onChange={handleChange}

                                    >
                                        <option value="">{t("worker.settings.please_Select")}</option>
                                        <option value="cheque">{t("worker.settings.cheque")}</option>
                                        <option value="money_transfer">{t("worker.settings.moneyTrasnfer")}</option>
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
                            {
                                bankDetails.payment_type === "money_transfer" && (

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
                                                    onChange={handleChange}
                                                    className="form-control"
                                                    placeholder={t("worker.settings.enterFullname")}
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
                                                    {t("worker.settings.bankName")}
                                                </label>
                                                <input
                                                    type="text"
                                                    value={bankDetails.bank_name}
                                                    name="bank_name"
                                                    onChange={handleChange}
                                                    className="form-control"
                                                    placeholder={t("worker.settings.enterBankname")}
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
                                                    {t("worker.settings.bankNumber")}
                                                </label>
                                                <input
                                                    type="text"
                                                    value={bankDetails.bank_no}
                                                    name="bank_no"
                                                    onChange={handleChange}
                                                    className="form-control"
                                                    placeholder={t("worker.settings.enterBanknumber")}
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
                                                    {t("worker.settings.branchNumber")}
                                                </label>
                                                <input
                                                    type="text"
                                                    value={bankDetails.branch_no}
                                                    name="branch_no"
                                                    onChange={handleChange}
                                                    className="form-control"
                                                    placeholder={t("worker.settings.enterBranchnumber")}
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
                                                    {t("worker.settings.accountNumber")}
                                                </label>
                                                <input
                                                    type="text"
                                                    value={bankDetails.account_no}
                                                    name="account_no"
                                                    onChange={handleChange}
                                                    className="form-control"
                                                    placeholder={t("worker.settings.enterAccountnumber")}
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

                        </div>
                        <div className="form-group">
                            <label className="control-label">
                                {t("worker.settings.address")}
                            </label>
                            <input
                                type="text"
                                value={address}
                                onChange={(e) => setAddress(e.target.value)}
                                className="form-control"
                                placeholder={t("worker.settings.address")}
                            />
                            {errors.address ? (
                                <small className="text-danger mb-1">
                                    {errors.address}
                                </small>
                            ) : (
                                ""
                            )}
                        </div>
                        <div className="form-group">
                            <label className="control-label">
                                {t("worker.settings.areYouAfraid")}
                            </label>
                            {animalArray.map((item, index) => (
                                <div className="" key={item.key}>
                                    <label className="">
                                        {item.name}
                                    </label>
                                    <input
                                        ref={elementsRef.current[index]}
                                        type="checkbox"
                                        className="mx-2"
                                        style={{ height: "auto" }}
                                        name={item.key}
                                        value={item.key}
                                    />
                                </div>
                            ))}
                        </div>

                        <div className="form-group d-flex align-items-center">
                            <div className="toggle-switch">
                                <div className="switch">
                                    <span className="mr-2">{t("worker.settings.verification")}</span>
                                    <input
                                        onChange={() => setTwostepverification(prev => !prev)}
                                        type="checkbox"
                                        id="switch"
                                        checked={twostepverification}
                                    />
                                    <label htmlFor="switch">
                                        <span className="slider round"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div className="form-group text-center mb-0">
                            <input
                                type="submit"
                                value={t("worker.settings.update")}
                                onClick={handleUpdate}
                                className="btn btn-primary saveBtn"
                            />
                        </div>
                    </form>
                </div>
                {loading && <FullPageLoader visible={loading} />}
            </div>
        </>
    );
}
