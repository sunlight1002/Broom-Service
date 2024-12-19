import axios from "axios";
import i18next from "i18next";
import React, { useEffect, useRef, useState } from "react";
import { useAlert } from "react-alert";
import { Button, Modal } from "react-bootstrap";
import { useTranslation } from "react-i18next";
import { Link } from "react-router-dom";
import { Table, Tbody, Td, Th, Thead, Tr } from "react-super-responsive-table";
import Swal from "sweetalert2";
import Map from "../Admin/Components/Map/map";
import ClientSidebar from "../Client/Layouts/ClientSidebar";

const ClientPropertyAdress = () => {
    const [loading, setLoading] = useState(i18next.t("common.loading"));
    const [addresses, setAddresses] = useState([]);
    const [isModalOpen, setModalStatus] = useState(false);
    const [address, setAddress] = useState("");
    const [place, setPlace] = useState();
    const [latitude, setLatitude] = useState(32.109333);
    const [longitude, setLongitude] = useState(34.855499);
    const [libraries] = useState(["places", "geometry"]);
    const [currentAddress, setCurrentAddress] = useState(null); // Holds data for the selected address
    const alert = useAlert();
    const { t } = useTranslation();

    let isAdd = useRef(true);
    let fullAddress = useRef();
    let floor = useRef();
    let Apt = useRef();
    let enterance = useRef();
    let zip = useRef();
    let parking = useRef();
    let addressId = useRef();
    let lat = useRef();
    let long = useRef();
    let city = useRef();
    let prefer_type = useRef();
    let is_dog_avail = useRef();
    let is_cat_avail = useRef();
    let client_id = useRef();
    let addressName = useRef();
    let key = useRef();
    let lobby = useRef();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("client-token"),
    };

    const language = localStorage.getItem("client-lng");

    const getAddressess = async () => {
        try {
            const res = await axios.get(`/api/client/get-property-addressess`, { headers });
            setAddresses(res.data.total_address);
        } catch (error) {
            console.error(error);
        }
    };

    const onLoad = (autocomplete) => {
        setPlace(autocomplete);
    };
    const onPlaceChanged = () => {
        if (place) {
            const _place = place.getPlace();
            setAddress(_place.formatted_address);
            fullAddress.current.value = _place.formatted_address;
            addressName.current.value = _place.name;
            setLatitude(_place.geometry.location.lat());
            lat.current.value = _place.geometry.location.lat();
            setLongitude(_place.geometry.location.lng());
            long.current.value = _place.geometry.location.lng();
        }
    };
    useEffect(() => {
        if (place?.getPlace() && isModalOpen && isAdd.current) {
            const _place = place.getPlace();
            lat.current.value = _place.geometry.location.lat();
            long.current.value = _place.geometry.location.lng();
            city.current.value = _place.vicinity;

            const address_components = _place.address_components;
            $.each(address_components, function (index, component) {
                const types = component.types;
                if (types.includes("postal_code")) {
                    zip.current.value = component.long_name;
                }
            });
        }

        // Clear zip if no address
        if (!address && isModalOpen) {
            zip.current.value = "";
        }
    }, [place?.getPlace(), isModalOpen]);


    const handleAddAddress = () => {
        isAdd.current = true;
        resetForm();
        setCurrentAddress(null); // Clear current address
        setModalStatus(true);
    };

    // Function to open modal for Edit
    const handleEditAddress = (address) => {
        isAdd.current = false;
        setCurrentAddress(address); // Set current address for editing
        setModalStatus(true);

        // Populate form fields
        addressName.current.value = address.address_name || "";
        fullAddress.current.value = address.geo_address || "";
        floor.current.value = address.floor || "";
        Apt.current.value = address.apt_no || "";
        enterance.current.value = address.entrence_code || "";
        zip.current.value = address.zipcode || "";
        parking.current.value = address.parking || "";
        lobby.current.value = address.lobby || "";
        key.current.value = address.key || "";
        prefer_type.current.value = address.prefer_type || "default";
        is_dog_avail.current.checked = address.is_dog_avail || false;
        is_cat_avail.current.checked = address.is_cat_avail || false;
        lat.current.value = address.latitude || "";
        long.current.value = address.longitude || "";
        city.current.value = address.city || "";

        // Update React state for dependent UI
        setLatitude(address.latitude || 32.109333);
        setLongitude(address.longitude || 34.855499);
        setAddress(address.geo_address || "");
    };

    // Handle Save/Update
    const handleAddress = async (e) => {
        e.preventDefault();
        const addressData = {
            client_id: localStorage.getItem("client-id"),
            address_name: addressName.current.value,
            geo_address: fullAddress.current.value,
            floor: floor.current.value,
            apt_no: Apt.current.value,
            entrence_code: enterance.current.value,
            zipcode: zip.current.value,
            parking: parking.current.value,
            lobby: lobby.current.value,
            key: key.current.value,
            prefer_type: prefer_type.current.value,
            is_dog_avail: is_dog_avail.current.checked,
            is_cat_avail: is_cat_avail.current.checked,
            latitude: lat.current.value,
            longitude: long.current.value,
            city: city.current.value,
        };

        if (isAdd.current) {

            if (!addressData.geo_address) {
                alert.error("Please select address");
                return;
            }

            try {
                const response = await axios.post('/api/client/property-address', addressData, { headers });
                alert.success(response.data.message);
                setModalStatus(false); // Close modal
                getAddressess();
            } catch (error) {
                console.error("Error saving address:", error.response?.data || error.message);
            }

        } else {
            // Call API or function to update address
            addressData.id = currentAddress.id; // Include ID for updating

            try {
                const response = await axios.put(`/api/client/property-address/${currentAddress.id}`, addressData, { headers });
                alert.success(response.data.message);
                setModalStatus(false); // Close modal
                getAddressess();
            } catch (error) {
                console.error("Error saving address:", error.response?.data || error.message);
            }
        }

        resetForm();
        setModalStatus(false);
    };


    const removeAddress = async (id) => {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Delete Address!",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(
                        `/api/client/property-address/${id}`, { headers })
                    .then((response) => {
                        alert.success(response.data.message);
                        getAddressess();
                    });
            }
        });
    };


    const resetForm = () => {
        fullAddress.current && (fullAddress.current.value = "");
        addressName.current && (addressName.current.value = "");
        floor.current && (floor.current.value = "");
        Apt.current && (Apt.current.value = "");
        enterance.current && (enterance.current.value = "");
        zip.current && (zip.current.value = "");
        parking.current && (parking.current.value = "");
        key.current && (key.current.value = "");
        lobby.current && (lobby.current.value = "");
        prefer_type.current && (prefer_type.current.value = "default");
        is_cat_avail.current && (is_cat_avail.current.checked = false);
        is_dog_avail.current && (is_dog_avail.current.checked = false);
        client_id.current && (client_id.current.value = 0);
        lat.current && (lat.current.value = 32.109333);
        long.current && (long.current.value = 34.855499);
        setAddress("");
        setLatitude(32.109333);
        setLongitude(34.855499);
    };

    useEffect(() => {
        getAddressess();
    }, []);

    return (
        <div id="container">
            <ClientSidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">
                                {t("client.property_address.title")}
                            </h1>
                        </div>
                        <div className="col-sm-6 d-flex justify-content-end align-items-center">
                            <button
                                type="button"
                                className="btn navyblue"
                                onClick={() => handleAddAddress()}
                            >
                                + {t("admin.leads.AddLead.addAddress.Add")}
                            </button>
                        </div>
                    </div>
                </div>
                <div className="card" style={{ boxShadow: "none" }}>
                    <div className="card-body px-0">
                        <div className="boxPanel">
                            <div className="table-responsive">
                                <Table className="table table-bordered responsiveTable">
                                    <Thead>
                                        <Tr>
                                            <Th>{t("client.property_address.address_name")}</Th>
                                            <Th>{t("client.property_address.geo_address")}</Th>
                                            <Th>{t("client.property_address.city")}</Th>
                                            <Th>{t("client.property_address.floor")}</Th>
                                            <Th>{t("client.property_address.zipcode")}</Th>
                                            <Th>{t("client.property_address.actions")}</Th>
                                        </Tr>
                                    </Thead>
                                    <Tbody>
                                        {addresses.length > 0 ? (
                                            addresses.map((address, index) => (
                                                <Tr key={index}>
                                                    <Td>{address.address_name || "-"}</Td>
                                                    <Td><a href={`https://maps.google.com?q=${address.geo_address}`} target="_blank" style={{ "color": "black", "textDecoration": "underline" }}>{address.geo_address || "-"}</a></Td>
                                                    <Td>{address.city || "-"}</Td>
                                                    <Td>{address.floor || "-"}</Td>
                                                    <Td>{address.zipcode || "-"}</Td>
                                                    <Td>
                                                        <div className="action-dropdown dropdown">
                                                            <button
                                                                type="button"
                                                                className="btn btn-default dropdown-toggle"
                                                                data-toggle="dropdown"
                                                            >
                                                                <i className="fa fa-ellipsis-vertical"></i>
                                                            </button>
                                                            <div className="dropdown-menu">
                                                                <button
                                                                    type="button"
                                                                    className="dropdown-item"
                                                                    onClick={() => handleEditAddress(address)}
                                                                >
                                                                    {t("client.property_address.edit")}
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    className="dropdown-item"
                                                                    onClick={() => removeAddress(address.id)}
                                                                >
                                                                    {t("client.property_address.delete")}
                                                                </button>
                                                            </div>
                                                        </div>

                                                    </Td>
                                                </Tr>
                                            ))
                                        ) : (
                                            <Tr>
                                                <Td colSpan="6" className="text-center">
                                                    {t("client.common.no_data_found")}
                                                </Td>
                                            </Tr>
                                        )}
                                    </Tbody>
                                </Table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {isModalOpen && (
                <div>
                    <Modal
                        size="lg"
                        className="modal-container"
                        dialogClassName="custom-modal-dialog" // Apply your custom class here
                        show={isModalOpen}
                        onHide={() => {
                            isAdd.current = true;
                            resetForm();
                            setModalStatus(false);
                        }}
                    >
                        <Modal.Header closeButton
                            className="border-0"
                            style={{ padding: "1rem 2rem" }}
                        >
                            <Modal.Title>
                                <div className="navyblueColor">
                                    {isAdd.current
                                        ? t(
                                            "admin.leads.AddLead.addAddress.AddPropertyAddress"
                                        )
                                        : t(
                                            "admin.leads.AddLead.addAddress.EditPropertyAddress"
                                        )}
                                </div>
                            </Modal.Title>
                        </Modal.Header>

                        <Modal.Body
                            className="border-0"
                            style={{ padding: "1rem 2rem" }}
                        >
                            <div className="row">
                                <div className="w-100 mr-3 ml-3">
                                    <Map
                                        onLoad={onLoad}
                                        onPlaceChanged={onPlaceChanged}
                                        latitude={latitude}
                                        longitude={longitude}
                                        address={address}
                                        setLatitude={setLatitude}
                                        setLongitude={setLongitude}
                                        libraries={libraries}
                                        place={place}
                                        language={language}
                                    />
                                </div>
                            </div>
                            <div className="row">
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label navyblueColor">
                                            {t(
                                                "admin.leads.AddLead.addAddress.FullAddress"
                                            )}
                                            <small className="text-pink mb-1">
                                                &nbsp; (
                                                {t(
                                                    "admin.leads.AddLead.addAddress.autocomplete"
                                                )}
                                                )
                                            </small>
                                        </label>
                                        <input
                                            ref={fullAddress}
                                            type="text"
                                            defaultValue={currentAddress?.geo_address || ""}
                                            className="form-control"
                                            placeholder={t("admin.leads.AddLead.addAddress.placeHolder.fullAddress")}
                                        />
                                    </div>
                                </div>
                            </div>
                            <div className=" d-flex property-modal">
                                <div className="d-flex flex-column ">
                                    <div className="form-group d-flex align-items-center">
                                        <label className="control-label mb-0 navyblueColor" style={{ width: "15rem", fontWeight: "500", fontSize: "14px" }}>
                                            {t(
                                                "admin.leads.AddLead.addAddress.Name"
                                            )}
                                        </label>
                                        <input
                                            name="address_name"
                                            ref={addressName}
                                            type="text"
                                            defaultValue={currentAddress?.address_name || ""}
                                            className="form-control skyBorder"
                                            placeholder={t(
                                                "admin.leads.AddLead.addAddress.placeHolder.addressName"
                                            )}
                                        />
                                    </div>
                                    <div className="form-group d-flex align-items-center">
                                        <label className="control-label navyblueColor" style={{ width: "15rem", fontWeight: "500", fontSize: "14px" }}>
                                            {t(
                                                "admin.leads.AddLead.addAddress.Floor"
                                            )}
                                        </label>
                                        <input
                                            type="text"
                                            ref={floor}
                                            defaultValue={currentAddress?.floor || ""}
                                            className="form-control skyBorder"
                                            placeholder={t(
                                                "admin.leads.AddLead.addAddress.placeHolder.floor"
                                            )}
                                        />
                                    </div>
                                    <div className="form-group d-flex align-items-center">
                                        <label className="control-label navyblueColor" style={{ width: "15rem", fontWeight: "500", fontSize: "14px" }}>
                                            {t(
                                                "admin.leads.AddLead.addAddress.AptNumberAndAptName"
                                            )}
                                        </label>
                                        <input
                                            type="text"
                                            ref={Apt}
                                            defaultValue={currentAddress?.apt_no || ""}
                                            className="form-control skyBorder"
                                            placeholder={t(
                                                "admin.leads.AddLead.addAddress.placeHolder.AptNumberAndAptName"
                                            )}
                                        />
                                    </div>
                                    <div className="form-group d-flex align-items-center">
                                        <label className="control-label navyblueColor" style={{ width: "15rem", fontWeight: "500", fontSize: "14px" }}>
                                            {t(
                                                "admin.leads.AddLead.addAddress.EnteranceCode"
                                            )}
                                        </label>
                                        <input
                                            type="text"
                                            ref={enterance}
                                            defaultValue={currentAddress?.entrence_code || ""}
                                            className="form-control skyBorder"
                                            placeholder={t(
                                                "admin.leads.AddLead.addAddress.placeHolder.EnteranceCode"
                                            )}
                                        />
                                    </div>
                                    <div className="form-group d-flex align-items-center">
                                        <label className="control-label navyblueColor" style={{ width: "15rem", fontWeight: "500", fontSize: "14px" }}>
                                            {t(
                                                "admin.leads.AddLead.addAddress.ZipCode"
                                            )}
                                        </label>
                                        <input
                                            type="text"
                                            ref={zip}
                                            defaultValue={currentAddress?.zipcode || ""}
                                            className="form-control skyBorder"
                                            placeholder={t(
                                                "admin.leads.AddLead.addAddress.placeHolder.ZipCode"
                                            )}
                                        />
                                    </div>
                                    <div className="form-group d-flex align-items-center">
                                        <label className="control-label" style={{ width: "15rem", fontWeight: "500", fontSize: "14px" }}>
                                            {t(
                                                "admin.leads.AddLead.addAddress.parking"
                                            )}
                                        </label>
                                        <input
                                            type="text"
                                            ref={parking}
                                            defaultValue={currentAddress?.parking || ""}
                                            className="form-control skyBorder"
                                            placeholder={t(
                                                "admin.leads.AddLead.addAddress.placeHolder.parking"
                                            )}
                                        />
                                    </div>
                                </div>
                                <div className="d-flex flex-column ml-0 ml-md-3">
                                    <div className="form-group d-flex align-items-center">
                                        <label className="control-label navyblueColor" style={{ width: "15rem", fontWeight: "500", fontSize: "14px" }}>
                                            {t(
                                                "admin.leads.AddLead.addAddress.Lobby"
                                            )}
                                        </label>
                                        <input
                                            type="text"
                                            ref={lobby}
                                            defaultValue={currentAddress?.lobby || ""}
                                            className="form-control skyBorder"
                                            placeholder={t(
                                                "admin.leads.AddLead.addAddress.placeHolder.Lobby"
                                            )}
                                        />
                                    </div>
                                    <div className="form-group d-flex align-items-center">
                                        <label className="control-label navyblueColor" style={{ width: "15rem", fontWeight: "500", fontSize: "14px" }}>
                                            {t(
                                                "admin.leads.AddLead.addAddress.Key"
                                            )}
                                        </label>
                                        <input
                                            type="text"
                                            ref={key}
                                            defaultValue={currentAddress?.key || ""}
                                            className="form-control skyBorder"
                                            placeholder={t(
                                                "admin.leads.AddLead.addAddress.placeHolder.Key"
                                            )}
                                        />
                                    </div>
                                    <div className="form-group d-flex align-items-center">
                                        <label className="control-label navyblueColor" style={{ width: "15rem", fontWeight: "500", fontSize: "14px" }}>
                                            {t(
                                                "admin.leads.AddLead.addAddress.PreferedType"
                                            )}
                                        </label>
                                        <select
                                            ref={prefer_type}

                                            className="form-control skyBorder"
                                            name="prefer_type"
                                            defaultValue={currentAddress?.prefer_type || "default"}
                                        >
                                            <option value="default">
                                                {t(
                                                    "admin.leads.AddLead.addAddress.Options.PreferedType.Default"
                                                )}
                                            </option>
                                            <option value="female">
                                                {t(
                                                    "admin.leads.AddLead.addAddress.Options.PreferedType.Female"
                                                )}
                                            </option>
                                            <option value="male">
                                                {" "}
                                                {t(
                                                    "admin.leads.AddLead.addAddress.Options.PreferedType.Male"
                                                )}
                                            </option>
                                            <option value="both">
                                                {" "}
                                                {t(
                                                    "admin.leads.AddLead.addAddress.Options.PreferedType.Both"
                                                )}
                                            </option>
                                        </select>
                                    </div>
                                    <div className="form-group d-flex align-items-center">
                                        <div className="form-check form-switch pl-0">
                                            <label
                                                className="form-check-label custom-checkbox navyblueColor"
                                                htmlFor="isDogAvail"
                                                style={{ fontWeight: "500", fontSize: "14px" }}
                                            >
                                                <input
                                                    ref={is_dog_avail}
                                                    className="form-check-input"
                                                    type="checkbox"
                                                    id="isDogAvail"
                                                    name="is_dog_avail"
                                                    defaultChecked={currentAddress?.is_dog_avail != 0 && currentAddress?.is_dog_avail != null ? true : false}
                                                />
                                                <span className="checkmark"></span>

                                                {t(
                                                    "admin.leads.AddLead.addAddress.IsDOG"
                                                )}
                                            </label>
                                        </div>
                                    </div>
                                    <div className="form-group d-flex align-items-center">
                                        <div className="form-check form-switch pl-0 ">
                                            <label
                                                className="form-check-label custom-checkbox navyblueColor"
                                                htmlFor="isCatAvail"
                                                style={{ fontWeight: "500", fontSize: "14px" }}
                                            >
                                                <input
                                                    ref={is_cat_avail}
                                                    className="form-check-input  skyBorder"
                                                    type="checkbox"
                                                    id="isCatAvail"
                                                    name="is_cat_avail"
                                                    defaultChecked={currentAddress?.is_cat_avail != 0 && currentAddress?.is_cat_avail != null ? true : false}
                                                />
                                                <span className="checkmark"></span>

                                                {t(
                                                    "admin.leads.AddLead.addAddress.IsCat"
                                                )}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input
                                type="hidden"
                                ref={addressId}
                                name="addressId"
                            />
                            <input type="hidden" ref={lat} name="lat" />
                            <input type="hidden" ref={long} name="long" />
                            <input type="hidden" ref={city} name="city" />
                        </Modal.Body>
                        <Modal.Footer
                            className="border-0"
                            style={{ padding: "1rem 2rem" }}
                        >
                            <div className="bg-transparent">
                                <Button
                                    type="button"
                                    className="navyblue"
                                    onClick={() => {
                                        isAdd.current = true;
                                        resetForm();
                                        setModalStatus(false);
                                    }}
                                >
                                    {t("admin.leads.AddLead.addAddress.Close")}
                                </Button>
                            </div>
                            <div>
                                <Button
                                    type="button"
                                    onClick={(e) => handleAddress(e)}
                                    className="navyblue"
                                >
                                    {t("admin.leads.AddLead.addAddress.Save")}
                                </Button>
                            </div>
                        </Modal.Footer>
                    </Modal>
                </div>
            )}
        </div>
    );
};

export default ClientPropertyAdress;