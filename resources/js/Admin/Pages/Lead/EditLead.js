import React, { useEffect, useState } from "react";
import { useAlert } from "react-alert";
import { useParams, useNavigate } from "react-router-dom";
import Sidebar from "../../Layouts/Sidebar";
import {
    GoogleMap,
    LoadScript,
    InfoWindow,
    Marker,
    Autocomplete,
} from "@react-google-maps/api";
import Geocode from "react-geocode";

export default function EditWorker() {
    const [firstname, setFirstName] = useState("");
    const [lastname, setLastName] = useState("");
    const [email, setEmail] = useState("");
    const [invoiceName, setInvoiceName] = useState("");
    const [phone, setPhone] = useState("");
    const [city, setCity] = useState("");
    const [streetNumber, setStreetNumber] = useState("");
    const [floor, setFloor] = useState("");
    const [Apt, setApt] = useState("");
    const [enterance, setEnterance] = useState("");
    const [zip, setZip] = useState("");
    const [dob, setDob] = useState("");
    const [passcode, setPassCode] = useState("");
    const [lng, setLng] = useState("");
    const [color, setColor] = useState("");
    const [status, setStatus] = useState("");
    const [errors, setErrors] = useState([]);
    const [paymentMethod, setPaymentMethod] = useState("");
    const [extra, setExtra] = useState([{ email: "", name: "", phone: "" }]);
    const alert = useAlert();
    const params = useParams();
    const navigate = useNavigate();

    const [libraries] = useState(["places", "geometry"]);
    const [latitude, setLatitude] = useState(-33.865143);
    const [longitude, setLongitude] = useState(151.2099);
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

    const handleUpdate = (e) => {
        e.preventDefault();

        {
            /*lead data */
        }
        var phoneClc = "";
        var phones = document.querySelectorAll(".pphone");
        phones.forEach((p, i) => {
            phoneClc += p.value + ",";
        });
        phoneClc = phoneClc.replace(/,\s*$/, "");
        const data = {
            firstname: firstname,
            lastname: lastname,
            invoicename: invoiceName,
            city: city,
            street_n_no: streetNumber,
            floor: floor,
            apt_no: Apt,
            entrence_code: enterance,
            zipcode: zip,
            lng: lng ? lng : "heb",
            dob: dob,
            passcode: passcode,
            geo_address: address,
            latitude: latitude,
            longitude: longitude,
            color: !color ? "#fff" : color,
            email: email,
            phone: phoneClc,
            password: passcode,
            payment_method: paymentMethod,
            extra: JSON.stringify(extra),
            status: !status ? 0 : parseInt(status),
        };

        axios
            .put(
                `/api/admin/leads/${params.id}`,
                { data: data, jobdata: {} },
                { headers }
            )
            .then((response) => {
                if (response.data.errors) {
                    setErrors(response.data.errors);
                } else {
                    alert.success("Lead has been updated successfully");
                    setTimeout(() => {
                        navigate("/admin/leads");
                    }, 1000);
                }
            });
    };

    const getLead = () => {
        axios
            .get(`/api/admin/leads/${params.id}/edit`, { headers })
            .then((response) => {
                const {
                    firstname,
                    lastname,
                    email,
                    phone,
                    passcode,
                    city,
                    floor,
                    apt_no,
                    dob,
                    entrence_code,
                    lng,
                    color,
                    invoicename,
                    street_n_no,
                    zipcode,
                    status,
                    geo_address,
                    payment_method,
                    extra,
                } = response.data.lead;
                setFirstName(firstname);
                setLastName(lastname);
                setEmail(email);
                setPhone(phone);
                setPassCode(passcode);
                setCity(city);
                setFloor(floor);
                setApt(apt_no);
                setDob(dob);
                setEnterance(entrence_code);
                setLng(lng);
                setColor(color);
                setInvoiceName(invoicename);
                setStreetNumber(street_n_no);
                setZip(zipcode);
                setStatus(status);
                setAddress(geo_address);
                setPaymentMethod(payment_method);
                extra != null
                    ? setExtra(JSON.parse(extra))
                    : setExtra([{ email: "", name: "", phone: "" }]);
                if (color) {
                    let clr = document.querySelectorAll(
                        'input[name="swatch_demo"]'
                    );
                    clr.forEach((e, i) => {
                        e.getAttribute("color") == color
                            ? (e.checked = true)
                            : "";
                    });
                }
            });
    };

    useEffect(() => {
        getLead();
    }, []);
    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="edit-customer">
                    <h1 className="page-title editEmployer">Edit Lead</h1>
                    <div className="dashBox p-4">
                        <form>
                            <div className="row">
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            First Name *
                                        </label>
                                        <input
                                            type="text"
                                            value={firstname}
                                            onChange={(e) =>
                                                setFirstName(e.target.value)
                                            }
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
                                            value={lastname}
                                            onChange={(e) =>
                                                setLastName(e.target.value)
                                            }
                                            className="form-control"
                                            required
                                            placeholder="Enter Last Name"
                                        />
                                    </div>
                                </div>
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            Invoice Name
                                        </label>
                                        <input
                                            type="text"
                                            value={invoiceName}
                                            onChange={(e) =>
                                                setInvoiceName(e.target.value)
                                            }
                                            className="form-control"
                                            required
                                            placeholder="Enter Last Name"
                                        />
                                    </div>
                                </div>

                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            Primary Email *
                                        </label>
                                        <input
                                            type="email"
                                            value={email}
                                            onChange={(e) =>
                                                setEmail(e.target.value)
                                            }
                                            className="form-control"
                                            required
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
                                            Password
                                        </label>
                                        <input
                                            type="password"
                                            onChange={(e) =>
                                                setPassCode(e.target.value)
                                            }
                                            className="form-control"
                                            required
                                            placeholder="Password"
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
                                            Primary Phone
                                        </label>
                                        <input
                                            type="tel"
                                            value={phone}
                                            name="phone"
                                            onChange={(e) =>
                                                setPhone(e.target.value)
                                            }
                                            className="form-control pphone"
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

                                {extra &&
                                    extra.map((ex, i) => {
                                        return (
                                            <React.Fragment key={i}>
                                                <div className="col-sm-4">
                                                    <div className="form-group">
                                                        <label className="control-label">
                                                            Alternate Email
                                                        </label>
                                                        <input
                                                            type="tel"
                                                            value={
                                                                ex.email || ""
                                                            }
                                                            name="email"
                                                            onChange={(e) =>
                                                                handleAlternate(
                                                                    i,
                                                                    e
                                                                )
                                                            }
                                                            className="form-control"
                                                            placeholder="email"
                                                        />
                                                    </div>
                                                </div>

                                                <div className="col-sm-4">
                                                    <div className="form-group">
                                                        <label className="control-label">
                                                            Person Name
                                                        </label>
                                                        <input
                                                            type="tel"
                                                            value={
                                                                ex.name || ""
                                                            }
                                                            name="name"
                                                            onChange={(e) =>
                                                                handleAlternate(
                                                                    i,
                                                                    e
                                                                )
                                                            }
                                                            className="form-control"
                                                            placeholder="person name"
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
                                                                ex.phone || ""
                                                            }
                                                            name="phone"
                                                            onChange={(e) =>
                                                                handleAlternate(
                                                                    i,
                                                                    e
                                                                )
                                                            }
                                                            className="form-control"
                                                            placeholder="Phone"
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
                                            placeholder="Search Your Address"
                                            className="form-control mt-1"
                                        />
                                    </Autocomplete>
                                </LoadScript>
                            </div>
                            <h4 className="mt-2 mb-3">Client Full Address</h4>

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
                                    placeholder="Full Address"
                                />
                            </div>

                            <div className="form-group">
                                <label className="control-label">Floor</label>
                                <input
                                    type="text"
                                    value={floor}
                                    onChange={(e) => setFloor(e.target.value)}
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
                                    placeholder="Enter Apt number"
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
                                    placeholder="Enter enterance"
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
                                    Zip Code
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
                                    placeholder="Enter dob"
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
                                    Payment Method
                                </label>

                                <select
                                    className="form-control"
                                    value={paymentMethod}
                                    onChange={(e) => {
                                        setPaymentMethod(e.target.value);
                                    }}
                                >
                                    <option
                                        value="cc"
                                        selected={paymentMethod == "cc"}
                                    >
                                        Credit Card
                                    </option>
                                    <option
                                        value="mt"
                                        selected={paymentMethod == "mt"}
                                    >
                                        Money Transfer
                                    </option>
                                    <option
                                        value="cheque"
                                        selected={paymentMethod == "cheque"}
                                    >
                                        By Cheque
                                    </option>
                                    <option
                                        value="cash"
                                        selected={paymentMethod == "cash"}
                                    >
                                        By Cash
                                    </option>
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
                                    <option value="heb" selected={lng == "heb"}>
                                        Hebrew
                                    </option>
                                    <option value="en" selected={lng == "en"}>
                                        English
                                    </option>
                                </select>
                            </div>
                            <div className="form-group lcs">
                                <div
                                    className="form-check form-check-inline1 pl-0"
                                    style={{ paddingLeft: "0" }}
                                >
                                    <label
                                        className="form-check-label"
                                        for="title"
                                    >
                                        Color
                                    </label>
                                </div>
                                <div className="swatch white">
                                    <input
                                        type="radio"
                                        name="swatch_demo"
                                        id="swatch_7"
                                        value="0"
                                        color="#fff"
                                        onChange={(e) => setColor("#fff")}
                                    />
                                    <label for="swatch_7">
                                        <i className="fa fa-check"></i>
                                    </label>
                                    <span>white</span>
                                </div>
                                <div className="swatch green">
                                    <input
                                        type="radio"
                                        name="swatch_demo"
                                        id="swatch_2"
                                        value="2"
                                        color="#28a745"
                                        onChange={(e) => setColor("#28a745")}
                                    />
                                    <label for="swatch_2">
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
                                        onChange={(e) => setColor("#007bff")}
                                    />
                                    <label for="swatch_3">
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
                                        onChange={(e) => setColor("#6f42c1")}
                                    />
                                    <label for="swatch_1">
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
                                        onChange={(e) => setColor("#dc3545")}
                                    />
                                    <label for="swatch_5">
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
                                        onChange={(e) => setColor("#fd7e14")}
                                    />
                                    <label for="swatch_4">
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
                                        onChange={(e) => setColor("#ffc107")}
                                    />
                                    <label for="swatch_6">
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
                                    onClick={handleUpdate}
                                    className="btn btn-pink saveBtn"
                                />
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
}
