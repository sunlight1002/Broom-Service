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
    let addressId = useRef();
    let lat = useRef();
    let long = useRef();
    let city = useRef();
    let prefer_type = useRef();
    let is_dog_avail = useRef();
    let is_cat_avail = useRef();
    let client_id = useRef();
    let addressName = useRef();

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
                longitude: long.current.value,
                latitude: lat.current.value,
                city: city.current.value,
                prefer_type: prefer_type.current.value,
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
                addressVal[addressId.current.value]["prefer_type"] =
                    updatedData.prefer_type;
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
                fullAddress.current.value = data.geo_address;
                addressName.current.value = data.address_name
                    ? data.address_name
                    : "";
                floor.current.value = data.floor;
                Apt.current.value = data.apt_no;
                enterance.current.value = data.entrence_code;
                zip.current.value = data.zipcode;
                prefer_type.current.value = data.prefer_type
                    ? data.prefer_type
                    : "default";
                is_cat_avail.current.checked = data.is_cat_avail ? true : false;
                is_dog_avail.current.checked = data.is_dog_avail ? true : false;
                addressId.current.value =
                    data.indexId !== undefined ? data.indexId : data.id;
                client_id.current.value = data.client_id ? data.client_id : 0;
                lat.current.value = data.latitude;
                long.current.value = data.longitude;
                setLatitude(Number(data.latitude));
                setLongitude(Number(data.longitude));
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
            <div className="row">
                <div className="col-sm-8">
                    <h4 className="mt-2 mb-3">{heading}</h4>
                </div>
                <div className="text-right col-sm-3">
                    <button
                        type="button"
                        onClick={() => {
                            setModalStatus(true);
                            isAdd.current = true;
                            resetForm();
                        }}
                        className="btn btn-success"
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
                        show={isModalOpen}
                        onHide={() => {
                            isAdd.current = true;
                            resetForm();
                            setModalStatus(false);
                        }}
                    >
                        <Modal.Header closeButton>
                            <Modal.Title>
                                {isAdd.current
                                    ? t(
                                          "admin.leads.AddLead.addAddress.AddPropertyAddress"
                                      )
                                    : t(
                                          "admin.leads.AddLead.addAddress.EditPropertyAddress"
                                      )}
                            </Modal.Title>
                        </Modal.Header>

                        <Modal.Body>
                            <div className="row">
                                <div className="col-sm-12">
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
                                        <label className="control-label">
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
                                            className="form-control"
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
                            <div className="row">
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t(
                                                "admin.leads.AddLead.addAddress.Name"
                                            )}
                                        </label>
                                        <input
                                            name="address_name"
                                            ref={addressName}
                                            type="text"
                                            className="form-control"
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
                                </div>
                            </div>
                            <div className="row">
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t(
                                                "admin.leads.AddLead.addAddress.Floor"
                                            )}
                                        </label>
                                        <input
                                            type="text"
                                            ref={floor}
                                            className="form-control"
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
                                </div>
                            </div>
                            <div className="row">
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t(
                                                "admin.leads.AddLead.addAddress.AptNumberAndAptName"
                                            )}
                                        </label>
                                        <input
                                            type="text"
                                            ref={Apt}
                                            className="form-control"
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
                                </div>
                            </div>
                            <div className="row">
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t(
                                                "admin.leads.AddLead.addAddress.EnteranceCode"
                                            )}
                                        </label>
                                        <input
                                            type="text"
                                            ref={enterance}
                                            className="form-control"
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
                                </div>
                            </div>
                            <div className="row">
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t(
                                                "admin.leads.AddLead.addAddress.ZipCode"
                                            )}
                                        </label>
                                        <input
                                            type="text"
                                            ref={zip}
                                            className="form-control"
                                            placeholder={t(
                                                "admin.leads.AddLead.addAddress.placeHolder.ZipCode"
                                            )}
                                        />
                                        {errors.zip ? (
                                            <small className="text-danger mb-1">
                                                {errors.zip}
                                            </small>
                                        ) : (
                                            ""
                                        )}
                                    </div>
                                </div>
                            </div>
                            <div className="row">
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t(
                                                "admin.leads.AddLead.addAddress.PreferedType"
                                            )}
                                        </label>
                                        <select
                                            ref={prefer_type}
                                            className="form-control"
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
                                </div>
                            </div>
                            <div className="row">
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <div className="form-check form-switch">
                                            <input
                                                ref={is_dog_avail}
                                                className="form-check-input"
                                                type="checkbox"
                                                id="isDogAvail"
                                                name="is_dog_avail"
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="isDogAvail"
                                            >
                                                {t(
                                                    "admin.leads.AddLead.addAddress.IsDOG"
                                                )}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="row">
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <div className="form-check form-switch">
                                            <input
                                                ref={is_cat_avail}
                                                className="form-check-input"
                                                type="checkbox"
                                                id="isCatAvail"
                                                name="is_cat_avail"
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="isCatAvail"
                                            >
                                                {t(
                                                    "admin.leads.AddLead.addAddress.IsCat"
                                                )}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="row">
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t(
                                                "admin.leads.AddLead.addAddress.NotAllowedWorkers"
                                            )}
                                        </label>
                                        <Select
                                            value={workers}
                                            name="workers"
                                            isMulti
                                            options={allWorkers}
                                            className="basic-multi-single "
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

                        <Modal.Footer>
                            <Button
                                type="button"
                                className="btn btn-secondary"
                                onClick={() => {
                                    isAdd.current = true;
                                    resetForm();
                                    setModalStatus(false);
                                }}
                            >
                                {t("admin.leads.AddLead.addAddress.Close")}
                            </Button>
                            <Button
                                type="button"
                                onClick={(e) => handleAddress(e)}
                                className="btn btn-primary"
                            >
                                {t("admin.leads.AddLead.addAddress.Save")}
                            </Button>
                        </Modal.Footer>
                    </Modal>
                </div>
            )}
            <div className="card">
                <div className="card-body">
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
                                            {" "}
                                            {t(
                                                "admin.leads.AddLead.addAddress.Address"
                                            )}
                                        </Th>
                                        <Th>
                                            {t(
                                                "admin.leads.AddLead.addAddress.Zipcode"
                                            )}
                                        </Th>
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
                                                    <Td>
                                                        {item.address_name
                                                            ? item.address_name
                                                            : "NA"}{" "}
                                                    </Td>
                                                    <Td>
                                                        {item.geo_address
                                                            ? item.geo_address
                                                            : "NA"}{" "}
                                                    </Td>
                                                    <Td>
                                                        {item.zipcode
                                                            ? item.zipcode
                                                            : "NA"}
                                                    </Td>
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
