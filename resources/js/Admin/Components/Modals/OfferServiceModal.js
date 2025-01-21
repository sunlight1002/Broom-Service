import { useEffect, useState, useMemo } from "react";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";
import Select from "react-select";
import { useTranslation } from "react-i18next";
import { FaTrashAlt } from "react-icons/fa";

const initialValues = {
    service: "",
    sub_services: {
        id: "",
        address: "",
        address_name: "",
        sub_service_name: "",
    },
    name: "",
    type: "fixed",
    freq_name: "",
    frequency: "",
    fixed_price: "",
    freelancer_price: "",
    rateperhour: "",
    ratepersquaremeter: "",
    totalsquaremeter: "",
    other_title: "",
    template: "",
    cycle: "",
    period: "",
    address: "",
    weekdays: [],
    is_freelancer: false,
    weekday_occurrence: "1",
    weekday: "sunday",
    month_occurrence: 1,
    month_date: 1,
    monthday_selection_type: "weekday",
    workers: [
        {
            jobHours: "",
        },
    ],
};
export default function OfferServiceModal({
    setIsOpen,
    isOpen,
    addresses,
    services,
    frequencies,
    tmpFormValues,
    handleSaveForm,
    formValues,
    isAdd,
    editIndex,
}) {

    const { t } = useTranslation();
    const alert = useAlert();
    const [offerServiceTmp, setOfferServiceTmp] = useState(
        isAdd ? [initialValues] : tmpFormValues && Array.isArray(tmpFormValues) ? tmpFormValues : [initialValues]
    );

    useEffect(() => {
        if (!isAdd && Array.isArray(tmpFormValues) && tmpFormValues.length > 0) {
            setOfferServiceTmp(tmpFormValues);
            const filterServiceId = tmpFormValues.filter(
                (service) => service.template == "airbnb"
            );
            if (filterServiceId.length > 0) {
                handleGetSubServices(filterServiceId[0].service);
            }
        } else if (!isAdd && tmpFormValues && !Array.isArray(tmpFormValues)) {
            setOfferServiceTmp([tmpFormValues]);
        }
    }, [isAdd, tmpFormValues]);

    const [toggleOtherService, setToggleOtherService] = useState([]);
    const [subServiceState, setSubServiceState] = useState({}); // Store selected subservices per index

    const [toggleAirbnbService, setToggleAirbnbService] = useState([]);
    const [selectedSubServices, setSelectedSubServices] = useState([]);
    const [subData, setSubData] = useState([]);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const checkValidation = (_formValues) => {
        if (_formValues.address == "") {
            alert.error("The address is not selected");
            return false;
        }
        return true;
    };

    const handleGetSubServices = async (id) => {
        try {
            const res = await axios.get(`/api/admin/get-sub-services/${id}`, { headers });
            setSubData(res.data.subServices || []);
        } catch (error) {
            console.log("Error fetching sub-services:", error);
        }
    };

    const handleSubServices = (selectedOptions, index) => {
        const selectedValues = selectedOptions.target.value;

        const selectedOptionName = selectedOptions.target.options[selectedOptions.target.selectedIndex].getAttribute("subname");
        const selectedSubPrice = selectedOptions.target.options[selectedOptions.target.selectedIndex].getAttribute("subprice");
        const selectedSubHours = selectedOptions.target.options[selectedOptions.target.selectedIndex].getAttribute("hours");


        // Update subservices for the specific index
        setSubServiceState((prevState) => ({
            ...prevState,
            [index]: selectedValues,
        }));

        setOfferServiceTmp((prevState) =>
            prevState.map((service, i) => ({
                ...service,
                fixed_price: i === index ? selectedSubPrice : service.fixed_price,
                workers: i === index ? [{ jobHours: selectedSubHours }] : service.workers,
                sub_services: {
                    ...service.sub_services,
                    id: i === index ? selectedValues : service.sub_services?.id,
                    sub_service_name: i === index ? selectedOptionName : service.sub_services?.sub_service_name,
                },
            }))
        );
    };


    const handleSubServiceAddress = (index, value) => {

        const selectedAddress = addresses.find(address => address.id == parseInt(value));

        setOfferServiceTmp((prevState) =>
            prevState.map((service, i) =>
                i === index
                    ? {
                        ...service,
                        sub_services: {
                            ...service.sub_services,
                            address: value,
                            address_name: selectedAddress ? selectedAddress.address_name : "",
                        },
                    }
                    : service
            )
        );
    };


    const handleInputChange = (index, field, value) => {
        setOfferServiceTmp((prevState) =>
            prevState.map((service, i) =>
                i === index ? { ...service, [field]: value } : service
            )
        );
    };



    const handleFrequencyChange = (index, e) => {
        const target = e.target;

        if (!target) {
            console.warn("Event target is null or undefined.");
            return;
        }

        const selectedOption = target.selectedIndex >= 0 ? target.options[target.selectedIndex] : null;

        if (!selectedOption) {
            console.warn("No option selected for frequency.");
            return;
        }

        setOfferServiceTmp((prevState) =>
            prevState.map((service, i) => {
                if (i === index) {
                    return {
                        ...service,
                        frequency: target.value,
                        freq_name: selectedOption.getAttribute("name") || "",
                        cycle: selectedOption.getAttribute("cycle") || "",
                        period: selectedOption.getAttribute("period") || "",
                    };
                }
                return service;
            })
        );
    };

    const handleServiceChange = (index, e) => {
        const target = e.target;

        if (!target) {
            console.warn("Event target is null or undefined.");
            return;
        }
        const selectedOption = target.selectedIndex >= 0 ? target.options[target.selectedIndex] : null;
        if (!selectedOption) {
            console.warn("No option selected for service.");
            return;
        }

        setOfferServiceTmp((prevState) =>
            prevState.map((service, i) => {
                if (i === index) {
                    return {
                        ...service,
                        service: target.value,
                        name: selectedOption.getAttribute("name") || "",
                        template: selectedOption.getAttribute("template") || "",
                    };
                }
                return service;
            })
        );
    };

    const handleAddressChange = (index, value) => {
        const selectedAddress = addresses.find(address => address.id === parseInt(value));

        setOfferServiceTmp((prevState) =>
            prevState.map((service, i) =>
                i === index
                    ? {
                        ...service,
                        address: value,
                        address_name: selectedAddress ? selectedAddress.address_name : "",
                    }
                    : service
            )
        );
    };

    const handleAddService = () => {
        const selectedAddress = addresses[0];

        setOfferServiceTmp((prevState) => [
            ...prevState,
            {
                ...initialValues,
                address: selectedAddress.id
            }
        ]);
    };


    const handleDeleteService = (index) => {
        setOfferServiceTmp((prevState) => prevState.filter((_, i) => i !== index));
    };

    const handleSubmit = () => {
        const combinedArray = isAdd ? ([...formValues, ...offerServiceTmp]) : offerServiceTmp;

        const valid = offerServiceTmp.every((formValues) => checkValidation(formValues));
        if (valid) {
            handleSaveForm(isAdd ? -1 : editIndex, combinedArray);
            setIsOpen(false);
        }
    };

    const handleChangeWorkerCount = (e, serviceIndex) => {
        const _noOfWorker = e.target.value > 0 ? e.target.value : 1;

        const _workerForms = Array.from(
            { length: _noOfWorker },
            () => initialValues.workers[0]
        );

        setOfferServiceTmp((prevState) => {
            const updatedServices = [...prevState];
            updatedServices[serviceIndex] = {
                ...updatedServices[serviceIndex],
                workers: _workerForms,
            };
            return updatedServices;
        });
    };

    const handleWorkerForm = (serviceIndex, workerIndex, tmpvalue) => {
        setOfferServiceTmp((prevState) => {
            const updatedServices = [...prevState];

            if (!updatedServices[serviceIndex]) {
                console.error(`Service at index ${serviceIndex} does not exist.`);
                return prevState;
            }

            if (!updatedServices[serviceIndex].workers) {
                updatedServices[serviceIndex].workers = [];
            }

            if (workerIndex >= updatedServices[serviceIndex].workers.length) {
                console.error(`Worker at index ${workerIndex} does not exist.`);
            }

            const updatedWorkers = updatedServices[serviceIndex].workers.map((worker, wIndex) => {
                if (wIndex === workerIndex) {
                    return tmpvalue;
                }
                return worker;
            });

            updatedServices[serviceIndex].workers = updatedWorkers;

            return updatedServices;
        });
    };

    const handleFreelancer = (serviceIndex, tmpvalue) => {
        setOfferServiceTmp((prevState) => {
            const updatedServices = [...prevState];
            updatedServices[serviceIndex] = {
                ...updatedServices[serviceIndex],
                is_freelancer: tmpvalue || false,
                freelancer_price: tmpvalue ? updatedServices[serviceIndex]?.freelancer_price : 0,
            };
            return updatedServices;
        });
    };
    

    const handleFreelancerPrice = (serviceIndex, tmpvalue) => {
        setOfferServiceTmp((prevState) => {
            const updatedServices = [...prevState];
            updatedServices[serviceIndex] = {
                ...updatedServices[serviceIndex],
                freelancer_price: tmpvalue,
            };
            return updatedServices;
        })
    }

    return (
        <Modal
            size="lg"
            className="modal-container"
            show={isOpen}
            onHide={() => {
                setIsOpen(false);
            }}
        >
            <Modal.Header closeButton>
                <Modal.Title>
                    {isAdd ? t("global.addService") : t("admin.global.Edit") + t("global.service")}
                </Modal.Title>
            </Modal.Header>

            <Modal.Body>
                {/* {
                    ((toggleAirbnbService[0] == false) || (toggleAirbnbService.length == 0))  ? ( */}
                <div className="row mb-1">
                    <div className="col-sm-6">
                        <div className="form-group m-0">
                            <label className="control-label">{t("client.jobs.change.property")}</label>
                            <select
                                className="form-control"
                                name="address"
                                value={offerServiceTmp[0]?.address || ""}
                                onChange={(e) => handleAddressChange(0, e.target.value)}
                            >
                                <option value="">{t("admin.leads.AddLead.AddLeadClient.JobModal.pleaseSelect")}</option>
                                {addresses.map((address, i) => (
                                    <option value={address.id} key={i}>
                                        {address.address_name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>
                </div>

                {Array.isArray(offerServiceTmp) && offerServiceTmp.length > 0 ? (
                    offerServiceTmp.map((service, index) => (
                        <div key={index}>
                            <div className="d-flex justify-content-between align-items-center mb-1">
                                <h5 className="mt-3 mb-2">{t("global.service")} {index + 1}</h5>
                                {offerServiceTmp.length > 1 && index !== offerServiceTmp.length - 1 && (
                                    <Button
                                        className="navyblue delete-btn"
                                        onClick={() => handleDeleteService(index)}
                                    >
                                        <FaTrashAlt />
                                    </Button>
                                )}
                            </div>

                            <div className="row mb-1">
                                {/* Service Input */}
                                <div className="col-sm-6">
                                    <div className="form-group m-0">
                                        <label className="control-label">{t("global.service")}</label>
                                        <select
                                            name="service"
                                            className="form-control"
                                            value={service.service || ""}
                                            onChange={(e) => {
                                                handleServiceChange(index, e);
                                                const updatedToggleState = [...toggleOtherService];
                                                const selectedIndex = e.target.selectedIndex;
                                                const selectedOptionName = e.target.options[selectedIndex].getAttribute("template");

                                                if (selectedOptionName === "others") {
                                                    updatedToggleState[index] = true;
                                                } else {
                                                    updatedToggleState[index] = false;
                                                }

                                                setToggleOtherService(updatedToggleState);
                                                const updatedAirbnbState = [...toggleAirbnbService];

                                                if (selectedOptionName === "airbnb") {
                                                    updatedAirbnbState[index] = true;
                                                    handleGetSubServices(e.target.value);
                                                } else {
                                                    updatedAirbnbState[index] = false;
                                                }
                                                setToggleAirbnbService(updatedAirbnbState);
                                            }}
                                        >
                                            <option value={0}>{t("admin.leads.AddLead.AddLeadClient.JobModal.pleaseSelect")}</option>
                                            {services.map((service, i) => (
                                                <option
                                                    key={i}
                                                    value={service.id}
                                                    name={service.name}
                                                    template={service.template}
                                                >
                                                    {service.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>

                                {/* Frequency Input */}
                                <div className="col-sm-6">
                                    <div className="form-group m-0">
                                        <label className="control-label">{t("client.offer.view.frequency")}</label>
                                        <select
                                            name="frequency"
                                            className="form-control mb-2"
                                            value={service.frequency || 0}
                                            onChange={(e) => handleFrequencyChange(index, e)}
                                        >
                                            <option value={0}>{t("admin.leads.AddLead.AddLeadClient.JobModal.pleaseSelect")}</option>
                                            {frequencies.map((s, i) => (
                                                <option cycle={s.cycle} period={s.period} name={s.name} value={s.id} key={i}>
                                                    {s.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div className="row mb-2">
                                {/* Type Input */}
                                <div className="col-sm-6">
                                    <div className="form-group m-0">
                                        <label className="control-label">{t("price_offer.type")}</label>
                                        <select
                                            name="type"
                                            className="form-control"
                                            value={service.type}
                                            onChange={(e) => handleInputChange(index, 'type', e.target.value)}
                                        >
                                            <option value="fixed">{t("admin.leads.AddLead.Options.Type.Fixed")}</option>
                                            <option value="hourly">{t("admin.leads.AddLead.Options.Type.Hourly")}</option>
                                            <option value="squaremeter">{t("admin.leads.AddLead.Options.Type.Squaremeter")}</option>
                                        </select>
                                    </div>
                                </div>

                                {/* Price Input */}
                                <div className="col-sm-6">
                                    <div className="form-group m-0">
                                        <label className="control-label">{t("admin.leads.AddLead.AddLeadClient.jobMenu.Price")}</label>
                                        {service.type === "fixed" && (
                                            <input
                                                type="number"
                                                name="fixed_price"
                                                value={service.fixed_price || ""}
                                                onChange={(e) => handleInputChange(index, 'fixed_price', e.target.value)}
                                                className="form-control jobprice"
                                                required
                                                placeholder="Enter job price"
                                            />
                                        )}
                                        {service.type === "hourly" && (
                                            <input
                                                type="number"
                                                name="rateperhour"
                                                value={service.rateperhour || ""}
                                                onChange={(e) => handleInputChange(index, 'rateperhour', e.target.value)}
                                                className="form-control jobprice"
                                                required
                                                placeholder="Enter rate P/Hour"
                                            />
                                        )}
                                        {service.type === "squaremeter" && (
                                            <input
                                                type="number"
                                                name="ratepersquaremeter"
                                                value={service.ratepersquaremeter || ""}
                                                onChange={(e) => handleInputChange(index, 'ratepersquaremeter', e.target.value)}
                                                className="form-control p-2"
                                                required
                                                placeholder="Enter rate P/Square meter"
                                            />
                                        )}
                                    </div>
                                </div>
                            </div>

                            <div className="row">
                                <div className="col-sm-6">
                                    <div className="form-group m-0 d-flex align-items-center my-2">
                                        <label htmlFor="isfreelancer" className="control-label mb-0 mr-2"
                                            style={{
                                                userSelect: "none",
                                                cursor: "pointer"
                                            }}
                                        >{t("admin.global.is_freelancer")}</label>
                                        <input
                                            name="isfreelancer"
                                            id="isfreelancer"
                                            type="checkbox"
                                            className=""

                                            defaultChecked={service.is_freelancer}
                                            onChange={(e) => handleFreelancer(index, e.target.checked)}
                                        />
                                    </div>
                                </div>

                                {
                                    (toggleAirbnbService[index] || service?.sub_services?.address) && (
                                        <div className="col-sm-6">
                                            <div className="form-group m-0">
                                                <label className="control-label">{t("client.jobs.change.property")}</label>
                                                <select
                                                    className="form-control"
                                                    name="address"
                                                    value={service?.sub_services?.address || ""}
                                                    onChange={(e) => handleSubServiceAddress(index, e.target.value)}
                                                >
                                                    <option value="">{t("admin.leads.AddLead.AddLeadClient.JobModal.pleaseSelect")}</option>
                                                    {addresses.map((address, i) => (
                                                        <option value={address.id} key={i}>
                                                            {address.address_name}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>
                                        </div>
                                    )
                                }
                                {offerServiceTmp[index].is_freelancer && (
                                    <div className="col-sm-6">
                                        <div className="form-group m-0">
                                            <label className="control-label">{t("admin.global.freelancer_price")}</label>
                                            <input
                                                type="number"
                                                name="freelancer_price"
                                                value={service.freelancer_price || ""}
                                                onChange={(e) => handleFreelancerPrice(index, e.target.value)}
                                                className="form-control jobprice"
                                                required
                                                placeholder="Enter job price"
                                            />
                                        </div>
                                    </div>
                                )}

                            </div>

                            {service.type === "squaremeter" && (
                                <div className="row mb-1">
                                    <div className="col-sm-12">
                                        <div className="form-group m-0">
                                            <label className="control-label">Total Square Meter</label>
                                            <input
                                                type="number"
                                                name="totalsquaremeter"
                                                value={service.totalsquaremeter || ""}
                                                onChange={(e) => handleInputChange(index, 'totalsquaremeter', e.target.value)}
                                                className="form-control p-2"
                                                required
                                                placeholder="Total Square meter"
                                            />
                                        </div>
                                    </div>
                                </div>
                            )}

                            {(toggleAirbnbService[index] || service?.sub_services?.id) && (
                                <div className="row mb-1">
                                    <div className="col-sm-12">
                                        <div className="form-group m-0">
                                            <label className="control-label">{t("price_offer.subservice")}</label>
                                            <select
                                                className="form-control"
                                                value={service.sub_services?.id || ""}
                                                onChange={(selectedOptions) => handleSubServices(selectedOptions, index)}
                                            >
                                                <option value="">{t("price_offer.select_subservice")}</option>
                                                {subData?.map((item, idx) => (
                                                    <option key={idx} subname={`${item.name_en} - ${item.apartment_size}`} subprice={item.price} hours={item.hours} value={item.id}>
                                                        {`${item.name_en} - ${item.apartment_size}`}
                                                    </option>
                                                ))}
                                            </select>

                                        </div>
                                    </div>
                                </div>
                            )}

                            {toggleOtherService[index] && (
                                <div className="form-group">
                                    <textarea
                                        type="text"
                                        name="other_title"
                                        id={`other_title_${index}`}
                                        placeholder="Service Title"
                                        className="form-control"
                                        value={offerServiceTmp[index]?.other_title || ""}
                                        onChange={(e) => handleInputChange(index, 'other_title', e.target.value)}
                                    />
                                </div>
                            )}



                            <div className="mb-2">
                                <h5 className="mt-3 mb-2">{t("global.workers")}</h5>
                                <div className="d-flex align-items-center">
                                    <label htmlFor={`noOfWorkers-${index}`} className="mr-2">{t("global.noOfWorker")} :</label>
                                    <input
                                        type="number"
                                        min={1}
                                        className="form-control w-25"
                                        id={`noOfWorkers-${index}`}
                                        value={service.workers.length}
                                        onChange={(e) => handleChangeWorkerCount(e, index)}
                                    />
                                </div>
                            </div>
                            <div className="row">
                                {service.workers.map((worker, workerIndex) => (
                                    <div key={workerIndex} className="col-sm-6">
                                        <WorkerForm
                                            workerFormValues={worker}
                                            handleTmpValue={handleWorkerForm}
                                            index={workerIndex}
                                            serviceIndex={index}
                                        />
                                    </div>
                                ))}
                            </div>
                        </div>
                    ))
                ) : (
                    <p>{t("global.noServicesAvailable")}</p>
                )
                }
                <div className="mt-4 text-right">
                    <Button onClick={handleAddService} className="navyblue btn">
                        {t("global.addService")}
                    </Button>
                </div>
            </Modal.Body>

            <Modal.Footer>
                <Button
                    type="button"
                    className="btn btn-secondary"
                    onClick={() => {
                        setIsOpen(false);
                    }}
                >
                    {t("modal.close")}
                </Button>
                <Button
                    type="button"
                    onClick={handleSubmit}
                    className="btn btn-primary"
                >
                    {t("modal.save")}
                </Button>
            </Modal.Footer>
        </Modal>
    );
}

function WorkerForm({ workerFormValues, handleTmpValue, index, serviceIndex }) {
    const { t } = useTranslation();
    const handleInputChange = (e) => {
        let newFormValues = { ...workerFormValues };

        newFormValues[e.target.name] = e.target.value;

        handleTmpValue(serviceIndex, index, newFormValues);
    };

    return (
        <div className="row mb-3">
            <div className="col-sm-3 d-flex align-items-center justify-content-center">
                <div className="form-group m-0">
                    <strong>{t("global.worker")} {index + 1}</strong>
                </div>
            </div>

            <div className="col-sm-7">
                <div className="form-group m-0">
                    <input
                        type="number"
                        name="jobHours"
                        id={`jobHours-${index}`}
                        value={workerFormValues.jobHours || ""}
                        onChange={(e) => handleInputChange(e)}
                        className="form-control jobhr"
                        required
                        placeholder="Enter job Hrs"
                    />
                </div>
            </div>
        </div>
    );
}
