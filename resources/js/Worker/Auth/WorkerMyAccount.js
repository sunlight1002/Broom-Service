import React, { createRef, useEffect, useRef, useState } from "react";
import { useAlert } from "react-alert";
import { useParams, useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";

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

    const [countries, setCountries] = useState([]);

    const [errors, setErrors] = useState([]);
    const alert = useAlert();
    const { t } = useTranslation();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };

    const handleUpdate = (e) => {
        e.preventDefault();

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
        };

        elementsRef.current.map(
            (ref) => (data[ref.current.name] = ref.current.checked)
        );
        axios.post(`/api/profile`, data, { headers }).then((response) => {
            if (response.data.errors) {
                setErrors(response.data.errors);
            } else {
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
                <div className="dashBox p-4">
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
                                    <input
                                        type="tel"
                                        value={phone}
                                        onChange={(e) =>
                                            setPhone(e.target.value)
                                        }
                                        className="form-control"
                                        placeholder={t("worker.settings.phone")}
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
                        </div>
                        {animalArray.map((item, index) => (
                            <div className="form-check" key={item.key}>
                                <label className="form-check-label">
                                    <input
                                        ref={elementsRef.current[index]}
                                        type="checkbox"
                                        className="form-check-input"
                                        name={item.key}
                                        value={item.key}
                                    />
                                    {item.name}
                                </label>
                            </div>
                        ))}
                        <div className="form-group text-center">
                            <input
                                type="submit"
                                value={t("worker.settings.update")}
                                onClick={handleUpdate}
                                className="btn btn-primary saveBtn"
                            />
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}
