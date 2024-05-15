import React, { useState, useEffect, useRef, createRef } from "react";
import { useAlert } from "react-alert";
import { useNavigate } from "react-router-dom";
import {
    GoogleMap,
    LoadScript,
    InfoWindow,
    Marker,
    Autocomplete,
} from "@react-google-maps/api";
import Geocode from "react-geocode";
import Swal from "sweetalert2";

import Sidebar from "../../Layouts/Sidebar";

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

export default function AddWorker() {
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
    const [isLoading, setIsLoading] = useState(false);
    const [password, setPassword] = useState("");
    const [lng, setLng] = useState("");
    const [address, setAddress] = useState("");
    const [skill, setSkill] = useState([]);
    const [itemStatus, setItemStatus] = useState("");
    const [country, setCountry] = useState("Israel");

    const [avl_skill, setAvlSkill] = useState([]);
    const [countries, setCountries] = useState([]);

    const [errors, setErrors] = useState([]);
    const [manpowerCompanies, setManpowerCompanies] = useState([]);

    const alert = useAlert();
    const navigate = useNavigate();
    const [libraries] = useState(["places", "geometry"]);
    const [latitude, setLatitude] = useState(-33.865143);
    const [longitude, setLongitude] = useState(151.2099);
    const [place, setPlace] = useState();
    Geocode.setApiKey("AIzaSyBva3Ymax7XLY17ytw_rqRHggZmqegMBuM");
    const containerStyle = {
        width: "100%",
        height: "300px",
    };
    const center = {
        lat: latitude,
        lng: longitude,
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
    const handleSubmit = (e) => {
        e.preventDefault();
        const data = {
            ...formValues,
            address: address,
            renewal_visa: formValues.renewal_date,
            lng: !lng ? "en" : lng,
            password: password,
            skill: skill,
            status: !itemStatus ? 1 : parseInt(itemStatus),
            country: country,
            latitude: latitude,
            longitude: longitude,
        };
        elementsRef.current.map(
            (ref) => (data[ref.current.name] = ref.current.checked)
        );

        setIsLoading(true);

        axios
            .post(`/api/admin/workers`, data, { headers })
            .then((response) => {
                if (response.data.errors) {
                    setErrors(response.data.errors);
                    setIsLoading(false);
                } else {
                    alert.success("Worker has been created successfully");
                    setTimeout(() => {
                        navigate("/admin/workers");
                    }, 1000);
                }
            })
            .catch((e) => {
                setIsLoading(false);

                Swal.fire({
                    title: "Error!",
                    text: e.response.data.message,
                    icon: "error",
                });
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
        getAvailableSkill();
        getCountries();
        getManpowerCompanies();
    }, []);
    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="edit-customer">
                    <h1 className="page-title addEmployer">Add Worker</h1>
                    <div className="card">
                        <div className="card-body">
                            <form>
                                <div className="row">
                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                First Name *
                                            </label>
                                            <input
                                                type="text"
                                                value={formValues.firstname}
                                                onChange={(e) => {
                                                    setFormValues({
                                                        ...formValues,
                                                        firstname:
                                                            e.target.value,
                                                    });
                                                }}
                                                className="form-control"
                                                required
                                                placeholder="Enter First Name"
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
                                                Last Name
                                            </label>
                                            <input
                                                type="text"
                                                value={formValues.lastname}
                                                onChange={(e) => {
                                                    setFormValues({
                                                        ...formValues,
                                                        lastname:
                                                            e.target.value,
                                                    });
                                                }}
                                                className="form-control"
                                                placeholder="Enter Last Name"
                                            />
                                        </div>
                                    </div>
                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                Email
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
                                                placeholder="Email"
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
                                                Phone
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
                                                className="form-control"
                                                placeholder="Phone"
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
                                                Gender
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
                                                Male
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
                                                Female
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
                                                Role
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
                                                placeholder="Role"
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
                                                Payment Per Hour (ILS)
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
                                                placeholder="Payment Per Hour"
                                            />
                                        </div>
                                    </div>
                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                Worker Id
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
                                                placeholder="Payment Per Hour"
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
                                                Password *
                                            </label>
                                            <input
                                                type="password"
                                                value={password}
                                                onChange={(e) =>
                                                    setPassword(e.target.value)
                                                }
                                                className="form-control"
                                                required
                                                placeholder="Password"
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
                                    </div>
                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                Language
                                            </label>

                                            <select
                                                className="form-control"
                                                value={lng}
                                                onChange={(e) =>
                                                    setLng(e.target.value)
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
                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                Country
                                            </label>

                                            <select
                                                className="form-control"
                                                value={country}
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
                                    </div>
                                    {country != "Israel" && (
                                        <div className="col-sm-6">
                                            <div className="form-group">
                                                <label className="control-label">
                                                    Renewal of visa
                                                </label>
                                                <input
                                                    type="date"
                                                    onChange={(e) => {
                                                        setFormValues({
                                                            ...formValues,
                                                            renewal_date:
                                                                e.target.value,
                                                        });
                                                    }}
                                                    className="form-control"
                                                    placeholder="Email"
                                                />
                                            </div>
                                        </div>
                                    )}

                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                Company
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
                                                My Company
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
                                                Manpower
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
                                                    Manpower
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
                                                        --Select Manpower---
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
                                </div>

                                <div className="form-group">
                                    <label className="control-label">
                                        Enter a location
                                    </label>
                                    <LoadScript
                                        googleMapsApiKey="AIzaSyBva3Ymax7XLY17ytw_rqRHggZmqegMBuM"
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
                                                placeholder="Search Your Address"
                                                className="form-control mt-1"
                                            />
                                        </Autocomplete>
                                    </LoadScript>
                                </div>
                                <div className="form-group">
                                    <label className="control-label">
                                        Full Address
                                        <small className="text-pink mb-1">
                                            &nbsp; (auto complete from google
                                            address)
                                        </small>
                                    </label>
                                    <input
                                        type="text"
                                        value={address}
                                        className="form-control"
                                        placeholder="Enter your address"
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
                                            Skills
                                        </label>
                                    </div>
                                    <div className="form-check mb-3">
                                        <label className="form-check-label">
                                            <input
                                                type="checkbox"
                                                className="form-check-input"
                                                onChange={handleAllSkills}
                                            />
                                            <strong>Select All</strong>
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
                                            Are you afraid of any follwing pet
                                            animals ?
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
                                <div className="form-group mt-4">
                                    <label className="control-label">
                                        Status
                                    </label>
                                    <select
                                        className="form-control"
                                        value={itemStatus}
                                        onChange={(e) =>
                                            setItemStatus(e.target.value)
                                        }
                                    >
                                        <option value="1">Enable</option>
                                        <option value="0">Disable</option>
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
                                    <button
                                        type="submit"
                                        onClick={handleSubmit}
                                        className="btn btn-pink"
                                        disabled={isLoading}
                                    >
                                        Submit
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
