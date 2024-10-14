import { memo, useEffect, useRef, useState } from "react";
import { Button, Modal } from "react-bootstrap";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import { useParams } from "react-router-dom";
import { useAlert } from "react-alert";
import Select from "react-select";
import Swal from "sweetalert2";

import Map from "../Map/map";
import { useTranslation } from "react-i18next";

const addressMenu = [
    {
        key: "edit",
        label: "Edit",
    },
    {
        key: "delete",
        label: "Delete",
    },
];

const PropertyAddress = memo(function PropertyAddress({
    heading,
    errors,
    addresses,
    setAddresses,
    setErrors,
}) {
    const params = useParams();
    const { t } = useTranslation();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };
    const alert = useAlert();
    const [isModalOpen, setModalStatus] = useState(false);
    const [address, setAddress] = useState("");
    const [place, setPlace] = useState();
    const [latitude, setLatitude] = useState(32.109333);
    const [longitude, setLongitude] = useState(34.855499);
    const [libraries] = useState(["places", "geometry"]);
    const [allWorkers, setAllWorkers] = useState([]);
    const [workers, setWorkers] = useState([]);

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

    useEffect(() => {
        setTimeout(() => {
            if (address && isModalOpen && isAdd.current) {
                fullAddress.current && (fullAddress.current.value = address);
            }
            if ((address && isModalOpen) || (!isAdd.current && isModalOpen)) {
                let newErrors = { ...errors };
                newErrors.address = "";
                setErrors(newErrors);
            }
        }, 500);
    }, [isModalOpen, isAdd.current, address]);

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
                var types = component.types;
                $.each(types, function (index, type) {
                    if (type === "postal_code") {
                        zip.current.value = component.long_name;
                    }
                });
            });
        }
        if (!address && isModalOpen) {
            zip.current.value = "";
        }
    }, [place?.getPlace(), isModalOpen]);

    const handleAddress = (e) => {
        e.preventDefault();
        let addressVal = [...addresses];
        if (address === "" && fullAddress.current.value === "") {
            let newErrors = { ...errors };
            newErrors.address = "Please Select address";
            setErrors(newErrors);
            return false;
        } else if (addressName.current.value === "") {
            let newErrors = { ...errors };
            newErrors.address_name = "Please add address";
            setErrors(newErrors);
            return false;
        } else {
            const getWorkerId = [...workers].map((w) => w.value);
            const updatedData = {
                geo_address: fullAddress.current.value,
                address_name: addressName.current.value
                    ? addressName.current.value
                    : "",
                floor: floor.current.value,
                apt_no: Apt.current.value,
                entrence_code: enterance.current.value,
                zipcode: zip.current.value,
                parking: parking.current.value,
                longitude: long.current.value,
                latitude: lat.current.value,
                city: city.current.value,
                prefer_type: prefer_type.current.value,
                key: key.current.value,
                lobby: lobby.current.value,
                is_dog_avail: is_dog_avail.current.checked,
                is_cat_avail: is_cat_avail.current.checked,
                client_id: client_id.current.value,
                id: 0,
                not_allowed_worker_ids:
                    getWorkerId.length > 0 ? getWorkerId.toString() : null,
            };
            const adId = addressId.current?.value;
            if (isAdd.current) {
                if (!params.id) {
                    addressVal = [updatedData, ...addressVal];
                }
            } else {
                addressVal[addressId.current.value]["geo_address"] =
                    updatedData.geo_address;
                addressVal[addressId.current.value]["floor"] =
                    updatedData.floor;
                addressVal[addressId.current.value]["apt_no"] =
                    updatedData.apt_no;
                addressVal[addressId.current.value]["entrence_code"] =
                    updatedData.entrence_code;
                addressVal[addressId.current.value]["zipcode"] =
                    updatedData.zipcode;
                addressVal[addressId.current.value]["parking"] =
                    updatedData.parking;
                addressVal[addressId.current.value]["prefer_type"] =
                    updatedData.prefer_type;
                addressVal[addressId.current.value]["key"] =
                    updatedData.key;
                addressVal[addressId.current.value]["lobby"] =
                    updatedData.lobby;
                addressVal[addressId.current.value]["is_dog_avail"] =
                    updatedData.is_dog_avail;
                addressVal[addressId.current.value]["is_cat_avail"] =
                    updatedData.is_cat_avail;
                addressVal[addressId.current.value]["longitude"] =
                    updatedData.longitude;
                addressVal[addressId.current.value]["latitude"] =
                    updatedData.latitude;
                addressVal[addressId.current.value]["address_name"] =
                    updatedData.address_name ? updatedData.address_name : "";
                addressVal[addressId.current.value]["not_allowed_worker_ids"] =
                    updatedData.not_allowed_worker_ids
                        ? updatedData.not_allowed_worker_ids
                        : "";
                // console.log(updatedData.not_allowed_worker_ids);
            }
            if (params.id) {
                axios
                    .post(
                        `/api/admin/leads/save-property-address`,
                        {
                            data: isAdd.current
                                ? updatedData
                                : addressVal[addressId.current.value],
                        },
                        { headers }
                    )
                    .then((response) => {
                        if (isAdd.current) {
                            addressVal = [response.data.data, ...addressVal];
                        } else {
                            addressVal[adId] = response.data.data;
                        }
                        setAddresses(addressVal);
                        alert.success(
                            "Lead property address saved successfully!"
                        );
                    });
            } else {
                setAddresses(addressVal);
            }
        }
        resetForm();
        setModalStatus(false);
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
        setWorkers([]);
    };

    const handleMenu = (e, data) => {
        const menuType = e.target.getAttribute("menutype");
        if (menuType === "edit") {
            setModalStatus(true);
            isAdd.current = false;
            setTimeout(() => {
                fullAddress.current.value = data?.geo_address;
                addressName.current.value = data?.address_name
                    ? data.address_name
                    : "";
                floor.current.value = data?.floor;
                Apt.current.value = data?.apt_no;
                enterance.current.value = data?.entrence_code;
                zip.current.value = data?.zipcode;
                parking.current.value = data?.parking;
                key.current.value = data?.key;
                lobby.current.value = data?.lobby;
                prefer_type.current.value = data?.prefer_type
                    ? data.prefer_type
                    : "default";
                is_cat_avail.current.checked = data?.is_cat_avail ? true : false;
                is_dog_avail.current.checked = data?.is_dog_avail ? true : false;
                addressId.current.value =
                    data?.indexId !== undefined ? data?.indexId : data?.id;
                client_id.current.value = data.client_id ? data.client_id : 0;
                lat.current.value = data?.latitude;
                long.current.value = data?.longitude;
                setLatitude(Number(data?.latitude));
                setLongitude(Number(data?.longitude));
                setAddress(data.geo_address);
                let wArr = [];
                if (data.not_allowed_worker_ids) {
                    const strToArr = data.not_allowed_worker_ids.split(",");
                    wArr = [...allWorkers].filter((w) =>
                        strToArr.includes(w.value.toString())
                    );
                }
                setWorkers(wArr);
            }, 500);
        } else {
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
                    let addressVal = [...addresses];
                    if (data.indexId !== undefined) {
                        addressVal = addressVal.filter(
                            (a, i) => i !== Number(data.indexId)
                        );
                    } else {
                        addressVal = addressVal.filter(
                            (a, i) => a.id !== Number(data.id)
                        );
                    }
                    if (Number(data.client_id)) {
                        axios
                            .delete(
                                `/api/admin/leads/remove-property-address/${data.id}`,
                                { headers }
                            )
                            .then((response) => {
                                alert.success(
                                    "Lead property address has been deleted."
                                );
                            });
                    }
                    setAddresses(addressVal);
                }
            });
        }
    };

    const getWorkers = () => {
        axios.get("/api/admin/all-workers", { headers }).then((res) => {
            const { workers } = res.data;
            const mapWorkersArr = workers.map((w) => {
                let obj = {
                    value: w.id,
                    label: `${w.firstname} ${w.lastname}`,
                };
                return obj;
            });
            setAllWorkers(mapWorkersArr);
        });
    };

    useEffect(() => {
        getWorkers();
    }, []);

    return (
        <div>
            <div className="row align-items-center mt-3 ml-0 mr-0 justify-content-between">
                <div className="">
                    <h4 className="mt-2 mb-3">{heading}</h4>
                </div>
                <div className="text-right ">
                    <button
                        type="button"
                        onClick={() => {
                            setModalStatus(true);
                            isAdd.current = true;
                            resetForm();
                        }}
                        className="btn navyblue"
                    >
                        {" "}
                        + {t("admin.leads.AddLead.addAddress.Add")}
                    </button>
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
                                            className="form-control skyBorder"
                                            placeholder={t(
                                                "admin.leads.AddLead.addAddress.placeHolder.fullAddress"
                                            )}
                                        // readOnly
                                        />
                                        {errors.address ? (
                                            <small className="text-danger mb-1">
                                                {errors.address}
                                            </small>
                                        ) : (
                                            ""
                                        )}
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
                                            className="form-control skyBorder"
                                            placeholder={t(
                                                "admin.leads.AddLead.addAddress.placeHolder.addressName"
                                            )}
                                        />
                                        {errors.address_name ? (
                                            <small className="text-danger mb-1">
                                                {errors.address_name}
                                            </small>
                                        ) : (
                                            ""
                                        )}
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
                                            className="form-control skyBorder"
                                            placeholder={t(
                                                "admin.leads.AddLead.addAddress.placeHolder.floor"
                                            )}
                                        />
                                        {errors.floor ? (
                                            <small className="text-danger mb-1">
                                                {errors.floor}
                                            </small>
                                        ) : (
                                            ""
                                        )}
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
                                            className="form-control skyBorder"
                                            placeholder={t(
                                                "admin.leads.AddLead.addAddress.placeHolder.AptNumberAndAptName"
                                            )}
                                        />
                                        {errors.Apt ? (
                                            <small className="text-danger mb-1">
                                                {errors.Apt}
                                            </small>
                                        ) : (
                                            ""
                                        )}
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
                                            className="form-control skyBorder"
                                            placeholder={t(
                                                "admin.leads.AddLead.addAddress.placeHolder.EnteranceCode"
                                            )}
                                        />
                                        {errors.enterance ? (
                                            <small className="text-danger mb-1">
                                                {errors.enterance}
                                            </small>
                                        ) : (
                                            ""
                                        )}
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
                                            className="form-control skyBorder"
                                            placeholder={t(
                                                "admin.leads.AddLead.addAddress.placeHolder.ZipCode"
                                            )}
                                        />
                                        {errors.zip && (
                                            <small className="text-danger mb-1">
                                                {errors.zip}
                                            </small>
                                        )}
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
                                            className="form-control skyBorder"
                                            placeholder={t(
                                                "admin.leads.AddLead.addAddress.placeHolder.parking"
                                            )}
                                        />
                                        {errors.parking && (
                                            <small className="text-danger mb-1">
                                                {errors.parking}
                                            </small>
                                        )}
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
                                            className="form-control skyBorder"
                                            placeholder={t(
                                                "admin.leads.AddLead.addAddress.placeHolder.Lobby"
                                            )}
                                        />
                                        {errors.lobby ? (
                                            <small className="text-danger mb-1">
                                                {errors.lobby}
                                            </small>
                                        ) : (
                                            ""
                                        )}
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
                                            className="form-control skyBorder"
                                            placeholder={t(
                                                "admin.leads.AddLead.addAddress.placeHolder.Key"
                                            )}
                                        />
                                        {errors.key ? (
                                            <small className="text-danger mb-1">
                                                {errors.key}
                                            </small>
                                        ) : (
                                            ""
                                        )}
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
                                            defaultValue="default"
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
                                                />
                                                <span className="checkmark"></span>

                                                {t(
                                                    "admin.leads.AddLead.addAddress.IsCat"
                                                )}
                                            </label>
                                        </div>
                                    </div>
                                    <div className="form-group d-flex align-items-center">
                                        <label className="control-label navyblueColor" style={{ width: "15rem", fontWeight: "500", fontSize: "14px" }}>
                                            {t(
                                                "admin.leads.AddLead.addAddress.NotAllowedWorkers"
                                            )}
                                        </label>
                                        <Select
                                            value={workers}
                                            name="workers"
                                            isMulti
                                            options={allWorkers}
                                            className="basic-multi-single w-100 skyBorder"
                                            isClearable={true}
                                            placeholder={t(
                                                "admin.leads.AddLead.addAddress.Options.pleaseSelect"
                                            )}
                                            classNamePrefix="select"
                                            onChange={(newValue) =>
                                                setWorkers(newValue)
                                            }
                                        />
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
                            <input
                                type="hidden"
                                ref={client_id}
                                name="client_id"
                                defaultValue={params.id ? params.id : 0}
                            />
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
            <div className="card">
                <div className="card-body" style={{ background: "#FAFBFC" }}>
                    <div className="boxPanel">
                        {addresses.length > 0 ? (
                            <Table className="table table-bordered">
                                <Thead>
                                    <Tr>
                                        <Th>
                                            {t(
                                                "admin.leads.AddLead.addAddress.Name"
                                            )}
                                        </Th>
                                        <Th>
                                            {t(
                                                "admin.leads.AddLead.addAddress.Address"
                                            )}
                                        </Th>
                                        <Th>
                                            {t(
                                                "admin.leads.AddLead.addAddress.Floor"
                                            )}
                                        </Th>
                                        <Th>
                                            {t(
                                                "admin.leads.AddLead.addAddress.AptNumberAndAptName"
                                            )}
                                        </Th>
                                        <Th>
                                            {t(
                                                "admin.leads.AddLead.addAddress.EnteranceCode"
                                            )}
                                        </Th>
                                        <Th>

                                            {t(
                                                "admin.leads.AddLead.addAddress.Zipcode"
                                            )}
                                        </Th>
                                        <Th>
                                            {t(
                                                "admin.leads.AddLead.addAddress.Gender_Prefernce"
                                            )}
                                        </Th>

                                        <Th>
                                        {t(
                                                "admin.leads.AddLead.addAddress.Cat"
                                            )}
                                        </Th>
                                        <Th>
                                        {t(
                                                "admin.leads.AddLead.addAddress.Dog"
                                            )}
                                        </Th>

                                        {/* <Th>

                                            {t(
                                                "admin.leads.AddLead.addAddress.Allowed_workers"
                                            )}
                                        </Th> */}

                                        <Th>

                                            {t(
                                                "admin.leads.AddLead.addAddress.Action"
                                            )}
                                        </Th>
                                    </Tr>
                                </Thead>
                                <Tbody>
                                    {addresses &&
                                        addresses.map((item, index) => {
                                            return (
                                                <Tr key={index}>
                                                    <Td className="my-3">
                                                        {"  "}
                                                        {item.address_name
                                                            ? item.address_name
                                                            : "NA"}{" "}
                                                    </Td>
                                                    <Td className="my-3">
                                                        {"  "}
                                                        {item.geo_address
                                                            ? item.geo_address
                                                            : "NA"}{" "}
                                                    </Td>
                                                    <Td className="my-3">
                                                        {"  "}
                                                        {item.floor
                                                            ? item.floor
                                                            : "NA"}
                                                    </Td>
                                                    <Td className="my-3">
                                                        {"  "}
                                                        {item.apt_no
                                                            ? item.apt_no
                                                            : "NA"}
                                                    </Td>
                                                    <Td className="my-3">
                                                        {"  "}
                                                        {item.entrence_code
                                                            ? item.entrence_code
                                                            : "NA"}
                                                    </Td>
                                                    <Td className="my-3">
                                                        {"  "}
                                                        {item.zipcode
                                                            ? item.zipcode
                                                            : "NA"}
                                                    </Td>
                                                    <Td className="my-3">
                                                        {"  "}
                                                        {item.prefer_type
                                                            ? item.prefer_type
                                                            : "NA"}
                                                    </Td>
                                                    <Td className="my-3">
                                                        {"  "}
                                                        <label
                                                            className="form-check-label custom-checkbox navyblueColor"
                                                            style={{ fontWeight: "500", fontSize: "14px" }}
                                                        >
                                                            <input
                                                                ref={is_cat_avail}
                                                                className="form-check-input"
                                                                type="checkbox"
                                                                defaultChecked={item?.is_cat_avail != 0 ? true: false}
                                                            />
                                                            <span className="checkmark"></span>
                                                        </label>

                                                    </Td>
                                                    <Td className="my-3">
                                                        {" "}
                                                        <label
                                                            className="form-check-label custom-checkbox navyblueColor"
                                                            style={{ fontWeight: "500", fontSize: "14px" }}
                                                        >
                                                            <input
                                                                ref={is_dog_avail}
                                                                className="form-check-input"
                                                                type="checkbox"
                                                                defaultChecked={item?.is_dog_avail != 0 ? true: false}
                                                            />
                                                            <span className="checkmark"></span>
                                                        </label>

                                                    </Td>
                                                    {/* <Td>
                                                        {item.not_allowed_worker_ids ? item.not_allowed_worker_ids
                                                            .map((worker, idx) => (
                                                                <span class="user-item">
                                                                    <div class="">
                                                                        <i class="fa fa-user"></i>
                                                                    </div>
                                                                    <span class="">{worker.label}</span>
                                                                    <span class="">
                                                                        <i class="fa fa-trash"></i>
                                                                    </span>
                                                                </span>
                                                            ))
                                                            : "NA"}

                                                    </Td> */}

                                                    <Td className="my-3">
                                                        {" "}
                                                        <div className="action-dropdown dropdown">
                                                            <button
                                                                type="button"
                                                                className="btn btn-default dropdown-toggle"
                                                                data-toggle="dropdown"
                                                            >
                                                                <i className="fa fa-ellipsis-vertical"></i>
                                                            </button>
                                                            <div className="dropdown-menu">
                                                                {addressMenu.map(
                                                                    (
                                                                        menu,
                                                                        i
                                                                    ) => {
                                                                        return (
                                                                            <button
                                                                                type="button"
                                                                                menutype={
                                                                                    menu.key
                                                                                }
                                                                                className="dropdown-item"
                                                                                key={
                                                                                    menu.key
                                                                                }
                                                                                onClick={(
                                                                                    e
                                                                                ) => {
                                                                                    e.preventDefault();
                                                                                    handleMenu(
                                                                                        e,
                                                                                        {
                                                                                            ...item,
                                                                                            indexId:
                                                                                                index,
                                                                                            id: item.id,
                                                                                        }
                                                                                    );
                                                                                }}
                                                                            >
                                                                                {
                                                                                    menu.label
                                                                                }
                                                                            </button>
                                                                        );
                                                                    }
                                                                )}
                                                            </div>
                                                        </div>
                                                    </Td>
                                                </Tr>
                                            );
                                        })}
                                </Tbody>
                            </Table>
                        ) : (
                            <p className="text-center mt-5">
                                {t(
                                    "admin.leads.AddLead.addAddress.AddressNotFound"
                                )}
                            </p>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
});

export default PropertyAddress;
