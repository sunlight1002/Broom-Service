import { useRef, useState, memo, useEffect } from "react";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import OfferServiceModal from "../../Components/Modals/OfferServiceModal";
import { useParams } from "react-router-dom";
import { useTranslation } from "react-i18next";

const initialValue = {
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
    rateperhour: "",
    ratepersquaremeter: "",
    totalsquaremeter: "",
    other_title: "",
    template: "",
    cycle: "",
    period: "",
    address: "",
    start_date: "",
    weekdays: [],
    weekday_occurrence: "1",
    weekday: "sunday",
    month_occurrence: 1,
    month_date: 1,
    monthday_selection_type: "weekday",
    workers: [{ jobHours: "" }],
};

const workerJobHours = (_service) => {
    // Flatten the workers array to join all jobHours in a string
    if (_service.workers && _service.workers.length > 0) {
        return _service.workers.map((w) => w.jobHours).join(", ");
    }
    return "-";
};

const OfferServiceMenu = memo(function OfferServiceMenu({
    addresses,
    services,
    frequencies,
    formValues,
    handleSaveForm,
    handleRemoveFormFields,
}) {
    const urlPath = window.location.pathname;

    const { t } = useTranslation();

    const jobActions = [
        {
            key: "edit",
            label: t("admin.global.Edit"),
        },
        {
            key: "delete",
            label: t("admin.global.Delete"),
        },
    ];

    let isAdd = useRef(true);
    let indexRef = useRef();
    const [isOpen, setIsOpen] = useState(false);
    const [tmpFormValues, setTmpFormValues] = useState([initialValue]);
    let param = useParams();

    const handleAddService = () => {
        if (!addresses.length) {
            alert("Please add property address for the client");
            return;
        }
        setTmpFormValues(initialValue);
        isAdd.current = true;
        setIsOpen(true);
    };

    const calcPrice = (_service) => {
        if (_service.workers) {
            if (_service.type === "hourly") {
                const _totalHours = _service.workers
                    .map((w) => parseInt(w.jobHours))
                    .reduce((a, b) => a + b, 0);
                return _service.rateperhour * _totalHours;

            } else if (_service.type === "squaremeter") {
                if (_service.ratepersquaremeter && _service.totalsquaremeter) {
                    return _service.ratepersquaremeter * _service.totalsquaremeter;
                } else {
                    return "-";
                }

            } else {
                return _service.fixed_price;
            }
        } else {
            return "-";
        }
    };


    return (
        <div>
            <div className="text-right" style={{ marginBottom: "5px" }}>
                {
                    !urlPath.includes("edit") && (
                        <button
                            type="button"
                            onClick={handleAddService}
                            className="btn btn-success"
                        >
                            + {t("global.addService")}
                        </button>
                    )
                }
            </div>
            <div className="table-responsive">
                {formValues.length > 0 ? (
                    <Table className="table table-bordered">
                        <Thead>
                            <Tr>
                                <Th>{t("price_offer.address_text")}</Th>
                                <Th>{t("global.addService")}</Th>
                                <Th>{t("price_offer.type")}</Th>
                                <Th>{t("global.noOfWorker")}</Th>
                                <Th>{t("price_offer.job_h_txt")}</Th>
                                <Th>{t("admin.leads.AddLead.AddLeadClient.jobMenu.Price")}</Th>
                                <Th>{t("admin.leads.AddLead.AddLeadClient.jobMenu.Frequency")}</Th>
                                <Th>{t("admin.leads.AddLead.AddLeadClient.jobMenu.Actions")}</Th>
                            </Tr>
                        </Thead>
                        <Tbody>
                            {formValues.map((item, innerIndex) => {

                                const address =
                                    item.template === "airbnb"
                                        ? (item?.sub_services?.address_name + " " + addresses.find(
                                            (a) => a.id.toString() === item?.sub_services?.address?.toString()
                                        )?.geo_address) ?? ""
                                        : (item?.address_name + " " + addresses.find(
                                            (a) => a.id.toString() === item.address?.toString()
                                        )?.geo_address) ?? "";

                                const serviceName = item?.template === "airbnb"
                                    ? `${item?.name} (${item?.sub_services?.sub_service_name || ""})`
                                    : item?.name;

                                return (
                                    <Tr key={innerIndex}>
                                        <Td>{address || "NA"}</Td>
                                        <Td>{serviceName}</Td>
                                        <Td>{item.type}</Td>
                                        <Td>{item.workers ? item.workers.length : 0}</Td>
                                        <Td>{workerJobHours(item)}</Td>
                                        <Td>{calcPrice(item)}</Td>
                                        <Td>{item.freq_name}</Td>
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
                                                    {jobActions.map((menu) => (
                                                        <button
                                                            type="button"
                                                            className="dropdown-item"
                                                            key={menu.key}
                                                            onClick={(e) => {
                                                                e.preventDefault();
                                                                if (menu.key === "edit") {
                                                                    indexRef.current = innerIndex;
                                                                    isAdd.current = false;
                                                                    setTmpFormValues(formValues);
                                                                    setIsOpen(true);
                                                                } else {
                                                                    handleRemoveFormFields(innerIndex);
                                                                }
                                                            }}
                                                        >
                                                            {menu.label}
                                                        </button>
                                                    ))}
                                                </div>
                                            </div>
                                        </Td>
                                    </Tr>
                                );
                            })}
                        </Tbody>

                    </Table>
                ) : (
                    <p className="text-center mt-5">{"Services not found!"}</p>
                )}
            </div>
            {isOpen && (
                <OfferServiceModal
                    setIsOpen={setIsOpen}
                    formValues={formValues}
                    isOpen={isOpen}
                    addresses={addresses}
                    services={services}
                    frequencies={frequencies}
                    tmpFormValues={tmpFormValues}
                    handleTmpValue={setTmpFormValues}
                    handleSaveForm={handleSaveForm}
                    isAdd={isAdd.current}
                    editIndex={indexRef.current}
                />
            )}
        </div>
    );
});

export { workerJobHours };
export default OfferServiceMenu;
