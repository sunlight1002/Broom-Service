// import { useEffect, useState, useMemo } from "react";
// import { Button, Modal } from "react-bootstrap";
// import { useAlert } from "react-alert";
// import Select from "react-select";

// // import Select from "react-select";
// import { useTranslation } from "react-i18next";

// const initialValues = {
//     service: "",
//     name: "",
//     type: "fixed",
//     freq_name: "",
//     frequency: "",
//     fixed_price: "",
//     rateperhour: "",
//     ratepersquaremeter: "",
//     totalsquaremeter: "",
//     other_title: "",
//     template: "",
//     cycle: "",
//     period: "",
//     address: "",
//     weekdays: [],
//     weekday_occurrence: "1",
//     weekday: "sunday",
//     month_occurrence: 1,
//     month_date: 1,
//     monthday_selection_type: "weekday",
//     workers: [
//         {
//             jobHours: "",
//         },
//     ],
// };

// export default function OfferServiceModal({
//     setIsOpen,
//     isOpen,
//     addresses,
//     services,
//     frequencies,
//     tmpFormValues,
//     handleSaveForm,
//     isAdd,
//     editIndex,
// }) {

//     const {t} = useTranslation()
//     const alert = useAlert();
//     const [offerServiceTmp, setOfferServiceTmp] = useState(
//         isAdd ? initialValues : tmpFormValues
//     );
//     const [toggleOtherService, setToggleOtherService] = useState(false);
//     const [toggleAirbnbService, setToggleAirbnbService] = useState(false);
//     const [selectedSubServices, setSelectedSubServices] = useState(offerServiceTmp.subService || []);
//     const [subData, setSubData] = useState([]);

//     const adminlng = localStorage.getItem("admin-lng");

//     const transformedSubData = subData.map(s => ({
//         value: s.id,
//         label: adminlng === "en" ? s.name_en : s.name_heb
//     }));


//     const headers = {
//         Accept: "application/json, text/plain, */*",
//         "Content-Type": "application/json",
//         Authorization: `Bearer ` + localStorage.getItem("admin-token"),
//     };


//     useEffect(() => {
//         if (offerServiceTmp.service === '29') {
//             setToggleAirbnbService(true);
//             handleGetSubServices(29);
//         } else if (offerServiceTmp.service === '10') {
//             setToggleOtherService(true);
//         }
//     }, [offerServiceTmp.service]);

//     const handleChangeWorkerCount = (e) => {
//         const _noOfWorker = e.target.value > 0 ? e.target.value : 1;

//         const _workerForms = Array.from(
//             { length: _noOfWorker },
//             () => initialValues.workers[0]
//         );

//         setOfferServiceTmp({ ...offerServiceTmp, workers: _workerForms });
//     };

//     const checkValidation = (_formValues) => {
//         if (_formValues.address == "") {
//             alert.error("The address is not selected");
//             return false;
//         }
//         if (_formValues.service == "" || _formValues.service == 0) {
//             alert.error("The service is not selected");
//             return false;
//         }

//         let ot = document.querySelector("#other_title");

//         if (_formValues.service == "10" && ot != undefined) {
//             if (_formValues.other_title == "") {
//                 alert.error("Other title cannot be blank");
//                 return false;
//             }
//             _formValues.other_title =
//                 document.querySelector("#other_title").value;
//         } else {
//             _formValues.other_title = "";
//         }

//         !_formValues.type ? (_formValues.type = "fixed") : "";
//         if (_formValues.type == "hourly") {
//             if (_formValues.rateperhour == "") {
//                 alert.error("The rate per hour value is missing");
//                 return false;
//             }
//         }  else if (_formValues.type === "squaremeter") {
//             if (_formValues.ratepersquaremeter === ""  && _formValues.totalsquaremeter === "") {
//                 alert.error("The rate per square meter is missing and total square meter is missing");
//                 return false;
//             }
//         }
//         else {
//             if (_formValues.fixed_price == "") {
//                 alert.error("The job price is missing");
//                 return false;
//             }
//         }

//         if (_formValues.frequency == "" || _formValues.frequency == 0) {
//             alert.error("The frequency is not selected");
//             return false;
//         } else {
//         }

//         let workerIssue = true;
//         for (let index = 0; index < _formValues.workers.length; index++) {
//             const _worker = _formValues.workers[index];

//             if (_worker.jobHours == "") {
//                 alert.error("The job hours value is missing");
//                 workerIssue = false;
//                 break;
//             }
//         }

//         if (!workerIssue) {
//             return workerIssue;
//         }

//         return true;
//     };

//     const handleGetSubServices = async (id) => {
//         try {
//             const res = await axios.get(`/api/admin/get-sub-services/${id}`, { headers });
//             setSubData(res.data.subServices || []);
//         } catch (error) {
//             console.log("Error fetching sub-services:", error);
//         }
//     };


//     const selectedOptions = transformedSubData.filter(option => selectedSubServices.includes(option.value));

//     const handleSubServices = (selectedOptions) => {
//         const selectedValues = selectedOptions ? selectedOptions.map(option => option.value) : [];
//         setSelectedSubServices(selectedValues);

//         setOfferServiceTmp((prevState) => ({
//             ...prevState,
//             sub_services: selectedValues
//         }));
//     };

//     const handleWorkerForm = (index, tmpvalue) => {
//         const _workers = offerServiceTmp.workers.map((worker, wIndex) => {
//             if (wIndex == index) {
//                 return tmpvalue;
//             }
//             return worker;
//         });

//         setOfferServiceTmp({
//             ...offerServiceTmp,
//             workers: _workers,
//         });
//     };

//     const handleInputChange = (e) => {
//         let newFormValues = { ...offerServiceTmp };

//         newFormValues[e.target.name] = e.target.value;
//         if (e.target.name == "service") {
//             const _selectedServiceOption =
//                 e.target.options[e.target.selectedIndex];

//             newFormValues["name"] = _selectedServiceOption.getAttribute("name");
//             newFormValues["template"] =
//                 _selectedServiceOption.getAttribute("template");
//         }

//         if (e.target.name == "frequency") {
//             const _selectedFrequencyOption =
//                 e.target.options[e.target.selectedIndex];

//             newFormValues["freq_name"] =
//                 _selectedFrequencyOption.getAttribute("name");
//             newFormValues["cycle"] =
//                 _selectedFrequencyOption.getAttribute("cycle");
//             newFormValues["period"] =
//                 _selectedFrequencyOption.getAttribute("period");
//         }

//         setOfferServiceTmp({ ...newFormValues });
//     };

//     const handleSubmit = () => {
//         let hasError = false;
//         const valid = checkValidation(offerServiceTmp);
//         if (!valid) {
//             hasError = true;
//         }
//         if (!hasError) {
//             handleSaveForm(isAdd ? -1 : editIndex, offerServiceTmp);
//             setIsOpen(false);
//         }
//     };

//     return (
//         <Modal
//             size="lg"
//             className="modal-container"
//             show={isOpen}
//             onHide={() => {
//                 setIsOpen(false);
//             }}
//         >
//             <Modal.Header closeButton>
//                 <Modal.Title>
//                     {isAdd ? t("global.addService") : (t("admin.global.Edit") + t("global.service"))}
//                 </Modal.Title>
//             </Modal.Header>

//             <Modal.Body>
//             <div className="row">
//                     <div className="col-sm-12">
//                         <div className="form-group">
//                             <label className="control-label">{t("client.jobs.change.property")}</label>
//                             <select
//                                 className="form-control"
//                                 name="address"
//                                 value={offerServiceTmp.address || ""}
//                                 onChange={(e) => {
//                                     handleInputChange(e);
//                                 }}
//                             >
//                                 <option value="">{t("admin.leads.AddLead.AddLeadClient.JobModal.pleaseSelect")}</option>
//                                 {addresses.map((address, i) => (
//                                     <option value={address.id} key={i}>
//                                         {address.address_name}
//                                     </option>
//                                 ))}
//                             </select>
//                         </div>
//                     </div>
//                 </div>

//                 <div className="align-items-center mb-3">
//                     <h4>{t("global.workers")}</h4>
//                     <label htmlFor="noOfWrkers">{t("global.noOfWorker")} : </label>
//                     <input
//                         type="number"
//                         min={1}
//                         className="form-control w-25"
//                         id="noOfWrkers"
//                         defaultValue={offerServiceTmp.workers.length}
//                         onChange={handleChangeWorkerCount}
//                     />
//                 </div>

//                 <div className="row">
//                     <div className="col-sm-12">
//                         <div className="form-group">
//                             <label className="control-label">{t("global.service")}</label>
//                             <select
//                                 name="service"
//                                 className="form-control"
//                                 value={offerServiceTmp.service || 0}
//                                 onChange={(e) => {
//                                     handleInputChange(e);
//                                     if (e.target.value === "10") {
//                                         setToggleOtherService(true);
//                                     } else if (e.target.value === '29') {
//                                         setToggleAirbnbService(true);
//                                         handleGetSubServices(29);
//                                     } else {
//                                         setToggleAirbnbService(false);
//                                         setToggleOtherService(false);
//                                     }
//                                 }}
//                             >
//                                 <option value={0}> {t("admin.leads.AddLead.AddLeadClient.JobModal.pleaseSelect")}</option>
//                                 {services.map((s, i) => {
//                                     return (
//                                         <option
//                                             name={s.name}
//                                             template={s.template}
//                                             value={s.id}
//                                             key={i}
//                                         >
//                                             {" "}
//                                             {s.name}{" "}
//                                         </option>
//                                     );
//                                 })}

//                             </select>
//                         </div>
//                         {toggleAirbnbService && (
//                             <div className="form-group">
//                                 <label className="control-label">Sub-Service</label>
//                                 <Select
//                                     value={selectedOptions}
//                                     name="subService"
//                                     isMulti
//                                     options={transformedSubData}
//                                     className="basic-multi-select"
//                                     isClearable={true}
//                                     placeholder="-- Please select --"
//                                     classNamePrefix="select"
//                                     onChange={(selectedOptions) => {
//                                         handleSubServices(selectedOptions);
//                                         const event = {
//                                             target: {
//                                                 name: 'subService',
//                                                 value: selectedOptions ? selectedOptions.map(option => option.value) : []
//                                             }
//                                         };
//                                         handleInputChange(event);
//                                     }}
//                                 />
//                             </div>
//                         )}
//                         {toggleOtherService && (
//                             <div className="form-group">
//                                 <textarea
//                                     type="text"
//                                     name="other_title"
//                                     id={`other_title`}
//                                     placeholder="Service Title"
//                                     className="form-control"
//                                     value={offerServiceTmp.other_title || ""}
//                                     onChange={(e) => handleInputChange(e)}
//                                 />
//                             </div>
//                         )}
//                     </div>
//                 </div>

//                 <div className="row">
//                     <div className="col-sm-12">
//                         <div className="form-group">
//                             <label className="control-label">{t("client.offer.view.frequency")}</label>
//                             <select
//                                 name="frequency"
//                                 className="form-control mb-2"
//                                 value={offerServiceTmp.frequency || 0}
//                                 onChange={(e) => handleInputChange(e)}
//                             >
//                                 <option value={0}> {t("admin.leads.AddLead.AddLeadClient.JobModal.pleaseSelect")}</option>
//                                 {frequencies.map((s, i) => {
//                                     return (
//                                         <option
//                                             cycle={s.cycle}
//                                             period={s.period}
//                                             name={s.name}
//                                             value={s.id}
//                                             key={i}
//                                         >
//                                             {" "}
//                                             {s.name}{" "}
//                                         </option>
//                                     );
//                                 })}
//                             </select>
//                         </div>
//                     </div>
//                 </div>

//                 <div className="row">
//                     <div className="col-sm-4">
//                         <div className="form-group">
//                             <label className="control-label">{t("price_offer.type")}</label>
//                             <select
//                                 name="type"
//                                 className="form-control"
//                                 value={offerServiceTmp.type}
//                                 onChange={(e) => {
//                                     handleInputChange(e);
//                                 }}
//                             >
//                                 <option value="fixed">{t("admin.leads.AddLead.Options.Type.Fixed")}</option>
//                                 <option value="hourly">{t("admin.leads.AddLead.Options.Type.Hourly")}</option>
//                                 <option value="squaremeter">{t("admin.leads.AddLead.Options.Type.Squaremeter")}</option>
//                             </select>
//                         </div>
//                     </div>
//                     <div className="col-sm-4">
//                         <div className="form-group">
//                             <label className="control-label">{t("admin.leads.AddLead.AddLeadClient.jobMenu.Price")}</label>
//                             {offerServiceTmp.type === "fixed" && (
//                                 <input
//                                     type="number"
//                                     name="fixed_price"
//                                     value={offerServiceTmp.fixed_price || ""}
//                                     onChange={handleInputChange}
//                                     className="form-control jobprice"
//                                     required
//                                     placeholder="Enter job price"
//                                 />
//                             )}

//                             {offerServiceTmp.type === "hourly" && (
//                                 <input
//                                     type="number"
//                                     name="rateperhour"
//                                     value={offerServiceTmp.rateperhour || ""}
//                                     onChange={handleInputChange}
//                                     className="form-control jobprice"
//                                     required
//                                     placeholder="Enter rate P/Hour"
//                                 />
//                             )}
//                             {offerServiceTmp.type === "squaremeter" && (
//                                 <input
//                                     type="number"
//                                     name="ratepersquaremeter"
//                                     value={offerServiceTmp.ratepersquaremeter || ""}
//                                     onChange={handleInputChange}
//                                     className="form-control p-2"
//                                     required
//                                     placeholder="Enter rate P/Square meter"
//                                 />
//                             )}
//                         </div>
//                     </div>
//                     <div className="col-sm-4">
//                         <div className="form-group">
//                             {offerServiceTmp.type === "squaremeter" && (
//                                 <div className="flex-1">
//                                     <label className="control-label">Total Square Meter</label>
//                                     <input
//                                         type="number"
//                                         name="totalsquaremeter"
//                                         value={offerServiceTmp.totalsquaremeter || ""}
//                                         onChange={handleInputChange}
//                                         className="form-control p-2"
//                                         required
//                                         placeholder="Total Square meter"
//                                     />
//                                 </div>
//                             )}
//                         </div>  
//                     </div>
//                 </div>

//                 <div className="bg-dark text-white py-2 px-2 mb-2">
//                     <strong>{t("admin.sidebar.workers")}</strong>
//                 </div>

//                 {offerServiceTmp.workers.map((worker, _index) => (
//                     <WorkerForm
//                         workerFormValues={worker}
//                         handleTmpValue={handleWorkerForm}
//                         index={_index}
//                         key={_index}
//                     />
//                 ))}
//             </Modal.Body>

//             <Modal.Footer>
//                 <Button
//                     type="button"
//                     className="btn btn-secondary"
//                     onClick={() => {
//                         setIsOpen(false);
//                     }}
//                 >
//                     {t("modal.close")}
//                 </Button>
//                 <Button
//                     type="button"
//                     onClick={handleSubmit}
//                     className="btn btn-primary"
//                 >
//                     {t("modal.save")}
//                 </Button>
//             </Modal.Footer>
//         </Modal>
//     );
// }

// function WorkerForm({ workerFormValues, handleTmpValue, index }) {
//     const { t } = useTranslation();
//     const handleInputChange = (e) => {
//         let newFormValues = { ...workerFormValues };

//         newFormValues[e.target.name] = e.target.value;

//         handleTmpValue(index, newFormValues);
//     };

//     return (
//         <div className="row">
//             <div className="col-sm-auto text-right pt-2">
//                 <strong>{t("global.worker")} {index + 1}</strong>
//             </div>

//             <div className="col-sm-4">
//                 <div className="form-group">
//                     <input
//                         type="number"
//                         name="jobHours"
//                         value={workerFormValues.jobHours || ""}
//                         onChange={(e) => handleInputChange(e)}
//                         className="form-control jobhr"
//                         required
//                         placeholder="Enter job Hrs"
//                     />
//                 </div>
//             </div>
//         </div>
//     );
// }


import { useEffect, useState, useMemo } from "react";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";
import Select from "react-select";
import { useTranslation } from "react-i18next";
import { FaTrashAlt } from "react-icons/fa";

const initialValues = {
    service: "",
    name: "",
    type: "fixed",
    freq_name: "",
    frequency: "",
    fixed_price: "",
    rateperhour: "",
    ratepersquaremeter: "",
    totalsquaremeter: "",
    other_title: "",
    template: "",
    cycle: "",
    period: "",
    address: "",
    weekdays: [],
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
    isAdd,
    editIndex,
}) {
    const { t } = useTranslation();
    const alert = useAlert();
    // const [offerServiceTmp, setOfferServiceTmp] = useState(
    //     isAdd ? [initialValues] : tmpFormValues || []
    // );

    const [offerServiceTmp, setOfferServiceTmp] = useState(
        isAdd ? [initialValues] : tmpFormValues && Array.isArray(tmpFormValues) ? tmpFormValues : [initialValues]
    );

    useEffect(() => {
        if (!isAdd && Array.isArray(tmpFormValues) && tmpFormValues.length > 0) {
            setOfferServiceTmp(tmpFormValues);
        } else if (!isAdd && tmpFormValues && !Array.isArray(tmpFormValues)) {
            setOfferServiceTmp([tmpFormValues]);
        }
    }, [isAdd, tmpFormValues]);

    const [toggleOtherService, setToggleOtherService] = useState([]);

    const [toggleAirbnbService, setToggleAirbnbService] = useState(false);
    const [selectedSubServices, setSelectedSubServices] = useState([]);
    const [subData, setSubData] = useState([]);
    const adminlng = localStorage.getItem("admin-lng");

    const transformedSubData = subData.map((s) => ({
        value: s.id,
        label: adminlng === "en" ? s.name_en : s.name_heb,
    }));

    console.log(toggleOtherService);


    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    // useEffect(() => {
    //     if (offerServiceTmp[0].service === "29") {
    //         setToggleAirbnbService(true);
    //         handleGetSubServices(29);
    //     } else if (offerServiceTmp[0].service === "10") {
    //         console.log(offerServiceTmp);

    //         setToggleOtherService(true);
    //     }
    // }, [offerServiceTmp]);

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

    const selectedOptions = transformedSubData.filter((option) =>
        selectedSubServices.includes(option.value)
    );

    const handleSubServices = (selectedOptions) => {
        const selectedValues = selectedOptions ? selectedOptions.map((option) => option.value) : [];
        setSelectedSubServices(selectedValues);

        setOfferServiceTmp((prevState) =>
            prevState.map((service) => ({
                ...service,
                sub_services: selectedValues,
            }))
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
        const valid = offerServiceTmp.every((formValues) => checkValidation(formValues));
        if (valid) {
            handleSaveForm(isAdd ? -1 : editIndex, offerServiceTmp);
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

                {/* {offerServiceTmp.map((service, index) => (
                    <div key={index}>
                        <div className="d-flex justify-content-between align-items-center mb-3">
                            <h5>{t("global.service")} {index + 1}</h5>
                    
                            {offerServiceTmp.length > 1 && index !== offerServiceTmp.length - 1 && (
                                <Button
                                    className="navyblue delete-btn"
                                    onClick={() => handleDeleteService(index)}
                                >
                                    <FaTrashAlt />
                                </Button>
                            )}
                        </div>
    
                        <div className="row mb-3">
                            <div className="col-sm-6">
                                <div className="form-group">
                                    <label className="control-label">{t("global.service")}</label>
                                    <select
                                        name="service"
                                        className="form-control"
                                        value={service.service || ""}
                                        onChange={(e) => {
                                            handleServiceChange(index, e);
                                            if (e.target.value === "10") {
                                                setToggleOtherService(true);
                                            } else if (e.target.value === "29") {
                                                setToggleAirbnbService(true);
                                                handleGetSubServices(29);
                                            } else {
                                                setToggleAirbnbService(false);
                                                setToggleOtherService(false);
                                            }
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

                            <div className="col-sm-6">
                                <div className="form-group">
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

                        <div className="row mb-3">
                            <div className="col-sm-6">
                                <div className="form-group">
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

                            <div className="col-sm-6">
                                <div className="form-group">
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

                        {service.type === "squaremeter" && (
                            <div className="row mb-3">
                                <div className="col-sm-12">
                                    <div className="form-group">
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

                        {toggleAirbnbService && (
                            <div className="row mb-3">
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">{t("price_offer.subservice")}</label>
                                        <Select
                                            options={transformedSubData}
                                            isMulti
                                            value={selectedOptions}
                                            onChange={handleSubServices}
                                            placeholder={t("client.jobs.change.select_subservices")}
                                        />
                                    </div>
                                </div>
                            </div>
                        )}

                        <div>
                            <div className="mb-3">
                                <h5>{t("global.workers")}</h5>
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
                    </div>
                ))} */}

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

                                                if (e.target.value === "10") {
                                                    updatedToggleState[index] = true;
                                                } else {
                                                    updatedToggleState[index] = false;
                                                }

                                                setToggleOtherService(updatedToggleState);

                                                if (e.target.value === "29") {
                                                    setToggleAirbnbService(true);
                                                    handleGetSubServices(29);
                                                } else {
                                                    setToggleAirbnbService(false);
                                                }
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

                            {toggleAirbnbService && (
                                <div className="row mb-1">
                                    <div className="col-sm-12">
                                        <div className="form-group m-0">
                                            <label className="control-label">{t("price_offer.subservice")}</label>
                                            <Select
                                                options={transformedSubData}
                                                isMulti
                                                value={selectedOptions}
                                                onChange={handleSubServices}
                                                placeholder={t("client.jobs.change.select_subservices")}
                                            />
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
