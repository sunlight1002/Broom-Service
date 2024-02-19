import React, { useState, useEffect } from "react";
import { useAlert } from "react-alert";
import { useNavigate } from "react-router-dom";
import Sidebar from "../../Layouts/Sidebar";
import {
    GoogleMap,
    LoadScript,
    InfoWindow,
    Marker,
    Autocomplete,
} from "@react-google-maps/api";
import Geocode from "react-geocode";
import axios from "axios";
import { MultiSelect } from "react-multi-select-component";
import Select from "react-select";
import { create } from "lodash";

export default function AddLead() {
    const [firstname, setFirstName] = useState("");
    const [lastname, setLastName] = useState("");
    const [email, setEmail] = useState("");
    const [invoiceName, setInvoiceName] = useState("");
    const [phone, setPhone] = useState("");
    const [floor, setFloor] = useState("");
    const [Apt, setApt] = useState("");
    const [enterance, setEnterance] = useState("");
    const [zip, setZip] = useState("");
    const [dob, setDob] = useState("");
    const [passcode, setPassCode] = useState("");
    const [lng, setLng] = useState("");
    const [color, setColor] = useState("");
    const [status, setStatus] = useState(0);
    const [errors, setErrors] = useState([]);
    const [city, setCity] = useState("");
    const alert = useAlert();
    const [extra, setExtra] = useState([{ email: "", name: "", phone: "" }]);
    const [paymentMethod, setPaymentMethod] = useState("cc");
    const navigate = useNavigate();

    const [libraries] = useState(["places", "geometry"]);
    const [latitude, setLatitude] = useState(32.109333);
    const [longitude, setLongitude] = useState(34.855499);
    const [address, setAddress] = useState("");
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
            setCity(place.getPlace().vicinity);
            setAddress(place.getPlace().formatted_address);
            setLatitude(place.getPlace().geometry.location.lat());
            setLongitude(place.getPlace().geometry.location.lng());
        }
    };

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleFormSubmit = (e) => {
        e.preventDefault();
        var phoneClc = "";
        var phones = document.querySelectorAll(".pphone");
        phones.forEach((p, i) => {
            phoneClc += p.value + ",";
        });
        phoneClc = phoneClc.replace(/,\s*$/, "");

        axios
            .post(
                `/api/admin/leads`,
                {
                    firstname: firstname,
                    lastname: lastname == null ? "" : lastname,
                    invoicename: invoiceName ? invoiceName : "",
                    floor: floor,
                    apt_no: Apt,
                    entrence_code: enterance,
                    city: city,
                    zipcode: zip,
                    dob: dob,
                    passcode: passcode,
                    lng: lng ? lng : "heb",
                    color: !color ? "#fff" : color,
                    geo_address: address,
                    latitude: latitude,
                    longitude: longitude,
                    email: email,
                    phone: phoneClc,
                    password: passcode,
                    payment_method: paymentMethod,
                    extra: JSON.stringify(extra),
                    status: !status ? 0 : parseInt(status),
                    meta: "",
                },
                { headers }
            )
            .then((response) => {
                if (response.data.errors) {
                    setErrors(response.data.errors);
                } else {
                    alert.success("Lead has been created successfully");
                    setTimeout(() => {
                        navigate("/admin/leads");
                    }, 1000);
                }
            });
    };

    const handleAlternate = (i, e) => {
        let extraValues = [...extra];
        extraValues[i][e.target.name] = e.target.value;
        setExtra(extraValues);
    };

    let addExtras = (e) => {
        e.preventDefault();
        setExtra([
            ...extra,
            {
                email: "",
                name: "",
                phone: "",
            },
        ]);
    };

    let removeExtras = (e, i) => {
        e.preventDefault();
        let extraValues = [...extra];
        extraValues.splice(i, 1);
        setExtra(extraValues);
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="edit-customer">
                    <h1 className="page-title addEmployer">Add Lead</h1>
                    <div className="card">
                        <div className="card-body">
                            <form>
                                <div className="row">
                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                First name *
                                            </label>
                                            <input
                                                type="text"
                                                value={firstname}
                                                onChange={(e) =>
                                                    setFirstName(e.target.value)
                                                }
                                                className="form-control"
                                                required
                                                placeholder="Enter first name"
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
                                                Last name
                                            </label>
                                            <input
                                                type="text"
                                                value={lastname}
                                                onChange={(e) =>
                                                    setLastName(e.target.value)
                                                }
                                                className="form-control"
                                                required
                                                placeholder="Enter last name"
                                            />
                                        </div>
                                    </div>
                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                Invoice name
                                            </label>
                                            <input
                                                type="text"
                                                value={invoiceName}
                                                onChange={(e) =>
                                                    setInvoiceName(
                                                        e.target.value
                                                    )
                                                }
                                                className="form-control"
                                                required
                                                placeholder="Enter invoice name"
                                            />
                                        </div>
                                    </div>
                                    <div className="col-sm-6">
                                        <div className="form-group">
                                            <label className="control-label">
                                                Primary email *
                                            </label>
                                            <input
                                                type="email"
                                                value={email}
                                                onChange={(e) =>
                                                    setEmail(e.target.value)
                                                }
                                                className="form-control"
                                                required
                                                placeholder="Enter primary email"
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
                                                Password
                                            </label>
                                            <input
                                                type="password"
                                                value={passcode}
                                                onChange={(e) =>
                                                    setPassCode(e.target.value)
                                                }
                                                className="form-control"
                                                required
                                                placeholder="Enter password"
                                                autoComplete="new-password"
                                            />
                                            {errors.passcode ? (
                                                <small className="text-danger mb-1">
                                                    {errors.passcode}
                                                </small>
                                            ) : (
                                                ""
                                            )}
                                        </div>
                                    </div>

                                    <div className="col-sm-6 phone">
                                        <div className="form-group">
                                            <label className="control-label">
                                                Primary phone
                                            </label>
                                            <input
                                                type="tel"
                                                value={phone}
                                                name={"phone"}
                                                onChange={(e) =>
                                                    setPhone(e.target.value)
                                                }
                                                className="form-control pphone"
                                                placeholder="Enter primary phone"
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

                                    {extra &&
                                        extra.map((ex, i) => {
                                            return (
                                                <React.Fragment key={i}>
                                                    <div className="col-sm-4">
                                                        <div className="form-group">
                                                            <label className="control-label">
                                                                Alternate email
                                                            </label>
                                                            <input
                                                                type="tel"
                                                                value={
                                                                    ex.email ||
                                                                    ""
                                                                }
                                                                name="email"
                                                                onChange={(e) =>
                                                                    handleAlternate(
                                                                        i,
                                                                        e
                                                                    )
                                                                }
                                                                className="form-control"
                                                                placeholder="Enter alternate email"
                                                            />
                                                        </div>
                                                    </div>

                                                    <div className="col-sm-4">
                                                        <div className="form-group">
                                                            <label className="control-label">
                                                                Person name
                                                            </label>
                                                            <input
                                                                type="tel"
                                                                value={
                                                                    ex.name ||
                                                                    ""
                                                                }
                                                                name="name"
                                                                onChange={(e) =>
                                                                    handleAlternate(
                                                                        i,
                                                                        e
                                                                    )
                                                                }
                                                                className="form-control"
                                                                placeholder="Enter person name"
                                                            />
                                                        </div>
                                                    </div>

                                                    <div className="col-sm-3">
                                                        <div className="form-group">
                                                            <label className="control-label">
                                                                Alternate phone
                                                            </label>
                                                            <input
                                                                type="tel"
                                                                value={
                                                                    ex.phone ||
                                                                    ""
                                                                }
                                                                name="phone"
                                                                onChange={(e) =>
                                                                    handleAlternate(
                                                                        i,
                                                                        e
                                                                    )
                                                                }
                                                                className="form-control"
                                                                placeholder="Enter alternate phone"
                                                            />
                                                        </div>
                                                    </div>
                                                    <div className="col-sm-1">
                                                        {i == 0 ? (
                                                            <>
                                                                <button
                                                                    className="mt-25 btn btn-success"
                                                                    onClick={(
                                                                        e
                                                                    ) => {
                                                                        addExtras(
                                                                            e
                                                                        );
                                                                    }}
                                                                >
                                                                    {" "}
                                                                    +{" "}
                                                                </button>
                                                            </>
                                                        ) : (
                                                            <>
                                                                <button
                                                                    className="mt-25 btn bg-red"
                                                                    onClick={(
                                                                        e
                                                                    ) => {
                                                                        removeExtras(
                                                                            e,
                                                                            i
                                                                        );
                                                                    }}
                                                                >
                                                                    {" "}
                                                                    <i className="fa fa-minus"></i>{" "}
                                                                </button>
                                                            </>
                                                        )}
                                                    </div>
                                                </React.Fragment>
                                            );
                                        })}
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
                                                placeholder="Search your address"
                                                className="form-control mt-1"
                                            />
                                        </Autocomplete>
                                    </LoadScript>
                                </div>

                                <h4 className="mt-2 mb-3">
                                    Client Full Address
                                </h4>

                                <div className="form-group">
                                    <label className="control-label">
                                        Full address
                                        <small className="text-pink mb-1">
                                            &nbsp; (auto complete from google
                                            address)
                                        </small>
                                    </label>
                                    <input
                                        type="text"
                                        value={address}
                                        className="form-control"
                                        placeholder="Full Address"
                                        readOnly
                                    />
                                </div>

                                <div className="form-group">
                                    <label className="control-label">
                                        Floor
                                    </label>
                                    <input
                                        type="text"
                                        value={floor}
                                        onChange={(e) =>
                                            setFloor(e.target.value)
                                        }
                                        className="form-control"
                                        placeholder="Enter floor"
                                    />
                                    {errors.floor ? (
                                        <small className="text-danger mb-1">
                                            {errors.floor}
                                        </small>
                                    ) : (
                                        ""
                                    )}
                                </div>

                                <div className="form-group">
                                    <label className="control-label">
                                        Apt number and Apt name
                                    </label>
                                    <input
                                        type="text"
                                        value={Apt}
                                        onChange={(e) => setApt(e.target.value)}
                                        className="form-control"
                                        placeholder="Enter Apt number and Apt name"
                                    />
                                    {errors.Apt ? (
                                        <small className="text-danger mb-1">
                                            {errors.Apt}
                                        </small>
                                    ) : (
                                        ""
                                    )}
                                </div>

                                <div className="form-group">
                                    <label className="control-label">
                                        Enterance code
                                    </label>
                                    <input
                                        type="text"
                                        value={enterance}
                                        onChange={(e) =>
                                            setEnterance(e.target.value)
                                        }
                                        className="form-control"
                                        placeholder="Enter enterance code"
                                    />
                                    {errors.enterance ? (
                                        <small className="text-danger mb-1">
                                            {errors.enterance}
                                        </small>
                                    ) : (
                                        ""
                                    )}
                                </div>

                                <div className="form-group">
                                    <label className="control-label">
                                        Zip code
                                    </label>
                                    <input
                                        type="text"
                                        value={zip}
                                        onChange={(e) => setZip(e.target.value)}
                                        className="form-control"
                                        placeholder="Enter zip code"
                                    />
                                    {errors.zip ? (
                                        <small className="text-danger mb-1">
                                            {errors.zip}
                                        </small>
                                    ) : (
                                        ""
                                    )}
                                </div>

                                <div className="form-group">
                                    <label className="control-label">
                                        Date of Birth
                                    </label>
                                    <input
                                        type="date"
                                        value={dob}
                                        onChange={(e) => setDob(e.target.value)}
                                        className="form-control"
                                    />
                                    {errors.dob ? (
                                        <small className="text-danger mb-1">
                                            {errors.dob}
                                        </small>
                                    ) : (
                                        ""
                                    )}
                                </div>

                                <div className="form-group">
                                    <label className="control-label">
                                        Payment method
                                    </label>

                                    <select
                                        className="form-control"
                                        value={paymentMethod}
                                        onChange={(e) => {
                                            setPaymentMethod(e.target.value);
                                        }}
                                    >
                                        <option value="cc">Credit Card</option>
                                        <option value="mt">
                                            Money Transfer
                                        </option>
                                        <option value="cheque">
                                            By Cheque
                                        </option>
                                        <option value="cash">By Cash</option>
                                    </select>
                                </div>

                                <div className="form-group">
                                    <label className="control-label">
                                        Language
                                    </label>

                                    <select
                                        className="form-control"
                                        value={lng}
                                        onChange={(e) => {
                                            setLng(e.target.value);
                                        }}
                                    >
                                        <option value="heb">Hebrew</option>
                                        <option value="en">English</option>
                                    </select>
                                </div>
                                <div className="form-group">
                                    <div
                                        className="form-check form-check-inline1 pl-0"
                                        style={{ paddingLeft: "0" }}
                                    >
                                        <label
                                            className="form-check-label"
                                            htmlFor="title"
                                        >
                                            Color
                                        </label>
                                    </div>
                                    <div className="swatch white">
                                        <input
                                            type="radio"
                                            name="swatch_demo"
                                            id="swatch_2"
                                            value="0"
                                            color="#fff"
                                            onChange={(e) => setColor("#fff")}
                                        />
                                        <label htmlFor="swatch_2">
                                            <i className="fa fa-check"></i>
                                        </label>
                                        <span>white</span>
                                    </div>
                                    <div className="swatch green">
                                        <input
                                            type="radio"
                                            name="swatch_demo"
                                            id="swatch_7"
                                            value="2"
                                            color="#28a745"
                                            onChange={(e) =>
                                                setColor("#28a745")
                                            }
                                        />
                                        <label htmlFor="swatch_7">
                                            <i className="fa fa-check"></i>
                                        </label>
                                        <span>Green</span>
                                    </div>
                                    <div className="swatch blue">
                                        <input
                                            type="radio"
                                            name="swatch_demo"
                                            id="swatch_3"
                                            value="3"
                                            color="#007bff"
                                            onChange={(e) =>
                                                setColor("#007bff")
                                            }
                                        />
                                        <label htmlFor="swatch_3">
                                            <i className="fa fa-check"></i>
                                        </label>
                                        <span>Blue</span>
                                    </div>
                                    <div className="swatch purple">
                                        <input
                                            type="radio"
                                            name="swatch_demo"
                                            id="swatch_1"
                                            value="1"
                                            color="#6f42c1"
                                            onChange={(e) =>
                                                setColor("#6f42c1")
                                            }
                                        />
                                        <label htmlFor="swatch_1">
                                            <i className="fa fa-check"></i>
                                        </label>
                                        <span>Voilet</span>
                                    </div>
                                    <div className="swatch red">
                                        <input
                                            type="radio"
                                            name="swatch_demo"
                                            id="swatch_5"
                                            value="5"
                                            color="#dc3545"
                                            onChange={(e) =>
                                                setColor("#dc3545")
                                            }
                                        />
                                        <label htmlFor="swatch_5">
                                            <i className="fa fa-check"></i>
                                        </label>
                                        <span>Red</span>
                                    </div>
                                    <div className="swatch orange">
                                        <input
                                            type="radio"
                                            name="swatch_demo"
                                            id="swatch_4"
                                            value="4"
                                            color="#fd7e14"
                                            onChange={(e) =>
                                                setColor("#fd7e14")
                                            }
                                        />
                                        <label htmlFor="swatch_4">
                                            <i className="fa fa-check"></i>
                                        </label>
                                        <span>Orange</span>
                                    </div>
                                    <div className="swatch yellow">
                                        <input
                                            type="radio"
                                            name="swatch_demo"
                                            id="swatch_6"
                                            value="6"
                                            color="#ffc107"
                                            onChange={(e) =>
                                                setColor("#ffc107")
                                            }
                                        />
                                        <label htmlFor="swatch_6">
                                            <i className="fa fa-check"></i>
                                        </label>
                                        <span>Yellow</span>
                                    </div>

                                    {errors.color ? (
                                        <small className="text-danger mb-1">
                                            {errors.color}
                                        </small>
                                    ) : (
                                        ""
                                    )}
                                </div>

                                <div className="form-group text-center">
                                    <input
                                        type="submit"
                                        onClick={handleFormSubmit}
                                        className="btn btn-pink saveBtn"
                                    />
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
